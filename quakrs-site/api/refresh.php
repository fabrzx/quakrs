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

$baseUrlRaw = trim((string) ($appConfig['public_base_url'] ?? 'https://www.quakrs.com'));
$parsedBase = parse_url($baseUrlRaw);
$baseScheme = is_array($parsedBase) ? strtolower((string) ($parsedBase['scheme'] ?? '')) : '';
$baseHost = is_array($parsedBase) ? strtolower((string) ($parsedBase['host'] ?? '')) : '';
$baseUrl = ($baseScheme === 'https' || $baseScheme === 'http') && $baseHost !== ''
    ? rtrim($baseUrlRaw, '/')
    : 'https://www.quakrs.com';

$targets = ['earthquakes', 'aftershocks', 'volcanoes', 'volcano-catalog', 'tremors', 'tsunami', 'space-weather', 'earthquake-cams', 'weather-cams', 'space-weather-cams', 'tsunami-cams', 'volcano-cams', 'hotspots', 'bulletins'];
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
