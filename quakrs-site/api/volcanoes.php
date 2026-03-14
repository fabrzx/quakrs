<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

function norm_merge_part(string $value): string
{
    $lower = function_exists('mb_strtolower')
        ? mb_strtolower(trim($value), 'UTF-8')
        : strtolower(trim($value));
    if (function_exists('iconv')) {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $lower);
        if (is_string($ascii) && $ascii !== '') {
            $lower = $ascii;
        }
    }
    $clean = preg_replace('/[^a-z0-9]+/', ' ', $lower ?? '') ?? '';
    return trim($clean);
}

function merge_key(string $volcano, string $country): string
{
    return norm_merge_part($volcano) . '|' . norm_merge_part($country);
}

function activity_index(array $rows, int $now): int
{
    if (count($rows) === 0) {
        return 0;
    }
    usort($rows, static function (array $a, array $b): int {
        $aTs = isset($a['event_time_utc']) ? strtotime((string) $a['event_time_utc']) : 0;
        $bTs = isset($b['event_time_utc']) ? strtotime((string) $b['event_time_utc']) : 0;
        return $bTs <=> $aTs;
    });
    $latestTs = isset($rows[0]['event_time_utc']) ? strtotime((string) $rows[0]['event_time_utc']) : 0;
    $ageHours = is_int($latestTs) && $latestTs > 0
        ? max(0, (int) floor(($now - $latestTs) / 3600))
        : 168;
    $recencyBoost = max(0, 32 - min(32, (int) floor($ageHours / 4)));
    $eruptive = 0;
    $unrest = 0;
    $continuing = 0;
    foreach ($rows as $row) {
        $title = strtolower((string) ($row['title'] ?? ''));
        if (!empty($row['is_new_eruptive'])) {
            $eruptive += 1;
        }
        if (str_contains($title, 'unrest')) {
            $unrest += 1;
        }
        if (str_contains($title, 'continuing')) {
            $continuing += 1;
        }
    }
    $score = min(42, count($rows) * 7) + ($eruptive * 20) + ($unrest * 12) + ($continuing * 6) + $recencyBoost;
    return max(0, min(100, (int) round($score)));
}

function build_live_snapshot(array $events, int $snapshotTs, int $now): array
{
    $byMerge = [];
    foreach ($events as $event) {
        if (!is_array($event)) {
            continue;
        }
        $volcano = (string) ($event['volcano'] ?? $event['title'] ?? 'Unknown');
        $country = (string) ($event['country'] ?? 'Unknown');
        $key = merge_key($volcano, $country);
        if (!isset($byMerge[$key])) {
            $byMerge[$key] = [
                'volcano' => $volcano,
                'country' => $country,
                'rows' => [],
            ];
        }
        $byMerge[$key]['rows'][] = $event;
    }

    $snapshot = [];
    foreach ($byMerge as $key => $bucket) {
        $rows = is_array($bucket['rows']) ? $bucket['rows'] : [];
        $score = activity_index($rows, $now);
        $newEruptive = 0;
        foreach ($rows as $row) {
            if (!empty($row['is_new_eruptive'])) {
                $newEruptive += 1;
            }
        }
        $snapshot[$key] = [
            'volcano' => (string) ($bucket['volcano'] ?? 'Unknown'),
            'country' => (string) ($bucket['country'] ?? 'Unknown'),
            'point' => [
                'ts' => $snapshotTs,
                'time_utc' => gmdate('c', $snapshotTs),
                'score' => $score,
                'reports' => count($rows),
                'new_eruptive' => $newEruptive,
            ],
        ];
    }

    return $snapshot;
}

$cachePath = $appConfig['data_dir'] . '/volcanoes_latest.json';
$historyPath = $appConfig['data_dir'] . '/volcanoes_history.json';
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

$feedUrl = $feedConfig['volcanoes']['url'] ?? '';
$provider = $feedConfig['volcanoes']['provider'] ?? 'Unknown';
$externalRaw = fetch_external_text($feedUrl, (int) $appConfig['http_timeout_seconds']);

if (!is_string($externalRaw) || $externalRaw === '') {
    write_log($appConfig['logs_dir'], "Volcanoes feed fetch failed: {$feedUrl}");

    if (is_array($cachedPayload)) {
        $cachedPayload['from_cache'] = true;
        $cachedPayload['stale_cache'] = true;
        json_response(200, $cachedPayload);
    }

    json_response(502, [
        'ok' => false,
        'error' => 'Unable to load volcano feed',
    ]);
}

libxml_use_internal_errors(true);
if (!function_exists('simplexml_load_string')) {
    write_log($appConfig['logs_dir'], 'SimpleXML extension is not available for volcanoes feed parsing');

    if (is_array($cachedPayload)) {
        $cachedPayload['from_cache'] = true;
        $cachedPayload['stale_cache'] = true;
        json_response(200, $cachedPayload);
    }

    json_response(502, [
        'ok' => false,
        'error' => 'Volcano feed parser unavailable',
    ]);
}

