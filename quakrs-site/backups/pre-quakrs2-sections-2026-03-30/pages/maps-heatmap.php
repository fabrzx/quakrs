<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Maps Heatmap';
$pageDescription = 'Event-density heatmap view built from the last 24h global earthquake feed.';
$currentPage = 'maps-heatmap';
$includeLeaflet = true;

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.maps_heatmap.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.maps_heatmap.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.maps_heatmap.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Events (24h)</p>
    <p id="heat-kpi-total" class="kpi-value">--</p>
    <p class="kpi-note">Input events in heatmap</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Active Cells</p>
    <p id="heat-kpi-cells" class="kpi-value">--</p>
    <p class="kpi-note">Current active grid</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Hottest Cell</p>
    <p id="heat-kpi-hottest" class="kpi-value">--</p>
    <p class="kpi-note">Max events in one cell</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Last Update</p>
    <p id="heat-kpi-updated" class="kpi-value">--</p>
    <p id="heat-kpi-source" class="kpi-note">Loading source...</p>
  </article>
</section>

<section class="panel panel-main">
  <article class="card map-card">
    <div class="feed-head">
      <div class="map-head-left">
        <h3>Heatmap Layer</h3>
        <button id="heat-theme-toggle" class="map-mini-toggle" type="button" aria-pressed="true" aria-label="Disattiva modalita notturna" title="Disattiva modalita notturna">☾</button>
      </div>
      <p class="feed-meta">Cells + optional event markers</p>
    </div>
    <div class="map-wrap">
      <div id="heatmap-leaflet" class="world-map-leaflet" aria-label="Heatmap map"></div>
    </div>
    <div class="map-legend">
      <button class="map-filter-btn band-m3" data-grid="2" type="button" aria-pressed="false">Grid 2°</button>
      <button class="map-filter-btn band-m4" data-grid="5" type="button" aria-pressed="true">Grid 5°</button>
      <button class="map-filter-btn band-m5" data-grid="10" type="button" aria-pressed="false">Grid 10°</button>
      <button class="map-filter-btn band-m6" data-overlay="events" type="button" aria-pressed="true">Events ON</button>
      <span class="map-legend-swatch map-legend-heat-low">Low</span>
      <span class="map-legend-swatch map-legend-heat-high">High</span>
    </div>
  </article>
  <article class="card side-card map-side-list-card">
    <h3>Top Heat Cells</h3>
    <ul id="heat-cells-list" class="events-list map-side-list-scroll">
      <li class="event-item">Loading heat cells...</li>
    </ul>
  </article>
</section>

