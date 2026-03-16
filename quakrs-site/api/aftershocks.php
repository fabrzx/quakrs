<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

const AFTERSHOCK_TRIGGER_MAGNITUDE = 6.0;
const AFTERSHOCK_RADIUS_KM = 150.0;
const AFTERSHOCK_WINDOW_HOURS = 168;
const AFTERSHOCK_MIN_MAGNITUDE = 1.0;
const AFTERSHOCK_MAX_SEQUENCES = 24;
const AFTERSHOCK_MAX_EVENTS_PER_SEQUENCE = 600;

function quake_time_ts(array $event): int
{
    if (!isset($event['event_time_utc']) || !is_string($event['event_time_utc']) || trim($event['event_time_utc']) === '') {
        return 0;
    }
    $ts = strtotime((string) $event['event_time_utc']);
    return is_int($ts) ? $ts : 0;
}

function quake_mag(array $event): ?float
{
    if (!isset($event['magnitude']) || !is_numeric($event['magnitude'])) {
        return null;
    }
    return (float) $event['magnitude'];
}

function quake_lat(array $event): ?float
{
    if (!isset($event['latitude']) || !is_numeric($event['latitude'])) {
        return null;
    }
    return (float) $event['latitude'];
}

function quake_lon(array $event): ?float
{
    if (!isset($event['longitude']) || !is_numeric($event['longitude'])) {
        return null;
    }
    return (float) $event['longitude'];
}

function quake_key(array $event): string
{
    $id = isset($event['id']) ? trim((string) $event['id']) : '';
    if ($id !== '') {
        return 'id:' . $id;
    }

    $lat = quake_lat($event);
    $lon = quake_lon($event);
    $mag = quake_mag($event);
    $time = isset($event['event_time_utc']) ? (string) $event['event_time_utc'] : '';

    return implode('|', [
        't:' . $time,
        'lat:' . ($lat !== null ? number_format($lat, 4, '.', '') : 'na'),
        'lon:' . ($lon !== null ? number_format($lon, 4, '.', '') : 'na'),
        'm:' . ($mag !== null ? number_format($mag, 1, '.', '') : 'na'),
    ]);
}

function quake_public_payload(array $event): array
{
    return [
        'id' => isset($event['id']) ? (string) $event['id'] : '',
        'key' => quake_key($event),
        'place' => isset($event['place']) ? (string) $event['place'] : 'Unknown location',
        'magnitude' => quake_mag($event),
        'depth_km' => isset($event['depth_km']) && is_numeric($event['depth_km']) ? (float) $event['depth_km'] : null,
        'latitude' => quake_lat($event),
        'longitude' => quake_lon($event),
        'event_time_utc' => isset($event['event_time_utc']) ? (string) $event['event_time_utc'] : null,
        'source_url' => isset($event['source_url']) ? (string) $event['source_url'] : '',
        'source_provider' => isset($event['source_provider']) ? (string) $event['source_provider'] : '',
    ];
}

function haversine_distance_km_simple(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $earthRadiusKm = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadiusKm * $c;
}

function sort_events_desc_by_time(array &$events): void
{
    usort($events, static function (array $a, array $b): int {
        return quake_time_ts($b) <=> quake_time_ts($a);
    });
}

