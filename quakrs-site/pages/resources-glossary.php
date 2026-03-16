<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Glossary';
$pageDescription = 'Monitoring glossary for seismic, tsunami and space-weather terms.';
$currentPage = 'resources-glossary';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.resources_glossary.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.resources_glossary.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.resources_glossary.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Seismic Terms</h3>
    <ul class="events-list">
      <li class="event-item"><strong>Magnitude (M):</strong> Logarithmic measure of released seismic energy.</li>
      <li class="event-item"><strong>Intensity (MMI):</strong> Observed shaking impact at a location, not total released energy.</li>
      <li class="event-item"><strong>Hypocenter:</strong> Subsurface origin point of rupture.</li>
      <li class="event-item"><strong>Epicenter:</strong> Surface point directly above the hypocenter.</li>
      <li class="event-item"><strong>Depth:</strong> Hypocenter depth in kilometers.</li>
    </ul>
  </article>
  <article class="card page-card">
    <h3>Tsunami Terms</h3>
    <ul class="events-list">
      <li class="event-item"><strong>Warning:</strong> Hazard expected or occurring, immediate protective action required.</li>
      <li class="event-item"><strong>Watch:</strong> Potential hazard under evaluation, be prepared to evacuate.</li>
      <li class="event-item"><strong>Advisory:</strong> Strong currents or dangerous waves possible.</li>
      <li class="event-item"><strong>Statement:</strong> Informational update without immediate severe impact.</li>
      <li class="event-item"><strong>All-clear:</strong> Official notice indicating hazardous wave threat has ended.</li>
    </ul>
  </article>
  <article class="card page-card">
    <h3>Space Weather Terms</h3>
    <ul class="events-list">
      <li class="event-item"><strong>Kp Index:</strong> Planetary geomagnetic disturbance index (0 to 9).</li>
      <li class="event-item"><strong>G-Scale:</strong> NOAA geomagnetic storm classification (G1 to G5).</li>
      <li class="event-item"><strong>Solar Wind:</strong> Charged particles emitted by the Sun.</li>
      <li class="event-item"><strong>CME:</strong> Coronal mass ejection that can trigger geomagnetic storms.</li>
      <li class="event-item"><strong>X-ray Class:</strong> Solar flare intensity class (A, B, C, M, X) from GOES flux.</li>
    </ul>
  </article>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
