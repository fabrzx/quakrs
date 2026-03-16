<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Data Energy';
$pageDescription = 'Seismic energy trends and activity analytics.';
$currentPage = 'data-energy';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Data / Energy</p>
    <h1>Seismic Trends &amp; Energy Pulse.</h1>
    <p class="sub">Magnitude and hourly activity analytics for rapid global interpretation.</p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Events (24h)</p>
    <p id="energy-kpi-events" class="kpi-value">--</p>
    <p class="kpi-note">Merged earthquake feed volume</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">M5+ Events</p>
    <p id="energy-kpi-significant" class="kpi-value">--</p>
    <p class="kpi-note">Potentially impactful activity</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Estimated Energy (24h)</p>
    <a id="energy-kpi-average" class="kpi-value" href="/about-energy.php" title="How seismic energy is estimated">--</a>
    <p class="kpi-note">Derived from magnitude mix · <a href="/about-energy.php" class="inline-link" style="margin-top:0">How this is measured</a></p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Baseline Delta</p>
    <p id="energy-kpi-momentum" class="kpi-value">--</p>
    <p id="energy-kpi-source" class="kpi-note">Today vs 30d average</p>
  </article>
</section>

<section class="panel panel-charts">
  <article class="card">
    <div class="feed-head">
      <h3>Magnitude Distribution</h3>
    </div>
    <div id="energy-mag-chart" class="bars-vertical bars-magnitude"></div>
  </article>
  <article class="card">
    <div class="feed-head">
      <h3>Activity by Hour (UTC)</h3>
      <p class="feed-meta">Last 24 hours</p>
    </div>
    <div id="energy-hourly-chart" class="bars-vertical bars-hourly-vertical"></div>
  </article>
  <article class="card">
    <div class="feed-head">
      <h3>Baseline Check</h3>
      <p id="energy-baseline-meta" class="feed-meta">Today against rolling 30-day mean</p>
    </div>
    <div id="energy-baseline-chart" class="bars"></div>
  </article>
</section>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Operational Story</h3>
    <p id="energy-story" class="insight-lead">Building a short operational summary...</p>
  </article>
  <article class="card page-card">
    <h3>7-Day Pulse</h3>
    <p id="energy-anomaly-pill" class="insight-pill">Loading baseline state...</p>
    <div id="energy-week-chart" class="bars-vertical bars-hourly-vertical"></div>
  </article>
  <article class="card page-card">
    <h3>Operator Cues</h3>
    <ul id="energy-cues" class="events-list">
      <li class="event-item">Loading recommended checks...</li>
    </ul>
  </article>
</section>

