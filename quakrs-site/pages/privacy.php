<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Privacy';
$pageDescription = 'Privacy notice for Quakrs.com.';
$currentPage = 'about';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Legal / Privacy</p>
    <h1>Privacy Notice.</h1>
    <p class="sub">How Quakrs handles technical logs, operational metrics and external data sources.</p>
  </div>
</main>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Data We Process</h3>
    <p>
      Quakrs is an operational monitoring interface. We aggregate public hazard feeds and process minimal technical
      request data required for security, uptime and abuse prevention.
    </p>
  </article>
  <article class="card page-card">
    <h3>No Account Tracking</h3>
    <p>
      The website does not provide user account features in the current version. No profile, password or private user
      dashboard data is collected by default.
    </p>
  </article>
  <article class="card page-card">
    <h3>External Sources</h3>
    <p>
      Hazard content comes from institutional providers listed in Sources. Their own privacy policies apply when you
      open external links.
    </p>
  </article>
  <article class="card page-card">
    <h3>Operational Logs</h3>
    <p>
      Server and API logs may include timestamp, route, status code and anonymized technical diagnostics for reliability
      and incident response.
    </p>
  </article>
  <article class="card page-card">
    <h3>Cookies</h3>
    <p>
      At this stage, Quakrs does not rely on advertising cookies. Essential technical storage may be used only for
      runtime behavior and service continuity.
    </p>
  </article>
  <article class="card page-card">
    <h3>Contact & Updates</h3>
    <p>
      This notice can be updated as platform capabilities evolve. Material changes should be reflected on this page.
    </p>
  </article>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
