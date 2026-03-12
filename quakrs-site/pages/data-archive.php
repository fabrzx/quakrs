<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Data Archive';
$pageDescription = 'Searchable earthquake archive with filters for time window, magnitude, depth and region.';
$currentPage = 'data-archive';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Data / Archive</p>
    <h1>Searchable Seismic Archive.</h1>
    <p class="sub">Filter historical events by time window, magnitude threshold, depth band and region text.</p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Visible Events</p>
    <p id="archive-kpi-visible" class="kpi-value">--</p>
    <p class="kpi-note">Rows after filtering</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Dataset Size</p>
    <p id="archive-kpi-total" class="kpi-value">--</p>
    <p class="kpi-note">Fetched events</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Max Magnitude</p>
    <p id="archive-kpi-max-mag" class="kpi-value">--</p>
    <p class="kpi-note">Within active filters</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Latest Event</p>
    <p id="archive-kpi-latest" class="kpi-value">--</p>
    <p id="archive-kpi-source" class="kpi-note">Loading source...</p>
  </article>
</section>

<section class="panel">
  <article class="card">
    <div class="feed-head">
      <h3>Filters</h3>
      <p class="feed-meta">Applied server-side on full archive</p>
    </div>
    <div class="archive-filter-grid">
      <label class="event-item archive-filter-item">
        <strong>Time Window</strong><br />
        <select id="archive-filter-window">
          <option value="6">Last 6 hours</option>
          <option value="24" selected>Last 24 hours</option>
          <option value="72">Last 72 hours</option>
          <option value="168">Last 7 days</option>
          <option value="0">All loaded rows</option>
        </select>
      </label>
      <label class="event-item archive-filter-item">
        <strong>Minimum Magnitude</strong><br />
        <select id="archive-filter-mag">
          <option value="0">M0+</option>
          <option value="2">M2+</option>
          <option value="3">M3+</option>
          <option value="4">M4+</option>
          <option value="5">M5+</option>
          <option value="6">M6+</option>
        </select>
      </label>
      <label class="event-item archive-filter-item">
        <strong>Depth Band</strong><br />
        <select id="archive-filter-depth">
          <option value="all">All depths</option>
          <option value="shallow">Shallow (0-70 km)</option>
          <option value="intermediate">Intermediate (70-300 km)</option>
          <option value="deep">Deep (300+ km)</option>
        </select>
      </label>
      <label class="event-item archive-filter-item">
        <strong>Region Contains</strong><br />
        <input id="archive-filter-region" type="text" placeholder="e.g. Japan, Alaska, Chile" />
      </label>
    </div>
    <div id="archive-presets" class="preset-row" aria-label="Archive quick presets">
      <button class="preset-btn" type="button" data-preset="rapid">Rapid Scan (24h, M4+)</button>
      <button class="preset-btn" type="button" data-preset="major">Major Events (7d, M5+)</button>
      <button class="preset-btn" type="button" data-preset="deep">Deep Focus (24h, deep)</button>
      <button class="preset-btn" type="button" data-preset="swarm">Swarm Watch (6h, M0+)</button>
    </div>
  </article>
</section>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Filter Insight</h3>
    <p id="archive-insight-summary" class="insight-lead">Apply filters to generate an operational summary.</p>
  </article>
  <article class="card page-card">
    <h3>Depth &amp; Coverage</h3>
    <p id="archive-insight-depth" class="insight-lead">Depth and regional composition will appear here.</p>
  </article>
  <article class="card page-card">
    <h3>Source Blend</h3>
    <div id="archive-insight-providers" class="insight-pills">
      <span class="insight-pill">Loading providers...</span>
    </div>
  </article>
</section>

<section class="panel">
  <article class="card recent-card">
    <div class="feed-head">
      <h3>Archive Results</h3>
      <p id="archive-feed-meta" class="feed-meta">Loading archive...</p>
    </div>
    <ul id="archive-results" class="events-list archive-results-list">
      <li class="event-item">Loading archived events...</li>
    </ul>
    <div class="preset-row">
      <button id="archive-page-prev" class="btn btn-ghost" type="button">Previous</button>
      <button id="archive-page-next" class="btn btn-ghost" type="button">Next</button>
    </div>
  </article>
</section>

