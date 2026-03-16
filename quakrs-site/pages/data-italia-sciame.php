<?php
declare(strict_types=1);

$swarmId = isset($_GET['swarm']) ? trim((string) $_GET['swarm']) : '';
$pageTitle = 'Quakrs.com - Sciame Italia';
$pageDescription = 'Dettaglio sciame Italia con mappa dedicata e grafici operativi.';
$currentPage = 'data-italia-sciame';
$includeLeaflet = true;

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>
<style>
  #swarm-title {
    max-width: none;
    font-size: clamp(2.75rem, 4.9vw, 4.15rem);
    line-height: 0.94;
    letter-spacing: -0.05em;
  }

  .swarm-highlight-label {
    margin: 0 0 0.62rem;
    color: var(--yellow);
    letter-spacing: 0.09em;
    text-transform: uppercase;
    font: 700 0.82rem/1 "Space Grotesk", sans-serif;
  }

  .swarm-map-card .map-wrap {
    min-height: 36rem;
  }

  .swarm-map-card .world-map-leaflet {
    min-height: 36rem;
  }

  .swarm-map-legend span {
    color: #0d121a;
    font-weight: 700;
  }

  .swarm-map-legend span:last-child {
    color: #fff;
  }

  .swarm-mini-note {
    color: var(--muted);
    margin-top: 0.36rem;
    font-size: 0.76rem;
  }

  .swarm-back {
    display: inline-flex;
    margin-top: 0.7rem;
  }

  .swarm-empty {
    color: var(--muted);
  }

  .swarm-sidebar {
    display: grid;
    grid-template-rows: auto minmax(0, 1fr);
    gap: 0.9rem;
  }

  .swarm-list-scroll {
    max-height: 30rem;
    overflow: auto;
  }
</style>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Data / Italia / Sciame</p>
    <p id="swarm-highlight-label" class="swarm-highlight-label">Sciame in evidenza</p>
    <h1 id="swarm-title">Dettaglio cluster locale.</h1>
    <p id="swarm-sub" class="sub">Mappa dedicata, concentrazione eventi e trend utili per monitoraggio operativo.</p>
    <a class="btn btn-ghost swarm-back" href="<?= htmlspecialchars(qk_localized_url('/data-italia.php'), ENT_QUOTES, 'UTF-8'); ?>">Torna a Data Italia</a>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Events (24h)</p>
    <p id="swarm-kpi-24h" class="kpi-value">--</p>
    <p class="kpi-note">Nel cluster selezionato</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Events (7d)</p>
    <p id="swarm-kpi-7d" class="kpi-value">--</p>
    <p class="kpi-note">Persistenza breve periodo</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Max magnitude</p>
    <p id="swarm-kpi-mag" class="kpi-value">--</p>
    <p class="kpi-note">Picco ultime 24h</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Avg depth (24h)</p>
    <p id="swarm-kpi-depth" class="kpi-value">--</p>
    <p class="kpi-note">Media ipocentrale</p>
  </article>
</section>

<section class="panel panel-main">
  <article class="card map-card swarm-map-card">
    <div class="feed-head">
      <div class="map-head-left">
        <h3>Mappa sciame</h3>
        <button id="swarm-theme-toggle" class="map-mini-toggle is-active" type="button" aria-pressed="true" aria-label="Disattiva modalita notturna" title="Disattiva modalita notturna">☾</button>
      </div>
      <p id="swarm-map-meta" class="feed-meta">Caricamento in corso...</p>
    </div>
    <div class="map-wrap">
      <div id="swarm-map" class="world-map-leaflet" aria-label="Mappa dettaglio sciame"></div>
    </div>
    <div class="map-legend swarm-map-legend">
      <span style="background:#22d3ee">M &lt; 2</span>
      <span style="background:#5de4c7">M 2-3</span>
      <span style="background:#f7d21e">M 3-4</span>
      <span style="background:#ff5f45">M 4+</span>
    </div>
    <p id="swarm-window" class="swarm-mini-note">Intervallo: --</p>
  </article>

  <article class="card side-card swarm-sidebar">
    <section>
      <h3>Indicatori rapidi</h3>
      <ul id="swarm-cues" class="events-list">
        <li class="event-item">Calcolo indicatori...</li>
      </ul>
    </section>
    <section>
      <h3>Eventi recenti del cluster</h3>
      <ul id="swarm-events" class="events-list swarm-list-scroll">
        <li class="event-item">Caricamento eventi...</li>
      </ul>
    </section>
  </article>
