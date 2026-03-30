<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/earthquakes-archive-lib.php';

function health_feed_generated_timestamp(string $path): ?int
{
    $payload = read_json_file($path);
    if (!is_array($payload)) {
        return null;
    }

    if (isset($payload['generated_at_ts']) && is_numeric($payload['generated_at_ts'])) {
        return (int) $payload['generated_at_ts'];
    }

    if (isset($payload['generated_at']) && is_string($payload['generated_at'])) {
        $ts = strtotime($payload['generated_at']);
        if (is_int($ts)) {
            return $ts;
        }
    }

    return null;
}

function health_feed_status(?int $ageMinutes, int $maxAgeMinutes): string
{
    if (!is_int($ageMinutes)) {
        return 'unknown';
    }
    if ($ageMinutes <= $maxAgeMinutes) {
        return 'healthy';
    }
    if ($ageMinutes <= ($maxAgeMinutes * 3)) {
        return 'lagging';
    }
    return 'outdated';
}

function health_incident_severity_weight(string $severity): int
{
    return match ($severity) {
        'critical' => 4,
        'major' => 3,
        'minor' => 2,
        default => 1,
    };
}

function health_incident_from_feed(array $feed): ?array
{
    $status = (string) ($feed['status'] ?? 'unknown');
    if (!in_array($status, ['missing', 'outdated', 'lagging'], true)) {
        return null;
    }

    $key = (string) ($feed['key'] ?? 'feed');
    $severity = match ($status) {
        'missing' => 'critical',
        'outdated' => 'major',
        default => 'minor',
    };
    $age = isset($feed['age_minutes']) && is_numeric($feed['age_minutes']) ? (int) $feed['age_minutes'] : null;
    $maxAge = isset($feed['max_age_minutes']) && is_numeric($feed['max_age_minutes']) ? (int) $feed['max_age_minutes'] : null;
    $detail = 'delay unknown';
    if (is_int($age) && is_int($maxAge)) {
        $detail = 'delay +' . max(0, $age - $maxAge) . ' min';
    }

    return [
        'key' => 'feed:' . $key,
        'scope' => 'feed',
        'target' => $key,
        'severity' => $severity,
        'title' => 'Feed ' . $key . ' is ' . $status,
        'detail' => $detail,
        'status' => $status,
        'source_time' => $feed['last_success_at'] ?? null,
    ];
}

function health_incident_from_component(array $component): ?array
{
    $status = (string) ($component['status'] ?? 'unknown');
    if ($status !== 'degraded') {
        return null;
    }

    $impact = (string) ($component['impact'] ?? 'none');
    $severity = $impact === 'visible' ? 'major' : 'minor';
    $key = (string) ($component['key'] ?? 'component');

    return [
        'key' => 'component:' . $key,
        'scope' => 'component',
        'target' => $key,
        'severity' => $severity,
        'title' => 'Component ' . $key . ' degraded',
        'detail' => 'impact ' . $impact,
        'status' => 'degraded',
        'source_time' => $component['since'] ?? null,
    ];
}

$feeds = [
    ['key' => 'earthquakes', 'file' => $appConfig['data_dir'] . '/earthquakes_latest.json', 'max_age_minutes' => 10],
    ['key' => 'aftershocks', 'file' => $appConfig['data_dir'] . '/aftershocks_index_latest.json', 'max_age_minutes' => 10],
    ['key' => 'volcanoes', 'file' => $appConfig['data_dir'] . '/volcanoes_latest.json', 'max_age_minutes' => 20],
    ['key' => 'tremors', 'file' => $appConfig['data_dir'] . '/tremors_latest.json', 'max_age_minutes' => 20],
    ['key' => 'tsunami', 'file' => $appConfig['data_dir'] . '/tsunami_latest.json', 'max_age_minutes' => 30],
    ['key' => 'space_weather', 'file' => $appConfig['data_dir'] . '/space_weather_latest.json', 'max_age_minutes' => 20],
    ['key' => 'bulletins', 'file' => $appConfig['data_dir'] . '/bulletins_latest.json', 'max_age_minutes' => 30],
    ['key' => 'hotspots', 'file' => $appConfig['data_dir'] . '/hotspots_latest.json', 'max_age_minutes' => 30],
    ['key' => 'volcano_cams', 'file' => $appConfig['data_dir'] . '/volcano_cams_latest.json', 'max_age_minutes' => 45],
];

$now = time();
$items = [];
$counters = [
    'healthy' => 0,
    'lagging' => 0,
    'outdated' => 0,
    'missing' => 0,
    'unknown' => 0,
];

