<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Depth View';
$pageDescription = 'Depth-oriented seismic view with shallow, intermediate and deep event layers.';
$currentPage = 'maps-depth';
$includeLeaflet = true;

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.maps_depth.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.maps_depth.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.maps_depth.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Shallow (&lt;70 km)</p>
    <p id="depth-kpi-shallow" class="kpi-value">--</p>
    <p class="kpi-note">Surface-impact prone events</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Intermediate (70-300 km)</p>
    <p id="depth-kpi-intermediate" class="kpi-value">--</p>
    <p class="kpi-note">Subduction-depth layer</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Deep (300+ km)</p>
    <p id="depth-kpi-deep" class="kpi-value">--</p>
    <p class="kpi-note">Deep mantle events</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Visible Events</p>
    <p id="depth-kpi-visible" class="kpi-value">--</p>
    <p id="depth-kpi-source" class="kpi-note">Loading source...</p>
  </article>
</section>

<section class="panel panel-main">
  <article class="card map-card">
    <div class="feed-head">
      <div class="map-head-left">
        <h3>Depth Overlay Map</h3>
        <button id="depth-theme-toggle" class="map-mini-toggle" type="button" aria-pressed="true" aria-label="Disattiva modalita notturna" title="Disattiva modalita notturna">☾</button>
      </div>
      <p class="feed-meta">Depth-filtered events on live basemap</p>
    </div>
    <div class="map-wrap">
      <div id="depth-map-leaflet" class="world-map-leaflet" aria-label="Depth map"></div>
    </div>
    <div class="map-legend">
      <button class="map-filter-btn band-m3" data-depth="all" type="button" aria-pressed="true">All</button>
      <button class="map-filter-btn band-m1-2" data-depth="shallow" type="button" aria-pressed="false">Shallow</button>
      <button class="map-filter-btn band-m5" data-depth="intermediate" type="button" aria-pressed="false">Intermediate</button>
      <button class="map-filter-btn band-m7p" data-depth="deep" type="button" aria-pressed="false">Deep</button>
      <span style="background:#22d3ee">&lt;70 km</span>
      <span style="background:#f7d21e">70-300 km</span>
      <span style="background:#ef4444">300+ km</span>
    </div>
  </article>
  <article class="card side-card">
    <h3>Deepest Events</h3>
    <ul id="depth-deepest-list" class="events-list">
      <li class="event-item">Loading depth layers...</li>
    </ul>
  </article>
</section>

