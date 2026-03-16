<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/i18n.php';
qk_boot_i18n();

$pageTitle = 'Quakrs.com - Data Status';
$pageDescription = 'Feed freshness and operational status for Quakrs data sources.';
$currentPage = 'data-status';

function feed_generated_timestamp(string $path): ?int
{
    if (!is_file($path)) {
        return null;
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw) || $raw === '') {
        return null;
    }

    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        return null;
    }

    if (isset($payload['generated_at_ts']) && is_numeric($payload['generated_at_ts'])) {
        return (int) $payload['generated_at_ts'];
    }

    if (isset($payload['generated_at']) && is_string($payload['generated_at'])) {
        $ts = strtotime($payload['generated_at']);
        if (is_int($ts)) {
            return $ts;
        }
    }

    return null;
}

$feedFiles = [
    ['label' => 'Earthquakes', 'file' => __DIR__ . '/../data/earthquakes_latest.json', 'maxAgeMinutes' => 10],
    ['label' => 'Aftershocks', 'file' => __DIR__ . '/../data/aftershocks_index_latest.json', 'maxAgeMinutes' => 10],
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
            'status' => qk_t('data_status.status_missing'),
            'detail' => qk_t('data_status.detail_missing'),
            'ageMinutes' => null,
            'sizeKb' => null,
        ];
        $errorCount++;
        continue;
    }

    $mtime = filemtime($path);
    $generatedTs = feed_generated_timestamp($path);
    $referenceTs = is_int($generatedTs) && $generatedTs > 0
        ? $generatedTs
        : ($mtime ? (int) $mtime : null);
    $ageMinutes = is_int($referenceTs) ? (int) floor(max(0, $now - $referenceTs) / 60) : null;
    $sizeBytes = filesize($path);
    $sizeKb = $sizeBytes !== false ? round($sizeBytes / 1024, 1) : null;
    $maxAge = (int) $feed['maxAgeMinutes'];

    if ($ageMinutes === null) {
        $status = qk_t('data_status.status_unknown');
        $detail = qk_t('data_status.detail_unknown');
        $warnCount++;
    } elseif ($ageMinutes <= $maxAge) {
        $status = qk_t('data_status.status_healthy');
        $detail = qk_t('data_status.detail_healthy');
        $healthyCount++;
    } elseif ($ageMinutes <= ($maxAge * 3)) {
        $status = qk_t('data_status.status_lagging');
        $detail = qk_t('data_status.detail_lagging');
        $warnCount++;
    } else {
        $status = qk_t('data_status.status_outdated');
        $detail = qk_t('data_status.detail_outdated');
        $errorCount++;
    }

    $statusRows[] = [
        'label' => $feed['label'],
        'status' => $status,
        'detail' => $detail,
        'ageMinutes' => $ageMinutes,
        'sizeKb' => $sizeKb,
        'ageSource' => is_int($generatedTs) ? 'payload' : 'file',
    ];
}

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('data_status.hero_eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('data_status.hero_title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('data_status.hero_sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label"><?= htmlspecialchars(qk_t('data_status.kpi_healthy'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p class="kpi-value"><?= $healthyCount; ?></p>
    <p class="kpi-note"><?= htmlspecialchars(qk_t('data_status.kpi_healthy_note'), ENT_QUOTES, 'UTF-8'); ?></p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label"><?= htmlspecialchars(qk_t('data_status.kpi_lagging'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p class="kpi-value"><?= $warnCount; ?></p>
    <p class="kpi-note"><?= htmlspecialchars(qk_t('data_status.kpi_lagging_note'), ENT_QUOTES, 'UTF-8'); ?></p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label"><?= htmlspecialchars(qk_t('data_status.kpi_outdated'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p class="kpi-value"><?= $errorCount; ?></p>
    <p class="kpi-note"><?= htmlspecialchars(qk_t('data_status.kpi_outdated_note'), ENT_QUOTES, 'UTF-8'); ?></p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label"><?= htmlspecialchars(qk_t('data_status.kpi_checked'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p class="kpi-value"><?= count($statusRows); ?></p>
    <p class="kpi-note"><?= htmlspecialchars(qk_t('data_status.kpi_checked_note'), ENT_QUOTES, 'UTF-8'); ?></p>
  </article>
</section>

<section class="panel">
  <article class="card">
    <div class="feed-head">
      <h3><?= htmlspecialchars(qk_t('data_status.health_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p class="feed-meta"><?= htmlspecialchars(qk_t('data_status.health_sub'), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div class="page-grid">
      <div class="event-item">
        <strong><?= htmlspecialchars(qk_t('data_status.health_overall'), ENT_QUOTES, 'UTF-8'); ?></strong><br />
        <span id="health-overall"><?= htmlspecialchars(qk_t('data_status.health_loading'), ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <div class="event-item">
        <strong><?= htmlspecialchars(qk_t('data_status.health_archive'), ENT_QUOTES, 'UTF-8'); ?></strong><br />
        <span id="health-archive">--</span>
      </div>
      <div class="event-item">
        <strong><?= htmlspecialchars(qk_t('data_status.health_feeds'), ENT_QUOTES, 'UTF-8'); ?></strong><br />
        <span id="health-counts">--</span>
      </div>
    </div>
    <div id="health-feed-pills" class="insight-pills" style="margin-top:0.8rem">
      <span class="insight-pill"><?= htmlspecialchars(qk_t('data_status.health_loading'), ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
  </article>
</section>

<section class="panel">
  <article class="card">
    <div class="feed-head">
      <h3><?= htmlspecialchars(qk_t('data_status.status_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p class="feed-meta"><?= htmlspecialchars(qk_t('data_status.status_sub'), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <ul class="events-list">
      <?php foreach ($statusRows as $row): ?>
        <li class="event-item">
          <strong><?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?></strong><br />
          <?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?> - <?= htmlspecialchars($row['detail'], ENT_QUOTES, 'UTF-8'); ?>
          <?php if ($row['ageMinutes'] !== null): ?>
            - Age: <?= (int) $row['ageMinutes']; ?> min (<?= htmlspecialchars($row['ageSource'] === 'payload' ? qk_t('data_status.age_payload') : qk_t('data_status.age_file'), ENT_QUOTES, 'UTF-8'); ?>)
          <?php endif; ?>
          <?php if ($row['sizeKb'] !== null): ?>
            - Size: <?= (float) $row['sizeKb']; ?> KB
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </article>
</section>

<script>
  (() => {
    const overallNode = document.querySelector("#health-overall");
    const archiveNode = document.querySelector("#health-archive");
    const countsNode = document.querySelector("#health-counts");
    const pillsNode = document.querySelector("#health-feed-pills");

    const i18n = {
      unavailable: <?= json_encode(qk_t('data_status.health_unavailable', 'Health data unavailable'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
    };

    const setUnavailable = () => {
      if (overallNode) overallNode.textContent = i18n.unavailable;
      if (archiveNode) archiveNode.textContent = "--";
      if (countsNode) countsNode.textContent = "--";
      if (pillsNode) pillsNode.innerHTML = `<span class="insight-pill">${i18n.unavailable}</span>`;
    };

    const renderHealth = (payload) => {
      if (!payload || payload.ok !== true) {
        setUnavailable();
        return;
      }

      const counts = payload.counts || {};
      const overall = String(payload.overall_status || "unknown");
      const archive = payload.archive_mysql?.status ? String(payload.archive_mysql.status) : "unknown";
      const feeds = Array.isArray(payload.feeds) ? payload.feeds : [];

      if (overallNode) overallNode.textContent = overall;
      if (archiveNode) archiveNode.textContent = archive;
      if (countsNode) {
        const healthy = Number(counts.healthy || 0);
        const lagging = Number(counts.lagging || 0);
        const outdated = Number(counts.outdated || 0);
        const missing = Number(counts.missing || 0);
        countsNode.textContent = `healthy ${healthy} · lagging ${lagging} · outdated ${outdated} · missing ${missing}`;
      }

      if (pillsNode) {
        if (feeds.length === 0) {
          pillsNode.innerHTML = `<span class="insight-pill">no feeds</span>`;
          return;
        }
        pillsNode.innerHTML = feeds.map((feed) => {
          const key = String(feed.key || "feed");
          const status = String(feed.status || "unknown");
          const age = Number.isFinite(Number(feed.age_minutes)) ? `${Number(feed.age_minutes)}m` : "n/a";
          return `<span class="insight-pill">${key}: ${status} (${age})</span>`;
        }).join("");
      }
    };

    const load = async () => {
      try {
        const response = await fetch("/api/health.php", { headers: { Accept: "application/json" } });
        if (!response.ok) throw new Error("health request failed");
        const payload = await response.json();
        renderHealth(payload);
      } catch (error) {
        setUnavailable();
      }
    };

    load();
    window.setInterval(() => {
      if (document.hidden) return;
      load();
    }, 60000);
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
