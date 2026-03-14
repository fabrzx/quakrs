<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Data Reports';
$pageDescription = 'Operational reports generated from institutional monitoring feeds.';
$currentPage = 'data-reports';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Data / Reports</p>
    <h1>Institutional Situation Reports.</h1>
    <p class="sub">Rolling operational summaries built from source feeds without editorial commentary.</p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Earthquakes (24h)</p>
    <p id="reports-kpi-earthquakes" class="kpi-value">--</p>
    <p class="kpi-note">From merged earthquake feed</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Volcano Reports</p>
    <p id="reports-kpi-volcanoes" class="kpi-value">--</p>
    <p class="kpi-note">Weekly bulletin entries</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Tsunami Alerts</p>
    <p id="reports-kpi-tsunami" class="kpi-value">--</p>
    <p class="kpi-note">Current active alerts</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Space Weather</p>
    <p id="reports-kpi-space" class="kpi-value">--</p>
    <p id="reports-kpi-source" class="kpi-note">Loading source status...</p>
  </article>
</section>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Earthquake Report</h3>
    <ul id="reports-earthquakes" class="events-list">
      <li class="event-item">Loading earthquake report...</li>
    </ul>
    <a class="inline-link" href="/earthquakes.php">Open monitor</a>
  </article>
  <article class="card page-card">
    <h3>Volcano Report</h3>
    <ul id="reports-volcanoes" class="events-list">
      <li class="event-item">Loading volcano report...</li>
    </ul>
    <a class="inline-link" href="/volcanoes.php">Open monitor</a>
  </article>
  <article class="card page-card">
    <h3>Tsunami / Space Weather</h3>
    <ul id="reports-hazards" class="events-list">
      <li class="event-item">Loading hazard report...</li>
    </ul>
    <a class="inline-link" href="/resources-bulletins.php">Open bulletins</a>
  </article>
</section>

<script>
  (() => {
    const kpiEq = document.querySelector("#reports-kpi-earthquakes");
    const kpiVolc = document.querySelector("#reports-kpi-volcanoes");
    const kpiTsu = document.querySelector("#reports-kpi-tsunami");
    const kpiSpace = document.querySelector("#reports-kpi-space");
    const kpiSource = document.querySelector("#reports-kpi-source");
    const eqList = document.querySelector("#reports-earthquakes");
    const volcList = document.querySelector("#reports-volcanoes");
    const hazList = document.querySelector("#reports-hazards");

    const safeTime = (iso) => iso
      ? new Date(iso).toLocaleString([], { month: "short", day: "2-digit", hour: "2-digit", minute: "2-digit" })
      : "n/a";

    const setError = () => {
      const fallback = "<li class='event-item'>Unable to load report section right now.</li>";
      if (eqList) eqList.innerHTML = fallback;
      if (volcList) volcList.innerHTML = fallback;
      if (hazList) hazList.innerHTML = fallback;
      if (kpiSource) kpiSource.textContent = "Some sources unavailable";
    };

    const load = async () => {
      try {
        const [eqRes, volcRes, tsuRes, spaceRes, bulletinsRes] = await Promise.all([
          fetch("/api/earthquakes.php", { headers: { Accept: "application/json" } }),
          fetch("/api/volcanoes.php", { headers: { Accept: "application/json" } }),
          fetch("/api/tsunami.php", { headers: { Accept: "application/json" } }),
          fetch("/api/space-weather.php", { headers: { Accept: "application/json" } }),
          fetch("/api/bulletins.php", { headers: { Accept: "application/json" } }),
        ]);

        if (!eqRes.ok || !volcRes.ok || !tsuRes.ok || !spaceRes.ok || !bulletinsRes.ok) {
          throw new Error("One or more report requests failed");
        }

        const [eq, volc, tsu, space, bulletins] = await Promise.all([
          eqRes.json(),
          volcRes.json(),
          tsuRes.json(),
          spaceRes.json(),
          bulletinsRes.json(),
        ]);

        const eqEvents = Array.isArray(eq.events) ? eq.events : [];
        const strongest = eqEvents.reduce((best, row) => {
          const mag = typeof row.magnitude === "number" ? row.magnitude : -1;
          return mag > best.magnitude ? { magnitude: mag, place: row.place || "Unknown", time: row.event_time_utc } : best;
        }, { magnitude: -1, place: "Unknown", time: null });

        const volcEvents = Array.isArray(volc.events) ? volc.events : [];
        const latestVolcano = volcEvents[0] || null;

        const tsunamiAlerts = Array.isArray(tsu.alerts) ? tsu.alerts : [];
        const bulletinsRows = Array.isArray(bulletins.bulletins) ? bulletins.bulletins : [];

        if (kpiEq) kpiEq.textContent = String(typeof eq.events_count === "number" ? eq.events_count : eqEvents.length);
        if (kpiVolc) kpiVolc.textContent = String(typeof volc.reports_count === "number" ? volc.reports_count : volcEvents.length);
        if (kpiTsu) kpiTsu.textContent = String(typeof tsu.alerts_count === "number" ? tsu.alerts_count : tsunamiAlerts.length);
        if (kpiSpace) kpiSpace.textContent = typeof space.kp_index_current === "number" ? space.kp_index_current.toFixed(1) : "--";
        if (kpiSource) kpiSource.textContent = "Sources: USGS/INGV/EMSC, Smithsonian GVP, NOAA";

        if (eqList) {
          eqList.innerHTML = `
            <li class="event-item"><strong>Strongest:</strong> ${strongest.magnitude >= 0 ? `M${strongest.magnitude.toFixed(1)}` : "M?"} ${strongest.place} (${safeTime(strongest.time)})</li>
            <li class="event-item"><strong>Significant:</strong> ${typeof eq.significant_count === "number" ? eq.significant_count : 0} events M5+</li>
            <li class="event-item"><strong>Regions:</strong> ${typeof eq.regions_count === "number" ? eq.regions_count : 0} active regions</li>
          `;
        }

        if (volcList) {
          volcList.innerHTML = `
            <li class="event-item"><strong>Reports:</strong> ${typeof volc.reports_count === "number" ? volc.reports_count : volcEvents.length} latest items</li>
            <li class="event-item"><strong>New eruptive:</strong> ${typeof volc.new_eruptive_count === "number" ? volc.new_eruptive_count : 0}</li>
            <li class="event-item"><strong>Latest:</strong> ${latestVolcano ? `${latestVolcano.volcano || latestVolcano.title || "Unknown"} (${safeTime(latestVolcano.event_time_utc)})` : "n/a"}</li>
          `;
        }

        if (hazList) {
          hazList.innerHTML = `
            <li class="event-item"><strong>Tsunami level:</strong> ${tsu.highest_level || "None"} (${tsunamiAlerts.length} active)</li>
            <li class="event-item"><strong>Space weather:</strong> ${space.storm_level || "Unknown"} | Kp max 24h ${typeof space.kp_index_max_24h === "number" ? space.kp_index_max_24h.toFixed(1) : "--"}</li>
            <li class="event-item"><strong>Institutional bulletins:</strong> ${bulletinsRows.length} entries</li>
          `;
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
