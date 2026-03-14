const feedMeta = document.querySelector("#feed-meta");
const eventsList = document.querySelector("#events-list");
const mapLeafletContainer = document.querySelector("#world-map-leaflet");
const mapGraticule = document.querySelector("#map-graticule");
const mapContinents = document.querySelector("#map-continents");
const mapPoints = document.querySelector("#map-points");
const magChart = document.querySelector("#mag-chart");
const hourlyChart = document.querySelector("#hourly-chart");
const regionsList = document.querySelector("#regions-list");
const timelineList = document.querySelector("#timeline-list");
const timelineMoreButton = document.querySelector("#timeline-more");
const barTemplate = document.querySelector("#bar-template");

const kpiTotal = document.querySelector("#kpi-total");
const kpiStrongest = document.querySelector("#kpi-strongest");
const kpiStrongestPlace = document.querySelector("#kpi-strongest-place");
const kpiSignificant = document.querySelector("#kpi-significant");
const kpiUpdated = document.querySelector("#kpi-updated");
const kpiSource = document.querySelector("#kpi-source");
const homeSnapshot = document.querySelector("#home-snapshot");
const homeKpiTotal = document.querySelector("#home-kpi-total");
const homeKpiStrongest = document.querySelector("#home-kpi-strongest");
const homeKpiStrongestPlace = document.querySelector("#home-kpi-strongest-place");
const homeKpiSignificant = document.querySelector("#home-kpi-significant");
const homeKpiUpdated = document.querySelector("#home-kpi-updated");
const homeKpiSource = document.querySelector("#home-kpi-source");
const homeLaunch = document.querySelector("#launch");
const homeSnapshotBrief = document.querySelector("#home-snapshot-brief");
const homeSourcesLine = document.querySelector("#home-sources-line");
const homeClustersList = document.querySelector("#home-clusters-list");
const homeModuleEarthquakesList = document.querySelector("#home-module-earthquakes-list");
const homeVolcanoList = document.querySelector("#home-volcano-list");
const homeModuleTsunamiList = document.querySelector("#home-module-tsunami-list");
const homeModuleSpaceList = document.querySelector("#home-module-space-list");
const homeSignificantList = document.querySelector("#home-significant-list");
const homeSignificantHeadNote = document.querySelector("#home-significant-head-note");
const homePriorityMode = document.querySelector("#home-priority-mode");
const homePriorityBoardCards = document.querySelector("#home-priority-board-cards");
const homePrioritySupport = document.querySelector("#home-priority-support");
const homeMapFeedList = document.querySelector("#home-map-feed-list");
const homeMapViewportOnlyToggle = document.querySelector("#home-map-viewport-only");
const homeMapFeedTitle = document.querySelector("#home-map-feed-title");
const homeContextTitle = document.querySelector("#home-context-title");
const homeContextMode = document.querySelector("#home-context-mode");
const homePriorityNow = document.querySelector("#home-priority-now");
const homeContextSummary = document.querySelector("#home-context-summary");
const homeContextRegion = document.querySelector("#home-context-region");
const homeContextWindow = document.querySelector("#home-context-window");
const homeContextPressure = document.querySelector("#home-context-pressure");
const homeContextProbability = document.querySelector("#home-context-probability");
const homeContextEqTitle = document.querySelector("#home-context-eq-title");
const homeContextEqList = document.querySelector("#home-context-eq-list");
const homeContextVisualTitle = document.querySelector("#home-context-visual-title");
const homeContextVisualMeta = document.querySelector("#home-context-visual-meta");
const homeAiTech = document.querySelector("#home-ai-tech");
const homeAiText = document.querySelector("#home-ai-text");
const homePanelClusters = document.querySelector("#home-panel-clusters");
const homePanelVolcano = document.querySelector("#home-panel-volcano");
const homeStatusVolcanoes = document.querySelector("#home-status-volcanoes");
const homeStatusVolcanoNote = document.querySelector("#home-status-volcano-note");
const homeStatusTsunami = document.querySelector("#home-status-tsunami");
const homeStatusTsunamiNote = document.querySelector("#home-status-tsunami-note");
const homeStatusKp = document.querySelector("#home-status-kp");
const homeStatusSpaceNote = document.querySelector("#home-status-space-note");
const homeStatusTremorClusters = document.querySelector("#home-status-tremor-clusters");
const homeStatusTremorNote = document.querySelector("#home-status-tremor-note");
const homeMirrorNodes = document.querySelectorAll("[data-home-mirror]");
const footerUpdateInterval = document.querySelector("#footer-update-interval");
const footerDataLatency = document.querySelector("#footer-data-latency");
const mapFilterButtons = document.querySelectorAll(".map-filter-btn");
const globalThemeToggle = document.querySelector("#global-theme-toggle");
const eventInsightPanel = document.querySelector("#event-insight-panel");
const eventInsightTitle = document.querySelector("#event-insight-title");
const eventInsightSummary = document.querySelector("#event-insight-summary");
const eventInsightMapContainer = document.querySelector("#event-insight-map-leaflet");
const eventInsightRegime = document.querySelector("#event-insight-regime");
const eventInsightPlate = document.querySelector("#event-insight-plate");
const eventInsightFault = document.querySelector("#event-insight-fault");
const eventInsightSlip = document.querySelector("#event-insight-slip");
const eventInsightNearbyList = document.querySelector("#event-insight-nearby-list");
const earthquakesMainLayout = document.querySelector(".earthquakes-main-layout");
const earthquakesMapCard = earthquakesMainLayout ? earthquakesMainLayout.querySelector(".map-card") : null;
const earthquakesSideCard = earthquakesMainLayout ? earthquakesMainLayout.querySelector(".side-card") : null;

const homePulseState = {
  quakeBrief: null,
  volcanoBrief: null,
  tremorBrief: null,
};

const homeHazardsState = {
  volcanoReports: null,
  volcanoes: null,
  newEruptive: null,
  latestVolcano: null,
  latestCountry: null,
  latestVolcanoStatus: null,
  latestVolcanoTime: null,
  volcanoEvents: [],
  topVolcanoCountries: [],
  newEruptiveVolcanoes: [],
  tremorSignals: null,
  tremorClusters: null,
  tremorPeakHour: null,
  tremorPeakCount: null,
  tremorTopClusters: [],
  tsunamiAlerts: null,
  tsunamiLevel: null,
  tsunamiRegions: null,
  tsunamiPayload: null,
  spaceKp: null,
  spaceStormLevel: null,
  spacePayload: null,
};

let timelineExpanded = false;
let timelineEventsCache = [];
let leafletMap = null;
let leafletMarkers = [];
let leafletPulseLayers = [];
let leafletPulseTimers = [];
let leafletPulseRunToken = 0;
let leafletLightTiles = null;
let leafletDarkTiles = null;
let leafletNightLightsTiles = null;
let leafletDarkMode = true;
const mapEventLookup = new Map();
const eventLookupByKey = new Map();
let activeMagnitudeBand = null;
let latestEarthquakePayload = null;
let latestHomeSituationContext = null;
let latestHomeLiveMode = "normal-watch";
let allEarthquakeEvents = [];
let previousPayloadEventKeys = new Set();
let hasHydratedPayloadKeys = false;
let pendingLeafletPulseEvents = [];
let selectedEventKey = null;
let homeMapViewportOnly = false;
let leafletResizeRaf = 0;
let eventInsightMap = null;
let eventInsightEventLayer = null;
let eventInsightStrongLayer = null;
let eventInsightPlateLayer = null;
let eventInsightFaultLayer = null;
let eventInsightLightTiles = null;
let eventInsightDarkTiles = null;
let eventInsightNightLightsTiles = null;
const tectonicContextCache = {
  plates: null,
  faults: null,
};
const bootstrapPayloads =
  typeof window !== "undefined" && window.__QUAKRS_BOOTSTRAP && typeof window.__QUAKRS_BOOTSTRAP === "object"
    ? window.__QUAKRS_BOOTSTRAP
    : null;
const currentPath = typeof window !== "undefined" ? window.location.pathname.replace(/\/+$/, "") : "";
const isMapsPage = currentPath === "/maps.php" || currentPath === "/maps";
const isEarthquakesPage = currentPath === "/earthquakes.php" || currentPath === "/earthquakes";
const isHomePage = currentPath === "" || currentPath === "/home.php" || currentPath === "/home";
const FORCE_LIVE_FEEDS = false;
const SKIP_BOOTSTRAP_PAYLOADS = FORCE_LIVE_FEEDS && isHomePage;
const siteLocale =
  typeof document !== "undefined" && document.documentElement && document.documentElement.lang
    ? document.documentElement.lang.toLowerCase().startsWith("it")
      ? "it"
      : "en"
    : "en";
const homeI18n = {
  en: {
    mode_regional_focus: "Regional focus",
    mode_global_watch: "Global watch",
    title_under_watch: "{region} under watch",
    title_global_situation: "Global situation",
    summary_regional:
      "{focusCount} events in the last {lookbackHours}h across {regionLabel}, peaking at {focusStrongest}. Current activity is elevated versus the global baseline.",
    summary_global:
      "Distributed activity across {workingSetSize} recent events, with no single dominant area right now.",
    label_area: "Area: {regionLabel}",
    label_window: "Window: last {lookbackHours}h",
    label_intensity: "Intensity: {pressure}",
    label_activity_index: "Activity index: {probability}/100",
    map_feed_priority_area: "Priority area feed",
    map_feed_earthquakes: "Earthquake feed",
    significant_now: "Featured now",
    significant_live: "Live",
    visual_title_distributed: "Distributed activity",
    visual_meta_regional: "{focusCount} events in {lookbackHours}h · peak {focusStrongest}",
    visual_meta_global: "{workingSetSize} events in analyzed window · peak {strongestMagnitude}",
    ai_text_regional: "This event belongs to the {regionLabel} cluster, currently concentrating {focusCount} events.",
    ai_text_global: "No dominant cluster right now: activity is distributed across multiple areas.",
    eq_highlighted: "Highlighted earthquakes",
    eq_highlighted_region: "Highlighted earthquakes - {regionLabel}",
    no_event_available: "No event available.",
    no_update_available: "No update available.",
    night_mode_disable: "Disable night mode",
    night_mode_enable: "Enable night mode",
  },
  it: {
    mode_regional_focus: "Focus regionale",
    mode_global_watch: "Monitoraggio globale",
    title_under_watch: "{region} sotto osservazione",
    title_global_situation: "Situazione globale",
    summary_regional:
      "{focusCount} eventi nelle ultime {lookbackHours}h in area {regionLabel}, con picco {focusStrongest}. Attività attuale elevata rispetto al quadro globale.",
    summary_global:
      "Attività distribuita su {workingSetSize} eventi recenti, senza una singola area dominante in questo momento.",
    label_area: "Area: {regionLabel}",
    label_window: "Finestra: ultime {lookbackHours}h",
    label_intensity: "Intensità: {pressure}",
    label_activity_index: "Indice attività: {probability}/100",
    map_feed_priority_area: "Feed area prioritaria",
    map_feed_earthquakes: "Feed terremoti",
    significant_now: "In evidenza ora",
    significant_live: "Live",
    visual_title_distributed: "Attività distribuita",
    visual_meta_regional: "{focusCount} eventi in {lookbackHours}h · picco {focusStrongest}",
    visual_meta_global: "{workingSetSize} eventi in finestra analizzata · picco {strongestMagnitude}",
    ai_text_regional: "Questo evento rientra nel cluster {regionLabel}, che in questo momento concentra {focusCount} eventi.",
    ai_text_global: "Nessun cluster dominante al momento: l'attività è distribuita su più aree.",
    eq_highlighted: "Terremoti in evidenza",
    eq_highlighted_region: "Terremoti in evidenza - {regionLabel}",
    no_event_available: "Nessun evento disponibile.",
    no_update_available: "Nessun aggiornamento disponibile.",
    night_mode_disable: "Disattiva modalità notturna",
    night_mode_enable: "Attiva modalità notturna",
  },
};

function tHome(key, vars = {}) {
  const template = (homeI18n[siteLocale] && homeI18n[siteLocale][key]) || homeI18n.en[key] || key;
  return String(template).replace(/\{([a-zA-Z0-9_]+)\}/g, (_, token) =>
    Object.prototype.hasOwnProperty.call(vars, token) ? String(vars[token]) : ""
  );
}

function buildApiUrl(path, forceRefresh = false) {
  if (typeof window === "undefined") {
    return path;
  }
  const url = new URL(path, window.location.origin);
  if (forceRefresh) {
    url.searchParams.set("force_refresh", "1");
    url.searchParams.set("t", String(Date.now()));
  }
  return url.toString();
}

function fetchApiJson(path, forceRefresh = false) {
  return fetch(buildApiUrl(path, forceRefresh), {
    headers: { Accept: "application/json" },
    cache: "no-store",
  });
}

function initMobileNavDropdowns() {
  const navGroups = Array.from(document.querySelectorAll(".nav-group"));
  if (navGroups.length === 0) {
    return;
  }

  const navTriggers = navGroups
    .map((group) => group.querySelector(".nav-group-trigger"))
    .filter((trigger) => trigger instanceof HTMLButtonElement);

  const mobileMq = window.matchMedia("(max-width: 760px)");
  const closeAll = () => {
    navGroups.forEach((group) => group.classList.remove("is-open"));
  };

  navTriggers.forEach((trigger) => {
    trigger.addEventListener("click", () => {
      if (!mobileMq.matches) {
        return;
      }

      const group = trigger.closest(".nav-group");
      if (!group) {
        return;
      }

      const shouldOpen = !group.classList.contains("is-open");
      closeAll();
      if (shouldOpen) {
        group.classList.add("is-open");
      }
    });
  });

  document.addEventListener("click", (event) => {
    if (!mobileMq.matches) {
      return;
    }
    const target = event.target;
    if (!(target instanceof Element)) {
      return;
    }
    if (target.closest(".main-nav")) {
      return;
    }
    closeAll();
  });

  mobileMq.addEventListener("change", () => {
    closeAll();
  });
}

function initMobileNavToggle() {
  const topbar = document.querySelector(".topbar");
  const nav = document.querySelector("#main-nav");
  const toggle = document.querySelector("#mobile-nav-toggle");

  if (!(topbar instanceof HTMLElement) || !(nav instanceof HTMLElement) || !(toggle instanceof HTMLButtonElement)) {
    return;
  }

  const mobileMq = window.matchMedia("(max-width: 760px)");
  const labelOpen = toggle.dataset.labelOpen || "Menu";
  const labelClose = toggle.dataset.labelClose || "Close";
  const ariaOpen = toggle.dataset.ariaOpen || "Open navigation menu";
  const ariaClose = toggle.dataset.ariaClose || "Close navigation menu";

  const closeMenu = () => {
    topbar.classList.remove("is-nav-open");
    toggle.setAttribute("aria-expanded", "false");
    toggle.setAttribute("aria-label", ariaOpen);
    toggle.textContent = labelOpen;
  };

  const openMenu = () => {
    topbar.classList.add("is-nav-open");
    toggle.setAttribute("aria-expanded", "true");
    toggle.setAttribute("aria-label", ariaClose);
    toggle.textContent = labelClose;
  };

  toggle.addEventListener("click", () => {
    if (!mobileMq.matches) {
      return;
    }

    const isOpen = topbar.classList.contains("is-nav-open");
    if (isOpen) {
      closeMenu();
      return;
    }

    openMenu();
  });

  document.addEventListener("click", (event) => {
    if (!mobileMq.matches || !topbar.classList.contains("is-nav-open")) {
      return;
    }

    const target = event.target;
    if (!(target instanceof Element)) {
      return;
    }
    if (target.closest(".topbar")) {
      return;
    }
    closeMenu();
  });

  nav.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => {
      if (mobileMq.matches) {
        closeMenu();
      }
    });
  });

  mobileMq.addEventListener("change", (event) => {
    if (!event.matches) {
      closeMenu();
    }
  });
}

