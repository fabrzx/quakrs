<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Tectonic Plates View';
$pageDescription = 'Tectonic plates and active faults map with filtered earthquake overlay.';
$currentPage = 'maps-plates';
$includeLeaflet = true;

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Maps / Tectonic Plates</p>
    <h1>Tectonic Plates &amp; Active Faults.</h1>
    <p class="sub">Confini di placca + faglie attive globali, con filtri magnitudo e controllo layer in mappa.</p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Events Analyzed</p>
    <p id="plates-kpi-total" class="kpi-value">--</p>
    <p class="kpi-note">Last 24h seismic events</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Plate Segments</p>
    <p id="plates-kpi-segments" class="kpi-value">--</p>
    <p class="kpi-note">Boundary lines loaded</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Fault Traces</p>
    <p id="plates-kpi-faults" class="kpi-value">--</p>
    <p class="kpi-note">Active faults rendered</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Last Update</p>
    <p id="plates-kpi-updated" class="kpi-value">--</p>
    <p id="plates-kpi-source" class="kpi-note">Loading source...</p>
  </article>
</section>

<section class="panel panel-main">
  <article class="card map-card">
    <div class="feed-head">
      <div class="map-head-left">
        <h3>Tectonic + Fault Overlay</h3>
        <button id="plates-theme-toggle" class="map-mini-toggle" type="button" aria-pressed="false" aria-label="Attiva modalita notturna" title="Attiva modalita notturna">☀</button>
      </div>
      <p class="feed-meta">Plates (cyan), faults (orange), events by magnitude</p>
    </div>
    <div class="map-wrap">
      <div id="plates-map-leaflet" class="world-map-leaflet" aria-label="Tectonic plates and faults map"></div>
    </div>
    <div class="map-legend">
      <button class="map-filter-btn band-m3" data-mag="0" type="button" aria-pressed="true">All</button>
      <button class="map-filter-btn band-m4" data-mag="4" type="button" aria-pressed="false">M4+</button>
      <button class="map-filter-btn band-m5" data-mag="5" type="button" aria-pressed="false">M5+</button>
      <button class="map-filter-btn band-m6" data-mag="6" type="button" aria-pressed="false">M6+</button>
      <button class="map-filter-btn band-m1-2 is-active" data-layer="plates" type="button" aria-pressed="true">Plates ON</button>
      <button class="map-filter-btn band-m7p is-active" data-layer="faults" type="button" aria-pressed="true">Faults ON</button>
    </div>
  </article>
  <article class="card side-card">
    <h3>Strongest Events</h3>
    <ul id="plates-strongest-list" class="events-list">
      <li class="event-item">Loading tectonic context...</li>
    </ul>
  </article>
</section>

