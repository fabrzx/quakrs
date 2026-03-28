<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/earthquakes-archive-lib.php';

function italy_event_time(mixed $rawTime): ?int
{
    if (is_numeric($rawTime)) {
        $time = (int) $rawTime;
        if ($time > 9999999999) {
            $time = (int) floor($time / 1000);
        }
        return $time > 0 ? $time : null;
    }
    if (is_string($rawTime) && trim($rawTime) !== '') {
        $raw = trim($rawTime);
        $hasTimezone = (bool) preg_match('/(?:Z|[+\-]\d{2}:?\d{2})$/i', $raw);
        $input = $hasTimezone ? $raw : ($raw . ' UTC');
        $parsed = strtotime($input);
        return is_int($parsed) ? $parsed : null;
    }
    return null;
}

function italy_fetch_ingv_features(string $startIso, string $endIso, int $limit, int $timeoutSeconds): ?array
{
    $params = [
        'format' => 'geojson',
        'orderby' => 'time',
        'starttime' => $startIso,
        'endtime' => $endIso,
        'limit' => $limit,
    ];
    $url = 'https://webservices.ingv.it/fdsnws/event/1/query?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    $payload = fetch_external_json($url, $timeoutSeconds);
    if (!is_array($payload) || !isset($payload['features']) || !is_array($payload['features'])) {
        return null;
    }
    return $payload['features'];
}

function italy_parse_region(string $place): string
{
    $place = trim($place);
    if ($place === '') {
        return 'Unknown';
    }
    if (strpos($place, ' of ') !== false) {
        $parts = explode(' of ', $place);
        $region = trim((string) end($parts));
        return $region !== '' ? $region : 'Unknown';
    }
    $parts = array_values(array_filter(array_map('trim', explode(',', $place)), static fn(string $v): bool => $v !== ''));
    if (count($parts) === 0) {
        return $place;
    }
    return (string) $parts[count($parts) - 1];
}

function italy_estimated_energy_j(float $magnitude): float
{
    return 10 ** ((1.5 * $magnitude) + 4.8);
}

function italy_bbox_filter(float $lat, float $lon): bool
{
    // Broad Italy viewport including islands.
    return $lat >= 35.0 && $lat <= 48.8 && $lon >= 6.0 && $lon <= 19.6;
}

function italy_normalize_events(array $features): array
{
    $rows = [];
    foreach ($features as $feature) {
        if (!is_array($feature)) {
            continue;
        }
        $properties = $feature['properties'] ?? null;
        $geometry = $feature['geometry'] ?? null;
        $coords = is_array($geometry) ? ($geometry['coordinates'] ?? null) : null;
        if (!is_array($properties) || !is_array($coords) || count($coords) < 3) {
            continue;
        }
        $lon = is_numeric($coords[0] ?? null) ? (float) $coords[0] : null;
        $lat = is_numeric($coords[1] ?? null) ? (float) $coords[1] : null;
        if (!is_float($lat) || !is_float($lon) || !italy_bbox_filter($lat, $lon)) {
            continue;
        }
        $timeTs = italy_event_time($properties['time'] ?? null);
        if (!is_int($timeTs)) {
            continue;
        }
        $mag = is_numeric($properties['mag'] ?? null) ? (float) $properties['mag'] : null;
        $depth = is_numeric($coords[2] ?? null) ? abs((float) $coords[2]) : null;
        $place = (string) ($properties['place'] ?? 'Unknown location');
        $rows[] = [
            'id' => (string) ($feature['id'] ?? ''),
            'place' => $place,
            'region' => italy_parse_region($place),
            'magnitude' => $mag,
            'depth_km' => $depth,
            'latitude' => $lat,
            'longitude' => $lon,
            'event_time_ts' => $timeTs,
            'event_time_utc' => gmdate('c', $timeTs),
            'source_url' => (string) ($properties['url'] ?? ''),
            'source_provider' => 'INGV',
        ];
    }
    usort($rows, static fn(array $a, array $b): int => ((int) ($b['event_time_ts'] ?? 0)) <=> ((int) ($a['event_time_ts'] ?? 0)));
    return $rows;
}

