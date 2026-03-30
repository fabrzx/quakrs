<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/earthquakes-archive-lib.php';

function normalize_event_time(mixed $rawTime): ?string
{
    $maxAcceptedTs = time() + 86400; // tolerate small upstream clock drift, reject far-future timestamps

    if (is_numeric($rawTime)) {
        $time = (int) $rawTime;
        while ($time > 9999999999) {
            $time = (int) floor($time / 1000);
        }
        if ($time <= 0 || $time > $maxAcceptedTs) {
            return null;
        }
        return gmdate('c', $time);
    }

    if (is_string($rawTime) && $rawTime !== '') {
        $raw = trim($rawTime);
        $hasTimezone = (bool) preg_match('/(?:Z|[+\-]\d{2}:?\d{2})$/i', $raw);
        $input = $hasTimezone ? $raw : ($raw . ' UTC');
        $parsed = strtotime($input);
        if (!is_int($parsed) || $parsed <= 0 || $parsed > $maxAcceptedTs) {
            return null;
        }
        return gmdate('c', $parsed);
    }

    return null;
}

function normalize_earthquake_feature(array $feature, string $provider): ?array
{
    $properties = $feature['properties'] ?? [];
    $geometry = $feature['geometry'] ?? [];
    $coords = $geometry['coordinates'] ?? [];

    if (!is_array($properties) || !is_array($coords) || count($coords) < 3) {
        return null;
    }

    $eventTime = normalize_event_time($properties['time'] ?? null);
    $place = (string) ($properties['place'] ?? $properties['flynn_region'] ?? 'Unknown location');

    $depth = is_numeric($coords[2]) ? (float) $coords[2] : null;

    return [
        'id' => (string) ($feature['id'] ?? $properties['eventId'] ?? $properties['source_id'] ?? ''),
        'place' => $place,
        'magnitude' => is_numeric($properties['mag'] ?? null) ? (float) $properties['mag'] : null,
        'depth_km' => $depth !== null ? abs($depth) : null,
        'latitude' => is_numeric($coords[1]) ? (float) $coords[1] : null,
        'longitude' => is_numeric($coords[0]) ? (float) $coords[0] : null,
        'event_time_utc' => $eventTime,
        'source_url' => (string) ($properties['url'] ?? $properties['evt_url'] ?? ''),
        'source_provider' => $provider,
    ];
}

function event_fingerprint(array $event): string
{
    $lat = isset($event['latitude']) && is_numeric($event['latitude']) ? number_format((float) $event['latitude'], 2, '.', '') : 'na';
    $lon = isset($event['longitude']) && is_numeric($event['longitude']) ? number_format((float) $event['longitude'], 2, '.', '') : 'na';
    $mag = isset($event['magnitude']) && is_numeric($event['magnitude']) ? number_format((float) $event['magnitude'], 1, '.', '') : 'na';
    $time = isset($event['event_time_utc']) ? (string) $event['event_time_utc'] : 'na';
    $timeMinute = $time !== 'na' ? substr($time, 0, 16) : 'na';
    return implode('|', [$lat, $lon, $mag, $timeMinute]);
}

function provider_priority(string $provider): int
{
    $key = strtolower(trim($provider));
    return match ($key) {
        'usgs' => 3,
        'emsc' => 2,
        'ingv' => 1,
        default => 0,
    };
}

function event_quality_score(array $event): int
{
    $score = provider_priority((string) ($event['source_provider'] ?? '')) * 100;

    if (isset($event['depth_km']) && is_numeric($event['depth_km']) && (float) $event['depth_km'] >= 0.0) {
        $score += 10;
    }
    if (isset($event['latitude'], $event['longitude']) && is_numeric($event['latitude']) && is_numeric($event['longitude'])) {
        $score += 5;
    }
    if (isset($event['magnitude']) && is_numeric($event['magnitude'])) {
        $score += 5;
    }

    return $score;
}

function haversine_distance_km(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $earthRadiusKm = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadiusKm * $c;
}

function event_is_italy_region(array $event): bool
{
    $lat = isset($event['latitude']) && is_numeric($event['latitude']) ? (float) $event['latitude'] : null;
    $lon = isset($event['longitude']) && is_numeric($event['longitude']) ? (float) $event['longitude'] : null;
    if ($lat === null || $lon === null) {
        return false;
    }
    // Align with the Italy viewport used by the dedicated Italy feed.
    return $lat >= 35.0 && $lat <= 48.8 && $lon >= 6.0 && $lon <= 19.6;
}

function event_provider_is_ingv(array $event): bool
{
    return strtolower((string) ($event['source_provider'] ?? '')) === 'ingv';
}

