<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/earthquakes-archive-lib.php';

function italy_stats_queue_background_refresh(array $appConfig, string $cachePath): bool
{
    $token = trim((string) ($appConfig['refresh_token'] ?? ''));
    $baseUrl = trim((string) ($appConfig['public_base_url'] ?? ''));
    if ($token === '' || $baseUrl === '' || !function_exists('shell_exec')) {
        return false;
    }

    $lockPath = dirname($cachePath) . '/italy_statistics_refresh.lock';
    $lock = @fopen($lockPath, 'c');
    if (!is_resource($lock)) {
        return false;
    }
    if (!@flock($lock, LOCK_EX | LOCK_NB)) {
        @fclose($lock);
        return false;
    }

    $url = rtrim($baseUrl, '/') . '/api/italy-statistics.php?force_refresh=1&token=' . rawurlencode($token);
    $command = 'nohup curl -fsS --max-time 40 ' . escapeshellarg($url) . ' >/dev/null 2>&1 &';
    @shell_exec($command);

    @flock($lock, LOCK_UN);
    @fclose($lock);
    return true;
}

function italy_stats_event_ts_expr(): string
{
    return 'COALESCE(
        CASE
            WHEN event_time_ts IS NULL OR event_time_ts <= 0 THEN NULL
            WHEN event_time_ts > 9999999999 THEN FLOOR(event_time_ts / 1000)
            ELSE event_time_ts
        END,
        UNIX_TIMESTAMP(event_time_utc)
    )';
}

function italy_stats_region_expr(): string
{
    return 'TRIM(CASE
        WHEN place LIKE "%% of %%" THEN SUBSTRING_INDEX(place, " of ", -1)
        WHEN place LIKE "%%,%%" THEN SUBSTRING_INDEX(place, ",", -1)
        ELSE place
    END)';
}

function italy_stats_ingv_filter_sql(): string
{
    return "LOWER(COALESCE(source_provider, '')) = 'ingv'";
}

function italy_stats_compact_monthly(array $rows): array
{
    $pairs = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $month = trim((string) ($row['month'] ?? ''));
        if ($month === '') {
            continue;
        }
        $pairs[$month] = ($pairs[$month] ?? 0) + (int) ($row['count'] ?? 0);
    }
    ksort($pairs);

    $out = [];
    foreach ($pairs as $month => $count) {
        $out[] = ['month' => $month, 'count' => (int) $count];
    }
    return $out;
}

function italy_stats_compact_yearly(array $rows): array
{
    $pairs = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $year = trim((string) ($row['year'] ?? ''));
        if ($year === '') {
            continue;
        }
        $pairs[$year] = ($pairs[$year] ?? 0) + (int) ($row['count'] ?? 0);
    }
    ksort($pairs);

    $out = [];
    foreach ($pairs as $year => $count) {
        $out[] = ['year' => $year, 'count' => (int) $count];
    }
    return $out;
}

function italy_stats_finalize_regions(array $rows): array
{
    usort($rows, static function (array $a, array $b): int {
        if ((int) ($b['count'] ?? 0) !== (int) ($a['count'] ?? 0)) {
            return ((int) ($b['count'] ?? 0)) <=> ((int) ($a['count'] ?? 0));
        }
        return strcmp((string) ($a['region'] ?? ''), (string) ($b['region'] ?? ''));
    });

    $total = 0;
    foreach ($rows as $row) {
        $total += (int) ($row['count'] ?? 0);
    }

    $out = [];
    foreach ($rows as $row) {
        $count = (int) ($row['count'] ?? 0);
        if ($count <= 0) {
            continue;
        }
        $region = trim((string) ($row['region'] ?? ''));
        if ($region === '') {
            $region = 'Unknown';
        }
        $share = $total > 0 ? round(($count / $total) * 100, 1) : 0.0;
        $maxMag = isset($row['max_magnitude']) && is_numeric($row['max_magnitude'])
            ? round((float) $row['max_magnitude'], 1)
            : null;
        $out[] = [
            'region' => $region,
            'count' => $count,
            'share_pct' => $share,
            'max_magnitude' => $maxMag,
        ];
    }

    return array_slice($out, 0, 15);
}

