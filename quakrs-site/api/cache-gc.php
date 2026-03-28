<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';
if (!$forceRefresh) {
    json_response(400, [
        'ok' => false,
        'error' => 'Missing required query parameter: force_refresh=1',
    ]);
}

require_refresh_token($appConfig);

$dataDir = rtrim((string) ($appConfig['data_dir'] ?? ''), '/');
if ($dataDir === '' || !is_dir($dataDir)) {
    json_response(503, [
        'ok' => false,
        'error' => 'Data directory unavailable',
    ]);
}

/**
 * Delete old cache files for a strict filename prefix in a single directory.
 * No external input is accepted for prefix/ttl, keeping behavior predictable.
 */
function cache_gc_delete_prefix(string $dir, string $prefix, int $olderThanTs): int
{
    if (!is_dir($dir)) {
        return 0;
    }

    $realDir = realpath($dir);
    if (!is_string($realDir) || $realDir === '') {
        return 0;
    }

    $deleted = 0;
    $entries = @scandir($realDir);
    if (!is_array($entries)) {
        return 0;
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        if (!str_starts_with($entry, $prefix) || !str_ends_with($entry, '.json')) {
            continue;
        }

        $path = $realDir . '/' . $entry;
        if (!is_file($path)) {
            continue;
        }

        $mtime = @filemtime($path);
        if (!is_int($mtime) || $mtime > $olderThanTs) {
            continue;
        }

        if (@unlink($path)) {
            $deleted += 1;
        }
    }

    return $deleted;
}

$lockPath = $dataDir . '/cache_gc.lock';
$lockHandle = @fopen($lockPath, 'c');
if (!is_resource($lockHandle) || !@flock($lockHandle, LOCK_EX | LOCK_NB)) {
    json_response(409, [
        'ok' => false,
        'error' => 'Cache GC already running',
    ]);
}

$nowTs = time();
$eventHistoryMaxAgeSeconds = 24 * 60 * 60; // 24h
$archiveMetaMaxAgeSeconds = 3 * 60 * 60;   // 3h

$eventOlderThanTs = $nowTs - $eventHistoryMaxAgeSeconds;
$archiveOlderThanTs = $nowTs - $archiveMetaMaxAgeSeconds;

$eventDeleted = cache_gc_delete_prefix($dataDir, 'event_history_', $eventOlderThanTs);
$metaDir = $dataDir . '/archive_meta_cache';
$aggDeleted = cache_gc_delete_prefix($metaDir, 'agg_', $archiveOlderThanTs);
$facetsDeleted = cache_gc_delete_prefix($metaDir, 'facets_', $archiveOlderThanTs);

@flock($lockHandle, LOCK_UN);
@fclose($lockHandle);

json_response(200, [
    'ok' => true,
    'job' => 'cache_gc',
    'deleted' => [
        'event_history' => $eventDeleted,
        'archive_meta_agg' => $aggDeleted,
        'archive_meta_facets' => $facetsDeleted,
        'total' => $eventDeleted + $aggDeleted + $facetsDeleted,
    ],
    'retention' => [
        'event_history_seconds' => $eventHistoryMaxAgeSeconds,
        'archive_meta_seconds' => $archiveMetaMaxAgeSeconds,
    ],
    'generated_at' => gmdate('c', $nowTs),
]);
