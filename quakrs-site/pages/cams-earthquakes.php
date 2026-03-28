<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Earthquake Cams';
$pageDescription = 'Curated earthquake-region camera directory ranked by nearby seismic relevance.';
$currentPage = 'cams-earthquakes';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.cams_earthquakes.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.cams_earthquakes.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.cams_earthquakes.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Cameras</p>
    <p id="eqcams-kpi-total" class="kpi-value">--</p>
    <p class="kpi-note">Curated entries</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Countries</p>
    <p id="eqcams-kpi-countries" class="kpi-value">--</p>
    <p class="kpi-note">Geographic coverage</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Hot Now</p>
    <p id="eqcams-kpi-hot" class="kpi-value">--</p>
    <p class="kpi-note">Priority cams in evidenza</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Last Update</p>
    <p id="eqcams-kpi-updated" class="kpi-value">--</p>
    <p id="eqcams-kpi-source" class="kpi-note">Loading source...</p>
  </article>
</section>

<section class="panel">
  <article class="card card--hot">
    <div class="feed-head">
      <h3>Hot Now</h3>
      <p class="feed-meta">Cams ordinate per sismicita recente nelle aree vicine</p>
    </div>
    <div id="eqcams-hot-grid" class="page-grid cams-grid">
      <article class="card page-card">
        <h3>Loading cameras...</h3>
        <p>Please wait while the earthquake camera directory is prepared.</p>
      </article>
    </div>
  </article>
</section>

<section class="panel">
  <article class="card card--rotation">
    <div class="feed-head">
      <h3>Rotation Pool</h3>
      <p id="eqcams-rotation-meta" class="feed-meta">Rotating view loading...</p>
    </div>
    <div id="eqcams-rotate-grid" class="page-grid cams-grid">
      <article class="card page-card">
        <h3>Preparing rotation...</h3>
        <p>Additional cameras will appear here automatically.</p>
      </article>
    </div>
  </article>
</section>

