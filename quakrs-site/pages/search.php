<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Search';
$pageDescription = 'Global operational search across events, alerts, and core Quakrs pages.';
$currentPage = 'search';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.search.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.search.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.search.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel">
  <article class="card">
    <div class="feed-head">
      <h3>Search Controls</h3>
      <p id="search-meta" class="feed-meta">Loading operational index...</p>
    </div>

    <div class="page-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:0.7rem; margin-bottom:0.8rem;">
      <label>
        Query<br />
        <input id="search-query" type="search" placeholder="Try: Italy, tsunami, M6, Etna, Kp" />
      </label>
      <label>
        Category<br />
        <select id="search-category">
          <option value="all">All categories</option>
          <option value="events">Events</option>
          <option value="alerts">Alerts</option>
          <option value="pages">Pages</option>
          <option value="resources">Resources</option>
        </select>
      </label>
      <label>
        Hazard
        <br />
        <select id="search-hazard">
          <option value="all">All hazards</option>
          <option value="earthquakes">Earthquakes</option>
          <option value="volcanoes">Volcanoes</option>
          <option value="tsunami">Tsunami</option>
          <option value="space-weather">Space weather</option>
        </select>
      </label>
      <label>
        Scope
        <br />
        <select id="search-scope">
          <option value="all">Global + Italy</option>
          <option value="italy">Italy</option>
          <option value="global">Global only</option>
        </select>
      </label>
    </div>

    <div class="insight-pills" style="margin-bottom:0.8rem;">
      <span id="search-updated" class="insight-pill">Updated --</span>
      <span id="search-count" class="insight-pill">Results --</span>
      <button id="search-refresh" class="btn btn-ghost" type="button">Refresh index</button>
    </div>

    <ul id="search-results" class="events-list live-feed-scroll">
      <li class="event-item">Type a query to start searching...</li>
    </ul>

    <button id="search-more" class="timeline-more" type="button" hidden>Load more</button>
  </article>
</section>

