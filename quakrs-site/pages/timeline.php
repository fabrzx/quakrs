<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Live Timeline';
$pageDescription = 'Cross-hazard live timeline with chronological updates across earthquakes, volcanoes, tsunami, and space weather.';
$currentPage = 'timeline';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.timeline.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.timeline.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.timeline.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel panel-kpi timeline-kpi-row">
  <article class="card kpi-card">
    <p class="kpi-label">Total (filtered)</p>
    <p id="timeline-kpi-total" class="kpi-value">--</p>
    <p class="kpi-note">Visible across selected filters</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Earthquakes</p>
    <p id="timeline-kpi-earthquakes" class="kpi-value">--</p>
    <p class="kpi-note">Current filtered stream</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Volcanoes</p>
    <p id="timeline-kpi-volcanoes" class="kpi-value">--</p>
    <p class="kpi-note">Current filtered stream</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Tsunami</p>
    <p id="timeline-kpi-tsunami" class="kpi-value">--</p>
    <p class="kpi-note">Current filtered stream</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Space Weather</p>
    <p id="timeline-kpi-space" class="kpi-value">--</p>
    <p class="kpi-note">Current filtered stream</p>
  </article>
</section>

<section class="panel timeline-main-panel">
  <article class="card timeline-main-card">
    <div class="feed-head">
      <h3>Timeline Controls</h3>
      <p id="timeline-live-meta" class="feed-meta">Loading timeline sources...</p>
    </div>

    <div class="timeline-controls-grid">
      <label class="timeline-filter-item">
        <span class="timeline-filter-label">Hazard</span>
        <select id="timeline-filter-hazard">
          <option value="all">All hazards</option>
          <option value="earthquakes">Earthquakes</option>
          <option value="volcanoes">Volcanoes</option>
          <option value="tsunami">Tsunami</option>
          <option value="space-weather">Space weather</option>
        </select>
      </label>
      <label class="timeline-filter-item">
        <span class="timeline-filter-label">Priority</span>
        <select id="timeline-filter-priority">
          <option value="all">All priorities</option>
          <option value="P1">P1</option>
          <option value="P2">P2</option>
          <option value="P3">P3</option>
        </select>
      </label>
      <label class="timeline-filter-item">
        <span class="timeline-filter-label">Window</span>
        <select id="timeline-filter-window">
          <option value="1">Last 1h</option>
          <option value="6">Last 6h</option>
          <option value="24" selected>Last 24h</option>
          <option value="72">Last 72h</option>
          <option value="168">Last 7d</option>
        </select>
      </label>
      <label class="timeline-filter-item">
        <span class="timeline-filter-label">Type</span>
        <select id="timeline-filter-type">
          <option value="all">All types</option>
          <option value="new">New</option>
          <option value="update">Update</option>
          <option value="active">Active</option>
        </select>
      </label>
      <label class="timeline-filter-item">
        <span class="timeline-filter-label">Scope</span>
        <select id="timeline-filter-area">
          <option value="all">Global + Italy</option>
          <option value="italy">Italy</option>
          <option value="global">Global only</option>
        </select>
      </label>
      <label class="timeline-filter-item timeline-filter-toggle">
        <span class="timeline-filter-label">State</span>
        <span class="timeline-toggle-row">
          <input id="timeline-filter-active" type="checkbox" />
          <span>Active only</span>
        </span>
      </label>
    </div>

    <div class="insight-pills timeline-live-toolbar">
      <span id="timeline-live-updated" class="insight-pill">Updated --</span>
      <button id="timeline-live-refresh" class="btn btn-ghost" type="button">Refresh now</button>
    </div>

    <ul id="timeline-live-list" class="events-list live-feed-scroll">
      <li class="event-item">Loading timeline events...</li>
    </ul>

    <button id="timeline-live-more" class="timeline-more" type="button" hidden>Load more</button>
  </article>
</section>

