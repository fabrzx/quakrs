<?php
declare(strict_types=1);

$appConfig = require __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../lib/editorial_engine.php';

$bundle = qk_editorial_load_bundle($appConfig);
if (!is_array($bundle['articles'] ?? null) || $bundle['articles'] === []) {
    $bundle = qk_editorial_generate($appConfig, 120);
}

$slug = isset($editorialSlug) && is_string($editorialSlug)
    ? trim($editorialSlug)
    : trim((string) ($_GET['slug'] ?? ''));

if (!function_exists('qk_editorial_canonical_article_url')) {
    function qk_editorial_canonical_article_url(array $article): string
    {
        $event = is_array($article['event'] ?? null) ? $article['event'] : [];
        $type = (string) ($article['type'] ?? 'latest');
        if ($event !== [] && function_exists('qk_editorial_predict_slug')) {
            $predicted = qk_editorial_predict_slug($type, $event);
            if ($predicted !== '') {
                return '/editoriale/' . $predicted . '.php';
            }
        }
        return (string) ($article['url'] ?? '/editoriale/');
    }
}

if (!function_exists('qk_editorial_extract_event_id_from_slug')) {
    function qk_editorial_extract_event_id_from_slug(string $slug): string
    {
        if (preg_match('/\b(us[0-9a-z]+)\b/i', $slug, $m)) {
            return strtolower((string) ($m[1] ?? ''));
        }
        return '';
    }
}

$article = null;
foreach (($bundle['articles'] ?? []) as $candidate) {
    if (!is_array($candidate)) {
        continue;
    }
    if ((string) ($candidate['slug'] ?? '') === $slug) {
        $article = $candidate;
        break;
    }
}

if ($article === null) {
    $eventIdFromSlug = qk_editorial_extract_event_id_from_slug($slug);
    if ($eventIdFromSlug !== '') {
        foreach (($bundle['articles'] ?? []) as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }
            $event = is_array($candidate['event'] ?? null) ? $candidate['event'] : [];
            $eventId = strtolower((string) ($event['id'] ?? ''));
            if ($eventId !== '' && $eventId === $eventIdFromSlug) {
                $article = $candidate;
                break;
            }
        }
    }
}

if (!is_array($article) || (string) ($article['status'] ?? '') !== 'published') {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$canonicalArticleUrl = qk_editorial_canonical_article_url($article);
$requestedUrlPath = '/editoriale/' . $slug . '.php';
if ($canonicalArticleUrl !== '' && stripos($requestedUrlPath, 'gpt') !== false && $canonicalArticleUrl !== $requestedUrlPath) {
    header('Location: ' . qk_localized_url($canonicalArticleUrl), true, 301);
    exit;
}

$title = (string) ($article['seo_title'] ?? $article['title'] ?? 'Editoriale Quakrs');
$description = (string) ($article['seo_description'] ?? $article['excerpt'] ?? '');
$pageTitle = $title;
$pageDescription = $description;
$currentPage = 'editoriale';
$includeLeaflet = true;

$event = is_array($article['event'] ?? null) ? $article['event'] : [];
$sourceUrl = trim((string) ($event['source_url'] ?? ''));
$articleUrl = 'https://quakrs.com' . (string) ($article['url'] ?? '/editoriale/');
$eventLat = is_numeric($event['latitude'] ?? null) ? (float) $event['latitude'] : null;
$eventLon = is_numeric($event['longitude'] ?? null) ? (float) $event['longitude'] : null;
$hasMapPoint = $eventLat !== null && $eventLon !== null;
$articleSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => (string) ($article['title'] ?? ''),
    'description' => $description,
    'inLanguage' => 'it',
    'datePublished' => (string) ($article['published_at'] ?? ''),
    'dateModified' => (string) ($article['updated_at'] ?? $article['published_at'] ?? ''),
    'mainEntityOfPage' => $articleUrl,
    'author' => [
        '@type' => 'Organization',
        'name' => 'Quakrs',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Quakrs',
    ],
];
if ($sourceUrl !== '') {
    $articleSchema['citation'] = $sourceUrl;
}

$allArticles = array_values(array_filter($bundle['articles'] ?? [], static function ($item): bool {
    return is_array($item) && (string) ($item['status'] ?? '') === 'published';
}));
$recent = [];
$related = [];
$currentType = (string) ($article['type'] ?? '');
foreach ($allArticles as $item) {
    if (!is_array($item)) {
        continue;
    }
    $itemSlug = (string) ($item['slug'] ?? '');
    if ($itemSlug === $slug) {
        continue;
    }
    if (count($recent) < 6) {
        $recent[] = $item;
    }
    if ((string) ($item['type'] ?? '') === $currentType && count($related) < 6) {
        $related[] = $item;
    }
}
if ($related === []) {
    $related = array_slice($recent, 0, 6);
}

