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
  <article class="card page-card">
    <h3>Need source provenance context?</h3>
    <p class="insight-lead">Open <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/sources-status.php'), ENT_QUOTES, 'UTF-8'); ?>">Sources & Reliability</a> to combine provider coverage, expected cadence, latency targets, and known data limits with this technical status view.</p>
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
      <h3>Operational Impact</h3>
      <p class="feed-meta">Component-level health and user-visible impact</p>
    </div>
    <div class="page-grid">
      <div class="event-item">
        <strong>User Impact</strong><br />
        <span id="health-user-impact">Loading impact...</span>
      </div>
      <div class="event-item">
        <strong>Degraded Components</strong><br />
        <span id="health-components-degraded">--</span>
      </div>
    </div>
    <ul id="health-components-list" class="events-list" style="margin-top:0.8rem;">
      <li class="event-item">Loading component matrix...</li>
    </ul>
  </article>
</section>

<section class="panel">
  <article class="card">
    <div class="feed-head">
      <h3>Incident Log (MVP)</h3>
      <p class="feed-meta">Auto-generated from live feed/component degradation signals.</p>
    </div>
    <div class="page-grid">
      <div class="event-item">
        <strong>Active incidents</strong><br />
        <span id="health-incidents-count">--</span>
      </div>
      <div class="event-item">
        <strong>Highest severity</strong><br />
        <span id="health-incidents-severity">--</span>
      </div>
    </div>
    <ul id="health-incidents-list" class="events-list" style="margin-top:0.8rem;">
      <li class="event-item">Loading incident log...</li>
    </ul>
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
    const impactNode = document.querySelector("#health-user-impact");
    const degradedComponentsNode = document.querySelector("#health-components-degraded");
    const componentsListNode = document.querySelector("#health-components-list");
    const incidentsCountNode = document.querySelector("#health-incidents-count");
    const incidentsSeverityNode = document.querySelector("#health-incidents-severity");
    const incidentsListNode = document.querySelector("#health-incidents-list");

    const i18n = {
      unavailable: <?= json_encode(qk_t('data_status.health_unavailable', 'Health data unavailable'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
    };

    const setUnavailable = () => {
      if (overallNode) overallNode.textContent = i18n.unavailable;
      if (archiveNode) archiveNode.textContent = "--";
      if (countsNode) countsNode.textContent = "--";
      if (pillsNode) pillsNode.innerHTML = `<span class="insight-pill">${i18n.unavailable}</span>`;
      if (impactNode) impactNode.textContent = i18n.unavailable;
      if (degradedComponentsNode) degradedComponentsNode.textContent = "--";
      if (componentsListNode) {
        componentsListNode.innerHTML = `<li class="event-item">${i18n.unavailable}</li>`;
      }
      if (incidentsCountNode) incidentsCountNode.textContent = "--";
      if (incidentsSeverityNode) incidentsSeverityNode.textContent = "--";
      if (incidentsListNode) incidentsListNode.innerHTML = `<li class="event-item">${i18n.unavailable}</li>`;
    };

    const severityWeight = (severity) => {
      switch (String(severity || "minor")) {
        case "critical":
          return 4;
        case "major":
          return 3;
        case "minor":
          return 2;
        default:
          return 1;
      }
    };

    const formatSince = (isoValue) => {
      if (!isoValue) return "since unknown";
      const ts = new Date(isoValue).getTime();
      if (!Number.isFinite(ts)) return "since unknown";
      return `since ${new Date(ts).toLocaleString()}`;
    };

    const formatAt = (isoValue) => {
      if (!isoValue) return "time unknown";
      const ts = new Date(isoValue).getTime();
      if (!Number.isFinite(ts)) return "time unknown";
      return new Date(ts).toLocaleString();
    };

    const inferSeverityFromFeed = (status) => {
      if (status === "missing") return "critical";
      if (status === "outdated") return "major";
      if (status === "lagging") return "minor";
      return "notice";
    };

    const inferSeverityFromComponent = (status, impact) => {
      if (status !== "degraded") return "notice";
      if (impact === "visible") return "major";
      if (impact === "limited") return "minor";
      return "minor";
    };

    const buildIncidents = (feeds, components) => {
      const incidents = [];

      feeds.forEach((feed) => {
        const status = String(feed?.status || "unknown");
        if (!["missing", "outdated", "lagging"].includes(status)) {
          return;
        }
        const key = String(feed?.key || "feed");
        const severity = inferSeverityFromFeed(status);
        const maxAge = Number(feed?.max_age_minutes);
        const age = Number(feed?.age_minutes);
        const delayedBy = Number.isFinite(age) && Number.isFinite(maxAge) ? Math.max(0, Math.round(age - maxAge)) : null;
        incidents.push({
          key: `feed:${key}`,
          severity,
          label: `Feed ${key} is ${status}`,
          detail: delayedBy !== null ? `delay +${delayedBy} min` : "delay unknown",
          since: formatSince(feed?.last_success_at),
        });
      });

      components.forEach((component) => {
        const status = String(component?.status || "unknown");
        if (status !== "degraded") {
          return;
        }
        const impact = String(component?.impact || "none");
        const key = String(component?.key || "component");
        const severity = inferSeverityFromComponent(status, impact);
        incidents.push({
          key: `component:${key}`,
          severity,
          label: `Component ${key} degraded`,
          detail: `impact ${impact}`,
          since: formatSince(component?.since),
        });
      });

      incidents.sort((a, b) => {
        const sevDelta = severityWeight(b.severity) - severityWeight(a.severity);
        if (sevDelta !== 0) return sevDelta;
        return a.label.localeCompare(b.label);
      });
      return incidents;
    };

    const renderIncidents = (payload, fallbackIncidents) => {
      const activeIncidents = Array.isArray(payload?.incidents_active) ? payload.incidents_active : [];
      const historyEvents = Array.isArray(payload?.incidents_history) ? payload.incidents_history : [];
      const summary = payload?.incidents_summary && typeof payload.incidents_summary === "object" ? payload.incidents_summary : {};

      const activeCount = Number.isFinite(Number(summary.active_count))
        ? Number(summary.active_count)
        : activeIncidents.length;
      const highestSeverity = String(summary.highest_severity || (activeIncidents[0]?.severity || "none")).toUpperCase();

      if (incidentsCountNode) incidentsCountNode.textContent = String(activeCount);
      if (incidentsSeverityNode) incidentsSeverityNode.textContent = highestSeverity;

      if (!incidentsListNode) {
        return;
      }

      if (historyEvents.length > 0) {
        incidentsListNode.innerHTML = historyEvents.slice(0, 20).map((eventRow) => {
          const eventType = String(eventRow?.event || "event");
          const at = formatAt(eventRow?.at);
          const incident = eventRow?.incident && typeof eventRow.incident === "object" ? eventRow.incident : {};
          const severity = String(incident.severity || "notice").toUpperCase();
          const title = String(incident.title || incident.key || "Incident");
          const detail = String(incident.detail || "");
          const lifecycle = eventType === "resolved"
            ? `resolved ${at}`
            : eventType === "opened"
              ? `opened ${at}`
              : `updated ${at}`;
          return `<li class="event-item"><strong>${severity} · ${title}</strong><br />${detail ? `${detail} · ` : ""}${lifecycle}</li>`;
        }).join("");
        return;
      }

      if (fallbackIncidents.length === 0) {
        incidentsListNode.innerHTML = "<li class='event-item'>No active incidents. System currently nominal.</li>";
      } else {
        incidentsListNode.innerHTML = fallbackIncidents.map((incident) => {
          return `<li class="event-item"><strong>${incident.severity.toUpperCase()} · ${incident.label}</strong><br />${incident.detail} · ${incident.since}</li>`;
        }).join("");
      }
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
      const components = Array.isArray(payload.components) ? payload.components : [];
      const userImpact = String(payload.user_impact || "No impact details available.");
      const degradedComponents = Number(payload.degraded_components || 0);
      const incidents = buildIncidents(feeds, components);

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
        } else {
          pillsNode.innerHTML = feeds.map((feed) => {
            const key = String(feed.key || "feed");
            const status = String(feed.status || "unknown");
            const age = Number.isFinite(Number(feed.age_minutes)) ? `${Number(feed.age_minutes)}m` : "n/a";
            return `<span class="insight-pill">${key}: ${status} (${age})</span>`;
          }).join("");
        }
      }

      if (impactNode) {
        impactNode.textContent = userImpact;
      }
      if (degradedComponentsNode) {
        degradedComponentsNode.textContent = String(degradedComponents);
      }
      if (componentsListNode) {
        if (components.length === 0) {
          componentsListNode.innerHTML = "<li class='event-item'>No component details available.</li>";
        } else {
          componentsListNode.innerHTML = components.map((component) => {
            const key = String(component.key || "component");
            const status = String(component.status || "unknown");
            const impact = String(component.impact || "none");
            const note = String(component.note || "");
            return `<li class="event-item"><strong>${key}</strong><br />${status} · impact ${impact}${note ? ` · ${note}` : ""}</li>`;
          }).join("");
        }
      }

      renderIncidents(payload, incidents);
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