function updateHomeBrief() {
  if (!homeSnapshotBrief) {
    return;
  }

  const parts = [homePulseState.quakeBrief, homePulseState.volcanoBrief, homePulseState.tremorBrief].filter(Boolean);
  if (parts.length === 0) {
    homeSnapshotBrief.textContent = "Loading global activity summary...";
    return;
  }

  homeSnapshotBrief.textContent = parts.join(" ");
}

function inferHomeSituationContext(events) {
  const now = Date.now();
  const lookbackMs = 6 * 60 * 60 * 1000;
  const recentRows = events.filter((event) => {
    if (!event?.event_time_utc) {
      return false;
    }
    return new Date(event.event_time_utc).getTime() >= now - lookbackMs;
  });
  const workingSet = recentRows.length > 0 ? recentRows : events.slice(0, 48);
  const regionStats = new Map();
  let strongestMagnitude = 0;

  workingSet.forEach((event) => {
    const region = parseRegion(event.place);
    const magnitude = typeof event.magnitude === "number" ? event.magnitude : 0;
    const timestamp = event?.event_time_utc ? new Date(event.event_time_utc).getTime() : 0;
    strongestMagnitude = Math.max(strongestMagnitude, magnitude);
    const current = regionStats.get(region) || { count: 0, strongest: 0, latest: 0 };
    current.count += 1;
    current.strongest = Math.max(current.strongest, magnitude);
    current.latest = Math.max(current.latest, timestamp);
    regionStats.set(region, current);
  });

  const rankedRegions = [...regionStats.entries()].sort((a, b) => {
    const countDiff = b[1].count - a[1].count;
    if (countDiff !== 0) {
      return countDiff;
    }
    const strongDiff = b[1].strongest - a[1].strongest;
    if (strongDiff !== 0) {
      return strongDiff;
    }
    return b[1].latest - a[1].latest;
  });

  const focusRegion = rankedRegions[0]?.[0] || "Global";
  const focusRegionLabel = formatRegionLabel(focusRegion);
  const focusStats = rankedRegions[0]?.[1] || { count: 0, strongest: 0, latest: 0 };
  const focusShare = workingSet.length > 0 ? focusStats.count / workingSet.length : 0;
  const hasRegionalFocus =
    (focusStats.count >= 6 && focusShare >= 0.24) || focusStats.strongest >= 6.2;

  let pressure = "baseline";
  if (focusStats.count >= 10 || focusStats.strongest >= 6.5) {
    pressure = "high";
  } else if (focusStats.count >= 5 || focusStats.strongest >= 5.6) {
    pressure = "elevated";
  }

  const probabilityRaw = 18 + focusStats.count * 4 + Math.max(0, focusStats.strongest - 4.5) * 10;
  const probability = Math.max(15, Math.min(92, Math.round(probabilityRaw)));

  return {
    mode: hasRegionalFocus ? "regional-focus" : "global-watch",
    modeLabel: hasRegionalFocus ? tHome("mode_regional_focus") : tHome("mode_global_watch"),
    title: hasRegionalFocus
      ? tHome("title_under_watch", { region: focusRegionLabel })
      : tHome("title_global_situation"),
    region: focusRegion,
    regionLabel: focusRegionLabel,
    pressure,
    probability,
    focusCount: focusStats.count,
    focusStrongest: focusStats.strongest,
    lookbackHours: 6,
    workingSetSize: workingSet.length,
    strongestMagnitude,
  };
}

function renderHomeSituationContext(context) {
  if (homeLaunch) {
    homeLaunch.setAttribute("data-context-layout", context.mode);
    homeLaunch.setAttribute("data-context-pressure", context.pressure);
  }
  if (homeContextMode) {
    homeContextMode.textContent = context.modeLabel;
  }
  if (homeContextTitle) {
    homeContextTitle.textContent = context.title;
  }
  if (homeContextSummary) {
    if (context.mode === "regional-focus") {
      homeContextSummary.textContent = tHome("summary_regional", {
        focusCount: context.focusCount,
        lookbackHours: context.lookbackHours,
        regionLabel: context.regionLabel,
        focusStrongest: formatMagnitude(context.focusStrongest),
      });
    } else {
      homeContextSummary.textContent = tHome("summary_global", {
        workingSetSize: context.workingSetSize,
      });
    }
  }
  if (homeContextRegion) {
    homeContextRegion.textContent = tHome("label_area", { regionLabel: context.regionLabel });
  }
  if (homeContextWindow) {
    homeContextWindow.textContent = tHome("label_window", { lookbackHours: context.lookbackHours });
  }
  if (homeContextPressure) {
    homeContextPressure.textContent = tHome("label_intensity", { pressure: context.pressure });
  }
  if (homeContextProbability) {
    homeContextProbability.textContent = tHome("label_activity_index", { probability: context.probability });
  }
  if (homeMapFeedTitle) {
    homeMapFeedTitle.textContent =
      context.mode === "regional-focus" ? tHome("map_feed_priority_area") : tHome("map_feed_earthquakes");
  }
  if (homeSignificantHeadNote) {
    homeSignificantHeadNote.textContent =
      context.mode === "regional-focus" ? tHome("significant_now") : tHome("significant_live");
  }
  if (homeContextVisualTitle) {
    homeContextVisualTitle.textContent =
      context.mode === "regional-focus" ? context.regionLabel : tHome("visual_title_distributed");
  }
  if (homeContextVisualMeta) {
    if (context.mode === "regional-focus") {
      homeContextVisualMeta.textContent = tHome("visual_meta_regional", {
        focusCount: context.focusCount,
        lookbackHours: context.lookbackHours,
        focusStrongest: formatMagnitude(context.focusStrongest),
      });
    } else {
      homeContextVisualMeta.textContent = tHome("visual_meta_global", {
        workingSetSize: context.workingSetSize,
        strongestMagnitude: formatMagnitude(context.strongestMagnitude),
      });
    }
  }
}

function renderClustersList() {
  if (!homeClustersList) {
    return;
  }

  const rows = [];
  rows.push({
    kind: "summary",
    label: "Tremor overview",
    value: homeHazardsState.tremorSignals !== null ? `${homeHazardsState.tremorSignals} signals` : "--",
    meta:
      homeHazardsState.tremorClusters !== null && homeHazardsState.tremorPeakCount !== null
        ? `${homeHazardsState.tremorClusters} active clusters, peak ${homeHazardsState.tremorPeakHour || "--:00"} UTC (${homeHazardsState.tremorPeakCount} signals)`
        : "Loading tremor feed",
    href: "/data-clusters.php",
  });

  const clusterRows = Array.isArray(homeHazardsState.tremorTopClusters)
    ? homeHazardsState.tremorTopClusters.slice(0, 3).map((cluster) => ({
        kind: "cluster",
        label: cluster.region || "Unknown cluster",
        value: `${cluster.count ?? 0} signals`,
        meta: `Max ${typeof cluster.max_magnitude === "number" ? formatMagnitude(cluster.max_magnitude) : "M?"}`,
        href: "/data-clusters.php",
      }))
    : [];

  const finalRows = clusterRows.length > 0 ? [...rows, ...clusterRows] : rows;

  homeClustersList.innerHTML = finalRows
    .map(
      (row) => `
        <li class="snapshot-row ${row.kind === "cluster" ? "is-cluster" : "is-summary"}">
          <a class="snapshot-row-anchor" href="${row.href}">
            <div class="snapshot-main">
              <strong>${row.label}</strong>
              <span>${row.value}</span>
            </div>
            <div class="snapshot-meta">${row.meta}</div>
          </a>
        </li>
      `,
    )
    .join("");
}

function renderVolcanoList() {
  if (!homeVolcanoList) {
    return;
  }

  const latestLabel = homeHazardsState.latestVolcano && homeHazardsState.latestCountry
    ? `${homeHazardsState.latestVolcano}, ${homeHazardsState.latestCountry}`
    : homeHazardsState.latestVolcano || "No recent volcano item";
  const latestTime = homeHazardsState.latestVolcanoTime
    ? `${homeHazardsState.latestVolcanoTime.toLocaleDateString(undefined, { day: "2-digit", month: "short" })} · ${homeHazardsState.latestVolcanoTime.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })} UTC`
    : "Time unavailable";
  const topZones = Array.isArray(homeHazardsState.topVolcanoCountries) && homeHazardsState.topVolcanoCountries.length > 0
    ? homeHazardsState.topVolcanoCountries.join(" · ")
    : "No dominant zone";
  const newEruptiveList = Array.isArray(homeHazardsState.newEruptiveVolcanoes) && homeHazardsState.newEruptiveVolcanoes.length > 0
    ? homeHazardsState.newEruptiveVolcanoes.join(" · ")
    : "No new eruptive flags in this cycle";

  const rows = [
    {
      label: "Latest bulletin",
      value: `${homeHazardsState.latestVolcanoStatus || "Weekly activity update"} · ${latestLabel}`,
      meta:
        homeHazardsState.newEruptive !== null && homeHazardsState.volcanoReports !== null
          ? `${latestTime} · ${homeHazardsState.volcanoReports} reports in this cycle`
          : "Loading volcano activity",
    },
    {
      label: "New eruptive volcanoes",
      value: homeHazardsState.newEruptive !== null ? `${homeHazardsState.newEruptive} detected` : "--",
      meta: newEruptiveList,
    },
    {
      label: "Most reported zones",
      value: topZones,
      meta:
        homeHazardsState.volcanoes !== null
          ? `${homeHazardsState.volcanoes} volcanoes monitored this cycle`
          : "Tracking volcano zones",
    },
  ];

  homeVolcanoList.innerHTML = rows
    .map(
      (row) => `
        <li class="snapshot-row">
          <a class="snapshot-row-anchor" href="/volcanoes.php">
            <div class="snapshot-main">
              <strong>${row.label}</strong>
              <span>${row.value}</span>
            </div>
            <div class="snapshot-meta">${row.meta}</div>
          </a>
        </li>
      `,
    )
    .join("");
}

function renderHazardStatusCards() {
  if (homeStatusVolcanoes) {
    homeStatusVolcanoes.textContent =
      homeHazardsState.volcanoes !== null ? String(homeHazardsState.volcanoes) : "--";
  }
  if (homeStatusVolcanoNote) {
    homeStatusVolcanoNote.textContent =
      homeHazardsState.newEruptive !== null
        ? `${homeHazardsState.newEruptive} new eruptive this cycle`
        : "Loading volcano status...";
  }

  if (homeStatusTsunami) {
    homeStatusTsunami.textContent =
      homeHazardsState.tsunamiAlerts !== null ? String(homeHazardsState.tsunamiAlerts) : "--";
  }
  if (homeStatusTsunamiNote) {
    homeStatusTsunamiNote.textContent = homeHazardsState.tsunamiLevel
      ? `Highest level: ${homeHazardsState.tsunamiLevel}`
      : "Loading tsunami status...";
  }

  if (homeStatusKp) {
    homeStatusKp.textContent =
      homeHazardsState.spaceKp !== null ? Number(homeHazardsState.spaceKp).toFixed(1) : "--";
  }
  if (homeStatusSpaceNote) {
    homeStatusSpaceNote.textContent = homeHazardsState.spaceStormLevel || "Loading space weather...";
  }

  if (homeStatusTremorClusters) {
    homeStatusTremorClusters.textContent =
      homeHazardsState.tremorClusters !== null ? String(homeHazardsState.tremorClusters) : "--";
  }
  if (homeStatusTremorNote) {
    homeStatusTremorNote.textContent =
      homeHazardsState.tremorSignals !== null
        ? `${homeHazardsState.tremorSignals} signals this cycle`
        : "Loading tremor signals...";
  }
}

function magnitudeColor(magnitude) {
  if (magnitude === null || Number.isNaN(magnitude)) {
    return "#6b7280";
  }

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

  return palette[bucket];
}

function formatMagnitude(magnitude) {
  if (typeof magnitude !== "number" || Number.isNaN(magnitude)) {
    return "M?";
  }
  return `M${magnitude.toFixed(1)}`;
}

function shortPlaceLabel(place) {
  if (!place) {
    return "Unknown";
  }
  const parts = String(place).split(",");
  return parts[0].trim() || String(place);
}

function escapeHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function setHomeMirror(key, value, color = "") {
  if (!homeMirrorNodes.length) {
    return;
  }
  homeMirrorNodes.forEach((node) => {
    if (!(node instanceof HTMLElement) || node.dataset.homeMirror !== key) {
      return;
    }
    node.textContent = value;
    if (color) {
      node.style.color = color;
    } else {
      node.style.removeProperty("color");
    }
  });
}

function formatUtcLabel(input) {
  if (!input) {
    return "--:-- UTC";
  }
  const date = new Date(input);
  if (Number.isNaN(date.getTime())) {
    return "--:-- UTC";
  }
  return `${date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit", hour12: false })} UTC`;
}

function formatUpdatedAgo(input) {
  if (!input) {
    return "updated --";
  }
  const date = new Date(input);
  const ms = date.getTime();
  if (Number.isNaN(ms)) {
    return "updated --";
  }
  const deltaMin = Math.max(0, Math.round((Date.now() - ms) / 60000));
  if (deltaMin < 1) {
    return "updated now";
  }
  if (deltaMin < 60) {
    return `updated ${deltaMin}m ago`;
  }
  const deltaHour = Math.round(deltaMin / 60);
  return `updated ${deltaHour}h ago`;
}

function getMagnitudeBandLabel(band) {
  const labels = {
    "m1-2": "M1-2",
    m3: "M3",
    m4: "M4",
    m5: "M5",
    m6: "M6",
    m7p: "M7+",
  };
  return labels[band] || "";
}

function eventInMagnitudeBand(event, band) {
  const mag = typeof event?.magnitude === "number" ? event.magnitude : NaN;
  if (Number.isNaN(mag)) {
    return false;
  }
  if (band === "m1-2") return mag < 3;
  if (band === "m3") return mag >= 3 && mag < 4;
  if (band === "m4") return mag >= 4 && mag < 5;
  if (band === "m5") return mag >= 5 && mag < 6;
  if (band === "m6") return mag >= 6 && mag < 7;
  if (band === "m7p") return mag >= 7;
  return true;
}

function setMagnitudeFilterState(nextBand) {
  activeMagnitudeBand = nextBand;
  mapFilterButtons.forEach((button) => {
    const isActive = button.dataset.band === activeMagnitudeBand;
    button.classList.toggle("is-active", isActive);
    button.setAttribute("aria-pressed", isActive ? "true" : "false");
  });
}

function getFilteredEarthquakeEvents() {
  if (!activeMagnitudeBand) {
    return allEarthquakeEvents;
  }
  return allEarthquakeEvents.filter((event) => eventInMagnitudeBand(event, activeMagnitudeBand));
}

function filterEventsToCurrentMapViewport(events) {
  if (!Array.isArray(events)) {
    return [];
  }
  if (!leafletMap) {
    return events;
  }
  const bounds = leafletMap.getBounds();
  return events.filter((event) => {
    if (typeof event?.latitude !== "number" || typeof event?.longitude !== "number") {
      return false;
    }
    return bounds.contains([event.latitude, event.longitude]);
  });
}

function getEventKey(event) {
  if (event && typeof event.id === "string" && event.id !== "") {
    return event.id;
  }
  const lat = typeof event?.latitude === "number" ? event.latitude.toFixed(3) : "na";
  const lon = typeof event?.longitude === "number" ? event.longitude.toFixed(3) : "na";
  const time = event?.event_time_utc || "na";
  return `${lat}|${lon}|${time}`;
}

function clearLeafletPulses() {
  leafletPulseRunToken += 1;
  leafletPulseTimers.forEach((timerId) => {
    window.clearTimeout(timerId);
  });
  leafletPulseTimers = [];
  leafletPulseLayers.forEach((layer) => {
    if (leafletMap && leafletMap.hasLayer(layer)) {
      layer.remove();
    }
  });
  leafletPulseLayers = [];
}