<script>
  (() => {
    const kpiEvents = document.querySelector("#energy-kpi-events");
    const kpiSignificant = document.querySelector("#energy-kpi-significant");
    const kpiAverage = document.querySelector("#energy-kpi-average");
    const kpiMomentum = document.querySelector("#energy-kpi-momentum");
    const kpiSource = document.querySelector("#energy-kpi-source");
    const story = document.querySelector("#energy-story");
    const baselineChart = document.querySelector("#energy-baseline-chart");
    const baselineMeta = document.querySelector("#energy-baseline-meta");
    const weekChart = document.querySelector("#energy-week-chart");
    const anomalyPill = document.querySelector("#energy-anomaly-pill");
    const cuesList = document.querySelector("#energy-cues");
    const magChart = document.querySelector("#energy-mag-chart");
    const hourlyChart = document.querySelector("#energy-hourly-chart");

    const parseRegion = (place) => {
      if (!place) return "Unknown";
      if (String(place).includes(" of ")) {
        return String(place).split(" of ").slice(-1)[0].trim();
      }
      const bits = String(place).split(",");
      return bits[bits.length - 1].trim() || String(place);
    };

    const estimateEnergyJ = (magnitude) => {
      // Gutenberg-Richter empirical relation, joules estimate.
      return 10 ** ((1.5 * magnitude) + 4.8);
    };

    const formatEnergy = (joules) => {
      if (!Number.isFinite(joules) || joules <= 0) return "--";
      if (joules >= 1e15) return `${(joules / 1e15).toFixed(2)} PJ`;
      if (joules >= 1e12) return `${(joules / 1e12).toFixed(2)} TJ`;
      if (joules >= 1e9) return `${(joules / 1e9).toFixed(2)} GJ`;
      return `${joules.toExponential(2)} J`;
    };

    const renderRowsChart = (container, rows) => {
      if (!container) return;
      const maxValue = rows.reduce((max, row) => Math.max(max, row.value), 0) || 1;
      container.innerHTML = rows.map((row) => {
        const width = Math.max(6, Math.round((row.value / maxValue) * 100));
        return `
          <div class="bar-row">
            <div class="bar-label">${row.label}</div>
            <div class="bar-track"><div class="bar-fill" style="width:${width}%"></div></div>
            <div class="bar-value">${row.display}</div>
          </div>
        `;
      }).join("");
    };

    const renderVerticalChart = (container, rows) => {
      if (!container) return;
      const maxValue = rows.reduce((max, row) => Math.max(max, row.value), 0) || 1;
      container.style.setProperty("--bar-count", String(rows.length));
      container.innerHTML = rows.map((row) => {
        const height = Math.max(4, Math.round((row.value / maxValue) * 100));
        return `
          <div class="bar-col">
            <div class="bar-col-value">${row.display}</div>
            <div class="bar-col-track">
              <div class="bar-col-fill" style="height:${height}%;background:${row.color || "#5de4c7"}"></div>
            </div>
            <div class="bar-col-label">${row.label}</div>
          </div>
        `;
      }).join("");
    };

    const BASELINE_MIN_MAG = 2.5;

    const buildBaselineUrl = (minMagnitude) => {
      const params = new URLSearchParams();
      params.set("min_magnitude", minMagnitude.toFixed(1));
      return `/api/energy-baseline.php?${params.toString()}`;
    };

    const fetchBaselineModel = async (minMagnitude) => {
      const response = await fetch(buildBaselineUrl(minMagnitude), { headers: { Accept: "application/json" } });
      if (!response.ok) throw new Error("Baseline request failed");
      const payload = await response.json();
      if (!payload || payload.ok !== true) throw new Error("Baseline payload invalid");
      return payload;
    };

    const setError = () => {
      if (story) story.textContent = "Unable to build the energy narrative right now.";
      if (cuesList) cuesList.innerHTML = "<li class='event-item'>Operational cues unavailable.</li>";
      if (baselineChart) baselineChart.innerHTML = "<div class='event-item'>Baseline comparison unavailable.</div>";
      if (weekChart) weekChart.innerHTML = "<div class='event-item'>7-day trend unavailable.</div>";
      if (anomalyPill) anomalyPill.textContent = "Baseline state unavailable";
      if (kpiSource) kpiSource.textContent = "Source unavailable";
    };

    const load = async () => {
      try {
        const response = await fetch("/api/earthquakes.php", { headers: { Accept: "application/json" } });
        if (!response.ok) throw new Error("Request failed");

        const payload = await response.json();
        const events = Array.isArray(payload.events) ? payload.events : [];
        const withMag = events.filter((row) => typeof row.magnitude === "number");
        const significant = withMag.filter((row) => row.magnitude >= 5);
        const avgMag = withMag.length > 0
          ? withMag.reduce((sum, row) => sum + row.magnitude, 0) / withMag.length
          : null;
        const totalEnergyJ = withMag.reduce((sum, row) => sum + estimateEnergyJ(row.magnitude), 0);
        const meanEnergyPerEventJ = withMag.length > 0 ? (totalEnergyJ / withMag.length) : 0;

        const now = Date.now();
        const last1h = events.filter((row) => {
          const ts = row.event_time_utc ? Date.parse(row.event_time_utc) : NaN;
          return Number.isFinite(ts) && ts >= now - (60 * 60 * 1000);
        }).length;
        const prev1h = events.filter((row) => {
          const ts = row.event_time_utc ? Date.parse(row.event_time_utc) : NaN;
          return Number.isFinite(ts) && ts < now - (60 * 60 * 1000) && ts >= now - (2 * 60 * 60 * 1000);
        }).length;
        const momentumDelta = last1h - prev1h;

        const regionCounter = new Map();
        events.forEach((row) => {
          const region = parseRegion(row.place);
          regionCounter.set(region, (regionCounter.get(region) || 0) + 1);
        });
        const topRegions = [...regionCounter.entries()].sort((a, b) => b[1] - a[1]).slice(0, 5);
        const topRegionLabel = topRegions.length > 0 ? `${topRegions[0][0]} (${topRegions[0][1]})` : "No dominant region";

        const magBins = [
          { label: "M0-1", value: 0, color: "#22d3ee" },
          { label: "M1-2", value: 0, color: "#5de4c7" },
          { label: "M2-3", value: 0, color: "#94f1dd" },
          { label: "M3-4", value: 0, color: "#f7d21e" },
          { label: "M4-5", value: 0, color: "#ff895b" },
          { label: "M5+", value: 0, color: "#ff5f45" },
        ];
        withMag.forEach((row) => {
          const mag = row.magnitude;
          if (mag < 1) magBins[0].value += 1;
          else if (mag < 2) magBins[1].value += 1;
          else if (mag < 3) magBins[2].value += 1;
          else if (mag < 4) magBins[3].value += 1;
          else if (mag < 5) magBins[4].value += 1;
          else magBins[5].value += 1;
        });

        const hourly = Array.from({ length: 24 }, (_, idx) => ({ label: String(idx).padStart(2, "0"), value: 0 }));
        events.forEach((row) => {
          const ts = row.event_time_utc ? Date.parse(row.event_time_utc) : NaN;
          if (!Number.isFinite(ts)) return;
          const hour = new Date(ts).getUTCHours();
          if (!hourly[hour]) return;
          hourly[hour].value += 1;
        });

        if (kpiEvents) kpiEvents.textContent = String(events.length);
        if (kpiSignificant) kpiSignificant.textContent = String(significant.length);
        if (kpiAverage) {
          kpiAverage.textContent = formatEnergy(totalEnergyJ);
          kpiAverage.style.color = totalEnergyJ >= 8e12 ? "#ff895b" : (totalEnergyJ >= 2e12 ? "#f7d21e" : "");
        }

        const provider = Array.isArray(payload.providers) && payload.providers.length > 0
          ? payload.providers.join(" + ")
          : (payload.provider || "Quakrs API");

        if (story) {
          const strongest = withMag.slice().sort((a, b) => b.magnitude - a.magnitude)[0] || null;
          const strongestLabel = strongest ? `Strongest ${strongest.magnitude.toFixed(1)} in ${strongest.place || "Unknown area"}.` : "No strongest event available.";
          const energyLabel = `Estimated total energy in the current 24h window: ${formatEnergy(totalEnergyJ)} (mean ${formatEnergy(meanEnergyPerEventJ)} per event).`;
          story.textContent = `${strongestLabel} ${energyLabel} Most active region now: ${topRegionLabel}.`;
        }

        if (cuesList) {
          const cues = [];
          if (significant.length >= 5) {
            cues.push("Elevated strong-event volume: monitor tsunami and bulletin channels.");
          } else {
            cues.push("Strong-event volume is moderate: keep routine cadence checks.");
          }
          if (totalEnergyJ >= 8e12) {
            cues.push("High cumulative energy release: prioritize verification of strongest sequences.");
          } else if (totalEnergyJ >= 2e12) {
            cues.push("Moderate-high energy release: watch if dominant region keeps intensifying.");
          } else {
            cues.push("Energy release remains contained in the current cycle.");
          }
          if (momentumDelta > 0) {
            cues.push(`Short-term pulse rising (+${momentumDelta}) over the last hour.`);
          } else if (momentumDelta < 0) {
            cues.push(`Short-term pulse easing (${momentumDelta}) compared to previous hour.`);
          } else {
            cues.push("Short-term pulse stable hour-over-hour.");
          }
          cuesList.innerHTML = cues.map((cue) => `<li class="event-item">${cue}</li>`).join("");
        }

        renderVerticalChart(magChart, magBins.map((row) => ({
          ...row,
          display: String(row.value),
        })));

        renderVerticalChart(hourlyChart, hourly.map((row) => ({
          ...row,
          display: String(row.value),
          color: row.value > 0 ? "#5de4c7" : "#6e7e96",
        })));

        renderRowsChart(baselineChart, [
          { label: "Today", value: events.length, display: String(events.length) },
          { label: "30d avg", value: Math.max(1, events.length * 0.7), display: "..." },
        ]);
        renderVerticalChart(weekChart, Array.from({ length: 7 }, (_, idx) => ({
          label: idx === 6 ? "Today" : `D-${6 - idx}`,
          value: 0,
          display: "...",
          color: "#6e7e96",
        })));

        if (kpiSource) {
          kpiSource.textContent = `${provider}${payload.from_cache ? " (cache)" : ""} · baseline M${BASELINE_MIN_MAG.toFixed(1)}+ loading`;
        }
        if (kpiMomentum) kpiMomentum.textContent = "--";
        if (anomalyPill) {
          anomalyPill.textContent = "Baseline model loading...";
          anomalyPill.style.borderColor = "";
          anomalyPill.style.color = "";
        }

        try {
          const baselinePayload = await fetchBaselineModel(BASELINE_MIN_MAG);
          const todayCount = Number(baselinePayload.today_count || 0);
          const baselineDailyAvg = Number(baselinePayload.baseline_daily_avg || 0);
          const baselineDeltaPct = Number(baselinePayload.baseline_delta_pct || 0);
          const baselineState = String(baselinePayload.baseline_state || "Within normal");
          const isProxy = String(baselinePayload.source || "") === "live-proxy";
          const weekRowsRaw = Array.isArray(baselinePayload.week_daily_counts) ? baselinePayload.week_daily_counts : [];
          const weekRows = weekRowsRaw.map((row) => ({
            label: String(row.label || "").trim() || "n/a",
            value: Number(row.value || 0),
            display: String(Number(row.value || 0)),
            color: "#5de4c7",
          }));
          const stateColor = baselineDeltaPct >= 35 ? "#ff5f45"
            : baselineDeltaPct <= -25 ? "#22d3ee"
              : "#5de4c7";

          if (kpiMomentum) {
            const signed = baselineDeltaPct >= 0 ? `+${baselineDeltaPct.toFixed(0)}%` : `${baselineDeltaPct.toFixed(0)}%`;
            kpiMomentum.textContent = signed;
            kpiMomentum.style.color = stateColor;
          }
          if (kpiSource) {
            const cacheLabel = baselinePayload.from_cache ? "cache" : "fresh";
            const staleLabel = baselinePayload.stale_cache ? " stale" : "";
            const modelSource = baselinePayload.source ? ` ${baselinePayload.source}` : "";
            kpiSource.textContent = `${provider}${payload.from_cache ? " (cache)" : ""} · ${baselineState} (baseline M${BASELINE_MIN_MAG.toFixed(1)}+ · ${cacheLabel}${staleLabel}${modelSource})`;
          }
          if (anomalyPill) {
            const baselineLabel = isProxy ? "24h reference" : "30d avg";
            anomalyPill.textContent = `${baselineState} · M${BASELINE_MIN_MAG.toFixed(1)}+ today ${todayCount} vs ${baselineLabel} ${baselineDailyAvg.toFixed(1)}`;
            anomalyPill.style.borderColor = stateColor;
            anomalyPill.style.color = stateColor;
          }
          if (baselineMeta) {
            baselineMeta.textContent = isProxy
              ? "Today against recent 24h proxy baseline"
              : "Today against rolling 30-day mean";
          }
          if (story) {
            const strongest = withMag.slice().sort((a, b) => b.magnitude - a.magnitude)[0] || null;
            const strongestLabel = strongest ? `Strongest ${strongest.magnitude.toFixed(1)} in ${strongest.place || "Unknown area"}.` : "No strongest event available.";
            const baselineLabel = baselineDeltaPct >= 35
              ? `Current activity is above baseline by ${baselineDeltaPct.toFixed(0)}%.`
              : baselineDeltaPct <= -25
                ? `Current activity is below baseline by ${Math.abs(baselineDeltaPct).toFixed(0)}%.`
                : "Current activity is aligned with baseline.";
            const energyLabel = `Estimated total energy in the current 24h window: ${formatEnergy(totalEnergyJ)} (mean ${formatEnergy(meanEnergyPerEventJ)} per event).`;
            story.textContent = `${strongestLabel} ${baselineLabel} ${energyLabel} Most active region now: ${topRegionLabel}.`;
          }
          renderRowsChart(baselineChart, [
            { label: `Today (M${BASELINE_MIN_MAG.toFixed(1)}+)`, value: todayCount, display: String(todayCount) },
            { label: isProxy ? "24h ref" : "30d avg", value: baselineDailyAvg, display: baselineDailyAvg.toFixed(1) },
          ]);
          if (weekRows.length > 0) {
            renderVerticalChart(weekChart, weekRows);
          }
        } catch (baselineError) {
          if (kpiMomentum) {
            kpiMomentum.textContent = "n/a";
            kpiMomentum.style.color = "";
          }
          if (kpiSource) {
            kpiSource.textContent = `${provider}${payload.from_cache ? " (cache)" : ""} · baseline unavailable`;
          }
          if (anomalyPill) anomalyPill.textContent = "Baseline temporarily unavailable";
        }
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
