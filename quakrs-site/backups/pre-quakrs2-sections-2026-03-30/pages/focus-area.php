<?php
declare(strict_types=1);

$area = isset($_GET['area']) ? trim((string) $_GET['area']) : '';
$windowRaw = isset($_GET['window']) ? trim((string) $_GET['window']) : '6h';
$windowHours = 6;
if (preg_match('/^(\d{1,2})h$/', strtolower($windowRaw), $m) === 1) {
    $windowHours = max(1, min(48, (int) $m[1]));
}

$pageTitle = 'Quakrs.com - Area Focus';
$pageDescription = 'Operational area focus with regional sequence and local event detail.';
$currentPage = 'earthquakes';
$includeLeaflet = true;
$bodyClass = 'focus-area-page';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<style>
  .focus-area-page .panel {
    gap: 0.72rem;
    margin-bottom: 0.82rem;
  }

  .focus-area-page .panel-kpi {
    gap: 0.55rem;
  }

  .focus-area-page .earthquakes-main-layout {
    grid-template-columns: minmax(0, 1.62fr) minmax(290px, 0.82fr);
    gap: 0.62rem;
    align-items: start;
  }

  .focus-area-page .earthquakes-main-layout > .side-card .live-feed-scroll {
    max-height: calc(520px + 2.5rem);
  }

  .focus-area-page .focus-insight-card {
    display: block;
  }

  .focus-area-page .focus-insight-card h3 {
    margin-bottom: 0.36rem;
  }

  .focus-area-page .focus-brief-lines {
    margin-top: 0.1rem;
    display: grid;
    gap: 0.3rem;
  }

  .focus-area-page .focus-brief-lines .insight-lead,
  .focus-area-page .focus-brief-lines .kpi-note {
    margin: 0;
    border-left: 2px solid color-mix(in srgb, var(--line-strong) 38%, transparent);
    padding-left: 0.58rem;
  }

  .focus-area-page .focus-brief-lines .kpi-note {
    font-size: 0.82rem;
    color: color-mix(in srgb, var(--muted) 88%, var(--text));
  }

  .focus-area-page .page-grid.focus-area-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.62rem;
    align-items: start;
  }

  .focus-area-page .page-grid.focus-area-grid > .card {
    min-height: 0;
  }

  .focus-area-page #focus-hotspots {
    max-height: 24.4rem;
    overflow-y: auto;
    padding-right: 0.2rem;
  }

  .focus-area-page #focus-hotspots .event-item {
    padding: 0.46rem 0.62rem;
  }

  @media (max-width: 980px) {
    .focus-area-page .page-grid.focus-area-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Area Focus</p>
    <h1 id="focus-area-title">Area focus: <?= htmlspecialchars($area !== '' ? $area : 'Not selected', ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub">Dedicated regional sequence view from the live earthquake feed, with local timeline and quick operational context.</p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Events in window</p>
    <p id="focus-kpi-window" class="kpi-value">--</p>
    <p id="focus-kpi-window-note" class="kpi-note">Window loading...</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Events in feed</p>
    <p id="focus-kpi-total" class="kpi-value">--</p>
    <p class="kpi-note">Same area in full live payload</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Strongest</p>
    <p id="focus-kpi-strongest" class="kpi-value">--</p>
    <p id="focus-kpi-strongest-place" class="kpi-note">Awaiting area context</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Feed update</p>
    <p id="focus-kpi-updated" class="kpi-value">--</p>
    <p id="focus-kpi-source" class="kpi-note">Source loading...</p>
  </article>
</section>

<section class="panel">
  <article class="card page-card focus-insight-card">
    <h3>Area brief</h3>
    <div class="focus-brief-lines">
      <p id="focus-narrative" class="insight-lead">Building regional narrative...</p>
      <p id="focus-phase-text" class="insight-lead">Evaluating current trend...</p>
      <p id="focus-change-text" class="insight-lead">Computing changes vs previous window...</p>
      <p id="focus-pulse-meta" class="kpi-note">Preparing additional context...</p>
    </div>
  </article>
</section>

<section class="panel panel-main earthquakes-main-layout">
  <article class="card map-card">
    <div class="feed-head">
      <h3>Area Map</h3>
      <p id="focus-map-meta" class="feed-meta">Loading map context...</p>
    </div>
    <div class="map-wrap">
      <div id="focus-area-map" class="world-map-leaflet" aria-label="Area focus map"></div>
    </div>
    <div class="map-legend">
      <button class="map-filter-btn band-m1-2" data-band="m1-2" type="button" aria-pressed="false">M1-2</button>
      <button class="map-filter-btn band-m3" data-band="m3" type="button" aria-pressed="false">M3</button>
      <button class="map-filter-btn band-m4" data-band="m4" type="button" aria-pressed="false">M4</button>
      <button class="map-filter-btn band-m5" data-band="m5" type="button" aria-pressed="false">M5</button>
      <button class="map-filter-btn band-m6" data-band="m6" type="button" aria-pressed="false">M6</button>
      <button class="map-filter-btn band-m7p" data-band="m7p" type="button" aria-pressed="false">M7+</button>
    </div>
  </article>

  <article class="card side-card">
    <div class="feed-head">
      <h3>Sequence (Newest First)</h3>
      <p id="focus-seq-meta" class="feed-meta">Loading sequence...</p>
    </div>
    <ul id="focus-seq-list" class="events-list live-feed-scroll">
      <li class="event-item">Loading area events...</li>
    </ul>
  </article>
</section>

<section class="panel page-grid focus-area-grid">
  <article class="card page-card">
    <h3>Local concentration</h3>
    <ul id="focus-hotspots" class="events-list">
      <li class="event-item">Loading local concentration...</li>
    </ul>
  </article>
  <article class="card page-card">
    <h3>Operational note</h3>
    <p id="focus-note" class="insight-lead">Computing area trend...</p>
    <p class="kpi-note">
      <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/earthquakes.php'), ENT_QUOTES, 'UTF-8'); ?>">Open global earthquakes monitor</a>
      ·
      <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/aftershocks.php'), ENT_QUOTES, 'UTF-8'); ?>">Check aftershock sequences</a>
    </p>
  </article>
</section>

<script>
  (() => {
    const areaRaw = <?= json_encode($area, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    const windowHours = <?= (int) $windowHours; ?>;

    const els = {
      title: document.querySelector("#focus-area-title"),
      kpiWindow: document.querySelector("#focus-kpi-window"),
      kpiWindowNote: document.querySelector("#focus-kpi-window-note"),
      kpiTotal: document.querySelector("#focus-kpi-total"),
      kpiStrongest: document.querySelector("#focus-kpi-strongest"),
      kpiStrongestPlace: document.querySelector("#focus-kpi-strongest-place"),
      kpiUpdated: document.querySelector("#focus-kpi-updated"),
      kpiSource: document.querySelector("#focus-kpi-source"),
      mapMeta: document.querySelector("#focus-map-meta"),
      seqMeta: document.querySelector("#focus-seq-meta"),
      seqList: document.querySelector("#focus-seq-list"),
      hotspots: document.querySelector("#focus-hotspots"),
      note: document.querySelector("#focus-note"),
      map: document.querySelector("#focus-area-map"),
      narrative: document.querySelector("#focus-narrative"),
      pulseMeta: document.querySelector("#focus-pulse-meta"),
      windowContext: document.querySelector("#focus-window-context"),
      phaseBadge: document.querySelector("#focus-phase-badge"),
      phaseText: document.querySelector("#focus-phase-text"),
      opsActions: document.querySelector("#focus-actions-ops"),
      changeText: document.querySelector("#focus-change-text"),
      deltaEvents: document.querySelector("#focus-delta-events"),
      deltaPeak: document.querySelector("#focus-delta-peak"),
      deltaSubareas: document.querySelector("#focus-delta-subareas"),
      rateHour: document.querySelector("#focus-rate-hour"),
      windowPeak: document.querySelector("#focus-window-peak"),
      lastEvent: document.querySelector("#focus-last-event"),
      checkCount: document.querySelector("#focus-check-count"),
      checkPeak: document.querySelector("#focus-check-peak"),
      checkSpread: document.querySelector("#focus-check-spread"),
      topCurrent: document.querySelector("#focus-top-current"),
      topPrev: document.querySelector("#focus-top-prev"),
      concentration: document.querySelector("#focus-concentration"),
    };
    const mapFilterButtons = Array.from(document.querySelectorAll(".map-legend .map-filter-btn"));

    const normalize = (value) =>
      String(value || "")
        .toLowerCase()
        .replace(/\s+/g, " ")
        .trim()
        .replace(/\b\w/g, (char) => char.toUpperCase());

    const parseRegion = (place) => {
      if (!place) return "Unknown";
      const text = String(place);
      if (text.includes(" of ")) {
        return text.split(" of ").slice(-1)[0].trim();
      }
      const parts = text.split(",");
      return parts[parts.length - 1].trim() || text;
    };

    const shortPlaceLabel = (place) => {
      if (!place) return "Unknown";
      const parts = String(place).split(",");
      return parts[0].trim() || String(place);
    };

    const formatTime = (iso) => {
      if (!iso) return "n/a";
      const date = new Date(iso);
      if (Number.isNaN(date.getTime())) return "n/a";
      return date.toLocaleString([], {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
      }).replaceAll(".", "/");
    };

    const formatMagnitude = (value) => (typeof value === "number" && Number.isFinite(value) ? `M${value.toFixed(1)}` : "M?");

    const magnitudeColor = (magnitude) => {
      if (typeof magnitude !== "number" || Number.isNaN(magnitude)) return "#6b7280";
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
      return palette[bucket];
    };

    const eventInMagnitudeBand = (event, band) => {
      const mag = typeof event?.magnitude === "number" ? event.magnitude : NaN;
      if (Number.isNaN(mag)) return false;
      if (band === "m1-2") return mag < 3;
      if (band === "m3") return mag >= 3 && mag < 4;
      if (band === "m4") return mag >= 4 && mag < 5;
      if (band === "m5") return mag >= 5 && mag < 6;
      if (band === "m6") return mag >= 6 && mag < 7;
      if (band === "m7p") return mag >= 7;
      return true;
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

    const areaLabel = normalize(areaRaw);
    if (els.title) {
      els.title.textContent = areaLabel ? `Area focus: ${areaLabel}` : "Area focus: Not selected";
    }

    if (!areaLabel) {
      if (els.seqList) {
        els.seqList.innerHTML = "<li class='event-item'>No area selected. Open this page from Home Area focus link.</li>";
      }
      if (els.note) {
        els.note.textContent = "No area selected yet. Use Home > Editorial brief > Area focus.";
      }
      return;
    }

    let map = null;
    let layer = null;
    const ensureMap = () => {
      if (!els.map || !window.L) return null;
      if (map) return map;
      map = window.L.map(els.map, { zoomControl: true, worldCopyJump: true, preferCanvas: true }).setView([15, 10], 2);
      layer = window.L.layerGroup().addTo(map);
      window.L.tileLayer("https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png", {
        attribution: "&copy; OpenStreetMap contributors &copy; CARTO",
      }).addTo(map);
      return map;
    };

    const renderMap = (rows) => {
      const instance = ensureMap();
      if (!instance || !layer) return;
      layer.clearLayers();

      const valid = rows.filter((row) => typeof row?.latitude === "number" && typeof row?.longitude === "number");
      valid.forEach((row) => {
        const marker = window.L.circleMarker([row.latitude, row.longitude], {
          radius: 3.2 + Math.max(0, Math.min(5.6, Number(row.magnitude || 0))),
          color: magnitudeColor(row.magnitude),
          weight: 0.9,
          fillColor: magnitudeColor(row.magnitude),
          fillOpacity: 0.82,
        });
        marker.bindPopup(`<strong>${formatMagnitude(row.magnitude)}</strong> ${shortPlaceLabel(row.place)}<br/>${formatTime(row.event_time_utc)}`);
        marker.addTo(layer);
      });

      if (valid.length > 0) {
        const bounds = window.L.featureGroup(valid.map((row) => window.L.marker([row.latitude, row.longitude]))).getBounds();
        if (bounds.isValid()) {
          instance.fitBounds(bounds.pad(0.2), { maxZoom: 6 });
        }
      }
    };

    const renderHotspots = (rows) => {
      if (!els.hotspots) return;
      const counter = new Map();
      rows.forEach((row) => {
        const key = shortPlaceLabel(row.place);
        counter.set(key, (counter.get(key) || 0) + 1);
      });
      const top = [...counter.entries()].sort((a, b) => b[1] - a[1]).slice(0, 6);
      if (top.length === 0) {
        els.hotspots.innerHTML = "<li class='event-item'>No local concentration in current window.</li>";
        return;
      }
      els.hotspots.innerHTML = top
        .map(([name, count]) => `<li class="event-item"><strong>${name}</strong><br />${count} events</li>`)
        .join("");
    };

    const renderSequence = (rows, allAreaRows) => {
      const maxRows = 120;
      if (els.seqList) {
        if (!rows.length) {
          els.seqList.innerHTML = "<li class='event-item'>No events in selected time window for this area.</li>";
        } else {
          els.seqList.innerHTML = rows
            .slice(0, maxRows)
            .map((event) => {
              const mag = formatMagnitude(event.magnitude);
              const depth = typeof event.depth_km === "number" ? `${event.depth_km.toFixed(1)} km` : "n/a";
              const when = formatTime(event.event_time_utc);
              const url = eventDetailUrl(event);
              return `<li class="event-item"><strong style="color:${magnitudeColor(event.magnitude)}">${mag}</strong> · ${shortPlaceLabel(event.place)}<br />${when} · Depth ${depth}<br /><a class="inline-link" href="${url}">Open event detail</a></li>`;
            })
            .join("");
        }
      }
      if (els.seqMeta) {
        els.seqMeta.textContent = `${rows.length} events in last ${windowHours}h · ${allAreaRows.length} events in full feed`;
      }
    };

    let allAreaRowsState = [];
    let windowRowsState = [];
    let activeMagnitudeBand = null;

    const setMagnitudeFilterState = (nextBand) => {
      activeMagnitudeBand = nextBand;
      mapFilterButtons.forEach((button) => {
        const isActive = button.dataset.band === activeMagnitudeBand;
        button.classList.toggle("is-active", isActive);
        button.setAttribute("aria-pressed", isActive ? "true" : "false");
      });
    };

    const applyMagnitudeFilter = (rows) => {
      if (!activeMagnitudeBand) return rows;
      return rows.filter((event) => eventInMagnitudeBand(event, activeMagnitudeBand));
    };

    const phaseModel = (windowRows, prevRows) => {
      const currentCount = windowRows.length;
      const prevCount = prevRows.length;
      const delta = currentCount - prevCount;
      const strongest = [...windowRows].sort((a, b) => (b.magnitude || 0) - (a.magnitude || 0))[0] || null;
      const strongestMag = typeof strongest?.magnitude === "number" ? strongest.magnitude : 0;
      if (currentCount >= 8 || delta >= 3 || strongestMag >= 5.5) {
        return { key: "escalating", label: "Higher" };
      }
      if (currentCount >= 3 || delta > 0 || strongestMag >= 4.2) {
        return { key: "active", label: "Moderate" };
      }
      return { key: "quiet", label: "Background" };
    };

    const buildNarrative = (windowRows, prevRows) => {
      const current = windowRows.length;
      const prev = prevRows.length;
      const delta = current - prev;
      const strongest = [...windowRows].sort((a, b) => (b.magnitude || 0) - (a.magnitude || 0))[0] || null;
      const strongestLabel = strongest ? `${formatMagnitude(strongest.magnitude)} near ${shortPlaceLabel(strongest.place)}` : "no notable peak";
      if (current >= 8) {
        return `Activity is higher in ${areaLabel}: ${current} events in the last ${windowHours}h (${delta >= 0 ? "+" : ""}${delta} vs previous window), with ${strongestLabel}.`;
      }
      if (current >= 3) {
        return `Activity is moderate in ${areaLabel}: ${current} events in the last ${windowHours}h, distributed across local subareas with ${strongestLabel}.`;
      }
      return `Background activity in ${areaLabel}: ${current} events in the last ${windowHours}h; trend remains stable with ${strongestLabel}.`;
    };

    const buildPulseMeta = (allAreaRows) => {
      const now = Date.now();
      const hourMs = 60 * 60 * 1000;
      const bins = new Array(24).fill(0);
      allAreaRows.forEach((row) => {
        if (!row?.event_time_utc) return;
        const ts = new Date(row.event_time_utc).getTime();
        if (Number.isNaN(ts)) return;
        const deltaHours = Math.floor((now - ts) / hourMs);
        if (deltaHours >= 0 && deltaHours < 24) {
          const idx = 23 - deltaHours;
          bins[idx] += 1;
        }
      });
      const peak = Math.max(...bins, 1);
      const peakHourAgo = bins.lastIndexOf(peak);
      const hoursAgo = peakHourAgo >= 0 ? Math.max(0, 23 - peakHourAgo) : 0;
      return `Peak ${peak} events about ${hoursAgo}h ago in the last 24h`;
    };

    const renderStoryBlocks = (windowRows, prevRows, allAreaRows) => {
      const phase = phaseModel(windowRows, prevRows);
      const current = windowRows.length;
      const prev = prevRows.length;
      const delta = current - prev;
      const strongestWindow = [...windowRows].sort((a, b) => (b.magnitude || 0) - (a.magnitude || 0))[0] || null;
      const strongestPrev = [...prevRows].sort((a, b) => (b.magnitude || 0) - (a.magnitude || 0))[0] || null;
      const strongestWindowMag = typeof strongestWindow?.magnitude === "number" ? strongestWindow.magnitude : 0;
      const strongestPrevMag = typeof strongestPrev?.magnitude === "number" ? strongestPrev.magnitude : 0;
      const peakDelta = strongestWindowMag - strongestPrevMag;
      const curAreas = new Set(windowRows.map((row) => shortPlaceLabel(row.place)));
      const prevAreas = new Set(prevRows.map((row) => shortPlaceLabel(row.place)));
      const newSubareas = [...curAreas].filter((name) => !prevAreas.has(name)).length;
      const latestTs = windowRows[0]?.event_time_utc ? new Date(windowRows[0].event_time_utc).getTime() : NaN;
      const latestMinAgo = Number.isNaN(latestTs) ? null : Math.max(0, Math.round((Date.now() - latestTs) / 60000));
      const ratePerHour = windowHours > 0 ? (current / windowHours) : 0;

      const counterOf = (rows) => {
        const map = new Map();
        rows.forEach((row) => {
          const key = shortPlaceLabel(row.place);
          map.set(key, (map.get(key) || 0) + 1);
        });
        return [...map.entries()].sort((a, b) => b[1] - a[1]);
      };
      const currentTop = counterOf(windowRows)[0] || null;
      const prevTop = counterOf(prevRows)[0] || null;
      const currentTopShare = current > 0 && currentTop ? (currentTop[1] / current) * 100 : 0;
      const prevTopShare = prev > 0 && prevTop ? (prevTop[1] / prev) * 100 : 0;
      const concDelta = currentTopShare - prevTopShare;

      if (els.narrative) {
        els.narrative.textContent = buildNarrative(windowRows, prevRows);
      }
      if (els.phaseBadge) {
        els.phaseBadge.textContent = phase.label;
      }
      if (els.phaseText) {
        if (phase.key === "escalating") {
          els.phaseText.textContent = `Trend ${phase.label.toLowerCase()}: ${current} events in ${windowHours}h, with strongest ${strongestWindow ? formatMagnitude(strongestWindow.magnitude) : "M?"}.`;
        } else if (phase.key === "active") {
          els.phaseText.textContent = `Trend ${phase.label.toLowerCase()}: localized clustering remains steady, with ${curAreas.size} active subareas in this window.`;
        } else {
          els.phaseText.textContent = `Trend ${phase.label.toLowerCase()}: activity is near background, with ${current} events in the last ${windowHours}h.`;
        }
      }
      if (els.opsActions) {
        if (phase.key === "escalating") {
          els.opsActions.textContent = "Count is above previous window; magnitude range remains consistent with current regional pattern.";
        } else if (phase.key === "active") {
          els.opsActions.textContent = "Moderate clustering across local subareas, with stable magnitude progression.";
        } else {
          els.opsActions.textContent = "Activity remains near background with no abrupt changes in this window.";
        }
      }
      if (els.changeText) {
        els.changeText.textContent = `Compared with previous ${windowHours}h: ${delta >= 0 ? "+" : ""}${delta} events, ${peakDelta >= 0 ? "+" : ""}${peakDelta.toFixed(1)}M on peak, ${newSubareas} newly active subareas.`;
      }
      if (els.deltaEvents) {
        els.deltaEvents.textContent = `${delta >= 0 ? "+" : ""}${delta}`;
      }
      if (els.deltaPeak) {
        els.deltaPeak.textContent = `${peakDelta >= 0 ? "+" : ""}${peakDelta.toFixed(1)}M`;
      }
      if (els.deltaSubareas) {
        els.deltaSubareas.textContent = String(newSubareas);
      }
      if (els.rateHour) {
        els.rateHour.textContent = `${ratePerHour.toFixed(1)} /h`;
      }
      if (els.windowContext) {
        els.windowContext.textContent = `${current} events in ${windowHours}h (${delta >= 0 ? "+" : ""}${delta} vs previous ${windowHours}h)`;
      }
      if (els.windowPeak) {
        els.windowPeak.textContent = strongestWindow ? `${formatMagnitude(strongestWindow.magnitude)} ${shortPlaceLabel(strongestWindow.place)}` : "--";
      }
      if (els.lastEvent) {
        els.lastEvent.textContent = latestMinAgo === null ? "n/a" : (latestMinAgo < 1 ? "now" : `${latestMinAgo}m ago`);
      }
      if (els.checkCount) {
        els.checkCount.textContent = `${delta >= 0 ? "+" : ""}${delta} vs previous ${windowHours}h`;
      }
      if (els.checkPeak) {
        els.checkPeak.textContent = `${peakDelta >= 0 ? "+" : ""}${peakDelta.toFixed(1)}M window peak change`;
      }
      if (els.checkSpread) {
        const spreadDelta = curAreas.size - prevAreas.size;
        els.checkSpread.textContent = `${spreadDelta >= 0 ? "+" : ""}${spreadDelta} active subareas`;
      }
      if (els.topCurrent) {
        els.topCurrent.textContent = currentTop ? `${currentTop[0]} (${currentTop[1]})` : "--";
      }
      if (els.topPrev) {
        els.topPrev.textContent = prevTop ? `${prevTop[0]} (${prevTop[1]})` : "--";
      }
      if (els.concentration) {
        els.concentration.textContent = `${currentTopShare.toFixed(0)}% top-share (${concDelta >= 0 ? "+" : ""}${concDelta.toFixed(0)}pp)`;
      }
      if (els.pulseMeta) {
        els.pulseMeta.textContent = buildPulseMeta(allAreaRows);
      }
    };

    const refreshAreaViews = () => {
      const baseRows = windowRowsState.length > 0 ? windowRowsState : allAreaRowsState.slice(0, 80);
      const filteredRows = applyMagnitudeFilter(baseRows);
      renderSequence(filteredRows, allAreaRowsState);
      renderHotspots(filteredRows);
      renderMap(filteredRows);
      if (els.mapMeta) {
        const bandText = activeMagnitudeBand ? ` · filter ${activeMagnitudeBand.toUpperCase()}` : "";
        els.mapMeta.textContent = `${areaLabel} · ${filteredRows.length} mapped events in current view${bandText}`;
      }
    };

    mapFilterButtons.forEach((button) => {
      button.addEventListener("click", () => {
        const band = button.dataset.band || null;
        const nextBand = activeMagnitudeBand === band ? null : band;
        setMagnitudeFilterState(nextBand);
        refreshAreaViews();
      });
    });

    const fetchArea = async () => {
      try {
        const response = await fetch("/api/earthquakes.php", { headers: { Accept: "application/json" } });
        if (!response.ok) {
          throw new Error("Feed unavailable");
        }
        const payload = await response.json();
        const events = Array.isArray(payload.events) ? payload.events : [];
        const allAreaRows = events
          .filter((event) => normalize(parseRegion(event?.place)) === areaLabel)
          .sort((a, b) => {
            const at = a?.event_time_utc ? new Date(a.event_time_utc).getTime() : 0;
            const bt = b?.event_time_utc ? new Date(b.event_time_utc).getTime() : 0;
            return bt - at;
          });

        const threshold = Date.now() - (windowHours * 60 * 60 * 1000);
        const prevStart = Date.now() - (windowHours * 2 * 60 * 60 * 1000);
        const prevEnd = threshold;
        const windowRows = allAreaRows.filter((event) => {
          if (!event?.event_time_utc) return false;
          return new Date(event.event_time_utc).getTime() >= threshold;
        });
        const prevRows = allAreaRows.filter((event) => {
          if (!event?.event_time_utc) return false;
          const ts = new Date(event.event_time_utc).getTime();
          return ts >= prevStart && ts < prevEnd;
        });
        allAreaRowsState = allAreaRows;
        windowRowsState = windowRows;
        const renderRows = windowRows.length > 0 ? windowRows : allAreaRows.slice(0, 80);

        const strongest = [...allAreaRows].sort((a, b) => (b.magnitude || 0) - (a.magnitude || 0))[0] || null;
        if (els.kpiWindow) els.kpiWindow.textContent = String(windowRows.length);
        if (els.kpiWindowNote) els.kpiWindowNote.textContent = `Window: last ${windowHours}h`;
        if (els.kpiTotal) els.kpiTotal.textContent = String(allAreaRows.length);
        if (els.kpiStrongest) {
          els.kpiStrongest.textContent = strongest ? formatMagnitude(strongest.magnitude) : "--";
          els.kpiStrongest.style.color = strongest ? magnitudeColor(strongest.magnitude) : "";
        }
        if (els.kpiStrongestPlace) {
          els.kpiStrongestPlace.textContent = strongest ? shortPlaceLabel(strongest.place) : "No events in current feed";
        }
        if (els.kpiUpdated) {
          els.kpiUpdated.textContent = payload.generated_at ? formatTime(payload.generated_at).split(" ").slice(-1)[0] : "--";
        }
        if (els.kpiSource) {
          els.kpiSource.textContent = `Source: ${payload.provider || "Quakrs API"}`;
        }
        if (els.mapMeta) {
          els.mapMeta.textContent = `${areaLabel} · ${renderRows.length} mapped events in current view`;
        }
        if (els.note) {
          if (windowRows.length >= 8) {
            els.note.textContent = `Activity currently above baseline: ${windowRows.length} events in the last ${windowHours}h.`;
          } else if (windowRows.length >= 3) {
            els.note.textContent = `Activity moderate: ${windowRows.length} events in the last ${windowHours}h.`;
          } else {
            els.note.textContent = `Activity near background in the last ${windowHours}h.`;
          }
        }

        renderStoryBlocks(windowRows, prevRows, allAreaRows);
        refreshAreaViews();
      } catch (error) {
        if (els.seqList) {
          els.seqList.innerHTML = "<li class='event-item'>Unable to load area feed right now.</li>";
        }
        if (els.seqMeta) {
          els.seqMeta.textContent = "Feed unavailable";
        }
        if (els.note) {
          els.note.textContent = "Area focus is temporarily unavailable due to feed issues.";
        }
      }
    };

    fetchArea();
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
