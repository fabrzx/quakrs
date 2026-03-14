<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Tsunami Alerts';
$pageDescription = 'Active tsunami alerts with warning level, region and issue time.';
$currentPage = 'tsunami-alerts';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Monitors / Tsunami Alerts</p>
    <h1>Active Tsunami Alert Monitor.</h1>
    <p class="sub">Operational view of active tsunami bulletins, warning level, issue time and target regions.</p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Active Alerts</p>
    <p id="tsunami-kpi-total" class="kpi-value">--</p>
    <p class="kpi-note">Current open bulletins</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Highest Level</p>
    <p id="tsunami-kpi-level" class="kpi-value">--</p>
    <p class="kpi-note">Most severe active class</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Regions</p>
    <p id="tsunami-kpi-regions" class="kpi-value">--</p>
    <p class="kpi-note">Unique impacted areas</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Last Update</p>
    <p id="tsunami-kpi-updated" class="kpi-value">--</p>
    <p id="tsunami-kpi-source" class="kpi-note">Loading source...</p>
  </article>
</section>

<section class="panel">
  <article class="card recent-card">
    <div class="feed-head">
      <h3>Active Bulletins</h3>
      <p class="feed-meta">Newest first</p>
    </div>
    <ul id="tsunami-alerts-list" class="events-list">
      <li class="event-item">Loading tsunami alerts...</li>
    </ul>
  </article>
</section>

<script>
  (() => {
    const list = document.querySelector("#tsunami-alerts-list");
    const kpiTotal = document.querySelector("#tsunami-kpi-total");
    const kpiLevel = document.querySelector("#tsunami-kpi-level");
    const kpiRegions = document.querySelector("#tsunami-kpi-regions");
    const kpiUpdated = document.querySelector("#tsunami-kpi-updated");
    const kpiSource = document.querySelector("#tsunami-kpi-source");

    const setError = (message) => {
      if (list) {
        list.innerHTML = `<li class="event-item">${message}</li>`;
      }
      if (kpiSource) {
        kpiSource.textContent = "Source unavailable";
      }
    };

    const load = async () => {
      try {
        const response = await fetch("/api/tsunami.php", { headers: { Accept: "application/json" } });
        if (!response.ok) {
          throw new Error("Request failed");
        }

        const payload = await response.json();
        const alerts = Array.isArray(payload.alerts) ? payload.alerts : [];
        const updatedAt = payload.generated_at ? new Date(payload.generated_at).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }) : "--";

        if (kpiTotal) kpiTotal.textContent = String(typeof payload.alerts_count === "number" ? payload.alerts_count : alerts.length);
        if (kpiLevel) kpiLevel.textContent = payload.highest_level || "None";
        if (kpiRegions) kpiRegions.textContent = String(typeof payload.regions_count === "number" ? payload.regions_count : 0);
        if (kpiUpdated) kpiUpdated.textContent = updatedAt;
        if (kpiSource) kpiSource.textContent = `Source: ${payload.provider || "NOAA / NWS"}${payload.from_cache ? " (cache)" : ""}`;

        if (!list) {
          return;
        }

        if (alerts.length === 0) {
          list.innerHTML = "<li class='event-item'>No active tsunami alerts.</li>";
          return;
        }

        list.innerHTML = alerts.slice(0, 20).map((alert) => {
          const when = alert.issued_at_utc
            ? new Date(alert.issued_at_utc).toLocaleString([], { month: "short", day: "2-digit", hour: "2-digit", minute: "2-digit" })
            : "n/a";
          const bulletin = alert.source_bulletin ? `<a class="inline-link" href="${alert.source_bulletin}" target="_blank" rel="noopener noreferrer">Bulletin</a>` : "";

          return `
            <li class="event-item">
              <strong>${alert.event || "Tsunami Alert"} - ${alert.warning_level || "Unknown"}</strong><br />
              <span>${alert.region || "Unknown region"} | ${alert.severity || "Unknown"} | ${when}</span>
              ${bulletin ? `<div>${bulletin}</div>` : ""}
            </li>
          `;
        }).join("");
      } catch (error) {
        setError("Unable to load tsunami alerts right now.");
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
