<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Priority Map';
$pageDescription = 'Priority earthquake map with layered views and operational feed.';
$currentPage = 'maps';
$includeLeaflet = true;
$isFullscreen = isset($_GET['fullscreen']) && (string) $_GET['fullscreen'] === '1';
if ($isFullscreen) {
  $bodyClass = 'maps-fullscreen-mode';
}

require __DIR__ . '/../partials/head.php';
if (!$isFullscreen) {
  require __DIR__ . '/../partials/topbar.php';
}
?>

<?php if ($isFullscreen): ?>
  <main class="maps-fullscreen-shell" aria-label="Fullscreen global seismic map">
    <header class="maps-fullscreen-topbar">
      <a class="maps-fullscreen-brand" href="/" aria-label="Quakrs home">
        <img class="maps-fullscreen-logo" src="/assets/icons/favicon.svg" alt="" />
        <span>Quakrs</span>
      </a>
      <div class="maps-fullscreen-actions">
        <a class="btn btn-ghost" href="/maps.php">Exit full map</a>
      </div>
    </header>

    <section class="maps-fullscreen-main">
      <aside class="card side-card maps-fullscreen-feed">
        <div class="maps-feed-controls" aria-label="Feed controls">
          <div id="maps-feed-format-dropdown" class="maps-feed-dropdown" data-dropdown="format">
            <button id="maps-feed-format-trigger" class="maps-feed-dropdown-trigger" type="button" aria-haspopup="listbox" aria-expanded="false">
              <span class="maps-feed-dropdown-label">Format</span>
              <span id="maps-feed-format-value" class="maps-feed-dropdown-value">Magnitude</span>
            </button>
            <div class="maps-feed-dropdown-menu" role="listbox" aria-label="Format">
              <button class="maps-feed-option is-selected" type="button" role="option" data-value="magnitude" aria-selected="true">Magnitude</button>
              <button class="maps-feed-option" type="button" role="option" data-value="dyfi" aria-selected="false">DYFI</button>
              <button class="maps-feed-option" type="button" role="option" data-value="shakemap" aria-selected="false">ShakeMap</button>
              <button class="maps-feed-option" type="button" role="option" data-value="pager" aria-selected="false">PAGER</button>
            </div>
          </div>
          <div id="maps-feed-sort-dropdown" class="maps-feed-dropdown" data-dropdown="sort">
            <button id="maps-feed-sort-trigger" class="maps-feed-dropdown-trigger" type="button" aria-haspopup="listbox" aria-expanded="false">
              <span class="maps-feed-dropdown-label">Sort</span>
              <span id="maps-feed-sort-value" class="maps-feed-dropdown-value">Newest First</span>
            </button>
            <div class="maps-feed-dropdown-menu" role="listbox" aria-label="Sort">
              <button class="maps-feed-option is-selected" type="button" role="option" data-value="newest" aria-selected="true">Newest First</button>
              <button class="maps-feed-option" type="button" role="option" data-value="oldest" aria-selected="false">Oldest First</button>
              <button class="maps-feed-option" type="button" role="option" data-value="largest" aria-selected="false">Largest Magnitude First</button>
              <button class="maps-feed-option" type="button" role="option" data-value="smallest" aria-selected="false">Smallest Magnitude First</button>
            </div>
          </div>
        </div>
        <label class="home-map-list-filter maps-feed-filter" for="maps-viewport-only">
          <input id="maps-viewport-only" type="checkbox" />
          <span>Only List Earthquakes Shown on Map</span>
        </label>
        <ul id="events-list" class="events-list live-feed-scroll" data-order="chronological">
          <li class="event-item">Loading latest events...</li>
        </ul>
      </aside>

      <article class="card map-card maps-fullscreen-map">
        <div class="map-wrap">
          <div id="world-map-leaflet" class="world-map-leaflet" aria-label="Global seismic map"></div>
        </div>
        <div class="map-legend">
          <button class="map-filter-btn band-m1-2" data-band="m1-2" type="button" aria-pressed="false">M1-2</button>
          <button class="map-filter-btn band-m3" data-band="m3" type="button" aria-pressed="false">M3</button>
          <button class="map-filter-btn band-m4" data-band="m4" type="button" aria-pressed="false">M4</button>
          <button class="map-filter-btn band-m5" data-band="m5" type="button" aria-pressed="false">M5</button>
          <button class="map-filter-btn band-m6" data-band="m6" type="button" aria-pressed="false">M6</button>
          <button class="map-filter-btn band-m7p" data-band="m7p" type="button" aria-pressed="false">M7+</button>
        </div>
      </article>
    </section>
  </main>