<script>
  (() => {
    const kpiTotal = document.querySelector("#eqcams-kpi-total");
    const kpiCountries = document.querySelector("#eqcams-kpi-countries");
    const kpiHot = document.querySelector("#eqcams-kpi-hot");
    const kpiUpdated = document.querySelector("#eqcams-kpi-updated");
    const kpiSource = document.querySelector("#eqcams-kpi-source");
    const hotGrid = document.querySelector("#eqcams-hot-grid");
    const rotateGrid = document.querySelector("#eqcams-rotate-grid");
    const rotationMeta = document.querySelector("#eqcams-rotation-meta");

    const cardHtml = (cam, prefix) => {
      const playerUrl = cam.embed_url || null;
      const hasSnapshot = !!cam.snapshot_url;
      const reasons = Array.isArray(cam.priority_reasons) ? cam.priority_reasons.slice(0, 2).join(" · ") : "";
      const statusText = String(cam.status || "").trim();
      const showStatus = statusText !== "" && statusText.toLowerCase() !== "monitoring";
      const locationMeta = showStatus ? `${cam.country} · ${statusText}` : cam.country;

      let mediaBlock = `
        <div class="cam-media">
          <div class="cam-media-placeholder">Inline player non disponibile per questa fonte.</div>
        </div>
      `;

      if (playerUrl) {
        mediaBlock = `
          <div class="cam-media">
            <iframe
              id="${prefix}-frame-${cam.id}"
              src="${playerUrl}"
              title="${cam.name} live stream"
              loading="lazy"
              referrerpolicy="no-referrer"
              allowfullscreen
            ></iframe>
          </div>
        `;
      } else if (hasSnapshot) {
        mediaBlock = `
          <div class="cam-media">
            <img id="${prefix}-thumb-${cam.id}" src="${cam.snapshot_url}" alt="${cam.name} snapshot" loading="lazy" />
          </div>
        `;
      }

      return `
        <article class="card page-card cam-card">
          <div class="cam-body">
            <h3>${cam.name}</h3>
            <p class="cam-meta">${locationMeta}</p>
            ${mediaBlock}
            ${reasons ? `<p class="kpi-note">Priority: ${reasons}</p>` : ""}
            <p class="kpi-note">Source: ${cam.source || "Unknown"}</p>
          </div>
          <div class="cam-footer">
            <a class="inline-link" href="${cam.stream_url}" target="_blank" rel="noopener noreferrer">Open Camera</a>
          </div>
        </article>
      `;
    };

    const bindSnapshotFallbacks = (container, prefix, cams) => {
      if (!container || !Array.isArray(cams)) return;
      cams.forEach((cam) => {
        if (!cam.snapshot_url) return;
        const image = container.querySelector(`#${prefix}-thumb-${cam.id}`);
        if (!image) return;
        image.addEventListener("error", () => {
          const media = image.closest(".cam-media");
          if (!media) return;
          media.innerHTML = `<div class="cam-media-placeholder">Snapshot unavailable. Use Open Camera.</div>`;
        });
      });
    };

    const setError = () => {
      if (hotGrid) {
        hotGrid.innerHTML = `<article class="card page-card"><h3>Directory unavailable</h3><p>Unable to load earthquake cams right now.</p></article>`;
      }
      if (rotateGrid) {
        rotateGrid.innerHTML = `<article class="card page-card"><h3>Rotation unavailable</h3><p>Unable to prepare rotation pool.</p></article>`;
      }
      if (rotationMeta) {
        rotationMeta.textContent = "Rotation unavailable";
      }
      if (kpiSource) {
        kpiSource.textContent = "Source unavailable";
      }
    };

    const REFRESH_MS = 60000;
    let refreshInFlight = false;
    let rotationTimerId = null;

    const load = async () => {
      try {
        const response = await fetch("/api/earthquake-cams.php", { headers: { Accept: "application/json" } });
        if (!response.ok) throw new Error("Request failed");

        const payload = await response.json();
        const cams = Array.isArray(payload.cams) ? payload.cams : [];
        const hotNow = Array.isArray(payload.hot_now) ? payload.hot_now : cams.slice(0, 6);
        const rotatingPool = Array.isArray(payload.rotating_candidates) ? payload.rotating_candidates : [];
        const rotationInterval = typeof payload.rotation_interval_seconds === "number" && payload.rotation_interval_seconds >= 8
          ? payload.rotation_interval_seconds
          : 20;

        if (kpiTotal) kpiTotal.textContent = String(typeof payload.cams_count === "number" ? payload.cams_count : cams.length);
        if (kpiCountries) kpiCountries.textContent = String(typeof payload.countries_count === "number" ? payload.countries_count : 0);
        if (kpiHot) kpiHot.textContent = String(hotNow.length);
        if (kpiUpdated) {
          kpiUpdated.textContent = payload.generated_at
            ? new Date(payload.generated_at).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
            : "--";
        }
        if (kpiSource) {
          kpiSource.textContent = `Source: ${payload.provider || "Curated Seismic Region Cameras"}`;
        }

        if (cams.length === 0) {
          if (hotGrid) {
            hotGrid.innerHTML = `<article class="card page-card"><h3>No cameras configured</h3><p>Add entries to <code>config/feeds.php</code> under <code>earthquake_cams</code>.</p></article>`;
          }
          return;
        }

        if (hotGrid) {
          hotGrid.innerHTML = hotNow.map((cam) => cardHtml(cam, "hot")).join("");
          bindSnapshotFallbacks(hotGrid, "hot", hotNow);
        }

        if (!rotateGrid) return;
        if (rotatingPool.length === 0) {
          rotateGrid.innerHTML = `<article class="card page-card"><h3>No additional cams</h3><p>The full catalog is currently shown in Hot Now.</p></article>`;
          if (rotationMeta) rotationMeta.textContent = "No rotation needed";
          return;
        }

        const windowSize = Math.min(6, rotatingPool.length);
        let cursor = 0;
        const renderRotation = () => {
          const window = [];
          for (let i = 0; i < windowSize; i += 1) {
            const idx = (cursor + i) % rotatingPool.length;
            window.push(rotatingPool[idx]);
          }
          rotateGrid.innerHTML = window.map((cam) => cardHtml(cam, "rot")).join("");
          bindSnapshotFallbacks(rotateGrid, "rot", window);
          if (rotationMeta) {
            rotationMeta.textContent = `Rotates every ${rotationInterval}s · ${rotatingPool.length} cams in pool`;
          }
        };

        if (rotationTimerId) {
          window.clearInterval(rotationTimerId);
        }
        renderRotation();
        rotationTimerId = window.setInterval(() => {
          cursor = (cursor + windowSize) % rotatingPool.length;
          renderRotation();
        }, rotationInterval * 1000);
      } catch (error) {
        setError();
      }
    };

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
