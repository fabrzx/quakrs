<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/earthquakes-archive-lib.php';

$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';
if (!$forceRefresh) {
    json_response(400, ['ok' => false, 'error' => 'Missing required query parameter: force_refresh=1']);
}

require_refresh_token($appConfig);

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

$cutoffTs = time() - (48 * 60 * 60);
$sql = sprintf(
    "DELETE FROM `%s`
     WHERE event_time_ts < ?
       AND source_provider IN ('USGS', 'EMSC')
       AND (magnitude IS NULL OR magnitude < 2.5)",
    $table
);

$stmt = $db->prepare($sql);
if (!$stmt instanceof mysqli_stmt) {
    $db->close();
    json_response(500, ['ok' => false, 'error' => 'Unable to prepare cleanup query']);
}

$cutoffText = (string) $cutoffTs;
if (!$stmt->bind_param('s', $cutoffText) || !$stmt->execute()) {
    $stmt->close();
    $db->close();
    json_response(500, ['ok' => false, 'error' => 'Unable to execute cleanup query']);
}

$deletedRows = $stmt->affected_rows;
$stmt->close();
$db->close();

json_response(200, [
    'ok' => true,
    'deleted_rows' => max(0, (int) $deletedRows),
    'cutoff_utc' => gmdate('c', $cutoffTs),
    'providers' => ['USGS', 'EMSC'],
    'rule' => 'Delete rows older than 48h where magnitude is NULL or < 2.5',
    'generated_at' => gmdate('c'),
]);