<script>
  (() => {
    const kpiTotal = document.querySelector("#heat-kpi-total");
    const kpiCells = document.querySelector("#heat-kpi-cells");
    const kpiHottest = document.querySelector("#heat-kpi-hottest");
    const kpiUpdated = document.querySelector("#heat-kpi-updated");
    const kpiSource = document.querySelector("#heat-kpi-source");
    const cellsList = document.querySelector("#heat-cells-list");
    const mapContainer = document.querySelector("#heatmap-leaflet");
    const themeToggle = document.querySelector("#heat-theme-toggle");
    const gridButtons = Array.from(document.querySelectorAll("[data-grid]"));
    const overlayButton = document.querySelector("[data-overlay='events']");

    let cellSize = 5;
    let showEventsOverlay = true;
    let cachedPayload = null;
    const rootStyles = window.getComputedStyle(document.documentElement);
    const cssVar = (name, fallback) => {
      const value = rootStyles.getPropertyValue(name);
      return value ? value.trim() : fallback;
    };
    const palette = {
      base: cssVar("--acid-cyan", "#20e0ff"),
      low: cssVar("--info-acid", "#39d5ff"),
      mild: cssVar("--success-acid", "#7dff3a"),
      moderate: cssVar("--warning-acid", "#ffd400"),
      elevated: cssVar("--acid-orange", "#ff7a00"),
      high: cssVar("--danger-acid", "#ff4d6d"),
      markerFill: cssVar("--bg-2", "#0d1630"),
    };

    const intensityColor = (ratio) => {
      const clamped = Math.max(0, Math.min(1, ratio));
      if (clamped > 0.86) return palette.high;
      if (clamped > 0.68) return palette.elevated;
      if (clamped > 0.5) return palette.moderate;
      if (clamped > 0.34) return palette.mild;
      if (clamped > 0.2) return palette.low;
      return palette.base;
    };

    const map = mapContainer && window.L
      ? window.L.map(mapContainer, { zoomControl: true, worldCopyJump: true }).setView([14, 10], 2)
      : null;

    const cellLayer = window.L && map ? window.L.layerGroup().addTo(map) : null;
    const eventsLayer = window.L && map ? window.L.layerGroup().addTo(map) : null;

    const lightTiles = map && window.L
      ? window.L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
          maxZoom: 7,
          minZoom: 2,
          attribution: "&copy; OpenStreetMap contributors",
        })
      : null;
    const darkTiles = map && window.L
      ? window.L.tileLayer("https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png", {
          maxZoom: 7,
          minZoom: 2,
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
        themeToggle.setAttribute("aria-pressed", darkMode ? "true" : "false");
        themeToggle.textContent = darkMode ? "☾" : "☀";
        const label = darkMode ? "Disattiva modalita notturna" : "Attiva modalita notturna";
        themeToggle.setAttribute("aria-label", label);
        themeToggle.setAttribute("title", label);
      }
    };

    if (map && darkTiles) {
      darkTiles.addTo(map);
    }

    const updateControlsState = () => {
      gridButtons.forEach((button) => {
        const selected = Number(button.dataset.grid) === cellSize;
        button.classList.toggle("is-active", selected);
        button.setAttribute("aria-pressed", selected ? "true" : "false");
      });
      if (overlayButton) {
        overlayButton.classList.toggle("is-active", showEventsOverlay);
        overlayButton.setAttribute("aria-pressed", showEventsOverlay ? "true" : "false");
      }
    };

    const setError = () => {
      if (cellsList) cellsList.innerHTML = "<li class='event-item'>Heatmap unavailable right now.</li>";
      if (kpiSource) kpiSource.textContent = "Source unavailable";
    };

    const buildCells = (events) => {
      const cells = new Map();
      events.forEach((event) => {
        if (typeof event.latitude !== "number" || typeof event.longitude !== "number") return;
        const latMin = Math.floor(event.latitude / cellSize) * cellSize;
        const lonMin = Math.floor(event.longitude / cellSize) * cellSize;
        const key = `${latMin},${lonMin}`;
        const current = cells.get(key) || { latMin, lonMin, count: 0, strongest: 0 };
        current.count += 1;
        if (typeof event.magnitude === "number" && event.magnitude > current.strongest) {
          current.strongest = event.magnitude;
        }
        cells.set(key, current);
      });
      return [...cells.values()].sort((a, b) => b.count - a.count);
    };

    const renderMap = (events, sortedCells) => {
      if (!map || !cellLayer || !eventsLayer) return;
      cellLayer.clearLayers();
      eventsLayer.clearLayers();

      const maxCount = sortedCells[0] ? sortedCells[0].count : 1;
      const zoom = map ? map.getZoom() : 2;
      const cellStroke = zoom >= 6 ? 1.8 : (zoom >= 5 ? 1.5 : (zoom >= 4 ? 1.25 : 1));
      sortedCells.forEach((cell) => {
        const ratio = maxCount > 0 ? cell.count / maxCount : 0;
        const color = intensityColor(ratio);
        const bounds = [
          [cell.latMin, cell.lonMin],
          [cell.latMin + cellSize, cell.lonMin + cellSize],
        ];

        window.L.rectangle(bounds, {
          color,
          weight: cellStroke,
          fillColor: color,
          fillOpacity: 0.2 + ratio * 0.55,
        })
          .bindTooltip(`${cell.count} events · strongest M${cell.strongest.toFixed(1)}`)
          .addTo(cellLayer);
      });

      if (!showEventsOverlay) return;

      const zoomBoost = Math.max(0, (zoom - 2) * 0.45);
      const lowZoomScale = zoom <= 2.2 ? 0.82 : (zoom <= 3 ? 0.9 : 1);
      const lowZoom = zoom <= 2.2;
      const midZoom = zoom > 2.2 && zoom <= 3;
      events.slice(0, 500).forEach((event) => {
        if (typeof event.latitude !== "number" || typeof event.longitude !== "number") return;
        const mag = typeof event.magnitude === "number" ? event.magnitude : 0;
        const baseRadius = Math.max(2.2, Math.min(7.4, 2.2 + mag * 0.62));
        const radius = Math.min(11, (baseRadius + zoomBoost) * lowZoomScale);
        const strokeOpacity = lowZoom ? 0.42 : (midZoom ? 0.62 : 0.85);
        window.L.circleMarker([event.latitude, event.longitude], {
          radius,
          color: `rgba(255,255,255,${strokeOpacity})`,
          opacity: strokeOpacity,
          weight: lowZoom ? 0.55 : (midZoom ? 0.75 : 1),
          fillColor: palette.markerFill,
          fillOpacity: lowZoom ? 0.2 : (midZoom ? 0.22 : 0.25),
        })
          .bindTooltip(`M${mag.toFixed(1)} - ${event.place || "Unknown"}`)
          .addTo(eventsLayer);
      });
    };

    const renderFromPayload = (payload) => {
      const events = Array.isArray(payload.events) ? payload.events : [];
      const sortedCells = buildCells(events);
      const hottest = sortedCells[0] || null;

      if (kpiTotal) kpiTotal.textContent = String(events.length);
      if (kpiCells) kpiCells.textContent = String(sortedCells.length);
      if (kpiHottest) kpiHottest.textContent = hottest ? `${hottest.count}` : "--";
      if (kpiUpdated) {
        kpiUpdated.textContent = payload.generated_at
          ? new Date(payload.generated_at).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
          : "--";
      }
      if (kpiSource) {
        const provider = Array.isArray(payload.providers) && payload.providers.length > 0
          ? payload.providers.join(" + ")
          : (payload.provider || "Quakrs API");
        kpiSource.textContent = `Source: ${provider}`;
      }

      if (cellsList) {
        cellsList.innerHTML = sortedCells.slice(0, 12).map((cell) => {
          const label = `Lat ${cell.latMin}..${cell.latMin + cellSize}, Lon ${cell.lonMin}..${cell.lonMin + cellSize}`;
          return `<li class="event-item"><strong>${label}</strong><br /><span>${cell.count} events · strongest M${cell.strongest.toFixed(1)}</span></li>`;
        }).join("") || "<li class='event-item'>No cells available.</li>";
      }

      renderMap(events, sortedCells);
    };

    const fetchData = async () => {
      const response = await fetch("/api/earthquakes.php", { headers: { Accept: "application/json" } });
      if (!response.ok) throw new Error("Request failed");
      cachedPayload = await response.json();
      renderFromPayload(cachedPayload);
    };

    gridButtons.forEach((button) => {
      button.addEventListener("click", () => {
        const value = Number(button.dataset.grid);
        if (![2, 5, 10].includes(value)) return;
        cellSize = value;
        updateControlsState();
        if (cachedPayload) renderFromPayload(cachedPayload);
      });
    });

    overlayButton?.addEventListener("click", () => {
      showEventsOverlay = !showEventsOverlay;
      updateControlsState();
      if (cachedPayload) renderFromPayload(cachedPayload);
    });

    themeToggle?.addEventListener("click", () => {
      darkMode = !darkMode;
      applyTheme();
    });

    const REFRESH_MS = 60000;
    let refreshInFlight = false;
    const refresh = async () => {
      if (refreshInFlight) return;
      refreshInFlight = true;
      try {
        await fetchData();
      } catch (error) {
        setError();
      } finally {
        refreshInFlight = false;
      }
    };

    updateControlsState();
    applyTheme();
    refresh();
    window.setInterval(() => {
      if (document.hidden) return;
      void refresh();
    }, REFRESH_MS);
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
