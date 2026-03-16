<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';
if (!$forceRefresh) {
    json_response(400, [
        'ok' => false,
        'error' => 'Missing required query parameter: force_refresh=1',
    ]);
}

$requestToken = require_refresh_token($appConfig);

$host = isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== ''
    ? $_SERVER['HTTP_HOST']
    : 'www.quakrs.com';
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$scheme = $isHttps ? 'https' : 'http';
$baseUrl = $scheme . '://' . $host;

$targets = ['earthquakes', 'aftershocks', 'volcanoes', 'volcano-catalog', 'tremors', 'tsunami', 'space-weather', 'volcano-cams', 'hotspots', 'bulletins'];
$results = [];
$okCount = 0;

foreach ($targets as $target) {
    $url = $baseUrl . '/api/' . $target . '.php?force_refresh=1&token=' . rawurlencode($requestToken);
    $payload = fetch_external_json($url, max(10, (int) $appConfig['http_timeout_seconds']));

    if (!is_array($payload) || !isset($payload['ok']) || $payload['ok'] !== true) {
        $results[] = [
            'target' => $target,
            'status' => 'error',
            'url' => $url,
        ];
        continue;
    }

    $okCount += 1;
    $results[] = [
        'target' => $target,
        'status' => 'ok',
        'url' => $url,
        'generated_at' => $payload['generated_at'] ?? null,
        'from_cache' => (bool) ($payload['from_cache'] ?? false),
    ];
}

$statusCode = $okCount === count($targets) ? 200 : ($okCount > 0 ? 207 : 502);
json_response($statusCode, [
    'ok' => $okCount > 0,
    'updated' => $okCount,
    'total' => count($targets),
    'results' => $results,
    'generated_at' => gmdate('c'),
]);
