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

<main class="hero home-v2-hero home-priority-hero home-neo-hero">
  <div class="home-hero-editorial-column">
    <div class="home-v2-hero-main home-neo-hero-main">
      <p class="eyebrow">Global seismic intelligence</p>
      <h1>
        <span class="hero-line">Earthquakes,</span>
        <span class="hero-line">Volcanoes &amp; Multi-Hazard</span>
        <span class="hero-line">Live Monitors</span>
      </h1>
      <p class="sub">
        Global real-time monitoring for seismic, volcanic and multi-hazard events.
      </p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="/earthquakes.php">Open monitors</a>
        <a class="btn btn-ghost" href="/data-archive.php">Browse archive</a>
      </div>
    </div>

    <div class="home-hero-lower">
      <aside class="card home-neo-editorial home-hero-editorial-card" aria-label="Editorial and dynamic briefing">
        <div class="home-neo-editorial-head">
          <p class="home-neo-console-kicker">Editorial brief</p>
          <span id="home-priority-now" class="home-neo-live-pill">Dynamic focus</span>
        </div>
        <div class="home-v2-context-room home-context-room">
          <div class="home-context-layout">
            <div class="home-context-copy">
              <div class="snapshot-head home-context-head">
                <h4 id="home-context-title">Global watch in progress</h4>
                <span id="home-context-mode">Loading mode...</span>
              </div>
              <p id="home-context-summary" class="home-context-summary">Loading live editorial summary...</p>
              <div class="home-context-facts">
                <p id="home-context-region" class="home-context-fact">Area: --</p>
                <p id="home-context-window" class="home-context-fact">Window: --</p>
                <p id="home-context-pressure" class="home-context-fact">Intensity: --</p>
                <p id="home-context-probability" class="home-context-fact">Activity index: --</p>
              </div>
              <div class="home-context-earthquake-row">
                <div class="home-context-earthquake-head">
                  <h5 id="home-context-eq-title">Highlighted earthquakes</h5>
                </div>
                <ul id="home-context-eq-list" class="home-context-earthquake-list">
                  <li class="home-context-earthquake-item">Loading contextual events...</li>
                </ul>
              </div>
            </div>
            <article class="home-context-visual">
              <p class="home-context-visual-kicker">Signal canvas</p>
              <h5 id="home-context-visual-title" class="home-context-visual-title">Distributed activity</h5>
              <p id="home-context-visual-meta" class="home-context-visual-meta">Loading signal canvas...</p>
            </article>
            <article class="home-context-ai">
              <p class="home-context-ai-label">AI-assisted readout</p>
              <h5 id="home-ai-tech" class="home-context-ai-tech">M-- · Pending</h5>
              <p id="home-ai-text" class="home-context-ai-text">Preparing the latest contextual interpretation...</p>
            </article>
          </div>
        </div>
      </aside>
      <aside class="card home-hero-events-rail" aria-label="Live significant events">
        <div class="feed-head home-hero-events-head">
          <div class="home-section-heading">
            <p class="home-section-kicker">Priority stream</p>
            <h3>Live significant events</h3>
          </div>
          <div class="home-priority-meta-links">
            <span id="home-significant-head-note">Ranked feed</span>
            <a class="home-priority-explain-link" href="/priority-levels.php">How P1/P2 works</a>
          </div>
        </div>
        <ul id="home-significant-list" class="snapshot-list home-priority-rail-list home-hero-events-list">
          <li class="snapshot-row">Loading significant events...</li>
        </ul>
      </aside>
    </div>
  </div>
</main>

