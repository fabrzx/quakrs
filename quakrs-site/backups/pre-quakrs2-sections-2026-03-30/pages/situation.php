<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Situation Room';
$pageDescription = 'Operational overview with global status, active alerts, and feed health snapshots.';
$currentPage = 'situation';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.situation.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.situation.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.situation.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel panel-kpi situation-kpi-grid">
  <article class="card kpi-card">
    <p class="kpi-label">Global status</p>
    <p id="situation-kpi-global" class="kpi-value">--</p>
    <p class="kpi-note">From live health assessment</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Active alerts</p>
    <p id="situation-kpi-alerts" class="kpi-value">--</p>
    <p class="kpi-note">Cross-hazard active advisories</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Strongest EQ</p>
    <p id="situation-kpi-strongest" class="kpi-value">--</p>
    <p id="situation-kpi-strongest-note" class="kpi-note">Loading strongest event...</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Space Kp</p>
    <p id="situation-kpi-kp" class="kpi-value">--</p>
    <p id="situation-kpi-kp-note" class="kpi-note">Loading geomagnetic status...</p>
  </article>
</section>

<section class="panel situation-summary-panel">
  <article class="card situation-summary-card">
    <div class="feed-head">
      <h3>Situation Summary</h3>
      <p id="situation-summary-meta" class="feed-meta">Loading overview data...</p>
    </div>
    <p id="situation-summary-impact" class="insight-lead">Preparing impact summary...</p>
    <div id="situation-feed-pills" class="insight-pills situation-feed-pills"></div>
    <div class="insight-pills situation-summary-actions">
      <span id="situation-updated-pill" class="insight-pill">Updated --</span>
      <button id="situation-refresh" class="btn btn-ghost" type="button">Refresh now</button>
      <a class="btn btn-ghost" href="/alerts.php">Open Alerts</a>
      <a class="btn btn-ghost" href="/timeline.php">Open Timeline</a>
    </div>
  </article>
</section>

<section class="panel page-grid situation-snapshots-grid">
  <article class="card recent-card situation-snapshot-card">
    <div class="feed-head">
      <h3>Active Alerts Snapshot</h3>
      <p class="feed-meta">Top active advisories</p>
    </div>
    <ul id="situation-alerts-list" class="events-list">
      <li class="event-item">Loading alerts snapshot...</li>
    </ul>
  </article>

  <article class="card recent-card situation-snapshot-card">
    <div class="feed-head">
      <h3>Evolving Signals</h3>
      <p class="feed-meta">Strong earthquakes + eruptive volcanoes + geomagnetic watch</p>
    </div>
    <ul id="situation-signals-list" class="events-list">
      <li class="event-item">Loading evolving signals...</li>
    </ul>
  </article>
</section>

<section class="panel situation-components-panel">
  <article class="card recent-card situation-components-card">
    <div class="feed-head">
      <h3>Component Health Snapshot</h3>
      <p class="feed-meta">Operational backend components</p>
    </div>
    <ul id="situation-components-list" class="events-list situation-components-list">
      <li class="event-item">Loading component health...</li>
    </ul>
  </article>
</section>

