<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../lib/editorial_engine.php';

$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';
$slug = trim((string) ($_GET['slug'] ?? ''));
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int) $_GET['limit'] : 30;
$limit = max(1, min(120, $limit));

if ($forceRefresh) {
    require_refresh_token($appConfig);
    $bundle = qk_editorial_generate($appConfig, 120);
    $bundle['from_cache'] = false;
} else {
    $bundle = qk_editorial_load_bundle($appConfig);
    if (!is_array($bundle['articles'] ?? null) || $bundle['articles'] === []) {
        $bundle = qk_editorial_generate($appConfig, 120);
        $bundle['from_cache'] = false;
    } else {
        $bundle['from_cache'] = true;
    }
}

$articles = array_values(array_filter($bundle['articles'] ?? [], static function ($article): bool {
    return is_array($article) && (string) ($article['status'] ?? '') === 'published';
}));

if ($slug !== '') {
    $match = null;
    foreach ($articles as $article) {
        if ((string) ($article['slug'] ?? '') === $slug) {
            $match = $article;
            break;
        }
    }

    if ($match === null) {
        json_response(404, [
            'ok' => false,
            'error' => 'Article not found',
            'slug' => $slug,
        ]);
    }

    json_response(200, [
        'ok' => true,
        'generated_at_ts' => (int) ($bundle['generated_at_ts'] ?? 0),
        'generated_at' => (string) ($bundle['generated_at'] ?? ''),
        'from_cache' => (bool) ($bundle['from_cache'] ?? false),
        'article' => $match,
    ]);
}

json_response(200, [
    'ok' => true,
    'generated_at_ts' => (int) ($bundle['generated_at_ts'] ?? 0),
    'generated_at' => (string) ($bundle['generated_at'] ?? ''),
    'from_cache' => (bool) ($bundle['from_cache'] ?? false),
    'articles_count' => count($articles),
    'articles' => array_slice($articles, 0, $limit),
]);