$articleGlossary = is_array($article['glossary'] ?? null) ? $article['glossary'] : [];
$articleSections = is_array($article['sections'] ?? null) ? $article['sections'] : [];
$articleEyebrowRaw = (string) ($article['eyebrow'] ?? 'Analisi');
$articleEyebrowClean = preg_replace('/\bgpt\b/i', '', $articleEyebrowRaw);
$articleEyebrow = trim(preg_replace('/\s+/', ' ', (string) $articleEyebrowClean));
if ($articleEyebrow === '') {
    $articleEyebrow = 'Analisi';
}
if (function_exists('qk_editorial_detect_interesting_terms')) {
    $detectedTerms = qk_editorial_detect_interesting_terms($articleSections);
    foreach ($detectedTerms as $detected) {
        if (!is_array($detected)) {
            continue;
        }
        $term = trim((string) ($detected['term'] ?? ''));
        $definition = trim((string) ($detected['definition'] ?? ''));
        if ($term === '' || $definition === '') {
            continue;
        }
        $exists = false;
        foreach ($articleGlossary as $existing) {
            if (!is_array($existing)) {
                continue;
            }
            if (mb_strtolower((string) ($existing['term'] ?? '')) === mb_strtolower($term)) {
                $exists = true;
                break;
            }
        }
        if ($exists) {
            continue;
        }
        $articleGlossary[] = [
            'term' => $term,
            'definition' => $definition,
        ];
    }
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

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>

<script type="application/ld+json"><?= htmlspecialchars((string) json_encode($articleSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_NOQUOTES, 'UTF-8'); ?></script>
<style>
  .editorial-article-main .article-title {
    margin: 0 0 0.6rem;
  }
  .editorial-article-main {
    padding: 1.28rem 1.42rem 1.2rem !important;
    border: 1px solid var(--line-strong);
    border-left: 3px solid var(--acid-cyan);
    background: var(--bg-1);
    box-shadow: inset 0 0 0 1px rgba(120, 220, 255, 0.08);
  }
  .editorial-article-main .article-meta {
    margin: 0 0 1.12rem;
    color: var(--text-2);
    font-size: 0.9rem;
    line-height: 1.5;
  }
  .editorial-map-shot {
    margin: 1.05rem 0 1.2rem;
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
    padding-top: 0.95rem;
    border-top: 1px solid rgba(120, 220, 255, 0.3);
    margin-top: 0.95rem;
  }
  .editorial-section h3 {
    margin: 0 0 0.58rem;
    font-size: 1.03rem;
    line-height: 1.26;
    letter-spacing: 0.01em;
  }
  .editorial-article-sidebar .card + .card {
    margin-top: 0.9rem;
  }
  .editorial-article-main .insight-lead {
    margin: 0;
    line-height: 1.78;
    color: var(--text-1);
    font-size: 1rem;
    overflow-wrap: anywhere;
  }
  @media (max-width: 980px) {
    .editorial-article-main {
      padding: 1.12rem 1.08rem 1.04rem !important;
      border-left-width: 2px;
    }
    .editorial-article-main .insight-lead {
      font-size: 0.97rem;
      line-height: 1.74;
    }
  }
  .editorial-article-sidebar .events-list .event-item {
    padding: 0.72rem 0.75rem;
    min-height: 0;
    display: block;
    align-items: initial;
    justify-content: initial;
  }
  .editorial-article-sidebar .events-list {
    margin: 0.36rem 0 0;
    padding: 0;
    list-style: none;
  }
  .editorial-article-sidebar .events-list .event-item strong {
    line-height: 1.35;
    display: block;
  }
  .editorial-article-sidebar .events-list .event-item .inline-link {
    margin-top: 0;
    display: inline;
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
  .site-2026 .editorial-article-hero h1 {
    font-size: clamp(1.55rem, 2.6vw, 2.15rem) !important;
    line-height: 1.08 !important;
    letter-spacing: 0.01em;
    margin-bottom: 0.45rem;
  }
  .editorial-article-hero .sub {
    max-width: 900px;
  }
  .editorial-link-rows {
    display: grid;
    gap: 1rem;
  }
  .editorial-link-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.8rem;
  }
  .editorial-link-grid .event-item {
    min-height: 100%;
    padding: 0.75rem 0.82rem;
    background: var(--bg-2);
    border-color: var(--line-soft);
  }
  @media (max-width: 980px) {
    .editorial-link-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<main class="hero compact-hero editorial-article-hero">
  <div>
    <p class="eyebrow"><?= htmlspecialchars($articleEyebrow, ENT_QUOTES, 'UTF-8'); ?></p>
    <h1><?= htmlspecialchars((string) ($article['title'] ?? 'Articolo'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars((string) ($article['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel">
  <article class="card page-card editorial-article-main">
    <p class="article-meta">
      <?= htmlspecialchars((string) ($event['event_time_utc'] ?? 'n/d'), ENT_QUOTES, 'UTF-8'); ?>
      · M<?= isset($event['magnitude']) && is_numeric($event['magnitude']) ? htmlspecialchars(number_format((float) $event['magnitude'], 1, '.', ''), ENT_QUOTES, 'UTF-8') : 'n/d'; ?>
      · <?= isset($event['depth_km']) && is_numeric($event['depth_km']) ? htmlspecialchars(number_format((float) $event['depth_km'], 1, '.', '') . ' km', ENT_QUOTES, 'UTF-8') : 'n/d'; ?>
      · <?= htmlspecialchars((string) ($event['place'] ?? 'n/d'), ENT_QUOTES, 'UTF-8'); ?>
    </p>

    <?php if ($hasMapPoint): ?>
      <figure class="editorial-map-shot">
        <div id="editorial-article-map" class="editorial-map-canvas" aria-label="Mappa epicentro"></div>
        <figcaption>
          Epicentro: <?= htmlspecialchars(is_numeric($event['latitude'] ?? null) ? number_format((float) $event['latitude'], 3, '.', '') : 'n/d', ENT_QUOTES, 'UTF-8'); ?>,
          <?= htmlspecialchars(is_numeric($event['longitude'] ?? null) ? number_format((float) $event['longitude'], 3, '.', '') : 'n/d', ENT_QUOTES, 'UTF-8'); ?>
          <?php if ($sourceUrl !== ''): ?>
            · <a class="inline-link" href="<?= htmlspecialchars($sourceUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Fonte evento</a>
          <?php endif; ?>
          <div class="editorial-wave-legend" aria-label="Legenda isocrone onde sismiche">
            <div class="editorial-wave-legend-row">
              <span id="editorial-article-wave-p" class="editorial-wave-pill is-p"><span class="dot" aria-hidden="true"></span>P-wave --/--/--s</span>
              <span id="editorial-article-wave-s" class="editorial-wave-pill is-s"><span class="dot" aria-hidden="true"></span>S-wave --/--/--s</span>
            </div>
            <p id="editorial-article-wave-note" class="editorial-wave-note">Isocrone con velocita medie crostali e correzione ipocentro (modello semplificato, non stima locale di scuotimento).</p>
          </div>
        </figcaption>
      </figure>
    <?php endif; ?>

    <?php foreach (($article['sections'] ?? []) as $section): ?>
      <?php if (!is_array($section)) { continue; } ?>
      <section class="editorial-section">
        <h3><?= htmlspecialchars((string) ($section['heading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
        <p class="insight-lead"><?= qk_editorial_render_text_with_glossary((string) ($section['body'] ?? ''), $articleGlossary); ?></p>
      </section>
    <?php endforeach; ?>

  </article>
</section>

<section class="panel editorial-link-rows">
  <article class="card">
    <div class="feed-head">
      <h3>Articoli correlati</h3>
    </div>
    <div class="editorial-link-grid">
      <?php foreach (array_slice($related, 0, 3) as $item): ?>
        <?php $cTitle = (string) ($item['title'] ?? 'Articolo'); $cUrl = qk_editorial_canonical_article_url($item); ?>
        <div class="event-item"><strong><a class="inline-link" href="<?= htmlspecialchars(qk_localized_url($cUrl), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($cTitle, ENT_QUOTES, 'UTF-8'); ?></a></strong></div>
      <?php endforeach; ?>
    </div>
  </article>

  <article class="card">
    <div class="feed-head">
      <h3>Articoli recenti</h3>
    </div>
    <div class="editorial-link-grid">
      <?php foreach (array_slice($recent, 0, 3) as $item): ?>
        <?php $rTitle = (string) ($item['title'] ?? 'Articolo'); $rUrl = qk_editorial_canonical_article_url($item); ?>
        <div class="event-item"><strong><a class="inline-link" href="<?= htmlspecialchars(qk_localized_url($rUrl), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($rTitle, ENT_QUOTES, 'UTF-8'); ?></a></strong></div>
      <?php endforeach; ?>
    </div>
  </article>
</section>

<?php if ($hasMapPoint): ?>
<script>
  (function () {
    if (!window.L) return;
    var el = document.getElementById('editorial-article-map');
    if (!el) return;
    var lat = <?= json_encode($eventLat, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    var lon = <?= json_encode($eventLon, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
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
        // no-op: map still renders event point even if tectonic overlay is unavailable
      });
    var hypoDepthKm = typeof depthKm === 'number' ? Math.max(0, depthKm) : 10;
    var vp = 6.0;
    var vs = 3.5;
    var pLegend = document.getElementById('editorial-article-wave-p');
    var sLegend = document.getElementById('editorial-article-wave-s');
    var waveNote = document.getElementById('editorial-article-wave-note');
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

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Continua il monitoraggio</h3>
    <p class="insight-lead"><a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/earthquakes.php'), ENT_QUOTES, 'UTF-8'); ?>">Monitor terremoti</a> · <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/timeline.php'), ENT_QUOTES, 'UTF-8'); ?>">Timeline</a> · <a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/alerts.php'), ENT_QUOTES, 'UTF-8'); ?>">Alerts</a></p>
  </article>
  <article class="card page-card">
    <h3>Archivio</h3>
    <p class="insight-lead"><a class="inline-link" href="<?= htmlspecialchars(qk_localized_url('/data-archive.php'), ENT_QUOTES, 'UTF-8'); ?>">Archivio sismico avanzato</a> per confronti su finestre temporali più ampie.</p>
  </article>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