function italy_stats_fetch_region_rows(mysqli $db, string $sql): array
{
    $rows = [];
    $result = $db->query($sql);
    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = [
                'region' => trim((string) ($row['region_guess'] ?? '')),
                'count' => (int) ($row['c'] ?? 0),
                'max_magnitude' => is_numeric($row['max_magnitude'] ?? null) ? (float) $row['max_magnitude'] : null,
            ];
        }
        $result->free();
    }
    return $rows;
}

function italy_stats_build_last_12_months(array $monthlyAll, int $nowTs): array
{
    $countsByMonth = [];
    foreach ($monthlyAll as $row) {
        if (!is_array($row)) {
            continue;
        }
        $month = trim((string) ($row['month'] ?? ''));
        if ($month === '') {
            continue;
        }
        $countsByMonth[$month] = (int) ($row['count'] ?? 0);
    }

    $base = new DateTimeImmutable(gmdate('Y-m-01 00:00:00', $nowTs), new DateTimeZone('UTC'));
    $out = [];
    for ($i = 11; $i >= 0; $i--) {
        $slot = $base->modify('-' . $i . ' months');
        if (!$slot instanceof DateTimeImmutable) {
            continue;
        }
        $key = $slot->format('Y-m');
        $out[] = [
            'month' => $key,
            'count' => (int) ($countsByMonth[$key] ?? 0),
        ];
    }

    return $out;
}

function italy_stats_build_last_10_years(array $yearlyAll, int $nowTs): array
{
    $countsByYear = [];
    foreach ($yearlyAll as $row) {
        if (!is_array($row)) {
            continue;
        }
        $year = trim((string) ($row['year'] ?? ''));
        if ($year === '' || !preg_match('/^\d{4}$/', $year)) {
            continue;
        }
        $countsByYear[$year] = (int) ($row['count'] ?? 0);
    }

    $currentYear = (int) gmdate('Y', $nowTs);
    $out = [];
    for ($year = $currentYear - 9; $year <= $currentYear; $year++) {
        $key = (string) $year;
        $out[] = [
            'year' => $key,
            'count' => (int) ($countsByYear[$key] ?? 0),
        ];
    }

    return $out;
}

function italy_stats_cache_has_required_shape(array $cached): bool
{
    $monthly = $cached['monthly_counts_last12'] ?? null;
    $yearly = $cached['yearly_counts_last10'] ?? null;
    $rankings = $cached['region_rankings'] ?? null;
    if (!is_array($monthly) || count($monthly) !== 12) {
        return false;
    }
    if (!is_array($yearly) || count($yearly) !== 10) {
        return false;
    }
    if (!is_array($rankings)) {
        return false;
    }
    if (!is_array($rankings['month_current'] ?? null) || !is_array($rankings['year_current'] ?? null) || !is_array($rankings['all_time'] ?? null)) {
        return false;
    }
    return true;
}

