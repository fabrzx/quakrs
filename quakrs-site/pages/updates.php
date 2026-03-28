<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Updates';
$pageDescription = 'Product updates and operational changelog for Quakrs.';
$currentPage = 'updates';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.updates.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.updates.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.updates.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Current phase</p>
    <p class="kpi-value">v2 rollout</p>
    <p class="kpi-note">Incremental architecture hardening with non-destructive migrations.</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Latest release date</p>
    <p class="kpi-value">2026-03-19</p>
    <p class="kpi-note">Federated archive entry + product updates page activation.</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Change type</p>
    <p class="kpi-value">Operational IA</p>
    <p class="kpi-note">Navigation, trust layers, and live-console structure updates.</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Transparency</p>
    <p class="kpi-value">Public log</p>
    <p class="kpi-note">Major product-level changes tracked here over time.</p>
  </article>
</section>

<section class="panel">
  <article class="card">
    <div class="feed-head">
      <h3>Recent updates</h3>
      <p class="feed-meta">Product-level changelog (not a news feed). Entries focus on structure, capability, and operational behavior.</p>
    </div>
    <ul class="events-list">
      <li class="event-item">
        <strong>2026-03-19 · Data status incident log (MVP)</strong><br />
        Added auto-generated incident log to <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/data-status.php'), ENT_QUOTES, 'UTF-8'); ?>">/data-status.php</a> with severity, active incident count, degraded-since hints, and persistent opened/updated/resolved history.
      </li>
      <li class="event-item">
        <strong>2026-03-19 · Alerts ranking hardening</strong><br />
        Refined alert taxonomy mapping and deterministic weighted ranking in <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/alerts.php'), ENT_QUOTES, 'UTF-8'); ?>">/alerts.php</a>, with visible in-page ranking rules.
      </li>
      <li class="event-item">
        <strong>2026-03-19 · Archive architecture update</strong><br />
        Added <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/archive.php'), ENT_QUOTES, 'UTF-8'); ?>">/archive.php</a> as federated multi-hazard archive entry.
        Kept <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/data-archive.php'), ENT_QUOTES, 'UTF-8'); ?>">/data-archive.php</a> as advanced seismic module.
      </li>
      <li class="event-item">
        <strong>2026-03-19 · Updates page activated</strong><br />
        Added <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/updates.php'), ENT_QUOTES, 'UTF-8'); ?>">/updates.php</a> in Info navigation for transparent product evolution tracking.
      </li>
      <li class="event-item">
        <strong>2026-03-19 · Search UX improvement</strong><br />
        Topbar quick search popup added, with direct jump to advanced search and prefilled query handoff.
      </li>
      <li class="event-item">
        <strong>2026-03-18 · Sources reliability bridge</strong><br />
        Added <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/sources-status.php'), ENT_QUOTES, 'UTF-8'); ?>">/sources-status.php</a> to connect source provenance with live technical feed health.
      </li>
      <li class="event-item">
        <strong>2026-03-18 · Core live v2 pages</strong><br />
        Activated <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/timeline.php'), ENT_QUOTES, 'UTF-8'); ?>">Timeline</a>, <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/alerts.php'), ENT_QUOTES, 'UTF-8'); ?>">Alerts</a>, <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/situation.php'), ENT_QUOTES, 'UTF-8'); ?>">Situation</a>, and <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/search.php'), ENT_QUOTES, 'UTF-8'); ?>">Search</a> pages.
      </li>
    </ul>
  </article>
</section>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>What goes here</h3>
    <p class="insight-lead">Architecture changes, monitoring capability additions, routing changes, reliability model changes, and major UX shifts.</p>
  </article>
  <article class="card page-card">
    <h3>What does not go here</h3>
    <p class="insight-lead">Breaking news, hazard alerts, and live events. Those remain in monitors, alerts, and timeline surfaces.</p>
  </article>
  <article class="card page-card">
    <h3>Related pages</h3>
    <p class="insight-lead"><a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/about-methodology.php'), ENT_QUOTES, 'UTF-8'); ?>">Methodology</a> · <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/sources-status.php'), ENT_QUOTES, 'UTF-8'); ?>">Sources & Reliability</a> · <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/data-status.php'), ENT_QUOTES, 'UTF-8'); ?>">Data Status</a></p>
  </article>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