</section>

<section class="panel panel-charts">
  <article class="card">
    <div class="feed-head">
      <h3>Trend 14 days (cluster)</h3>
      <p class="feed-meta">Conteggio giornaliero nello stesso grid-cell</p>
    </div>
    <div id="swarm-chart-14d" class="bars-vertical"></div>
  </article>
  <article class="card">
    <div class="feed-head">
      <h3>Hourly pattern (24h)</h3>
      <p class="feed-meta">Distribuzione UTC 00-23</p>
    </div>
    <div id="swarm-chart-hourly" class="bars-vertical"></div>
  </article>
  <article class="card">
    <div class="feed-head">
      <h3>Magnitude mix (24h)</h3>
      <p class="feed-meta">M0-1, M1-2, M2-3, M3+</p>
    </div>
    <div id="swarm-chart-mag" class="bars-vertical"></div>
  </article>
</section>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Depth profile (24h)</h3>
    <div id="swarm-chart-depth" class="bars"></div>
  </article>
  <article class="card page-card">
    <h3>Strongest event</h3>
    <p id="swarm-strongest" class="insight-lead">Caricamento evento massimo...</p>
  </article>
  <article class="card page-card">
    <h3>Operator story</h3>
    <p id="swarm-story" class="insight-lead">Costruzione briefing locale...</p>
  </article>
</section>

