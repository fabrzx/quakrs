<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Safety Guides';
$pageDescription = 'Operational hazard safety guidance links and immediate actions.';
$currentPage = 'resources-safety';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Resources / Safety Guides</p>
    <h1>Immediate Hazard Safety Guides.</h1>
    <p class="sub">Quick action checklists and official guidance links for earthquakes, tsunami and volcanic ash.</p>
  </div>
</main>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Earthquake</h3>
    <ul class="events-list">
      <li class="event-item"><strong>During shaking:</strong> Drop, Cover, Hold On.</li>
      <li class="event-item"><strong>After shaking:</strong> Check injuries, gas leaks and unstable structures.</li>
      <li class="event-item"><strong>Aftershocks:</strong> Expect additional shaking and avoid damaged buildings.</li>
      <li class="event-item"><strong>Official guidance:</strong> <a class="inline-link" href="https://www.ready.gov/earthquakes" target="_blank" rel="noopener noreferrer">Ready.gov Earthquakes</a></li>
    </ul>
  </article>
  <article class="card page-card">
    <h3>Tsunami</h3>
    <ul class="events-list">
      <li class="event-item"><strong>If near coast and strong quake:</strong> Move immediately to higher ground.</li>
      <li class="event-item"><strong>Do not return:</strong> Wait for official all-clear bulletin.</li>
      <li class="event-item"><strong>Natural warning signs:</strong> Rapid sea retreat or unusual roar require immediate evacuation.</li>
      <li class="event-item"><strong>Official guidance:</strong> <a class="inline-link" href="https://www.tsunami.gov/" target="_blank" rel="noopener noreferrer">NOAA Tsunami.gov</a></li>
    </ul>
  </article>
  <article class="card page-card">
    <h3>Volcanic Ash</h3>
    <ul class="events-list">
      <li class="event-item"><strong>Shelter:</strong> Stay indoors and close windows/vents.</li>
      <li class="event-item"><strong>Outside:</strong> Use mask and eye protection.</li>
      <li class="event-item"><strong>Driving:</strong> Reduce speed, avoid heavy ash areas and protect air intakes.</li>
      <li class="event-item"><strong>Official guidance:</strong> <a class="inline-link" href="https://www.usgs.gov/programs/VHP/volcanic-hazards" target="_blank" rel="noopener noreferrer">USGS Volcanic Hazards</a></li>
    </ul>
  </article>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
