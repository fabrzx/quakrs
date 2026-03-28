<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Alerts';
$pageDescription = 'Active cross-hazard alerts and advisories ranked by operational urgency.';
$currentPage = 'alerts';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.alerts.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.alerts.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.alerts.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Active alerts</p>
    <p id="alerts-kpi-total" class="kpi-value">--</p>
    <p class="kpi-note">Current filtered list</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Warning / Watch</p>
    <p id="alerts-kpi-critical" class="kpi-value">--</p>
    <p class="kpi-note">Highest alert tiers</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">P1 / P2</p>
    <p id="alerts-kpi-priority" class="kpi-value">--</p>
    <p class="kpi-note">Quakrs urgency levels</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Last update</p>
    <p id="alerts-kpi-updated" class="kpi-value">--</p>
    <p id="alerts-kpi-source" class="kpi-note">Loading sources...</p>
  </article>
</section>

<section class="panel">
  <article class="card">
    <div class="feed-head">
      <h3>Alert Filters</h3>
      <p id="alerts-feed-meta" class="feed-meta">Loading active alert surfaces...</p>
    </div>

    <div class="page-grid" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:0.7rem; margin-bottom:0.8rem;">
      <label>
        Level<br />
        <select id="alerts-filter-level">
          <option value="all">All levels</option>
          <option value="warning">Warning</option>
          <option value="watch">Watch</option>
          <option value="advisory">Advisory</option>
          <option value="elevated-attention">Elevated attention</option>
          <option value="information">Information</option>
        </select>
      </label>
      <label>
        Hazard<br />
        <select id="alerts-filter-hazard">
          <option value="all">All hazards</option>
          <option value="tsunami">Tsunami</option>
          <option value="volcanoes">Volcanoes</option>
          <option value="earthquakes">Earthquakes</option>
          <option value="space-weather">Space weather</option>
        </select>
      </label>
      <label>
        Priority<br />
        <select id="alerts-filter-priority">
          <option value="all">All priorities</option>
          <option value="P1">P1</option>
          <option value="P2">P2</option>
          <option value="P3">P3</option>
        </select>
      </label>
      <label>
        Scope<br />
        <select id="alerts-filter-area">
          <option value="all">Global + Italy</option>
          <option value="italy">Italy</option>
          <option value="global">Global only</option>
        </select>
      </label>
    </div>

    <div class="insight-pills" style="margin-bottom:0.8rem;">
      <span id="alerts-live-updated" class="insight-pill">Updated --</span>
      <button id="alerts-live-refresh" class="btn btn-ghost" type="button">Refresh now</button>
    </div>

    <ul id="alerts-live-list" class="events-list live-feed-scroll">
      <li class="event-item">Loading active alerts...</li>
    </ul>

    <button id="alerts-live-more" class="timeline-more" type="button" hidden>Load more</button>
  </article>
</section>

<section class="panel">
  <article class="card page-card">
    <h3>Ranking rules</h3>
    <p class="insight-lead">Ordering is deterministic: taxonomy level first (warning/watch/advisory/elevated-attention/information), then Quakrs priority (P1/P2/P3), then recency and source confidence.</p>
  </article>
</section>

