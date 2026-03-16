#!/usr/bin/env php
<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be executed from CLI.\n");
    exit(1);
}

require __DIR__ . '/../api/bootstrap.php';
require __DIR__ . '/../api/earthquakes-archive-lib.php';

function cli_arg(array $argv, string $name, ?string $default = null): ?string
{
    foreach ($argv as $arg) {
        if (!str_starts_with($arg, '--' . $name . '=')) {
            continue;
        }
        return substr($arg, strlen($name) + 3);
    }
    return $default;
}

function cli_bool(array $argv, string $name): bool
{
    return in_array('--' . $name, $argv, true);
}

function normalize_date_arg(?string $value, string $fallback): string
{
    $raw = trim((string) $value);
    if ($raw === '') {
        return $fallback;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1) {
        return $raw;
    }
    $dt = date_create_immutable($raw, new DateTimeZone('UTC'));
    if (!$dt instanceof DateTimeImmutable) {
        return $fallback;
    }
    return $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d');
}

function source_config(string $source): array
{
    $key = strtolower(trim($source));
    if ($key === 'ingv') {
        return [
            'key' => 'ingv',
            'provider' => 'INGV',
            'base_url' => 'https://webservices.ingv.it/fdsnws/event/1/query',
        ];
    }
    return [
        'key' => 'usgs',
        'provider' => 'USGS',
        'base_url' => 'https://earthquake.usgs.gov/fdsnws/event/1/query',
    ];
}

