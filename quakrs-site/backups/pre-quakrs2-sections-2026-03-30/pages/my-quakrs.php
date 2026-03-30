<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - My Quakrs';
$pageDescription = 'Local-only personal operational preferences for Quakrs.';
$currentPage = 'my-quakrs';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.my_quakrs.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars(qk_t('page.my_quakrs.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.my_quakrs.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Profile mode</p>
    <p class="kpi-value">Local only</p>
    <p class="kpi-note">Preferences are saved in this browser, no account required.</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Sync scope</p>
    <p class="kpi-value">This device</p>
    <p class="kpi-note">No server-side storage in MVP.</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Default hazard</p>
    <p id="myq-kpi-hazard" class="kpi-value">all</p>
    <p class="kpi-note">Used in quick launch links.</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Priority floor</p>
    <p id="myq-kpi-priority" class="kpi-value">all</p>
    <p class="kpi-note">Used in timeline/alerts presets.</p>
  </article>
</section>

<section class="panel">
  <article class="card">
    <div class="feed-head">
      <h3>Preference profile</h3>
      <p class="feed-meta">Set your default operational view. Changes apply to quick links immediately after save.</p>
    </div>

    <form id="myq-form" class="page-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap:0.7rem;">
      <label class="event-item">
        <strong>Preferred language</strong><br />
        <select name="language">
          <option value="auto">Auto (site default)</option>
          <option value="it">Italiano</option>
          <option value="en">English</option>
        </select>
      </label>
      <label class="event-item">
        <strong>Default hazard</strong><br />
        <select name="hazard">
          <option value="all">All hazards</option>
          <option value="earthquakes">Earthquakes</option>
          <option value="volcanoes">Volcanoes</option>
          <option value="tsunami">Tsunami</option>
          <option value="space-weather">Space weather</option>
        </select>
      </label>
      <label class="event-item">
        <strong>Default scope</strong><br />
        <select name="area">
          <option value="all">Global + Italy</option>
          <option value="italy">Italy</option>
          <option value="global">Global only</option>
        </select>
      </label>
      <label class="event-item">
        <strong>Priority floor</strong><br />
        <select name="priority">
          <option value="all">All priorities</option>
          <option value="P1">P1</option>
          <option value="P2">P2</option>
          <option value="P3">P3</option>
        </select>
      </label>
      <label class="event-item">
        <strong>Landing page</strong><br />
        <select name="landing">
          <option value="situation">Situation</option>
          <option value="timeline">Timeline</option>
          <option value="alerts">Alerts</option>
          <option value="archive">Archive</option>
        </select>
      </label>
      <label class="event-item">
        <strong>Timeline window</strong><br />
        <select name="window">
          <option value="1">Last 1h</option>
          <option value="6">Last 6h</option>
          <option value="24">Last 24h</option>
          <option value="72">Last 72h</option>
          <option value="168">Last 7d</option>
        </select>
      </label>
      <label class="event-item">
        <strong>Watch locations</strong><br />
        <input name="watchlist" type="text" placeholder="Italy, Mediterranean, Japan, Iceland" />
      </label>
      <label class="event-item">
        <strong>Compact cards</strong><br />
        <select name="compactCards">
          <option value="off">Off</option>
          <option value="on">On</option>
        </select>
      </label>
    </form>

    <div class="insight-pills" style="margin-top:0.9rem;">
      <button id="myq-save" class="btn btn-primary" type="button">Save preferences</button>
      <button id="myq-reset" class="btn btn-ghost" type="button">Reset defaults</button>
      <button id="myq-open-landing" class="btn btn-ghost" type="button">Open preferred landing</button>
      <span id="myq-status" class="insight-pill">No saved profile yet.</span>
    </div>
  </article>
</section>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Quick launch (your profile)</h3>
    <ul class="events-list">
      <li class="event-item"><a id="myq-link-situation" class="inline-link" href="/situation.php">Situation</a></li>
      <li class="event-item"><a id="myq-link-timeline" class="inline-link" href="/timeline.php">Timeline preset</a></li>
      <li class="event-item"><a id="myq-link-alerts" class="inline-link" href="/alerts.php">Alerts preset</a></li>
      <li class="event-item"><a id="myq-link-archive" class="inline-link" href="/archive.php">Archive preset</a></li>
    </ul>
  </article>
  <article class="card page-card">
    <h3>Profile summary</h3>
    <p id="myq-summary" class="insight-lead">No profile loaded yet.</p>
  </article>
  <article class="card page-card">
    <h3>MVP limits</h3>
    <p class="insight-lead">No account sync, no server profile, no push alerts yet. This first layer focuses on fast personal navigation and filter defaults.</p>
  </article>
</section>

<script>
  (() => {
    const STORAGE_KEY = "quakrs_user_prefs_v1";
    const form = document.querySelector("#myq-form");
    const saveButton = document.querySelector("#myq-save");
    const resetButton = document.querySelector("#myq-reset");
    const openLandingButton = document.querySelector("#myq-open-landing");
    const statusNode = document.querySelector("#myq-status");
    const summaryNode = document.querySelector("#myq-summary");
    const kpiHazard = document.querySelector("#myq-kpi-hazard");
    const kpiPriority = document.querySelector("#myq-kpi-priority");

    const linkSituation = document.querySelector("#myq-link-situation");
    const linkTimeline = document.querySelector("#myq-link-timeline");
    const linkAlerts = document.querySelector("#myq-link-alerts");
    const linkArchive = document.querySelector("#myq-link-archive");

    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    const defaults = {
      language: "auto",
      hazard: "all",
      area: "all",
      priority: "all",
      landing: "situation",
      window: "24",
      watchlist: "",
      compactCards: "off",
    };

    const getFormData = () => {
      const data = new FormData(form);
      return {
        language: String(data.get("language") || defaults.language),
        hazard: String(data.get("hazard") || defaults.hazard),
        area: String(data.get("area") || defaults.area),
        priority: String(data.get("priority") || defaults.priority),
        landing: String(data.get("landing") || defaults.landing),
        window: String(data.get("window") || defaults.window),
        watchlist: String(data.get("watchlist") || "").trim(),
        compactCards: String(data.get("compactCards") || defaults.compactCards),
      };
    };

    const applyToForm = (prefs) => {
      Object.entries({ ...defaults, ...(prefs || {}) }).forEach(([key, value]) => {
        const node = form.elements.namedItem(key);
        if (node instanceof HTMLInputElement || node instanceof HTMLSelectElement) {
          node.value = String(value);
        }
      });
    };

    const readPrefs = () => {
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return { ...defaults };
        const parsed = JSON.parse(raw);
        if (!parsed || typeof parsed !== "object") return { ...defaults };
        return { ...defaults, ...parsed };
      } catch (error) {
        return { ...defaults };
      }
    };

    const savePrefs = (prefs) => {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
    };

    const buildTimelineHref = (prefs) => {
      const params = new URLSearchParams();
      if (prefs.hazard && prefs.hazard !== "all") params.set("hazard", prefs.hazard);
      if (prefs.priority && prefs.priority !== "all") params.set("priority", prefs.priority);
      if (prefs.area && prefs.area !== "all") params.set("area", prefs.area);
      if (prefs.window) params.set("window", prefs.window);
      const qs = params.toString();
      return qs ? `/timeline.php?${qs}` : "/timeline.php";
    };

    const buildAlertsHref = (prefs) => {
      const params = new URLSearchParams();
      if (prefs.hazard && prefs.hazard !== "all") params.set("hazard", prefs.hazard);
      if (prefs.priority && prefs.priority !== "all") params.set("priority", prefs.priority);
      if (prefs.area && prefs.area !== "all") params.set("area", prefs.area);
      const qs = params.toString();
      return qs ? `/alerts.php?${qs}` : "/alerts.php";
    };

    const buildArchiveHref = (prefs) => {
      const params = new URLSearchParams();
      if (prefs.hazard && prefs.hazard !== "all") params.set("hazard", prefs.hazard);
      if (prefs.area && prefs.area !== "all") params.set("area", prefs.area);
      const qs = params.toString();
      return qs ? `/archive.php?${qs}` : "/archive.php";
    };

    const landingHref = (prefs) => {
      if (prefs.landing === "timeline") return buildTimelineHref(prefs);
      if (prefs.landing === "alerts") return buildAlertsHref(prefs);
      if (prefs.landing === "archive") return buildArchiveHref(prefs);
      return "/situation.php";
    };

    const render = (prefs) => {
      kpiHazard.textContent = prefs.hazard || "all";
      kpiPriority.textContent = prefs.priority || "all";

      if (linkSituation instanceof HTMLAnchorElement) linkSituation.href = "/situation.php";
      if (linkTimeline instanceof HTMLAnchorElement) linkTimeline.href = buildTimelineHref(prefs);
      if (linkAlerts instanceof HTMLAnchorElement) linkAlerts.href = buildAlertsHref(prefs);
      if (linkArchive instanceof HTMLAnchorElement) linkArchive.href = buildArchiveHref(prefs);

      const watchLabel = prefs.watchlist ? `watchlist: ${prefs.watchlist}` : "watchlist: none";
      summaryNode.textContent = `hazard ${prefs.hazard} · area ${prefs.area} · priority ${prefs.priority} · landing ${prefs.landing} · ${watchLabel}`;
    };

    const hydrateFromUrl = () => {
      const params = new URLSearchParams(window.location.search);
      if (params.get("reset") === "1") {
        localStorage.removeItem(STORAGE_KEY);
        return { ...defaults };
      }
      return null;
    };

    const bootPrefs = hydrateFromUrl() || readPrefs();
    applyToForm(bootPrefs);
    render(bootPrefs);
    statusNode.textContent = "Profile loaded.";

    saveButton?.addEventListener("click", () => {
      const prefs = getFormData();
      savePrefs(prefs);
      render(prefs);
      statusNode.textContent = "Preferences saved locally.";
    });

    resetButton?.addEventListener("click", () => {
      localStorage.removeItem(STORAGE_KEY);
      applyToForm(defaults);
      render(defaults);
      statusNode.textContent = "Defaults restored.";
    });

    openLandingButton?.addEventListener("click", () => {
      const prefs = getFormData();
      savePrefs(prefs);
      window.location.href = landingHref(prefs);
    });
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
