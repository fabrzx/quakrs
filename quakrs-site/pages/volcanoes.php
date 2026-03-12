<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Volcanoes';
$pageDescription = 'Live volcanic operations with top active volcanoes, selection and webcam coverage.';
$currentPage = 'volcanoes';
$bodyClass = 'volcanoes-page';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero volc-hero">
  <div>
    <p class="eyebrow">Volcanoes</p>
    <h1>Volcanic Operations</h1>
    <p class="sub">Editorial monitoring console for live volcanic activity. Track top systems, review trend evolution, and inspect webcams and bulletins in one streamlined workflow.</p>
  </div>
</main>

<section class="panel">
  <article class="card volc-ops">
    <div class="volc-ops-head">
      <div>
        <h3>Live Console</h3>
        <p id="volc-ops-updated" class="kpi-note">Loading feeds...</p>
      </div>
      <div class="hero-actions">
        <a class="btn btn-ghost" href="/cams-volcanoes.php">Open All Volcano Cams</a>
        <a class="btn btn-ghost" href="/data-reports.php">Open Data Reports</a>
      </div>
    </div>
    <p id="volc-criterion-line" class="volc-criterion-line">Top Active Now = recency + new eruptive flags + unrest/continuing indicators.</p>

    <div class="volc-ops-grid">
      <aside class="volc-ops-side" aria-label="Volcano selection">
        <div class="volc-side-section">
          <h4>Top Active Now</h4>
          <ul id="volc-top-list" class="volc-text-list">
            <li>Loading top active volcanoes...</li>
          </ul>
        </div>

        <div class="volc-side-section">
          <h4>World Coverage</h4>
          <div class="volc-filter-grid">
            <label for="volc-filter-continent" class="sr-only">Filter continent</label>
            <select id="volc-filter-continent">
              <option value="">All continents</option>
            </select>
            <label for="volc-filter-country" class="sr-only">Filter country</label>
            <select id="volc-filter-country">
              <option value="">All countries</option>
            </select>
            <label for="volc-filter-status" class="sr-only">Filter status</label>
            <select id="volc-filter-status">
              <option value="">All status</option>
              <option value="new eruptive">New eruptive</option>
              <option value="new unrest">New unrest</option>
              <option value="continuing eruptive">Continuing eruptive</option>
              <option value="continuing">Continuing activity</option>
              <option value="weekly update">Weekly update</option>
              <option value="no bulletin">No weekly bulletin</option>
            </select>
            <label for="volc-search" class="sr-only">Search volcano or country</label>
            <input id="volc-search" type="search" placeholder="Search volcano or country" />
          </div>
          <p id="volc-coverage-line" class="kpi-note">Loading world coverage...</p>
          <ul id="volc-all-list" class="volc-text-list volc-text-list-scroll">
            <li>Loading volcano list...</li>
          </ul>
        </div>
      </aside>

      <section class="volc-ops-detail" aria-label="Selected volcano details">
        <div class="volc-detail-head">
          <h2 id="volc-detail-name">Select a volcano</h2>
          <p id="volc-detail-country" class="kpi-note">--</p>
        </div>

        <p id="volc-detail-status" class="volc-detail-status">Waiting for data...</p>
        <p id="volc-detail-meta" class="kpi-note">Loading selected volcano metadata...</p>

        <div class="volc-inline-metrics" aria-label="Selected volcano metrics">
          <div><span class="kpi-label">Activity Index</span><strong id="volc-metric-index">--</strong></div>
          <div><span class="kpi-label">Reports</span><strong id="volc-metric-reports">--</strong></div>
          <div><span class="kpi-label">New Eruptive</span><strong id="volc-metric-eruptive">--</strong></div>
          <div><span class="kpi-label">Webcams</span><strong id="volc-metric-cams">--</strong></div>
        </div>

        <div class="volc-insight-grid">
          <div class="volc-chart-block">
            <div class="snapshot-head">
              <h4>Activity Trend</h4>
              <span id="volc-trend-note">--</span>
            </div>
            <div class="volc-trend-chart">
              <svg id="volc-trend-svg" class="volc-trend-svg" viewBox="0 0 560 260" role="img" aria-label="Volcano activity trend"></svg>
            </div>
          </div>
          <div class="volc-index-block">
            <div class="snapshot-head">
              <h4>Activity Profile</h4>
              <span>Composite score</span>
            </div>
            <div class="volc-index-wrap">
              <p id="volc-gauge-value" class="volc-gauge-value">--</p>
              <p id="volc-gauge-label" class="kpi-note">Loading...</p>
              <div class="volc-index-meter" role="img" aria-label="Volcano activity profile">
                <span id="volc-gauge-fill" class="volc-index-fill"></span>
              </div>
            </div>
          </div>
        </div>

        <div class="volc-media-layout">
          <div class="volc-webcam-wrap">
            <div class="snapshot-head">
              <h4>Webcam</h4>
              <span id="volc-webcam-count">-- feeds</span>
            </div>
            <div id="volc-webcam-media" class="volc-webcam-media">No webcam selected yet.</div>
            <ul id="volc-webcam-list" class="volc-text-list"></ul>
          </div>

          <div class="volc-bulletin-wrap">
            <div class="snapshot-head">
              <h4>Latest Bulletins</h4>
              <span id="volc-bulletin-count">-- entries</span>
            </div>
            <ul id="volc-bulletin-list" class="events-list volc-bulletin-list">
              <li class="event-item">Loading bulletins...</li>
            </ul>
          </div>
        </div>
      </section>
    </div>
  </article>