function italy_daily_series(array $events, int $days, int $todayStartTs): array
{
    $map = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $dayTs = $todayStartTs - ($i * 86400);
        $key = gmdate('Y-m-d', $dayTs);
        $map[$key] = [
            'date_utc' => $key,
            'label' => gmdate('D', $dayTs),
            'count' => 0,
        ];
    }
    foreach ($events as $row) {
        if (!is_array($row) || !isset($row['event_time_ts']) || !is_numeric($row['event_time_ts'])) {
            continue;
        }
        $key = gmdate('Y-m-d', (int) $row['event_time_ts']);
        if (isset($map[$key])) {
            $map[$key]['count'] += 1;
        }
    }
    return array_values($map);
}

function italy_swarm_grid_key(float $lat, float $lon): string
{
    $gridLat = floor($lat * 4.0) / 4.0;
    $gridLon = floor($lon * 4.0) / 4.0;
    return number_format($gridLat, 2, '.', '') . '|' . number_format($gridLon, 2, '.', '');
}

function italy_parse_swarm_id(string $swarmId): ?array
{
    $swarmId = trim($swarmId);
    if (!preg_match('/^(-?\d+\.\d{2})\|(-?\d+\.\d{2})$/', $swarmId, $m)) {
        return null;
    }
    return [
        'grid_lat' => (float) $m[1],
        'grid_lon' => (float) $m[2],
    ];
}

function italy_detect_swarms(array $events): array
{
    $clusters = [];
    foreach ($events as $row) {
        if (!is_array($row)) {
            continue;
        }
        $lat = isset($row['latitude']) && is_numeric($row['latitude']) ? (float) $row['latitude'] : null;
        $lon = isset($row['longitude']) && is_numeric($row['longitude']) ? (float) $row['longitude'] : null;
        $ts = isset($row['event_time_ts']) && is_numeric($row['event_time_ts']) ? (int) $row['event_time_ts'] : null;
        if (!is_float($lat) || !is_float($lon) || !is_int($ts)) {
            continue;
        }
        $key = italy_swarm_grid_key($lat, $lon);
        if (!isset($clusters[$key])) {
            $clusters[$key] = [
                'key' => $key,
                'events' => 0,
                'max_magnitude' => -1.0,
                'first_ts' => $ts,
                'last_ts' => $ts,
                'center_lat' => 0.0,
                'center_lon' => 0.0,
                'top_region' => (string) ($row['region'] ?? 'Unknown'),
            ];
        }
        $clusters[$key]['events'] += 1;
        $clusters[$key]['center_lat'] += $lat;
        $clusters[$key]['center_lon'] += $lon;
        $mag = isset($row['magnitude']) && is_numeric($row['magnitude']) ? (float) $row['magnitude'] : -1.0;
        if ($mag > $clusters[$key]['max_magnitude']) {
            $clusters[$key]['max_magnitude'] = $mag;
        }
        if ($ts < $clusters[$key]['first_ts']) {
            $clusters[$key]['first_ts'] = $ts;
        }
        if ($ts > $clusters[$key]['last_ts']) {
            $clusters[$key]['last_ts'] = $ts;
        }
    }

    $rows = [];
    foreach ($clusters as $cluster) {
        if ((int) $cluster['events'] < 5) {
            continue;
        }
        $durationHours = ((int) $cluster['last_ts'] - (int) $cluster['first_ts']) / 3600.0;
        $rows[] = [
            'swarm_id' => (string) $cluster['key'],
            'events' => (int) $cluster['events'],
            'max_magnitude' => (float) $cluster['max_magnitude'],
            'duration_hours' => round(max(0.0, $durationHours), 1),
            'center_lat' => round(((float) $cluster['center_lat']) / max(1, (int) $cluster['events']), 3),
            'center_lon' => round(((float) $cluster['center_lon']) / max(1, (int) $cluster['events']), 3),
            'region' => (string) $cluster['top_region'],
            'first_event_utc' => gmdate('c', (int) $cluster['first_ts']),
            'last_event_utc' => gmdate('c', (int) $cluster['last_ts']),
        ];
    }

    usort($rows, static function (array $a, array $b): int {
        if ((int) $b['events'] !== (int) $a['events']) {
            return ((int) $b['events']) <=> ((int) $a['events']);
        }
        return ((float) $b['max_magnitude']) <=> ((float) $a['max_magnitude']);
    });

    return array_slice($rows, 0, 8);
}

