#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Export Quakrs editorial JSON bundle to Grav blog content structure.
 *
 * Default source: quakrs-site/data/editorial_articles_latest.json
 * Default output: quakrs-site/tmp/grav-editoriale/user/pages
 *
 * Usage:
 *   php scripts/export-editoriale-to-grav.php
 *   php scripts/export-editoriale-to-grav.php --input=/path/editorial_articles_latest.json --output=/tmp/grav/user/pages
 */

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    fwrite(STDERR, "Cannot resolve project root.\n");
    exit(1);
}

$options = getopt('', ['input::', 'output::', 'site-url::']);
$inputPath = isset($options['input'])
    ? (string) $options['input']
    : $root . '/data/editorial_articles_latest.json';
$outputPagesPath = isset($options['output'])
    ? rtrim((string) $options['output'], '/')
    : $root . '/tmp/grav-editoriale/user/pages';
$siteUrl = isset($options['site-url']) ? rtrim((string) $options['site-url'], '/') : 'https://quakrs.com';

if (!is_file($inputPath)) {
    fwrite(STDERR, "Input JSON not found: {$inputPath}\n");
    exit(1);
}

$json = file_get_contents($inputPath);
if (!is_string($json) || $json === '') {
    fwrite(STDERR, "Unable to read input JSON: {$inputPath}\n");
    exit(1);
}

$data = json_decode($json, true);
if (!is_array($data)) {
    fwrite(STDERR, "Invalid JSON input: {$inputPath}\n");
    exit(1);
}

$articles = $data['articles'] ?? null;
if (!is_array($articles)) {
    fwrite(STDERR, "Input JSON has no articles array.\n");
    exit(1);
}

$published = array_values(array_filter($articles, static function ($article): bool {
    return is_array($article) && (string) ($article['status'] ?? '') === 'published';
}));

if ($published === []) {
    fwrite(STDERR, "No published articles found.\n");
    exit(1);
}

$blogRoot = $outputPagesPath . '/01.home';
if (!is_dir($blogRoot) && !mkdir($blogRoot, 0775, true) && !is_dir($blogRoot)) {
    fwrite(STDERR, "Cannot create output directory: {$blogRoot}\n");
    exit(1);
}

$blogFrontmatter = [
    'title' => 'Blog',
    'content' => [
        'items' => '@self.children',
        'order' => [
            'by' => 'date',
            'dir' => 'desc',
        ],
        'limit' => 20,
        'pagination' => true,
    ],
    'pagination' => true,
    'route' => '/',
    'menu' => 'Blog',
    'visible' => true,
    'process' => [
        'markdown' => true,
        'twig' => false,
    ],
    'taxonomy' => [
        'category' => ['blog'],
    ],
];

$blogMarkdown = "---\n" . yaml_encode($blogFrontmatter) . "---\n\n";
$blogMarkdown .= "Il blog di Quakrs.\n";
file_put_contents($blogRoot . '/blog.md', $blogMarkdown);

$count = 0;
foreach ($published as $article) {
    $slug = sanitize_slug((string) ($article['slug'] ?? ''));
    if ($slug === '') {
        continue;
    }

    $articleDir = $blogRoot . '/' . $slug;
    if (!is_dir($articleDir) && !mkdir($articleDir, 0775, true) && !is_dir($articleDir)) {
        fwrite(STDERR, "Cannot create article dir: {$articleDir}\n");
        exit(1);
    }

    $title = normalize_text((string) ($article['title'] ?? 'Blog Quakrs'));
    $excerpt = normalize_text((string) ($article['excerpt'] ?? ''));
    $publishedAt = normalize_iso8601((string) ($article['published_at'] ?? ''));
    $updatedAt = normalize_iso8601((string) ($article['updated_at'] ?? ''));
    $type = normalize_text((string) ($article['type'] ?? 'analisi'));

    $event = is_array($article['event'] ?? null) ? $article['event'] : [];
    $eventId = normalize_text((string) ($event['id'] ?? ''));
    $eventPlace = normalize_text((string) ($event['place'] ?? ''));
    $eventMag = format_number($event['magnitude'] ?? null, 1);
    $eventDepth = format_number($event['depth_km'] ?? null, 1);
    $eventLat = format_number($event['latitude'] ?? null, 4);
    $eventLon = format_number($event['longitude'] ?? null, 4);
    $eventSource = normalize_text((string) ($event['source_provider'] ?? ''));
    $eventSourceUrl = normalize_text((string) ($event['source_url'] ?? ''));

    $oldUrl = normalize_text((string) ($article['url'] ?? '/blog/' . $slug));
    $newPath = '/blog/' . $slug;

    $frontmatter = [
        'title' => $title,
        'date' => $publishedAt,
        'publish_date' => $publishedAt,
        'route' => '/' . $slug,
        'metadata' => [
            'description' => $excerpt,
            'canonical' => $siteUrl . $newPath,
            'robots' => 'index,follow',
        ],
        'taxonomy' => [
            'category' => ['blog'],
            'tag' => [$type, 'sisma'],
        ],
        'quake' => [
            'id' => $eventId,
            'place' => $eventPlace,
            'magnitude' => $eventMag,
            'depth_km' => $eventDepth,
            'latitude' => $eventLat,
            'longitude' => $eventLon,
            'source_provider' => $eventSource,
            'source_url' => $eventSourceUrl,
            'legacy_url' => $oldUrl,
        ],
        'legacy_url' => $oldUrl,
        'updated_at' => $updatedAt,
        'visible' => true,
    ];

    $body = [];
    $body[] = "---\n" . yaml_encode($frontmatter) . "---\n";

    if ($excerpt !== '') {
        $body[] = $excerpt . "\n";
    }

    $sections = is_array($article['sections'] ?? null) ? $article['sections'] : [];
    foreach ($sections as $section) {
        if (!is_array($section)) {
            continue;
        }
        $heading = normalize_text((string) ($section['heading'] ?? ''));
        $sectionBody = normalize_text((string) ($section['body'] ?? ''));
        if ($heading !== '') {
            $body[] = '## ' . escape_markdown_heading($heading) . "\n";
        }
        if ($sectionBody !== '') {
            $body[] = $sectionBody . "\n";
        }
    }

    if ($eventSourceUrl !== '') {
        $body[] = "Fonte primaria: [{$eventSource}]({$eventSourceUrl})\n";
    }

    file_put_contents($articleDir . '/item.md', implode("\n", $body));
    $count++;
}

