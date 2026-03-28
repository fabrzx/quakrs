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
    <h3>P1 (Critical)</h3>
    <p>Highest operational urgency. Use it to prioritize immediate cross-hazard attention and escalation checks.</p>
  </article>
  <article class="card page-card">
    <h3>P2 (Elevated)</h3>
    <p>Strong signal under active watch. It requires tracking and can escalate to P1 if context worsens.</p>
  </article>
  <article class="card page-card">
    <h3>P3 (Baseline)</h3>
    <p>Background monitoring level. Still visible in vertical monitors, usually not promoted in high-priority lanes.</p>
  </article>
</section>

<section class="panel">
  <article class="card recent-card">
    <div class="feed-head">
      <h3>Operational Rules (Escalation / De-Escalation)</h3>
      <p class="feed-meta">How a level changes over time</p>
    </div>
    <ul class="events-list">
      <li class="event-item">
        <strong>1. Escalate to P1:</strong> Triggered when hazard-specific severity reaches critical thresholds or active warning context appears.
      </li>
      <li class="event-item">
        <strong>2. Keep at P2:</strong> Elevated but not critical. Event remains monitored with explicit watch posture and frequent refresh checks.
      </li>
      <li class="event-item">
        <strong>3. De-escalate to P3:</strong> Applied when intensity and update velocity both decline and no critical context remains active.
      </li>
      <li class="event-item">
        <strong>4. Rank inside level:</strong> Quakrs orders events by score and recency inside each priority class.
      </li>
    </ul>
  </article>
</section>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Earthquakes</h3>
    <ul class="events-list">
      <li class="event-item"><strong>P1:</strong> magnitude >= 6.8, or magnitude >= 6.2 within ~120 minutes.</li>
      <li class="event-item"><strong>P2:</strong> magnitude >= 5.0 when P1 conditions are not met.</li>
      <li class="event-item"><strong>Score drivers:</strong> magnitude, recency boost, shallow-depth bonus.</li>
      <li class="event-item"><strong>Typical de-escalation:</strong> reduced follow-up intensity + no new critical updates.</li>
    </ul>
  </article>
  <article class="card page-card">
    <h3>Volcanoes</h3>
    <ul class="events-list">
      <li class="event-item"><strong>P1:</strong> strongest new eruptive signal during high eruptive cycle.</li>
      <li class="event-item"><strong>P2:</strong> new eruptive items or high-report bulletin cycles.</li>
      <li class="event-item"><strong>Score drivers:</strong> eruptive flag, bulletin rank, cycle intensity.</li>
      <li class="event-item"><strong>Typical de-escalation:</strong> bulletin cycle cool-down without strong eruptive updates.</li>
    </ul>
  </article>
  <article class="card page-card">
    <h3>Tsunami / Space Weather</h3>
    <ul class="events-list">
      <li class="event-item"><strong>Tsunami:</strong> active advisories can escalate directly to P1.</li>
      <li class="event-item"><strong>Space weather:</strong> Kp and storm tier determine P1/P2/P3 level.</li>
      <li class="event-item"><strong>Goal:</strong> maintain comparable urgency across different hazard types.</li>
      <li class="event-item"><strong>Typical de-escalation:</strong> warning/watch closure or geomagnetic decline below storming tiers.</li>
    </ul>
  </article>
</section>

<section class="panel">
  <article class="card recent-card">
    <div class="feed-head">
      <h3>Priority vs Official Alert Level</h3>
      <p class="feed-meta">Do not treat them as identical fields</p>
    </div>
    <ul class="events-list">
      <li class="event-item"><strong>Priority (Quakrs):</strong> internal cross-hazard ranking used to order attention lanes.</li>
      <li class="event-item"><strong>Alert Level (official):</strong> authority-specific classification emitted by institutional sources.</li>
      <li class="event-item"><strong>Why they differ:</strong> a strong signal can be high-priority in Quakrs even before formal alert escalation.</li>
      <li class="event-item"><strong>Operator rule:</strong> use Priority for triage, then verify official advisory details in source-linked monitor pages.</li>
    </ul>
  </article>
</section>

<section class="panel">
  <article class="card recent-card">
    <div class="feed-head">
      <h3>Where Priority Is Visible</h3>
      <p class="feed-meta">Operational navigation map</p>
    </div>
    <ul class="events-list">
      <li class="event-item"><strong>Home:</strong> top critical lanes and priority stream.</li>
      <li class="event-item"><strong>Timeline:</strong> chronological stream with hazard and priority filters.</li>
      <li class="event-item"><strong>Alerts:</strong> active advisories ranked by level and urgency.</li>
      <li class="event-item"><strong>Monitors:</strong> domain context beyond priority labels.</li>
    </ul>
    <p>
      <a class="inline-link" href="/timeline.php">Open Timeline</a> ·
      <a class="inline-link" href="/alerts.php">Open Alerts</a> ·
      <a class="inline-link" href="/data-status.php">Open Data Status</a>
    </p>
  </article>
</section>

<section class="panel">
  <article class="card recent-card">
    <div class="feed-head">
      <h3>Limits And Caveats</h3>
      <p class="feed-meta">Operational transparency</p>
    </div>
    <p class="insight-lead">
      Priority is a live operational ranking, not a legal warning system. Data latency, feed gaps,
      and cross-hazard normalization limits can affect ordering. For local protective decisions,
      always confirm official authority guidance.
    </p>
  </article>
</section>

<section class="panel">
  <article class="card recent-card">
    <div class="feed-head">
      <h3>Methodology Reference</h3>
      <p class="feed-meta">Full technical background</p>
    </div>
    <p class="insight-lead">
      Thresholds and score weighting evolve with operational calibration. Use this page for quick
      interpretation and open methodology for the complete technical rationale.
    </p>
    <a class="inline-link" href="/about-methodology.php">Open full methodology</a>
  </article>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