function italy_events_for_swarm(array $events, string $swarmId): array
{
    $parsed = italy_parse_swarm_id($swarmId);
    if (!is_array($parsed)) {
        return [];
    }
    $targetKey = number_format((float) $parsed['grid_lat'], 2, '.', '') . '|' . number_format((float) $parsed['grid_lon'], 2, '.', '');
    $rows = [];
    foreach ($events as $row) {
        if (!is_array($row)) {
            continue;
        }
        $lat = isset($row['latitude']) && is_numeric($row['latitude']) ? (float) $row['latitude'] : null;
        $lon = isset($row['longitude']) && is_numeric($row['longitude']) ? (float) $row['longitude'] : null;
        if (!is_float($lat) || !is_float($lon)) {
            continue;
        }
        if (italy_swarm_grid_key($lat, $lon) !== $targetKey) {
            continue;
        }
        $rows[] = $row;
    }
    return $rows;
}

function italy_events_from_local_cache(string $dataDir, int $fromTs, int $toTs): array
{
    $payload = read_json_file($dataDir . '/earthquakes_latest.json');
    $events = is_array($payload) && isset($payload['events']) && is_array($payload['events']) ? $payload['events'] : [];
    $rows = [];
    foreach ($events as $event) {
        if (!is_array($event)) {
            continue;
        }
        $lat = isset($event['latitude']) && is_numeric($event['latitude']) ? (float) $event['latitude'] : null;
        $lon = isset($event['longitude']) && is_numeric($event['longitude']) ? (float) $event['longitude'] : null;
        if (!is_float($lat) || !is_float($lon) || !italy_bbox_filter($lat, $lon)) {
            continue;
        }
        $ts = isset($event['event_time_utc']) ? strtotime((string) $event['event_time_utc']) : false;
        if (!is_int($ts) || $ts < $fromTs || $ts > $toTs) {
            continue;
        }
        $place = (string) ($event['place'] ?? 'Unknown location');
        $mag = isset($event['magnitude']) && is_numeric($event['magnitude']) ? (float) $event['magnitude'] : null;
        $depth = isset($event['depth_km']) && is_numeric($event['depth_km']) ? abs((float) $event['depth_km']) : null;
        $rows[] = [
            'id' => (string) ($event['id'] ?? ''),
            'place' => $place,
            'region' => italy_parse_region($place),
            'magnitude' => $mag,
            'depth_km' => $depth,
            'latitude' => $lat,
            'longitude' => $lon,
            'event_time_ts' => $ts,
            'event_time_utc' => gmdate('c', $ts),
            'source_url' => (string) ($event['source_url'] ?? ''),
            'source_provider' => (string) ($event['source_provider'] ?? 'Quakrs cache'),
        ];
    }
    usort($rows, static fn(array $a, array $b): int => ((int) ($b['event_time_ts'] ?? 0)) <=> ((int) ($a['event_time_ts'] ?? 0)));
    return $rows;
}

