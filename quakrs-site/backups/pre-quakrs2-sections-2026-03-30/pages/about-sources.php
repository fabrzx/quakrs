<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - About Sources';
$pageDescription = 'Primary data providers used by Quakrs.';
$currentPage = 'about-sources';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.about_sources.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.about_sources.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub">
      <?= htmlspecialchars(qk_t('page.about_sources.sub'), ENT_QUOTES, 'UTF-8'); ?>
    </p>
  </div>
</main>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Sources + Status bridge</h3>
    <p>Open <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/sources-status.php'), ENT_QUOTES, 'UTF-8'); ?>">Sources & Reliability</a> for a unified view of provenance, latency, freshness, and known feed limits.</p>
  </article>
  <article class="card page-card">
    <h3>Earthquakes</h3>
    <p>USGS, INGV and EMSC feeds are merged into one operational stream for map, timeline and regional activity modules.</p>
  </article>
  <article class="card page-card">
    <h3>Volcanoes</h3>
    <p>Smithsonian GVP weekly reports and observatory channels provide volcano activity and eruptive tracking context.</p>
  </article>
  <article class="card page-card">
    <h3>Tsunami Alerts</h3>
    <p>NOAA / NWS active alert feeds are filtered to tsunami-related notices with warning level and region extraction.</p>
  </article>
  <article class="card page-card">
    <h3>Space Weather</h3>
    <p>NOAA SWPC products drive Kp, geomagnetic storm level, X-ray class and short-term heliophysics status widgets.</p>
  </article>
  <article class="card page-card">
    <h3>Volcano Cams</h3>
    <p>Curated observatory camera endpoints include INGV, USGS HVO and partner institutions with source attribution.</p>
  </article>
  <article class="card page-card">
    <h3>Bulletins</h3>
    <p>Resources bulletins are institutional-only aggregates (Smithsonian, USGS, NOAA SWPC) without editorial rewrites.</p>
  </article>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
