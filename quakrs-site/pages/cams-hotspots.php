<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Eruption Hotspots';
$pageDescription = 'Volcanic eruption hotspots derived from current volcano activity reports.';
$currentPage = 'cams-hotspots';
$bodyClass = 'hotspots-page';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.cams_hotspots.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.cams_hotspots.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.cams_hotspots.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Hotspots</p>
    <p id="hot-kpi-total" class="kpi-value">--</p>
    <p class="kpi-note">Tracked volcano hotspots</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Countries</p>
    <p id="hot-kpi-countries" class="kpi-value">--</p>
    <p class="kpi-note">Countries represented</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Top Country</p>
    <p id="hot-kpi-country" class="kpi-value">--</p>
    <p id="hot-kpi-country-reports" class="kpi-note">--</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Last Update</p>
    <p id="hot-kpi-updated" class="kpi-value">--</p>
    <p id="hot-kpi-source" class="kpi-note">Loading source...</p>
  </article>
</section>

<section class="panel panel-main hotspots-main-layout">
  <article class="card hot-main-card">
    <div class="feed-head">
      <h3>Hotspot Ranking</h3>
      <p class="feed-meta">Highest priority first · top 20 volcanoes</p>
    </div>
    <ul id="hotspots-list" class="events-list">
      <li class="event-item">Loading eruption hotspots...</li>
    </ul>
  </article>
  <article class="card side-card hot-side-card">
    <h3>How To Use This Page</h3>
    <p class="kpi-note">Hotspots = bulletin-derived priority ranking. For live camera feeds use Volcano Cams.</p>
    <div class="hot-links">
      <a id="hotspots-monitor-link" class="inline-link" href="/volcanoes.php">Open Volcanoes Monitor</a>
      <a class="inline-link" href="/cams-volcanoes.php">Open Volcano Cams</a>
    </div>
  </article>
</section>

<script>
  (() => {
    const kpiTotal = document.querySelector("#hot-kpi-total");
    const kpiCountries = document.querySelector("#hot-kpi-countries");
    const kpiCountry = document.querySelector("#hot-kpi-country");
    const kpiCountryReports = document.querySelector("#hot-kpi-country-reports");
    const kpiUpdated = document.querySelector("#hot-kpi-updated");
    const kpiSource = document.querySelector("#hot-kpi-source");
    const list = document.querySelector("#hotspots-list");
    const monitorLink = document.querySelector("#hotspots-monitor-link");

    const setError = () => {
      if (list) list.innerHTML = "<li class='event-item'>Unable to load hotspot ranking right now.</li>";
      if (kpiSource) kpiSource.textContent = "Source unavailable";
    };

    const load = async () => {
      try {
        const response = await fetch("/api/hotspots.php", { headers: { Accept: "application/json" } });
        if (!response.ok) throw new Error("Request failed");

        const payload = await response.json();
        const hotspots = Array.isArray(payload.hotspots) ? payload.hotspots : [];

        if (kpiTotal) kpiTotal.textContent = String(typeof payload.hotspots_count === "number" ? payload.hotspots_count : hotspots.length);
        if (kpiCountries) kpiCountries.textContent = String(typeof payload.countries_count === "number" ? payload.countries_count : 0);
        if (kpiCountry) kpiCountry.textContent = payload.top_country || "--";
        if (kpiCountryReports) {
          const reports = typeof payload.top_country_reports === "number" ? payload.top_country_reports : 0;
          kpiCountryReports.textContent = `${reports} reports`;
        }
        if (kpiUpdated) {
          kpiUpdated.textContent = payload.generated_at
            ? new Date(payload.generated_at).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
            : "--";
        }
        if (kpiSource) {
          const updatedLabel = payload.generated_at
            ? new Date(payload.generated_at).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
            : "--";
          kpiSource.textContent = `Bulletin-derived ranking · updated ${updatedLabel}`;
        }
        if (monitorLink && payload.linked_monitor) {
          monitorLink.setAttribute("href", payload.linked_monitor);
        }

        if (!list) return;
        if (hotspots.length === 0) {
          list.innerHTML = "<li class='event-item'>No eruption hotspots available.</li>";
          return;
        }

        list.innerHTML = hotspots
          .map((hotspot) => {
            const when = hotspot.latest_event_utc
              ? new Date(hotspot.latest_event_utc).toLocaleString([], { month: "short", day: "2-digit", hour: "2-digit", minute: "2-digit" })
              : "n/a";
            const reports = typeof hotspot.reports === "number" ? hotspot.reports : 0;
            const eruptive = typeof hotspot.new_eruptive_reports === "number" ? hotspot.new_eruptive_reports : 0;
            const bulletin = hotspot.source_url
              ? `<a class="inline-link" href="${hotspot.source_url}" target="_blank" rel="noopener noreferrer">Open bulletin</a>`
              : "";
            const rank = typeof hotspot.rank === "number" ? hotspot.rank : "--";

            return `
              <li class="event-item hot-item">
                <div class="hot-item-head">
                  <strong>#${rank} · ${hotspot.volcano || "Unknown volcano"}</strong>
                  <span>${hotspot.country || "Unknown"} · ${hotspot.status || "Elevated Volcanic Activity"}</span>
                </div>
                <div class="hot-item-metrics">
                  <span>Reports ${reports}</span>
                  <span>New eruptive ${eruptive}</span>
                  <span>Latest ${when}</span>
                </div>
                ${bulletin ? `<div>${bulletin}</div>` : ""}
              </li>
            `;
          })
          .join("");
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