function events_are_probable_duplicate(array $a, array $b): bool
{
    $aTs = isset($a['event_time_utc']) ? strtotime((string) $a['event_time_utc']) : false;
    $bTs = isset($b['event_time_utc']) ? strtotime((string) $b['event_time_utc']) : false;
    if (!is_int($aTs) || !is_int($bTs)) {
        return false;
    }

    $aMag = isset($a['magnitude']) && is_numeric($a['magnitude']) ? (float) $a['magnitude'] : null;
    $bMag = isset($b['magnitude']) && is_numeric($b['magnitude']) ? (float) $b['magnitude'] : null;
    if ($aMag !== null && $bMag !== null && abs($aMag - $bMag) > 0.2) {
        return false;
    }

    $referenceMag = max($aMag ?? 0.0, $bMag ?? 0.0);
    $maxTimeDiffSeconds = $referenceMag >= 4.8 ? 12 : 1;
    $maxDistanceKm = $referenceMag >= 4.8 ? 120.0 : 35.0;
    $maxDepthDiffKm = $referenceMag >= 4.8 ? 60.0 : 35.0;

    $timeDiff = abs($aTs - $bTs);
    if ($timeDiff > $maxTimeDiffSeconds) {
        $aProvider = strtolower((string) ($a['source_provider'] ?? ''));
        $bProvider = strtolower((string) ($b['source_provider'] ?? ''));
        $ingvTimezoneShiftCase = ($aProvider === 'ingv' || $bProvider === 'ingv') && abs($timeDiff - 3600) <= 2;
        if (!$ingvTimezoneShiftCase) {
            return false;
        }
    }

    $aLat = isset($a['latitude']) && is_numeric($a['latitude']) ? (float) $a['latitude'] : null;
    $aLon = isset($a['longitude']) && is_numeric($a['longitude']) ? (float) $a['longitude'] : null;
    $bLat = isset($b['latitude']) && is_numeric($b['latitude']) ? (float) $b['latitude'] : null;
    $bLon = isset($b['longitude']) && is_numeric($b['longitude']) ? (float) $b['longitude'] : null;
    if ($aLat === null || $aLon === null || $bLat === null || $bLon === null) {
        return false;
    }

    $aDepth = isset($a['depth_km']) && is_numeric($a['depth_km']) ? (float) $a['depth_km'] : null;
    $bDepth = isset($b['depth_km']) && is_numeric($b['depth_km']) ? (float) $b['depth_km'] : null;
    if ($aDepth !== null && $bDepth !== null && abs($aDepth - $bDepth) > $maxDepthDiffKm) {
        return false;
    }

    return haversine_distance_km($aLat, $aLon, $bLat, $bLon) <= $maxDistanceKm;
}

function dedupe_events(array $events, int $maxEvents): array
{
    $exactDeduped = [];
    $seen = [];
    foreach ($events as $event) {
        if (!is_array($event)) {
            continue;
        }
        if (isset($event['depth_km']) && is_numeric($event['depth_km'])) {
            $event['depth_km'] = abs((float) $event['depth_km']);
        }
        $fingerprint = event_fingerprint($event);
        if (isset($seen[$fingerprint])) {
            continue;
        }
        $seen[$fingerprint] = true;
        $exactDeduped[] = $event;
    }

    $merged = [];
    foreach ($exactDeduped as $event) {
        $mergedIndex = null;
        foreach ($merged as $idx => $existing) {
            if (events_are_probable_duplicate($existing, $event)) {
                $mergedIndex = $idx;
                break;
            }
        }

        if ($mergedIndex === null) {
            $event['source_providers'] = is_array($event['source_providers'] ?? null)
                ? array_values(array_unique($event['source_providers']))
                : [(string) ($event['source_provider'] ?? '')];
            $merged[] = $event;
            continue;
        }

        $existing = $merged[$mergedIndex];
        $existingScore = event_quality_score($existing);
        $candidateScore = event_quality_score($event);
        $providers = array_values(array_unique(array_merge(
            is_array($existing['source_providers'] ?? null) ? $existing['source_providers'] : [(string) ($existing['source_provider'] ?? '')],
            is_array($event['source_providers'] ?? null) ? $event['source_providers'] : [(string) ($event['source_provider'] ?? '')]
        )));

        $italyScoped = event_is_italy_region($existing) || event_is_italy_region($event);
        if ($italyScoped) {
            $existingIsIngv = event_provider_is_ingv($existing);
            $candidateIsIngv = event_provider_is_ingv($event);
            if ($candidateIsIngv && !$existingIsIngv) {
                $event['source_providers'] = $providers;
                $merged[$mergedIndex] = $event;
                continue;
            }
            if ($existingIsIngv && !$candidateIsIngv) {
                $existing['source_providers'] = $providers;
                $merged[$mergedIndex] = $existing;
                continue;
            }
        }

        if ($candidateScore > $existingScore) {
            $event['source_providers'] = $providers;
            $merged[$mergedIndex] = $event;
        } else {
            $existing['source_providers'] = $providers;
            $merged[$mergedIndex] = $existing;
        }
    }

    return array_slice($merged, 0, $maxEvents);
}