function hydrate_stats(array $sequence, int $nowTs): array
{
    $aftershocks = isset($sequence['aftershocks']) && is_array($sequence['aftershocks']) ? $sequence['aftershocks'] : [];
    $total = count($aftershocks);
    $last24hCutoff = $nowTs - 86400;
    $last24hCount = 0;
    $strongest = null;
    $latestTs = 0;

    foreach ($aftershocks as $row) {
        if (!is_array($row)) {
            continue;
        }
        $mag = isset($row['magnitude']) && is_numeric($row['magnitude']) ? (float) $row['magnitude'] : null;
        $ts = isset($row['event_time_utc']) && is_string($row['event_time_utc']) ? strtotime($row['event_time_utc']) : false;
        if (is_int($ts)) {
            if ($ts >= $last24hCutoff) {
                $last24hCount++;
            }
            if ($ts > $latestTs) {
                $latestTs = $ts;
            }
        }
        if ($mag !== null && ($strongest === null || $mag > $strongest)) {
            $strongest = $mag;
        }
    }

    $mainshockTs = isset($sequence['mainshock_ts']) ? (int) $sequence['mainshock_ts'] : 0;
    $windowHours = isset($sequence['window_hours']) ? max(1, (int) $sequence['window_hours']) : AFTERSHOCK_WINDOW_HOURS;
    $windowEndTs = $mainshockTs + ($windowHours * 3600);
    $elapsedHours = max(1, min($nowTs, $windowEndTs) - $mainshockTs) / 3600;
    $status = $nowTs <= $windowEndTs ? 'active' : 'expired';

    $sequence['status'] = $status;
    $sequence['expires_at_ts'] = $windowEndTs;
    $sequence['expires_at'] = gmdate('c', $windowEndTs);
    $sequence['aftershocks_count'] = $total;
    $sequence['aftershocks_24h_count'] = $last24hCount;
    $sequence['strongest_aftershock_magnitude'] = $strongest;
    $sequence['latest_aftershock_time_utc'] = $latestTs > 0 ? gmdate('c', $latestTs) : null;
    $sequence['aftershock_rate_per_hour'] = round($total / $elapsedHours, 3);

    return $sequence;
}

function create_sequence_from_mainshock(array $event, int $nowTs): array
{
    $mainshockKey = quake_key($event);
    $sequenceId = substr(hash('sha256', $mainshockKey), 0, 20);
    $mainshockTs = quake_time_ts($event);

    return [
        'sequence_id' => $sequenceId,
        'mainshock_key' => $mainshockKey,
        'mainshock' => quake_public_payload($event),
        'mainshock_ts' => $mainshockTs,
        'trigger_magnitude' => AFTERSHOCK_TRIGGER_MAGNITUDE,
        'radius_km' => AFTERSHOCK_RADIUS_KM,
        'window_hours' => AFTERSHOCK_WINDOW_HOURS,
        'min_aftershock_magnitude' => AFTERSHOCK_MIN_MAGNITUDE,
        'aftershocks' => [],
        'aftershock_keys' => [],
        'generated_at_ts' => $nowTs,
        'generated_at' => gmdate('c', $nowTs),
    ];
}

function sequence_file_path(string $dir, string $sequenceId): string
{
    return rtrim($dir, '/') . '/aftershocks_' . $sequenceId . '.json';
}

$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';
$querySequenceId = isset($_GET['sequence_id']) ? trim((string) $_GET['sequence_id']) : '';
$queryEventId = isset($_GET['event_id']) ? trim((string) $_GET['event_id']) : '';
$now = time();
$cacheTtl = max(30, (int) ($appConfig['cache_ttl_seconds'] ?? 90));
$indexPath = $appConfig['data_dir'] . '/aftershocks_index_latest.json';
$sequenceDir = $appConfig['data_dir'] . '/aftershocks';

if (!is_dir($sequenceDir)) {
    @mkdir($sequenceDir, 0775, true);
}

$cachedIndex = read_json_file($indexPath);
$cacheAge = is_array($cachedIndex) && isset($cachedIndex['generated_at_ts'])
    ? $now - (int) $cachedIndex['generated_at_ts']
    : null;

