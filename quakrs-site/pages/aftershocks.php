<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Aftershock Sequences';
$pageDescription = 'Automatic aftershock tracking for strong mainshocks (M6+), with live sequence updates.';
$currentPage = 'aftershocks';
$includeLeaflet = true;

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.aftershocks.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.aftershocks.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.aftershocks.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel panel-kpi aftershocks-kpi-panel">
  <article class="card kpi-card">
    <p class="kpi-label">Active Sequences</p>
    <p id="aftershocks-kpi-active" class="kpi-value">--</p>
    <p class="kpi-note">Mainshock windows currently tracked</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Trigger Rule</p>
    <p id="aftershocks-kpi-trigger" class="kpi-value">M6.0+</p>
    <p class="kpi-note">Automatic sequence creation</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Tracking Radius</p>
    <p id="aftershocks-kpi-radius" class="kpi-value">150 km</p>
    <p class="kpi-note">Around mainshock epicenter</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Window</p>
    <p id="aftershocks-kpi-window" class="kpi-value">7 days</p>
    <p class="kpi-note">Time span per sequence</p>
  </article>
</section>

<section class="panel aftershocks-map-panel">
  <article class="card aftershocks-map-card">
    <div class="feed-head">
      <h3>Sequence Map</h3>
      <p id="aftershocks-map-meta" class="feed-meta">Waiting for sequence selection...</p>
    </div>
    <div class="map-wrap insight-map-wrap">
      <div id="aftershocks-map" class="world-map-leaflet" aria-label="Aftershock sequence map"></div>
    </div>
  </article>
</section>

<section class="panel panel-main aftershocks-main-panel">
  <article class="card aftershocks-sequences-card">
    <div class="feed-head">
      <h3>1) Pick A Sequence</h3>
      <p id="aftershocks-sequences-meta" class="feed-meta">Loading active sequences...</p>
    </div>
    <ul id="aftershocks-sequences-list" class="events-list live-feed-scroll aftershocks-sequences-list">
      <li class="event-item">Loading sequence index...</li>
    </ul>
  </article>

  <article class="card side-card aftershocks-detail-card">
    <div class="feed-head">
      <h3 id="aftershocks-detail-title">2) Sequence Detail</h3>
      <p id="aftershocks-detail-meta" class="feed-meta">Awaiting selection...</p>
    </div>
    <ul id="aftershocks-detail-kpis" class="events-list aftershocks-detail-kpis">
      <li class="event-item">Waiting for sequence data...</li>
    </ul>
    <h4 class="aftershocks-stream-title">Recent Aftershock Stream</h4>
    <ul id="aftershocks-events-list" class="events-list live-feed-scroll aftershocks-events-list">
      <li class="event-item">Select a sequence to inspect events.</li>
    </ul>
  </article>
</section>

<section class="panel panel-charts aftershocks-charts-panel">
  <article class="card aftershocks-chart-card">
    <div class="feed-head">
      <h3>3) Aftershock Timeline</h3>
      <p id="aftershocks-timeline-meta" class="feed-meta">Daily counts in sequence window.</p>
    </div>
    <ul id="aftershocks-timeline-bars" class="events-list aftershocks-bars-list">
      <li class="event-item">Select a sequence to load timeline.</li>
    </ul>
  </article>
  <article class="card aftershocks-chart-card">
    <div class="feed-head">
      <h3>4) Magnitude Distribution</h3>
      <p id="aftershocks-mag-meta" class="feed-meta">Histogram by aftershock magnitude.</p>
    </div>
    <ul id="aftershocks-mag-bars" class="events-list aftershocks-bars-list">
      <li class="event-item">Select a sequence to load distribution.</li>
    </ul>
  </article>
</section>

