<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Dati Italia';
$pageDescription = 'Monitor Italia con mappa dedicata, trend operativi e rilevamento sciami.';
$currentPage = 'data-italia';
$includeLeaflet = true;

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>
<style>
  .it-map-legend span {
    color: #0d121a;
    font-weight: 700;
    text-shadow: none;
  }

  .it-map-legend .map-filter-btn {
    width: 100%;
    padding: 0.3rem 0.28rem;
    font-size: 0.66rem;
  }

  .it-map-legend .map-filter-btn.band-m1-2 {
    --band-bg: #22d3ee;
    --band-fg: #04131b;
  }

  .it-map-legend .map-filter-btn.band-m3 {
    --band-bg: #5de4c7;
    --band-fg: #082218;
  }

  .it-map-legend .map-filter-btn.band-m4 {
    --band-bg: #f7d21e;
    --band-fg: #231b02;
  }

  .it-map-legend .map-filter-btn.band-m6 {
    --band-bg: #ff7a00;
    --band-fg: #241101;
  }

  .it-map-legend .map-filter-btn.band-m7p {
    --band-bg: #ff1f2d;
    --band-fg: #ffffff;
  }

  .it-map-legend .map-filter-btn.band-m8p {
    --band-bg: #b84dff;
    --band-fg: #ffffff;
  }

  .it-map-legend {
    grid-template-columns: repeat(auto-fit, minmax(0, 1fr));
  }

  .it-depth-chart {
    margin-top: 0.72rem;
  }

  .it-line-chart {
    height: 24.5rem;
    min-height: 24.5rem;
    margin-top: 0.1rem;
  }

  .it-trend-bars {
    --bar-thickness: clamp(16px, 2.2vw, 24px);
    min-height: 24.5rem;
    display: grid;
    grid-template-columns: repeat(var(--bar-count), var(--bar-thickness));
    justify-content: space-between;
    gap: 0;
  }

  .it-trend-bars .bar-col-value {
    font-size: 0.78rem;
    font-weight: 700;
  }

  .it-trend-bars .bar-col {
    width: var(--bar-thickness);
  }

  .it-trend-bars .bar-col-track {
    width: 100%;
    margin: 0;
  }

  .it-trend-bars .bar-col-label {
    font-size: 0.62rem;
  }

  .it-trend-bars-30 {
    --bar-thickness: clamp(8px, 1.1vw, 14px);
    min-height: 0;
    height: 100%;
  }

  .it-trend-bars-30 .bar-col {
    grid-template-rows: minmax(0, 1fr);
    gap: 0;
  }

  .it-trend-bars-30 .bar-col-value,
  .it-trend-bars-30 .bar-col-label {
    display: none;
  }

  .it-trend-bars-30 .bar-col-label {
    font-size: 0.54rem;
  }

  .it-trend-bars-30 .bar-col-value {
    font-size: 0.58rem;
    font-weight: 700;
    letter-spacing: 0.01em;
  }

  .it-trend30-stack {
    display: grid;
    grid-template-rows: minmax(0, 1fr) auto;
    gap: 2px;
    min-height: 24.5rem;
    height: 100%;
  }

  .it-trend30-dates {
    position: relative;
    height: 1rem;
    color: var(--muted);
    font-variant-numeric: tabular-nums;
    font: 700 0.54rem/1 "Manrope", sans-serif;
  }

  .it-trend30-dates span {
    position: absolute;
    top: 1px;
    left: 0;
    transform: translateX(-50%);
    white-space: nowrap;
  }

  .it-trend30-dates span.is-start {
    transform: none;
  }

  .it-trend30-dates span.is-end {
    transform: translateX(-100%);
  }

  .it-line-chart svg {
    width: 100%;
    height: 100%;
    display: block;
  }

  .it-line-grid {
    stroke: color-mix(in srgb, var(--line) 74%, transparent);
    stroke-width: 1;
  }

  .it-line-grid-major {
    stroke: color-mix(in srgb, var(--line) 92%, transparent);
    stroke-width: 1.1;
  }

  .it-line-cap {
    stroke: color-mix(in srgb, #ff895b 82%, transparent);
    stroke-width: 1.2;
    stroke-dasharray: 5 5;
  }

  .it-line-path {
    fill: none;
    stroke: #5de4c7;
    stroke-width: 3;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  .it-line-dot {
    fill: #94f1dd;
    stroke: color-mix(in srgb, var(--surface-2) 70%, transparent);
    stroke-width: 1.5;
  }

  .it-line-label {
    fill: color-mix(in srgb, var(--muted) 92%, transparent);
    font-size: 13px;
    font-weight: 700;
    font-family: "Manrope", sans-serif;
  }

  .it-line-value {
    fill: color-mix(in srgb, var(--text) 92%, transparent);
    font-size: 17px;
    font-weight: 800;
    font-family: "Space Grotesk", sans-serif;
  }

  .it-line-overflow {
    fill: #ff895b;
  }

  #it-chart-mag {
    min-height: 24.5rem;
  }

  .it-trend-card {
    display: grid;
    grid-template-rows: auto minmax(0, 1fr);
    min-height: 29rem;
  }

  .it-swarm-link {
    display: block;
    color: var(--text);
    text-decoration: none;
    border: 1px solid transparent;
    border-radius: 10px;
    padding: 0.56rem 0.62rem;
    margin: -0.3rem -0.36rem;
    transition: border-color 140ms ease, background-color 140ms ease, color 140ms ease;
  }

  .it-swarm-link:hover {
    color: color-mix(in srgb, var(--cyan) 88%, #ecfffa);
    border-color: color-mix(in srgb, var(--cyan) 46%, var(--line));
    background: color-mix(in srgb, var(--cyan) 14%, var(--surface-2));
  }

  .it-swarm-link span {
    color: var(--muted);
    font-size: 0.76rem;
  }

  .it-main-layout {
    --it-map-frame-height: clamp(900px, 96vh, 1180px);
    --it-map-card-height: calc(var(--it-map-frame-height) + 8.4rem);
    display: grid;
    grid-template-columns: minmax(300px, 0.62fr) minmax(0, 1.46fr) minmax(300px, 0.72fr);
    gap: 0.95rem;
    align-items: start;
  }

  .it-feed-card {
    display: grid;
    grid-template-rows: auto minmax(0, 1fr);
    min-height: var(--it-map-card-height);
    max-height: var(--it-map-card-height);
  }

  .it-feed-list {
    margin-top: 0.38rem;
    display: block;
    overflow: auto;
    padding-right: 0.2rem;
  }

  .it-feed-list .event-item {
    display: grid;
    grid-template-columns: 3rem minmax(0, 1fr);
    grid-template-areas:
      "mag place"
      ". sub";
    column-gap: 0.26rem;
    row-gap: 0.1rem;
    align-items: start;
    min-height: 0;
    padding: 0.44rem 0.56rem;
    min-height: 2.68rem;
    line-height: 1.25;
    overflow: hidden;
  }

  .it-feed-list .event-item.is-clickable {
    cursor: pointer;
  }

  .it-feed-list .it-feed-mag {
    grid-area: mag;
    min-width: 3rem;
    font: 800 0.95rem/1 "Space Grotesk", sans-serif;
    align-self: start;
  }

  .it-feed-list .it-feed-place {
    grid-area: place;
    display: block;
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: var(--text-1);
    font-size: 0.86rem;
    line-height: 1.16;
    font-weight: 400;
  }

  .it-feed-list .it-feed-sub {
    grid-area: sub;
    display: block;
    margin-top: 0;
    max-width: none;
    color: var(--muted);
    font-size: 0.78rem;
    line-height: 1.18;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-variant-numeric: tabular-nums;
    font-weight: 400;
  }

  .it-feed-list .event-item.is-strong .it-feed-place,
  .it-feed-list .event-item.is-strong .it-feed-sub {
    font-weight: 700;
  }

  .it-map-card {
    display: grid;
    grid-template-rows: auto auto auto;
    min-height: var(--it-map-card-height);
    max-height: var(--it-map-card-height);
    overflow: visible;
  }

  .it-map-card .map-wrap {
    height: auto;
    min-height: 0;
  }

  .it-map-card .world-map-leaflet {
    height: var(--it-map-frame-height);
    min-height: var(--it-map-frame-height);
  }

  .it-map-card .it-map-legend {
    margin-top: 0.48rem;
    position: relative;
    z-index: 2;
  }

  .it-swarms-card {
    display: grid;
    grid-template-rows: auto minmax(0, 1fr);
    min-height: var(--it-map-card-height);
    max-height: var(--it-map-card-height);
  }

  .it-swarms-card .events-list {
    margin-top: 0.34rem;
    overflow: auto;
    padding-right: 0.2rem;
    align-content: start;
  }

  @media (max-width: 1120px) {
    .it-main-layout {
      grid-template-columns: 1fr;
      --it-map-card-height: auto;
    }

    .it-feed-card {
      min-height: 0;
      max-height: none;
    }
  }
</style>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.data_italia.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.data_italia.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.data_italia.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Events (24h)</p>
    <p id="it-kpi-events" class="kpi-value">--</p>
    <p class="kpi-note">INGV Italy feed</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Max magnitude</p>
    <p id="it-kpi-maxmag" class="kpi-value">--</p>
    <p class="kpi-note">Strongest in last 24h</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Estimated energy</p>
    <p id="it-kpi-energy" class="kpi-value">--</p>
    <p class="kpi-note">24h cumulative estimate</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Baseline delta</p>
    <p id="it-kpi-baseline" class="kpi-value">--</p>
    <p id="it-kpi-source" class="kpi-note">Loading source...</p>
  </article>
</section>

<section class="panel it-main-layout">
  <article class="card side-card it-feed-card">
    <h3>Feed live Italia</h3>
    <ul id="it-feed-list" class="events-list it-feed-list">
      <li class="event-item">Loading Italy feed...</li>
    </ul>
  </article>
    <article class="card map-card it-map-card">
      <div class="feed-head">
        <h3>Italy map (INGV)</h3>
      </div>
    <div class="map-wrap">
      <div id="it-map-leaflet" class="world-map-leaflet" aria-label="Italy seismic map"></div>
    </div>
      <div class="map-legend it-map-legend">
        <button class="map-filter-btn band-m1-2" data-band="m1-2" type="button" aria-pressed="false">M &lt; 2</button>
        <button class="map-filter-btn band-m3" data-band="m3" type="button" aria-pressed="false">M 2.0-2.9</button>
        <button class="map-filter-btn band-m4" data-band="m4" type="button" aria-pressed="false">M 3.0-4.9</button>
        <button class="map-filter-btn band-m6" data-band="m6" type="button" aria-pressed="false">M 5.0-5.9</button>
        <button class="map-filter-btn band-m7p" data-band="m7p" type="button" aria-pressed="false">M 6.0-6.9</button>
        <button class="map-filter-btn band-m8p" data-band="m8p" type="button" aria-pressed="false">M 7+</button>
      </div>
    </article>
  <article class="card side-card it-swarms-card">
    <h3>Sciami in evidenza</h3>
    <ul id="it-swarms-list" class="events-list">
      <li class="event-item">Loading swarm candidates...</li>
    </ul>
  </article>
</section>

<section class="panel panel-charts">
  <article class="card it-trend-card">
    <div class="feed-head">
      <h3>Trend 7 days</h3>
      <p class="feed-meta">Daily counts</p>
    </div>
    <div id="it-chart-7d" class="bars-vertical it-trend-bars"></div>
  </article>
  <article class="card it-trend-card">
    <div class="feed-head">
      <h3>Trend 30 days</h3>
      <p class="feed-meta">Daily counts</p>
    </div>
    <div class="it-trend30-stack">
      <div id="it-chart-30d" class="bars-vertical it-trend-bars it-trend-bars-30"></div>
      <div id="it-chart-30d-dates" class="it-trend30-dates" aria-hidden="true"></div>
    </div>
  </article>
  <article class="card it-trend-card">
    <div class="feed-head">
      <h3>Magnitude mix (24h)</h3>
      <p class="feed-meta">M0-1, M1-2, M2-3, M3+</p>
    </div>
    <div id="it-chart-mag" class="bars-vertical bars-magnitude"></div>
  </article>
</section>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Operational story</h3>
    <p id="it-story" class="insight-lead">Building Italy summary...</p>
  </article>
  <article class="card page-card">
    <h3>Depth profile (24h)</h3>
    <div id="it-chart-depth" class="bars it-depth-chart"></div>
  </article>
  <article class="card page-card">
    <h3>Operator cues</h3>
    <ul id="it-cues" class="events-list">
      <li class="event-item">Loading cues...</li>
    </ul>
  </article>
</section>

<script>
  (() => {
    const kpiEvents = document.querySelector("#it-kpi-events");
    const kpiMaxMag = document.querySelector("#it-kpi-maxmag");
    const kpiEnergy = document.querySelector("#it-kpi-energy");
    const kpiBaseline = document.querySelector("#it-kpi-baseline");
    const kpiSource = document.querySelector("#it-kpi-source");
    const swarmsList = document.querySelector("#it-swarms-list");
    const feedList = document.querySelector("#it-feed-list");
    const chart7d = document.querySelector("#it-chart-7d");
    const chart30d = document.querySelector("#it-chart-30d");
    const chart30dDates = document.querySelector("#it-chart-30d-dates");
    const chartMag = document.querySelector("#it-chart-mag");
    const chartDepth = document.querySelector("#it-chart-depth");
    const story = document.querySelector("#it-story");
    const cues = document.querySelector("#it-cues");
    const mapContainer = document.querySelector("#it-map-leaflet");
    const itMapLegend = document.querySelector(".it-map-legend");
    const itMapFilterButtons = document.querySelectorAll(".it-map-legend .map-filter-btn");

    const formatEnergy = (joules) => {
      if (!Number.isFinite(joules) || joules <= 0) return "--";
      if (joules >= 1e15) return `${(joules / 1e15).toFixed(2)} PJ`;
      if (joules >= 1e12) return `${(joules / 1e12).toFixed(2)} TJ`;
      if (joules >= 1e9) return `${(joules / 1e9).toFixed(2)} GJ`;
      return `${joules.toExponential(2)} J`;
    };

    const escapeHtml = (value) =>
      String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#39;");

    const renderVerticalChart = (container, rows, options = {}) => {
      if (!container) return;
      const forcedMax = Number(options.maxValue || 0);
      const maxValue = forcedMax > 0 ? forcedMax : (rows.reduce((max, row) => Math.max(max, Number(row.value || 0)), 0) || 1);
      if (options.adaptiveThickness === true) {
        const rectWidth = Math.round(container.getBoundingClientRect().width || 0);
        const parentWidth = container.parentElement ? Math.round(container.parentElement.getBoundingClientRect().width || 0) : 0;
        const width = Math.max(320, rectWidth || container.clientWidth || parentWidth || 0);
        const count = Math.max(1, rows.length);
        const gap = Math.max(0, Number(options.gapPx || 4));
        const minT = Number(options.minThickness || 8);
        const maxT = Number(options.maxThickness || 24);
        let thickness;
        if (options.uniformColumns === true) {
          const totalGap = gap * Math.max(0, count - 1);
          const fitThickness = Math.floor((width - totalGap) / count);
          const bounded = Math.max(3, Math.min(maxT, fitThickness));
          thickness = fitThickness >= minT ? Math.max(minT, bounded) : bounded;
          container.style.setProperty("--bar-gap", `${gap}px`);
        } else {
          const raw = Math.floor((width / count) - gap);
          thickness = Math.max(minT, Math.min(maxT, raw));
          container.style.removeProperty("--bar-gap");
        }
        container.style.setProperty("--bar-thickness", `${thickness}px`);
      }
      container.style.setProperty("--bar-count", String(rows.length));
      container.innerHTML = rows.map((row) => {
        const value = Number(row.value || 0);
        const clamped = Math.max(0, Math.min(maxValue, value));
        const height = Math.max(4, Math.round((clamped / maxValue) * 100));
        const displayValue = row.display ?? String(value);
        return `
          <div class="bar-col">
            <div class="bar-col-value${displayValue ? "" : " is-empty"}">${displayValue}</div>
            <div class="bar-col-track">
              <div class="bar-col-fill" style="height:${height}%;background:${row.color || "#5de4c7"}"></div>
            </div>
            <div class="bar-col-label">${row.label || ""}</div>
          </div>
        `;
      }).join("");
    };

    const renderLineChart = (container, rows, labelStep = 1, valueStep = 1, attempt = 0) => {
      if (!container) return;
      if (!Array.isArray(rows) || rows.length === 0) {
        container.innerHTML = "<div class='event-item'>No trend data available.</div>";
        return;
      }

      const rect = container.getBoundingClientRect();
      if ((rect.width < 240 || rect.height < 220) && attempt < 4) {
        window.requestAnimationFrame(() => renderLineChart(container, rows, labelStep, valueStep, attempt + 1));
        return;
      }

      const width = Math.max(780, Math.round(rect.width || 0));
      const height = Math.max(392, Math.round(rect.height || 0));
      const pad = { top: 8, right: 10, bottom: 34, left: 10 };
      const plotW = width - pad.left - pad.right;
      const plotH = height - pad.top - pad.bottom;
      const yCap = 100;
      const maxY = yCap;
      const xStep = rows.length > 1 ? (plotW / (rows.length - 1)) : 0;

      const yAt = (value) => {
        const clamped = Math.max(0, Math.min(maxY, value));
        return pad.top + (plotH - ((clamped / maxY) * plotH));
      };
      const xAt = (index) => pad.left + (index * xStep);
      const points = rows.map((row, index) => `${xAt(index).toFixed(2)},${yAt(Number(row.value || 0)).toFixed(2)}`).join(" ");

      let labels = "";
      rows.forEach((row, index) => {
        const isLast = index === rows.length - 1;
        if (!(index % labelStep === 0 || isLast)) return;
        labels += `<text class="it-line-label" x="${xAt(index).toFixed(2)}" y="${(height - 14).toFixed(2)}" text-anchor="middle">${String(row.label || "")}</text>`;
      });

      let dots = "";
      let values = "";
      rows.forEach((row, index) => {
        const value = Number(row.value || 0);
        const y = yAt(value).toFixed(2);
        const x = xAt(index).toFixed(2);
        const overflow = value > yCap;
        dots += `<circle class="it-line-dot${overflow ? " it-line-overflow" : ""}" cx="${x}" cy="${y}" r="5" />`;
        const isLast = index === rows.length - 1;
        if (index % valueStep === 0 || isLast || overflow) {
          const valueLabel = overflow ? `${value}↑` : String(value);
          values += `<text class="it-line-value${overflow ? " it-line-overflow" : ""}" x="${x}" y="${Math.max(16, Number(y) - 10).toFixed(2)}" text-anchor="middle">${valueLabel}</text>`;
        }
      });

      let gridLines = "";
      for (let step = 0; step <= 10; step += 1) {
        const value = step * 10;
        const y = yAt(value).toFixed(2);
        const cls = value % 20 === 0 ? "it-line-grid-major" : "it-line-grid";
        gridLines += `<line class="${cls}" x1="${pad.left}" y1="${y}" x2="${width - pad.right}" y2="${y}" />`;
      }
      const capY = yAt(yCap).toFixed(2);
      const capLine = `<line class="it-line-cap" x1="${pad.left}" y1="${capY}" x2="${width - pad.right}" y2="${capY}" />`;
      const capLabel = `<text class="it-line-label" x="${width - pad.right - 2}" y="${Math.max(14, Number(capY) - 8).toFixed(2)}" text-anchor="end">cap 100</text>`;

      container.innerHTML = `
        <svg viewBox="0 0 ${width} ${height}" role="img" aria-label="Trend line chart">
          ${gridLines}
          ${capLine}
          <polyline class="it-line-path" points="${points}" />
          ${dots}
          ${values}
          ${capLabel}
          ${labels}
        </svg>
      `;
    };

    const renderRowsChart = (container, rows) => {
      if (!container) return;
      const maxValue = rows.reduce((max, row) => Math.max(max, Number(row.value || 0)), 0) || 1;
      container.innerHTML = rows.map((row) => {
        const value = Number(row.value || 0);
        const width = Math.max(6, Math.round((value / maxValue) * 100));
        return `
          <div class="bar-row">
            <div class="bar-label">${row.label}</div>
            <div class="bar-track"><div class="bar-fill" style="width:${width}%"></div></div>
            <div class="bar-value">${row.display ?? String(value)}</div>
          </div>
        `;
      }).join("");
    };

    const renderTrend30Ticks = (series) => {
      if (!chart30dDates) return;
      const rows = Array.isArray(series) ? series : [];
      if (rows.length === 0) {
        chart30dDates.innerHTML = "";
        return;
      }

      const picked = [];
      const seen = new Set();
      for (let i = 0; i < rows.length; i += 5) seen.add(i);
      seen.add(rows.length - 1);
      Array.from(seen).sort((a, b) => a - b).forEach((index) => picked.push(index));

      chart30dDates.innerHTML = picked.map((index, tickPos) => {
        const row = rows[index] || {};
        const dateLabel = row.date_utc ? String(row.date_utc).slice(5) : "";
        const pct = rows.length > 1 ? (index / (rows.length - 1)) * 100 : 0;
        const edgeClass = tickPos === 0 ? " is-start" : (tickPos === picked.length - 1 ? " is-end" : "");
        return `<span class="${edgeClass.trim()}" style="left:${pct.toFixed(2)}%">${dateLabel}</span>`;
      }).join("");
    };

    const italyDefaultBounds = [[36.2, 6.0], [47.4, 19.2]];

    const map = mapContainer && window.L
      ? window.L.map(mapContainer, {
          zoomControl: true,
          maxBounds: [[35.0, 6.0], [48.8, 19.6]],
          maxBoundsViscosity: 1.0,
          maxZoom: 13,
          minZoom: 4,
        })
      : null;
    if (map) {
      map.fitBounds(italyDefaultBounds, {
        padding: [18, 18],
        maxZoom: 5.2,
        animate: false,
      });
      const lockedMinZoom = map.getZoom();
      map.setMinZoom(lockedMinZoom);
    }
    const markersLayer = map && window.L ? window.L.layerGroup().addTo(map) : null;
    let darkMode = true;
    let italyBaseStyle = "grayscale";
    const italyBaseLayerCache = new Map();
    let italyActiveBaseLayer = null;
    const italyOverlayState = {
      heat: false,
      depth: false,
      plates: false,
      faults: false,
    };
    let italyOverlayHeatLayer = null;
    let italyOverlayDepthLayer = null;
    let italyOverlayPlatesLayer = null;
    let italyOverlayFaultsLayer = null;
    let italyPlatesLoaded = false;
    let italyFaultsLoaded = false;
    let italyPlatesFeatures = [];
    let italyFaultsFeatures = [];

    const createItalyBaseLayer = (style, dark) => {
      if (!window.L) return null;
      const key = `${style}|${dark ? "dark" : "light"}`;
      if (italyBaseLayerCache.has(key)) {
        return italyBaseLayerCache.get(key);
      }
      const common = { maxZoom: 13, minZoom: 4 };
      let layer = null;
      if (style === "grayscale") {
        layer = window.L.tileLayer(
          dark
            ? "https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png"
            : "https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png",
          { ...common, attribution: "&copy; OpenStreetMap contributors &copy; CARTO" },
        );
      } else if (style === "ocean") {
        layer = window.L.tileLayer("https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png", {
          ...common,
          attribution: "&copy; OpenStreetMap contributors",
        });
      } else if (style === "terrain") {
        layer = window.L.tileLayer("https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png", {
          ...common,
          attribution: "&copy; OpenStreetMap contributors, SRTM",
        });
      } else if (style === "street") {
        layer = window.L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
          ...common,
          attribution: "&copy; OpenStreetMap contributors",
        });
      } else if (style === "satellite") {
        layer = window.L.tileLayer(
          "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
          { ...common, attribution: "Tiles &copy; Esri" },
        );
      }
      if (layer) italyBaseLayerCache.set(key, layer);
      return layer;
    };

    const syncItalyBaseLayer = () => {
      if (!map || !window.L) return;
      const nextLayer = createItalyBaseLayer(italyBaseStyle, darkMode);
      if (!nextLayer) return;
      if (italyActiveBaseLayer && map.hasLayer(italyActiveBaseLayer)) {
        map.removeLayer(italyActiveBaseLayer);
      }
      italyActiveBaseLayer = nextLayer;
      if (!map.hasLayer(nextLayer)) nextLayer.addTo(map);
      if (mapContainer) {
        mapContainer.classList.remove("map-style-dark-ocean", "map-style-dark-terrain", "map-style-dark-street", "map-style-dark-satellite");
        if (darkMode && italyBaseStyle !== "grayscale") {
          mapContainer.classList.add(`map-style-dark-${italyBaseStyle}`);
        }
      }
    };

    const getDepthColor = (depthKm) => {
      const depth = Number(depthKm);
      if (!Number.isFinite(depth)) return "#7f8cad";
      if (depth < 10) return "#20e0ff";
      if (depth < 30) return "#b7ff00";
      if (depth < 70) return "#ffe600";
      return "#ff7a00";
    };

    const getItalyOverlayTheme = () => (darkMode
      ? {
          heatFill: "#20e0ff",
          heatOpacity: 0.12,
          depthOpacity: 0.52,
          depthWeight: 1.1,
          platesColor: "#ff2bd6",
          platesOpacity: 0.58,
          platesWeight: 1.3,
          faultsColor: "#ff7a00",
          faultsOpacity: 0.66,
          faultsWeight: 1.6,
        }
      : {
          heatFill: "#1a8ca1",
          heatOpacity: 0.1,
          depthOpacity: 0.4,
          depthWeight: 1,
          platesColor: "#c125a7",
          platesOpacity: 0.48,
          platesWeight: 1.15,
          faultsColor: "#d65d35",
          faultsOpacity: 0.56,
          faultsWeight: 1.4,
        });

    const renderItalyHeatOverlay = (events) => {
      if (!map || !window.L || !italyOverlayHeatLayer) return;
      const theme = getItalyOverlayTheme();
      italyOverlayHeatLayer.clearLayers();
      events.forEach((event) => {
        if (!Number.isFinite(Number(event?.latitude)) || !Number.isFinite(Number(event?.longitude))) return;
        const mag = Number(event?.magnitude);
        const radiusMeters = Number.isFinite(mag)
          ? Math.max(5000, Math.min(24000, 5000 + mag * 2800))
          : 7000;
        window.L.circle([Number(event.latitude), Number(event.longitude)], {
          radius: radiusMeters,
          color: "transparent",
          fillColor: theme.heatFill,
          fillOpacity: theme.heatOpacity,
          weight: 0,
          interactive: false,
        }).addTo(italyOverlayHeatLayer);
      });
    };

    const renderItalyDepthOverlay = (events) => {
      if (!map || !window.L || !italyOverlayDepthLayer) return;
      const theme = getItalyOverlayTheme();
      italyOverlayDepthLayer.clearLayers();
      events.forEach((event) => {
        if (!Number.isFinite(Number(event?.latitude)) || !Number.isFinite(Number(event?.longitude))) return;
        const depth = Number(event?.depth_km);
        const radiusMeters = Number.isFinite(depth)
          ? Math.max(2400, Math.min(26000, 2200 + depth * 180))
          : 3000;
        window.L.circle([Number(event.latitude), Number(event.longitude)], {
          radius: radiusMeters,
          color: getDepthColor(depth),
          weight: theme.depthWeight,
          opacity: theme.depthOpacity,
          fillColor: "transparent",
          fillOpacity: 0,
          interactive: false,
        }).addTo(italyOverlayDepthLayer);
      });
    };

    const renderItalyPlatesOverlay = () => {
      if (!map || !window.L || !italyOverlayPlatesLayer) return;
      const theme = getItalyOverlayTheme();
      italyOverlayPlatesLayer.clearLayers();
      italyPlatesFeatures.forEach((feature) => {
        const geometry = feature?.geometry;
        const type = String(geometry?.type || "");
        const coordinates = geometry?.coordinates;
        const drawLine = (coords) => {
          if (!Array.isArray(coords) || coords.length < 2) return;
          const latLngs = coords
            .map((pair) => (Array.isArray(pair) && pair.length >= 2 ? [Number(pair[1]), Number(pair[0])] : null))
            .filter((pair) => Array.isArray(pair) && Number.isFinite(pair[0]) && Number.isFinite(pair[1]));
          if (latLngs.length < 2) return;
          window.L.polyline(latLngs, {
            color: theme.platesColor,
            weight: theme.platesWeight,
            opacity: theme.platesOpacity,
            interactive: false,
          }).addTo(italyOverlayPlatesLayer);
        };
        if (type === "LineString") {
          drawLine(coordinates);
        } else if (type === "MultiLineString" && Array.isArray(coordinates)) {
          coordinates.forEach((line) => drawLine(line));
        }
      });
    };

    const loadItalyPlatesOverlay = async () => {
      if (!map || !window.L || italyPlatesLoaded) return;
      italyPlatesLoaded = true;
      try {
        const response = await fetch("/data/tectonic_plates_latest.json", { cache: "no-store" });
        if (!response.ok) {
          return;
        }
        const payload = await response.json();
        italyPlatesFeatures = Array.isArray(payload?.features) ? payload.features : [];
        renderItalyPlatesOverlay();
      } catch (_) {
        // keep silent: overlay is optional
      }
    };

    const italyPointInView = (lat, lon) => lat >= 35.0 && lat <= 48.8 && lon >= 6.0 && lon <= 19.6;

    const loadItalyFaultsOverlay = async () => {
      if (!map || !window.L || italyFaultsLoaded) return;
      italyFaultsLoaded = true;
      try {
        const response = await fetch("/data/tectonic_faults_latest.json", { cache: "no-store" });
        if (!response.ok) return;
        const payload = await response.json();
        italyFaultsFeatures = Array.isArray(payload?.features) ? payload.features : [];
        renderItalyFaultsOverlay();
      } catch (_) {
        // keep silent: overlay is optional
      }
    };

    const renderItalyFaultsOverlay = () => {
      if (!map || !window.L || !italyOverlayFaultsLayer) return;
      const theme = getItalyOverlayTheme();
      italyOverlayFaultsLayer.clearLayers();
      const drawLine = (coords) => {
        if (!Array.isArray(coords) || coords.length < 2) return;
        const latLngs = coords
          .map((pair) => (Array.isArray(pair) && pair.length >= 2 ? [Number(pair[1]), Number(pair[0])] : null))
          .filter((pair) => Array.isArray(pair) && Number.isFinite(pair[0]) && Number.isFinite(pair[1]));
        if (latLngs.length < 2) return;
        const touchesItaly = latLngs.some(([lat, lon]) => italyPointInView(lat, lon));
        if (!touchesItaly) return;
        window.L.polyline(latLngs, {
          color: theme.faultsColor,
          weight: theme.faultsWeight,
          opacity: theme.faultsOpacity,
          interactive: false,
        }).addTo(italyOverlayFaultsLayer);
      };
      italyFaultsFeatures.forEach((feature) => {
        const geometry = feature?.geometry;
        const type = String(geometry?.type || "");
        const coordinates = geometry?.coordinates;
        if (type === "LineString") {
          drawLine(coordinates);
        } else if (type === "MultiLineString" && Array.isArray(coordinates)) {
          coordinates.forEach((line) => drawLine(line));
        }
      });
    };

    const syncItalyOverlays = (events) => {
      if (!map || !window.L) return;
      if (!italyOverlayHeatLayer) italyOverlayHeatLayer = window.L.layerGroup();
      if (!italyOverlayDepthLayer) italyOverlayDepthLayer = window.L.layerGroup();
      if (!italyOverlayPlatesLayer) italyOverlayPlatesLayer = window.L.layerGroup();
      if (!italyOverlayFaultsLayer) italyOverlayFaultsLayer = window.L.layerGroup();

      renderItalyHeatOverlay(events);
      renderItalyDepthOverlay(events);
      if (italyOverlayState.plates) {
        if (italyPlatesLoaded) {
          renderItalyPlatesOverlay();
        } else {
          void loadItalyPlatesOverlay();
        }
      }
      if (italyOverlayState.faults) {
        if (italyFaultsLoaded) {
          renderItalyFaultsOverlay();
        } else {
          void loadItalyFaultsOverlay();
        }
      }

      const setLayer = (layer, enabled) => {
        if (!layer) return;
        if (enabled) {
          if (!map.hasLayer(layer)) layer.addTo(map);
        } else if (map.hasLayer(layer)) {
          map.removeLayer(layer);
        }
      };

      setLayer(italyOverlayHeatLayer, italyOverlayState.heat);
      setLayer(italyOverlayDepthLayer, italyOverlayState.depth);
      setLayer(italyOverlayPlatesLayer, italyOverlayState.plates);
      setLayer(italyOverlayFaultsLayer, italyOverlayState.faults);
    };

    const formatCoord = (value, positiveLabel, negativeLabel) => {
      const num = Number(value);
      if (!Number.isFinite(num)) return "--";
      const abs = Math.abs(num).toFixed(3);
      return `${abs}${num >= 0 ? positiveLabel : negativeLabel}`;
    };

    if (map && window.L) {
      syncItalyBaseLayer();

      let coordsValueEl = null;
      const coordsControl = window.L.control({ position: "bottomright" });
      coordsControl.onAdd = () => {
        const container = window.L.DomUtil.create("div", "leaflet-control map-coords-control");
        coordsValueEl = window.L.DomUtil.create("span", "map-coords-value", container);
        coordsValueEl.textContent = "--";
        return container;
      };
      coordsControl.addTo(map);

      const updateCoords = (latlng) => {
        if (!coordsValueEl) return;
        const lat = latlng && Number.isFinite(latlng.lat) ? latlng.lat : map.getCenter().lat;
        const lon = latlng && Number.isFinite(latlng.lng) ? latlng.lng : map.getCenter().lng;
        coordsValueEl.textContent = `${formatCoord(lat, "°N", "°S")} : ${formatCoord(lon, "°E", "°W")}`;
      };
      map.on("mousemove", (evt) => updateCoords(evt?.latlng || null));
      map.on("mouseout", () => updateCoords(null));
      updateCoords(map.getCenter());

      const styleControl = window.L.control({ position: "topright" });
      styleControl.onAdd = () => {
        const container = window.L.DomUtil.create("div", "leaflet-control map-style-control");
        const quickThemeButton = window.L.DomUtil.create("button", "map-quick-theme-btn", container);
        quickThemeButton.type = "button";
        quickThemeButton.setAttribute("aria-label", "Toggle dark or light map");
        quickThemeButton.setAttribute("title", "Scuro / Chiaro");
        quickThemeButton.innerHTML = '<span class="map-quick-theme-icon" aria-hidden="true">☾</span>';

        const trigger = window.L.DomUtil.create("button", "map-style-control-btn", container);
        trigger.type = "button";
        trigger.setAttribute("aria-label", "Personalize map style");
        trigger.setAttribute("title", "Map style");
        trigger.setAttribute("aria-expanded", "false");
        trigger.innerHTML = '<span class="map-style-icon" aria-hidden="true"><span></span><span></span><span></span></span>';

        const panel = window.L.DomUtil.create("div", "map-style-panel", container);
        panel.innerHTML = `
          <div class="map-style-panel-section-label">Base map</div>
          <div class="map-style-options" role="radiogroup" aria-label="Base layer">
            <button class="map-style-option" type="button" role="radio" data-style="grayscale"><span class="dot"></span><span>Grayscale</span></button>
            <button class="map-style-option" type="button" role="radio" data-style="ocean"><span class="dot"></span><span>Ocean</span></button>
            <button class="map-style-option" type="button" role="radio" data-style="terrain"><span class="dot"></span><span>Terrain</span></button>
            <button class="map-style-option" type="button" role="radio" data-style="street"><span class="dot"></span><span>Street</span></button>
            <button class="map-style-option" type="button" role="radio" data-style="satellite"><span class="dot"></span><span>Satellite</span></button>
          </div>
          <div class="map-style-panel-divider"></div>
          <div class="map-style-panel-section-label">Overlay layers</div>
          <div class="map-style-overlays" role="group" aria-label="Overlay layers">
            <button class="map-style-overlay-option" type="button" role="checkbox" data-overlay="heat" aria-checked="false"><span class="box"></span><span>Heat density</span></button>
            <button class="map-style-overlay-option" type="button" role="checkbox" data-overlay="depth" aria-checked="false"><span class="box"></span><span>Depth contours</span></button>
            <button class="map-style-overlay-option" type="button" role="checkbox" data-overlay="plates" aria-checked="false"><span class="box"></span><span>Plate boundaries</span></button>
            <button class="map-style-overlay-option" type="button" role="checkbox" data-overlay="faults" aria-checked="false"><span class="box"></span><span>Fault lines (IT)</span></button>
          </div>
        `;

        const styleOptions = Array.from(panel.querySelectorAll(".map-style-option"));
        const overlayOptions = Array.from(panel.querySelectorAll(".map-style-overlay-option"));
        const syncControlUi = () => {
          container.classList.toggle("is-light", !darkMode);
          quickThemeButton.classList.toggle("is-active", darkMode);
          quickThemeButton.setAttribute("aria-pressed", darkMode ? "true" : "false");
          const icon = quickThemeButton.querySelector(".map-quick-theme-icon");
          if (icon) icon.textContent = darkMode ? "☾" : "☀";
          styleOptions.forEach((button) => {
            const isSelected = button.dataset.style === italyBaseStyle;
            button.classList.toggle("is-selected", isSelected);
            button.setAttribute("aria-checked", isSelected ? "true" : "false");
          });
          overlayOptions.forEach((button) => {
            const key = String(button.dataset.overlay || "");
            const isSelected = Boolean(italyOverlayState[key]);
            button.classList.toggle("is-selected", isSelected);
            button.setAttribute("aria-checked", isSelected ? "true" : "false");
          });
        };

        const closePanel = () => {
          container.classList.remove("is-open");
          trigger.setAttribute("aria-expanded", "false");
        };

        trigger.addEventListener("click", () => {
          const nextOpen = !container.classList.contains("is-open");
          container.classList.toggle("is-open", nextOpen);
          trigger.setAttribute("aria-expanded", nextOpen ? "true" : "false");
        });

        quickThemeButton.addEventListener("click", () => {
          darkMode = !darkMode;
          syncItalyBaseLayer();
          syncItalyOverlays(filteredItalyEvents());
          syncControlUi();
        });

        styleOptions.forEach((button) => {
          button.addEventListener("click", () => {
            const style = String(button.dataset.style || "");
            if (!style) return;
            italyBaseStyle = style;
            syncItalyBaseLayer();
            syncControlUi();
            closePanel();
          });
        });

        overlayOptions.forEach((button) => {
          button.addEventListener("click", () => {
            const key = String(button.dataset.overlay || "");
            if (!Object.prototype.hasOwnProperty.call(italyOverlayState, key)) return;
            italyOverlayState[key] = !italyOverlayState[key];
            syncItalyOverlays(filteredItalyEvents());
            syncControlUi();
          });
        });

        window.L.DomEvent.disableClickPropagation(container);
        window.L.DomEvent.disableScrollPropagation(container);
        map.on("click", closePanel);
        syncControlUi();
        return container;
      };
      styleControl.addTo(map);
    }

    const markerColor = (mag) => {
      if (mag >= 7) return "#b84dff";
      if (mag >= 6) return "#ff1f2d";
      if (mag >= 5) return "#ff7a00";
      if (mag >= 3) return "#f7d21e";
      if (mag >= 2) return "#5de4c7";
      return "#22d3ee";
    };

    const eventKey = (event) => {
      const id = String(event?.id || "").trim();
      if (id) return id;
      const ts = String(event?.event_time_ts || event?.event_time_utc || "");
      const lat = Number.isFinite(Number(event?.latitude)) ? Number(event.latitude).toFixed(3) : "na";
      const lon = Number.isFinite(Number(event?.longitude)) ? Number(event.longitude).toFixed(3) : "na";
      const mag = Number.isFinite(Number(event?.magnitude)) ? Number(event.magnitude).toFixed(1) : "na";
      return `${ts}|${lat}|${lon}|${mag}`;
    };

    const eventInBand = (event, band) => {
      const mag = Number(event?.magnitude);
      if (!Number.isFinite(mag)) return false;
      if (band === "m1-2") return mag < 2;
      if (band === "m3") return mag >= 2 && mag < 3;
      if (band === "m4") return mag >= 3 && mag < 5;
      if (band === "m6") return mag >= 5 && mag < 6;
      if (band === "m7p") return mag >= 6 && mag < 7;
      if (band === "m8p") return mag >= 7;
      return true;
    };

    const buildEventDetailUrl = (event) => {
      const params = new URLSearchParams();
      const id = String(event?.id || "").trim();
      const place = String(event?.place || "").trim();
      const time = String(event?.event_time_utc || "").trim();
      const mag = Number(event?.magnitude);
      const depth = Number(event?.depth_km);
      const lat = Number(event?.latitude);
      const lon = Number(event?.longitude);

      if (id) params.set("id", id);
      if (place) params.set("place", place);
      if (time) params.set("time", time);
      if (Number.isFinite(mag)) params.set("mag", mag.toFixed(1));
      if (Number.isFinite(depth)) params.set("depth", depth.toFixed(1));
      if (Number.isFinite(lat)) params.set("lat", lat.toFixed(3));
      if (Number.isFinite(lon)) params.set("lon", lon.toFixed(3));
      return `/event.php?${params.toString()}`;
    };

    const formatUtcShort = (value) => {
      if (!value) return "--:--";
      const date = new Date(value);
      if (Number.isNaN(date.getTime())) return "--:--";
      return date.toLocaleString("it-IT", {
        day: "2-digit",
        month: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
      });
    };

    const buildSwarmUrl = (swarmId) => {
      const params = new URLSearchParams();
      params.set("swarm", String(swarmId || ""));
      const currentParams = new URLSearchParams(window.location.search);
      const lang = currentParams.get("lang");
      if (lang) params.set("lang", lang);
      return `/data-italia-sciame.php?${params.toString()}`;
    };

    const deriveSwarmId = (row) => {
      const explicit = String(row?.swarm_id || "").trim();
      if (explicit) return explicit;
      const lat = Number(row?.center_lat);
      const lon = Number(row?.center_lon);
      if (!Number.isFinite(lat) || !Number.isFinite(lon)) return "";
      const gridLat = (Math.floor(lat * 4) / 4).toFixed(2);
      const gridLon = (Math.floor(lon * 4) / 4).toFixed(2);
      return `${gridLat}|${gridLon}`;
    };

    const setError = () => {
      if (kpiSource) kpiSource.textContent = "Italy data unavailable";
      if (feedList) feedList.innerHTML = "<li class='event-item'>Unable to load Italy live feed.</li>";
      if (swarmsList) swarmsList.innerHTML = "<li class='event-item'>Unable to load swarm candidates.</li>";
      if (story) story.textContent = "Unable to build Italy summary right now.";
      if (cues) cues.innerHTML = "<li class='event-item'>Operational cues unavailable.</li>";
      currentEvents = [];
      activeBand = null;
      syncBandVisibility([]);
    };

    let currentEvents = [];
    let activeBand = null;
    const markerByKey = new Map();

    const setBandState = (nextBand) => {
      activeBand = nextBand;
      itMapFilterButtons.forEach((button) => {
        const isActive = button.dataset.band === activeBand;
        button.classList.toggle("is-active", isActive);
        button.setAttribute("aria-pressed", isActive ? "true" : "false");
      });
    };

    const magBandCounts = (events) => {
      const counts = { "m1-2": 0, m3: 0, m4: 0, m6: 0, m7p: 0, m8p: 0 };
      events.forEach((event) => {
        if (eventInBand(event, "m1-2")) counts["m1-2"] += 1;
        else if (eventInBand(event, "m3")) counts.m3 += 1;
        else if (eventInBand(event, "m4")) counts.m4 += 1;
        else if (eventInBand(event, "m6")) counts.m6 += 1;
        else if (eventInBand(event, "m7p")) counts.m7p += 1;
        else if (eventInBand(event, "m8p")) counts.m8p += 1;
      });
      return counts;
    };

    const syncBandVisibility = (events) => {
      const counts = magBandCounts(events);
      let visibleCount = 0;
      itMapFilterButtons.forEach((button) => {
        const band = String(button.dataset.band || "");
        const count = Number(counts[band] || 0);
        const shouldShow = count > 0;
        button.hidden = !shouldShow;
        button.disabled = !shouldShow;
        button.setAttribute("aria-disabled", shouldShow ? "false" : "true");
        if (shouldShow) {
          visibleCount += 1;
          button.title = `${count} eventi`;
        } else {
          button.removeAttribute("title");
        }
      });
      if (activeBand && Number(counts[activeBand] || 0) === 0) {
        activeBand = null;
      }
      if (itMapLegend) {
        itMapLegend.hidden = visibleCount === 0;
      }
      setBandState(activeBand);
    };

    const filteredItalyEvents = () => {
      if (!activeBand) return currentEvents;
      return currentEvents.filter((event) => eventInBand(event, activeBand));
    };

    const renderItalyFeed = (events) => {
      if (!feedList) return;
      const feedRows = events
        .slice()
        .sort((a, b) => {
          const aTime = a?.event_time_utc ? new Date(a.event_time_utc).getTime() : 0;
          const bTime = b?.event_time_utc ? new Date(b.event_time_utc).getTime() : 0;
          return bTime - aTime;
        })
        .slice(0, 300);

      feedList.innerHTML = feedRows.length > 0
        ? feedRows.map((event) => {
              const mag = typeof event.magnitude === "number" ? event.magnitude : 0;
              const magLabel = typeof event.magnitude === "number" ? `M${event.magnitude.toFixed(1)}` : "M?";
              const place = escapeHtml(event.place || "Italia");
              const depth = typeof event.depth_km === "number" ? `${event.depth_km.toFixed(1)} km` : "n/a";
              const timeLabel = formatUtcShort(event.event_time_utc);
              const strongClass = mag >= 3 ? " is-strong" : "";
              return `
                <li class="event-item is-clickable${strongClass}" data-event-key="${escapeHtml(eventKey(event))}">
                  <span class="it-feed-mag" style="color:${markerColor(mag)}">${magLabel}</span>
                  <span class="it-feed-place">${place}</span>
                  <span class="it-feed-sub">${timeLabel} · ${depth}</span>
                </li>
              `;
            }).join("")
        : "<li class='event-item'>No Italy events in current feed.</li>";
    };

    const renderItalyMarkers = (events) => {
      if (!markersLayer || !window.L) return;
      markersLayer.clearLayers();
      markerByKey.clear();
      const zoom = map && typeof map.getZoom === "function" ? map.getZoom() : 6;
      const lowZoomScale = zoom <= 5.8 ? 0.88 : (zoom <= 7.2 ? 0.94 : 1);
      const lowZoom = zoom <= 5.8;
      const midZoom = zoom > 5.8 && zoom <= 7.2;
      events.slice(0, 1200).forEach((event) => {
        if (typeof event.latitude !== "number" || typeof event.longitude !== "number") return;
        const mag = typeof event.magnitude === "number" ? event.magnitude : 0;
        const magLabel = typeof event.magnitude === "number" ? `M${event.magnitude.toFixed(1)}` : "M?";
        const depth = typeof event.depth_km === "number" ? `${event.depth_km.toFixed(1)} km` : "n/a";
        const place = escapeHtml(event.place || "Unknown");
        const timeLabel = formatUtcShort(event.event_time_utc);
        const openUrl = buildEventDetailUrl(event);
        const popupHtml = `
          <div class="it-map-popup">
            <strong>${magLabel} · ${place}</strong><br />
            <span>${depth} · ${timeLabel}</span><br />
            <a href="${escapeHtml(openUrl)}">Apri evento</a>
          </div>
        `;
        const baseRadius = Math.max(2.2, Math.min(12, 2 + (mag * 1.2)));
        const radius = baseRadius * lowZoomScale;
        const strokeOpacity = lowZoom ? 0.52 : (midZoom ? 0.68 : 0.85);
        const marker = window.L.circleMarker([event.latitude, event.longitude], {
          radius,
          color: `rgba(255,255,255,${strokeOpacity})`,
          opacity: strokeOpacity,
          weight: lowZoom ? 0.7 : (midZoom ? 0.85 : 1),
          fillColor: markerColor(mag),
          fillOpacity: lowZoom ? 0.82 : (midZoom ? 0.85 : 0.88),
        })
          .bindPopup(popupHtml, { maxWidth: 320 })
          .bindTooltip(`${magLabel} · ${depth} · ${event.place || "Unknown"}`)
          .addTo(markersLayer);
        markerByKey.set(eventKey(event), { marker, event });
      });
    };

    const applyMapBandFilter = () => {
      const events = filteredItalyEvents();
      renderItalyFeed(events);
      renderItalyMarkers(events);
      syncItalyOverlays(events);
    };

    const load = async () => {
      try {
        const response = await fetch("/api/italy-earthquakes.php", { headers: { Accept: "application/json" } });
        if (!response.ok) throw new Error("Request failed");
        const payload = await response.json();
        if (!payload || payload.ok !== true) throw new Error("Payload invalid");

        const events = Array.isArray(payload.events) ? payload.events : [];
        const series7d = Array.isArray(payload.series_7d) ? payload.series_7d : [];
        const series30d = Array.isArray(payload.series_30d) ? payload.series_30d : [];
        const swarms = Array.isArray(payload.swarms_24h) ? payload.swarms_24h : [];
        const magBands = payload.magnitude_bands_24h && typeof payload.magnitude_bands_24h === "object" ? payload.magnitude_bands_24h : {};
        const depthBands = payload.depth_bands_24h && typeof payload.depth_bands_24h === "object" ? payload.depth_bands_24h : {};
        const baseline = payload.baseline && typeof payload.baseline === "object" ? payload.baseline : {};

        const maxMag = typeof payload.max_magnitude_24h === "number" ? payload.max_magnitude_24h : null;
        const energyJ = typeof payload.estimated_energy_j_24h === "number" ? payload.estimated_energy_j_24h : 0;
        const deltaPct = typeof baseline.delta_pct === "number" ? baseline.delta_pct : 0;
        const baselineState = String(baseline.state || "Within normal");
        const baselineMethod = String(baseline.method || "rolling-30d");
        const deltaLabel = `${deltaPct >= 0 ? "+" : ""}${deltaPct.toFixed(0)}%`;
        const baselineColor = deltaPct >= 35 ? "#ff5f45" : (deltaPct <= -25 ? "#22d3ee" : "#5de4c7");

        currentEvents = events;
        syncBandVisibility(currentEvents);
        applyMapBandFilter();

        if (kpiEvents) kpiEvents.textContent = String(payload.events_count_24h ?? events.length);
        if (kpiMaxMag) kpiMaxMag.textContent = maxMag !== null ? `M${maxMag.toFixed(1)}` : "--";
        if (kpiEnergy) kpiEnergy.textContent = formatEnergy(energyJ);
        if (kpiBaseline) {
          kpiBaseline.textContent = deltaLabel;
          kpiBaseline.style.color = baselineColor;
        }
        if (kpiSource) {
          const methodLabel = baselineMethod === "proxy-24h" ? "proxy baseline" : "30d baseline";
          kpiSource.textContent = `${payload.provider || "INGV"} · ${baselineState} · ${methodLabel}`;
        }

        renderVerticalChart(chart7d, series7d.map((row) => {
          const value = Number(row.count || 0);
          return {
            label: row.label || "",
            value,
            display: String(value),
            color: "#5de4c7",
          };
        }), { maxValue: 100, adaptiveThickness: true, minThickness: 18, maxThickness: 32, gapPx: 10 });

        const rows30 = series30d.map((row, index, arr) => {
          const value = Number(row.count || 0);
          return {
            label: "",
            value,
            display: "",
            color: "#5de4c7",
          };
        });
        renderVerticalChart(chart30d, rows30, { maxValue: 100, adaptiveThickness: true, minThickness: 8, maxThickness: 14, gapPx: 6 });
        renderTrend30Ticks(series30d);
        renderVerticalChart(chartMag, [
          { label: "M0-1", value: Number(magBands["M0-1"] || 0), display: String(Number(magBands["M0-1"] || 0)), color: "#22d3ee" },
          { label: "M1-2", value: Number(magBands["M1-2"] || 0), display: String(Number(magBands["M1-2"] || 0)), color: "#5de4c7" },
          { label: "M2-3", value: Number(magBands["M2-3"] || 0), display: String(Number(magBands["M2-3"] || 0)), color: "#94f1dd" },
          { label: "M3+", value: Number(magBands["M3+"] || 0), display: String(Number(magBands["M3+"] || 0)), color: "#f7d21e" },
        ]);
        renderRowsChart(chartDepth, [
          { label: "0-10 km", value: Number(depthBands["0-10"] || 0), display: String(Number(depthBands["0-10"] || 0)) },
          { label: "10-30 km", value: Number(depthBands["10-30"] || 0), display: String(Number(depthBands["10-30"] || 0)) },
          { label: "30-70 km", value: Number(depthBands["30-70"] || 0), display: String(Number(depthBands["30-70"] || 0)) },
          { label: "70+ km", value: Number(depthBands["70+"] || 0), display: String(Number(depthBands["70+"] || 0)) },
        ]);

        if (swarmsList) {
          swarmsList.innerHTML = swarms.length > 0
            ? swarms.map((row) => {
              const swarmId = deriveSwarmId(row);
              const labelRegion = row.region || "Italia";
              const maxMagValue = typeof row.max_magnitude === "number" ? row.max_magnitude : null;
              const labelMag = maxMagValue !== null ? `M${maxMagValue.toFixed(1)}` : "M?";
              const labelMagHtml = maxMagValue !== null
                ? `<span style="color:${markerColor(maxMagValue)}">${labelMag}</span>`
                : labelMag;
              const labelEvents = Number(row.events || 0);
              const labelDuration = Number(row.duration_hours || 0);
              const href = swarmId ? buildSwarmUrl(swarmId) : "";
              if (!href) {
                return `<li class="event-item"><strong>${labelRegion}</strong><br />${labelEvents} eventi · max ${labelMagHtml} · durata ${labelDuration}h</li>`;
              }
              return `<li class="event-item"><a class="it-swarm-link" href="${href}" aria-label="Apri dettaglio sciame ${labelRegion}"><strong>${labelRegion}</strong><br />${labelEvents} eventi · max ${labelMagHtml} · durata ${labelDuration}h<br /><span>Apri scheda sciame</span></a></li>`;
            }).join("")
            : "<li class='event-item'>Nessun sciame evidente nelle ultime 24h.</li>";
        }

        if (story) {
          const topRegion = payload.top_region_24h || "Unknown";
          const baselineText = baselineMethod === "proxy-24h" ? "baseline proxy (storico non completo)" : "baseline 30 giorni";
          story.textContent = `Italia 24h: ${payload.events_count_24h ?? events.length} eventi, max ${maxMag !== null ? `M${maxMag.toFixed(1)}` : "n/a"}, energia ${formatEnergy(energyJ)}. Area più attiva: ${topRegion}. Baseline: ${baselineState.toLowerCase()} (${baselineText}).`;
        }

        if (cues) {
          const items = [];
          if (deltaPct >= 35) items.push("Attività sopra baseline: aumentare frequenza monitoraggio locale.");
          else if (deltaPct <= -25) items.push("Attività sotto baseline: mantenere monitoraggio ordinario.");
          else items.push("Attività in linea con baseline: monitoraggio regolare.");
          if (swarms.length > 0) items.push("Sono presenti cluster locali: verificare evoluzione area per area.");
          if (maxMag !== null && maxMag >= 4.0) items.push("Evento significativo rilevato: monitorare eventuale sequenza successiva.");
          cues.innerHTML = items.map((row) => `<li class="event-item">${row}</li>`).join("");
        }

      } catch (error) {
        setError();
      }
    };

    itMapFilterButtons.forEach((button) => {
      button.addEventListener("click", () => {
        if (button.disabled) return;
        const band = String(button.dataset.band || "");
        const nextBand = activeBand === band ? null : band;
        setBandState(nextBand);
        applyMapBandFilter();
      });
    });

    feedList?.addEventListener("click", (event) => {
      const target = event.target;
      if (!(target instanceof HTMLElement)) return;
      const row = target.closest(".event-item[data-event-key]");
      if (!row) return;
      const key = row.getAttribute("data-event-key");
      if (!key || !map) return;
      const bound = markerByKey.get(key);
      if (!bound || !bound.event) return;
      const lat = Number(bound.event.latitude);
      const lon = Number(bound.event.longitude);
      if (!Number.isFinite(lat) || !Number.isFinite(lon)) return;
      map.flyTo([lat, lon], 9, { duration: 0.45 });
      bound.marker.openPopup();
    });

    load();
    window.setInterval(() => {
      if (document.hidden) return;
      void load();
    }, 60 * 1000);
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
