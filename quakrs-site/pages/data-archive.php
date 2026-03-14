<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Data Archive';
$pageDescription = 'Searchable earthquake archive with global map location autocomplete.';
$currentPage = 'data-archive';
$includeLeaflet = true;

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Data / Archive</p>
    <h1>Searchable Seismic Archive.</h1>
    <p class="sub">Use filters + interactive map to select a center point and radius, then inspect matching earthquakes.</p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Visible Events</p>
    <p id="archive-kpi-visible" class="kpi-value">--</p>
    <p id="archive-kpi-visible-note" class="kpi-note">Rows loaded on current page</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Dataset Size</p>
    <p id="archive-kpi-total" class="kpi-value">--</p>
    <p class="kpi-note">Filtered archive count</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Max Magnitude</p>
    <p id="archive-kpi-max-mag" class="kpi-value">--</p>
    <p class="kpi-note">Within active filters</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Latest Event</p>
    <p id="archive-kpi-latest" class="kpi-value">--</p>
    <p id="archive-kpi-source" class="kpi-note">Loading source...</p>
  </article>
</section>

<section class="panel">
  <article class="card">
    <div class="feed-head">
      <h3>Filters</h3>
      <p class="feed-meta">Applied server-side on full archive</p>
    </div>
    <div class="archive-filter-grid">
      <label class="event-item archive-filter-item">
        <strong>Finestra temporale</strong><br />
        <select id="archive-filter-window">
          <option value="24h">Ultime 24 ore</option>
          <option value="7d">Ultimi 7 giorni</option>
          <option value="30d" selected>Ultimi 30 giorni</option>
          <option value="90d">Ultimi 90 giorni</option>
          <option value="1y">Ultimo 1 anno</option>
          <option value="all">Tutti i dati disponibili</option>
          <option value="custom">Personalizzato</option>
        </select>
      </label>
      <label class="event-item archive-filter-item">
        <strong>Location</strong><br />
        <input id="archive-filter-location" type="search" list="archive-location-list" placeholder="Search any place in the world..." autocomplete="off" />
        <datalist id="archive-location-list"></datalist>
      </label>
      <label class="event-item archive-filter-item">
        <strong>Min Magnitude</strong><br />
        <select id="archive-filter-mag">
          <option value="0">M0+</option>
          <option value="2">M2+</option>
          <option value="3">M3+</option>
          <option value="4">M4+</option>
          <option value="5">M5+</option>
          <option value="6">M6+</option>
        </select>
      </label>
      <label class="event-item archive-filter-item">
        <strong>Depth Band</strong><br />
        <select id="archive-filter-depth">
          <option value="all">All depths</option>
          <option value="shallow">Shallow (0-70 km)</option>
          <option value="intermediate">Intermediate (70-300 km)</option>
          <option value="deep">Deep (300+ km)</option>
        </select>
      </label>
    </div>
    <div class="preset-row">
      <button id="archive-search-btn" class="btn btn-ghost" type="button">Cerca</button>
      <button id="archive-custom-range-btn" class="btn btn-ghost" type="button">Imposta intervallo personalizzato</button>
      <span id="archive-custom-range-label" class="kpi-note">Nessun intervallo personalizzato attivo</span>
      <button id="archive-reset-filters" class="btn btn-ghost" type="button">Reset Filters</button>
    </div>
    <div id="archive-search-feedback" class="archive-search-feedback" aria-live="polite" aria-atomic="true">
      <div class="archive-search-feedback-track">
        <span id="archive-search-feedback-bar" class="archive-search-feedback-bar" style="width:0%"></span>
      </div>
      <p id="archive-search-feedback-text" class="feed-meta">Pronto</p>
    </div>
  </article>
</section>

<dialog id="archive-custom-range-dialog" class="archive-dialog">
  <form method="dialog" class="archive-dialog-card">
    <h3>Intervallo personalizzato</h3>
    <p class="kpi-note">Seleziona data inizio e data fine per il filtro storico.</p>
    <label class="archive-dialog-field">
      <strong>Data inizio</strong>
      <input id="archive-filter-from" type="date" />
    </label>
    <label class="archive-dialog-field">
      <strong>Data fine</strong>
      <input id="archive-filter-to" type="date" />
    </label>
    <div class="preset-row">
      <button id="archive-custom-range-cancel" class="btn btn-ghost" type="button">Annulla</button>
      <button id="archive-custom-range-apply" class="btn btn-ghost" type="button">Applica intervallo</button>
    </div>
  </form>
</dialog>

<section class="panel panel-main earthquakes-main-layout archive-map-layout">
  <article class="map-card archive-map-card">
    <div class="feed-head">
      <h3>Archive Map</h3>
      <p id="archive-map-meta" class="feed-meta">Click map to set center point</p>
    </div>
    <div class="archive-radius-control event-item">
      <label for="archive-filter-radius"><strong>Radius</strong></label>
      <input id="archive-filter-radius" type="range" min="5" max="1200" step="5" value="120" />
      <span id="archive-radius-value" class="archive-radius-value">120 km</span>
    </div>
    <p id="archive-center-status" class="kpi-note archive-center-status">Center not set. Use localita or click on the map.</p>
    <div class="map-wrap">
      <div id="archive-map-leaflet" class="world-map-leaflet archive-map-leaflet" aria-label="Archive interactive map"></div>
    </div>
  </article>
  <article class="card side-card archive-list-card">
    <div class="archive-list-head">
      <h3>Matching Events</h3>
      <label class="archive-list-sort">
        <span class="sr-only">Sort matching events</span>
        <select id="archive-list-sort" aria-label="Sort matching events">
          <option value="date_desc" selected>Data ↓</option>
          <option value="date_asc">Data ↑</option>
          <option value="mag_desc">Magnitudo ↓</option>
          <option value="mag_asc">Magnitudo ↑</option>
        </select>
      </label>
    </div>
    <p id="archive-feed-meta" class="feed-meta">Loading archive...</p>
    <ul id="archive-map-list" class="events-list live-feed-scroll archive-map-list">
      <li class="event-item">Loading archived events...</li>
    </ul>
  </article>
