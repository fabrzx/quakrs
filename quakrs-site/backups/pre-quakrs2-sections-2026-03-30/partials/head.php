<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/i18n.php';
qk_boot_i18n();

$pageTitle = $pageTitle ?? 'Quakrs.com - Live Hazard Monitoring';
$pageDescription = $pageDescription ?? 'Quakrs.com live monitoring for earthquakes, volcanoes, tsunami alerts and space weather.';
$faviconVersion = '3';
$canonicalBase = 'https://quakrs.com';
$scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '/');
$scriptFile = basename($scriptName);
$scriptDir = str_replace('\\', '/', dirname($scriptName));
if ($scriptFile === 'index.php') {
    $canonicalPath = ($scriptDir === '' || $scriptDir === '.' || $scriptDir === '/')
        ? '/'
        : rtrim($scriptDir, '/') . '/';
} else {
    $canonicalPath = $scriptName !== '' ? $scriptName : '/';
}
if ($scriptFile === 'event.php') {
    $queryString = (string) ($_SERVER['QUERY_STRING'] ?? '');
    if ($queryString !== '') {
        $canonicalPath .= '?' . $queryString;
    }
}
$canonicalUrl = $canonicalBase . $canonicalPath;
$mainCssVersion = @filemtime(__DIR__ . '/../assets/css/styles.css') ?: time();

$bodyTokens = [];
if (!empty($currentPage) && is_string($currentPage)) {
    $pageToken = preg_replace('/[^a-z0-9_-]+/i', '-', $currentPage);
    $pageToken = trim((string) $pageToken, '-');
    if ($pageToken !== '') {
        $bodyTokens[] = $pageToken . '-page';
    }
}
$bodyTokens[] = 'site-2026';
$bodyTokens[] = 'site-acid-balanced';
if (!empty($bodyClass) && is_string($bodyClass)) {
    foreach (preg_split('/\s+/', trim($bodyClass)) ?: [] as $token) {
        if ($token !== '') {
            $bodyTokens[] = $token;
        }
    }
}
$bodyTokens = array_values(array_unique($bodyTokens));
$bodyClassAttr = implode(' ', $bodyTokens);
?>
<!doctype html>
<html lang="<?= htmlspecialchars(qk_locale(), ENT_QUOTES, 'UTF-8'); ?>">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>" />
    <meta name="theme-color" content="#050816" />
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>" />
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" href="/assets/icons/favicon.svg?v=<?= urlencode($faviconVersion); ?>" type="image/svg+xml" />
    <link rel="shortcut icon" href="/assets/icons/favicon.svg?v=<?= urlencode($faviconVersion); ?>" type="image/svg+xml" />
    <link rel="apple-touch-icon" href="/assets/icons/favicon.svg?v=<?= urlencode($faviconVersion); ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@500;700&family=IBM+Plex+Sans:wght@400;500;700&family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap"
      rel="stylesheet"
    />
    <?php if (!empty($includeLeaflet)): ?>
      <link
        rel="stylesheet"
        href="/assets/vendor/leaflet/leaflet.css"
        crossorigin=""
      />
      <script src="/assets/vendor/leaflet/leaflet.js" crossorigin=""></script>
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/styles.css?v=<?= urlencode((string) $mainCssVersion); ?>" />
  </head>
  <body class="<?= htmlspecialchars($bodyClassAttr, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="ambient ambient-a"></div>
    <div class="ambient ambient-b"></div>
