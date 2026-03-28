<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

function slugify_eq_cam_name(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-') !== '' ? trim($value, '-') : 'cam';
}

function eq_distance_km(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $earthRadiusKm = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(max(0.0, 1 - $a)));
    return $earthRadiusKm * $c;
}

function load_recent_earthquakes(string $path): array
{
    $payload = read_json_file($path);
    if (!is_array($payload) || !isset($payload['events']) || !is_array($payload['events'])) {
        return [];
    }
    return $payload['events'];
}

function earthquake_cam_priority(array $cam, array $events, int $now): array
{
    $score = 10;
    $reasons = [];

    $hasEmbed = !empty($cam['embed_url']);
    $hasSnapshot = !empty($cam['snapshot_url']);

    if ($hasEmbed) {
        $score += 12;
        $reasons[] = 'Inline live player';
    } elseif ($hasSnapshot) {
        $score += 7;
        $reasons[] = 'Direct snapshot available';
    } else {
        $reasons[] = 'External-only source';
    }

    $lat = isset($cam['latitude']) && is_numeric($cam['latitude']) ? (float) $cam['latitude'] : null;
    $lon = isset($cam['longitude']) && is_numeric($cam['longitude']) ? (float) $cam['longitude'] : null;
    if ($lat === null || $lon === null) {
        return [
            'score' => $score,
            'reasons' => $reasons,
            'nearby_events' => 0,
            'close_events' => 0,
            'max_magnitude' => 0.0,
        ];
    }

    $nearbyEvents = 0;
    $closeEvents = 0;
    $maxMagnitude = 0.0;
    $freshWithin6h = 0;

    foreach ($events as $event) {
        if (!is_array($event)) {
            continue;
        }

        $evLat = isset($event['latitude']) && is_numeric($event['latitude']) ? (float) $event['latitude'] : null;
        $evLon = isset($event['longitude']) && is_numeric($event['longitude']) ? (float) $event['longitude'] : null;
        $magnitude = isset($event['magnitude']) && is_numeric($event['magnitude']) ? (float) $event['magnitude'] : 0.0;
        if ($evLat === null || $evLon === null) {
            continue;
        }

        $distanceKm = eq_distance_km($lat, $lon, $evLat, $evLon);
        if ($distanceKm <= 450.0) {
            $nearbyEvents += 1;
            if ($magnitude > $maxMagnitude) {
                $maxMagnitude = $magnitude;
            }

            if ($distanceKm <= 180.0) {
                $closeEvents += 1;
            }

            $eventTs = isset($event['event_time_utc']) ? strtotime((string) $event['event_time_utc']) : 0;
            if (is_int($eventTs) && $eventTs > 0 && ($now - $eventTs) <= 21600) {
                $freshWithin6h += 1;
            }
        }
    }

    if ($nearbyEvents > 0) {
        $score += min(20, $nearbyEvents * 2);
        $reasons[] = "{$nearbyEvents} nearby events";
    }

    if ($closeEvents > 0) {
        $score += min(15, $closeEvents * 3);
        $reasons[] = "{$closeEvents} close-range events";
    }

    if ($maxMagnitude >= 6.0) {
        $score += 28;
        $reasons[] = 'Nearby M6+ signal';
    } elseif ($maxMagnitude >= 5.0) {
        $score += 18;
        $reasons[] = 'Nearby M5+ signal';
    } elseif ($maxMagnitude >= 4.0) {
        $score += 10;
        $reasons[] = 'Nearby M4+ signal';
    } elseif ($maxMagnitude >= 3.0) {
        $score += 5;
        $reasons[] = 'Nearby felt-level signal';
    }

    if ($freshWithin6h > 0) {
        $score += 10;
        $reasons[] = 'Updated in last 6h';
    }

    return [
        'score' => $score,
        'reasons' => $reasons,
        'nearby_events' => $nearbyEvents,
        'close_events' => $closeEvents,
        'max_magnitude' => $maxMagnitude,
    ];
}

$cachePath = $appConfig['data_dir'] . '/earthquake_cams_latest.json';
$now = time();
$cacheTtl = (int) $appConfig['cache_ttl_seconds'];
$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';

$cachedPayload = read_json_file($cachePath);
$cacheAge = is_array($cachedPayload) && isset($cachedPayload['generated_at_ts'])
    ? $now - (int) $cachedPayload['generated_at_ts']
    : null;

if (!$forceRefresh && is_array($cachedPayload) && is_int($cacheAge) && $cacheAge <= $cacheTtl) {
    $cachedPayload['from_cache'] = true;
    $cachedPayload['stale_cache'] = !is_int($cacheAge) || $cacheAge > $cacheTtl;
    json_response(200, $cachedPayload);
}

