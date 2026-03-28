<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

function slugify_tsunami_cam_name(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-') !== '' ? trim($value, '-') : 'cam';
}

function tsunami_alert_level_rank(string $value): int
{
    $value = strtolower(trim($value));
    return match ($value) {
        'warning' => 4,
        'watch' => 3,
        'advisory' => 2,
        'statement' => 1,
        default => 0,
    };
}

function normalize_tag(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9\s]+/', ' ', $value) ?? '';
    $value = preg_replace('/\s+/', ' ', $value) ?? '';
    return trim($value);
}

function tsunami_cam_priority(array $cam, array $alerts, int $highestLevelRank): array
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

    if ($highestLevelRank >= 4) {
        $score += 22;
        $reasons[] = 'Global tsunami warning level';
    } elseif ($highestLevelRank === 3) {
        $score += 12;
        $reasons[] = 'Global tsunami watch level';
    } elseif ($highestLevelRank === 2) {
        $score += 6;
        $reasons[] = 'Global advisory level';
    }

    $tagMatches = 0;
    $maxMatchedLevel = 0;

    $tags = [];
    if (isset($cam['region_tags']) && is_array($cam['region_tags'])) {
        foreach ($cam['region_tags'] as $tag) {
            if (!is_string($tag)) {
                continue;
            }
            $normalized = normalize_tag($tag);
            if ($normalized !== '') {
                $tags[] = $normalized;
            }
        }
    }

    foreach ($alerts as $alert) {
        if (!is_array($alert)) {
            continue;
        }

        $region = normalize_tag((string) ($alert['region'] ?? ''));
        if ($region === '') {
            continue;
        }

        $alertLevel = tsunami_alert_level_rank((string) ($alert['warning_level'] ?? ''));
        foreach ($tags as $tag) {
            if ($tag !== '' && str_contains($region, $tag)) {
                $tagMatches += 1;
                if ($alertLevel > $maxMatchedLevel) {
                    $maxMatchedLevel = $alertLevel;
                }
                break;
            }
        }
    }

    if ($tagMatches > 0) {
        $score += min(30, $tagMatches * 10);
        $reasons[] = "{$tagMatches} regional alert matches";
    }

    if ($maxMatchedLevel >= 4) {
        $score += 25;
        $reasons[] = 'Matched tsunami warning';
    } elseif ($maxMatchedLevel === 3) {
        $score += 16;
        $reasons[] = 'Matched tsunami watch';
    } elseif ($maxMatchedLevel === 2) {
        $score += 9;
        $reasons[] = 'Matched tsunami advisory';
    }

    return [
        'score' => $score,
        'reasons' => $reasons,
        'risk_tier' => $riskTier,
        'region_match_count' => $tagMatches,
        'matched_max_level_rank' => $maxMatchedLevel,
    ];
}

$cachePath = $appConfig['data_dir'] . '/tsunami_cams_latest.json';
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

$provider = (string) ($feedConfig['tsunami_cams']['provider'] ?? 'Curated Coastal Tsunami Watch Cameras');
$items = $feedConfig['tsunami_cams']['items'] ?? [];
$tsunamiPayload = read_json_file($appConfig['data_dir'] . '/tsunami_latest.json');
$alerts = is_array($tsunamiPayload) && isset($tsunamiPayload['alerts']) && is_array($tsunamiPayload['alerts'])
    ? $tsunamiPayload['alerts']
    : [];
$highestLevelRank = is_array($tsunamiPayload)
    ? tsunami_alert_level_rank((string) ($tsunamiPayload['highest_level'] ?? 'None'))
    : 0;

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
        $status = trim((string) ($row['status'] ?? 'Coastal watch'));

        if ($name === '' || $streamUrl === '') {
            continue;
        }

        $hasSnapshot = $snapshotUrl !== '';
        if ($hasSnapshot) {
            $withSnapshots += 1;
        }

        $countries[$country] = true;

        $priority = tsunami_cam_priority([
            'embed_url' => $embedUrl,
            'snapshot_url' => $snapshotUrl,
            'risk_tier' => $row['risk_tier'] ?? 1,
            'region_tags' => $row['region_tags'] ?? [],
        ], $alerts, $highestLevelRank);

        $cams[] = [
            'id' => slugify_tsunami_cam_name($name . '-' . $country),
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
            'matched_max_level_rank' => (int) $priority['matched_max_level_rank'],
            'matched_max_level' => match ((int) $priority['matched_max_level_rank']) {
                4 => 'Warning',
                3 => 'Watch',
                2 => 'Advisory',
                1 => 'Statement',
                default => 'None',
            },
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

    if ($score < 36) {
        return false;
    }

    return $matchedRank >= 2 || $matches >= 1 || ($risk >= 4 && $score >= 44);
}));

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
    'highest_level' => is_array($tsunamiPayload) ? (string) ($tsunamiPayload['highest_level'] ?? 'None') : 'None',
    'cams' => $cams,
    'hot_now' => $hotNow,
    'rotating_candidates' => $rotatingCandidates,
    'rotation_interval_seconds' => 20,
    'from_cache' => false,
];

if (!write_json_file($cachePath, $payload)) {
    write_log($appConfig['logs_dir'], 'Failed writing tsunami cams cache JSON');
}

json_response(200, $payload);
