<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/earthquakes-archive-lib.php';

function query_int(string $key, int $default, int $min, int $max): int
{
    $raw = $_GET[$key] ?? null;
    if ($raw === null || $raw === '' || !is_numeric($raw)) {
        return $default;
    }
    $value = (int) $raw;
    return max($min, min($max, $value));
}

function query_float(string $key, float $default): float
{
    $raw = $_GET[$key] ?? null;
    if ($raw === null || $raw === '' || !is_numeric($raw)) {
        return $default;
    }
    return (float) $raw;
}

function ctx_cache_read(string $path): ?array
{
    $raw = @file_get_contents($path);
    if (!is_string($raw) || $raw === '') {
        return null;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function ctx_cache_write(string $path, array $payload): void
{
    write_json_file($path, $payload);
}

function percentile(array $values, float $percent): float
{
    if (count($values) === 0) {
        return 0.0;
    }
    sort($values, SORT_NUMERIC);
    $maxIndex = count($values) - 1;
    $rank = max(0.0, min(100.0, $percent)) / 100.0 * $maxIndex;
    $lower = (int) floor($rank);
    $upper = (int) ceil($rank);
    if ($lower === $upper) {
        return (float) $values[$lower];
    }
    $weight = $rank - $lower;
    return ((1.0 - $weight) * (float) $values[$lower]) + ($weight * (float) $values[$upper]);
}

function cell_key(float $lat, float $lon): string
{
    return sprintf('%.2f|%.2f', $lat, $lon);
}

function live_proxy_cells(string $dataDir, float $minMag, int $cellMultiplier): array
{
    $payload = read_json_file($dataDir . '/earthquakes_latest.json');
    $events = is_array($payload) && isset($payload['events']) && is_array($payload['events'])
        ? $payload['events']
        : [];

    $counts = [];
    foreach ($events as $event) {
        if (!is_array($event)) {
            continue;
        }
        $mag = isset($event['magnitude']) && is_numeric($event['magnitude']) ? (float) $event['magnitude'] : null;
        $lat = isset($event['latitude']) && is_numeric($event['latitude']) ? (float) $event['latitude'] : null;
        $lon = isset($event['longitude']) && is_numeric($event['longitude']) ? (float) $event['longitude'] : null;
        if (!is_float($mag) || !is_float($lat) || !is_float($lon) || $mag < $minMag) {
            continue;
        }
        $cellLat = floor($lat * $cellMultiplier) / $cellMultiplier;
        $cellLon = floor($lon * $cellMultiplier) / $cellMultiplier;
        $key = cell_key($cellLat, $cellLon);
        if (!isset($counts[$key])) {
            $counts[$key] = ['count' => 0];
        }
        $counts[$key]['count'] += 1;
    }

    $cells = [];
    foreach ($counts as $key => $row) {
        $count = (int) ($row['count'] ?? 0);
        $cells[$key] = [
            'count' => $count,
            'daily_avg' => (float) $count,
        ];
    }
    return $cells;
}

$days = query_int('days', 30, 7, 120);
$cellSizeRaw = query_float('cell_size', 1.0);
$minMag = max(1.0, min(5.0, query_float('min_magnitude', 2.5)));
$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';

$allowedCellSizes = [0.5, 1.0, 2.0];
$cellSize = 1.0;
foreach ($allowedCellSizes as $candidate) {
    if (abs($cellSizeRaw - $candidate) < 0.001) {
        $cellSize = $candidate;
        break;
    }
}
$cellMultiplier = (int) round(1.0 / $cellSize);

$cacheName = sprintf(
    '/seismicity_context_d%d_c%s_m%s.json',
    $days,
    str_replace('.', '_', number_format($cellSize, 1, '.', '')),
    str_replace('.', '_', number_format($minMag, 1, '.', ''))
);
$cachePath = $appConfig['data_dir'] . $cacheName;
$cacheTtl = 6 * 3600;

$cached = ctx_cache_read($cachePath);
$cacheAge = is_array($cached) && isset($cached['generated_at_ts']) ? time() - (int) $cached['generated_at_ts'] : null;
if (!$forceRefresh && is_array($cached) && is_int($cacheAge) && $cacheAge >= 0 && $cacheAge <= $cacheTtl) {
    $cached['from_cache'] = true;
    json_response(200, $cached);
}

$nowTs = time();
$fromTs = $nowTs - ($days * 86400);

$cells = [];
$source = 'archive-mysql';
$reason = null;
$db = earthquake_archive_open($appConfig, $reason);

if ($db instanceof mysqli) {
    $archiveCfg = earthquake_archive_mysql_config($appConfig);
    $table = (string) ($archiveCfg['table'] ?? 'earthquake_events');
    $safeTable = preg_match('/^[a-zA-Z0-9_]+$/', $table) ? $table : 'earthquake_events';
    $sql = sprintf(
        'SELECT FLOOR(latitude * %d) / %d AS cell_lat,
                FLOOR(longitude * %d) / %d AS cell_lon,
                COUNT(*) AS c
         FROM `%s`
         WHERE event_time_ts >= ?
           AND event_time_ts <= ?
           AND magnitude IS NOT NULL
           AND magnitude >= ?
           AND latitude IS NOT NULL
           AND longitude IS NOT NULL
         GROUP BY cell_lat, cell_lon',
        $cellMultiplier,
        $cellMultiplier,
        $cellMultiplier,
        $cellMultiplier,
        $safeTable
    );

    $stmt = $db->prepare($sql);
    if ($stmt instanceof mysqli_stmt && $stmt->bind_param('iid', $fromTs, $nowTs, $minMag) && $stmt->execute()) {
        $result = $stmt->get_result();
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                if (!is_array($row)) {
                    continue;
                }
                $lat = isset($row['cell_lat']) && is_numeric($row['cell_lat']) ? (float) $row['cell_lat'] : null;
                $lon = isset($row['cell_lon']) && is_numeric($row['cell_lon']) ? (float) $row['cell_lon'] : null;
                $count = isset($row['c']) && is_numeric($row['c']) ? (int) $row['c'] : 0;
                if (!is_float($lat) || !is_float($lon) || $count <= 0) {
                    continue;
                }
                $key = cell_key($lat, $lon);
                $cells[$key] = [
                    'count' => $count,
                    'daily_avg' => $count / max(1, $days),
                ];
            }
            $result->free();
        }
        $stmt->close();
    } else {
        $source = 'live-proxy';
    }
    $db->close();
} else {
    $source = 'live-proxy';
}