$redirectsPath = $outputPagesPath . '/_quakrs_editoriale_redirects.csv';
$redirectLines = ["legacy_url,new_url"];
foreach ($published as $article) {
    if (!is_array($article)) {
        continue;
    }
    $slug = sanitize_slug((string) ($article['slug'] ?? ''));
    if ($slug === '') {
        continue;
    }
    $legacy = normalize_text((string) ($article['url'] ?? '/blog/' . $slug));
    $redirectLines[] = $legacy . ',/blog/' . $slug;
}
file_put_contents($redirectsPath, implode("\n", $redirectLines) . "\n");

fwrite(STDOUT, "Exported {$count} Grav items to {$blogRoot}\n");
fwrite(STDOUT, "Redirect map: {$redirectsPath}\n");

function sanitize_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9\-]+/', '-', $value);
    $value = preg_replace('/-+/', '-', (string) $value);
    return trim((string) $value, '-');
}

function normalize_text(string $value): string
{
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = trim($value);
    return $value;
}

function normalize_iso8601(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return gmdate(DATE_ATOM);
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return gmdate(DATE_ATOM);
    }
    return gmdate(DATE_ATOM, $ts);
}

function format_number($value, int $decimals): string
{
    if (!is_numeric($value)) {
        return '';
    }
    return number_format((float) $value, $decimals, '.', '');
}

function escape_markdown_heading(string $value): string
{
    return ltrim($value, "# \t");
}

function yaml_encode(array $data, int $indent = 0): string
{
    $lines = [];
    foreach ($data as $key => $value) {
        $prefix = str_repeat('  ', $indent) . $key . ':';

        if (is_array($value)) {
            if ($value === []) {
                $lines[] = $prefix . ' []';
                continue;
            }

            if (array_is_list_polyfill($value)) {
                $lines[] = $prefix;
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $lines[] = str_repeat('  ', $indent + 1) . '-';
                        $lines[] = yaml_encode($item, $indent + 2);
                    } else {
                        $lines[] = str_repeat('  ', $indent + 1) . '- ' . yaml_scalar($item);
                    }
                }
                continue;
            }

            $lines[] = $prefix;
            $lines[] = yaml_encode($value, $indent + 1);
            continue;
        }

        $lines[] = $prefix . ' ' . yaml_scalar($value);
    }

    return implode("\n", $lines) . "\n";
}

function yaml_scalar($value): string
{
    if ($value === null) {
        return 'null';
    }
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    $text = trim((string) $value);
    if ($text === '') {
        return "''";
    }

    if (preg_match('/^[a-zA-Z0-9_\-\.\/:]+$/', $text)) {
        return $text;
    }

    $escaped = str_replace('"', '\\"', $text);
    return '"' . $escaped . '"';
}

function array_is_list_polyfill(array $array): bool
{
    if (function_exists('array_is_list')) {
        return array_is_list($array);
    }

    $i = 0;
    foreach ($array as $k => $_v) {
        if ($k !== $i) {
            return false;
        }
        $i++;
    }
    return true;
}
