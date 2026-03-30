<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Bulletins';
$pageDescription = 'Institutional bulletins from trusted operational agencies.';
$currentPage = 'resources-bulletins';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.resources_bulletins.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.resources_bulletins.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.resources_bulletins.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Bulletins</p>
    <p id="bulletins-kpi-total" class="kpi-value">--</p>
    <p class="kpi-note">Items loaded</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Sources</p>
    <p id="bulletins-kpi-sources" class="kpi-value">--</p>
    <p class="kpi-note">Institutional providers</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Categories</p>
    <p id="bulletins-kpi-categories" class="kpi-value">--</p>
    <p class="kpi-note">Hazard groups</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Last Update</p>
    <p id="bulletins-kpi-updated" class="kpi-value">--</p>
    <p id="bulletins-kpi-source" class="kpi-note">Loading source...</p>
  </article>
</section>

<section class="panel">
  <article class="card">
    <div class="feed-head">
      <h3>Source Filter</h3>
      <p class="feed-meta">Client-side filter</p>
    </div>
    <label class="event-item archive-filter-item bulletins-filter-item">
      <strong>Provider</strong><br />
      <select id="bulletins-filter-source">
        <option value="all">All sources</option>
      </select>
    </label>
  </article>
</section>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Featured Bulletin</h3>
    <ul id="bulletins-featured" class="events-list">
      <li class="event-item">Selecting the most recent institutional item...</li>
    </ul>
  </article>
  <article class="card page-card">
    <h3>Channel Pulse</h3>
    <ul id="bulletins-pulse" class="events-list">
      <li class="event-item">Loading source cadence...</li>
    </ul>
  </article>
  <article class="card page-card">
    <h3>Category Mix</h3>
    <div id="bulletins-category-pills" class="insight-pills">
      <span class="insight-pill">Loading categories...</span>
    </div>
  </article>
</section>

<section class="panel">
  <article class="card recent-card">
    <div class="feed-head">
      <h3>Latest Bulletins</h3>
      <p id="bulletins-feed-meta" class="feed-meta">Loading bulletins...</p>
    </div>
    <ul id="bulletins-list" class="events-list">
      <li class="event-item">Loading institutional bulletins...</li>
    </ul>
  </article>
</section>