<script>
  (() => {
    const listNode = document.querySelector('#alerts-live-list');
    const metaNode = document.querySelector('#alerts-feed-meta');
    const updatedNode = document.querySelector('#alerts-live-updated');
    const moreButton = document.querySelector('#alerts-live-more');
    const refreshButton = document.querySelector('#alerts-live-refresh');

    const kpiTotal = document.querySelector('#alerts-kpi-total');
    const kpiCritical = document.querySelector('#alerts-kpi-critical');
    const kpiPriority = document.querySelector('#alerts-kpi-priority');
    const kpiUpdated = document.querySelector('#alerts-kpi-updated');
    const kpiSource = document.querySelector('#alerts-kpi-source');

    const filterLevel = document.querySelector('#alerts-filter-level');
    const filterHazard = document.querySelector('#alerts-filter-hazard');
    const filterPriority = document.querySelector('#alerts-filter-priority');
    const filterArea = document.querySelector('#alerts-filter-area');

    const state = {
      allItems: [],
      filteredItems: [],
      visibleCount: 30,
      loading: false,
      lastUpdatedTs: 0,
      sources: [],
    };

    function applyInitialFiltersFromQuery() {
      const params = new URLSearchParams(window.location.search);
      const level = String(params.get('level') || '').trim();
      const hazard = String(params.get('hazard') || '').trim();
      const priority = String(params.get('priority') || '').trim();
      const area = String(params.get('area') || '').trim();

      const applyIfValid = (node, value, allowed) => {
        if (!(node instanceof HTMLSelectElement)) return;
        if (!value || !allowed.includes(value)) return;
        node.value = value;
      };

      applyIfValid(filterLevel, level, ['all', 'warning', 'watch', 'advisory', 'elevated-attention', 'information']);
      applyIfValid(filterHazard, hazard, ['all', 'tsunami', 'volcanoes', 'earthquakes', 'space-weather']);
      applyIfValid(filterPriority, priority, ['all', 'P1', 'P2', 'P3']);
      applyIfValid(filterArea, area, ['all', 'italy', 'global']);
    }

    function esc(value) {
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

    function isItalyArea(area) {
      const label = String(area || '').toLowerCase();
      return label.includes('italy') || label.includes('italia');
    }

    const SOURCE_CONFIDENCE = {
      tsunami: 4,
      earthquakes: 3,
      'space-weather': 3,
      volcanoes: 2,
    };

    function levelScore(level) {
      switch (String(level || 'information')) {
        case 'warning': return 5;
        case 'watch': return 4;
        case 'advisory': return 3;
        case 'elevated-attention': return 2;
        default: return 1;
      }
    }

    function priorityScore(priority) {
      if (priority === 'P1') return 3;
      if (priority === 'P2') return 2;
      return 1;
    }

    function normalizeLevel(rawLevel) {
      const label = String(rawLevel || '').toLowerCase();
      if (label.includes('warning')) return 'warning';
      if (label.includes('watch')) return 'watch';
      if (label.includes('advisory')) return 'advisory';
      if (label.includes('elevated')) return 'elevated-attention';
      if (label.includes('statement')) return 'information';
      return 'information';
    }

    function priorityFromLevel(level) {
      if (level === 'warning') return 'P1';
      if (level === 'watch' || level === 'advisory') return 'P2';
      return 'P3';
    }

    function recencyScore(timeTs) {
      if (!Number.isFinite(timeTs) || timeTs <= 0) return 0;
      const ageMinutes = Math.max(0, (Date.now() - timeTs) / 60000);
      if (ageMinutes <= 30) return 3;
      if (ageMinutes <= 120) return 2;
      if (ageMinutes <= 360) return 1;
      return 0;
    }

    function computeRank(item) {
      const level = levelScore(item.level) * 100;
      const priority = priorityScore(item.priority) * 10;
      const confidence = Number(SOURCE_CONFIDENCE[item.hazard] || 1);
      const recency = recencyScore(item.timeTs);
      return level + priority + confidence + recency;
    }

    function normalizeTsunami(payload) {
      const alerts = Array.isArray(payload?.alerts) ? payload.alerts : [];
      const now = Date.now();
      return alerts.map((alert, index) => {
        const level = normalizeLevel(alert?.warning_level);
        const issuedTs = parseTime(alert?.issued_at_utc || payload?.generated_at);
        const expiresTs = parseTime(alert?.expires_at_utc);
        const active = expiresTs > 0 ? expiresTs > now : true;
        const item = {
          id: 'tsunami:' + String(alert?.id || index),
          hazard: 'tsunami',
          level,
          priority: priorityFromLevel(level),
          status: active ? 'active' : 'ended',
          timeTs: issuedTs,
          timeIso: alert?.issued_at_utc || payload?.generated_at || null,
          headline: alert?.event || 'Tsunami advisory',
          area: alert?.region || 'Unknown region',
          validity: alert?.expires_at_utc ? `valid until ${new Date(alert.expires_at_utc).toLocaleString()}` : 'validity not provided',
          source: payload?.provider || 'Tsunami feed',
          links: {
            monitor: '/tsunami.php',
            map: '/maps.php',
            timeline: '/timeline.php',
            detail: alert?.source_bulletin || '/tsunami.php',
          },
        };
        item.rank = computeRank(item);
        return item;
      }).filter((item) => item.status === 'active');
    }

    function normalizeVolcanoes(payload) {
      const events = Array.isArray(payload?.events) ? payload.events : [];
      return events
        .filter((event) => !!event?.is_new_eruptive)
        .slice(0, 40)
        .map((event, index) => {
          const item = {
          id: 'volc:' + String(event?.id || index),
          hazard: 'volcanoes',
          level: 'elevated-attention',
          priority: 'P2',
          status: 'active',
          timeTs: parseTime(event?.event_time_utc),
          timeIso: event?.event_time_utc || null,
          headline: `${event?.volcano || 'Volcano'} new eruptive activity`,
          area: event?.country || 'Unknown country',
          validity: 'weekly cycle advisory',
          source: payload?.provider || 'Volcano feed',
          links: {
            monitor: '/volcanoes.php',
            map: '/maps.php',
            timeline: '/timeline.php',
            detail: event?.source_url || '/volcanoes.php',
          },
          };
          item.rank = computeRank(item);
          return item;
        });
    }

    function normalizeEarthquakes(payload) {
      const events = Array.isArray(payload?.events) ? payload.events : [];
      return events
        .filter((event) => typeof event?.magnitude === 'number' && event.magnitude >= 6.0)
        .slice(0, 40)
        .map((event, index) => {
          const magnitude = Number(event.magnitude);
          const level = magnitude >= 7.5 ? 'warning' : magnitude >= 6.8 ? 'watch' : 'advisory';
          const priority = magnitude >= 7.5 ? 'P1' : magnitude >= 6.8 ? 'P1' : 'P2';
          const params = new URLSearchParams();
          if (typeof event.id === 'string' && event.id !== '') params.set('id', event.id);
          if (typeof event.latitude === 'number') params.set('lat', event.latitude.toFixed(5));
          if (typeof event.longitude === 'number') params.set('lon', event.longitude.toFixed(5));
          if (typeof event.magnitude === 'number') params.set('mag', event.magnitude.toFixed(2));
          if (typeof event.depth_km === 'number') params.set('depth', event.depth_km.toFixed(2));
          if (event.place) params.set('place', String(event.place));
          if (event.event_time_utc) params.set('time', String(event.event_time_utc));

          const item = {
            id: 'eq:' + String(event?.id || index),
            hazard: 'earthquakes',
            level,
            priority,
            status: 'active',
            timeTs: parseTime(event?.event_time_utc),
            timeIso: event?.event_time_utc || null,
            headline: `M${magnitude.toFixed(1)} strong earthquake`,
            area: event?.place || 'Unknown area',
            validity: 'strong-event watch window',
            source: event?.source_provider || 'Seismic feed',
            links: {
              monitor: '/earthquakes.php',
              map: '/maps.php',
              timeline: '/timeline.php',
              detail: '/event.php?' + params.toString(),
            },
          };
          item.rank = computeRank(item);
          return item;
        });
    }

    function normalizeSpace(payload) {
      const items = [];
      const kp = typeof payload?.kp_index_current === 'number' ? payload.kp_index_current : null;
      const generated = payload?.generated_at || null;
      const generatedTs = parseTime(generated);

      if (typeof kp === 'number' && kp >= 5) {
        const level = kp >= 8 ? 'warning' : kp >= 7 ? 'watch' : 'advisory';
        const priority = kp >= 7 ? 'P1' : 'P2';
        const item = {
          id: 'space:kp',
          hazard: 'space-weather',
          level,
          priority,
          status: 'active',
          timeTs: generatedTs,
          timeIso: generated,
          headline: `Geomagnetic storm potential (Kp ${kp.toFixed(1)})`,
          area: 'Global',
          validity: 'current geomagnetic cycle',
          source: payload?.provider || 'Space weather feed',
          links: {
            monitor: '/space-weather.php',
            map: '/maps.php',
            timeline: '/timeline.php',
            detail: '/space-weather.php',
          },
        };
        item.rank = computeRank(item);
        items.push(item);
      }

      return items;
    }

    async function loadFeeds() {
      const requests = [
        fetch('/api/tsunami.php', { headers: { Accept: 'application/json' }, cache: 'no-store' }),
        fetch('/api/volcanoes.php', { headers: { Accept: 'application/json' }, cache: 'no-store' }),
        fetch('/api/earthquakes.php', { headers: { Accept: 'application/json' }, cache: 'no-store' }),
        fetch('/api/space-weather.php', { headers: { Accept: 'application/json' }, cache: 'no-store' }),
      ];

      const [tsRes, volcRes, eqRes, swRes] = await Promise.allSettled(requests);

      const readJson = async (result) => {
        if (result.status !== 'fulfilled' || !result.value.ok) {
          return null;
        }
        return result.value.json().catch(() => null);
      };

      const [tsPayload, volcPayload, eqPayload, swPayload] = await Promise.all([
        readJson(tsRes),
        readJson(volcRes),
        readJson(eqRes),
        readJson(swRes),
      ]);

      const allItems = [
        ...normalizeTsunami(tsPayload),
        ...normalizeVolcanoes(volcPayload),
        ...normalizeEarthquakes(eqPayload),
        ...normalizeSpace(swPayload),
      ].filter((item) => item.timeTs > 0 && item.status === 'active');

      allItems.sort((a, b) => {
        const rankDelta = Number(b.rank || 0) - Number(a.rank || 0);
        if (rankDelta !== 0) return rankDelta;
        return b.timeTs - a.timeTs;
      });

      state.allItems = allItems;
      state.lastUpdatedTs = Date.now();
      state.sources = [];
      if (tsPayload) state.sources.push('tsunami');
      if (volcPayload) state.sources.push('volcanoes');
      if (eqPayload) state.sources.push('earthquakes');
      if (swPayload) state.sources.push('space');
    }

    function applyFilters(items) {
      const level = String(filterLevel?.value || 'all');
      const hazard = String(filterHazard?.value || 'all');
      const priority = String(filterPriority?.value || 'all');
      const area = String(filterArea?.value || 'all');

      return items.filter((item) => {
        if (level !== 'all' && item.level !== level) return false;
        if (hazard !== 'all' && item.hazard !== hazard) return false;
        if (priority !== 'all' && item.priority !== priority) return false;
        if (area === 'italy' && !isItalyArea(item.area)) return false;
        if (area === 'global' && isItalyArea(item.area)) return false;
        return true;
      });
    }

    function renderKpis(items) {
      const warningsOrWatch = items.filter((item) => item.level === 'warning' || item.level === 'watch').length;
      const p1p2 = items.filter((item) => item.priority === 'P1' || item.priority === 'P2').length;

      kpiTotal.textContent = String(items.length);
      kpiCritical.textContent = String(warningsOrWatch);
      kpiPriority.textContent = String(p1p2);
      kpiUpdated.textContent = state.lastUpdatedTs > 0 ? new Date(state.lastUpdatedTs).toLocaleTimeString() : '--';
      kpiSource.textContent = state.sources.length > 0
        ? `Sources: ${state.sources.join(', ')}`
        : 'Sources unavailable';
    }

    function renderList(items) {
      state.filteredItems = items;
      const visible = items.slice(0, state.visibleCount);

      if (visible.length === 0) {
        listNode.innerHTML = "<li class='event-item'>No active alerts for current filters.</li>";
        moreButton.hidden = true;
        renderKpis([]);
        return;
      }

      listNode.innerHTML = visible.map((item) => {
        const time = item.timeIso ? new Date(item.timeIso).toLocaleString() : '--';
        const rank = Number.isFinite(Number(item.rank)) ? Math.round(Number(item.rank)) : 0;
        const priorityClass = String(item.priority || 'P3').toLowerCase().replace(/[^a-z0-9-]/g, '');
        return `
          <li class="event-item">
            <strong>${esc(item.level.toUpperCase())} · ${esc(item.headline)}</strong><br />
            ${esc(item.hazard)} · <span class="priority-inline is-${esc(priorityClass)}">${esc(item.priority)}</span> · ${esc(item.area)} · ${esc(time)}<br />
            <span>${esc(item.validity)} · Source: ${esc(item.source)} · Rank ${rank}</span><br />
            <a class="inline-link" href="${esc(item.links.monitor)}">Open monitor</a> ·
            <a class="inline-link" href="${esc(item.links.map)}">Open map</a> ·
            <a class="inline-link" href="${esc(item.links.timeline)}">Open timeline</a> ·
            <a class="inline-link" href="${esc(item.links.detail)}">Detail</a>
          </li>
        `;
      }).join('');

      renderKpis(items);
      moreButton.hidden = items.length <= state.visibleCount;
    }

    function render() {
      const filtered = applyFilters(state.allItems);
      renderList(filtered);
      updatedNode.textContent = state.lastUpdatedTs > 0
        ? 'Updated ' + new Date(state.lastUpdatedTs).toLocaleTimeString()
        : 'Updated --';
      metaNode.textContent = state.sources.length > 0
        ? `Active surfaces: ${state.sources.join(', ')}`
        : 'All alert surfaces unavailable right now.';
    }

    async function refreshAlerts() {
      if (state.loading) {
        return;
      }
      state.loading = true;
      refreshButton.disabled = true;
      try {
        await loadFeeds();
        state.visibleCount = 30;
        render();
      } catch (error) {
        metaNode.textContent = 'Alert refresh failed. Retrying automatically.';
      } finally {
        state.loading = false;
        refreshButton.disabled = false;
      }
    }

    [filterLevel, filterHazard, filterPriority, filterArea].forEach((node) => {
      if (!node) return;
      node.addEventListener('change', () => {
        state.visibleCount = 30;
        render();
      });
    });

    moreButton.addEventListener('click', () => {
      state.visibleCount += 30;
      renderList(state.filteredItems);
    });

    refreshButton.addEventListener('click', () => {
      void refreshAlerts();
    });

    void refreshAlerts();
    window.setInterval(() => {
      if (document.hidden) return;
      void refreshAlerts();
    }, 60000);
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