<script>
  (() => {
    const kpiGlobal = document.querySelector('#situation-kpi-global');
    const kpiAlerts = document.querySelector('#situation-kpi-alerts');
    const kpiStrongest = document.querySelector('#situation-kpi-strongest');
    const kpiStrongestNote = document.querySelector('#situation-kpi-strongest-note');
    const kpiKp = document.querySelector('#situation-kpi-kp');
    const kpiKpNote = document.querySelector('#situation-kpi-kp-note');

    const summaryMeta = document.querySelector('#situation-summary-meta');
    const summaryImpact = document.querySelector('#situation-summary-impact');
    const feedPills = document.querySelector('#situation-feed-pills');
    const updatedPill = document.querySelector('#situation-updated-pill');
    const refreshButton = document.querySelector('#situation-refresh');

    const alertsList = document.querySelector('#situation-alerts-list');
    const signalsList = document.querySelector('#situation-signals-list');
    const componentsList = document.querySelector('#situation-components-list');

    const state = {
      loading: false,
      lastUpdatedTs: 0,
    };

    function esc(value) {
      return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    function parseTime(value) {
      if (!value) return 0;
      const ts = new Date(value).getTime();
      return Number.isFinite(ts) ? ts : 0;
    }

    function levelWeight(level) {
      const text = String(level || '').toLowerCase();
      if (text.includes('warning')) return 4;
      if (text.includes('watch')) return 3;
      if (text.includes('advisory')) return 2;
      return 1;
    }

    function statusTone(status) {
      const text = String(status || '').toLowerCase();
      if (text.includes('degraded') || text.includes('down') || text.includes('outdated')) return 'degraded';
      if (text.includes('lagging') || text.includes('warning')) return 'lagging';
      if (text.includes('up') || text.includes('healthy') || text.includes('ok')) return 'healthy';
      return 'unknown';
    }

    async function fetchJson(path) {
      const response = await fetch(path, { headers: { Accept: 'application/json' }, cache: 'no-store' });
      if (!response.ok) {
        throw new Error(path + ' request failed');
      }
      return response.json();
    }

    function renderAlertsSnapshot(tsunamiPayload) {
      const alerts = Array.isArray(tsunamiPayload?.alerts) ? tsunamiPayload.alerts.slice(0, 5) : [];
      if (alerts.length === 0) {
        alertsList.innerHTML = "<li class='event-item'>No active tsunami alerts in current feed.</li>";
        return 0;
      }

      const sorted = [...alerts].sort((a, b) => levelWeight(b?.warning_level) - levelWeight(a?.warning_level));
      alertsList.innerHTML = sorted.map((alert) => {
        const level = alert?.warning_level || 'Statement';
        const eventName = alert?.event || 'Tsunami advisory';
        const area = alert?.region || 'Unknown region';
        const link = alert?.source_bulletin || '/tsunami.php';
        return `
          <li class="event-item">
            <strong>${esc(level)} · ${esc(eventName)}</strong><br />
            ${esc(area)}<br />
            <a class="inline-link" href="${esc(link)}">Source bulletin</a>
          </li>
        `;
      }).join('');

      return alerts.length;
    }

    function renderSignals(eqPayload, volcPayload, spacePayload) {
      const signals = [];

      const eqEvents = Array.isArray(eqPayload?.events) ? eqPayload.events : [];
      const strongestEq = [...eqEvents]
        .filter((event) => typeof event?.magnitude === 'number')
        .sort((a, b) => b.magnitude - a.magnitude)[0] || null;

      if (strongestEq) {
        signals.push({
          title: `EQ M${Number(strongestEq.magnitude).toFixed(1)}`,
          body: strongestEq.place || 'Unknown location',
          link: '/earthquakes.php',
        });
        kpiStrongest.textContent = `M${Number(strongestEq.magnitude).toFixed(1)}`;
        kpiStrongestNote.textContent = strongestEq.place || 'Unknown location';
      } else {
        kpiStrongest.textContent = '--';
        kpiStrongestNote.textContent = 'No earthquake signal available';
      }

      const volcEvents = Array.isArray(volcPayload?.events) ? volcPayload.events : [];
      const eruptive = volcEvents.filter((event) => !!event?.is_new_eruptive).slice(0, 2);
      eruptive.forEach((event) => {
        signals.push({
          title: `VOLC ${event?.volcano || 'Volcano'}`,
          body: 'New eruptive activity',
          link: event?.source_url || '/volcanoes.php',
        });
      });

      const kp = typeof spacePayload?.kp_index_current === 'number' ? spacePayload.kp_index_current : null;
      if (kp !== null) {
        kpiKp.textContent = kp.toFixed(1);
        kpiKpNote.textContent = spacePayload?.storm_level || spacePayload?.kp_band_current || 'Space weather status';
        if (kp >= 5) {
          signals.push({
            title: `SPACE Kp ${kp.toFixed(1)}`,
            body: 'Geomagnetic storming conditions',
            link: '/space-weather.php',
          });
        }
      } else {
        kpiKp.textContent = '--';
        kpiKpNote.textContent = 'No geomagnetic signal available';
      }

      if (signals.length === 0) {
        signalsList.innerHTML = "<li class='event-item'>No strong evolving signal detected in current snapshot.</li>";
      } else {
        signalsList.innerHTML = signals.slice(0, 6).map((signal) => `
          <li class="event-item">
            <strong>${esc(signal.title)}</strong><br />
            ${esc(signal.body)}<br />
            <a class="inline-link" href="${esc(signal.link)}">Open source</a>
          </li>
        `).join('');
      }
    }

    function renderComponents(healthPayload) {
      const components = Array.isArray(healthPayload?.components) ? healthPayload.components : [];
      if (components.length === 0) {
        componentsList.innerHTML = "<li class='event-item'>No component data available.</li>";
        return;
      }
      const sorted = [...components].sort((a, b) => {
        const weight = (value) => {
          const tone = statusTone(value);
          if (tone === 'degraded') return 3;
          if (tone === 'lagging') return 2;
          if (tone === 'unknown') return 1;
          return 0;
        };
        return weight(b?.status) - weight(a?.status);
      });

      componentsList.innerHTML = sorted.map((component) => {
        const key = component?.key || 'component';
        const status = component?.status || 'unknown';
        const impact = component?.impact || 'none';
        const note = component?.note || '';
        const tone = statusTone(status);
        return `
          <li class="event-item situation-component-item">
            <div class="situation-component-head">
              <strong>${esc(key)}</strong>
              <span class="situation-state-pill is-${esc(tone)}">${esc(status)}</span>
            </div>
            <span>impact ${esc(impact)}${note ? ` · ${esc(note)}` : ''}</span>
          </li>
        `;
      }).join('');
    }

    async function refreshSituation() {
      if (state.loading) return;
      state.loading = true;
      refreshButton.disabled = true;
      try {
        const [eqPayload, tsunamiPayload, volcPayload, spacePayload, healthPayload] = await Promise.all([
          fetchJson('/api/earthquakes.php'),
          fetchJson('/api/tsunami.php'),
          fetchJson('/api/volcanoes.php'),
          fetchJson('/api/space-weather.php'),
          fetchJson('/api/health.php'),
        ]);

        const alertCount = renderAlertsSnapshot(tsunamiPayload);
        renderSignals(eqPayload, volcPayload, spacePayload);
        renderComponents(healthPayload);

        const overallStatus = String(healthPayload?.overall_status || 'unknown');
        const overallTone = statusTone(overallStatus);
        kpiGlobal.textContent = overallStatus.toUpperCase();
        kpiGlobal.classList.remove('is-healthy', 'is-lagging', 'is-degraded', 'is-unknown');
        kpiGlobal.classList.add(`is-${overallTone}`);
        kpiAlerts.textContent = String(alertCount);

        const degradedComponents = Number(healthPayload?.degraded_components || 0);
        const feeds = healthPayload?.counts || {};
        summaryImpact.textContent = String(healthPayload?.user_impact || 'No impact summary available.');
        summaryMeta.textContent = `Feeds healthy ${Number(feeds.healthy || 0)} · lagging ${Number(feeds.lagging || 0)} · outdated ${Number(feeds.outdated || 0)} · degraded components ${degradedComponents}`;
        if (feedPills) {
          const healthy = Number(feeds.healthy || 0);
          const lagging = Number(feeds.lagging || 0);
          const outdated = Number(feeds.outdated || 0);
          feedPills.innerHTML = [
            `<span class="insight-pill situation-pill is-healthy">Healthy ${healthy}</span>`,
            `<span class="insight-pill situation-pill is-lagging">Lagging ${lagging}</span>`,
            `<span class="insight-pill situation-pill is-degraded">Outdated ${outdated}</span>`,
            `<span class="insight-pill situation-pill is-unknown">Components degraded ${degradedComponents}</span>`,
          ].join('');
        }

        state.lastUpdatedTs = Date.now();
        updatedPill.textContent = 'Updated ' + new Date(state.lastUpdatedTs).toLocaleTimeString();
      } catch (error) {
        summaryMeta.textContent = 'Situation data unavailable right now.';
        summaryImpact.textContent = 'Unable to load full operational picture. Retry in a few moments.';
        alertsList.innerHTML = "<li class='event-item'>Unable to load alert snapshot.</li>";
        signalsList.innerHTML = "<li class='event-item'>Unable to load evolving signals.</li>";
        componentsList.innerHTML = "<li class='event-item'>Unable to load component health.</li>";
      } finally {
        state.loading = false;
        refreshButton.disabled = false;
      }
    }

    refreshButton.addEventListener('click', () => {
      void refreshSituation();
    });

    void refreshSituation();
    window.setInterval(() => {
      if (document.hidden) return;
      void refreshSituation();
    }, 60000);
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