function pulseSpecFromMagnitude(magnitude) {
  const mag = typeof magnitude === "number" ? magnitude : 0;
  if (mag >= 7) {
    return { rings: 5, duration: 4200, maxRadius: 64 };
  }
  if (mag >= 6) {
    return { rings: 4, duration: 3600, maxRadius: 56 };
  }
  if (mag >= 5) {
    return { rings: 4, duration: 3200, maxRadius: 50 };
  }
  if (mag >= 4) {
    return { rings: 3, duration: 2800, maxRadius: 42 };
  }
  return { rings: 2, duration: 2200, maxRadius: 34 };
}

function playLeafletPulse(event, staggerMs = 0) {
  if (!leafletMap || typeof window === "undefined" || !window.L) {
    return;
  }
  if (typeof event?.latitude !== "number" || typeof event?.longitude !== "number") {
    return;
  }

  const startPulse = () => {
    const token = leafletPulseRunToken;
    const spec = pulseSpecFromMagnitude(event.magnitude);
    const color = magnitudeColor(event.magnitude);
    const baseRadius = Math.max(3, Math.min(8, 3 + (typeof event.magnitude === "number" ? event.magnitude * 0.45 : 0)));
    const startTs = performance.now();
    const rings = [];

    for (let i = 0; i < spec.rings; i += 1) {
      const layer = window.L.circleMarker([event.latitude, event.longitude], {
        radius: baseRadius,
        color,
        weight: 1.4,
        fill: false,
        opacity: 0,
        interactive: false,
      }).addTo(leafletMap);
      leafletPulseLayers.push(layer);
      rings.push({ layer, delay: i * 210 });
    }

    const tick = (nowTs) => {
      if (token !== leafletPulseRunToken) {
        return;
      }

      let hasLiveRing = false;
      rings.forEach((ring, index) => {
        const elapsed = nowTs - startTs - ring.delay;
        if (elapsed < 0) {
          hasLiveRing = true;
          return;
        }

        const progress = Math.min(1, elapsed / spec.duration);
        const radius = baseRadius + spec.maxRadius * progress;
        const opacity = Math.max(0, (1 - progress) ** 1.55 * (0.92 - index * 0.12));
        ring.layer.setRadius(radius);
        ring.layer.setStyle({ opacity });

        if (progress < 1) {
          hasLiveRing = true;
        }
      });

      if (hasLiveRing) {
        window.requestAnimationFrame(tick);
        return;
      }

      rings.forEach((ring) => {
        if (leafletMap && leafletMap.hasLayer(ring.layer)) {
          ring.layer.remove();
        }
      });
      leafletPulseLayers = leafletPulseLayers.filter((layer) => !rings.some((ring) => ring.layer === layer));
    };

    window.requestAnimationFrame(tick);
  };

  if (staggerMs > 0) {
    const timerId = window.setTimeout(startPulse, staggerMs);
    leafletPulseTimers.push(timerId);
    return;
  }

  startPulse();
}

function eventDetailUrl(event) {
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
}

function projectPoint(latitude, longitude) {
  const x = ((longitude + 180) / 360) * 1200;
  const y = ((90 - latitude) / 180) * 520;
  return { x, y };
}

function drawGraticule() {
  if (!mapGraticule) {
    return;
  }

  mapGraticule.innerHTML = "";
  const ns = "http://www.w3.org/2000/svg";

  for (let lon = -120; lon <= 120; lon += 60) {
    const x = ((lon + 180) / 360) * 1200;
    const line = document.createElementNS(ns, "line");
    line.setAttribute("x1", String(x));
    line.setAttribute("y1", "0");
    line.setAttribute("x2", String(x));
    line.setAttribute("y2", "520");
    line.setAttribute("class", "map-grid-line");
    mapGraticule.appendChild(line);
  }

  for (let lat = -60; lat <= 60; lat += 30) {
    const y = ((90 - lat) / 180) * 520;
    const line = document.createElementNS(ns, "line");
    line.setAttribute("x1", "0");
    line.setAttribute("y1", String(y));
    line.setAttribute("x2", "1200");
    line.setAttribute("y2", String(y));
    line.setAttribute("class", "map-grid-line");
    mapGraticule.appendChild(line);
  }
}

function drawContinents() {
  if (!mapContinents) {
    return;
  }

  mapContinents.innerHTML = "";
  const ns = "http://www.w3.org/2000/svg";
  const blobs = [
    "M90 180 L225 140 L310 155 L340 210 L275 265 L205 290 L120 260 Z",
    "M260 295 L330 345 L320 430 L275 490 L220 458 L238 372 Z",
    "M510 132 L610 110 L710 130 L782 176 L752 230 L675 252 L590 238 L520 196 Z",
    "M612 258 L672 282 L704 352 L676 430 L622 454 L568 400 L558 322 Z",
    "M785 118 L845 92 L896 112 L914 146 L878 172 L824 164 Z",
    "M820 302 L876 318 L902 362 L872 406 L824 396 L798 344 Z",
    "M1008 402 L1066 384 L1102 412 L1088 448 L1028 462 L992 438 Z",
  ];

  blobs.forEach((d) => {
    const path = document.createElementNS(ns, "path");
    path.setAttribute("d", d);
    path.setAttribute("class", "map-continent");
    mapContinents.appendChild(path);
  });
}

function renderMap(events) {
  if (mapLeafletContainer && typeof window !== "undefined" && window.L) {
    if (!leafletMap) {
      leafletMap = window.L.map(mapLeafletContainer, {
        zoomControl: true,
        worldCopyJump: true,
        attributionControl: true,
      }).setView([14, 10], 2);

      leafletLightTiles = window.L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 8,
        minZoom: 2,
        attribution: "&copy; OpenStreetMap contributors",
      });
      leafletDarkTiles = window.L.tileLayer("https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png", {
        maxZoom: 8,
        minZoom: 2,
        attribution: "&copy; OpenStreetMap contributors &copy; CARTO",
      });
      leafletNightLightsTiles = window.L.tileLayer(
        "https://gibs.earthdata.nasa.gov/wmts/epsg3857/best/VIIRS_CityLights_2012/default/GoogleMapsCompatible_Level8/{z}/{y}/{x}.jpg",
        {
          maxZoom: 8,
          minZoom: 2,
          maxNativeZoom: 8,
          opacity: 0.28,
          attribution: "&copy; NASA Earth Observatory / NOAA NGDC",
        }
      );

      leafletLightTiles.addTo(leafletMap);

      leafletMap.on("zoomend", () => {
        if (latestEarthquakePayload) {
          applyEarthquakeView();
        }
      });
      leafletMap.on("moveend", () => {
        if (homeMapViewportOnly && latestEarthquakePayload) {
          applyEarthquakeView();
        }
      });
    }

    syncLeafletTheme();

    clearLeafletPulses();
    leafletMarkers.forEach((marker) => marker.remove());
    leafletMarkers = [];
    mapEventLookup.clear();

    events.forEach((event) => {
      if (typeof event.latitude !== "number" || typeof event.longitude !== "number") {
        return;
      }

      const magnitude = typeof event.magnitude === "number" ? event.magnitude : 0;
      const zoom = leafletMap ? leafletMap.getZoom() : 2;
      const zoomBoost = Math.max(0, (zoom - 2) * 0.55);
      const baseRadius = Math.max(3.2, Math.min(14.5, 3.2 + magnitude * 1.25));
      const radius = Math.min(18, baseRadius + zoomBoost);
      const marker = window.L.circleMarker([event.latitude, event.longitude], {
        radius,
        color: "rgba(255,255,255,0.92)",
        weight: 1,
        fillColor: magnitudeColor(event.magnitude),
        fillOpacity: 0.88,
      }).bindTooltip(`${formatMagnitude(event.magnitude)} - ${event.place}`, {
        direction: "top",
        offset: [0, -4],
        opacity: 0.9,
      });
      const eventKey = getEventKey(event);
      marker.on("click", () => {
        focusEventOnMap(eventKey, true);
      });

      marker.addTo(leafletMap);
      leafletMarkers.push(marker);
      mapEventLookup.set(eventKey, {
        marker,
        lat: event.latitude,
        lon: event.longitude,
        event,
      });
    });

    if (isEarthquakesPage && pendingLeafletPulseEvents.length > 0) {
      const pulseCandidates = pendingLeafletPulseEvents
        .filter((event) => mapEventLookup.has(getEventKey(event)))
        .sort((a, b) => {
          const magA = typeof a.magnitude === "number" ? a.magnitude : -1;
          const magB = typeof b.magnitude === "number" ? b.magnitude : -1;
          if (magB !== magA) {
            return magB - magA;
          }
          const tsA = a.event_time_utc ? Date.parse(a.event_time_utc) : 0;
          const tsB = b.event_time_utc ? Date.parse(b.event_time_utc) : 0;
          return tsB - tsA;
        })
        .slice(0, 10);

      pulseCandidates.forEach((event, index) => {
        playLeafletPulse(event, index * 160);
      });
      pendingLeafletPulseEvents = [];
    }

    return;
  }

  if (!mapPoints) {
    return;
  }

  mapPoints.innerHTML = "";
  const ns = "http://www.w3.org/2000/svg";

  events.forEach((event) => {
    if (typeof event.latitude !== "number" || typeof event.longitude !== "number") {
      return;
    }

    const projected = projectPoint(event.latitude, event.longitude);
    const magnitude = typeof event.magnitude === "number" ? event.magnitude : 0;
    const radius = Math.max(2.4, Math.min(12, 2.4 + magnitude * 1.1));

    const point = document.createElementNS(ns, "circle");
    point.setAttribute("cx", projected.x.toFixed(2));
    point.setAttribute("cy", projected.y.toFixed(2));
    point.setAttribute("r", radius.toFixed(2));
    point.setAttribute("fill", magnitudeColor(event.magnitude));
    point.setAttribute("class", "map-point");
    point.setAttribute("fill-opacity", "0.9");

    const title = document.createElementNS(ns, "title");
    title.textContent = `${formatMagnitude(event.magnitude)} - ${event.place}`;
    point.appendChild(title);
    mapPoints.appendChild(point);
  });
}

function syncEarthquakesFeedHeight() {
  if ((!isEarthquakesPage && !isMapsPage) || !eventsList || !earthquakesMapCard || !earthquakesSideCard) {
    return;
  }

  if (typeof window !== "undefined" && window.matchMedia("(max-width: 1120px)").matches) {
    eventsList.style.removeProperty("max-height");
    return;
  }

  const mapRect = earthquakesMapCard.getBoundingClientRect();
  const sideRect = earthquakesSideCard.getBoundingClientRect();
  const listRect = eventsList.getBoundingClientRect();
  const sideStyles = window.getComputedStyle(earthquakesSideCard);
  const sidePaddingBottom = parseFloat(sideStyles.paddingBottom || "0") || 0;
  const topOffset = Math.max(0, listRect.top - sideRect.top);
  const available = Math.floor(mapRect.height - topOffset - sidePaddingBottom);
  eventsList.style.maxHeight = `${Math.max(220, available)}px`;
}

function syncLeafletMapSize() {
  if (!leafletMap || typeof window === "undefined") {
    return;
  }
  if (leafletResizeRaf) {
    window.cancelAnimationFrame(leafletResizeRaf);
  }
  leafletResizeRaf = window.requestAnimationFrame(() => {
    leafletMap?.invalidateSize(false);
    leafletResizeRaf = 0;
  });
}

function depthBand(depthKm) {
  if (typeof depthKm !== "number" || Number.isNaN(depthKm)) {
    return { label: "N/A", cls: "" };
  }
  if (depthKm < 70) {
    return { label: "Shallow", cls: "depth-shallow" };
  }
  if (depthKm < 300) {
    return { label: "Intermediate", cls: "depth-intermediate" };
  }
  return { label: "Deep", cls: "depth-deep" };
}

function parseRegion(place) {
  if (!place) {
    return "Unknown";
  }

  if (place.includes(" of ")) {
    return place.split(" of ").slice(-1)[0].trim();
  }

  const commaParts = place.split(",");
  return commaParts[commaParts.length - 1].trim() || place;
}

function formatRegionLabel(region) {
  if (!region) {
    return "Unknown";
  }
  const normalized = String(region)
    .toLowerCase()
    .replace(/\s+/g, " ")
    .trim();
  return normalized.replace(/\b\w/g, (char) => char.toUpperCase());
}

function renderHomeAiInsight(context, events) {
  if (!homeAiTech && !homeAiText) {
    return;
  }

  const leadEvent = [...events]
    .filter((event) => context.mode !== "regional-focus" || formatRegionLabel(parseRegion(event.place)) === context.regionLabel)
    .sort((a, b) => {
      const magDiff = (b.magnitude || 0) - (a.magnitude || 0);
      if (magDiff !== 0) {
        return magDiff;
      }
      const aTime = a?.event_time_utc ? new Date(a.event_time_utc).getTime() : 0;
      const bTime = b?.event_time_utc ? new Date(b.event_time_utc).getTime() : 0;
      return bTime - aTime;
    })[0] || null;

  const leadMag = leadEvent && typeof leadEvent.magnitude === "number" ? formatMagnitude(leadEvent.magnitude) : "M--";
  const leadPlace = leadEvent ? shortPlaceLabel(leadEvent.place) : context.regionLabel;

  if (homeAiTech) {
    const magColor = leadEvent ? magnitudeColor(leadEvent.magnitude) : "#6b7280";
    homeAiTech.innerHTML = `<span style="color:${magColor}">${escapeHtml(leadMag)}</span> · ${escapeHtml(leadPlace)}`;
  }
  if (homeAiText) {
    if (context.mode === "regional-focus") {
      homeAiText.textContent = tHome("ai_text_regional", {
        regionLabel: context.regionLabel,
        focusCount: context.focusCount,
      });
    } else {
      homeAiText.textContent = tHome("ai_text_global");
    }
  }
}

function renderHomeContextEarthquakeRow(events, context) {
  if (!homeContextEqList) {
    return;
  }

  const scopedEvents =
    context?.mode === "regional-focus" && context?.regionLabel
      ? events.filter((event) => formatRegionLabel(parseRegion(event.place)) === context.regionLabel)
      : events;

  const rows = [...scopedEvents]
    .sort((a, b) => {
      const magDiff = (b.magnitude || 0) - (a.magnitude || 0);
      if (magDiff !== 0) return magDiff;
      const aTime = a?.event_time_utc ? new Date(a.event_time_utc).getTime() : 0;
      const bTime = b?.event_time_utc ? new Date(b.event_time_utc).getTime() : 0;
      return bTime - aTime;
    })
    .slice(0, 3);

  if (homeContextEqTitle) {
    homeContextEqTitle.textContent =
      context?.mode === "regional-focus"
        ? tHome("eq_highlighted_region", { regionLabel: context.regionLabel })
        : tHome("eq_highlighted");
  }

  if (rows.length === 0) {
    homeContextEqList.innerHTML = `<li class='home-context-earthquake-item'>${escapeHtml(tHome("no_event_available"))}</li>`;
    return;
  }

  homeContextEqList.innerHTML = rows
    .map((event) => {
      const when = event?.event_time_utc
        ? new Date(event.event_time_utc).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
        : "--:--";
      return `
        <li class="home-context-earthquake-item">
          <strong style="color:${magnitudeColor(event.magnitude)}">${formatMagnitude(event.magnitude)}</strong>
          <span>${shortPlaceLabel(event.place)}</span>
          <em>${when}</em>
        </li>
      `;
    })
    .join("");
}