foreach ($feeds as $feed) {
    $path = (string) $feed['file'];
    $exists = is_file($path);

    if (!$exists) {
        $items[] = [
            'key' => $feed['key'],
            'status' => 'missing',
            'age_minutes' => null,
            'age_source' => null,
            'size_kb' => null,
        ];
        $counters['missing']++;
        continue;
    }

    $generatedTs = health_feed_generated_timestamp($path);
    $mtime = filemtime($path);
    $referenceTs = is_int($generatedTs) && $generatedTs > 0
        ? $generatedTs
        : (is_int($mtime) ? $mtime : null);
    $ageMinutes = is_int($referenceTs)
        ? (int) floor(max(0, $now - $referenceTs) / 60)
        : null;
    $sizeBytes = filesize($path);
    $sizeKb = is_int($sizeBytes) ? round($sizeBytes / 1024, 1) : null;
    $status = health_feed_status($ageMinutes, (int) $feed['max_age_minutes']);

    $items[] = [
        'key' => $feed['key'],
        'status' => $status,
        'age_minutes' => $ageMinutes,
        'max_age_minutes' => (int) $feed['max_age_minutes'],
        'age_source' => is_int($generatedTs) ? 'payload' : 'file',
        'size_kb' => $sizeKb,
        'last_success_at' => is_int($referenceTs) ? gmdate('c', $referenceTs) : null,
        'error_streak' => in_array($status, ['outdated', 'missing'], true) ? 1 : 0,
        'reliability_class' => in_array($status, ['healthy', 'lagging'], true) ? 'A' : 'C',
    ];
    if (isset($counters[$status])) {
        $counters[$status]++;
    }
}

$mysqlRoles = ['live', 'archive', 'ingest', 'stats'];
$mysqlDatabases = [];
$archiveStatus = 'unavailable';
$archiveReason = null;
foreach ($mysqlRoles as $role) {
    $cfg = earthquake_mysql_role_config($appConfig, $role);
    $isConfigured = $cfg['host'] !== '' && $cfg['database'] !== '' && $cfg['user'] !== '';
    if (!$isConfigured) {
        $mysqlDatabases[$role] = [
            'status' => 'not_configured',
            'database' => $cfg['database'],
            'table' => $cfg['table'],
        ];
        continue;
    }

    $reason = null;
    $db = earthquake_mysql_open($appConfig, $role, $reason);
    $roleStatus = $db instanceof mysqli ? 'ok' : 'unavailable';
    if ($db instanceof mysqli) {
        $db->close();
    }
    if ($role === 'archive') {
        $archiveStatus = $roleStatus;
        $archiveReason = $reason;
    }
    $mysqlDatabases[$role] = [
        'status' => $roleStatus,
        'database' => $cfg['database'],
        'table' => $cfg['table'],
        'reason' => $reason,
    ];
}

$overall = 'healthy';
if ($counters['missing'] > 0 || $counters['outdated'] > 0) {
    $overall = 'degraded';
} elseif ($counters['lagging'] > 0 || $counters['unknown'] > 0) {
    $overall = 'warning';
}

$components = [
    [
        'key' => 'feed_ingestion',
        'status' => ($counters['missing'] > 0 || $counters['outdated'] > 0) ? 'degraded' : 'up',
        'since' => gmdate('c', $now),
        'impact' => ($counters['missing'] > 0 || $counters['outdated'] > 0) ? 'visible' : 'none',
        'note' => 'Source fetch and local ingest freshness.',
    ],
    [
        'key' => 'normalization_pipeline',
        'status' => ($counters['unknown'] > 0) ? 'degraded' : 'up',
        'since' => gmdate('c', $now),
        'impact' => ($counters['unknown'] > 0) ? 'limited' : 'none',
        'note' => 'Payload parsing and normalization checks.',
    ],
    [
        'key' => 'cache_write_layer',
        'status' => ($counters['missing'] > 0) ? 'degraded' : 'up',
        'since' => gmdate('c', $now),
        'impact' => ($counters['missing'] > 0) ? 'visible' : 'none',
        'note' => 'Cache persistence for local feed files.',
    ],
    [
        'key' => 'public_api_read',
        'status' => 'up',
        'since' => gmdate('c', $now),
        'impact' => 'none',
        'note' => 'Public API response layer.',
    ],
    [
        'key' => 'archive_db',
        'status' => $archiveStatus === 'ok' ? 'up' : 'degraded',
        'since' => gmdate('c', $now),
        'impact' => $archiveStatus === 'ok' ? 'none' : 'limited',
        'note' => $archiveStatus === 'ok' ? 'Archive connection available.' : 'Archive DB unavailable.',
    ],
];

$degradedComponents = 0;
foreach ($components as $component) {
    if (($component['status'] ?? 'up') !== 'up') {
        $degradedComponents++;
    }
}

$userImpact = 'Nominal monitoring. No major user-visible degradation.';
if ($overall === 'warning') {
    $userImpact = 'Some feeds are lagging. Recent updates may appear with delay.';
}
if ($overall === 'degraded') {
    $userImpact = 'Visible degradation: one or more feeds are outdated or missing.';
}

$incidentStatePath = $appConfig['data_dir'] . '/health_incidents_active.json';
$incidentHistoryPath = $appConfig['data_dir'] . '/health_incidents_history.json';
$incidentLockPath = $appConfig['data_dir'] . '/health_incidents.lock';
$nowIso = gmdate('c', $now);

$incidentLockHandle = @fopen($incidentLockPath, 'c');
if (is_resource($incidentLockHandle)) {
    @flock($incidentLockHandle, LOCK_EX);
}

