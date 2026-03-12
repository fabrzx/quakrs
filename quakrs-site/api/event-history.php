<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

function clamp_float(float $value, float $min, float $max): float
{
    return max($min, min($max, $value));
}

function normalize_date(string $value, string $fallback): string
{
    $value = trim($value);
    if ($value === '') {
        return $fallback;
    }

    $ts = strtotime($value);
    if (!is_int($ts)) {
        return $fallback;
    }

    return gmdate('Y-m-d', $ts);
}

function build_usgs_url(array $params): string
{
    $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    return 'https://earthquake.usgs.gov/fdsnws/event/1/query?' . $query;
}

function parse_event_rows(array $features): array
{
    $rows = [];

    foreach ($features as $feature) {
        if (!is_array($feature)) {
            continue;
        }

        $properties = $feature['properties'] ?? [];
        $geometry = $feature['geometry'] ?? [];
        $coordinates = $geometry['coordinates'] ?? [];

        if (!is_array($properties) || !is_array($coordinates) || count($coordinates) < 3) {
            continue;
        }

        $mag = is_numeric($properties['mag'] ?? null) ? (float) $properties['mag'] : null;
        $timeMs = isset($properties['time']) ? (int) $properties['time'] : 0;
        $timeTs = $timeMs > 0 ? (int) floor($timeMs / 1000) : 0;

        $rows[] = [
            'id' => (string) ($feature['id'] ?? ''),
            'magnitude' => $mag,
            'place' => (string) ($properties['place'] ?? 'Unknown location'),
            'event_time_utc' => $timeTs > 0 ? gmdate('c', $timeTs) : null,
            'depth_km' => is_numeric($coordinates[2]) ? (float) $coordinates[2] : null,
            'latitude' => is_numeric($coordinates[1]) ? (float) $coordinates[1] : null,
            'longitude' => is_numeric($coordinates[0]) ? (float) $coordinates[0] : null,
            'source_url' => (string) ($properties['url'] ?? ''),
        ];
    }

    return $rows;
}

function haversine_km(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $toRad = static fn(float $v): float => ($v * M_PI) / 180.0;
    $dLat = $toRad($lat2 - $lat1);
    $dLon = $toRad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2 + cos($toRad($lat1)) * cos($toRad($lat2)) * sin($dLon / 2) ** 2;
    return 6371.0 * (2 * atan2(sqrt($a), sqrt(1 - $a)));
}

function local_history_fallback(
    string $dataDir,
    float $lat,
    float $lon,
    float $radiusKm,
    ?float $minMagnitude,
    int $page,
    int $perPage,
    int $now
): ?array {
    $localPath = $dataDir . '/earthquakes_latest.json';
    $localPayload = read_json_file($localPath);
    if (!is_array($localPayload) || !isset($localPayload['events']) || !is_array($localPayload['events'])) {
        return null;
    }

    $rows = [];
    foreach ($localPayload['events'] as $row) {
        if (!is_array($row)) {
            continue;
        }
        $rowLat = isset($row['latitude']) && is_numeric($row['latitude']) ? (float) $row['latitude'] : null;
        $rowLon = isset($row['longitude']) && is_numeric($row['longitude']) ? (float) $row['longitude'] : null;
        $rowMag = isset($row['magnitude']) && is_numeric($row['magnitude']) ? (float) $row['magnitude'] : null;
        if (!is_float($rowLat) || !is_float($rowLon)) {
            continue;
        }
        if ($minMagnitude !== null && (!is_float($rowMag) || $rowMag < $minMagnitude)) {
            continue;
        }
        $km = haversine_km($lat, $lon, $rowLat, $rowLon);
        if ($km > $radiusKm) {
            continue;
        }
        $rows[] = [
            'id' => (string) ($row['id'] ?? ''),
            'magnitude' => $rowMag,
            'place' => (string) ($row['place'] ?? 'Unknown location'),
            'event_time_utc' => isset($row['event_time_utc']) ? (string) $row['event_time_utc'] : null,
            'depth_km' => isset($row['depth_km']) && is_numeric($row['depth_km']) ? (float) $row['depth_km'] : null,
            'latitude' => $rowLat,
            'longitude' => $rowLon,
            'source_url' => isset($row['source_url']) ? (string) $row['source_url'] : '',
        ];
    }

    usort($rows, static function (array $a, array $b): int {
        $aTs = isset($a['event_time_utc']) ? strtotime((string) $a['event_time_utc']) : 0;
        $bTs = isset($b['event_time_utc']) ? strtotime((string) $b['event_time_utc']) : 0;
        return $bTs <=> $aTs;
    });

    $total = count($rows);
    $totalPages = max(1, (int) ceil($total / max(1, $perPage)));
    $safePage = min(max(1, $page), $totalPages);
    $slice = array_slice($rows, ($safePage - 1) * $perPage, $perPage);

    $strongest = $rows;
    usort($strongest, static fn(array $a, array $b): int => ((float) ($b['magnitude'] ?? -1)) <=> ((float) ($a['magnitude'] ?? -1)));
    $strongest = array_slice($strongest, 0, 12);

    return [
        'ok' => true,
        'provider' => 'Local Operational Fallback (earthquakes_latest.json)',
        'generated_at_ts' => $now,
        'generated_at' => gmdate('c', $now),
        'page' => $safePage,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'total_events' => $total,
        'events_count' => count($slice),
        'events' => $slice,
        'strongest_events' => $strongest,
        'from_cache' => false,
        'fallback_mode' => true,
    ];
}

$latRaw = isset($_GET['lat']) ? (float) $_GET['lat'] : NAN;
$lonRaw = isset($_GET['lon']) ? (float) $_GET['lon'] : NAN;

