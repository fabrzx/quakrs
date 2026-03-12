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
    <p class="kpi-label">Average Magnitude</p>
    <p id="energy-kpi-average" class="kpi-value">--</p>
    <p class="kpi-note">Across valid magnitude rows</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Momentum</p>
    <p id="energy-kpi-momentum" class="kpi-value">--</p>
    <p id="energy-kpi-source" class="kpi-note">Loading source...</p>
  </article>
</section>

<section class="panel panel-charts">
  <article class="card">
    <div class="feed-head">
      <h3>Magnitude Distribution</h3>
    </div>
    <div id="mag-chart" class="bars"></div>
  </article>
  <article class="card">
    <div class="feed-head">
      <h3>Activity by Hour (UTC)</h3>
      <p class="feed-meta">Last 24 hours</p>
    </div>
    <div id="hourly-chart" class="bars bars-hourly"></div>
  </article>
  <article class="card">
    <div class="feed-head">
      <h3>Top Regions</h3>
      <p class="feed-meta">Most active places now</p>
    </div>
    <ul id="regions-list" class="regions-list">
      <li>No data loaded yet.</li>
    </ul>
  </article>
</section>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Energy Story</h3>
    <p id="energy-story" class="insight-lead">Building a short operational summary...</p>
  </article>
  <article class="card page-card">
    <h3>Regional Drift</h3>
    <ul id="energy-regional-drift" class="events-list">
      <li class="event-item">Loading regional drift...</li>
    </ul>
  </article>
  <article class="card page-card">
    <h3>Operator Cues</h3>
    <ul id="energy-cues" class="events-list">
      <li class="event-item">Loading recommended checks...</li>
    </ul>
  </article>
</section>

<template id="bar-template">
  <div class="bar-row">
    <div class="bar-label"></div>
    <div class="bar-track">
      <div class="bar-fill"></div>
    </div>
    <div class="bar-value"></div>
  </div>
</template>

<script>
  (() => {
    const kpiEvents = document.querySelector("#energy-kpi-events");
    const kpiSignificant = document.querySelector("#energy-kpi-significant");
    const kpiAverage = document.querySelector("#energy-kpi-average");
    const kpiMomentum = document.querySelector("#energy-kpi-momentum");
    const kpiSource = document.querySelector("#energy-kpi-source");
    const story = document.querySelector("#energy-story");
    const driftList = document.querySelector("#energy-regional-drift");
    const cuesList = document.querySelector("#energy-cues");

    const parseRegion = (place) => {
      if (!place) return "Unknown";
      if (String(place).includes(" of ")) {
        return String(place).split(" of ").slice(-1)[0].trim();
      }
      const bits = String(place).split(",");
      return bits[bits.length - 1].trim() || String(place);
    };

    const setError = () => {
      if (story) story.textContent = "Unable to build the energy narrative right now.";
      if (driftList) driftList.innerHTML = "<li class='event-item'>Regional drift unavailable.</li>";
      if (cuesList) cuesList.innerHTML = "<li class='event-item'>Operational cues unavailable.</li>";
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

        if (kpiEvents) kpiEvents.textContent = String(events.length);
        if (kpiSignificant) kpiSignificant.textContent = String(significant.length);
        if (kpiAverage) kpiAverage.textContent = avgMag !== null ? avgMag.toFixed(2) : "--";
        if (kpiMomentum) {
          if (momentumDelta > 0) kpiMomentum.textContent = `+${momentumDelta}`;
          else if (momentumDelta < 0) kpiMomentum.textContent = String(momentumDelta);
          else kpiMomentum.textContent = "Stable";
        }
        if (kpiSource) {
          const provider = Array.isArray(payload.providers) && payload.providers.length > 0
            ? payload.providers.join(" + ")
            : (payload.provider || "Quakrs API");
          kpiSource.textContent = `${provider}${payload.from_cache ? " (cache)" : ""}`;
        }

        if (story) {
          const strongest = withMag.slice().sort((a, b) => b.magnitude - a.magnitude)[0] || null;
          const strongestLabel = strongest ? `Strongest ${strongest.magnitude.toFixed(1)} in ${strongest.place || "Unknown area"}.` : "No strongest event available.";
          const momentumLabel = momentumDelta > 0
            ? `Pulse is rising in the last hour (+${momentumDelta}).`
            : momentumDelta < 0
              ? `Pulse is easing compared to previous hour (${momentumDelta}).`
              : "Pulse is stable hour-over-hour.";
          story.textContent = `${strongestLabel} ${momentumLabel}`;
        }

        if (driftList) {
          driftList.innerHTML = topRegions.length > 0
            ? topRegions.map(([region, count]) => `<li class="event-item"><strong>${region}</strong><br />${count} events in current window</li>`).join("")
            : "<li class='event-item'>No regional drift available.</li>";
        }

        if (cuesList) {
          const cues = [];
          if (significant.length >= 5) {
            cues.push("Elevated strong-event volume: monitor tsunami and bulletin channels.");
          } else {
            cues.push("Strong-event volume is moderate: keep routine cadence checks.");
          }
          if (momentumDelta > 0) {
            cues.push("Rising hourly pulse: watch cluster and regional spread updates.");
          } else if (momentumDelta < 0) {
            cues.push("Cooling hourly pulse: prioritize verification of strongest events.");
          } else {
            cues.push("Stable pulse: maintain standard monitoring rotation.");
          }
          if (topRegions.length > 0) {
            cues.push(`Primary focus regions: ${topRegions.slice(0, 2).map((row) => row[0]).join(" and ")}.`);
          }
          cuesList.innerHTML = cues.map((cue) => `<li class="event-item">${cue}</li>`).join("");
        }
      } catch (error) {
        setError();
      }
    };

    load();
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
