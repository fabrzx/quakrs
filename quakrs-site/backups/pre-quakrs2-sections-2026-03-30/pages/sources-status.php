<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Sources & Status';
$pageDescription = 'Bridge page for source provenance, feed freshness, latency, and reliability.';
$currentPage = 'sources-status';

function qk_source_generated_timestamp(string $path): ?int
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

function qk_source_status_from_age(?int $ageMinutes, int $maxAgeMinutes): string
{
    if (!is_int($ageMinutes)) {
        return 'unknown';
    }
    if ($ageMinutes <= $maxAgeMinutes) {
        return 'healthy';
    }
    if ($ageMinutes <= ($maxAgeMinutes * 3)) {
        return 'lagging';
    }
    return 'outdated';
}

function qk_source_status_weight(string $status): int
{
    return match ($status) {
        'healthy' => 1,
        'lagging' => 2,
        'unknown' => 3,
        'missing' => 4,
        'outdated' => 5,
        default => 3,
    };
}

function qk_source_status_label(string $status): string
{
    return match ($status) {
        'healthy' => qk_t('data_status.status_healthy'),
        'lagging' => qk_t('data_status.status_lagging'),
        'outdated' => qk_t('data_status.status_outdated'),
        'missing' => qk_t('data_status.status_missing'),
        default => qk_t('data_status.status_unknown'),
    };
}

$now = time();
$feedFiles = [
    'earthquakes' => ['file' => __DIR__ . '/../data/earthquakes_latest.json', 'maxAgeMinutes' => 10],
    'aftershocks' => ['file' => __DIR__ . '/../data/aftershocks_index_latest.json', 'maxAgeMinutes' => 10],
    'volcanoes' => ['file' => __DIR__ . '/../data/volcanoes_latest.json', 'maxAgeMinutes' => 20],
    'tremors' => ['file' => __DIR__ . '/../data/tremors_latest.json', 'maxAgeMinutes' => 20],
    'tsunami' => ['file' => __DIR__ . '/../data/tsunami_latest.json', 'maxAgeMinutes' => 30],
    'space_weather' => ['file' => __DIR__ . '/../data/space_weather_latest.json', 'maxAgeMinutes' => 20],
    'bulletins' => ['file' => __DIR__ . '/../data/bulletins_latest.json', 'maxAgeMinutes' => 30],
    'hotspots' => ['file' => __DIR__ . '/../data/hotspots_latest.json', 'maxAgeMinutes' => 30],
    'volcano_cams' => ['file' => __DIR__ . '/../data/volcano_cams_latest.json', 'maxAgeMinutes' => 45],
];

$feedStatus = [];
$healthyCount = 0;
$laggingCount = 0;
$problemCount = 0;

foreach ($feedFiles as $feedKey => $feed) {
    $path = $feed['file'];
    $exists = is_file($path);
    if (!$exists) {
        $feedStatus[$feedKey] = [
            'status' => 'missing',
            'age_minutes' => null,
            'max_age_minutes' => $feed['maxAgeMinutes'],
        ];
        $problemCount++;
        continue;
    }

    $generatedTs = qk_source_generated_timestamp($path);
    $mtime = filemtime($path);
    $referenceTs = is_int($generatedTs) && $generatedTs > 0
        ? $generatedTs
        : (is_int($mtime) ? $mtime : null);
    $ageMinutes = is_int($referenceTs)
        ? (int) floor(max(0, $now - $referenceTs) / 60)
        : null;
    $status = qk_source_status_from_age($ageMinutes, (int) $feed['maxAgeMinutes']);

    if ($status === 'healthy') {
        $healthyCount++;
    } elseif ($status === 'lagging') {
        $laggingCount++;
    } else {
        $problemCount++;
    }

    $feedStatus[$feedKey] = [
        'status' => $status,
        'age_minutes' => $ageMinutes,
        'max_age_minutes' => $feed['maxAgeMinutes'],
    ];
}

