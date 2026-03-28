<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/earthquakes-archive-lib.php';

function baseline_cache_read(string $path): ?array
{
    $raw = @file_get_contents($path);
    if (!is_string($raw) || $raw === '') {
        return null;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function baseline_cache_write(string $path, array $payload): void
{
    write_json_file($path, $payload);
}

function baseline_state(float $deltaPct): string
{
    if ($deltaPct >= 35.0) {
        return 'Above normal';
    }
    if ($deltaPct <= -25.0) {
        return 'Below normal';
    }
    return 'Within normal';
}

function count_mysql_range(mysqli $db, string $table, int $fromTs, int $toTs, float $minMag): ?int
{
    $sql = sprintf(
        'SELECT COUNT(*) AS c
         FROM `%s`
         WHERE event_time_ts >= ?
           AND event_time_ts <= ?
           AND magnitude IS NOT NULL
           AND magnitude >= ?',
        $table
    );
    $stmt = $db->prepare($sql);
    if (!$stmt instanceof mysqli_stmt) {
        return null;
    }
    if (!$stmt->bind_param('iid', $fromTs, $toTs, $minMag) || !$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $result = $stmt->get_result();
    $row = $result instanceof mysqli_result ? $result->fetch_assoc() : null;
    if ($result instanceof mysqli_result) {
        $result->free();
    }
    $stmt->close();
    return is_array($row) ? (int) ($row['c'] ?? 0) : 0;
}

function count_usgs_range(int $fromTs, int $toTs, float $minMag, int $timeoutSeconds): ?int
{
    $params = [
        'format' => 'geojson',
        'starttime' => gmdate('Y-m-d\TH:i:s\Z', $fromTs),
        'endtime' => gmdate('Y-m-d\TH:i:s\Z', $toTs),
        'minmagnitude' => number_format($minMag, 1, '.', ''),
        'limit' => 1,
        'offset' => 1,
    ];
    $url = 'https://earthquake.usgs.gov/fdsnws/event/1/query?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    $payload = fetch_external_json($url, $timeoutSeconds);
    if (!is_array($payload) || !isset($payload['metadata']) || !is_array($payload['metadata'])) {
        return null;
    }
    if (!array_key_exists('count', $payload['metadata'])) {
        return null;
    }
    return max(0, (int) ($payload['metadata']['count'] ?? 0));
}

function build_baseline_payload(callable $counter, float $minMag, string $source): ?array
{
    $nowTs = time();
    $todayStartTs = (int) gmdate('U', strtotime(gmdate('Y-m-d 00:00:00', $nowTs) . ' UTC'));
    if ($todayStartTs <= 0) {
        return null;
    }

    $yesterdayEndTs = $todayStartTs - 1;
    $baselineStartTs = $todayStartTs - (30 * 86400);

    $todayCount = $counter($todayStartTs, $nowTs, $minMag);
    $baselineTotal30d = $counter($baselineStartTs, $yesterdayEndTs, $minMag);
    if (!is_int($todayCount) || !is_int($baselineTotal30d)) {
        return null;
    }

    $baselineDailyAvg = $baselineTotal30d / 30.0;
    $deltaPct = $baselineDailyAvg > 0 ? (($todayCount - $baselineDailyAvg) / $baselineDailyAvg) * 100.0 : 0.0;

    $weekRows = [];
    for ($i = 6; $i >= 0; $i--) {
        $dayStart = $todayStartTs - ($i * 86400);
        $dayEnd = $dayStart + 86399;
        $count = $counter($dayStart, $dayEnd, $minMag);
        if (!is_int($count)) {
            return null;
        }
        $weekRows[] = [
            'date_utc' => gmdate('Y-m-d', $dayStart),
            'label' => gmdate('D', $dayStart),
            'value' => $count,
        ];
    }

    return [
        'ok' => true,
        'provider' => 'Quakrs baseline model',
        'generated_at_ts' => $nowTs,
        'generated_at' => gmdate('c', $nowTs),
        'source' => $source,
        'min_magnitude' => $minMag,
        'today_count' => $todayCount,
        'baseline_total_30d' => $baselineTotal30d,
        'baseline_daily_avg' => $baselineDailyAvg,
        'baseline_delta_pct' => $deltaPct,
        'baseline_state' => baseline_state($deltaPct),
        'week_daily_counts' => $weekRows,
        'from_cache' => false,
        'stale_cache' => false,
    ];
}

function build_live_proxy_payload(string $dataDir, float $minMag): ?array
{
    $latestPath = $dataDir . '/earthquakes_latest.json';
    $latest = read_json_file($latestPath);
    $events = is_array($latest) && isset($latest['events']) && is_array($latest['events'])
        ? $latest['events']
        : null;
    if (!is_array($events) || count($events) === 0) {
        return null;
    }

    $nowTs = time();
    $todayStartTs = (int) gmdate('U', strtotime(gmdate('Y-m-d 00:00:00', $nowTs) . ' UTC'));
    if ($todayStartTs <= 0) {
        return null;
    }

    $todayCount = 0;
    $recent24hCount = 0;
    $weekBuckets = [];
    for ($i = 6; $i >= 0; $i--) {
        $dayStart = $todayStartTs - ($i * 86400);
        $weekBuckets[gmdate('Y-m-d', $dayStart)] = [
            'date_utc' => gmdate('Y-m-d', $dayStart),
            'label' => gmdate('D', $dayStart),
            'value' => 0,
        ];
    }

    foreach ($events as $event) {
        if (!is_array($event)) {
            continue;
        }
        $mag = isset($event['magnitude']) && is_numeric($event['magnitude']) ? (float) $event['magnitude'] : null;
        if (!is_float($mag) || $mag < $minMag) {
            continue;
        }
        $ts = isset($event['event_time_utc']) ? strtotime((string) $event['event_time_utc']) : false;
        if (!is_int($ts)) {
            continue;
        }
        if ($ts >= $todayStartTs) {
            $todayCount += 1;
        }
        if ($ts >= ($nowTs - 86400)) {
            $recent24hCount += 1;
        }
        $dayKey = gmdate('Y-m-d', $ts);
        if (isset($weekBuckets[$dayKey])) {
            $weekBuckets[$dayKey]['value'] += 1;
        }
    }

    $baselineDailyAvg = max(1.0, (float) $recent24hCount);
    $deltaPct = (($todayCount - $baselineDailyAvg) / $baselineDailyAvg) * 100.0;

    return [
        'ok' => true,
        'provider' => 'Quakrs baseline model',
        'generated_at_ts' => $nowTs,
        'generated_at' => gmdate('c', $nowTs),
        'source' => 'live-proxy',
        'min_magnitude' => $minMag,
        'today_count' => $todayCount,
        'baseline_total_30d' => null,
        'baseline_daily_avg' => $baselineDailyAvg,
        'baseline_delta_pct' => $deltaPct,
        'baseline_state' => baseline_state($deltaPct),
        'week_daily_counts' => array_values($weekBuckets),
        'from_cache' => false,
        'stale_cache' => false,
    ];
}

function live_today_mag_count(string $dataDir, float $minMag): int
{
    $latest = read_json_file($dataDir . '/earthquakes_latest.json');
    $events = is_array($latest) && isset($latest['events']) && is_array($latest['events'])
        ? $latest['events']
        : [];
    $nowTs = time();
    $todayStartTs = (int) gmdate('U', strtotime(gmdate('Y-m-d 00:00:00', $nowTs) . ' UTC'));
    $count = 0;
    foreach ($events as $event) {
        if (!is_array($event)) {
            continue;
        }
        $mag = isset($event['magnitude']) && is_numeric($event['magnitude']) ? (float) $event['magnitude'] : null;
        if (!is_float($mag) || $mag < $minMag) {
            continue;
        }
        $ts = isset($event['event_time_utc']) ? strtotime((string) $event['event_time_utc']) : false;
        if (is_int($ts) && $ts >= $todayStartTs) {
            $count += 1;
        }
    }
    return $count;
}

$minMagRaw = trim((string) ($_GET['min_magnitude'] ?? '2.5'));
$minMag = is_numeric($minMagRaw) ? max(-1.0, (float) $minMagRaw) : 2.5;
$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';

$cachePath = $appConfig['data_dir'] . '/energy_baseline_latest.json';
if (abs($minMag - 2.5) > 0.00001) {
    $cachePath = sprintf(
        '%s/energy_baseline_latest_m%s.json',
        $appConfig['data_dir'],
        str_replace('.', '_', number_format($minMag, 1, '.', ''))
    );
}
$cacheTtl = 3600;
$cached = baseline_cache_read($cachePath);
$cacheAge = is_array($cached) && isset($cached['generated_at_ts']) ? time() - (int) $cached['generated_at_ts'] : null;
if (!$forceRefresh && is_array($cached) && is_int($cacheAge) && $cacheAge <= $cacheTtl) {
    $cacheUsable = true;
    $source = strtolower((string) ($cached['source'] ?? ''));
    $cachedToday = isset($cached['today_count']) && is_numeric($cached['today_count']) ? (int) $cached['today_count'] : 0;
    $liveToday = live_today_mag_count($appConfig['data_dir'], $minMag);
    if ($cachedToday === 0 && $liveToday > 0) {
        $cacheUsable = false;
    }
    if ($cacheUsable) {
        $cached['from_cache'] = true;
        $cached['stale_cache'] = false;
        json_response(200, $cached);
    }
}

$payload = null;

$archiveReason = null;
$mysql = earthquake_archive_open($appConfig, $archiveReason);
if ($mysql instanceof mysqli) {
    $archiveCfg = earthquake_archive_mysql_config($appConfig);
    $table = (string) ($archiveCfg['table'] ?? 'earthquake_events');
    $payload = build_baseline_payload(
        static fn(int $fromTs, int $toTs, float $minMagnitude): ?int
            => count_mysql_range($mysql, $table, $fromTs, $toTs, $minMagnitude),
        $minMag,
        'archive-mysql'
    );
    $mysql->close();
}

if (!is_array($payload)) {
    $payload = build_baseline_payload(
        static fn(int $fromTs, int $toTs, float $minMagnitude): ?int
            => count_usgs_range($fromTs, $toTs, $minMagnitude, max(8, (int) ($appConfig['http_timeout_seconds'] ?? 12))),
        $minMag,
        'usgs-fdsn-count'
    );
}

if (is_array($payload)) {
    baseline_cache_write($cachePath, $payload);
    json_response(200, $payload);
}

$proxyPayload = build_live_proxy_payload($appConfig['data_dir'], $minMag);
if (is_array($proxyPayload)) {
    baseline_cache_write($cachePath, $proxyPayload);
    json_response(200, $proxyPayload);
}

if (is_array($cached)) {
    $cached['from_cache'] = true;
    $cached['stale_cache'] = true;
    $cached['error'] = 'Baseline recomputation failed, serving stale cache';
    json_response(200, $cached);
}

json_response(503, [
    'ok' => false,
    'error' => 'Baseline unavailable',
    'reason' => $archiveReason ?: 'no cache and no archive source',
]);