<?php else: ?>
  <main class="hero compact-hero">
    <div>
      <p class="eyebrow"><?= htmlspecialchars(qk_t('page.maps.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
      <h1><?= htmlspecialchars(qk_t('page.maps.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
      <p class="sub"><?= htmlspecialchars(qk_t('page.maps.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
  </main>

  <section id="dashboard" class="panel panel-kpi">
    <article class="card kpi-card">
      <p class="kpi-label">Events (24h)</p>
      <p id="kpi-total" class="kpi-value">--</p>
      <p class="kpi-note">Global feed volume</p>
    </article>
    <article class="card kpi-card">
      <p class="kpi-label">Strongest</p>
      <p id="kpi-strongest" class="kpi-value">--</p>
      <p id="kpi-strongest-place" class="kpi-note">No data</p>
    </article>
    <article class="card kpi-card">
      <p class="kpi-label">M5+ Events</p>
      <p id="kpi-significant" class="kpi-value">--</p>
      <p class="kpi-note">High impact candidates</p>
    </article>
    <article class="card kpi-card">
      <p class="kpi-label">Last Update</p>
      <p id="kpi-updated" class="kpi-value">--</p>
    </article>
  </section>

  <section class="panel panel-main earthquakes-main-layout">
    <article class="card map-card">
      <div class="feed-head">
        <h3>Global Seismic Map</h3>
        <p id="feed-meta" class="feed-meta">Loading sources...</p>
      </div>
      <div class="map-wrap">
        <div id="world-map-leaflet" class="world-map-leaflet" aria-label="Global seismic map"></div>
      </div>
      <div class="map-legend">
        <button class="map-filter-btn band-m4" data-band="m45p" type="button" aria-pressed="false">M4.5+</button>
        <button class="map-filter-btn band-m4" data-band="m45" type="button" aria-pressed="false">M4.5-4.9</button>
        <button class="map-filter-btn band-m5" data-band="m5" type="button" aria-pressed="false">M5</button>
        <button class="map-filter-btn band-m6" data-band="m6" type="button" aria-pressed="false">M6</button>
        <button class="map-filter-btn band-m7p" data-band="m7p" type="button" aria-pressed="false">M7+</button>
      </div>
    </article>
    <article class="card side-card">
      <h3>Priority Events</h3>
      <ul id="events-list" class="events-list live-feed-scroll" data-order="priority">
        <li class="event-item">Loading latest events...</li>
      </ul>
    </article>
  </section>

<?php endif; ?>

<template id="bar-template">
  <div class="bar-row">
    <div class="bar-label"></div>
    <div class="bar-track">
      <div class="bar-fill"></div>
    </div>
    <div class="bar-value"></div>
  </div>
</template>

<?php if (!$isFullscreen): ?>
  <?php require __DIR__ . '/../partials/footer.php'; ?>
<?php else: ?>
  <?php
    $bootstrapData = [
      'earthquakes' => null,
      'volcanoes' => null,
      'tremors' => null,
      'tsunami' => null,
      'space-weather' => null,
    ];
    $bootstrapFiles = [
      'earthquakes' => __DIR__ . '/../data/earthquakes_latest.json',
      'volcanoes' => __DIR__ . '/../data/volcanoes_latest.json',
      'tremors' => __DIR__ . '/../data/tremors_latest.json',
      'tsunami' => __DIR__ . '/../data/tsunami_latest.json',
      'space-weather' => __DIR__ . '/../data/space_weather_latest.json',
    ];
    foreach ($bootstrapFiles as $key => $path) {
      if (!is_file($path)) {
        continue;
      }
      $raw = @file_get_contents($path);
      if (!is_string($raw) || $raw === '') {
        continue;
      }
      $decoded = json_decode($raw, true);
      if (is_array($decoded)) {
        $bootstrapData[$key] = $decoded;
      }
    }
    $mainJsVersion = @filemtime(__DIR__ . '/../assets/js/main.js') ?: time();
  ?>
  <script>
    window.__QUAKRS_BOOTSTRAP = <?= json_encode($bootstrapData, JSON_UNESCAPED_SLASHES); ?>;
  </script>
  <script src="/assets/js/main.js?v=<?= urlencode((string) $mainJsVersion); ?>"></script>
  </body>
  </html>
<?php endif; ?>