function italy_stats_count_for_period(array $appConfig, string $fromDate, string $toDate, ?string &$errorReason = null): ?array
{
    $errorReason = null;
    $fromDate = trim($fromDate);
    $toDate = trim($toDate);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
        $errorReason = 'Invalid date format. Use YYYY-MM-DD';
        return null;
    }

    $fromTs = strtotime($fromDate . ' 00:00:00 UTC');
    $toTs = strtotime($toDate . ' 23:59:59 UTC');
    if (!is_int($fromTs) || !is_int($toTs) || $fromTs > $toTs) {
        $errorReason = 'Invalid date range';
        return null;
    }

    $reason = null;
    $db = earthquake_mysql_open($appConfig, 'archive', $reason);
    if (!$db instanceof mysqli) {
        $errorReason = is_string($reason) && $reason !== '' ? $reason : 'MySQL archive unavailable';
        return null;
    }

    $cfg = earthquake_mysql_role_config($appConfig, 'archive');
    $table = preg_match('/^[a-zA-Z0-9_]+$/', (string) ($cfg['table'] ?? '')) ? (string) $cfg['table'] : 'earthquake_events';
    $eventTsExpr = italy_stats_event_ts_expr();
    $regionExpr = italy_stats_region_expr();

    $providerFilter = italy_stats_ingv_filter_sql();
    $sql = sprintf(
        'SELECT COUNT(*) AS c
         FROM `%s`
         WHERE latitude BETWEEN 35.0 AND 48.8
           AND longitude BETWEEN 6.0 AND 19.6
           AND %s
           AND %s IS NOT NULL
           AND %s BETWEEN ? AND ?',
        $table,
        $providerFilter,
        $eventTsExpr,
        $eventTsExpr
    );

    $stmt = $db->prepare($sql);
    if (!$stmt instanceof mysqli_stmt) {
        $db->close();
        $errorReason = 'Prepare failed';
        return null;
    }

    $ok = $stmt->bind_param('ii', $fromTs, $toTs) && $stmt->execute();
    $count = 0;
    if ($ok) {
        $result = $stmt->get_result();
        if ($result instanceof mysqli_result) {
            $row = $result->fetch_assoc();
            $count = is_array($row) && is_numeric($row['c'] ?? null) ? (int) $row['c'] : 0;
            $result->free();
        }
    }

    $stmt->close();
    if (!$ok) {
        $db->close();
        $errorReason = 'Query failed';
        return null;
    }

    $regionsSql = sprintf(
        'SELECT %s AS region_guess, COUNT(*) AS c, MAX(magnitude) AS max_magnitude
         FROM `%s`
         WHERE latitude BETWEEN 35.0 AND 48.8
           AND longitude BETWEEN 6.0 AND 19.6
           AND %s
           AND %s IS NOT NULL
           AND %s BETWEEN ? AND ?
         GROUP BY region_guess
         ORDER BY c DESC, region_guess ASC
         LIMIT 50',
        $regionExpr,
        $table,
        $providerFilter,
        $eventTsExpr,
        $eventTsExpr
    );

    $regionsStmt = $db->prepare($regionsSql);
    if (!$regionsStmt instanceof mysqli_stmt) {
        $db->close();
        $errorReason = 'Prepare regions query failed';
        return null;
    }

    $regionsOk = $regionsStmt->bind_param('ii', $fromTs, $toTs) && $regionsStmt->execute();
    $regionsRows = [];
    if ($regionsOk) {
        $regionsResult = $regionsStmt->get_result();
        if ($regionsResult instanceof mysqli_result) {
            while ($row = $regionsResult->fetch_assoc()) {
                $regionsRows[] = [
                    'region' => trim((string) ($row['region_guess'] ?? '')),
                    'count' => (int) ($row['c'] ?? 0),
                    'max_magnitude' => is_numeric($row['max_magnitude'] ?? null) ? (float) $row['max_magnitude'] : null,
                ];
            }
            $regionsResult->free();
        }
    }
    $regionsStmt->close();
    $db->close();

    if (!$regionsOk) {
        $errorReason = 'Regions query failed';
        return null;
    }

    $regionsPeriod = italy_stats_finalize_regions($regionsRows);

    return [
        'from' => $fromDate,
        'to' => $toDate,
        'events_total_period' => $count,
        'top_regions_period' => $regionsPeriod,
    ];
}

