<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

function tsunami_time_to_ts(?string $value): int
{
    if (!is_string($value) || trim($value) === '') {
        return 0;
    }

    $ts = strtotime($value);
    return is_int($ts) ? $ts : 0;
}

function tsunami_rank(string $event, string $severity): int
{
    $eventLower = strtolower($event);
    $severityLower = strtolower($severity);

    if (str_contains($eventLower, 'warning') || $severityLower === 'extreme' || $severityLower === 'severe') {
        return 4;
    }
    if (str_contains($eventLower, 'watch') || $severityLower === 'moderate') {
        return 3;
    }
    if (str_contains($eventLower, 'advisory')) {
        return 2;
    }
    if (str_contains($eventLower, 'statement') || str_contains($eventLower, 'information')) {
        return 1;
    }

    return 0;
}

function tsunami_level_label(int $rank): string
{
    return match (true) {
        $rank >= 4 => 'Warning',
        $rank === 3 => 'Watch',
        $rank === 2 => 'Advisory',
        $rank === 1 => 'Statement',
        default => 'None',
    };
}

$cachePath = $appConfig['data_dir'] . '/tsunami_latest.json';
$now = time();
$cacheTtl = (int) $appConfig['cache_ttl_seconds'];
$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';

$cachedPayload = read_json_file($cachePath);
$cacheAge = is_array($cachedPayload) && isset($cachedPayload['generated_at_ts'])
    ? $now - (int) $cachedPayload['generated_at_ts']
    : null;

if (!$forceRefresh && is_array($cachedPayload)) {
    $cachedPayload['from_cache'] = true;
    $cachedPayload['stale_cache'] = !is_int($cacheAge) || $cacheAge > $cacheTtl;
    json_response(200, $cachedPayload);
}

$feedUrl = (string) ($feedConfig['tsunami']['url'] ?? '');
$provider = (string) ($feedConfig['tsunami']['provider'] ?? 'Unknown');
$external = fetch_external_json($feedUrl, (int) $appConfig['http_timeout_seconds']);

if (!is_array($external) || !isset($external['features']) || !is_array($external['features'])) {
    write_log($appConfig['logs_dir'], "Tsunami feed fetch failed: {$feedUrl}");

    if (is_array($cachedPayload)) {
        $cachedPayload['from_cache'] = true;
        $cachedPayload['stale_cache'] = true;
        json_response(200, $cachedPayload);
    }

    json_response(502, [
        'ok' => false,
        'error' => 'Unable to load tsunami feed',
    ]);
}

$alerts = [];
$regionSet = [];
$maxRank = 0;
$maxAlerts = min(100, (int) $appConfig['max_events']);

foreach ($external['features'] as $feature) {
    if (!is_array($feature)) {
        continue;
    }

    $properties = $feature['properties'] ?? [];
    if (!is_array($properties)) {
        continue;
    }

    $event = (string) ($properties['event'] ?? '');
    $headline = (string) ($properties['headline'] ?? '');
    $targetText = strtolower(trim($event . ' ' . $headline));
    if (!str_contains($targetText, 'tsunami')) {
        continue;
    }

    $severity = (string) ($properties['severity'] ?? 'Unknown');
    $rank = tsunami_rank($event, $severity);
    if ($rank > $maxRank) {
        $maxRank = $rank;
    }

    $areaDesc = trim((string) ($properties['areaDesc'] ?? 'Unknown region'));
    $regionSet[$areaDesc] = true;

    $issuedAt = (string) ($properties['sent'] ?? $properties['effective'] ?? '');
    $expiresAt = (string) ($properties['expires'] ?? '');
    $issuedTs = tsunami_time_to_ts($issuedAt);

    $expiresTs = tsunami_time_to_ts($expiresAt);

    $alerts[] = [
        'id' => (string) ($properties['id'] ?? ''),
        'event' => $event !== '' ? $event : 'Tsunami Alert',
        'region' => $areaDesc,
        'warning_level' => tsunami_level_label($rank),
        'severity' => $severity,
        'status' => (string) ($properties['status'] ?? 'Unknown'),
        'issued_at_utc' => $issuedTs > 0 ? gmdate('c', $issuedTs) : null,
        'expires_at_utc' => $expiresTs > 0 ? gmdate('c', $expiresTs) : null,
        'source_bulletin' => (string) ($properties['uri'] ?? $properties['web'] ?? ''),
    ];
}

usort($alerts, static function (array $a, array $b): int {
    $aTs = isset($a['issued_at_utc']) ? strtotime((string) $a['issued_at_utc']) : 0;
    $bTs = isset($b['issued_at_utc']) ? strtotime((string) $b['issued_at_utc']) : 0;
    return $bTs <=> $aTs;
});
$alerts = array_slice($alerts, 0, $maxAlerts);

$payload = [
    'ok' => true,
    'provider' => $provider,
    'generated_at_ts' => $now,
    'generated_at' => gmdate('c', $now),
    'alerts_count' => count($alerts),
    'regions_count' => count($regionSet),
    'highest_level' => tsunami_level_label($maxRank),
    'alerts' => $alerts,
    'from_cache' => false,
];

if (!write_json_file($cachePath, $payload)) {
    write_log($appConfig['logs_dir'], 'Failed writing tsunami cache JSON');
}

json_response(200, $payload);
