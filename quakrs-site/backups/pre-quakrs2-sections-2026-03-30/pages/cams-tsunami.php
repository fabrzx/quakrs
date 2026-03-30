<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Tsunami Cams';
$pageDescription = 'Curated coastal camera directory for tsunami situational awareness.';
$currentPage = 'cams-tsunami';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.cams_tsunami.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.cams_tsunami.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.cams_tsunami.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Cameras</p>
    <p id="tcams-kpi-total" class="kpi-value">--</p>
    <p class="kpi-note">Curated entries</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Countries</p>
    <p id="tcams-kpi-countries" class="kpi-value">--</p>
    <p class="kpi-note">Coastal coverage</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Hot Now</p>
    <p id="tcams-kpi-hot" class="kpi-value">--</p>
    <p id="tcams-kpi-level" class="kpi-note">Global level --</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Last Update</p>
    <p id="tcams-kpi-updated" class="kpi-value">--</p>
    <p id="tcams-kpi-source" class="kpi-note">Loading source...</p>
  </article>
</section>

<section class="panel">
  <article class="card card--hot">
    <div class="feed-head">
      <h3>Hot Now</h3>
      <p class="feed-meta">Cams ordinate per livello tsunami globale e match regionali</p>
    </div>
    <div id="tcams-hot-grid" class="page-grid cams-grid">
      <article class="card page-card">
        <h3>Loading cameras...</h3>
        <p>Please wait while the tsunami camera directory is prepared.</p>
      </article>
    </div>
  </article>
</section>

<section class="panel">
  <article class="card card--rotation">
    <div class="feed-head">
      <h3>Rotation Pool</h3>
      <p id="tcams-rotation-meta" class="feed-meta">Rotating view loading...</p>
    </div>
    <div id="tcams-rotate-grid" class="page-grid cams-grid">
      <article class="card page-card">
        <h3>Preparing rotation...</h3>
        <p>Additional cameras will appear here automatically.</p>
      </article>
    </div>
  </article>
</section>

<script>
  (() => {
    const kpiTotal = document.querySelector('#tcams-kpi-total');
    const kpiCountries = document.querySelector('#tcams-kpi-countries');
    const kpiHot = document.querySelector('#tcams-kpi-hot');
    const kpiLevel = document.querySelector('#tcams-kpi-level');
    const kpiUpdated = document.querySelector('#tcams-kpi-updated');
    const kpiSource = document.querySelector('#tcams-kpi-source');
    const hotGrid = document.querySelector('#tcams-hot-grid');
    const rotateGrid = document.querySelector('#tcams-rotate-grid');
    const rotationMeta = document.querySelector('#tcams-rotation-meta');

    const cardHtml = (cam, prefix) => {
      const playerUrl = cam.embed_url || null;
      const hasSnapshot = !!cam.snapshot_url;
      const reasons = Array.isArray(cam.priority_reasons) ? cam.priority_reasons.slice(0, 2).join(' · ') : '';
      const statusText = String(cam.status || '').trim();
      const showStatus = statusText !== '';
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

      const matchLabel = Number(cam.region_match_count || 0) > 0
        ? `Regional matches: ${cam.region_match_count} · Max level: ${cam.matched_max_level || 'None'}`
        : `Risk tier: ${cam.risk_tier || 1}`;

      return `
        <article class="card page-card cam-card">
          <div class="cam-body">
            <h3>${cam.name}</h3>
            <p class="cam-meta">${locationMeta}</p>
            ${mediaBlock}
            ${reasons ? `<p class="kpi-note">Priority: ${reasons}</p>` : ''}
            <p class="kpi-note">${matchLabel}</p>
            <p class="kpi-note">Source: ${cam.source || 'Unknown'}</p>
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
        image.addEventListener('error', () => {
          const media = image.closest('.cam-media');
          if (!media) return;
          media.innerHTML = `<div class="cam-media-placeholder">Snapshot unavailable. Use Open Camera.</div>`;
        });
      });
    };

    const setError = () => {
      if (hotGrid) {
        hotGrid.innerHTML = `<article class="card page-card"><h3>Directory unavailable</h3><p>Unable to load tsunami cams right now.</p></article>`;
      }
      if (rotateGrid) {
        rotateGrid.innerHTML = `<article class="card page-card"><h3>Rotation unavailable</h3><p>Unable to prepare rotation pool.</p></article>`;
      }
      if (rotationMeta) {
        rotationMeta.textContent = 'Rotation unavailable';
      }
      if (kpiSource) {
        kpiSource.textContent = 'Source unavailable';
      }
    };

    const REFRESH_MS = 60000;
    let refreshInFlight = false;
    let rotationTimerId = null;

    const load = async () => {
      try {
        const response = await fetch('/api/tsunami-cams.php', { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Request failed');

        const payload = await response.json();
        const cams = Array.isArray(payload.cams) ? payload.cams : [];
        const hotNow = Array.isArray(payload.hot_now) ? payload.hot_now : [];
        const rotatingPool = Array.isArray(payload.rotating_candidates) ? payload.rotating_candidates : [];
        const rotationInterval = typeof payload.rotation_interval_seconds === 'number' && payload.rotation_interval_seconds >= 8
          ? payload.rotation_interval_seconds
          : 20;

        if (kpiTotal) kpiTotal.textContent = String(typeof payload.cams_count === 'number' ? payload.cams_count : cams.length);
        if (kpiCountries) kpiCountries.textContent = String(typeof payload.countries_count === 'number' ? payload.countries_count : 0);
        if (kpiHot) kpiHot.textContent = String(hotNow.length);
        if (kpiLevel) kpiLevel.textContent = `Global level ${payload.highest_level || 'None'} · Alerts ${payload.alerts_count || 0}`;
        if (kpiUpdated) {
          kpiUpdated.textContent = payload.generated_at
            ? new Date(payload.generated_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            : '--';
        }
        if (kpiSource) {
          kpiSource.textContent = `Source: ${payload.provider || 'Curated Coastal Tsunami Watch Cameras'}`;
        }

        if (cams.length === 0) {
          if (hotGrid) {
            hotGrid.innerHTML = `<article class="card page-card"><h3>No cameras configured</h3><p>Add entries to <code>config/feeds.php</code> under <code>tsunami_cams</code>.</p></article>`;
          }
          return;
        }

        if (hotGrid) {
          if (hotNow.length === 0) {
            hotGrid.innerHTML = `<article class="card page-card"><h3>No hot cameras now</h3><p>No active tsunami alert matches at this time.</p></article>`;
          } else {
            hotGrid.innerHTML = hotNow.map((cam) => cardHtml(cam, 'hot')).join('');
            bindSnapshotFallbacks(hotGrid, 'hot', hotNow);
          }
        }

        if (!rotateGrid) return;
        if (rotatingPool.length === 0) {
          rotateGrid.innerHTML = `<article class="card page-card"><h3>No additional cams</h3><p>The full catalog is currently shown in Hot Now.</p></article>`;
          if (rotationMeta) rotationMeta.textContent = 'No rotation needed';
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
          rotateGrid.innerHTML = window.map((cam) => cardHtml(cam, 'rot')).join('');
          bindSnapshotFallbacks(rotateGrid, 'rot', window);
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