function italy_events_from_mysql_archive(mysqli $db, string $table, int $fromTs, int $toTs, int $limit = 50000): array
{
    $sql = sprintf(
        'SELECT event_id, place, magnitude, depth_km, latitude, longitude, event_time_utc, event_time_ts, source_url, source_provider
         FROM `%s`
         WHERE event_time_ts >= ?
           AND event_time_ts <= ?
           AND latitude IS NOT NULL
           AND longitude IS NOT NULL
           AND latitude BETWEEN 35.0 AND 48.8
           AND longitude BETWEEN 6.0 AND 19.6
         ORDER BY event_time_ts DESC
         LIMIT ?',
        preg_match('/^[a-zA-Z0-9_]+$/', $table) ? $table : 'earthquake_events'
    );
    $stmt = $db->prepare($sql);
    if (!$stmt instanceof mysqli_stmt) {
        return [];
    }
    if (!$stmt->bind_param('iii', $fromTs, $toTs, $limit) || !$stmt->execute()) {
        $stmt->close();
        return [];
    }
    $rows = [];
    $result = $stmt->get_result();
    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $lat = is_numeric($row['latitude'] ?? null) ? (float) $row['latitude'] : null;
            $lon = is_numeric($row['longitude'] ?? null) ? (float) $row['longitude'] : null;
            if (!is_float($lat) || !is_float($lon) || !italy_bbox_filter($lat, $lon)) {
                continue;
            }
            $ts = is_numeric($row['event_time_ts'] ?? null) ? (int) $row['event_time_ts'] : null;
            if (!is_int($ts)) {
                $parsed = isset($row['event_time_utc']) ? strtotime((string) $row['event_time_utc']) : false;
                $ts = is_int($parsed) ? $parsed : null;
            }
            if (!is_int($ts)) {
                continue;
            }
            $place = (string) ($row['place'] ?? 'Unknown location');
            $rows[] = [
                'id' => (string) ($row['event_id'] ?? ''),
                'place' => $place,
                'region' => italy_parse_region($place),
                'magnitude' => is_numeric($row['magnitude'] ?? null) ? (float) $row['magnitude'] : null,
                'depth_km' => is_numeric($row['depth_km'] ?? null) ? abs((float) $row['depth_km']) : null,
                'latitude' => $lat,
                'longitude' => $lon,
                'event_time_ts' => $ts,
                'event_time_utc' => gmdate('c', $ts),
                'source_url' => (string) ($row['source_url'] ?? ''),
                'source_provider' => (string) ($row['source_provider'] ?? 'Archive'),
            ];
        }
        $result->free();
    }
    $stmt->close();
    return $rows;
}

$nowTs = time();
$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';
$swarmRequestedId = isset($_GET['swarm']) ? trim((string) $_GET['swarm']) : '';
$cachePath = $appConfig['data_dir'] . '/italy_earthquakes_latest.json';
$cacheTtl = max(60, min(3600, (int) ($appConfig['italy_cache_ttl_seconds'] ?? 300)));

$cached = read_json_file($cachePath);
$cacheAge = is_array($cached) && isset($cached['generated_at_ts']) ? ($nowTs - (int) $cached['generated_at_ts']) : null;
if ($swarmRequestedId === '' && !$forceRefresh && is_array($cached) && is_int($cacheAge) && $cacheAge <= $cacheTtl) {
    $cached['from_cache'] = true;
    $cached['stale_cache'] = false;
    json_response(200, $cached);
}

$todayStartTs = (int) gmdate('U', strtotime(gmdate('Y-m-d 00:00:00', $nowTs) . ' UTC'));
$start24hTs = $nowTs - 86400;
$start31dTs = $todayStartTs - (30 * 86400);

$sourceMode = 'INGV';
$events24h = [];
$events31d = [];

$features24h = italy_fetch_ingv_features(
    gmdate('Y-m-d\TH:i:s', $start24hTs),
    gmdate('Y-m-d\TH:i:s', $nowTs),
    6000,
    max(12, (int) ($appConfig['http_timeout_seconds'] ?? 12))
);
$features31d = italy_fetch_ingv_features(
    gmdate('Y-m-d\TH:i:s', $start31dTs),
    gmdate('Y-m-d\TH:i:s', $nowTs),
    20000,
    max(16, (int) ($appConfig['http_timeout_seconds'] ?? 12))
);
if (is_array($features24h) && is_array($features31d)) {
    $events24h = italy_normalize_events($features24h);
    $events31d = italy_normalize_events($features31d);
}

if (count($events24h) === 0 || count($events31d) === 0) {
    $archiveReason = null;
    $archiveDb = earthquake_archive_open($appConfig, $archiveReason);
    if ($archiveDb instanceof mysqli) {
        $archiveCfg = earthquake_archive_mysql_config($appConfig);
        $archiveTable = (string) ($archiveCfg['table'] ?? 'earthquake_events');
        $events24h = italy_events_from_mysql_archive($archiveDb, $archiveTable, $start24hTs, $nowTs, 15000);
        $events31d = italy_events_from_mysql_archive($archiveDb, $archiveTable, $start31dTs, $nowTs, 80000);
        $archiveDb->close();
        if (count($events24h) > 0 && count($events31d) > 0) {
            $sourceMode = 'Archive MySQL';
        }
    }
}

