<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/earthquakes-archive-lib.php';

$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';
if (!$forceRefresh) {
    json_response(400, ['ok' => false, 'error' => 'Missing required query parameter: force_refresh=1']);
}

require_refresh_token($appConfig);

$cutoffTs = time() - (48 * 60 * 60);
$targets = [
    'live' => earthquake_mysql_role_config($appConfig, 'live'),
    'archive' => earthquake_mysql_role_config($appConfig, 'archive'),
];

$deletedByDb = [];
foreach ($targets as $role => $cfg) {
    $reason = null;
    $db = earthquake_mysql_open($appConfig, $role, $reason);
    if (!$db instanceof mysqli) {
        json_response(503, [
            'ok' => false,
            'error' => sprintf('%s database unavailable', ucfirst($role)),
            'reason' => $reason ?: 'not configured',
        ]);
    }

    $table = (string) ($cfg['table'] ?? 'earthquake_events');
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
        json_response(500, ['ok' => false, 'error' => sprintf('Unable to prepare cleanup query (%s)', $role)]);
    }

    if (!$stmt->bind_param('i', $cutoffTs) || !$stmt->execute()) {
        $stmt->close();
        $db->close();
        json_response(500, ['ok' => false, 'error' => sprintf('Unable to execute cleanup query (%s)', $role)]);
    }

    $deletedByDb[$role] = [
        'database' => (string) ($cfg['database'] ?? ''),
        'table' => $table,
        'deleted_rows' => max(0, (int) $stmt->affected_rows),
    ];

    $stmt->close();
    $db->close();
}

$deletedRows = 0;
foreach ($deletedByDb as $row) {
    $deletedRows += (int) ($row['deleted_rows'] ?? 0);
}

json_response(200, [
    'ok' => true,
    'deleted_rows' => $deletedRows,
    'deleted_by_db' => $deletedByDb,
    'cutoff_utc' => gmdate('c', $cutoffTs),
    'providers' => ['USGS', 'EMSC'],
    'rule' => 'Delete rows older than 48h where magnitude is NULL or < 2.5',
    'generated_at' => gmdate('c'),
]);
