<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

function bulletin_time_to_iso(mixed $raw): ?string
{
    if (!is_string($raw) || trim($raw) === '') {
        return null;
    }

    $ts = strtotime($raw);
    if (!is_int($ts) || $ts <= 0) {
        return null;
    }

    return gmdate('c', $ts);
}

function parse_bulletin_items(string $rawFeed, array $source, int $maxItems): array
{
    $items = [];
    if (!function_exists('simplexml_load_string')) {
        return $items;
    }

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($rawFeed, SimpleXMLElement::class, LIBXML_NOCDATA);
    if (!$xml) {
        return $items;
    }

    $sourceKey = (string) ($source['key'] ?? 'unknown');
    $sourceLabel = (string) ($source['label'] ?? 'Unknown source');
    $category = (string) ($source['category'] ?? 'General');

    $pushItem = static function (array $entry) use (&$items, $maxItems): void {
        if (count($items) >= $maxItems) {
            return;
        }
        $items[] = $entry;
    };

    if (isset($xml->channel) && isset($xml->channel->item)) {
        foreach ($xml->channel->item as $item) {
            if (count($items) >= $maxItems) {
                break;
            }

            $title = trim((string) ($item->title ?? 'Untitled bulletin'));
            $link = trim((string) ($item->link ?? ''));
            $descRaw = trim((string) ($item->description ?? ''));
            $summary = trim(html_entity_decode(strip_tags($descRaw), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $summary = preg_replace('/\s+/', ' ', $summary ?? '') ?? '';
            $summary = substr($summary, 0, 260);
            $publishedAt = bulletin_time_to_iso((string) ($item->pubDate ?? ''));

            $pushItem([
                'id' => $sourceKey . ':' . sha1($title . '|' . ($publishedAt ?? '') . '|' . $link),
                'title' => $title,
                'summary' => $summary,
                'published_at_utc' => $publishedAt,
                'source_bulletin' => $link,
                'source_provider' => $sourceLabel,
                'category' => $category,
            ]);
        }

        return $items;
    }

    if (isset($xml->entry)) {
        foreach ($xml->entry as $entry) {
            if (count($items) >= $maxItems) {
                break;
            }

            $title = trim((string) ($entry->title ?? 'Untitled bulletin'));
            $summaryRaw = trim((string) ($entry->summary ?? $entry->content ?? ''));
            $summary = trim(html_entity_decode(strip_tags($summaryRaw), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $summary = preg_replace('/\s+/', ' ', $summary ?? '') ?? '';
            $summary = substr($summary, 0, 260);

            $link = '';
            if (isset($entry->link)) {
                foreach ($entry->link as $atomLink) {
                    $href = trim((string) ($atomLink['href'] ?? ''));
                    if ($href !== '') {
                        $link = $href;
                        break;
                    }
                }
            }

            $publishedAt = bulletin_time_to_iso((string) ($entry->updated ?? $entry->published ?? ''));

            $pushItem([
                'id' => $sourceKey . ':' . sha1($title . '|' . ($publishedAt ?? '') . '|' . $link),
                'title' => $title,
                'summary' => $summary,
                'published_at_utc' => $publishedAt,
                'source_bulletin' => $link,
                'source_provider' => $sourceLabel,
                'category' => $category,
            ]);
        }
    }

    return $items;
}

$cachePath = $appConfig['data_dir'] . '/bulletins_latest.json';
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

$sources = $feedConfig['bulletins']['sources'] ?? [];
if (!is_array($sources) || count($sources) === 0) {
    json_response(500, [
        'ok' => false,
        'error' => 'Bulletin sources not configured',
    ]);
}

$bulletins = [];
$sourceStatus = [];
$maxPerSource = 20;

foreach ($sources as $source) {
    if (!is_array($source)) {
        continue;
    }

    $sourceKey = (string) ($source['key'] ?? 'unknown');
    $sourceLabel = (string) ($source['label'] ?? 'Unknown source');
    $url = (string) ($source['url'] ?? '');
    if ($url === '') {
        continue;
    }

    $raw = fetch_external_text($url, (int) $appConfig['http_timeout_seconds']);
    if (!is_string($raw) || $raw === '') {
        $sourceStatus[] = [
            'key' => $sourceKey,
            'label' => $sourceLabel,
            'status' => 'error',
            'url' => $url,
        ];
        write_log($appConfig['logs_dir'], "Bulletins feed fetch failed [{$sourceKey}]: {$url}");
        continue;
    }

    $items = parse_bulletin_items($raw, $source, $maxPerSource);
    if (count($items) === 0) {
        $sourceStatus[] = [
            'key' => $sourceKey,
            'label' => $sourceLabel,
            'status' => 'empty',
            'url' => $url,
        ];
        continue;
    }

    $sourceStatus[] = [
        'key' => $sourceKey,
        'label' => $sourceLabel,
        'status' => 'ok',
        'url' => $url,
        'items' => count($items),
    ];
    $bulletins = array_merge($bulletins, $items);
}

if (count($bulletins) === 0) {
    if (is_array($cachedPayload)) {
        $cachedPayload['from_cache'] = true;
        $cachedPayload['stale_cache'] = true;
        json_response(200, $cachedPayload);
    }

    json_response(502, [
        'ok' => false,
        'error' => 'Unable to load institutional bulletins',
        'sources' => $sourceStatus,
    ]);
}

usort($bulletins, static function (array $a, array $b): int {
    $aTs = isset($a['published_at_utc']) ? strtotime((string) $a['published_at_utc']) : 0;
    $bTs = isset($b['published_at_utc']) ? strtotime((string) $b['published_at_utc']) : 0;
    return $bTs <=> $aTs;
});

$bulletins = array_slice($bulletins, 0, 120);

$categories = [];
foreach ($bulletins as $bulletin) {
    $cat = trim((string) ($bulletin['category'] ?? 'General'));
    if ($cat === '') {
        $cat = 'General';
    }
    $categories[$cat] = ($categories[$cat] ?? 0) + 1;
}

$payload = [
    'ok' => true,
    'provider' => (string) ($feedConfig['bulletins']['provider'] ?? 'Institutional Bulletins'),
    'generated_at_ts' => $now,
    'generated_at' => gmdate('c', $now),
    'bulletins_count' => count($bulletins),
    'sources_count' => count($sourceStatus),
    'categories' => $categories,
    'source_status' => $sourceStatus,
    'bulletins' => $bulletins,
    'from_cache' => false,
];

if (!write_json_file($cachePath, $payload)) {
    write_log($appConfig['logs_dir'], 'Failed writing bulletins cache JSON');
}

json_response(200, $payload);
