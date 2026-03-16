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
    <p class="kpi-label">Forecast Kp Max</p>
    <p id="space-kpi-forecast" class="kpi-value">--</p>
    <p class="kpi-note">Next 24h</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Flare Peak (24h)</p>
    <p id="space-kpi-flare" class="kpi-value">--</p>
    <p class="kpi-note">GOES X-ray class</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Solar Wind</p>
    <p id="space-kpi-wind" class="kpi-value">--</p>
    <p class="kpi-note">km/s current speed</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">IMF Bz</p>
    <p id="space-kpi-bz" class="kpi-value">--</p>
    <p id="space-kpi-source" class="kpi-note">Loading source...</p>
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
        <img id="space-sun-image" class="space-sun-image" src="" alt="Latest solar disk image" loading="lazy" />
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

  <article class="card">
    <div class="feed-head">
      <h3>Kp Timeline</h3>
      <p class="feed-meta">Observed + forecast</p>
    </div>
    <div class="space-kp-chart-wrap">
      <svg id="space-kp-chart" class="space-kp-chart" viewBox="0 0 560 220" role="img" aria-label="Kp trend chart"></svg>
    </div>
    <div class="space-chart-legend">
      <span><i class="space-dot-live"></i>Observed Kp</span>
      <span><i class="space-dot-forecast"></i>Forecast Kp</span>
    </div>
  </article>
</section>

<section class="panel panel-charts space-extra-charts">
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

<section class="panel panel-main">
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

  <article class="card side-card">
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

    const sunImage = document.querySelector("#space-sun-image");
    const sunFallback = document.querySelector("#space-sun-fallback");
    const sunWrap = document.querySelector("#space-sun-wrap");
    const sunModal = document.querySelector("#space-sun-modal");
    const sunModalImage = document.querySelector("#space-sun-modal-image");
    const sunModalClose = document.querySelector("#space-sun-modal-close");

    const formatTime = (iso) => (iso
      ? new Date(iso).toLocaleString([], { month: "short", day: "2-digit", hour: "2-digit", minute: "2-digit" })
      : "n/a");

    const formatNumber = (value, digits = 1) => (typeof value === "number" && Number.isFinite(value) ? value.toFixed(digits) : "--");

    const inferBand = (kp) => {
      if (typeof kp !== "number") return "Unknown";
      if (kp >= 7) return "Severe";
      if (kp >= 5) return "Storming";
      if (kp >= 3) return "Active";
      if (kp >= 2) return "Unsettled";
      return "Quiet";
    };

    const flareTier = (flareClass) => {
      if (!flareClass || typeof flareClass !== "string") return "flare-a";
      const c = flareClass.trim().toUpperCase();
      if (c.startsWith("X")) return "flare-x";
      if (c.startsWith("M")) return "flare-m";
      if (c.startsWith("C")) return "flare-c";
      return "flare-a";
    };

    const ensureChartTooltip = (svg) => {
      const wrap = svg?.closest(".space-kp-chart-wrap");
      if (!wrap) return null;
      let tooltip = wrap.querySelector(".space-chart-tooltip");
      if (!tooltip) {
        tooltip = document.createElement("div");
        tooltip.className = "space-chart-tooltip";
        tooltip.hidden = true;
        wrap.appendChild(tooltip);
      }
      return tooltip;
    };

    const bindChartHover = (svg, projected, formatter) => {
      if (!svg || !Array.isArray(projected) || projected.length === 0) {
        return;
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
      };

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

        tooltip.innerHTML = formatter(nearest);
        tooltip.hidden = false;

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
      svg.addEventListener("touchstart", (event) => {
        const touch = event.touches && event.touches[0];
        if (!touch) return;
        onMove(touch);
      }, { passive: true });
      svg.addEventListener("touchend", hide);
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

      const cls = options.pathClass || "space-path-live";
      svg.insertAdjacentHTML("beforeend", `<path d="${path.trim()}" class="${cls}" />`);
      return { projected, yMin, yMax, xOffset, width, yOffset, height };
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
    };

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

      if (kpiCurrent) kpiCurrent.textContent = formatNumber(currentKp, 1);
      if (kpiLevel) kpiLevel.textContent = payload.storm_level || "Unknown";
      if (kpiForecast) kpiForecast.textContent = formatNumber(forecastKp, 1);
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

      const showRealSun = () => {
        if (sunFallback) sunFallback.style.display = "none";
        if (sunImage) sunImage.style.display = "block";
      };

      const showFallbackSun = () => {
        if (sunImage) sunImage.style.display = "none";
        if (sunFallback) sunFallback.style.display = "block";
      };

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
            }, 2400);

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
        listReadings.innerHTML = readings.length === 0
          ? "<li class='timeline-row'>No Kp readings available.</li>"
          : readings.slice().reverse().map((row) => `<li class='timeline-row'><strong>${formatTime(row.time_utc)}</strong><span>Kp ${formatNumber(row.kp_index, 1)}</span></li>`).join("");
      }

      if (listForecast) {
        listForecast.innerHTML = forecastRows.length === 0
          ? "<li class='timeline-row'>No forecast rows available.</li>"
          : forecastRows.slice(0, 10).map((row) => `<li class='timeline-row'><strong>${formatTime(row.time_utc)}</strong><span>Forecast Kp ${formatNumber(row.kp_index, 1)}</span></li>`).join("");
      }

      const flares = Array.isArray(payload.flare_events) ? payload.flare_events : [];
      if (listFlares) {
        listFlares.innerHTML = flares.length === 0
          ? "<li class='timeline-row'>No C/M/X flare peaks detected in this window.</li>"
          : flares.map((row) => {
            const cls = row.class || "--";
            const tier = flareTier(cls);
            return `<li class='timeline-row'><div class='timeline-head'><strong>${formatTime(row.time_utc)}</strong><span class='flare-tag ${tier}'>${cls}</span></div><span>Flux ${typeof row.flux === "number" ? row.flux.toExponential(2) : "--"} W/m²</span></li>`;
          }).join("");
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
      const windRender = renderLineChart(chartWind, windPoints, { pathClass: "space-path-wind" });
      bindChartHover(chartWind, windRender?.projected || [], (point) => {
        const when = point.time_utc ? formatTime(point.time_utc) : "n/a";
        return `<strong>Solar wind</strong><span>${when}</span><span>${formatNumber(point.value, 0)} km/s</span>`;
      });

      const bzSeries = Array.isArray(payload.imf_bz_series_24h) ? payload.imf_bz_series_24h : [];
      const bzPoints = bzSeries
        .map((row) => ({ value: typeof row.bz === "number" ? row.bz : null, time_utc: row.time_utc, label: "IMF Bz" }))
        .filter((row) => row.value !== null);
      const bzRender = renderLineChart(chartBz, bzPoints, { pathClass: "space-path-bz", zeroAt: 0 });
      bindChartHover(chartBz, bzRender?.projected || [], (point) => {
        const when = point.time_utc ? formatTime(point.time_utc) : "n/a";
        return `<strong>IMF Bz</strong><span>${when}</span><span>${formatNumber(point.value, 1)} nT</span>`;
      });
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
    window.setInterval(() => {
      if (document.hidden) return;
      void refresh();
    }, REFRESH_MS);
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