function italy_stats_from_mysql(array $appConfig, ?string &$errorReason = null): ?array
{
    $errorReason = null;
    $reason = null;
    $db = earthquake_mysql_open($appConfig, 'archive', $reason);
    if (!$db instanceof mysqli) {
        $errorReason = is_string($reason) && $reason !== '' ? $reason : 'MySQL archive unavailable';
        return null;
    }

    $cfg = earthquake_mysql_role_config($appConfig, 'archive');
    $table = preg_match('/^[a-zA-Z0-9_]+$/', (string) ($cfg['table'] ?? '')) ? (string) $cfg['table'] : 'earthquake_events';
    $eventTsExpr = italy_stats_event_ts_expr();
    $regionExpr = italy_stats_region_expr();
    $providerFilter = italy_stats_ingv_filter_sql();
    $currentMonthKey = gmdate('Y-m');
    $currentYearKey = gmdate('Y');

    $monthlySql = sprintf(
        'SELECT DATE_FORMAT(FROM_UNIXTIME(%s), "%%Y-%%m") AS month_utc, COUNT(*) AS c
         FROM `%s`
         WHERE latitude BETWEEN 35.0 AND 48.8
           AND longitude BETWEEN 6.0 AND 19.6
           AND %s
           AND %s IS NOT NULL
         GROUP BY month_utc
         ORDER BY month_utc ASC',
        $eventTsExpr,
        $table,
        $providerFilter,
        $eventTsExpr
    );

    $yearlySql = sprintf(
        'SELECT DATE_FORMAT(FROM_UNIXTIME(%s), "%%Y") AS year_utc, COUNT(*) AS c
         FROM `%s`
         WHERE latitude BETWEEN 35.0 AND 48.8
           AND longitude BETWEEN 6.0 AND 19.6
           AND %s
           AND %s IS NOT NULL
         GROUP BY year_utc
         ORDER BY year_utc ASC',
        $eventTsExpr,
        $table,
        $providerFilter,
        $eventTsExpr
    );

    $regionsAllSql = sprintf(
        'SELECT %s AS region_guess, COUNT(*) AS c, MAX(magnitude) AS max_magnitude
         FROM `%s`
         WHERE latitude BETWEEN 35.0 AND 48.8
           AND longitude BETWEEN 6.0 AND 19.6
           AND %s
           AND place IS NOT NULL
           AND place <> ""
           AND %s IS NOT NULL
         GROUP BY region_guess
         ORDER BY c DESC, region_guess ASC
         LIMIT 50',
        $regionExpr,
        $table,
        $providerFilter,
        $eventTsExpr
    );

    $regionsMonthSql = sprintf(
        'SELECT %s AS region_guess, COUNT(*) AS c, MAX(magnitude) AS max_magnitude
         FROM `%s`
         WHERE latitude BETWEEN 35.0 AND 48.8
           AND longitude BETWEEN 6.0 AND 19.6
           AND %s
           AND place IS NOT NULL
           AND place <> ""
           AND %s IS NOT NULL
           AND DATE_FORMAT(FROM_UNIXTIME(%s), "%%Y-%%m") = %s
         GROUP BY region_guess
         ORDER BY c DESC, region_guess ASC
         LIMIT 50',
        $regionExpr,
        $table,
        $providerFilter,
        $eventTsExpr,
        $eventTsExpr,
        "'" . $db->real_escape_string($currentMonthKey) . "'"
    );

    $regionsYearSql = sprintf(
        'SELECT %s AS region_guess, COUNT(*) AS c, MAX(magnitude) AS max_magnitude
         FROM `%s`
         WHERE latitude BETWEEN 35.0 AND 48.8
           AND longitude BETWEEN 6.0 AND 19.6
           AND %s
           AND place IS NOT NULL
           AND place <> ""
           AND %s IS NOT NULL
           AND DATE_FORMAT(FROM_UNIXTIME(%s), "%%Y") = %s
         GROUP BY region_guess
         ORDER BY c DESC, region_guess ASC
         LIMIT 50',
        $regionExpr,
        $table,
        $providerFilter,
        $eventTsExpr,
        $eventTsExpr,
        "'" . $db->real_escape_string($currentYearKey) . "'"
    );

    $metaSql = sprintf(
        'SELECT MIN(%s) AS min_ts, MAX(%s) AS max_ts, COUNT(*) AS c
         FROM `%s`
         WHERE latitude BETWEEN 35.0 AND 48.8
           AND longitude BETWEEN 6.0 AND 19.6
           AND %s
           AND %s IS NOT NULL',
        $eventTsExpr,
        $eventTsExpr,
        $table,
        $providerFilter,
        $eventTsExpr
    );

    $monthlyRows = [];
    $result = $db->query($monthlySql);
    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $month = trim((string) ($row['month_utc'] ?? ''));
            if ($month === '') {
                continue;
            }
            $monthlyRows[] = ['month' => $month, 'count' => (int) ($row['c'] ?? 0)];
        }
        $result->free();
    }

    $yearlyRows = [];
    $result = $db->query($yearlySql);
    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $year = trim((string) ($row['year_utc'] ?? ''));
            if ($year === '') {
                continue;
            }
            $yearlyRows[] = ['year' => $year, 'count' => (int) ($row['c'] ?? 0)];
        }
        $result->free();
    }

    $regionsAll = italy_stats_finalize_regions(italy_stats_fetch_region_rows($db, $regionsAllSql));
    $regionsMonth = italy_stats_finalize_regions(italy_stats_fetch_region_rows($db, $regionsMonthSql));
    $regionsYear = italy_stats_finalize_regions(italy_stats_fetch_region_rows($db, $regionsYearSql));

    $minTs = null;
    $maxTs = null;
    $totalEvents = 0;
    $result = $db->query($metaSql);
    if ($result instanceof mysqli_result) {
        $meta = $result->fetch_assoc();
        if (is_array($meta)) {
            $minTs = is_numeric($meta['min_ts'] ?? null) ? (int) $meta['min_ts'] : null;
            $maxTs = is_numeric($meta['max_ts'] ?? null) ? (int) $meta['max_ts'] : null;
            $totalEvents = is_numeric($meta['c'] ?? null) ? (int) $meta['c'] : 0;
        }
        $result->free();
    }

    $db->close();

    // Overlay recent period from LIVE DB to avoid stale/lagging archive values
    // on current-month/current-year stats and rankings.
    // Keep overlay queries index-friendly to avoid timeouts on large tables.
    $liveReason = null;
    $liveDb = earthquake_mysql_open($appConfig, 'live', $liveReason);
    if ($liveDb instanceof mysqli) {
        $liveCfg = earthquake_mysql_role_config($appConfig, 'live');
        $liveTable = preg_match('/^[a-zA-Z0-9_]+$/', (string) ($liveCfg['table'] ?? '')) ? (string) $liveCfg['table'] : 'earthquake_events';
        $nowSec = time();
        $nowMs = $nowSec * 1000;
        $start15Sec = (int) strtotime(gmdate('Y-m-01 00:00:00', strtotime('-14 months')));
        $start15Ms = $start15Sec * 1000;
        $startYearSec = (int) strtotime(gmdate('Y-01-01 00:00:00'));
        $startYearMs = $startYearSec * 1000;
        $monthStartSec = (int) strtotime(gmdate('Y-m-01 00:00:00'));
        $monthStartMs = $monthStartSec * 1000;

        $liveMonthlySql = sprintf(
            'SELECT CASE
                WHEN event_time_ts > 9999999999 THEN DATE_FORMAT(FROM_UNIXTIME(FLOOR(event_time_ts / 1000)), "%%Y-%%m")
                ELSE DATE_FORMAT(FROM_UNIXTIME(event_time_ts), "%%Y-%%m")
             END AS month_utc,
             COUNT(*) AS c
             FROM `%s`
             WHERE latitude BETWEEN 35.0 AND 48.8
               AND longitude BETWEEN 6.0 AND 19.6
               AND %s
               AND event_time_ts IS NOT NULL
               AND (
                 (event_time_ts BETWEEN %d AND %d)
                 OR
                 (event_time_ts BETWEEN %d AND %d)
               )
             GROUP BY month_utc',
            $liveTable,
            $providerFilter,
            $start15Sec,
            $nowSec,
            $start15Ms,
            $nowMs
        );
        $liveYearSql = sprintf(
            'SELECT %s AS year_utc, COUNT(*) AS c
             FROM `%s`
             WHERE latitude BETWEEN 35.0 AND 48.8
               AND longitude BETWEEN 6.0 AND 19.6
               AND %s
               AND event_time_ts IS NOT NULL
               AND (
                 (event_time_ts BETWEEN %d AND %d)
                 OR
                 (event_time_ts BETWEEN %d AND %d)
               )',
            "'" . $liveDb->real_escape_string($currentYearKey) . "'",
            $liveTable,
            $providerFilter,
            $startYearSec,
            $nowSec,
            $startYearMs,
            $nowMs
        );
        $liveRegionsMonthSql = sprintf(
            'SELECT %s AS region_guess, COUNT(*) AS c, MAX(magnitude) AS max_magnitude
             FROM `%s`
             WHERE latitude BETWEEN 35.0 AND 48.8
               AND longitude BETWEEN 6.0 AND 19.6
               AND %s
               AND place IS NOT NULL
               AND place <> ""
               AND event_time_ts IS NOT NULL
               AND (
                 (event_time_ts BETWEEN %d AND %d)
                 OR
                 (event_time_ts BETWEEN %d AND %d)
               )
             GROUP BY region_guess
             ORDER BY c DESC, region_guess ASC
             LIMIT 50',
            $regionExpr,
            $liveTable,
            $providerFilter,
            $monthStartSec,
            $nowSec,
            $monthStartMs,
            $nowMs
        );
        $liveRegionsYearSql = sprintf(
            'SELECT %s AS region_guess, COUNT(*) AS c, MAX(magnitude) AS max_magnitude
             FROM `%s`
             WHERE latitude BETWEEN 35.0 AND 48.8
               AND longitude BETWEEN 6.0 AND 19.6
               AND %s
               AND place IS NOT NULL
               AND place <> ""
               AND event_time_ts IS NOT NULL
               AND (
                 (event_time_ts BETWEEN %d AND %d)
                 OR
                 (event_time_ts BETWEEN %d AND %d)
               )
             GROUP BY region_guess
             ORDER BY c DESC, region_guess ASC
             LIMIT 50',
            $regionExpr,
            $liveTable,
            $providerFilter,
            $startYearSec,
            $nowSec,
            $startYearMs,
            $nowMs
        );

        $monthlyByKey = [];
        foreach ($monthlyRows as $row) {
            $key = (string) ($row['month'] ?? '');
            if ($key !== '') {
                $monthlyByKey[$key] = (int) ($row['count'] ?? 0);
            }
        }
        $resultLiveMonth = $liveDb->query($liveMonthlySql);
        if ($resultLiveMonth instanceof mysqli_result) {
            while ($row = $resultLiveMonth->fetch_assoc()) {
                $key = trim((string) ($row['month_utc'] ?? ''));
                if ($key === '') {
                    continue;
                }
                $liveCount = (int) ($row['c'] ?? 0);
                $monthlyByKey[$key] = max((int) ($monthlyByKey[$key] ?? 0), $liveCount);
            }
            $resultLiveMonth->free();
        }
        ksort($monthlyByKey);
        $monthlyRows = [];
        foreach ($monthlyByKey as $key => $count) {
            $monthlyRows[] = ['month' => $key, 'count' => $count];
        }

        $yearlyByKey = [];
        foreach ($yearlyRows as $row) {
            $key = (string) ($row['year'] ?? '');
            if ($key !== '') {
                $yearlyByKey[$key] = (int) ($row['count'] ?? 0);
            }
        }
        $resultLiveYear = $liveDb->query($liveYearSql);
        if ($resultLiveYear instanceof mysqli_result) {
            while ($row = $resultLiveYear->fetch_assoc()) {
                $key = trim((string) ($row['year_utc'] ?? ''));
                if ($key === '') {
                    continue;
                }
                $liveCount = (int) ($row['c'] ?? 0);
                $yearlyByKey[$key] = max((int) ($yearlyByKey[$key] ?? 0), $liveCount);
            }
            $resultLiveYear->free();
        }
        ksort($yearlyByKey);
        $yearlyRows = [];
        foreach ($yearlyByKey as $key => $count) {
            $yearlyRows[] = ['year' => $key, 'count' => $count];
        }

        $liveMonthRegions = italy_stats_finalize_regions(italy_stats_fetch_region_rows($liveDb, $liveRegionsMonthSql));
        $archiveMonthRegionsTotal = array_reduce($regionsMonth, static fn (int $carry, array $row): int => $carry + (int) ($row['count'] ?? 0), 0);
        $liveMonthRegionsTotal = array_reduce($liveMonthRegions, static fn (int $carry, array $row): int => $carry + (int) ($row['count'] ?? 0), 0);
        if ($liveMonthRegionsTotal > $archiveMonthRegionsTotal) {
            $regionsMonth = $liveMonthRegions;
        }
        $liveYearRegions = italy_stats_finalize_regions(italy_stats_fetch_region_rows($liveDb, $liveRegionsYearSql));
        $archiveYearRegionsTotal = array_reduce($regionsYear, static fn (int $carry, array $row): int => $carry + (int) ($row['count'] ?? 0), 0);
        $liveYearRegionsTotal = array_reduce($liveYearRegions, static fn (int $carry, array $row): int => $carry + (int) ($row['count'] ?? 0), 0);
        if ($liveYearRegionsTotal > $archiveYearRegionsTotal) {
            $regionsYear = $liveYearRegions;
        }

        $liveDb->close();
    }

    if (count($monthlyRows) === 0 || count($yearlyRows) === 0) {
        $errorReason = 'No monthly/yearly rows from MySQL archive';
        return null;
    }

    return [
        'provider' => 'Archive MySQL',
        'monthly_counts' => italy_stats_compact_monthly($monthlyRows),
        'yearly_counts' => italy_stats_compact_yearly($yearlyRows),
        'top_regions' => $regionsAll,
        'region_rankings' => [
            'month_current' => $regionsMonth,
            'year_current' => $regionsYear,
            'all_time' => $regionsAll,
            'month_key' => $currentMonthKey,
            'year_key' => $currentYearKey,
        ],
        'meta' => [
            'events_total' => $totalEvents,
            'range_start_utc' => is_int($minTs) ? gmdate('c', $minTs) : null,
            'range_end_utc' => is_int($maxTs) ? gmdate('c', $maxTs) : null,
        ],
    ];
}

