<?php
declare(strict_types=1);

$place = isset($_GET['place']) ? trim((string) $_GET['place']) : '';
$mag = isset($_GET['mag']) ? trim((string) $_GET['mag']) : '';
$depth = isset($_GET['depth']) ? trim((string) $_GET['depth']) : '';
$time = isset($_GET['time']) ? trim((string) $_GET['time']) : '';
$eventId = isset($_GET['id']) ? trim((string) $_GET['id']) : '';

$titlePlace = $place !== '' ? $place : 'Selected seismic event';
$titleMag = $mag !== '' ? "M{$mag}" : 'M?';
$initialMagnitudeValue = is_numeric($mag) ? (float) $mag : null;
$initialMagnitudeBand = 'pending';
if ($initialMagnitudeValue !== null) {
  $bucket = (int) floor($initialMagnitudeValue);
  if ($bucket < 1) $bucket = 1;
  if ($bucket > 9) $bucket = 9;
  $initialMagnitudeBand = "b{$bucket}";
}
$showAftershocksCta = $initialMagnitudeValue !== null && $initialMagnitudeValue >= 6.0;
$historyRadiusKm = 30;
$timeNote = 'Awaiting tectonic context';
if ($time !== '') {
  try {
    $dt = new DateTimeImmutable($time);
    $timeNote = sprintf('Event time %s UTC', $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i'));
  } catch (Throwable $e) {
    $timeNote = 'Event time available';
  }
}

$aftershocksUrl = '/aftershocks.php';
if ($eventId !== '') {
  $aftershocksUrl .= '?event_id=' . rawurlencode($eventId);
}

$pageTitle = 'Quakrs.com - Event Insight';
$pageDescription = 'Detailed tectonic and seismic context for a selected earthquake event.';
$currentPage = 'earthquakes';
$includeLeaflet = true;

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero event-hero">
  <div class="event-hero-main">
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.event.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1 id="event-detail-title" class="event-title">
      <span id="event-title-mag" class="event-title-mag"><?= htmlspecialchars(str_replace('M', 'M ', $titleMag), ENT_QUOTES, 'UTF-8'); ?></span>
      <span id="event-title-place" class="event-title-place"><?= htmlspecialchars($titlePlace, ENT_QUOTES, 'UTF-8'); ?></span>
    </h1>
    <p id="event-meta-line" class="event-meta-line">
      Pending date · Pending UTC · Depth <?= htmlspecialchars($depth !== '' ? "{$depth} km" : 'Pending depth', ENT_QUOTES, 'UTF-8'); ?> · Coordinates pending
    </p>
    <p id="event-context-line" class="event-context-line">Classification pending · Region pending · Inland · Automatic</p>
  </div>
  <div class="hero-side event-hero-side">
    <div class="hero-actions event-hero-actions">
      <a
        id="event-open-aftershocks"
        class="btn btn-primary"
        href="<?= htmlspecialchars($aftershocksUrl, ENT_QUOTES, 'UTF-8'); ?>"
        <?= $showAftershocksCta ? '' : 'hidden aria-hidden="true" style="display:none"'; ?>
      ><?= htmlspecialchars(qk_t('page.event.open_aftershocks'), ENT_QUOTES, 'UTF-8'); ?></a>
      <a class="btn btn-primary" href="/maps.php"><?= htmlspecialchars(qk_t('page.event.open_maps_hub'), ENT_QUOTES, 'UTF-8'); ?></a>
      <a class="btn btn-ghost" href="/earthquakes.php"><?= htmlspecialchars(qk_t('page.event.back_to_earthquakes'), ENT_QUOTES, 'UTF-8'); ?></a>
    </div>
  </div>
</main>

<section class="panel panel-kpi event-kpi-row">
  <article id="event-kpi-magnitude-card" class="card kpi-card event-kpi-card event-kpi-primary" data-intensity="<?= htmlspecialchars($initialMagnitudeBand, ENT_QUOTES, 'UTF-8'); ?>">
    <p class="kpi-label"><?= htmlspecialchars(qk_t('page.event.kpi_magnitude'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p id="event-kpi-mag" class="kpi-value"><?= htmlspecialchars($titleMag, ENT_QUOTES, 'UTF-8'); ?></p>
    <p class="kpi-note"><?= htmlspecialchars(qk_t('page.event.kpi_magnitude_note'), ENT_QUOTES, 'UTF-8'); ?></p>
  </article>
  <article class="card kpi-card event-kpi-card">
    <p class="kpi-label"><?= htmlspecialchars(qk_t('page.event.kpi_depth'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p id="event-kpi-depth" class="kpi-value"><?= htmlspecialchars($depth !== '' ? "{$depth} km" : 'Unavailable', ENT_QUOTES, 'UTF-8'); ?></p>
    <p class="kpi-note"><?= htmlspecialchars(qk_t('page.event.kpi_depth_note'), ENT_QUOTES, 'UTF-8'); ?></p>
  </article>
  <article class="card kpi-card event-kpi-card">
    <p class="kpi-label"><?= htmlspecialchars(qk_t('page.event.kpi_plate_boundary'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p id="event-kpi-plate-distance" class="kpi-value">Pending</p>
    <p class="kpi-note"><?= htmlspecialchars(qk_t('page.event.kpi_plate_boundary_note'), ENT_QUOTES, 'UTF-8'); ?></p>
  </article>
  <article class="card kpi-card event-kpi-card">
    <p class="kpi-label"><?= htmlspecialchars(qk_t('page.event.kpi_regime'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p id="event-kpi-regime" class="kpi-value">Not classified yet</p>
    <p id="event-kpi-regime-note" class="kpi-note"><?= htmlspecialchars($timeNote, ENT_QUOTES, 'UTF-8'); ?></p>
  </article>
</section>

<section class="panel event-section-head event-spatial-head">
  <div>
    <p class="eyebrow event-section-eyebrow"><?= htmlspecialchars(qk_t('page.event.spatial_context'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h2 class="event-section-title"><?= htmlspecialchars(qk_t('page.event.main_spatial_section'), ENT_QUOTES, 'UTF-8'); ?></h2>
  </div>
</section>

<section class="panel panel-main event-spatial-main">
  <article class="card map-card event-map-card">
    <div class="feed-head">
      <h3><?= htmlspecialchars(qk_t('page.event.zone_tectonic_map'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p class="feed-meta"><?= htmlspecialchars(qk_t('page.event.zone_tectonic_map_sub'), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div class="map-wrap insight-map-wrap">
      <div id="event-detail-map" class="world-map-leaflet" aria-label="Event zone map"></div>
    </div>
    <div class="insight-badges event-status-rails" aria-label="Map layer status">
      <span id="event-layer-plates" class="insight-badge"><?= htmlspecialchars(qk_t('page.event.layer_plates_loading'), ENT_QUOTES, 'UTF-8'); ?></span>
      <span id="event-layer-faults" class="insight-badge"><?= htmlspecialchars(qk_t('page.event.layer_faults_loading'), ENT_QUOTES, 'UTF-8'); ?></span>
      <span id="event-layer-strong" class="insight-badge">Strong nearby: pending</span>
      <span id="event-layer-window" class="insight-badge">Window: last 24h feed</span>
    </div>
  </article>

  <div class="event-side-column">
    <article class="card side-card event-side-console">
      <section class="event-console-block event-console-primary">
        <h3><?= htmlspecialchars(qk_t('page.event.zone_briefing'), ENT_QUOTES, 'UTF-8'); ?></h3>
        <p id="event-zone-summary" class="kpi-note event-briefing-summary">Loading context for local geodynamic briefing.</p>
      </section>
      <section class="event-console-block event-console-faults">
        <h3><?= htmlspecialchars(qk_t('page.event.nearest_active_fault'), ENT_QUOTES, 'UTF-8'); ?></h3>
        <ul id="event-fault-list" class="events-list fault-list-scroll">
          <li class="event-item">Loading fault context for this zone.</li>
        </ul>
      </section>
    </article>

  </div>
</section>

<section class="panel event-section-head event-history-head">
  <div>
    <p class="eyebrow event-section-eyebrow"><?= htmlspecialchars(qk_t('page.event.historical_context'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h2 class="event-section-title"><?= htmlspecialchars(qk_t('page.event.historical_context'), ENT_QUOTES, 'UTF-8'); ?></h2>
    <p class="event-section-subtitle"><?= htmlspecialchars(qk_t('page.event.historical_context_sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</section>

<section class="panel panel-kpi event-history-kpi">
  <article class="card kpi-card event-kpi-card">
    <p class="kpi-label"><?= htmlspecialchars(qk_t('page.event.kpi_historical_records'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p id="hist-kpi-total" class="kpi-value">Awaiting archive data</p>
    <p class="kpi-note">Complete archive count in this zone</p>
  </article>
  <article class="card kpi-card event-kpi-card">
    <p class="kpi-label"><?= htmlspecialchars(qk_t('page.event.kpi_archive_window'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p id="hist-kpi-window" class="kpi-value">1900-now</p>
    <p class="kpi-note">USGS historical availability</p>
  </article>
  <article class="card kpi-card event-kpi-card">
    <p class="kpi-label"><?= htmlspecialchars(qk_t('page.event.kpi_strongest_historical'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p id="hist-kpi-strongest" class="kpi-value">Pending</p>
    <p class="kpi-note"><?= htmlspecialchars(str_replace('{km}', (string) $historyRadiusKm, qk_t('page.event.kpi_strongest_note_100')), ENT_QUOTES, 'UTF-8'); ?></p>
  </article>
  <article class="card kpi-card event-kpi-card">
    <p class="kpi-label"><?= htmlspecialchars(qk_t('page.event.kpi_loaded_pages'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p id="hist-kpi-pages" class="kpi-value">0/0</p>
    <p id="hist-kpi-source" class="kpi-note">Awaiting archive data</p>
  </article>
</section>

<section class="panel panel-main event-history-main">
  <article class="card event-history-strongest-card">
    <div class="feed-head">
      <h3><?= htmlspecialchars(str_replace('{km}', (string) $historyRadiusKm, qk_t('page.event.strongest_historical_events')), ENT_QUOTES, 'UTF-8'); ?></h3>
    </div>
    <ul id="event-history-strongest-list" class="events-list live-feed-scroll history-list-scroll">
      <li class="event-item">Loading strongest historical records for this zone.</li>
    </ul>
  </article>
  <article class="card side-card event-history-stream-card">
    <h3><?= htmlspecialchars(qk_t('page.event.historical_archive_stream'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <ul id="event-history-list" class="events-list live-feed-scroll history-list-scroll history-list-compact">
      <li class="event-item">Awaiting archive data for this zone stream.</li>
    </ul>
    <button id="event-history-more" class="timeline-more" type="button" hidden><?= htmlspecialchars(qk_t('page.event.load_older_history'), ENT_QUOTES, 'UTF-8'); ?></button>
  </article>
</section>

<script>
  (() => {
    const params = new URLSearchParams(window.location.search);
    const q = {
      id: params.get("id") || "",
      place: params.get("place") || "",
      time: params.get("time") || "",
      mag: Number(params.get("mag")),
      depth: Number(params.get("depth")),
      lat: Number(params.get("lat")),
      lon: Number(params.get("lon")),
    };

    const mapContainer = document.querySelector("#event-detail-map");
    const spatialMapCard = document.querySelector(".event-map-card");
    const spatialSideColumn = document.querySelector(".event-side-column");
    const titleMagLine = document.querySelector("#event-title-mag");
    const titlePlaceLine = document.querySelector("#event-title-place");
    const openAftershocksButton = document.querySelector("#event-open-aftershocks");
    const metaLine = document.querySelector("#event-meta-line");
    const kpiMagCard = document.querySelector("#event-kpi-magnitude-card");
    const contextLine = document.querySelector("#event-context-line");
    const kpiMag = document.querySelector("#event-kpi-mag");
    const kpiDepth = document.querySelector("#event-kpi-depth");
    const kpiPlateDistance = document.querySelector("#event-kpi-plate-distance");
    const kpiRegime = document.querySelector("#event-kpi-regime");
    const kpiRegimeNote = document.querySelector("#event-kpi-regime-note");
    const layerPlates = document.querySelector("#event-layer-plates");
    const layerFaults = document.querySelector("#event-layer-faults");
    const layerStrong = document.querySelector("#event-layer-strong");
    const layerWindow = document.querySelector("#event-layer-window");
    const zoneSummary = document.querySelector("#event-zone-summary");
    const faultList = document.querySelector("#event-fault-list");
    const strongList = document.querySelector("#event-nearby-strong-list");
    const ringList = document.querySelector("#event-ring-list");
    const regionContextList = document.querySelector("#event-region-context-list");
    const histKpiTotal = document.querySelector("#hist-kpi-total");
    const histKpiStrongest = document.querySelector("#hist-kpi-strongest");
    const histKpiPages = document.querySelector("#hist-kpi-pages");
    const histKpiSource = document.querySelector("#hist-kpi-source");
    const historyStrongestList = document.querySelector("#event-history-strongest-list");
    const historyList = document.querySelector("#event-history-list");
    const historyMoreButton = document.querySelector("#event-history-more");
    const i18n = {
      unknown: <?= json_encode(qk_t('page.event.js_unknown', 'Unknown'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      unavailable: <?= json_encode(qk_t('page.event.js_unavailable', 'Unavailable'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      notAvailable: <?= json_encode(qk_t('page.event.js_not_available', 'Not available'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      pendingTime: <?= json_encode(qk_t('page.event.js_pending_time', 'Pending time'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      pendingDate: <?= json_encode(qk_t('page.event.js_pending_date', 'Pending date'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      pendingUtc: <?= json_encode(qk_t('page.event.js_pending_utc', 'Pending UTC'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      coordinatesPending: <?= json_encode(qk_t('page.event.js_coordinates_pending', 'Coordinates pending'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      pendingClassification: <?= json_encode(qk_t('page.event.js_pending_classification', 'Pending classification'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      faultNameUnavailable: <?= json_encode(qk_t('page.event.js_fault_name_unavailable', 'Fault segment (name unavailable)'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      mapLayerContextUnavailable: <?= json_encode(qk_t('page.event.js_map_layer_context_unavailable', 'Context unavailable right now.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      faultContextUnavailable: <?= json_encode(qk_t('page.event.js_fault_context_unavailable', 'Fault context unavailable.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      ringStatsPending: <?= json_encode(qk_t('page.event.js_ring_stats_pending', 'Ring statistics pending.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      regionalSnapshotUnavailable: <?= json_encode(qk_t('page.event.js_regional_snapshot_unavailable', 'Regional snapshot unavailable.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      regionalSynthesisPending: <?= json_encode(qk_t('page.event.js_regional_synthesis_pending', 'Regional synthesis pending.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      regionalSynthesisUnavailable: <?= json_encode(qk_t('page.event.js_regional_synthesis_unavailable', 'Regional synthesis panel is active but context is currently unavailable.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      awaitingArchiveStrongest: <?= json_encode(qk_t('page.event.js_awaiting_archive_strongest', 'Awaiting archive data for strongest records.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      awaitingArchiveStream: <?= json_encode(qk_t('page.event.js_awaiting_archive_stream', 'Awaiting archive data for stream view.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      awaitingArchiveData: <?= json_encode(qk_t('page.event.js_awaiting_archive_data', 'Awaiting archive data'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      eventCoordinatesMissing: <?= json_encode(qk_t('page.event.js_event_coordinates_missing', 'Event coordinates missing. Open this page from the event list in Earthquakes.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      liveFeedUnavailable: <?= json_encode(qk_t('page.event.js_live_feed_unavailable', 'Live feed unavailable. Showing best available event context.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      coordinatesUnavailable: <?= json_encode(qk_t('page.event.js_coordinates_unavailable', 'Coordinates unavailable for selected event.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      mapReducedMode: <?= json_encode(qk_t('page.event.js_map_reduced_mode', 'Map rendered in reduced mode. Context modules are still loading.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      nearbyReducedMode: <?= json_encode(qk_t('page.event.js_nearby_reduced_mode', 'Nearby strong context loading in reduced mode.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      loadOlderHistory: <?= json_encode(qk_t('page.event.load_older_history', 'Load older history'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      allHistoryLoaded: <?= json_encode(qk_t('page.event.js_all_history_loaded', 'All history loaded'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      unknownLocation: <?= json_encode(qk_t('page.event.js_unknown_location', 'Unknown location'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      regionalZone: <?= json_encode(qk_t('page.event.js_regional_zone', 'Regional zone'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      inland: <?= json_encode(qk_t('page.event.js_inland', 'Inland'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      automatic: <?= json_encode(qk_t('page.event.js_automatic', 'Automatic'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      noM5Nearby: <?= json_encode(qk_t('page.event.js_no_m5_nearby', 'No M5+ events within 500 km in current feed window.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      ringLabel: <?= json_encode(qk_t('page.event.js_ring_label', '{km} km ring'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      ringCounts: <?= json_encode(qk_t('page.event.js_ring_counts', 'M4+: {m4} · M5+: {m5}'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      strongEventsWider900: <?= json_encode(qk_t('page.event.js_strong_events_wider_900', '{count} strong events in wider 900 km context'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      noM5NearbyInRadius: <?= json_encode(qk_t('page.event.js_no_m5_nearby_in_radius', 'No M5+ events within {radius} km in current feed window.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      strongNearbyBadge: <?= json_encode(qk_t('page.event.js_strong_nearby_badge', 'Strong nearby ({radius} km): {count}'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      strongEventsRegionalRadius: <?= json_encode(qk_t('page.event.js_strong_events_regional_radius', '{count} strong events in {radius} km regional context'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      directionN: <?= json_encode(qk_t('page.event.js_direction_n', 'N'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      directionNE: <?= json_encode(qk_t('page.event.js_direction_ne', 'NE'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      directionE: <?= json_encode(qk_t('page.event.js_direction_e', 'E'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      directionSE: <?= json_encode(qk_t('page.event.js_direction_se', 'SE'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      directionS: <?= json_encode(qk_t('page.event.js_direction_s', 'S'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      directionSW: <?= json_encode(qk_t('page.event.js_direction_sw', 'SW'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      directionW: <?= json_encode(qk_t('page.event.js_direction_w', 'W'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      directionNW: <?= json_encode(qk_t('page.event.js_direction_nw', 'NW'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      beforeMain: <?= json_encode(qk_t('page.event.js_before_main', 'before main event'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      afterMain: <?= json_encode(qk_t('page.event.js_after_main', 'after main event'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      fromMainDistance: <?= json_encode(qk_t('page.event.js_from_main_distance', '{distance} km {direction} from main event'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      deltaFromMain: <?= json_encode(qk_t('page.event.js_delta_from_main', '{delta} {position}'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      noStrongRegionalContext: <?= json_encode(qk_t('page.event.js_no_strong_regional_context', 'No strong regional context in current feed window.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      strongEventsWiderFrame: <?= json_encode(qk_t('page.event.js_strong_events_wider_frame', '{count} strong events in wider regional frame'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      regionalSynthesisFromWindow: <?= json_encode(qk_t('page.event.js_regional_synthesis_from_window', 'Regional synthesis pending from current window.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      activeRegionalFrame: <?= json_encode(qk_t('page.event.js_active_regional_frame', 'Active regional frame: {regions}. Seismic clustering signals are being tracked for deeper synthesis.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      regionalSynthesisAwaitingStronger: <?= json_encode(qk_t('page.event.js_regional_synthesis_awaiting_stronger', 'Regional synthesis panel active. Awaiting stronger clustering signals in the current frame.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      loaded: <?= json_encode(qk_t('page.event.js_loaded', 'loaded'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      noNearbyFaultsDataset: <?= json_encode(qk_t('page.event.js_no_nearby_faults_dataset', 'No nearby active faults available from current dataset.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      noResolvedActiveFault: <?= json_encode(qk_t('page.event.js_no_resolved_active_fault', 'no resolved active fault'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      platesProxyLoaded: <?= json_encode(qk_t('page.event.js_plates_proxy_loaded', 'Plates: proxy loaded'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      faultsOperationalProxy: <?= json_encode(qk_t('page.event.js_faults_operational_proxy', 'Faults: operational proxy'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      regionalSeismicLine: <?= json_encode(qk_t('page.event.js_regional_seismic_line', 'Regional seismic line'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      noNearbyStrongProxy: <?= json_encode(qk_t('page.event.js_no_nearby_strong_proxy', 'No nearby strong seismic proxy signals in current feed.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      selectedEventTooltip: <?= json_encode(qk_t('page.event.js_selected_event_tooltip', 'Selected event'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      shakeMapModelled: <?= json_encode(qk_t('page.event.js_shakemap_modelled', 'ShakeMap: modelled MMI fallback'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      shakeMapUnavailable: <?= json_encode(qk_t('page.event.js_shakemap_unavailable', 'ShakeMap: unavailable'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      pending: <?= json_encode(qk_t('page.event.js_pending', 'Pending'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      recordsLoaded: <?= json_encode(qk_t('page.event.js_records_loaded', '{loaded}/{total} records loaded ({page}/{pages} pages)'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      noStrongestHistoricalRows: <?= json_encode(qk_t('page.event.js_no_strongest_historical_rows', 'No strongest historical rows available.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      archiveUnavailableFallback: <?= json_encode(qk_t('page.event.js_archive_unavailable_fallback', 'Historical archive unavailable (live fallback)'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      regimeProxy: <?= json_encode(qk_t('page.event.js_regime_proxy', '{regime} (proxy)'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      platesStatus: <?= json_encode(qk_t('page.event.js_plates_status', 'Plates: {status}'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      faultsStatus: <?= json_encode(qk_t('page.event.js_faults_status', 'Faults: {status}'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      operationalModeSummary: <?= json_encode(qk_t('page.event.js_operational_mode_summary', 'Operational mode: external tectonic layers unavailable. Local seismic proxy active ({count} nearby strong signals), depth {depth}, inferred regime computed with fallback geometry.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      shakeMapUsgs: <?= json_encode(qk_t('page.event.js_shakemap_usgs', 'ShakeMap: USGS intensity contours'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
    };
    const interpolate = (template, vars) =>
      String(template).replace(/\{([a-zA-Z0-9_]+)\}/g, (_, key) =>
        Object.prototype.hasOwnProperty.call(vars, key) ? String(vars[key]) : ""
      );

    let historyRows = [];
    let historyPage = 0;
    let historyTotalPages = 0;
    let historyTotalEvents = 0;
    const HISTORY_RADIUS_KM = <?= (int) $historyRadiusKm; ?>;

    let map = null;
    let eventLayer = null;
    let strongLayer = null;
    let plateLayer = null;
    let faultLayer = null;
    let selectedFaultLayer = null;
    let shakeLayer = null;

    const magnitudeColor = (magnitude) => {
      if (!Number.isFinite(magnitude)) return "#6b7280";
      const bucket = Math.max(1, Math.min(9, Math.floor(magnitude)));
      const palette = {
        1: "#3b82f6", 2: "#06b6d4", 3: "#14b8a6", 4: "#22c55e", 5: "#eab308",
        6: "#f59e0b", 7: "#f97316", 8: "#d946ef", 9: "#7e22ce",
      };
      return palette[bucket];
    };

    const toRad = (v) => (v * Math.PI) / 180;
    const haversineKm = (lat1, lon1, lat2, lon2) => {
      const dLat = toRad(lat2 - lat1);
      const dLon = toRad(lon2 - lon1);
      const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
      return 6371 * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
    };
    const bearingDegrees = (lat1, lon1, lat2, lon2) => {
      const p1 = toRad(lat1);
      const p2 = toRad(lat2);
      const dLon = toRad(lon2 - lon1);
      const y = Math.sin(dLon) * Math.cos(p2);
      const x = Math.cos(p1) * Math.sin(p2) - Math.sin(p1) * Math.cos(p2) * Math.cos(dLon);
      return (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;
    };
    const cardinalDirection = (bearing) => {
      if (!Number.isFinite(bearing)) return "";
      const labels = [
        i18n.directionN, i18n.directionNE, i18n.directionE, i18n.directionSE,
        i18n.directionS, i18n.directionSW, i18n.directionW, i18n.directionNW,
      ];
      const idx = Math.round(bearing / 45) % 8;
      return labels[idx];
    };
    const adaptiveRadii = (selected) => {
      const magnitude = Number(selected?.magnitude);
      const depth = Number(selected?.depth_km);
      const local = 30;
      let sequence = Number.isFinite(magnitude) && magnitude > 5.5 ? 100 : 50;
      let regional = 100;
      if (Number.isFinite(depth) && depth > 50) {
        const boost = Math.min(40, Math.round((Math.min(depth, 200) - 50) / 5));
        sequence = Math.min(140, sequence + boost);
        regional = Math.min(150, regional + boost);
      }
      return { local, sequence, regional };
    };
    const formatDeltaFromMain = (mainIso, eventIso) => {
      if (!mainIso || !eventIso) return i18n.pendingTime;
      const mainTs = Date.parse(mainIso);
      const eventTs = Date.parse(eventIso);
      if (!Number.isFinite(mainTs) || !Number.isFinite(eventTs)) return i18n.pendingTime;
      const deltaMs = eventTs - mainTs;
      const absMinutes = Math.max(0, Math.round(Math.abs(deltaMs) / 60000));
      const days = Math.floor(absMinutes / (60 * 24));
      const hours = Math.floor((absMinutes % (60 * 24)) / 60);
      const minutes = absMinutes % 60;
      const chunks = [];
      if (days > 0) chunks.push(`${days}d`);
      if (hours > 0 || days > 0) chunks.push(`${hours}h`);
      chunks.push(`${minutes}m`);
      const position = deltaMs >= 0 ? i18n.afterMain : i18n.beforeMain;
      return interpolate(i18n.deltaFromMain, { delta: chunks.join(" "), position });
    };
    const historyRowContext = (selected, row) => {
      const canComputeDistance =
        Number.isFinite(selected?.latitude) &&
        Number.isFinite(selected?.longitude) &&
        Number.isFinite(row?.latitude) &&
        Number.isFinite(row?.longitude);
      const distanceKm = canComputeDistance
        ? haversineKm(selected.latitude, selected.longitude, row.latitude, row.longitude)
        : Number.NaN;
      const direction = canComputeDistance
        ? cardinalDirection(bearingDegrees(selected.latitude, selected.longitude, row.latitude, row.longitude))
        : "";
      const distanceDirection = Number.isFinite(distanceKm)
        ? interpolate(i18n.fromMainDistance, {
          distance: distanceKm.toFixed(1),
          direction: direction || i18n.directionN,
        })
        : i18n.unavailable;
      return { distanceDirection };
    };
    const historyListRowHtml = (selected, row) => {
      const depth = asDepth(row.depth_km);
      const href = eventDetailHref(row);
      const context = historyRowContext(selected, row);
      return `<li class="event-item"><a class="snapshot-row-anchor" href="${href}"><strong>${magnitudeText(row.magnitude)} ${row.place || i18n.unknown}</strong><span class="event-history-row-meta"><span class="event-history-row-meta-left">${safeTimeHistory(row.event_time_utc)} · depth ${depth}</span><span class="event-history-row-meta-right">${context.distanceDirection}</span></span></a></li>`;
    };

    const parseRegion = (place) => {
      if (!place) return i18n.unknown;
      if (String(place).includes(" of ")) return String(place).split(" of ").slice(-1)[0].trim();
      const parts = String(place).split(",");
      return parts[parts.length - 1].trim() || String(place);
    };

    const collectCoordinates = (geometry, out) => {
      if (!geometry || typeof geometry !== "object") return;
      const coords = geometry.coordinates;
      if (!Array.isArray(coords)) return;
      if (typeof coords[0] === "number" && typeof coords[1] === "number") {
        out.push([coords[1], coords[0]]);
        return;
      }
      coords.forEach((row) => {
        if (Array.isArray(row)) collectCoordinates({ coordinates: row }, out);
      });
    };

    const nearestFeatureDistanceKm = (feature, lat, lon) => {
      const points = [];
      collectCoordinates(feature?.geometry, points);
      if (points.length === 0) return Number.POSITIVE_INFINITY;
      const step = points.length > 900 ? 6 : points.length > 300 ? 4 : 2;
      let best = Number.POSITIVE_INFINITY;
      for (let i = 0; i < points.length; i += step) {
        const [pLat, pLon] = points[i];
        const km = haversineKm(lat, lon, pLat, pLon);
        if (km < best) best = km;
      }
      return best;
    };

    const getFeatureName = (feature) => {
      const props = feature?.properties || {};
      const keys = ["name", "NAME", "fault_name", "FAULT_NAME", "fault", "structure", "id"];
      for (const key of keys) {
        const value = props[key];
        if (typeof value === "string" && value.trim() !== "") return value.trim();
      }
      return i18n.faultNameUnavailable;
    };

    const getSlipRate = (feature) => {
      const props = feature?.properties || {};
      for (const key of Object.keys(props)) {
        if (!/slip/i.test(key)) continue;
        const raw = props[key];
        if (typeof raw === "number" && Number.isFinite(raw)) return `${raw.toFixed(2)} mm/yr`;
        if (typeof raw === "string" && raw.trim() !== "") return raw.trim();
      }
      return i18n.notAvailable;
    };
    const focusFaultOnMap = (faultRow) => {
      if (!faultRow?.feature || !window.L) return;
      const theMap = ensureMap();
      if (!theMap) return;
      if (selectedFaultLayer) {
        selectedFaultLayer.clearLayers();
        window.L.geoJSON(faultRow.feature, {
          style: {
            color: "#ffe37a",
            weight: 4.2,
            opacity: 0.98,
            lineCap: "round",
            lineJoin: "round",
          },
        }).addTo(selectedFaultLayer);
      }
      try {
        const bounds = window.L.geoJSON(faultRow.feature).getBounds();
        if (bounds && bounds.isValid()) {
          theMap.fitBounds(bounds.pad(0.35), { maxZoom: 8 });
          selectedFaultLayer?.eachLayer((layer) => {
            if (layer && typeof layer.bringToFront === "function") layer.bringToFront();
          });
          return;
        }
      } catch (error) {
        // noop
      }
      const center = faultRow.feature?.geometry?.coordinates;
      if (Array.isArray(center) && typeof center[0] === "number" && typeof center[1] === "number") {
        theMap.setView([center[1], center[0]], 8);
      }
    };
    const bindFaultListInteractions = (nearbyFaults) => {
      if (!faultList) return;
      const items = faultList.querySelectorAll("[data-fault-index]");
      items.forEach((node) => {
        const onOpen = () => {
          const index = Number(node.getAttribute("data-fault-index"));
          if (!Number.isFinite(index) || !nearbyFaults[index]) return;
          faultList.querySelectorAll("[data-fault-index]").forEach((el) => el.classList.remove("is-selected"));
          node.classList.add("is-selected");
          focusFaultOnMap(nearbyFaults[index]);
        };
        node.addEventListener("click", onOpen);
        node.addEventListener("keydown", (event) => {
          if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            onOpen();
          }
        });
      });
    };

    const regimeLabel = (event, faultKm, plateKm) => {
      const depth = Number.isFinite(event.depth_km) ? event.depth_km : NaN;
      if (Number.isFinite(depth) && depth >= 300) return "Deep slab";
      if (Number.isFinite(depth) && depth >= 70 && plateKm <= 220) return "Subduction";
      if (faultKm <= 35 && Number.isFinite(depth) && depth < 70) return "Crustal fault";
      if (plateKm <= 140) return "Boundary";
      return "Intraplate";
    };

    const eventKey = (event) => {
      if (event.id) return String(event.id);
      const lat = Number.isFinite(event.latitude) ? event.latitude.toFixed(3) : "na";
      const lon = Number.isFinite(event.longitude) ? event.longitude.toFixed(3) : "na";
      return `${lat}|${lon}|${event.event_time_utc || "na"}`;
    };

    const ensureMap = () => {
      if (!mapContainer || !window.L) return null;
      if (map) return map;
      map = window.L.map(mapContainer, { zoomControl: true, worldCopyJump: true }).setView([10, 0], 2);
      window.L.tileLayer("https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png", {
        maxZoom: 9,
        minZoom: 2,
        attribution: "&copy; OpenStreetMap contributors &copy; CARTO",
      }).addTo(map);
      eventLayer = window.L.layerGroup().addTo(map);
      strongLayer = window.L.layerGroup().addTo(map);
      plateLayer = window.L.layerGroup().addTo(map);
      faultLayer = window.L.layerGroup().addTo(map);
      selectedFaultLayer = window.L.layerGroup().addTo(map);
      shakeLayer = window.L.layerGroup().addTo(map);
      return map;
    };

    const safeTime = (iso) => (iso ? new Date(iso).toLocaleString([], { month: "short", day: "2-digit", hour: "2-digit", minute: "2-digit" }) : i18n.pendingTime);
    const safeTimeHistory = (iso) => (iso
      ? new Date(iso).toLocaleString([], { year: "numeric", month: "short", day: "2-digit", hour: "2-digit", minute: "2-digit" })
      : i18n.pendingTime);
    const asMagnitude = (value) => (Number.isFinite(value) ? `M${value.toFixed(1)}` : i18n.unavailable);
    const asMagnitudeTitle = (value) => (Number.isFinite(value) ? `M ${value.toFixed(1)}` : "M ?");
    const magnitudeBandClass = (magnitude) => {
      if (!Number.isFinite(magnitude)) return "m-na";
      const bucket = Math.max(1, Math.min(9, Math.floor(magnitude)));
      return `m-b${bucket}`;
    };
    const magnitudeText = (magnitude) => {
      if (!Number.isFinite(magnitude)) return '<span class="mag-value m-na">M?</span>';
      return `<span class="mag-value ${magnitudeBandClass(magnitude)}">M${magnitude.toFixed(1)}</span>`;
    };
    const asDepth = (value) => (Number.isFinite(value) ? `${value.toFixed(1)} km` : i18n.unavailable);
    const asDistance = (value, { approximate = false } = {}) => {
      if (!Number.isFinite(value)) return i18n.unavailable;
      return `${approximate ? "~" : ""}${value.toFixed(0)} km`;
    };
    const severityLabel = (magnitude) => {
      if (!Number.isFinite(magnitude)) return i18n.pendingClassification;
      if (magnitude < 2) return "Microquake";
      if (magnitude < 4) return "Minor";
      if (magnitude < 5) return "Light";
      if (magnitude < 6) return "Moderate";
      return "Strong";
    };
    const intensityBand = (magnitude) => {
      if (!Number.isFinite(magnitude)) return "pending";
      const bucket = Math.max(1, Math.min(9, Math.floor(magnitude)));
      return `b${bucket}`;
    };
    const formatUtcMeta = (iso, depthKm, lat, lon) => {
      let datePart = i18n.pendingDate;
      let timePart = i18n.pendingUtc;
      if (iso) {
        const dt = new Date(iso);
        if (!Number.isNaN(dt.getTime())) {
          datePart = dt.toLocaleDateString("en-GB", {
            day: "2-digit",
            month: "short",
            year: "numeric",
            timeZone: "UTC",
          });
          timePart = `${dt.toLocaleTimeString("en-GB", {
            hour: "2-digit",
            minute: "2-digit",
            hour12: false,
            timeZone: "UTC",
          })} UTC`;
        }
      }
      const depthPart = `Depth ${asDepth(depthKm)}`;
      const coordPart = Number.isFinite(lat) && Number.isFinite(lon)
        ? `${lat.toFixed(3)}, ${lon.toFixed(3)}`
        : i18n.coordinatesPending;
      return `${datePart} · ${timePart} · ${depthPart} · ${coordPart}`;
    };
    const fetchJsonWithTimeout = async (url, timeoutMs = 8000) => {
      const controller = new AbortController();
      const timer = window.setTimeout(() => controller.abort(), timeoutMs);
      try {
        const response = await fetch(url, {
          headers: { Accept: "application/json" },
          signal: controller.signal,
        });
        if (!response.ok) {
          throw new Error(`Request failed ${response.status}`);
        }
        return await response.json();
      } finally {
        window.clearTimeout(timer);
      }
    };
    const setZoneSummary = (text) => {
      if (!zoneSummary) return;
      zoneSummary.textContent = text;
      zoneSummary.setAttribute("title", text);
    };
    const eventDetailHref = (event) => {
      const qd = new URLSearchParams();
      if (event?.id) qd.set("id", String(event.id));
      if (Number.isFinite(event?.latitude)) qd.set("lat", Number(event.latitude).toFixed(5));
      if (Number.isFinite(event?.longitude)) qd.set("lon", Number(event.longitude).toFixed(5));
      if (Number.isFinite(event?.magnitude)) qd.set("mag", Number(event.magnitude).toFixed(1));
      if (Number.isFinite(event?.depth_km)) qd.set("depth", Number(event.depth_km).toFixed(1));
      if (event?.place) qd.set("place", String(event.place));
      if (event?.event_time_utc) qd.set("time", String(event.event_time_utc));
      const currentLang = params.get("lang");
      if (currentLang) qd.set("lang", currentLang);
      return `/event.php?${qd.toString()}`;
    };

    const readMmi = (feature) => {
      const props = feature?.properties || {};
      const keys = ["value", "mmi", "MMI", "cmi", "intensity", "GRID_CODE"];
      for (const key of keys) {
        const raw = props[key];
        const value = Number(raw);
        if (Number.isFinite(value)) return value;
      }
      return NaN;
    };

    const mmiColor = (mmi) => {
      if (!Number.isFinite(mmi)) return "#6b7280";
      if (mmi < 3) return "#3b82f6";
      if (mmi < 4) return "#06b6d4";
      if (mmi < 5) return "#14b8a6";
      if (mmi < 6) return "#22c55e";
      if (mmi < 7) return "#eab308";
      if (mmi < 8) return "#f59e0b";
      if (mmi < 9) return "#f97316";
      if (mmi < 10) return "#d946ef";
      return "#7e22ce";
    };

    const usgsEventIdFrom = (event) => {
      if (event?.id && typeof event.id === "string" && event.id.trim() !== "") return event.id.trim();
      const url = String(event?.source_url || "");
      const match = url.match(/eventpage\/([a-z0-9]+)/i);
      return match ? match[1] : "";
    };

    const renderModelledShake = (selected) => {
      if (!shakeLayer) return false;
      shakeLayer.clearLayers();
      const mag = Number(selected.magnitude);
      const depth = Number.isFinite(selected.depth_km) ? selected.depth_km : 12;
      if (!Number.isFinite(mag)) return false;

      const levels = [7, 6, 5, 4, 3];
      let plotted = 0;
      levels.forEach((mmi) => {
        // Simplified attenuation fallback (MMI) for when official contours are unavailable.
        const rhs = (1.08 * mag + 1.55 - mmi);
        const rh = Math.pow(10, rhs / 1.35);
        const radiusKm = Math.max(4, Math.sqrt(Math.max(0, rh * rh - depth * depth)));
        if (!Number.isFinite(radiusKm) || radiusKm < 4) return;
        window.L.circle([selected.latitude, selected.longitude], {
          radius: radiusKm * 1000,
          color: mmiColor(mmi),
          weight: 1.1,
          opacity: 0.7,
          fillColor: mmiColor(mmi),
          fillOpacity: Math.max(0.06, 0.2 - (7 - mmi) * 0.03),
          interactive: false,
        }).addTo(shakeLayer);
        plotted += 1;
      });

      if (layerWindow) layerWindow.textContent = plotted > 0 ? i18n.shakeMapModelled : i18n.shakeMapUnavailable;
      return plotted > 0;
    };

    const loadAndRenderShakeMap = async (selected) => {
      if (!shakeLayer) return false;
      shakeLayer.clearLayers();
      const eventId = usgsEventIdFrom(selected);
      if (!eventId) {
        return renderModelledShake(selected);
      }
      try {
        const detail = await fetchJsonWithTimeout(`https://earthquake.usgs.gov/fdsnws/event/1/query?format=geojson&eventid=${encodeURIComponent(eventId)}`, 9000);
        const products = detail?.properties?.products || {};
        const shakeProducts = Array.isArray(products.shakemap) ? products.shakemap : [];
        if (shakeProducts.length === 0) {
          return renderModelledShake(selected);
        }
        const latest = shakeProducts[0] || {};
        const contents = latest.contents && typeof latest.contents === "object" ? latest.contents : {};
        const contourEntry = Object.entries(contents).find(([key]) => /cont[_-]?mi\.json/i.test(key))
          || Object.entries(contents).find(([key]) => /mmi.*geojson/i.test(key))
          || Object.entries(contents).find(([key]) => /intensity.*geojson/i.test(key));
        const contourUrl = contourEntry?.[1]?.url;
        if (!contourUrl) {
          return renderModelledShake(selected);
        }
        const contours = await fetchJsonWithTimeout(contourUrl, 9000);
        const features = Array.isArray(contours?.features) ? contours.features : [];
        if (features.length === 0) {
          return renderModelledShake(selected);
        }
        window.L.geoJSON(contours, {
          style: (feature) => {
            const mmi = readMmi(feature);
            return {
              color: mmiColor(mmi),
              weight: 1.2,
              opacity: 0.72,
              fillColor: mmiColor(mmi),
              fillOpacity: 0.12,
            };
          },
          onEachFeature: (feature, layer) => {
            const mmi = readMmi(feature);
            if (Number.isFinite(mmi)) layer.bindTooltip(`MMI ${mmi.toFixed(1)}`, { direction: "center" });
          },
        }).addTo(shakeLayer);
        if (layerWindow) layerWindow.textContent = i18n.shakeMapUsgs;
        return true;
      } catch (error) {
        return renderModelledShake(selected);
      }
    };

    const setFailure = (message) => {
      setZoneSummary(message);
      if (strongList) strongList.innerHTML = `<li class='event-item'>${i18n.mapLayerContextUnavailable}</li>`;
      if (faultList) faultList.innerHTML = `<li class='event-item'>${i18n.faultContextUnavailable}</li>`;
      if (ringList) ringList.innerHTML = `<li class='event-item'>${i18n.ringStatsPending}</li>`;
      if (regionContextList) regionContextList.innerHTML = `<li class='event-item'>${i18n.regionalSnapshotUnavailable}</li>`;
      if (historyStrongestList) historyStrongestList.innerHTML = `<li class='event-item'>${i18n.awaitingArchiveStrongest}</li>`;
      if (historyList) historyList.innerHTML = `<li class='event-item'>${i18n.awaitingArchiveStream}</li>`;
      if (histKpiSource) histKpiSource.textContent = i18n.awaitingArchiveData;
    };

    const fallbackPlateLines = [
      [[-55, -75], [60, -75]],
      [[-55, -110], [60, -110]],
      [[-55, 160], [60, 160]],
      [[-55, -20], [70, -20]],
      [[-35, 30], [20, 40]],
      [[20, 40], [45, 90]],
    ];

    const buildHistoryFallbackFromFeed = (selected, events) => {
      const rows = events
        .filter((row) => Number.isFinite(row.latitude) && Number.isFinite(row.longitude))
        .map((row) => ({
          ...row,
          distanceKm: haversineKm(selected.latitude, selected.longitude, row.latitude, row.longitude),
        }))
        .filter((row) => row.distanceKm <= HISTORY_RADIUS_KM)
        .sort((a, b) => {
          const aTs = row => (row.event_time_utc ? Date.parse(row.event_time_utc) : 0);
          return aTs(b) - aTs(a);
        });

      const strongest = rows
        .filter((row) => Number.isFinite(row.magnitude))
        .sort((a, b) => b.magnitude - a.magnitude)
        .slice(0, 12);

      historyRows = rows.slice(0, 80);
      historyPage = rows.length > 0 ? 1 : 0;
      historyTotalPages = rows.length > 0 ? 1 : 0;
      historyTotalEvents = rows.length;

      if (histKpiTotal) histKpiTotal.textContent = String(rows.length);
      if (histKpiPages) histKpiPages.textContent = rows.length > 0 ? "1/1" : "0/0";
      if (histKpiSource) histKpiSource.textContent = "Operational fallback (last 24h feed)";
      if (histKpiStrongest) {
        histKpiStrongest.textContent = strongest[0] && Number.isFinite(strongest[0].magnitude)
          ? `M${strongest[0].magnitude.toFixed(1)}`
          : i18n.pending;
      }

      if (historyStrongestList) {
        historyStrongestList.innerHTML = strongest.length > 0
          ? strongest.map((row) => historyListRowHtml(selected, row)).join("")
          : `<li class='event-item'>${i18n.awaitingArchiveData}</li>`;
      }

      if (historyMoreButton) {
        historyMoreButton.hidden = true;
      }

      renderHistoryList(selected);
    };

    const renderHistoryList = (selected) => {
      if (!historyList) return;
      if (historyRows.length === 0) {
        historyList.innerHTML = `<li class='event-item'>${i18n.awaitingArchiveData}</li>`;
      } else {
        historyList.innerHTML = historyRows.map((row) => {
          return historyListRowHtml(selected, row);
        }).join("");
      }

      if (histKpiPages) {
        histKpiPages.textContent = `${historyPage}/${historyTotalPages}`;
      }
      if (historyMoreButton) {
        const hasMore = historyPage < historyTotalPages;
        historyMoreButton.hidden = !hasMore;
        historyMoreButton.textContent = hasMore ? i18n.loadOlderHistory : i18n.allHistoryLoaded;
      }
    };

    const loadHistoryPage = async (selected, pageToLoad) => {
      const url = `/api/event-history.php?lat=${selected.latitude.toFixed(5)}&lon=${selected.longitude.toFixed(5)}&radius_km=${HISTORY_RADIUS_KM}&start=1900-01-01&page=${pageToLoad}&per_page=80`;
      const payload = await fetchJsonWithTimeout(url, 10000);
      const rows = Array.isArray(payload.events) ? payload.events : [];
      const strongest = Array.isArray(payload.strongest_events) ? payload.strongest_events : [];

      historyPage = typeof payload.page === "number" ? payload.page : pageToLoad;
      historyTotalPages = typeof payload.total_pages === "number" ? payload.total_pages : historyTotalPages;
      historyTotalEvents = typeof payload.total_events === "number" ? payload.total_events : historyTotalEvents;
      if (pageToLoad === 1) {
        historyRows = rows.slice();
      } else {
        historyRows = historyRows.concat(rows);
      }
      if (histKpiTotal) histKpiTotal.textContent = String(historyTotalEvents);
      if (histKpiSource) histKpiSource.textContent = `${payload.provider || "USGS historical archive"}${payload.from_cache ? " (cache)" : ""}`;
      if (histKpiStrongest) {
        const top = strongest[0] || null;
        histKpiStrongest.textContent = top && Number.isFinite(top.magnitude) ? `M${top.magnitude.toFixed(1)}` : i18n.pending;
      }

      if (historyStrongestList && pageToLoad === 1) {
        historyStrongestList.innerHTML = strongest.length > 0
          ? strongest.slice(0, 10).map((row) => historyListRowHtml(selected, row)).join("")
          : `<li class='event-item'>${i18n.noStrongestHistoricalRows}</li>`;
      }

      renderHistoryList(selected);
    };

    const hydrateBasics = (event) => {
      if (titleMagLine) titleMagLine.textContent = asMagnitudeTitle(event.magnitude);
      if (titlePlaceLine) titlePlaceLine.textContent = event.place || i18n.unknownLocation;
      if (openAftershocksButton) {
        const magnitude = Number(event?.magnitude);
        const canOpenAftershocks = Number.isFinite(magnitude) && magnitude >= 6;
        openAftershocksButton.hidden = !canOpenAftershocks;
        openAftershocksButton.setAttribute("aria-hidden", canOpenAftershocks ? "false" : "true");
        openAftershocksButton.style.display = canOpenAftershocks ? "" : "none";
      }
      if (metaLine) {
        metaLine.textContent = formatUtcMeta(event.event_time_utc, event.depth_km, event.latitude, event.longitude);
      }
      if (kpiMag) kpiMag.textContent = asMagnitude(event.magnitude);
      if (kpiDepth) kpiDepth.textContent = asDepth(event.depth_km);
      if (kpiMagCard) kpiMagCard.setAttribute("data-intensity", intensityBand(event.magnitude));
      if (kpiRegimeNote) {
        const whenUtc = formatUtcMeta(event.event_time_utc, NaN, NaN, NaN).split(" · ").slice(0, 2).join(" · ");
        kpiRegimeNote.textContent = `Event reference ${whenUtc}`;
      }
      if (contextLine) {
        contextLine.textContent = `${severityLabel(event.magnitude)} · ${parseRegion(event.place || i18n.regionalZone)} · ${i18n.inland} · ${i18n.automatic}`;
      }
    };

    const findSelectedEvent = (events) => {
      if (!Array.isArray(events) || events.length === 0) return null;
      const hasDirectSelection = Boolean(
        q.id
        || q.time
        || q.place
        || (Number.isFinite(q.lat) && Number.isFinite(q.lon))
      );
      if (q.id) {
        const byId = events.find((row) => String(row.id || "") === q.id);
        if (byId) return byId;
      }
      if (Number.isFinite(q.lat) && Number.isFinite(q.lon)) {
        const byCoord = events.find((row) =>
          Number.isFinite(row.latitude) &&
          Number.isFinite(row.longitude) &&
          Math.abs(row.latitude - q.lat) < 0.02 &&
          Math.abs(row.longitude - q.lon) < 0.02
        );
        if (byCoord) return byCoord;
      }
      if (q.time) {
        const byTime = events.find((row) => row.event_time_utc === q.time);
        if (byTime) return byTime;
      }
      if (q.place) {
        const byPlace = events.find((row) => String(row.place || "").toLowerCase() === q.place.toLowerCase());
        if (byPlace) return byPlace;
      }
      return hasDirectSelection ? null : events[0];
    };

    const buildNearbyStrong = (selected, events, radii) =>
      events
        .filter((row) => Number.isFinite(row.magnitude) && row.magnitude >= 5)
        .map((row) => ({
          ...row,
          distanceKm:
            Number.isFinite(row.latitude) && Number.isFinite(row.longitude)
              ? haversineKm(selected.latitude, selected.longitude, row.latitude, row.longitude)
              : Number.POSITIVE_INFINITY,
          direction:
            Number.isFinite(row.latitude) && Number.isFinite(row.longitude)
              ? cardinalDirection(bearingDegrees(selected.latitude, selected.longitude, row.latitude, row.longitude))
              : "",
        }))
        .filter((row) => row.distanceKm <= radii.sequence && eventKey(row) !== eventKey(selected))
        .sort((a, b) => a.distanceKm - b.distanceKm || b.magnitude - a.magnitude);

    const renderNearby = (selected, nearby, radii) => {
      if (strongList) {
        strongList.innerHTML = nearby.length > 0
          ? nearby.slice(0, 10).map((row) => {
            const distanceDirection = interpolate(i18n.fromMainDistance, {
              distance: row.distanceKm.toFixed(1),
              direction: row.direction || i18n.directionN,
            });
            const delta = formatDeltaFromMain(selected.event_time_utc, row.event_time_utc);
            return `<li class="event-item"><strong>${magnitudeText(row.magnitude)} ${row.place || i18n.unknown}</strong><br />${distanceDirection} · ${delta}</li>`;
          }).join("")
          : `<li class='event-item'>${interpolate(i18n.noM5NearbyInRadius, { radius: radii.sequence })}</li>`;
      }
      if (layerStrong) {
        layerStrong.clearLayers();
        nearby.slice(0, 20).forEach((row) => {
          if (!Number.isFinite(row.latitude) || !Number.isFinite(row.longitude)) return;
          window.L.circleMarker([row.latitude, row.longitude], {
            radius: Math.max(5, Math.min(11, 2 + row.magnitude)),
            color: "rgba(255,255,255,0.9)",
            weight: 1,
            fillColor: magnitudeColor(row.magnitude),
            fillOpacity: 0.82,
          }).bindTooltip(`M${row.magnitude.toFixed(1)} · ${row.distanceKm.toFixed(0)} km`).addTo(layerStrong);
        });
      }
      if (layerStrong) {
        const count = nearby.filter((row) => row.distanceKm <= radii.sequence).length;
        if (layerStrong && layerStrong.getLayers().length === 0 && layerStrong) {
          // no-op
        }
        if (layerStrong && document.querySelector("#event-layer-strong")) {
          document.querySelector("#event-layer-strong").textContent = interpolate(i18n.strongNearbyBadge, {
            radius: radii.sequence,
            count,
          });
        }
      }
    };

    const renderRings = (selected, events, radii) => {
      const ringSet = new Set([radii.local, 50, radii.regional]);
      const rings = [...ringSet].sort((a, b) => a - b).map((km) => {
        const countM4 = events.filter((row) => {
          if (!Number.isFinite(row.latitude) || !Number.isFinite(row.longitude) || !Number.isFinite(row.magnitude)) return false;
          return row.magnitude >= 4 && haversineKm(selected.latitude, selected.longitude, row.latitude, row.longitude) <= km;
        }).length;
        const countM5 = events.filter((row) => {
          if (!Number.isFinite(row.latitude) || !Number.isFinite(row.longitude) || !Number.isFinite(row.magnitude)) return false;
          return row.magnitude >= 5 && haversineKm(selected.latitude, selected.longitude, row.latitude, row.longitude) <= km;
        }).length;
        return { km, countM4, countM5 };
      });
      if (ringList) {
        ringList.innerHTML = rings
          .map((row) => `<li class="event-item"><strong>${interpolate(i18n.ringLabel, { km: row.km })}</strong><br />${interpolate(i18n.ringCounts, { m4: row.countM4, m5: row.countM5 })}</li>`)
          .join("");
      }
    };

    const renderRegionContext = (selected, events, radii) => {
      const regionCounter = new Map();
      events.forEach((row) => {
        if (!Number.isFinite(row.latitude) || !Number.isFinite(row.longitude) || !Number.isFinite(row.magnitude) || row.magnitude < 5) return;
        const km = haversineKm(selected.latitude, selected.longitude, row.latitude, row.longitude);
        if (km > radii.regional) return;
        const region = parseRegion(row.place || "");
        regionCounter.set(region, (regionCounter.get(region) || 0) + 1);
      });
      const top = [...regionCounter.entries()].sort((a, b) => b[1] - a[1]).slice(0, 6);
      if (regionContextList) {
        regionContextList.innerHTML = top.length > 0
          ? top.map(([region, count]) => `<li class="event-item"><strong>${region}</strong><br />${interpolate(i18n.strongEventsRegionalRadius, { count, radius: radii.regional })}</li>`).join("")
          : `<li class='event-item'>${i18n.noStrongRegionalContext}</li>`;
      }
    };

    const loadTectonicData = async () => {
      const lat = Number.isFinite(q.lat) ? q.lat : null;
      const lon = Number.isFinite(q.lon) ? q.lon : null;
      const focus = lat !== null && lon !== null
        ? `&lat=${lat.toFixed(5)}&lon=${lon.toFixed(5)}&radius_km=900`
        : "";
      const payload = await fetchJsonWithTimeout(`/api/tectonic-context.php?scope=local${focus}&max_plates=30&max_faults=48`, 9000);
      return {
        plates: payload && typeof payload === "object" ? payload.plates : null,
        faults: payload && typeof payload === "object" ? payload.faults : null,
      };
    };

    const renderTectonic = (selected, tectonic) => {
      const plateFeatures = Array.isArray(tectonic.plates?.features) ? tectonic.plates.features : [];
      const faultFeatures = Array.isArray(tectonic.faults?.features) ? tectonic.faults.features : [];

      const nearbyPlates = plateFeatures
        .map((feature) => ({ feature, km: nearestFeatureDistanceKm(feature, selected.latitude, selected.longitude) }))
        .filter((row) => Number.isFinite(row.km))
        .sort((a, b) => a.km - b.km)
        .slice(0, 20);
      const nearbyFaults = faultFeatures
        .map((feature) => ({ feature, km: nearestFeatureDistanceKm(feature, selected.latitude, selected.longitude) }))
        .filter((row) => Number.isFinite(row.km))
        .sort((a, b) => a.km - b.km)
        .slice(0, 24);

      if (plateLayer) {
        plateLayer.clearLayers();
        nearbyPlates.forEach((row) => {
          window.L.geoJSON(row.feature, {
            style: { color: "#22d3ee", weight: 2.1, opacity: 0.8 },
          }).addTo(plateLayer);
        });
      }
      if (faultLayer) {
        faultLayer.clearLayers();
        nearbyFaults.forEach((row) => {
          window.L.geoJSON(row.feature, {
            style: { color: "#ff7a5f", weight: 1.5, opacity: 0.64 },
          }).addTo(faultLayer);
        });
      }
      if (selectedFaultLayer) {
        selectedFaultLayer.clearLayers();
      }

      const nearestPlateKm = nearbyPlates.length > 0 ? nearbyPlates[0].km : Number.POSITIVE_INFINITY;
      const nearestFault = nearbyFaults.length > 0 ? nearbyFaults[0] : null;
      const nearestFaultKm = nearestFault ? nearestFault.km : Number.POSITIVE_INFINITY;
      const regime = regimeLabel(selected, nearestFaultKm, nearestPlateKm);

      if (kpiPlateDistance) {
        kpiPlateDistance.textContent = asDistance(nearestPlateKm);
      }
      if (kpiRegime) {
        kpiRegime.textContent = regime;
      }
      if (layerPlates) {
        layerPlates.textContent = interpolate(i18n.platesStatus, { status: nearbyPlates.length > 0 ? i18n.loaded : i18n.unavailable });
      }
      if (layerFaults) {
        layerFaults.textContent = interpolate(i18n.faultsStatus, { status: nearbyFaults.length > 0 ? i18n.loaded : i18n.unavailable });
      }

      if (faultList) {
        faultList.innerHTML = nearbyFaults.length > 0
          ? nearbyFaults.slice(0, 5).map((row, index) => {
            const name = getFeatureName(row.feature);
            const slip = getSlipRate(row.feature);
            return `<li class="event-item event-item-clickable" data-fault-index="${index}" role="button" tabindex="0"><strong>${name}</strong><br />${row.km.toFixed(0)} km · slip ${slip}</li>`;
          }).join("")
          : `<li class='event-item'>${i18n.noNearbyFaultsDataset}</li>`;
        bindFaultListInteractions(nearbyFaults.slice(0, 5));
      }

      if (zoneSummary) {
        const faultName = nearestFault ? getFeatureName(nearestFault.feature) : i18n.noResolvedActiveFault;
        setZoneSummary(`${regime}. Nearest active fault: ${faultName}. Boundary proximity: ${asDistance(nearestPlateKm)}.`);
      }
    };

    const renderTectonicFallback = (selected, nearbyStrong) => {
      if (plateLayer) {
        plateLayer.clearLayers();
        fallbackPlateLines.forEach((line) => {
          window.L.polyline(line, {
            color: "#22d3ee",
            weight: 1.5,
            opacity: 0.75,
            dashArray: "6 6",
          }).addTo(plateLayer);
        });
      }
      if (faultLayer) {
        faultLayer.clearLayers();
      }
      if (selectedFaultLayer) {
        selectedFaultLayer.clearLayers();
      }

      const nearestProxyPlateKm = fallbackPlateLines
        .flatMap((line) => line)
        .map(([lat, lon]) => haversineKm(selected.latitude, selected.longitude, lat, lon))
        .sort((a, b) => a - b)[0];
      const localSignals = nearbyStrong.slice(0, 6);

      if (kpiPlateDistance) {
        kpiPlateDistance.textContent = asDistance(nearestProxyPlateKm, { approximate: true });
      }
      if (kpiRegime) {
        const regime = regimeLabel(selected, 999, Number.isFinite(nearestProxyPlateKm) ? nearestProxyPlateKm : 999);
        kpiRegime.textContent = interpolate(i18n.regimeProxy, { regime });
      }
      if (layerPlates) layerPlates.textContent = i18n.platesProxyLoaded;
      if (layerFaults) layerFaults.textContent = i18n.faultsOperationalProxy;

      if (faultList) {
        faultList.innerHTML = localSignals.length > 0
          ? localSignals.map((row) => {
            return `<li class="event-item"><strong>${row.place || i18n.regionalSeismicLine}</strong><br />${row.distanceKm.toFixed(0)} km · ${magnitudeText(row.magnitude)} signal</li>`;
          }).join("")
          : `<li class='event-item'>${i18n.noNearbyStrongProxy}</li>`;
      }

      if (zoneSummary) {
        const depth = asDepth(selected.depth_km);
        setZoneSummary(interpolate(i18n.operationalModeSummary, { count: localSignals.length, depth }));
      }
    };

    let spatialSyncScheduled = false;

    const syncSpatialHeights = () => {
      if (!spatialMapCard || !spatialSideColumn) return;
      if (window.matchMedia("(max-width: 1120px)").matches) {
        spatialSideColumn.style.height = "";
        return;
      }
      spatialSideColumn.style.height = `${spatialMapCard.offsetHeight}px`;
    };

    const scheduleSpatialSync = () => {
      if (spatialSyncScheduled) return;
      spatialSyncScheduled = true;
      window.requestAnimationFrame(() => {
        spatialSyncScheduled = false;
        syncSpatialHeights();
      });
    };

    window.addEventListener("resize", scheduleSpatialSync);

    const init = async () => {
      if (!Number.isFinite(q.lat) || !Number.isFinite(q.lon)) {
        setFailure(i18n.eventCoordinatesMissing);
        return;
      }

      let events = [];
      try {
        const eqPayload = await fetchJsonWithTimeout("/api/earthquakes.php", 10000);
        events = Array.isArray(eqPayload.events) ? eqPayload.events : [];
      } catch (error) {
        setZoneSummary(i18n.liveFeedUnavailable);
      }

      const selected = findSelectedEvent(events) || {
        id: q.id,
        place: q.place || i18n.unknown,
        event_time_utc: q.time || null,
        magnitude: Number.isFinite(q.mag) ? q.mag : NaN,
        depth_km: Number.isFinite(q.depth) ? q.depth : NaN,
        latitude: q.lat,
        longitude: q.lon,
      };

      if (!Number.isFinite(selected.latitude) || !Number.isFinite(selected.longitude)) {
        setFailure(i18n.coordinatesUnavailable);
        return;
      }

      try {
        hydrateBasics(selected);
        const theMap = ensureMap();
        if (theMap && eventLayer) {
          eventLayer.clearLayers();
          window.L.circleMarker([selected.latitude, selected.longitude], {
            radius: 11,
            color: "rgba(255,255,255,0.96)",
            weight: 2,
            fillColor: magnitudeColor(selected.magnitude),
            fillOpacity: 0.95,
          }).bindTooltip(i18n.selectedEventTooltip, { direction: "top", opacity: 0.95 }).addTo(eventLayer);

          theMap.setView([selected.latitude, selected.longitude], 6);
        }
        scheduleSpatialSync();
      } catch (error) {
        setZoneSummary(i18n.mapReducedMode);
      }

      let nearbyStrong = [];
      try {
        const radii = adaptiveRadii(selected);
        nearbyStrong = buildNearbyStrong(selected, events, radii);
        renderNearby(selected, nearbyStrong, radii);
        renderRings(selected, events, radii);
        renderRegionContext(selected, events, radii);
      } catch (error) {
        if (strongList) strongList.innerHTML = `<li class='event-item'>${i18n.nearbyReducedMode}</li>`;
      }

      try {
        await loadAndRenderShakeMap(selected);
        const tectonic = await loadTectonicData();
        const hasTectonic = Array.isArray(tectonic?.plates?.features) || Array.isArray(tectonic?.faults?.features);
        if (hasTectonic) {
          renderTectonic(selected, tectonic);
        } else {
          renderTectonicFallback(selected, nearbyStrong);
        }
        scheduleSpatialSync();
      } catch (error) {
        renderTectonicFallback(selected, nearbyStrong);
        scheduleSpatialSync();
      }

      try {
        await loadHistoryPage(selected, 1);
        historyMoreButton?.addEventListener("click", async () => {
          if (historyPage >= historyTotalPages) {
            return;
          }
          historyMoreButton.disabled = true;
          try {
            await loadHistoryPage(selected, historyPage + 1);
          } finally {
            historyMoreButton.disabled = false;
          }
        });
      } catch (error) {
        try {
          buildHistoryFallbackFromFeed(selected, events);
        } catch (fallbackError) {
          if (histKpiSource) histKpiSource.textContent = i18n.awaitingArchiveData;
        }
      }

      scheduleSpatialSync();
    };

    init();
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