<script>
  (() => {
    const resultsList = document.querySelector("#archive-results");
    const feedMeta = document.querySelector("#archive-feed-meta");
    const filterWindow = document.querySelector("#archive-filter-window");
    const filterMag = document.querySelector("#archive-filter-mag");
    const filterDepth = document.querySelector("#archive-filter-depth");
    const filterRegion = document.querySelector("#archive-filter-region");
    const kpiVisible = document.querySelector("#archive-kpi-visible");
    const kpiTotal = document.querySelector("#archive-kpi-total");
    const kpiMaxMag = document.querySelector("#archive-kpi-max-mag");
    const kpiLatest = document.querySelector("#archive-kpi-latest");
    const kpiSource = document.querySelector("#archive-kpi-source");
    const presets = document.querySelector("#archive-presets");
    const insightSummary = document.querySelector("#archive-insight-summary");
    const insightDepth = document.querySelector("#archive-insight-depth");
    const insightProviders = document.querySelector("#archive-insight-providers");
    const pagePrev = document.querySelector("#archive-page-prev");
    const pageNext = document.querySelector("#archive-page-next");

    const perPage = 120;
    let currentPage = 1;
    let totalPages = 1;
    let currentEvents = [];

    const timeLabel = (iso) => {
      if (!iso) return "n/a";
      return new Date(iso).toLocaleString([], { month: "short", day: "2-digit", hour: "2-digit", minute: "2-digit" });
    };

    const classifyDepth = (depth) => {
      if (typeof depth !== "number" || Number.isNaN(depth)) return "all";
      if (depth < 70) return "shallow";
      if (depth < 300) return "intermediate";
      return "deep";
    };

    const renderRows = (rows) => {
      if (!resultsList) {
        return;
      }
      if (!Array.isArray(rows) || rows.length === 0) {
        resultsList.innerHTML = "<li class='event-item'>No events match current filters.</li>";
        return;
      }
      resultsList.innerHTML = rows.map((row) => {
        const mag = typeof row.magnitude === "number" ? `M${row.magnitude.toFixed(1)}` : "M?";
        const depth = typeof row.depth_km === "number" ? `${row.depth_km.toFixed(1)} km` : "n/a";
        const when = timeLabel(row.event_time_utc);
        const place = row.place || "Unknown location";
        const providers = Array.isArray(row.source_providers) && row.source_providers.length > 0
          ? row.source_providers.join(" + ")
          : (row.source_provider || "Unknown");
        return `
          <li class="event-item">
            <strong>${mag} ${place}</strong><br />
            <span class="archive-result-meta">${when} | Depth ${depth} | ${providers}</span>
          </li>
        `;
      }).join("");
    };

    const collectQuery = () => {
      const windowHours = Number(filterWindow?.value || "24");
      const magMin = Number(filterMag?.value || "0");
      const depthBand = filterDepth?.value || "all";
      const regionNeedle = String(filterRegion?.value || "").trim().toLowerCase();
      const nowTs = Date.now();

      const params = new URLSearchParams();
      params.set("page", String(currentPage));
      params.set("per_page", String(perPage));
      params.set("min_magnitude", String(magMin));

      if (windowHours > 0) {
        const fromIso = new Date(nowTs - (windowHours * 60 * 60 * 1000)).toISOString();
        params.set("from", fromIso);
      }

      if (depthBand === "shallow") {
        params.set("min_depth_km", "0");
        params.set("max_depth_km", "70");
      } else if (depthBand === "intermediate") {
        params.set("min_depth_km", "70");
        params.set("max_depth_km", "300");
      } else if (depthBand === "deep") {
        params.set("min_depth_km", "300");
      }

      if (regionNeedle !== "") {
        params.set("q", regionNeedle);
      }

      return params;
    };

    const updateKpisAndInsights = (payload, rows) => {
      if (kpiVisible) kpiVisible.textContent = String(Array.isArray(rows) ? rows.length : 0);
      if (kpiTotal) kpiTotal.textContent = String(Number(payload.total_count || 0));
      const maxMag = (Array.isArray(rows) ? rows : []).reduce((best, row) => {
        const mag = typeof row.magnitude === "number" ? row.magnitude : best;
        return mag > best ? mag : best;
      }, 0);
      if (kpiMaxMag) kpiMaxMag.textContent = Array.isArray(rows) && rows.length > 0 ? `M${maxMag.toFixed(1)}` : "--";
      if (kpiLatest) kpiLatest.textContent = rows[0]?.event_time_utc ? timeLabel(rows[0].event_time_utc) : "--";

      if (feedMeta) {
        feedMeta.textContent = `Page ${payload.page || 1}/${payload.total_pages || 1} · ${payload.total_count || 0} total rows`;
      }

      if (insightSummary) {
        insightSummary.textContent = `${payload.total_count || 0} events match current filters. Showing ${Array.isArray(rows) ? rows.length : 0} on this page.`;
      }
      if (insightDepth) {
        const shallow = (Array.isArray(rows) ? rows : []).filter((row) => classifyDepth(row.depth_km) === "shallow").length;
        const intermediate = (Array.isArray(rows) ? rows : []).filter((row) => classifyDepth(row.depth_km) === "intermediate").length;
        const deep = (Array.isArray(rows) ? rows : []).filter((row) => classifyDepth(row.depth_km) === "deep").length;
        insightDepth.textContent = `Depth mix: ${shallow} shallow, ${intermediate} intermediate, ${deep} deep.`;
      }

      const providers = Array.isArray(payload.providers) ? payload.providers : [];
      if (insightProviders) {
        insightProviders.innerHTML = providers.length > 0
          ? providers.map((name) => `<span class="insight-pill">${name}</span>`).join("")
          : "<span class='insight-pill'>No providers in current page</span>";
      }

      totalPages = Number(payload.total_pages || 1) || 1;
      if (pagePrev) pagePrev.disabled = currentPage <= 1;
      if (pageNext) pageNext.disabled = currentPage >= totalPages;
    };

    const fetchArchive = async () => {
      try {
        const query = collectQuery();
        const response = await fetch(`/api/earthquakes-archive.php?${query.toString()}`, { headers: { Accept: "application/json" } });
        if (!response.ok) {
          throw new Error("Archive request failed");
        }

        const payload = await response.json();
        currentEvents = Array.isArray(payload.events) ? payload.events : [];
        renderRows(currentEvents);

        if (kpiSource) {
          kpiSource.textContent = `Source: ${payload.provider || "Archive API"}`;
        }

        updateKpisAndInsights(payload, currentEvents);
      } catch (error) {
        setError();
      }
    };

    let debounceTimer = null;
    const scheduleFetch = () => {
      currentPage = 1;
      if (debounceTimer) {
        clearTimeout(debounceTimer);
      }
      debounceTimer = setTimeout(fetchArchive, 220);
    };

    const bindFilters = () => {
      [filterWindow, filterMag, filterDepth].forEach((el) => el?.addEventListener("change", scheduleFetch));
      filterRegion?.addEventListener("input", scheduleFetch);
      presets?.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof HTMLButtonElement)) {
          return;
        }
        const preset = target.dataset.preset || "";
        if (!filterWindow || !filterMag || !filterDepth || !filterRegion) {
          return;
        }
        if (preset === "rapid") {
          filterWindow.value = "24";
          filterMag.value = "4";
          filterDepth.value = "all";
          filterRegion.value = "";
        } else if (preset === "major") {
          filterWindow.value = "168";
          filterMag.value = "5";
          filterDepth.value = "all";
          filterRegion.value = "";
        } else if (preset === "deep") {
          filterWindow.value = "24";
          filterMag.value = "0";
          filterDepth.value = "deep";
          filterRegion.value = "";
        } else if (preset === "swarm") {
          filterWindow.value = "6";
          filterMag.value = "0";
          filterDepth.value = "all";
          filterRegion.value = "";
        }
        scheduleFetch();
      });

      pagePrev?.addEventListener("click", () => {
        if (currentPage <= 1) return;
        currentPage -= 1;
        fetchArchive();
      });
      pageNext?.addEventListener("click", () => {
        if (currentPage >= totalPages) return;
        currentPage += 1;
        fetchArchive();
      });
    };

    const setError = () => {
      if (resultsList) {
        resultsList.innerHTML = "<li class='event-item'>Unable to load archive right now.</li>";
      }
      if (kpiSource) {
        kpiSource.textContent = "Source unavailable";
      }
      if (insightSummary) insightSummary.textContent = "Unable to build archive summary right now.";
      if (insightDepth) insightDepth.textContent = "Depth summary unavailable.";
      if (insightProviders) insightProviders.innerHTML = "<span class='insight-pill'>Provider mix unavailable</span>";
      if (pagePrev) pagePrev.disabled = true;
      if (pageNext) pageNext.disabled = true;
    };

    bindFilters();
    fetchArchive();
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