$nowTs = time();
$cachePath = $appConfig['data_dir'] . '/italy_statistics_latest.json';
$cacheTtl = max(300, min(21600, (int) ($appConfig['italy_statistics_cache_ttl_seconds'] ?? 3600)));
$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';
$compactResponse = isset($_GET['compact']) && (string) $_GET['compact'] === '1';
$periodFrom = isset($_GET['from']) ? trim((string) $_GET['from']) : '';
$periodTo = isset($_GET['to']) ? trim((string) $_GET['to']) : '';

if ($forceRefresh) {
    require_refresh_token($appConfig);
}

if ($periodFrom !== '' || $periodTo !== '') {
    $periodError = null;
    $period = italy_stats_count_for_period($appConfig, $periodFrom, $periodTo, $periodError);
    if (!is_array($period)) {
        json_response(400, [
            'ok' => false,
            'error' => 'Unable to compute custom period total',
            'reason' => is_string($periodError) && $periodError !== '' ? $periodError : 'unknown',
        ]);
    }

    json_response(200, [
        'ok' => true,
        'provider' => 'Archive MySQL',
        'mode' => 'custom_period',
        'from' => $period['from'],
        'to' => $period['to'],
        'events_total_period' => $period['events_total_period'],
        'top_regions_period' => $period['top_regions_period'] ?? [],
        'generated_at' => gmdate('c', $nowTs),
    ]);
}

