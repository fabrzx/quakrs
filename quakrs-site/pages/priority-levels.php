<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Priority Levels P1/P2';
$pageDescription = 'How Quakrs priority levels work: P1, P2 and ranking logic across earthquakes, volcanoes, tsunami and space weather.';
$currentPage = 'priority-levels';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.priority_levels.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.priority_levels.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub">
      <?= htmlspecialchars(qk_t('page.priority_levels.sub'), ENT_QUOTES, 'UTF-8'); ?>
    </p>
  </div>
</main>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>What P1 Means</h3>
    <p>P1 is a critical signal. It receives top visibility in the Priority board and can open single, dual or triple critical mode.</p>
  </article>
  <article class="card page-card">
    <h3>What P2 Means</h3>
    <p>P2 is a strong signal that does not meet P1 thresholds. It appears in Attention watch and in the Priority stream.</p>
  </article>
  <article class="card page-card">
    <h3>What P3 Means</h3>
    <p>P3 is baseline monitoring. It is still available in monitors, but usually excluded from high-priority homepage slots.</p>
  </article>
</section>

<section class="panel">
  <article class="card recent-card">
    <div class="feed-head">
      <h3>How Events Are Ranked</h3>
      <p class="feed-meta">Cross-domain normalization</p>
    </div>
    <ul class="events-list">
      <li class="event-item">
        <strong>1. Normalize per hazard:</strong> Earthquakes, volcanoes, tsunami and space weather are transformed into a common event model.
      </li>
      <li class="event-item">
        <strong>2. Assign a priority level:</strong> Each event is tagged P1, P2 or P3 using hazard-specific operational thresholds.
      </li>
      <li class="event-item">
        <strong>3. Compute score:</strong> Within the same level, events are ordered by score and recency.
      </li>
      <li class="event-item">
        <strong>4. Build homepage blocks:</strong> Priority board receives the top ranked set, then remaining events feed Priority stream.
      </li>
    </ul>
  </article>
</section>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Earthquakes (Current Logic)</h3>
    <ul class="events-list">
      <li class="event-item"><strong>P1:</strong> magnitude >= 6.8, or magnitude >= 6.2 within ~120 minutes.</li>
      <li class="event-item"><strong>P2:</strong> magnitude >= 5.0 when P1 conditions are not met.</li>
      <li class="event-item"><strong>Score drivers:</strong> magnitude, recency boost, shallow-depth bonus.</li>
    </ul>
  </article>
  <article class="card page-card">
    <h3>Volcanoes (Current Logic)</h3>
    <ul class="events-list">
      <li class="event-item"><strong>P1:</strong> strongest new eruptive signal during high eruptive cycle.</li>
      <li class="event-item"><strong>P2:</strong> new eruptive items or high-report bulletin cycles.</li>
      <li class="event-item"><strong>Score drivers:</strong> eruptive flag, bulletin rank, cycle intensity.</li>
    </ul>
  </article>
  <article class="card page-card">
    <h3>Tsunami / Space Weather</h3>
    <ul class="events-list">
      <li class="event-item"><strong>Tsunami:</strong> active advisories can escalate directly to P1.</li>
      <li class="event-item"><strong>Space weather:</strong> Kp and storm tier determine P1/P2/P3 level.</li>
      <li class="event-item"><strong>Goal:</strong> maintain comparable urgency across different hazard types.</li>
    </ul>
  </article>
</section>

<section class="panel">
  <article class="card recent-card">
    <div class="feed-head">
      <h3>Why You Can See Mixed Regions</h3>
      <p class="feed-meta">Global ranking behavior</p>
    </div>
    <p class="insight-lead">
      P1/P2 sections rank globally by urgency, not by a single country. A heading such as
      "regional focus" can coexist with events from other regions if those events have stronger
      global priority. When map viewport filtering is enabled, list panels can instead follow your
      current geographic zoom area.
    </p>
  </article>
</section>

<section class="panel">
  <article class="card recent-card">
    <div class="feed-head">
      <h3>Where To Read P1/P2 On Home</h3>
      <p class="feed-meta">Quick visual guide</p>
    </div>
    <ul class="events-list">
      <li class="event-item"><strong>Priority board:</strong> top critical lane (P1 first, otherwise strongest P2).</li>
      <li class="event-item"><strong>Attention watch:</strong> secondary lanes, usually P2.</li>
      <li class="event-item"><strong>Priority stream:</strong> continuous P2 and P1 feed sorted by current ranking.</li>
    </ul>
    <a class="inline-link" href="/about-methodology.php">Open full methodology</a>
  </article>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
