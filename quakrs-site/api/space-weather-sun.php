<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$cacheBodyPath = $appConfig['data_dir'] . '/space_sun_latest.bin';
$cacheMetaPath = $appConfig['data_dir'] . '/space_sun_latest.json';
$cacheTtl = 300;
$now = time();
$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';

$primaryUrl = (string) ($feedConfig['space_weather']['sun_image_url'] ?? '');
$fallbackUrls = [
    'https://services.swpc.noaa.gov/images/animations/suvi/primary/304/latest.png',
    'https://sdo.gsfc.nasa.gov/assets/img/latest/latest_512_0171.jpg',
];

$sources = array_values(array_filter(array_merge([$primaryUrl], $fallbackUrls), static fn(string $url): bool => $url !== ''));

$meta = read_json_file($cacheMetaPath);
$metaTs = is_array($meta) && isset($meta['generated_at_ts']) ? (int) $meta['generated_at_ts'] : 0;
$metaType = is_array($meta) && isset($meta['content_type']) ? (string) $meta['content_type'] : 'image/jpeg';
$cacheExists = file_exists($cacheBodyPath);
$cacheFresh = !$forceRefresh && $cacheExists && $metaTs > 0 && ($now - $metaTs) <= $cacheTtl;

if ($cacheFresh) {
    $cachedBody = file_get_contents($cacheBodyPath);
    if (is_string($cachedBody) && $cachedBody !== '') {
        header('Content-Type: ' . $metaType);
        header('Cache-Control: no-store');
        echo $cachedBody;
        exit;
    }
}

// Serve cached image immediately for instant UX, even when stale.
if (!$forceRefresh && $cacheExists) {
    $cachedBody = file_get_contents($cacheBodyPath);
    if (is_string($cachedBody) && $cachedBody !== '') {
        header('Content-Type: ' . $metaType);
        header('Cache-Control: no-store');
        echo $cachedBody;
        exit;
    }
}

foreach ($sources as $sourceUrl) {
    $body = fetch_external_text($sourceUrl, (int) $appConfig['http_timeout_seconds']);
    if (!is_string($body) || strlen($body) < 512) {
        continue;
    }

    $contentType = str_contains(strtolower($sourceUrl), '.png') ? 'image/png' : 'image/jpeg';

    @file_put_contents($cacheBodyPath, $body, LOCK_EX);
    write_json_file($cacheMetaPath, [
        'generated_at_ts' => $now,
        'generated_at' => gmdate('c', $now),
        'content_type' => $contentType,
        'source_url' => $sourceUrl,
    ]);

    header('Content-Type: ' . $contentType);
    header('Cache-Control: no-store');
    echo $body;
    exit;
}

if (file_exists($cacheBodyPath)) {
    $cachedBody = file_get_contents($cacheBodyPath);
    if (is_string($cachedBody) && $cachedBody !== '') {
        header('Content-Type: ' . $metaType);
        header('Cache-Control: no-store');
        echo $cachedBody;
        exit;
    }
}

http_response_code(502);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
echo 'Unable to load solar image.';