<script>
  (() => {
    const queryNode = document.querySelector('#search-query');
    const categoryNode = document.querySelector('#search-category');
    const hazardNode = document.querySelector('#search-hazard');
    const scopeNode = document.querySelector('#search-scope');
    const refreshButton = document.querySelector('#search-refresh');

    const metaNode = document.querySelector('#search-meta');
    const updatedNode = document.querySelector('#search-updated');
    const countNode = document.querySelector('#search-count');
    const resultsNode = document.querySelector('#search-results');
    const moreButton = document.querySelector('#search-more');

    const state = {
      index: [],
      filtered: [],
      visibleCount: 30,
      loading: false,
      updatedAt: 0,
      sources: [],
    };

    function esc(value) {
      return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    function norm(value) {
      return String(value || '').toLowerCase().trim();
    }

    function parseTime(value) {
      if (!value) return 0;
      const ts = new Date(value).getTime();
      return Number.isFinite(ts) ? ts : 0;
    }

    function isItaly(text) {
      const value = norm(text);
      return value.includes('italy') || value.includes('italia');
    }

    function pageIndexItems() {
      return [
        { id: 'page:timeline', category: 'pages', hazard: 'all', title: 'Timeline live', subtitle: 'Chronological cross-hazard stream', url: '/timeline.php', area: 'global', score: 10 },
        { id: 'page:alerts', category: 'alerts', hazard: 'all', title: 'Alerts', subtitle: 'Active advisories and warnings', url: '/alerts.php', area: 'global', score: 10 },
        { id: 'page:situation', category: 'pages', hazard: 'all', title: 'Situation room', subtitle: 'Compact operational command view', url: '/situation.php', area: 'global', score: 10 },
        { id: 'page:myquakrs', category: 'pages', hazard: 'all', title: 'My Quakrs', subtitle: 'Personal operational preferences', url: '/my-quakrs.php', area: 'global', score: 8 },
        { id: 'page:earthquakes', category: 'events', hazard: 'earthquakes', title: 'Earthquakes monitor', subtitle: 'Live global earthquake feed', url: '/earthquakes.php', area: 'global', score: 8 },
        { id: 'page:volcanoes', category: 'events', hazard: 'volcanoes', title: 'Volcanoes monitor', subtitle: 'Volcanic operations console', url: '/volcanoes.php', area: 'global', score: 8 },
        { id: 'page:tsunami', category: 'alerts', hazard: 'tsunami', title: 'Tsunami monitor', subtitle: 'Active tsunami bulletins', url: '/tsunami.php', area: 'global', score: 8 },
        { id: 'page:space', category: 'events', hazard: 'space-weather', title: 'Space weather monitor', subtitle: 'Geomagnetic and solar status', url: '/space-weather.php', area: 'global', score: 8 },
        { id: 'page:italia', category: 'events', hazard: 'earthquakes', title: 'Italia data', subtitle: 'Dedicated Italy seismic monitor', url: '/data-italia.php', area: 'italy', score: 9 },
        { id: 'page:archive', category: 'resources', hazard: 'all', title: 'Federated archive', subtitle: 'Multi-hazard archive entry and routing', url: '/archive.php', area: 'global', score: 7 },
        { id: 'page:priority', category: 'resources', hazard: 'all', title: 'Priority levels', subtitle: 'P1/P2/P3 operational logic', url: '/priority-levels.php', area: 'global', score: 7 },
        { id: 'page:status', category: 'resources', hazard: 'all', title: 'Data status', subtitle: 'Feed and component health', url: '/data-status.php', area: 'global', score: 7 },
        { id: 'page:updates', category: 'pages', hazard: 'all', title: 'Updates', subtitle: 'Product architecture and release log', url: '/updates.php', area: 'global', score: 6 },
      ];
    }

    function eventUrl(event) {
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

    async function fetchJson(path) {
      const response = await fetch(path, { headers: { Accept: 'application/json' }, cache: 'no-store' });
      if (!response.ok) throw new Error(path + ' request failed');
      return response.json();
    }

    async function buildIndex() {
      const [eqPayload, volcPayload, tsunamiPayload, spacePayload] = await Promise.all([
        fetchJson('/api/earthquakes.php'),
        fetchJson('/api/volcanoes.php'),
        fetchJson('/api/tsunami.php'),
        fetchJson('/api/space-weather.php'),
      ]);

      const items = [];

      const eqEvents = Array.isArray(eqPayload?.events) ? eqPayload.events : [];
      eqEvents.slice(0, 120).forEach((event, index) => {
        const magnitude = typeof event?.magnitude === 'number' ? event.magnitude : null;
        items.push({
          id: 'eq:' + String(event?.id || index),
          category: 'events',
          hazard: 'earthquakes',
          title: `${magnitude !== null ? 'M' + magnitude.toFixed(1) : 'M?'} ${event?.place || 'Unknown location'}`,
          subtitle: `Depth ${typeof event?.depth_km === 'number' ? event.depth_km.toFixed(1) + ' km' : 'n/a'} · ${event?.source_provider || 'Seismic feed'}`,
          url: eventUrl(event),
          area: isItaly(event?.place) ? 'italy' : 'global',
          ts: parseTime(event?.event_time_utc),
          score: magnitude !== null ? magnitude : 0,
        });
      });

      const volcEvents = Array.isArray(volcPayload?.events) ? volcPayload.events : [];
      volcEvents.slice(0, 80).forEach((event, index) => {
        const eruptive = !!event?.is_new_eruptive;
        items.push({
          id: 'volc:' + String(event?.id || index),
          category: 'events',
          hazard: 'volcanoes',
          title: `${event?.volcano || 'Volcano'}${eruptive ? ' (new eruptive)' : ''}`,
          subtitle: `${event?.country || 'Unknown country'} · ${eruptive ? 'elevated attention' : 'activity update'}`,
          url: event?.source_url || '/volcanoes.php',
          area: isItaly(event?.country) ? 'italy' : 'global',
          ts: parseTime(event?.event_time_utc),
          score: eruptive ? 7 : 4,
        });
      });

      const alerts = Array.isArray(tsunamiPayload?.alerts) ? tsunamiPayload.alerts : [];
      alerts.slice(0, 60).forEach((alert, index) => {
        const level = String(alert?.warning_level || 'Statement');
        const levelScore = level.toLowerCase().includes('warning') ? 9
          : level.toLowerCase().includes('watch') ? 7
          : level.toLowerCase().includes('advisory') ? 5
          : 3;
        items.push({
          id: 'tsunami:' + String(alert?.id || index),
          category: 'alerts',
          hazard: 'tsunami',
          title: `${level} · ${alert?.event || 'Tsunami advisory'}`,
          subtitle: `${alert?.region || 'Unknown region'} · ${alert?.severity || 'Unknown severity'}`,
          url: alert?.source_bulletin || '/tsunami.php',
          area: isItaly(alert?.region) ? 'italy' : 'global',
          ts: parseTime(alert?.issued_at_utc || tsunamiPayload?.generated_at),
          score: levelScore,
        });
      });

      const kp = typeof spacePayload?.kp_index_current === 'number' ? spacePayload.kp_index_current : null;
      if (kp !== null) {
        items.push({
          id: 'space:kp',
          category: 'events',
          hazard: 'space-weather',
          title: `Geomagnetic Kp ${kp.toFixed(1)}`,
          subtitle: spacePayload?.storm_level || spacePayload?.kp_band_current || 'Space weather status',
          url: '/space-weather.php',
          area: 'global',
          ts: parseTime(spacePayload?.generated_at),
          score: kp >= 7 ? 9 : kp >= 5 ? 7 : 4,
        });
      }

      const flares = Array.isArray(spacePayload?.flare_events) ? spacePayload.flare_events : [];
      flares.slice(0, 30).forEach((flare, index) => {
        const classLabel = String(flare?.class || 'flare');
        const classScore = classLabel.startsWith('X') ? 9 : classLabel.startsWith('M') ? 7 : 4;
        items.push({
          id: 'space:flare:' + index,
          category: 'events',
          hazard: 'space-weather',
          title: `Solar flare ${classLabel}`,
          subtitle: 'Detected in X-ray flux timeline',
          url: '/space-weather.php',
          area: 'global',
          ts: parseTime(flare?.time_utc),
          score: classScore,
        });
      });

      items.push(...pageIndexItems());
      state.index = items;
      state.sources = ['earthquakes', 'volcanoes', 'tsunami', 'space', 'pages'];
      state.updatedAt = Date.now();
    }

    function computeMatchScore(item, query) {
      if (!query) return item.score || 0;
      const hay = `${item.title} ${item.subtitle} ${item.hazard} ${item.category}`.toLowerCase();
      const tokens = query.split(/\s+/).filter(Boolean);
      let score = item.score || 0;
      for (const token of tokens) {
        if (hay.includes(token)) {
          score += 5;
        }
      }
      if (norm(item.title).includes(query)) score += 8;
      return score;
    }

    function applyFilters() {
      const query = norm(queryNode?.value || '');
      const category = String(categoryNode?.value || 'all');
      const hazard = String(hazardNode?.value || 'all');
      const scope = String(scopeNode?.value || 'all');

      const filtered = state.index
        .filter((item) => {
          if (category !== 'all' && item.category !== category) return false;
          if (hazard !== 'all' && item.hazard !== hazard) return false;
          if (scope === 'italy' && item.area !== 'italy') return false;
          if (scope === 'global' && item.area === 'italy') return false;
          if (!query) return true;
          const hay = `${item.title} ${item.subtitle} ${item.hazard} ${item.category}`.toLowerCase();
          return query.split(/\s+/).filter(Boolean).every((token) => hay.includes(token));
        })
        .map((item) => ({ ...item, _score: computeMatchScore(item, query) }))
        .sort((a, b) => {
          if (b._score !== a._score) return b._score - a._score;
          return (b.ts || 0) - (a.ts || 0);
        });

      state.filtered = filtered;
      renderResults();
    }

    function renderResults() {
      const visible = state.filtered.slice(0, state.visibleCount);

      if (visible.length === 0) {
        resultsNode.innerHTML = "<li class='event-item'>No results. Try a broader query.</li>";
        moreButton.hidden = true;
      } else {
        resultsNode.innerHTML = visible.map((item) => {
          const time = item.ts ? new Date(item.ts).toLocaleString() : '--';
          return `
            <li class="event-item">
              <strong>${esc(item.title)}</strong><br />
              ${esc(item.category)} · ${esc(item.hazard)} · ${esc(item.area)} · ${esc(time)}<br />
              <span>${esc(item.subtitle || '')}</span><br />
              <a class="inline-link" href="${esc(item.url)}">Open result</a>
            </li>
          `;
        }).join('');
        moreButton.hidden = state.filtered.length <= state.visibleCount;
      }

      countNode.textContent = `Results ${state.filtered.length}`;
      updatedNode.textContent = state.updatedAt > 0
        ? 'Updated ' + new Date(state.updatedAt).toLocaleTimeString()
        : 'Updated --';
      metaNode.textContent = state.sources.length > 0
        ? `Index sources: ${state.sources.join(', ')}`
        : 'Index sources unavailable';
    }

    async function refreshIndex() {
      if (state.loading) return;
      state.loading = true;
      refreshButton.disabled = true;
      try {
        await buildIndex();
        state.visibleCount = 30;
        applyFilters();
      } catch (error) {
        metaNode.textContent = 'Search index unavailable right now.';
        resultsNode.innerHTML = "<li class='event-item'>Unable to build search index at the moment.</li>";
      } finally {
        state.loading = false;
        refreshButton.disabled = false;
      }
    }

    const initialQueryFromUrl = (() => {
      const params = new URLSearchParams(window.location.search);
      return String(params.get('q') || '').trim();
    })();
    if (initialQueryFromUrl !== '') {
      queryNode.value = initialQueryFromUrl;
    }

    let queryTimer = null;
    queryNode.addEventListener('input', () => {
      if (queryTimer) window.clearTimeout(queryTimer);
      queryTimer = window.setTimeout(() => {
        state.visibleCount = 30;
        applyFilters();
      }, 120);
    });

    [categoryNode, hazardNode, scopeNode].forEach((node) => {
      node.addEventListener('change', () => {
        state.visibleCount = 30;
        applyFilters();
      });
    });

    moreButton.addEventListener('click', () => {
      state.visibleCount += 30;
      renderResults();
    });

    refreshButton.addEventListener('click', () => {
      void refreshIndex();
    });

    void refreshIndex();
    window.setInterval(() => {
      if (document.hidden) return;
      void refreshIndex();
    }, 60000);
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
