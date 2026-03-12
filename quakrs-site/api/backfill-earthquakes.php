<?php
declare(strict_types=1);

$__debugFlag = isset($_GET['debug']) && (string) $_GET['debug'] === '1';
if ($__debugFlag) {
    register_shutdown_function(static function (): void {
        $e = error_get_last();
        if (!is_array($e)) {
            return;
        }
        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array((int) ($e['type'] ?? 0), $fatalTypes, true)) {
            return;
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'ok' => false,
            'error' => 'Fatal error in backfill endpoint',
            'fatal' => [
                'type' => $e['type'] ?? null,
                'message' => $e['message'] ?? '',
                'file' => $e['file'] ?? '',
                'line' => $e['line'] ?? null,
            ],
        ], JSON_UNESCAPED_SLASHES);
    });
}

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/earthquakes-archive-lib.php';

function normalize_date_value(string $value, string $fallback): string
{
    $raw = trim($value);
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

function parse_ymd_utc(?string $value): ?DateTimeImmutable
{
    if (!is_string($value) || trim($value) === '') {
        return null;
    }
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
    return $dt instanceof DateTimeImmutable ? $dt : null;
}

function add_days_ymd(string $ymd, int $days): string
{
    $dt = parse_ymd_utc($ymd);
    if (!$dt instanceof DateTimeImmutable) {
        return $ymd;
    }
    if ($days === 0) {
        return $dt->format('Y-m-d');
    }
    $sign = $days > 0 ? '+' : '';
    return $dt->modify($sign . $days . ' days')->format('Y-m-d');
}

function inclusive_days_between(string $fromYmd, string $toYmd): int
{
    $from = parse_ymd_utc($fromYmd);
    $to = parse_ymd_utc($toYmd);
    if (!$from instanceof DateTimeImmutable || !$to instanceof DateTimeImmutable) {
        return 1;
    }
    $diff = $from->diff($to);
    if ((int) $diff->invert === 1) {
        return 0;
    }
    return ((int) $diff->days) + 1;
}

function usgs_query_url(array $params): string
{
    return 'https://earthquake.usgs.gov/fdsnws/event/1/query?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

function fetch_json_diagnostic(string $url, int $timeoutSeconds): array
{
    $result = [
        'ok' => false,
        'status' => 0,
        'error' => '',
        'body_excerpt' => '',
        'json' => null,
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_USERAGENT => 'QuakrsAPI/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        $result['status'] = $status;
        $result['error'] = $errno !== 0 ? ("cURL #" . $errno . ": " . $error) : '';
        $result['body_excerpt'] = is_string($body) ? substr($body, 0, 220) : '';
        if ($errno === 0 && is_string($body)) {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                $result['ok'] = true;
                $result['json'] = $decoded;
            }
        }
        return $result;
    }

    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeoutSeconds,
            'header' => "User-Agent: QuakrsAPI/1.0\r\n",
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    $result['body_excerpt'] = is_string($body) ? substr($body, 0, 220) : '';
    if (is_string($body)) {
        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            $result['ok'] = true;
            $result['json'] = $decoded;
        } else {
            $result['error'] = 'Unable to decode upstream JSON';
        }
    } else {
        $result['error'] = 'Unable to fetch upstream URL';
    }
    return $result;
}

function parse_usgs_events(array $features): array
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

        $timeRaw = $properties['time'] ?? null;
        if (!is_numeric($timeRaw)) {
            continue;
        }

        $timeMs = (int) $timeRaw;
        $timeTs = (int) floor($timeMs / 1000);

        if ($timeMs === 0) {
            continue;
        }

        $eventTimeUtc = (new DateTimeImmutable('@' . $timeTs))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('c');

        $rows[] = [
            'id' => (string) ($feature['id'] ?? ''),
            'place' => (string) ($properties['place'] ?? 'Unknown location'),
            'magnitude' => isset($properties['mag']) && is_numeric($properties['mag']) ? (float) $properties['mag'] : null,
            'depth_km' => isset($coords[2]) && is_numeric($coords[2]) ? abs((float) $coords[2]) : null,
            'latitude' => isset($coords[1]) && is_numeric($coords[1]) ? (float) $coords[1] : null,
            'longitude' => isset($coords[0]) && is_numeric($coords[0]) ? (float) $coords[0] : null,
            'event_time_utc' => $eventTimeUtc,
            'source_url' => (string) ($properties['url'] ?? ''),
            'source_provider' => 'USGS',
            'source_providers' => ['USGS'],
        ];
    }
    return $rows;
}

