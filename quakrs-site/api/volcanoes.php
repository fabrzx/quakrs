<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$cachePath = $appConfig['data_dir'] . '/volcanoes_latest.json';
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
    $summary = substr($description, 0, 240);
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
    'from_cache' => false,
];

if (!write_json_file($cachePath, $payload)) {
    write_log($appConfig['logs_dir'], 'Failed writing volcanoes cache JSON');
}

json_response(200, $payload);