$sourceCards = [
    [
        'key' => 'earthquakes',
        'title' => 'Earthquakes Composite',
        'providers' => 'USGS, INGV, EMSC',
        'coverage' => 'Global + Italy regional relevance',
        'cadence' => '~60s dashboard refresh, ingest target <= 10 min',
        'latencyTarget' => 'Target freshness: <= 10 min',
        'reliability' => 'High when at least two providers are fresh; medium in single-provider fallback.',
        'limitations' => 'Magnitude revisions and location refinements can appear after first publication.',
        'feeds' => ['earthquakes', 'aftershocks'],
    ],
    [
        'key' => 'volcanoes',
        'title' => 'Volcanoes Activity',
        'providers' => 'Smithsonian GVP + observatory channels',
        'coverage' => 'Global active volcano reports',
        'cadence' => 'Event-driven + periodic refresh (target <= 20 min)',
        'latencyTarget' => 'Target freshness: <= 20 min',
        'reliability' => 'High for bulletin-backed activity context; medium for very recent local changes.',
        'limitations' => 'Observatory reporting cadence is not uniform across regions.',
        'feeds' => ['volcanoes', 'hotspots'],
    ],
    [
        'key' => 'tsunami',
        'title' => 'Tsunami Advisories',
        'providers' => 'NOAA / NWS bulletins',
        'coverage' => 'Active tsunami watch/warning/advisory regions',
        'cadence' => 'Bulletin-driven updates (target <= 30 min)',
        'latencyTarget' => 'Target freshness: <= 30 min',
        'reliability' => 'High for alert-state visibility; requires source bulletin verification for legal decisions.',
        'limitations' => 'Advisory texts can change quickly during evolving events.',
        'feeds' => ['tsunami'],
    ],
    [
        'key' => 'space',
        'title' => 'Space Weather',
        'providers' => 'NOAA SWPC',
        'coverage' => 'Kp index, storm level, flare context',
        'cadence' => 'Frequent ingest (target <= 20 min)',
        'latencyTarget' => 'Target freshness: <= 20 min',
        'reliability' => 'High for operational awareness; medium for short-term forecast certainty.',
        'limitations' => 'Solar conditions can shift faster than fixed polling windows.',
        'feeds' => ['space_weather'],
    ],
    [
        'key' => 'support',
        'title' => 'Operational Support Feeds',
        'providers' => 'Institutional pages and curated endpoints',
        'coverage' => 'Bulletins, volcano cams directory, resources support',
        'cadence' => 'Target <= 30-45 min depending on source',
        'latencyTarget' => 'Target freshness: <= 45 min',
        'reliability' => 'Medium-high for context, not for immediate hazard detection.',
        'limitations' => 'Some endpoints are curated and may include temporary unavailability.',
        'feeds' => ['bulletins', 'volcano_cams', 'tremors'],
    ],
];

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.sources_status.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.sources_status.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.sources_status.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Healthy feeds</p>
    <p class="kpi-value"><?= (int) $healthyCount; ?></p>
    <p class="kpi-note">Within target freshness windows.</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Lagging feeds</p>
    <p class="kpi-value"><?= (int) $laggingCount; ?></p>
    <p class="kpi-note">Data may appear with operational delay.</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Problem feeds</p>
    <p class="kpi-value"><?= (int) $problemCount; ?></p>
    <p class="kpi-note">Unknown/outdated/missing feed states detected.</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Bridge links</p>
    <p class="kpi-value">2</p>
    <p class="kpi-note"><a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/about-sources.php'), ENT_QUOTES, 'UTF-8'); ?>">Sources</a> · <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/data-status.php'), ENT_QUOTES, 'UTF-8'); ?>">Data Status</a></p>
  </article>
</section>

<section class="panel">
  <article class="card">
    <div class="feed-head">
      <h3>Source + Feed Reliability Matrix</h3>
      <p class="feed-meta">Each card combines provider provenance, coverage scope, expected cadence, current freshness, and known limits.</p>
    </div>
    <div class="page-grid source-status-grid">
      <?php foreach ($sourceCards as $card): ?>
        <?php
        $worstStatus = 'healthy';
        $worstWeight = 0;
        $maxAgeFound = null;
        foreach ($card['feeds'] as $feedKey) {
            $row = $feedStatus[$feedKey] ?? ['status' => 'unknown', 'age_minutes' => null];
            $weight = qk_source_status_weight((string) $row['status']);
            if ($weight > $worstWeight) {
                $worstWeight = $weight;
                $worstStatus = (string) $row['status'];
            }
            if (is_int($row['age_minutes'])) {
                $maxAgeFound = is_int($maxAgeFound) ? max($maxAgeFound, $row['age_minutes']) : $row['age_minutes'];
            }
        }
        $statusLabel = qk_source_status_label($worstStatus);
        $statusClass = 'is-' . preg_replace('/[^a-z0-9_-]/', '', strtolower($worstStatus));
        $ageLabel = is_int($maxAgeFound) ? (string) $maxAgeFound . ' min' : 'n/a';
        ?>
        <article class="page-card card source-status-card" data-source-key="<?= htmlspecialchars($card['key'], ENT_QUOTES, 'UTF-8'); ?>">
          <div class="source-status-head">
            <h3><?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <span class="insight-pill source-status-pill <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>" data-source-status><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <ul class="events-list source-status-list">
            <li class="event-item"><strong>Providers</strong><br /><?= htmlspecialchars($card['providers'], ENT_QUOTES, 'UTF-8'); ?></li>
            <li class="event-item"><strong>Coverage</strong><br /><?= htmlspecialchars($card['coverage'], ENT_QUOTES, 'UTF-8'); ?></li>
            <li class="event-item"><strong>Cadence</strong><br /><?= htmlspecialchars($card['cadence'], ENT_QUOTES, 'UTF-8'); ?></li>
            <li class="event-item"><strong>Freshness snapshot</strong><br /><span data-source-age>max observed age: <?= htmlspecialchars($ageLabel, ENT_QUOTES, 'UTF-8'); ?></span></li>
            <li class="event-item"><strong>Latency target</strong><br /><?= htmlspecialchars($card['latencyTarget'], ENT_QUOTES, 'UTF-8'); ?></li>
            <li class="event-item"><strong>Reliability note</strong><br /><?= htmlspecialchars($card['reliability'], ENT_QUOTES, 'UTF-8'); ?></li>
            <li class="event-item"><strong>Known limits</strong><br /><?= htmlspecialchars($card['limitations'], ENT_QUOTES, 'UTF-8'); ?></li>
          </ul>
        </article>
      <?php endforeach; ?>
    </div>
  </article>