function checkpoint_read(string $path): ?array
{
    $decoded = read_json_file($path);
    return is_array($decoded) ? $decoded : null;
}

function checkpoint_write(string $path, array $state): void
{
    $state['updated_at'] = gmdate('c');
    write_json_file($path, $state);
}

function db_count_rows(mysqli $db, string $table): int
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

$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';
if (!$forceRefresh) {
    json_response(400, ['ok' => false, 'error' => 'Missing required query parameter: force_refresh=1']);
}

$requiredToken = trim((string) ($appConfig['refresh_token'] ?? ''));
if ($requiredToken !== '') {
    $requestToken = (string) ($_GET['token'] ?? '');
    if (!hash_equals($requiredToken, $requestToken)) {
        json_response(403, ['ok' => false, 'error' => 'Invalid refresh token']);
    }
}

$startDate = normalize_date_value((string) ($_GET['start'] ?? '1900-01-01'), '1900-01-01');
$hasEndParam = isset($_GET['end']) && trim((string) $_GET['end']) !== '';
$requestedEndDate = normalize_date_value((string) ($_GET['end'] ?? gmdate('Y-m-d')), gmdate('Y-m-d'));
$minMagnitudeRaw = trim((string) ($_GET['min_magnitude'] ?? ''));
$minMagnitude = $minMagnitudeRaw !== '' && is_numeric($minMagnitudeRaw) ? max(-1.0, (float) $minMagnitudeRaw) : null;
$maxWindowDays = max(1, min(90, (int) ($_GET['max_window_days'] ?? 14)));
$maxEventsPerWindow = max(200, min(50000, (int) ($_GET['max_events_per_window'] ?? 12000)));
$pageSize = max(50, min(1000, (int) ($_GET['page_size'] ?? 300)));
$reset = isset($_GET['reset']) && (string) $_GET['reset'] === '1';
$debug = isset($_GET['debug']) && (string) $_GET['debug'] === '1';

$startDateObj = parse_ymd_utc($startDate);
$endDate = $requestedEndDate;
$endDateObj = parse_ymd_utc($endDate);
if (!$startDateObj instanceof DateTimeImmutable || !$endDateObj instanceof DateTimeImmutable || strcmp($startDate, $endDate) > 0) {
    json_response(400, ['ok' => false, 'error' => 'Invalid date range']);
}

$archiveReason = null;
$db = earthquake_archive_open($appConfig, $archiveReason);
if (!$db instanceof mysqli) {
    json_response(503, [
        'ok' => false,
        'error' => 'Archive database unavailable',
        'reason' => $archiveReason ?: 'not configured',
    ]);
}
$archiveCfg = earthquake_archive_mysql_config($appConfig);
$table = (string) ($archiveCfg['table'] ?? 'earthquake_events');

$checkpointPath = $appConfig['data_dir'] . '/backfill_earthquakes_http_checkpoint.json';
$state = $reset ? null : checkpoint_read($checkpointPath);

if (!is_array($state) || ($state['mode'] ?? '') !== 'http_backfill_v1') {
    $state = [
        'mode' => 'http_backfill_v1',
        'range_start' => $startDate,
        'range_end' => $requestedEndDate,
        'min_magnitude' => $minMagnitude,
        'page_size' => $pageSize,
        'max_window_days' => $maxWindowDays,
        'max_events_per_window' => $maxEventsPerWindow,
        'cursor_date' => $startDate,
        'count_mode' => 'known',
        'window_start' => null,
        'window_end' => null,
        'window_events' => 0,
        'window_pages' => 0,
        'next_page' => 1,
        'requests_total' => 0,
        'pages_total' => 0,
        'rows_written_total' => 0,
    ];
}

$endDate = is_string($state['range_end'] ?? null) ? (string) $state['range_end'] : $requestedEndDate;
if ($hasEndParam) {
    $endDate = $requestedEndDate;
}
$endDateObj = parse_ymd_utc($endDate);
if (!$endDateObj instanceof DateTimeImmutable || strcmp($startDate, $endDate) > 0) {
    $endDate = $requestedEndDate;
    $state['range_end'] = $endDate;
}

$mustResetRange = $state['range_start'] !== $startDate
    || ($hasEndParam && $state['range_end'] !== $endDate);
