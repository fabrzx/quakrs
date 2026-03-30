<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Space Weather';
$pageDescription = 'Solar imagery, flare activity, geomagnetic and solar-wind monitor.';
$currentPage = 'space-weather';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.space_weather.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.space_weather.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.space_weather.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel panel-kpi space-kpi-grid">
  <article class="card kpi-card">
    <p class="kpi-label">Kp Current</p>
    <p id="space-kpi-current" class="kpi-value">--</p>
    <p class="kpi-note">Latest observed Kp index</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Storm Level</p>
    <p id="space-kpi-level" class="kpi-value">--</p>
    <p class="kpi-note">Geomagnetic class</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">IMF Bz</p>
    <p id="space-kpi-bz" class="kpi-value">--</p>
    <p id="space-kpi-source" class="kpi-note">Loading source...</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Solar Wind</p>
    <p id="space-kpi-wind" class="kpi-value">--</p>
    <p class="kpi-note">km/s current speed</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Dst</p>
    <p id="space-kpi-forecast" class="kpi-value">--</p>
    <p class="kpi-note">Disturbance storm time (nT)</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Flare Peak (24h)</p>
    <p id="space-kpi-flare" class="kpi-value">--</p>
    <p class="kpi-note">GOES X-ray class</p>
  </article>
</section>

<section class="panel">
  <article class="card space-alerts-card">
    <div class="feed-head">
      <h3>Avvisi</h3>
      <p id="space-alerts-meta" class="feed-meta">Valutazione in corso...</p>
    </div>
    <div id="space-alerts-grid" class="space-alerts-grid">
      <article class="space-alert-item space-alert-info">
        <h4>Inizializzazione</h4>
        <p>Costruzione avvisi operativi...</p>
      </article>
    </div>
  </article>
</section>

<section class="panel space-weather-grid">
  <article class="card space-solar-card">
    <div class="feed-head">
      <h3>Solar Disk (Live)</h3>
      <p id="space-cache-status" class="feed-meta">Syncing...</p>
    </div>
    <div class="space-solar-body">
      <div class="space-sun-wrap space-sun-clickable" id="space-sun-wrap" role="button" tabindex="0" aria-label="Open solar image zoom">
        <img id="space-sun-image" class="space-sun-image" src="" alt="Latest solar disk image" loading="eager" fetchpriority="high" decoding="async" />
        <div id="space-sun-fallback" class="space-sun-fallback" aria-hidden="true"></div>
      </div>
      <ul class="space-solar-metrics">
        <li>
          <span>Current band</span>
          <strong id="space-current-band">--</strong>
        </li>
        <li>
          <span>Last observation</span>
          <strong id="space-last-observation">--</strong>
        </li>
        <li>
          <span>Generated</span>
          <strong id="space-generated-at">--</strong>
        </li>
      </ul>
    </div>
    <p id="space-solar-summary" class="snapshot-brief">Loading solar summary...</p>
    <p id="space-source-line" class="sources-line">Source loading...</p>
  </article>

  <article class="card space-kp-card">
    <div class="space-kp-chart-wrap space-kp-chart-wrap-kp">
      <div class="space-chart-head-inline">
        <h3>Kp Timeline</h3>
        <p class="feed-meta">Observed + forecast</p>
      </div>
      <svg id="space-kp-chart" class="space-kp-chart" viewBox="0 0 560 220" role="img" aria-label="Kp trend chart"></svg>
      <div class="space-chart-legend space-chart-legend-inline">
        <span><i class="space-dot-live"></i>Observed Kp</span>
        <span><i class="space-dot-forecast"></i>Forecast Kp</span>
      </div>
    </div>
  </article>
</section>

<section class="panel panel-charts space-extra-charts space-chart-row-primary">
  <article class="card">
    <div class="feed-head">
      <h3>X-ray Flux / Flare Channel</h3>
      <p class="feed-meta">GOES 24h</p>
    </div>
    <div class="space-kp-chart-wrap">
      <svg id="space-xray-chart" class="space-kp-chart" viewBox="0 0 560 220" role="img" aria-label="X-ray flux chart"></svg>
    </div>
    <div class="space-scale-row">
      <span class="flare-tag flare-a">A/B</span>
      <span class="flare-tag flare-c">C</span>
      <span class="flare-tag flare-m">M</span>
      <span class="flare-tag flare-x">X</span>
    </div>
  </article>

  <article class="card">
    <div class="feed-head">
      <h3>Solar Wind Speed</h3>
      <p class="feed-meta">km/s last 24h</p>
    </div>
    <div class="space-kp-chart-wrap">
      <svg id="space-wind-chart" class="space-kp-chart" viewBox="0 0 560 220" role="img" aria-label="Solar wind speed chart"></svg>
    </div>
  </article>

  <article class="card">
    <div class="feed-head">
      <h3>IMF Bz (nT)</h3>
      <p class="feed-meta">Southward turns highlighted</p>
    </div>
    <div class="space-kp-chart-wrap">
      <svg id="space-bz-chart" class="space-kp-chart" viewBox="0 0 560 220" role="img" aria-label="IMF Bz chart"></svg>
    </div>
  </article>
</section>

<section class="panel panel-charts space-extra-charts">
  <article class="card">
    <div class="feed-head">
      <h3>Solar Wind Density</h3>
      <p class="feed-meta">p/cm3 last 24h</p>
    </div>
    <div class="space-kp-chart-wrap">
      <svg id="space-density-chart" class="space-kp-chart" viewBox="0 0 560 220" role="img" aria-label="Solar wind density chart"></svg>
    </div>
  </article>

  <article class="card">
    <div class="feed-head">
      <h3>Interplanetary Magnetic Field Bt</h3>
      <p class="feed-meta">nT last 24h</p>
    </div>
    <div class="space-kp-chart-wrap">
      <svg id="space-bt-chart" class="space-kp-chart" viewBox="0 0 560 220" role="img" aria-label="Interplanetary magnetic field Bt chart"></svg>
    </div>
  </article>

  <article class="card">
    <div class="feed-head">
      <h3>Dst Index</h3>
      <p class="feed-meta">Disturbance storm time</p>
    </div>
    <div class="space-kp-chart-wrap">
      <svg id="space-dst-chart" class="space-kp-chart" viewBox="0 0 560 220" role="img" aria-label="Dst index chart"></svg>
    </div>
  </article>
</section>

<section class="panel panel-charts space-triple-panel">
  <article class="card">
    <div class="feed-head">
      <h3>Magnetometers (GOES)</h3>
      <p class="feed-meta">Hp / He components</p>
    </div>
    <div class="space-kp-chart-wrap">
      <svg id="space-mag-chart" class="space-kp-chart" viewBox="0 0 560 220" role="img" aria-label="GOES magnetometers chart"></svg>
    </div>
    <div class="space-chart-legend">
      <span><i class="space-dot-mag-hp"></i>Hp</span>
      <span><i class="space-dot-mag-he"></i>He</span>
    </div>
  </article>

  <article class="card side-card space-aurora-card">
    <div class="feed-head">
      <h3>Auroral Oval</h3>
      <p class="feed-meta">Latest OVATION map</p>
    </div>
    <div class="space-aurora-wrap">
      <img id="space-aurora-image" class="space-aurora-image" src="" alt="Auroral oval map" loading="lazy" />
    </div>
  </article>

  <article class="card space-moon-card">
    <div class="feed-head">
      <h3>Moon Phase</h3>
      <p id="space-moon-name" class="feed-meta">Loading moon phase...</p>
    </div>
    <div class="space-moon-block">
      <div id="space-moon-visual" class="space-moon-visual" aria-hidden="true">
        <canvas id="space-moon-canvas" class="space-moon-canvas" width="320" height="320" aria-hidden="true"></canvas>
      </div>
      <p id="space-moon-meta" class="kpi-note">Illumination --</p>
      <div class="space-moon-meter" role="img" aria-label="Moon illumination">
        <span id="space-moon-meter-fill"></span>
      </div>
    </div>
  </article>
