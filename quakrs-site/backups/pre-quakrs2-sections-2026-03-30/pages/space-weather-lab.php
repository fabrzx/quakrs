<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Space Weather Lab';
$pageDescription = 'Test page for automated-style space weather advisories generated from threshold rules.';
$currentPage = 'space-weather';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Space Weather Lab</p>
    <h1>Prototipo avvisi automatici</h1>
    <p class="sub">Pagina test: KPI sintetici + regole deterministiche che generano avvisi in stile operativo.</p>
  </div>
</main>

<section class="panel panel-kpi swlab-kpi-grid">
  <article class="card kpi-card">
    <p class="kpi-label">Kp osservato</p>
    <p id="swlab-kpi-kp" class="kpi-value">--</p>
    <p class="kpi-note">Indice geomagnetico corrente</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Classe tempesta</p>
    <p id="swlab-kpi-storm" class="kpi-value">--</p>
    <p class="kpi-note">Classificazione NOAA G-scale</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Solar wind</p>
    <p id="swlab-kpi-wind" class="kpi-value">--</p>
    <p class="kpi-note">Velocita in km/s</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">IMF Bz</p>
    <p id="swlab-kpi-bz" class="kpi-value">--</p>
    <p class="kpi-note">Southward coupling (nT)</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Dst</p>
    <p id="swlab-kpi-dst" class="kpi-value">--</p>
    <p class="kpi-note">Indice storm time</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Aggiornamento</p>
    <p id="swlab-kpi-updated" class="kpi-value">--</p>
    <p class="kpi-note">Timestamp scenario (UTC)</p>
  </article>
</section>

<section class="panel swlab-grid">
  <article class="card swlab-card">
    <div class="feed-head">
      <h3>Scenario input (live)</h3>
      <p class="feed-meta">Dati reali da API space weather</p>
    </div>
    <ul id="swlab-metrics" class="timeline-list swlab-metrics-list">
      <li class="timeline-row">Caricamento scenario...</li>
    </ul>
    <div class="swlab-actions">
      <button id="swlab-reroll" class="btn btn-ghost" type="button">Aggiorna ora</button>
    </div>
  </article>

  <article class="card swlab-card swlab-alerts-card">
    <div class="feed-head">
      <h3>Avvisi generati</h3>
      <p id="swlab-alerts-meta" class="feed-meta">Valutazione in corso...</p>
    </div>
    <div id="swlab-alerts" class="swlab-alerts-list">
      <article class="swlab-alert swlab-alert-info">
        <h4>Inizializzazione</h4>
        <p>Costruzione blocchi avvisi...</p>
      </article>
    </div>
  </article>
</section>

<section class="panel">
  <article class="card page-card">
    <h3>Come usarla</h3>
    <p class="insight-lead">Questa pagina usa feed live e regole soglia deterministiche: input numerici reali, avvisi ordinati per severita e aggiornamento periodico.</p>
  </article>
</section>

