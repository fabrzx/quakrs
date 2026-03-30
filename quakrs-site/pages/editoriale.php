<?php
declare(strict_types=1);

$appConfig = require __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../lib/editorial_engine.php';

$bundle = qk_editorial_load_bundle($appConfig);
if (!is_array($bundle['articles'] ?? null) || $bundle['articles'] === []) {
    $bundle = qk_editorial_generate($appConfig, 120);
}

$articles = array_values(array_filter($bundle['articles'] ?? [], static function ($article): bool {
    return is_array($article) && (string) ($article['status'] ?? '') === 'published';
}));

$featured = $articles[0] ?? null;
$recent = array_slice($articles, 1, 8);
$related = [];
if (is_array($featured)) {
    $featuredType = (string) ($featured['type'] ?? '');
    foreach ($articles as $idx => $article) {
        if ($idx === 0 || !is_array($article)) {
            continue;
        }
        if ((string) ($article['type'] ?? '') === $featuredType) {
            $related[] = $article;
        }
        if (count($related) >= 6) {
            break;
        }
    }
}
if ($related === []) {
    $related = array_slice($recent, 0, 6);
}

if (!function_exists('qk_editorial_glossary_lookup')) {
    function qk_editorial_glossary_lookup(array $glossary): array
    {
        $lookup = [];
        foreach ($glossary as $item) {
            if (!is_array($item)) {
                continue;
            }
            $term = trim((string) ($item['term'] ?? ''));
            $def = trim((string) ($item['definition'] ?? ''));
            if ($term === '' || $def === '') {
                continue;
            }
            $lookup[mb_strtolower($term)] = ['term' => $term, 'definition' => $def];
        }
        uksort($lookup, static fn(string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        return $lookup;
    }
}

if (!function_exists('qk_editorial_render_text_with_glossary')) {
    function qk_editorial_render_text_with_glossary(string $text, array $glossary): string
    {
        $lookup = qk_editorial_glossary_lookup($glossary);
        if ($lookup === []) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
        $terms = array_map(static fn(string $k): string => preg_quote($k, '/'), array_keys($lookup));
        if ($terms === []) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
        $used = [];
        $pattern = '/(' . implode('|', $terms) . ')/iu';
        $rendered = preg_replace_callback($pattern, static function (array $m) use (&$used, $lookup): string {
            $match = (string) ($m[0] ?? '');
            $key = mb_strtolower($match);
            if ($match === '' || !isset($lookup[$key]) || isset($used[$key])) {
                return htmlspecialchars($match, ENT_QUOTES, 'UTF-8');
            }
            $used[$key] = true;
            $def = (string) $lookup[$key]['definition'];
            $slug = qk_editorial_term_slug((string) $lookup[$key]['term']);
            $href = '/glossario/' . rawurlencode($slug) . '.php';
            return '<a class="term-help" href="' . htmlspecialchars(qk_localized_url($href), ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($def, ENT_QUOTES, 'UTF-8')
                . '" aria-label="' . htmlspecialchars($lookup[$key]['term'] . ': ' . $def, ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($match, ENT_QUOTES, 'UTF-8') . '</a>';
        }, $text);
        return is_string($rendered) ? $rendered : htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('qk_editorial_canonical_article_url')) {
    function qk_editorial_canonical_article_url(array $article): string
    {
        $event = is_array($article['event'] ?? null) ? $article['event'] : [];
        $type = (string) ($article['type'] ?? 'latest');
        if ($event !== [] && function_exists('qk_editorial_predict_slug')) {
            $slug = qk_editorial_predict_slug($type, $event);
            if ($slug !== '') {
                return '/editoriale/' . $slug . '.php';
            }
        }
        return (string) ($article['url'] ?? '/editoriale/');
    }
}

$pageTitle = 'Quakrs.com - Editoriale';
$pageDescription = 'Analisi e retrospettive sismiche strutturate con focus su contesto, dinamica locale e implicazioni operative.';
$currentPage = 'editoriale';
$includeLeaflet = true;

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>
<style>
  .editorial-home-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 1rem;
    align-items: stretch;
  }
  .editorial-feature-head {
    padding: 0.1rem 0.06rem 0.12rem;
    border-bottom: 1px solid var(--line-soft);
  }
  .editorial-feature-head .article-kicker {
    margin: 0 0 0.28rem;
    color: var(--acid-yellow);
    font: 800 0.71rem/1.1 "Space Grotesk", sans-serif;
    letter-spacing: 0.11em;
    text-transform: uppercase;
  }
  .editorial-feature-head .eyebrow {
    margin: 0 0 0.62rem;
  }
  .editorial-feature-head .article-title {
    margin: 0 0 0.7rem;
    font-size: clamp(1.55rem, 2.6vw, 2.15rem);
    line-height: 1.08;
    letter-spacing: 0.01em;
    display: block;
    font-weight: 700;
    max-width: 34ch;
  }
  .editorial-feature-head .article-title .inline-link {
    color: var(--text-1);
    font-size: inherit;
    line-height: inherit;
    font-weight: inherit;
    margin-top: 0;
    display: inline;
  }
  .editorial-feature-head .article-title .inline-link:hover {
    color: color-mix(in srgb, var(--text-1) 92%, var(--acid-cyan));
  }
  .editorial-feature-head .article-excerpt {
    margin: 0 0 1.05rem;
    color: var(--text-2);
    font-size: clamp(1rem, 1.45vw, 1.28rem);
    line-height: 1.45;
    max-width: 78ch;
    font-weight: 600;
  }
  .editorial-feature-head .article-meta {
    margin: 0;
    color: var(--text-2);
    font-size: 0.96rem;
    line-height: 1.5;
  }
  .editorial-main {
    padding: 1.45rem 1.6rem 1.3rem !important;
    border: 1px solid var(--line-strong);
    border-left: 3px solid var(--acid-cyan);
    background: var(--bg-1);
    box-shadow: inset 0 0 0 1px rgba(120, 220, 255, 0.08);
  }
  .editorial-map-shot {
    margin: 0 0 1.2rem;
    border: 1px solid var(--line-strong);
    border-top: 2px solid var(--acid-orange);
    background: var(--bg-2);
    box-shadow: inset 0 0 0 1px rgba(255, 122, 0, 0.08);
  }
  .editorial-map-canvas {
    width: 100%;
    aspect-ratio: 1200 / 560;
    min-height: 280px;
    border-bottom: 1px solid var(--line-soft);
  }
  .editorial-map-shot figcaption {
    margin: 0;
    padding: 0.7rem 0.88rem 0.76rem;
    color: var(--text-2);
    font-size: 0.8rem;
    line-height: 1.4;
  }
  .editorial-wave-legend {
    margin-top: 0.45rem;
    padding-top: 0.45rem;
    border-top: 1px solid var(--line-soft);
    display: grid;
    gap: 0.35rem;
  }
  .editorial-wave-legend-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    align-items: center;
  }
  .editorial-wave-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.33rem;
    padding: 0.15rem 0.42rem;
    border: 1px solid var(--line-soft);
    border-radius: 999px;
    background: rgba(5, 8, 22, 0.7);
    color: var(--text-2);
    font-size: 0.7rem;
    line-height: 1.2;
    letter-spacing: 0.01em;
    font-weight: 700;
  }
  .editorial-wave-pill .dot {
    width: 0.58rem;
    height: 0.58rem;
    border-radius: 999px;
    display: inline-block;
  }
  .editorial-wave-pill.is-p .dot {
    background: var(--acid-cyan);
    box-shadow: 0 0 0 1px rgba(32, 224, 255, 0.35);
  }
  .editorial-wave-pill.is-s .dot {
    background: var(--acid-lime);
    box-shadow: 0 0 0 1px rgba(183, 255, 0, 0.3);
  }
  .editorial-wave-note {
    color: var(--text-3);
    font-size: 0.72rem;
    line-height: 1.42;
  }
  .editorial-section {
    padding-top: 1.05rem;
    border-top: 1px solid rgba(120, 220, 255, 0.3);
    margin-top: 1.05rem;
  }
  .editorial-section h3 {
    margin: 0 0 0.55rem;
    font-size: 1.02rem;
    line-height: 1.25;
    letter-spacing: 0.01em;
  }
  .editorial-sidebar {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
  }
  .editorial-sidebar .card {
    padding: 1.02rem 1.06rem 0.98rem;
    border-color: var(--line-strong);
    border-top: 2px solid var(--acid-cyan);
    box-shadow: inset 0 0 0 1px rgba(120, 220, 255, 0.06);
  }
  .editorial-sidebar .card.is-recent {
    border-top-color: var(--acid-lime);
  }
  .editorial-sidebar .card h3 {
    margin: 0 0 0.36rem;
    color: var(--text-1);
    letter-spacing: 0.008em;
    font-size: 1.01rem;
  }
  .editorial-sidebar .card + .card {
    margin-top: 0;
  }
  .editorial-main .insight-lead {
    margin: 0;
    line-height: 1.76;
    color: var(--text-1);
    font-size: 0.99rem;
    overflow-wrap: anywhere;
  }
  .editorial-sidebar .events-list .event-item {
    padding: 0.76rem 0.8rem;
    background: var(--bg-2);
    border-color: var(--line-soft);
    min-height: 0;
    display: block;
    align-items: initial;
    justify-content: initial;
  }
  .editorial-sidebar .events-list {
    margin: 0.36rem 0 0;
    padding: 0;
    list-style: none;
  }
  .editorial-sidebar .card.is-related .events-list .event-item strong a {
    color: var(--acid-cyan);
  }
  .editorial-sidebar .card.is-recent .events-list .event-item strong a {
    color: var(--acid-lime);
  }
  .editorial-sidebar .events-list .event-item strong {
    line-height: 1.35;
    display: block;
  }
  .editorial-sidebar .events-list .event-item .inline-link {
    margin-top: 0;
    display: inline;
  }
  .editorial-page .hero h1 {
    font-size: clamp(2.3rem, 5.2vw, 4.1rem);
    line-height: 1.04;
    letter-spacing: 0.004em;
  }
  .term-help {
    display: inline;
    color: var(--acid-cyan);
    text-decoration: underline dotted;
    text-underline-offset: 0.15rem;
    cursor: pointer;
    font: inherit;
    font-weight: 700;
  }
  @media (max-width: 1040px) {
    .editorial-main {
      padding: 1.18rem 1.12rem 1.08rem !important;
    }
    .editorial-sidebar {
      grid-template-columns: 1fr;
    }
    .editorial-home-layout {
      grid-template-columns: 1fr;
    }
  }
  @media (max-width: 640px) {
    .editorial-feature-head .article-title {
      font-size: clamp(1.42rem, 7.4vw, 1.85rem);
      line-height: 1.1;
    }
    .editorial-feature-head .article-excerpt {
      font-size: clamp(0.95rem, 4.2vw, 1.08rem);
      line-height: 1.45;
      margin-bottom: 0.9rem;
    }
    .editorial-feature-head .article-meta {
      font-size: 0.9rem;
    }
    .editorial-main {
      padding: 1.08rem 1rem 1rem !important;
      border-left-width: 2px;
    }
    .editorial-main .insight-lead {
      font-size: 0.96rem;
      line-height: 1.72;
    }
  }
</style>

<?php
$editorialeHeroTitle = 'Editoriale';
?>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars(qk_t('page.editoriale.eyebrow'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars($editorialeHeroTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars(qk_t('page.editoriale.sub'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<?php if (is_array($featured)): ?>
<section class="panel editorial-home-layout">
  <?php
  $title = (string) ($featured['title'] ?? 'Articolo');
  $url = qk_editorial_canonical_article_url($featured);
  $published = (string) ($featured['published_at'] ?? '');
  $eyebrowRaw = (string) ($featured['eyebrow'] ?? 'Analisi');
  $eyebrowClean = preg_replace('/\bgpt\b/i', '', $eyebrowRaw);
  $eyebrow = trim(preg_replace('/\s+/', ' ', (string) $eyebrowClean));
  if ($eyebrow === '') {
    $eyebrow = 'Analisi';
  }
  $excerpt = (string) ($featured['excerpt'] ?? '');
  $event = is_array($featured['event'] ?? null) ? $featured['event'] : [];
  $mag = isset($event['magnitude']) && is_numeric($event['magnitude']) ? number_format((float) $event['magnitude'], 1, '.', '') : 'n/d';
  $depth = isset($event['depth_km']) && is_numeric($event['depth_km']) ? number_format((float) $event['depth_km'], 1, '.', '') . ' km' : 'n/d';
  $place = (string) ($event['place_short'] ?? $event['place'] ?? 'n/d');
  $lat = is_numeric($event['latitude'] ?? null) ? (float) $event['latitude'] : null;
  $lon = is_numeric($event['longitude'] ?? null) ? (float) $event['longitude'] : null;
  $hasMapPoint = $lat !== null && $lon !== null;
  ?>
  <header class="editorial-feature-head">
    <p class="article-kicker">Ultimo articolo</p>
    <p class="eyebrow"><?= htmlspecialchars($eyebrow, ENT_QUOTES, 'UTF-8'); ?></p>
    <h2 class="article-title">
      <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url($url), ENT_QUOTES, 'UTF-8'); ?>">
        <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
      </a>
    </h2>
    <?php if ($excerpt !== ''): ?>
      <p class="article-excerpt"><?= htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <p class="article-meta"><?= htmlspecialchars($published, ENT_QUOTES, 'UTF-8'); ?> · M<?= htmlspecialchars($mag, ENT_QUOTES, 'UTF-8'); ?> · <?= htmlspecialchars($depth, ENT_QUOTES, 'UTF-8'); ?> · <?= htmlspecialchars($place, ENT_QUOTES, 'UTF-8'); ?></p>
  </header>

  <article class="card page-card editorial-main">

    <?php if ($hasMapPoint): ?>
      <figure class="editorial-map-shot">
        <div id="editorial-home-map" class="editorial-map-canvas" aria-label="Mappa epicentro"></div>
        <figcaption>
          Epicentro: <?= htmlspecialchars(is_numeric($event['latitude'] ?? null) ? number_format((float) $event['latitude'], 3, '.', '') : 'n/d', ENT_QUOTES, 'UTF-8'); ?>,
          <?= htmlspecialchars(is_numeric($event['longitude'] ?? null) ? number_format((float) $event['longitude'], 3, '.', '') : 'n/d', ENT_QUOTES, 'UTF-8'); ?>
          <div class="editorial-wave-legend" aria-label="Legenda isocrone onde sismiche">
            <div class="editorial-wave-legend-row">
              <span id="editorial-home-wave-p" class="editorial-wave-pill is-p"><span class="dot" aria-hidden="true"></span>P-wave --/--/--s</span>
              <span id="editorial-home-wave-s" class="editorial-wave-pill is-s"><span class="dot" aria-hidden="true"></span>S-wave --/--/--s</span>
            </div>
            <p id="editorial-home-wave-note" class="editorial-wave-note">Isocrone con velocita medie crostali e correzione ipocentro (modello semplificato, non stima locale di scuotimento).</p>
          </div>
        </figcaption>
      </figure>
    <?php endif; ?>

    <?php foreach (($featured['sections'] ?? []) as $section): ?>
      <?php if (!is_array($section)) { continue; } ?>
      <section class="editorial-section">
        <h3><?= htmlspecialchars((string) ($section['heading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
        <p class="insight-lead"><?= qk_editorial_render_text_with_glossary((string) ($section['body'] ?? ''), is_array($featured['glossary'] ?? null) ? $featured['glossary'] : []); ?></p>
      </section>
    <?php endforeach; ?>
  </article>

  <aside class="editorial-sidebar">
    <article class="card page-card is-related">
      <h3>Articoli correlati</h3>
      <?php if ($related === []): ?>
        <p class="insight-lead">Nessun correlato disponibile.</p>
      <?php else: ?>
        <ul class="events-list">
          <?php foreach ($related as $item): ?>
            <?php $cTitle = (string) ($item['title'] ?? 'Articolo'); $cUrl = qk_editorial_canonical_article_url($item); ?>
            <li class="event-item"><strong><a class="inline-link" href="<?= htmlspecialchars(qk_localized_url($cUrl), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($cTitle, ENT_QUOTES, 'UTF-8'); ?></a></strong></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </article>

    <article class="card page-card is-recent">
      <h3>Articoli recenti</h3>
      <?php if ($recent === []): ?>
        <p class="insight-lead">Nessun articolo recente disponibile.</p>
      <?php else: ?>
        <ul class="events-list">
          <?php foreach (array_slice($recent, 0, 8) as $item): ?>
            <?php $rTitle = (string) ($item['title'] ?? 'Articolo'); $rUrl = qk_editorial_canonical_article_url($item); ?>
            <li class="event-item"><strong><a class="inline-link" href="<?= htmlspecialchars(qk_localized_url($rUrl), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($rTitle, ENT_QUOTES, 'UTF-8'); ?></a></strong></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </article>
  </aside>
</section>
<?php if (isset($hasMapPoint) && $hasMapPoint): ?>
<script>
  (function () {
    if (!window.L) return;
    var el = document.getElementById('editorial-home-map');
    if (!el) return;
    var lat = <?= json_encode($lat, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    var lon = <?= json_encode($lon, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    var magnitude = <?= json_encode(isset($event['magnitude']) && is_numeric($event['magnitude']) ? (float) $event['magnitude'] : null, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    var depthKm = <?= json_encode(isset($event['depth_km']) && is_numeric($event['depth_km']) ? (float) $event['depth_km'] : null, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    if (typeof lat !== 'number' || typeof lon !== 'number') return;
    var map = window.L.map(el, { zoomControl: true, dragging: false, scrollWheelZoom: false }).setView([lat, lon], 8);
    window.L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
      maxZoom: 18,
      attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
    }).addTo(map);
    var faultsLayer = window.L.layerGroup().addTo(map);
    window.L.circleMarker([lat, lon], {
      radius: 8,
      color: '#ff7a00',
      weight: 2,
      fillColor: '#ff2bd6',
      fillOpacity: 0.85
    }).addTo(map);
    fetch('/api/tectonic-context.php?scope=local&lat=' + encodeURIComponent(String(lat)) + '&lon=' + encodeURIComponent(String(lon)) + '&max_faults=220', { cache: 'no-store' })
      .then(function (res) { return res.ok ? res.json() : null; })
      .then(function (payload) {
        if (!payload || !payload.faults || !Array.isArray(payload.faults.features)) return;
        window.L.geoJSON(payload.faults, {
          style: function () {
            return {
              color: '#ff7a00',
              weight: 1.7,
              opacity: 0.6
            };
          }
        }).addTo(faultsLayer);
      })
      .catch(function () {
        // no-op: keep base map + epicenter marker even if faults are unavailable
      });

    var hypoDepthKm = typeof depthKm === 'number' ? Math.max(0, depthKm) : 10;
    var vp = 6.0;
    var vs = 3.5;
    var pLegend = document.getElementById('editorial-home-wave-p');
    var sLegend = document.getElementById('editorial-home-wave-s');
    var waveNote = document.getElementById('editorial-home-wave-note');
    var magScale = typeof magnitude === 'number'
      ? Math.max(0.88, Math.min(1.35, 0.86 + ((magnitude - 5.0) * 0.12)))
      : 1.0;
    var ringsLayer = window.L.layerGroup().addTo(map);
    function horizontalRadiusMeters(velocityKmS, tSeconds) {
      var hypoKm = velocityKmS * tSeconds;
      var horizontalKm = Math.sqrt(Math.max(0, (hypoKm * hypoKm) - (hypoDepthKm * hypoDepthKm)));
      return Math.round(horizontalKm * 1000 * magScale);
    }
    function addIsochrone(radius, opts) {
      if (!(radius > 0)) return;
      window.L.circle([lat, lon], {
        radius: radius,
        color: opts.color,
        weight: opts.weight,
        opacity: opts.opacity,
        dashArray: opts.dash || null,
        fillOpacity: 0,
        interactive: false
      }).addTo(ringsLayer);
    }
    var pStart = Math.max(10, Math.ceil(hypoDepthKm / vp) + 5);
    var sStart = Math.max(10, Math.ceil(hypoDepthKm / vs) + 5);
    var pMarks = [pStart, pStart + 10, pStart + 20];
    var sMarks = [sStart, sStart + 10, sStart + 20];
    if (pLegend) pLegend.innerHTML = '<span class="dot" aria-hidden="true"></span>P-wave ' + pMarks.join('/') + 's';
    if (sLegend) sLegend.innerHTML = '<span class="dot" aria-hidden="true"></span>S-wave ' + sMarks.join('/') + 's';
    if (waveNote) {
      waveNote.textContent = 'Isocrone con Vp ' + vp.toFixed(1) + ' km/s, Vs ' + vs.toFixed(1) + ' km/s, profondita ' + hypoDepthKm.toFixed(1) + ' km (modello semplificato).';
    }
    for (var i = 0; i < pMarks.length; i++) {
      var tp = pMarks[i];
      var pRadius = horizontalRadiusMeters(vp, tp);
      addIsochrone(pRadius, { color: '#20e0ff', weight: 1.55, opacity: 0.42, label: 'P ' + tp + 's' });
    }
    for (var j = 0; j < sMarks.length; j++) {
      var ts = sMarks[j];
      var sRadius = horizontalRadiusMeters(vs, ts);
      addIsochrone(sRadius, { color: '#b7ff00', weight: 1.35, opacity: 0.36, dash: '6 4', label: 'S ' + ts + 's' });
    }
    setTimeout(function () { map.invalidateSize(); }, 80);
  })();
</script>
<?php endif; ?>
<?php else: ?>
<section class="panel">
  <article class="card">
    <h3>Editoriale in aggiornamento</h3>
    <p class="insight-lead">Nessun articolo disponibile in questo momento.</p>
  </article>
</section>
<?php endif; ?>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Link rapidi</h3>
    <p class="insight-lead"><a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/earthquakes.php'), ENT_QUOTES, 'UTF-8'); ?>">Monitor terremoti</a> · <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/data-archive.php'), ENT_QUOTES, 'UTF-8'); ?>">Archivio sismico</a> · <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/timeline.php'), ENT_QUOTES, 'UTF-8'); ?>">Timeline</a></p>
  </article>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
