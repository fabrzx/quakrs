<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Spiegazione energia sismica';
$pageDescription = 'Simple explanation of seismic energy estimate shown in Quakrs.';
$currentPage = 'about-energy';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.about_energy.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.about_energy.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.about_energy.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>In one line</h3>
    <p>
      The energy value is an estimate of how much seismic energy was released by all earthquakes
      in the selected time window (usually last 24h).
    </p>
  </article>
  <article class="card page-card">
    <h3>Why it matters</h3>
    <p>
      Event count alone can be misleading. Many small quakes may look busy but release less energy than
      one stronger quake. Energy helps you read the real intensity of activity.
    </p>
  </article>
  <article class="card page-card">
    <h3>How we estimate it</h3>
    <p>
      For each event with a valid magnitude, Quakrs applies a standard seismology relation:
      <strong> log10(E) = 1.5M + 4.8 </strong> (E in joules). Then all events are summed.
    </p>
  </article>
</section>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Readable units</h3>
    <p>
      We convert joules into larger units for readability:
      <strong>GJ</strong> (billion J), <strong>TJ</strong> (trillion J), <strong>PJ</strong> (quadrillion J).
      Example: if you see <strong>24.24 TJ</strong>, it means about 24.24 trillion joules.
    </p>
  </article>
  <article class="card page-card">
    <h3>Important limits</h3>
    <p>
      This is an operational estimate, not a laboratory measurement. It depends on catalog completeness,
      event magnitude revisions, and provider differences. Use it as a robust indicator, not an exact physical audit.
    </p>
  </article>
  <article class="card page-card">
    <h3>How to read it fast</h3>
    <p>
      High event count + low energy: mostly micro to moderate activity.
      Lower count + high energy: one or few stronger events dominate.
      Best practice: read energy together with max magnitude and baseline delta.
    </p>
  </article>
</section>

<section class="panel">
  <article class="card page-card">
    <h3>Back to dashboard</h3>
    <p>Return to the Energy page to see the value in context with baseline and trend charts.</p>
    <a href="/data-energy.php" class="inline-link"><?= htmlspecialchars(qk_t('page.about_energy.open_data_energy'), ENT_QUOTES, 'UTF-8'); ?></a>
  </article>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
