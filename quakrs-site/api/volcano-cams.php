<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

function slugify_cam_name(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-') !== '' ? trim($value, '-') : 'cam';
}

function normalize_volcano_key(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    return $value;
}

function load_hotspot_index(string $path): array
{
    $payload = read_json_file($path);
    if (!is_array($payload) || !isset($payload['hotspots']) || !is_array($payload['hotspots'])) {
        return [];
    }

    $index = [];
    foreach ($payload['hotspots'] as $hotspot) {
        if (!is_array($hotspot)) {
            continue;
        }

        $volcano = trim((string) ($hotspot['volcano'] ?? ''));
        if ($volcano === '') {
            continue;
        }

        $key = normalize_volcano_key($volcano);
        if ($key === '') {
            continue;
        }

        $index[$key] = $hotspot;
    }

    return $index;
}

function volcano_cam_priority(array $cam, ?array $hotspot): array
{
    $score = 10;
    $reasons = [];

    $hasEmbed = !empty($cam['embed_url']);
    $hasSnapshot = !empty($cam['snapshot_url']);

    if ($hasEmbed) {
        $score += 16;
        $reasons[] = 'Inline live player';
    } elseif ($hasSnapshot) {
        $score += 9;
        $reasons[] = 'Direct snapshot available';
    } else {
        $reasons[] = 'External-only source';
    }

    if (is_array($hotspot)) {
        $reports = (int) ($hotspot['reports'] ?? 0);
        $newEruptive = (int) ($hotspot['new_eruptive_reports'] ?? 0);
        $status = strtolower((string) ($hotspot['status'] ?? ''));

        if ($newEruptive > 0) {
            $score += 45;
            $reasons[] = 'New eruptive activity';
        } elseif (str_contains($status, 'continuing eruptive')) {
            $score += 28;
            $reasons[] = 'Continuing eruptive activity';
        } elseif (str_contains($status, 'unrest')) {
            $score += 18;
            $reasons[] = 'Volcanic unrest';
        } else {
            $score += 10;
            $reasons[] = 'Active hotspot';
        }

        if ($reports > 0) {
            $score += min(15, $reports * 4);
            $reasons[] = "{$reports} hotspot reports";
        }

        $latestTs = isset($hotspot['latest_event_utc']) ? strtotime((string) $hotspot['latest_event_utc']) : 0;
        if (is_int($latestTs) && $latestTs > 0) {
            $hoursAgo = (int) floor((time() - $latestTs) / 3600);
            if ($hoursAgo <= 24) {
                $score += 10;
                $reasons[] = 'Updated in last 24h';
            } elseif ($hoursAgo <= 72) {
                $score += 6;
                $reasons[] = 'Updated in last 72h';
            }
        }
    }

    return [
        'score' => $score,
        'reasons' => $reasons,
    ];
}

$cachePath = $appConfig['data_dir'] . '/volcano_cams_latest.json';
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

$provider = (string) ($feedConfig['volcano_cams']['provider'] ?? 'Curated Observatory Cameras');
$items = $feedConfig['volcano_cams']['items'] ?? [];
$cams = [];
$countries = [];
$withSnapshots = 0;
$hotspotIndex = load_hotspot_index($appConfig['data_dir'] . '/hotspots_latest.json');

