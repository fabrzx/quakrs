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

function query_year(string $key): ?int
{
    $raw = trim((string) ($_GET[$key] ?? ''));
    if ($raw === '' || !preg_match('/^\d{4}$/', $raw)) {
        return null;
    }
    $year = (int) $raw;
    if ($year < 1800 || $year > 2200) {
        return null;
    }
    return $year;
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

function country_sql_expr(string $column): string
{
    return sprintf('LOWER(TRIM(SUBSTRING_INDEX(%s, \',\', -1)))', $column);
}

function country_from_place(string $place): string
{
    $parts = array_values(array_filter(array_map('trim', explode(',', $place)), static fn (string $v): bool => $v !== ''));
    if (count($parts) === 0) {
        return '';
    }
    return (string) $parts[count($parts) - 1];
}

function haversine_km(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $r = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = (sin($dLat / 2) ** 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * (sin($dLon / 2) ** 2);
    return $r * (2 * atan2(sqrt($a), sqrt(max(0.0, 1.0 - $a))));
}

function location_index_add(array &$index, string $place): void
{
    $place = trim($place);
    if ($place === '') {
        return;
    }
    $index[$place] = true;
    foreach (explode(',', $place) as $part) {
        $token = trim($part);
        if ($token !== '' && strlen($token) >= 3) {
            $index[$token] = true;
        }
    }
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

$page = query_int('page', 1, 1, 1000000);
$perPage = query_int('per_page', 120, 1, 500);
$offset = ($page - 1) * $perPage;

$fromTs = query_ts('from');
$toTs = query_ts('to');
$minMag = query_float('min_magnitude');
$maxMag = query_float('max_magnitude');
$minDepth = query_float('min_depth_km');
$maxDepth = query_float('max_depth_km');
$year = query_year('year');
$query = trim((string) ($_GET['q'] ?? ''));
$provider = trim((string) ($_GET['provider'] ?? ''));
$country = trim((string) ($_GET['country'] ?? ''));
$locality = trim((string) ($_GET['locality'] ?? ''));
$centerPlace = trim((string) ($_GET['center_place'] ?? ''));
$centerLat = query_float('center_lat');
$centerLon = query_float('center_lon');
$radiusKm = query_float('radius_km');
if ($radiusKm !== null) {
    $radiusKm = max(0.0, min(20000.0, $radiusKm));
}
$sortByRaw = strtolower(trim((string) ($_GET['sort_by'] ?? 'date')));
$sortDirRaw = strtolower(trim((string) ($_GET['sort_dir'] ?? 'desc')));
$sortBy = in_array($sortByRaw, ['date', 'magnitude'], true) ? $sortByRaw : 'date';
$sortDir = in_array($sortDirRaw, ['asc', 'desc'], true) ? $sortDirRaw : 'desc';

$archiveReason = null;
$db = earthquake_archive_open($appConfig, $archiveReason);

if (!$db instanceof mysqli) {
    json_response(503, [
        'ok' => false,
        'error' => 'Archive MySQL unavailable',
        'reason' => $archiveReason ?: 'connect failed',
        'provider' => 'Quakrs Earthquakes Archive',
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
if ($year !== null) {
    $fromYearTs = strtotime(sprintf('%04d-01-01 00:00:00 UTC', $year));
    $toYearTs = strtotime(sprintf('%04d-01-01 00:00:00 UTC', $year + 1));
    if (is_int($fromYearTs) && is_int($toYearTs)) {
        $where[] = 'event_time_ts >= ? AND event_time_ts < ?';
        $types .= 'ii';
        $params[] = $fromYearTs;
        $params[] = $toYearTs;
    }
}
if ($query !== '') {
    $where[] = 'LOWER(place) LIKE LOWER(?)';
    $types .= 's';
    $params[] = '%' . $query . '%';
}
if ($country !== '') {
    $countryExpr = country_sql_expr('place');
    $where[] = sprintf('(%s = LOWER(?) OR LOWER(TRIM(place)) = LOWER(?))', $countryExpr);
    $types .= 'ss';
    $params[] = $country;
    $params[] = $country;
}
if ($locality !== '') {
    $where[] = 'LOWER(place) LIKE LOWER(?)';
    $types .= 's';
    $params[] = '%' . $locality . '%';
}
if ($provider !== '') {
    $where[] = '(LOWER(source_provider) = LOWER(?) OR LOWER(source_providers_json) LIKE LOWER(?))';
    $types .= 'ss';
    $params[] = $provider;
    $params[] = '%"' . $provider . '"%';
}

$resolvedCenter = null;
if ($centerLat !== null && $centerLon !== null) {
    $resolvedCenter = [
        'name' => $centerPlace !== '' ? $centerPlace : 'Manual center',
        'latitude' => $centerLat,
        'longitude' => $centerLon,
        'source' => 'manual',
    ];
} elseif ($centerPlace !== '') {
    $centerSql = sprintf(
        'SELECT place, latitude, longitude
         FROM `%s`
         WHERE latitude IS NOT NULL
           AND longitude IS NOT NULL
           AND LOWER(place) LIKE LOWER(?)
         ORDER BY event_time_ts DESC
         LIMIT 1',
        $table
    );
    $centerStmt = $db->prepare($centerSql);
    if ($centerStmt instanceof mysqli_stmt) {
        $centerNeedle = '%' . $centerPlace . '%';
        if ($centerStmt->bind_param('s', $centerNeedle) && $centerStmt->execute()) {
            $centerResult = $centerStmt->get_result();
            $centerRow = $centerResult instanceof mysqli_result ? $centerResult->fetch_assoc() : null;
            if ($centerResult instanceof mysqli_result) {
                $centerResult->free();
            }
            if (is_array($centerRow)) {
                $lat = is_numeric($centerRow['latitude'] ?? null) ? (float) $centerRow['latitude'] : null;
                $lon = is_numeric($centerRow['longitude'] ?? null) ? (float) $centerRow['longitude'] : null;
                if ($lat !== null && $lon !== null) {
                    $resolvedCenter = [
                        'name' => (string) ($centerRow['place'] ?? $centerPlace),
                        'latitude' => $lat,
                        'longitude' => $lon,
                        'source' => 'archive-match',
                    ];
                }
            }
        }
        $centerStmt->close();
    }
}

$radiusApplied = false;
if ($radiusKm !== null && $radiusKm > 0 && is_array($resolvedCenter)) {
    $where[] = 'latitude IS NOT NULL
        AND longitude IS NOT NULL
        AND (
            6371.0 * 2.0 * ASIN(SQRT(
                POWER(SIN(RADIANS(latitude - ?) / 2.0), 2.0) +
                COS(RADIANS(?)) * COS(RADIANS(latitude)) *
                POWER(SIN(RADIANS(longitude - ?) / 2.0), 2.0)
            ))
        ) <= ?';
    $types .= 'dddd';
    $params[] = (float) $resolvedCenter['latitude'];
    $params[] = (float) $resolvedCenter['latitude'];
    $params[] = (float) $resolvedCenter['longitude'];
    $params[] = $radiusKm;
    $radiusApplied = true;
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

$maxMagnitude = null;
$maxSql = sprintf('SELECT MAX(magnitude) AS m FROM `%s` %s', $table, $whereSql);
$maxStmt = $db->prepare($maxSql);
if (!$maxStmt instanceof mysqli_stmt) {
    json_response(500, [
        'ok' => false,
        'error' => 'Unable to prepare max magnitude query',
    ]);
}
if (!mysqli_stmt_bind_dynamic($maxStmt, $types, $params) || !$maxStmt->execute()) {
    json_response(500, [
        'ok' => false,
        'error' => 'Unable to execute max magnitude query',
    ]);
}
$maxResult = $maxStmt->get_result();
$maxRow = $maxResult instanceof mysqli_result ? $maxResult->fetch_assoc() : null;
if (is_array($maxRow) && isset($maxRow['m']) && is_numeric($maxRow['m'])) {
    $maxMagnitude = (float) $maxRow['m'];
}
if ($maxResult instanceof mysqli_result) {
    $maxResult->free();
}
$maxStmt->close();

$selectSql = sprintf(
    'SELECT event_id, place, magnitude, depth_km, latitude, longitude, event_time_utc, source_url, source_provider, source_providers_json
     FROM `%s` %s %s
     LIMIT ? OFFSET ?',
    $table,
    $whereSql,
    $sortBy === 'magnitude'
        ? sprintf('ORDER BY (magnitude IS NULL) ASC, magnitude %s, event_time_ts DESC', strtoupper($sortDir))
        : sprintf('ORDER BY event_time_ts %s', strtoupper($sortDir))
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

$countryValues = [];
$countrySql = sprintf(
    'SELECT TRIM(SUBSTRING_INDEX(place, \',\', -1)) AS country, COUNT(*) AS c
     FROM `%s`
     WHERE place IS NOT NULL AND TRIM(place) <> \'\'
     GROUP BY country
     HAVING country IS NOT NULL AND TRIM(country) <> \'\'
     ORDER BY c DESC, country ASC
     LIMIT 250',
    $table
);
$countryResult = $db->query($countrySql);
if ($countryResult instanceof mysqli_result) {
    while ($countryRow = $countryResult->fetch_assoc()) {
        $label = trim((string) ($countryRow['country'] ?? ''));
        if ($label === '') {
            continue;
        }
        $countryValues[] = $label;
    }
    $countryResult->free();
}

$locationMap = [];
$locationSql = sprintf(
    'SELECT place, COUNT(*) AS c
     FROM `%s`
     WHERE place IS NOT NULL AND TRIM(place) <> \'\'
     GROUP BY place
     ORDER BY c DESC
     LIMIT 2000',
    $table
);
$locationResult = $db->query($locationSql);
if ($locationResult instanceof mysqli_result) {
    while ($locationRow = $locationResult->fetch_assoc()) {
        $place = trim((string) ($locationRow['place'] ?? ''));
        if ($place === '') {
            continue;
        }
        location_index_add($locationMap, $place);
    }
    $locationResult->free();
}
$db->close();

$payload = [
    'ok' => true,
    'provider' => 'Quakrs Earthquakes Archive',
    'generated_at' => gmdate('c'),
    'generated_at_ts' => time(),
    'page' => $page,
    'per_page' => $perPage,
    'total_count' => $totalCount,
    'filtered_max_magnitude' => $maxMagnitude,
    'total_pages' => $perPage > 0 ? (int) ceil($totalCount / $perPage) : 0,
    'events_count' => count($rows),
    'events' => $rows,
    'providers' => array_values(array_keys($providers)),
    'countries' => array_values(array_unique($countryValues)),
    'locations' => array_slice(array_values(array_keys($locationMap)), 0, 1200),
    'center' => $resolvedCenter,
    'filters_applied' => [
        'year' => $year,
        'country' => $country !== '' ? $country : null,
        'locality' => $locality !== '' ? $locality : null,
        'center_place' => $centerPlace !== '' ? $centerPlace : null,
        'radius_km' => $radiusApplied ? $radiusKm : null,
        'sort_by' => $sortBy,
        'sort_dir' => $sortDir,
    ],
];

json_response(200, $payload);