</section>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>How to read this page</h3>
    <p class="insight-lead">Use this page for trust calibration: where data comes from, how fresh it is, and where uncertainty is expected before decisions.</p>
  </article>
  <article class="card page-card">
    <h3>Methodology context</h3>
    <p class="insight-lead">Pipeline and normalization rules stay documented in <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/about-methodology.php'), ENT_QUOTES, 'UTF-8'); ?>">Methodology</a>.</p>
  </article>
  <article class="card page-card">
    <h3>Live technical check</h3>
    <p class="insight-lead">For component degradation and ingest health, open <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/data-status.php'), ENT_QUOTES, 'UTF-8'); ?>">Data Status</a>.</p>
  </article>
</section>

<script>
  (() => {
    const cards = Array.from(document.querySelectorAll(".source-status-card"));
    if (cards.length === 0) {
      return;
    }

    const sourceFeeds = {
      earthquakes: ["earthquakes", "aftershocks"],
      volcanoes: ["volcanoes", "hotspots"],
      tsunami: ["tsunami"],
      space: ["space_weather"],
      support: ["bulletins", "volcano_cams", "tremors"],
    };

    const statusWeight = (status) => {
      switch (String(status || "unknown")) {
        case "healthy":
          return 1;
        case "lagging":
          return 2;
        case "unknown":
          return 3;
        case "missing":
          return 4;
        case "outdated":
          return 5;
        default:
          return 3;
      }
    };

    const statusLabel = (status) => {
      switch (String(status || "unknown")) {
        case "healthy":
          return "<?= htmlspecialchars(qk_t('data_status.status_healthy'), ENT_QUOTES, 'UTF-8'); ?>";
        case "lagging":
          return "<?= htmlspecialchars(qk_t('data_status.status_lagging'), ENT_QUOTES, 'UTF-8'); ?>";
        case "outdated":
          return "<?= htmlspecialchars(qk_t('data_status.status_outdated'), ENT_QUOTES, 'UTF-8'); ?>";
        case "missing":
          return "<?= htmlspecialchars(qk_t('data_status.status_missing'), ENT_QUOTES, 'UTF-8'); ?>";
        default:
          return "<?= htmlspecialchars(qk_t('data_status.status_unknown'), ENT_QUOTES, 'UTF-8'); ?>";
      }
    };

    const renderFromHealth = (payload) => {
      const feeds = Array.isArray(payload?.feeds) ? payload.feeds : [];
      const byKey = new Map(feeds.map((row) => [String(row?.key || ""), row]));

      cards.forEach((card) => {
        const sourceKey = String(card.getAttribute("data-source-key") || "");
        const keys = sourceFeeds[sourceKey] || [];
        let worstStatus = "unknown";
        let worstWeight = 0;
        let maxAge = null;

        keys.forEach((key) => {
          const row = byKey.get(key);
          const status = String(row?.status || "unknown");
          const weight = statusWeight(status);
          if (weight > worstWeight) {
            worstWeight = weight;
            worstStatus = status;
          }
          const age = Number(row?.age_minutes);
          if (Number.isFinite(age)) {
            maxAge = Number.isFinite(maxAge) ? Math.max(maxAge, age) : age;
          }
        });

        const badge = card.querySelector("[data-source-status]");
        if (badge instanceof HTMLElement) {
          badge.textContent = statusLabel(worstStatus);
          badge.classList.remove("is-healthy", "is-lagging", "is-outdated", "is-missing", "is-unknown");
          badge.classList.add(`is-${worstStatus}`);
        }
        const ageNode = card.querySelector("[data-source-age]");
        if (ageNode instanceof HTMLElement) {
          ageNode.textContent = Number.isFinite(maxAge)
            ? `max observed age: ${Math.round(maxAge)} min`
            : "max observed age: n/a";
        }
      });
    };

    const load = async () => {
      try {
        const response = await fetch("/api/health.php", { headers: { Accept: "application/json" }, cache: "no-store" });
        if (!response.ok) {
          throw new Error("health failed");
        }
        const payload = await response.json();
        renderFromHealth(payload);
      } catch (error) {
        // keep server-rendered fallback
      }
    };

    void load();
    window.setInterval(() => {
      if (document.hidden) {
        return;
      }
      void load();
    }, 60000);
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
