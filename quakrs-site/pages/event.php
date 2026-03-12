<?php
declare(strict_types=1);

$place = isset($_GET['place']) ? trim((string) $_GET['place']) : '';
$mag = isset($_GET['mag']) ? trim((string) $_GET['mag']) : '';
$depth = isset($_GET['depth']) ? trim((string) $_GET['depth']) : '';
$time = isset($_GET['time']) ? trim((string) $_GET['time']) : '';

$titlePlace = $place !== '' ? $place : 'Selected seismic event';
$titleMag = $mag !== '' ? "M{$mag}" : 'M?';
$initialMagnitudeValue = is_numeric($mag) ? (float) $mag : null;
$initialMagnitudeBand = 'pending';
if ($initialMagnitudeValue !== null) {
  $bucket = (int) floor($initialMagnitudeValue);
  if ($bucket < 1) $bucket = 1;
  if ($bucket > 9) $bucket = 9;
  $initialMagnitudeBand = "b{$bucket}";
}
$timeNote = 'Awaiting tectonic context';
if ($time !== '') {
  try {
    $dt = new DateTimeImmutable($time);
    $timeNote = sprintf('Event time %s UTC', $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i'));
  } catch (Throwable $e) {
    $timeNote = 'Event time available';
  }
}

$pageTitle = 'Quakrs.com - Event Insight';
$pageDescription = 'Detailed tectonic and seismic context for a selected earthquake event.';
$currentPage = 'earthquakes';
$includeLeaflet = true;

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero event-hero">
  <div class="event-hero-main">
    <p class="eyebrow">MONITORS / EARTHQUAKES / EVENT INSIGHT</p>
    <h1 id="event-detail-title" class="event-title">
      <span id="event-title-mag" class="event-title-mag"><?= htmlspecialchars(str_replace('M', 'M ', $titleMag), ENT_QUOTES, 'UTF-8'); ?></span>
      <span id="event-title-place" class="event-title-place"><?= htmlspecialchars($titlePlace, ENT_QUOTES, 'UTF-8'); ?></span>
    </h1>
    <p id="event-meta-line" class="event-meta-line">
      Pending date · Pending UTC · Depth <?= htmlspecialchars($depth !== '' ? "{$depth} km" : 'Pending depth', ENT_QUOTES, 'UTF-8'); ?> · Coordinates pending
    </p>
    <p id="event-context-line" class="event-context-line">Classification pending · Region pending · Inland · Automatic</p>
  </div>
  <div class="hero-side event-hero-side">
    <div class="hero-actions event-hero-actions">
      <a class="btn btn-primary" href="/maps.php">Open Maps Hub</a>
      <a class="btn btn-ghost" href="/earthquakes.php">Back to Earthquakes</a>
    </div>
  </div>
</main>

<section class="panel panel-kpi event-kpi-row">
  <article id="event-kpi-magnitude-card" class="card kpi-card event-kpi-card event-kpi-primary" data-intensity="<?= htmlspecialchars($initialMagnitudeBand, ENT_QUOTES, 'UTF-8'); ?>">
    <p class="kpi-label">Magnitude</p>
    <p id="event-kpi-mag" class="kpi-value"><?= htmlspecialchars($titleMag, ENT_QUOTES, 'UTF-8'); ?></p>
    <p class="kpi-note">Reported event magnitude</p>
  </article>
  <article class="card kpi-card event-kpi-card">
    <p class="kpi-label">Depth</p>
    <p id="event-kpi-depth" class="kpi-value"><?= htmlspecialchars($depth !== '' ? "{$depth} km" : 'Unavailable', ENT_QUOTES, 'UTF-8'); ?></p>
    <p class="kpi-note">Hypocentral depth</p>
  </article>
  <article class="card kpi-card event-kpi-card">
    <p class="kpi-label">Plate Boundary</p>
    <p id="event-kpi-plate-distance" class="kpi-value">Pending</p>
    <p class="kpi-note">Nearest boundary distance</p>
  </article>
  <article class="card kpi-card event-kpi-card">
    <p class="kpi-label">Regime</p>
    <p id="event-kpi-regime" class="kpi-value">Not classified yet</p>
    <p id="event-kpi-regime-note" class="kpi-note"><?= htmlspecialchars($timeNote, ENT_QUOTES, 'UTF-8'); ?></p>
  </article>
</section>

<section class="panel event-section-head event-spatial-head">
  <div>
    <p class="eyebrow event-section-eyebrow">Spatial Context</p>
    <h2 class="event-section-title">Main Spatial Section</h2>
  </div>
</section>

<section class="panel panel-main event-spatial-main">
  <article class="card map-card event-map-card">
    <div class="feed-head">
      <h3>Zone Tectonic Map</h3>
      <p class="feed-meta">Selected event + nearby strong seismicity + tectonic layers</p>
    </div>
    <div class="map-wrap insight-map-wrap">
      <div id="event-detail-map" class="world-map-leaflet" aria-label="Event zone map"></div>
    </div>
    <div class="insight-badges event-status-rails" aria-label="Map layer status">
      <span id="event-layer-plates" class="insight-badge">Plates: loading context</span>
      <span id="event-layer-faults" class="insight-badge">Faults: loading context</span>
      <span id="event-layer-strong" class="insight-badge">Strong nearby: pending</span>
      <span id="event-layer-window" class="insight-badge">Window: last 24h feed</span>
    </div>
  </article>

  <div class="event-side-column">
    <article class="card side-card event-side-console">
      <section class="event-console-block event-console-primary">
        <h3>Zone Briefing</h3>
        <p id="event-zone-summary" class="kpi-note">Loading context for local geodynamic briefing.</p>
      </section>
      <section class="event-console-block">
        <h3>Nearest Active Fault</h3>
        <ul id="event-fault-list" class="events-list fault-list-scroll">
          <li class="event-item">Loading fault context for this zone.</li>
        </ul>
      </section>
    </article>

    <article class="card event-side-detached">
      <h3>Regional Context</h3>
      <ul id="event-region-console-list" class="events-list">
        <li class="event-item">Loading regional synthesis for this event.</li>
      </ul>
      <div id="event-region-canvas" class="event-console-canvas">
        Synthesis panel active. Awaiting deeper contextual layers for this zone.
      </div>
    </article>
  </div>
</section>

<section class="panel panel-charts event-insight-strip">
  <article class="card event-insight-card">
    <div class="feed-head">
      <h3>Nearby Strong Seismicity</h3>
      <p class="feed-meta">M5+ events within 500 km (last 24h feed)</p>
    </div>
    <ul id="event-nearby-strong-list" class="events-list">
      <li class="event-item">Loading nearby strong seismicity context.</li>
    </ul>
  </article>
  <article class="card event-insight-card">
    <div class="feed-head">
      <h3>Local Intensity Ring</h3>
      <p class="feed-meta">Counts in 100/250/500 km rings</p>
    </div>
    <ul id="event-ring-list" class="events-list">
      <li class="event-item">Building ring statistics...</li>
    </ul>
  </article>
  <article class="card event-insight-card">
    <div class="feed-head">
      <h3>Regional Context</h3>
      <p class="feed-meta">Strong seismic places near selected event</p>
    </div>
    <ul id="event-region-context-list" class="events-list">
      <li class="event-item">Loading regional context...</li>
    </ul>
  </article>
</section>

<section class="panel event-section-head event-history-head">
  <div>
    <p class="eyebrow event-section-eyebrow">Historical Context</p>
    <h2 class="event-section-title">Historical Context</h2>
    <p class="event-section-subtitle">Archive depth, strongest events and long-range local seismic memory</p>
  </div>
</section>

<section class="panel panel-kpi event-history-kpi">
  <article class="card kpi-card event-kpi-card">
    <p class="kpi-label">Historical Records</p>
    <p id="hist-kpi-total" class="kpi-value">Awaiting archive data</p>
    <p class="kpi-note">Complete archive count in this zone</p>
  </article>
  <article class="card kpi-card event-kpi-card">
    <p class="kpi-label">Archive Window</p>
    <p id="hist-kpi-window" class="kpi-value">1900-now</p>
    <p class="kpi-note">USGS historical availability</p>
  </article>
  <article class="card kpi-card event-kpi-card">
    <p class="kpi-label">Strongest Historical</p>
    <p id="hist-kpi-strongest" class="kpi-value">Pending</p>
    <p class="kpi-note">Within selected radius</p>
  </article>
  <article class="card kpi-card event-kpi-card">
    <p class="kpi-label">Loaded Pages</p>
    <p id="hist-kpi-pages" class="kpi-value">0/0</p>
    <p id="hist-kpi-source" class="kpi-note">Awaiting archive data</p>
  </article>
</section>

<section class="panel panel-main event-history-main">
  <article class="card event-history-strongest-card">
    <div class="feed-head">
      <h3>Strongest Historical Events</h3>
      <p class="feed-meta">Top magnitudes ever recorded in this zone</p>
    </div>
    <ul id="event-history-strongest-list" class="events-list live-feed-scroll history-list-scroll">
      <li class="event-item">Loading strongest historical records for this zone.</li>
    </ul>
  </article>
  <article class="card side-card event-history-stream-card">
    <h3>Historical Archive Stream</h3>
    <p id="event-history-meta" class="kpi-note">Awaiting archive data.</p>
    <ul id="event-history-list" class="events-list live-feed-scroll history-list-scroll">
      <li class="event-item">Awaiting archive data for this zone stream.</li>
    </ul>
    <button id="event-history-more" class="timeline-more" type="button" hidden>Load older history</button>
  </article>
</section>

<script>
  (() => {
    const params = new URLSearchParams(window.location.search);
    const q = {
      id: params.get("id") || "",
      place: params.get("place") || "",
      time: params.get("time") || "",
      mag: Number(params.get("mag")),
      depth: Number(params.get("depth")),
      lat: Number(params.get("lat")),
      lon: Number(params.get("lon")),
    };

    const mapContainer = document.querySelector("#event-detail-map");
    const spatialMapCard = document.querySelector(".event-map-card");
    const spatialSideColumn = document.querySelector(".event-side-column");
    const titleMagLine = document.querySelector("#event-title-mag");
    const titlePlaceLine = document.querySelector("#event-title-place");
    const metaLine = document.querySelector("#event-meta-line");
    const kpiMagCard = document.querySelector("#event-kpi-magnitude-card");
    const contextLine = document.querySelector("#event-context-line");
    const kpiMag = document.querySelector("#event-kpi-mag");
    const kpiDepth = document.querySelector("#event-kpi-depth");
    const kpiPlateDistance = document.querySelector("#event-kpi-plate-distance");
    const kpiRegime = document.querySelector("#event-kpi-regime");
    const kpiRegimeNote = document.querySelector("#event-kpi-regime-note");
    const layerPlates = document.querySelector("#event-layer-plates");
    const layerFaults = document.querySelector("#event-layer-faults");
    const layerStrong = document.querySelector("#event-layer-strong");
    const layerWindow = document.querySelector("#event-layer-window");
    const zoneSummary = document.querySelector("#event-zone-summary");
    const faultList = document.querySelector("#event-fault-list");
    const regionConsoleList = document.querySelector("#event-region-console-list");
    const regionCanvas = document.querySelector("#event-region-canvas");
    const strongList = document.querySelector("#event-nearby-strong-list");
    const ringList = document.querySelector("#event-ring-list");
    const regionContextList = document.querySelector("#event-region-context-list");
    const histKpiTotal = document.querySelector("#hist-kpi-total");
    const histKpiStrongest = document.querySelector("#hist-kpi-strongest");
    const histKpiPages = document.querySelector("#hist-kpi-pages");
    const histKpiSource = document.querySelector("#hist-kpi-source");
    const historyStrongestList = document.querySelector("#event-history-strongest-list");
    const historyList = document.querySelector("#event-history-list");
    const historyMeta = document.querySelector("#event-history-meta");
    const historyMoreButton = document.querySelector("#event-history-more");

    let historyRows = [];
    let historyPage = 0;
    let historyTotalPages = 0;
    let historyTotalEvents = 0;

    let map = null;
    let eventLayer = null;
    let strongLayer = null;
    let plateLayer = null;
    let faultLayer = null;
    let shakeLayer = null;

    const magnitudeColor = (magnitude) => {
      if (!Number.isFinite(magnitude)) return "#6b7280";
      const bucket = Math.max(1, Math.min(9, Math.floor(magnitude)));
      const palette = {
        1: "#3b82f6", 2: "#06b6d4", 3: "#14b8a6", 4: "#22c55e", 5: "#eab308",
        6: "#f59e0b", 7: "#f97316", 8: "#d946ef", 9: "#7e22ce",
      };
      return palette[bucket];
    };

    const toRad = (v) => (v * Math.PI) / 180;
    const haversineKm = (lat1, lon1, lat2, lon2) => {
      const dLat = toRad(lat2 - lat1);
      const dLon = toRad(lon2 - lon1);
      const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
      return 6371 * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
    };

    const parseRegion = (place) => {
      if (!place) return "Unknown";
      if (String(place).includes(" of ")) return String(place).split(" of ").slice(-1)[0].trim();
      const parts = String(place).split(",");
      return parts[parts.length - 1].trim() || String(place);
    };

    const collectCoordinates = (geometry, out) => {
      if (!geometry || typeof geometry !== "object") return;
      const coords = geometry.coordinates;
      if (!Array.isArray(coords)) return;
      if (typeof coords[0] === "number" && typeof coords[1] === "number") {
        out.push([coords[1], coords[0]]);
        return;
      }
      coords.forEach((row) => {
        if (Array.isArray(row)) collectCoordinates({ coordinates: row }, out);
      });
    };

    const nearestFeatureDistanceKm = (feature, lat, lon) => {
      const points = [];
      collectCoordinates(feature?.geometry, points);
      if (points.length === 0) return Number.POSITIVE_INFINITY;
      const step = points.length > 900 ? 6 : points.length > 300 ? 4 : 2;
      let best = Number.POSITIVE_INFINITY;
      for (let i = 0; i < points.length; i += step) {
        const [pLat, pLon] = points[i];
        const km = haversineKm(lat, lon, pLat, pLon);
        if (km < best) best = km;
      }
      return best;
    };

    const getFeatureName = (feature) => {
      const props = feature?.properties || {};
      const keys = ["name", "NAME", "fault_name", "FAULT_NAME", "fault", "structure", "id"];
      for (const key of keys) {
        const value = props[key];
        if (typeof value === "string" && value.trim() !== "") return value.trim();
      }
      return "Fault segment (name unavailable)";
    };

    const getSlipRate = (feature) => {
      const props = feature?.properties || {};
      for (const key of Object.keys(props)) {
        if (!/slip/i.test(key)) continue;
        const raw = props[key];
        if (typeof raw === "number" && Number.isFinite(raw)) return `${raw.toFixed(2)} mm/yr`;
        if (typeof raw === "string" && raw.trim() !== "") return raw.trim();
      }
      return "Not available";
    };

    const regimeLabel = (event, faultKm, plateKm) => {
      const depth = Number.isFinite(event.depth_km) ? event.depth_km : NaN;
      if (Number.isFinite(depth) && depth >= 300) return "Deep slab";
      if (Number.isFinite(depth) && depth >= 70 && plateKm <= 220) return "Subduction";
      if (faultKm <= 35 && Number.isFinite(depth) && depth < 70) return "Crustal fault";
      if (plateKm <= 140) return "Boundary";
      return "Intraplate";
    };

    const eventKey = (event) => {
      if (event.id) return String(event.id);
      const lat = Number.isFinite(event.latitude) ? event.latitude.toFixed(3) : "na";
      const lon = Number.isFinite(event.longitude) ? event.longitude.toFixed(3) : "na";
      return `${lat}|${lon}|${event.event_time_utc || "na"}`;
    };

    const ensureMap = () => {
      if (!mapContainer || !window.L) return null;
      if (map) return map;
      map = window.L.map(mapContainer, { zoomControl: true, worldCopyJump: true }).setView([10, 0], 2);
      window.L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 9,
        minZoom: 2,
        attribution: "&copy; OpenStreetMap contributors",
      }).addTo(map);
      eventLayer = window.L.layerGroup().addTo(map);
      strongLayer = window.L.layerGroup().addTo(map);
      plateLayer = window.L.layerGroup().addTo(map);
      faultLayer = window.L.layerGroup().addTo(map);
      shakeLayer = window.L.layerGroup().addTo(map);
      return map;
    };

    const safeTime = (iso) => (iso ? new Date(iso).toLocaleString([], { month: "short", day: "2-digit", hour: "2-digit", minute: "2-digit" }) : "Pending time");
    const asMagnitude = (value) => (Number.isFinite(value) ? `M${value.toFixed(1)}` : "Unavailable");
    const asMagnitudeTitle = (value) => (Number.isFinite(value) ? `M ${value.toFixed(1)}` : "M ?");
    const magnitudeBandClass = (magnitude) => {
      if (!Number.isFinite(magnitude)) return "m-na";
      const bucket = Math.max(1, Math.min(9, Math.floor(magnitude)));
      return `m-b${bucket}`;
    };
    const magnitudeText = (magnitude) => {
      if (!Number.isFinite(magnitude)) return '<span class="mag-value m-na">M?</span>';
      return `<span class="mag-value ${magnitudeBandClass(magnitude)}">M${magnitude.toFixed(1)}</span>`;
    };
    const asDepth = (value) => (Number.isFinite(value) ? `${value.toFixed(1)} km` : "Unavailable");
    const asDistance = (value, { approximate = false } = {}) => {
      if (!Number.isFinite(value)) return "Unavailable";
      return `${approximate ? "~" : ""}${value.toFixed(0)} km`;
    };
    const severityLabel = (magnitude) => {
      if (!Number.isFinite(magnitude)) return "Pending classification";
      if (magnitude < 2) return "Microquake";
      if (magnitude < 4) return "Minor";
      if (magnitude < 5) return "Light";
      if (magnitude < 6) return "Moderate";
      return "Strong";
    };
    const intensityBand = (magnitude) => {
      if (!Number.isFinite(magnitude)) return "pending";
      const bucket = Math.max(1, Math.min(9, Math.floor(magnitude)));
      return `b${bucket}`;
    };
    const formatUtcMeta = (iso, depthKm, lat, lon) => {
      let datePart = "Pending date";
      let timePart = "Pending UTC";
      if (iso) {
        const dt = new Date(iso);
        if (!Number.isNaN(dt.getTime())) {
          datePart = dt.toLocaleDateString("en-GB", {
            day: "2-digit",
            month: "short",
            year: "numeric",
            timeZone: "UTC",
          });
          timePart = `${dt.toLocaleTimeString("en-GB", {
            hour: "2-digit",
            minute: "2-digit",
            hour12: false,
            timeZone: "UTC",
          })} UTC`;
        }
      }
      const depthPart = `Depth ${asDepth(depthKm)}`;
      const coordPart = Number.isFinite(lat) && Number.isFinite(lon)
        ? `${lat.toFixed(3)}, ${lon.toFixed(3)}`
        : "Coordinates pending";
      return `${datePart} · ${timePart} · ${depthPart} · ${coordPart}`;
    };
    const fetchJsonWithTimeout = async (url, timeoutMs = 8000) => {
      const controller = new AbortController();
      const timer = window.setTimeout(() => controller.abort(), timeoutMs);
      try {
        const response = await fetch(url, {
          headers: { Accept: "application/json" },
          signal: controller.signal,
        });
        if (!response.ok) {
          throw new Error(`Request failed ${response.status}`);
        }
        return await response.json();
      } finally {
        window.clearTimeout(timer);
      }
    };

    const readMmi = (feature) => {
      const props = feature?.properties || {};
      const keys = ["value", "mmi", "MMI", "cmi", "intensity", "GRID_CODE"];
      for (const key of keys) {
        const raw = props[key];
        const value = Number(raw);
        if (Number.isFinite(value)) return value;
      }
      return NaN;
    };

    const mmiColor = (mmi) => {
      if (!Number.isFinite(mmi)) return "#6b7280";
      if (mmi < 3) return "#3b82f6";
      if (mmi < 4) return "#06b6d4";
      if (mmi < 5) return "#14b8a6";
      if (mmi < 6) return "#22c55e";
      if (mmi < 7) return "#eab308";
      if (mmi < 8) return "#f59e0b";
      if (mmi < 9) return "#f97316";
      if (mmi < 10) return "#d946ef";
      return "#7e22ce";
    };

    const usgsEventIdFrom = (event) => {
      if (event?.id && typeof event.id === "string" && event.id.trim() !== "") return event.id.trim();
      const url = String(event?.source_url || "");
      const match = url.match(/eventpage\/([a-z0-9]+)/i);
      return match ? match[1] : "";
    };

    const renderModelledShake = (selected) => {
      if (!shakeLayer) return false;
      shakeLayer.clearLayers();
      const mag = Number(selected.magnitude);
      const depth = Number.isFinite(selected.depth_km) ? selected.depth_km : 12;
      if (!Number.isFinite(mag)) return false;

      const levels = [7, 6, 5, 4, 3];
      let plotted = 0;
      levels.forEach((mmi) => {
        // Simplified attenuation fallback (MMI) for when official contours are unavailable.
        const rhs = (1.08 * mag + 1.55 - mmi);
        const rh = Math.pow(10, rhs / 1.35);
        const radiusKm = Math.max(4, Math.sqrt(Math.max(0, rh * rh - depth * depth)));
        if (!Number.isFinite(radiusKm) || radiusKm < 4) return;
        window.L.circle([selected.latitude, selected.longitude], {
          radius: radiusKm * 1000,
          color: mmiColor(mmi),
          weight: 1.1,
          opacity: 0.7,
          fillColor: mmiColor(mmi),
          fillOpacity: Math.max(0.06, 0.2 - (7 - mmi) * 0.03),
          interactive: false,
        }).addTo(shakeLayer);
        plotted += 1;
      });

      if (layerWindow) layerWindow.textContent = plotted > 0 ? "ShakeMap: modelled MMI fallback" : "ShakeMap: unavailable";
      return plotted > 0;
    };

    const loadAndRenderShakeMap = async (selected) => {
      if (!shakeLayer) return false;
      shakeLayer.clearLayers();
      const eventId = usgsEventIdFrom(selected);
      if (!eventId) {
        return renderModelledShake(selected);
      }
      try {
        const detail = await fetchJsonWithTimeout(`https://earthquake.usgs.gov/fdsnws/event/1/query?format=geojson&eventid=${encodeURIComponent(eventId)}`, 9000);
        const products = detail?.properties?.products || {};
        const shakeProducts = Array.isArray(products.shakemap) ? products.shakemap : [];
        if (shakeProducts.length === 0) {
          return renderModelledShake(selected);
        }
        const latest = shakeProducts[0] || {};
        const contents = latest.contents && typeof latest.contents === "object" ? latest.contents : {};
        const contourEntry = Object.entries(contents).find(([key]) => /cont[_-]?mi\.json/i.test(key))
          || Object.entries(contents).find(([key]) => /mmi.*geojson/i.test(key))
          || Object.entries(contents).find(([key]) => /intensity.*geojson/i.test(key));
        const contourUrl = contourEntry?.[1]?.url;
        if (!contourUrl) {
          return renderModelledShake(selected);
        }
        const contours = await fetchJsonWithTimeout(contourUrl, 9000);
        const features = Array.isArray(contours?.features) ? contours.features : [];
        if (features.length === 0) {
          return renderModelledShake(selected);
        }
        window.L.geoJSON(contours, {
          style: (feature) => {
            const mmi = readMmi(feature);
            return {
              color: mmiColor(mmi),
              weight: 1.2,
              opacity: 0.72,
              fillColor: mmiColor(mmi),
              fillOpacity: 0.12,
            };
          },
          onEachFeature: (feature, layer) => {
            const mmi = readMmi(feature);
            if (Number.isFinite(mmi)) layer.bindTooltip(`MMI ${mmi.toFixed(1)}`, { direction: "center" });
          },
        }).addTo(shakeLayer);
        if (layerWindow) layerWindow.textContent = "ShakeMap: USGS intensity contours";
        return true;
      } catch (error) {
        return renderModelledShake(selected);
      }
    };

    const setFailure = (message) => {
      if (zoneSummary) zoneSummary.textContent = message;
      if (strongList) strongList.innerHTML = "<li class='event-item'>Context unavailable right now.</li>";
      if (faultList) faultList.innerHTML = "<li class='event-item'>Fault context unavailable.</li>";
      if (ringList) ringList.innerHTML = "<li class='event-item'>Ring statistics pending.</li>";
      if (regionContextList) regionContextList.innerHTML = "<li class='event-item'>Regional snapshot unavailable.</li>";
      if (regionConsoleList) regionConsoleList.innerHTML = "<li class='event-item'>Regional synthesis pending.</li>";
      if (regionCanvas) regionCanvas.textContent = "Regional synthesis panel is active but context is currently unavailable.";
      if (historyStrongestList) historyStrongestList.innerHTML = "<li class='event-item'>Awaiting archive data for strongest records.</li>";
      if (historyList) historyList.innerHTML = "<li class='event-item'>Awaiting archive data for stream view.</li>";
      if (historyMeta) historyMeta.textContent = "Awaiting archive data";
      if (histKpiSource) histKpiSource.textContent = "Awaiting archive data";
    };

    const fallbackPlateLines = [
      [[-55, -75], [60, -75]],
      [[-55, -110], [60, -110]],
      [[-55, 160], [60, 160]],
      [[-55, -20], [70, -20]],
      [[-35, 30], [20, 40]],
      [[20, 40], [45, 90]],
    ];

    const buildHistoryFallbackFromFeed = (selected, events) => {
      const rows = events
        .filter((row) => Number.isFinite(row.latitude) && Number.isFinite(row.longitude))
        .map((row) => ({
          ...row,
          distanceKm: haversineKm(selected.latitude, selected.longitude, row.latitude, row.longitude),
        }))
        .filter((row) => row.distanceKm <= 500)
        .sort((a, b) => {
          const aTs = row => (row.event_time_utc ? Date.parse(row.event_time_utc) : 0);
          return aTs(b) - aTs(a);
        });

      const strongest = rows
        .filter((row) => Number.isFinite(row.magnitude))
        .sort((a, b) => b.magnitude - a.magnitude)
        .slice(0, 12);

      historyRows = rows.slice(0, 80);
      historyPage = rows.length > 0 ? 1 : 0;
      historyTotalPages = rows.length > 0 ? 1 : 0;
      historyTotalEvents = rows.length;

      if (histKpiTotal) histKpiTotal.textContent = String(rows.length);
      if (histKpiPages) histKpiPages.textContent = rows.length > 0 ? "1/1" : "0/0";
      if (histKpiSource) histKpiSource.textContent = "Operational fallback (last 24h feed)";
      if (histKpiStrongest) {
        histKpiStrongest.textContent = strongest[0] && Number.isFinite(strongest[0].magnitude)
          ? `M${strongest[0].magnitude.toFixed(1)}`
          : "Pending";
      }

      if (historyStrongestList) {
        historyStrongestList.innerHTML = strongest.length > 0
          ? strongest.map((row) => {
            const mag = asMagnitude(row.magnitude);
            const depth = asDepth(row.depth_km);
            return `<li class="event-item"><strong>${magnitudeText(row.magnitude)} ${row.place || "Unknown"}</strong><br />${safeTime(row.event_time_utc)} · depth ${depth}</li>`;
          }).join("")
          : "<li class='event-item'>No historical rows in local fallback window.</li>";
      }

      if (historyMeta) {
        historyMeta.textContent = rows.length > 0
          ? `Loaded ${historyRows.length}/${rows.length} records from local operational window`
          : "No local history rows for this radius";
      }

      if (historyMoreButton) {
        historyMoreButton.hidden = true;
      }

      renderHistoryList();
    };

    const renderHistoryList = () => {
      if (!historyList) return;
      if (historyRows.length === 0) {
        historyList.innerHTML = "<li class='event-item'>No historical records loaded yet.</li>";
      } else {
        historyList.innerHTML = historyRows.map((row) => {
          const mag = asMagnitude(row.magnitude);
          const depth = asDepth(row.depth_km);
          return `<li class="event-item"><strong>${magnitudeText(row.magnitude)} ${row.place || "Unknown"}</strong><br />${safeTime(row.event_time_utc)} · depth ${depth}</li>`;
        }).join("");
      }

      if (historyMeta) {
        historyMeta.textContent = `${historyRows.length}/${historyTotalEvents} records loaded (${historyPage}/${historyTotalPages} pages)`;
      }
      if (histKpiPages) {
        histKpiPages.textContent = `${historyPage}/${historyTotalPages}`;
      }
      if (historyMoreButton) {
        const hasMore = historyPage < historyTotalPages;
        historyMoreButton.hidden = !hasMore;
        historyMoreButton.textContent = hasMore ? "Load older history" : "All history loaded";
      }
    };

    const loadHistoryPage = async (selected, pageToLoad) => {
      const url = `/api/event-history.php?lat=${selected.latitude.toFixed(5)}&lon=${selected.longitude.toFixed(5)}&radius_km=500&start=1900-01-01&page=${pageToLoad}&per_page=80`;
      const payload = await fetchJsonWithTimeout(url, 10000);
      const rows = Array.isArray(payload.events) ? payload.events : [];
      const strongest = Array.isArray(payload.strongest_events) ? payload.strongest_events : [];

      historyPage = typeof payload.page === "number" ? payload.page : pageToLoad;
      historyTotalPages = typeof payload.total_pages === "number" ? payload.total_pages : historyTotalPages;
      historyTotalEvents = typeof payload.total_events === "number" ? payload.total_events : historyTotalEvents;
      if (pageToLoad === 1) {
        historyRows = rows.slice();
      } else {
        historyRows = historyRows.concat(rows);
      }

      if (histKpiTotal) histKpiTotal.textContent = String(historyTotalEvents);
      if (histKpiSource) histKpiSource.textContent = `${payload.provider || "USGS historical archive"}${payload.from_cache ? " (cache)" : ""}`;
      if (histKpiStrongest) {
        const top = strongest[0] || null;
        histKpiStrongest.textContent = top && Number.isFinite(top.magnitude) ? `M${top.magnitude.toFixed(1)}` : "Pending";
      }

      if (historyStrongestList && pageToLoad === 1) {
        historyStrongestList.innerHTML = strongest.length > 0
          ? strongest.slice(0, 10).map((row) => {
            const mag = asMagnitude(row.magnitude);
            const depth = asDepth(row.depth_km);
            return `<li class="event-item"><strong>${magnitudeText(row.magnitude)} ${row.place || "Unknown"}</strong><br />${safeTime(row.event_time_utc)} · depth ${depth}</li>`;
          }).join("")
          : "<li class='event-item'>No strongest historical rows available.</li>";
      }

      renderHistoryList();
    };

    const hydrateBasics = (event) => {
      if (titleMagLine) titleMagLine.textContent = asMagnitudeTitle(event.magnitude);
      if (titlePlaceLine) titlePlaceLine.textContent = event.place || "Unknown location";
      if (metaLine) {
        metaLine.textContent = formatUtcMeta(event.event_time_utc, event.depth_km, event.latitude, event.longitude);
      }
      if (kpiMag) kpiMag.textContent = asMagnitude(event.magnitude);
      if (kpiDepth) kpiDepth.textContent = asDepth(event.depth_km);
      if (kpiMagCard) kpiMagCard.setAttribute("data-intensity", intensityBand(event.magnitude));
      if (kpiRegimeNote) {
        const whenUtc = formatUtcMeta(event.event_time_utc, NaN, NaN, NaN).split(" · ").slice(0, 2).join(" · ");
        kpiRegimeNote.textContent = `Event reference ${whenUtc}`;
      }
      if (contextLine) {
        contextLine.textContent = `${severityLabel(event.magnitude)} · ${parseRegion(event.place || "Regional zone")} · Inland · Automatic`;
      }
    };

    const findSelectedEvent = (events) => {
      if (!Array.isArray(events) || events.length === 0) return null;
      if (q.id) {
        const byId = events.find((row) => row.id === q.id);
        if (byId) return byId;
      }
      if (Number.isFinite(q.lat) && Number.isFinite(q.lon)) {
        const byCoord = events.find((row) =>
          Number.isFinite(row.latitude) &&
          Number.isFinite(row.longitude) &&
          Math.abs(row.latitude - q.lat) < 0.12 &&
          Math.abs(row.longitude - q.lon) < 0.12
        );
        if (byCoord) return byCoord;
      }
      if (q.time) {
        const byTime = events.find((row) => row.event_time_utc === q.time);
        if (byTime) return byTime;
      }
      if (q.place) {
        const byPlace = events.find((row) => String(row.place || "").toLowerCase() === q.place.toLowerCase());
        if (byPlace) return byPlace;
      }
      return events[0];
    };

    const buildNearbyStrong = (selected, events) =>
      events
        .filter((row) => Number.isFinite(row.magnitude) && row.magnitude >= 5)
        .map((row) => ({
          ...row,
          distanceKm:
            Number.isFinite(row.latitude) && Number.isFinite(row.longitude)
              ? haversineKm(selected.latitude, selected.longitude, row.latitude, row.longitude)
              : Number.POSITIVE_INFINITY,
        }))
        .filter((row) => row.distanceKm <= 500 && eventKey(row) !== eventKey(selected))
        .sort((a, b) => a.distanceKm - b.distanceKm || b.magnitude - a.magnitude);

    const renderNearby = (nearby) => {
      if (strongList) {
        strongList.innerHTML = nearby.length > 0
          ? nearby.slice(0, 10).map((row) => `<li class="event-item"><strong>${magnitudeText(row.magnitude)} ${row.place || "Unknown"}</strong><br />${row.distanceKm.toFixed(0)} km · ${safeTime(row.event_time_utc)}</li>`).join("")
          : "<li class='event-item'>No M5+ events within 500 km in current feed window.</li>";
      }
      if (layerStrong) {
        layerStrong.clearLayers();
        nearby.slice(0, 20).forEach((row) => {
          if (!Number.isFinite(row.latitude) || !Number.isFinite(row.longitude)) return;
          window.L.circleMarker([row.latitude, row.longitude], {
            radius: Math.max(5, Math.min(11, 2 + row.magnitude)),
            color: "rgba(255,255,255,0.9)",
            weight: 1,
            fillColor: magnitudeColor(row.magnitude),
            fillOpacity: 0.82,
          }).bindTooltip(`M${row.magnitude.toFixed(1)} · ${row.distanceKm.toFixed(0)} km`).addTo(layerStrong);
        });
      }
      if (layerStrong) {
        const count = nearby.filter((row) => row.distanceKm <= 500).length;
        if (layerStrong && layerStrong.getLayers().length === 0 && layerStrong) {
          // no-op
        }
        if (layerStrong && document.querySelector("#event-layer-strong")) {
          document.querySelector("#event-layer-strong").textContent = `Strong nearby: ${count}`;
        }
      }
    };

    const renderRings = (selected, events) => {
      const rings = [100, 250, 500].map((km) => {
        const countM4 = events.filter((row) => {
          if (!Number.isFinite(row.latitude) || !Number.isFinite(row.longitude) || !Number.isFinite(row.magnitude)) return false;
          return row.magnitude >= 4 && haversineKm(selected.latitude, selected.longitude, row.latitude, row.longitude) <= km;
        }).length;
        const countM5 = events.filter((row) => {
          if (!Number.isFinite(row.latitude) || !Number.isFinite(row.longitude) || !Number.isFinite(row.magnitude)) return false;
          return row.magnitude >= 5 && haversineKm(selected.latitude, selected.longitude, row.latitude, row.longitude) <= km;
        }).length;
        return { km, countM4, countM5 };
      });
      if (ringList) {
        ringList.innerHTML = rings
          .map((row) => `<li class="event-item"><strong>${row.km} km ring</strong><br />M4+: ${row.countM4} · M5+: ${row.countM5}</li>`)
          .join("");
      }
    };

    const renderRegionContext = (selected, events) => {
      const regionCounter = new Map();
      events.forEach((row) => {
        if (!Number.isFinite(row.latitude) || !Number.isFinite(row.longitude) || !Number.isFinite(row.magnitude) || row.magnitude < 5) return;
        const km = haversineKm(selected.latitude, selected.longitude, row.latitude, row.longitude);
        if (km > 900) return;
        const region = parseRegion(row.place || "");
        regionCounter.set(region, (regionCounter.get(region) || 0) + 1);
      });
      const top = [...regionCounter.entries()].sort((a, b) => b[1] - a[1]).slice(0, 6);
      if (regionContextList) {
        regionContextList.innerHTML = top.length > 0
          ? top.map(([region, count]) => `<li class="event-item"><strong>${region}</strong><br />${count} strong events in wider 900 km context</li>`).join("")
          : "<li class='event-item'>No strong regional context in current feed window.</li>";
      }
      if (regionConsoleList) {
        regionConsoleList.innerHTML = top.length > 0
          ? top.slice(0, 3).map(([region, count]) => `<li class="event-item"><strong>${region}</strong><br />${count} strong events in wider regional frame</li>`).join("")
          : "<li class='event-item'>Regional synthesis pending from current window.</li>";
      }
      if (regionCanvas) {
        regionCanvas.textContent = top.length > 0
          ? `Active regional frame: ${top.map(([region]) => region).slice(0, 2).join(" · ")}. Seismic clustering signals are being tracked for deeper synthesis.`
          : "Regional synthesis panel active. Awaiting stronger clustering signals in the current frame.";
      }
    };

    const loadTectonicData = async () => {
      const lat = Number.isFinite(q.lat) ? q.lat : null;
      const lon = Number.isFinite(q.lon) ? q.lon : null;
      const focus = lat !== null && lon !== null
        ? `&lat=${lat.toFixed(5)}&lon=${lon.toFixed(5)}&radius_km=900`
        : "";
      const payload = await fetchJsonWithTimeout(`/api/tectonic-context.php?scope=local${focus}&max_plates=30&max_faults=48`, 9000);
      return {
        plates: payload && typeof payload === "object" ? payload.plates : null,
        faults: payload && typeof payload === "object" ? payload.faults : null,
      };
    };

    const renderTectonic = (selected, tectonic) => {
      const plateFeatures = Array.isArray(tectonic.plates?.features) ? tectonic.plates.features : [];
      const faultFeatures = Array.isArray(tectonic.faults?.features) ? tectonic.faults.features : [];

      const nearbyPlates = plateFeatures
        .map((feature) => ({ feature, km: nearestFeatureDistanceKm(feature, selected.latitude, selected.longitude) }))
        .filter((row) => Number.isFinite(row.km))
        .sort((a, b) => a.km - b.km)
        .slice(0, 20);
      const nearbyFaults = faultFeatures
        .map((feature) => ({ feature, km: nearestFeatureDistanceKm(feature, selected.latitude, selected.longitude) }))
        .filter((row) => Number.isFinite(row.km))
        .sort((a, b) => a.km - b.km)
        .slice(0, 24);

      if (plateLayer) {
        plateLayer.clearLayers();
        nearbyPlates.forEach((row) => {
          window.L.geoJSON(row.feature, {
            style: { color: "#22d3ee", weight: 2.1, opacity: 0.8 },
          }).addTo(plateLayer);
        });
      }
      if (faultLayer) {
        faultLayer.clearLayers();
        nearbyFaults.forEach((row) => {
          window.L.geoJSON(row.feature, {
            style: { color: "#ff7a5f", weight: 1.5, opacity: 0.64 },
          }).addTo(faultLayer);
        });
      }

      const nearestPlateKm = nearbyPlates.length > 0 ? nearbyPlates[0].km : Number.POSITIVE_INFINITY;
      const nearestFault = nearbyFaults.length > 0 ? nearbyFaults[0] : null;
      const nearestFaultKm = nearestFault ? nearestFault.km : Number.POSITIVE_INFINITY;
      const regime = regimeLabel(selected, nearestFaultKm, nearestPlateKm);

      if (kpiPlateDistance) {
        kpiPlateDistance.textContent = asDistance(nearestPlateKm);
      }
      if (kpiRegime) {
        kpiRegime.textContent = regime;
      }
      if (layerPlates) {
        layerPlates.textContent = `Plates: ${nearbyPlates.length > 0 ? "loaded" : "unavailable"}`;
      }
      if (layerFaults) {
        layerFaults.textContent = `Faults: ${nearbyFaults.length > 0 ? "loaded" : "unavailable"}`;
      }

      if (faultList) {
        faultList.innerHTML = nearbyFaults.length > 0
          ? nearbyFaults.slice(0, 5).map((row) => {
            const name = getFeatureName(row.feature);
            const slip = getSlipRate(row.feature);
            return `<li class="event-item"><strong>${name}</strong><br />${row.km.toFixed(0)} km · slip ${slip}</li>`;
          }).join("")
          : "<li class='event-item'>No nearby active faults available from current dataset.</li>";
      }

      if (zoneSummary) {
        const faultName = nearestFault ? getFeatureName(nearestFault.feature) : "no resolved active fault";
        zoneSummary.textContent = `${regime}. Nearest active fault: ${faultName}. Boundary proximity: ${asDistance(nearestPlateKm)}.`;
      }
    };

    const renderTectonicFallback = (selected, nearbyStrong) => {
      if (plateLayer) {
        plateLayer.clearLayers();
        fallbackPlateLines.forEach((line) => {
          window.L.polyline(line, {
            color: "#22d3ee",
            weight: 1.5,
            opacity: 0.75,
            dashArray: "6 6",
          }).addTo(plateLayer);
        });
      }
      if (faultLayer) {
        faultLayer.clearLayers();
      }

      const nearestProxyPlateKm = fallbackPlateLines
        .flatMap((line) => line)
        .map(([lat, lon]) => haversineKm(selected.latitude, selected.longitude, lat, lon))
        .sort((a, b) => a - b)[0];
      const localSignals = nearbyStrong.slice(0, 6);

      if (kpiPlateDistance) {
        kpiPlateDistance.textContent = asDistance(nearestProxyPlateKm, { approximate: true });
      }
      if (kpiRegime) {
        const regime = regimeLabel(selected, 999, Number.isFinite(nearestProxyPlateKm) ? nearestProxyPlateKm : 999);
        kpiRegime.textContent = `${regime} (proxy)`;
      }
      if (layerPlates) layerPlates.textContent = "Plates: proxy loaded";
      if (layerFaults) layerFaults.textContent = "Faults: operational proxy";

      if (faultList) {
        faultList.innerHTML = localSignals.length > 0
          ? localSignals.map((row) => {
            return `<li class="event-item"><strong>${row.place || "Regional seismic line"}</strong><br />${row.distanceKm.toFixed(0)} km · ${magnitudeText(row.magnitude)} signal</li>`;
          }).join("")
          : "<li class='event-item'>No nearby strong seismic proxy signals in current feed.</li>";
      }

      if (zoneSummary) {
        const depth = asDepth(selected.depth_km);
        zoneSummary.textContent = `Operational mode: external tectonic layers unavailable. Local seismic proxy active (${localSignals.length} nearby strong signals), depth ${depth}, inferred regime computed with fallback geometry.`;
      }
    };

    let spatialSyncScheduled = false;

    const syncSpatialHeights = () => {
      if (!spatialMapCard || !spatialSideColumn) return;
      if (window.matchMedia("(max-width: 1120px)").matches) {
        spatialSideColumn.style.height = "";
        return;
      }
      spatialSideColumn.style.height = `${spatialMapCard.offsetHeight}px`;
    };

    const scheduleSpatialSync = () => {
      if (spatialSyncScheduled) return;
      spatialSyncScheduled = true;
      window.requestAnimationFrame(() => {
        spatialSyncScheduled = false;
        syncSpatialHeights();
      });
    };

    window.addEventListener("resize", scheduleSpatialSync);

    const init = async () => {
      if (!Number.isFinite(q.lat) || !Number.isFinite(q.lon)) {
        setFailure("Event coordinates missing. Open this page from the event list in Earthquakes.");
        return;
      }

      let events = [];
      try {
        const eqPayload = await fetchJsonWithTimeout("/api/earthquakes.php", 10000);
        events = Array.isArray(eqPayload.events) ? eqPayload.events : [];
      } catch (error) {
        if (zoneSummary) zoneSummary.textContent = "Live feed unavailable. Showing best available event context.";
      }

      const selected = findSelectedEvent(events) || {
        id: q.id,
        place: q.place || "Unknown location",
        event_time_utc: q.time || null,
        magnitude: Number.isFinite(q.mag) ? q.mag : NaN,
        depth_km: Number.isFinite(q.depth) ? q.depth : NaN,
        latitude: q.lat,
        longitude: q.lon,
      };

      if (!Number.isFinite(selected.latitude) || !Number.isFinite(selected.longitude)) {
        setFailure("Coordinates unavailable for selected event.");
        return;
      }

      try {
        hydrateBasics(selected);
        const theMap = ensureMap();
        if (theMap && eventLayer) {
          eventLayer.clearLayers();
          window.L.circleMarker([selected.latitude, selected.longitude], {
            radius: 11,
            color: "rgba(255,255,255,0.96)",
            weight: 2,
            fillColor: magnitudeColor(selected.magnitude),
            fillOpacity: 0.95,
          }).bindTooltip("Selected event", { direction: "top", opacity: 0.95 }).addTo(eventLayer);

          theMap.setView([selected.latitude, selected.longitude], 6);
        }
        scheduleSpatialSync();
      } catch (error) {
        if (zoneSummary) zoneSummary.textContent = "Map rendered in reduced mode. Context modules are still loading.";
      }

      let nearbyStrong = [];
      try {
        nearbyStrong = buildNearbyStrong(selected, events);
        renderNearby(nearbyStrong);
        renderRings(selected, events);
        renderRegionContext(selected, events);
      } catch (error) {
        if (strongList) strongList.innerHTML = "<li class='event-item'>Nearby strong context loading in reduced mode.</li>";
      }

      try {
        await loadAndRenderShakeMap(selected);
        const tectonic = await loadTectonicData();
        const hasTectonic = Array.isArray(tectonic?.plates?.features) || Array.isArray(tectonic?.faults?.features);
        if (hasTectonic) {
          renderTectonic(selected, tectonic);
        } else {
          renderTectonicFallback(selected, nearbyStrong);
        }
        scheduleSpatialSync();
      } catch (error) {
        renderTectonicFallback(selected, nearbyStrong);
        scheduleSpatialSync();
      }

      try {
        await loadHistoryPage(selected, 1);
        historyMoreButton?.addEventListener("click", async () => {
          if (historyPage >= historyTotalPages) {
            return;
          }
          historyMoreButton.disabled = true;
          try {
            await loadHistoryPage(selected, historyPage + 1);
          } finally {
            historyMoreButton.disabled = false;
          }
        });
      } catch (error) {
        try {
          buildHistoryFallbackFromFeed(selected, events);
        } catch (fallbackError) {
          if (historyMeta) historyMeta.textContent = "Awaiting archive data";
        }
      }

      scheduleSpatialSync();
    };

    init();
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