<section id="launch" class="launch home-v2-launch home-priority-launch home-neo-launch">
  <article class="card home-priority-snapshot home-neo-snapshot" aria-label="Global snapshot">
    <div class="feed-head">
      <div class="home-section-heading">
        <p class="home-section-kicker">Operational overview</p>
        <h3>Global snapshot</h3>
      </div>
    </div>
    <div id="home-snapshot" class="launch-overview home-v2-overview home-neo-overview">
      <article class="overview-item">
        <p class="kpi-label">Events (24h)</p>
        <p class="kpi-value" data-home-mirror="total">--</p>
        <p class="kpi-note">Earthquakes in latest feed</p>
      </article>
      <article class="overview-item home-v2-strongest">
        <p class="kpi-label">Strongest earthquake</p>
        <p class="kpi-value" data-home-mirror="strongest">--</p>
        <p class="kpi-note" data-home-mirror="strongest-place">No data</p>
      </article>
      <article class="overview-item">
        <p class="kpi-label">Tremor clusters</p>
        <p id="home-status-tremor-clusters" class="kpi-value">--</p>
        <p id="home-status-tremor-note" class="kpi-note">Loading tremor signals...</p>
      </article>
      <article class="overview-item">
        <p class="kpi-label">Active volcanoes</p>
        <p id="home-status-volcanoes" class="kpi-value">--</p>
        <p id="home-status-volcano-note" class="kpi-note">Loading volcano status...</p>
      </article>
      <article class="overview-item">
        <p class="kpi-label">Geomagnetic Kp</p>
        <p id="home-status-kp" class="kpi-value">--</p>
        <p id="home-status-space-note" class="kpi-note">Loading space weather...</p>
      </article>
      <article class="overview-item">
        <p class="kpi-label">Tsunami alerts</p>
        <p id="home-status-tsunami" class="kpi-value">--</p>
        <p id="home-status-tsunami-note" class="kpi-note">Loading tsunami status...</p>
      </article>
    </div>
  </article>

  <div class="home-dashboard-grid">
    <div class="home-dashboard-main">
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
        <a class="home-priority-explain-link" href="/priority-levels.php">Understand P1/P2 priority logic</a>
      </article>
    </div>

    <aside class="home-dashboard-side">
    </aside>

    <section class="home-v2-map home-neo-map home-dashboard-map" aria-label="Global Activity Map">
      <div class="feed-head">
        <h3>Global Activity Map</h3>
        <a class="btn btn-ghost home-v2-map-btn" href="/maps.php">Open Full Map</a>
      </div>
      <div class="home-dashboard-map-frame">
        <article class="card home-dashboard-earthquakes">
          <label class="home-map-list-filter" for="home-map-viewport-only">
            <input id="home-map-viewport-only" type="checkbox">
            <span>Only list earthquakes shown on map</span>
          </label>
          <ul id="home-map-feed-list" class="snapshot-list">
            <li class="snapshot-row">Loading earthquake feed...</li>
          </ul>
          <a class="inline-link" href="/earthquakes.php">Open Earthquakes</a>
        </article>
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
      </div>
    </section>
  </div>
</section>

