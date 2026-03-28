<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

function slugify_space_cam_name(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-') !== '' ? trim($value, '-') : 'cam';
}

function normalize_space_tag(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9\s]+/', ' ', $value) ?? '';
    $value = preg_replace('/\s+/', ' ', $value) ?? '';
    return trim($value);
}

function space_aurora_match_label(int $rank): string
{
    return match (true) {
        $rank >= 3 => 'High',
        $rank === 2 => 'Moderate',
        $rank === 1 => 'Watch',
        default => 'None',
    };
}

function space_cam_priority(array $cam, float $kpCurrent, float $kpMax24h, array $auroraTargets): array
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

    if ($kpCurrent >= 7.0) {
        $score += 24;
        $reasons[] = 'Geomagnetic storm severe';
    } elseif ($kpCurrent >= 6.0) {
        $score += 18;
        $reasons[] = 'Geomagnetic storm active';
    } elseif ($kpCurrent >= 5.0) {
        $score += 12;
        $reasons[] = 'Geomagnetic storm threshold reached';
    } elseif ($kpCurrent >= 4.0) {
        $score += 6;
        $reasons[] = 'Geomagnetic level elevated';
    }

    if ($kpMax24h >= 7.0) {
        $score += 8;
        $reasons[] = 'Strong 24h Kp peak';
    } elseif ($kpMax24h >= 6.0) {
        $score += 5;
    }

    $tags = [];
    if (isset($cam['region_tags']) && is_array($cam['region_tags'])) {
        foreach ($cam['region_tags'] as $tag) {
            if (!is_string($tag)) {
                continue;
            }
            $normalized = normalize_space_tag($tag);
            if ($normalized !== '') {
                $tags[] = $normalized;
            }
        }
    }

    $tagMatches = 0;
    $maxMatchedRank = 0;

    foreach ($auroraTargets as $target) {
        if (!is_array($target)) {
            continue;
        }

        $region = normalize_space_tag((string) (($target['city'] ?? '') . ' ' . ($target['country'] ?? '')));
        if ($region === '') {
            continue;
        }

        $targetRank = isset($target['rank']) && is_numeric($target['rank'])
            ? (int) $target['rank']
            : 0;

        foreach ($tags as $tag) {
            if ($tag !== '' && str_contains($region, $tag)) {
                $tagMatches += 1;
                if ($targetRank > $maxMatchedRank) {
                    $maxMatchedRank = $targetRank;
                }
                break;
            }
        }
    }

    if ($tagMatches > 0) {
        $score += min(24, $tagMatches * 8);
        $reasons[] = "{$tagMatches} regional aurora matches";
    }

    if ($maxMatchedRank >= 3) {
        $score += 16;
        $reasons[] = 'Matched high aurora target';
    } elseif ($maxMatchedRank === 2) {
        $score += 10;
        $reasons[] = 'Matched moderate aurora target';
    } elseif ($maxMatchedRank === 1) {
        $score += 4;
        $reasons[] = 'Matched aurora watch target';
    }

    return [
        'score' => $score,
        'reasons' => $reasons,
        'risk_tier' => $riskTier,
        'region_match_count' => $tagMatches,
        'matched_max_rank' => $maxMatchedRank,
    ];
}

$cachePath = $appConfig['data_dir'] . '/space_weather_cams_latest.json';
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

$provider = (string) ($feedConfig['space_weather_cams']['provider'] ?? 'Curated Aurora & Space Weather Cameras');
$items = $feedConfig['space_weather_cams']['items'] ?? [];
$spacePayload = read_json_file($appConfig['data_dir'] . '/space_weather_latest.json');

$kpCurrent = is_array($spacePayload) && isset($spacePayload['kp_index_current']) && is_numeric($spacePayload['kp_index_current'])
    ? (float) $spacePayload['kp_index_current']
    : 0.0;
$kpMax24h = is_array($spacePayload) && isset($spacePayload['kp_index_max_24h']) && is_numeric($spacePayload['kp_index_max_24h'])
    ? (float) $spacePayload['kp_index_max_24h']
    : $kpCurrent;
$stormLevel = is_array($spacePayload) ? (string) ($spacePayload['storm_level'] ?? 'Quiet to unsettled') : 'Quiet to unsettled';

$auroraRaw = is_array($spacePayload) && isset($spacePayload['aurora_targets']) && is_array($spacePayload['aurora_targets'])
    ? $spacePayload['aurora_targets']
    : [];

$auroraTargets = [];
$highestAuroraRank = 0;

foreach (['high' => 3, 'moderate' => 2, 'watch' => 1] as $bucket => $rank) {
    $rows = isset($auroraRaw[$bucket]) && is_array($auroraRaw[$bucket]) ? $auroraRaw[$bucket] : [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $city = trim((string) ($row['city'] ?? ''));
        $country = trim((string) ($row['country'] ?? ''));
        if ($city === '' && $country === '') {
            continue;
        }

        $auroraTargets[] = [
            'city' => $city,
            'country' => $country,
            'rank' => $rank,
            'value' => isset($row['value']) && is_numeric($row['value']) ? (int) $row['value'] : null,
        ];

        if ($rank > $highestAuroraRank) {
            $highestAuroraRank = $rank;
        }
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
        $status = trim((string) ($row['status'] ?? 'Aurora watch'));

        if ($name === '' || $streamUrl === '') {
            continue;
        }

        $hasSnapshot = $snapshotUrl !== '';
        if ($hasSnapshot) {
            $withSnapshots += 1;
        }

        $countries[$country] = true;

        $priority = space_cam_priority([
            'embed_url' => $embedUrl,
            'snapshot_url' => $snapshotUrl,
            'risk_tier' => $row['risk_tier'] ?? 1,
            'region_tags' => $row['region_tags'] ?? [],
        ], $kpCurrent, $kpMax24h, $auroraTargets);

        $cams[] = [
            'id' => slugify_space_cam_name($name . '-' . $country),
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
            'matched_max_level' => space_aurora_match_label((int) $priority['matched_max_rank']),
        ];
    }
}

usort($cams, static function (array $a, array $b): int {
    if (($b['priority_score'] ?? 0) !== ($a['priority_score'] ?? 0)) {
        return (int) $b['priority_score'] <=> (int) $a['priority_score'];
    }
    return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
});

$hotNow = array_values(array_filter($cams, static function (array $cam) use ($kpCurrent): bool {
    $score = (int) ($cam['priority_score'] ?? 0);
    $matches = (int) ($cam['region_match_count'] ?? 0);
    $matchedRank = (int) ($cam['matched_max_level_rank'] ?? 0);
    $risk = (int) ($cam['risk_tier'] ?? 1);

    if ($score < 38) {
        return false;
    }

    return $matchedRank >= 2
        || ($kpCurrent >= 5.0 && $matches >= 1)
        || ($kpCurrent >= 6.0 && $risk >= 3)
        || ($kpCurrent >= 7.0 && $risk >= 2);
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
    'kp_index_current' => $kpCurrent,
    'kp_index_max_24h' => $kpMax24h,
    'storm_level' => $stormLevel,
    'aurora_targets_count' => count($auroraTargets),
    'highest_aurora_level' => space_aurora_match_label($highestAuroraRank),
    'cams' => $cams,
    'hot_now' => $hotNow,
    'rotating_candidates' => $rotatingCandidates,
    'rotation_interval_seconds' => 20,
    'from_cache' => false,
];

if (!write_json_file($cachePath, $payload)) {
    write_log($appConfig['logs_dir'], 'Failed writing space weather cams cache JSON');
}

json_response(200, $payload);