$cached = read_json_file($cachePath);
$cacheAge = is_array($cached) && isset($cached['generated_at_ts']) ? ($nowTs - (int) $cached['generated_at_ts']) : null;
if (!$forceRefresh && is_array($cached) && ((string) ($cached['provider'] ?? '')) === 'Archive MySQL') {
    $cacheValid = italy_stats_cache_has_required_shape($cached);
    if ($cacheValid && is_int($cacheAge) && $cacheAge <= $cacheTtl) {
        $cached['from_cache'] = true;
        $cached['stale_cache'] = false;
        json_response(200, $cached);
    }

    $refreshQueued = italy_stats_queue_background_refresh($appConfig, $cachePath);
    if ($cacheValid) {
        $cached['from_cache'] = true;
        $cached['stale_cache'] = true;
        $cached['refresh_queued'] = $refreshQueued;
        json_response(200, $cached);
    }
}

$mysqlErrorReason = null;
$result = italy_stats_from_mysql($appConfig, $mysqlErrorReason);

if (!is_array($result)) {
    if (is_array($cached) && ((string) ($cached['provider'] ?? '')) === 'Archive MySQL' && italy_stats_cache_has_required_shape($cached)) {
        $cached['from_cache'] = true;
        $cached['stale_cache'] = true;
        json_response(200, $cached);
    }
    json_response(502, [
        'ok' => false,
        'error' => 'Unable to compute Italy statistics from archive MySQL',
        'reason' => is_string($mysqlErrorReason) && $mysqlErrorReason !== '' ? $mysqlErrorReason : 'unknown',
    ]);
}