if (count($events24h) === 0 || count($events31d) === 0) {
    $fallback24 = italy_events_from_local_cache($appConfig['data_dir'], $start24hTs, $nowTs);
    $fallback31 = italy_events_from_local_cache($appConfig['data_dir'], $start31dTs, $nowTs);
    if (count($fallback24) > 0 && count($fallback31) > 0) {
        $events24h = $fallback24;
        $events31d = $fallback31;
        $sourceMode = 'Cache fallback';
    }
}

if (count($events24h) === 0 && is_array($cached)) {
    $cached['from_cache'] = true;
    $cached['stale_cache'] = true;
    json_response(200, $cached);
}
if (count($events24h) === 0) {
    json_response(502, [
        'ok' => false,
        'error' => 'Unable to load Italy feed from INGV and no local fallback data found',
    ]);
}

$maxMag = null;
$totalEnergyJ = 0.0;
$regionCounter = [];
$depthBands = ['0-10' => 0, '10-30' => 0, '30-70' => 0, '70+' => 0];
$magBands = ['M0-1' => 0, 'M1-2' => 0, 'M2-3' => 0, 'M3+' => 0];

foreach ($events24h as $row) {
    $mag = isset($row['magnitude']) && is_numeric($row['magnitude']) ? (float) $row['magnitude'] : null;
    $depth = isset($row['depth_km']) && is_numeric($row['depth_km']) ? (float) $row['depth_km'] : null;
    $region = (string) ($row['region'] ?? 'Unknown');
    $regionCounter[$region] = ($regionCounter[$region] ?? 0) + 1;

    if (is_float($mag)) {
        if ($maxMag === null || $mag > $maxMag) {
            $maxMag = $mag;
        }
        $totalEnergyJ += italy_estimated_energy_j($mag);
        if ($mag < 1.0) {
            $magBands['M0-1'] += 1;
        } elseif ($mag < 2.0) {
            $magBands['M1-2'] += 1;
        } elseif ($mag < 3.0) {
            $magBands['M2-3'] += 1;
        } else {
            $magBands['M3+'] += 1;
        }
    }

    if (is_float($depth)) {
        if ($depth < 10.0) {
            $depthBands['0-10'] += 1;
        } elseif ($depth < 30.0) {
            $depthBands['10-30'] += 1;
        } elseif ($depth < 70.0) {
            $depthBands['30-70'] += 1;
        } else {
            $depthBands['70+'] += 1;
        }
    }
}

arsort($regionCounter);
$topRegion = count($regionCounter) > 0 ? (string) array_key_first($regionCounter) : 'Unknown';

$series30 = italy_daily_series($events31d, 30, $todayStartTs);
$series7 = array_slice($series30, -7);

$todayCount = count($events24h);
$baselinePrev30 = 0.0;
if (count($series30) > 1) {
    $sum = 0;
    $n = 0;
    for ($i = 0; $i < count($series30) - 1; $i++) {
        $sum += (int) ($series30[$i]['count'] ?? 0);
        $n += 1;
    }
    $baselinePrev30 = $n > 0 ? ($sum / $n) : 0.0;
}
$baselineMethod = 'rolling-30d';
if ($sourceMode === 'Cache fallback') {
    // In fallback mode historical depth can be incomplete; use a stable proxy baseline.
    $baselinePrev30 = max(1.0, (float) round($todayCount * 0.85, 2));
    $baselineMethod = 'proxy-24h';
}
$deltaPct = $baselinePrev30 > 0.0 ? (($todayCount - $baselinePrev30) / $baselinePrev30) * 100.0 : 0.0;
$baselineState = $deltaPct >= 35.0 ? 'Above normal' : ($deltaPct <= -25.0 ? 'Below normal' : 'Within normal');