<script>
  (() => {
    const kpiTotal = document.querySelector("#plates-kpi-total");
    const kpiSegments = document.querySelector("#plates-kpi-segments");
    const kpiFaults = document.querySelector("#plates-kpi-faults");
    const kpiUpdated = document.querySelector("#plates-kpi-updated");
    const kpiSource = document.querySelector("#plates-kpi-source");
    const strongestList = document.querySelector("#plates-strongest-list");
    const mapContainer = document.querySelector("#plates-map-leaflet");
    const themeToggle = document.querySelector("#plates-theme-toggle");
    const magButtons = Array.from(document.querySelectorAll("[data-mag]"));
    const layerButtons = Array.from(document.querySelectorAll("[data-layer]"));

    const fallbackPlateLines = [
      [[-55, -75], [60, -75]],
      [[-55, -110], [60, -110]],
      [[-55, 160], [60, 160]],
      [[-55, -20], [70, -20]],
      [[-35, 30], [20, 40]],
      [[20, 40], [45, 90]],
    ];

    let minMagnitude = 0;
    let showPlates = true;
    let showFaults = true;
    let cachedPayload = null;
    let platesGeoJson = null;
    let faultsGeoJson = null;
    let tectonicBundle = null;

    const map = mapContainer && window.L
      ? window.L.map(mapContainer, { zoomControl: true, worldCopyJump: true, preferCanvas: true }).setView([16, 8], 2)
      : null;

    const platesLayer = window.L && map ? window.L.layerGroup().addTo(map) : null;
    const faultsLayer = window.L && map ? window.L.layerGroup().addTo(map) : null;
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
    let darkMode = false;

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

    if (map && lightTiles) {
      lightTiles.addTo(map);
    }

    const setError = () => {
      if (strongestList) strongestList.innerHTML = "<li class='event-item'>Tectonic map unavailable right now.</li>";
      if (kpiSource) kpiSource.textContent = "Source unavailable";
    };

    const updateButtonsState = () => {
      magButtons.forEach((button) => {
        const selected = Number(button.dataset.mag) === minMagnitude;
        button.classList.toggle("is-active", selected);
        button.setAttribute("aria-pressed", selected ? "true" : "false");
      });

      layerButtons.forEach((button) => {
        const layer = button.dataset.layer;
        const enabled = layer === "plates" ? showPlates : showFaults;
        button.classList.toggle("is-active", enabled);
        button.setAttribute("aria-pressed", enabled ? "true" : "false");
        button.textContent = `${layer === "plates" ? "Plates" : "Faults"} ${enabled ? "ON" : "OFF"}`;
      });
    };

    const simplifyLine = (coords, step) => {
      if (!Array.isArray(coords) || coords.length <= 2) return coords;
      const out = [coords[0]];
      for (let i = step; i < coords.length - 1; i += step) {
        out.push(coords[i]);
      }
      out.push(coords[coords.length - 1]);
      return out;
    };

    const simplifyFaultGeometry = (geometry, step) => {
      if (!geometry || !geometry.type || !Array.isArray(geometry.coordinates)) return null;

      if (geometry.type === "LineString") {
        return {
          type: "LineString",
          coordinates: simplifyLine(geometry.coordinates, step),
        };
      }

      if (geometry.type === "MultiLineString") {
        return {
          type: "MultiLineString",
          coordinates: geometry.coordinates.map((line) => simplifyLine(line, step)),
        };
      }

      return null;
    };

    const faultSimplifyStep = () => {
      const z = map ? map.getZoom() : 2;
      if (z <= 2) return 10;
      if (z <= 3) return 8;
      if (z <= 4) return 6;
      if (z <= 5) return 4;
      return 3;
    };

    const fetchJsonWithTimeout = async (url, timeoutMs = 12000) => {
      const controller = new AbortController();
      const timer = window.setTimeout(() => controller.abort(), timeoutMs);
      try {
        const response = await fetch(url, { signal: controller.signal, headers: { Accept: "application/json" } });
        if (!response.ok) throw new Error(`request failed ${response.status}`);
        return await response.json();
      } finally {
        window.clearTimeout(timer);
      }
    };

    const ensureTectonicBundle = async () => {
      if (tectonicBundle && typeof tectonicBundle === "object") return tectonicBundle;
      try {
        tectonicBundle = await fetchJsonWithTimeout("/api/tectonic-context.php?scope=global&max_plates=1800&max_faults=3200", 14000);
      } catch (_) {
        tectonicBundle = null;
      }
      return tectonicBundle;
    };

    const ensurePlatesData = async () => {
      if (platesGeoJson) return platesGeoJson;
      const bundle = await ensureTectonicBundle();
      platesGeoJson = bundle && bundle.plates && Array.isArray(bundle.plates.features) ? bundle.plates : null;
      return platesGeoJson;
    };

    const ensureFaultsData = async () => {
      if (faultsGeoJson) return faultsGeoJson;
      const bundle = await ensureTectonicBundle();
      faultsGeoJson = bundle && bundle.faults && Array.isArray(bundle.faults.features) ? bundle.faults : null;
      return faultsGeoJson;
    };

    const renderPlatesLayer = async () => {
      if (!platesLayer || !window.L) return 0;
      platesLayer.clearLayers();
      if (!showPlates) return 0;

      const data = await ensurePlatesData();
      if (data && Array.isArray(data.features)) {
        let segments = 0;
        window.L.geoJSON(data, {
          style: {
            color: "#22d3ee",
            weight: 1.25,
            opacity: 0.8,
          },
          onEachFeature: (feature, layer) => {
            segments += 1;
            const featureName = feature?.properties?.Name || feature?.properties?.name || "Plate boundary";
            layer.bindTooltip(String(featureName));
          },
        }).addTo(platesLayer);
        return segments;
      }

      fallbackPlateLines.forEach((line) => {
        window.L.polyline(line, {
          color: "#22d3ee",
          weight: 1.5,
          opacity: 0.75,
          dashArray: "6 6",
        }).addTo(platesLayer);
      });
      return fallbackPlateLines.length;
    };

    const renderFaultsLayer = async () => {
      if (!faultsLayer || !window.L) return 0;
      faultsLayer.clearLayers();
      if (!showFaults) return 0;

      const data = await ensureFaultsData();
      if (!data || !Array.isArray(data.features)) return 0;

      const step = faultSimplifyStep();
      const zoom = map ? map.getZoom() : 2;
      const faultWeight = zoom >= 6 ? 2.4 : (zoom >= 5 ? 2.0 : (zoom >= 4 ? 1.6 : (zoom >= 3 ? 1.3 : 1.05)));
      const faultOpacity = zoom >= 6 ? 0.62 : (zoom >= 5 ? 0.54 : (zoom >= 4 ? 0.48 : 0.4));
      let traces = 0;

      data.features.forEach((feature) => {
        const geometry = simplifyFaultGeometry(feature.geometry, step);
        if (!geometry) return;

        if (geometry.type === "LineString") {
          if (geometry.coordinates.length < 2) return;
          traces += 1;
            window.L.polyline(geometry.coordinates.map(([lng, lat]) => [lat, lng]), {
              color: "#ff7a5f",
              weight: faultWeight,
              opacity: faultOpacity,
              interactive: false,
            }).addTo(faultsLayer);
          return;
        }

        if (geometry.type === "MultiLineString") {
          geometry.coordinates.forEach((line) => {
            if (!Array.isArray(line) || line.length < 2) return;
            traces += 1;
            window.L.polyline(line.map(([lng, lat]) => [lat, lng]), {
              color: "#ff7a5f",
              weight: faultWeight,
              opacity: faultOpacity,
              interactive: false,
            }).addTo(faultsLayer);
          });
        }
      });

      return traces;
    };

    const renderEventsLayer = (events) => {
      if (!eventsLayer || !window.L) return [];
      eventsLayer.clearLayers();

      const filtered = events.filter((event) => typeof event.magnitude === "number" && event.magnitude >= minMagnitude);
      const zoom = map ? map.getZoom() : 2;
      const zoomBoost = Math.max(0, (zoom - 2) * 0.55);
      filtered.slice(0, 600).forEach((event) => {
        if (typeof event.latitude !== "number" || typeof event.longitude !== "number") return;
        const mag = event.magnitude;
        const baseRadius = Math.max(2.8, Math.min(12.5, 2.8 + mag * 1.14));
        const radius = Math.min(16, baseRadius + zoomBoost);
        const color = mag >= 6 ? "#ef4444" : (mag >= 5 ? "#f97316" : (mag >= 4 ? "#f59e0b" : "#22c55e"));

        window.L.circleMarker([event.latitude, event.longitude], {
          radius,
          color: "rgba(255,255,255,0.9)",
          weight: 1,
          fillColor: color,
          fillOpacity: 0.88,
        })
          .bindTooltip(`M${mag.toFixed(1)} - ${event.place || "Unknown"}`)
          .addTo(eventsLayer);
      });

      return filtered;
    };

    const renderFromPayload = async (payload) => {
      const events = Array.isArray(payload.events) ? payload.events : [];
      const filtered = renderEventsLayer(events);

      if (kpiTotal) kpiTotal.textContent = String(events.length);
      if (kpiUpdated) {
        kpiUpdated.textContent = payload.generated_at
          ? new Date(payload.generated_at).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
          : "--";
      }
      if (kpiSource) {
        const provider = Array.isArray(payload.providers) && payload.providers.length > 0
          ? payload.providers.join(" + ")
          : (payload.provider || "Quakrs API");
        kpiSource.textContent = `Source: ${provider}${payload.from_cache ? " (cache)" : ""}`;
      }

      const strongest = [...filtered]
        .sort((a, b) => b.magnitude - a.magnitude)
        .slice(0, 10);

      if (strongestList) {
        strongestList.innerHTML = strongest.length === 0
          ? "<li class='event-item'>No events at current threshold.</li>"
          : strongest.map((event) => `<li class=\"event-item\"><strong>M${event.magnitude.toFixed(1)}</strong><br /><span>${event.place || "Unknown"}</span></li>`).join("");
      }

      const [segments, traces] = await Promise.all([
        renderPlatesLayer(),
        renderFaultsLayer(),
      ]);

      if (kpiSegments) kpiSegments.textContent = String(segments);
      if (kpiFaults) kpiFaults.textContent = String(traces);
    };

    const fetchData = async () => {
      const response = await fetch("/api/earthquakes.php", { headers: { Accept: "application/json" } });
      if (!response.ok) throw new Error("Request failed");
      cachedPayload = await response.json();
      await renderFromPayload(cachedPayload);
    };

    magButtons.forEach((button) => {
      button.addEventListener("click", async () => {
        const value = Number(button.dataset.mag);
        if (![0, 4, 5, 6].includes(value)) return;
        minMagnitude = value;
        updateButtonsState();
        if (cachedPayload) await renderFromPayload(cachedPayload);
      });
    });

    layerButtons.forEach((button) => {
      button.addEventListener("click", async () => {
        const layer = button.dataset.layer;
        if (layer === "plates") showPlates = !showPlates;
        if (layer === "faults") showFaults = !showFaults;
        updateButtonsState();
        if (cachedPayload) await renderFromPayload(cachedPayload);
      });
    });

    map?.on("zoomend", async () => {
      if (cachedPayload && showFaults) {
        await renderFromPayload(cachedPayload);
      }
    });

    themeToggle?.addEventListener("click", () => {
      darkMode = !darkMode;
      applyTheme();
    });

    updateButtonsState();
    applyTheme();
    fetchData().catch(setError);
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
