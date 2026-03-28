<?php
declare(strict_types=1);

$currentPage = $currentPage ?? 'home';
$currentLocale = qk_locale();
$availableLocales = qk_supported_locales();

$menuItems = [
    [
        'key' => 'live',
        'label' => qk_t('nav.live'),
        'children' => [
            ['key' => 'home', 'label' => qk_t('nav.live'), 'href' => '/'],
            ['key' => 'situation', 'label' => qk_t('nav.situation'), 'href' => '/situation.php'],
            ['key' => 'timeline', 'label' => qk_t('nav.timeline'), 'href' => '/timeline.php'],
            ['key' => 'alerts', 'label' => qk_t('nav.alerts'), 'href' => '/alerts.php'],
        ],
    ],
    [
        'key' => 'monitors',
        'label' => qk_t('nav.monitors'),
        'children' => [
            ['key' => 'earthquakes', 'label' => qk_t('nav.earthquakes'), 'href' => '/earthquakes.php'],
            ['key' => 'aftershocks', 'label' => qk_t('nav.aftershocks'), 'href' => '/aftershocks.php'],
            ['key' => 'volcanoes', 'label' => qk_t('nav.volcanoes'), 'href' => '/volcanoes.php'],
            ['key' => 'tsunami-alerts', 'label' => qk_t('nav.tsunami_alerts'), 'href' => '/tsunami.php'],
            ['key' => 'space-weather', 'label' => qk_t('nav.space_weather'), 'href' => '/space-weather.php'],
        ],
    ],
    [
        'key' => 'maps',
        'label' => qk_t('nav.maps'),
        'children' => [
            ['key' => 'maps', 'label' => qk_t('nav.priority_map'), 'href' => '/maps.php'],
            ['key' => 'maps-heatmap', 'label' => qk_t('nav.heatmap'), 'href' => '/maps-heatmap.php'],
            ['key' => 'maps-plates', 'label' => qk_t('nav.tectonic_plates'), 'href' => '/maps-plates.php'],
            ['key' => 'maps-depth', 'label' => qk_t('nav.depth_view'), 'href' => '/maps-depth.php'],
        ],
    ],
    [
        'key' => 'cams',
        'label' => qk_t('nav.cams'),
        'children' => [
            ['key' => 'cams-earthquakes', 'label' => qk_t('nav.earthquake_cams'), 'href' => '/cams-earthquakes.php'],
            ['key' => 'cams-weather', 'label' => qk_t('nav.weather_cams'), 'href' => '/cams-weather.php'],
            ['key' => 'cams-space-weather', 'label' => qk_t('nav.space_weather_cams'), 'href' => '/cams-space-weather.php'],
            ['key' => 'cams-tsunami', 'label' => qk_t('nav.tsunami_cams'), 'href' => '/cams-tsunami.php'],
            ['key' => 'cams-volcanoes', 'label' => qk_t('nav.volcano_cams'), 'href' => '/cams-volcanoes.php'],
            ['key' => 'cams-hotspots', 'label' => qk_t('nav.eruption_hotspots'), 'href' => '/cams-hotspots.php'],
        ],
    ],
    [
        'key' => 'data',
        'label' => qk_t('nav.data'),
        'children' => [
            ['key' => 'data-italia', 'label' => qk_t('nav.italy'), 'href' => '/data-italia.php'],
            ['key' => 'archive', 'label' => qk_t('nav.archive'), 'href' => '/archive.php'],
            ['key' => 'data-energy', 'label' => qk_t('nav.energy'), 'href' => '/data-energy.php'],
            ['key' => 'data-reports', 'label' => qk_t('nav.reports'), 'href' => '/data-reports.php'],
            ['key' => 'data-clusters', 'label' => qk_t('nav.clusters'), 'href' => '/data-clusters.php'],
            ['key' => 'data-api', 'label' => qk_t('nav.api'), 'href' => '/data-api.php'],
            ['key' => 'data-status', 'label' => qk_t('nav.data_status'), 'href' => '/data-status.php'],
            ['key' => 'sources-status', 'label' => qk_t('nav.sources_status'), 'href' => '/sources-status.php'],
        ],
    ],
    [
        'key' => 'resources',
        'label' => qk_t('nav.resources'),
        'children' => [
            ['key' => 'resources-safety', 'label' => qk_t('nav.safety_guides'), 'href' => '/resources-safety.php'],
            ['key' => 'resources-glossary', 'label' => qk_t('nav.glossary'), 'href' => '/resources-glossary.php'],
            ['key' => 'resources-bulletins', 'label' => qk_t('nav.bulletins'), 'href' => '/resources-bulletins.php'],
            ['key' => 'priority-levels', 'label' => qk_t('nav.priority_levels'), 'href' => '/priority-levels.php'],
            ['key' => 'about-energy', 'label' => qk_t('nav.about_energy'), 'href' => '/about-energy.php'],
        ],
    ],
    [
        'key' => 'about',
        'label' => qk_t('nav.about'),
        'children' => [
            ['key' => 'about-sources', 'label' => qk_t('nav.sources'), 'href' => '/about-sources.php'],
            ['key' => 'about-methodology', 'label' => qk_t('nav.methodology'), 'href' => '/about-methodology.php'],
            ['key' => 'updates', 'label' => qk_t('nav.updates'), 'href' => '/updates.php'],
        ],
    ],
];

