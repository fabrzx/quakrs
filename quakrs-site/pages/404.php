<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Page Not Found';
$pageDescription = 'Requested page was not found.';
$currentPage = 'home';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Error 404</p>
    <h1>Page Not Found.</h1>
    <p class="sub">The requested path is unavailable or has moved.</p>
  </div>
</main>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Suggested Actions</h3>
    <ul class="events-list">
      <li class="event-item">Go back to the live monitors home.</li>
      <li class="event-item">Open the Global Map dashboard.</li>
      <li class="event-item">Use Earthquakes live feed for latest events.</li>
    </ul>
    <p>
      <a class="inline-link" href="/">Open Home</a> ·
      <a class="inline-link" href="/maps.php">Open Maps</a> ·
      <a class="inline-link" href="/earthquakes.php">Open Earthquakes</a>
    </p>
  </article>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