$swarmCandidates = italy_detect_swarms($events24h);
$swarmDetail = null;
if ($swarmRequestedId !== '') {
    $swarmEvents30d = italy_events_for_swarm($events31d, $swarmRequestedId);
    if (count($swarmEvents30d) > 0) {
        usort($swarmEvents30d, static fn(array $a, array $b): int => ((int) ($b['event_time_ts'] ?? 0)) <=> ((int) ($a['event_time_ts'] ?? 0)));
        $swarmEvents24h = array_values(array_filter($swarmEvents30d, static fn(array $row): bool => ((int) ($row['event_time_ts'] ?? 0)) >= $start24hTs));
        $swarmEvents7d = array_values(array_filter($swarmEvents30d, static fn(array $row): bool => ((int) ($row['event_time_ts'] ?? 0)) >= ($nowTs - (7 * 86400))));
        $swarmSeries14d = italy_daily_series($swarmEvents30d, 14, $todayStartTs);

        $swarmMagBands = ['M0-1' => 0, 'M1-2' => 0, 'M2-3' => 0, 'M3+' => 0];
        $swarmDepthBands = ['0-10' => 0, '10-30' => 0, '30-70' => 0, '70+' => 0];
        $swarmHourly = array_fill(0, 24, 0);
        $swarmRegionCounter = [];
        $swarmDepthSum = 0.0;
        $swarmDepthN = 0;
        $swarmMagMax24h = null;
        foreach ($swarmEvents24h as $row) {
            $mag = isset($row['magnitude']) && is_numeric($row['magnitude']) ? (float) $row['magnitude'] : null;
            $depth = isset($row['depth_km']) && is_numeric($row['depth_km']) ? (float) $row['depth_km'] : null;
            $ts = isset($row['event_time_ts']) && is_numeric($row['event_time_ts']) ? (int) $row['event_time_ts'] : null;
            $region = (string) ($row['region'] ?? 'Unknown');
            $swarmRegionCounter[$region] = ($swarmRegionCounter[$region] ?? 0) + 1;

            if (is_float($mag)) {
                if ($swarmMagMax24h === null || $mag > $swarmMagMax24h) {
                    $swarmMagMax24h = $mag;
                }
                if ($mag < 1.0) {
                    $swarmMagBands['M0-1'] += 1;
                } elseif ($mag < 2.0) {
                    $swarmMagBands['M1-2'] += 1;
                } elseif ($mag < 3.0) {
                    $swarmMagBands['M2-3'] += 1;
                } else {
                    $swarmMagBands['M3+'] += 1;
                }
            }
            if (is_float($depth)) {
                if ($depth < 10.0) {
                    $swarmDepthBands['0-10'] += 1;
                } elseif ($depth < 30.0) {
                    $swarmDepthBands['10-30'] += 1;
                } elseif ($depth < 70.0) {
                    $swarmDepthBands['30-70'] += 1;
                } else {
                    $swarmDepthBands['70+'] += 1;
                }
                $swarmDepthSum += $depth;
                $swarmDepthN += 1;
            }
            if (is_int($ts)) {
                $hour = (int) gmdate('G', $ts);
                if ($hour >= 0 && $hour <= 23) {
                    $swarmHourly[$hour] += 1;
                }
            }
        }
        arsort($swarmRegionCounter);
        $swarmTopRegion = count($swarmRegionCounter) > 0 ? (string) array_key_first($swarmRegionCounter) : 'Unknown';

        $swarmMagMax30d = null;
        $swarmFirstTs = null;
        $swarmLastTs = null;
        $swarmCenterLatSum = 0.0;
        $swarmCenterLonSum = 0.0;
        $swarmCenterN = 0;
        $swarmStrongest = null;
        foreach ($swarmEvents30d as $row) {
            $ts = isset($row['event_time_ts']) && is_numeric($row['event_time_ts']) ? (int) $row['event_time_ts'] : null;
            $mag = isset($row['magnitude']) && is_numeric($row['magnitude']) ? (float) $row['magnitude'] : null;
            $lat = isset($row['latitude']) && is_numeric($row['latitude']) ? (float) $row['latitude'] : null;
            $lon = isset($row['longitude']) && is_numeric($row['longitude']) ? (float) $row['longitude'] : null;
            if (is_int($ts)) {
                if ($swarmFirstTs === null || $ts < $swarmFirstTs) {
                    $swarmFirstTs = $ts;
                }
                if ($swarmLastTs === null || $ts > $swarmLastTs) {
                    $swarmLastTs = $ts;
                }
            }
            if (is_float($mag) && ($swarmMagMax30d === null || $mag > $swarmMagMax30d)) {
                $swarmMagMax30d = $mag;
            }
            if (is_float($lat) && is_float($lon)) {
                $swarmCenterLatSum += $lat;
                $swarmCenterLonSum += $lon;
                $swarmCenterN += 1;
            }
            if ($swarmStrongest === null) {
                $swarmStrongest = $row;
            } else {
                $rowMag = is_float($mag) ? $mag : -999.0;
                $bestMag = isset($swarmStrongest['magnitude']) && is_numeric($swarmStrongest['magnitude']) ? (float) $swarmStrongest['magnitude'] : -999.0;
                $rowTs = is_int($ts) ? $ts : 0;
                $bestTs = isset($swarmStrongest['event_time_ts']) && is_numeric($swarmStrongest['event_time_ts']) ? (int) $swarmStrongest['event_time_ts'] : 0;
                if ($rowMag > $bestMag || ($rowMag === $bestMag && $rowTs > $bestTs)) {
                    $swarmStrongest = $row;
                }
            }
        }

        $swarmDetail = [
            'swarm_id' => $swarmRequestedId,
            'region' => $swarmTopRegion,
            'events_24h' => count($swarmEvents24h),
            'events_7d' => count($swarmEvents7d),
            'events_30d' => count($swarmEvents30d),
            'max_magnitude_24h' => $swarmMagMax24h,
            'max_magnitude_30d' => $swarmMagMax30d,
            'avg_depth_km_24h' => $swarmDepthN > 0 ? round($swarmDepthSum / $swarmDepthN, 1) : null,
            'center_lat' => $swarmCenterN > 0 ? round($swarmCenterLatSum / $swarmCenterN, 4) : null,
            'center_lon' => $swarmCenterN > 0 ? round($swarmCenterLonSum / $swarmCenterN, 4) : null,
            'first_event_utc' => is_int($swarmFirstTs) ? gmdate('c', $swarmFirstTs) : null,
            'last_event_utc' => is_int($swarmLastTs) ? gmdate('c', $swarmLastTs) : null,
            'series_14d' => $swarmSeries14d,
            'hourly_24h' => array_map(static fn(int $count, int $hour): array => [
                'hour_utc' => sprintf('%02d', $hour),
                'count' => $count,
            ], $swarmHourly, array_keys($swarmHourly)),
            'magnitude_bands_24h' => $swarmMagBands,
            'depth_bands_24h' => $swarmDepthBands,
            'strongest_event' => $swarmStrongest,
            'events' => array_slice($swarmEvents30d, 0, 500),
        ];
    }
}

