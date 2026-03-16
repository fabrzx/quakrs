<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Data Archive';
$pageDescription = 'Searchable earthquake archive with global map location autocomplete.';
$currentPage = 'data-archive';
$includeLeaflet = true;

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('data_archive.hero_eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('data_archive.hero_title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('data_archive.hero_sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label"><?= htmlspecialchars(qk_t('data_archive.kpi_visible'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p id="archive-kpi-visible" class="kpi-value">--</p>
    <p id="archive-kpi-visible-note" class="kpi-note"><?= htmlspecialchars(qk_t('data_archive.kpi_visible_note'), ENT_QUOTES, 'UTF-8'); ?></p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label"><?= htmlspecialchars(qk_t('data_archive.kpi_dataset'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p id="archive-kpi-total" class="kpi-value">--</p>
    <p class="kpi-note"><?= htmlspecialchars(qk_t('data_archive.kpi_dataset_note'), ENT_QUOTES, 'UTF-8'); ?></p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label"><?= htmlspecialchars(qk_t('data_archive.kpi_max'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p id="archive-kpi-max-mag" class="kpi-value">--</p>
    <p class="kpi-note"><?= htmlspecialchars(qk_t('data_archive.kpi_max_note'), ENT_QUOTES, 'UTF-8'); ?></p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label"><?= htmlspecialchars(qk_t('data_archive.kpi_latest'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p id="archive-kpi-latest" class="kpi-value">--</p>
    <p id="archive-kpi-source" class="kpi-note"><?= htmlspecialchars(qk_t('data_archive.kpi_source_loading'), ENT_QUOTES, 'UTF-8'); ?></p>
  </article>
</section>

<section class="panel">
  <article class="card">
    <div class="feed-head">
      <h3><?= htmlspecialchars(qk_t('data_archive.filters_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p class="feed-meta"><?= htmlspecialchars(qk_t('data_archive.filters_sub'), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div class="archive-filter-grid">
      <label class="event-item archive-filter-item">
        <strong><?= htmlspecialchars(qk_t('data_archive.filter_window'), ENT_QUOTES, 'UTF-8'); ?></strong><br />
        <select id="archive-filter-window">
          <option value="24h"><?= htmlspecialchars(qk_t('data_archive.window_24h'), ENT_QUOTES, 'UTF-8'); ?></option>
          <option value="7d"><?= htmlspecialchars(qk_t('data_archive.window_7d'), ENT_QUOTES, 'UTF-8'); ?></option>
          <option value="30d" selected><?= htmlspecialchars(qk_t('data_archive.window_30d'), ENT_QUOTES, 'UTF-8'); ?></option>
          <option value="90d"><?= htmlspecialchars(qk_t('data_archive.window_90d'), ENT_QUOTES, 'UTF-8'); ?></option>
          <option value="1y"><?= htmlspecialchars(qk_t('data_archive.window_1y'), ENT_QUOTES, 'UTF-8'); ?></option>
          <option value="all"><?= htmlspecialchars(qk_t('data_archive.window_all'), ENT_QUOTES, 'UTF-8'); ?></option>
          <option value="custom"><?= htmlspecialchars(qk_t('data_archive.window_custom'), ENT_QUOTES, 'UTF-8'); ?></option>
        </select>
      </label>
      <label class="event-item archive-filter-item">
        <strong><?= htmlspecialchars(qk_t('data_archive.filter_location'), ENT_QUOTES, 'UTF-8'); ?></strong><br />
        <input id="archive-filter-location" type="search" list="archive-location-list" placeholder="<?= htmlspecialchars(qk_t('data_archive.location_placeholder'), ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" />
        <datalist id="archive-location-list"></datalist>
      </label>
      <label class="event-item archive-filter-item">
        <strong><?= htmlspecialchars(qk_t('data_archive.filter_min_magnitude'), ENT_QUOTES, 'UTF-8'); ?></strong><br />
        <select id="archive-filter-mag">
          <option value="0">M0+</option>
          <option value="2">M2+</option>
          <option value="3">M3+</option>
          <option value="4">M4+</option>
          <option value="5">M5+</option>
          <option value="6">M6+</option>
        </select>
      </label>
      <label class="event-item archive-filter-item">
        <strong><?= htmlspecialchars(qk_t('data_archive.filter_depth_band'), ENT_QUOTES, 'UTF-8'); ?></strong><br />
        <select id="archive-filter-depth">
          <option value="all"><?= htmlspecialchars(qk_t('data_archive.depth_all'), ENT_QUOTES, 'UTF-8'); ?></option>
          <option value="shallow"><?= htmlspecialchars(qk_t('data_archive.depth_shallow'), ENT_QUOTES, 'UTF-8'); ?></option>
          <option value="intermediate"><?= htmlspecialchars(qk_t('data_archive.depth_intermediate'), ENT_QUOTES, 'UTF-8'); ?></option>
          <option value="deep"><?= htmlspecialchars(qk_t('data_archive.depth_deep'), ENT_QUOTES, 'UTF-8'); ?></option>
        </select>
      </label>
    </div>
    <div class="preset-row">
      <button id="archive-search-btn" class="btn btn-ghost" type="button"><?= htmlspecialchars(qk_t('data_archive.search'), ENT_QUOTES, 'UTF-8'); ?></button>
      <button id="archive-custom-range-btn" class="btn btn-ghost" type="button"><?= htmlspecialchars(qk_t('data_archive.custom_range'), ENT_QUOTES, 'UTF-8'); ?></button>
      <span id="archive-custom-range-label" class="kpi-note"><?= htmlspecialchars(qk_t('data_archive.custom_range_none'), ENT_QUOTES, 'UTF-8'); ?></span>
      <button id="archive-reset-filters" class="btn btn-ghost" type="button"><?= htmlspecialchars(qk_t('data_archive.reset_filters'), ENT_QUOTES, 'UTF-8'); ?></button>
    </div>
    <div id="archive-search-feedback" class="archive-search-feedback" aria-live="polite" aria-atomic="true">
      <div class="archive-search-feedback-track">
        <span id="archive-search-feedback-bar" class="archive-search-feedback-bar" style="width:0%"></span>
      </div>
      <p id="archive-search-feedback-text" class="feed-meta"><?= htmlspecialchars(qk_t('data_archive.ready', 'Ready'), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
  </article>
</section>

<dialog id="archive-custom-range-dialog" class="archive-dialog">
  <form method="dialog" class="archive-dialog-card">
    <h3><?= htmlspecialchars(qk_t('data_archive.custom_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p class="kpi-note"><?= htmlspecialchars(qk_t('data_archive.custom_sub'), ENT_QUOTES, 'UTF-8'); ?></p>
    <label class="archive-dialog-field">
      <strong><?= htmlspecialchars(qk_t('data_archive.custom_from'), ENT_QUOTES, 'UTF-8'); ?></strong>
      <input id="archive-filter-from" type="date" />
    </label>
    <label class="archive-dialog-field">
      <strong><?= htmlspecialchars(qk_t('data_archive.custom_to'), ENT_QUOTES, 'UTF-8'); ?></strong>
      <input id="archive-filter-to" type="date" />
    </label>
    <div class="preset-row">
      <button id="archive-custom-range-cancel" class="btn btn-ghost" type="button"><?= htmlspecialchars(qk_t('data_archive.custom_cancel'), ENT_QUOTES, 'UTF-8'); ?></button>
      <button id="archive-custom-range-apply" class="btn btn-ghost" type="button"><?= htmlspecialchars(qk_t('data_archive.custom_apply'), ENT_QUOTES, 'UTF-8'); ?></button>
    </div>
  </form>
</dialog>

<section class="panel panel-main earthquakes-main-layout archive-map-layout">
  <article class="map-card archive-map-card">
    <div class="feed-head">
      <h3><?= htmlspecialchars(qk_t('data_archive.map_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p id="archive-map-meta" class="feed-meta"><?= htmlspecialchars(qk_t('data_archive.map_sub'), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div class="archive-radius-control event-item">
      <label for="archive-filter-radius"><strong><?= htmlspecialchars(qk_t('data_archive.radius'), ENT_QUOTES, 'UTF-8'); ?></strong></label>
      <input id="archive-filter-radius" type="range" min="5" max="1200" step="5" value="120" />
      <span id="archive-radius-value" class="archive-radius-value">120 km</span>
    </div>
    <p id="archive-center-status" class="kpi-note archive-center-status"><?= htmlspecialchars(qk_t('data_archive.center_not_set', 'Center not set. Select a valid location or click on the map (radius disabled).'), ENT_QUOTES, 'UTF-8'); ?></p>
    <div class="map-wrap">
      <div id="archive-map-leaflet" class="world-map-leaflet archive-map-leaflet" aria-label="<?= htmlspecialchars(qk_t('data_archive.map_aria'), ENT_QUOTES, 'UTF-8'); ?>"></div>
    </div>
  </article>
  <article class="card side-card archive-list-card">
    <div class="archive-list-head">
      <h3><?= htmlspecialchars(qk_t('data_archive.matching_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <label class="archive-list-sort">
        <span class="sr-only"><?= htmlspecialchars(qk_t('data_archive.sort_aria'), ENT_QUOTES, 'UTF-8'); ?></span>
        <select id="archive-list-sort" aria-label="<?= htmlspecialchars(qk_t('data_archive.sort_aria'), ENT_QUOTES, 'UTF-8'); ?>">
          <option value="date_desc" selected><?= htmlspecialchars(qk_t('data_archive.sort_date_desc'), ENT_QUOTES, 'UTF-8'); ?></option>
          <option value="date_asc"><?= htmlspecialchars(qk_t('data_archive.sort_date_asc'), ENT_QUOTES, 'UTF-8'); ?></option>
          <option value="mag_desc"><?= htmlspecialchars(qk_t('data_archive.sort_mag_desc'), ENT_QUOTES, 'UTF-8'); ?></option>
          <option value="mag_asc"><?= htmlspecialchars(qk_t('data_archive.sort_mag_asc'), ENT_QUOTES, 'UTF-8'); ?></option>
        </select>
      </label>
    </div>
    <p id="archive-feed-meta" class="feed-meta"><?= htmlspecialchars(qk_t('data_archive.feed_loading'), ENT_QUOTES, 'UTF-8'); ?></p>
    <ul id="archive-map-list" class="events-list live-feed-scroll archive-map-list">
      <li class="event-item"><?= htmlspecialchars(qk_t('data_archive.events_loading'), ENT_QUOTES, 'UTF-8'); ?></li>
    </ul>
  </article>
</section>

<section class="panel page-grid">
  <article class="card page-card">
    <h3><?= htmlspecialchars(qk_t('data_archive.insight_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p id="archive-insight-summary" class="insight-lead"><?= htmlspecialchars(qk_t('data_archive.insight_sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </article>
  <article class="card page-card">
    <h3><?= htmlspecialchars(qk_t('data_archive.coverage_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p id="archive-insight-depth" class="insight-lead"><?= htmlspecialchars(qk_t('data_archive.coverage_sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </article>
  <article class="card page-card">
    <h3><?= htmlspecialchars(qk_t('data_archive.blend_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <div id="archive-insight-providers" class="insight-pills">
      <span class="insight-pill"><?= htmlspecialchars(qk_t('data_archive.providers_loading'), ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
  </article>
</section>

<script>
  (() => {
    const i18n = {
      search: <?= json_encode(qk_t('data_archive.search'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      searching: <?= json_encode(qk_t('data_archive.searching'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      resetFilters: <?= json_encode(qk_t('data_archive.reset_filters'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      resetting: <?= json_encode(qk_t('data_archive.resetting'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      loadingArchive: <?= json_encode(qk_t('data_archive.loading_archive', 'Loading archive'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      searchingInProgress: <?= json_encode(qk_t('data_archive.searching_in_progress', 'Searching in progress'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      completed: <?= json_encode(qk_t('data_archive.completed', 'Completed'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      ready: <?= json_encode(qk_t('data_archive.ready', 'Ready'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      searchError: <?= json_encode(qk_t('data_archive.search_error', 'Search failed'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      noCustomRange: <?= json_encode(qk_t('data_archive.custom_range_none'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      centerNotSet: <?= json_encode(qk_t('data_archive.center_not_set', 'Center not set. Select a valid location or click on the map (radius disabled).'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      centerCustomPoint: <?= json_encode(qk_t('data_archive.center_custom_point', 'Custom point'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      centerSetPressSearch: <?= json_encode(qk_t('data_archive.center_set_press_search', 'set. Press Search to apply filters.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      centerSelected: <?= json_encode(qk_t('data_archive.center_selected', 'selected'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      centerReady: <?= json_encode(qk_t('data_archive.center_ready', 'ready'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      locationNotFound: <?= json_encode(qk_t('data_archive.location_not_found', 'Location not found. Try adding country/region.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      searchFailed: <?= json_encode(qk_t('data_archive.search_failed', 'Request failed'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      searchDone: <?= json_encode(qk_t('data_archive.search_done', 'completed'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      resetFiltersAction: <?= json_encode(qk_t('data_archive.reset_filters_action', 'Reset filters'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      preparingSearch: <?= json_encode(qk_t('data_archive.preparing_search', 'Preparing search'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      radiusOff: <?= json_encode(qk_t('data_archive.radius_off', 'off'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      centerPrefix: <?= json_encode(qk_t('data_archive.center_prefix', 'Center'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      centerDetails: <?= json_encode(qk_t('data_archive.center_details', 'Center: {name} ({lat}, {lon})'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      kpiRowsNote: <?= json_encode(qk_t('data_archive.kpi_rows_note', 'Rows loaded in current filtered result set'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      feedRowsArchive: <?= json_encode(qk_t('data_archive.feed_rows_archive', '{count} matching rows in archive'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      summaryEventsFound: <?= json_encode(qk_t('data_archive.summary_events_found', '{count} events found {locality}.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      coverageDepthMix: <?= json_encode(qk_t('data_archive.coverage_depth_mix', '{center} · Radius: {radius}. Depth mix: {shallow} shallow, {intermediate} intermediate, {deep} deep.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      sourcePrefix: <?= json_encode(qk_t('data_archive.source_prefix', 'Source'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      noEventsMatch: <?= json_encode(qk_t('data_archive.no_events_match', 'No events match current filters.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      openEvent: <?= json_encode(qk_t('data_archive.open_event', 'Open event'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      noProvidersSet: <?= json_encode(qk_t('data_archive.no_providers_set', 'No providers in current result set'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      archiveUnavailable: <?= json_encode(qk_t('data_archive.archive_unavailable', 'Archive unavailable'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      sourceUnavailable: <?= json_encode(qk_t('data_archive.source_unavailable', 'Source unavailable'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      summaryUnavailable: <?= json_encode(qk_t('data_archive.summary_unavailable', 'Unable to build archive summary right now.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      depthUnavailable: <?= json_encode(qk_t('data_archive.depth_unavailable', 'Depth summary unavailable.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      providerMixUnavailable: <?= json_encode(qk_t('data_archive.provider_mix_unavailable', 'Provider mix unavailable'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      loadedRowsProgress: <?= json_encode(qk_t('data_archive.loaded_rows_progress', 'Loaded {rows} / {total} rows'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      loadingRowsProgress: <?= json_encode(qk_t('data_archive.loading_rows_progress', 'Loading {rows} / {total} rows...'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      localityAround: <?= json_encode(qk_t('data_archive.locality_around', 'around "{value}"'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      localityAll: <?= json_encode(qk_t('data_archive.locality_all', 'across all locations'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      loadingRowsArchive: <?= json_encode(qk_t('data_archive.loading_rows_archive', 'Loading archive rows {rows} / {total}...'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      centerPlaceNotFound: <?= json_encode(qk_t('data_archive.center_place_not_found', 'Center place not found in archive, try map click or broader text.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      clickMapCenter: <?= json_encode(qk_t('data_archive.click_map_center', 'Click map to set center point'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      depthLabel: <?= json_encode(qk_t('data_archive.depth_label', 'Depth'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
      centerRadius: <?= json_encode(qk_t('data_archive.center_radius', 'Center {name} · radius {radius}'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
    };
    const mapContainer = document.querySelector("#archive-map-leaflet");
    const mapList = document.querySelector("#archive-map-list");
    const mapMeta = document.querySelector("#archive-map-meta");
    const feedMeta = document.querySelector("#archive-feed-meta");
    const listSort = document.querySelector("#archive-list-sort");

    const filterWindow = document.querySelector("#archive-filter-window");
    const filterFrom = document.querySelector("#archive-filter-from");
    const filterTo = document.querySelector("#archive-filter-to");
    const searchBtn = document.querySelector("#archive-search-btn");
    const customRangeBtn = document.querySelector("#archive-custom-range-btn");
    const customRangeLabel = document.querySelector("#archive-custom-range-label");
    const customRangeDialog = document.querySelector("#archive-custom-range-dialog");
    const customRangeApply = document.querySelector("#archive-custom-range-apply");
    const customRangeCancel = document.querySelector("#archive-custom-range-cancel");
    const filterLocation = document.querySelector("#archive-filter-location");
    const locationList = document.querySelector("#archive-location-list");
    const filterRadius = document.querySelector("#archive-filter-radius");
    const radiusValue = document.querySelector("#archive-radius-value");
    const centerStatus = document.querySelector("#archive-center-status");

    const filterMag = document.querySelector("#archive-filter-mag");
    const filterDepth = document.querySelector("#archive-filter-depth");
    const resetFilters = document.querySelector("#archive-reset-filters");
    const searchFeedback = document.querySelector("#archive-search-feedback");
    const searchFeedbackBar = document.querySelector("#archive-search-feedback-bar");
    const searchFeedbackText = document.querySelector("#archive-search-feedback-text");

    const kpiVisible = document.querySelector("#archive-kpi-visible");
    const kpiVisibleNote = document.querySelector("#archive-kpi-visible-note");
    const kpiTotal = document.querySelector("#archive-kpi-total");
    const kpiMaxMag = document.querySelector("#archive-kpi-max-mag");
    const kpiLatest = document.querySelector("#archive-kpi-latest");
    const kpiSource = document.querySelector("#archive-kpi-source");

    const insightSummary = document.querySelector("#archive-insight-summary");
    const insightDepth = document.querySelector("#archive-insight-depth");
    const insightProviders = document.querySelector("#archive-insight-providers");

    const perPage = 500;
    let debounceTimer = null;

    let map = null;
    let tileLayer = null;
    let centerMarker = null;
    let radiusCircle = null;
    let selectedRowKey = null;
    const eventMarkers = [];
    const markerByKey = new Map();

    let manualCenter = null;
    let resolvedCenter = null;
    let lastNonCustomWindow = "30d";
    let pinnedCenterPlace = "";
    let pendingMapFocus = false;
    let requestSeq = 0;
    let pendingFetchReason = i18n.loadingArchive;
    let locationSuggestSeq = 0;
    let locationSuggestTimer = null;
    let feedbackProgressTimer = null;
    let feedbackHideTimer = null;
    let feedbackProgress = 0;
    let currentVisibleRows = [];
    let activeArchiveController = null;
    const locationGeoMap = new Map();

    const escapeHtml = (value) => String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/\"/g, "&quot;")
      .replace(/'/g, "&#39;");

    const escapeAttrValue = (value) => String(value ?? "")
      .replace(/\\/g, "\\\\")
      .replace(/"/g, '\\"');

    const interpolate = (template, vars) =>
      String(template).replace(/\{([a-zA-Z0-9_]+)\}/g, (_, key) =>
        Object.prototype.hasOwnProperty.call(vars, key) ? String(vars[key]) : ""
      );

    const eventKey = (row, index) => {
      if (row && typeof row.id === "string" && row.id !== "") return row.id;
      const lat = typeof row?.latitude === "number" ? row.latitude.toFixed(3) : "na";
      const lon = typeof row?.longitude === "number" ? row.longitude.toFixed(3) : "na";
      const ts = row?.event_time_utc || "na";
      return `${lat}|${lon}|${ts}|${index}`;
    };

    const eventDetailUrl = (event) => {
      const params = new URLSearchParams();
      if (event && typeof event === "object") {
        if (typeof event.id === "string" && event.id !== "") params.set("id", event.id);
        if (typeof event.latitude === "number") params.set("lat", event.latitude.toFixed(5));
        if (typeof event.longitude === "number") params.set("lon", event.longitude.toFixed(5));
        if (typeof event.magnitude === "number") params.set("mag", event.magnitude.toFixed(2));
        if (typeof event.depth_km === "number") params.set("depth", event.depth_km.toFixed(2));
        if (event.place) params.set("place", String(event.place));
        if (event.event_time_utc) params.set("time", String(event.event_time_utc));
      }
      return `/event.php?${params.toString()}`;
    };

    const timeLabel = (iso) => {
      if (!iso) return "n/a";
      return new Date(iso).toLocaleString([], {
        year: "numeric",
        month: "short",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
      });
    };

    const classifyDepth = (depth) => {
      if (typeof depth !== "number" || Number.isNaN(depth)) return "all";
      if (depth < 70) return "shallow";
      if (depth < 300) return "intermediate";
      return "deep";
    };

    const haversineKm = (lat1, lon1, lat2, lon2) => {
      const toRad = (value) => (value * Math.PI) / 180;
      const dLat = toRad(lat2 - lat1);
      const dLon = toRad(lon2 - lon1);
      const a = Math.sin(dLat / 2) ** 2
        + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
      return 6371 * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
    };

    const radiusKm = () => {
      const value = Number(filterRadius?.value || "120");
      if (!Number.isFinite(value) || value <= 0) return 120;
      return Math.max(5, Math.min(1200, value));
    };

    const hasActiveCenter = () => (
      !!resolvedCenter
      && typeof resolvedCenter.latitude === "number"
      && typeof resolvedCenter.longitude === "number"
    );

    const hasCenterIntent = () => {
      if (manualCenter && typeof manualCenter.latitude === "number" && typeof manualCenter.longitude === "number") {
        return true;
      }
      const location = normalizeSelectedLocation();
      const centerPlace = String(pinnedCenterPlace || location || "").trim();
      return centerPlace !== "";
    };

    const syncRadiusControl = () => {
      const enabled = hasActiveCenter();
      if (filterRadius instanceof HTMLInputElement) {
        filterRadius.disabled = !enabled;
      }
      if (radiusValue) {
        radiusValue.textContent = enabled ? `${radiusKm()} km` : i18n.radiusOff;
      }
    };

    const setRadiusLabel = () => {
      syncRadiusControl();
    };

    const setActionButtonsBusy = (busy, label = i18n.searching) => {
      if (searchBtn instanceof HTMLButtonElement) {
        searchBtn.disabled = busy;
        searchBtn.textContent = busy ? label : i18n.search;
      }
      if (resetFilters instanceof HTMLButtonElement) {
        resetFilters.disabled = busy;
        resetFilters.textContent = busy ? i18n.resetting : i18n.resetFilters;
      }
    };

    const stopFeedbackTimers = () => {
      if (feedbackProgressTimer) {
        window.clearInterval(feedbackProgressTimer);
        feedbackProgressTimer = null;
      }
      if (feedbackHideTimer) {
        window.clearTimeout(feedbackHideTimer);
        feedbackHideTimer = null;
      }
    };

    const updateFeedbackUi = (percent, message, { active = true, error = false } = {}) => {
      if (!searchFeedback || !searchFeedbackBar || !searchFeedbackText) return;
      searchFeedback.classList.toggle("is-active", active);
      searchFeedback.classList.toggle("is-error", !!error);
      searchFeedbackBar.style.width = `${Math.max(0, Math.min(100, percent))}%`;
      searchFeedbackText.textContent = `${message} · ${Math.round(percent)}%`;
    };

    const beginFeedbackProgress = (reason) => {
      stopFeedbackTimers();
      feedbackProgress = Math.max(6, Math.min(18, feedbackProgress || 8));
      updateFeedbackUi(feedbackProgress, reason || i18n.searchingInProgress, { active: true, error: false });
      feedbackProgressTimer = window.setInterval(() => {
        feedbackProgress = Math.min(92, feedbackProgress + (Math.random() * 9 + 2));
        updateFeedbackUi(feedbackProgress, reason || i18n.searchingInProgress, { active: true, error: false });
      }, 180);
    };

    const finishFeedbackProgress = (ok, message) => {
      stopFeedbackTimers();
      if (ok) {
        feedbackProgress = 100;
        updateFeedbackUi(100, message || i18n.completed, { active: true, error: false });
        feedbackHideTimer = window.setTimeout(() => {
          updateFeedbackUi(0, i18n.ready, { active: false, error: false });
          feedbackProgress = 0;
        }, 850);
      } else {
        const stuckAt = Math.max(8, Math.min(95, feedbackProgress || 12));
        updateFeedbackUi(stuckAt, message || i18n.searchError, { active: true, error: true });
      }
    };

    const formatDateRangeLabel = () => {
      const from = String(filterFrom?.value || "").trim();
      const to = String(filterTo?.value || "").trim();
      if (!customRangeLabel) return;
      if (from === "" || to === "") {
        customRangeLabel.textContent = i18n.noCustomRange;
        return;
      }
      customRangeLabel.textContent = `${from} → ${to}`;
    };

    const openCustomRangeDialog = () => {
      const now = new Date();
      const toIso = now.toISOString().slice(0, 10);
      const fromIso = new Date(now.getTime() - (30 * 24 * 60 * 60 * 1000)).toISOString().slice(0, 10);
      if (filterFrom && String(filterFrom.value || "").trim() === "") filterFrom.value = fromIso;
      if (filterTo && String(filterTo.value || "").trim() === "") filterTo.value = toIso;
      if (customRangeDialog && typeof customRangeDialog.showModal === "function") {
        if (customRangeDialog.open) return;
        customRangeDialog.showModal();
      }
    };

    const parseIsoDate = (iso) => {
      if (!/^\d{4}-\d{2}-\d{2}$/.test(iso)) return null;
      const d = new Date(`${iso}T00:00:00`);
      return Number.isNaN(d.getTime()) ? null : d;
    };

    const setCenterStatus = (center) => {
      if (!centerStatus) return;
      if (!center || typeof center.latitude !== "number" || typeof center.longitude !== "number") {
        centerStatus.textContent = i18n.centerNotSet;
        syncRadiusControl();
        return;
      }
      const name = center.name ? String(center.name) : i18n.centerCustomPoint;
      centerStatus.textContent = interpolate(i18n.centerDetails, {
        name,
        lat: center.latitude.toFixed(3),
        lon: center.longitude.toFixed(3),
      });
      syncRadiusControl();
    };

    const ensureMap = () => {
      if (!mapContainer || !window.L) return;
      if (map) return;

      map = window.L.map(mapContainer, { zoomControl: true, worldCopyJump: true, preferCanvas: true }).setView([16, 8], 2);
      tileLayer = window.L.tileLayer("https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png", {
        attribution: "&copy; OpenStreetMap contributors &copy; CARTO",
      });
      tileLayer.addTo(map);

      map.on("click", (event) => {
        const lat = event?.latlng?.lat;
        const lon = event?.latlng?.lng;
        if (typeof lat !== "number" || typeof lon !== "number") return;

        manualCenter = {
          name: `${i18n.centerCustomPoint} ${lat.toFixed(3)}, ${lon.toFixed(3)}`,
          latitude: lat,
          longitude: lon,
          source: "manual-click",
        };
        resolvedCenter = manualCenter;
        drawCenterGeometry();
        setCenterStatus(resolvedCenter);
        if (mapMeta) {
          mapMeta.textContent = `Center ${resolvedCenter.name || i18n.centerSelected} ${i18n.centerSetPressSearch}`;
        }
      });
    };

    const clearMapEvents = () => {
      eventMarkers.forEach((marker) => marker.remove());
      eventMarkers.length = 0;
      markerByKey.clear();
    };

    const drawCenterGeometry = () => {
      if (!map) return;
      if (centerMarker) {
        centerMarker.remove();
        centerMarker = null;
      }
      if (radiusCircle) {
        radiusCircle.remove();
        radiusCircle = null;
      }

      if (!resolvedCenter || typeof resolvedCenter.latitude !== "number" || typeof resolvedCenter.longitude !== "number") {
        return;
      }

      const latLng = [resolvedCenter.latitude, resolvedCenter.longitude];
      centerMarker = window.L.circleMarker(latLng, {
        radius: 7,
        weight: 2,
        color: "#ff5f45",
        fillColor: "#ff7a5f",
        fillOpacity: 0.8,
      }).addTo(map);

      radiusCircle = window.L.circle(latLng, {
        radius: radiusKm() * 1000,
        color: "#ff7a5f",
        weight: 1.2,
        fillColor: "#ff7a5f",
        fillOpacity: 0.09,
      }).addTo(map);
    };

    const markerRadius = (mag) => {
      if (typeof mag !== "number") return 3.5;
      return Math.max(3.5, Math.min(10, 2.4 + (mag * 0.95)));
    };

    const magnitudeBandClass = (magnitude) => {
      if (!Number.isFinite(magnitude)) return "m-na";
      const bucket = Math.max(1, Math.min(9, Math.floor(magnitude)));
      return `m-b${bucket}`;
    };

    const magnitudeColor = (magnitude) => {
      if (!Number.isFinite(magnitude)) return "#6b7280";
      const bucket = Math.max(1, Math.min(9, Math.floor(magnitude)));
      const palette = {
        1: "#3b82f6",
        2: "#06b6d4",
        3: "#14b8a6",
        4: "#22c55e",
        5: "#eab308",
        6: "#f59e0b",
        7: "#f97316",
        8: "#d946ef",
        9: "#7e22ce",
      };
      return palette[bucket] || "#6b7280";
    };

    const targetZoomForRadius = () => {
      const r = radiusKm();
      if (r <= 30) return 10;
      if (r <= 60) return 9;
      if (r <= 120) return 8;
      if (r <= 220) return 7;
      if (r <= 520) return 6;
      return 5;
    };

    const normalizeSelectedLocation = () => {
      const raw = String(filterLocation?.value || "").trim();
      if (raw === "") return "";
      return raw;
    };

    const selectedSuggestion = () => {
      const key = normalizeSelectedLocation();
      if (key === "") return null;
      return locationGeoMap.get(key) || null;
    };

    const locationRank = (item) => {
      if (!item || typeof item !== "object") return 0;
      const addresstype = String(item.addresstype || item.type || "").toLowerCase();
      const clazz = String(item.class || "").toLowerCase();
      const type = String(item.type || "").toLowerCase();
      const importance = Number(item.importance || 0);
      let score = importance;
      if (clazz === "place") score += 8;
      if (["city", "town", "village", "hamlet", "suburb", "quarter", "municipality"].includes(addresstype)) score += 18;
      if (["country", "state", "region", "province", "county"].includes(addresstype)) score += 10;
      if (["administrative"].includes(clazz) && !["country", "state", "region", "province", "county"].includes(addresstype)) score -= 2;
      if (["industrial", "commercial", "farm", "residential"].includes(type)) score -= 8;
      return score;
    };

    const isSettlementResult = (item) => {
      const kind = String(item?.addresstype || item?.type || "").toLowerCase();
      return ["city", "town", "village", "hamlet", "suburb", "quarter", "municipality"].includes(kind);
    };

    const buildResolvedLocation = (item, fallbackName) => {
      const lat = Number(item?.lat);
      const lon = Number(item?.lon);
      if (!Number.isFinite(lat) || !Number.isFinite(lon)) return null;
      return {
        name: String(item?.display_name || fallbackName || ""),
        latitude: lat,
        longitude: lon,
        source: "geocode",
      };
    };

    const parseCityCountryHint = (location) => {
      const parts = String(location || "")
        .split(",")
        .map((v) => v.trim())
        .filter((v) => v !== "");
      if (parts.length < 2) return null;
      return {
        city: parts[0],
        country: parts[parts.length - 1],
      };
    };

    const renderMapEvents = (rows) => {
      if (!map || !Array.isArray(rows)) return;
      clearMapEvents();

      rows.forEach((row, index) => {
        if (typeof row?.latitude !== "number" || typeof row?.longitude !== "number") {
          return;
        }

        const key = eventKey(row, index);
        const magValue = typeof row.magnitude === "number" ? row.magnitude : NaN;
        const magBand = magnitudeBandClass(magValue);
        const magColor = magnitudeColor(magValue);
        const marker = window.L.circleMarker([row.latitude, row.longitude], {
          radius: markerRadius(row.magnitude),
          color: magColor,
          weight: 1.2,
          fillColor: magColor,
          fillOpacity: 0.78,
        });

        const mag = typeof row.magnitude === "number" ? `M${row.magnitude.toFixed(1)}` : "M?";
        const depth = typeof row.depth_km === "number" ? `${row.depth_km.toFixed(1)} km` : "n/a";
        const place = escapeHtml(row.place || "Unknown location");
        const when = escapeHtml(timeLabel(row.event_time_utc));
        const url = eventDetailUrl(row);

        marker.bindPopup(
          `<strong><span class="mag-value ${magBand}">${mag}</span> ${place}</strong><br/>${when} | ${escapeHtml(i18n.depthLabel)} ${depth}<br/><a href="${url}">${escapeHtml(i18n.openEvent)}</a>`
        );

        marker.on("click", () => {
          selectedRowKey = key;
          highlightRowByKey(key);
        });

        marker.addTo(map);
        eventMarkers.push(marker);
        markerByKey.set(key, marker);
      });

      if (!resolvedCenter && eventMarkers.length > 0) {
        const group = window.L.featureGroup(eventMarkers);
        const bounds = group.getBounds();
        if (bounds.isValid()) {
          map.fitBounds(bounds.pad(0.15), { maxZoom: pendingMapFocus ? 9 : 5 });
          if (pendingMapFocus) {
            pendingMapFocus = false;
          }
        }
      } else if (resolvedCenter) {
        const zoom = targetZoomForRadius();
        if (pendingMapFocus) {
          map.flyTo([resolvedCenter.latitude, resolvedCenter.longitude], zoom, { duration: 0.45 });
          pendingMapFocus = false;
        } else {
          map.setView([resolvedCenter.latitude, resolvedCenter.longitude], zoom);
        }
      } else if (pendingMapFocus && map) {
        map.setView([16, 8], 2);
        pendingMapFocus = false;
      }
    };

    const renderList = (rows) => {
      if (!mapList) return;
      if (!Array.isArray(rows) || rows.length === 0) {
        mapList.innerHTML = `<li class='event-item'>${escapeHtml(i18n.noEventsMatch)}</li>`;
        return;
      }

      const center = resolvedCenter;
      const enriched = rows.map((row, index) => {
        const key = eventKey(row, index);
        const hasCenter = center && typeof center.latitude === "number" && typeof center.longitude === "number";
        const hasCoords = typeof row.latitude === "number" && typeof row.longitude === "number";
        const distance = hasCenter && hasCoords ? haversineKm(center.latitude, center.longitude, row.latitude, row.longitude) : null;
        return { row, index, key, distance };
      });

      mapList.innerHTML = enriched.slice(0, 160).map(({ row, key, distance }) => {
        const magValue = typeof row.magnitude === "number" ? row.magnitude : NaN;
        const magBand = magnitudeBandClass(magValue);
        const mag = typeof row.magnitude === "number" ? `M${row.magnitude.toFixed(1)}` : "M?";
        const depth = typeof row.depth_km === "number" ? `${row.depth_km.toFixed(1)} km` : "n/a";
        const when = timeLabel(row.event_time_utc);
        const place = row.place || "Unknown location";
        const dist = distance !== null ? ` | ${distance.toFixed(0)} km` : "";
        const url = eventDetailUrl(row);

        return `
          <li class="event-item event-item-clickable archive-map-item${selectedRowKey === key ? " is-active" : ""}" data-key="${escapeHtml(key)}" data-url="${escapeHtml(url)}">
            <strong><span class="mag-value ${magBand}">${escapeHtml(mag)}</span> ${escapeHtml(place)}</strong><br />
            <span class="archive-result-meta">${escapeHtml(when)} | ${escapeHtml(i18n.depthLabel)} ${escapeHtml(depth)}${escapeHtml(dist)}</span>
          </li>
        `;
      }).join("");
    };

    const highlightRowByKey = (key) => {
      if (!mapList || !key) return;
      mapList.querySelectorAll(".archive-map-item.is-active").forEach((node) => node.classList.remove("is-active"));
      const target = mapList.querySelector(`.archive-map-item[data-key="${escapeAttrValue(key)}"]`);
      if (target) {
        target.classList.add("is-active");
        target.scrollIntoView({ block: "nearest", behavior: "smooth" });
      }
    };

    const fetchLocationSuggestions = async (query) => {
      const q = String(query || "").trim();
      if (q.length < 2) {
        if (locationList) locationList.innerHTML = "";
        locationGeoMap.clear();
        return;
      }
      const seq = ++locationSuggestSeq;
      const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=12&dedupe=1&addressdetails=1&accept-language=it,en&q=${encodeURIComponent(q)}`;
      try {
        const response = await fetch(url, { headers: { Accept: "application/json" } });
        if (!response.ok || seq !== locationSuggestSeq) return;
        const payload = await response.json();
        if (!Array.isArray(payload) || !locationList) return;
        const ranked = payload
          .filter((item) => item && typeof item === "object")
          .map((item) => ({ item, score: locationRank(item) }))
          .sort((a, b) => b.score - a.score)
          .slice(0, 10)
          .map((entry) => entry.item);
        locationList.innerHTML = "";
        locationGeoMap.clear();
        const seenDisplay = new Set();
        ranked.forEach((item) => {
          const display = String(item.display_name || "").trim();
          const lat = Number(item.lat);
          const lon = Number(item.lon);
          if (display === "" || !Number.isFinite(lat) || !Number.isFinite(lon)) return;
          const key = display.toLowerCase();
          if (seenDisplay.has(key)) return;
          seenDisplay.add(key);
          const option = document.createElement("option");
          option.value = display;
          locationList.appendChild(option);
          locationGeoMap.set(display, {
            name: display,
            latitude: lat,
            longitude: lon,
            source: "geocode-suggest",
            _meta: {
              addresstype: String(item.addresstype || ""),
              class: String(item.class || ""),
              type: String(item.type || ""),
            },
          });
        });
      } catch (error) {
        // Keep UI usable even if geocoder is temporarily unavailable.
      }
    };

    const resolveLocation = async (rawLocation) => {
      const location = String(rawLocation || "").trim();
      if (location === "") return null;
      const fromSuggest = locationGeoMap.get(location);
      if (fromSuggest) return fromSuggest;
      const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=6&dedupe=1&addressdetails=1&accept-language=it,en&q=${encodeURIComponent(location)}`;
      try {
        const response = await fetch(url, { headers: { Accept: "application/json" } });
        if (!response.ok) return null;
        const payload = await response.json();
        const ranked = Array.isArray(payload)
          ? payload
              .filter((item) => item && typeof item === "object")
              .map((item) => ({ item, score: locationRank(item) }))
              .sort((a, b) => b.score - a.score)
              .map((entry) => entry.item)
          : [];
        const first = ranked.length > 0 ? ranked[0] : null;
        if (!first) return null;

        // If first result looks administrative for a city-like query, try a city-focused structured lookup.
        const hint = parseCityCountryHint(location);
        if (hint && !isSettlementResult(first)) {
          const structuredUrl = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=5&dedupe=1&addressdetails=1&accept-language=it,en&city=${encodeURIComponent(hint.city)}&country=${encodeURIComponent(hint.country)}`;
          const structuredRes = await fetch(structuredUrl, { headers: { Accept: "application/json" } });
          if (structuredRes.ok) {
            const structuredPayload = await structuredRes.json();
            const structuredRanked = Array.isArray(structuredPayload)
              ? structuredPayload
                  .filter((item) => item && typeof item === "object")
                  .map((item) => ({ item, score: locationRank(item) + (isSettlementResult(item) ? 25 : 0) }))
                  .sort((a, b) => b.score - a.score)
                  .map((entry) => entry.item)
              : [];
            const structuredBest = structuredRanked.length > 0 ? structuredRanked[0] : null;
            const structuredResolved = buildResolvedLocation(structuredBest, location);
            if (structuredResolved) {
              return {
                ...structuredResolved,
                source: "geocode-structured",
              };
            }
          }
        }

        const resolved = buildResolvedLocation(first, location);
        if (!resolved) return null;
        return {
          ...resolved,
          source: "geocode-direct",
        };
      } catch (error) {
        return null;
      }
    };

    const collectQuery = () => {
      const params = new URLSearchParams();

      const magMin = Number(filterMag?.value || "0");
      const depthBand = filterDepth?.value || "all";
      const windowPreset = String(filterWindow?.value || "30d").trim();
      const location = normalizeSelectedLocation();
      const centerPlace = String(pinnedCenterPlace || location || "").trim();
      const sortMode = String(listSort?.value || "date_desc");

      params.set("page", "1");
      params.set("per_page", String(perPage));
      params.set("min_magnitude", String(magMin));
      params.set("sort_by", sortMode.startsWith("mag_") ? "magnitude" : "date");
      params.set("sort_dir", sortMode.endsWith("_asc") ? "asc" : "desc");
      if (hasCenterIntent()) {
        params.set("radius_km", String(radiusKm()));
      }

      // Location is used as map center, not as hard textual event filter.

      const now = new Date();
      const nowMs = now.getTime();
      const applyWindow = (hours) => {
        const fromIso = new Date(nowMs - (hours * 60 * 60 * 1000)).toISOString();
        params.set("from", fromIso);
        params.set("to", now.toISOString());
      };

      if (windowPreset === "24h") {
        applyWindow(24);
      } else if (windowPreset === "7d") {
        applyWindow(24 * 7);
      } else if (windowPreset === "30d") {
        applyWindow(24 * 30);
      } else if (windowPreset === "90d") {
        applyWindow(24 * 90);
      } else if (windowPreset === "1y") {
        applyWindow(24 * 365);
      } else if (windowPreset === "custom") {
        const from = String(filterFrom?.value || "").trim();
        const to = String(filterTo?.value || "").trim();
        if (from !== "") params.set("from", `${from}T00:00:00Z`);
        if (to !== "") params.set("to", `${to}T23:59:59Z`);
      }

      if (manualCenter && typeof manualCenter.latitude === "number" && typeof manualCenter.longitude === "number") {
        params.set("center_lat", String(manualCenter.latitude));
        params.set("center_lon", String(manualCenter.longitude));
        params.set("center_place", manualCenter.name || "Manual center");
      } else if (centerPlace !== "") {
        params.set("center_place", centerPlace);
      }

      if (depthBand === "shallow") {
        params.set("min_depth_km", "0");
        params.set("max_depth_km", "70");
      } else if (depthBand === "intermediate") {
        params.set("min_depth_km", "70");
        params.set("max_depth_km", "300");
      } else if (depthBand === "deep") {
        params.set("min_depth_km", "300");
      }

      return params;
    };

    const fetchArchiveNow = () => {
      if (debounceTimer) {
        window.clearTimeout(debounceTimer);
        debounceTimer = null;
      }
      fetchArchive();
    };

    const updateKpisAndInsights = (payload, rows) => {
      if (kpiVisible) kpiVisible.textContent = String(Array.isArray(rows) ? rows.length : 0);
      if (kpiVisibleNote) {
        kpiVisibleNote.textContent = i18n.kpiRowsNote;
      }
      if (kpiTotal) kpiTotal.textContent = String(Number(payload.total_count || 0));

      const pageMaxMag = (Array.isArray(rows) ? rows : []).reduce((best, row) => {
        const mag = typeof row.magnitude === "number" ? row.magnitude : best;
        return mag > best ? mag : best;
      }, 0);
      const filteredMaxMag = Number(payload?.filtered_max_magnitude);
      const effectiveMaxMag = Number.isFinite(filteredMaxMag) ? filteredMaxMag : pageMaxMag;
      if (kpiMaxMag) kpiMaxMag.textContent = Number.isFinite(effectiveMaxMag) && effectiveMaxMag > 0 ? `M${effectiveMaxMag.toFixed(1)}` : "--";
      if (kpiLatest) kpiLatest.textContent = rows[0]?.event_time_utc ? timeLabel(rows[0].event_time_utc) : "--";

      if (feedMeta) {
        feedMeta.textContent = interpolate(i18n.feedRowsArchive, { count: payload.total_count || 0 });
      }

      if (insightSummary) {
        const filters = payload.filters_applied || {};
        const localityText = filters.locality
          ? interpolate(i18n.localityAround, { value: filters.locality })
          : i18n.localityAll;
        insightSummary.textContent = interpolate(i18n.summaryEventsFound, {
          count: payload.total_count || 0,
          locality: localityText,
        });
      }

      if (insightDepth) {
        const shallow = (Array.isArray(rows) ? rows : []).filter((row) => classifyDepth(row.depth_km) === "shallow").length;
        const intermediate = (Array.isArray(rows) ? rows : []).filter((row) => classifyDepth(row.depth_km) === "intermediate").length;
        const deep = (Array.isArray(rows) ? rows : []).filter((row) => classifyDepth(row.depth_km) === "deep").length;
        const center = payload.center;
        const radius = payload.filters_applied?.radius_km;
        const centerText = center && center.name ? `${i18n.centerPrefix}: ${center.name}` : `${i18n.centerPrefix}: ${i18n.centerNotSet.toLowerCase()}`;
        const radiusText = typeof radius === "number" ? `${radius} km` : i18n.radiusOff;
        insightDepth.textContent = interpolate(i18n.coverageDepthMix, {
          center: centerText,
          radius: radiusText,
          shallow,
          intermediate,
          deep,
        });
      }

      const providers = Array.isArray(payload.providers) ? payload.providers : [];
      if (insightProviders) {
        insightProviders.innerHTML = providers.length > 0
          ? providers.map((name) => `<span class=\"insight-pill\">${escapeHtml(name)}</span>`).join("")
          : `<span class='insight-pill'>${escapeHtml(i18n.noProvidersSet)}</span>`;
      }

      if (kpiSource) {
        const providerLabel = payload.provider || "Archive API";
        kpiSource.textContent = `${i18n.sourcePrefix}: ${providerLabel}`;
      }
    };

    const fetchArchive = async () => {
      const seq = ++requestSeq;
      if (activeArchiveController) {
        activeArchiveController.abort();
      }
      activeArchiveController = new AbortController();
      const actionReason = pendingFetchReason || i18n.searchingInProgress;
      beginFeedbackProgress(actionReason);
      try {
        const query = collectQuery();
        const firstResponse = await fetch(`/api/earthquakes-archive.php?${query.toString()}`, {
          headers: { Accept: "application/json" },
          signal: activeArchiveController.signal,
        });
        if (!firstResponse.ok) {
          throw new Error("Archive request failed");
        }
        const firstPayload = await firstResponse.json();
        if (seq !== requestSeq) return;
        if (!firstPayload || typeof firstPayload !== "object") return;

        if (firstPayload.center && typeof firstPayload.center.latitude === "number" && typeof firstPayload.center.longitude === "number") {
          resolvedCenter = firstPayload.center;
        } else if (manualCenter) {
          resolvedCenter = manualCenter;
        } else {
          resolvedCenter = null;
        }

        const totalCountRaw = Number(firstPayload.total_count);
        const expectedTotal = Number.isFinite(totalCountRaw) && totalCountRaw >= 0 ? Math.floor(totalCountRaw) : null;
        const totalPagesRaw = Number(firstPayload.total_pages);
        const pageCount = Number.isFinite(totalPagesRaw) && totalPagesRaw >= 1 ? Math.floor(totalPagesRaw) : 1;
        const providerSet = new Set(Array.isArray(firstPayload.providers) ? firstPayload.providers : []);
        let rows = Array.isArray(firstPayload.events) ? firstPayload.events.slice() : [];

        setCenterStatus(resolvedCenter);
        const typedCenter = String(pinnedCenterPlace || normalizeSelectedLocation()).trim();
        if (mapMeta) {
          mapMeta.textContent = resolvedCenter
            ? interpolate(i18n.centerRadius, {
                name: resolvedCenter.name || i18n.centerSelected,
                radius: `${radiusKm()} km`,
              })
            : (typedCenter !== "" ? i18n.centerPlaceNotFound : i18n.clickMapCenter);
        }

        ensureMap();
        drawCenterGeometry();
        const renderProgress = (done) => {
          currentVisibleRows = rows;
          renderMapEvents(rows);
          renderList(currentVisibleRows);
          const payloadForUi = {
            ...firstPayload,
            providers: Array.from(providerSet),
            total_count: expectedTotal ?? rows.length,
            events_count: rows.length,
          };
          updateKpisAndInsights(payloadForUi, rows);
          if (kpiVisibleNote) {
            const totalLabel = expectedTotal !== null ? expectedTotal : rows.length;
            kpiVisibleNote.textContent = done
              ? interpolate(i18n.loadedRowsProgress, { rows: rows.length, total: totalLabel })
              : interpolate(i18n.loadingRowsProgress, { rows: rows.length, total: totalLabel });
          }
          if (feedMeta) {
            const totalLabel = expectedTotal !== null ? expectedTotal : rows.length;
            feedMeta.textContent = done
              ? interpolate(i18n.feedRowsArchive, { count: rows.length })
              : interpolate(i18n.loadingRowsArchive, { rows: rows.length, total: totalLabel });
          }
        };

        renderProgress(pageCount <= 1);

        for (let page = 2; page <= pageCount; page += 1) {
          const pageQuery = new URLSearchParams(query.toString());
          pageQuery.set("page", String(page));
          const response = await fetch(`/api/earthquakes-archive.php?${pageQuery.toString()}`, {
            headers: { Accept: "application/json" },
            signal: activeArchiveController.signal,
          });
          if (!response.ok) {
            throw new Error("Archive paginated request failed");
          }
          const payload = await response.json();
          if (seq !== requestSeq) return;
          const pageRows = Array.isArray(payload?.events) ? payload.events : [];
          if (pageRows.length > 0) {
            rows = rows.concat(pageRows);
          }
          if (Array.isArray(payload?.providers)) {
            payload.providers.forEach((name) => {
              if (typeof name === "string" && name.trim() !== "") {
                providerSet.add(name);
              }
            });
          }
          renderProgress(page >= pageCount);
        }

        finishFeedbackProgress(true, `${actionReason} ${i18n.searchDone}`);
        setActionButtonsBusy(false);
        pendingFetchReason = i18n.searchingInProgress;
        if (activeArchiveController && activeArchiveController.signal.aborted === false) {
          activeArchiveController = null;
        }
      } catch (error) {
        if (seq !== requestSeq) return;
        if (error instanceof DOMException && error.name === "AbortError") {
          return;
        }
        setError();
        finishFeedbackProgress(false, i18n.searchFailed);
        setActionButtonsBusy(false);
        pendingFetchReason = i18n.searchingInProgress;
        activeArchiveController = null;
      }
    };

    const runSearch = async () => {
      const location = normalizeSelectedLocation();
      pendingFetchReason = i18n.searchingInProgress;
      setActionButtonsBusy(true, i18n.searching);
      beginFeedbackProgress(i18n.preparingSearch);

      if (location !== "") {
        let resolved = selectedSuggestion();
        if (!resolved) {
          resolved = await resolveLocation(location);
        } else if (resolved && resolved._meta && !isSettlementResult(resolved._meta)) {
          const refined = await resolveLocation(location);
          if (refined) {
            resolved = refined;
          }
        }
        if (!resolved) {
          setActionButtonsBusy(false);
          finishFeedbackProgress(false, i18n.locationNotFound);
          if (mapMeta) mapMeta.textContent = i18n.locationNotFound;
          return;
        }
        manualCenter = resolved;
        resolvedCenter = resolved;
        pinnedCenterPlace = resolved.name || location;
        pendingMapFocus = true;
        ensureMap();
        drawCenterGeometry();
        setCenterStatus(resolvedCenter);
        if (mapMeta) {
          mapMeta.textContent = interpolate(i18n.centerRadius, {
            name: resolvedCenter.name || i18n.centerSelected,
            radius: `${radiusKm()} km`,
          });
        }
        if (map && typeof resolvedCenter.latitude === "number" && typeof resolvedCenter.longitude === "number") {
          map.flyTo([resolvedCenter.latitude, resolvedCenter.longitude], targetZoomForRadius(), { duration: 0.35 });
          pendingMapFocus = false;
        }
      } else {
        manualCenter = null;
        resolvedCenter = null;
        pinnedCenterPlace = "";
        pendingMapFocus = false;
      }

      fetchArchiveNow();
      const mapSection = document.querySelector(".archive-map-layout");
      if (mapSection instanceof HTMLElement) {
        mapSection.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    };

    const activateCenterFromLocationInput = async () => {
      const location = normalizeSelectedLocation();
      if (location === "") {
        manualCenter = null;
        resolvedCenter = null;
        pinnedCenterPlace = "";
        pendingMapFocus = false;
        drawCenterGeometry();
        setCenterStatus(resolvedCenter);
        return;
      }

      let resolved = selectedSuggestion();
      if (!resolved) {
        resolved = await resolveLocation(location);
      }
      if (!resolved) {
        manualCenter = null;
        resolvedCenter = null;
        pinnedCenterPlace = "";
        pendingMapFocus = false;
        drawCenterGeometry();
        setCenterStatus(resolvedCenter);
        return;
      }

      manualCenter = resolved;
      resolvedCenter = resolved;
      pinnedCenterPlace = resolved.name || location;
      ensureMap();
      drawCenterGeometry();
      setCenterStatus(resolvedCenter);
      if (mapMeta) {
        mapMeta.textContent = `${interpolate(i18n.centerRadius, {
          name: resolvedCenter.name || i18n.centerSelected,
          radius: `${radiusKm()} km`,
        })} · ${i18n.centerReady}`;
      }
      if (map && typeof resolvedCenter.latitude === "number" && typeof resolvedCenter.longitude === "number") {
        map.flyTo([resolvedCenter.latitude, resolvedCenter.longitude], targetZoomForRadius(), { duration: 0.35 });
      }
    };

    const bindFilters = () => {
      filterLocation?.addEventListener("input", () => {
        manualCenter = null;
        pinnedCenterPlace = "";
        pendingMapFocus = false;
        if (locationSuggestTimer) window.clearTimeout(locationSuggestTimer);
        const q = String(filterLocation?.value || "");
        locationSuggestTimer = window.setTimeout(() => {
          fetchLocationSuggestions(q);
        }, 180);
      });
      filterLocation?.addEventListener("change", () => {
        activateCenterFromLocationInput();
      });
      filterLocation?.addEventListener("keydown", (event) => {
        if (!(event instanceof KeyboardEvent)) return;
        if (event.key === "Enter") {
          event.preventDefault();
          runSearch();
        }
      });
      filterLocation?.addEventListener("blur", () => {
        // Keep user text; center activation is handled on explicit selection/change.
      });

      filterWindow?.addEventListener("change", () => {
        const selected = String(filterWindow?.value || "");
        if (selected === "custom") {
          openCustomRangeDialog();
          return;
        }
        lastNonCustomWindow = selected || "30d";
      });

      customRangeBtn?.addEventListener("click", () => {
        if (filterWindow) filterWindow.value = "custom";
        openCustomRangeDialog();
      });

      searchBtn?.addEventListener("click", () => {
        runSearch();
      });

      [filterFrom, filterTo].forEach((el) => el?.addEventListener("change", () => {
        if (!filterFrom || !filterTo) return;
        const from = String(filterFrom.value || "").trim();
        const to = String(filterTo.value || "").trim();
        const fromDate = parseIsoDate(from);
        const toDate = parseIsoDate(to);
        if (fromDate && toDate && from > to) {
          filterTo.value = from;
        }
      }));

      customRangeCancel?.addEventListener("click", () => {
        if (customRangeDialog && typeof customRangeDialog.close === "function") {
          customRangeDialog.close();
        }
        if (!filterFrom?.value || !filterTo?.value) {
          if (filterWindow) filterWindow.value = lastNonCustomWindow;
        }
      });

      customRangeDialog?.addEventListener("close", () => {
        if ((filterWindow?.value || "") !== "custom") return;
        if (!filterFrom?.value || !filterTo?.value) {
          if (filterWindow) filterWindow.value = lastNonCustomWindow;
        }
      });

      customRangeApply?.addEventListener("click", () => {
        const from = String(filterFrom?.value || "").trim();
        const to = String(filterTo?.value || "").trim();
        if (from === "" || to === "") {
          return;
        }
        if (from > to) {
          return;
        }
        if (filterWindow) filterWindow.value = "custom";
        formatDateRangeLabel();
        if (customRangeDialog && typeof customRangeDialog.close === "function") {
          customRangeDialog.close();
        }
      });

      filterRadius?.addEventListener("input", () => {
        setRadiusLabel();
        drawCenterGeometry();
      });

      resetFilters?.addEventListener("click", () => {
      pendingFetchReason = i18n.resetFiltersAction;
      setActionButtonsBusy(true, i18n.searching);
      beginFeedbackProgress(i18n.resetFiltersAction);
        if (filterWindow) filterWindow.value = "30d";
        if (filterFrom) filterFrom.value = "";
        if (filterTo) filterTo.value = "";
        if (filterLocation) filterLocation.value = "";
        if (filterMag) filterMag.value = "0";
        if (filterDepth) filterDepth.value = "all";
        if (filterRadius) filterRadius.value = "120";
        manualCenter = null;
        pinnedCenterPlace = "";
        resolvedCenter = null;
        pendingMapFocus = false;
        lastNonCustomWindow = "30d";
        setRadiusLabel();
        formatDateRangeLabel();
        drawCenterGeometry();
        syncRadiusControl();
        fetchArchiveNow();
      });

      mapList?.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        const row = target.closest(".archive-map-item");
        if (!(row instanceof HTMLElement)) return;

        const key = row.dataset.key || "";
        const url = row.dataset.url || "";
        selectedRowKey = key;
        highlightRowByKey(key);

        const marker = markerByKey.get(key);
        if (marker && map) {
          const latLng = marker.getLatLng();
          map.setView(latLng, Math.max(map.getZoom(), 5));
          marker.openPopup();
        }

        if (url !== "") {
          window.location.href = url;
        }
      });

      listSort?.addEventListener("change", () => {
        pendingFetchReason = "Riordino risultati";
        fetchArchiveNow();
      });
    };

    const setError = () => {
      if (mapList) {
        mapList.innerHTML = `<li class='event-item'>${escapeHtml(i18n.archiveUnavailable)}</li>`;
      }
      if (feedMeta) feedMeta.textContent = i18n.archiveUnavailable;
      if (kpiSource) kpiSource.textContent = i18n.sourceUnavailable;
      if (insightSummary) insightSummary.textContent = i18n.summaryUnavailable;
      if (insightDepth) insightDepth.textContent = i18n.depthUnavailable;
      if (insightProviders) insightProviders.innerHTML = `<span class='insight-pill'>${escapeHtml(i18n.providerMixUnavailable)}</span>`;
    };

    setRadiusLabel();
    formatDateRangeLabel();
    ensureMap();
    bindFilters();
    syncRadiusControl();
    fetchArchiveNow();
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