function renderHomeContextGenericRow(title, rows) {
  if (!homeContextEqList) {
    return;
  }
  if (homeContextEqTitle) {
    homeContextEqTitle.textContent = title;
  }

  const safeRows = Array.isArray(rows) ? rows.slice(0, 3) : [];
  if (safeRows.length === 0) {
    homeContextEqList.innerHTML = `<li class='home-context-earthquake-item'>${escapeHtml(tHome("no_update_available"))}</li>`;
    return;
  }

  homeContextEqList.innerHTML = safeRows
    .map((row) => {
      const lead = row?.lead || "--";
      const label = row?.label || "";
      const meta = row?.meta || "";
      return `
        <li class="home-context-earthquake-item">
          <strong>${lead}</strong>
          <span>${label}</span>
          <em>${meta}</em>
        </li>
      `;
    })
    .join("");
}

function parseGeomagneticTier(stormLevel, kp) {
  const text = typeof stormLevel === "string" ? stormLevel.toUpperCase() : "";
  const match = text.match(/G([1-5])/);
  if (match) {
    return Number(match[1]);
  }
  if (typeof kp === "number") {
    if (kp >= 8) return 4;
    if (kp >= 7) return 3;
    if (kp >= 6) return 2;
    if (kp >= 5) return 1;
  }
  return 0;
}

function clampEditorialText(text, maxLen = 0) {
  const normalized = String(text || "").replace(/\s+/g, " ").trim();
  if (!normalized) {
    return "";
  }
  if (!maxLen || maxLen <= 0 || normalized.length <= maxLen) {
    return normalized;
  }
  return `${normalized.slice(0, maxLen).trimEnd()}...`;
}

function ensureBulletinEllipsis(text) {
  const value = String(text || "").trim();
  if (!value) {
    return "";
  }
  if (/[.!?]$/.test(value)) {
    return value;
  }
  if (value.endsWith("...")) {
    return value;
  }
  return `${value}...`;
}

function buildPriorityEditorialNotes(event, relatedEvents = []) {
  const notes = [];
  const primary = ensureBulletinEllipsis(
    clampEditorialText(event?.editorial_note || event?.summary || event?.status || ""),
  );
  if (primary) {
    notes.push({
      kind: "primary",
      label: "Primary bulletin",
      text: primary,
      url: event?.bulletin_url || event?.detail_url || event?.monitor_url || null,
    });
  }

  const secondary = Array.isArray(relatedEvents)
    ? relatedEvents.find((row) => row && row.id && row.id !== event?.id)
    : null;
  const shouldAddSecondary = primary.length > 0 && primary.length < 380;
  if (secondary && shouldAddSecondary) {
    const lead = secondary.title || secondary.location_or_subject || "Secondary signal";
    const detail = secondary.editorial_note || secondary.summary || secondary.status || "";
    const merged = ensureBulletinEllipsis(clampEditorialText(`${detail}`.trim()));
    if (merged) {
      notes.push({
        kind: "related",
        label: `Related bulletin: ${lead}`,
        text: merged,
        url: secondary?.bulletin_url || secondary?.detail_url || secondary?.monitor_url || null,
      });
    }
  }

  return notes.slice(0, 2);
}

function buildPriorityContextRows(event, relatedEvents = []) {
  const related = Array.isArray(relatedEvents) ? relatedEvents.filter((row) => row && row.id !== event?.id) : [];
  const firstRelated = related[0] || null;
  const typeLabel = String(event?.type || "signal").replace("_", " ");
  const rows = [
    { label: "Track", value: `${typeLabel.toUpperCase()} · ${event?.priority_level || "P3"}` },
    { label: "Primary status", value: event?.status || event?.secondary_value || "--" },
    { label: "Secondary lanes", value: `${related.length}` },
  ];

  if (firstRelated) {
    rows.push({
      label: "Next signal",
      value: firstRelated.title || firstRelated.location_or_subject || "Secondary monitor",
    });
  } else {
    rows.push({
      label: "Next signal",
      value: "No secondary signal in this cycle",
    });
  }

  return rows.slice(0, 4);
}

function priorityLevelWeight(level) {
  if (level === "P1") return 3;
  if (level === "P2") return 2;
  return 1;
}

function normalizeEarthquakeEvents(events) {
  return [...events]
    .filter((event) => typeof event?.magnitude === "number")
    .map((event) => {
      const magnitude = Number(event.magnitude || 0);
      const depth = typeof event.depth_km === "number" ? event.depth_km : null;
      const ts = event?.event_time_utc ? new Date(event.event_time_utc).getTime() : 0;
      const ageMinutes = ts > 0 ? Math.max(0, (Date.now() - ts) / 60000) : 9999;
      const recencyBoost = Math.max(0, 160 - ageMinutes) / 9;
      const score = magnitude * 14 + recencyBoost + (depth !== null && depth < 50 ? 5 : 0);
      const level = magnitude >= 6.8 || (magnitude >= 6.2 && ageMinutes <= 120) ? "P1" : magnitude >= 5 ? "P2" : "P3";
      return {
        id: `eq:${getEventKey(event)}`,
        type: "earthquake",
        priority_score: Math.round(score),
        priority_level: level,
        title: `${formatMagnitude(magnitude)} earthquake - ${shortPlaceLabel(event.place)}`,
        summary: `${shortPlaceLabel(event.place)} · ${depth !== null ? `${depth.toFixed(0)} km depth` : "depth unavailable"}`,
        editorial_note: clampEditorialText(
          `${shortPlaceLabel(event.place)} registered ${formatMagnitude(magnitude)} with ${
            depth !== null ? `${depth.toFixed(0)} km depth` : "depth not available"
          }.`,
        ),
        bulletin_url: null,
        location_or_subject: shortPlaceLabel(event.place),
        main_value: formatMagnitude(magnitude),
        secondary_value: depth !== null ? `${depth.toFixed(0)} km` : "N/A",
        timestamp: event?.event_time_utc || null,
        status: level === "P1" ? "Critical seismic signal" : "Seismic monitoring",
        detail_url: eventDetailUrl(event),
        monitor_url: "/earthquakes.php",
      };
    });
}

function normalizeVolcanoEvents() {
  const events = Array.isArray(homeHazardsState.volcanoEvents) ? homeHazardsState.volcanoEvents : [];
  if (events.length === 0 && homeHazardsState.newEruptive === null) {
    return [];
  }

  const reports = Number(homeHazardsState.volcanoReports || 0);
  const newEruptive = Number(homeHazardsState.newEruptive || 0);
  const ranked = events.slice(0, 3);
  if (ranked.length === 0) {
    return [{
      id: "volcano:summary",
      type: "volcano",
      priority_score: reports + newEruptive * 20,
      priority_level: newEruptive >= 2 ? "P1" : reports > 0 ? "P2" : "P3",
      title: "Volcanic activity cycle",
      summary: `${newEruptive} new eruptive signals · ${reports} reports`,
      editorial_note: clampEditorialText(
        `Bulletin cycle tracks ${reports} reports with ${newEruptive} new eruptive signals across monitored volcanoes.`,
      ),
      bulletin_url: null,
      location_or_subject: homeHazardsState.latestCountry || "Global",
      main_value: `${newEruptive}`,
      secondary_value: `${reports} reports`,
      timestamp: homeHazardsState.latestVolcanoTime ? homeHazardsState.latestVolcanoTime.toISOString() : null,
      status: "Volcano monitoring",
      detail_url: "/volcanoes.php",
      monitor_url: "/volcanoes.php",
    }];
  }

  return ranked.map((event, index) => {
    const isNew = Boolean(event?.is_new_eruptive);
    const level = index === 0 && isNew && newEruptive >= 2 ? "P1" : isNew || reports >= 8 ? "P2" : "P3";
    const score = (index === 0 && level === "P1" ? 92 : isNew ? 68 : 58) + Math.max(0, reports - index * 2);
    const volcanoName = shortPlaceLabel(event?.volcano || event?.title || "Volcano update");
    return {
      id: `volcano:${event?.id || volcanoName}:${index}`,
      type: "volcano",
      priority_score: score,
      priority_level: level,
      title: isNew ? `${volcanoName} - new eruptive activity` : `${volcanoName} - activity update`,
      summary: event?.country ? `${event.country} · ${isNew ? "new eruptive signal" : "ongoing activity"}` : "Weekly volcano status",
      editorial_note: clampEditorialText(event?.summary || ""),
      bulletin_url: event?.source_url || null,
      location_or_subject: event?.country || volcanoName,
      main_value: isNew ? "New eruptive" : "Active",
      secondary_value: `${reports} reports`,
      timestamp: event?.event_time_utc || null,
      status: isNew ? "Escalation detected" : "Volcanic monitoring",
      detail_url: "/volcanoes.php",
      monitor_url: "/volcanoes.php",
    };
  });
}

function normalizeTsunamiEvents() {
  if (homeHazardsState.tsunamiAlerts === null) {
    return [];
  }
  const alerts = Number(homeHazardsState.tsunamiAlerts || 0);
  const levelLabel = homeHazardsState.tsunamiLevel || "None";
  const isActive = alerts > 0 && !String(levelLabel).toLowerCase().includes("none");
  return [{
    id: "tsunami:global",
    type: "tsunami",
    priority_score: isActive ? 99 : 26,
    priority_level: isActive ? "P1" : "P3",
    title: isActive ? `Tsunami alerts active - ${levelLabel}` : "No active tsunami alerts",
    summary: isActive ? `${alerts} active alert(s) across monitored coasts` : "Operational feed reports no active tsunami advisories",
    editorial_note: clampEditorialText(
      isActive
        ? `Operational tsunami advisories are active in ${Math.max(1, alerts)} monitored coastal sector(s).`
        : "No active tsunami advisories are reported in current operational bulletins.",
    ),
    bulletin_url: null,
    location_or_subject: "Global coastlines",
    main_value: `${alerts}`,
    secondary_value: levelLabel,
    timestamp: homeHazardsState.tsunamiPayload?.generated_at || null,
    status: isActive ? "Coastal alerting active" : "Calm",
    detail_url: "/tsunami.php",
    monitor_url: "/tsunami.php",
  }];
}

function normalizeSpaceEvents() {
  if (homeHazardsState.spaceKp === null && !homeHazardsState.spaceStormLevel) {
    return [];
  }
  const kp = typeof homeHazardsState.spaceKp === "number" ? homeHazardsState.spaceKp : 0;
  const tier = parseGeomagneticTier(homeHazardsState.spaceStormLevel, kp);
  const level = tier >= 3 || kp >= 7 ? "P1" : tier >= 1 || kp >= 5 ? "P2" : "P3";
  return [{
    id: "space:global",
    type: "space_weather",
    priority_score: Math.round(kp * 14 + tier * 18),
    priority_level: level,
    title: `Geomagnetic status - ${homeHazardsState.spaceStormLevel || "Monitoring"}`,
    summary: `Current Kp ${kp.toFixed(1)} · NOAA level ${homeHazardsState.spaceStormLevel || "not classified"}`,
    editorial_note: clampEditorialText(
      `Geomagnetic conditions are at Kp ${kp.toFixed(1)} with level ${
        homeHazardsState.spaceStormLevel || "monitoring"
      }.`,
    ),
    bulletin_url: null,
    location_or_subject: "Global",
    main_value: `Kp ${kp.toFixed(1)}`,
    secondary_value: homeHazardsState.spaceStormLevel || "Monitoring",
    timestamp: homeHazardsState.spacePayload?.generated_at || null,
    status: level === "P1" ? "Elevated geomagnetic storm risk" : "Space weather monitoring",
    detail_url: "/space-weather.php",
    monitor_url: "/space-weather.php",
  }];
}

function buildHomePriorityModel(events) {
  const normalized = [
    ...normalizeEarthquakeEvents(events),
    ...normalizeVolcanoEvents(),
    ...normalizeTsunamiEvents(),
    ...normalizeSpaceEvents(),
  ].sort((a, b) => {
    const levelDiff = priorityLevelWeight(b.priority_level) - priorityLevelWeight(a.priority_level);
    if (levelDiff !== 0) return levelDiff;
    const scoreDiff = (b.priority_score || 0) - (a.priority_score || 0);
    if (scoreDiff !== 0) return scoreDiff;
    const aTs = a.timestamp ? new Date(a.timestamp).getTime() : 0;
    const bTs = b.timestamp ? new Date(b.timestamp).getTime() : 0;
    return bTs - aTs;
  });

  const p1Events = normalized.filter((event) => event.priority_level === "P1");
  let mode = "fallback";
  let boardEvents = [];
  if (p1Events.length === 1) {
    mode = "single";
    boardEvents = p1Events.slice(0, 1);
  } else if (p1Events.length === 2) {
    mode = "dual";
    boardEvents = p1Events.slice(0, 2);
  } else if (p1Events.length >= 3) {
    mode = "triple";
    boardEvents = p1Events.slice(0, 3);
  } else {
    boardEvents = normalized.filter((event) => event.priority_level !== "P3").slice(0, 3);
    if (boardEvents.length === 0 && normalized.length > 0) {
      boardEvents = normalized.slice(0, 1);
    }
  }

  const used = new Set(boardEvents.map((event) => event.id));
  const railEvents = normalized
    .filter((event) => !used.has(event.id) && event.priority_level !== "P3")
    .slice(0, 7);
  if (railEvents.length < 5) {
    normalized.forEach((event) => {
      if (railEvents.length >= 7 || used.has(event.id) || railEvents.find((row) => row.id === event.id)) {
        return;
      }
      railEvents.push(event);
    });
  }

  return { mode, boardEvents, railEvents, allEvents: normalized };
}

function renderPriorityCard(event, compact = false, options = {}) {
  const levelClass = `is-${String(event.priority_level || "P3").toLowerCase()}`;
  const metrics = [
    { label: "Main", value: event.main_value || "--" },
    { label: "Scope", value: event.location_or_subject || "--" },
    { label: "Status", value: event.secondary_value || event.status || "--" },
  ];
  const safeTitle = escapeHtml(event.title || "Signal");
  const safeSummary = escapeHtml(event.summary || "");
  const safeType = escapeHtml((event.type || "signal").replace("_", " "));
  const safeLevel = escapeHtml(event.priority_level || "P3");
  const safeFreshness = escapeHtml(formatUpdatedAgo(event.timestamp));
  const editorialNotes = buildPriorityEditorialNotes(event, options.relatedEvents || []);
  const contextRows = buildPriorityContextRows(event, options.relatedEvents || []);
  const cta = escapeHtml(event.type === "earthquake" ? "Open details" : "Open monitor");
  const primaryUrlRaw = event.detail_url || event.monitor_url || "#";
  const secondaryUrlRaw = event.monitor_url || "";
  const primaryUrl = String(primaryUrlRaw || "").trim();
  const secondaryUrl = String(secondaryUrlRaw || "").trim();
  const showSecondaryLink =
    secondaryUrl !== "" &&
    secondaryUrl !== "#" &&
    primaryUrl !== "" &&
    primaryUrl !== "#" &&
    secondaryUrl !== primaryUrl;

  if (compact) {
    return `
      <article class="home-priority-card home-priority-card-compact ${levelClass}">
        <p class="home-priority-card-top">
          <span class="home-priority-chip">${safeType}</span>
          <span class="home-priority-chip home-priority-chip-level">${safeLevel}</span>
        </p>
        <h4>${safeTitle}</h4>
        <p class="home-priority-card-meta">${escapeHtml(event.main_value || "--")} · ${safeFreshness}</p>
        <a class="inline-link" href="${event.detail_url || event.monitor_url || "#"}">${cta}</a>
      </article>
    `;
  }

  return `
    <article class="home-priority-card ${levelClass}">
      <div class="home-priority-card-head">
        <div class="home-priority-card-metahead">
          <div class="home-priority-card-badges">
            <span class="home-priority-chip">${safeType}</span>
            <span class="home-priority-chip home-priority-chip-level">${safeLevel}</span>
          </div>
          <span class="home-priority-card-freshness">${safeFreshness}</span>
        </div>
      </div>
      <h4>${safeTitle}</h4>
      <p class="home-priority-card-summary">${safeSummary}</p>
      <dl class="home-priority-card-metrics">
        ${metrics
          .map(
            (metric) => `
          <div>
            <dt>${escapeHtml(metric.label)}</dt>
            <dd>${escapeHtml(metric.value)}</dd>
          </div>`,
          )
          .join("")}
      </dl>
      ${
        editorialNotes.length > 0 || contextRows.length > 0
          ? `<div class="home-priority-card-editorial">
              ${
                editorialNotes.length > 0
                  ? `<ul class="home-priority-card-editorial-list">
                      ${editorialNotes
                        .map((note) => {
                          const safeLabel = escapeHtml(note.label || "Bulletin");
                          const safeText = escapeHtml(note.text || "");
                          const safeUrl = typeof note.url === "string" && note.url.trim() !== ""
                            ? escapeHtml(note.url.trim())
                            : "";
                          const body = safeUrl
                            ? `<a class="home-priority-bulletin-link" href="${safeUrl}" target="_blank" rel="noopener noreferrer">${safeText}</a>`
                            : `<span>${safeText}</span>`;
                          const itemClass = note.kind === "primary" ? "is-primary" : note.kind === "related" ? "is-related" : "";
                          return `<li class="${itemClass}"><strong>${safeLabel}</strong>${body}</li>`;
                        })
                        .join("")}
                    </ul>`
                  : ""
              }
              ${
                contextRows.length > 0
                  ? `<dl class="home-priority-card-context">
                      ${contextRows
                        .map(
                          (row) => `
                        <div>
                          <dt>${escapeHtml(row.label)}</dt>
                          <dd>${escapeHtml(row.value)}</dd>
                        </div>`,
                        )
                        .join("")}
                    </dl>`
                  : ""
              }
            </div>`
          : ""
      }
      <div class="home-priority-card-actions">
        <a class="btn btn-primary home-priority-card-cta" href="${primaryUrl || "#"}">${cta}</a>
        ${showSecondaryLink ? `<a class="inline-link home-priority-card-inline" href="${secondaryUrl}">Open category monitor</a>` : ""}
      </div>
    </article>
  `;
}

