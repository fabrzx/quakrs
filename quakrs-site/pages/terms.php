<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Terms';
$pageDescription = 'Terms of use for Quakrs.com.';
$currentPage = 'about';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Legal / Terms</p>
    <h1>Terms Of Use.</h1>
    <p class="sub">Usage rules for Quakrs monitoring pages, API endpoints and operational content.</p>
  </div>
</main>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Informational Service</h3>
    <p>
      Quakrs provides operational monitoring and situational awareness content. It does not replace official emergency
      instructions or local authority warnings.
    </p>
  </article>
  <article class="card page-card">
    <h3>No Safety Guarantee</h3>
    <p>
      Data is sourced from third-party institutional feeds and can be delayed, revised or unavailable. Always verify
      critical decisions with official agencies.
    </p>
  </article>
  <article class="card page-card">
    <h3>Acceptable Use</h3>
    <p>
      Automated scraping, abusive traffic, service disruption attempts and unauthorized security testing are prohibited
      unless explicitly authorized.
    </p>
  </article>
  <article class="card page-card">
    <h3>API Use</h3>
    <p>
      Public API usage must remain reasonable and must not degrade platform availability. Endpoint behavior and limits
      may change to protect service integrity.
    </p>
  </article>
  <article class="card page-card">
    <h3>Content & Attribution</h3>
    <p>
      Provider names, marks and data remain property of their respective owners. Reuse should preserve source
      attribution and context.
    </p>
  </article>
  <article class="card page-card">
    <h3>Changes</h3>
    <p>
      These terms may be updated over time. Continued use of Quakrs after changes indicates acceptance of the updated
      terms.
    </p>
  </article>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