</section>

<section class="panel panel-main space-main-panel">
  <article class="card recent-card">
    <div class="feed-head">
      <h3>Recent Flare Peaks</h3>
      <p class="feed-meta">Auto-detected local maxima</p>
    </div>
    <ul id="space-flare-list" class="timeline-list">
      <li class="timeline-row">Loading flare events...</li>
    </ul>
    <div class="feed-head space-distro-head">
      <h3>Recent Kp Readings</h3>
      <p class="feed-meta">Latest observations</p>
    </div>
    <ul id="space-readings-list" class="timeline-list">
      <li class="timeline-row">Loading Kp readings...</li>
    </ul>
  </article>

  <article class="card side-card space-main-side-card">
    <h3>Forecast Steps</h3>
    <ul id="space-forecast-list" class="timeline-list">
      <li class="timeline-row">Loading forecast rows...</li>
    </ul>
    <div class="feed-head space-distro-head">
      <h3>Kp Distribution (24h)</h3>
    </div>
    <div id="space-band-bars" class="bars"></div>
    <p id="space-trend-note" class="kpi-note">Trend analysis pending...</p>
  </article>
</section>

<div id="space-sun-modal" class="space-sun-modal" hidden aria-hidden="true">
  <div class="space-sun-modal-backdrop" data-close-sun-modal="1"></div>
  <div class="space-sun-modal-dialog" role="dialog" aria-modal="true" aria-label="Solar image zoom">
    <button id="space-sun-modal-close" class="space-sun-modal-close" type="button" aria-label="Close zoom">Close</button>
    <img id="space-sun-modal-image" class="space-sun-modal-image" src="" alt="Solar image zoomed" />
  </div>
</div>

