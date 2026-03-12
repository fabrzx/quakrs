<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Global Seismic Platform';
$pageDescription = 'Real-time earthquakes, volcanoes, tsunami alerts, space weather and operational data views.';
$currentPage = 'home';
$includeLeaflet = true;
$bodyClass = 'home-page home-2026';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero home-v2-hero home-priority-hero">
  <div class="home-v2-hero-main">
    <p class="eyebrow">Global seismic intelligence</p>
    <h1>
      <span class="hero-line">Earthquakes,</span>
      <span class="hero-line">Volcanoes &amp; Multi-Hazard</span>
      <span class="hero-line">Live Monitors</span>
    </h1>
    <p class="sub">
      Live earthquakes, volcanoes, tsunami alerts, tremor and operational hazard data in one interface.
    </p>
    <div class="hero-actions">
      <a class="btn btn-primary" href="/earthquakes.php">Open monitors</a>
      <a class="btn btn-ghost" href="/data-archive.php">Browse archive</a>
    </div>
  </div>
  <aside class="hero-side home-v2-hero-side" aria-label="Live operational panel">
    <p id="home-kpi-source" class="kpi-note home-v2-source-note">Source: loading...</p>
  </aside>
</main>

<section id="launch" class="launch home-v2-launch home-priority-launch">
  <div class="home-v2-bento">
    <section class="home-priority-top" aria-label="Priority board and significant events">
      <article class="card home-priority-board" aria-live="polite">
        <div class="snapshot-head">
          <h3>Priority board</h3>
          <span id="home-priority-mode">Current global focus</span>
        </div>
        <div id="home-priority-board-cards" class="home-priority-board-cards">
          <p class="home-priority-loading">Loading critical signals...</p>
        </div>
        <p id="home-priority-support" class="home-priority-support">
          Ranking incoming signals across earthquakes, volcanoes, tsunami, and space weather.
        </p>
      </article>
      <article class="card home-priority-rail">
        <div class="snapshot-head">
          <h4>Live significant events</h4>
          <span id="home-significant-head-note">Ranked feed</span>
        </div>
        <ul id="home-significant-list" class="snapshot-list home-priority-rail-list">
          <li class="snapshot-row">Loading significant events...</li>
        </ul>
      </article>
    </section>

    <section class="home-priority-snapshot" aria-label="Global snapshot">
      <div class="feed-head">
        <h3>Global snapshot</h3>
      </div>
      <div id="home-snapshot" class="launch-overview home-v2-overview">
        <article class="overview-item">
          <p class="kpi-label">Events (24h)</p>
          <p id="home-kpi-total" class="kpi-value">--</p>
          <p class="kpi-note">Earthquakes in latest feed</p>
        </article>
        <article class="overview-item home-v2-strongest">
          <p class="kpi-label">Strongest earthquake</p>
          <p id="home-kpi-strongest" class="kpi-value">--</p>
          <p id="home-kpi-strongest-place" class="kpi-note">No data</p>
        </article>
        <article class="overview-item">
          <p class="kpi-label">Active volcanoes</p>
          <p id="home-status-volcanoes" class="kpi-value">--</p>
          <p id="home-status-volcano-note" class="kpi-note">Loading volcano status...</p>
        </article>
        <article class="overview-item">
          <p class="kpi-label">Tsunami alerts</p>
          <p id="home-status-tsunami" class="kpi-value">--</p>
          <p id="home-status-tsunami-note" class="kpi-note">Loading tsunami status...</p>
        </article>
      </div>
    </section>

    <section class="home-priority-modules" aria-label="Category modules">
      <article id="home-panel-clusters" class="snapshot-card home-priority-module">
        <div class="snapshot-head">
          <h4>Earthquakes</h4>
          <a class="inline-link home-live-head-link" href="/earthquakes.php">Open monitor</a>
        </div>
        <ul id="home-module-earthquakes-list" class="snapshot-list home-live-list">
          <li class="snapshot-row">Loading earthquakes...</li>
        </ul>
      </article>
      <article id="home-panel-volcano" class="snapshot-card home-priority-module">
        <div class="snapshot-head">
          <h4>Volcanoes</h4>
          <a class="inline-link home-live-head-link" href="/volcanoes.php">Open monitor</a>
        </div>
        <ul id="home-volcano-list" class="snapshot-list home-live-list home-volcano-list">
          <li class="snapshot-row">Loading volcano feed...</li>
        </ul>
      </article>
      <article class="snapshot-card home-priority-module">
        <div class="snapshot-head">
          <h4>Tsunami alerts</h4>
          <a class="inline-link home-live-head-link" href="/tsunami.php">Open monitor</a>
        </div>
        <ul id="home-module-tsunami-list" class="snapshot-list home-live-list">
          <li class="snapshot-row">Loading tsunami status...</li>
        </ul>
      </article>
      <article class="snapshot-card home-priority-module">
        <div class="snapshot-head">
          <h4>Space weather</h4>
          <a class="inline-link home-live-head-link" href="/space-weather.php">Open monitor</a>
        </div>
        <ul id="home-module-space-list" class="snapshot-list home-live-list">
          <li class="snapshot-row">Loading space weather...</li>
        </ul>
      </article>
    </section>

    <p id="home-snapshot-brief" class="snapshot-brief">Loading global activity summary...</p>
    <p id="home-sources-line" class="sources-line">Sources loading...</p>

    <section class="home-v2-map" aria-label="Global Activity Map">
      <div class="feed-head">
        <h3>Global Activity Map</h3>
        <a class="btn btn-ghost home-v2-map-btn" href="/maps.php">Open Full Map</a>
      </div>
      <div class="home-v2-map-grid">
        <article class="card home-v2-map-card">
          <div class="map-wrap insight-map-wrap">
            <div
              id="world-map-leaflet"
              class="world-map-leaflet"
              role="img"
              aria-label="Live global earthquake map"
            ></div>
          </div>
        </article>
        <article class="card home-v2-map-side">
          <div class="snapshot-head">
            <h4 id="home-map-feed-title">Earthquake Feed</h4>
            <span id="home-map-feed-note">Global now</span>
          </div>
          <ul id="home-map-feed-list" class="snapshot-list">
            <li class="snapshot-row">Loading earthquake feed...</li>
          </ul>
          <a class="inline-link" href="/earthquakes.php">Open Earthquakes</a>
        </article>
      </div>
    </section>

  </div>
  <p class="sr-only">
    <span id="home-kpi-significant">--</span>
    <span id="home-status-kp">--</span>
    <span id="home-status-space-note">Loading space weather...</span>
  </p>
