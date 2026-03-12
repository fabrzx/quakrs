<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

function tremor_region(string $place): string
{
    if (strpos($place, ' of ') !== false) {
        $parts = explode(' of ', $place);
        return trim((string) end($parts));
    }

    $parts = explode(',', $place);
    $region = trim((string) end($parts));
    return $region !== '' ? $region : 'Unknown';
}

$cachePath = $appConfig['data_dir'] . '/tremors_latest.json';
$now = time();
$cacheTtl = (int) $appConfig['cache_ttl_seconds'];
$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';

$cachedPayload = read_json_file($cachePath);
$cacheAge = is_array($cachedPayload) && isset($cachedPayload['generated_at_ts'])
    ? $now - (int) $cachedPayload['generated_at_ts']
    : null;

if (!$forceRefresh && is_array($cachedPayload)) {
    $cachedPayload['from_cache'] = true;
    $cachedPayload['stale_cache'] = !is_int($cacheAge) || $cacheAge > $cacheTtl;
    json_response(200, $cachedPayload);
}

$feedUrl = $feedConfig['tremors']['url'] ?? '';
$provider = $feedConfig['tremors']['provider'] ?? 'Unknown';
$external = fetch_external_json($feedUrl, (int) $appConfig['http_timeout_seconds']);

if (!is_array($external) || !isset($external['features']) || !is_array($external['features'])) {
    write_log($appConfig['logs_dir'], "Tremors feed fetch failed: {$feedUrl}");

    if (is_array($cachedPayload)) {
        $cachedPayload['from_cache'] = true;
        $cachedPayload['stale_cache'] = true;
        json_response(200, $cachedPayload);
    }

    json_response(502, [
        'ok' => false,
        'error' => 'Unable to load tremor feed',
    ]);
}

$maxEvents = (int) $appConfig['max_events'];
$tremorLimit = 3.5;
$hourlyCounter = array_fill(0, 24, 0);
$regionCounter = [];
$events = [];

foreach ($external['features'] as $feature) {
    if (!is_array($feature)) {
        continue;
    }

    $properties = $feature['properties'] ?? [];
    $geometry = $feature['geometry'] ?? [];
    $coords = $geometry['coordinates'] ?? [];

    if (!is_array($properties) || !is_array($coords) || count($coords) < 3) {
        continue;
    }

    $mag = is_numeric($properties['mag'] ?? null) ? (float) $properties['mag'] : null;
    if (!is_float($mag) || $mag > $tremorLimit) {
        continue;
    }

    $timeMs = isset($properties['time']) ? (int) $properties['time'] : 0;
    $eventTs = $timeMs > 0 ? (int) floor($timeMs / 1000) : 0;
    $eventIso = $eventTs > 0 ? gmdate('c', $eventTs) : null;
    $place = (string) ($properties['place'] ?? 'Unknown');
    $region = tremor_region($place);

    if ($eventTs > 0) {
        $hourUtc = (int) gmdate('G', $eventTs);
        if (isset($hourlyCounter[$hourUtc])) {
            $hourlyCounter[$hourUtc] += 1;
        }
    }

    if (!isset($regionCounter[$region])) {
        $regionCounter[$region] = [
            'count' => 0,
            'max_magnitude' => -1.0,
        ];
    }
    $regionCounter[$region]['count'] += 1;
    if ($mag > $regionCounter[$region]['max_magnitude']) {
        $regionCounter[$region]['max_magnitude'] = $mag;
    }

    $events[] = [
        'id' => (string) ($feature['id'] ?? ''),
        'place' => $place,
        'region' => $region,
        'magnitude' => $mag,
        'depth_km' => is_numeric($coords[2]) ? (float) $coords[2] : null,
        'event_time_utc' => $eventIso,
        'source_url' => (string) ($properties['url'] ?? ''),
    ];
}

usort($events, static function (array $a, array $b): int {
    $aTs = isset($a['event_time_utc']) ? strtotime((string) $a['event_time_utc']) : 0;
    $bTs = isset($b['event_time_utc']) ? strtotime((string) $b['event_time_utc']) : 0;
    return $bTs <=> $aTs;
});
$events = array_slice($events, 0, $maxEvents);

uasort($regionCounter, static function (array $a, array $b): int {
    if ($b['count'] !== $a['count']) {
        return $b['count'] <=> $a['count'];
    }
    return $b['max_magnitude'] <=> $a['max_magnitude'];
});

$clusters = [];
foreach ($regionCounter as $region => $stats) {
    $clusters[] = [
        'region' => $region,
        'count' => (int) $stats['count'],
        'max_magnitude' => (float) $stats['max_magnitude'],
    ];
    if (count($clusters) >= 8) {
        break;
    }
}

$peakHour = 0;
$peakCount = -1;
foreach ($hourlyCounter as $hour => $count) {
    if ($count > $peakCount) {
        $peakCount = $count;
        $peakHour = (int) $hour;
    }
}

$payload = [
    'ok' => true,
    'provider' => $provider,
    'generated_at_ts' => $now,
    'generated_at' => gmdate('c', $now),
    'methodology' => 'Derived from low-magnitude events (M3.5 and below) in the USGS last-24h feed.',
    'signals_count' => count($events),
    'clusters_count' => count($clusters),
    'peak_hour_utc' => sprintf('%02d:00', $peakHour),
    'peak_hour_count' => max(0, (int) $peakCount),
    'clusters' => $clusters,
    'events' => $events,
    'from_cache' => false,
];

if (!write_json_file($cachePath, $payload)) {
    write_log($appConfig['logs_dir'], 'Failed writing tremors cache JSON');
}

json_response(200, $payload);