<script>
  (() => {
    const params = new URLSearchParams(window.location.search);
    const requestedSequenceId = (params.get("sequence_id") || "").trim();
    const requestedEventId = (params.get("event_id") || "").trim();

    const kpiActive = document.querySelector("#aftershocks-kpi-active");
    const kpiTrigger = document.querySelector("#aftershocks-kpi-trigger");
    const kpiRadius = document.querySelector("#aftershocks-kpi-radius");
    const kpiWindow = document.querySelector("#aftershocks-kpi-window");
    const sequencesMeta = document.querySelector("#aftershocks-sequences-meta");
    const sequencesList = document.querySelector("#aftershocks-sequences-list");
    const detailTitle = document.querySelector("#aftershocks-detail-title");
    const detailMeta = document.querySelector("#aftershocks-detail-meta");
    const detailKpis = document.querySelector("#aftershocks-detail-kpis");
    const eventsList = document.querySelector("#aftershocks-events-list");
    const mapMeta = document.querySelector("#aftershocks-map-meta");
    const mapNode = document.querySelector("#aftershocks-map");
    const timelineMeta = document.querySelector("#aftershocks-timeline-meta");
    const timelineBars = document.querySelector("#aftershocks-timeline-bars");
    const magMeta = document.querySelector("#aftershocks-mag-meta");
    const magBars = document.querySelector("#aftershocks-mag-bars");

    let activeSequenceId = requestedSequenceId;
    let latestIndex = null;
    let sequenceMap = null;
    let mainshockLayer = null;
    let aftershockLayer = null;
    let radiusLayer = null;

    const fmtMag = (value) => (typeof value === "number" && Number.isFinite(value) ? `M${value.toFixed(1)}` : "M?");
    const fmtUtc = (iso) => {
      if (!iso) return "----/--/-- --:--";
      const dt = new Date(iso);
      if (Number.isNaN(dt.getTime())) return "----/--/-- --:--";
      return dt.toLocaleString("it-IT", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
      });
    };
    const dayKeyItaly = (input) => {
      const dt = new Date(input);
      if (Number.isNaN(dt.getTime())) return "";
      return dt.toLocaleDateString("en-CA", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
      });
    };

    const escapeHtml = (value) =>
      String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#39;");

    const magColor = (magnitude) => {
      if (!Number.isFinite(magnitude)) return "#64748b";
      if (magnitude >= 6) return "#ef4444";
      if (magnitude >= 5) return "#f97316";
      if (magnitude >= 4) return "#eab308";
      if (magnitude >= 3) return "#22c55e";
      if (magnitude >= 2) return "#06b6d4";
      return "#3b82f6";
    };

    const mapRadius = (magnitude) => {
      if (!Number.isFinite(magnitude)) return 4;
      return Math.max(4, Math.min(11, 3 + magnitude));
    };

    function ensureMap() {
      if (!mapNode || !window.L) {
        return null;
      }
      if (sequenceMap) {
        return sequenceMap;
      }
      sequenceMap = window.L.map(mapNode, {
        zoomControl: true,
        worldCopyJump: true,
        attributionControl: true,
      }).setView([8, 0], 2);

      window.L.tileLayer("https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png", {
        maxZoom: 9,
        minZoom: 2,
        attribution: "&copy; OpenStreetMap contributors &copy; CARTO",
      }).addTo(sequenceMap);

      mainshockLayer = window.L.layerGroup().addTo(sequenceMap);
      aftershockLayer = window.L.layerGroup().addTo(sequenceMap);
      radiusLayer = window.L.layerGroup().addTo(sequenceMap);
      return sequenceMap;
    }

    function renderMap(sequence) {
      if (!mapNode || !mapMeta) return;
      const map = ensureMap();
      if (!map || !mainshockLayer || !aftershockLayer || !radiusLayer) {
        mapMeta.textContent = "Map unavailable in this environment.";
        return;
      }

      mainshockLayer.clearLayers();
      aftershockLayer.clearLayers();
      radiusLayer.clearLayers();

      if (!sequence || typeof sequence !== "object") {
        mapMeta.textContent = "Waiting for sequence selection...";
        return;
      }

      const mainshock = sequence.mainshock || {};
      const mLat = Number(mainshock.latitude);
      const mLon = Number(mainshock.longitude);
      const radiusKm = Number(sequence.radius_km || 150);
      const points = [];

      if (Number.isFinite(mLat) && Number.isFinite(mLon)) {
        const marker = window.L.circleMarker([mLat, mLon], {
          radius: 9,
          color: "#fecaca",
          weight: 2,
          fillColor: "#ef4444",
          fillOpacity: 0.9,
        });
        marker.bindTooltip(`Mainshock ${fmtMag(mainshock.magnitude)}`);
        marker.addTo(mainshockLayer);
        points.push([mLat, mLon]);

        const radiusCircle = window.L.circle([mLat, mLon], {
          radius: radiusKm * 1000,
          color: "#f97316",
          weight: 1.4,
          fillOpacity: 0.05,
        });
        radiusCircle.addTo(radiusLayer);
      }

      const rows = Array.isArray(sequence.aftershocks) ? sequence.aftershocks : [];
      const baseZoom = map.getZoom();
      const lowZoom = baseZoom <= 2.2;
      const midZoom = baseZoom > 2.2 && baseZoom <= 3;
      const strokeOpacity = lowZoom ? 0.42 : (midZoom ? 0.62 : 0.78);
      rows.forEach((event) => {
        const lat = Number(event.latitude);
        const lon = Number(event.longitude);
        if (!Number.isFinite(lat) || !Number.isFinite(lon)) return;
        const marker = window.L.circleMarker([lat, lon], {
          radius: mapRadius(Number(event.magnitude)),
          color: `rgba(255,255,255,${strokeOpacity})`,
          opacity: strokeOpacity,
          weight: lowZoom ? 0.55 : (midZoom ? 0.75 : 0.9),
          fillColor: magColor(Number(event.magnitude)),
          fillOpacity: lowZoom ? 0.78 : (midZoom ? 0.82 : 0.85),
        });
        marker.bindTooltip(`${fmtMag(event.magnitude)} · ${event.place || "Unknown"}`);
        marker.addTo(aftershockLayer);
        points.push([lat, lon]);
      });

      if (points.length === 1) {
        map.setView(points[0], 6);
      } else if (points.length > 1) {
        map.fitBounds(window.L.latLngBounds(points), { padding: [24, 24], maxZoom: 7 });
      } else {
        map.setView([8, 0], 2);
      }

      mapMeta.textContent = `${rows.length} aftershocks plotted · Radius ${radiusKm.toFixed(0)} km`;
    }

    function renderTimeline(sequence) {
      if (!timelineBars || !timelineMeta) return;
      if (!sequence || typeof sequence !== "object") {
        timelineBars.innerHTML = "<li class='event-item'>Select a sequence to load timeline.</li>";
        timelineMeta.textContent = "Daily counts in sequence window.";
        return;
      }

      const mainTs = Date.parse(sequence?.mainshock?.event_time_utc || "");
      const days = Math.max(1, Math.round(Number(sequence.window_hours || 168) / 24));
      const rows = Array.isArray(sequence.aftershocks) ? sequence.aftershocks : [];
      if (!Number.isFinite(mainTs) || rows.length === 0) {
        timelineBars.innerHTML = "<li class='event-item'>No aftershock points available for timeline.</li>";
        timelineMeta.textContent = "No aftershock points in this cycle.";
        return;
      }

      const byDay = new Map();
      for (let i = 0; i < days; i++) {
        const ts = mainTs + i * 86400000;
        const key = dayKeyItaly(ts);
        byDay.set(key, 0);
      }

      rows.forEach((event) => {
        const ts = Date.parse(event.event_time_utc || "");
        if (!Number.isFinite(ts)) return;
        const key = dayKeyItaly(ts);
        if (byDay.has(key)) {
          byDay.set(key, Number(byDay.get(key) || 0) + 1);
        }
      });

      const values = Array.from(byDay.values());
      const maxCount = Math.max(1, ...values);
      timelineBars.innerHTML = Array.from(byDay.entries())
        .map(([label, count]) => {
          const width = Math.round((count / maxCount) * 100);
          return `
            <li class="event-item aftershocks-bar-item">
              <strong>${escapeHtml(label)}</strong>
              <span class="aftershocks-bar-count">${count} event(s)</span>
              <div class="aftershocks-meter">
                <div class="aftershocks-meter-fill is-timeline" style="width:${width}%"></div>
              </div>
            </li>
          `;
        })
        .join("");
      timelineMeta.textContent = `${rows.length} total aftershocks across ${days} days`;
    }

    function renderMagnitudeHistogram(sequence) {
      if (!magBars || !magMeta) return;
      if (!sequence || typeof sequence !== "object") {
        magBars.innerHTML = "<li class='event-item'>Select a sequence to load distribution.</li>";
        magMeta.textContent = "Histogram by aftershock magnitude.";
        return;
      }

      const rows = Array.isArray(sequence.aftershocks) ? sequence.aftershocks : [];
      if (rows.length === 0) {
        magBars.innerHTML = "<li class='event-item'>No aftershocks available for distribution.</li>";
        magMeta.textContent = "No aftershock points in this cycle.";
        return;
      }

      const bins = [
        { key: "1-1.9", min: 1.0, max: 2.0, count: 0 },
        { key: "2-2.9", min: 2.0, max: 3.0, count: 0 },
        { key: "3-3.9", min: 3.0, max: 4.0, count: 0 },
        { key: "4-4.9", min: 4.0, max: 5.0, count: 0 },
        { key: "5-5.9", min: 5.0, max: 6.0, count: 0 },
        { key: "6+", min: 6.0, max: 99, count: 0 },
      ];

      rows.forEach((event) => {
        const mag = Number(event.magnitude);
        if (!Number.isFinite(mag)) return;
        const bucket = bins.find((bin) => mag >= bin.min && mag < bin.max);
        if (bucket) bucket.count += 1;
      });

      const maxCount = Math.max(1, ...bins.map((bin) => bin.count));
      magBars.innerHTML = bins
        .map((bin) => {
          const width = Math.round((bin.count / maxCount) * 100);
          return `
            <li class="event-item aftershocks-bar-item">
              <strong>M${escapeHtml(bin.key)}</strong>
              <span class="aftershocks-bar-count">${bin.count} event(s)</span>
              <div class="aftershocks-meter">
                <div class="aftershocks-meter-fill is-magnitude" style="width:${width}%"></div>
              </div>
            </li>
          `;
        })
        .join("");

      magMeta.textContent = `Strongest aftershock ${fmtMag(sequence.strongest_aftershock_magnitude)} · ${rows.length} events`;
    }

    const indexUrl = () => "/api/aftershocks.php";
    const detailUrl = (sequenceId) => `/api/aftershocks.php?sequence_id=${encodeURIComponent(sequenceId)}`;

    async function fetchJson(url) {
      const response = await fetch(url, {
        headers: { Accept: "application/json" },
        cache: "no-store",
      });
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      return response.json();
    }

    function renderIndex(payload) {
      latestIndex = payload;
      if (kpiActive) kpiActive.textContent = String(payload.active_sequences_count ?? 0);
      if (kpiTrigger) kpiTrigger.textContent = `M${Number(payload.trigger_magnitude || 6).toFixed(1)}+`;
      if (kpiRadius) kpiRadius.textContent = `${Number(payload.radius_km || 150).toFixed(0)} km`;
      if (kpiWindow) kpiWindow.textContent = `${Number(payload.window_hours || 168) / 24} days`;
      if (sequencesMeta) {
        sequencesMeta.textContent = `Updated ${fmtUtc(payload.generated_at)} · Source feed ${fmtUtc(payload.source_feed_generated_at)}`;
      }

      const rows = Array.isArray(payload.sequences) ? payload.sequences : [];
      if (!sequencesList) return;

      if (rows.length === 0) {
        sequencesList.innerHTML = "<li class='event-item'>No active aftershock sequence right now.</li>";
        return;
      }

      if (!activeSequenceId) {
        if (requestedEventId) {
          const byEvent = rows.find((row) => (row?.mainshock?.id || "") === requestedEventId);
          if (byEvent?.sequence_id) {
            activeSequenceId = byEvent.sequence_id;
          }
        }
        if (!activeSequenceId) {
          activeSequenceId = rows[0].sequence_id || "";
        }
      }

      sequencesList.innerHTML = rows
        .map((row) => {
          const sid = row.sequence_id || "";
          const mainshock = row.mainshock || {};
          const isActive = sid === activeSequenceId;
          const href = `/aftershocks.php?sequence_id=${encodeURIComponent(sid)}`;
          return `
            <li class="event-item aftershocks-sequence-item">
              <strong>${escapeHtml(fmtMag(mainshock.magnitude))} · ${escapeHtml(mainshock.place || "Unknown location")}</strong>
              <span class="aftershocks-sequence-meta">Mainshock: ${escapeHtml(fmtUtc(mainshock.event_time_utc))}</span>
              <span class="aftershocks-sequence-meta">Aftershocks: ${Number(row.aftershocks_count || 0)} total · ${Number(row.aftershocks_24h_count || 0)} in 24h</span>
              <a class="inline-link ${isActive ? "is-active" : ""}" href="${href}">${isActive ? "Viewing sequence" : "Open sequence"}</a>
            </li>
          `;
        })
        .join("");
    }

    function renderSequence(sequencePayload) {
      const sequence =
        sequencePayload && typeof sequencePayload === "object"
          ? (sequencePayload.sequence && typeof sequencePayload.sequence === "object"
              ? sequencePayload.sequence
              : sequencePayload)
          : null;
      if (!sequence || typeof sequence !== "object" || !sequence.mainshock) {
        if (detailTitle) detailTitle.textContent = "Sequence Detail";
        if (detailMeta) detailMeta.textContent = "Sequence unavailable.";
        if (detailKpis) detailKpis.innerHTML = "<li class='event-item'>No detail payload available.</li>";
        if (eventsList) eventsList.innerHTML = "<li class='event-item'>No aftershock events loaded.</li>";
        renderMap(null);
        renderTimeline(null);
        renderMagnitudeHistogram(null);
        return;
      }

      const mainshock = sequence.mainshock || {};
      if (detailTitle) {
        detailTitle.textContent = `${fmtMag(mainshock.magnitude)} · ${mainshock.place || "Unknown location"}`;
      }
      if (detailMeta) {
        detailMeta.textContent = `Mainshock ${fmtUtc(mainshock.event_time_utc)} · Expires ${fmtUtc(sequence.expires_at)}`;
      }
      if (detailKpis) {
        detailKpis.innerHTML = `
          <li class="event-item aftershocks-detail-kpi"><strong>Status</strong><span>${escapeHtml(sequence.status || "active")}</span></li>
          <li class="event-item aftershocks-detail-kpi"><strong>Total aftershocks</strong><span>${Number(sequence.aftershocks_count || 0)}</span></li>
          <li class="event-item aftershocks-detail-kpi"><strong>Last 24h</strong><span>${Number(sequence.aftershocks_24h_count || 0)}</span></li>
          <li class="event-item aftershocks-detail-kpi"><strong>Strongest aftershock</strong><span>${escapeHtml(fmtMag(sequence.strongest_aftershock_magnitude))}</span></li>
        `;
      }

      const rows = Array.isArray(sequence.aftershocks) ? sequence.aftershocks : [];
      if (eventsList) {
        if (rows.length === 0) {
          eventsList.innerHTML = "<li class='event-item'>No aftershocks in current feed window for this sequence.</li>";
        } else {
          eventsList.innerHTML = rows
            .map((event) => `
              <li class="event-item aftershocks-stream-item">
                <strong>${escapeHtml(fmtMag(event.magnitude))} · ${escapeHtml(event.place || "Unknown location")}</strong>
                <span class="aftershocks-stream-meta">${escapeHtml(fmtUtc(event.event_time_utc))} · ${Number(event.distance_km_from_mainshock || 0).toFixed(1)} km from mainshock</span>
              </li>
            `)
            .join("");
        }
      }

      renderMap(sequence);
      renderTimeline(sequence);
      renderMagnitudeHistogram(sequence);
    }

    async function refresh() {
      try {
        const indexPayload = await fetchJson(indexUrl());
        renderIndex(indexPayload);

        if (!activeSequenceId) {
          renderSequence(null);
          return;
        }

        const detailPayload = await fetchJson(detailUrl(activeSequenceId));
        renderSequence(detailPayload);
      } catch (error) {
        if (sequencesMeta) sequencesMeta.textContent = "Unable to load aftershock data right now.";
        if (sequencesList) sequencesList.innerHTML = "<li class='event-item'>Aftershock index unavailable.</li>";
        if (detailMeta) detailMeta.textContent = "Detail unavailable.";
      }
    }

    void refresh();
    window.setInterval(() => {
      if (document.hidden) return;
      void refresh();
    }, 60000);
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