</section>

<section class="panel home-v2-covers">
  <h3>What Quakrs Covers</h3>
  <div class="home-v2-cover-grid">
    <article class="card home-v2-cover-card">
      <div class="home-v2-cover-media home-v2-cover-media-earthquakes" aria-hidden="true"></div>
      <h4>Earthquakes</h4>
      <p>Global seismic events, magnitude trends and rapid regional prioritization.</p>
    </article>
    <article class="card home-v2-cover-card">
      <div class="home-v2-cover-media home-v2-cover-media-volcanoes" aria-hidden="true"></div>
      <h4>Volcanoes</h4>
      <p>Weekly bulletin tracking, eruptive change highlights and country-level context.</p>
    </article>
    <article class="card home-v2-cover-card">
      <div class="home-v2-cover-media home-v2-cover-media-tsunami" aria-hidden="true"></div>
      <h4>Tsunami Alerts</h4>
      <p>Operational alert status with current highest level and response visibility.</p>
    </article>
    <article class="card home-v2-cover-card">
      <div class="home-v2-cover-media home-v2-cover-media-space" aria-hidden="true"></div>
      <h4>Space Weather</h4>
      <p>Kp index and geomagnetic storm levels integrated in the same live brief.</p>
    </article>
    <article class="card home-v2-cover-card">
      <div class="home-v2-cover-media home-v2-cover-media-archive" aria-hidden="true"></div>
      <h4>Data Archive</h4>
      <p>Historic earthquake search, analysis views and export-ready monitoring records.</p>
    </article>
  </div>
</section>

<section class="panel home-v2-trust">
  <p class="launch-copy">Trusted by leading scientific providers and institutional sources.</p>
  <div class="home-v2-trust-row card" aria-label="Sources and partners">
    <span class="home-v2-trust-item">
      <img class="home-v2-trust-logo" src="/assets/icons/providers/usgs-mark.svg" alt="" loading="lazy" />
      <span>USGS</span>
    </span>
    <span class="home-v2-trust-item">
      <img class="home-v2-trust-logo" src="/assets/icons/providers/emsc.png" alt="" loading="lazy" />
      <span>EMSC</span>
    </span>
    <span class="home-v2-trust-item">
      <img class="home-v2-trust-logo" src="/assets/icons/providers/noaa.svg" alt="" loading="lazy" />
      <span>NOAA</span>
    </span>
    <span class="home-v2-trust-item">
      <img class="home-v2-trust-logo" src="/assets/icons/providers/cnp.svg" alt="" loading="lazy" />
      <span>Earthquakes CNP</span>
    </span>
    <span class="home-v2-trust-item">
      <img class="home-v2-trust-logo" src="/assets/icons/providers/gvp-mark.svg" alt="" loading="lazy" />
      <span>Smithsonian GVP</span>
    </span>
    <span class="home-v2-trust-item">
      <img class="home-v2-trust-logo" src="/assets/icons/providers/ingv.ico" alt="" loading="lazy" />
      <span>INGV</span>
    </span>
  </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