$previousState = read_json_file($incidentStatePath);
$previousActive = is_array($previousState['active'] ?? null) ? $previousState['active'] : [];

$currentIncidents = [];
foreach ($items as $feedItem) {
    $incident = health_incident_from_feed($feedItem);
    if (is_array($incident)) {
        $currentIncidents[$incident['key']] = $incident;
    }
}
foreach ($components as $componentItem) {
    $incident = health_incident_from_component($componentItem);
    if (is_array($incident)) {
        $currentIncidents[$incident['key']] = $incident;
    }
}

$historyPayload = read_json_file($incidentHistoryPath);
$historyEvents = is_array($historyPayload['events'] ?? null) ? $historyPayload['events'] : [];
$newEvents = [];
$nextActive = [];

foreach ($currentIncidents as $incidentKey => $incident) {
    $previous = is_array($previousActive[$incidentKey] ?? null) ? $previousActive[$incidentKey] : null;
    if ($previous === null) {
        $incident['started_at'] = $nowIso;
        $incident['last_seen_at'] = $nowIso;
        $incident['last_change_at'] = $nowIso;
        $nextActive[$incidentKey] = $incident;
        $newEvents[] = [
            'event' => 'opened',
            'at' => $nowIso,
            'incident' => $incident,
        ];
        continue;
    }

    $incident['started_at'] = is_string($previous['started_at'] ?? null) ? $previous['started_at'] : $nowIso;
    $incident['last_seen_at'] = $nowIso;
    $changed = ($incident['severity'] ?? '') !== ($previous['severity'] ?? '')
        || ($incident['title'] ?? '') !== ($previous['title'] ?? '')
        || ($incident['detail'] ?? '') !== ($previous['detail'] ?? '')
        || ($incident['status'] ?? '') !== ($previous['status'] ?? '');
    $incident['last_change_at'] = $changed
        ? $nowIso
        : (is_string($previous['last_change_at'] ?? null) ? $previous['last_change_at'] : $nowIso);
    $nextActive[$incidentKey] = $incident;
    if ($changed) {
        $newEvents[] = [
            'event' => 'updated',
            'at' => $nowIso,
            'incident' => $incident,
        ];
    }
}

foreach ($previousActive as $incidentKey => $previousIncident) {
    if (isset($currentIncidents[$incidentKey])) {
        continue;
    }
    $startedAt = is_string($previousIncident['started_at'] ?? null) ? $previousIncident['started_at'] : $nowIso;
    $durationMinutes = null;
    $startedTs = strtotime($startedAt);
    if (is_int($startedTs)) {
        $durationMinutes = max(0, (int) floor(($now - $startedTs) / 60));
    }
    $resolvedIncident = $previousIncident;
    $resolvedIncident['ended_at'] = $nowIso;
    $resolvedIncident['duration_minutes'] = $durationMinutes;
    $newEvents[] = [
        'event' => 'resolved',
        'at' => $nowIso,
        'incident' => $resolvedIncident,
    ];
}

if (!empty($newEvents)) {
    $historyEvents = array_merge($newEvents, $historyEvents);
}
$historyEvents = array_slice($historyEvents, 0, 600);

write_json_file($incidentStatePath, [
    'updated_at' => $nowIso,
    'updated_at_ts' => $now,
    'active' => $nextActive,
]);
write_json_file($incidentHistoryPath, [
    'updated_at' => $nowIso,
    'updated_at_ts' => $now,
    'events' => $historyEvents,
]);
if (is_resource($incidentLockHandle)) {
    @flock($incidentLockHandle, LOCK_UN);
    @fclose($incidentLockHandle);
}

$activeIncidents = array_values($nextActive);
usort($activeIncidents, static function (array $a, array $b): int {
    $severityDelta = health_incident_severity_weight((string) ($b['severity'] ?? 'notice'))
        - health_incident_severity_weight((string) ($a['severity'] ?? 'notice'));
    if ($severityDelta !== 0) {
        return $severityDelta;
    }
    $aStarted = strtotime((string) ($a['started_at'] ?? '')) ?: 0;
    $bStarted = strtotime((string) ($b['started_at'] ?? '')) ?: 0;
    return $bStarted <=> $aStarted;
});

$highestSeverity = 'none';
if (count($activeIncidents) > 0) {
    $highestSeverity = (string) ($activeIncidents[0]['severity'] ?? 'notice');
}

json_response(200, [
    'ok' => true,
    'overall_status' => $overall,
    'generated_at_ts' => $now,
    'generated_at' => gmdate('c', $now),
    'feeds' => $items,
    'counts' => $counters,
    'components' => $components,
    'degraded_components' => $degradedComponents,
    'user_impact' => $userImpact,
    'incidents' => [],
    'incidents_active' => $activeIncidents,
    'incidents_history' => array_slice($historyEvents, 0, 80),
    'incidents_summary' => [
        'active_count' => count($activeIncidents),
        'highest_severity' => $highestSeverity,
    ],
    'archive_mysql' => [
        'status' => $archiveStatus,
        'reason' => $archiveReason,
    ],
    'mysql_databases' => $mysqlDatabases,
]);
