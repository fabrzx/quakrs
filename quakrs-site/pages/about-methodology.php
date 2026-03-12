<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - About Methodology';
$pageDescription = 'Normalization, caching and publication methodology.';
$currentPage = 'about-methodology';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow">About / Methodology</p>
    <h1>Pipeline, Caching &amp; Output Rules.</h1>
    <p class="sub">
      The frontend reads local API outputs only. Feed ingestion, normalization and cache refresh
      are handled server-side to keep UI responses consistent and fast.
    </p>
  </div>
</main>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>API-First Frontend</h3>
    <p>Pages consume `/api/*.php` endpoints and never call third-party feeds directly from the browser.</p>
  </article>
  <article class="card page-card">
    <h3>Normalization</h3>
    <p>Earthquake records are harmonized on magnitude, depth, UTC time, coordinates and provider attribution before rendering.</p>
  </article>
  <article class="card page-card">
    <h3>Cache Strategy</h3>
    <p>Each feed writes local JSON snapshots in `/data`; APIs serve cache first and expose stale flags when upstream is degraded.</p>
  </article>
  <article class="card page-card">
    <h3>Refresh Cadence</h3>
    <p>The refresh endpoint rotates earthquakes, volcanoes, tremors, tsunami, space weather, cams and bulletin snapshots.</p>
  </article>
  <article class="card page-card">
    <h3>Map Rendering</h3>
    <p>Maps reuse normalized earthquake payloads for global, heatmap, plates and depth views to keep hazard views consistent.</p>
  </article>
  <article class="card page-card">
    <h3>Cluster Logic</h3>
    <p>Cluster pages summarize tremor signal density by time/region windows, highlighting active zones and peak-hour behavior.</p>
  </article>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
