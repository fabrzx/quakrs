<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/earthquakes-archive-lib.php';

function rollover_int_param(string $key, int $default, int $min, int $max): int
{
    $raw = $_GET[$key] ?? null;
    if (!is_numeric($raw)) {
        return $default;
    }
    $value = (int) $raw;
    return max($min, min($max, $value));
}

function rollover_bind_dynamic(mysqli_stmt $stmt, string $types, array $values): bool
{
    if ($types === '' || count($values) === 0) {
        return true;
    }
    $refs = [];
    foreach ($values as $i => $value) {
        $refs[$i] = $value;
    }
    $args = [$types];
    foreach ($refs as &$value) {
        $args[] = &$value;
    }
    return $stmt->bind_param(...$args);
}

function rollover_existing_keys(mysqli $db, string $table, array $eventKeys): array
{
    if (count($eventKeys) === 0) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($eventKeys), '?'));
    $sql = sprintf('SELECT event_key FROM `%s` WHERE event_key IN (%s)', $table, $placeholders);
    $stmt = $db->prepare($sql);
    if (!$stmt instanceof mysqli_stmt) {
        return [];
    }

    $types = str_repeat('s', count($eventKeys));
    if (!rollover_bind_dynamic($stmt, $types, $eventKeys) || !$stmt->execute()) {
        $stmt->close();
        return [];
    }

    $keys = [];
    $result = $stmt->get_result();
    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $key = is_array($row) ? (string) ($row['event_key'] ?? '') : '';
            if ($key !== '') {
                $keys[] = $key;
            }
        }
        $result->free();
    }
    $stmt->close();
    return array_values(array_unique($keys));
}

$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';
if (!$forceRefresh) {
    json_response(400, ['ok' => false, 'error' => 'Missing required query parameter: force_refresh=1']);
}
require_refresh_token($appConfig);

$retentionDays = rollover_int_param('retention_days', 90, 7, 3650);
$batchLimit = rollover_int_param('limit', 5000, 100, 20000);
$dryRun = isset($_GET['dry_run']) && (string) $_GET['dry_run'] === '1';
$nowTs = time();
$cutoffTs = $nowTs - ($retentionDays * 86400);

$liveReason = null;
$liveDb = earthquake_mysql_open($appConfig, 'live', $liveReason);
if (!$liveDb instanceof mysqli) {
    json_response(503, [
        'ok' => false,
        'error' => 'Live database unavailable',
        'reason' => $liveReason ?: 'not configured',
    ]);
}
$liveCfg = earthquake_mysql_role_config($appConfig, 'live');
$liveTable = (string) ($liveCfg['table'] ?? 'earthquake_events');

$archiveReason = null;
$archiveDb = earthquake_mysql_open($appConfig, 'archive', $archiveReason);
if (!$archiveDb instanceof mysqli) {
    $liveDb->close();
    json_response(503, [
        'ok' => false,
        'error' => 'Archive database unavailable',
        'reason' => $archiveReason ?: 'not configured',
    ]);
}
$archiveCfg = earthquake_mysql_role_config($appConfig, 'archive');
$archiveTable = (string) ($archiveCfg['table'] ?? 'earthquake_events');

$countSql = sprintf('SELECT COUNT(*) AS c FROM `%s` WHERE event_time_ts < ?', $liveTable);
$countStmt = $liveDb->prepare($countSql);
$eligibleCount = 0;
if ($countStmt instanceof mysqli_stmt) {
    if ($countStmt->bind_param('i', $cutoffTs) && $countStmt->execute()) {
        $res = $countStmt->get_result();
        $row = $res instanceof mysqli_result ? $res->fetch_assoc() : null;
        if ($res instanceof mysqli_result) {
            $res->free();
        }
        $eligibleCount = is_array($row) ? (int) ($row['c'] ?? 0) : 0;
    }
    $countStmt->close();
}