$payload = [
    'ok' => true,
    'provider' => $sourceMode,
    'generated_at_ts' => $nowTs,
    'generated_at' => gmdate('c', $nowTs),
    'window' => [
        'events_24h_start' => gmdate('c', $start24hTs),
        'events_24h_end' => gmdate('c', $nowTs),
    ],
    'events_count_24h' => count($events24h),
    'max_magnitude_24h' => $maxMag,
    'estimated_energy_j_24h' => $totalEnergyJ,
    'top_region_24h' => $topRegion,
    'baseline' => [
        'today_count' => $todayCount,
        'daily_avg_prev30' => $baselinePrev30,
        'delta_pct' => $deltaPct,
        'state' => $baselineState,
        'method' => $baselineMethod,
    ],
    'series_7d' => $series7,
    'series_30d' => $series30,
    'magnitude_bands_24h' => $magBands,
    'depth_bands_24h' => $depthBands,
    'swarms_24h' => $swarmCandidates,
    'requested_swarm_id' => $swarmRequestedId !== '' ? $swarmRequestedId : null,
    'swarm_detail' => $swarmDetail,
    'events' => array_slice($events24h, 0, 1500),
    'from_cache' => false,
    'stale_cache' => false,
];

if (!write_json_file($cachePath, $payload)) {
    write_log($appConfig['logs_dir'], 'Failed writing italy_earthquakes_latest cache JSON');
}

json_response(200, $payload);