<script>
  (() => {
    const metricList = document.querySelector('#swlab-metrics');
    const alertsBox = document.querySelector('#swlab-alerts');
    const alertsMeta = document.querySelector('#swlab-alerts-meta');
    const rerollButton = document.querySelector('#swlab-reroll');

    const kpiKp = document.querySelector('#swlab-kpi-kp');
    const kpiStorm = document.querySelector('#swlab-kpi-storm');
    const kpiWind = document.querySelector('#swlab-kpi-wind');
    const kpiBz = document.querySelector('#swlab-kpi-bz');
    const kpiDst = document.querySelector('#swlab-kpi-dst');
    const kpiUpdated = document.querySelector('#swlab-kpi-updated');

    function formatNumber(value, fractionDigits = 1) {
      return new Intl.NumberFormat(undefined, {
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits,
      }).format(value);
    }

    function toStormScale(kpValue) {
      if (kpValue >= 9) return 'G5';
      if (kpValue >= 8) return 'G4';
      if (kpValue >= 7) return 'G3';
      if (kpValue >= 6) return 'G2';
      if (kpValue >= 5) return 'G1';
      return 'G0';
    }

    function severityRank(level) {
      if (level === 'critical') return 4;
      if (level === 'elevated') return 3;
      if (level === 'watch') return 2;
      return 1;
    }

    function evaluateAlerts(scenario) {
      const alerts = [];

      if (scenario.kp >= 6) {
        alerts.push({
          level: 'critical',
          title: `${scenario.storm} - tempesta geomagnetica moderata`,
          body: `Kp osservato ${formatNumber(scenario.kp)}. Possibili aurore ad alte latitudini nel breve termine.`,
        });
      } else if (scenario.kp >= 5) {
        alerts.push({
          level: 'elevated',
          title: `${scenario.storm} - condizioni geomagnetiche attive`,
          body: `Kp ${formatNumber(scenario.kp)} vicino a soglia operativa. Monitorare variazioni rapide.`,
        });
      }

      if (scenario.wind >= 500 && scenario.density >= 20) {
        alerts.push({
          level: 'elevated',
          title: 'Flusso solare sostenuto',
          body: `Velocita ${scenario.wind} km/s e densita ${formatNumber(scenario.density)} p/cm3 in fascia moderata-alta.`,
        });
      } else if (scenario.wind >= 470 || scenario.density >= 18) {
        alerts.push({
          level: 'watch',
          title: 'Input vento solare in aumento',
          body: `Parametri in crescita (${scenario.wind} km/s, ${formatNumber(scenario.density)} p/cm3).`,
        });
      }

      if (scenario.bz <= -8) {
        alerts.push({
          level: 'critical',
          title: 'Bz fortemente sud',
          body: `Bz a ${formatNumber(scenario.bz)} nT: coupling con magnetosfera potenzialmente efficiente.`,
        });
      } else if (scenario.bz <= -4) {
        alerts.push({
          level: 'watch',
          title: 'Bz orientato a sud',
          body: `Bz ${formatNumber(scenario.bz)} nT, possibile rinforzo della risposta geomagnetica.`,
        });
      }

      if (scenario.dst <= -100) {
        alerts.push({
          level: 'critical',
          title: 'Dst severo',
          body: `Dst ${scenario.dst} nT indica condizioni tempestose marcate.`,
        });
      } else if (scenario.dst <= -60) {
        alerts.push({
          level: 'elevated',
          title: 'Dst in fascia tempesta moderata',
          body: `Dst ${scenario.dst} nT suggerisce disturbo geomagnetico persistente.`,
        });
      }

      if (scenario.coronalHoleFacingEarth) {
        alerts.push({
          level: 'watch',
          title: 'Coronal hole earth-facing',
          body: 'Rilevata configurazione compatibile con rinforzo vento solare nelle prossime ore.',
        });
      }

      if (alerts.length === 0) {
        alerts.push({
          level: 'info',
          title: 'Nessuna anomalia operativa',
          body: 'Parametri attuali sotto soglie di attenzione.',
        });
      }

      return alerts.sort((a, b) => severityRank(b.level) - severityRank(a.level));
    }

    function renderScenario(scenario) {
      const entries = [
        ['Kp index', formatNumber(scenario.kp)],
        ['Storm class', scenario.storm],
        ['Solar wind', `${formatNumber(scenario.wind, 0)} km/s`],
        ['Density', `${formatNumber(scenario.density)} p/cm3`],
        ['IMF Bz', `${formatNumber(scenario.bz)} nT`],
        ['Dst', `${formatNumber(scenario.dst, 0)} nT`],
        ['Coronal hole', scenario.coronalHoleFacingEarth ? 'Yes' : 'No'],
        ['Updated (UTC)', new Date(scenario.updatedAtUtc).toUTCString()],
      ];

      metricList.innerHTML = entries.map(([label, value]) => {
        return `<li class="timeline-row"><span>${label}</span><strong>${value}</strong></li>`;
      }).join('');

      kpiKp.textContent = formatNumber(scenario.kp);
      kpiStorm.textContent = scenario.storm;
      kpiWind.textContent = String(scenario.wind);
      kpiBz.textContent = formatNumber(scenario.bz);
      kpiDst.textContent = String(scenario.dst);
      kpiUpdated.textContent = new Date(scenario.updatedAtUtc).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
      });
    }

    function renderAlerts(alerts) {
      alertsMeta.textContent = `${alerts.length} avvisi generati da regole soglia`; 

      alertsBox.innerHTML = alerts.map((alert) => {
        const className = `swlab-alert swlab-alert-${alert.level}`;
        return `
          <article class="${className}">
            <h4>${alert.title}</h4>
            <p>${alert.body}</p>
          </article>
        `;
      }).join('');
    }

    async function loadLiveScenario() {
      const response = await fetch('/api/space-weather.php', { headers: { Accept: 'application/json' } });
      if (!response.ok) {
        throw new Error('Live feed request failed');
      }
      const payload = await response.json();
      const kp = typeof payload.kp_index_current === 'number' ? payload.kp_index_current : null;
      const wind = typeof payload.solar_wind_speed_current === 'number' ? payload.solar_wind_speed_current : null;
      const density = typeof payload.solar_wind_density_current === 'number' ? payload.solar_wind_density_current : null;
      const bz = typeof payload.imf_bz_current === 'number' ? payload.imf_bz_current : null;
      const dst = typeof payload.dst_current === 'number' ? payload.dst_current : null;

      return {
        kp,
        wind,
        density,
        bz,
        dst,
        storm: typeof payload.storm_level === 'string' ? payload.storm_level : toStormScale(kp ?? 0),
        coronalHoleFacingEarth: false,
        updatedAtUtc: typeof payload.generated_at === 'string' ? payload.generated_at : new Date().toISOString(),
      };
    }

    async function run() {
      rerollButton.disabled = true;
      alertsMeta.textContent = 'Aggiornamento live in corso...';
      try {
        const scenario = await loadLiveScenario();
        renderScenario(scenario);
        const alerts = evaluateAlerts(scenario);
        renderAlerts(alerts);
      } catch (error) {
        alertsMeta.textContent = 'Feed live non disponibile';
        alertsBox.innerHTML = '<article class="swlab-alert swlab-alert-info"><h4>Dati non disponibili</h4><p>Impossibile caricare il feed live in questo momento.</p></article>';
      } finally {
        rerollButton.disabled = false;
      }
    }

    rerollButton?.addEventListener('click', () => {
      void run();
    });

    void run();
    window.setInterval(() => {
      if (document.hidden) return;
      void run();
    }, 60000);
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
