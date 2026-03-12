<?php
declare(strict_types=1);

$currentPage = $currentPage ?? 'home';

$menuItems = [
    [
        'key' => 'live',
        'label' => 'Live',
        'href' => '/',
    ],
    [
        'key' => 'monitors',
        'label' => 'Monitors',
        'children' => [
            ['key' => 'earthquakes', 'label' => 'Earthquakes', 'href' => '/earthquakes.php'],
            ['key' => 'volcanoes', 'label' => 'Volcanoes', 'href' => '/volcanoes.php'],
            ['key' => 'tsunami-alerts', 'label' => 'Tsunami Alerts', 'href' => '/tsunami.php'],
            ['key' => 'space-weather', 'label' => 'Space Weather', 'href' => '/space-weather.php'],
        ],
    ],
    [
        'key' => 'maps',
        'label' => 'Maps',
        'children' => [
            ['key' => 'maps', 'label' => 'Global Map', 'href' => '/maps.php'],
            ['key' => 'maps-heatmap', 'label' => 'Heatmap', 'href' => '/maps-heatmap.php'],
            ['key' => 'maps-plates', 'label' => 'Tectonic Plates', 'href' => '/maps-plates.php'],
            ['key' => 'maps-depth', 'label' => 'Depth View', 'href' => '/maps-depth.php'],
        ],
    ],
    [
        'key' => 'cams',
        'label' => 'Cams',
        'children' => [
            ['key' => 'cams-volcanoes', 'label' => 'Volcano Cams', 'href' => '/cams-volcanoes.php'],
            ['key' => 'cams-hotspots', 'label' => 'Eruption Hotspots', 'href' => '/cams-hotspots.php'],
        ],
    ],
    [
        'key' => 'data',
        'label' => 'Data',
        'children' => [
            ['key' => 'data-archive', 'label' => 'Archive', 'href' => '/data-archive.php'],
            ['key' => 'data-energy', 'label' => 'Energy', 'href' => '/data-energy.php'],
            ['key' => 'data-reports', 'label' => 'Reports', 'href' => '/data-reports.php'],
            ['key' => 'data-clusters', 'label' => 'Clusters', 'href' => '/data-clusters.php'],
            ['key' => 'data-api', 'label' => 'API', 'href' => '/data-api.php'],
            ['key' => 'data-status', 'label' => 'Data Status', 'href' => '/data-status.php'],
        ],
    ],
    [
        'key' => 'resources',
        'label' => 'Resources',
        'children' => [
            ['key' => 'resources-safety', 'label' => 'Safety Guides', 'href' => '/resources-safety.php'],
            ['key' => 'resources-glossary', 'label' => 'Glossary', 'href' => '/resources-glossary.php'],
            ['key' => 'resources-bulletins', 'label' => 'Bulletins', 'href' => '/resources-bulletins.php'],
            ['key' => 'priority-levels', 'label' => 'Priority Levels (P1/P2)', 'href' => '/priority-levels.php'],
        ],
    ],
    [
        'key' => 'about',
        'label' => 'About',
        'children' => [
            ['key' => 'about-sources', 'label' => 'Sources', 'href' => '/about-sources.php'],
            ['key' => 'about-methodology', 'label' => 'Methodology', 'href' => '/about-methodology.php'],
        ],
    ],
];

$legacyTopLevelMap = [
    'home' => 'live',
    'earthquakes' => 'monitors',
    'volcanoes' => 'monitors',
    'tsunami-alerts' => 'monitors',
    'space-weather' => 'monitors',
    'maps' => 'maps',
    'maps-heatmap' => 'maps',
    'maps-plates' => 'maps',
    'maps-depth' => 'maps',
    'cams-volcanoes' => 'cams',
    'cams-hotspots' => 'cams',
    'analytics' => 'data',
    'tremors' => 'data',
    'data-energy' => 'data',
    'data-clusters' => 'data',
    'data-archive' => 'data',
    'data-reports' => 'data',
    'data-api' => 'data',
    'data-status' => 'data',
    'resources-safety' => 'resources',
    'resources-glossary' => 'resources',
    'resources-bulletins' => 'resources',
    'priority-levels' => 'resources',
    'about' => 'about',
    'about-sources' => 'about',
    'about-methodology' => 'about',
];
?>
<header class="topbar">
  <a class="brand" href="/" aria-label="Go to homepage">Quakrs<span>.com</span></a>
  <button
    id="mobile-nav-toggle"
    class="nav-toggle"
    type="button"
    aria-controls="main-nav"
    aria-expanded="false"
    aria-label="Open navigation menu"
  >
    Menu
  </button>
  <nav id="main-nav" class="main-nav" aria-label="Main navigation">
    <?php foreach ($menuItems as $item): ?>
      <?php
      $topKey = $item['key'];
      $isTopActive = ($legacyTopLevelMap[$currentPage] ?? $currentPage) === $topKey || $currentPage === $topKey;
      ?>
      <?php if (!isset($item['children'])): ?>
        <a class="nav-link <?= $isTopActive ? 'is-active' : ''; ?>" href="<?= $item['href']; ?>">
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
                <a class="nav-sublink <?= $isChildActive ? 'is-active' : ''; ?>" href="<?= $child['href']; ?>" role="menuitem">
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
  </nav>
  <a class="cta" href="/earthquakes.php">Live Feed</a>
</header>