$xml = simplexml_load_string($externalRaw, SimpleXMLElement::class, LIBXML_NOCDATA);
if (!$xml || !isset($xml->channel)) {
    write_log($appConfig['logs_dir'], "Volcanoes feed parse failed: {$feedUrl}");

    if (is_array($cachedPayload)) {
        $cachedPayload['from_cache'] = true;
        $cachedPayload['stale_cache'] = true;
        json_response(200, $cachedPayload);
    }

    json_response(502, [
        'ok' => false,
        'error' => 'Invalid volcano feed format',
    ]);
}

$maxEvents = (int) $appConfig['max_events'];
$events = [];
$uniqueVolcanoes = [];
$newEruptiveCount = 0;
$channelPubDate = isset($xml->channel->pubDate) ? strtotime((string) $xml->channel->pubDate) : 0;

foreach ($xml->channel->item as $item) {
    if (count($events) >= $maxEvents) {
        break;
    }

    $title = trim((string) ($item->title ?? ''));
    $descriptionRaw = trim((string) ($item->description ?? ''));
    $description = trim(html_entity_decode(strip_tags($descriptionRaw), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $description = preg_replace('/\s+/', ' ', $description ?? '') ?? '';
    $summary = $description;
    $eventTs = isset($item->pubDate) ? strtotime((string) $item->pubDate) : 0;
    $eventIso = $eventTs > 0 ? gmdate('c', $eventTs) : null;

    $volcanoName = $title;
    $country = null;
    if (preg_match('/^(.*?)\s*\(([^)]+)\)/', $title, $matches)) {
        $volcanoName = trim($matches[1]);
        $country = trim($matches[2]);
    }

    $isNewEruptive = stripos($title, 'new eruptive activity') !== false;
    if ($isNewEruptive) {
        $newEruptiveCount += 1;
    }

    $uniqKey = strtolower($volcanoName . '|' . (string) $country);
    $uniqueVolcanoes[$uniqKey] = true;

    $events[] = [
        'id' => (string) ($item->guid ?? ''),
        'title' => $title,
        'volcano' => $volcanoName,
        'country' => $country,
        'is_new_eruptive' => $isNewEruptive,
        'event_time_utc' => $eventIso,
        'summary' => $summary,
        'source_url' => (string) ($item->link ?? ''),
    ];
}

$snapshotTs = (int) (floor($now / 1800) * 1800);
$historyPayload = read_json_file($historyPath);
$historyByMerge = is_array($historyPayload) && isset($historyPayload['history_by_volcano']) && is_array($historyPayload['history_by_volcano'])
    ? $historyPayload['history_by_volcano']
    : [];

$liveSnapshot = build_live_snapshot($events, $snapshotTs, $now);
foreach ($liveSnapshot as $merge => $entry) {
    if (!isset($historyByMerge[$merge]) || !is_array($historyByMerge[$merge])) {
        $historyByMerge[$merge] = [];
    }
    $points = $historyByMerge[$merge];
    $point = $entry['point'];

    $lastIdx = count($points) - 1;
    if ($lastIdx >= 0 && isset($points[$lastIdx]['ts']) && (int) $points[$lastIdx]['ts'] === (int) $point['ts']) {
        $points[$lastIdx] = $point;
    } else {
        $points[] = $point;
    }

    $minTs = $now - (180 * 86400);
    $points = array_values(array_filter($points, static function ($p) use ($minTs): bool {
        if (!is_array($p)) return false;
        $ts = isset($p['ts']) ? (int) $p['ts'] : 0;
        return $ts >= $minTs;
    }));
    if (count($points) > 160) {
        $points = array_slice($points, -160);
    }
    $historyByMerge[$merge] = $points;
}

$historyOut = [];
foreach ($historyByMerge as $merge => $points) {
    if (!is_string($merge) || !is_array($points) || count($points) === 0) {
        continue;
    }
    $historyOut[$merge] = array_values($points);
}

$historyToStore = [
    'ok' => true,
    'generated_at_ts' => $now,
    'generated_at' => gmdate('c', $now),
    'history_by_volcano' => $historyOut,
];
if (!write_json_file($historyPath, $historyToStore)) {
    write_log($appConfig['logs_dir'], 'Failed writing volcanoes history JSON');
}

$payload = [
    'ok' => true,
    'provider' => $provider,
    'generated_at_ts' => $now,
    'generated_at' => gmdate('c', $now),
    'feed_updated_at' => $channelPubDate > 0 ? gmdate('c', $channelPubDate) : null,
    'reports_count' => count($events),
    'volcanoes_count' => count($uniqueVolcanoes),
    'new_eruptive_count' => $newEruptiveCount,
    'events' => $events,
    'history_by_volcano' => $historyOut,
    'from_cache' => false,
];

if (!write_json_file($cachePath, $payload)) {
    write_log($appConfig['logs_dir'], 'Failed writing volcanoes cache JSON');
}

json_response(200, $payload);