$legacyTopLevelMap = [
    'home' => 'live',
    'situation' => 'live',
    'timeline' => 'live',
    'alerts' => 'live',
    'earthquakes' => 'monitors',
    'aftershocks' => 'monitors',
    'volcanoes' => 'monitors',
    'tsunami-alerts' => 'monitors',
    'space-weather' => 'monitors',
    'maps' => 'maps',
    'maps-heatmap' => 'maps',
    'maps-plates' => 'maps',
    'maps-depth' => 'maps',
    'cams-earthquakes' => 'cams',
    'cams-weather' => 'cams',
    'cams-space-weather' => 'cams',
    'cams-tsunami' => 'cams',
    'cams-volcanoes' => 'cams',
    'cams-hotspots' => 'cams',
    'analytics' => 'data',
    'tremors' => 'data',
    'data-energy' => 'data',
    'data-italia' => 'data',
    'data-italia-statistiche' => 'data',
    'archive' => 'data',
    'data-italia-sciame' => 'data',
    'data-clusters' => 'data',
    'data-archive' => 'data',
    'data-reports' => 'data',
    'data-api' => 'data',
    'data-status' => 'data',
    'sources-status' => 'data',
    'resources-safety' => 'resources',
    'resources-glossary' => 'resources',
    'resources-bulletins' => 'resources',
    'priority-levels' => 'resources',
    'about-energy' => 'resources',
    'about' => 'about',
    'about-sources' => 'about',
    'about-methodology' => 'about',
    'updates' => 'about',
];
?>
<header class="topbar">
  <a class="brand" href="<?= htmlspecialchars(qk_localized_url('/'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?= htmlspecialchars(qk_t('nav.go_home'), ENT_QUOTES, 'UTF-8'); ?>">Quakrs<span>.com</span></a>
  <button
    id="mobile-nav-toggle"
    class="nav-toggle"
    type="button"
    aria-controls="main-nav"
    aria-expanded="false"
    aria-label="<?= htmlspecialchars(qk_t('nav.open_menu'), ENT_QUOTES, 'UTF-8'); ?>"
    data-label-open="<?= htmlspecialchars(qk_t('nav.menu'), ENT_QUOTES, 'UTF-8'); ?>"
    data-label-close="<?= htmlspecialchars(qk_t('nav.close'), ENT_QUOTES, 'UTF-8'); ?>"
    data-aria-open="<?= htmlspecialchars(qk_t('nav.open_menu'), ENT_QUOTES, 'UTF-8'); ?>"
    data-aria-close="<?= htmlspecialchars(qk_t('nav.close_menu'), ENT_QUOTES, 'UTF-8'); ?>"
  >
    <?= htmlspecialchars(qk_t('nav.menu'), ENT_QUOTES, 'UTF-8'); ?>
  </button>
  <nav id="main-nav" class="main-nav" aria-label="<?= htmlspecialchars(qk_t('nav.main_aria'), ENT_QUOTES, 'UTF-8'); ?>">
    <?php foreach ($menuItems as $item): ?>
      <?php
      $topKey = $item['key'];
      $isTopActive = ($legacyTopLevelMap[$currentPage] ?? $currentPage) === $topKey || $currentPage === $topKey;
      ?>
      <?php if (!isset($item['children'])): ?>
        <a class="nav-link <?= $isTopActive ? 'is-active' : ''; ?>" href="<?= htmlspecialchars(qk_localized_url((string) $item['href']), ENT_QUOTES, 'UTF-8'); ?>">
          <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
        </a>
      <?php else: ?>
        <div class="nav-group <?= $isTopActive ? 'is-active' : ''; ?>">
          <button class="nav-link nav-group-trigger" type="button" aria-haspopup="true">
            <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
          </button>
          <div class="nav-submenu" role="menu">
            <?php foreach ($item['children'] as $child): ?>
              <?php $isChildActive = $currentPage === $child['key']; ?>
              <?php if (!empty($child['href'])): ?>
                <a class="nav-sublink <?= $isChildActive ? 'is-active' : ''; ?>" href="<?= htmlspecialchars(qk_localized_url((string) $child['href']), ENT_QUOTES, 'UTF-8'); ?>" role="menuitem">
                  <?= htmlspecialchars($child['label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
              <?php else: ?>
                <span class="nav-sublink is-disabled" aria-disabled="true">
                  <?= htmlspecialchars($child['label'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
    <div class="mobile-nav-tools" aria-label="<?= htmlspecialchars(qk_t('nav.main_aria'), ENT_QUOTES, 'UTF-8'); ?>">
      <button
        class="mobile-nav-tool mobile-nav-search"
        type="button"
        aria-label="<?= htmlspecialchars(qk_t('nav.search_open'), ENT_QUOTES, 'UTF-8'); ?>"
        data-search-trigger="1"
        data-advanced-href="<?= htmlspecialchars(qk_localized_url('/search.php'), ENT_QUOTES, 'UTF-8'); ?>"
      >
        <?= htmlspecialchars(qk_t('nav.search'), ENT_QUOTES, 'UTF-8'); ?>
      </button>
      <a class="mobile-nav-tool" href="<?= htmlspecialchars(qk_localized_url('/my-quakrs.php'), ENT_QUOTES, 'UTF-8'); ?>">
        <?= htmlspecialchars(qk_t('nav.my_quakrs'), ENT_QUOTES, 'UTF-8'); ?>
      </a>
      <div class="mobile-nav-lang">
        <?php foreach ($availableLocales as $localeCode => $localeLabel): ?>
          <a
            class="mobile-nav-tool mobile-nav-lang-link <?= $currentLocale === $localeCode ? 'is-active' : ''; ?>"
            href="<?= htmlspecialchars(qk_locale_switch_url($localeCode), ENT_QUOTES, 'UTF-8'); ?>"
            hreflang="<?= htmlspecialchars($localeCode, ENT_QUOTES, 'UTF-8'); ?>"
            <?= $currentLocale === $localeCode ? 'aria-current="true"' : ''; ?>
          >
            <?= htmlspecialchars($localeLabel, ENT_QUOTES, 'UTF-8'); ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </nav>
  <button
    class="topbar-search"
    type="button"
    aria-label="<?= htmlspecialchars(qk_t('nav.search_open'), ENT_QUOTES, 'UTF-8'); ?>"
    data-search-trigger="1"
    data-advanced-href="<?= htmlspecialchars(qk_localized_url('/search.php'), ENT_QUOTES, 'UTF-8'); ?>"
  >
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
      <circle cx="11" cy="11" r="6.5"></circle>
      <path d="M16.5 16.5 21 21"></path>
    </svg>
    <span class="sr-only"><?= htmlspecialchars(qk_t('nav.search'), ENT_QUOTES, 'UTF-8'); ?></span>
  </button>
  <a
    class="topbar-utility"
    href="<?= htmlspecialchars(qk_localized_url('/my-quakrs.php'), ENT_QUOTES, 'UTF-8'); ?>"
    aria-label="<?= htmlspecialchars(qk_t('nav.my_quakrs'), ENT_QUOTES, 'UTF-8'); ?>"
  >
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
      <circle cx="12" cy="8" r="3.5"></circle>
      <path d="M4 20a8 8 0 0 1 16 0"></path>
    </svg>
    <span class="sr-only"><?= htmlspecialchars(qk_t('nav.my_quakrs'), ENT_QUOTES, 'UTF-8'); ?></span>
  </a>
  <details class="lang-dropdown">
    <summary class="lang-dropdown-trigger" aria-label="<?= htmlspecialchars(qk_t('nav.lang_aria'), ENT_QUOTES, 'UTF-8'); ?>">
      <span><?= htmlspecialchars($availableLocales[$currentLocale] ?? strtoupper($currentLocale), ENT_QUOTES, 'UTF-8'); ?></span>
    </summary>
    <div class="lang-dropdown-menu" role="menu" aria-label="<?= htmlspecialchars(qk_t('nav.lang_aria'), ENT_QUOTES, 'UTF-8'); ?>">
      <?php foreach ($availableLocales as $localeCode => $localeLabel): ?>
        <a
          class="lang-switch-link <?= $currentLocale === $localeCode ? 'is-active' : ''; ?>"
          href="<?= htmlspecialchars(qk_locale_switch_url($localeCode), ENT_QUOTES, 'UTF-8'); ?>"
          hreflang="<?= htmlspecialchars($localeCode, ENT_QUOTES, 'UTF-8'); ?>"
          role="menuitem"
          <?= $currentLocale === $localeCode ? 'aria-current="true"' : ''; ?>
        >
          <span><?= htmlspecialchars($localeLabel, ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </details>
  <a class="cta" href="<?= htmlspecialchars(qk_localized_url('/earthquakes.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(qk_t('nav.live_feed'), ENT_QUOTES, 'UTF-8'); ?></a>
</header>

<dialog id="topbar-search-dialog" class="topbar-search-dialog" aria-label="<?= htmlspecialchars(qk_t('nav.search_open'), ENT_QUOTES, 'UTF-8'); ?>">
  <form class="topbar-search-dialog-card" method="get" action="<?= htmlspecialchars(qk_localized_url('/search.php'), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="topbar-search-dialog-head">
      <h3><?= htmlspecialchars(qk_t('nav.search_open'), ENT_QUOTES, 'UTF-8'); ?></h3>
      <button class="topbar-search-close" type="button" data-search-close><?= htmlspecialchars(qk_t('nav.close'), ENT_QUOTES, 'UTF-8'); ?></button>
    </div>
    <label class="topbar-search-field">
      <span class="sr-only"><?= htmlspecialchars(qk_t('nav.search'), ENT_QUOTES, 'UTF-8'); ?></span>
      <input
        id="topbar-search-input"
        name="q"
        type="search"
        placeholder="<?= htmlspecialchars(qk_t('nav.search_placeholder'), ENT_QUOTES, 'UTF-8'); ?>"
        autocomplete="off"
      />
    </label>
    <div class="topbar-search-actions">
      <button class="btn btn-primary" type="submit"><?= htmlspecialchars(qk_t('nav.search'), ENT_QUOTES, 'UTF-8'); ?></button>
      <a class="btn btn-ghost" href="<?= htmlspecialchars(qk_localized_url('/search.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(qk_t('nav.advanced_search'), ENT_QUOTES, 'UTF-8'); ?></a>
    </div>
  </form>
</dialog>
