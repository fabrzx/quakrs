<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Data Status';
$pageDescription = 'Feed freshness and operational status for Quakrs data sources.';
$currentPage = 'data-status';

$feedFiles = [
    ['label' => 'Earthquakes', 'file' => __DIR__ . '/../data/earthquakes_latest.json', 'maxAgeMinutes' => 10],
    ['label' => 'Volcanoes', 'file' => __DIR__ . '/../data/volcanoes_latest.json', 'maxAgeMinutes' => 20],
    ['label' => 'Tremors', 'file' => __DIR__ . '/../data/tremors_latest.json', 'maxAgeMinutes' => 20],
    ['label' => 'Tsunami', 'file' => __DIR__ . '/../data/tsunami_latest.json', 'maxAgeMinutes' => 30],
    ['label' => 'Space Weather', 'file' => __DIR__ . '/../data/space_weather_latest.json', 'maxAgeMinutes' => 20],
    ['label' => 'Bulletins', 'file' => __DIR__ . '/../data/bulletins_latest.json', 'maxAgeMinutes' => 30],
    ['label' => 'Hotspots', 'file' => __DIR__ . '/../data/hotspots_latest.json', 'maxAgeMinutes' => 30],
    ['label' => 'Volcano Cams', 'file' => __DIR__ . '/../data/volcano_cams_latest.json', 'maxAgeMinutes' => 45],
];

$now = time();
$statusRows = [];
$healthyCount = 0;
$warnCount = 0;
$errorCount = 0;

foreach ($feedFiles as $feed) {
    $path = $feed['file'];
    $exists = is_file($path);
    if (!$exists) {
        $statusRows[] = [
            'label' => $feed['label'],
            'status' => 'Missing',
            'detail' => 'File not found',
            'ageMinutes' => null,
            'sizeKb' => null,
        ];
        $errorCount++;
        continue;
    }

    $mtime = filemtime($path);
    $ageMinutes = $mtime ? (int) floor(max(0, $now - $mtime) / 60) : null;
    $sizeBytes = filesize($path);
    $sizeKb = $sizeBytes !== false ? round($sizeBytes / 1024, 1) : null;
    $maxAge = (int) $feed['maxAgeMinutes'];

    if ($ageMinutes === null) {
        $status = 'Unknown';
        $detail = 'Unable to read file timestamp';
        $warnCount++;
    } elseif ($ageMinutes <= $maxAge) {
        $status = 'Healthy';
        $detail = 'Fresh feed';
        $healthyCount++;
    } elseif ($ageMinutes <= ($maxAge * 3)) {
        $status = 'Lagging';
        $detail = 'Older than expected';
        $warnCount++;
    } else {
        $status = 'Stale';
        $detail = 'Likely ingestion issue';
        $errorCount++;
    }

    $statusRows[] = [
        'label' => $feed['label'],
        'status' => $status,
        'detail' => $detail,
        'ageMinutes' => $ageMinutes,
        'sizeKb' => $sizeKb,
    ];
}

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Data / Status</p>
    <h1>Feed Freshness Console.</h1>
    <p class="sub">Operational status and update latency for core ingestion outputs.</p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Healthy</p>
    <p class="kpi-value"><?= $healthyCount; ?></p>
    <p class="kpi-note">Within target window</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Lagging</p>
    <p class="kpi-value"><?= $warnCount; ?></p>
    <p class="kpi-note">Needs attention soon</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Stale/Missing</p>
    <p class="kpi-value"><?= $errorCount; ?></p>
    <p class="kpi-note">Check ingestion jobs</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Feeds Checked</p>
    <p class="kpi-value"><?= count($statusRows); ?></p>
    <p class="kpi-note">Local cache files</p>
  </article>
</section>

<section class="panel">
  <article class="card">
    <div class="feed-head">
      <h3>Feed Status</h3>
      <p class="feed-meta">Status derives from file age thresholds per feed type.</p>
    </div>
    <ul class="events-list">
      <?php foreach ($statusRows as $row): ?>
        <li class="event-item">
          <strong><?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?></strong><br />
          <?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?> - <?= htmlspecialchars($row['detail'], ENT_QUOTES, 'UTF-8'); ?>
          <?php if ($row['ageMinutes'] !== null): ?>
            - Age: <?= (int) $row['ageMinutes']; ?> min
          <?php endif; ?>
          <?php if ($row['sizeKb'] !== null): ?>
            - Size: <?= (float) $row['sizeKb']; ?> KB
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </article>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