<section class="panel home-dashboard-monitors" aria-label="Category modules">
  <div class="feed-head">
    <div class="home-section-heading">
      <p class="home-section-kicker">Monitoring layers</p>
      <h3>Monitors</h3>
    </div>
    <a class="inline-link home-dashboard-archive-link" href="/data-archive.php">Browse archive</a>
  </div>
  <div class="home-dashboard-monitors-grid">
    <article id="home-panel-clusters" class="snapshot-card home-priority-module home-neo-module home-dashboard-monitor">
      <div class="snapshot-head">
        <h4><a class="home-module-title-link" href="/data-clusters.php">Tremor clusters</a></h4>
        <span>Subsurface pressure</span>
      </div>
      <ul id="home-clusters-list" class="snapshot-list home-live-list home-hazard-list">
        <li class="snapshot-row">Loading tremor clusters...</li>
      </ul>
    </article>
    <article class="snapshot-card home-priority-module home-neo-module home-dashboard-monitor">
      <div class="snapshot-head">
        <h4><a class="home-module-title-link" href="/earthquakes.php">Earthquakes</a></h4>
        <span>Rapid picks</span>
      </div>
      <ul id="home-module-earthquakes-list" class="snapshot-list home-live-list">
        <li class="snapshot-row">Loading earthquakes...</li>
      </ul>
    </article>
    <article id="home-panel-volcano" class="snapshot-card home-priority-module home-neo-module home-dashboard-monitor">
      <div class="snapshot-head">
        <h4><a class="home-module-title-link" href="/volcanoes.php">Volcanoes</a></h4>
        <span>Bulletin cycle</span>
      </div>
      <ul id="home-volcano-list" class="snapshot-list home-live-list home-volcano-list">
        <li class="snapshot-row">Loading volcano feed...</li>
      </ul>
    </article>
    <article id="home-panel-tsunami" class="snapshot-card home-priority-module home-neo-module home-dashboard-monitor">
      <div class="snapshot-head">
        <h4><a class="home-module-title-link" href="/tsunami.php">Tsunami alerts</a></h4>
        <span>Operational alerting</span>
      </div>
      <ul id="home-module-tsunami-list" class="snapshot-list home-live-list">
        <li class="snapshot-row">Loading tsunami status...</li>
      </ul>
    </article>
    <article id="home-panel-space" class="snapshot-card home-priority-module home-neo-module home-dashboard-monitor home-dashboard-monitor-wide">
      <div class="snapshot-head">
        <h4><a class="home-module-title-link" href="/space-weather.php">Space weather</a></h4>
        <span>Solar watch</span>
      </div>
      <ul id="home-module-space-list" class="snapshot-list home-live-list">
        <li class="snapshot-row">Loading space weather...</li>
      </ul>
    </article>
  </div>
</section>

<section class="panel home-dashboard-covers" aria-label="What Quakrs covers">
  <div class="home-section-heading">
    <p class="home-section-kicker">Mission scope</p>
    <h3>What Quakrs Covers</h3>
    <p class="home-section-note">Four operational surfaces, one continuous monitoring system.</p>
  </div>
  <div class="home-v2-cover-grid home-dashboard-cover-grid">
    <a class="card home-v2-cover-card" href="/earthquakes.php">
      <span class="home-v2-cover-tag">Live monitor</span>
      <div class="home-v2-cover-media home-v2-cover-media-earthquakes" aria-hidden="true"></div>
      <h4>Earthquakes</h4>
      <p>Global seismic events, magnitude trends and rapid regional prioritization.</p>
    </a>
    <a class="card home-v2-cover-card" href="/volcanoes.php">
      <span class="home-v2-cover-tag">Bulletins</span>
      <div class="home-v2-cover-media home-v2-cover-media-volcanoes" aria-hidden="true"></div>
      <h4>Volcanoes</h4>
      <p>Weekly bulletin tracking, eruptive change highlights and country-level context.</p>
    </a>
    <a class="card home-v2-cover-card" href="/tsunami.php">
      <span class="home-v2-cover-tag">Alerts</span>
      <div class="home-v2-cover-media home-v2-cover-media-tsunami" aria-hidden="true"></div>
      <h4>Tsunami Alerts</h4>
      <p>Operational alert status with current highest level and response visibility.</p>
    </a>
    <a class="card home-v2-cover-card" href="/space-weather.php">
      <span class="home-v2-cover-tag">Solar watch</span>
      <div class="home-v2-cover-media home-v2-cover-media-space" aria-hidden="true"></div>
      <h4>Space Weather</h4>
      <p>Kp index and geomagnetic storm levels integrated in the same live brief.</p>
    </a>
    <a class="card home-v2-cover-card" href="/data-archive.php">
      <span class="home-v2-cover-tag">Historic search</span>
      <div class="home-v2-cover-media home-v2-cover-media-archive" aria-hidden="true"></div>
      <h4>Data Archive</h4>
      <p>Historic earthquake search, analysis views and export-ready monitoring records.</p>
    </a>
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