<script>
  (() => {
    const list = document.querySelector("#bulletins-list");
    const feedMeta = document.querySelector("#bulletins-feed-meta");
    const sourceFilter = document.querySelector("#bulletins-filter-source");
    const kpiTotal = document.querySelector("#bulletins-kpi-total");
    const kpiSources = document.querySelector("#bulletins-kpi-sources");
    const kpiCategories = document.querySelector("#bulletins-kpi-categories");
    const kpiUpdated = document.querySelector("#bulletins-kpi-updated");
    const kpiSource = document.querySelector("#bulletins-kpi-source");
    const featured = document.querySelector("#bulletins-featured");
    const pulse = document.querySelector("#bulletins-pulse");
    const categoryPills = document.querySelector("#bulletins-category-pills");

    let allBulletins = [];

    const timeLabel = (iso) => {
      if (!iso) return "n/a";
      return new Date(iso).toLocaleString([], { month: "short", day: "2-digit", hour: "2-digit", minute: "2-digit" });
    };

    const render = () => {
      const wantedSource = sourceFilter?.value || "all";
      const rows = wantedSource === "all"
        ? allBulletins
        : allBulletins.filter((item) => (item.source_provider || "") === wantedSource);

      if (feedMeta) {
        feedMeta.textContent = `${rows.length}/${allBulletins.length} bulletins shown`;
      }

      if (!list) {
        return;
      }

      if (rows.length === 0) {
        list.innerHTML = "<li class='event-item'>No bulletins match current filter.</li>";
        return;
      }

      list.innerHTML = rows.slice(0, 80).map((item) => {
        const link = item.source_bulletin
          ? `<a class="inline-link" href="${item.source_bulletin}" target="_blank" rel="noopener noreferrer">Open bulletin</a>`
          : "";
        const summary = item.summary ? `<div>${item.summary}</div>` : "";
        return `
          <li class="event-item">
            <strong>${item.title || "Untitled bulletin"}</strong><br />
            <span>${item.source_provider || "Unknown source"} | ${item.category || "General"} | ${timeLabel(item.published_at_utc)}</span>
            ${summary}
            ${link}
          </li>
        `;
      }).join("");
    };

    const setError = (message) => {
      if (list) {
        list.innerHTML = `<li class="event-item">${message}</li>`;
      }
      if (kpiSource) {
        kpiSource.textContent = "Source unavailable";
      }
      if (featured) featured.innerHTML = "<li class='event-item'>Featured bulletin unavailable.</li>";
      if (pulse) pulse.innerHTML = "<li class='event-item'>Channel pulse unavailable.</li>";
      if (categoryPills) categoryPills.innerHTML = "<span class='insight-pill'>No category data</span>";
    };

    const load = async () => {
      try {
        const response = await fetch("/api/bulletins.php", { headers: { Accept: "application/json" } });
        if (!response.ok) {
          throw new Error("Request failed");
        }

        const payload = await response.json();
        allBulletins = Array.isArray(payload.bulletins) ? payload.bulletins : [];

        const sourceSet = new Set();
        allBulletins.forEach((item) => {
          if (item.source_provider) {
            sourceSet.add(item.source_provider);
          }
        });

        if (sourceFilter) {
          const options = ["<option value='all'>All sources</option>"];
          Array.from(sourceSet).sort().forEach((name) => {
            options.push(`<option value="${name}">${name}</option>`);
          });
          sourceFilter.innerHTML = options.join("");
        }

        if (kpiTotal) kpiTotal.textContent = String(typeof payload.bulletins_count === "number" ? payload.bulletins_count : allBulletins.length);
        if (kpiSources) kpiSources.textContent = String(sourceSet.size);
        if (kpiCategories) kpiCategories.textContent = String(payload.categories ? Object.keys(payload.categories).length : 0);
        if (kpiUpdated) kpiUpdated.textContent = payload.generated_at ? timeLabel(payload.generated_at) : "--";
        if (kpiSource) kpiSource.textContent = `Source: ${payload.provider || "Institutional feeds"}`;

        if (featured) {
          const top = allBulletins[0] || null;
          if (!top) {
            featured.innerHTML = "<li class='event-item'>No featured bulletin available.</li>";
          } else {
            const link = top.source_bulletin
              ? `<a class="inline-link" href="${top.source_bulletin}" target="_blank" rel="noopener noreferrer">Open bulletin</a>`
              : "";
            featured.innerHTML = `
              <li class="event-item">
                <strong>${top.title || "Untitled bulletin"}</strong><br />
                <span>${top.source_provider || "Unknown source"} | ${top.category || "General"} | ${timeLabel(top.published_at_utc)}</span>
                ${top.summary ? `<div>${top.summary}</div>` : ""}
                ${link}
              </li>
            `;
          }
        }

        if (pulse) {
          const sourceCounter = new Map();
          allBulletins.forEach((row) => {
            const key = row.source_provider || "Unknown";
            sourceCounter.set(key, (sourceCounter.get(key) || 0) + 1);
          });
          const topSources = [...sourceCounter.entries()].sort((a, b) => b[1] - a[1]).slice(0, 4);
          pulse.innerHTML = topSources.length > 0
            ? topSources.map(([name, count]) => `<li class="event-item"><strong>${name}</strong><br />${count} recent bulletins</li>`).join("")
            : "<li class='event-item'>No source cadence data available.</li>";
        }

        if (categoryPills) {
          const categories = payload.categories && typeof payload.categories === "object"
            ? Object.entries(payload.categories).sort((a, b) => Number(b[1]) - Number(a[1])).slice(0, 8)
            : [];
          categoryPills.innerHTML = categories.length > 0
            ? categories.map(([name, count]) => `<span class="insight-pill">${name}: ${count}</span>`).join("")
            : "<span class='insight-pill'>No categories available</span>";
        }

        render();
      } catch (error) {
        setError("Unable to load institutional bulletins right now.");
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

    sourceFilter?.addEventListener("change", render);
    refresh();
    window.setInterval(() => {
      if (document.hidden) return;
      void refresh();
    }, REFRESH_MS);
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