$cachePath = $appConfig['data_dir'] . '/earthquakes_latest.json';
$now = time();
$cacheTtl = (int) $appConfig['cache_ttl_seconds'];
$maxEvents = (int) $appConfig['max_events'];
$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';

$cachedPayload = read_json_file($cachePath);
$cacheAge = is_array($cachedPayload) && isset($cachedPayload['generated_at_ts'])
    ? $now - (int) $cachedPayload['generated_at_ts']
    : null;

if (!$forceRefresh && is_array($cachedPayload) && is_int($cacheAge) && $cacheAge <= $cacheTtl) {
    if (isset($cachedPayload['events']) && is_array($cachedPayload['events'])) {
        $cachedPayload['events'] = dedupe_events($cachedPayload['events'], $maxEvents);
        $cachedPayload['events_count'] = count($cachedPayload['events']);
    }
    $cachedPayload['from_cache'] = true;
    $cachedPayload['stale_cache'] = !is_int($cacheAge) || $cacheAge > $cacheTtl;
    json_response(200, $cachedPayload);
}

$sourceConfigs = $feedConfig['earthquakes']['sources'] ?? [];
if (!is_array($sourceConfigs) || count($sourceConfigs) === 0) {
    $legacyUrl = $feedConfig['earthquakes']['url'] ?? null;
    $legacyProvider = (string) ($feedConfig['earthquakes']['provider'] ?? 'USGS');
    if (is_string($legacyUrl) && $legacyUrl !== '') {
        $sourceConfigs = [[
            'key' => 'legacy',
            'provider' => $legacyProvider,
            'url' => $legacyUrl,
        ]];
    }
}

if (count($sourceConfigs) === 0) {
    json_response(500, [
        'ok' => false,
        'error' => 'Earthquake sources not configured',
    ]);
}

$events = [];
$ingestRawEvents = [];
$sourceStatus = [];
$providersUsed = [];
$recentWindowSeconds = 24 * 60 * 60;
$recentCutoffTs = $now - $recentWindowSeconds;

foreach ($sourceConfigs as $sourceConfig) {
    $provider = (string) ($sourceConfig['provider'] ?? 'Unknown');
    $url = (string) ($sourceConfig['url'] ?? '');
    $sourceKey = (string) ($sourceConfig['key'] ?? strtolower(str_replace(' ', '_', $provider)));
    if ($url === '') {
        continue;
    }

    $external = fetch_external_json($url, (int) $appConfig['http_timeout_seconds']);
    if (!is_array($external) || !isset($external['features']) || !is_array($external['features'])) {
        $sourceStatus[] = [
            'key' => $sourceKey,
            'provider' => $provider,
            'status' => 'error',
            'events' => 0,
        ];
        write_log($appConfig['logs_dir'], "Earthquakes feed fetch failed: {$provider} {$url}");
        continue;
    }

    $added = 0;
    foreach ($external['features'] as $feature) {
        if (!is_array($feature)) {
            continue;
        }

        $normalized = normalize_earthquake_feature($feature, $provider);
        if (!is_array($normalized)) {
            continue;
        }

        $eventTs = isset($normalized['event_time_utc']) ? strtotime((string) $normalized['event_time_utc']) : false;
        if (!is_int($eventTs)) {
            continue;
        }

        $ingestRawEvents[] = $normalized;
        if ($eventTs < $recentCutoffTs) {
            continue;
        }

        $events[] = $normalized;
        $added += 1;
    }

    $sourceStatus[] = [
        'key' => $sourceKey,
        'provider' => $provider,
        'status' => 'ok',
        'events' => $added,
    ];
    if ($added > 0) {
        $providersUsed[] = $provider;
    }
}

if (count($events) === 0) {
    if (is_array($cachedPayload)) {
        if (isset($cachedPayload['events']) && is_array($cachedPayload['events'])) {
            $cachedPayload['events'] = dedupe_events($cachedPayload['events'], $maxEvents);
            $cachedPayload['events_count'] = count($cachedPayload['events']);
        }
        $cachedPayload['from_cache'] = true;
        $cachedPayload['stale_cache'] = true;
        json_response(200, $cachedPayload);
    }

    json_response(502, [
        'ok' => false,
        'error' => 'Unable to load earthquake feeds',
    ]);
}