function renderAttentionWatch(events) {
  const rows = Array.isArray(events) ? events.slice(0, 3) : [];
  if (rows.length === 0) {
    return `
      <aside class="home-priority-watch">
        <div class="home-priority-watch-head">
          <p class="home-priority-watch-kicker">Attention watch</p>
          <span>Secondary lanes</span>
        </div>
        <p class="home-priority-watch-empty">No secondary watch signals in this cycle.</p>
      </aside>
    `;
  }

  return `
    <aside class="home-priority-watch">
      <div class="home-priority-watch-head">
        <p class="home-priority-watch-kicker">Attention watch</p>
        <span>Secondary lanes</span>
      </div>
      <div class="home-priority-watch-list">
        ${rows
          .map((event) => {
            const safeTitle = escapeHtml(event.title || "Signal");
            const safeType = escapeHtml((event.type || "signal").replace("_", " "));
            const safeLevel = escapeHtml(event.priority_level || "P2");
            const safeMeta = escapeHtml(`${event.main_value || "--"} · ${formatUpdatedAgo(event.timestamp)}`);
            const cta = escapeHtml(event.type === "earthquake" ? "Open details" : "Open monitor");
            return `
              <article class="home-priority-watch-row">
                <div class="home-priority-watch-row-top">
                  <span class="home-priority-chip">${safeType}</span>
                  <span class="home-priority-chip home-priority-chip-level">${safeLevel}</span>
                </div>
                <h4>${safeTitle}</h4>
                <p class="home-priority-watch-meta">${safeMeta}</p>
                <a class="inline-link" href="${event.detail_url || event.monitor_url || "#"}">${cta}</a>
              </article>
            `;
          })
          .join("")}
      </div>
    </aside>
  `;
}

function renderPriorityBoard(model) {
  if (!homePriorityBoardCards) {
    return;
  }

  if (homePriorityNow) {
    const liveLabels = {
      single: "Single critical signal",
      dual: "Dual critical signal",
      triple: "Stacked critical signal",
      fallback: "Global watch",
    };
    homePriorityNow.textContent = liveLabels[model.mode] || "Global watch";
  }

  if (homePriorityMode) {
    const labels = {
      single: "Current critical signal",
      dual: "Dual critical signals",
      triple: "Three concurrent critical signals",
      fallback: "Current global focus",
    };
    homePriorityMode.textContent = labels[model.mode] || "Current global focus";
  }

  if (homePrioritySupport) {
    homePrioritySupport.textContent =
      model.mode === "fallback"
        ? "No P1 event detected: board is showing strongest P2 signals."
        : "Signals ranked by normalized cross-domain priority.";
  }

  if (!Array.isArray(model.boardEvents) || model.boardEvents.length === 0) {
    homePriorityBoardCards.innerHTML = "<p class='home-priority-loading'>No ranked signal available right now.</p>";
    return;
  }

  if (model.mode === "single" || model.mode === "fallback") {
    const primary = model.boardEvents[0];
    const secondary = model.boardEvents.slice(1, 3);
    const watchEvents = secondary.length > 0 ? secondary : model.railEvents.slice(0, 3);
    homePriorityBoardCards.innerHTML = `
      <div class="home-priority-board-single">
        ${renderPriorityCard(primary, false, { relatedEvents: watchEvents })}
        ${renderAttentionWatch(watchEvents)}
      </div>
    `;
    return;
  }

  if (model.mode === "dual") {
    homePriorityBoardCards.innerHTML = `
      <div class="home-priority-board-dual">
        ${model.boardEvents.slice(0, 2).map((event) => renderPriorityCard(event, false)).join("")}
      </div>
    `;
    return;
  }

  homePriorityBoardCards.innerHTML = `
    <div class="home-priority-board-triple">
      ${model.boardEvents.slice(0, 3).map((event) => renderPriorityCard(event, true)).join("")}
    </div>
  `;
}

function renderSignificantRail(model) {
  if (!homeSignificantList) {
    return;
  }
  if (homeSignificantHeadNote) {
    homeSignificantHeadNote.textContent = model.mode === "fallback" ? "Live ranked" : "P2 and P1 stream";
  }
  const rows = Array.isArray(model.railEvents) ? model.railEvents.slice(0, 8) : [];
  if (rows.length === 0) {
    homeSignificantList.innerHTML = "<li class='snapshot-row'>No significant events in this cycle.</li>";
    return;
  }
  homeSignificantList.innerHTML = rows
    .map((event) => {
      const safeType = escapeHtml((event.type || "signal").replace("_", " ").toUpperCase());
      return `
        <li class="snapshot-row">
          <a class="snapshot-row-anchor" href="${event.detail_url || event.monitor_url || "#"}">
            <div class="snapshot-main">
              <strong>${safeType}</strong>
              <span>${escapeHtml(event.main_value || "--")} · ${escapeHtml(event.location_or_subject || "--")}</span>
            </div>
            <div class="snapshot-meta">${escapeHtml(formatUtcLabel(event.timestamp))}</div>
          </a>
        </li>
      `;
    })
    .join("");
}

function renderEarthquakesModule(events) {
  if (!homeModuleEarthquakesList) {
    return;
  }
  const rows = [...events]
    .sort((a, b) => {
      const magDiff = (b.magnitude || 0) - (a.magnitude || 0);
      if (magDiff !== 0) return magDiff;
      const aTime = a?.event_time_utc ? new Date(a.event_time_utc).getTime() : 0;
      const bTime = b?.event_time_utc ? new Date(b.event_time_utc).getTime() : 0;
      return bTime - aTime;
    })
    .slice(0, 3);
  if (rows.length === 0) {
    homeModuleEarthquakesList.innerHTML = "<li class='snapshot-row'>No earthquake events available.</li>";
    return;
  }
  homeModuleEarthquakesList.innerHTML = rows
    .map((event) => {
      const detailUrl = eventDetailUrl(event);
      return `
        <li class="snapshot-row">
          <a class="snapshot-row-anchor" href="${detailUrl}">
            <div class="snapshot-main">
              <strong>${escapeHtml(formatMagnitude(event.magnitude))}</strong>
              <span>${escapeHtml(shortPlaceLabel(event.place))}</span>
            </div>
            <div class="snapshot-meta">${escapeHtml(formatUtcLabel(event.event_time_utc))}</div>
          </a>
        </li>
      `;
    })
    .join("");
}

function renderTsunamiModule() {
  if (!homeModuleTsunamiList) {
    return;
  }
  if (homeHazardsState.tsunamiAlerts === null) {
    homeModuleTsunamiList.innerHTML = "<li class='snapshot-row'>Loading tsunami status...</li>";
    return;
  }
  const alerts = Number(homeHazardsState.tsunamiAlerts || 0);
  const highestLevel = homeHazardsState.tsunamiLevel || "None";
  const regions = Number(homeHazardsState.tsunamiRegions || 0);
  homeModuleTsunamiList.innerHTML = `
    <li class="snapshot-row">
      <a class="snapshot-row-anchor" href="/tsunami.php">
        <div class="snapshot-main">
          <strong>${alerts} active alerts</strong>
          <span>Highest level: ${escapeHtml(highestLevel)}</span>
        </div>
        <div class="snapshot-meta">${regions} region(s) in bulletin</div>
      </a>
    </li>
  `;
}

function renderSpaceModule() {
  if (!homeModuleSpaceList) {
    return;
  }
  if (homeHazardsState.spaceKp === null && !homeHazardsState.spaceStormLevel) {
    homeModuleSpaceList.innerHTML = "<li class='snapshot-row'>Loading space weather...</li>";
    return;
  }
  const kpNow = typeof homeHazardsState.spaceKp === "number" ? homeHazardsState.spaceKp.toFixed(1) : "--";
  const stormLevel = homeHazardsState.spaceStormLevel || "Monitoring";
  const forecastMax = typeof homeHazardsState.spacePayload?.forecast_kp_max_24h === "number"
    ? homeHazardsState.spacePayload.forecast_kp_max_24h.toFixed(1)
    : "--";
  homeModuleSpaceList.innerHTML = `
    <li class="snapshot-row">
      <a class="snapshot-row-anchor" href="/space-weather.php">
        <div class="snapshot-main">
          <strong>Kp ${kpNow}</strong>
          <span>${escapeHtml(stormLevel)}</span>
        </div>
        <div class="snapshot-meta">Forecast max (24h): Kp ${forecastMax}</div>
      </a>
    </li>
  `;
}

function deriveHomeLiveMode(context) {
  const model = buildHomePriorityModel(allEarthquakeEvents);
  const modeMap = {
    single: "priority-single",
    dual: "priority-dual",
    triple: "priority-triple",
    fallback: "priority-fallback",
  };
  const mode = modeMap[model.mode] || "priority-fallback";
  const intensity = model.mode === "fallback" ? "baseline" : "high";
  const leadEvent = Array.isArray(model.boardEvents) && model.boardEvents.length > 0
    ? model.boardEvents[0]
    : Array.isArray(model.allEvents) && model.allEvents.length > 0
      ? model.allEvents[0]
      : null;
  const activeModuleMap = {
    earthquake: "earthquake",
    volcano: "volcano",
    tsunami: "tsunami",
    space_weather: "space",
  };
  const activeModule = activeModuleMap[String(leadEvent?.type || "")] || "earthquake";
  return { mode, intensity, model, activeModule };
}

function applyHomeLiveMode(context) {
  const live = deriveHomeLiveMode(context);
  latestHomeLiveMode = live.mode;
  if (homeLaunch) {
    homeLaunch.setAttribute("data-live-mode", live.mode);
    homeLaunch.setAttribute("data-live-intensity", live.intensity);
    homeLaunch.setAttribute("data-active-module", live.activeModule);
  }
  renderPriorityBoard(live.model);
  renderSignificantRail(live.model);
  renderEarthquakesModule(allEarthquakeEvents);
  renderTsunamiModule();
  renderSpaceModule();
}

function haversineKm(lat1, lon1, lat2, lon2) {
  const toRad = (v) => (v * Math.PI) / 180;
  const dLat = toRad(lat2 - lat1);
  const dLon = toRad(lon2 - lon1);
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
  return 6371 * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
}

function collectCoordinates(geometry, out) {
  if (!geometry || typeof geometry !== "object") {
    return;
  }
  const coords = geometry.coordinates;
  if (!Array.isArray(coords)) {
    return;
  }
  if (typeof coords[0] === "number" && typeof coords[1] === "number") {
    out.push([coords[1], coords[0]]);
    return;
  }
  coords.forEach((entry) => {
    if (Array.isArray(entry)) {
      collectCoordinates({ coordinates: entry }, out);
    }
  });
}

function nearestFeatureDistanceKm(feature, latitude, longitude) {
  if (!feature || typeof latitude !== "number" || typeof longitude !== "number") {
    return Number.POSITIVE_INFINITY;
  }
  const points = [];
  collectCoordinates(feature.geometry, points);
  if (points.length === 0) {
    return Number.POSITIVE_INFINITY;
  }
  const step = points.length > 800 ? 6 : points.length > 300 ? 4 : 2;
  let minKm = Number.POSITIVE_INFINITY;
  for (let i = 0; i < points.length; i += step) {
    const [lat, lon] = points[i];
    const km = haversineKm(latitude, longitude, lat, lon);
    if (km < minKm) {
      minKm = km;
    }
  }
  return minKm;
}

function getFeatureName(feature) {
  const props = feature && typeof feature === "object" && feature.properties ? feature.properties : {};
  const candidateKeys = ["name", "NAME", "fault_name", "FAULT_NAME", "fault", "structure", "id"];
  for (const key of candidateKeys) {
    const value = props[key];
    if (typeof value === "string" && value.trim() !== "") {
      return value.trim();
    }
  }
  return "Unnamed feature";
}

function getSlipRateLabel(feature) {
  const props = feature && typeof feature === "object" && feature.properties ? feature.properties : {};
  const keys = Object.keys(props);
  for (const key of keys) {
    if (!/slip/i.test(key)) {
      continue;
    }
    const raw = props[key];
    if (typeof raw === "number" && Number.isFinite(raw)) {
      return `${raw.toFixed(2)} mm/yr`;
    }
    if (typeof raw === "string" && raw.trim() !== "") {
      return raw.trim();
    }
  }
  return "Not available";
}

function estimateTectonicRegime(event, nearestFaultKm, nearestPlateKm) {
  const depth = typeof event?.depth_km === "number" ? event.depth_km : NaN;
  if (Number.isFinite(depth) && depth >= 300) {
    return "Deep-focus slab regime";
  }
  if (Number.isFinite(depth) && depth >= 70 && nearestPlateKm <= 220) {
    return "Subduction/intermediate regime";
  }
  if (nearestFaultKm <= 35 && Number.isFinite(depth) && depth < 70) {
    return "Shallow crustal fault regime";
  }
  if (nearestPlateKm <= 140) {
    return "Plate-boundary regime";
  }
  return "Likely intraplate regime";
}

async function loadTectonicContext() {
  if (tectonicContextCache.plates && tectonicContextCache.faults) {
    return tectonicContextCache;
  }
  const response = await fetch("/api/tectonic-context.php?scope=global&max_plates=1200&max_faults=2400", {
    headers: { Accept: "application/json" },
  }).catch(() => null);

  if (response && response.ok) {
    const payload = await response.json().catch(() => null);
    if (payload && payload.plates && Array.isArray(payload.plates.features)) {
      tectonicContextCache.plates = payload.plates;
    }
    if (payload && payload.faults && Array.isArray(payload.faults.features)) {
      tectonicContextCache.faults = payload.faults;
    }
  }
  return tectonicContextCache;
}

function nearbyStrongSeismicity(event) {
  if (
    !event ||
    typeof event.latitude !== "number" ||
    typeof event.longitude !== "number" ||
    !Array.isArray(allEarthquakeEvents)
  ) {
    return [];
  }
  const key = getEventKey(event);
  return allEarthquakeEvents
    .filter((row) => typeof row?.magnitude === "number" && row.magnitude >= 5)
    .map((row) => ({
      ...row,
      distanceKm:
        typeof row.latitude === "number" && typeof row.longitude === "number"
          ? haversineKm(event.latitude, event.longitude, row.latitude, row.longitude)
          : Number.POSITIVE_INFINITY,
    }))
    .filter((row) => row.distanceKm <= 350 && getEventKey(row) !== key)
    .sort((a, b) => a.distanceKm - b.distanceKm || (b.magnitude || 0) - (a.magnitude || 0))
    .slice(0, 8);
}