if ($mustResetRange) {
    $state['range_start'] = $startDate;
    $state['range_end'] = $endDate;
    $state['cursor_date'] = $startDate;
    $state['count_mode'] = 'known';
    $state['window_start'] = null;
    $state['window_end'] = null;
    $state['window_events'] = 0;
    $state['window_pages'] = 0;
    $state['next_page'] = 1;
}
$state['min_magnitude'] = $minMagnitude;
$state['page_size'] = $pageSize;
$state['max_window_days'] = $maxWindowDays;
$state['max_events_per_window'] = $maxEventsPerWindow;
if (!isset($state['count_mode']) || !is_string($state['count_mode'])) {
    $state['count_mode'] = 'known';
}

$cursorDate = is_string($state['cursor_date'] ?? null) ? (string) $state['cursor_date'] : $startDate;
if (!(parse_ymd_utc($cursorDate) instanceof DateTimeImmutable) || strcmp($cursorDate, $startDate) < 0) {
    $cursorDate = $startDate;
    $state['cursor_date'] = $startDate;
}

if (strcmp($cursorDate, $endDate) > 0) {
    checkpoint_write($checkpointPath, $state);
    $db->close();
    json_response(200, [
        'ok' => true,
        'done' => true,
        'message' => 'Historical backfill already complete for selected range',
        'state' => $state,
    ]);
}

$needsWindowResolution = !is_string($state['window_start'])
    || !is_string($state['window_end'])
    || ((int) $state['window_pages'] <= 0 && (string) ($state['count_mode'] ?? 'known') !== 'unknown');

if ($needsWindowResolution) {
    $remainingDays = inclusive_days_between($cursorDate, $endDate);
    $candidateDays = max(1, min($remainingDays, (int) $state['max_window_days']));
    $windowResolved = false;

    while ($candidateDays >= 1) {
        $wStart = $cursorDate;
        $wEndCandidate = add_days_ymd($wStart, $candidateDays - 1);
        $wEnd = strcmp($wEndCandidate, $endDate) <= 0 ? $wEndCandidate : $endDate;

        $countParams = [
            'format' => 'geojson',
            'starttime' => $wStart,
            'endtime' => $wEnd,
            'limit' => 1,
            'offset' => 1,
        ];
        if ($minMagnitude !== null) {
            $countParams['minmagnitude'] = number_format($minMagnitude, 2, '.', '');
        }

        $countUrl = usgs_query_url($countParams);
        $countResult = fetch_json_diagnostic($countUrl, (int) $appConfig['http_timeout_seconds']);
        $state['requests_total'] = (int) ($state['requests_total'] ?? 0) + 1;

        $countPayload = is_array($countResult['json'] ?? null) ? $countResult['json'] : null;
        if (!is_array($countPayload)) {
            $db->close();
            $payload = [
                'ok' => false,
                'error' => 'Unable to count USGS events for current window',
                'window_start' => $wStart,
                'window_end' => $wEnd,
            ];
            if ($debug) {
                $payload['upstream'] = [
                    'url' => $countUrl,
                    'status' => (int) ($countResult['status'] ?? 0),
                    'error' => (string) ($countResult['error'] ?? ''),
                    'body_excerpt' => (string) ($countResult['body_excerpt'] ?? ''),
                ];
            }
            json_response(502, $payload);
        }

        $hasCount = isset($countPayload['metadata']) && is_array($countPayload['metadata']) && array_key_exists('count', $countPayload['metadata']);
        if (!$hasCount) {
            // Some USGS responses omit metadata.count: process pages until one returns fewer rows than page_size.
            $state['count_mode'] = 'unknown';
            $state['window_start'] = $wStart;
            $state['window_end'] = $wEnd;
            $state['window_events'] = -1;
            $state['window_pages'] = 0;
            $state['next_page'] = 1;
            $windowResolved = true;
            break;
        }

        $state['count_mode'] = 'known';
        $events = max(0, (int) ($countPayload['metadata']['count'] ?? 0));
        if ($events > (int) $state['max_events_per_window'] && $candidateDays > 1) {
            $candidateDays = max(1, (int) floor($candidateDays / 2));
            continue;
        }

        $state['window_start'] = $wStart;
        $state['window_end'] = $wEnd;
        $state['window_events'] = $events;
        $state['window_pages'] = $events > 0 ? (int) ceil($events / (int) $state['page_size']) : 0;
        $state['next_page'] = 1;
        $windowResolved = true;
        break;
    }

    if (!$windowResolved) {
        $db->close();
        json_response(500, ['ok' => false, 'error' => 'Unable to resolve a valid backfill window']);
    }
}