<script>
  (() => {
    const chartKp = document.querySelector("#space-kp-chart");
    const chartXray = document.querySelector("#space-xray-chart");
    const chartWind = document.querySelector("#space-wind-chart");
    const chartBz = document.querySelector("#space-bz-chart");
    const chartDensity = document.querySelector("#space-density-chart");
    const chartBt = document.querySelector("#space-bt-chart");
    const chartDst = document.querySelector("#space-dst-chart");
    const chartMag = document.querySelector("#space-mag-chart");

    const listReadings = document.querySelector("#space-readings-list");
    const listForecast = document.querySelector("#space-forecast-list");
    const listFlares = document.querySelector("#space-flare-list");
    const bandBars = document.querySelector("#space-band-bars");

    const kpiCurrent = document.querySelector("#space-kpi-current");
    const kpiLevel = document.querySelector("#space-kpi-level");
    const kpiForecast = document.querySelector("#space-kpi-forecast");
    const kpiFlare = document.querySelector("#space-kpi-flare");
    const kpiWind = document.querySelector("#space-kpi-wind");
    const kpiBz = document.querySelector("#space-kpi-bz");
    const kpiSource = document.querySelector("#space-kpi-source");

    const cacheStatus = document.querySelector("#space-cache-status");
    const sourceLine = document.querySelector("#space-source-line");
    const currentBand = document.querySelector("#space-current-band");
    const lastObservation = document.querySelector("#space-last-observation");
    const generatedAt = document.querySelector("#space-generated-at");
    const trendNote = document.querySelector("#space-trend-note");
    const summary = document.querySelector("#space-solar-summary");
    const alertsMeta = document.querySelector("#space-alerts-meta");
    const alertsGrid = document.querySelector("#space-alerts-grid");

    const sunImage = document.querySelector("#space-sun-image");
    const sunFallback = document.querySelector("#space-sun-fallback");
    const sunWrap = document.querySelector("#space-sun-wrap");
    const sunModal = document.querySelector("#space-sun-modal");
    const sunModalImage = document.querySelector("#space-sun-modal-image");
    const sunModalClose = document.querySelector("#space-sun-modal-close");
    const auroraImage = document.querySelector("#space-aurora-image");
    const moonName = document.querySelector("#space-moon-name");
    const moonMeta = document.querySelector("#space-moon-meta");
    const moonMeterFill = document.querySelector("#space-moon-meter-fill");
    const moonCanvas = document.querySelector("#space-moon-canvas");
    let lastMoonPhase = null;
    const moonTexture = new Image();
    let moonTextureData = null;
    let moonTextureWidth = 0;
    let moonTextureHeight = 0;

    const showRealSun = () => {
      if (sunFallback) sunFallback.style.display = "none";
      if (sunImage) sunImage.style.display = "block";
    };

    const showFallbackSun = () => {
      if (sunImage) sunImage.style.display = "none";
      if (sunFallback) sunFallback.style.display = "block";
    };

    const primeSunImage = () => {
      if (!sunImage) return;
      let settled = false;
      const timeoutId = window.setTimeout(() => {
        if (settled) return;
        settled = true;
        showFallbackSun();
      }, 1500);
      sunImage.onload = () => {
        if (settled) return;
        settled = true;
        window.clearTimeout(timeoutId);
        showRealSun();
      };
      sunImage.onerror = () => {
        if (settled) return;
        settled = true;
        window.clearTimeout(timeoutId);
        showFallbackSun();
      };
      sunImage.src = "/api/space-weather-sun.php";
    };

    const formatTime = (iso) => (iso
      ? new Date(iso).toLocaleString([], { month: "short", day: "2-digit", hour: "2-digit", minute: "2-digit" })
      : "n/a");

    const formatNumber = (value, digits = 1) => (typeof value === "number" && Number.isFinite(value) ? value.toFixed(digits) : "--");
    const MAX_FLARE_ROWS = 6;
    const MAX_FORECAST_ROWS = 6;
    const MAX_READING_ROWS = 8;

    const inferBand = (kp) => {
      if (typeof kp !== "number") return "Unknown";
      if (kp >= 7) return "Severe";
      if (kp >= 5) return "Storming";
      if (kp >= 3) return "Active";
      if (kp >= 2) return "Unsettled";
      return "Quiet";
    };

    const stormCodeOnly = (stormLevelText) => {
      if (typeof stormLevelText === "string") {
        const match = stormLevelText.match(/\bG[1-5]\b/i);
        if (match && match[0]) return match[0].toUpperCase();
      }
      return "G0";
    };

    const alertRank = (level) => {
      if (level === "g5") return 9;
      if (level === "g4") return 8;
      if (level === "g3") return 7;
      if (level === "g2") return 6;
      if (level === "g1") return 5;
      if (level === "critical") return 4;
      if (level === "elevated") return 3;
      if (level === "watch") return 2;
      return 1;
    };

    const formatAuroraPlaces = (rows, max = 3) => {
      if (!Array.isArray(rows) || rows.length === 0) return "";
      return rows
        .slice(0, max)
        .map((row) => {
          const city = typeof row?.city === "string" ? row.city : "";
          const country = typeof row?.country === "string" ? row.country : "";
          if (city && country) return `${city} (${country})`;
          return city || country;
        })
        .filter(Boolean)
        .join(", ");
    };

    const selectAuroraTargets = (auroraTargets) => {
      const high = Array.isArray(auroraTargets?.high) ? auroraTargets.high : [];
      const moderate = Array.isArray(auroraTargets?.moderate) ? auroraTargets.moderate : [];
      const watch = Array.isArray(auroraTargets?.watch) ? auroraTargets.watch : [];
      if (high.length > 0) return high;
      if (moderate.length > 0) return moderate;
      if (watch.length > 0) return watch;
      return [];
    };

    const evaluateOperationalAlerts = (scenario) => {
      const alerts = [];
      const kp = typeof scenario.kp === "number" ? scenario.kp : null;
      const wind = typeof scenario.wind === "number" ? scenario.wind : null;
      const density = typeof scenario.density === "number" ? scenario.density : null;
      const bz = typeof scenario.bz === "number" ? scenario.bz : null;
      const dst = typeof scenario.dst === "number" ? scenario.dst : null;
      const stormLabel = scenario.storm || "G0";
      const auroraTargets = scenario.auroraTargets && typeof scenario.auroraTargets === "object"
        ? scenario.auroraTargets
        : { high: [], moderate: [], watch: [] };

      if (kp !== null && kp >= 9) {
        alerts.push({
          level: "g5",
          title: `${stormLabel} - tempesta geomagnetica estrema`,
          body: `Kp ${formatNumber(kp, 1)}: tempesta geomagnetica estrema in corso.`,
        });
      } else if (kp !== null && kp >= 8) {
        alerts.push({
          level: "g4",
          title: `${stormLabel} - tempesta geomagnetica severa`,
          body: `Kp ${formatNumber(kp, 1)}: tempesta geomagnetica severa in corso.`,
        });
      } else if (kp !== null && kp >= 7) {
        alerts.push({
          level: "g3",
          title: `${stormLabel} - tempesta geomagnetica forte`,
          body: `Kp ${formatNumber(kp, 1)}: tempesta geomagnetica forte in corso.`,
        });
      } else if (kp !== null && kp >= 6) {
        alerts.push({
          level: "g2",
          title: `${stormLabel} - tempesta geomagnetica moderata`,
          body: `Kp ${formatNumber(kp, 1)}: tempesta geomagnetica moderata in corso.`,
        });
      } else if (kp !== null && kp >= 5) {
        alerts.push({
          level: "g1",
          title: `${stormLabel} - tempesta geomagnetica minore`,
          body: `Kp ${formatNumber(kp, 1)}: tempesta geomagnetica minore in corso.`,
        });
      }

      if (wind !== null && density !== null && wind >= 500 && density >= 20) {
        alerts.push({
          level: "elevated",
          title: "Flusso solare sostenuto",
          body: `Solar wind ${formatNumber(wind, 0)} km/s e densita ${formatNumber(density, 1)} p/cm3: input solare elevato.`,
        });
      } else if ((wind !== null && wind >= 470) || (density !== null && density >= 18)) {
        alerts.push({
          level: "watch",
          title: "Flusso solare in aumento",
          body: `Solar wind ${formatNumber(wind, 0)} km/s e densita ${formatNumber(density, 1)} p/cm3: condizioni da seguire.`,
        });
      }

      if (bz !== null && bz <= -8) {
        const ovationRows = selectAuroraTargets(auroraTargets);
        const places = formatAuroraPlaces(ovationRows, 2);
        alerts.push({
          level: "critical",
          title: "Bz molto negativo: alta probabilità di aurore",
          body: places
            ? `Bz ${formatNumber(bz, 1)} nT: possibile osservazione nel breve termine (${places}).`
            : `Bz ${formatNumber(bz, 1)} nT: possibile osservazione nel breve termine alle alte latitudini.`,
          ovationPlaces: ovationRows,
          ovationForecast: typeof auroraTargets?.forecast_time_utc === "string" ? auroraTargets.forecast_time_utc : null,
        });
      }

      if (dst !== null && dst <= -100) {
        alerts.push({
          level: "critical",
          title: "Dst severo",
          body: `Dst ${formatNumber(dst, 0)} nT: tempesta geomagnetica intensa in corso.`,
        });
      } else if (dst !== null && dst <= -60) {
        alerts.push({
          level: "elevated",
          title: "Dst moderato",
          body: `Dst ${formatNumber(dst, 0)} nT: tempesta geomagnetica moderata in corso.`,
        });
      }

      if (alerts.length === 0) {
        alerts.push({
          level: "info",
          title: "Nessuna anomalia operativa",
          body: "Nessuna soglia di allerta superata nei parametri principali.",
        });
      }

      return alerts.sort((a, b) => alertRank(b.level) - alertRank(a.level)).slice(0, 4);
    };

    const renderOperationalAlerts = (alerts) => {
      if (!alertsGrid || !alertsMeta) return;
      const escapeHtml = (value) => String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\"/g, "&quot;")
        .replace(/'/g, "&#39;");

      if (!Array.isArray(alerts) || alerts.length === 0) {
        alertsMeta.textContent = "Nessun avviso disponibile";
        alertsGrid.innerHTML = "<article class='space-alert-item space-alert-info'><h4>Nessuna anomalia operativa</h4><p>Parametri attuali sotto soglie di attenzione.</p></article>";
        return;
      }

      alertsMeta.textContent = `${alerts.length} avvisi attivi`;
      alertsGrid.innerHTML = alerts.map((alert) => {
        const level = typeof alert.level === "string" ? alert.level : "info";
        const title = typeof alert.title === "string" ? alert.title : "Avviso operativo";
        const body = typeof alert.body === "string" ? alert.body : "";
        const places = Array.isArray(alert.ovationPlaces) ? alert.ovationPlaces : [];
        const hasPopover = places.length > 0;
        const forecastLabel = typeof alert.ovationForecast === "string" ? formatTime(alert.ovationForecast) : "n/a";
        const placesList = hasPopover
          ? places
            .slice(0, 8)
            .map((row) => {
              const city = escapeHtml(typeof row?.city === "string" ? row.city : "");
              const country = escapeHtml(typeof row?.country === "string" ? row.country : "");
              const value = typeof row?.value === "number" ? `${row.value}%` : "--";
              const place = city && country ? `${city} (${country})` : (city || country || "n/a");
              return `<li><span>${place}</span><strong>${value}</strong></li>`;
            })
            .join("")
          : "";
        const popover = hasPopover
          ? `<details class="space-alert-popover"><summary title="Mostra località previste" aria-label="Mostra località previste">i</summary><div class="space-alert-popover-card"><p class="space-alert-popover-meta">Previsione aurora: ${escapeHtml(forecastLabel)}</p><ul>${placesList}</ul></div></details>`
          : "";
        return `<article class="space-alert-item space-alert-${level}"><div class="space-alert-title-row"><h4>${escapeHtml(title)}</h4>${popover}</div><p>${escapeHtml(body)}</p></article>`;
      }).join("");
    };

    const flareTier = (flareClass) => {
      if (!flareClass || typeof flareClass !== "string") return "flare-a";
      const c = flareClass.trim().toUpperCase();
      if (c.startsWith("X")) return "flare-x";
      if (c.startsWith("M")) return "flare-m";
      if (c.startsWith("C")) return "flare-c";
      return "flare-a";
    };

    const clamp01 = (value) => Math.max(0, Math.min(1, value));
    const lerp = (a, b, t) => a + ((b - a) * t);
    const lerpColor = (from, to, t) => [
      Math.round(lerp(from[0], to[0], t)),
      Math.round(lerp(from[1], to[1], t)),
      Math.round(lerp(from[2], to[2], t)),
    ];

    const windToneFromSpeed = (speed) => {
      const yellow = [247, 210, 30];
      const orange = [255, 137, 91];
      const red = [255, 95, 69];
      const violet = [166, 109, 255];

      if (speed <= 560) return yellow;
      if (speed <= 600) return lerpColor(yellow, orange, clamp01((speed - 560) / 40));
      if (speed <= 800) return lerpColor(orange, red, clamp01((speed - 600) / 200));
      if (speed <= 1000) return lerpColor(red, violet, clamp01((speed - 800) / 200));
      return violet;
    };

    const applyWindSeverityTheme = (svg, points) => {
      if (!svg) return;
      if (!Array.isArray(points) || points.length === 0) return;

      let maxSpeed = -Infinity;
      points.forEach((row) => {
        if (typeof row?.value === "number" && Number.isFinite(row.value) && row.value > maxSpeed) {
          maxSpeed = row.value;
        }
      });
      if (!Number.isFinite(maxSpeed)) return;
      const [r, g, b] = windToneFromSpeed(maxSpeed);
      const fillAlpha = maxSpeed >= 1000 ? 0.52 : maxSpeed >= 800 ? 0.48 : maxSpeed >= 600 ? 0.44 : 0.36;
      svg.style.setProperty("--space-wind-stroke", `rgb(${r}, ${g}, ${b})`);
      svg.style.setProperty("--space-wind-fill", `rgba(${r}, ${g}, ${b}, ${fillAlpha})`);
    };

    const ensureChartTooltip = (svg) => {
      const wrap = svg?.closest(".space-kp-chart-wrap");
      if (!wrap) return null;
      let tooltip = wrap.querySelector(".space-chart-tooltip");
      if (!tooltip) {
        tooltip = document.createElement("div");
        tooltip.className = "space-chart-tooltip";
        tooltip.hidden = true;
        tooltip.style.display = "none";
        wrap.appendChild(tooltip);
      }
      return tooltip;
    };

    let chartHoverGuardsBound = false;
    const hideAllChartHovers = (exceptWrap = null) => {
      document.querySelectorAll(".space-kp-chart-wrap").forEach((node) => {
        if (exceptWrap && node === exceptWrap) return;
        const tooltip = node.querySelector(".space-chart-tooltip");
        if (tooltip) tooltip.hidden = true;
        const svg = node.querySelector("svg");
        if (!svg) return;
        const line = svg.querySelector(".space-hover-line");
        const dot = svg.querySelector(".space-hover-dot");
        if (line) line.style.display = "none";
        if (dot) dot.style.display = "none";
      });
    };

    const ensureChartHoverGuards = () => {
      if (chartHoverGuardsBound) return;
      chartHoverGuardsBound = true;

      document.addEventListener("pointermove", (event) => {
        const target = event.target;
        if (!(target instanceof Element) || !target.closest(".space-kp-chart-wrap")) {
          hideAllChartHovers();
        }
      });

      document.addEventListener("scroll", () => hideAllChartHovers(), { passive: true });
      window.addEventListener("blur", () => hideAllChartHovers());
      document.addEventListener("visibilitychange", () => {
        if (document.hidden) hideAllChartHovers();
      });
    };

    const bindChartHover = (svg, projected, formatter) => {
      if (!svg) return;
      ensureChartHoverGuards();
      if (typeof svg.__spaceHoverCleanup === "function") {
        svg.__spaceHoverCleanup();
        svg.__spaceHoverCleanup = null;
      }

      const tooltip = ensureChartTooltip(svg);
      if (!tooltip) return;

      const ns = "http://www.w3.org/2000/svg";
      let hoverLine = svg.querySelector(".space-hover-line");
      if (!hoverLine) {
        hoverLine = document.createElementNS(ns, "line");
        hoverLine.setAttribute("class", "space-hover-line");
        hoverLine.style.display = "none";
        svg.appendChild(hoverLine);
      }

      let hoverDot = svg.querySelector(".space-hover-dot");
      if (!hoverDot) {
        hoverDot = document.createElementNS(ns, "circle");
        hoverDot.setAttribute("class", "space-hover-dot");
        hoverDot.setAttribute("r", "4");
        hoverDot.style.display = "none";
        svg.appendChild(hoverDot);
      }

      const wrap = svg.closest(".space-kp-chart-wrap");
      if (!wrap) return;

      const hide = () => {
        hoverLine.style.display = "none";
        hoverDot.style.display = "none";
        tooltip.hidden = true;
        tooltip.style.display = "none";
      };
      hide();

      if (!Array.isArray(projected) || projected.length === 0) {
        return;
      }

      const onMove = (event) => {
        const rect = svg.getBoundingClientRect();
        if (rect.width <= 0 || rect.height <= 0) return;
        const viewX = ((event.clientX - rect.left) / rect.width) * 560;

        let nearest = projected[0];
        let best = Math.abs(projected[0].x - viewX);
        for (let idx = 1; idx < projected.length; idx += 1) {
          const d = Math.abs(projected[idx].x - viewX);
          if (d < best) {
            best = d;
            nearest = projected[idx];
          }
        }

        hoverLine.setAttribute("x1", String(nearest.x));
        hoverLine.setAttribute("x2", String(nearest.x));
        hoverLine.setAttribute("y1", "20");
        hoverLine.setAttribute("y2", "200");
        hoverLine.style.display = "block";

        hoverDot.setAttribute("cx", String(nearest.x));
        hoverDot.setAttribute("cy", String(nearest.y));
        hoverDot.style.display = "block";

        hideAllChartHovers(wrap);
        tooltip.innerHTML = formatter(nearest);
        tooltip.hidden = false;
        tooltip.style.display = "grid";

        const wrapRect = wrap.getBoundingClientRect();
        let left = event.clientX - wrapRect.left + 12;
        const top = Math.max(6, (nearest.y / 220) * wrapRect.height - 44);
        const maxLeft = wrapRect.width - 180;
        if (left > maxLeft) left = maxLeft;
        if (left < 6) left = 6;

        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${top}px`;
      };

      svg.addEventListener("mousemove", onMove);
      svg.addEventListener("mouseleave", hide);
      svg.addEventListener("pointerleave", hide);
      wrap.addEventListener("mouseleave", hide);
      wrap.addEventListener("pointerleave", hide);

      const onTouchStart = (event) => {
        const touch = event.touches && event.touches[0];
        if (!touch) return;
        onMove(touch);
      };

      svg.addEventListener("touchstart", onTouchStart, { passive: true });
      svg.addEventListener("touchend", hide);
      svg.addEventListener("touchcancel", hide);

      svg.__spaceHoverCleanup = () => {
        hide();
        svg.removeEventListener("mousemove", onMove);
        svg.removeEventListener("mouseleave", hide);
        svg.removeEventListener("pointerleave", hide);
        wrap.removeEventListener("mouseleave", hide);
        wrap.removeEventListener("pointerleave", hide);
        svg.removeEventListener("touchstart", onTouchStart);
        svg.removeEventListener("touchend", hide);
        svg.removeEventListener("touchcancel", hide);
      };
    };

    const renderLineChart = (svg, series, options = {}) => {
      if (!svg) return;
      svg.innerHTML = "";

      const points = Array.isArray(series) ? series.filter((row) => typeof row.value === "number") : [];
      if (points.length < 2) {
        svg.innerHTML = "<text x='12' y='22' fill='currentColor'>No chart data</text>";
        return { projected: [] };
      }

      const width = 520;
      const height = 180;
      const xOffset = 20;
      const yOffset = 20;

      const values = points.map((row) => row.value);
      let yMin = typeof options.yMin === "number" ? options.yMin : Math.min(...values);
      let yMax = typeof options.yMax === "number" ? options.yMax : Math.max(...values);
      if (yMax - yMin < 1e-9) {
        yMax += 1;
        yMin -= 1;
      }

      const range = yMax - yMin;
      const step = width / (points.length - 1);
      const toY = (value) => yOffset + (height - ((value - yMin) / range) * height);

      for (let i = 0; i <= 4; i += 1) {
        const y = yOffset + ((height / 4) * i);
        svg.insertAdjacentHTML("beforeend", `<line x1="${xOffset}" y1="${y.toFixed(2)}" x2="${xOffset + width}" y2="${y.toFixed(2)}" class="space-grid-line" />`);
      }

      svg.insertAdjacentHTML("beforeend", `<text x="6" y="24" class="space-axis-label">${yMax.toFixed(1)}</text>`);
      svg.insertAdjacentHTML("beforeend", `<text x="6" y="198" class="space-axis-label">${yMin.toFixed(1)}</text>`);

      if (typeof options.zeroAt === "number" && options.zeroAt >= yMin && options.zeroAt <= yMax) {
        const zeroY = toY(options.zeroAt);
        svg.insertAdjacentHTML("beforeend", `<line x1="${xOffset}" y1="${zeroY.toFixed(2)}" x2="${xOffset + width}" y2="${zeroY.toFixed(2)}" class="space-zero-line" />`);
      }

      const projected = [];
      let path = "";
      points.forEach((row, idx) => {
        const x = xOffset + (idx * step);
        const y = toY(row.value);
        projected.push({
          x,
          y,
          value: row.value,
          time_utc: typeof row.time_utc === "string" ? row.time_utc : null,
          label: typeof row.label === "string" ? row.label : "",
        });
        path += `${idx === 0 ? "M" : "L"}${x.toFixed(2)} ${y.toFixed(2)} `;
      });

      const buildSplitPath = (rows, sign) => {
        if (!Array.isArray(rows) || rows.length < 2) return "";
        const chunks = [];
        let current = [];
        const flush = () => {
          if (current.length < 2) {
            current = [];
            return;
          }
          let part = "";
          current.forEach((p, idx) => {
            part += `${idx === 0 ? "M" : "L"}${p.x.toFixed(2)} ${p.y.toFixed(2)} `;
          });
          chunks.push(part.trim());
          current = [];
        };

        const inside = (value) => (sign === "positive" ? value >= 0 : value <= 0);
        for (let i = 0; i < rows.length - 1; i += 1) {
          const a = rows[i];
          const b = rows[i + 1];
          const aIn = inside(a.value);
          const bIn = inside(b.value);

          if (aIn && current.length === 0) current.push(a);
          if (aIn && bIn) {
            current.push(b);
            continue;
          }

          if ((a.value < 0 && b.value > 0) || (a.value > 0 && b.value < 0)) {
            const t = (0 - a.value) / (b.value - a.value);
            const cx = a.x + ((b.x - a.x) * t);
            const cy = a.y + ((b.y - a.y) * t);
            const cross = { x: cx, y: cy, value: 0 };
            if (aIn) {
              current.push(cross);
              flush();
            } else if (bIn) {
              current.push(cross);
              current.push(b);
            }
            continue;
          }

          if (aIn && !bIn) {
            flush();
          }
        }
        flush();
        return chunks.join(" ");
      };

      const cls = options.pathClass || "space-path-live";
      if (options.fillClass && projected.length > 1) {
        const fillTo = typeof options.fillToValue === "number" ? options.fillToValue : yMin;
        const clampedFill = Math.max(yMin, Math.min(yMax, fillTo));
        const fillY = toY(clampedFill);
        const firstPoint = projected[0];
        const lastPoint = projected[projected.length - 1];
        const areaPath = `${path.trim()} L${lastPoint.x.toFixed(2)} ${fillY.toFixed(2)} L${firstPoint.x.toFixed(2)} ${fillY.toFixed(2)} Z`;
        svg.insertAdjacentHTML("beforeend", `<path d="${areaPath}" class="${options.fillClass}" />`);
      }
      if (options.splitAtZero) {
        const posPath = buildSplitPath(projected, "positive");
        const negPath = buildSplitPath(projected, "negative");
        if (posPath) svg.insertAdjacentHTML("beforeend", `<path d="${posPath}" class="${options.positivePathClass || cls}" />`);
        if (negPath) svg.insertAdjacentHTML("beforeend", `<path d="${negPath}" class="${options.negativePathClass || cls}" />`);
      } else {
        svg.insertAdjacentHTML("beforeend", `<path d="${path.trim()}" class="${cls}" />`);
      }
      return { projected, yMin, yMax, xOffset, width, yOffset, height };
    };

    const renderMagnetometersChart = (svg, rows) => {
      if (!svg) return { projected: [] };
      svg.innerHTML = "";

      const points = Array.isArray(rows)
        ? rows
          .map((row) => ({
            time_utc: typeof row?.time_utc === "string" ? row.time_utc : null,
            hp: typeof row?.hp === "number" ? row.hp : null,
            he: typeof row?.he === "number" ? row.he : null,
          }))
          .filter((row) => row.hp !== null || row.he !== null)
        : [];

      if (points.length < 2) {
        svg.innerHTML = "<text x='12' y='22' fill='currentColor'>No chart data</text>";
        return { projected: [] };
      }

      const values = points.flatMap((row) => [row.hp, row.he]).filter((v) => typeof v === "number");
      const yMin = Math.min(...values);
      const yMax = Math.max(...values);
      const hpSeries = points.map((row) => ({ value: row.hp, time_utc: row.time_utc })).filter((row) => typeof row.value === "number");
      const heSeries = points.map((row) => ({ value: row.he, time_utc: row.time_utc })).filter((row) => typeof row.value === "number");

      const base = renderLineChart(svg, hpSeries, { yMin, yMax, pathClass: "space-path-mag-hp" });
      if (!base || heSeries.length < 2) return base || { projected: [] };

      const range = Math.max(1e-6, base.yMax - base.yMin);
      const step = heSeries.length > 1 ? base.width / (heSeries.length - 1) : base.width;
      let path = "";
      const heProjected = [];
      heSeries.forEach((row, idx) => {
        const x = base.xOffset + (idx * step);
        const y = base.yOffset + (base.height - (((row.value - base.yMin) / range) * base.height));
        heProjected.push({ x, y, value: row.value, time_utc: row.time_utc });
        path += `${idx === 0 ? "M" : "L"}${x.toFixed(2)} ${y.toFixed(2)} `;
      });
      svg.insertAdjacentHTML("beforeend", `<path d="${path.trim()}" class="space-path-mag-he" />`);

      const hover = base.projected.map((point, idx) => ({
        x: point.x,
        y: point.y,
        time_utc: point.time_utc || heProjected[idx]?.time_utc || null,
        hp: point.value,
        he: heProjected[idx]?.value ?? null,
      }));
      return { projected: hover };
    };

    const renderKpChart = (observedRows, forecastRows) => {
      if (!chartKp) return;
      chartKp.innerHTML = "";

      const observed = observedRows.filter((row) => typeof row.kp_index === "number");
      const forecast = forecastRows.filter((row) => typeof row.kp_index === "number");
      const observedPoints = observed.map((row) => ({ value: row.kp_index }));
      const forecastPoints = forecast.map((row) => ({ value: row.kp_index }));

      const kpBase = renderLineChart(chartKp, observedPoints, { yMin: 0, yMax: 9, pathClass: "space-path-live" });
      const observedProjected = Array.isArray(kpBase?.projected) ? kpBase.projected.map((p, idx) => ({
        ...p,
        time_utc: observed[idx]?.time_utc || null,
        series: "Observed Kp",
      })) : [];
      const hoverPoints = [...observedProjected];

      if (forecastPoints.length > 0) {
        const width = 520;
        const xOffset = 20;
        const height = 180;
        const yOffset = 20;
        const obsStep = observedPoints.length > 1 ? width / (observedPoints.length - 1) : width;
        const splitX = xOffset + (obsStep * Math.max(0, observedPoints.length - 1));
        const joined = observedPoints.length > 0 ? [observedPoints[observedPoints.length - 1], ...forecastPoints] : forecastPoints;

        const range = 9;
        const toY = (value) => yOffset + (height - ((value - 0) / range) * height);
        const step = joined.length > 1 ? (width - (splitX - xOffset)) / (joined.length - 1) : width - (splitX - xOffset);
        let path = "";
        joined.forEach((row, idx) => {
          const x = splitX + (idx * step);
          const y = toY(row.value);
          if (idx > 0) {
            const source = forecast[idx - 1] || null;
            hoverPoints.push({
              x,
              y,
              value: row.value,
              time_utc: source?.time_utc || null,
              series: "Forecast Kp",
            });
          }
          path += `${idx === 0 ? "M" : "L"}${x.toFixed(2)} ${y.toFixed(2)} `;
        });
        chartKp.insertAdjacentHTML("beforeend", `<path d="${path.trim()}" class="space-path-forecast" />`);
      }

      bindChartHover(chartKp, hoverPoints, (point) => {
        const when = point.time_utc ? formatTime(point.time_utc) : "n/a";
        return `<strong>${point.series || "Kp"}</strong><span>${when}</span><span>Kp ${formatNumber(point.value, 1)}</span>`;
      });
    };

    const renderBars = (rows) => {
      if (!bandBars) return;
      const distribution = Array.isArray(rows) ? rows : [];
      const maxCount = distribution.reduce((best, row) => {
        const count = typeof row?.count === "number" ? row.count : 0;
        return count > best ? count : best;
      }, 0);

      if (distribution.length === 0 || maxCount === 0) {
        bandBars.innerHTML = "<div class='bar-row'><div class='bar-label'>No bins</div><div class='bar-track'><div class='bar-fill' style='width:0%'></div></div><div class='bar-value'>0</div></div>";
        return;
      }

      bandBars.innerHTML = distribution.map((row) => {
        const count = typeof row.count === "number" ? row.count : 0;
        const width = (count / maxCount) * 100;
        const label = row.label || "Band";
        return `<div class="bar-row"><div class="bar-label">${label}</div><div class="bar-track"><div class="bar-fill" style="width:${width.toFixed(1)}%"></div></div><div class="bar-value">${count}</div></div>`;
      }).join("");
    };

    const setError = () => {
      if (listReadings) listReadings.innerHTML = "<li class='timeline-row'>Unable to load Kp readings.</li>";
      if (listForecast) listForecast.innerHTML = "<li class='timeline-row'>Unable to load forecast steps.</li>";
      if (listFlares) listFlares.innerHTML = "<li class='timeline-row'>Unable to load flare events.</li>";
      if (summary) summary.textContent = "Space weather streams unavailable right now.";
      if (kpiSource) kpiSource.textContent = "Source unavailable";
      if (moonName) moonName.textContent = "Moon phase unavailable";
      if (moonMeta) moonMeta.textContent = "Illumination --";
      updateMoonVisual(null);
    };

    const moonIsWaxing = (moon) => {
      if (typeof moon?.age_days === "number" && Number.isFinite(moon.age_days)) {
        const synodic = 29.53058867;
        const cycle = ((moon.age_days % synodic) + synodic) % synodic;
        return cycle <= (synodic / 2);
      }
      const name = typeof moon?.name === "string" ? moon.name.toLowerCase() : "";
      if (name.includes("waning") || name.includes("last quarter")) return false;
      if (name.includes("waxing") || name.includes("first quarter")) return true;
      return true;
    };

    const hydrateMoonTexture = () => {
      if (!moonTexture.complete || !moonTexture.naturalWidth || !moonTexture.naturalHeight) {
        moonTextureData = null;
        moonTextureWidth = 0;
        moonTextureHeight = 0;
        return;
      }
      const sampleCanvas = document.createElement("canvas");
      moonTextureWidth = moonTexture.naturalWidth;
      moonTextureHeight = moonTexture.naturalHeight;
      sampleCanvas.width = moonTextureWidth;
      sampleCanvas.height = moonTextureHeight;
      const sampleCtx = sampleCanvas.getContext("2d");
      if (!sampleCtx) {
        moonTextureData = null;
        moonTextureWidth = 0;
        moonTextureHeight = 0;
        return;
      }
      sampleCtx.drawImage(moonTexture, 0, 0, moonTextureWidth, moonTextureHeight);
      moonTextureData = sampleCtx.getImageData(0, 0, moonTextureWidth, moonTextureHeight).data;
    };

    const updateMoonVisual = (moon) => {
      if (!moonCanvas) return;
      const ctx = moonCanvas.getContext("2d", { willReadFrequently: true });
      if (!ctx) return;

      const size = moonCanvas.width;
      const center = size / 2;
      const radius = size * 0.47;
      const rawIllum = typeof moon?.illumination_pct === "number" && Number.isFinite(moon.illumination_pct) ? moon.illumination_pct : 0;
      const illumination = Math.max(0, Math.min(1, rawIllum / 100));
      const waxing = moonIsWaxing(moon);
      const cosAlpha = Math.max(-1, Math.min(1, (2 * illumination) - 1));
      const alpha = Math.acos(cosAlpha);
      const sx = (waxing ? -1 : 1) * Math.sin(alpha);
      const sz = Math.cos(alpha);
      const earthshine = 0.002 + (Math.pow(illumination, 1.8) * 0.026);
      const textureReady = Boolean(moonTextureData && moonTextureWidth > 0 && moonTextureHeight > 0);

      ctx.clearRect(0, 0, size, size);
      const image = ctx.createImageData(size, size);
      const data = image.data;

      for (let py = 0; py < size; py += 1) {
        for (let px = 0; px < size; px += 1) {
          const idx = (py * size * 4) + (px * 4);
          const nx = (px + 0.5 - center) / radius;
          const ny = (py + 0.5 - center) / radius;
          const rr = (nx * nx) + (ny * ny);
          if (rr > 1) {
            data[idx + 3] = 0;
            continue;
          }

          const nz = Math.sqrt(Math.max(0, 1 - rr));
          const lambert = Math.max(0, (nx * sx) + (nz * sz));
          const limb = Math.pow(Math.max(0, nz), 0.68);
          let albedo = 0.82;

          if (textureReady) {
            const lon = Math.atan2(nx, nz);
            const lat = Math.asin(Math.max(-1, Math.min(1, ny)));
            let u = (lon / (2 * Math.PI)) + 0.5;
            if (u < 0) u += 1;
            if (u >= 1) u -= 1;
            const v = 0.5 - (lat / Math.PI);
            const tx = Math.max(0, Math.min(moonTextureWidth - 1, Math.floor(u * (moonTextureWidth - 1))));
            const ty = Math.max(0, Math.min(moonTextureHeight - 1, Math.floor(v * (moonTextureHeight - 1))));
            const tIdx = ((ty * moonTextureWidth) + tx) * 4;
            const tr = moonTextureData[tIdx];
            const tg = moonTextureData[tIdx + 1];
            const tb = moonTextureData[tIdx + 2];
            const lum = ((0.2126 * tr) + (0.7152 * tg) + (0.0722 * tb)) / 255;
            albedo = 0.56 + (lum * 0.62);
          } else {
            const t1 = Math.sin((nx * 16.2) + (ny * 9.7));
            const t2 = Math.sin((nx * 33.1) - (ny * 19.4));
            const t3 = Math.sin((nx * 57.2) + (ny * 41.8));
            albedo = 0.64 + (0.22 * t1) + (0.12 * t2) + (0.08 * t3);
          }

          albedo = Math.max(0.12, Math.min(1.22, albedo));
          const direct = lambert > 0 ? Math.pow(lambert, 0.72) : 0;
          const crescentGain = illumination < 0.12
            ? 1.15 + (((0.12 - illumination) / 0.12) * 0.85)
            : 1.15;
          const lit = earthshine + (direct * crescentGain);
          const intensity = Math.max(0, Math.min(1, (lit * albedo) * (0.28 + (0.72 * limb))));
          const rim = Math.pow(Math.max(0, 1 - nz), 1.35) * Math.pow(Math.max(0, lambert), 0.35);
          const rimBoost = rim * (0.16 + ((1 - illumination) * 0.2));

          data[idx] = Math.round(8 + (intensity * 236) + (rimBoost * 34));
          data[idx + 1] = Math.round(12 + (intensity * 240) + (rimBoost * 38));
          data[idx + 2] = Math.round(18 + (intensity * 246) + (rimBoost * 46));
          data[idx + 3] = 255;
        }
      }

      ctx.putImageData(image, 0, 0);

      ctx.save();
      ctx.globalCompositeOperation = "source-atop";
      const vignette = ctx.createRadialGradient(center, center, radius * 0.58, center, center, radius);
      vignette.addColorStop(0, "rgba(0, 0, 0, 0)");
      vignette.addColorStop(1, "rgba(0, 0, 0, 0.32)");
      ctx.fillStyle = vignette;
      ctx.fillRect(0, 0, size, size);
      ctx.restore();
    };

    moonTexture.decoding = "async";
    moonTexture.loading = "eager";
    moonTexture.addEventListener("load", () => {
      hydrateMoonTexture();
      updateMoonVisual(lastMoonPhase);
    });
    moonTexture.addEventListener("error", () => {
      moonTextureData = null;
      moonTextureWidth = 0;
      moonTextureHeight = 0;
      updateMoonVisual(lastMoonPhase);
    });
    moonTexture.src = "/assets/img/moon-texture-1024.jpg";

    const load = async () => {
      try {
      const response = await fetch("/api/space-weather.php", { headers: { Accept: "application/json" } });
      if (!response.ok) throw new Error("Request failed");

      const payload = await response.json();

      const currentKp = typeof payload.kp_index_current === "number" ? payload.kp_index_current : null;
      const forecastKp = typeof payload.forecast_kp_max_24h === "number" ? payload.forecast_kp_max_24h : null;
      const flarePeakClass = payload.xray_class_peak_24h || "--";
      const windCurrent = typeof payload.solar_wind_speed_current === "number" ? payload.solar_wind_speed_current : null;
      const bzCurrent = typeof payload.imf_bz_current === "number" ? payload.imf_bz_current : null;
      const densityCurrent = typeof payload.solar_wind_density_current === "number" ? payload.solar_wind_density_current : null;
      const dstCurrent = typeof payload.dst_current === "number" ? payload.dst_current : null;

      if (kpiCurrent) kpiCurrent.textContent = formatNumber(currentKp, 1);
      if (kpiLevel) kpiLevel.textContent = stormCodeOnly(payload.storm_level);
      if (kpiForecast) kpiForecast.textContent = dstCurrent !== null ? `${formatNumber(dstCurrent, 0)}` : "--";
      if (kpiFlare) kpiFlare.textContent = flarePeakClass;
      if (kpiWind) kpiWind.textContent = windCurrent !== null ? `${formatNumber(windCurrent, 0)}` : "--";
      if (kpiBz) kpiBz.textContent = bzCurrent !== null ? `${formatNumber(bzCurrent, 1)}` : "--";

      const provider = payload.provider || "NOAA SWPC";
      const isStale = Boolean(payload.stale_cache);
      if (kpiSource) kpiSource.textContent = `Source: ${provider}`;
      if (cacheStatus) cacheStatus.textContent = isStale ? "Feed delayed" : "Feed synced";
      if (sourceLine) sourceLine.textContent = `Provider: ${provider} · Last update ${formatTime(payload.generated_at)}`;

      if (currentBand) currentBand.textContent = payload.kp_band_current || inferBand(currentKp);
      if (lastObservation) lastObservation.textContent = formatTime(payload.last_observation_utc);
      if (generatedAt) generatedAt.textContent = formatTime(payload.generated_at);

      const trend = payload.kp_trend_24h && typeof payload.kp_trend_24h === "object" ? payload.kp_trend_24h : null;
      const avg = trend && typeof trend.average === "number" ? trend.average.toFixed(1) : "--";
      const dir = trend && typeof trend.direction === "string" ? trend.direction : "stable";
      if (summary) {
        summary.textContent = `${payload.storm_level || "Unknown"} regime, Kp average ${avg}, ${dir} trend, flare peak ${flarePeakClass}.`;
      }
      if (trendNote) {
        const delta = trend && typeof trend.delta === "number" ? trend.delta : 0;
        trendNote.textContent = `24h trend: ${dir} (${delta >= 0 ? "+" : ""}${delta.toFixed(1)} Kp).`;
      }

      const operationalAlerts = evaluateOperationalAlerts({
        kp: currentKp,
        wind: windCurrent,
        density: densityCurrent,
        bz: bzCurrent,
        dst: dstCurrent,
        storm: payload.storm_level,
        auroraTargets: payload.aurora_targets,
      });
      renderOperationalAlerts(operationalAlerts);

      const openSunModal = () => {
        if (!sunModal || !sunModalImage || !sunImage) return;
        if (sunImage.style.display === "none" || !sunImage.src) return;
        sunModalImage.src = sunImage.currentSrc || sunImage.src;
        sunModal.hidden = false;
        sunModal.setAttribute("aria-hidden", "false");
      };

      const closeSunModal = () => {
        if (!sunModal || !sunModalImage) return;
        sunModal.hidden = true;
        sunModal.setAttribute("aria-hidden", "true");
        sunModalImage.src = "";
      };

      if (sunWrap) {
        sunWrap.addEventListener("click", openSunModal);
        sunWrap.addEventListener("keydown", (event) => {
          if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            openSunModal();
          }
        });
      }

      if (sunModalClose) {
        sunModalClose.addEventListener("click", closeSunModal);
      }

      if (sunModal) {
        sunModal.addEventListener("click", (event) => {
          const target = event.target;
          if (!(target instanceof Element)) return;
          if (target.closest("[data-close-sun-modal='1']")) {
            closeSunModal();
          }
        });
      }

      document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
          closeSunModal();
        }
      });

      const sunCandidates = Array.isArray(payload.sun_image_candidates)
        ? payload.sun_image_candidates
        : [typeof payload.sun_image_url === "string" ? payload.sun_image_url : ""];
      const cleanedSunCandidates = sunCandidates.filter((url) => typeof url === "string" && url.trim() !== "");

      if (sunImage) {
        if (cleanedSunCandidates.length === 0) {
          showFallbackSun();
        } else {
          const trySunCandidate = (index) => {
            if (index >= cleanedSunCandidates.length) {
              showFallbackSun();
              return;
            }

            const url = cleanedSunCandidates[index];
            let settled = false;
            const timeoutId = window.setTimeout(() => {
              if (settled) return;
              settled = true;
              trySunCandidate(index + 1);
            }, 1400);

            sunImage.onload = () => {
              if (settled) return;
              settled = true;
              window.clearTimeout(timeoutId);
              showRealSun();
            };
            sunImage.onerror = () => {
              if (settled) return;
              settled = true;
              window.clearTimeout(timeoutId);
              trySunCandidate(index + 1);
            };

            sunImage.src = url;
            if (sunImage.complete && sunImage.naturalWidth > 0) {
              settled = true;
              window.clearTimeout(timeoutId);
              showRealSun();
            }
          };

          trySunCandidate(0);
        }
      }

      const readings = Array.isArray(payload.readings) ? payload.readings : [];
      const readings24h = Array.isArray(payload.readings_24h) ? payload.readings_24h : readings;
      const forecastRows = Array.isArray(payload.forecast_readings_24h) ? payload.forecast_readings_24h : [];

      if (listReadings) {
        const shownReadings = readings.slice().reverse().slice(0, MAX_READING_ROWS);
        const extraReadings = Math.max(0, readings.length - shownReadings.length);
        listReadings.innerHTML = readings.length === 0
          ? "<li class='timeline-row'>No Kp readings available.</li>"
          : [
            ...shownReadings.map((row) => `<li class='timeline-row'><strong>${formatTime(row.time_utc)}</strong><span>Kp ${formatNumber(row.kp_index, 1)}</span></li>`),
            extraReadings > 0 ? `<li class='timeline-row'><span>+${extraReadings} older readings</span></li>` : "",
          ].join("");
      }

      if (listForecast) {
        const shownForecast = forecastRows.slice(0, MAX_FORECAST_ROWS);
        const extraForecast = Math.max(0, forecastRows.length - shownForecast.length);
        listForecast.innerHTML = forecastRows.length === 0
          ? "<li class='timeline-row'>No forecast rows available.</li>"
          : [
            ...shownForecast.map((row) => `<li class='timeline-row'><strong>${formatTime(row.time_utc)}</strong><span>Forecast Kp ${formatNumber(row.kp_index, 1)}</span></li>`),
            extraForecast > 0 ? `<li class='timeline-row'><span>+${extraForecast} more forecast steps</span></li>` : "",
          ].join("");
      }

      const flares = Array.isArray(payload.flare_events) ? payload.flare_events : [];
      if (listFlares) {
        const shownFlares = flares.slice(0, MAX_FLARE_ROWS);
        const extraFlares = Math.max(0, flares.length - shownFlares.length);
        listFlares.innerHTML = flares.length === 0
          ? "<li class='timeline-row'>No C/M/X flare peaks detected in this window.</li>"
          : [
            ...shownFlares.map((row) => {
            const cls = row.class || "--";
            const tier = flareTier(cls);
            return `<li class='timeline-row'><div class='timeline-head'><strong>${formatTime(row.time_utc)}</strong><span class='flare-tag ${tier}'>${cls}</span></div><span>Flux ${typeof row.flux === "number" ? row.flux.toExponential(2) : "--"} W/m²</span></li>`;
            }),
            extraFlares > 0 ? `<li class='timeline-row'><span>+${extraFlares} more flare peaks</span></li>` : "",
          ].join("");
      }

      renderBars(payload.kp_band_distribution || []);
      renderKpChart(readings24h, forecastRows);

      const xraySeries = Array.isArray(payload.xray_series_24h) ? payload.xray_series_24h : [];
      const xrayPoints = xraySeries
        .filter((row) => typeof row.flux === "number" && row.flux > 0)
        .map((row) => ({ value: Math.log10(row.flux), raw_flux: row.flux, time_utc: row.time_utc, label: "X-ray flux" }));
      const xrayRender = renderLineChart(chartXray, xrayPoints, { yMin: -8, yMax: -3, pathClass: "space-path-flare" });
      bindChartHover(chartXray, xrayRender?.projected || [], (point) => {
        const when = point.time_utc ? formatTime(point.time_utc) : "n/a";
        return `<strong>X-ray flux</strong><span>${when}</span><span>log10 ${formatNumber(point.value, 2)} W/m²</span>`;
      });

      const windSeries = Array.isArray(payload.solar_wind_speed_series_24h) ? payload.solar_wind_speed_series_24h : [];
      const windPoints = windSeries
        .map((row) => ({ value: typeof row.speed === "number" ? row.speed : null, time_utc: row.time_utc, label: "Solar wind" }))
        .filter((row) => row.value !== null);
      const windRender = renderLineChart(chartWind, windPoints, {
        pathClass: "space-path-wind",
        fillClass: "space-area-wind",
      });
      applyWindSeverityTheme(chartWind, windRender?.projected || []);
      bindChartHover(chartWind, windRender?.projected || [], (point) => {
        const when = point.time_utc ? formatTime(point.time_utc) : "n/a";
        return `<strong>Solar wind</strong><span>${when}</span><span>${formatNumber(point.value, 0)} km/s</span>`;
      });

      const bzSeries = Array.isArray(payload.imf_bz_series_24h) ? payload.imf_bz_series_24h : [];
      const bzPoints = bzSeries
        .map((row) => ({ value: typeof row.bz === "number" ? row.bz : null, time_utc: row.time_utc, label: "IMF Bz" }))
        .filter((row) => row.value !== null);
      const bzRender = renderLineChart(chartBz, bzPoints, {
        pathClass: "space-path-bz",
        zeroAt: 0,
        splitAtZero: true,
        positivePathClass: "space-path-bz-pos",
        negativePathClass: "space-path-bz-neg",
      });
      bindChartHover(chartBz, bzRender?.projected || [], (point) => {
        const when = point.time_utc ? formatTime(point.time_utc) : "n/a";
        return `<strong>IMF Bz</strong><span>${when}</span><span>${formatNumber(point.value, 1)} nT</span>`;
      });

      const densitySeries = Array.isArray(payload.solar_wind_density_series_24h) ? payload.solar_wind_density_series_24h : [];
      const densityPoints = densitySeries
        .map((row) => ({ value: typeof row.density === "number" ? row.density : null, time_utc: row.time_utc }))
        .filter((row) => row.value !== null);
      const densityRender = renderLineChart(chartDensity, densityPoints, { pathClass: "space-path-density", fillClass: "space-area-density", yMin: 0 });
      bindChartHover(chartDensity, densityRender?.projected || [], (point) => {
        const when = point.time_utc ? formatTime(point.time_utc) : "n/a";
        return `<strong>Solar wind density</strong><span>${when}</span><span>${formatNumber(point.value, 2)} p/cm3</span>`;
      });

      const btSeries = Array.isArray(payload.imf_bt_series_24h) ? payload.imf_bt_series_24h : [];
      const btPoints = btSeries
        .map((row) => ({ value: typeof row.bt === "number" ? row.bt : null, time_utc: row.time_utc }))
        .filter((row) => row.value !== null);
      const btRender = renderLineChart(chartBt, btPoints, { pathClass: "space-path-bt", fillClass: "space-area-bt", yMin: 0 });
      bindChartHover(chartBt, btRender?.projected || [], (point) => {
        const when = point.time_utc ? formatTime(point.time_utc) : "n/a";
        return `<strong>IMF Bt</strong><span>${when}</span><span>${formatNumber(point.value, 1)} nT</span>`;
      });

      const dstSeries = Array.isArray(payload.dst_series_24h) ? payload.dst_series_24h : [];
      const dstPoints = dstSeries
        .map((row) => ({ value: typeof row.dst === "number" ? row.dst : null, time_utc: row.time_utc }))
        .filter((row) => row.value !== null);
      const dstRender = renderLineChart(chartDst, dstPoints, { pathClass: "space-path-dst", zeroAt: 0 });
      bindChartHover(chartDst, dstRender?.projected || [], (point) => {
        const when = point.time_utc ? formatTime(point.time_utc) : "n/a";
        return `<strong>Dst index</strong><span>${when}</span><span>${formatNumber(point.value, 0)} nT</span>`;
      });

      const magSeries = Array.isArray(payload.magnetometers_series_24h) ? payload.magnetometers_series_24h : [];
      const magRender = renderMagnetometersChart(chartMag, magSeries);
      bindChartHover(chartMag, magRender?.projected || [], (point) => {
        const when = point.time_utc ? formatTime(point.time_utc) : "n/a";
        return `<strong>GOES magnetometers</strong><span>${when}</span><span>Hp ${formatNumber(point.hp, 1)} · He ${formatNumber(point.he, 1)}</span>`;
      });

      if (auroraImage) {
        const auroraUrl = typeof payload.auroral_oval_url === "string" ? payload.auroral_oval_url : "";
        if (auroraUrl) {
          auroraImage.src = auroraUrl;
        }
      }

      const moon = payload.moon_phase && typeof payload.moon_phase === "object" ? payload.moon_phase : null;
      lastMoonPhase = moon;
      if (moonName) {
        moonName.textContent = typeof moon?.name === "string" ? moon.name : "Moon phase unavailable";
      }
      if (moonMeta) {
        const illum = typeof moon?.illumination_pct === "number" ? `${formatNumber(moon.illumination_pct, 1)}%` : "--";
        const age = typeof moon?.age_days === "number" ? `${formatNumber(moon.age_days, 1)}d` : "--";
        moonMeta.textContent = `Illumination ${illum} · Age ${age}`;
      }
      if (moonMeterFill) {
        const illumValue = typeof moon?.illumination_pct === "number" ? Math.max(0, Math.min(100, moon.illumination_pct)) : 0;
        moonMeterFill.style.width = `${illumValue}%`;
      }
      updateMoonVisual(moon);
      } catch (error) {
        setError();
      }
    };

    const REFRESH_MS = 60000;
    let refreshInFlight = false;
    const refresh = async () => {
      if (refreshInFlight) return;
      refreshInFlight = true;
      try {
        await load();
      } finally {
        refreshInFlight = false;
      }
    };

    refresh();
    primeSunImage();
    window.setInterval(() => {
      if (document.hidden) return;
      void refresh();
    }, REFRESH_MS);
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
