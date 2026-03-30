<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Archive';
$pageDescription = 'Federated multi-hazard archive entry for Quakrs.';
$currentPage = 'archive';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.archive.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.archive.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.archive.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Model</p>
    <p class="kpi-value">Federated</p>
    <p class="kpi-note">One entry point, specialized hazard archives behind it.</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Earthquake depth</p>
    <p class="kpi-value">Advanced</p>
    <p class="kpi-note">Full filter + map workflow remains in the seismic archive.</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Coverage now</p>
    <p class="kpi-value">4 hazards</p>
    <p class="kpi-note">Earthquakes, volcanoes, tsunami, space weather via linked datasets.</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Legacy route</p>
    <p class="kpi-value">Active</p>
    <p class="kpi-note"><a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/data-archive.php'), ENT_QUOTES, 'UTF-8'); ?>">/data-archive.php</a> remains stable.</p>
  </article>
</section>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Earthquakes (specialized archive)</h3>
    <p class="insight-lead">Deep historical filters by time window, radius, depth, and magnitude.</p>
    <p><a class="btn btn-ghost" href="<?= htmlspecialchars(qk_localized_url('/data-archive.php'), ENT_QUOTES, 'UTF-8'); ?>">Open seismic archive</a></p>
  </article>
  <article class="card page-card">
    <h3>Italy seismic data</h3>
    <p class="insight-lead">Dedicated Italy monitor with local trends and swarm context.</p>
    <p><a class="btn btn-ghost" href="<?= htmlspecialchars(qk_localized_url('/data-italia.php'), ENT_QUOTES, 'UTF-8'); ?>">Open Italy data</a></p>
  </article>
  <article class="card page-card">
    <h3>Volcano history stream</h3>
    <p class="insight-lead">Operational volcano activity timeline from bulletin-driven data.</p>
    <p><a class="btn btn-ghost" href="<?= htmlspecialchars(qk_localized_url('/volcanoes.php'), ENT_QUOTES, 'UTF-8'); ?>">Open volcano monitor</a></p>
  </article>
  <article class="card page-card">
    <h3>Tsunami advisories history</h3>
    <p class="insight-lead">Review active and recent advisory contexts from institutional feeds.</p>
    <p><a class="btn btn-ghost" href="<?= htmlspecialchars(qk_localized_url('/alerts.php'), ENT_QUOTES, 'UTF-8'); ?>">Open alerts center</a></p>
  </article>
  <article class="card page-card">
    <h3>Space weather archive context</h3>
    <p class="insight-lead">Kp and flare-driven context through the space weather console.</p>
    <p><a class="btn btn-ghost" href="<?= htmlspecialchars(qk_localized_url('/space-weather.php'), ENT_QUOTES, 'UTF-8'); ?>">Open space weather</a></p>
  </article>
  <article class="card page-card">
    <h3>Reports and bulletins</h3>
    <p class="insight-lead">Aggregate situational reports and institutional bulletin history.</p>
    <p><a class="btn btn-ghost" href="<?= htmlspecialchars(qk_localized_url('/data-reports.php'), ENT_QUOTES, 'UTF-8'); ?>">Open reports</a> <a class="btn btn-ghost" href="<?= htmlspecialchars(qk_localized_url('/resources-bulletins.php'), ENT_QUOTES, 'UTF-8'); ?>">Open bulletins</a></p>
  </article>
</section>

<section class="panel">
  <article class="card">
    <div class="feed-head">
      <h3>Federated archive logic</h3>
      <p class="feed-meta">Use this page to choose the right depth: broad cross-hazard orientation first, specialized archive tooling second.</p>
    </div>
    <ul class="events-list">
      <li class="event-item"><strong>Step 1</strong><br />Pick hazard scope (earthquakes, volcanoes, tsunami, space weather).</li>
      <li class="event-item"><strong>Step 2</strong><br />Open the dedicated archive/monitor page for full filters and context.</li>
      <li class="event-item"><strong>Step 3</strong><br />Cross-check global chronology via <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/timeline.php'), ENT_QUOTES, 'UTF-8'); ?>">Timeline</a> and active severity via <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/alerts.php'), ENT_QUOTES, 'UTF-8'); ?>">Alerts</a>.</li>
    </ul>
  </article>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