if (!$forceRefresh && is_array($cachedIndex) && is_int($cacheAge) && $cacheAge <= $cacheTtl) {
    if ($querySequenceId === '' && $queryEventId === '') {
        $cachedIndex['from_cache'] = true;
        json_response(200, $cachedIndex);
    }

    $sequenceSummary = null;
    if (isset($cachedIndex['sequences']) && is_array($cachedIndex['sequences'])) {
        foreach ($cachedIndex['sequences'] as $summary) {
            if (!is_array($summary)) {
                continue;
            }
            if ($querySequenceId !== '' && isset($summary['sequence_id']) && (string) $summary['sequence_id'] === $querySequenceId) {
                $sequenceSummary = $summary;
                break;
            }
            $mainshockId = isset($summary['mainshock']['id']) ? (string) $summary['mainshock']['id'] : '';
            if ($queryEventId !== '' && $mainshockId !== '' && $mainshockId === $queryEventId) {
                $sequenceSummary = $summary;
                break;
            }
        }
    }

    if (is_array($sequenceSummary) && isset($sequenceSummary['sequence_id'])) {
        $path = sequence_file_path($sequenceDir, (string) $sequenceSummary['sequence_id']);
        $detail = read_json_file($path);
        if (is_array($detail)) {
            $detail['from_cache'] = true;
            json_response(200, $detail);
        }
    }
}

$earthquakesPath = $appConfig['data_dir'] . '/earthquakes_latest.json';
$earthquakePayload = read_json_file($earthquakesPath);
$events = is_array($earthquakePayload) && isset($earthquakePayload['events']) && is_array($earthquakePayload['events'])
    ? $earthquakePayload['events']
    : [];
sort_events_desc_by_time($events);

if (count($events) === 0) {
    if (is_array($cachedIndex)) {
        $cachedIndex['from_cache'] = true;
        $cachedIndex['stale_cache'] = true;
        json_response(200, $cachedIndex);
    }
    json_response(502, [
        'ok' => false,
        'error' => 'Unable to load local earthquake feed for aftershock tracking',
    ]);
}

$sequencesByMainshockKey = [];

if (is_array($cachedIndex) && isset($cachedIndex['sequences']) && is_array($cachedIndex['sequences'])) {
    foreach ($cachedIndex['sequences'] as $summary) {
        if (!is_array($summary) || !isset($summary['sequence_id']) || !is_string($summary['sequence_id'])) {
            continue;
        }
        $path = sequence_file_path($sequenceDir, $summary['sequence_id']);
        $detail = read_json_file($path);
        if (!is_array($detail) || !isset($detail['mainshock_key']) || !is_string($detail['mainshock_key'])) {
            continue;
        }
        $detail = hydrate_stats($detail, $now);
        if (($detail['status'] ?? 'expired') !== 'active') {
            continue;
        }
        $sequencesByMainshockKey[$detail['mainshock_key']] = $detail;
    }
}

foreach ($events as $event) {
    if (!is_array($event)) {
        continue;
    }
    $magnitude = quake_mag($event);
    $eventTs = quake_time_ts($event);
    if ($magnitude === null || $eventTs <= 0 || $magnitude < AFTERSHOCK_TRIGGER_MAGNITUDE) {
        continue;
    }

    $mainshockKey = quake_key($event);
    if (isset($sequencesByMainshockKey[$mainshockKey])) {
        continue;
    }

    $sequence = create_sequence_from_mainshock($event, $now);
    $sequence = hydrate_stats($sequence, $now);
    if (($sequence['status'] ?? 'expired') === 'active') {
        $sequencesByMainshockKey[$mainshockKey] = $sequence;
    }
}

