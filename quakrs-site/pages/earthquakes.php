<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Earthquakes';
$pageDescription = 'Live earthquake feed with global map and chronological event stream.';
$currentPage = 'earthquakes';
$includeLeaflet = true;

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.earthquakes.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.earthquakes.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.earthquakes.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

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
      <button class="map-filter-btn band-m1-2" data-band="m1-2" type="button" aria-pressed="false">M1-2</button>
      <button class="map-filter-btn band-m3" data-band="m3" type="button" aria-pressed="false">M3</button>
      <button class="map-filter-btn band-m4" data-band="m4" type="button" aria-pressed="false">M4</button>
      <button class="map-filter-btn band-m5" data-band="m5" type="button" aria-pressed="false">M5</button>
      <button class="map-filter-btn band-m6" data-band="m6" type="button" aria-pressed="false">M6</button>
      <button class="map-filter-btn band-m7p" data-band="m7p" type="button" aria-pressed="false">M7+</button>
    </div>
  </article>

  <article class="card side-card">
    <h3>Live Feed (Newest First)</h3>
    <ul id="events-list" class="events-list live-feed-scroll" data-order="chronological">
      <li class="event-item">Loading latest events...</li>
    </ul>
  </article>
</section>

<section class="panel panel-charts maps-charts">
  <article class="card maps-chart-card maps-chart-wide">
    <div class="feed-head">
      <h3>Magnitude Distribution</h3>
    </div>
    <div id="mag-chart" class="bars bars-vertical bars-magnitude"></div>
  </article>
  <article class="card maps-chart-card maps-chart-wide">
    <div class="feed-head">
      <h3>Activity by Hour (UTC)</h3>
      <p class="feed-meta">Last 24 hours</p>
    </div>
    <div id="hourly-chart" class="bars bars-vertical bars-hourly-vertical"></div>
  </article>
  <article class="card maps-chart-card maps-chart-regions">
    <div class="feed-head">
      <h3>Top Regions</h3>
      <p class="feed-meta">Most active places now</p>
    </div>
    <ul id="regions-list" class="regions-list">
      <li>No data loaded yet.</li>
    </ul>
  </article>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
