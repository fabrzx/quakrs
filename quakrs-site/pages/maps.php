<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Maps';
$pageDescription = 'Priority earthquake dashboard with map, charts and strongest events.';
$currentPage = 'maps';
$includeLeaflet = true;

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Earthquakes Dashboard</p>
    <h1>Global Earthquake Monitoring.</h1>
    <p class="sub">Operational view with map, charts and event prioritization.</p>
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
    <h3>Priority Events</h3>
    <ul id="events-list" class="events-list live-feed-scroll" data-order="priority">
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

<template id="bar-template">
  <div class="bar-row">
    <div class="bar-label"></div>
    <div class="bar-track">
      <div class="bar-fill"></div>
    </div>
    <div class="bar-value"></div>
  </div>
</template>

<?php require __DIR__ . '/../partials/footer.php'; ?>