function source_query_url(array $sourceCfg, array $params): string
{
    return (string) $sourceCfg['base_url'] . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

function normalize_feature_time_utc(mixed $rawTime): ?string
{
    if (is_numeric($rawTime)) {
        $time = (int) $rawTime;
        if ($time > 9999999999) {
            $time = (int) floor($time / 1000);
        }
        if ($time === 0) {
            return null;
        }
        return gmdate('c', $time);
    }

    if (!is_string($rawTime) || trim($rawTime) === '') {
        return null;
    }
    $parsed = strtotime(trim($rawTime));
    return is_int($parsed) ? gmdate('c', $parsed) : null;
}

function parse_geojson_features(array $features, string $provider): array
{
    $events = [];
    foreach ($features as $feature) {
        if (!is_array($feature)) {
            continue;
        }
        $properties = $feature['properties'] ?? null;
        $geometry = $feature['geometry'] ?? null;
        $coordinates = is_array($geometry) ? ($geometry['coordinates'] ?? null) : null;
        if (!is_array($properties) || !is_array($coordinates) || count($coordinates) < 3) {
            continue;
        }

        $eventTimeUtc = normalize_feature_time_utc($properties['time'] ?? null);
        if (!is_string($eventTimeUtc) || $eventTimeUtc === '') {
            continue;
        }

        $mag = isset($properties['mag']) && is_numeric($properties['mag']) ? (float) $properties['mag'] : null;
        $depth = isset($coordinates[2]) && is_numeric($coordinates[2]) ? (float) $coordinates[2] : null;
        $lat = isset($coordinates[1]) && is_numeric($coordinates[1]) ? (float) $coordinates[1] : null;
        $lon = isset($coordinates[0]) && is_numeric($coordinates[0]) ? (float) $coordinates[0] : null;

        $events[] = [
            'id' => (string) ($feature['id'] ?? ''),
            'place' => (string) ($properties['place'] ?? 'Unknown location'),
            'magnitude' => $mag,
            'depth_km' => $depth !== null ? abs($depth) : null,
            'latitude' => $lat,
            'longitude' => $lon,
            'event_time_utc' => $eventTimeUtc,
            'source_url' => (string) ($properties['url'] ?? ''),
            'source_provider' => $provider,
            'source_providers' => [$provider],
        ];
    }
    return $events;
}

function db_total_count(mysqli $db, string $table): int
{
    $safeTable = preg_match('/^[a-zA-Z0-9_]+$/', $table) ? $table : 'earthquake_events';
    $res = $db->query(sprintf('SELECT COUNT(*) AS c FROM `%s`', $safeTable));
    if (!$res instanceof mysqli_result) {
        return 0;
    }
    $row = $res->fetch_assoc();
    $res->free();
    return is_array($row) ? (int) ($row['c'] ?? 0) : 0;
}

function load_checkpoint(string $path): ?array
{
    $data = read_json_file($path);
    return is_array($data) ? $data : null;
}

function save_checkpoint(string $path, array $payload): void
{
    $tmp = $payload;
    $tmp['updated_at'] = gmdate('c');
    write_json_file($path, $tmp);
}

function choose_window_days(int $remainingDays, int $maxWindowDays): int
{
    return max(1, min($remainingDays, $maxWindowDays));
}

function print_help(): void
{
    $help = <<<TXT
Usage:
  php scripts/backfill-earthquakes-history.php [options]

Options:
  --source=usgs|ingv            Default: usgs
  --start=YYYY-MM-DD            Default: 1900-01-01
  --end=YYYY-MM-DD              Default: today (UTC)
  --min-magnitude=FLOAT         Optional USGS filter
  --max-window-days=INT         Default: 31
  --max-events-per-window=INT   Default: 15000 (window is split if exceeded)
  --page-size=INT               Default: 500 (max 20000)
  --checkpoint=PATH             Default: data/backfill_earthquakes_usgs_checkpoint.json
  --resume                      Resume from checkpoint if available
  --reset-checkpoint            Ignore and overwrite checkpoint start
  --dry-run                     Fetch/count only, no DB writes
  --help                        Show this help

Examples:
  php scripts/backfill-earthquakes-history.php --resume
  php scripts/backfill-earthquakes-history.php --source=ingv --resume
  php scripts/backfill-earthquakes-history.php --start=1970-01-01 --min-magnitude=3.0
TXT;
    fwrite(STDOUT, $help . PHP_EOL);
}

if (cli_bool($argv, 'help')) {
    print_help();
    exit(0);
}

$sourceInput = trim((string) cli_arg($argv, 'source', 'usgs'));
$sourceCfg = source_config($sourceInput);
$startDate = normalize_date_arg(cli_arg($argv, 'start'), '1900-01-01');
$endDate = normalize_date_arg(cli_arg($argv, 'end'), gmdate('Y-m-d'));
$minMagnitudeRaw = cli_arg($argv, 'min-magnitude');
$minMagnitude = ($minMagnitudeRaw !== null && is_numeric($minMagnitudeRaw))
    ? max(-1.0, (float) $minMagnitudeRaw)
    : null;
$maxWindowDays = max(1, min(365, (int) (cli_arg($argv, 'max-window-days', '31') ?? '31')));
$maxEventsPerWindow = max(200, min(50000, (int) (cli_arg($argv, 'max-events-per-window', '15000') ?? '15000')));
$pageSize = max(50, min(20000, (int) (cli_arg($argv, 'page-size', '500') ?? '500')));
$resume = cli_bool($argv, 'resume');
$resetCheckpoint = cli_bool($argv, 'reset-checkpoint');
$dryRun = cli_bool($argv, 'dry-run');

$defaultCheckpointPath = $appConfig['data_dir'] . '/backfill_earthquakes_' . $sourceCfg['key'] . '_checkpoint.json';
$checkpointPath = trim((string) cli_arg($argv, 'checkpoint', $defaultCheckpointPath));
if ($checkpointPath === '') {
    $checkpointPath = $defaultCheckpointPath;
}

$startTs = strtotime($startDate . ' 00:00:00 UTC');
$endTs = strtotime($endDate . ' 00:00:00 UTC');
if (!is_int($startTs) || !is_int($endTs) || $startTs > $endTs) {
    fwrite(STDERR, "Invalid date range: start={$startDate} end={$endDate}\n");
    exit(1);
}

if ($resume && !$resetCheckpoint) {
    $checkpoint = load_checkpoint($checkpointPath);
    if (is_array($checkpoint) && isset($checkpoint['next_start']) && is_string($checkpoint['next_start'])) {
        $candidate = normalize_date_arg($checkpoint['next_start'], $startDate);
        $candidateTs = strtotime($candidate . ' 00:00:00 UTC');
        if (is_int($candidateTs) && $candidateTs >= $startTs && $candidateTs <= $endTs) {
            $startDate = $candidate;
            $startTs = $candidateTs;
        }
    }
}

$archiveReason = null;
$db = null;
$table = 'earthquake_events';
if (!$dryRun) {
    $db = earthquake_archive_open($appConfig, $archiveReason);
    if (!$db instanceof mysqli) {
        fwrite(STDERR, "Archive DB unavailable: " . ($archiveReason ?: 'unknown') . "\n");
        exit(1);
    }
    $cfg = earthquake_archive_mysql_config($appConfig);
    $table = (string) ($cfg['table'] ?? 'earthquake_events');
}

$cursorTs = $startTs;
$globalFetched = 0;
$globalWritten = 0;
$globalInsertedEstimate = 0;
$globalUpdatedEstimate = 0;
$requestCount = 0;
$windowCount = 0;
$hadError = false;

fwrite(STDOUT, sprintf(
    "Backfill start | source=%s | range=%s..%s | min_mag=%s | max_window_days=%d | max_events_per_window=%d | page_size=%d | dry_run=%s\n",
    $sourceCfg['provider'],
    $startDate,
    $endDate,
    $minMagnitude !== null ? (string) $minMagnitude : 'all',
    $maxWindowDays,
    $maxEventsPerWindow,
    $pageSize,
    $dryRun ? 'yes' : 'no'
));

while ($cursorTs <= $endTs) {
    $remainingDays = (int) floor(($endTs - $cursorTs) / 86400) + 1;
    $windowDays = choose_window_days($remainingDays, $maxWindowDays);
    $windowStartTs = $cursorTs;
    $windowEndTs = min($endTs, $windowStartTs + (($windowDays - 1) * 86400));
    $windowStart = gmdate('Y-m-d', $windowStartTs);
    $windowEnd = gmdate('Y-m-d', $windowEndTs);

    $baseParams = [
        'format' => 'geojson',
        'starttime' => $windowStart,
        'endtime' => $windowEnd,
    ];
    if ($minMagnitude !== null) {
        $baseParams['minmagnitude'] = number_format($minMagnitude, 2, '.', '');
    }

    $countParams = $baseParams;
    $countParams['limit'] = 1;
    $countParams['offset'] = 1;
    $countPayload = fetch_external_json(source_query_url($sourceCfg, $countParams), (int) $appConfig['http_timeout_seconds']);
    $requestCount += 1;

    if (!is_array($countPayload)) {
        fwrite(STDERR, sprintf("Count failed for %s..%s\n", $windowStart, $windowEnd));
        $hadError = true;
        break;
    }

    $hasCount = isset($countPayload['metadata']) && is_array($countPayload['metadata']) && array_key_exists('count', $countPayload['metadata']);
    $totalEvents = $hasCount ? max(0, (int) ($countPayload['metadata']['count'] ?? 0)) : -1;
    if ($hasCount && $totalEvents > $maxEventsPerWindow && $windowDays > 1) {
        $maxWindowDays = max(1, (int) floor($windowDays / 2));
        fwrite(STDOUT, sprintf(
            "Split window %s..%s (events=%d > %d), new max_window_days=%d\n",
            $windowStart,
            $windowEnd,
            $totalEvents,
            $maxEventsPerWindow,
            $maxWindowDays
        ));
        continue;
    }

    $windowCount += 1;
    if ($hasCount && $totalEvents === 0) {
        fwrite(STDOUT, sprintf("[%d] %s..%s | events=0\n", $windowCount, $windowStart, $windowEnd));
        $cursorTs = $windowEndTs + 86400;
        save_checkpoint($checkpointPath, [
            'next_start' => gmdate('Y-m-d', $cursorTs),
            'last_window_start' => $windowStart,
            'last_window_end' => $windowEnd,
            'last_window_events' => 0,
            'requests' => $requestCount,
        ]);
        continue;
    }

    $unknownCount = !$hasCount;
    $totalPages = $unknownCount ? 0 : max(1, (int) ceil($totalEvents / $pageSize));
    $windowFetched = 0;
    $windowWritten = 0;
    $windowInsertedEstimate = 0;

    $beforeCount = (!$dryRun && $db instanceof mysqli) ? db_total_count($db, $table) : 0;
    for ($page = 1; $unknownCount || $page <= $totalPages; $page++) {
        $offset = (($page - 1) * $pageSize) + 1;
        $pageParams = $baseParams;
        $pageParams['orderby'] = 'time-asc';
        $pageParams['limit'] = $pageSize;
        $pageParams['offset'] = $offset;
        $payload = fetch_external_json(source_query_url($sourceCfg, $pageParams), (int) $appConfig['http_timeout_seconds']);
        $requestCount += 1;

        if (!is_array($payload) || !isset($payload['features']) || !is_array($payload['features'])) {
            fwrite(STDERR, sprintf("Page failed for %s..%s page=%d/%d\n", $windowStart, $windowEnd, $page, $totalPages));
            $hadError = true;
            break 2;
        }

        $events = parse_geojson_features($payload['features'], (string) $sourceCfg['provider']);
        $fetched = count($events);
        $windowFetched += $fetched;
        $globalFetched += $fetched;

        if (!$dryRun && $db instanceof mysqli && $fetched > 0) {
            $written = earthquake_archive_ingest($db, $events, time(), $table);
            $windowWritten += $written;
            $globalWritten += $written;
        }

        if ($unknownCount && $fetched < $pageSize) {
            break;
        }
    }

    $afterCount = (!$dryRun && $db instanceof mysqli) ? db_total_count($db, $table) : 0;
    if (!$dryRun && $afterCount >= $beforeCount) {
        $windowInsertedEstimate = $afterCount - $beforeCount;
        $windowInsertedEstimate = max(0, $windowInsertedEstimate);
        $windowUpdatedEstimate = max(0, $windowWritten - $windowInsertedEstimate);
        $globalInsertedEstimate += $windowInsertedEstimate;
        $globalUpdatedEstimate += $windowUpdatedEstimate;
    } else {
        $windowUpdatedEstimate = 0;
    }

    fwrite(STDOUT, sprintf(
        "[%d] %s..%s | events=%s | fetched=%d | written=%d | inserted~=%d | updated~=%d\n",
        $windowCount,
        $windowStart,
        $windowEnd,
        $unknownCount ? 'unknown' : (string) $totalEvents,
        $windowFetched,
        $windowWritten,
        $windowInsertedEstimate,
        $windowUpdatedEstimate
    ));

    $cursorTs = $windowEndTs + 86400;
    save_checkpoint($checkpointPath, [
        'next_start' => gmdate('Y-m-d', $cursorTs),
        'last_window_start' => $windowStart,
        'last_window_end' => $windowEnd,
        'last_window_events' => $unknownCount ? null : $totalEvents,
        'last_window_fetched' => $windowFetched,
        'last_window_written' => $windowWritten,
        'requests' => $requestCount,
    ]);
}

if ($db instanceof mysqli) {
    $db->close();
}

fwrite(STDOUT, sprintf(
    "Backfill %s | windows=%d | requests=%d | fetched=%d | written=%d | inserted~=%d | updated~=%d | checkpoint=%s\n",
    $hadError ? 'stopped_with_errors' : 'done',
    $windowCount,
    $requestCount,
    $globalFetched,
    $globalWritten,
    $globalInsertedEstimate,
    $globalUpdatedEstimate,
    $checkpointPath
));

exit($hadError ? 1 : 0);