<script>
  (() => {
    const swarmId = <?= json_encode($swarmId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

    const els = {
      highlight: document.querySelector("#swarm-highlight-label"),
      title: document.querySelector("#swarm-title"),
      sub: document.querySelector("#swarm-sub"),
      mapMeta: document.querySelector("#swarm-map-meta"),
      window: document.querySelector("#swarm-window"),
      kpi24h: document.querySelector("#swarm-kpi-24h"),
      kpi7d: document.querySelector("#swarm-kpi-7d"),
      kpiMag: document.querySelector("#swarm-kpi-mag"),
      kpiDepth: document.querySelector("#swarm-kpi-depth"),
      cues: document.querySelector("#swarm-cues"),
      events: document.querySelector("#swarm-events"),
      chart14d: document.querySelector("#swarm-chart-14d"),
      chartHourly: document.querySelector("#swarm-chart-hourly"),
      chartMag: document.querySelector("#swarm-chart-mag"),
      chartDepth: document.querySelector("#swarm-chart-depth"),
      strongest: document.querySelector("#swarm-strongest"),
      story: document.querySelector("#swarm-story"),
      map: document.querySelector("#swarm-map"),
      toggle: document.querySelector("#swarm-theme-toggle"),
    };

    const formatUtc = (raw) => {
      if (!raw) return "n/a";
      const d = new Date(String(raw));
      if (Number.isNaN(d.getTime())) return "n/a";
      return d.toISOString().replace("T", " ").slice(0, 16) + " UTC";
    };

    const markerColor = (mag) => {
      if (mag >= 4) return "#ff5f45";
      if (mag >= 3) return "#f7d21e";
      if (mag >= 2) return "#5de4c7";
      return "#22d3ee";
    };

    const renderVerticalChart = (container, rows, options = {}) => {
      if (!container) return;
      const forcedMax = Number(options.maxValue || 0);
      const maxValue = forcedMax > 0 ? forcedMax : (rows.reduce((max, row) => Math.max(max, Number(row.value || 0)), 0) || 1);
      container.style.setProperty("--bar-count", String(rows.length));
      container.style.setProperty("--bar-thickness", String(options.thickness || 16) + "px");
      container.innerHTML = rows.map((row) => {
        const value = Number(row.value || 0);
        const clamped = Math.max(0, Math.min(maxValue, value));
        const height = Math.max(4, Math.round((clamped / maxValue) * 100));
        return `
          <div class="bar-col">
            <div class="bar-col-value">${row.display ?? String(value)}</div>
            <div class="bar-col-track"><div class="bar-col-fill" style="height:${height}%;background:${row.color || "#5de4c7"}"></div></div>
            <div class="bar-col-label">${row.label || ""}</div>
          </div>
        `;
      }).join("");
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

    const map = els.map && window.L
      ? window.L.map(els.map, {
          zoomControl: true,
          maxBounds: [[35.0, 6.0], [48.8, 19.6]],
          maxBoundsViscosity: 1.0,
          maxZoom: 14,
          minZoom: 4,
        }).setView([42.5, 12.5], 7.2)
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
      if (els.toggle) {
        els.toggle.classList.toggle("is-active", darkMode);
        els.toggle.textContent = darkMode ? "☾" : "☀";
        els.toggle.setAttribute("aria-pressed", darkMode ? "true" : "false");
      }
    };

    els.toggle?.addEventListener("click", () => {
      darkMode = !darkMode;
      applyTheme();
    });

    const setError = (message) => {
      if (els.sub) els.sub.textContent = message;
      if (els.events) els.events.innerHTML = `<li class=\"event-item swarm-empty\">${message}</li>`;
      if (els.cues) els.cues.innerHTML = "<li class='event-item'>Nessun dato disponibile.</li>";
    };

    const load = async () => {
      if (!swarmId) {
        setError("ID sciame non presente nella URL.");
        return;
      }
      try {
        const response = await fetch(`/api/italy-earthquakes.php?swarm=${encodeURIComponent(swarmId)}`, {
          headers: { Accept: "application/json" },
        });
        if (!response.ok) throw new Error("Request failed");
        const payload = await response.json();
        const detail = payload && payload.swarm_detail && typeof payload.swarm_detail === "object" ? payload.swarm_detail : null;
        if (!detail) {
          setError("Sciame non trovato (potrebbe essere uscito dalla finestra dati corrente).");
          return;
        }

        const events = Array.isArray(detail.events) ? detail.events : [];
        const hourly = Array.isArray(detail.hourly_24h) ? detail.hourly_24h : [];
        const series14d = Array.isArray(detail.series_14d) ? detail.series_14d : [];
        const depthBands = detail.depth_bands_24h && typeof detail.depth_bands_24h === "object" ? detail.depth_bands_24h : {};
        const magBands = detail.magnitude_bands_24h && typeof detail.magnitude_bands_24h === "object" ? detail.magnitude_bands_24h : {};

        if (els.highlight) els.highlight.textContent = "Sciame in evidenza";
        if (els.title) els.title.textContent = String(detail.region || "Italia");
        if (els.sub) els.sub.textContent = `Grid ${detail.swarm_id || swarmId} · concentrazione ultimi 30 giorni con focus operativo 24h.`;
        if (els.mapMeta) els.mapMeta.textContent = `${events.length} eventi mappati (finestra cluster 30d)`;
        if (els.window) els.window.textContent = `Intervallo cluster: ${formatUtc(detail.first_event_utc)} -> ${formatUtc(detail.last_event_utc)}`;

        if (els.kpi24h) els.kpi24h.textContent = String(Number(detail.events_24h || 0));
        if (els.kpi7d) els.kpi7d.textContent = String(Number(detail.events_7d || 0));
        if (els.kpiMag) {
          const mag = typeof detail.max_magnitude_24h === "number" ? `M${detail.max_magnitude_24h.toFixed(1)}` : "--";
          els.kpiMag.textContent = mag;
        }
        if (els.kpiDepth) {
          const depth = typeof detail.avg_depth_km_24h === "number" ? `${detail.avg_depth_km_24h.toFixed(1)} km` : "--";
          els.kpiDepth.textContent = depth;
        }

        renderVerticalChart(els.chart14d, series14d.map((row) => ({
          label: row.date_utc ? String(row.date_utc).slice(5) : "",
          value: Number(row.count || 0),
          display: String(Number(row.count || 0)),
          color: "#5de4c7",
        })), { thickness: 13 });

        renderVerticalChart(els.chartHourly, hourly.map((row) => ({
          label: `${String(row.hour_utc || "00")}`,
          value: Number(row.count || 0),
          display: String(Number(row.count || 0)),
          color: "#22d3ee",
        })), { thickness: 9 });

        renderVerticalChart(els.chartMag, [
          { label: "M0-1", value: Number(magBands["M0-1"] || 0), display: String(Number(magBands["M0-1"] || 0)), color: "#22d3ee" },
          { label: "M1-2", value: Number(magBands["M1-2"] || 0), display: String(Number(magBands["M1-2"] || 0)), color: "#5de4c7" },
          { label: "M2-3", value: Number(magBands["M2-3"] || 0), display: String(Number(magBands["M2-3"] || 0)), color: "#94f1dd" },
          { label: "M3+", value: Number(magBands["M3+"] || 0), display: String(Number(magBands["M3+"] || 0)), color: "#f7d21e" },
        ], { thickness: 20 });

        renderRowsChart(els.chartDepth, [
          { label: "0-10 km", value: Number(depthBands["0-10"] || 0), display: String(Number(depthBands["0-10"] || 0)) },
          { label: "10-30 km", value: Number(depthBands["10-30"] || 0), display: String(Number(depthBands["10-30"] || 0)) },
          { label: "30-70 km", value: Number(depthBands["30-70"] || 0), display: String(Number(depthBands["30-70"] || 0)) },
          { label: "70+ km", value: Number(depthBands["70+"] || 0), display: String(Number(depthBands["70+"] || 0)) },
        ]);

        if (els.events) {
          els.events.innerHTML = events.length > 0
            ? events.slice(0, 100).map((event) => {
              const mag = typeof event.magnitude === "number" ? `M${event.magnitude.toFixed(1)}` : "M?";
              const depth = typeof event.depth_km === "number" ? `${event.depth_km.toFixed(1)} km` : "n/a";
              return `<li class=\"event-item\"><strong>${mag}</strong> · ${depth}<br />${event.place || "Unknown"}<br /><span class=\"swarm-mini-note\">${formatUtc(event.event_time_utc)}</span></li>`;
            }).join("")
            : "<li class='event-item'>Nessun evento disponibile.</li>";
        }

        const cueRows = [];
        if (Number(detail.events_24h || 0) >= 12) cueRows.push("Concentrazione elevata 24h: mantenere monitoraggio stretto locale.");
        else if (Number(detail.events_24h || 0) >= 6) cueRows.push("Concentrazione moderata: osservare eventuale accelerazione nella prossima ora.");
        else cueRows.push("Cluster in fase debole: sorveglianza ordinaria.");
        if (typeof detail.max_magnitude_24h === "number" && detail.max_magnitude_24h >= 3.5) cueRows.push("Picco magnitudo rilevante nel cluster: validare sequenza successiva.");
        if (typeof detail.avg_depth_km_24h === "number" && detail.avg_depth_km_24h < 10) cueRows.push("Ipocentri superficiali prevalenti nelle ultime 24h.");
        if (els.cues) els.cues.innerHTML = cueRows.map((row) => `<li class=\"event-item\">${row}</li>`).join("");

        const strongest = detail.strongest_event && typeof detail.strongest_event === "object" ? detail.strongest_event : null;
        if (els.strongest) {
          if (strongest) {
            const mag = typeof strongest.magnitude === "number" ? `M${strongest.magnitude.toFixed(1)}` : "M?";
            const depth = typeof strongest.depth_km === "number" ? `${strongest.depth_km.toFixed(1)} km` : "n/a";
            els.strongest.textContent = `${mag} · ${depth} · ${strongest.place || "Unknown"} · ${formatUtc(strongest.event_time_utc)}`;
          } else {
            els.strongest.textContent = "Nessun evento massimo disponibile.";
          }
        }

        if (els.story) {
          const max30 = typeof detail.max_magnitude_30d === "number" ? `M${detail.max_magnitude_30d.toFixed(1)}` : "n/a";
          els.story.textContent = `Cluster ${detail.swarm_id || swarmId}: ${Number(detail.events_30d || 0)} eventi in 30 giorni, ${Number(detail.events_24h || 0)} nelle ultime 24h, picco storico locale ${max30}.`; 
        }

        if (map && markersLayer && window.L) {
          markersLayer.clearLayers();

          const latlngs = [];
          events.forEach((event, idx) => {
            if (typeof event.latitude !== "number" || typeof event.longitude !== "number") return;
            const mag = typeof event.magnitude === "number" ? event.magnitude : 0;
            const depth = typeof event.depth_km === "number" ? `${event.depth_km.toFixed(1)} km` : "n/a";
            const radius = Math.max(2.2, Math.min(11, 2 + (mag * 1.3)));
            const opacity = idx < 120 ? 0.92 : 0.62;
            latlngs.push([event.latitude, event.longitude]);
            window.L.circleMarker([event.latitude, event.longitude], {
              radius,
              color: "rgba(255,255,255,0.85)",
              weight: 1,
              fillColor: markerColor(mag),
              fillOpacity: opacity,
            })
              .bindTooltip(`M${mag.toFixed(1)} · ${depth} · ${event.place || "Unknown"}`)
              .addTo(markersLayer);
          });

          if (latlngs.length > 1) {
            map.fitBounds(window.L.latLngBounds(latlngs), { padding: [14, 14], maxZoom: 12 });
          } else if (latlngs.length === 1) {
            map.setView(latlngs[0], 10.2);
          }
        }
      } catch (error) {
        setError("Errore nel caricamento del dettaglio sciame.");
      }
    };

    applyTheme();
    load();
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