<script>
  (() => {
    const kpiShallow = document.querySelector("#depth-kpi-shallow");
    const kpiIntermediate = document.querySelector("#depth-kpi-intermediate");
    const kpiDeep = document.querySelector("#depth-kpi-deep");
    const kpiVisible = document.querySelector("#depth-kpi-visible");
    const kpiSource = document.querySelector("#depth-kpi-source");
    const deepestList = document.querySelector("#depth-deepest-list");
    const mapContainer = document.querySelector("#depth-map-leaflet");
    const themeToggle = document.querySelector("#depth-theme-toggle");
    const depthButtons = Array.from(document.querySelectorAll("[data-depth]"));

    let depthFilter = "all";
    let cachedPayload = null;

    const depthBand = (depth) => {
      if (depth < 70) return "shallow";
      if (depth < 300) return "intermediate";
      return "deep";
    };

    const depthColor = (depth) => {
      if (depth < 70) return "#22d3ee";
      if (depth < 300) return "#f7d21e";
      return "#ef4444";
    };

    const map = mapContainer && window.L
      ? window.L.map(mapContainer, { zoomControl: true, worldCopyJump: true }).setView([14, 10], 2)
      : null;

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

    const setError = () => {
      if (deepestList) deepestList.innerHTML = "<li class='event-item'>Depth map unavailable right now.</li>";
      if (kpiSource) kpiSource.textContent = "Source unavailable";
    };

    const updateButtonsState = () => {
      depthButtons.forEach((button) => {
        const selected = button.dataset.depth === depthFilter;
        button.classList.toggle("is-active", selected);
        button.setAttribute("aria-pressed", selected ? "true" : "false");
      });
    };

    const filterEvents = (events) => {
      if (depthFilter === "all") return events;
      return events.filter((event) => depthBand(Math.abs(event.depth_km)) === depthFilter);
    };

    const renderMap = (events) => {
      if (!eventsLayer || !window.L) return;
      eventsLayer.clearLayers();

      const zoom = map ? map.getZoom() : 2;
      const zoomBoost = Math.max(0, (zoom - 2) * 0.5);
      events.slice(0, 700).forEach((event) => {
        if (typeof event.latitude !== "number" || typeof event.longitude !== "number") return;
        const depth = Math.abs(event.depth_km);
        const mag = typeof event.magnitude === "number" ? event.magnitude : 0;
        const baseRadius = Math.max(2.8, Math.min(11.5, 2.6 + mag * 1.02));
        const radius = Math.min(16, baseRadius + zoomBoost);
        const color = depthColor(depth);

        window.L.circleMarker([event.latitude, event.longitude], {
          radius,
          color: "rgba(255,255,255,0.88)",
          weight: 1,
          fillColor: color,
          fillOpacity: 0.86,
        })
          .bindTooltip(`M${mag.toFixed(1)} · ${depth.toFixed(0)} km · ${event.place || "Unknown"}`)
          .addTo(eventsLayer);
      });
    };

    const renderFromPayload = (payload) => {
      const allEvents = (Array.isArray(payload.events) ? payload.events : []).filter((event) => typeof event.depth_km === "number");

      const layers = { shallow: 0, intermediate: 0, deep: 0 };
      allEvents.forEach((event) => {
        const depth = Math.abs(event.depth_km);
        if (depth < 70) layers.shallow += 1;
        else if (depth < 300) layers.intermediate += 1;
        else layers.deep += 1;
      });

      const visibleEvents = filterEvents(allEvents);
      const deepest = [...visibleEvents]
        .sort((a, b) => Math.abs(b.depth_km) - Math.abs(a.depth_km))
        .slice(0, 12);

      if (kpiShallow) kpiShallow.textContent = String(layers.shallow);
      if (kpiIntermediate) kpiIntermediate.textContent = String(layers.intermediate);
      if (kpiDeep) kpiDeep.textContent = String(layers.deep);
      if (kpiVisible) kpiVisible.textContent = String(visibleEvents.length);
      if (kpiSource) {
        const provider = Array.isArray(payload.providers) && payload.providers.length > 0
          ? payload.providers.join(" + ")
          : (payload.provider || "Quakrs API");
        kpiSource.textContent = `Source: ${provider}`;
      }

      if (deepestList) {
        deepestList.innerHTML = deepest.length === 0
          ? "<li class='event-item'>No events in this depth band.</li>"
          : deepest.map((event) => {
              const mag = typeof event.magnitude === "number" ? `M${event.magnitude.toFixed(1)}` : "M?";
              const depth = `${Math.abs(event.depth_km).toFixed(0)} km`;
              return `<li class=\"event-item\"><strong>${mag} · ${depth}</strong><br /><span>${event.place || "Unknown"}</span></li>`;
            }).join("");
      }

      renderMap(visibleEvents);
    };

    const fetchData = async () => {
      const response = await fetch("/api/earthquakes.php", { headers: { Accept: "application/json" } });
      if (!response.ok) throw new Error("Request failed");
      cachedPayload = await response.json();
      renderFromPayload(cachedPayload);
    };

    depthButtons.forEach((button) => {
      button.addEventListener("click", () => {
        const value = button.dataset.depth;
        if (!["all", "shallow", "intermediate", "deep"].includes(value)) return;
        depthFilter = value;
        updateButtonsState();
        if (cachedPayload) renderFromPayload(cachedPayload);
      });
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

    updateButtonsState();
    applyTheme();
    refresh();
    window.setInterval(() => {
      if (document.hidden) return;
      void refresh();
    }, REFRESH_MS);
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
