<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Data API';
$pageDescription = 'Operational API reference for Quakrs monitoring endpoints.';
$currentPage = 'data-api';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.data_api.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.data_api.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.data_api.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Core Feeds</h3>
    <ul class="events-list">
      <li class="event-item"><strong>GET /api/earthquakes.php</strong><br />Merged earthquake events and KPI fields.</li>
      <li class="event-item"><strong>GET /api/volcanoes.php</strong><br />Weekly volcano bulletin stream with parsed metadata.</li>
      <li class="event-item"><strong>GET /api/tremors.php</strong><br />Tremor signal model and cluster metrics.</li>
    </ul>
  </article>
  <article class="card page-card">
    <h3>Hazard Feeds</h3>
    <ul class="events-list">
      <li class="event-item"><strong>GET /api/tsunami.php</strong><br />Active tsunami alerts and warning levels.</li>
      <li class="event-item"><strong>GET /api/space-weather.php</strong><br />NOAA SWPC Kp stream and forecast summary.</li>
      <li class="event-item"><strong>GET /api/bulletins.php</strong><br />Institutional RSS/Atom bulletin aggregation.</li>
    </ul>
  </article>
  <article class="card page-card">
    <h3>Derived Feeds</h3>
    <ul class="events-list">
      <li class="event-item"><strong>GET /api/aftershocks.php</strong><br />Automatic aftershock sequences (M6+ mainshock trigger, 7d/150km tracking).</li>
      <li class="event-item"><strong>GET /api/hotspots.php</strong><br />Volcano hotspot ranking from volcano event snapshots.</li>
      <li class="event-item"><strong>GET /api/volcano-cams.php</strong><br />Curated volcano cams and stream metadata.</li>
      <li class="event-item"><strong>GET /api/earthquakes-archive.php</strong><br />Paginated historical earthquake archive with server-side filters.</li>
      <li class="event-item"><strong>GET /api/event-history.php</strong><br />Historical archive for an event zone (paginated + strongest in area).</li>
      <li class="event-item"><strong>GET /api/tectonic-context.php</strong><br />Tectonic plates and active faults (global or local focus).</li>
      <li class="event-item"><strong>GET /api/health.php</strong><br />Operational health summary (feed freshness + archive DB availability).</li>
    </ul>
  </article>
</section>

<section class="launch">
  <h3>Refresh Endpoint</h3>
  <p class="launch-copy">Force refresh all configured feeds in one call. For production, schedule refresh scripts by cron.</p>
  <ul class="events-list">
    <li class="event-item"><strong>GET /api/refresh.php?force_refresh=1&amp;token=YOUR_REFRESH_TOKEN</strong><br />Returns per-target refresh status (token required).</li>
    <li class="event-item"><strong>/scripts/refresh-feeds.sh</strong><br />Quick refresh for all operational feeds + tectonic context.</li>
    <li class="event-item"><strong>/scripts/prewarm-all.sh</strong><br />Deep refresh including event history hotspots by active seismic zones.</li>
  </ul>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