if ($source === 'live-proxy' || count($cells) === 0) {
    $cells = live_proxy_cells($appConfig['data_dir'], $minMag, $cellMultiplier);
    $source = 'live-proxy';
}

$rates = [];
foreach ($cells as $row) {
    $rate = isset($row['daily_avg']) && is_numeric($row['daily_avg']) ? (float) $row['daily_avg'] : 0.0;
    if ($rate > 0) {
        $rates[] = $rate;
    }
}

$payload = [
    'ok' => true,
    'provider' => 'Quakrs seismicity context model',
    'generated_at_ts' => $nowTs,
    'generated_at' => gmdate('c', $nowTs),
    'source' => $source,
    'baseline_days' => $source === 'archive-mysql' ? $days : 1,
    'cell_size_deg' => $cellSize,
    'min_magnitude' => $minMag,
    'cell_count' => count($cells),
    'distribution' => [
        'p30_daily_avg' => percentile($rates, 30.0),
        'p50_daily_avg' => percentile($rates, 50.0),
        'p70_daily_avg' => percentile($rates, 70.0),
        'p90_daily_avg' => percentile($rates, 90.0),
        'max_daily_avg' => count($rates) > 0 ? max($rates) : 0.0,
    ],
    'cells' => $cells,
    'from_cache' => false,
];

ctx_cache_write($cachePath, $payload);
json_response(200, $payload);