usort($events, static function (array $a, array $b): int {
    $aTs = isset($a['event_time_utc']) ? strtotime((string) $a['event_time_utc']) : 0;
    $bTs = isset($b['event_time_utc']) ? strtotime((string) $b['event_time_utc']) : 0;
    return $bTs <=> $aTs;
});

$deduped = dedupe_events($events, $maxEvents);

$providerLabel = count($providersUsed) > 0 ? implode(' + ', array_values(array_unique($providersUsed))) : 'Quakrs Multi-source';

$payload = [
    'ok' => true,
    'provider' => $providerLabel,
    'providers' => array_values(array_unique($providersUsed)),
    'sources' => $sourceStatus,
    'generated_at_ts' => $now,
    'generated_at' => gmdate('c', $now),
    'events_count' => count($deduped),
    'events' => $deduped,
    'from_cache' => false,
];

$liveReason = null;
$liveDb = earthquake_mysql_open($appConfig, 'live', $liveReason);
$liveCfg = earthquake_mysql_role_config($appConfig, 'live');
$liveTables = earthquake_mysql_role_tables($appConfig, 'live');
$liveTable = (string) ($liveCfg['table'] ?? 'earthquake_events');
if ($liveDb instanceof mysqli) {
    $written = earthquake_archive_ingest($liveDb, $deduped, $now, $liveTables);
    $liveDb->close();
    $payload['live'] = [
        'enabled' => true,
        'db' => 'mysql',
        'table' => $liveTable,
        'tables' => $liveTables,
        'written' => $written,
    ];
} else {
    $payload['live'] = [
        'enabled' => false,
        'db' => 'mysql',
        'reason' => $liveReason ?: 'not configured',
    ];
    write_log($appConfig['logs_dir'], 'Earthquakes live DB unavailable: ' . ($liveReason ?: 'not configured'));
}

$archiveCfg = earthquake_archive_mysql_config($appConfig);
$archiveTable = (string) ($archiveCfg['table'] ?? 'earthquake_events');
$payload['archive'] = [
    'enabled' => false,
    'db' => 'mysql',
    'table' => $archiveTable,
    'reason' => 'archive is populated by rollover/backfill only',
];

$ingestReason = null;
$ingestDb = earthquake_mysql_open($appConfig, 'ingest', $ingestReason);
$ingestCfg = earthquake_mysql_role_config($appConfig, 'ingest');
$ingestTables = earthquake_mysql_role_tables($appConfig, 'ingest');
$ingestTable = (string) ($ingestCfg['table'] ?? 'earthquake_events_raw');
if ($ingestDb instanceof mysqli) {
    $written = earthquake_archive_ingest($ingestDb, $ingestRawEvents, $now, $ingestTables);
    $ingestDb->close();
    $payload['ingest'] = [
        'enabled' => true,
        'db' => 'mysql',
        'table' => $ingestTable,
        'tables' => $ingestTables,
        'written' => $written,
        'raw_events_count' => count($ingestRawEvents),
    ];
} else {
    $payload['ingest'] = [
        'enabled' => false,
        'db' => 'mysql',
        'reason' => $ingestReason ?: 'not configured',
    ];
}

$maxMagnitude = null;
foreach ($deduped as $event) {
    if (!is_array($event) || !isset($event['magnitude']) || !is_numeric($event['magnitude'])) {
        continue;
    }
    $mag = (float) $event['magnitude'];
    if ($maxMagnitude === null || $mag > $maxMagnitude) {
        $maxMagnitude = $mag;
    }
}

$statsReason = null;
$statsDb = earthquake_mysql_open($appConfig, 'stats', $statsReason);
$statsCfg = earthquake_mysql_role_config($appConfig, 'stats');
$statsTable = (string) ($statsCfg['table'] ?? 'earthquake_daily_stats');
if ($statsDb instanceof mysqli) {
    $statsOk = earthquake_stats_upsert_daily(
        $statsDb,
        $statsTable,
        $now,
        count($deduped),
        array_values(array_unique($providersUsed)),
        $maxMagnitude
    );
    $statsDb->close();
    $payload['stats'] = [
        'enabled' => true,
        'db' => 'mysql',
        'table' => $statsTable,
        'updated' => $statsOk,
    ];
} else {
    $payload['stats'] = [
        'enabled' => false,
        'db' => 'mysql',
        'reason' => $statsReason ?: 'not configured',
    ];
}

if (!write_json_file($cachePath, $payload)) {
    write_log($appConfig['logs_dir'], 'Failed writing earthquakes cache JSON');
}

json_response(200, $payload);