<script>
  (() => {
    const listNode = document.querySelector('#timeline-live-list');
    const metaNode = document.querySelector('#timeline-live-meta');
    const updatedNode = document.querySelector('#timeline-live-updated');
    const moreButton = document.querySelector('#timeline-live-more');
    const refreshButton = document.querySelector('#timeline-live-refresh');

    const kpiTotal = document.querySelector('#timeline-kpi-total');
    const kpiEq = document.querySelector('#timeline-kpi-earthquakes');
    const kpiVolc = document.querySelector('#timeline-kpi-volcanoes');
    const kpiTsunami = document.querySelector('#timeline-kpi-tsunami');
    const kpiSpace = document.querySelector('#timeline-kpi-space');

    const filterHazard = document.querySelector('#timeline-filter-hazard');
    const filterPriority = document.querySelector('#timeline-filter-priority');
    const filterWindow = document.querySelector('#timeline-filter-window');
    const filterType = document.querySelector('#timeline-filter-type');
    const filterArea = document.querySelector('#timeline-filter-area');
    const filterActive = document.querySelector('#timeline-filter-active');

    const state = {
      allItems: [],
      filteredItems: [],
      visibleCount: 40,
      loading: false,
      lastUpdatedTs: 0,
    };

    const seismicContext = {
      ready: false,
      source: 'none',
      cellSize: 1,
      p30: 0,
      p70: 0,
      cells: new Map(),
      fetchedAtTs: 0,
      loading: false,
    };

    function applyInitialFiltersFromQuery() {
      const params = new URLSearchParams(window.location.search);
      const hazard = String(params.get('hazard') || '').trim();
      const priority = String(params.get('priority') || '').trim();
      const area = String(params.get('area') || '').trim();
      const windowHours = String(params.get('window') || '').trim();
      const type = String(params.get('type') || '').trim();
      const active = String(params.get('active') || '').trim();

      const applyIfValid = (node, value, allowed) => {
        if (!(node instanceof HTMLSelectElement)) return;
        if (!value || !allowed.includes(value)) return;
        node.value = value;
      };

      applyIfValid(filterHazard, hazard, ['all', 'earthquakes', 'volcanoes', 'tsunami', 'space-weather']);
      applyIfValid(filterPriority, priority, ['all', 'P1', 'P2', 'P3']);
      applyIfValid(filterArea, area, ['all', 'italy', 'global']);
      applyIfValid(filterWindow, windowHours, ['1', '6', '24', '72', '168']);
      applyIfValid(filterType, type, ['all', 'new', 'update', 'active']);

      if (filterActive instanceof HTMLInputElement) {
        filterActive.checked = active === '1' || active === 'true';
      }
    }

    function escapeHtml(value) {
      return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    applyInitialFiltersFromQuery();

    function parseTime(value) {
      if (!value) {
        return 0;
      }
      const ts = new Date(value).getTime();
      return Number.isFinite(ts) ? ts : 0;
    }

    function toCellBucket(value, cellSize) {
      const numeric = Number(value);
      if (!Number.isFinite(numeric) || !Number.isFinite(cellSize) || cellSize <= 0) {
        return null;
      }
      return Math.floor(numeric / cellSize) * cellSize;
    }

    function cellKeyForEvent(event) {
      const lat = Number(event?.latitude);
      const lon = Number(event?.longitude);
      if (!Number.isFinite(lat) || !Number.isFinite(lon)) return null;
      const cellLat = toCellBucket(lat, seismicContext.cellSize);
      const cellLon = toCellBucket(lon, seismicContext.cellSize);
      if (!Number.isFinite(cellLat) || !Number.isFinite(cellLon)) return null;
      return `${cellLat.toFixed(2)}|${cellLon.toFixed(2)}`;
    }

    function cellDailyAvgForEvent(event) {
      const key = cellKeyForEvent(event);
      if (!key) return 0;
      const row = seismicContext.cells.get(key);
      if (!row || typeof row.dailyAvg !== 'number') return 0;
      return row.dailyAvg;
    }

    function applySeismicContext(payload) {
      if (!payload || typeof payload !== 'object') {
        return;
      }
      const cellSize = Number(payload.cell_size_deg);
      const p30 = Number(payload?.distribution?.p30_daily_avg);
      const p70 = Number(payload?.distribution?.p70_daily_avg);
      const rawCells = payload.cells && typeof payload.cells === 'object' ? payload.cells : {};
      const nextCells = new Map();
      Object.entries(rawCells).forEach(([key, value]) => {
        const count = Number(value?.count);
        const dailyAvg = Number(value?.daily_avg);
        if (!Number.isFinite(count) || !Number.isFinite(dailyAvg) || count <= 0) return;
        nextCells.set(String(key), { count, dailyAvg });
      });
      seismicContext.ready = nextCells.size > 0;
      seismicContext.source = String(payload.source || 'none');
      seismicContext.cellSize = Number.isFinite(cellSize) && cellSize > 0 ? cellSize : 1;
      seismicContext.p30 = Number.isFinite(p30) ? p30 : 0;
      seismicContext.p70 = Number.isFinite(p70) ? p70 : 0;
      seismicContext.cells = nextCells;
      seismicContext.fetchedAtTs = Date.now();
    }

    async function ensureSeismicContext() {
      const maxAgeMs = 6 * 60 * 60 * 1000;
      if (seismicContext.loading) return;
      if (seismicContext.ready && (Date.now() - seismicContext.fetchedAtTs) <= maxAgeMs) return;
      seismicContext.loading = true;
      try {
        const response = await fetch('/api/seismicity-context.php?days=30&cell_size=1.0&min_magnitude=2.5', {
          headers: { Accept: 'application/json' },
          cache: 'no-store',
        });
        if (!response.ok) return;
        const payload = await response.json();
        applySeismicContext(payload);
      } catch (_) {
        // Keep last known context when refresh fails.
      } finally {
        seismicContext.loading = false;
      }
    }

    function hasSettlementReference(place) {
      const label = String(place || '').toLowerCase();
      if (!label) return false;
      return /\b\d{1,3}\s?km\b/.test(label) || /\bof\b/.test(label) || /\bnear\b/.test(label);
    }

    function haversineKm(lat1, lon1, lat2, lon2) {
      const toRad = (value) => (value * Math.PI) / 180;
      const dLat = toRad(lat2 - lat1);
      const dLon = toRad(lon2 - lon1);
      const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
      return 6371 * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
    }

    function localSwarmCount(referenceEvent, events) {
      if (!referenceEvent || !Array.isArray(events)) return 0;
      const lat = Number(referenceEvent?.latitude);
      const lon = Number(referenceEvent?.longitude);
      const ts = parseTime(referenceEvent?.event_time_utc);
      if (!Number.isFinite(lat) || !Number.isFinite(lon) || ts <= 0) return 0;

      return events.reduce((count, candidate) => {
        const cLat = Number(candidate?.latitude);
        const cLon = Number(candidate?.longitude);
        const cTs = parseTime(candidate?.event_time_utc);
        const cMag = Number(candidate?.magnitude);
        if (!Number.isFinite(cLat) || !Number.isFinite(cLon) || cTs <= 0 || !Number.isFinite(cMag)) return count;
        if (Math.abs(ts - cTs) > 36 * 60 * 60 * 1000) return count;
        if (cMag < 1.5) return count;
        const distance = haversineKm(lat, lon, cLat, cLon);
        if (!Number.isFinite(distance) || distance > 140) return count;
        return count + 1;
      }, 0);
    }

    function deriveSeismicContext(event, swarm, nearSettlement) {
      const dailyAvg = cellDailyAvgForEvent(event);
      const hasBaseline = seismicContext.ready && seismicContext.p70 > 0;
      const high = hasBaseline ? dailyAvg >= seismicContext.p70 : swarm >= 8;
      const low = hasBaseline ? (dailyAvg > 0 && dailyAvg <= seismicContext.p30) : (swarm <= 2 && !nearSettlement);
      return { high, low, dailyAvg, hasBaseline };
    }

    function p3UpperBoundForContext(event, swarm, nearSettlement) {
      const ctx = deriveSeismicContext(event, swarm, nearSettlement);
      if (ctx.high) return 4.0;
      if (ctx.low && !nearSettlement) return 3.0;
      return 3.5;
    }

    function minMagnitudeForPriority(event, swarm, nearSettlement) {
      const ctx = deriveSeismicContext(event, swarm, nearSettlement);
      if (ctx.high) return 3.0;
      if (ctx.low && !nearSettlement) return 2.9;
      if (swarm <= 2 && !nearSettlement) return 2.7;
      return 2.5;
    }

    function priorityForEarthquake(event, allEvents, nowTs) {
      const magnitude = Number(event?.magnitude);
      if (!Number.isFinite(magnitude)) {
        return null;
      }
      const depth = Number(event?.depth_km);
      const depthAbs = Number.isFinite(depth) ? Math.abs(depth) : NaN;
      const ts = parseTime(event?.event_time_utc);
      const ageMinutes = ts > 0 ? Math.max(0, (nowTs - ts) / 60000) : 9999;
      const nearSettlement = hasSettlementReference(event?.place);
      const swarm = localSwarmCount(event, allEvents);
      const minMagnitude = minMagnitudeForPriority(event, swarm, nearSettlement);
      if (magnitude < minMagnitude) {
        return null;
      }
      const p3UpperBound = p3UpperBoundForContext(event, swarm, nearSettlement);

      if (magnitude >= 6.8) {
        return 'P1';
      }
      if (magnitude >= 6.2 && ageMinutes <= 120) {
        return 'P1';
      }
      if (magnitude >= 5.0) {
        return 'P2';
      }
      if (magnitude >= 4.2 && Number.isFinite(depthAbs) && depthAbs <= 35) {
        return 'P2';
      }
      if (magnitude >= 3.4 && swarm >= 5) {
        return 'P2';
      }
      if (magnitude < p3UpperBound) {
        return 'P3';
      }
      return 'P2';
    }

    function priorityForTsunami(level) {
      const value = String(level || '').toLowerCase();
      if (value.includes('warning')) {
        return 'P1';
      }
      if (value.includes('watch') || value.includes('advisory')) {
        return 'P2';
      }
      return 'P3';
    }

    function priorityForSpace(kp) {
      if (typeof kp !== 'number') {
        return 'P3';
      }
      if (kp >= 7) {
        return 'P1';
      }
      if (kp >= 5) {
        return 'P2';
      }
      return 'P3';
    }

    function isItalyArea(area) {
      const label = String(area || '').toLowerCase();
      return label.includes('italy') || label.includes('italia');
    }

    function buildEventDetailUrl(event) {
      const params = new URLSearchParams();
      if (typeof event.id === 'string' && event.id !== '') params.set('id', event.id);
      if (typeof event.latitude === 'number') params.set('lat', event.latitude.toFixed(5));
      if (typeof event.longitude === 'number') params.set('lon', event.longitude.toFixed(5));
      if (typeof event.magnitude === 'number') params.set('mag', event.magnitude.toFixed(2));
      if (typeof event.depth_km === 'number') params.set('depth', event.depth_km.toFixed(2));
      if (event.place) params.set('place', String(event.place));
      if (event.event_time_utc) params.set('time', String(event.event_time_utc));
      return '/event.php?' + params.toString();
    }

    function normalizeEarthquakes(payload) {
      const events = Array.isArray(payload?.events) ? payload.events : [];
      const now = Date.now();
      return events.slice(0, 180).map((event) => {
        const ts = parseTime(event?.event_time_utc);
        const source = event?.source_provider || (Array.isArray(event?.source_providers) ? event.source_providers.join(', ') : 'Seismic feed');
        const mag = typeof event?.magnitude === 'number' ? event.magnitude : null;
        const depth = typeof event?.depth_km === 'number' ? event.depth_km.toFixed(1) + ' km' : 'n/a depth';
        const priority = priorityForEarthquake(event, events, now);
        if (!priority) return null;
        return {
          id: 'eq:' + String(event?.id || Math.random()),
          hazard: 'earthquakes',
          priority,
          type: 'new',
          status: 'active',
          active: now - ts <= 24 * 60 * 60 * 1000,
          timeTs: ts,
          timeIso: event?.event_time_utc || null,
          title: `${mag !== null ? 'M' + mag.toFixed(1) : 'M?'} · ${event?.place || 'Unknown location'}`,
          area: event?.place || 'Unknown area',
          detail: depth,
          source,
          links: {
            monitor: '/earthquakes.php',
            map: '/maps.php',
            detail: buildEventDetailUrl(event),
          },
        };
      }).filter(Boolean);
    }

    function normalizeVolcanoes(payload) {
      const events = Array.isArray(payload?.events) ? payload.events : [];
      const now = Date.now();
      return events.slice(0, 80).map((event) => {
        const ts = parseTime(event?.event_time_utc);
        const volcano = event?.volcano || event?.title || 'Volcano event';
        const country = event?.country || 'Unknown country';
        const isEruptive = !!event?.is_new_eruptive;
        return {
          id: 'volc:' + String(event?.id || Math.random()),
          hazard: 'volcanoes',
          priority: isEruptive ? 'P2' : 'P3',
          type: isEruptive ? 'new' : 'update',
          status: 'active',
          active: now - ts <= 7 * 24 * 60 * 60 * 1000,
          timeTs: ts,
          timeIso: event?.event_time_utc || null,
          title: `${volcano} · ${isEruptive ? 'new eruptive activity' : 'activity update'}`,
          area: country,
          detail: event?.summary || 'Volcanic bulletin update',
          source: payload?.provider || 'Volcano feed',
          links: {
            monitor: '/volcanoes.php',
            map: null,
            detail: event?.source_url || '/volcanoes.php',
          },
        };
      });
    }

    function normalizeTsunami(payload) {
      const alerts = Array.isArray(payload?.alerts) ? payload.alerts : [];
      const now = Date.now();
      return alerts.slice(0, 80).map((alert, index) => {
        const issuedTs = parseTime(alert?.issued_at_utc || payload?.generated_at);
        const expiresTs = parseTime(alert?.expires_at_utc);
        const level = alert?.warning_level || 'Statement';
        const statusRaw = String(alert?.status || 'active').toLowerCase();
        const active = expiresTs > 0 ? expiresTs > now : true;
        return {
          id: 'tsunami:' + String(alert?.id || index),
          hazard: 'tsunami',
          priority: priorityForTsunami(level),
          type: statusRaw.includes('update') ? 'update' : 'active',
          status: active ? 'active' : 'ended',
          active,
          timeTs: issuedTs,
          timeIso: alert?.issued_at_utc || payload?.generated_at || null,
          title: `${level} · ${alert?.event || 'Tsunami advisory'}`,
          area: alert?.region || 'Unknown region',
          detail: alert?.severity ? `Severity: ${alert.severity}` : 'Operational advisory',
          source: payload?.provider || 'Tsunami feed',
          links: {
            monitor: '/tsunami.php',
            map: null,
            detail: alert?.source_bulletin || '/tsunami.php',
          },
        };
      });
    }

    function normalizeSpaceWeather(payload) {
      const items = [];
      const now = Date.now();
      const generatedTs = parseTime(payload?.generated_at);
      const kp = typeof payload?.kp_index_current === 'number' ? payload.kp_index_current : null;

      items.push({
        id: 'space:kp:current',
        hazard: 'space-weather',
        priority: priorityForSpace(kp),
        type: 'update',
        status: 'active',
        active: true,
        timeTs: generatedTs,
        timeIso: payload?.generated_at || null,
        title: `Geomagnetic conditions · Kp ${kp !== null ? kp.toFixed(1) : '--'}`,
        area: 'Global heliophysics',
        detail: payload?.storm_level || payload?.kp_band_current || 'Space weather update',
        source: payload?.provider || 'Space weather feed',
        links: {
          monitor: '/space-weather.php',
          map: null,
          detail: '/space-weather.php',
        },
      });

      const flareEvents = Array.isArray(payload?.flare_events) ? payload.flare_events : [];
      flareEvents.slice(0, 30).forEach((flare, index) => {
        const classLabel = String(flare?.class || 'flare');
        const ts = parseTime(flare?.time_utc);
        items.push({
          id: 'space:flare:' + index + ':' + String(flare?.time_utc || index),
          hazard: 'space-weather',
          priority: /^X|^M/.test(classLabel) ? 'P2' : 'P3',
          type: 'new',
          status: 'active',
          active: now - ts <= 24 * 60 * 60 * 1000,
          timeTs: ts,
          timeIso: flare?.time_utc || null,
          title: `Solar flare ${classLabel}`,
          area: 'Sun',
          detail: 'Detected from X-ray flux stream',
          source: payload?.provider || 'Space weather feed',
          links: {
            monitor: '/space-weather.php',
            map: null,
            detail: '/space-weather.php',
          },
        });
      });

      return items;
    }

    async function loadAllFeeds() {
      await ensureSeismicContext();

      const requests = [
        fetch('/api/earthquakes.php', { headers: { Accept: 'application/json' }, cache: 'no-store' }),
        fetch('/api/volcanoes.php', { headers: { Accept: 'application/json' }, cache: 'no-store' }),
        fetch('/api/tsunami.php', { headers: { Accept: 'application/json' }, cache: 'no-store' }),
        fetch('/api/space-weather.php', { headers: { Accept: 'application/json' }, cache: 'no-store' }),
      ];

      const [eqRes, volcRes, tsRes, swRes] = await Promise.allSettled(requests);

      const readJson = async (result) => {
        if (result.status !== 'fulfilled') {
          return null;
        }
        if (!result.value.ok) {
          return null;
        }
        return result.value.json().catch(() => null);
      };

      const [eqPayload, volcPayload, tsPayload, swPayload] = await Promise.all([
        readJson(eqRes),
        readJson(volcRes),
        readJson(tsRes),
        readJson(swRes),
      ]);

      const all = [
        ...normalizeEarthquakes(eqPayload),
        ...normalizeVolcanoes(volcPayload),
        ...normalizeTsunami(tsPayload),
        ...normalizeSpaceWeather(swPayload),
      ].filter((item) => item.timeTs > 0);

      all.sort((a, b) => b.timeTs - a.timeTs);
      state.allItems = all;
      state.lastUpdatedTs = Date.now();

      const sources = [];
      if (eqPayload) sources.push('earthquakes');
      if (volcPayload) sources.push('volcanoes');
      if (tsPayload) sources.push('tsunami');
      if (swPayload) sources.push('space');
      metaNode.textContent = sources.length > 0
        ? `Sources online: ${sources.join(', ')}${seismicContext.ready ? ` · seismic context: ${seismicContext.source}` : ''}`
        : 'All timeline sources unavailable right now.';
    }

    function applyFilters(items) {
      const hazard = String(filterHazard?.value || 'all');
      const priority = String(filterPriority?.value || 'all');
      const hours = Number(filterWindow?.value || '24');
      const type = String(filterType?.value || 'all');
      const area = String(filterArea?.value || 'all');
      const activeOnly = Boolean(filterActive?.checked);
      const now = Date.now();
      const minTs = now - hours * 60 * 60 * 1000;

      return items.filter((item) => {
        if (hazard !== 'all' && item.hazard !== hazard) {
          return false;
        }
        if (priority !== 'all' && item.priority !== priority) {
          return false;
        }
        if (item.timeTs < minTs) {
          return false;
        }
        if (type !== 'all') {
          if (type === 'active') {
            if (item.status !== 'active') {
              return false;
            }
          } else if (item.type !== type) {
            return false;
          }
        }
        if (area === 'italy' && !isItalyArea(item.area)) {
          return false;
        }
        if (area === 'global' && isItalyArea(item.area)) {
          return false;
        }
        if (activeOnly && !item.active) {
          return false;
        }
        return true;
      });
    }

    function renderKpis(items) {
      const eq = items.filter((item) => item.hazard === 'earthquakes').length;
      const volc = items.filter((item) => item.hazard === 'volcanoes').length;
      const tsunami = items.filter((item) => item.hazard === 'tsunami').length;
      const space = items.filter((item) => item.hazard === 'space-weather').length;
      kpiTotal.textContent = String(items.length);
      kpiEq.textContent = String(eq);
      kpiVolc.textContent = String(volc);
      kpiTsunami.textContent = String(tsunami);
      kpiSpace.textContent = String(space);
    }

    function renderList(items) {
      state.filteredItems = items;
      const visible = items.slice(0, state.visibleCount);

      if (visible.length === 0) {
        listNode.innerHTML = "<li class='event-item'>No timeline events for current filters.</li>";
        moreButton.hidden = true;
        renderKpis([]);
        return;
      }

      function detailLinkLabel(item) {
        const hazard = String(item?.hazard || '');
        const detailLink = String(item?.links?.detail || '');
        const monitorLink = String(item?.links?.monitor || '');

        if (hazard === 'earthquakes') {
          return 'Open event';
        }
        if (hazard === 'tsunami') {
          return 'Open bulletin';
        }
        if (hazard === 'volcanoes' || hazard === 'space-weather') {
          return detailLink && detailLink !== monitorLink ? 'Open source' : 'Open details';
        }
        return 'Open details';
      }

      listNode.innerHTML = visible.map((item) => {
        const time = item.timeIso ? new Date(item.timeIso).toLocaleString([], { month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' }) : '--';
        const linkDetail = item.links?.detail || item.links?.monitor || '/';
        const linkMonitor = item.links?.monitor || '/';
        const linkMap = typeof item.links?.map === 'string' ? item.links.map : '';
        const detailLabel = detailLinkLabel(item);
        const priorityClass = String(item.priority || 'P3').toLowerCase().replace(/[^a-z0-9-]/g, '');
        const hazardClass = String(item.hazard || 'unknown').toLowerCase().replace(/[^a-z0-9-]/g, '');
        const hazardLabel = String(item.hazard || 'unknown').replace('-', ' ');
        const mapLinkHtml = linkMap && linkMap !== linkMonitor
          ? ` · <a class="inline-link" href="${escapeHtml(linkMap)}">Open map</a>`
          : '';
        const detailLinkHtml = linkDetail && linkDetail !== linkMonitor
          ? ` · <a class="inline-link" href="${escapeHtml(linkDetail)}">${escapeHtml(detailLabel)}</a>`
          : '';
        return `
          <li class="event-item timeline-live-item hazard-${escapeHtml(hazardClass)}" style="display:flex;flex-direction:column;gap:6px;min-height:auto;padding:12px 12px;">
            <div class="timeline-live-head" style="display:flex;align-items:center;flex-wrap:wrap;gap:6px;">
              <span class="timeline-chip timeline-chip-priority is-${escapeHtml(priorityClass)}">${escapeHtml(item.priority)}</span>
              <span class="timeline-chip timeline-chip-hazard is-${escapeHtml(hazardClass)}">${escapeHtml(hazardLabel)}</span>
              <span class="timeline-live-time" style="margin-left:auto;">${escapeHtml(time)}</span>
            </div>
            <strong class="timeline-live-title">${escapeHtml(item.title)}</strong>
            <span class="timeline-live-meta" style="display:block;">${escapeHtml(item.area)} · ${escapeHtml(item.status)}</span>
            <span class="timeline-live-detail" style="display:block;">${escapeHtml(item.detail)} · Source: ${escapeHtml(item.source)}</span>
            <div class="timeline-live-links" style="display:block;"><a class="inline-link" href="${escapeHtml(linkMonitor)}">Open monitor</a>${mapLinkHtml}${detailLinkHtml}</div>
          </li>
        `;
      }).join('');

      renderKpis(items);
      moreButton.hidden = items.length <= state.visibleCount;
    }

    function render() {
      const filtered = applyFilters(state.allItems);
      renderList(filtered);
      if (state.lastUpdatedTs > 0) {
        updatedNode.textContent = 'Updated ' + new Date(state.lastUpdatedTs).toLocaleTimeString();
      }
    }

    async function refreshTimeline() {
      if (state.loading) {
        return;
      }
      state.loading = true;
      refreshButton.disabled = true;
      try {
        await loadAllFeeds();
        state.visibleCount = 40;
        render();
      } catch (error) {
        metaNode.textContent = 'Timeline refresh failed. Retrying automatically.';
      } finally {
        state.loading = false;
        refreshButton.disabled = false;
      }
    }

    [filterHazard, filterPriority, filterWindow, filterType, filterArea, filterActive].forEach((node) => {
      if (!node) {
        return;
      }
      node.addEventListener('change', () => {
        state.visibleCount = 40;
        render();
      });
    });

    moreButton.addEventListener('click', () => {
      state.visibleCount += 40;
      renderList(state.filteredItems);
    });

    refreshButton.addEventListener('click', () => {
      void refreshTimeline();
    });

    void refreshTimeline();
    window.setInterval(() => {
      if (document.hidden) {
        return;
      }
      void refreshTimeline();
    }, 60000);
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
