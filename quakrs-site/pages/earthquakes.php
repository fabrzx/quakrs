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
    <p class="eyebrow">Live Feed</p>
    <h1>Global Earthquake Feed.</h1>
    <p class="sub">USGS-style live stream: newest events first on map and feed list.</p>
  </div>
</main>

<section class="panel panel-main">
  <article class="card map-card">
    <div class="feed-head">
      <div class="map-head-left">
        <h3>Global Seismic Map</h3>
        <button id="global-theme-toggle" class="map-mini-toggle" type="button" aria-pressed="false" aria-label="Attiva modalita notturna" title="Attiva modalita notturna">☀</button>
      </div>
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

<?php require __DIR__ . '/../partials/footer.php'; ?>
