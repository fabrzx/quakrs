<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

function slugify_weather_cam_name(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-') !== '' ? trim($value, '-') : 'cam';
}

function weather_alert_rank(string $event, string $severity): int
{
    $eventLower = strtolower($event);
    $severityLower = strtolower($severity);

    if (
        str_contains($eventLower, 'hurricane')
        || str_contains($eventLower, 'tornado')
        || str_contains($eventLower, 'flash flood warning')
        || str_contains($eventLower, 'extreme wind')
        || $severityLower === 'extreme'
    ) {
        return 4;
    }

    if (
        str_contains($eventLower, 'warning')
        || str_contains($eventLower, 'blizzard')
        || str_contains($eventLower, 'severe thunderstorm')
        || str_contains($eventLower, 'flood warning')
        || $severityLower === 'severe'
    ) {
        return 3;
    }

    if (
        str_contains($eventLower, 'watch')
        || str_contains($eventLower, 'advisory')
        || str_contains($eventLower, 'wind')
        || str_contains($eventLower, 'heat')
        || $severityLower === 'moderate'
    ) {
        return 2;
    }

    if ($eventLower !== '') {
        return 1;
    }

    return 0;
}

function weather_level_label(int $rank): string
{
    return match (true) {
        $rank >= 4 => 'Critical',
        $rank === 3 => 'High',
        $rank === 2 => 'Elevated',
        $rank === 1 => 'Low',
        default => 'None',
    };
}

function normalize_weather_tag(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9\s]+/', ' ', $value) ?? '';
    $value = preg_replace('/\s+/', ' ', $value) ?? '';
    return trim($value);
}

function weather_cam_priority(array $cam, array $alerts, int $highestRank): array
{
    $score = 10;
    $reasons = [];

    $hasEmbed = !empty($cam['embed_url']);
    $hasSnapshot = !empty($cam['snapshot_url']);

    if ($hasEmbed) {
        $score += 12;
        $reasons[] = 'Inline live player';
    } elseif ($hasSnapshot) {
        $score += 7;
        $reasons[] = 'Direct snapshot available';
    } else {
        $reasons[] = 'External-only source';
    }

    $riskTier = isset($cam['risk_tier']) && is_numeric($cam['risk_tier'])
        ? max(1, min(4, (int) $cam['risk_tier']))
        : 1;
    $score += $riskTier * 4;
    $reasons[] = "Risk tier {$riskTier}";

    if ($highestRank >= 4) {
        $score += 16;
        $reasons[] = 'Critical weather alerts active';
    } elseif ($highestRank === 3) {
        $score += 10;
        $reasons[] = 'High weather alerts active';
    } elseif ($highestRank === 2) {
        $score += 5;
        $reasons[] = 'Elevated weather alerts active';
    }

    $tagMatches = 0;
    $maxMatchedRank = 0;

    $tags = [];
    if (isset($cam['region_tags']) && is_array($cam['region_tags'])) {
        foreach ($cam['region_tags'] as $tag) {
            if (!is_string($tag)) {
                continue;
            }
            $normalized = normalize_weather_tag($tag);
            if ($normalized !== '') {
                $tags[] = $normalized;
            }
        }
    }

    foreach ($alerts as $alert) {
        if (!is_array($alert)) {
            continue;
        }

        $region = normalize_weather_tag((string) ($alert['region'] ?? ''));
        if ($region === '') {
            continue;
        }

        $alertRank = (int) ($alert['rank'] ?? 0);
        foreach ($tags as $tag) {
            if ($tag !== '' && str_contains($region, $tag)) {
                $tagMatches += 1;
                if ($alertRank > $maxMatchedRank) {
                    $maxMatchedRank = $alertRank;
                }
                break;
            }
        }
    }

    if ($tagMatches > 0) {
        $score += min(24, $tagMatches * 8);
        $reasons[] = "{$tagMatches} regional alert matches";
    }

    if ($maxMatchedRank >= 4) {
        $score += 20;
        $reasons[] = 'Matched critical weather alert';
    } elseif ($maxMatchedRank === 3) {
        $score += 12;
        $reasons[] = 'Matched high weather alert';
    } elseif ($maxMatchedRank === 2) {
        $score += 6;
        $reasons[] = 'Matched elevated weather alert';
    }

    return [
        'score' => $score,
        'reasons' => $reasons,
        'risk_tier' => $riskTier,
        'region_match_count' => $tagMatches,
        'matched_max_rank' => $maxMatchedRank,
    ];
}

$cachePath = $appConfig['data_dir'] . '/weather_cams_latest.json';
$now = time();
$cacheTtl = (int) $appConfig['cache_ttl_seconds'];
$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';

$cachedPayload = read_json_file($cachePath);
$cacheAge = is_array($cachedPayload) && isset($cachedPayload['generated_at_ts'])
    ? $now - (int) $cachedPayload['generated_at_ts']
    : null;

if (!$forceRefresh && is_array($cachedPayload) && is_int($cacheAge) && $cacheAge <= $cacheTtl) {
    $cachedPayload['from_cache'] = true;
    $cachedPayload['stale_cache'] = !is_int($cacheAge) || $cacheAge > $cacheTtl;
    json_response(200, $cachedPayload);
}

