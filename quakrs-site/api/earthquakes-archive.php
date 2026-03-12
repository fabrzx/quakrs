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

function query_float(string $key): ?float
{
    $raw = $_GET[$key] ?? null;
    if ($raw === null || $raw === '' || !is_numeric($raw)) {
        return null;
    }
    return (float) $raw;
}

function query_ts(string $key): ?int
{
    $raw = trim((string) ($_GET[$key] ?? ''));
    if ($raw === '') {
        return null;
    }
    $ts = strtotime($raw);
    return is_int($ts) ? $ts : null;
}

function mysqli_stmt_bind_dynamic(mysqli_stmt $stmt, string $types, array $values): bool
{
    if ($types === '' || count($values) === 0) {
        return true;
    }
    $refs = [];
    foreach ($values as $index => $value) {
        $refs[$index] = $value;
    }
    $args = [$types];
    foreach ($refs as $index => &$value) {
        $args[] = &$value;
    }
    return $stmt->bind_param(...$args);
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

$seedCount = 0;
$seedResult = $db->query(sprintf('SELECT COUNT(*) AS c FROM `%s`', $table));
if ($seedResult instanceof mysqli_result) {
    $seedRow = $seedResult->fetch_assoc();
    $seedCount = is_array($seedRow) ? (int) ($seedRow['c'] ?? 0) : 0;
    $seedResult->free();
}
if ($seedCount === 0) {
    $latestCache = read_json_file($appConfig['data_dir'] . '/earthquakes_latest.json');
    $latestEvents = is_array($latestCache) && isset($latestCache['events']) && is_array($latestCache['events'])
        ? $latestCache['events']
        : [];
    if (count($latestEvents) > 0) {
        earthquake_archive_ingest($db, $latestEvents, time(), $table);
    }
}

$page = query_int('page', 1, 1, 1000000);
$perPage = query_int('per_page', 120, 1, 500);
$offset = ($page - 1) * $perPage;

$fromTs = query_ts('from');
$toTs = query_ts('to');
$minMag = query_float('min_magnitude');
$maxMag = query_float('max_magnitude');
$minDepth = query_float('min_depth_km');
$maxDepth = query_float('max_depth_km');
$query = trim((string) ($_GET['q'] ?? ''));
$provider = trim((string) ($_GET['provider'] ?? ''));

$where = [];
$types = '';
$params = [];

if (is_int($fromTs)) {
    $where[] = 'event_time_ts >= ?';
    $types .= 'i';
    $params[] = $fromTs;
}
if (is_int($toTs)) {
    $where[] = 'event_time_ts <= ?';
    $types .= 'i';
    $params[] = $toTs;
}
if ($minMag !== null) {
    $where[] = 'magnitude >= ?';
    $types .= 'd';
    $params[] = $minMag;
}
if ($maxMag !== null) {
    $where[] = 'magnitude <= ?';
    $types .= 'd';
    $params[] = $maxMag;
}
if ($minDepth !== null) {
    $where[] = 'depth_km >= ?';
    $types .= 'd';
    $params[] = $minDepth;
}
if ($maxDepth !== null) {
    $where[] = 'depth_km <= ?';
    $types .= 'd';
    $params[] = $maxDepth;
}
if ($query !== '') {
    $where[] = 'LOWER(place) LIKE LOWER(?)';
    $types .= 's';
    $params[] = '%' . $query . '%';
}
if ($provider !== '') {
    $where[] = '(LOWER(source_provider) = LOWER(?) OR LOWER(source_providers_json) LIKE LOWER(?))';
    $types .= 'ss';
    $params[] = $provider;
    $params[] = '%"' . $provider . '"%';
}

$whereSql = count($where) > 0 ? ('WHERE ' . implode(' AND ', $where)) : '';

$countSql = sprintf('SELECT COUNT(*) AS c FROM `%s` %s', $table, $whereSql);
$countStmt = $db->prepare($countSql);
if (!$countStmt instanceof mysqli_stmt) {
    json_response(500, [
        'ok' => false,
        'error' => 'Unable to prepare count query',
    ]);
}
if (!mysqli_stmt_bind_dynamic($countStmt, $types, $params) || !$countStmt->execute()) {
    json_response(500, [
        'ok' => false,
        'error' => 'Unable to execute count query',
    ]);
}
$countResult = $countStmt->get_result();
$countRow = $countResult instanceof mysqli_result ? $countResult->fetch_assoc() : null;
$totalCount = is_array($countRow) ? (int) ($countRow['c'] ?? 0) : 0;
if ($countResult instanceof mysqli_result) {
    $countResult->free();
}
$countStmt->close();

$selectSql = sprintf(
    'SELECT event_id, place, magnitude, depth_km, latitude, longitude, event_time_utc, source_url, source_provider, source_providers_json
     FROM `%s` %s
     ORDER BY event_time_ts DESC
     LIMIT ? OFFSET ?',
    $table,
    $whereSql
);
$selectStmt = $db->prepare($selectSql);
if (!$selectStmt instanceof mysqli_stmt) {
    json_response(500, [
        'ok' => false,
        'error' => 'Unable to prepare archive query',
    ]);
}
$selectTypes = $types . 'ii';
$selectParams = array_merge($params, [$perPage, $offset]);
if (!mysqli_stmt_bind_dynamic($selectStmt, $selectTypes, $selectParams) || !$selectStmt->execute()) {
    json_response(500, [
        'ok' => false,
        'error' => 'Unable to execute archive query',
    ]);
}

$rows = [];
$providers = [];
$result = $selectStmt->get_result();
if ($result instanceof mysqli_result) {
    while ($row = $result->fetch_assoc()) {
        $providersJson = (string) ($row['source_providers_json'] ?? '[]');
        $providersList = json_decode($providersJson, true);
        if (!is_array($providersList) || count($providersList) === 0) {
            $fallbackProvider = (string) ($row['source_provider'] ?? '');
            $providersList = $fallbackProvider !== '' ? [$fallbackProvider] : [];
        }
        foreach ($providersList as $p) {
            if (is_string($p) && $p !== '') {
                $providers[$p] = true;
            }
        }

        $rows[] = [
            'id' => (string) ($row['event_id'] ?? ''),
            'place' => (string) ($row['place'] ?? ''),
            'magnitude' => is_numeric($row['magnitude'] ?? null) ? (float) $row['magnitude'] : null,
            'depth_km' => is_numeric($row['depth_km'] ?? null) ? (float) $row['depth_km'] : null,
            'latitude' => is_numeric($row['latitude'] ?? null) ? (float) $row['latitude'] : null,
            'longitude' => is_numeric($row['longitude'] ?? null) ? (float) $row['longitude'] : null,
            'event_time_utc' => (string) ($row['event_time_utc'] ?? ''),
            'source_url' => (string) ($row['source_url'] ?? ''),
            'source_provider' => (string) ($row['source_provider'] ?? ''),
            'source_providers' => array_values(array_filter($providersList, static fn (mixed $v): bool => is_string($v) && $v !== '')),
        ];
    }
    $result->free();
}
$selectStmt->close();
$db->close();

$payload = [
    'ok' => true,
    'provider' => 'Quakrs Earthquakes Archive',
    'generated_at' => gmdate('c'),
    'generated_at_ts' => time(),
    'page' => $page,
    'per_page' => $perPage,
    'total_count' => $totalCount,
    'total_pages' => $perPage > 0 ? (int) ceil($totalCount / $perPage) : 0,
    'events_count' => count($rows),
    'events' => $rows,
    'providers' => array_values(array_keys($providers)),
];

json_response(200, $payload);

