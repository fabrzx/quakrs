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
        'age_source' => is_int($generatedTs) ? 'payload' : 'file',
        'size_kb' => $sizeKb,
    ];
    if (isset($counters[$status])) {
        $counters[$status]++;
    }
}

$archiveReason = null;
$archiveDb = earthquake_archive_open($appConfig, $archiveReason);
$archiveStatus = $archiveDb instanceof mysqli ? 'ok' : 'unavailable';
if ($archiveDb instanceof mysqli) {
    $archiveDb->close();
}

$overall = 'healthy';
if ($counters['missing'] > 0 || $counters['outdated'] > 0) {
    $overall = 'degraded';
} elseif ($counters['lagging'] > 0 || $counters['unknown'] > 0) {
    $overall = 'warning';
}

json_response(200, [
    'ok' => true,
    'overall_status' => $overall,
    'generated_at_ts' => $now,
    'generated_at' => gmdate('c', $now),
    'feeds' => $items,
    'counts' => $counters,
    'archive_mysql' => [
        'status' => $archiveStatus,
        'reason' => $archiveReason,
    ],
]);