function ensureEventInsightMap() {
  if (!eventInsightMapContainer || typeof window === "undefined" || !window.L) {
    return null;
  }
  if (eventInsightMap) {
    return eventInsightMap;
  }

  eventInsightMap = window.L.map(eventInsightMapContainer, {
    zoomControl: true,
    worldCopyJump: true,
    attributionControl: true,
  }).setView([10, 0], 2);

  eventInsightLightTiles = window.L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 9,
    minZoom: 2,
    attribution: "&copy; OpenStreetMap contributors",
  });
  eventInsightDarkTiles = window.L.tileLayer("https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png", {
    maxZoom: 9,
    minZoom: 2,
    attribution: "&copy; OpenStreetMap contributors &copy; CARTO",
  });
  eventInsightNightLightsTiles = window.L.tileLayer(
    "https://gibs.earthdata.nasa.gov/wmts/epsg3857/best/VIIRS_CityLights_2012/default/GoogleMapsCompatible_Level8/{z}/{y}/{x}.jpg",
    {
      maxZoom: 9,
      minZoom: 2,
      maxNativeZoom: 8,
      opacity: 0.24,
      attribution: "&copy; NASA Earth Observatory / NOAA NGDC",
    }
  );
  eventInsightLightTiles.addTo(eventInsightMap);
  eventInsightEventLayer = window.L.layerGroup().addTo(eventInsightMap);
  eventInsightStrongLayer = window.L.layerGroup().addTo(eventInsightMap);
  eventInsightPlateLayer = window.L.layerGroup().addTo(eventInsightMap);
  eventInsightFaultLayer = window.L.layerGroup().addTo(eventInsightMap);
  return eventInsightMap;
}

function syncThemeToggleButton() {
  if (!globalThemeToggle) {
    return;
  }
  globalThemeToggle.classList.toggle("is-active", leafletDarkMode);
  globalThemeToggle.setAttribute("aria-pressed", leafletDarkMode ? "true" : "false");
  globalThemeToggle.textContent = leafletDarkMode ? "☾" : "☀";
  const label = leafletDarkMode ? tHome("night_mode_disable") : tHome("night_mode_enable");
  globalThemeToggle.setAttribute("aria-label", label);
  globalThemeToggle.setAttribute("title", label);
}

function syncLeafletTheme() {
  if (!leafletMap || !leafletLightTiles || !leafletDarkTiles) {
    return;
  }
  if (leafletDarkMode) {
    if (leafletMap.hasLayer(leafletLightTiles)) {
      leafletMap.removeLayer(leafletLightTiles);
    }
    if (!leafletMap.hasLayer(leafletDarkTiles)) {
      leafletDarkTiles.addTo(leafletMap);
    }
    if (leafletNightLightsTiles && !leafletMap.hasLayer(leafletNightLightsTiles)) {
      leafletNightLightsTiles.addTo(leafletMap);
    }
  } else {
    if (leafletMap.hasLayer(leafletDarkTiles)) {
      leafletMap.removeLayer(leafletDarkTiles);
    }
    if (leafletNightLightsTiles && leafletMap.hasLayer(leafletNightLightsTiles)) {
      leafletMap.removeLayer(leafletNightLightsTiles);
    }
    if (!leafletMap.hasLayer(leafletLightTiles)) {
      leafletLightTiles.addTo(leafletMap);
    }
  }
}

function syncInsightMapTheme() {
  if (!eventInsightMap || !eventInsightLightTiles || !eventInsightDarkTiles) {
    return;
  }
  if (leafletDarkMode) {
    if (eventInsightMap.hasLayer(eventInsightLightTiles)) {
      eventInsightMap.removeLayer(eventInsightLightTiles);
    }
    if (!eventInsightMap.hasLayer(eventInsightDarkTiles)) {
      eventInsightDarkTiles.addTo(eventInsightMap);
    }
    if (eventInsightNightLightsTiles && !eventInsightMap.hasLayer(eventInsightNightLightsTiles)) {
      eventInsightNightLightsTiles.addTo(eventInsightMap);
    }
  } else {
    if (eventInsightMap.hasLayer(eventInsightDarkTiles)) {
      eventInsightMap.removeLayer(eventInsightDarkTiles);
    }
    if (eventInsightNightLightsTiles && eventInsightMap.hasLayer(eventInsightNightLightsTiles)) {
      eventInsightMap.removeLayer(eventInsightNightLightsTiles);
    }
    if (!eventInsightMap.hasLayer(eventInsightLightTiles)) {
      eventInsightLightTiles.addTo(eventInsightMap);
    }
  }
}

async function renderEventInsight(eventKey) {
  if (!eventInsightPanel) {
    return;
  }
  const selected = eventLookupByKey.get(eventKey);
  if (!selected || typeof selected.latitude !== "number" || typeof selected.longitude !== "number") {
    return;
  }
  selectedEventKey = eventKey;

  if (eventInsightTitle) {
    eventInsightTitle.textContent = `${formatMagnitude(selected.magnitude)} ${selected.place || "Unknown location"}`;
  }
  if (eventInsightSummary) {
    const depth = typeof selected.depth_km === "number" ? `${selected.depth_km.toFixed(1)} km` : "n/a";
    const when = selected.event_time_utc ? new Date(selected.event_time_utc).toLocaleString() : "n/a";
    eventInsightSummary.textContent = `Depth ${depth} · ${when}`;
  }

  const strongNearby = nearbyStrongSeismicity(selected);
  if (eventInsightNearbyList) {
    eventInsightNearbyList.innerHTML =
      strongNearby.length > 0
        ? strongNearby
            .map((row) => {
              const when = row.event_time_utc
                ? new Date(row.event_time_utc).toLocaleString([], { month: "short", day: "2-digit", hour: "2-digit", minute: "2-digit" })
                : "n/a";
              return `<li class="event-item"><strong>${formatMagnitude(row.magnitude)} ${row.place || "Unknown"}</strong><br />${row.distanceKm.toFixed(0)} km · ${when}</li>`;
            })
            .join("")
        : "<li class='event-item'>No M5+ events within 350 km in current dataset.</li>";
  }

  const map = ensureEventInsightMap();
  if (!map || !eventInsightEventLayer || !eventInsightStrongLayer || !eventInsightPlateLayer || !eventInsightFaultLayer) {
    return;
  }
  syncInsightMapTheme();

  eventInsightEventLayer.clearLayers();
  eventInsightStrongLayer.clearLayers();
  eventInsightPlateLayer.clearLayers();
  eventInsightFaultLayer.clearLayers();

  window.L.circleMarker([selected.latitude, selected.longitude], {
    radius: 10,
    color: "rgba(255,255,255,0.96)",
    weight: 2,
    fillColor: magnitudeColor(selected.magnitude),
    fillOpacity: 0.95,
  })
    .bindTooltip(`Selected: ${formatMagnitude(selected.magnitude)} ${selected.place || "Unknown"}`, { direction: "top", opacity: 0.95 })
    .addTo(eventInsightEventLayer);

  strongNearby.forEach((row) => {
    if (typeof row.latitude !== "number" || typeof row.longitude !== "number") return;
    window.L.circleMarker([row.latitude, row.longitude], {
      radius: Math.max(5, Math.min(11, 2 + (row.magnitude || 0))),
      color: "rgba(255,255,255,0.9)",
      weight: 1,
      fillColor: magnitudeColor(row.magnitude),
      fillOpacity: 0.8,
    })
      .bindTooltip(`${formatMagnitude(row.magnitude)} · ${row.distanceKm.toFixed(0)} km`, { direction: "top", opacity: 0.9 })
      .addTo(eventInsightStrongLayer);
  });

  let nearestPlateKm = Number.POSITIVE_INFINITY;
  let nearestFaultKm = Number.POSITIVE_INFINITY;
  let nearestFaultFeature = null;

  try {
    const context = await loadTectonicContext();
    const plateFeatures = Array.isArray(context.plates?.features) ? context.plates.features : [];
    const faultFeatures = Array.isArray(context.faults?.features) ? context.faults.features : [];

    const nearbyPlates = plateFeatures
      .map((feature) => ({ feature, km: nearestFeatureDistanceKm(feature, selected.latitude, selected.longitude) }))
      .filter((row) => Number.isFinite(row.km))
      .sort((a, b) => a.km - b.km)
      .slice(0, 16);

    const nearbyFaults = faultFeatures
      .map((feature) => ({ feature, km: nearestFeatureDistanceKm(feature, selected.latitude, selected.longitude) }))
      .filter((row) => Number.isFinite(row.km))
      .sort((a, b) => a.km - b.km)
      .slice(0, 24);

    if (nearbyPlates.length > 0) {
      nearestPlateKm = nearbyPlates[0].km;
      nearbyPlates.forEach((row) => {
        window.L.geoJSON(row.feature, {
          style: { color: "#22d3ee", weight: 2.1, opacity: 0.78 },
        }).addTo(eventInsightPlateLayer);
      });
    }

    if (nearbyFaults.length > 0) {
      nearestFaultKm = nearbyFaults[0].km;
      nearestFaultFeature = nearbyFaults[0].feature;
      nearbyFaults.forEach((row) => {
        window.L.geoJSON(row.feature, {
          style: { color: "#ff7a5f", weight: 1.5, opacity: 0.62 },
        }).addTo(eventInsightFaultLayer);
      });
    }
  } catch (error) {
    // Keep baseline insight even if tectonic context fetch fails.
  }

  const regime = estimateTectonicRegime(selected, nearestFaultKm, nearestPlateKm);
  if (eventInsightRegime) {
    eventInsightRegime.textContent = `Regime: ${regime}`;
  }
  if (eventInsightPlate) {
    eventInsightPlate.textContent = Number.isFinite(nearestPlateKm)
      ? `Plate boundary: ${nearestPlateKm.toFixed(0)} km`
      : "Plate boundary: unavailable";
  }
  if (eventInsightFault) {
    const faultName = nearestFaultFeature ? getFeatureName(nearestFaultFeature) : "Unavailable";
    const faultDistance = Number.isFinite(nearestFaultKm) ? `${nearestFaultKm.toFixed(0)} km` : "n/a";
    eventInsightFault.textContent = `Nearest active fault: ${faultName} (${faultDistance})`;
  }
  if (eventInsightSlip) {
    const slipLabel = nearestFaultFeature ? getSlipRateLabel(nearestFaultFeature) : "Not available";
    eventInsightSlip.textContent = `Slip rate: ${slipLabel}`;
  }

  map.setView([selected.latitude, selected.longitude], 6);
}

function setBarRows(container, rows) {
  if (!container || !barTemplate) {
    return;
  }
  container.innerHTML = "";

  const maxValue = Math.max(1, ...rows.map((row) => row.value));
  rows.forEach((row) => {
    const fragment = barTemplate.content.cloneNode(true);
    const labelEl = fragment.querySelector(".bar-label");
    const fillEl = fragment.querySelector(".bar-fill");
    const valueEl = fragment.querySelector(".bar-value");
    if (!labelEl || !fillEl || !valueEl) {
      return;
    }
    labelEl.textContent = row.label;
    valueEl.textContent = String(row.value);
    fillEl.style.width = `${(row.value / maxValue) * 100}%`;
    fillEl.style.background = row.color || "linear-gradient(90deg, #22d3ee, #f7d21e, #ff7a5f)";
    container.appendChild(fragment);
  });
}

function setBarColumns(container, rows, options = {}) {
  if (!container) {
    return;
  }

  const labelStep = Number.isFinite(options.labelStep) && options.labelStep > 0 ? Math.floor(options.labelStep) : 1;
  const compact = Boolean(options.compact);
  container.innerHTML = "";

  const maxValue = Math.max(1, ...rows.map((row) => row.value));
  container.style.setProperty("--bar-count", String(Math.max(1, rows.length)));

  rows.forEach((row, idx) => {
    const col = document.createElement("div");
    col.className = "bar-col";
    if (compact) {
      col.classList.add("is-compact");
    }
    const titleLabel = row.tooltipLabel || row.label;
    col.title = `${titleLabel}: ${row.value}`;

    const valueEl = document.createElement("div");
    valueEl.className = "bar-col-value";
    valueEl.textContent = String(row.value);

    const trackEl = document.createElement("div");
    trackEl.className = "bar-col-track";

    const fillEl = document.createElement("div");
    fillEl.className = "bar-col-fill";
    fillEl.style.height = `${(row.value / maxValue) * 100}%`;
    fillEl.style.background = row.color || "linear-gradient(0deg, #22d3ee, #f7d21e, #ff7a5f)";
    trackEl.appendChild(fillEl);

    const labelEl = document.createElement("div");
    labelEl.className = "bar-col-label";
    labelEl.textContent = idx % labelStep === 0 ? row.label : "";

    col.appendChild(valueEl);
    col.appendChild(trackEl);
    col.appendChild(labelEl);
    container.appendChild(col);
  });
}

function renderMagnitudeChart(events) {
  const bins = [
    { label: "<2", min: -1, max: 2 },
    { label: "2-3", min: 2, max: 3 },
    { label: "3-4", min: 3, max: 4 },
    { label: "4-5", min: 4, max: 5 },
    { label: "5-6", min: 5, max: 6 },
    { label: "6+", min: 6, max: 99 },
  ];

  const rows = bins.map((bin) => {
    const count = events.filter((event) => {
      const mag = typeof event.magnitude === "number" ? event.magnitude : -1;
      return mag >= bin.min && mag < bin.max;
    }).length;
    return {
      label: bin.label,
      value: count,
      color: `linear-gradient(0deg, ${magnitudeColor(bin.min + 0.2)}, ${magnitudeColor(bin.max - 0.2)})`,
    };
  });

  setBarColumns(magChart, rows);
}

function hourlyActivityColor(value, maxValue) {
  const safeMax = Math.max(1, maxValue);
  const ratio = Math.max(0, Math.min(1, value / safeMax));

  if (ratio < 0.34) {
    // Low activity: keep the bar in cool tones.
    return "linear-gradient(0deg, #22d3ee, #5de4c7)";
  }
  if (ratio < 0.67) {
    // Medium activity: transition to yellow but avoid orange highlights.
    return "linear-gradient(0deg, #22d3ee, #f7d21e)";
  }
  // High activity: warm top for immediate visual emphasis.
  return "linear-gradient(0deg, #22d3ee, #f7d21e, #ff7a5f)";
}

function renderHourlyChart(events) {
  const now = new Date();
  const rows = [];

  for (let i = 23; i >= 0; i -= 1) {
    const slot = new Date(now);
    slot.setUTCHours(now.getUTCHours() - i, 0, 0, 0);
    const slotStart = slot.getTime();
    const slotEnd = slotStart + 60 * 60 * 1000;

    const count = events.filter((event) => {
      const eventTime = event.event_time_utc ? new Date(event.event_time_utc).getTime() : 0;
      return eventTime >= slotStart && eventTime < slotEnd;
    }).length;

    rows.push({
      label: i === 0 ? "now" : `${i}h`,
      tooltipLabel: `${String(slot.getUTCHours()).padStart(2, "0")}:00 UTC · ${i === 0 ? "current hour" : `${i}h ago`}`,
      value: count,
    });
  }

  const maxCount = Math.max(1, ...rows.map((row) => row.value));
  rows.forEach((row) => {
    row.color = hourlyActivityColor(row.value, maxCount);
  });

  setBarColumns(hourlyChart, rows, { compact: true, labelStep: 2 });
}