</section>

<script>
  (() => {
    const bootstrap = window.__QUAKRS_BOOTSTRAP && typeof window.__QUAKRS_BOOTSTRAP === "object"
      ? window.__QUAKRS_BOOTSTRAP
      : {};

    const updatedLine = document.querySelector("#volc-ops-updated");
    const topList = document.querySelector("#volc-top-list");
    const search = document.querySelector("#volc-search");
    const allList = document.querySelector("#volc-all-list");
    const criterionLine = document.querySelector("#volc-criterion-line");
    const coverageLine = document.querySelector("#volc-coverage-line");
    const continentFilter = document.querySelector("#volc-filter-continent");
    const countryFilter = document.querySelector("#volc-filter-country");
    const statusFilter = document.querySelector("#volc-filter-status");

    const detailName = document.querySelector("#volc-detail-name");
    const detailCountry = document.querySelector("#volc-detail-country");
    const detailStatus = document.querySelector("#volc-detail-status");
    const detailMeta = document.querySelector("#volc-detail-meta");

    const metricIndex = document.querySelector("#volc-metric-index");
    const metricReports = document.querySelector("#volc-metric-reports");
    const metricEruptive = document.querySelector("#volc-metric-eruptive");
    const metricCams = document.querySelector("#volc-metric-cams");

    const webcamCount = document.querySelector("#volc-webcam-count");
    const webcamMedia = document.querySelector("#volc-webcam-media");
    const webcamList = document.querySelector("#volc-webcam-list");

    const bulletinCount = document.querySelector("#volc-bulletin-count");
    const bulletinList = document.querySelector("#volc-bulletin-list");
    const trendSvg = document.querySelector("#volc-trend-svg");
    const trendNote = document.querySelector("#volc-trend-note");
    const gaugeFill = document.querySelector("#volc-gauge-fill");
    const gaugeValue = document.querySelector("#volc-gauge-value");
    const gaugeLabel = document.querySelector("#volc-gauge-label");

    const state = {
      provider: "Volcano feed",
      catalogProvider: "Volcano catalog",
      buckets: [],
      topKeys: [],
      selectedKey: null,
      selectedCamIndex: 0,
      query: "",
      camsByVolcano: new Map(),
      filters: {
        continent: "",
        country: "",
        status: "",
      },
    };

    const norm = (value) => String(value || "").trim().toLowerCase();
    const toTs = (iso) => (iso ? new Date(iso).getTime() : 0);
    const fmtTime = (iso) =>
      iso ? new Date(iso).toLocaleString([], { month: "short", day: "2-digit", hour: "2-digit", minute: "2-digit" }) : "n/a";
    const clip = (value, max = 170) => {
      const text = String(value || "").trim();
      if (text.length <= max) return text;
      return `${text.slice(0, max - 1).trimEnd()}…`;
    };
    const esc = (value) => String(value || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
    const token = (value) => String(value || "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/[^a-z0-9]+/g, " ")
      .trim();

    function keyOf(volcano, country) {
      return `${String(volcano || "Unknown")}__${String(country || "Unknown")}`;
    }

    function mergeKey(volcano, country) {
      return `${token(volcano)}|${token(country)}`;
    }

    const countryToContinent = {
      "italy": "Europe",
      "iceland": "Europe",
      "greece": "Europe",
      "russia": "Europe",
      "turkey": "Europe",
      "spain": "Europe",
      "france": "Europe",
      "united states": "North America",
      "usa": "North America",
      "canada": "North America",
      "mexico": "North America",
      "guatemala": "North America",
      "el salvador": "North America",
      "nicaragua": "North America",
      "costa rica": "North America",
      "ecuador": "South America",
      "colombia": "South America",
      "peru": "South America",
      "chile": "South America",
      "argentina": "South America",
      "bolivia": "South America",
      "vanuatu": "Oceania",
      "new zealand": "Oceania",
      "papua new guinea": "Oceania",
      "indonesia": "Asia",
      "japan": "Asia",
      "philippines": "Asia",
      "india": "Asia",
      "china": "Asia",
      "taiwan": "Asia",
      "iran": "Asia",
      "israel": "Asia",
      "congo": "Africa",
      "ethiopia": "Africa",
      "tanzania": "Africa",
      "kenya": "Africa",
      "cameroon": "Africa",
      "antarctica": "Antarctica",
    };

    function continentOf(country) {
      const key = norm(country);
      if (!key) return "Unknown";
      if (countryToContinent[key]) return countryToContinent[key];
      if (key.includes("united states")) return "North America";
      if (key.includes("new zealand")) return "Oceania";
      if (key.includes("papua")) return "Oceania";
      if (key.includes("russia")) return "Europe";
      return "Unknown";
    }

    function statusOf(event) {
      if (event?.is_new_eruptive) return "New eruptive activity";
      const title = norm(event?.title);
      if (title.includes("new unrest")) return "New unrest";
      if (title.includes("continuing eruptive")) return "Continuing eruptive activity";
      if (title.includes("continuing")) return "Continuing activity";
      return "Weekly update";
    }

    function activityIndex(rows) {
      if (!rows.length) return 0;
      const latestTs = toTs(rows[0]?.event_time_utc);
      const ageHours = latestTs > 0 ? Math.max(0, Math.floor((Date.now() - latestTs) / 3600000)) : 168;
      const recencyBoost = Math.max(0, 32 - Math.min(32, Math.floor(ageHours / 4)));
      const eruptive = rows.filter((row) => Boolean(row.is_new_eruptive)).length;
      const unrest = rows.filter((row) => norm(row.title).includes("unrest")).length;
      const continuing = rows.filter((row) => norm(row.title).includes("continuing")).length;
      const score = Math.min(42, rows.length * 7) + eruptive * 20 + unrest * 12 + continuing * 6 + recencyBoost;
      return Math.max(0, Math.min(100, score));
    }

    function buildBuckets(events) {
      const map = new Map();
      events.forEach((event) => {
        const volcano = event?.volcano || event?.title || "Unknown";
        const country = event?.country || "Unknown";
        const key = keyOf(volcano, country);
        if (!map.has(key)) {
          map.set(key, { key, volcano: String(volcano), country: String(country), rows: [] });
        }
        map.get(key).rows.push(event);
      });

      const buckets = [...map.values()].map((bucket) => {
        bucket.rows.sort((a, b) => toTs(b.event_time_utc) - toTs(a.event_time_utc));
        bucket.latestTs = toTs(bucket.rows[0]?.event_time_utc);
        bucket.status = statusOf(bucket.rows[0]);
        bucket.statusKey = norm(bucket.status);
        bucket.reports = bucket.rows.length;
        bucket.newEruptive = bucket.rows.filter((row) => Boolean(row.is_new_eruptive)).length;
        bucket.unrest = bucket.rows.filter((row) => norm(row.title).includes("unrest")).length;
        bucket.index = activityIndex(bucket.rows);
        bucket.continent = continentOf(bucket.country);
        bucket.merge_key = mergeKey(bucket.volcano, bucket.country);
        bucket.catalog = null;
        bucket.profileUrl = "";
        return bucket;
      });

      buckets.sort((a, b) => {
        if (b.index !== a.index) return b.index - a.index;
        return b.latestTs - a.latestTs;
      });
      return buckets;
    }

    function buildMergedBuckets(events, catalogRows) {
      const liveBuckets = buildBuckets(events);
      const liveByMerge = new Map(liveBuckets.map((bucket) => [bucket.merge_key, bucket]));
      const merged = [];
      const seen = new Set();

      catalogRows.forEach((item) => {
        const volcano = item?.volcano || "Unknown";
        const country = item?.country || "Unknown";
        const mKey = mergeKey(volcano, country);
        const live = liveByMerge.get(mKey) || null;

        const bucket = live
          ? { ...live }
          : {
              key: keyOf(volcano, country),
              merge_key: mKey,
              volcano: String(volcano),
              country: String(country),
              rows: [],
              latestTs: 0,
              status: "No weekly bulletin in this cycle",
              statusKey: "no weekly bulletin",
              reports: 0,
              newEruptive: 0,
              unrest: 0,
              index: 0,
              continent: item?.continent || continentOf(country),
            };

        bucket.catalog = item || null;
        bucket.profileUrl = item?.profile_url || "";
        bucket.continent = item?.continent || bucket.continent || continentOf(bucket.country);
        if (!live && state.camsByVolcano.get(norm(bucket.volcano))) {
          bucket.index = 4;
        }
        merged.push(bucket);
        seen.add(mKey);
      });

      liveBuckets.forEach((bucket) => {
        if (seen.has(bucket.merge_key)) return;
        merged.push(bucket);
      });

      merged.sort((a, b) => {
        if (b.index !== a.index) return b.index - a.index;
        if (b.reports !== a.reports) return b.reports - a.reports;
        return a.volcano.localeCompare(b.volcano);
      });
      return merged;
    }

    function deriveTopKeys(buckets) {
      const active = buckets.filter((bucket) => bucket.reports > 0 && (
        bucket.newEruptive > 0 ||
        bucket.unrest > 0 ||
        norm(bucket.status).includes("continuing eruptive")
      ));
      const source = active.length > 0 ? active : buckets.filter((bucket) => bucket.reports > 0);
      return source.slice(0, 8).map((bucket) => bucket.key);
    }

    function matchesStatusFilter(bucket, statusValue) {
      if (!statusValue) return true;
      const key = norm(statusValue);
      if (key === "new eruptive") return bucket.newEruptive > 0;
      if (key === "new unrest") return bucket.statusKey.includes("new unrest");
      if (key === "continuing eruptive") return bucket.statusKey.includes("continuing eruptive");
      if (key === "continuing") return bucket.statusKey.includes("continuing activity");
      if (key === "weekly update") return bucket.statusKey.includes("weekly update");
      if (key === "no bulletin") return bucket.reports === 0;
      return true;
    }

    function filteredBuckets() {
      const q = norm(state.query);
      return state.buckets.filter((bucket) => {
        if (state.filters.continent && bucket.continent !== state.filters.continent) return false;
        if (state.filters.country && bucket.country !== state.filters.country) return false;
        if (!matchesStatusFilter(bucket, state.filters.status)) return false;
        if (!q) return true;
        return norm(bucket.volcano).includes(q) || norm(bucket.country).includes(q);
      });
    }

    function syncFilterOptions() {
      if (!continentFilter || !countryFilter) return;
      const continents = [...new Set(state.buckets.map((bucket) => bucket.continent))].sort();
      const countries = [...new Set(
        state.buckets
          .filter((bucket) => !state.filters.continent || bucket.continent === state.filters.continent)
          .map((bucket) => bucket.country)
      )].sort();

      continentFilter.innerHTML = `<option value="">All continents</option>${continents.map((continent) => `<option value="${continent}">${continent}</option>`).join("")}`;
      continentFilter.value = state.filters.continent;

      countryFilter.innerHTML = `<option value="">All countries</option>${countries.map((country) => `<option value="${country}">${country}</option>`).join("")}`;
      if (state.filters.country && !countries.includes(state.filters.country)) {
        state.filters.country = "";
      }
      countryFilter.value = state.filters.country;
    }

    function renderCoverageLine() {
      if (!coverageLine) return;
      const total = state.buckets.length;
      const visible = filteredBuckets().length;
      const continents = new Set(state.buckets.map((bucket) => bucket.continent));
      const shown = Math.min(300, visible);
      coverageLine.textContent = `${visible}/${total} volcanoes visible · showing ${shown} rows · ${continents.size} continents in catalog`;
    }

    function buildCamsIndex(camsPayload) {
      const index = new Map();
      const cams = Array.isArray(camsPayload?.cams) ? camsPayload.cams : [];
      cams.forEach((cam) => {
        const volcKey = norm(cam?.volcano);
        if (!volcKey) return;
        if (!index.has(volcKey)) index.set(volcKey, []);
        index.get(volcKey).push(cam);
      });
      index.forEach((arr) => {
        arr.sort((a, b) => (b.priority_score || 0) - (a.priority_score || 0));
      });
      return index;
    }

    function findSelected() {
      const hasFilter = Boolean(state.filters.continent || state.filters.country || state.filters.status || state.query);
      const pool = hasFilter ? filteredBuckets() : state.buckets;
      if (pool.length === 0) return null;
      return pool.find((bucket) => bucket.key === state.selectedKey) || pool[0] || null;
    }

    function renderTop() {
      if (!topList) return;
      const topBuckets = state.topKeys
        .map((key) => state.buckets.find((bucket) => bucket.key === key))
        .filter(Boolean);

      topList.innerHTML = topBuckets.length > 0
        ? topBuckets.map((bucket) => `
            <li>
              <button type="button" class="volc-link-btn${bucket.key === state.selectedKey ? " is-active" : ""}" data-volc-key="${bucket.key}">
                <strong>${esc(bucket.volcano)}</strong>
                <span>${esc(bucket.country)} · idx ${bucket.index}</span>
              </button>
            </li>
          `).join("")
        : "<li>No active volcanoes in current cycle.</li>";
    }

    function renderAll() {
      if (!allList) return;
      const filtered = filteredBuckets()
        .sort((a, b) => {
          if (b.reports !== a.reports) return b.reports - a.reports;
          return a.volcano.localeCompare(b.volcano);
        })
        .slice(0, 300);

      allList.innerHTML = filtered.length > 0
        ? filtered.map((bucket) => `
            <li>
              <button type="button" class="volc-link-btn${bucket.key === state.selectedKey ? " is-active" : ""}" data-volc-key="${bucket.key}">
                <strong>${esc(bucket.volcano)}</strong>
                <span>${esc(bucket.country)} · ${esc(bucket.continent)} · ${bucket.reports > 0 ? `idx ${bucket.index}` : "no bulletin"}</span>
              </button>
            </li>
          `).join("")
        : "<li>No volcanoes match this filter.</li>";
    }

    function renderTrend(bucket) {
      if (!trendSvg || !trendNote) return;

      const rows = [...bucket.rows].sort((a, b) => toTs(a.event_time_utc) - toTs(b.event_time_utc));
      const points = rows.slice(-12).map((row) => {
        let score = 22;
        if (row.is_new_eruptive) score += 40;
        const title = norm(row.title);
        if (title.includes("new unrest")) score += 25;
        if (title.includes("continuing eruptive")) score += 16;
        if (title.includes("continuing")) score += 8;
        return { score: Math.min(100, score), label: fmtTime(row.event_time_utc) };
      });

      if (points.length === 0) {
        points.push({ score: 10, label: "No bulletin" });
        points.push({ score: 10, label: "Current cycle" });
      } else if (points.length === 1) {
        points.unshift({ score: Math.max(8, points[0].score - 10), label: "Prior cycle" });
      }

      const padX = 20;
      const padY = 18;
      const w = 560;
      const h = 260;
      const innerW = w - padX * 2;
      const innerH = h - padY * 2;
      const maxY = 100;
      const minY = 0;
      const coords = points.map((point, idx) => {
        const x = padX + (points.length === 1 ? 0 : (idx / (points.length - 1)) * innerW);
        const y = padY + (1 - ((point.score - minY) / (maxY - minY))) * innerH;
        return { x, y, label: point.label, score: point.score };
      });
      const line = coords.map((p) => `${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(" ");
      const last = coords[coords.length - 1];
      const guides = [20, 40, 60, 80].map((value) => {
        const y = padY + (1 - (value / 100)) * innerH;
        return `<line class="volc-axis-line" x1="${padX}" y1="${y.toFixed(1)}" x2="${w - padX}" y2="${y.toFixed(1)}"></line>`;
      }).join("");

      trendSvg.innerHTML = `
        ${guides}
        <polyline class="volc-trend-line" points="${line}"></polyline>
        <circle class="volc-trend-dot" cx="${last.x.toFixed(1)}" cy="${last.y.toFixed(1)}" r="5.4"></circle>
        <text class="volc-trend-tag" x="${Math.max(padX + 8, last.x - 22).toFixed(1)}" y="${Math.max(14, last.y - 10).toFixed(1)}">${Math.round(last.score)}</text>
      `;
      trendNote.textContent = bucket.reports > 0 ? `${Math.min(12, bucket.rows.length)} recent bulletin points` : "No weekly points";
    }

    function renderGauge(bucket) {
      if (!gaugeFill || !gaugeValue || !gaugeLabel) return;
      const value = Math.max(0, Math.min(100, Number(bucket.index) || 0));
      gaugeFill.style.width = `${value}%`;
      gaugeValue.textContent = String(Math.round(value));
      if (value >= 70) {
        gaugeLabel.textContent = "High activity profile";
      } else if (value >= 40) {
        gaugeLabel.textContent = "Elevated monitoring profile";
      } else if (value > 0) {
        gaugeLabel.textContent = "Low-to-moderate profile";
      } else {
        gaugeLabel.textContent = "No current weekly bulletin";
      }
    }

    function renderDetail() {
      const bucket = findSelected();
      if (!bucket) {
        if (detailStatus) detailStatus.textContent = "No volcano data available.";
        if (bulletinList) bulletinList.innerHTML = "<li class='event-item'>No bulletins available.</li>";
        if (webcamMedia) webcamMedia.textContent = "No webcam available.";
        renderGauge({ index: 0 });
        return;
      }
      state.selectedKey = bucket.key;

      if (detailName) detailName.textContent = bucket.volcano;
      if (detailCountry) detailCountry.textContent = `${bucket.country} · ${bucket.continent}`;
      if (detailStatus) detailStatus.textContent = bucket.status;

      const c = bucket.catalog || {};
      const metaParts = [];
      if (bucket.reports > 0) {
        metaParts.push(`Last bulletin ${fmtTime(bucket.rows[0]?.event_time_utc)}`);
      } else {
        metaParts.push("No weekly bulletin in current cycle");
      }
      if (c.primary_type) metaParts.push(c.primary_type);
      if (typeof c.elevation_m === "number" && Number.isFinite(c.elevation_m)) metaParts.push(`${Math.round(c.elevation_m)} m`);
      if (typeof c.latitude === "number" && typeof c.longitude === "number") metaParts.push(`${c.latitude.toFixed(2)}, ${c.longitude.toFixed(2)}`);
      if (detailMeta) detailMeta.textContent = metaParts.join(" · ");

      if (metricIndex) metricIndex.textContent = String(bucket.index);
      if (metricReports) metricReports.textContent = String(bucket.reports);
      if (metricEruptive) metricEruptive.textContent = String(bucket.newEruptive);

      const volcCamRows = state.camsByVolcano.get(norm(bucket.volcano)) || [];
      if (state.selectedCamIndex >= volcCamRows.length) {
        state.selectedCamIndex = 0;
      }
      if (metricCams) metricCams.textContent = String(volcCamRows.length);
      if (webcamCount) webcamCount.textContent = `${volcCamRows.length} feeds`;

      if (webcamMedia) {
        const bestCam = volcCamRows[state.selectedCamIndex] || null;
        if (!bestCam) {
          webcamMedia.innerHTML = "<p class='kpi-note'>No dedicated webcam in current curated set.</p>";
        } else if (bestCam.embed_url) {
          webcamMedia.innerHTML = `
            <iframe
              src="${bestCam.embed_url}"
              title="Live webcam for ${bucket.volcano}"
              loading="lazy"
              referrerpolicy="no-referrer"
              allowfullscreen
            ></iframe>
          `;
        } else if (bestCam.snapshot_url) {
          webcamMedia.innerHTML = `<img src="${bestCam.snapshot_url}" alt="Latest snapshot for ${bucket.volcano}" loading="lazy" />`;
        } else {
          webcamMedia.innerHTML = `<p class='kpi-note'>Webcam listed but no inline source available.</p>`;
        }
      }

      if (webcamList) {
        webcamList.innerHTML = volcCamRows.length > 0
          ? volcCamRows.slice(0, 8).map((cam, idx) => `
              <li>
                <button type="button" class="volc-cam-btn${idx === state.selectedCamIndex ? " is-active" : ""}" data-cam-idx="${idx}">
                  <strong>${esc(cam.name || cam.volcano)}</strong>
                  <span>${esc(cam.source || "Observatory")}</span>
                </button>
                <a class="inline-link" href="${cam.stream_url || "/cams-volcanoes.php"}" target="_blank" rel="noopener noreferrer">Open source</a>
              </li>
            `).join("")
          : "<li>No camera links for this volcano.</li>";
      }

      if (bulletinCount) bulletinCount.textContent = `${bucket.rows.length} entries`;
      if (bulletinList) {
        if (bucket.rows.length === 0) {
          bulletinList.innerHTML = `
            <li class="event-item">
              <strong>No bulletin in this weekly cycle.</strong>
              <div class="event-meta">The volcano is still available in global catalog monitoring.</div>
              ${bucket.profileUrl ? `<a class="inline-link" href="${bucket.profileUrl}" target="_blank" rel="noopener noreferrer">Open Smithsonian profile</a>` : ""}
            </li>
          `;
        } else {
          bulletinList.innerHTML = bucket.rows.slice(0, 10).map((row) => `
            <li class="event-item">
              <strong>${esc(clip(row.title || `${bucket.volcano} bulletin`, 120))}</strong>
              <div class="event-meta">${esc(statusOf(row))} · ${fmtTime(row.event_time_utc)}</div>
              <div class="event-meta">${esc(clip(row.summary || "No summary available.", 180))}</div>
            </li>
          `).join("");
        }
      }

      renderTrend(bucket);
      renderGauge(bucket);
    }

    function bindEvents() {
      document.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof Element)) return;
        const button = target.closest("[data-volc-key]");
        if (button instanceof HTMLElement) {
          const key = button.getAttribute("data-volc-key");
          if (!key) return;
          state.selectedKey = key;
          state.selectedCamIndex = 0;
          renderTop();
          renderAll();
          renderDetail();
          return;
        }

        const camButton = target.closest("[data-cam-idx]");
        if (!(camButton instanceof HTMLElement)) return;
        const idx = Number(camButton.getAttribute("data-cam-idx"));
        if (!Number.isFinite(idx) || idx < 0) return;
        state.selectedCamIndex = idx;
        renderDetail();
      });

      search?.addEventListener("input", () => {
        state.query = search.value || "";
        const filtered = filteredBuckets();
        state.selectedKey = filtered.find((bucket) => bucket.key === state.selectedKey)?.key || filtered[0]?.key || state.selectedKey;
        renderCoverageLine();
        renderAll();
        renderDetail();
      });

      continentFilter?.addEventListener("change", () => {
        state.filters.continent = continentFilter.value || "";
        state.filters.country = "";
        syncFilterOptions();
        const filtered = filteredBuckets();
        state.selectedKey = filtered.find((bucket) => bucket.key === state.selectedKey)?.key || filtered[0]?.key || state.selectedKey;
        state.selectedCamIndex = 0;
        renderCoverageLine();
        renderAll();
        renderDetail();
      });

      countryFilter?.addEventListener("change", () => {
        state.filters.country = countryFilter.value || "";
        const filtered = filteredBuckets();
        state.selectedKey = filtered.find((bucket) => bucket.key === state.selectedKey)?.key || filtered[0]?.key || state.selectedKey;
        state.selectedCamIndex = 0;
        renderCoverageLine();
        renderAll();
        renderDetail();
      });

      statusFilter?.addEventListener("change", () => {
        state.filters.status = statusFilter.value || "";
        const filtered = filteredBuckets();
        state.selectedKey = filtered.find((bucket) => bucket.key === state.selectedKey)?.key || filtered[0]?.key || state.selectedKey;
        state.selectedCamIndex = 0;
        renderCoverageLine();
        renderAll();
        renderDetail();
      });
    }

    async function loadData() {
      try {
        const volcanoPromise = bootstrap.volcanoes && typeof bootstrap.volcanoes === "object"
          ? Promise.resolve(bootstrap.volcanoes)
          : fetch("/api/volcanoes.php", { headers: { Accept: "application/json" } }).then((res) => {
              if (!res.ok) throw new Error("volcano feed failed");
              return res.json();
            });
        const catalogPromise = fetch("/api/volcano-catalog.php", { headers: { Accept: "application/json" } })
          .then((res) => (res.ok ? res.json() : { catalog: [] }))
          .catch(() => ({ catalog: [] }));
        const camsPromise = fetch("/api/volcano-cams.php", { headers: { Accept: "application/json" } })
          .then((res) => (res.ok ? res.json() : { cams: [] }))
          .catch(() => ({ cams: [] }));

        const [volcanoPayload, catalogPayload, camsPayload] = await Promise.all([volcanoPromise, catalogPromise, camsPromise]);
        const events = Array.isArray(volcanoPayload.events) ? volcanoPayload.events : [];
        const catalogRows = Array.isArray(catalogPayload.catalog) ? catalogPayload.catalog : [];

        state.provider = volcanoPayload.provider || "Volcano feed";
        state.catalogProvider = catalogPayload.provider || "Volcano catalog";
        state.camsByVolcano = buildCamsIndex(camsPayload);
        state.buckets = buildMergedBuckets(events, catalogRows);
        state.topKeys = deriveTopKeys(state.buckets);
        state.selectedKey = state.topKeys[0] || state.buckets.find((bucket) => norm(bucket.volcano) === "etna")?.key || state.buckets[0]?.key || null;

        const updated = volcanoPayload.feed_updated_at || volcanoPayload.generated_at;
        if (updatedLine) {
          const mode = volcanoPayload.from_cache ? "cache" : "live";
          updatedLine.textContent = `Feed ${state.provider} · updated ${fmtTime(updated)} · mode ${mode} · catalog ${state.catalogProvider}`;
        }
        if (criterionLine) {
          criterionLine.textContent = "Top Active Now = weighted score from recency, new eruptive/unrest flags and bulletin frequency.";
        }

        syncFilterOptions();
        renderCoverageLine();
        renderTop();
        renderAll();
        renderDetail();
      } catch (error) {
        if (updatedLine) updatedLine.textContent = "Volcano feed unavailable right now.";
        if (topList) topList.innerHTML = "<li>Top active unavailable.</li>";
        if (allList) allList.innerHTML = "<li>Volcano list unavailable.</li>";
        if (bulletinList) bulletinList.innerHTML = "<li class='event-item'>Status feed unavailable.</li>";
      }
    }

    bindEvents();
    loadData();
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