$provider = (string) ($feedConfig['weather_cams']['provider'] ?? 'Curated Severe Weather Cameras');
$items = $feedConfig['weather_cams']['items'] ?? [];
$alertsUrl = (string) ($feedConfig['weather_alerts']['url'] ?? '');
$external = fetch_external_json($alertsUrl, (int) $appConfig['http_timeout_seconds']);

$alerts = [];
$highestRank = 0;
if (is_array($external) && isset($external['features']) && is_array($external['features'])) {
    foreach ($external['features'] as $feature) {
        if (!is_array($feature)) {
            continue;
        }

        $properties = $feature['properties'] ?? [];
        if (!is_array($properties)) {
            continue;
        }

        $event = trim((string) ($properties['event'] ?? ''));
        if ($event === '') {
            continue;
        }

        $severity = trim((string) ($properties['severity'] ?? 'Unknown'));
        $rank = weather_alert_rank($event, $severity);
        if ($rank <= 0) {
            continue;
        }

        $highestRank = max($highestRank, $rank);
        $alerts[] = [
            'event' => $event,
            'severity' => $severity,
            'region' => (string) ($properties['areaDesc'] ?? ''),
            'rank' => $rank,
        ];
    }
}

$cams = [];
$countries = [];
$withSnapshots = 0;

if (is_array($items)) {
    foreach ($items as $row) {
        if (!is_array($row)) {
            continue;
        }

        $name = trim((string) ($row['name'] ?? ''));
        $region = trim((string) ($row['region'] ?? $name));
        $country = trim((string) ($row['country'] ?? 'Unknown'));
        $source = trim((string) ($row['source'] ?? 'Unknown source'));
        $streamUrl = trim((string) ($row['stream_url'] ?? ''));
        $embedUrl = array_key_exists('embed_url', $row)
            ? trim((string) $row['embed_url'])
            : '';
        $snapshotUrl = isset($row['snapshot_url']) ? trim((string) $row['snapshot_url']) : '';
        $status = trim((string) ($row['status'] ?? 'Weather watch'));

        if ($name === '' || $streamUrl === '') {
            continue;
        }

        $hasSnapshot = $snapshotUrl !== '';
        if ($hasSnapshot) {
            $withSnapshots += 1;
        }

        $countries[$country] = true;

        $priority = weather_cam_priority([
            'embed_url' => $embedUrl,
            'snapshot_url' => $snapshotUrl,
            'risk_tier' => $row['risk_tier'] ?? 1,
            'region_tags' => $row['region_tags'] ?? [],
        ], $alerts, $highestRank);

        $cams[] = [
            'id' => slugify_weather_cam_name($name . '-' . $country),
            'name' => $name,
            'region' => $region,
            'country' => $country,
            'source' => $source,
            'status' => $status,
            'stream_url' => $streamUrl,
            'embed_url' => $embedUrl !== '' ? $embedUrl : null,
            'snapshot_url' => $hasSnapshot ? $snapshotUrl : null,
            'snapshot_fallback_available' => $hasSnapshot,
            'priority_score' => (int) $priority['score'],
            'priority_reasons' => $priority['reasons'],
            'risk_tier' => (int) $priority['risk_tier'],
            'region_match_count' => (int) $priority['region_match_count'],
            'matched_max_level_rank' => (int) $priority['matched_max_rank'],
            'matched_max_level' => weather_level_label((int) $priority['matched_max_rank']),
        ];
    }
}

usort($cams, static function (array $a, array $b): int {
    if (($b['priority_score'] ?? 0) !== ($a['priority_score'] ?? 0)) {
        return (int) $b['priority_score'] <=> (int) $a['priority_score'];
    }
    return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
});

$hotNow = array_values(array_filter($cams, static function (array $cam): bool {
    $score = (int) ($cam['priority_score'] ?? 0);
    $matches = (int) ($cam['region_match_count'] ?? 0);
    $matchedRank = (int) ($cam['matched_max_level_rank'] ?? 0);
    $risk = (int) ($cam['risk_tier'] ?? 1);

    if ($score < 34) {
        return false;
    }

    return $matchedRank >= 2 || $matches >= 1 || ($risk >= 4 && $score >= 42);
}));

if ($hotNow === []) {
    $hotNow = array_slice($cams, 0, min(4, count($cams)));
}

$hotIds = array_fill_keys(array_map(static fn(array $cam): string => (string) $cam['id'], $hotNow), true);
$rotatingCandidates = array_values(array_filter($cams, static function (array $cam) use ($hotIds): bool {
    return !isset($hotIds[(string) $cam['id']]);
}));

$payload = [
    'ok' => true,
    'provider' => $provider,
    'generated_at_ts' => $now,
    'generated_at' => gmdate('c', $now),
    'cams_count' => count($cams),
    'countries_count' => count($countries),
    'snapshot_enabled_count' => $withSnapshots,
    'alerts_count' => count($alerts),
    'highest_level' => weather_level_label($highestRank),
    'cams' => $cams,
    'hot_now' => $hotNow,
    'rotating_candidates' => $rotatingCandidates,
    'rotation_interval_seconds' => 20,
    'from_cache' => false,
];

if (!write_json_file($cachePath, $payload)) {
    write_log($appConfig['logs_dir'], 'Failed writing weather cams cache JSON');
}

json_response(200, $payload);