$provider = (string) ($feedConfig['earthquake_cams']['provider'] ?? 'Curated Seismic Region Cameras');
$items = $feedConfig['earthquake_cams']['items'] ?? [];
$events = load_recent_earthquakes($appConfig['data_dir'] . '/earthquakes_latest.json');

$cams = [];
$countries = [];
$withSnapshots = 0;

if (is_array($items)) {
    foreach ($items as $row) {
        if (!is_array($row)) {
            continue;
        }

        $name = trim((string) ($row['name'] ?? ''));
        $region = trim((string) ($row['region'] ?? $name));
        $country = trim((string) ($row['country'] ?? 'Unknown'));
        $source = trim((string) ($row['source'] ?? 'Unknown source'));
        $streamUrl = trim((string) ($row['stream_url'] ?? ''));
        $embedUrl = array_key_exists('embed_url', $row)
            ? trim((string) $row['embed_url'])
            : '';
        $snapshotUrl = isset($row['snapshot_url']) ? trim((string) $row['snapshot_url']) : '';
        $status = trim((string) ($row['status'] ?? 'Seismic watch'));

        if ($name === '' || $streamUrl === '') {
            continue;
        }

        $latitude = isset($row['latitude']) && is_numeric($row['latitude']) ? (float) $row['latitude'] : null;
        $longitude = isset($row['longitude']) && is_numeric($row['longitude']) ? (float) $row['longitude'] : null;

        $hasSnapshot = $snapshotUrl !== '';
        if ($hasSnapshot) {
            $withSnapshots += 1;
        }

        $countries[$country] = true;

        $priority = earthquake_cam_priority([
            'embed_url' => $embedUrl,
            'snapshot_url' => $snapshotUrl,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ], $events, $now);

        $cams[] = [
            'id' => slugify_eq_cam_name($name . '-' . $country),
            'name' => $name,
            'region' => $region,
            'country' => $country,
            'source' => $source,
            'status' => $status,
            'stream_url' => $streamUrl,
            'embed_url' => $embedUrl !== '' ? $embedUrl : null,
            'snapshot_url' => $hasSnapshot ? $snapshotUrl : null,
            'snapshot_fallback_available' => $hasSnapshot,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'priority_score' => (int) $priority['score'],
            'priority_reasons' => $priority['reasons'],
            'nearby_events' => (int) $priority['nearby_events'],
            'close_events' => (int) $priority['close_events'],
            'max_nearby_magnitude' => round((float) $priority['max_magnitude'], 1),
        ];
    }
}

usort($cams, static function (array $a, array $b): int {
    if (($b['priority_score'] ?? 0) !== ($a['priority_score'] ?? 0)) {
        return (int) $b['priority_score'] <=> (int) $a['priority_score'];
    }
    return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
});

$hotNow = array_values(array_filter($cams, static function (array $cam): bool {
    $score = (int) ($cam['priority_score'] ?? 0);
    $nearby = (int) ($cam['nearby_events'] ?? 0);
    $close = (int) ($cam['close_events'] ?? 0);
    $maxMag = (float) ($cam['max_nearby_magnitude'] ?? 0.0);

    // Very strict "hot now": strong score + clear seismic stress signal.
    if ($score < 42) {
        return false;
    }

    return $maxMag >= 4.6
        || ($maxMag >= 4.2 && $close >= 3)
        || ($maxMag >= 4.0 && $close >= 6)
        || ($maxMag >= 3.8 && $close >= 12 && $nearby >= 45);
}));

if ($hotNow === []) {
    $hotNow = array_values(array_filter($cams, static function (array $cam): bool {
        $score = (int) ($cam['priority_score'] ?? 0);
        $nearby = (int) ($cam['nearby_events'] ?? 0);
        $maxMag = (float) ($cam['max_nearby_magnitude'] ?? 0.0);
        return $score >= 36 && $maxMag >= 4.0 && $nearby >= 5;
    }));
}

if ($hotNow === []) {
    $hotNow = array_slice($cams, 0, min(4, count($cams)));
}

$hotIds = array_fill_keys(array_map(static fn(array $cam): string => (string) $cam['id'], $hotNow), true);
$rotatingCandidates = array_values(array_filter($cams, static function (array $cam) use ($hotIds): bool {
    return !isset($hotIds[(string) $cam['id']]);
}));

$payload = [
    'ok' => true,
    'provider' => $provider,
    'generated_at_ts' => $now,
    'generated_at' => gmdate('c', $now),
    'cams_count' => count($cams),
    'countries_count' => count($countries),
    'snapshot_enabled_count' => $withSnapshots,
    'cams' => $cams,
    'hot_now' => $hotNow,
    'rotating_candidates' => $rotatingCandidates,
    'rotation_interval_seconds' => 20,
    'from_cache' => false,
];

if (!write_json_file($cachePath, $payload)) {
    write_log($appConfig['logs_dir'], 'Failed writing earthquake cams cache JSON');
}

json_response(200, $payload);