foreach ($sequencesByMainshockKey as $mainshockKey => $sequence) {
    $mainshock = isset($sequence['mainshock']) && is_array($sequence['mainshock']) ? $sequence['mainshock'] : [];
    $mainshockTs = isset($sequence['mainshock_ts']) ? (int) $sequence['mainshock_ts'] : quake_time_ts($mainshock);
    $windowHours = isset($sequence['window_hours']) ? max(1, (int) $sequence['window_hours']) : AFTERSHOCK_WINDOW_HOURS;
    $windowEndTs = $mainshockTs + ($windowHours * 3600);
    if ($mainshockTs <= 0 || $now > $windowEndTs) {
        unset($sequencesByMainshockKey[$mainshockKey]);
        continue;
    }

    $mainLat = quake_lat($mainshock);
    $mainLon = quake_lon($mainshock);
    if ($mainLat === null || $mainLon === null) {
        $mainLat = isset($sequence['mainshock']['latitude']) && is_numeric($sequence['mainshock']['latitude']) ? (float) $sequence['mainshock']['latitude'] : null;
        $mainLon = isset($sequence['mainshock']['longitude']) && is_numeric($sequence['mainshock']['longitude']) ? (float) $sequence['mainshock']['longitude'] : null;
    }
    if ($mainLat === null || $mainLon === null) {
        unset($sequencesByMainshockKey[$mainshockKey]);
        continue;
    }

    $knownKeys = [];
    if (isset($sequence['aftershock_keys']) && is_array($sequence['aftershock_keys'])) {
        foreach ($sequence['aftershock_keys'] as $k) {
            if (is_string($k) && $k !== '') {
                $knownKeys[$k] = true;
            }
        }
    } elseif (isset($sequence['aftershocks']) && is_array($sequence['aftershocks'])) {
        foreach ($sequence['aftershocks'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $k = isset($row['key']) ? (string) $row['key'] : quake_key($row);
            if ($k !== '') {
                $knownKeys[$k] = true;
            }
        }
    }

    $aftershocks = isset($sequence['aftershocks']) && is_array($sequence['aftershocks']) ? $sequence['aftershocks'] : [];
    foreach ($events as $event) {
        if (!is_array($event)) {
            continue;
        }
        $eventTs = quake_time_ts($event);
        if ($eventTs < $mainshockTs || $eventTs > $windowEndTs) {
            continue;
        }

        $eventKey = quake_key($event);
        if ($eventKey === $mainshockKey || isset($knownKeys[$eventKey])) {
            continue;
        }

        $magnitude = quake_mag($event);
        if ($magnitude === null || $magnitude < AFTERSHOCK_MIN_MAGNITUDE) {
            continue;
        }

        $lat = quake_lat($event);
        $lon = quake_lon($event);
        if ($lat === null || $lon === null) {
            continue;
        }

        $distanceKm = haversine_distance_km_simple($mainLat, $mainLon, $lat, $lon);
        if ($distanceKm > AFTERSHOCK_RADIUS_KM) {
            continue;
        }

        $row = quake_public_payload($event);
        $row['distance_km_from_mainshock'] = round($distanceKm, 1);
        $aftershocks[] = $row;
        $knownKeys[$eventKey] = true;
    }

    usort($aftershocks, static function (array $a, array $b): int {
        return quake_time_ts($b) <=> quake_time_ts($a);
    });
    if (count($aftershocks) > AFTERSHOCK_MAX_EVENTS_PER_SEQUENCE) {
        $aftershocks = array_slice($aftershocks, 0, AFTERSHOCK_MAX_EVENTS_PER_SEQUENCE);
    }

    $sequence['aftershocks'] = $aftershocks;
    $sequence['aftershock_keys'] = array_values(array_map(
        static fn(array $row): string => isset($row['key']) ? (string) $row['key'] : quake_key($row),
        $aftershocks
    ));
    $sequence['generated_at_ts'] = $now;
    $sequence['generated_at'] = gmdate('c', $now);
    $sequence = hydrate_stats($sequence, $now);
    $sequencesByMainshockKey[$mainshockKey] = $sequence;
}

$allSequences = array_values($sequencesByMainshockKey);
usort($allSequences, static function (array $a, array $b): int {
    $magA = isset($a['mainshock']['magnitude']) && is_numeric($a['mainshock']['magnitude']) ? (float) $a['mainshock']['magnitude'] : -1.0;
    $magB = isset($b['mainshock']['magnitude']) && is_numeric($b['mainshock']['magnitude']) ? (float) $b['mainshock']['magnitude'] : -1.0;
    if ($magB !== $magA) {
        return $magB <=> $magA;
    }
    $tsA = isset($a['mainshock_ts']) ? (int) $a['mainshock_ts'] : 0;
    $tsB = isset($b['mainshock_ts']) ? (int) $b['mainshock_ts'] : 0;
    return $tsB <=> $tsA;
});
if (count($allSequences) > AFTERSHOCK_MAX_SEQUENCES) {
    $allSequences = array_slice($allSequences, 0, AFTERSHOCK_MAX_SEQUENCES);
}

$summaries = [];
foreach ($allSequences as $sequence) {
    if (!isset($sequence['sequence_id']) || !is_string($sequence['sequence_id'])) {
        continue;
    }
    $sequencePath = sequence_file_path($sequenceDir, $sequence['sequence_id']);
    write_json_file($sequencePath, $sequence);
    $summaries[] = [
        'sequence_id' => $sequence['sequence_id'],
        'status' => $sequence['status'] ?? 'active',
        'mainshock' => $sequence['mainshock'] ?? null,
        'mainshock_ts' => $sequence['mainshock_ts'] ?? null,
        'expires_at' => $sequence['expires_at'] ?? null,
        'aftershocks_count' => $sequence['aftershocks_count'] ?? 0,
        'aftershocks_24h_count' => $sequence['aftershocks_24h_count'] ?? 0,
        'strongest_aftershock_magnitude' => $sequence['strongest_aftershock_magnitude'] ?? null,
        'latest_aftershock_time_utc' => $sequence['latest_aftershock_time_utc'] ?? null,
        'radius_km' => $sequence['radius_km'] ?? AFTERSHOCK_RADIUS_KM,
        'window_hours' => $sequence['window_hours'] ?? AFTERSHOCK_WINDOW_HOURS,
    ];
}

$indexPayload = [
    'ok' => true,
    'generated_at_ts' => $now,
    'generated_at' => gmdate('c', $now),
    'trigger_magnitude' => AFTERSHOCK_TRIGGER_MAGNITUDE,
    'radius_km' => AFTERSHOCK_RADIUS_KM,
    'window_hours' => AFTERSHOCK_WINDOW_HOURS,
    'min_aftershock_magnitude' => AFTERSHOCK_MIN_MAGNITUDE,
    'source_feed_generated_at' => $earthquakePayload['generated_at'] ?? null,
    'active_sequences_count' => count($summaries),
    'sequences' => $summaries,
    'from_cache' => false,
];
write_json_file($indexPath, $indexPayload);

if ($querySequenceId !== '' || $queryEventId !== '') {
    $target = null;
    foreach ($allSequences as $sequence) {
        if (!is_array($sequence) || !isset($sequence['sequence_id'])) {
            continue;
        }
        if ($querySequenceId !== '' && (string) $sequence['sequence_id'] === $querySequenceId) {
            $target = $sequence;
            break;
        }
        $mainshockId = isset($sequence['mainshock']['id']) ? (string) $sequence['mainshock']['id'] : '';
        if ($queryEventId !== '' && $mainshockId !== '' && $mainshockId === $queryEventId) {
            $target = $sequence;
            break;
        }
    }

    if (!is_array($target)) {
        json_response(404, [
            'ok' => false,
            'error' => 'Aftershock sequence not found',
            'active_sequences_count' => count($summaries),
            'generated_at' => gmdate('c', $now),
        ]);
    }

    json_response(200, [
        'ok' => true,
        'generated_at_ts' => $now,
        'generated_at' => gmdate('c', $now),
        'trigger_magnitude' => AFTERSHOCK_TRIGGER_MAGNITUDE,
        'radius_km' => AFTERSHOCK_RADIUS_KM,
        'window_hours' => AFTERSHOCK_WINDOW_HOURS,
        'min_aftershock_magnitude' => AFTERSHOCK_MIN_MAGNITUDE,
        'sequence' => $target,
        'from_cache' => false,
    ]);
}

json_response(200, $indexPayload);