function renderRegions(events) {
  if (!regionsList) {
    return;
  }

  const counter = new Map();
  events.forEach((event) => {
    const region = parseRegion(event.place);
    counter.set(region, (counter.get(region) || 0) + 1);
  });

  const top = [...counter.entries()].sort((a, b) => b[1] - a[1]).slice(0, 8);
  if (top.length === 0) {
    regionsList.innerHTML = "<li>No regions available.</li>";
    return;
  }

  regionsList.innerHTML = top
    .map(
      ([region, count]) =>
        `<li class="region-row"><span>${region}</span><strong>${count}</strong></li>`,
    )
    .join("");
}

function renderPriorityEvents(events) {
  if (!eventsList) {
    return;
  }

  const listMode = (eventsList.dataset.order || "priority").toLowerCase();
  if (listMode === "chronological") {
    const rows = [...events]
      .sort((a, b) => {
        const aTime = a.event_time_utc ? new Date(a.event_time_utc).getTime() : 0;
        const bTime = b.event_time_utc ? new Date(b.event_time_utc).getTime() : 0;
        return bTime - aTime;
      });

    if (rows.length === 0) {
      eventsList.innerHTML = "<li class='event-item'>No recent events available.</li>";
      return;
    }

    eventsList.innerHTML = rows
      .map((event) => {
        const mag = formatMagnitude(event.magnitude);
        const color = magnitudeColor(event.magnitude);
        const depth = typeof event.depth_km === "number" ? `${event.depth_km.toFixed(1)} km` : "N/A depth";
        const time = event.event_time_utc
          ? new Date(event.event_time_utc).toLocaleString()
          : "Unknown time";
        const eventKey = getEventKey(event);
        const detailUrl = eventDetailUrl(event);
        return `
        <li class="event-item event-item-compact event-item-clickable" data-event-key="${eventKey}" data-event-url="${detailUrl}">
          <div class="event-main">
            <span class="event-mag" style="color:${color}">${mag}</span>
            <span class="event-place">${event.place}</span>
          </div>
          <div class="event-meta">${depth} · ${time}</div>
        </li>
      `;
      })
      .join("");
    return;
  }

  const distanceKm = (a, b) => {
    if (
      typeof a?.latitude !== "number" ||
      typeof a?.longitude !== "number" ||
      typeof b?.latitude !== "number" ||
      typeof b?.longitude !== "number"
    ) {
      return Number.POSITIVE_INFINITY;
    }
    const toRad = (v) => (v * Math.PI) / 180;
    const dLat = toRad(b.latitude - a.latitude);
    const dLon = toRad(b.longitude - a.longitude);
    const lat1 = toRad(a.latitude);
    const lat2 = toRad(b.latitude);
    const x =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
    return 6371 * (2 * Math.atan2(Math.sqrt(x), Math.sqrt(1 - x)));
  };

  const isLikelyDuplicate = (a, b) => {
    const magA = typeof a?.magnitude === "number" ? a.magnitude : null;
    const magB = typeof b?.magnitude === "number" ? b.magnitude : null;
    if (magA !== null && magB !== null && Math.abs(magA - magB) > 0.2) {
      return false;
    }
    const refMag = Math.max(magA ?? 0, magB ?? 0);
    const maxTimeSec = refMag >= 4.8 ? 12 : 1;
    const maxDistKm = refMag >= 4.8 ? 120 : 35;
    const maxDepthDiff = refMag >= 4.8 ? 60 : 35;

    const tsA = a?.event_time_utc ? new Date(a.event_time_utc).getTime() : NaN;
    const tsB = b?.event_time_utc ? new Date(b.event_time_utc).getTime() : NaN;
    if (!Number.isFinite(tsA) || !Number.isFinite(tsB)) {
      return false;
    }
    const diffSec = Math.abs(Math.round((tsA - tsB) / 1000));
    if (diffSec > maxTimeSec) {
      return false;
    }

    const depthA = typeof a?.depth_km === "number" ? Math.abs(a.depth_km) : null;
    const depthB = typeof b?.depth_km === "number" ? Math.abs(b.depth_km) : null;
    if (depthA !== null && depthB !== null && Math.abs(depthA - depthB) > maxDepthDiff) {
      return false;
    }

    return distanceKm(a, b) <= maxDistKm;
  };

  const deduped = [];
  events.forEach((event) => {
    const duplicateIndex = deduped.findIndex((current) => isLikelyDuplicate(current, event));
    if (duplicateIndex === -1) {
      deduped.push(event);
      return;
    }
    const current = deduped[duplicateIndex];
    const currentMag = typeof current?.magnitude === "number" ? current.magnitude : -1;
    const nextMag = typeof event?.magnitude === "number" ? event.magnitude : -1;
    if (nextMag > currentMag) {
      deduped[duplicateIndex] = event;
    }
  });

  const ordered = [...deduped].sort((a, b) => {
    const magA = typeof a.magnitude === "number" ? a.magnitude : -1;
    const magB = typeof b.magnitude === "number" ? b.magnitude : -1;
    return magB - magA;
  });

  const rows = ordered.slice(0, 9);
  if (rows.length === 0) {
    eventsList.innerHTML = "<li class='event-item'>No recent events available.</li>";
    return;
  }

  eventsList.innerHTML = rows
    .map((event, index) => {
      const mag = formatMagnitude(event.magnitude);
      const color = magnitudeColor(event.magnitude);
      const depth = typeof event.depth_km === "number" ? `${event.depth_km.toFixed(1)} km` : "N/A depth";
      const time = event.event_time_utc
        ? new Date(event.event_time_utc).toLocaleString()
        : "Unknown time";
      const rowClass = index === 0 ? "event-item event-item-featured" : "event-item event-item-compact";
      const eventKey = getEventKey(event);
      const detailUrl = eventDetailUrl(event);
      return `
        <li class="${rowClass} event-item-clickable" data-event-key="${eventKey}" data-event-url="${detailUrl}">
          <div class="event-main">
            <span class="event-mag" style="color:${color}">${mag}</span>
            <span class="event-place">${event.place}</span>
          </div>
          <div class="event-meta">${depth} · ${time}</div>
        </li>
      `;
    })
    .join("");
}

function renderTimeline(events) {
  if (!timelineList) {
    return;
  }

  const sortedRows = [...events]
    .sort((a, b) => {
      const aTime = a.event_time_utc ? new Date(a.event_time_utc).getTime() : 0;
      const bTime = b.event_time_utc ? new Date(b.event_time_utc).getTime() : 0;
      return bTime - aTime;
    });
  timelineEventsCache = sortedRows;

  const visibleRows = sortedRows.slice(0, timelineExpanded ? 20 : 8);

  if (visibleRows.length === 0) {
    timelineList.innerHTML = "<div class='timeline-row'>No events yet.</div>";
    if (timelineMoreButton) {
      timelineMoreButton.hidden = true;
    }
    return;
  }

  timelineList.innerHTML = visibleRows
    .map((event) => {
      const time = event.event_time_utc
        ? new Date(event.event_time_utc).toLocaleString()
        : "Unknown time";
      const depth = typeof event.depth_km === "number" ? `${event.depth_km.toFixed(1)} km` : "N/A";
      const eventKey = getEventKey(event);
      const detailUrl = eventDetailUrl(event);
      return `
      <div class="timeline-row event-item-clickable" data-event-key="${eventKey}" data-event-url="${detailUrl}">
        <div class="timeline-head">
          <strong style="color:${magnitudeColor(event.magnitude)}">${formatMagnitude(event.magnitude)}</strong>
          <span>${time}</span>
        </div>
        <div class="timeline-place">${event.place} · ${depth}</div>
      </div>
    `;
    })
    .join("");

  if (timelineMoreButton) {
    const hasMore = sortedRows.length > 8;
    timelineMoreButton.hidden = !hasMore;
    timelineMoreButton.textContent = timelineExpanded ? "Show less" : "Load more";
  }
}

function renderKpis(events, payload) {
  const strongest = [...events].sort((a, b) => (b.magnitude || 0) - (a.magnitude || 0))[0] || null;
  const significant = events.filter((event) => typeof event.magnitude === "number" && event.magnitude >= 5).length;

  if (kpiTotal) {
    kpiTotal.textContent = String(events.length);
  }
  if (kpiStrongest) {
    kpiStrongest.textContent = strongest ? formatMagnitude(strongest.magnitude) : "--";
    kpiStrongest.style.color = strongest ? magnitudeColor(strongest.magnitude) : "";
  }
  if (kpiStrongestPlace) {
    kpiStrongestPlace.textContent = strongest ? strongest.place : "No events";
  }
  if (kpiSignificant) {
    kpiSignificant.textContent = String(significant);
  }
  if (kpiUpdated) {
    kpiUpdated.textContent = payload.generated_at
      ? new Date(payload.generated_at).toLocaleTimeString()
      : "--";
  }
  if (kpiSource) {
    const mode = payload.from_cache ? "cache" : "live";
    kpiSource.textContent = `Source: ${payload.provider || "Quakrs API"} (${mode})`;
  }
}

function focusEventOnMap(eventKey, shouldZoomIn = false) {
  if (!eventKey) {
    return;
  }
  selectedEventKey = eventKey;
  renderEventInsight(eventKey);

  if (!leafletMap) {
    return;
  }
  const mapPoint = mapEventLookup.get(eventKey);
  if (!mapPoint) {
    return;
  }

  const currentZoom = leafletMap.getZoom();
  const targetZoom = shouldZoomIn ? Math.min(8, Math.max(4, currentZoom + 1)) : Math.max(5, currentZoom);
  leafletMap.setView([mapPoint.lat, mapPoint.lon], targetZoom);
  mapPoint.marker.openTooltip();
}

function renderHomeSnapshot(events, payload) {
  if (!homeSnapshot) {
    return;
  }

  const strongest = [...events].sort((a, b) => (b.magnitude || 0) - (a.magnitude || 0))[0] || null;
  const significant = events.filter((event) => typeof event.magnitude === "number" && event.magnitude >= 5).length;
  const last1hThreshold = Date.now() - 60 * 60 * 1000;
  const last6hThreshold = Date.now() - 6 * 60 * 60 * 1000;
  const last1hCount = events.filter((event) => {
    if (!event.event_time_utc) {
      return false;
    }
    return new Date(event.event_time_utc).getTime() >= last1hThreshold;
  }).length;
  const last6hCount = events.filter((event) => {
    if (!event.event_time_utc) {
      return false;
    }
    return new Date(event.event_time_utc).getTime() >= last6hThreshold;
  }).length;
  latestHomeSituationContext = inferHomeSituationContext(events);
  renderHomeSituationContext(latestHomeSituationContext);
  renderHomeAiInsight(latestHomeSituationContext, events);
  renderHomeContextEarthquakeRow(events, latestHomeSituationContext);
  applyHomeLiveMode(latestHomeSituationContext);

  if (homeKpiTotal) {
    homeKpiTotal.textContent = String(events.length);
  }
  setHomeMirror("total", String(events.length));
  if (homeKpiStrongest) {
    homeKpiStrongest.textContent = strongest ? formatMagnitude(strongest.magnitude) : "--";
    homeKpiStrongest.style.color = strongest ? magnitudeColor(strongest.magnitude) : "";
  }
  setHomeMirror("strongest", strongest ? formatMagnitude(strongest.magnitude) : "--", strongest ? magnitudeColor(strongest.magnitude) : "");
  if (homeKpiStrongestPlace) {
    homeKpiStrongestPlace.textContent = strongest ? shortPlaceLabel(strongest.place) : "No data";
  }
  setHomeMirror("strongest-place", strongest ? shortPlaceLabel(strongest.place) : "No data");
  if (homeKpiSignificant) {
    homeKpiSignificant.textContent = String(significant);
  }
  setHomeMirror("significant", String(significant));
  if (homeKpiUpdated) {
    homeKpiUpdated.textContent = payload.generated_at
      ? new Date(payload.generated_at).toLocaleTimeString()
      : "--";
  }
  setHomeMirror("updated", payload.generated_at ? new Date(payload.generated_at).toLocaleTimeString() : "--");
  if (homeKpiSource) {
    homeKpiSource.textContent = `Source: ${payload.provider || "Quakrs API"}`;
  }
  setHomeMirror("source", payload.provider || "Quakrs API");

  if (footerUpdateInterval) {
    footerUpdateInterval.textContent = "Update interval: ~3 min";
  }
  if (footerDataLatency) {
    const generatedAt = payload.generated_at ? new Date(payload.generated_at).getTime() : NaN;
    const latencySeconds = Number.isNaN(generatedAt) ? null : Math.max(0, Math.floor((Date.now() - generatedAt) / 1000));
    if (latencySeconds === null) {
      footerDataLatency.textContent = "Data latency: unavailable";
    } else if (latencySeconds < 60) {
      footerDataLatency.textContent = `Data latency: ${latencySeconds}s`;
    } else {
      footerDataLatency.textContent = `Data latency: ${Math.floor(latencySeconds / 60)}m`;
    }
  }

  homePulseState.quakeBrief =
    `EQ: ${last1hCount} last hour, ${last6hCount} in 6h, ${significant} M5+.`;
  updateHomeBrief();
  renderHazardStatusCards();

  if (homeMapFeedList) {
    const selectedRows = [...events]
      .sort((a, b) => {
        const aTime = a?.event_time_utc ? new Date(a.event_time_utc).getTime() : 0;
        const bTime = b?.event_time_utc ? new Date(b.event_time_utc).getTime() : 0;
        return bTime - aTime;
      });

    const listSource = homeMapViewportOnly ? filterEventsToCurrentMapViewport(selectedRows) : selectedRows;
    const listRows = listSource.slice(0, 24);

    if (listRows.length === 0) {
      homeMapFeedList.innerHTML =
        homeMapViewportOnly && leafletMap
          ? "<li class='snapshot-row'>No earthquakes in current map view.</li>"
          : "<li class='snapshot-row'>No earthquake feed available.</li>";
    } else {
      homeMapFeedList.innerHTML = listRows
        .map((event) => {
          const eventDate = event.event_time_utc ? new Date(event.event_time_utc) : null;
          const timeLabel = eventDate
            ? eventDate.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit", hour12: false })
            : "--:--";
          return `
            <li class="snapshot-row">
              <div class="snapshot-main">
                <strong style="color:${magnitudeColor(event.magnitude)}">${formatMagnitude(event.magnitude)}</strong>
                <span>${shortPlaceLabel(event.place)}</span>
              </div>
              <div class="snapshot-meta">${timeLabel}</div>
            </li>
          `;
        })
        .join("");
    }
  }
}

function renderVolcanoSnapshot(payload) {
  if (!homeVolcanoList && !homeSnapshotBrief && !homeSourcesLine) {
    return;
  }

  const reports = typeof payload.reports_count === "number" ? payload.reports_count : 0;
  const volcanoes = typeof payload.volcanoes_count === "number" ? payload.volcanoes_count : 0;
  const newEruptive = typeof payload.new_eruptive_count === "number" ? payload.new_eruptive_count : 0;
  const events = Array.isArray(payload.events) ? payload.events : [];
  const latest = events[0] || null;
  const topCountriesCounter = new Map();
  events.forEach((event) => {
    const country = typeof event.country === "string" && event.country.trim() !== "" ? event.country.trim() : null;
    if (!country) {
      return;
    }
    topCountriesCounter.set(country, (topCountriesCounter.get(country) || 0) + 1);
  });
  const topCountries = [...topCountriesCounter.entries()]
    .sort((a, b) => b[1] - a[1])
    .slice(0, 3)
    .map(([country, count]) => `${country} (${count})`);
  const newEruptiveVolcanoes = events
    .filter((event) => Boolean(event.is_new_eruptive))
    .slice(0, 3)
    .map((event) => {
      const volcano = shortPlaceLabel(event.volcano || event.title || "Unknown");
      const country = event.country ? String(event.country) : null;
      return country ? `${volcano} (${country})` : volcano;
    });

  homeHazardsState.volcanoReports = reports;
  homeHazardsState.volcanoes = volcanoes;
  homeHazardsState.newEruptive = newEruptive;
  homeHazardsState.volcanoEvents = events;
  homeHazardsState.latestVolcano = latest ? shortPlaceLabel(latest.volcano || latest.title) : "No recent item";
  homeHazardsState.latestCountry = latest && latest.country ? latest.country : "--";
  homeHazardsState.latestVolcanoStatus = latest
    ? (latest.is_new_eruptive ? "New eruptive activity" : "Ongoing volcanic activity")
    : "Weekly activity update";
  homeHazardsState.latestVolcanoTime = latest && latest.event_time_utc ? new Date(latest.event_time_utc) : null;
  homeHazardsState.topVolcanoCountries = topCountries;
  homeHazardsState.newEruptiveVolcanoes = newEruptiveVolcanoes;
  renderVolcanoList();
  renderHazardStatusCards();

  homePulseState.volcanoBrief = `Volc: ${volcanoes} tracked, ${newEruptive} new eruptive.`;
  updateHomeBrief();
  applyHomeLiveMode(latestHomeSituationContext);
}