</section>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Filter Insight</h3>
    <p id="archive-insight-summary" class="insight-lead">Apply filters to generate an operational summary.</p>
  </article>
  <article class="card page-card">
    <h3>Radius &amp; Coverage</h3>
    <p id="archive-insight-depth" class="insight-lead">Depth and regional composition will appear here.</p>
  </article>
  <article class="card page-card">
    <h3>Source Blend</h3>
    <div id="archive-insight-providers" class="insight-pills">
      <span class="insight-pill">Loading providers...</span>
    </div>
  </article>
</section>

<script>
  (() => {
    const mapContainer = document.querySelector("#archive-map-leaflet");
    const mapList = document.querySelector("#archive-map-list");
    const mapMeta = document.querySelector("#archive-map-meta");
    const feedMeta = document.querySelector("#archive-feed-meta");
    const listSort = document.querySelector("#archive-list-sort");

    const filterWindow = document.querySelector("#archive-filter-window");
    const filterFrom = document.querySelector("#archive-filter-from");
    const filterTo = document.querySelector("#archive-filter-to");
    const searchBtn = document.querySelector("#archive-search-btn");
    const customRangeBtn = document.querySelector("#archive-custom-range-btn");
    const customRangeLabel = document.querySelector("#archive-custom-range-label");
    const customRangeDialog = document.querySelector("#archive-custom-range-dialog");
    const customRangeApply = document.querySelector("#archive-custom-range-apply");
    const customRangeCancel = document.querySelector("#archive-custom-range-cancel");
    const filterLocation = document.querySelector("#archive-filter-location");
    const locationList = document.querySelector("#archive-location-list");
    const filterRadius = document.querySelector("#archive-filter-radius");
    const radiusValue = document.querySelector("#archive-radius-value");
    const centerStatus = document.querySelector("#archive-center-status");

    const filterMag = document.querySelector("#archive-filter-mag");
    const filterDepth = document.querySelector("#archive-filter-depth");
    const resetFilters = document.querySelector("#archive-reset-filters");
    const searchFeedback = document.querySelector("#archive-search-feedback");
    const searchFeedbackBar = document.querySelector("#archive-search-feedback-bar");
    const searchFeedbackText = document.querySelector("#archive-search-feedback-text");

    const kpiVisible = document.querySelector("#archive-kpi-visible");
    const kpiVisibleNote = document.querySelector("#archive-kpi-visible-note");
    const kpiTotal = document.querySelector("#archive-kpi-total");
    const kpiMaxMag = document.querySelector("#archive-kpi-max-mag");
    const kpiLatest = document.querySelector("#archive-kpi-latest");
    const kpiSource = document.querySelector("#archive-kpi-source");

    const insightSummary = document.querySelector("#archive-insight-summary");
    const insightDepth = document.querySelector("#archive-insight-depth");
    const insightProviders = document.querySelector("#archive-insight-providers");

    const perPage = 220;
    let debounceTimer = null;

    let map = null;
    let tileLayer = null;
    let centerMarker = null;
    let radiusCircle = null;
    let selectedRowKey = null;
    const eventMarkers = [];
    const markerByKey = new Map();

    let manualCenter = null;
    let resolvedCenter = null;
    let lastNonCustomWindow = "30d";
    let pinnedCenterPlace = "";
    let pendingMapFocus = false;
    let requestSeq = 0;
    let pendingFetchReason = "Caricamento archivio";
    let locationSuggestSeq = 0;
    let locationSuggestTimer = null;
    let feedbackProgressTimer = null;
    let feedbackHideTimer = null;
    let feedbackProgress = 0;
    let currentVisibleRows = [];
    const locationGeoMap = new Map();

    const escapeHtml = (value) => String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/\"/g, "&quot;")
      .replace(/'/g, "&#39;");

    const escapeAttrValue = (value) => String(value ?? "")
      .replace(/\\/g, "\\\\")
      .replace(/"/g, '\\"');

    const eventKey = (row, index) => {
      if (row && typeof row.id === "string" && row.id !== "") return row.id;
      const lat = typeof row?.latitude === "number" ? row.latitude.toFixed(3) : "na";
      const lon = typeof row?.longitude === "number" ? row.longitude.toFixed(3) : "na";
      const ts = row?.event_time_utc || "na";
      return `${lat}|${lon}|${ts}|${index}`;
    };

    const eventDetailUrl = (event) => {
      const params = new URLSearchParams();
      if (event && typeof event === "object") {
        if (typeof event.id === "string" && event.id !== "") params.set("id", event.id);
        if (typeof event.latitude === "number") params.set("lat", event.latitude.toFixed(5));
        if (typeof event.longitude === "number") params.set("lon", event.longitude.toFixed(5));
        if (typeof event.magnitude === "number") params.set("mag", event.magnitude.toFixed(2));
        if (typeof event.depth_km === "number") params.set("depth", event.depth_km.toFixed(2));
        if (event.place) params.set("place", String(event.place));
        if (event.event_time_utc) params.set("time", String(event.event_time_utc));
      }
      return `/event.php?${params.toString()}`;
    };

    const timeLabel = (iso) => {
      if (!iso) return "n/a";
      return new Date(iso).toLocaleString([], {
        year: "numeric",
        month: "short",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
      });
    };

    const classifyDepth = (depth) => {
      if (typeof depth !== "number" || Number.isNaN(depth)) return "all";
      if (depth < 70) return "shallow";
      if (depth < 300) return "intermediate";
      return "deep";
    };

    const haversineKm = (lat1, lon1, lat2, lon2) => {
      const toRad = (value) => (value * Math.PI) / 180;
      const dLat = toRad(lat2 - lat1);
      const dLon = toRad(lon2 - lon1);
      const a = Math.sin(dLat / 2) ** 2
        + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
      return 6371 * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
    };

    const radiusKm = () => {
      const value = Number(filterRadius?.value || "120");
      if (!Number.isFinite(value) || value <= 0) return 120;
      return Math.max(5, Math.min(1200, value));
    };

    const hasActiveCenter = () => (
      !!resolvedCenter
      && typeof resolvedCenter.latitude === "number"
      && typeof resolvedCenter.longitude === "number"
    );

    const hasCenterIntent = () => {
      if (manualCenter && typeof manualCenter.latitude === "number" && typeof manualCenter.longitude === "number") {
        return true;
      }
      const location = normalizeSelectedLocation();
      const centerPlace = String(pinnedCenterPlace || location || "").trim();
      return centerPlace !== "";
    };

    const syncRadiusControl = () => {
      const enabled = hasActiveCenter();
      if (filterRadius instanceof HTMLInputElement) {
        filterRadius.disabled = !enabled;
      }
      if (radiusValue) {
        radiusValue.textContent = enabled ? `${radiusKm()} km` : "off";
      }
    };

    const setRadiusLabel = () => {
      syncRadiusControl();
    };

    const setActionButtonsBusy = (busy, label = "Cerca") => {
      if (searchBtn instanceof HTMLButtonElement) {
        searchBtn.disabled = busy;
        searchBtn.textContent = busy ? label : "Cerca";
      }
      if (resetFilters instanceof HTMLButtonElement) {
        resetFilters.disabled = busy;
        resetFilters.textContent = busy ? "Reset..." : "Reset Filters";
      }
    };

    const stopFeedbackTimers = () => {
      if (feedbackProgressTimer) {
        window.clearInterval(feedbackProgressTimer);
        feedbackProgressTimer = null;
      }
      if (feedbackHideTimer) {
        window.clearTimeout(feedbackHideTimer);
        feedbackHideTimer = null;
      }
    };

    const updateFeedbackUi = (percent, message, { active = true, error = false } = {}) => {
      if (!searchFeedback || !searchFeedbackBar || !searchFeedbackText) return;
      searchFeedback.classList.toggle("is-active", active);
      searchFeedback.classList.toggle("is-error", !!error);
      searchFeedbackBar.style.width = `${Math.max(0, Math.min(100, percent))}%`;
      searchFeedbackText.textContent = `${message} · ${Math.round(percent)}%`;
    };

    const beginFeedbackProgress = (reason) => {
      stopFeedbackTimers();
      feedbackProgress = Math.max(6, Math.min(18, feedbackProgress || 8));
      updateFeedbackUi(feedbackProgress, reason || "Ricerca in corso", { active: true, error: false });
      feedbackProgressTimer = window.setInterval(() => {
        feedbackProgress = Math.min(92, feedbackProgress + (Math.random() * 9 + 2));
        updateFeedbackUi(feedbackProgress, reason || "Ricerca in corso", { active: true, error: false });
      }, 180);
    };

    const finishFeedbackProgress = (ok, message) => {
      stopFeedbackTimers();
      if (ok) {
        feedbackProgress = 100;
        updateFeedbackUi(100, message || "Completato", { active: true, error: false });
        feedbackHideTimer = window.setTimeout(() => {
          updateFeedbackUi(0, "Pronto", { active: false, error: false });
          feedbackProgress = 0;
        }, 850);
      } else {
        const stuckAt = Math.max(8, Math.min(95, feedbackProgress || 12));
        updateFeedbackUi(stuckAt, message || "Errore durante la ricerca", { active: true, error: true });
      }
    };

    const formatDateRangeLabel = () => {
      const from = String(filterFrom?.value || "").trim();
      const to = String(filterTo?.value || "").trim();
      if (!customRangeLabel) return;
      if (from === "" || to === "") {
        customRangeLabel.textContent = "Nessun intervallo personalizzato attivo";
        return;
      }
      customRangeLabel.textContent = `${from} → ${to}`;
    };

    const openCustomRangeDialog = () => {
      const now = new Date();
      const toIso = now.toISOString().slice(0, 10);
      const fromIso = new Date(now.getTime() - (30 * 24 * 60 * 60 * 1000)).toISOString().slice(0, 10);
      if (filterFrom && String(filterFrom.value || "").trim() === "") filterFrom.value = fromIso;
      if (filterTo && String(filterTo.value || "").trim() === "") filterTo.value = toIso;
      if (customRangeDialog && typeof customRangeDialog.showModal === "function") {
        if (customRangeDialog.open) return;
        customRangeDialog.showModal();
      }
    };

    const parseIsoDate = (iso) => {
      if (!/^\d{4}-\d{2}-\d{2}$/.test(iso)) return null;
      const d = new Date(`${iso}T00:00:00`);
      return Number.isNaN(d.getTime()) ? null : d;
    };

    const setCenterStatus = (center) => {
      if (!centerStatus) return;
      if (!center || typeof center.latitude !== "number" || typeof center.longitude !== "number") {
        centerStatus.textContent = "Center not set. Select a valid location or click on the map (radius disabled).";
        syncRadiusControl();
        return;
      }
      const name = center.name ? String(center.name) : "Custom point";
      centerStatus.textContent = `Center: ${name} (${center.latitude.toFixed(3)}, ${center.longitude.toFixed(3)})`;
      syncRadiusControl();
    };

    const ensureMap = () => {
      if (!mapContainer || !window.L) return;
      if (map) return;

      map = window.L.map(mapContainer, { zoomControl: true, worldCopyJump: true, preferCanvas: true }).setView([16, 8], 2);
      tileLayer = window.L.tileLayer("https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png", {
        attribution: "&copy; OpenStreetMap contributors &copy; CARTO",
      });
      tileLayer.addTo(map);

      map.on("click", (event) => {
        const lat = event?.latlng?.lat;
        const lon = event?.latlng?.lng;
        if (typeof lat !== "number" || typeof lon !== "number") return;

        manualCenter = {
          name: `Custom point ${lat.toFixed(3)}, ${lon.toFixed(3)}`,
          latitude: lat,
          longitude: lon,
          source: "manual-click",
        };
        resolvedCenter = manualCenter;
        drawCenterGeometry();
        setCenterStatus(resolvedCenter);
        if (mapMeta) {
          mapMeta.textContent = `Center ${resolvedCenter.name || "selected"} set. Press Cerca to apply filters.`;
        }
      });
    };

    const clearMapEvents = () => {
      eventMarkers.forEach((marker) => marker.remove());
      eventMarkers.length = 0;
      markerByKey.clear();
    };

    const drawCenterGeometry = () => {
      if (!map) return;
      if (centerMarker) {
        centerMarker.remove();
        centerMarker = null;
      }
      if (radiusCircle) {
        radiusCircle.remove();
        radiusCircle = null;
      }

      if (!resolvedCenter || typeof resolvedCenter.latitude !== "number" || typeof resolvedCenter.longitude !== "number") {
        return;
      }

      const latLng = [resolvedCenter.latitude, resolvedCenter.longitude];
      centerMarker = window.L.circleMarker(latLng, {
        radius: 7,
        weight: 2,
        color: "#ff5f45",
        fillColor: "#ff7a5f",
        fillOpacity: 0.8,
      }).addTo(map);

      radiusCircle = window.L.circle(latLng, {
        radius: radiusKm() * 1000,
        color: "#ff7a5f",
        weight: 1.2,
        fillColor: "#ff7a5f",
        fillOpacity: 0.09,
      }).addTo(map);
    };

    const markerRadius = (mag) => {
      if (typeof mag !== "number") return 3.5;
      return Math.max(3.5, Math.min(10, 2.4 + (mag * 0.95)));
    };

    const magnitudeBandClass = (magnitude) => {
      if (!Number.isFinite(magnitude)) return "m-na";
      const bucket = Math.max(1, Math.min(9, Math.floor(magnitude)));
      return `m-b${bucket}`;
    };

    const magnitudeColor = (magnitude) => {
      if (!Number.isFinite(magnitude)) return "#6b7280";
      const bucket = Math.max(1, Math.min(9, Math.floor(magnitude)));
      const palette = {
        1: "#3b82f6",
        2: "#06b6d4",
        3: "#14b8a6",
        4: "#22c55e",
        5: "#eab308",
        6: "#f59e0b",
        7: "#f97316",
        8: "#d946ef",
        9: "#7e22ce",
      };
      return palette[bucket] || "#6b7280";
    };

    const targetZoomForRadius = () => {
      const r = radiusKm();
      if (r <= 30) return 10;
      if (r <= 60) return 9;
      if (r <= 120) return 8;
      if (r <= 220) return 7;
      if (r <= 520) return 6;
      return 5;
    };

    const normalizeSelectedLocation = () => {
      const raw = String(filterLocation?.value || "").trim();
      if (raw === "") return "";
      return raw;
    };

    const selectedSuggestion = () => {
      const key = normalizeSelectedLocation();
      if (key === "") return null;
      return locationGeoMap.get(key) || null;
    };

    const locationRank = (item) => {
      if (!item || typeof item !== "object") return 0;
      const addresstype = String(item.addresstype || item.type || "").toLowerCase();
      const clazz = String(item.class || "").toLowerCase();
      const type = String(item.type || "").toLowerCase();
      const importance = Number(item.importance || 0);
      let score = importance;
      if (clazz === "place") score += 8;
      if (["city", "town", "village", "hamlet", "suburb", "quarter", "municipality"].includes(addresstype)) score += 18;
      if (["country", "state", "region", "province", "county"].includes(addresstype)) score += 10;
      if (["administrative"].includes(clazz) && !["country", "state", "region", "province", "county"].includes(addresstype)) score -= 2;
      if (["industrial", "commercial", "farm", "residential"].includes(type)) score -= 8;
      return score;
    };

    const isSettlementResult = (item) => {
      const kind = String(item?.addresstype || item?.type || "").toLowerCase();
      return ["city", "town", "village", "hamlet", "suburb", "quarter", "municipality"].includes(kind);
    };

    const buildResolvedLocation = (item, fallbackName) => {
      const lat = Number(item?.lat);
      const lon = Number(item?.lon);
      if (!Number.isFinite(lat) || !Number.isFinite(lon)) return null;
      return {
        name: String(item?.display_name || fallbackName || ""),
        latitude: lat,
        longitude: lon,
        source: "geocode",
      };
    };

    const parseCityCountryHint = (location) => {
      const parts = String(location || "")
        .split(",")
        .map((v) => v.trim())
        .filter((v) => v !== "");
      if (parts.length < 2) return null;
      return {
        city: parts[0],
        country: parts[parts.length - 1],
      };
    };

    const renderMapEvents = (rows) => {
      if (!map || !Array.isArray(rows)) return;
      clearMapEvents();

      rows.forEach((row, index) => {
        if (typeof row?.latitude !== "number" || typeof row?.longitude !== "number") {
          return;
        }

        const key = eventKey(row, index);
        const magValue = typeof row.magnitude === "number" ? row.magnitude : NaN;
        const magBand = magnitudeBandClass(magValue);
        const magColor = magnitudeColor(magValue);
        const marker = window.L.circleMarker([row.latitude, row.longitude], {
          radius: markerRadius(row.magnitude),
          color: magColor,
          weight: 1.2,
          fillColor: magColor,
          fillOpacity: 0.78,
        });

        const mag = typeof row.magnitude === "number" ? `M${row.magnitude.toFixed(1)}` : "M?";
        const depth = typeof row.depth_km === "number" ? `${row.depth_km.toFixed(1)} km` : "n/a";
        const place = escapeHtml(row.place || "Unknown location");
        const when = escapeHtml(timeLabel(row.event_time_utc));
        const url = eventDetailUrl(row);

        marker.bindPopup(
          `<strong><span class="mag-value ${magBand}">${mag}</span> ${place}</strong><br/>${when} | Depth ${depth}<br/><a href="${url}">Open event</a>`
        );

        marker.on("click", () => {
          selectedRowKey = key;
          highlightRowByKey(key);
        });

        marker.addTo(map);
        eventMarkers.push(marker);
        markerByKey.set(key, marker);
      });

      if (!resolvedCenter && eventMarkers.length > 0) {
        const group = window.L.featureGroup(eventMarkers);
        const bounds = group.getBounds();
        if (bounds.isValid()) {
          map.fitBounds(bounds.pad(0.15), { maxZoom: pendingMapFocus ? 9 : 5 });
          if (pendingMapFocus) {
            pendingMapFocus = false;
          }
        }
      } else if (resolvedCenter) {
        const zoom = targetZoomForRadius();
        if (pendingMapFocus) {
          map.flyTo([resolvedCenter.latitude, resolvedCenter.longitude], zoom, { duration: 0.45 });
          pendingMapFocus = false;
        } else {
          map.setView([resolvedCenter.latitude, resolvedCenter.longitude], zoom);
        }
      } else if (pendingMapFocus && map) {
        map.setView([16, 8], 2);
        pendingMapFocus = false;
      }
    };

    const renderList = (rows) => {
      if (!mapList) return;
      if (!Array.isArray(rows) || rows.length === 0) {
        mapList.innerHTML = "<li class='event-item'>No events match current filters.</li>";
        return;
      }

      const center = resolvedCenter;
      const enriched = rows.map((row, index) => {
        const key = eventKey(row, index);
        const hasCenter = center && typeof center.latitude === "number" && typeof center.longitude === "number";
        const hasCoords = typeof row.latitude === "number" && typeof row.longitude === "number";
        const distance = hasCenter && hasCoords ? haversineKm(center.latitude, center.longitude, row.latitude, row.longitude) : null;
        return { row, index, key, distance };
      });

      mapList.innerHTML = enriched.slice(0, 160).map(({ row, key, distance }) => {
        const magValue = typeof row.magnitude === "number" ? row.magnitude : NaN;
        const magBand = magnitudeBandClass(magValue);
        const mag = typeof row.magnitude === "number" ? `M${row.magnitude.toFixed(1)}` : "M?";
        const depth = typeof row.depth_km === "number" ? `${row.depth_km.toFixed(1)} km` : "n/a";
        const when = timeLabel(row.event_time_utc);
        const place = row.place || "Unknown location";
        const dist = distance !== null ? ` | ${distance.toFixed(0)} km` : "";
        const url = eventDetailUrl(row);

        return `
          <li class="event-item event-item-clickable archive-map-item${selectedRowKey === key ? " is-active" : ""}" data-key="${escapeHtml(key)}" data-url="${escapeHtml(url)}">
            <strong><span class="mag-value ${magBand}">${escapeHtml(mag)}</span> ${escapeHtml(place)}</strong><br />
            <span class="archive-result-meta">${escapeHtml(when)} | Depth ${escapeHtml(depth)}${escapeHtml(dist)}</span>
          </li>
        `;
      }).join("");
    };

    const highlightRowByKey = (key) => {
      if (!mapList || !key) return;
      mapList.querySelectorAll(".archive-map-item.is-active").forEach((node) => node.classList.remove("is-active"));
      const target = mapList.querySelector(`.archive-map-item[data-key="${escapeAttrValue(key)}"]`);
      if (target) {
        target.classList.add("is-active");
        target.scrollIntoView({ block: "nearest", behavior: "smooth" });
      }
    };

    const fetchLocationSuggestions = async (query) => {
      const q = String(query || "").trim();
      if (q.length < 2) {
        if (locationList) locationList.innerHTML = "";
        locationGeoMap.clear();
        return;
      }
      const seq = ++locationSuggestSeq;
      const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=12&dedupe=1&addressdetails=1&accept-language=it,en&q=${encodeURIComponent(q)}`;
      try {
        const response = await fetch(url, { headers: { Accept: "application/json" } });
        if (!response.ok || seq !== locationSuggestSeq) return;
        const payload = await response.json();
        if (!Array.isArray(payload) || !locationList) return;
        const ranked = payload
          .filter((item) => item && typeof item === "object")
          .map((item) => ({ item, score: locationRank(item) }))
          .sort((a, b) => b.score - a.score)
          .slice(0, 10)
          .map((entry) => entry.item);
        locationList.innerHTML = "";
        locationGeoMap.clear();
        const seenDisplay = new Set();
        ranked.forEach((item) => {
          const display = String(item.display_name || "").trim();
          const lat = Number(item.lat);
          const lon = Number(item.lon);
          if (display === "" || !Number.isFinite(lat) || !Number.isFinite(lon)) return;
          const key = display.toLowerCase();
          if (seenDisplay.has(key)) return;
          seenDisplay.add(key);
          const option = document.createElement("option");
          option.value = display;
          locationList.appendChild(option);
          locationGeoMap.set(display, {
            name: display,
            latitude: lat,
            longitude: lon,
            source: "geocode-suggest",
            _meta: {
              addresstype: String(item.addresstype || ""),
              class: String(item.class || ""),
              type: String(item.type || ""),
            },
          });
        });
      } catch (error) {
        // Keep UI usable even if geocoder is temporarily unavailable.
      }
    };

    const resolveLocation = async (rawLocation) => {
      const location = String(rawLocation || "").trim();
      if (location === "") return null;
      const fromSuggest = locationGeoMap.get(location);
      if (fromSuggest) return fromSuggest;
      const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=6&dedupe=1&addressdetails=1&accept-language=it,en&q=${encodeURIComponent(location)}`;
      try {
        const response = await fetch(url, { headers: { Accept: "application/json" } });
        if (!response.ok) return null;
        const payload = await response.json();
        const ranked = Array.isArray(payload)
          ? payload
              .filter((item) => item && typeof item === "object")
              .map((item) => ({ item, score: locationRank(item) }))
              .sort((a, b) => b.score - a.score)
              .map((entry) => entry.item)
          : [];
        const first = ranked.length > 0 ? ranked[0] : null;
        if (!first) return null;

        // If first result looks administrative for a city-like query, try a city-focused structured lookup.
        const hint = parseCityCountryHint(location);
        if (hint && !isSettlementResult(first)) {
          const structuredUrl = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=5&dedupe=1&addressdetails=1&accept-language=it,en&city=${encodeURIComponent(hint.city)}&country=${encodeURIComponent(hint.country)}`;
          const structuredRes = await fetch(structuredUrl, { headers: { Accept: "application/json" } });
          if (structuredRes.ok) {
            const structuredPayload = await structuredRes.json();
            const structuredRanked = Array.isArray(structuredPayload)
              ? structuredPayload
                  .filter((item) => item && typeof item === "object")
                  .map((item) => ({ item, score: locationRank(item) + (isSettlementResult(item) ? 25 : 0) }))
                  .sort((a, b) => b.score - a.score)
                  .map((entry) => entry.item)
              : [];
            const structuredBest = structuredRanked.length > 0 ? structuredRanked[0] : null;
            const structuredResolved = buildResolvedLocation(structuredBest, location);
            if (structuredResolved) {
              return {
                ...structuredResolved,
                source: "geocode-structured",
              };
            }
          }
        }

        const resolved = buildResolvedLocation(first, location);
        if (!resolved) return null;
        return {
          ...resolved,
          source: "geocode-direct",
        };
      } catch (error) {
        return null;
      }
    };

    const collectQuery = () => {
      const params = new URLSearchParams();

      const magMin = Number(filterMag?.value || "0");
      const depthBand = filterDepth?.value || "all";
      const windowPreset = String(filterWindow?.value || "30d").trim();
      const location = normalizeSelectedLocation();
      const centerPlace = String(pinnedCenterPlace || location || "").trim();
      const sortMode = String(listSort?.value || "date_desc");

      params.set("page", "1");
      params.set("per_page", String(perPage));
      params.set("min_magnitude", String(magMin));
      params.set("sort_by", sortMode.startsWith("mag_") ? "magnitude" : "date");
      params.set("sort_dir", sortMode.endsWith("_asc") ? "asc" : "desc");
      if (hasCenterIntent()) {
        params.set("radius_km", String(radiusKm()));
      }

      // Location is used as map center, not as hard textual event filter.

      const now = new Date();
      const nowMs = now.getTime();
      const applyWindow = (hours) => {
        const fromIso = new Date(nowMs - (hours * 60 * 60 * 1000)).toISOString();
        params.set("from", fromIso);
        params.set("to", now.toISOString());
      };

      if (windowPreset === "24h") {
        applyWindow(24);
      } else if (windowPreset === "7d") {
        applyWindow(24 * 7);
      } else if (windowPreset === "30d") {
        applyWindow(24 * 30);
      } else if (windowPreset === "90d") {
        applyWindow(24 * 90);
      } else if (windowPreset === "1y") {
        applyWindow(24 * 365);
      } else if (windowPreset === "custom") {
        const from = String(filterFrom?.value || "").trim();
        const to = String(filterTo?.value || "").trim();
        if (from !== "") params.set("from", `${from}T00:00:00Z`);
        if (to !== "") params.set("to", `${to}T23:59:59Z`);
      }

      if (manualCenter && typeof manualCenter.latitude === "number" && typeof manualCenter.longitude === "number") {
        params.set("center_lat", String(manualCenter.latitude));
        params.set("center_lon", String(manualCenter.longitude));
        params.set("center_place", manualCenter.name || "Manual center");
      } else if (centerPlace !== "") {
        params.set("center_place", centerPlace);
      }

      if (depthBand === "shallow") {
        params.set("min_depth_km", "0");
        params.set("max_depth_km", "70");
      } else if (depthBand === "intermediate") {
        params.set("min_depth_km", "70");
        params.set("max_depth_km", "300");
      } else if (depthBand === "deep") {
        params.set("min_depth_km", "300");
      }

      return params;
    };

    const fetchArchiveNow = () => {
      if (debounceTimer) {
        window.clearTimeout(debounceTimer);
        debounceTimer = null;
      }
      fetchArchive();
    };

    const updateKpisAndInsights = (payload, rows) => {
      if (kpiVisible) kpiVisible.textContent = String(Array.isArray(rows) ? rows.length : 0);
      if (kpiVisibleNote) {
        const pageSize = Number(payload?.per_page || perPage);
        kpiVisibleNote.textContent = `Rows loaded on current page (max ${Number.isFinite(pageSize) ? pageSize : perPage})`;
      }
      if (kpiTotal) kpiTotal.textContent = String(Number(payload.total_count || 0));

      const pageMaxMag = (Array.isArray(rows) ? rows : []).reduce((best, row) => {
        const mag = typeof row.magnitude === "number" ? row.magnitude : best;
        return mag > best ? mag : best;
      }, 0);
      const filteredMaxMag = Number(payload?.filtered_max_magnitude);
      const effectiveMaxMag = Number.isFinite(filteredMaxMag) ? filteredMaxMag : pageMaxMag;
      if (kpiMaxMag) kpiMaxMag.textContent = Number.isFinite(effectiveMaxMag) && effectiveMaxMag > 0 ? `M${effectiveMaxMag.toFixed(1)}` : "--";
      if (kpiLatest) kpiLatest.textContent = rows[0]?.event_time_utc ? timeLabel(rows[0].event_time_utc) : "--";

      if (feedMeta) {
        feedMeta.textContent = `${payload.total_count || 0} matching rows in archive`;
      }

      if (insightSummary) {
        const filters = payload.filters_applied || {};
        const localityText = filters.locality ? `around "${filters.locality}"` : "across all locations";
        insightSummary.textContent = `${payload.total_count || 0} events found ${localityText}.`;
      }

      if (insightDepth) {
        const shallow = (Array.isArray(rows) ? rows : []).filter((row) => classifyDepth(row.depth_km) === "shallow").length;
        const intermediate = (Array.isArray(rows) ? rows : []).filter((row) => classifyDepth(row.depth_km) === "intermediate").length;
        const deep = (Array.isArray(rows) ? rows : []).filter((row) => classifyDepth(row.depth_km) === "deep").length;
        const center = payload.center;
        const radius = payload.filters_applied?.radius_km;
        const centerText = center && center.name ? `Center: ${center.name}` : "Center: not set";
        const radiusText = typeof radius === "number" ? `${radius} km` : "off";
        insightDepth.textContent = `${centerText} · Radius: ${radiusText}. Depth mix: ${shallow} shallow, ${intermediate} intermediate, ${deep} deep.`;
      }

      const providers = Array.isArray(payload.providers) ? payload.providers : [];
      if (insightProviders) {
        insightProviders.innerHTML = providers.length > 0
          ? providers.map((name) => `<span class=\"insight-pill\">${escapeHtml(name)}</span>`).join("")
          : "<span class='insight-pill'>No providers in current page</span>";
      }

      if (kpiSource) {
        const providerLabel = payload.provider || "Archive API";
        kpiSource.textContent = `Source: ${providerLabel}`;
      }
    };

    const fetchArchive = async () => {
      const seq = ++requestSeq;
      const actionReason = pendingFetchReason || "Ricerca in corso";
      beginFeedbackProgress(actionReason);
      try {
        const query = collectQuery();
        const response = await fetch(`/api/earthquakes-archive.php?${query.toString()}`, { headers: { Accept: "application/json" } });
        if (!response.ok) {
          throw new Error("Archive request failed");
        }

        const payload = await response.json();
        if (seq !== requestSeq) return;
        const rowsRaw = Array.isArray(payload.events) ? payload.events : [];

        if (payload.center && typeof payload.center.latitude === "number" && typeof payload.center.longitude === "number") {
          resolvedCenter = payload.center;
        } else if (manualCenter) {
          resolvedCenter = manualCenter;
        } else {
          resolvedCenter = null;
        }

        const rows = (resolvedCenter && typeof resolvedCenter.latitude === "number" && typeof resolvedCenter.longitude === "number")
          ? rowsRaw.filter((row) => {
              if (typeof row?.latitude !== "number" || typeof row?.longitude !== "number") return false;
              return haversineKm(resolvedCenter.latitude, resolvedCenter.longitude, row.latitude, row.longitude) <= radiusKm();
            })
          : rowsRaw;
        currentVisibleRows = rows;

        setCenterStatus(resolvedCenter);
        const typedCenter = String(pinnedCenterPlace || normalizeSelectedLocation()).trim();
        if (mapMeta) {
          mapMeta.textContent = resolvedCenter
            ? `Center ${resolvedCenter.name || "selected"} · radius ${radiusKm()} km`
            : (typedCenter !== "" ? "Center place not found in archive, try map click or broader text." : "Click map to set center point");
        }

        ensureMap();
        drawCenterGeometry();
        renderMapEvents(rows);
        renderList(currentVisibleRows);
        const payloadForUi = {
          ...payload,
          total_count: resolvedCenter ? rows.length : (payload.total_count || rows.length),
          events_count: rows.length,
        };
        updateKpisAndInsights(payloadForUi, rows);
        finishFeedbackProgress(true, `${actionReason} completata`);
        setActionButtonsBusy(false);
        pendingFetchReason = "Ricerca in corso";
      } catch (error) {
        if (seq !== requestSeq) return;
        setError();
        finishFeedbackProgress(false, "Richiesta non riuscita");
        setActionButtonsBusy(false);
        pendingFetchReason = "Ricerca in corso";
      }
    };

    const runSearch = async () => {
      const location = normalizeSelectedLocation();
      pendingFetchReason = "Ricerca in corso";
      setActionButtonsBusy(true, "Ricerca...");
      beginFeedbackProgress("Preparazione ricerca");

      if (location !== "") {
        let resolved = selectedSuggestion();
        if (!resolved) {
          resolved = await resolveLocation(location);
        } else if (resolved && resolved._meta && !isSettlementResult(resolved._meta)) {
          const refined = await resolveLocation(location);
          if (refined) {
            resolved = refined;
          }
        }
        if (!resolved) {
          setActionButtonsBusy(false);
          finishFeedbackProgress(false, "Localita non trovata");
          if (mapMeta) mapMeta.textContent = "Location not found. Try adding country/region.";
          return;
        }
        manualCenter = resolved;
        resolvedCenter = resolved;
        pinnedCenterPlace = resolved.name || location;
        pendingMapFocus = true;
        ensureMap();
        drawCenterGeometry();
        setCenterStatus(resolvedCenter);
        if (mapMeta) {
          mapMeta.textContent = `Center ${resolvedCenter.name || "selected"} · radius ${radiusKm()} km`;
        }
        if (map && typeof resolvedCenter.latitude === "number" && typeof resolvedCenter.longitude === "number") {
          map.flyTo([resolvedCenter.latitude, resolvedCenter.longitude], targetZoomForRadius(), { duration: 0.35 });
          pendingMapFocus = false;
        }
      } else {
        manualCenter = null;
        resolvedCenter = null;
        pinnedCenterPlace = "";
        pendingMapFocus = false;
      }

      fetchArchiveNow();
      const mapSection = document.querySelector(".archive-map-layout");
      if (mapSection instanceof HTMLElement) {
        mapSection.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    };

    const bindFilters = () => {
      filterLocation?.addEventListener("input", () => {
        manualCenter = null;
        pinnedCenterPlace = "";
        pendingMapFocus = false;
        if (locationSuggestTimer) window.clearTimeout(locationSuggestTimer);
        const q = String(filterLocation?.value || "");
        locationSuggestTimer = window.setTimeout(() => {
          fetchLocationSuggestions(q);
        }, 180);
      });
      filterLocation?.addEventListener("change", () => {});
      filterLocation?.addEventListener("keydown", (event) => {
        if (!(event instanceof KeyboardEvent)) return;
        if (event.key === "Enter") {
          event.preventDefault();
          runSearch();
        }
      });
      filterLocation?.addEventListener("blur", () => {
        // keep user text; validation happens on apply
      });

      filterWindow?.addEventListener("change", () => {
        const selected = String(filterWindow?.value || "");
        if (selected === "custom") {
          openCustomRangeDialog();
          return;
        }
        lastNonCustomWindow = selected || "30d";
      });

      customRangeBtn?.addEventListener("click", () => {
        if (filterWindow) filterWindow.value = "custom";
        openCustomRangeDialog();
      });

      searchBtn?.addEventListener("click", () => {
        runSearch();
      });

      [filterFrom, filterTo].forEach((el) => el?.addEventListener("change", () => {
        if (!filterFrom || !filterTo) return;
        const from = String(filterFrom.value || "").trim();
        const to = String(filterTo.value || "").trim();
        const fromDate = parseIsoDate(from);
        const toDate = parseIsoDate(to);
        if (fromDate && toDate && from > to) {
          filterTo.value = from;
        }
      }));

      customRangeCancel?.addEventListener("click", () => {
        if (customRangeDialog && typeof customRangeDialog.close === "function") {
          customRangeDialog.close();
        }
        if (!filterFrom?.value || !filterTo?.value) {
          if (filterWindow) filterWindow.value = lastNonCustomWindow;
        }
      });

      customRangeDialog?.addEventListener("close", () => {
        if ((filterWindow?.value || "") !== "custom") return;
        if (!filterFrom?.value || !filterTo?.value) {
          if (filterWindow) filterWindow.value = lastNonCustomWindow;
        }
      });

      customRangeApply?.addEventListener("click", () => {
        const from = String(filterFrom?.value || "").trim();
        const to = String(filterTo?.value || "").trim();
        if (from === "" || to === "") {
          return;
        }
        if (from > to) {
          return;
        }
        if (filterWindow) filterWindow.value = "custom";
        formatDateRangeLabel();
        if (customRangeDialog && typeof customRangeDialog.close === "function") {
          customRangeDialog.close();
        }
      });

      filterRadius?.addEventListener("input", () => {
        setRadiusLabel();
        drawCenterGeometry();
      });

      resetFilters?.addEventListener("click", () => {
        pendingFetchReason = "Reset filtri";
        setActionButtonsBusy(true, "Ricerca...");
        beginFeedbackProgress("Reset filtri");
        if (filterWindow) filterWindow.value = "30d";
        if (filterFrom) filterFrom.value = "";
        if (filterTo) filterTo.value = "";
        if (filterLocation) filterLocation.value = "";
        if (filterMag) filterMag.value = "0";
        if (filterDepth) filterDepth.value = "all";
        if (filterRadius) filterRadius.value = "120";
        manualCenter = null;
        pinnedCenterPlace = "";
        resolvedCenter = null;
        pendingMapFocus = false;
        lastNonCustomWindow = "30d";
        setRadiusLabel();
        formatDateRangeLabel();
        drawCenterGeometry();
        syncRadiusControl();
        fetchArchiveNow();
      });

      mapList?.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        const row = target.closest(".archive-map-item");
        if (!(row instanceof HTMLElement)) return;

        const key = row.dataset.key || "";
        const url = row.dataset.url || "";
        selectedRowKey = key;
        highlightRowByKey(key);

        const marker = markerByKey.get(key);
        if (marker && map) {
          const latLng = marker.getLatLng();
          map.setView(latLng, Math.max(map.getZoom(), 5));
          marker.openPopup();
        }

        if (url !== "") {
          window.location.href = url;
        }
      });

      listSort?.addEventListener("change", () => {
        pendingFetchReason = "Riordino risultati";
        fetchArchiveNow();
      });
    };

    const setError = () => {
      if (mapList) {
        mapList.innerHTML = "<li class='event-item'>Unable to load archive right now.</li>";
      }
      if (feedMeta) feedMeta.textContent = "Archive unavailable";
      if (kpiSource) kpiSource.textContent = "Source unavailable";
      if (insightSummary) insightSummary.textContent = "Unable to build archive summary right now.";
      if (insightDepth) insightDepth.textContent = "Depth summary unavailable.";
      if (insightProviders) insightProviders.innerHTML = "<span class='insight-pill'>Provider mix unavailable</span>";
    };

    setRadiusLabel();
    formatDateRangeLabel();
    ensureMap();
    bindFilters();
    syncRadiusControl();
    fetchArchiveNow();
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