$windowPages = (int) ($state['window_pages'] ?? 0);
$countMode = (string) ($state['count_mode'] ?? 'known');
$unknownCount = $countMode === 'unknown';
$rowsWritten = 0;
$rowsInsertedApprox = 0;
$rowsUpdatedApprox = 0;
$rowsFetchedThisPage = 0;

if ($windowPages > 0 || $unknownCount) {
    $page = max(1, (int) ($state['next_page'] ?? 1));
    if (!$unknownCount) {
        $page = min($windowPages, $page);
    }
    $offset = (($page - 1) * (int) $state['page_size']) + 1;

    $pageParams = [
        'format' => 'geojson',
        'starttime' => (string) $state['window_start'],
        'endtime' => (string) $state['window_end'],
        'orderby' => 'time-asc',
        'limit' => (int) $state['page_size'],
        'offset' => $offset,
    ];
    if ($minMagnitude !== null) {
        $pageParams['minmagnitude'] = number_format($minMagnitude, 2, '.', '');
    }

    $pageUrl = usgs_query_url($pageParams);
    $pageResult = fetch_json_diagnostic($pageUrl, (int) $appConfig['http_timeout_seconds']);
    $state['requests_total'] = (int) ($state['requests_total'] ?? 0) + 1;
    $pagePayload = is_array($pageResult['json'] ?? null) ? $pageResult['json'] : null;
    if (!is_array($pagePayload) || !isset($pagePayload['features']) || !is_array($pagePayload['features'])) {
        $db->close();
        $payload = [
            'ok' => false,
            'error' => 'Unable to load USGS page for current window',
            'window_start' => $state['window_start'],
            'window_end' => $state['window_end'],
            'page' => $page,
        ];
        if ($debug) {
            $payload['upstream'] = [
                'url' => $pageUrl,
                'status' => (int) ($pageResult['status'] ?? 0),
                'error' => (string) ($pageResult['error'] ?? ''),
                'body_excerpt' => (string) ($pageResult['body_excerpt'] ?? ''),
            ];
        }
        json_response(502, $payload);
    }

    $events = parse_usgs_events($pagePayload['features']);
    $rowsFetchedThisPage = count($events);
    $before = db_count_rows($db, $table);
    $rowsWritten = $rowsFetchedThisPage > 0 ? earthquake_archive_ingest($db, $events, time(), $table) : 0;
    $after = db_count_rows($db, $table);
    if ($after >= $before) {
        $rowsInsertedApprox = max(0, $after - $before);
        $rowsUpdatedApprox = max(0, $rowsWritten - $rowsInsertedApprox);
    }

    $state['rows_written_total'] = (int) ($state['rows_written_total'] ?? 0) + $rowsWritten;
    $state['pages_total'] = (int) ($state['pages_total'] ?? 0) + 1;
    $state['next_page'] = $page + 1;
}

$windowCompleted = $unknownCount
    ? ($rowsFetchedThisPage < (int) $state['page_size'])
    : ($windowPages === 0 || (int) $state['next_page'] > $windowPages);
if ($windowCompleted) {
    $state['cursor_date'] = add_days_ymd((string) $state['window_end'], 1);
    $state['count_mode'] = 'known';
    $state['window_start'] = null;
    $state['window_end'] = null;
    $state['window_events'] = 0;
    $state['window_pages'] = 0;
    $state['next_page'] = 1;
}

$done = strcmp((string) $state['cursor_date'], $endDate) > 0;

checkpoint_write($checkpointPath, $state);
$db->close();

json_response(200, [
    'ok' => true,
    'done' => $done,
    'range_start' => $startDate,
    'range_end' => $endDate,
    'checkpoint_path' => basename($checkpointPath),
    'processed' => [
        'rows_written' => $rowsWritten,
        'rows_inserted_approx' => $rowsInsertedApprox,
        'rows_updated_approx' => $rowsUpdatedApprox,
    ],
    'state' => [
        'cursor_date' => $state['cursor_date'],
        'window_start' => $state['window_start'],
        'window_end' => $state['window_end'],
        'count_mode' => $state['count_mode'],
        'window_events' => (int) ($state['window_events'] ?? 0),
        'window_pages' => (int) ($state['window_pages'] ?? 0),
        'next_page' => (int) ($state['next_page'] ?? 1),
        'requests_total' => (int) ($state['requests_total'] ?? 0),
        'pages_total' => (int) ($state['pages_total'] ?? 0),
        'rows_written_total' => (int) ($state['rows_written_total'] ?? 0),
    ],
    'generated_at' => gmdate('c'),
]);