function applyEarthquakeView() {
  if (!latestEarthquakePayload) {
    return;
  }

  eventLookupByKey.clear();
  allEarthquakeEvents.forEach((event) => {
    eventLookupByKey.set(getEventKey(event), event);
  });

  const filteredEvents = getFilteredEarthquakeEvents();
  if (feedMeta) {
    const updatedAt = latestEarthquakePayload?.generated_at || latestEarthquakePayload?.feed_updated_at || null;
    const updatedText = updatedAt ? ` · ${formatUpdatedAgo(updatedAt)}` : "";
    const filterText = activeMagnitudeBand ? ` · filtered ${getMagnitudeBandLabel(activeMagnitudeBand)}` : "";
    feedMeta.textContent = `${filteredEvents.length}/${allEarthquakeEvents.length} events shown${filterText}${updatedText}`;
  }

  renderKpis(filteredEvents, latestEarthquakePayload);
  renderHomeSnapshot(filteredEvents, latestEarthquakePayload);
  renderMap(filteredEvents);
  renderMagnitudeChart(filteredEvents);
  renderHourlyChart(filteredEvents);
  renderRegions(filteredEvents);
  renderPriorityEvents(filteredEvents);
  renderTimeline(filteredEvents);
  if ((isEarthquakesPage || isMapsPage) && eventsList) {
    window.requestAnimationFrame(() => syncEarthquakesFeedHeight());
  }
  syncLeafletMapSize();

  if (eventInsightPanel && filteredEvents.length > 0) {
    const fallbackKey = getEventKey(filteredEvents[0]);
    const currentKey = selectedEventKey && eventLookupByKey.has(selectedEventKey) ? selectedEventKey : fallbackKey;
    renderEventInsight(currentKey);
  }
}

async function loadEarthquakes() {
  const hasEarthquakeTargets =
    !!homeSnapshot ||
    !!eventsList ||
    !!mapLeafletContainer ||
    !!mapPoints ||
    !!magChart ||
    !!hourlyChart ||
    !!regionsList ||
    !!timelineList ||
    !!kpiTotal;
  if (!hasEarthquakeTargets) {
    return;
  }

  try {
    const response = await fetchApiJson("/api/earthquakes.php", FORCE_LIVE_FEEDS);

    if (!response.ok) {
      throw new Error("Feed request failed");
    }

    const payload = await response.json();
    const events = Array.isArray(payload.events) ? payload.events : [];
    const providers = Array.isArray(payload.providers) && payload.providers.length > 0
      ? payload.providers.join(" + ")
      : payload.provider || "Quakrs API";

    const incomingKeys = new Set(events.map((event) => getEventKey(event)));
    if (hasHydratedPayloadKeys) {
      pendingLeafletPulseEvents = events.filter((event) => !previousPayloadEventKeys.has(getEventKey(event)));
    } else {
      pendingLeafletPulseEvents = [];
      hasHydratedPayloadKeys = true;
    }
    previousPayloadEventKeys = incomingKeys;

    latestEarthquakePayload = payload;
    allEarthquakeEvents = events;
    applyEarthquakeView();
  } catch (error) {
    allEarthquakeEvents = [];
    pendingLeafletPulseEvents = [];
    if (feedMeta) {
      feedMeta.textContent = "Feed unavailable right now.";
    }
    if (footerDataLatency) {
      footerDataLatency.textContent = "Data latency: unavailable";
    }
    homePulseState.quakeBrief = "Earthquake pulse temporarily unavailable.";
    updateHomeBrief();
    if (homeSignificantList) {
      homeSignificantList.innerHTML = "<li class='snapshot-row'>Significant events unavailable right now.</li>";
    }
    if (homeMapFeedList) {
      homeMapFeedList.innerHTML = "<li class='snapshot-row'>Earthquake feed unavailable right now.</li>";
    }
    if (homeContextTitle) {
      homeContextTitle.textContent = "Global watch in progress";
    }
    if (homeContextMode) {
      homeContextMode.textContent = "Feed unavailable";
    }
    if (homeContextSummary) {
      homeContextSummary.textContent = "Earthquake feed unavailable in this runtime. Check local API routing/cache.";
    }
    if (homeContextRegion) {
      homeContextRegion.textContent = "Area: --";
    }
    if (homeContextWindow) {
      homeContextWindow.textContent = "Window: --";
    }
    if (homeContextPressure) {
      homeContextPressure.textContent = "Intensity: --";
    }
    if (homeContextProbability) {
      homeContextProbability.textContent = "Activity index: --";
    }
    renderHomeContextGenericRow("Highlighted earthquakes", [
      { lead: "--", label: "Feed unavailable", meta: "Earthquake API not responding" },
    ]);
    latestHomeSituationContext = null;
    latestHomeLiveMode = "priority-fallback";
    applyHomeLiveMode(latestHomeSituationContext);
    if (eventsList) {
      eventsList.innerHTML = "<li class='event-item'>Unable to load earthquake data.</li>";
    }
  }
}

async function loadVolcanoes() {
  if (!homeVolcanoList && !homeSnapshotBrief && !homeSourcesLine) {
    return;
  }

  try {
    const response = await fetchApiJson("/api/volcanoes.php", FORCE_LIVE_FEEDS);
    if (!response.ok) {
      throw new Error("Volcano feed request failed");
    }

    const payload = await response.json();
    renderVolcanoSnapshot(payload);
  } catch (error) {
    homeHazardsState.volcanoReports = null;
    homeHazardsState.volcanoes = null;
    homeHazardsState.newEruptive = null;
    homeHazardsState.volcanoEvents = [];
    homeHazardsState.latestVolcano = "Volcano feed unavailable";
    homeHazardsState.latestCountry = "--";
    homeHazardsState.latestVolcanoStatus = "Volcano feed unavailable";
    homeHazardsState.latestVolcanoTime = null;
    homeHazardsState.topVolcanoCountries = [];
    homeHazardsState.newEruptiveVolcanoes = [];
    renderVolcanoList();
    renderHazardStatusCards();
    homePulseState.volcanoBrief = "Volc unavailable.";
    updateHomeBrief();
    applyHomeLiveMode(latestHomeSituationContext);
  }
}

function renderTremorSnapshot(payload) {
  if (!homeClustersList && !homeSnapshotBrief) {
    return;
  }

  const signals = typeof payload.signals_count === "number" ? payload.signals_count : 0;
  const clustersCount = typeof payload.clusters_count === "number" ? payload.clusters_count : 0;
  const peakHour = payload.peak_hour_utc || "--:00";
  const peakCount = typeof payload.peak_hour_count === "number" ? payload.peak_hour_count : 0;
  const clusters = Array.isArray(payload.clusters) ? payload.clusters.slice(0, 3) : [];

  homeHazardsState.tremorSignals = signals;
  homeHazardsState.tremorClusters = clustersCount;
  homeHazardsState.tremorPeakHour = peakHour;
  homeHazardsState.tremorPeakCount = peakCount;
  homeHazardsState.tremorTopClusters = clusters;
  renderClustersList();
  renderHazardStatusCards();

  homePulseState.tremorBrief = `Tremor: ${signals} signals, ${clustersCount} clusters.`;
  updateHomeBrief();
  applyHomeLiveMode(latestHomeSituationContext);
}

async function loadTremors() {
  if (!homeClustersList && !homeSnapshotBrief && !homeSourcesLine) {
    return;
  }

  try {
    const response = await fetchApiJson("/api/tremors.php", FORCE_LIVE_FEEDS);
    if (!response.ok) {
      throw new Error("Tremor feed request failed");
    }

    const payload = await response.json();
    renderTremorSnapshot(payload);
  } catch (error) {
    homeHazardsState.tremorSignals = null;
    homeHazardsState.tremorClusters = null;
    homeHazardsState.tremorPeakHour = null;
    homeHazardsState.tremorPeakCount = null;
    homeHazardsState.tremorTopClusters = [];
    renderClustersList();
    renderHazardStatusCards();
    homePulseState.tremorBrief = "Tremor unavailable.";
    updateHomeBrief();
    applyHomeLiveMode(latestHomeSituationContext);
  }
}

function renderTsunamiSnapshot(payload) {
  if (!homeVolcanoList && !homeSnapshotBrief) {
    return;
  }

  const alertsCount = typeof payload.alerts_count === "number" ? payload.alerts_count : 0;
  const highestLevel = payload.highest_level || "None";
  homeHazardsState.tsunamiAlerts = alertsCount;
  homeHazardsState.tsunamiLevel = highestLevel;
  homeHazardsState.tsunamiRegions = typeof payload.regions_count === "number" ? payload.regions_count : 0;
  homeHazardsState.tsunamiPayload = payload;
  renderVolcanoList();
  renderHazardStatusCards();
  applyHomeLiveMode(latestHomeSituationContext);
}

async function loadTsunami() {
  if (!homeVolcanoList && !homeSnapshotBrief) {
    return;
  }

  try {
    const response = await fetchApiJson("/api/tsunami.php", FORCE_LIVE_FEEDS);
    if (!response.ok) {
      throw new Error("Tsunami feed request failed");
    }
    const payload = await response.json();
    renderTsunamiSnapshot(payload);
  } catch (error) {
    homeHazardsState.tsunamiAlerts = null;
    homeHazardsState.tsunamiLevel = null;
    homeHazardsState.tsunamiRegions = null;
    homeHazardsState.tsunamiPayload = null;
    renderVolcanoList();
    renderHazardStatusCards();
    applyHomeLiveMode(latestHomeSituationContext);
  }
}

function renderSpaceWeatherSnapshot(payload) {
  if (!homeVolcanoList && !homeSnapshotBrief) {
    return;
  }

  const kpCurrent = typeof payload.kp_index_current === "number" ? payload.kp_index_current : null;
  homeHazardsState.spaceKp = kpCurrent;
  homeHazardsState.spaceStormLevel = payload.storm_level || payload.kp_band_current || null;
  homeHazardsState.spacePayload = payload;
  renderVolcanoList();
  renderHazardStatusCards();
  applyHomeLiveMode(latestHomeSituationContext);
}

async function loadSpaceWeather() {
  if (!homeVolcanoList && !homeSnapshotBrief) {
    return;
  }

  try {
    const response = await fetchApiJson("/api/space-weather.php", FORCE_LIVE_FEEDS);
    if (!response.ok) {
      throw new Error("Space weather feed request failed");
    }
    const payload = await response.json();
    renderSpaceWeatherSnapshot(payload);
  } catch (error) {
    homeHazardsState.spaceKp = null;
    homeHazardsState.spaceStormLevel = null;
    homeHazardsState.spacePayload = null;
    renderVolcanoList();
    renderHazardStatusCards();
    applyHomeLiveMode(latestHomeSituationContext);
  }
}

drawGraticule();
drawContinents();
if (homeMapViewportOnlyToggle) {
  homeMapViewportOnlyToggle.addEventListener("change", () => {
    homeMapViewportOnly = Boolean(homeMapViewportOnlyToggle.checked);
    if (latestEarthquakePayload) {
      applyEarthquakeView();
    }
  });
}
if (bootstrapPayloads && !SKIP_BOOTSTRAP_PAYLOADS) {
  const eqBootstrap = bootstrapPayloads.earthquakes;
  if (eqBootstrap && Array.isArray(eqBootstrap.events)) {
    previousPayloadEventKeys = new Set(eqBootstrap.events.map((event) => getEventKey(event)));
    hasHydratedPayloadKeys = true;
    latestEarthquakePayload = eqBootstrap;
    allEarthquakeEvents = eqBootstrap.events;
    applyEarthquakeView();
  }

  const volcanoBootstrap = bootstrapPayloads.volcanoes;
  if (volcanoBootstrap && typeof volcanoBootstrap === "object") {
    renderVolcanoSnapshot(volcanoBootstrap);
  }

  const tremorBootstrap = bootstrapPayloads.tremors;
  if (tremorBootstrap && typeof tremorBootstrap === "object") {
    renderTremorSnapshot(tremorBootstrap);
  }

  const tsunamiBootstrap = bootstrapPayloads.tsunami;
  if (tsunamiBootstrap && typeof tsunamiBootstrap === "object") {
    renderTsunamiSnapshot(tsunamiBootstrap);
  }

  const spaceWeatherBootstrap = bootstrapPayloads["space-weather"];
  if (spaceWeatherBootstrap && typeof spaceWeatherBootstrap === "object") {
    renderSpaceWeatherSnapshot(spaceWeatherBootstrap);
  }
}
initMobileNavToggle();
initMobileNavDropdowns();
loadEarthquakes();
loadVolcanoes();
loadTremors();
loadTsunami();
loadSpaceWeather();

const LIVE_REFRESH_INTERVAL_MS = 60000;
let liveRefreshInFlight = false;

async function refreshLiveFeeds() {
  if (liveRefreshInFlight) {
    return;
  }
  liveRefreshInFlight = true;
  try {
    await Promise.allSettled([
      loadEarthquakes(),
      loadVolcanoes(),
      loadTremors(),
      loadTsunami(),
      loadSpaceWeather(),
    ]);
  } finally {
    liveRefreshInFlight = false;
  }
}

if (typeof window !== "undefined") {
  window.setInterval(() => {
    if (typeof document !== "undefined" && document.hidden) {
      return;
    }
    void refreshLiveFeeds();
  }, LIVE_REFRESH_INTERVAL_MS);
}

timelineMoreButton?.addEventListener("click", () => {
  timelineExpanded = !timelineExpanded;
  renderTimeline(timelineEventsCache);
});

mapFilterButtons.forEach((button) => {
  button.addEventListener("click", () => {
    const band = button.dataset.band || null;
    const nextBand = activeMagnitudeBand === band ? null : band;
    setMagnitudeFilterState(nextBand);
    applyEarthquakeView();
  });
});

globalThemeToggle?.addEventListener("click", () => {
  leafletDarkMode = !leafletDarkMode;
  syncThemeToggleButton();
  syncLeafletTheme();
  if (latestEarthquakePayload) {
    applyEarthquakeView();
  }
  syncInsightMapTheme();
});

syncThemeToggleButton();

if (typeof window !== "undefined") {
  window.addEventListener("resize", () => {
    syncEarthquakesFeedHeight();
    syncLeafletMapSize();
  });
}

document.addEventListener("click", (event) => {
  const target = event.target;
  if (!(target instanceof Element)) {
    return;
  }

  const row = target.closest(".event-item-clickable");
  if (!row) {
    return;
  }

  const eventKey = row.getAttribute("data-event-key");
  const eventUrl = row.getAttribute("data-event-url");
  const isLiveFeedRow = !!row.closest("#events-list.live-feed-scroll");
  if (eventUrl && isEarthquakesPage && isLiveFeedRow) {
    window.location.href = eventUrl;
    return;
  }
  if (!eventKey) {
    return;
  }
  focusEventOnMap(eventKey, false);
});