if (!is_finite($latRaw) || !is_finite($lonRaw)) {
    json_response(400, [
        'ok' => false,
        'error' => 'Missing required coordinates (lat, lon)',
    ]);
}

$lat = clamp_float($latRaw, -89.99, 89.99);
$lon = clamp_float($lonRaw, -179.99, 179.99);
$radiusKm = isset($_GET['radius_km']) ? clamp_float((float) $_GET['radius_km'], 50.0, 1500.0) : 500.0;
$minMagnitudeRaw = isset($_GET['min_magnitude']) ? trim((string) $_GET['min_magnitude']) : '';
$minMagnitude = $minMagnitudeRaw !== '' && is_numeric($minMagnitudeRaw) ? max(-1.0, (float) $minMagnitudeRaw) : null;
$startDate = normalize_date((string) ($_GET['start'] ?? '1900-01-01'), '1900-01-01');
$endDate = normalize_date((string) ($_GET['end'] ?? gmdate('Y-m-d')), gmdate('Y-m-d'));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = max(20, min(500, (int) ($_GET['per_page'] ?? 80)));
$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';

$cacheTtl = 12 * 60 * 60;
$queryKey = implode('|', [
    number_format($lat, 4, '.', ''),
    number_format($lon, 4, '.', ''),
    number_format($radiusKm, 0, '.', ''),
    $startDate,
    $endDate,
    $minMagnitude !== null ? number_format($minMagnitude, 2, '.', '') : 'all',
    "p{$page}",
    "n{$perPage}",
]);
$cachePath = $appConfig['data_dir'] . '/event_history_' . md5($queryKey) . '.json';
$now = time();

$cached = read_json_file($cachePath);
$cacheAge = is_array($cached) && isset($cached['generated_at_ts']) ? $now - (int) $cached['generated_at_ts'] : null;

if (!$forceRefresh && is_array($cached) && is_int($cacheAge) && $cacheAge <= $cacheTtl) {
    $cached['from_cache'] = true;
    json_response(200, $cached);
}

$baseParams = [
    'format' => 'geojson',
    'latitude' => number_format($lat, 4, '.', ''),
    'longitude' => number_format($lon, 4, '.', ''),
    'maxradiuskm' => number_format($radiusKm, 0, '.', ''),
    'starttime' => $startDate,
    'endtime' => $endDate,
];
if ($minMagnitude !== null) {
    $baseParams['minmagnitude'] = number_format($minMagnitude, 2, '.', '');
}

$countParams = $baseParams;
$countParams['limit'] = 1;
$countParams['offset'] = 1;
$countPayload = fetch_external_json(build_usgs_url($countParams), (int) $appConfig['http_timeout_seconds']);

if (!is_array($countPayload) || !isset($countPayload['metadata']) || !is_array($countPayload['metadata'])) {
    write_log($appConfig['logs_dir'], 'Event history count request failed');

    $fallbackPayload = local_history_fallback($appConfig['data_dir'], $lat, $lon, $radiusKm, $minMagnitude, $page, $perPage, $now);
    if (is_array($fallbackPayload)) {
        json_response(200, $fallbackPayload);
    }

    if (is_array($cached)) {
        $cached['from_cache'] = true;
        $cached['stale_cache'] = true;
        json_response(200, $cached);
    }

    json_response(502, [
        'ok' => false,
        'error' => 'Unable to load historical archive metadata',
    ]);
}

$totalEvents = (int) ($countPayload['metadata']['count'] ?? 0);
$totalPages = max(1, (int) ceil($totalEvents / max(1, $perPage)));
$safePage = min($page, $totalPages);
$offset = (($safePage - 1) * $perPage) + 1;

$pageParams = $baseParams;
$pageParams['orderby'] = 'time';
$pageParams['limit'] = $perPage;
$pageParams['offset'] = $offset;
$pagePayload = fetch_external_json(build_usgs_url($pageParams), (int) $appConfig['http_timeout_seconds']);

if (!is_array($pagePayload) || !isset($pagePayload['features']) || !is_array($pagePayload['features'])) {
    write_log($appConfig['logs_dir'], "Event history page request failed (page {$safePage})");

    if (is_array($cached)) {
        $cached['from_cache'] = true;
        $cached['stale_cache'] = true;
        json_response(200, $cached);
    }

    json_response(502, [
        'ok' => false,
        'error' => 'Unable to load historical archive page',
    ]);
}

$strongParams = $baseParams;
$strongParams['orderby'] = 'magnitude';
$strongParams['limit'] = 12;
$strongParams['offset'] = 1;
$strongPayload = fetch_external_json(build_usgs_url($strongParams), (int) $appConfig['http_timeout_seconds']);
$strongFeatures = is_array($strongPayload) && isset($strongPayload['features']) && is_array($strongPayload['features'])
    ? $strongPayload['features']
    : [];

$events = parse_event_rows($pagePayload['features']);
$strongest = parse_event_rows($strongFeatures);

$payload = [
    'ok' => true,
    'provider' => 'USGS FDSN Historical Archive',
    'generated_at_ts' => $now,
    'generated_at' => gmdate('c', $now),
    'query' => [
        'lat' => $lat,
        'lon' => $lon,
        'radius_km' => $radiusKm,
        'min_magnitude' => $minMagnitude,
        'start' => $startDate,
        'end' => $endDate,
    ],
    'page' => $safePage,
    'per_page' => $perPage,
    'total_pages' => $totalPages,
    'total_events' => $totalEvents,
    'events_count' => count($events),
    'events' => $events,
    'strongest_events' => $strongest,
    'from_cache' => false,
];

if (!write_json_file($cachePath, $payload)) {
    write_log($appConfig['logs_dir'], 'Failed writing event history cache');
}

json_response(200, $payload);
