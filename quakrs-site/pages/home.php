<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Global Seismic Platform';
$pageDescription = 'Real-time earthquakes, volcanoes, tsunami alerts, space weather and operational data views.';
$currentPage = 'home';
$includeLeaflet = true;
$bodyClass = 'home-page home-2026 home-acid-balanced home-acid-brutalist';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero home-v2-hero home-priority-hero home-neo-hero">
  <div class="home-hero-editorial-column">
    <div class="home-v2-hero-main home-neo-hero-main">
      <p class="eyebrow"><?= htmlspecialchars(qk_t('home.hero.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
      <h1>
        <span class="hero-line"><?= htmlspecialchars(qk_t('home.hero.title_line_1'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="hero-line"><?= htmlspecialchars(qk_t('home.hero.title_line_2'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="hero-line"><?= htmlspecialchars(qk_t('home.hero.title_line_3'), ENT_QUOTES, 'UTF-8'); ?></span>
      </h1>
      <p class="sub">
        <?= htmlspecialchars(qk_t('home.hero.sub'), ENT_QUOTES, 'UTF-8'); ?>
      </p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="/earthquakes.php"><?= htmlspecialchars(qk_t('home.hero.open_monitors'), ENT_QUOTES, 'UTF-8'); ?></a>
        <a class="btn btn-ghost" href="/archive.php"><?= htmlspecialchars(qk_t('home.hero.browse_archive'), ENT_QUOTES, 'UTF-8'); ?></a>
      </div>
    </div>

    <div class="home-hero-lower">
      <aside class="card home-neo-editorial home-hero-editorial-card" aria-label="<?= htmlspecialchars(qk_t('home.editorial_brief'), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="home-neo-editorial-head">
          <p class="home-neo-console-kicker"><?= htmlspecialchars(qk_t('home.editorial_brief'), ENT_QUOTES, 'UTF-8'); ?></p>
          <span id="home-priority-now" class="home-neo-live-pill"><?= htmlspecialchars(qk_t('home.dynamic_focus'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="home-v2-context-room home-context-room">
          <div class="home-context-layout">
            <div class="home-context-copy">
              <div class="snapshot-head home-context-head">
                <h4 id="home-context-title"><?= htmlspecialchars(qk_t('home.global_watch_progress'), ENT_QUOTES, 'UTF-8'); ?></h4>
                <span id="home-context-mode"><?= htmlspecialchars(qk_t('home.loading_mode'), ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <p id="home-context-summary" class="home-context-summary"><?= htmlspecialchars(qk_t('home.loading_editorial_summary'), ENT_QUOTES, 'UTF-8'); ?></p>
              <div class="home-context-facts">
                <p id="home-context-region" class="home-context-fact"><?= htmlspecialchars(qk_t('home.area_label'), ENT_QUOTES, 'UTF-8'); ?>: --</p>
                <p id="home-context-window" class="home-context-fact"><?= htmlspecialchars(qk_t('home.window_label'), ENT_QUOTES, 'UTF-8'); ?>: --</p>
                <p id="home-context-pressure" class="home-context-fact"><?= htmlspecialchars(qk_t('home.intensity_label'), ENT_QUOTES, 'UTF-8'); ?>: --</p>
                <p id="home-context-probability" class="home-context-fact"><?= htmlspecialchars(qk_t('home.activity_index_label'), ENT_QUOTES, 'UTF-8'); ?>: --</p>
              </div>
              <div class="home-context-earthquake-row">
                <div class="home-context-earthquake-head">
                  <h5 id="home-context-eq-title"><?= htmlspecialchars(qk_t('home.highlighted_earthquakes'), ENT_QUOTES, 'UTF-8'); ?></h5>
                </div>
                <ul id="home-context-eq-list" class="home-context-earthquake-list">
                  <li class="home-context-earthquake-item"><?= htmlspecialchars(qk_t('home.loading_contextual_events'), ENT_QUOTES, 'UTF-8'); ?></li>
                </ul>
              </div>
            </div>
            <article class="home-context-visual">
              <p class="home-context-visual-kicker"><?= htmlspecialchars(qk_t('home.signal_canvas'), ENT_QUOTES, 'UTF-8'); ?></p>
              <h5 id="home-context-visual-title" class="home-context-visual-title"><?= htmlspecialchars(qk_t('home.distributed_activity'), ENT_QUOTES, 'UTF-8'); ?></h5>
              <p id="home-context-visual-meta" class="home-context-visual-meta"><?= htmlspecialchars(qk_t('home.loading_signal_canvas'), ENT_QUOTES, 'UTF-8'); ?></p>
            </article>
            <article class="home-context-ai">
              <p class="home-context-ai-label"><?= htmlspecialchars(qk_t('home.ai_assisted_readout'), ENT_QUOTES, 'UTF-8'); ?></p>
              <h5 id="home-ai-tech" class="home-context-ai-tech">M-- · <?= htmlspecialchars(qk_t('home.pending'), ENT_QUOTES, 'UTF-8'); ?></h5>
              <p id="home-ai-text" class="home-context-ai-text"><?= htmlspecialchars(qk_t('home.preparing_contextual_interpretation'), ENT_QUOTES, 'UTF-8'); ?></p>
            </article>
          </div>
        </div>
      </aside>
      <aside class="card home-hero-events-rail" aria-label="<?= htmlspecialchars(qk_t('home.aria_live_significant'), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="feed-head home-hero-events-head">
          <div class="home-section-heading">
            <p class="home-section-kicker"><?= htmlspecialchars(qk_t('home.priority_stream'), ENT_QUOTES, 'UTF-8'); ?></p>
            <h3><?= htmlspecialchars(qk_t('home.live_significant'), ENT_QUOTES, 'UTF-8'); ?></h3>
          </div>
          <div class="home-priority-meta-links">
            <span id="home-significant-head-note"><?= htmlspecialchars(qk_t('home.ranked_feed'), ENT_QUOTES, 'UTF-8'); ?></span>
            <a class="home-priority-explain-link" href="/priority-levels.php"><?= htmlspecialchars(qk_t('home.how_priority'), ENT_QUOTES, 'UTF-8'); ?></a>
          </div>
        </div>
        <ul id="home-significant-list" class="snapshot-list home-priority-rail-list home-hero-events-list">
          <li class="snapshot-row"><?= htmlspecialchars(qk_t('home.loading_significant_events'), ENT_QUOTES, 'UTF-8'); ?></li>
        </ul>
      </aside>
    </div>
  </div>
</main>

<section id="launch" class="launch home-v2-launch home-priority-launch home-neo-launch">
  <article class="card home-priority-snapshot home-neo-snapshot" aria-label="<?= htmlspecialchars(qk_t('home.aria_global_snapshot'), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="feed-head">
      <div class="home-section-heading">
        <p class="home-section-kicker"><?= htmlspecialchars(qk_t('home.operational_overview'), ENT_QUOTES, 'UTF-8'); ?></p>
        <h3><?= htmlspecialchars(qk_t('home.global_snapshot'), ENT_QUOTES, 'UTF-8'); ?></h3>
      </div>
    </div>
    <div id="home-snapshot" class="launch-overview home-v2-overview home-neo-overview">
      <article class="overview-item">
        <p class="kpi-label"><?= htmlspecialchars(qk_t('home.events_24h'), ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="kpi-value" data-home-mirror="total">--</p>
        <p class="kpi-note"><?= htmlspecialchars(qk_t('home.earthquakes_latest_feed'), ENT_QUOTES, 'UTF-8'); ?></p>
      </article>
      <article class="overview-item home-v2-strongest">
        <p class="kpi-label"><?= htmlspecialchars(qk_t('home.strongest_earthquake'), ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="kpi-value" data-home-mirror="strongest">--</p>
        <p class="kpi-note" data-home-mirror="strongest-place"><?= htmlspecialchars(qk_t('home.no_data'), ENT_QUOTES, 'UTF-8'); ?></p>
      </article>
      <article class="overview-item">
        <p class="kpi-label"><?= htmlspecialchars(qk_t('home.tremor_clusters'), ENT_QUOTES, 'UTF-8'); ?></p>
        <p id="home-status-tremor-clusters" class="kpi-value">--</p>
        <p id="home-status-tremor-note" class="kpi-note"><?= htmlspecialchars(qk_t('home.loading_tremor_signals'), ENT_QUOTES, 'UTF-8'); ?></p>
      </article>
      <article class="overview-item">
        <p class="kpi-label"><?= htmlspecialchars(qk_t('home.active_volcanoes'), ENT_QUOTES, 'UTF-8'); ?></p>
        <p id="home-status-volcanoes" class="kpi-value">--</p>
        <p id="home-status-volcano-note" class="kpi-note"><?= htmlspecialchars(qk_t('home.loading_volcano_status'), ENT_QUOTES, 'UTF-8'); ?></p>
      </article>
      <article class="overview-item">
        <p class="kpi-label"><?= htmlspecialchars(qk_t('home.geomagnetic_kp'), ENT_QUOTES, 'UTF-8'); ?></p>
        <p id="home-status-kp" class="kpi-value">--</p>
        <p id="home-status-space-note" class="kpi-note"><?= htmlspecialchars(qk_t('home.loading_space_weather'), ENT_QUOTES, 'UTF-8'); ?></p>
      </article>
      <article class="overview-item">
        <p class="kpi-label"><?= htmlspecialchars(qk_t('home.tsunami_alerts'), ENT_QUOTES, 'UTF-8'); ?></p>
        <p id="home-status-tsunami" class="kpi-value">--</p>
        <p id="home-status-tsunami-note" class="kpi-note"><?= htmlspecialchars(qk_t('home.loading_tsunami_status'), ENT_QUOTES, 'UTF-8'); ?></p>
      </article>
    </div>
  </article>

  <div class="home-dashboard-grid">
    <div class="home-dashboard-main">
      <article class="card home-priority-board" aria-live="polite">
        <div class="snapshot-head">
          <h3><?= htmlspecialchars(qk_t('home.priority_board'), ENT_QUOTES, 'UTF-8'); ?></h3>
          <span id="home-priority-mode"><?= htmlspecialchars(qk_t('home.current_global_focus'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div id="home-priority-board-cards" class="home-priority-board-cards">
          <p class="home-priority-loading"><?= htmlspecialchars(qk_t('home.loading_critical_signals'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <p id="home-priority-support" class="home-priority-support">
          <?= htmlspecialchars(qk_t('home.priority_support'), ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <a class="home-priority-explain-link" href="/priority-levels.php"><?= htmlspecialchars(qk_t('home.understand_priority_logic'), ENT_QUOTES, 'UTF-8'); ?></a>
      </article>
    </div>

    <aside class="home-dashboard-side">
    </aside>

    <section class="home-v2-map home-neo-map home-dashboard-map" aria-label="<?= htmlspecialchars(qk_t('home.global_activity_map'), ENT_QUOTES, 'UTF-8'); ?>">
      <div class="feed-head">
        <h3><?= htmlspecialchars(qk_t('home.global_activity_map'), ENT_QUOTES, 'UTF-8'); ?></h3>
        <a class="btn btn-ghost home-v2-map-btn" href="/maps.php?fullscreen=1"><?= htmlspecialchars(qk_t('home.open_full_map'), ENT_QUOTES, 'UTF-8'); ?></a>
      </div>
      <div class="home-dashboard-map-frame">
        <article class="card home-dashboard-earthquakes">
          <label class="home-map-list-filter" for="home-map-viewport-only">
            <input id="home-map-viewport-only" type="checkbox">
            <span><?= htmlspecialchars(qk_t('home.only_list_earthquakes_shown'), ENT_QUOTES, 'UTF-8'); ?></span>
          </label>
          <ul id="home-map-feed-list" class="snapshot-list">
            <li class="snapshot-row"><?= htmlspecialchars(qk_t('home.loading_earthquake_feed'), ENT_QUOTES, 'UTF-8'); ?></li>
          </ul>
          <a class="inline-link" href="/earthquakes.php"><?= htmlspecialchars(qk_t('home.open_earthquakes'), ENT_QUOTES, 'UTF-8'); ?></a>
        </article>
        <article class="card home-v2-map-card">
          <div class="map-wrap insight-map-wrap">
            <div
              id="world-map-leaflet"
              class="world-map-leaflet"
              role="img"
              aria-label="<?= htmlspecialchars(qk_t('home.aria_live_global_map'), ENT_QUOTES, 'UTF-8'); ?>"
            ></div>
          </div>
        </article>
      </div>
    </section>
  </div>
</section>

<section class="panel home-dashboard-monitors" aria-label="<?= htmlspecialchars(qk_t('home.aria_category_modules'), ENT_QUOTES, 'UTF-8'); ?>">
  <div class="feed-head">
    <div class="home-section-heading">
      <p class="home-section-kicker"><?= htmlspecialchars(qk_t('home.monitoring_layers'), ENT_QUOTES, 'UTF-8'); ?></p>
      <h3><?= htmlspecialchars(qk_t('home.monitors'), ENT_QUOTES, 'UTF-8'); ?></h3>
    </div>
    <a class="inline-link home-dashboard-archive-link" href="/archive.php"><?= htmlspecialchars(qk_t('home.browse_archive'), ENT_QUOTES, 'UTF-8'); ?></a>
  </div>
  <div class="home-dashboard-monitors-grid">
    <article id="home-panel-clusters" class="snapshot-card home-priority-module home-neo-module home-dashboard-monitor">
      <div class="snapshot-head">
        <h4><a class="home-module-title-link" href="/data-clusters.php"><?= htmlspecialchars(qk_t('home.tremor_clusters'), ENT_QUOTES, 'UTF-8'); ?></a></h4>
        <span><?= htmlspecialchars(qk_t('home.subsurface_pressure'), ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <ul id="home-clusters-list" class="snapshot-list home-live-list home-hazard-list">
        <li class="snapshot-row"><?= htmlspecialchars(qk_t('home.loading_tremor_clusters'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>
    </article>
    <article class="snapshot-card home-priority-module home-neo-module home-dashboard-monitor">
      <div class="snapshot-head">
        <h4><a class="home-module-title-link" href="/earthquakes.php"><?= htmlspecialchars(qk_t('nav.earthquakes'), ENT_QUOTES, 'UTF-8'); ?></a></h4>
        <span><?= htmlspecialchars(qk_t('home.rapid_picks'), ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <ul id="home-module-earthquakes-list" class="snapshot-list home-live-list">
        <li class="snapshot-row"><?= htmlspecialchars(qk_t('home.loading_earthquakes'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>
    </article>
    <article id="home-panel-volcano" class="snapshot-card home-priority-module home-neo-module home-dashboard-monitor">
      <div class="snapshot-head">
        <h4><a class="home-module-title-link" href="/volcanoes.php"><?= htmlspecialchars(qk_t('nav.volcanoes'), ENT_QUOTES, 'UTF-8'); ?></a></h4>
        <span><?= htmlspecialchars(qk_t('home.bulletin_cycle'), ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <ul id="home-volcano-list" class="snapshot-list home-live-list home-volcano-list">
        <li class="snapshot-row"><?= htmlspecialchars(qk_t('home.loading_volcano_feed'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>
    </article>
    <article id="home-panel-tsunami" class="snapshot-card home-priority-module home-neo-module home-dashboard-monitor">
      <div class="snapshot-head">
        <h4><a class="home-module-title-link" href="/tsunami.php"><?= htmlspecialchars(qk_t('home.tsunami_alerts'), ENT_QUOTES, 'UTF-8'); ?></a></h4>
        <span><?= htmlspecialchars(qk_t('home.operational_alerting'), ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <ul id="home-module-tsunami-list" class="snapshot-list home-live-list">
        <li class="snapshot-row"><?= htmlspecialchars(qk_t('home.loading_tsunami_module'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>
    </article>
    <article id="home-panel-space" class="snapshot-card home-priority-module home-neo-module home-dashboard-monitor home-dashboard-monitor-wide">
      <div class="snapshot-head">
        <h4><a class="home-module-title-link" href="/space-weather.php"><?= htmlspecialchars(qk_t('nav.space_weather'), ENT_QUOTES, 'UTF-8'); ?></a></h4>
        <span><?= htmlspecialchars(qk_t('home.solar_watch'), ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <ul id="home-module-space-list" class="snapshot-list home-live-list">
        <li class="snapshot-row"><?= htmlspecialchars(qk_t('home.loading_space_module'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>
    </article>
  </div>
</section>

<section class="panel home-dashboard-covers" aria-label="<?= htmlspecialchars(qk_t('home.aria_what_quakrs_covers'), ENT_QUOTES, 'UTF-8'); ?>">
  <div class="home-section-heading">
    <p class="home-section-kicker"><?= htmlspecialchars(qk_t('home.mission_scope'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h3><?= htmlspecialchars(qk_t('home.what_quakrs_covers'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p class="home-section-note"><?= htmlspecialchars(qk_t('home.four_surfaces'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
  <div class="home-v2-cover-grid home-dashboard-cover-grid">
    <a class="card home-v2-cover-card" href="/earthquakes.php">
      <span class="home-v2-cover-tag"><?= htmlspecialchars(qk_t('home.cover_live_monitor'), ENT_QUOTES, 'UTF-8'); ?></span>
      <div class="home-v2-cover-media home-v2-cover-media-earthquakes" aria-hidden="true"></div>
      <h4><?= htmlspecialchars(qk_t('nav.earthquakes'), ENT_QUOTES, 'UTF-8'); ?></h4>
      <p><?= htmlspecialchars(qk_t('home.cover_earthquakes_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
    </a>
    <a class="card home-v2-cover-card" href="/volcanoes.php">
      <span class="home-v2-cover-tag"><?= htmlspecialchars(qk_t('home.cover_bulletins'), ENT_QUOTES, 'UTF-8'); ?></span>
      <div class="home-v2-cover-media home-v2-cover-media-volcanoes" aria-hidden="true"></div>
      <h4><?= htmlspecialchars(qk_t('nav.volcanoes'), ENT_QUOTES, 'UTF-8'); ?></h4>
      <p><?= htmlspecialchars(qk_t('home.cover_volcanoes_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
    </a>
    <a class="card home-v2-cover-card" href="/tsunami.php">
      <span class="home-v2-cover-tag"><?= htmlspecialchars(qk_t('home.cover_alerts'), ENT_QUOTES, 'UTF-8'); ?></span>
      <div class="home-v2-cover-media home-v2-cover-media-tsunami" aria-hidden="true"></div>
      <h4><?= htmlspecialchars(qk_t('home.tsunami_alerts'), ENT_QUOTES, 'UTF-8'); ?></h4>
      <p><?= htmlspecialchars(qk_t('home.cover_tsunami_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
    </a>
    <a class="card home-v2-cover-card" href="/space-weather.php">
      <span class="home-v2-cover-tag"><?= htmlspecialchars(qk_t('home.cover_solar_watch'), ENT_QUOTES, 'UTF-8'); ?></span>
      <div class="home-v2-cover-media home-v2-cover-media-space" aria-hidden="true"></div>
      <h4><?= htmlspecialchars(qk_t('nav.space_weather'), ENT_QUOTES, 'UTF-8'); ?></h4>
      <p><?= htmlspecialchars(qk_t('home.cover_space_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
    </a>
    <a class="card home-v2-cover-card" href="/archive.php">
      <span class="home-v2-cover-tag"><?= htmlspecialchars(qk_t('home.cover_historic_search'), ENT_QUOTES, 'UTF-8'); ?></span>
      <div class="home-v2-cover-media home-v2-cover-media-archive" aria-hidden="true"></div>
      <h4><?= htmlspecialchars(qk_t('nav.archive'), ENT_QUOTES, 'UTF-8'); ?></h4>
      <p><?= htmlspecialchars(qk_t('home.cover_archive_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
    </a>
  </div>
</section>

<section class="panel home-v2-trust">
  <p class="launch-copy"><?= htmlspecialchars(qk_t('home.trusted_sources'), ENT_QUOTES, 'UTF-8'); ?></p>
  <div class="home-v2-trust-row" aria-label="<?= htmlspecialchars(qk_t('home.aria_sources_partners'), ENT_QUOTES, 'UTF-8'); ?>">
    <span class="home-v2-trust-item">
      <img class="home-v2-trust-logo" src="/assets/icons/providers/usgs-mark.svg" alt="" />
      <span>USGS</span>
    </span>
    <span class="home-v2-trust-item">
      <img class="home-v2-trust-logo" src="/assets/icons/providers/emsc.png" alt="" />
      <span>EMSC</span>
    </span>
    <span class="home-v2-trust-item">
      <img class="home-v2-trust-logo" src="/assets/icons/providers/noaa.svg" alt="" />
      <span>NOAA</span>
    </span>
    <span class="home-v2-trust-item">
      <img class="home-v2-trust-logo" src="/assets/icons/providers/cnp.svg" alt="" />
      <span><?= htmlspecialchars(qk_t('home.trust_earthquakes_cnp'), ENT_QUOTES, 'UTF-8'); ?></span>
    </span>
    <span class="home-v2-trust-item">
      <img class="home-v2-trust-logo" src="/assets/icons/providers/gvp-mark.svg" alt="" />
      <span>Smithsonian GVP</span>
    </span>
    <span class="home-v2-trust-item">
      <img class="home-v2-trust-logo" src="/assets/icons/providers/ingv.ico" alt="" />
      <span>INGV</span>
    </span>
  </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