$selectSql = sprintf(
    'SELECT event_key, event_id, event_time_utc, event_time_ts, place, magnitude, depth_km, latitude, longitude, source_provider, source_providers_json, source_url
     FROM `%s`
     WHERE event_time_ts < ?
     ORDER BY event_time_ts ASC
     LIMIT ?',
    $liveTable
);
$selectStmt = $liveDb->prepare($selectSql);
if (!$selectStmt instanceof mysqli_stmt) {
    $archiveDb->close();
    $liveDb->close();
    json_response(500, ['ok' => false, 'error' => 'Unable to prepare live selection query']);
}
if (!$selectStmt->bind_param('ii', $cutoffTs, $batchLimit) || !$selectStmt->execute()) {
    $selectStmt->close();
    $archiveDb->close();
    $liveDb->close();
    json_response(500, ['ok' => false, 'error' => 'Unable to execute live selection query']);
}

$rows = [];
$result = $selectStmt->get_result();
if ($result instanceof mysqli_result) {
    while ($row = $result->fetch_assoc()) {
        if (!is_array($row)) {
            continue;
        }
        $providers = [];
        $providersRaw = $row['source_providers_json'] ?? null;
        if (is_string($providersRaw) && $providersRaw !== '') {
            $decoded = json_decode($providersRaw, true);
            if (is_array($decoded)) {
                $providers = array_values(array_filter($decoded, static fn (mixed $v): bool => is_string($v) && trim($v) !== ''));
            }
        }
        if (count($providers) === 0 && is_string($row['source_provider'] ?? null) && trim((string) $row['source_provider']) !== '') {
            $providers = [trim((string) $row['source_provider'])];
        }
        $rows[] = [
            'event_key' => (string) ($row['event_key'] ?? ''),
            'id' => (string) ($row['event_id'] ?? ''),
            'event_time_utc' => (string) ($row['event_time_utc'] ?? ''),
            'place' => (string) ($row['place'] ?? ''),
            'magnitude' => isset($row['magnitude']) && is_numeric($row['magnitude']) ? (float) $row['magnitude'] : null,
            'depth_km' => isset($row['depth_km']) && is_numeric($row['depth_km']) ? (float) $row['depth_km'] : null,
            'latitude' => isset($row['latitude']) && is_numeric($row['latitude']) ? (float) $row['latitude'] : null,
            'longitude' => isset($row['longitude']) && is_numeric($row['longitude']) ? (float) $row['longitude'] : null,
            'source_provider' => (string) ($row['source_provider'] ?? ''),
            'source_providers' => $providers,
            'source_url' => (string) ($row['source_url'] ?? ''),
        ];
    }
    $result->free();
}
$selectStmt->close();

$selected = count($rows);
$written = 0;
$deleted = 0;

if (!$dryRun && $selected > 0) {
    $written = earthquake_archive_ingest($archiveDb, $rows, $nowTs, $archiveTable);

    $eventKeys = array_values(array_filter(array_map(
        static fn (array $row): string => (string) ($row['event_key'] ?? ''),
        $rows
    ), static fn (string $key): bool => $key !== ''));

    $confirmedArchiveKeys = count($eventKeys) > 0
        ? rollover_existing_keys($archiveDb, $archiveTable, $eventKeys)
        : [];

    if (count($confirmedArchiveKeys) > 0) {
        $placeholders = implode(',', array_fill(0, count($confirmedArchiveKeys), '?'));
        $deleteSql = sprintf('DELETE FROM `%s` WHERE event_key IN (%s)', $liveTable, $placeholders);
        $deleteStmt = $liveDb->prepare($deleteSql);
        if ($deleteStmt instanceof mysqli_stmt) {
            $types = str_repeat('s', count($confirmedArchiveKeys));
            if (rollover_bind_dynamic($deleteStmt, $types, $confirmedArchiveKeys) && $deleteStmt->execute()) {
                $deleted = max(0, (int) $deleteStmt->affected_rows);
            }
            $deleteStmt->close();
        }
    }
}

$archiveDb->close();
$liveDb->close();

json_response(200, [
    'ok' => true,
    'action' => 'live_to_archive_rollover',
    'dry_run' => $dryRun,
    'retention_days' => $retentionDays,
    'cutoff_utc' => gmdate('c', $cutoffTs),
    'eligible_rows' => $eligibleCount,
    'selected_rows' => $selected,
    'archive_written' => $written,
    'live_deleted' => $deleted,
    'remaining_estimate' => max(0, $eligibleCount - ($dryRun ? 0 : $deleted)),
    'live_table' => $liveTable,
    'archive_table' => $archiveTable,
    'generated_at' => gmdate('c', $nowTs),
]);
