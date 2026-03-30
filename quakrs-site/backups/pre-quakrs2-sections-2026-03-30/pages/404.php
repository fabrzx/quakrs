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
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.404.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.404.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.404.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel page-grid">
  <article class="card page-card">
    <h3><?= htmlspecialchars(qk_t('page.404.suggested_actions'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <ul class="events-list">
      <li class="event-item"><?= htmlspecialchars(qk_t('page.404.action_home'), ENT_QUOTES, 'UTF-8'); ?></li>
      <li class="event-item"><?= htmlspecialchars(qk_t('page.404.action_maps'), ENT_QUOTES, 'UTF-8'); ?></li>
      <li class="event-item"><?= htmlspecialchars(qk_t('page.404.action_earthquakes'), ENT_QUOTES, 'UTF-8'); ?></li>
    </ul>
    <p>
      <a class="inline-link" href="/"><?= htmlspecialchars(qk_t('page.404.open_home'), ENT_QUOTES, 'UTF-8'); ?></a> ·
      <a class="inline-link" href="/maps.php"><?= htmlspecialchars(qk_t('page.404.open_maps'), ENT_QUOTES, 'UTF-8'); ?></a> ·
      <a class="inline-link" href="/earthquakes.php"><?= htmlspecialchars(qk_t('page.404.open_earthquakes'), ENT_QUOTES, 'UTF-8'); ?></a>
    </p>
  </article>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