$monthlyAll = is_array($result['monthly_counts'] ?? null) ? $result['monthly_counts'] : [];
$yearlyAll = is_array($result['yearly_counts'] ?? null) ? $result['yearly_counts'] : [];
$monthlyLast12 = italy_stats_build_last_12_months($monthlyAll, $nowTs);
$yearlyLast10 = italy_stats_build_last_10_years($yearlyAll, $nowTs);
$regionsTop = is_array($result['top_regions'] ?? null) ? $result['top_regions'] : [];
$regionRankings = is_array($result['region_rankings'] ?? null) ? $result['region_rankings'] : [];
$meta = is_array($result['meta'] ?? null) ? $result['meta'] : [];

$currentMonth = count($monthlyLast12) > 0 ? $monthlyLast12[count($monthlyLast12) - 1] : null;
$previousMonth = count($monthlyLast12) > 1 ? $monthlyLast12[count($monthlyLast12) - 2] : null;
$deltaCount = 0;
if (is_array($currentMonth) && is_array($previousMonth)) {
    $deltaCount = (int) ($currentMonth['count'] ?? 0) - (int) ($previousMonth['count'] ?? 0);
}

$payload = [
    'ok' => true,
    'provider' => (string) ($result['provider'] ?? 'Archive MySQL'),
    'generated_at_ts' => $nowTs,
    'generated_at' => gmdate('c', $nowTs),
    'monthly_counts' => $monthlyAll,
    'monthly_counts_last12' => $monthlyLast12,
    'yearly_counts' => $yearlyAll,
    'yearly_counts_last10' => $yearlyLast10,
    'top_regions' => $regionsTop,
    'region_rankings' => $regionRankings,
    'recap' => [
        'current_month' => $currentMonth,
        'previous_month' => $previousMonth,
        'delta_vs_previous_month' => $deltaCount,
        'top_region' => count($regionsTop) > 0 ? $regionsTop[0] : null,
    ],
    'meta' => [
        'events_total' => (int) ($meta['events_total'] ?? 0),
        'range_start_utc' => isset($meta['range_start_utc']) ? (string) $meta['range_start_utc'] : null,
        'range_end_utc' => isset($meta['range_end_utc']) ? (string) $meta['range_end_utc'] : null,
    ],
    'from_cache' => false,
    'stale_cache' => false,
];

if (!write_json_file($cachePath, $payload)) {
    write_log($appConfig['logs_dir'], 'Failed writing italy_statistics cache JSON');
}

if ($compactResponse) {
    json_response(200, [
        'ok' => true,
        'provider' => (string) ($payload['provider'] ?? 'Archive MySQL'),
        'generated_at' => (string) ($payload['generated_at'] ?? gmdate('c', $nowTs)),
        'events_total' => (int) ($payload['meta']['events_total'] ?? 0),
        'month' => (string) ($payload['recap']['current_month']['month'] ?? ''),
        'month_count' => (int) ($payload['recap']['current_month']['count'] ?? 0),
        'delta_vs_previous_month' => (int) ($payload['recap']['delta_vs_previous_month'] ?? 0),
    ]);
}

json_response(200, $payload);
