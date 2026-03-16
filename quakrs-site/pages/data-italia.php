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

  .it-map-legend span:last-child {
    color: #ffffff;
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
</style>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Data / Italia</p>
    <h1>Italia seismic monitor.</h1>
    <p class="sub">Vista dedicata Italia con dettaglio locale, trend 7g/30g e aree in possibile attivazione di sciame.</p>
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

<section class="panel panel-main">
  <article class="card map-card">
    <div class="feed-head">
      <div class="map-head-left">
        <h3>Italy map (INGV)</h3>
        <button id="it-theme-toggle" class="map-mini-toggle" type="button" aria-pressed="false" aria-label="Attiva modalita notturna" title="Attiva modalita notturna">☀</button>
      </div>
      <p class="feed-meta">Solo territorio italiano · aggiornamento orario</p>
    </div>
    <div class="map-wrap">
      <div id="it-map-leaflet" class="world-map-leaflet" aria-label="Italy seismic map"></div>
    </div>
    <div class="map-legend it-map-legend">
      <span style="background:#22d3ee">M &lt; 2</span>
      <span style="background:#5de4c7">M 2-3</span>
      <span style="background:#f7d21e">M 3-4</span>
      <span style="background:#ff5f45">M 4+</span>
    </div>
  </article>
  <article class="card side-card">
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
    const chart7d = document.querySelector("#it-chart-7d");
    const chart30d = document.querySelector("#it-chart-30d");
    const chart30dDates = document.querySelector("#it-chart-30d-dates");
    const chartMag = document.querySelector("#it-chart-mag");
    const chartDepth = document.querySelector("#it-chart-depth");
    const story = document.querySelector("#it-story");
    const cues = document.querySelector("#it-cues");
    const mapContainer = document.querySelector("#it-map-leaflet");
    const themeToggle = document.querySelector("#it-theme-toggle");

    const formatEnergy = (joules) => {
      if (!Number.isFinite(joules) || joules <= 0) return "--";
      if (joules >= 1e15) return `${(joules / 1e15).toFixed(2)} PJ`;
      if (joules >= 1e12) return `${(joules / 1e12).toFixed(2)} TJ`;
      if (joules >= 1e9) return `${(joules / 1e9).toFixed(2)} GJ`;
      return `${joules.toExponential(2)} J`;
    };

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

    const map = mapContainer && window.L
      ? window.L.map(mapContainer, {
          zoomControl: true,
          maxBounds: [[35.0, 6.0], [48.8, 19.6]],
          maxBoundsViscosity: 1.0,
          maxZoom: 13,
          minZoom: 4,
        }).setView([42.5, 12.5], 5.8)
      : null;
    const markersLayer = map && window.L ? window.L.layerGroup().addTo(map) : null;
    const lightTiles = map && window.L
      ? window.L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
          maxZoom: 13,
          minZoom: 4,
          attribution: "&copy; OpenStreetMap contributors",
        })
      : null;
    const darkTiles = map && window.L
      ? window.L.tileLayer("https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png", {
          maxZoom: 13,
          minZoom: 4,
          attribution: "&copy; OpenStreetMap contributors &copy; CARTO",
        })
      : null;
    let darkMode = true;

    const applyTheme = () => {
      if (!map || !lightTiles || !darkTiles) return;
      if (darkMode) {
        if (map.hasLayer(lightTiles)) map.removeLayer(lightTiles);
        if (!map.hasLayer(darkTiles)) darkTiles.addTo(map);
      } else {
        if (map.hasLayer(darkTiles)) map.removeLayer(darkTiles);
        if (!map.hasLayer(lightTiles)) lightTiles.addTo(map);
      }
      if (themeToggle) {
        themeToggle.classList.toggle("is-active", darkMode);
        themeToggle.textContent = darkMode ? "☾" : "☀";
      }
    };
    if (map && lightTiles) lightTiles.addTo(map);
    themeToggle?.addEventListener("click", () => {
      darkMode = !darkMode;
      applyTheme();
    });

    const markerColor = (mag) => {
      if (mag >= 4) return "#ff5f45";
      if (mag >= 3) return "#f7d21e";
      if (mag >= 2) return "#5de4c7";
      return "#22d3ee";
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
      if (swarmsList) swarmsList.innerHTML = "<li class='event-item'>Unable to load swarm candidates.</li>";
      if (story) story.textContent = "Unable to build Italy summary right now.";
      if (cues) cues.innerHTML = "<li class='event-item'>Operational cues unavailable.</li>";
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

        if (kpiEvents) kpiEvents.textContent = String(payload.events_count_24h ?? events.length);
        if (kpiMaxMag) kpiMaxMag.textContent = maxMag !== null ? `M${maxMag.toFixed(1)}` : "--";
        if (kpiEnergy) kpiEnergy.textContent = formatEnergy(energyJ);
        if (kpiBaseline) {
          kpiBaseline.textContent = deltaLabel;
          kpiBaseline.style.color = baselineColor;
        }
        if (kpiSource) {
          const methodLabel = baselineMethod === "proxy-24h" ? "proxy baseline" : "30d baseline";
          kpiSource.textContent = `${payload.provider || "INGV"} · ${baselineState} · ${methodLabel}${payload.from_cache ? " (cache)" : ""}`;
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
              const labelMag = typeof row.max_magnitude === "number" ? `M${row.max_magnitude.toFixed(1)}` : "M?";
              const labelEvents = Number(row.events || 0);
              const labelDuration = Number(row.duration_hours || 0);
              const href = swarmId ? buildSwarmUrl(swarmId) : "";
              if (!href) {
                return `<li class="event-item"><strong>${labelRegion}</strong><br />${labelEvents} eventi · max ${labelMag} · durata ${labelDuration}h</li>`;
              }
              return `<li class="event-item"><a class="it-swarm-link" href="${href}" aria-label="Apri dettaglio sciame ${labelRegion}"><strong>${labelRegion}</strong><br />${labelEvents} eventi · max ${labelMag} · durata ${labelDuration}h<br /><span>Apri scheda sciame</span></a></li>`;
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

        if (markersLayer && window.L) {
          markersLayer.clearLayers();
          events.slice(0, 1200).forEach((event) => {
            if (typeof event.latitude !== "number" || typeof event.longitude !== "number") return;
            const mag = typeof event.magnitude === "number" ? event.magnitude : 0;
            const depth = typeof event.depth_km === "number" ? `${event.depth_km.toFixed(1)} km` : "n/a";
            const radius = Math.max(2.2, Math.min(12, 2 + (mag * 1.2)));
            window.L.circleMarker([event.latitude, event.longitude], {
              radius,
              color: "rgba(255,255,255,0.85)",
              weight: 1,
              fillColor: markerColor(mag),
              fillOpacity: 0.88,
            })
              .bindTooltip(`M${mag.toFixed(1)} · ${depth} · ${event.place || "Unknown"}`)
              .addTo(markersLayer);
          });
        }
      } catch (error) {
        setError();
      }
    };

    applyTheme();
    load();
    window.setInterval(() => {
      if (document.hidden) return;
      void load();
    }, 60 * 1000);
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
