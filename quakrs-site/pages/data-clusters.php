<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Data Clusters';
$pageDescription = 'Global tremor clusters and regional signal analysis.';
$currentPage = 'data-clusters';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Data / Clusters</p>
    <h1>Tremor Clusters &amp; Signals.</h1>
    <p class="sub">Track broad tremor behavior and emerging regional clusters with rapid context.</p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Signals (24h)</p>
    <p id="clusters-kpi-signals" class="kpi-value">--</p>
    <p class="kpi-note">Low-magnitude tremor detections</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Active Clusters</p>
    <p id="clusters-kpi-clusters" class="kpi-value">--</p>
    <p class="kpi-note">Top regional concentration zones</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Peak Hour (UTC)</p>
    <p id="clusters-kpi-peak" class="kpi-value">--</p>
    <p id="clusters-kpi-peak-note" class="kpi-note">Loading trend...</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Method</p>
    <p class="kpi-value">M3.5</p>
    <p id="clusters-kpi-method" class="kpi-note">Loading source...</p>
  </article>
</section>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Cluster Radar</h3>
    <ul id="clusters-radar" class="events-list">
      <li class="event-item">Loading cluster radar...</li>
    </ul>
  </article>
  <article class="card page-card">
    <h3>Intensity Bands</h3>
    <ul id="clusters-bands" class="events-list">
      <li class="event-item">Loading intensity bands...</li>
    </ul>
  </article>
  <article class="card page-card">
    <h3>Recent Signals</h3>
    <ul id="clusters-recent" class="events-list">
      <li class="event-item">Loading recent signals...</li>
    </ul>
  </article>
</section>

<script>
  (() => {
    const kpiSignals = document.querySelector("#clusters-kpi-signals");
    const kpiClusters = document.querySelector("#clusters-kpi-clusters");
    const kpiPeak = document.querySelector("#clusters-kpi-peak");
    const kpiPeakNote = document.querySelector("#clusters-kpi-peak-note");
    const kpiMethod = document.querySelector("#clusters-kpi-method");
    const radarList = document.querySelector("#clusters-radar");
    const bandsList = document.querySelector("#clusters-bands");
    const recentList = document.querySelector("#clusters-recent");

    const safeTime = (iso) => (iso ? new Date(iso).toLocaleString([], { month: "short", day: "2-digit", hour: "2-digit", minute: "2-digit" }) : "n/a");

    const setError = () => {
      const fallback = "<li class='event-item'>Cluster data unavailable right now.</li>";
      if (radarList) radarList.innerHTML = fallback;
      if (bandsList) bandsList.innerHTML = fallback;
      if (recentList) recentList.innerHTML = fallback;
      if (kpiMethod) kpiMethod.textContent = "Source unavailable";
    };

    const load = async () => {
      try {
        const response = await fetch("/api/tremors.php", { headers: { Accept: "application/json" } });
        if (!response.ok) {
          throw new Error("Tremor request failed");
        }

        const payload = await response.json();
        const clusters = Array.isArray(payload.clusters) ? payload.clusters : [];
        const events = Array.isArray(payload.events) ? payload.events : [];

        if (kpiSignals) kpiSignals.textContent = String(typeof payload.signals_count === "number" ? payload.signals_count : events.length);
        if (kpiClusters) kpiClusters.textContent = String(typeof payload.clusters_count === "number" ? payload.clusters_count : clusters.length);
        if (kpiPeak) kpiPeak.textContent = payload.peak_hour_utc || "--:00";
        if (kpiPeakNote) {
          const count = typeof payload.peak_hour_count === "number" ? payload.peak_hour_count : 0;
          kpiPeakNote.textContent = `${count} signals in peak slot`;
        }
        if (kpiMethod) {
          const mode = payload.from_cache ? "cache" : "live";
          kpiMethod.textContent = `${payload.provider || "Tremor feed"} (${mode})`;
        }

        if (radarList) {
          const rows = clusters.slice(0, 8);
          radarList.innerHTML = rows.length > 0
            ? rows.map((row) => `<li class="event-item"><strong>${row.region || "Unknown"}</strong><br />${row.count || 0} signals · max ${typeof row.max_magnitude === "number" ? `M${row.max_magnitude.toFixed(1)}` : "M?"}</li>`).join("")
            : "<li class='event-item'>No active clusters in current window.</li>";
        }

        if (bandsList) {
          const buckets = [
            { label: "M0.0-1.4", count: 0 },
            { label: "M1.5-2.4", count: 0 },
            { label: "M2.5-3.0", count: 0 },
            { label: "M3.1-3.5", count: 0 },
          ];
          events.forEach((event) => {
            const mag = typeof event.magnitude === "number" ? event.magnitude : -1;
            if (mag < 0) return;
            if (mag < 1.5) buckets[0].count += 1;
            else if (mag < 2.5) buckets[1].count += 1;
            else if (mag < 3.1) buckets[2].count += 1;
            else buckets[3].count += 1;
          });

          bandsList.innerHTML = buckets
            .map((bucket) => `<li class="event-item"><strong>${bucket.label}</strong><br />${bucket.count} signals</li>`)
            .join("");
        }

        if (recentList) {
          const rows = events.slice(0, 8);
          recentList.innerHTML = rows.length > 0
            ? rows.map((row) => {
              const mag = typeof row.magnitude === "number" ? `M${row.magnitude.toFixed(1)}` : "M?";
              const depth = typeof row.depth_km === "number" ? `${row.depth_km.toFixed(1)} km` : "n/a";
              return `<li class="event-item"><strong>${mag}</strong> ${row.place || "Unknown"}<br />${safeTime(row.event_time_utc)} · depth ${depth}</li>`;
            }).join("")
            : "<li class='event-item'>No recent tremor signals.</li>";
        }
      } catch (error) {
        setError();
      }
    };

    load();
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