if (is_array($items)) {
    foreach ($items as $row) {
        if (!is_array($row)) {
            continue;
        }

        $name = trim((string) ($row['name'] ?? ''));
        $volcano = trim((string) ($row['volcano'] ?? $name));
        $country = trim((string) ($row['country'] ?? 'Unknown'));
        $source = trim((string) ($row['source'] ?? 'Unknown source'));
        $streamUrl = trim((string) ($row['stream_url'] ?? ''));
        $embedUrl = array_key_exists('embed_url', $row)
            ? trim((string) $row['embed_url'])
            : '';
        $snapshotUrl = isset($row['snapshot_url']) ? trim((string) $row['snapshot_url']) : '';
        $status = trim((string) ($row['status'] ?? 'Monitoring'));

        if ($name === '' || $streamUrl === '') {
            continue;
        }

        $hasSnapshot = $snapshotUrl !== '';
        if ($hasSnapshot) {
            $withSnapshots += 1;
        }

        $countries[$country] = true;
        $volcanoKey = normalize_volcano_key($volcano);
        $hotspot = $volcanoKey !== '' && isset($hotspotIndex[$volcanoKey]) ? $hotspotIndex[$volcanoKey] : null;

        $priority = volcano_cam_priority([
            'embed_url' => $embedUrl,
            'snapshot_url' => $snapshotUrl,
        ], $hotspot);

        $cams[] = [
            'id' => slugify_cam_name($name . '-' . $country),
            'name' => $name,
            'volcano' => $volcano,
            'country' => $country,
            'source' => $source,
            'status' => $status,
            'stream_url' => $streamUrl,
            'embed_url' => $embedUrl !== '' ? $embedUrl : null,
            'snapshot_url' => $hasSnapshot ? $snapshotUrl : null,
            'snapshot_fallback_available' => $hasSnapshot,
            'priority_score' => (int) $priority['score'],
            'priority_reasons' => $priority['reasons'],
            'hotspot_status' => is_array($hotspot) ? (string) ($hotspot['status'] ?? '') : null,
            'hotspot_reports' => is_array($hotspot) ? (int) ($hotspot['reports'] ?? 0) : 0,
            'hotspot_new_eruptive_reports' => is_array($hotspot) ? (int) ($hotspot['new_eruptive_reports'] ?? 0) : 0,
        ];
    }
}

usort($cams, static function (array $a, array $b): int {
    if (($b['priority_score'] ?? 0) !== ($a['priority_score'] ?? 0)) {
        return (int) $b['priority_score'] <=> (int) $a['priority_score'];
    }
    return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
});

$hotNowCount = min(8, max(6, (int) ceil(count($cams) * 0.45)));
$maxPerVolcanoInHot = 2;
$hotNow = [];
$deferred = [];
$volcanoCounter = [];

foreach ($cams as $cam) {
    $volcanoKey = normalize_volcano_key((string) ($cam['volcano'] ?? $cam['name'] ?? ''));
    $currentCount = (int) ($volcanoCounter[$volcanoKey] ?? 0);

    if (count($hotNow) < $hotNowCount && $currentCount < $maxPerVolcanoInHot) {
        $hotNow[] = $cam;
        $volcanoCounter[$volcanoKey] = $currentCount + 1;
        continue;
    }

    $deferred[] = $cam;
}

if (count($hotNow) < $hotNowCount) {
    foreach ($deferred as $cam) {
        $hotNow[] = $cam;
        if (count($hotNow) >= $hotNowCount) {
            break;
        }
    }
}

$hotIds = array_fill_keys(array_map(static fn(array $cam): string => (string) $cam['id'], $hotNow), true);
$rotatingCandidates = array_values(array_filter($cams, static function (array $cam) use ($hotIds): bool {
    return !isset($hotIds[(string) $cam['id']]);
}));

if ($rotatingCandidates === [] && count($cams) > 6) {
    $rotatingCandidates = array_slice($cams, 3);
}

$payload = [
    'ok' => true,
    'provider' => $provider,
    'generated_at_ts' => $now,
    'generated_at' => gmdate('c', $now),
    'cams_count' => count($cams),
    'countries_count' => count($countries),
    'snapshot_enabled_count' => $withSnapshots,
    'cams' => $cams,
    'hot_now' => $hotNow,
    'rotating_candidates' => $rotatingCandidates,
    'rotation_interval_seconds' => 20,
    'from_cache' => false,
];

if (!write_json_file($cachePath, $payload)) {
    write_log($appConfig['logs_dir'], 'Failed writing volcano cams cache JSON');
}

json_response(200, $payload);
