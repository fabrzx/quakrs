<?php
declare(strict_types=1);

require_once __DIR__ . '/editorial_gpt.php';

function qk_editorial_data_path(array $appConfig): string
{
    return rtrim((string) ($appConfig['data_dir'] ?? (__DIR__ . '/../data')), '/') . '/editorial_articles_latest.json';
}

function qk_editorial_site_root(): string
{
    return dirname(__DIR__);
}

function qk_editorial_load_bundle(array $appConfig): array
{
    $path = qk_editorial_data_path($appConfig);
    if (!is_file($path)) {
        return [
            'ok' => true,
            'generated_at_ts' => 0,
            'generated_at' => null,
            'articles_count' => 0,
            'articles' => [],
        ];
    }

    $raw = file_get_contents($path);
    if (!is_string($raw) || $raw === '') {
        return [
            'ok' => true,
            'generated_at_ts' => 0,
            'generated_at' => null,
            'articles_count' => 0,
            'articles' => [],
        ];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [
            'ok' => true,
            'generated_at_ts' => 0,
            'generated_at' => null,
            'articles_count' => 0,
            'articles' => [],
        ];
    }

    $decoded['articles'] = is_array($decoded['articles'] ?? null) ? $decoded['articles'] : [];
    $decoded['articles_count'] = count($decoded['articles']);
    return $decoded;
}

function qk_editorial_write_bundle(array $appConfig, array $bundle): bool
{
    $path = qk_editorial_data_path($appConfig);
    $encoded = json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) {
        return false;
    }

    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return false;
    }

    $tmp = sprintf('%s/.%s.tmp.%d.%s', $dir, basename($path), getmypid(), str_replace('.', '', uniqid('', true)));
    $written = @file_put_contents($tmp, $encoded . PHP_EOL, LOCK_EX);
    if ($written === false) {
        return false;
    }

    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }

    return true;
}

function qk_editorial_generate(array $appConfig, int $maxArticles = 80): array
{
    $now = time();
    $dataDir = rtrim((string) ($appConfig['data_dir'] ?? (__DIR__ . '/../data')), '/');
    $archivePath = $dataDir . '/earthquakes_archive.sqlite';
    $gptEnabled = qk_editorial_gpt_enabled($appConfig);

    if (!$gptEnabled) {
        $bundle = [
            'ok' => true,
            'generated_at_ts' => $now,
            'generated_at' => gmdate('c', $now),
            'articles_count' => 0,
            'created_in_run' => 0,
            'articles' => [],
        ];
        qk_editorial_publish_php_pages([], $appConfig);
        qk_editorial_write_bundle($appConfig, $bundle);
        return $bundle;
    }

    $existing = qk_editorial_load_bundle($appConfig);
    $existingBySlug = [];
    foreach (($existing['articles'] ?? []) as $article) {
        if (!is_array($article)) {
            continue;
        }
        $slug = (string) ($article['slug'] ?? '');
        if ($slug !== '') {
            $existingBySlug[$slug] = $article;
        }
    }

    $liveEvents = qk_editorial_load_live_events($dataDir . '/earthquakes_latest.json');
    $historicalEvents = qk_editorial_load_historical_events($dataDir, $archivePath);

    $created = 0;
    $decisions = qk_editorial_build_rubric_decisions($liveEvents, $historicalEvents, array_values($existingBySlug), $archivePath, $now, $appConfig);

    foreach ($decisions as $decision) {
        if (!is_array($decision)) {
            continue;
        }
        $event = is_array($decision['event'] ?? null) ? $decision['event'] : [];
        if ($event === []) {
            continue;
        }
        $type = (string) ($decision['type'] ?? 'event_live');
        $promptKey = (string) ($decision['prompt_key'] ?? $type);
        $slug = qk_editorial_predict_slug_for_type($type, $event);
        if ($slug === '') {
            continue;
        }
        $existingArticle = is_array($existingBySlug[$slug] ?? null) ? $existingBySlug[$slug] : null;
        $isNew = $existingArticle === null;
        $isLegacyNonGpt = is_array($existingArticle) && (string) ($existingArticle['generation_mode'] ?? '') !== 'gpt';
        $shouldUseGpt = $isNew || $isLegacyNonGpt || qk_editorial_article_event_changed($existingArticle, $event);
        if (!$shouldUseGpt && is_array($existingArticle)) {
            continue;
        }

        $article = qk_editorial_build_article(
            $type,
            $event,
            $archivePath,
            $liveEvents,
            $now,
            $appConfig,
            $shouldUseGpt,
            $promptKey,
            is_array($decision['data'] ?? null) ? $decision['data'] : [],
            (string) ($decision['title'] ?? ''),
            (string) ($decision['area'] ?? '')
        );
        if (!is_array($article)) {
            continue;
        }
        if (!$isNew && is_array($existingArticle)) {
            $article = qk_editorial_preserve_publication_fields($article, $existingArticle, $now);
        }
        $existingBySlug[$slug] = $article;
        if ($isNew) {
            $created++;
        }
    }

    // Enforce current publishing policy on legacy items already in the bundle.
    foreach ($existingBySlug as $slug => $article) {
        if (!is_string($slug) || $slug === '' || !is_array($article)) {
            continue;
        }
        if ((string) ($article['generation_mode'] ?? '') !== 'gpt') {
            unset($existingBySlug[$slug]);
            continue;
        }
        if (!qk_editorial_should_keep_article($article)) {
            unset($existingBySlug[$slug]);
        }
    }

    $existingBySlug = qk_editorial_apply_non_live_weekly_cap($existingBySlug, 2);

    // Keep glossary links useful on legacy posts too by auto-enriching terms from article sections.
    foreach ($existingBySlug as $slug => $article) {
        if (!is_string($slug) || $slug === '' || !is_array($article)) {
            continue;
        }
        $sections = is_array($article['sections'] ?? null) ? $article['sections'] : [];
        $glossary = is_array($article['glossary'] ?? null) ? $article['glossary'] : [];
        $article['glossary'] = qk_editorial_merge_glossary_with_detected_terms($glossary, $sections);
        $existingBySlug[$slug] = $article;
    }

    $all = array_values($existingBySlug);
    usort($all, static function (array $a, array $b): int {
        $aTs = (int) ($a['published_at_ts'] ?? 0);
        $bTs = (int) ($b['published_at_ts'] ?? 0);
        if ($aTs === $bTs) {
            return strcmp((string) ($a['slug'] ?? ''), (string) ($b['slug'] ?? ''));
        }
        return $bTs <=> $aTs;
    });
    $all = array_slice($all, 0, max(1, $maxArticles));

    qk_editorial_publish_php_pages($all, $appConfig);

    $bundle = [
        'ok' => true,
        'generated_at_ts' => $now,
        'generated_at' => gmdate('c', $now),
        'articles_count' => count($all),
        'created_in_run' => $created,
        'articles' => $all,
    ];

    qk_editorial_write_bundle($appConfig, $bundle);

    return $bundle;
}

function qk_editorial_publish_php_pages(array $articles, array $appConfig = []): void
{
    $siteRoot = qk_editorial_site_root();
    $dir = $siteRoot . '/editoriale';

    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return;
    }

    $indexPath = $dir . '/index.php';
    if (!is_file($indexPath)) {
        $indexBody = "<?php\ndeclare(strict_types=1);\n\nrequire __DIR__ . '/../pages/editoriale.php';\n";
        @file_put_contents($indexPath, $indexBody);
    }

    $activeSlugs = [];
    foreach ($articles as $article) {
        if (!is_array($article) || (string) ($article['status'] ?? '') !== 'published') {
            continue;
        }
        $slug = (string) ($article['slug'] ?? '');
        if ($slug !== '' && preg_match('/^[a-z0-9-]+$/', $slug)) {
            $activeSlugs[$slug] = true;
        }
    }
    foreach (glob($dir . '/*.php') ?: [] as $existingPath) {
        $base = basename((string) $existingPath);
        if ($base === 'index.php') {
            continue;
        }
        $slug = substr($base, 0, -4);
        if ($slug === false || isset($activeSlugs[$slug])) {
            continue;
        }
        if (preg_match('/^(analisi|retrospettiva)-[a-z0-9-]+$/', $slug)) {
            @unlink((string) $existingPath);
        }
    }

    foreach ($articles as $article) {
        if (!is_array($article)) {
            continue;
        }
        if ((string) ($article['status'] ?? '') !== 'published') {
            continue;
        }
        $slug = (string) ($article['slug'] ?? '');
        if ($slug === '' || !preg_match('/^[a-z0-9-]+$/', $slug)) {
            continue;
        }
        $path = $dir . '/' . $slug . '.php';
        $body = "<?php\ndeclare(strict_types=1);\n\n";
        $body .= '$editorialSlug = ' . var_export($slug, true) . ";\n";
        $body .= "require __DIR__ . '/../pages/editoriale-articolo.php';\n";
        @file_put_contents($path, $body, LOCK_EX);
    }

    qk_editorial_publish_glossary_pages($articles, $appConfig);
}

function qk_editorial_publish_glossary_pages(array $articles, array $appConfig = []): void
{
    $siteRoot = qk_editorial_site_root();
    $dir = $siteRoot . '/glossario';
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return;
    }

    $indexPath = $dir . '/index.php';
    if (!is_file($indexPath)) {
        $indexBody = "<?php\ndeclare(strict_types=1);\n\nrequire __DIR__ . '/../pages/glossario.php';\n";
        @file_put_contents($indexPath, $indexBody);
    }

    $registry = qk_editorial_update_glossary_registry($appConfig, $articles);
    $terms = is_array($registry['terms'] ?? null) ? $registry['terms'] : [];

    foreach ($terms as $slug => $payload) {
        if (!is_string($slug) || $slug === '' || !is_array($payload)) {
            continue;
        }
        $path = $dir . '/' . $slug . '.php';
        $body = "<?php\ndeclare(strict_types=1);\n\n";
        $body .= '$glossarySlug = ' . var_export($slug, true) . ";\n";
        $body .= '$glossaryTerm = ' . var_export((string) ($payload['term'] ?? ''), true) . ";\n";
        $body .= '$glossaryDefinition = ' . var_export((string) ($payload['definition'] ?? ''), true) . ";\n";
        $body .= "require __DIR__ . '/../pages/glossario-termine.php';\n";
        @file_put_contents($path, $body, LOCK_EX);
    }
}

function qk_editorial_glossary_registry_path(array $appConfig): string
{
    $dataDir = rtrim((string) ($appConfig['data_dir'] ?? (__DIR__ . '/../data')), '/');
    return $dataDir . '/editorial_glossary_latest.json';
}

function qk_editorial_load_glossary_registry(array $appConfig): array
{
    $path = qk_editorial_glossary_registry_path($appConfig);
    if (!is_file($path)) {
        return ['ok' => true, 'updated_at' => null, 'terms' => []];
    }
    $raw = @file_get_contents($path);
    if (!is_string($raw) || $raw === '') {
        return ['ok' => true, 'updated_at' => null, 'terms' => []];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return ['ok' => true, 'updated_at' => null, 'terms' => []];
    }
    $decoded['terms'] = is_array($decoded['terms'] ?? null) ? $decoded['terms'] : [];
    return $decoded;
}

function qk_editorial_write_glossary_registry(array $appConfig, array $registry): bool
{
    $path = qk_editorial_glossary_registry_path($appConfig);
    $encoded = json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) {
        return false;
    }

    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return false;
    }

    $tmp = sprintf('%s/.%s.tmp.%d.%s', $dir, basename($path), getmypid(), str_replace('.', '', uniqid('', true)));
    $written = @file_put_contents($tmp, $encoded . PHP_EOL, LOCK_EX);
    if ($written === false) {
        return false;
    }
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

function qk_editorial_update_glossary_registry(array $appConfig, array $articles): array
{
    $now = time();
    $registry = qk_editorial_load_glossary_registry($appConfig);
    $terms = is_array($registry['terms'] ?? null) ? $registry['terms'] : [];

    foreach ($articles as $article) {
        if (!is_array($article) || (string) ($article['status'] ?? '') !== 'published') {
            continue;
        }
        $slug = (string) ($article['slug'] ?? '');
        $entries = qk_editorial_collect_glossary_entries_from_article($article);
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $term = trim((string) ($entry['term'] ?? ''));
            $definition = trim((string) ($entry['definition'] ?? ''));
            if ($term === '' || $definition === '') {
                continue;
            }
            $termSlug = qk_editorial_term_slug($term);
            if ($termSlug === '') {
                continue;
            }
            $existing = is_array($terms[$termSlug] ?? null) ? $terms[$termSlug] : [];
            $seen = (int) ($existing['seen_count'] ?? 0) + 1;
            $terms[$termSlug] = [
                'term' => $term,
                'definition' => $definition,
                'first_seen_ts' => (int) ($existing['first_seen_ts'] ?? $now),
                'last_seen_ts' => $now,
                'seen_count' => $seen,
                'last_article_slug' => $slug,
                'source' => (string) ($entry['source'] ?? ($existing['source'] ?? 'article')),
            ];
        }
    }

    ksort($terms);
    $registry = [
        'ok' => true,
        'updated_at_ts' => $now,
        'updated_at' => gmdate('c', $now),
        'terms_count' => count($terms),
        'terms' => $terms,
    ];
    qk_editorial_write_glossary_registry($appConfig, $registry);
    return $registry;
}

function qk_editorial_collect_glossary_entries_from_article(array $article): array
{
    $out = [];
    $glossary = is_array($article['glossary'] ?? null) ? $article['glossary'] : [];
    foreach ($glossary as $item) {
        if (!is_array($item)) {
            continue;
        }
        $term = trim((string) ($item['term'] ?? ''));
        $definition = trim((string) ($item['definition'] ?? ''));
        if ($term === '' || $definition === '') {
            continue;
        }
        $out[] = [
            'term' => $term,
            'definition' => $definition,
            'source' => 'gpt',
        ];
    }

    $sections = is_array($article['sections'] ?? null) ? $article['sections'] : [];
    $detected = qk_editorial_detect_interesting_terms($sections);
    foreach ($detected as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $term = trim((string) ($entry['term'] ?? ''));
        if ($term === '') {
            continue;
        }
        $exists = false;
        foreach ($out as $existing) {
            if (mb_strtolower((string) ($existing['term'] ?? '')) === mb_strtolower($term)) {
                $exists = true;
                break;
            }
        }
        if ($exists) {
            continue;
        }
        $out[] = $entry;
    }

    return $out;
}

function qk_editorial_detect_interesting_terms(array $sections): array
{
    $dictionary = qk_editorial_interesting_term_definitions();
    if ($dictionary === []) {
        return [];
    }

    $text = '';
    foreach ($sections as $section) {
        if (!is_array($section)) {
            continue;
        }
        $text .= ' ' . (string) ($section['heading'] ?? '') . ' ' . (string) ($section['body'] ?? '');
    }
    $text = mb_strtolower($text);
    if (trim($text) === '') {
        return [];
    }

    $out = [];
    foreach ($dictionary as $term => $definition) {
        $t = mb_strtolower(trim((string) $term));
        if ($t === '' || trim((string) $definition) === '') {
            continue;
        }
        $pattern = '/\b' . preg_quote($t, '/') . '\b/u';
        if (!preg_match($pattern, $text)) {
            continue;
        }
        $out[] = [
            'term' => (string) $term,
            'definition' => (string) $definition,
            'source' => 'auto-detected',
        ];
    }
    return $out;
}

function qk_editorial_interesting_term_definitions(): array
{
    return [
        'ipocentro' => 'Punto in profondita dove inizia la rottura sismica.',
        'epicentro' => 'Punto in superficie verticale rispetto all ipocentro.',
        'magnitudo' => 'Misura dell energia rilasciata da un terremoto.',
        'faglia' => 'Frattura della crosta terrestre lungo cui i blocchi scorrono.',
        'replica' => 'Scossa successiva collegata allo stesso evento principale.',
        'sciame sismico' => 'Sequenza di molti eventi senza un mainshock nettamente dominante.',
        'meccanismo focale' => 'Descrizione del tipo di movimento avvenuto sulla faglia.',
        'intensita macrosismica' => 'Misura degli effetti osservati in superficie su persone e strutture.',
        'attenuazione sismica' => 'Riduzione dell ampiezza delle onde con distanza e caratteristiche del suolo.',
        'sismicita' => 'Frequenza e distribuzione dei terremoti in una data area.',
        'subduzione' => 'Processo in cui una placca tettonica scende sotto un altra.',
        'stress tettonico' => 'Forze accumulate nelle rocce che possono innescare rotture.',
    ];
}

function qk_editorial_load_live_events(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    if (!is_string($raw) || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || !is_array($decoded['events'] ?? null)) {
        return [];
    }

    $normalized = [];
    foreach ($decoded['events'] as $event) {
        if (!is_array($event)) {
            continue;
        }
        $eventNorm = qk_editorial_normalize_event($event);
        if ($eventNorm !== null) {
            $normalized[] = $eventNorm;
        }
    }

    usort($normalized, static function (array $a, array $b): int {
        return ((int) ($b['event_time_ts'] ?? 0)) <=> ((int) ($a['event_time_ts'] ?? 0));
    });

    return $normalized;
}

function qk_editorial_load_historical_events(string $dataDir, string $archivePath): array
{
    $events = [];

    if (is_file($archivePath) && class_exists('SQLite3')) {
        try {
            $db = new SQLite3($archivePath, SQLITE3_OPEN_READONLY);
            $query = "SELECT event_id, event_time_utc, event_time_ts, place, magnitude, depth_km, latitude, longitude, source_provider, source_url\n"
                . "FROM earthquake_events\n"
                . "WHERE magnitude IS NOT NULL AND magnitude >= 4.5\n"
                . "ORDER BY magnitude DESC, event_time_ts DESC\n"
                . "LIMIT 300";
            $result = $db->query($query);
            if ($result instanceof SQLite3Result) {
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $normalized = qk_editorial_normalize_event([
                        'id' => $row['event_id'] ?? '',
                        'event_time_utc' => $row['event_time_utc'] ?? '',
                        'event_time_ts' => $row['event_time_ts'] ?? null,
                        'place' => $row['place'] ?? '',
                        'magnitude' => $row['magnitude'] ?? null,
                        'depth_km' => $row['depth_km'] ?? null,
                        'latitude' => $row['latitude'] ?? null,
                        'longitude' => $row['longitude'] ?? null,
                        'source_provider' => $row['source_provider'] ?? '',
                        'source_url' => $row['source_url'] ?? '',
                    ]);
                    if ($normalized !== null) {
                        $events[] = $normalized;
                    }
                }
            }
            $db->close();
        } catch (Throwable) {
            // no-op
        }
    }

    foreach (glob($dataDir . '/event_history_*.json') ?: [] as $historyPath) {
        if (!is_string($historyPath) || !is_file($historyPath)) {
            continue;
        }
        $raw = file_get_contents($historyPath);
        if (!is_string($raw) || $raw === '') {
            continue;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !is_array($decoded['events'] ?? null)) {
            continue;
        }
        foreach ($decoded['events'] as $event) {
            if (!is_array($event)) {
                continue;
            }
            $normalized = qk_editorial_normalize_event($event);
            if ($normalized !== null) {
                $events[] = $normalized;
            }
        }
    }

    $deduped = [];
    foreach ($events as $event) {
        $id = (string) ($event['id'] ?? '');
        $fallback = sprintf('%s|%s|%s', (string) ($event['event_time_utc'] ?? ''), (string) ($event['place'] ?? ''), (string) ($event['magnitude'] ?? ''));
        $key = $id !== '' ? $id : $fallback;
        if (!isset($deduped[$key])) {
            $deduped[$key] = $event;
        }
    }

    $out = array_values($deduped);
    usort($out, static function (array $a, array $b): int {
        $aMag = isset($a['magnitude']) && is_numeric($a['magnitude']) ? (float) $a['magnitude'] : -INF;
        $bMag = isset($b['magnitude']) && is_numeric($b['magnitude']) ? (float) $b['magnitude'] : -INF;
        if ($aMag === $bMag) {
            return ((int) ($b['event_time_ts'] ?? 0)) <=> ((int) ($a['event_time_ts'] ?? 0));
        }
        return $bMag <=> $aMag;
    });

    return $out;
}

function qk_editorial_normalize_event(array $event): ?array
{
    $id = trim((string) ($event['id'] ?? $event['event_id'] ?? ''));
    $place = trim((string) ($event['place'] ?? 'Unknown area'));

    $magnitude = is_numeric($event['magnitude'] ?? null) ? (float) $event['magnitude'] : null;
    $depthKm = is_numeric($event['depth_km'] ?? null) ? abs((float) $event['depth_km']) : null;
    $latitude = is_numeric($event['latitude'] ?? null) ? (float) $event['latitude'] : null;
    $longitude = is_numeric($event['longitude'] ?? null) ? (float) $event['longitude'] : null;

    $eventTimeUtc = trim((string) ($event['event_time_utc'] ?? ''));
    $eventTimeTs = is_numeric($event['event_time_ts'] ?? null) ? (int) $event['event_time_ts'] : null;
    if ($eventTimeUtc === '' && $eventTimeTs !== null && $eventTimeTs > 0) {
        $eventTimeUtc = gmdate('c', $eventTimeTs);
    }
    if ($eventTimeTs === null && $eventTimeUtc !== '') {
        $parsed = strtotime($eventTimeUtc);
        if ($parsed !== false) {
            $eventTimeTs = $parsed;
            $eventTimeUtc = gmdate('c', $parsed);
        }
    }

    if ($eventTimeUtc === '' || $eventTimeTs === null || $eventTimeTs <= 0) {
        return null;
    }

    return [
        'id' => $id,
        'place' => $place,
        'magnitude' => $magnitude,
        'depth_km' => $depthKm,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'event_time_utc' => $eventTimeUtc,
        'event_time_ts' => $eventTimeTs,
        'source_provider' => trim((string) ($event['source_provider'] ?? 'Unknown')),
        'source_url' => trim((string) ($event['source_url'] ?? '')),
    ];
}

function qk_editorial_pick_latest_candidates(array $events): array
{
    if ($events === []) {
        return [];
    }

    $now = time();
    $inWindow = array_values(array_filter($events, static function (array $event) use ($now): bool {
        $ts = (int) ($event['event_time_ts'] ?? 0);
        return $ts > ($now - 172800);
    }));

    if ($inWindow === []) {
        $inWindow = array_slice($events, 0, 150);
    }

    $featured = array_values(array_filter($inWindow, static function (array $event): bool {
        return qk_editorial_should_publish_latest($event);
    }));

    usort($featured, static function (array $a, array $b): int {
        $aMag = (float) ($a['magnitude'] ?? -INF);
        $bMag = (float) ($b['magnitude'] ?? -INF);
        if ($aMag === $bMag) {
            return ((int) ($b['event_time_ts'] ?? 0)) <=> ((int) ($a['event_time_ts'] ?? 0));
        }
        return $bMag <=> $aMag;
    });

    return array_slice($featured, 0, 4);
}

function qk_editorial_pick_historical_candidates(array $events): array
{
    if ($events === []) {
        return [];
    }

    $maxTs = time() - (86400 * 120);
    $pool = array_values(array_filter($events, static function (array $event) use ($maxTs): bool {
        $ts = (int) ($event['event_time_ts'] ?? 0);
        return $ts > 0 && $ts <= $maxTs && qk_editorial_should_publish_historical($event);
    }));

    if ($pool === []) {
        $pool = array_values(array_filter($events, static function (array $event): bool {
            return qk_editorial_should_publish_historical($event);
        }));
    }

    usort($pool, static function (array $a, array $b): int {
        $aMag = (float) ($a['magnitude'] ?? -INF);
        $bMag = (float) ($b['magnitude'] ?? -INF);
        if ($aMag === $bMag) {
            return ((int) ($b['event_time_ts'] ?? 0)) <=> ((int) ($a['event_time_ts'] ?? 0));
        }
        return $bMag <=> $aMag;
    });

    return array_slice($pool, 0, 6);
}

function qk_editorial_build_rubric_decisions(
    array $liveEvents,
    array $historicalEvents,
    array $existingArticles,
    string $archivePath,
    int $now,
    array $appConfig = []
): array {
    $maxPerCycle = 5;
    $dailyMax = 12;
    $dailyCount = qk_editorial_count_articles_in_hours($existingArticles, 24, $now);
    if ($dailyCount >= $dailyMax) {
        return [];
    }

    $decisions = [];
    $latestCandidates = qk_editorial_pick_latest_candidates($liveEvents);
    $historicalCandidates = qk_editorial_pick_historical_candidates($historicalEvents);

    foreach ($latestCandidates as $event) {
        $area = qk_editorial_area_normalized((string) ($event['place'] ?? ''));
        if (qk_editorial_recent_area_hit($existingArticles, $area, 6, $now)) {
            continue;
        }
        if (qk_editorial_recent_area_type_hit($existingArticles, $area, 'event_live', 12, $now)) {
            continue;
        }
        $context = qk_editorial_compute_context($event, $archivePath, $liveEvents);
        $decisions[] = [
            'type' => 'event_live',
            'prompt_key' => 'event_live',
            'title' => sprintf('Terremoto M%s in %s', qk_editorial_format_mag((float) ($event['magnitude'] ?? 0.0)), $area),
            'area' => $area,
            'priority' => 1,
            'source_scope' => 'live',
            'event' => $event,
            'data' => [
                'magnitude' => $event['magnitude'] ?? null,
                'place' => $event['place'] ?? '',
                'lat' => $event['latitude'] ?? null,
                'lon' => $event['longitude'] ?? null,
                'depth' => $event['depth_km'] ?? null,
                'datetime' => $event['event_time_utc'] ?? '',
                'source' => $event['source_provider'] ?? '',
                'damage_info' => $event['damage_info'] ?? 'n.d.',
                'recent_events' => (int) ($context['nearby_count_48h'] ?? 0),
                'weekly_context' => (int) ($context['nearby_count_7d'] ?? 0),
            ],
        ];
        break;
    }

    $sequence = qk_editorial_pick_sequence_candidate($liveEvents, $now);
    if (is_array($sequence)) {
        $area = (string) ($sequence['area'] ?? '');
        if (!qk_editorial_recent_area_type_hit($existingArticles, $area, 'sequence_active', 12, $now)) {
            $decisions[] = [
                'type' => 'sequence_active',
                'prompt_key' => 'sequence_active',
                'title' => sprintf('Sequenza sismica in corso in %s', $area),
                'area' => $area,
                'priority' => 2,
                'source_scope' => 'dynamic',
                'event' => is_array($sequence['event'] ?? null) ? $sequence['event'] : [],
                'data' => [
                    'cluster_data' => $sequence['cluster_data'] ?? [],
                ],
            ];
        }
    }

    $zone = qk_editorial_pick_zone_active_candidate($historicalEvents, $now);
    if (is_array($zone)) {
        $area = (string) ($zone['area'] ?? '');
        if (!qk_editorial_recent_area_type_hit($existingArticles, $area, 'zone_active', 168, $now)) {
            $decisions[] = [
                'type' => 'zone_active',
                'prompt_key' => 'zone_active',
                'title' => sprintf('Attivita persistente in %s', $area),
                'area' => $area,
                'priority' => 3,
                'source_scope' => 'dynamic',
                'event' => is_array($zone['event'] ?? null) ? $zone['event'] : [],
                'data' => [
                    'area_data' => $zone['area_data'] ?? [],
                ],
            ];
        }
    }

    foreach ($liveEvents as $event) {
        if (!qk_editorial_is_italy_region($event)) {
            continue;
        }
        $mag = is_numeric($event['magnitude'] ?? null) ? (float) $event['magnitude'] : 0.0;
        if ($mag < 4.0) {
            continue;
        }
        $area = qk_editorial_area_normalized((string) ($event['place'] ?? 'Italia'));
        if (qk_editorial_recent_area_type_hit($existingArticles, $area, 'focus_italy', 12, $now)) {
            continue;
        }
        if (qk_editorial_recent_area_type_hit($existingArticles, $area, 'event_live', 6, $now)) {
            continue;
        }
        $context = qk_editorial_compute_context($event, $archivePath, $liveEvents);
        $decisions[] = [
            'type' => 'focus_italy',
            'prompt_key' => 'focus_italy',
            'title' => sprintf('Focus Italia: attivita recente in %s', $area),
            'area' => $area,
            'priority' => 4,
            'source_scope' => 'dynamic',
            'event' => $event,
            'data' => [
                'italy_data' => [
                    'area' => $area,
                    'recent_events_48h' => (int) ($context['nearby_count_48h'] ?? 0),
                    'recent_events_7d' => (int) ($context['nearby_count_7d'] ?? 0),
                    'max_magnitude_7d' => $context['nearby_max_magnitude'] ?? null,
                    'event' => $event,
                ],
            ],
        ];
        break;
    }

    foreach ($existingArticles as $article) {
        if (!is_array($article)) {
            continue;
        }
        if ((string) ($article['type'] ?? '') !== 'event_live') {
            continue;
        }
        $event = is_array($article['event'] ?? null) ? $article['event'] : [];
        $eventTs = (int) ($event['event_time_ts'] ?? 0);
        if ($eventTs <= 0) {
            continue;
        }
        $hours = (int) floor(($now - $eventTs) / 3600);
        if ($hours < 72 || $hours > 168) {
            continue;
        }
        $area = qk_editorial_area_normalized((string) ($event['place'] ?? 'Area sconosciuta'));
        if (qk_editorial_recent_area_type_hit($existingArticles, $area, 'retrospective', 24, $now)) {
            continue;
        }
        $context = qk_editorial_compute_context($event, $archivePath, $liveEvents);
        $decisions[] = [
            'type' => 'retrospective',
            'prompt_key' => 'retrospective',
            'title' => sprintf('Retrospettiva: %s dopo %d ore', $area, $hours),
            'area' => $area,
            'priority' => 5,
            'source_scope' => 'dynamic',
            'event' => $event,
            'data' => [
                'event_data' => $event,
                'aftershock_data' => [
                    'nearby_count_48h' => (int) ($context['nearby_count_48h'] ?? 0),
                    'nearby_count_7d' => (int) ($context['nearby_count_7d'] ?? 0),
                ],
                'trend_data' => $context,
                'source_event_reference' => (string) ($article['slug'] ?? ''),
            ],
        ];
        break;
    }

    $historicalPreferred = null;
    foreach ($historicalCandidates as $candidate) {
        if (!is_array($candidate)) {
            continue;
        }
        $ageYears = qk_editorial_event_age_years($candidate, $now);
        if ($ageYears >= 10) {
            $historicalPreferred = $candidate;
            break;
        }
    }
    if (!is_array($historicalPreferred)) {
        foreach ($historicalCandidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }
            $ageYears = qk_editorial_event_age_years($candidate, $now);
            $mag = is_numeric($candidate['magnitude'] ?? null) ? (float) $candidate['magnitude'] : 0.0;
            if ($ageYears >= 5 && $mag >= 7.5) {
                error_log(sprintf('qk_editorial: using exceptional recent event_historical candidate (year=%d, age=%d, mag=%.1f)', qk_editorial_event_year($candidate), $ageYears, $mag));
                $historicalPreferred = $candidate;
                break;
            }
        }
    }

    if (is_array($historicalPreferred)) {
        $event = $historicalPreferred;
        $area = qk_editorial_area_normalized((string) ($event['place'] ?? 'Area sconosciuta'));
        if (!qk_editorial_recent_area_type_hit($existingArticles, $area, 'event_historical', 24, $now)) {
            $context = qk_editorial_compute_context($event, $archivePath, $liveEvents);
            $decisions[] = [
                'type' => 'event_historical',
                'prompt_key' => 'event_historical',
                'title' => sprintf('Il terremoto di %s: analisi tecnica', $area),
                'area' => $area,
                'priority' => 8,
                'source_scope' => 'historical',
                'event' => $event,
                'data' => [
                    'event_data' => $event,
                    'sequence_info' => $context,
                    'related_events' => [
                        'nearby_count_7d' => (int) ($context['nearby_count_7d'] ?? 0),
                        'nearby_max_magnitude' => $context['nearby_max_magnitude'] ?? null,
                    ],
                ],
            ];
        }
    }

    foreach ($historicalCandidates as $event) {
        if (!is_array($event)) {
            continue;
        }
        if (!qk_editorial_is_valid_historical_today_event($event, $now, $appConfig)) {
            error_log(sprintf('skip historical_today: event too recent (%d, age %d years)', qk_editorial_event_year($event), qk_editorial_event_age_years($event, $now)));
            continue;
        }
        $area = qk_editorial_area_normalized((string) ($event['place'] ?? 'Area sconosciuta'));
        if (qk_editorial_recent_area_type_hit($existingArticles, $area, 'historical_today', 24, $now)) {
            continue;
        }
        $context = qk_editorial_compute_context($event, $archivePath, $liveEvents);
        if (!qk_editorial_historical_today_has_concrete_comparison($event, $context)) {
            error_log(sprintf('skip historical_today: weak then-vs-now comparison signal (%s)', (string) ($event['id'] ?? 'unknown')));
            continue;
        }
        $decisions[] = [
            'type' => 'historical_today',
            'prompt_key' => 'historical_today',
            'title' => sprintf('Se accadesse oggi: sisma storico in %s', $area),
            'area' => $area,
            'priority' => 9,
            'source_scope' => 'historical',
            'event' => $event,
            'data' => [
                'event_data' => $event,
            ],
        ];
        break;
    }

    usort($decisions, static function (array $a, array $b): int {
        $ap = (int) ($a['priority'] ?? 999);
        $bp = (int) ($b['priority'] ?? 999);
        if ($ap === $bp) {
            return strcmp((string) ($a['type'] ?? ''), (string) ($b['type'] ?? ''));
        }
        return $ap <=> $bp;
    });

    $selected = [];
    $seenAreaType = [];
    foreach ($decisions as $decision) {
        if (count($selected) >= $maxPerCycle) {
            break;
        }
        $type = (string) ($decision['type'] ?? '');
        $area = strtolower(trim((string) ($decision['area'] ?? '')));
        if ($type === '' || $area === '') {
            continue;
        }
        $key = $type . '|' . $area;
        if (isset($seenAreaType[$key])) {
            continue;
        }
        $seenAreaType[$key] = true;
        $selected[] = $decision;
    }

    return $selected;
}

function qk_editorial_pick_sequence_candidate(array $liveEvents, int $now): ?array
{
    $pool = [];
    foreach ($liveEvents as $event) {
        if (!is_array($event)) {
            continue;
        }
        $ts = (int) ($event['event_time_ts'] ?? 0);
        if ($ts <= ($now - 72 * 3600)) {
            continue;
        }
        if (!is_numeric($event['latitude'] ?? null) || !is_numeric($event['longitude'] ?? null)) {
            continue;
        }
        $mag = is_numeric($event['magnitude'] ?? null) ? (float) $event['magnitude'] : 0.0;
        if (!qk_editorial_is_italy_region($event) && $mag < 4.5) {
            continue;
        }
        if (qk_editorial_is_italy_region($event) && $mag < 3.5) {
            continue;
        }
        $pool[] = $event;
    }
    if (count($pool) < 5) {
        return null;
    }

    usort($pool, static function (array $a, array $b): int {
        return ((int) ($b['event_time_ts'] ?? 0)) <=> ((int) ($a['event_time_ts'] ?? 0));
    });
    $seed = $pool[0];
    $lat = (float) $seed['latitude'];
    $lon = (float) $seed['longitude'];
    $cluster = [];
    foreach ($pool as $event) {
        $d = qk_editorial_distance_km($lat, $lon, (float) $event['latitude'], (float) $event['longitude']);
        if ($d <= 50.0) {
            $cluster[] = $event;
        }
    }
    if (count($cluster) < 5) {
        return null;
    }

    $magnitudes = array_values(array_filter(array_map(static fn(array $e): ?float => is_numeric($e['magnitude'] ?? null) ? (float) $e['magnitude'] : null, $cluster)));
    $depths = array_values(array_filter(array_map(static fn(array $e): ?float => is_numeric($e['depth_km'] ?? null) ? (float) $e['depth_km'] : null, $cluster)));
    $area = qk_editorial_area_normalized((string) ($seed['place'] ?? 'Area sconosciuta'));
    $startTs = min(array_map(static fn(array $e): int => (int) ($e['event_time_ts'] ?? 0), $cluster));
    $endTs = max(array_map(static fn(array $e): int => (int) ($e['event_time_ts'] ?? 0), $cluster));

    return [
        'area' => $area,
        'event' => $seed,
        'cluster_data' => [
            'area' => $area,
            'events_count' => count($cluster),
            'magnitude_min' => $magnitudes === [] ? null : min($magnitudes),
            'magnitude_max' => $magnitudes === [] ? null : max($magnitudes),
            'depth_min' => $depths === [] ? null : min($depths),
            'depth_max' => $depths === [] ? null : max($depths),
            'start_time_utc' => gmdate('c', $startTs),
            'end_time_utc' => gmdate('c', $endTs),
            'events' => array_slice($cluster, 0, 30),
        ],
    ];
}

function qk_editorial_pick_zone_active_candidate(array $events, int $now): ?array
{
    $startTs = $now - (7 * 86400);
    $groups = [];
    foreach ($events as $event) {
        if (!is_array($event)) {
            continue;
        }
        $ts = (int) ($event['event_time_ts'] ?? 0);
        if ($ts <= 0 || $ts < $startTs) {
            continue;
        }
        $mag = is_numeric($event['magnitude'] ?? null) ? (float) $event['magnitude'] : 0.0;
        if (!qk_editorial_is_italy_region($event) && $mag < 4.5) {
            continue;
        }
        if (qk_editorial_is_italy_region($event) && $mag < 3.5) {
            continue;
        }
        $area = qk_editorial_area_normalized((string) ($event['place'] ?? 'Area sconosciuta'));
        $groups[$area][] = $event;
    }

    if ($groups === []) {
        return null;
    }

    uasort($groups, static function (array $a, array $b): int {
        return count($b) <=> count($a);
    });
    $area = (string) array_key_first($groups);
    $group = $groups[$area] ?? [];
    if (count($group) < 15) {
        return null;
    }
    $event = $group[0];
    $magnitudes = array_values(array_filter(array_map(static fn(array $e): ?float => is_numeric($e['magnitude'] ?? null) ? (float) $e['magnitude'] : null, $group)));
    $depths = array_values(array_filter(array_map(static fn(array $e): ?float => is_numeric($e['depth_km'] ?? null) ? (float) $e['depth_km'] : null, $group)));

    return [
        'area' => $area,
        'event' => $event,
        'area_data' => [
            'area' => $area,
            'events_7d' => count($group),
            'magnitude_min' => $magnitudes === [] ? null : min($magnitudes),
            'magnitude_max' => $magnitudes === [] ? null : max($magnitudes),
            'depth_min' => $depths === [] ? null : min($depths),
            'depth_max' => $depths === [] ? null : max($depths),
            'sample_events' => array_slice($group, 0, 50),
        ],
    ];
}

function qk_editorial_count_articles_in_hours(array $articles, int $hours, int $now): int
{
    $minTs = $now - ($hours * 3600);
    $count = 0;
    foreach ($articles as $article) {
        if (!is_array($article)) {
            continue;
        }
        $ts = (int) ($article['published_at_ts'] ?? 0);
        if ($ts <= 0) {
            continue;
        }
        if ($ts >= $minTs) {
            $count++;
        }
    }
    return $count;
}

function qk_editorial_recent_area_hit(array $articles, string $area, int $hours, int $now): bool
{
    $minTs = $now - ($hours * 3600);
    $target = strtolower(trim($area));
    foreach ($articles as $article) {
        if (!is_array($article)) {
            continue;
        }
        $ts = (int) ($article['published_at_ts'] ?? 0);
        if ($ts < $minTs) {
            continue;
        }
        $articleArea = strtolower(trim((string) ($article['area_normalized'] ?? '')));
        if ($articleArea !== '' && $articleArea === $target) {
            return true;
        }
    }
    return false;
}

function qk_editorial_recent_area_type_hit(array $articles, string $area, string $type, int $hours, int $now): bool
{
    $minTs = $now - ($hours * 3600);
    $targetArea = strtolower(trim($area));
    foreach ($articles as $article) {
        if (!is_array($article)) {
            continue;
        }
        $ts = (int) ($article['published_at_ts'] ?? 0);
        if ($ts < $minTs) {
            continue;
        }
        if ((string) ($article['type'] ?? '') !== $type) {
            continue;
        }
        $articleArea = strtolower(trim((string) ($article['area_normalized'] ?? '')));
        if ($articleArea !== '' && $articleArea === $targetArea) {
            return true;
        }
    }
    return false;
}

function qk_editorial_event_year(array $event): int
{
    $ts = (int) ($event['event_time_ts'] ?? 0);
    if ($ts > 0) {
        return (int) gmdate('Y', $ts);
    }
    $iso = trim((string) ($event['event_time_utc'] ?? ''));
    if ($iso !== '') {
        $parsed = strtotime($iso);
        if (is_int($parsed) && $parsed > 0) {
            return (int) gmdate('Y', $parsed);
        }
    }
    return 0;
}

function qk_editorial_event_age_years(array $event, int $now): int
{
    $ts = (int) ($event['event_time_ts'] ?? 0);
    if ($ts <= 0) {
        $iso = trim((string) ($event['event_time_utc'] ?? ''));
        if ($iso !== '') {
            $parsed = strtotime($iso);
            if (is_int($parsed) && $parsed > 0) {
                $ts = $parsed;
            }
        }
    }
    if ($ts <= 0 || $now <= $ts) {
        return 0;
    }
    return (int) floor(($now - $ts) / (365.25 * 86400));
}

function qk_editorial_is_valid_historical_today_event(array $event, int $now, array $appConfig = []): bool
{
    $ageYears = qk_editorial_event_age_years($event, $now);
    if ($ageYears < 15) {
        return false;
    }

    $editorialCfg = is_array($appConfig['editorial'] ?? null) ? $appConfig['editorial'] : [];
    $allowRecent = in_array(
        strtolower((string) ($editorialCfg['allow_recent_historical_today'] ?? (getenv('QUAKRS_EDITORIAL_ALLOW_RECENT_HISTORICAL_TODAY') ?: '0'))),
        ['1', 'true', 'yes', 'on'],
        true
    );
    $cutoffYear = (int) ($editorialCfg['historical_today_cutoff_year'] ?? (getenv('QUAKRS_EDITORIAL_HISTORICAL_TODAY_CUTOFF_YEAR') ?: 2010));
    if ($cutoffYear <= 0) {
        $cutoffYear = 2010;
    }

    $year = qk_editorial_event_year($event);
    if (!$allowRecent && $year > $cutoffYear) {
        return false;
    }

    return true;
}

function qk_editorial_historical_today_has_concrete_comparison(array $event, array $context): bool
{
    $hasCore = is_numeric($event['magnitude'] ?? null)
        && is_numeric($event['depth_km'] ?? null)
        && is_numeric($event['latitude'] ?? null)
        && is_numeric($event['longitude'] ?? null);
    if (!$hasCore) {
        return false;
    }

    $hasSource = trim((string) ($event['source_url'] ?? '')) !== '';
    $hasModernSignal = (int) ($context['nearby_count_7d'] ?? 0) > 0
        || (int) ($context['nearby_count_48h'] ?? 0) > 0
        || is_numeric($context['nearby_max_magnitude'] ?? null);

    return $hasSource && $hasModernSignal;
}

function qk_editorial_area_normalized(string $place): string
{
    $value = trim($place);
    if ($value === '') {
        return 'Area sconosciuta';
    }
    $value = preg_replace('/^\s*\d+\s*km\s+[NSEW]{1,3}\s+of\s+/i', '', $value);
    $value = preg_replace('/^\s*near the coast of\s+/i', '', (string) $value);
    $value = preg_replace('/\s{2,}/', ' ', (string) $value);
    return trim((string) $value, " \t\n\r\0\x0B,");
}

function qk_editorial_build_article(
    string $type,
    array $event,
    string $archivePath,
    array $liveEvents,
    int $now,
    array $appConfig = [],
    bool $allowGpt = true,
    string $promptKey = '',
    array $promptData = [],
    string $titleOverride = '',
    string $areaOverride = ''
): ?array
{
    $eventTs = (int) ($event['event_time_ts'] ?? 0);
    if ($eventTs <= 0) {
        return null;
    }

    $id = trim((string) ($event['id'] ?? ''));
    if ($id === '') {
        $id = 'evt-' . md5((string) ($event['event_time_utc'] ?? '') . '|' . (string) ($event['place'] ?? '') . '|' . (string) ($event['magnitude'] ?? ''));
    }

    $place = trim((string) ($event['place'] ?? 'Area sconosciuta'));
    $placeShort = qk_editorial_shorten_place($place);
    $magnitude = is_numeric($event['magnitude'] ?? null) ? (float) $event['magnitude'] : null;
    $depth = is_numeric($event['depth_km'] ?? null) ? (float) $event['depth_km'] : null;

    $context = qk_editorial_compute_context($event, $archivePath, $liveEvents);
    $dateIso = gmdate('Y-m-d', $eventTs);

    if ($type === 'historical_today' && !qk_editorial_is_valid_historical_today_event($event, $now, $appConfig)) {
        error_log(sprintf('skip historical_today: event too recent (%d, age %d years)', qk_editorial_event_year($event), qk_editorial_event_age_years($event, $now)));
        return null;
    }

    if ($type === 'event_historical' || $type === 'historical') {
        $title = sprintf('Retrospettiva sisma M%s del %s in %s: contesto e lettura operativa', qk_editorial_format_mag($magnitude), $dateIso, $placeShort);
        $eyebrow = 'Retrospettiva storica';
        $excerpt = sprintf('Analisi dell evento del %s (%s) con confronto sui segnali recenti e implicazioni operative.', $dateIso, $placeShort);
        $slug = 'retrospettiva-' . $dateIso . '-' . qk_editorial_slugify($id . '-' . $placeShort);
    } elseif ($type === 'sequence_active') {
        $area = $areaOverride !== '' ? $areaOverride : $placeShort;
        $title = sprintf('Sequenza sismica in corso in %s: evoluzione operativa', $area);
        $eyebrow = 'Sequenza attiva';
        $excerpt = sprintf('Analisi della sequenza in %s con distribuzione eventi e variazioni nelle ultime 72 ore.', $area);
        $slug = 'sequenza-' . $dateIso . '-' . qk_editorial_slugify($id . '-' . $area);
    } elseif ($type === 'zone_active') {
        $area = $areaOverride !== '' ? $areaOverride : $placeShort;
        $title = sprintf('%s resta tra le zone sismiche attive: quadro a 7 giorni', $area);
        $eyebrow = 'Zona attiva';
        $excerpt = sprintf('Lettura della persistenza sismica in %s su base settimanale, con trend principali e continuita temporale.', $area);
        $slug = 'zona-attiva-' . $dateIso . '-' . qk_editorial_slugify($id . '-' . $area);
    } elseif ($type === 'focus_italy') {
        $area = $areaOverride !== '' ? $areaOverride : $placeShort;
        $title = sprintf('Focus Italia: attivita recente in %s', $area);
        $eyebrow = 'Focus Italia';
        $excerpt = sprintf('Analisi territoriale del quadro sismico recente in %s con implicazioni operative locali.', $area);
        $slug = 'focus-italia-' . $dateIso . '-' . qk_editorial_slugify($id . '-' . $area);
    } elseif ($type === 'retrospective') {
        $area = $areaOverride !== '' ? $areaOverride : $placeShort;
        $title = sprintf('Retrospettiva su %s: evoluzione dopo il sisma del %s', $area, $dateIso);
        $eyebrow = 'Retrospettiva';
        $excerpt = sprintf('Evoluzione tecnica dell evento di %s con analisi del comportamento successivo della sequenza.', $area);
        $slug = 'retrospettiva-followup-' . $dateIso . '-' . qk_editorial_slugify($id . '-' . $area);
    } elseif ($type === 'comparison') {
        $area = $areaOverride !== '' ? $areaOverride : $placeShort;
        $title = sprintf('Confronto tecnico sismico: %s', $area);
        $eyebrow = 'Confronto';
        $excerpt = sprintf('Analisi comparativa tra due eventi con focus su differenze, analogie e implicazioni operative in %s.', $area);
        $slug = 'confronto-' . $dateIso . '-' . qk_editorial_slugify($id . '-' . $area);
    } elseif ($type === 'timeline_sequence') {
        $area = $areaOverride !== '' ? $areaOverride : $placeShort;
        $title = sprintf('Timeline sequenza sismica in %s', $area);
        $eyebrow = 'Timeline';
        $excerpt = sprintf('Ricostruzione temporale tecnica della sequenza in %s con fasi principali e stato finale.', $area);
        $slug = 'timeline-' . $dateIso . '-' . qk_editorial_slugify($id . '-' . $area);
    } elseif ($type === 'historical_today') {
        $area = $areaOverride !== '' ? $areaOverride : $placeShort;
        $title = sprintf('Se accadesse oggi: rilettura tecnica del sisma in %s', $area);
        $eyebrow = 'Storico nel presente';
        $excerpt = sprintf('Valutazione tecnica dell evento storico in %s nel contesto operativo attuale.', $area);
        $slug = 'storico-oggi-' . $dateIso . '-' . qk_editorial_slugify($id . '-' . $area);
    } else {
        $title = sprintf('Terremoto M%s in %s: quadro e implicazioni operative', qk_editorial_format_mag($magnitude), $placeShort);
        $eyebrow = 'Analisi evento recente';
        $excerpt = sprintf('Analisi dell evento, contesto locale a 7 giorni e punti da monitorare in %s.', $placeShort);
        $slug = 'analisi-' . $dateIso . '-' . qk_editorial_slugify($id . '-' . $placeShort);
    }

    if (!$allowGpt) {
        return null;
    }

    $effectivePromptKey = trim($promptKey) !== '' ? trim($promptKey) : $type;
    $gptCopy = qk_editorial_gpt_compose($appConfig, $effectivePromptKey, $event, $context, $promptData);
    if (!is_array($gptCopy)) {
        return null;
    }
    if (trim($titleOverride) !== '') {
        $title = trim($titleOverride);
    } else {
        $title = trim((string) ($gptCopy['title'] ?? $title));
    }
    $excerpt = trim((string) ($gptCopy['excerpt'] ?? $excerpt));
    $sections = is_array($gptCopy['sections'] ?? null)
        ? $gptCopy['sections']
        : [];
    $glossary = qk_editorial_normalize_glossary(is_array($gptCopy['glossary'] ?? null) ? $gptCopy['glossary'] : []);
    if ($sections === []) {
        return null;
    }
    $glossary = qk_editorial_merge_glossary_with_detected_terms($glossary, $sections);
    $wordCount = 0;
    foreach ($sections as $section) {
        if (!is_array($section)) {
            continue;
        }
        $wordCount += str_word_count((string) ($section['body'] ?? ''), 0, '0123456789àèéìòùÀÈÉÌÒÙ-');
    }

    $quality = qk_editorial_quality_score($event, $sections, $wordCount, $context);
    if ($quality['score'] < 70) {
        return null;
    }

    $publishedTs = max($now, $eventTs + 600);
    $eventFingerprint = qk_editorial_event_fingerprint($event);
    $areaNormalized = $areaOverride !== '' ? $areaOverride : qk_editorial_area_normalized($place);

    return [
        'id' => $id,
        'slug' => $slug,
        'status' => 'published',
        'type' => $type,
        'generation_mode' => 'gpt',
        'prompt_key' => $effectivePromptKey,
        'locale' => 'it',
        'eyebrow' => $eyebrow,
        'title' => $title,
        'excerpt' => $excerpt,
        'seo_title' => $title . ' | Quakrs Editoriale',
        'seo_description' => $excerpt,
        'published_at_ts' => $publishedTs,
        'published_at' => gmdate('c', $publishedTs),
        'updated_at_ts' => $now,
        'updated_at' => gmdate('c', $now),
        'event_fingerprint' => $eventFingerprint,
        'word_count' => $wordCount,
        'quality_score' => $quality['score'],
        'quality_checks' => $quality['checks'],
        'area_normalized' => $areaNormalized,
        'url' => '/editoriale/' . $slug . '.php',
        'event' => [
            'id' => $id,
            'place' => $place,
            'place_short' => $placeShort,
            'magnitude' => $magnitude,
            'depth_km' => $depth,
            'latitude' => is_numeric($event['latitude'] ?? null) ? (float) $event['latitude'] : null,
            'longitude' => is_numeric($event['longitude'] ?? null) ? (float) $event['longitude'] : null,
            'event_time_utc' => (string) ($event['event_time_utc'] ?? ''),
            'event_time_ts' => $eventTs,
            'source_provider' => (string) ($event['source_provider'] ?? 'Unknown'),
            'source_url' => (string) ($event['source_url'] ?? ''),
        ],
        'context' => $context,
        'sections' => $sections,
        'glossary' => $glossary,
        'internal_links' => [
            '/event.php?id=' . rawurlencode($id),
            '/earthquakes.php',
            '/archive.php',
            '/data-archive.php',
            '/timeline.php',
            '/alerts.php',
        ],
    ];
}

function qk_editorial_normalize_glossary(array $glossary): array
{
    $out = [];
    foreach ($glossary as $item) {
        if (!is_array($item)) {
            continue;
        }
        $term = trim((string) ($item['term'] ?? ''));
        $definition = trim((string) ($item['definition'] ?? ''));
        if ($term === '' || $definition === '') {
            continue;
        }
        if (mb_strlen($term) > 48 || mb_strlen($definition) > 220) {
            continue;
        }
        $out[] = [
            'term' => $term,
            'definition' => $definition,
        ];
        if (count($out) >= 8) {
            break;
        }
    }
    if ($out === []) {
        return qk_editorial_default_glossary('latest');
    }
    return $out;
}

function qk_editorial_merge_glossary_with_detected_terms(array $glossary, array $sections): array
{
    $merged = qk_editorial_normalize_glossary($glossary);
    $seen = [];
    foreach ($merged as $item) {
        if (!is_array($item)) {
            continue;
        }
        $k = mb_strtolower(trim((string) ($item['term'] ?? '')));
        if ($k !== '') {
            $seen[$k] = true;
        }
    }

    $detected = qk_editorial_detect_interesting_terms($sections);
    foreach ($detected as $item) {
        if (!is_array($item)) {
            continue;
        }
        $term = trim((string) ($item['term'] ?? ''));
        $definition = trim((string) ($item['definition'] ?? ''));
        if ($term === '' || $definition === '') {
            continue;
        }
        $k = mb_strtolower($term);
        if (isset($seen[$k])) {
            continue;
        }
        $merged[] = ['term' => $term, 'definition' => $definition];
        $seen[$k] = true;
        if (count($merged) >= 10) {
            break;
        }
    }

    return $merged;
}

function qk_editorial_default_glossary(string $type): array
{
    if ($type === 'historical') {
        return [
            ['term' => 'ipocentro', 'definition' => 'Punto in profondita dove inizia la rottura sismica.'],
            ['term' => 'epicentro', 'definition' => 'Punto in superficie verticale rispetto all ipocentro.'],
            ['term' => 'magnitudo', 'definition' => 'Misura dell energia rilasciata dal terremoto.'],
            ['term' => 'faglia', 'definition' => 'Frattura della crosta terrestre dove avviene lo scorrimento.'],
        ];
    }
    return [
        ['term' => 'ipocentro', 'definition' => 'Punto in profondita dove si genera il terremoto.'],
        ['term' => 'epicentro', 'definition' => 'Posizione in superficie sopra l origine del sisma.'],
        ['term' => 'magnitudo', 'definition' => 'Valore che descrive la forza energetica del sisma.'],
        ['term' => 'replica', 'definition' => 'Scossa successiva legata allo stesso processo sismico.'],
    ];
}

function qk_editorial_build_sections(string $type, array $event, array $context): array
{
    $eventTime = (string) ($event['event_time_utc'] ?? '');
    $eventTs = (int) ($event['event_time_ts'] ?? 0);
    $eventDate = $eventTs > 0 ? gmdate('Y-m-d', $eventTs) : substr($eventTime, 0, 10);
    $place = trim((string) ($event['place'] ?? 'Area sconosciuta'));
    $magnitude = qk_editorial_format_mag(is_numeric($event['magnitude'] ?? null) ? (float) $event['magnitude'] : null);
    $depth = qk_editorial_format_depth(is_numeric($event['depth_km'] ?? null) ? (float) $event['depth_km'] : null);
    $provider = trim((string) ($event['source_provider'] ?? 'Unknown'));

    $clusterCount = (int) ($context['nearby_count_7d'] ?? 0);
    $maxNear = qk_editorial_format_mag(is_numeric($context['nearby_max_magnitude'] ?? null) ? (float) $context['nearby_max_magnitude'] : null);
    $window = (string) ($context['window_days'] ?? '7');

    $summary = sprintf(
        'Il %s, nell area %s, i dati sismici disponibili indicano un evento di magnitudo %s con profondita %s. Il riferimento principale proviene da %s ed e utile per leggere priorita e contesto operativo. In questa lettura, la domanda chiave non e \"quanto e forte in assoluto\", ma se i parametri mostrano stabilita o cambiamento progressivo nella stessa zona.',
        $eventDate,
        $place,
        $magnitude,
        $depth,
        $provider
    );

    $dataBlock = sprintf(
        'Coordinate evento: %s, %s. Nella finestra di %s giorni attorno a questo punto risultano %d eventi entro ~250 km, con magnitudo massima %s. Questo aiuta a distinguere tra episodio isolato e area con attivita distribuita. Se il conteggio resta basso e la magnitudo massima non cresce, il quadro tende a rimanere contenuto; se entrambi salgono, aumenta la priorita di monitoraggio.',
        qk_editorial_format_coord($event['latitude'] ?? null),
        qk_editorial_format_coord($event['longitude'] ?? null),
        $window,
        $clusterCount,
        $maxNear
    );

    if ($type === 'historical') {
        return [
            [
                'heading' => 'Evento storico in breve',
                'body' => $summary,
            ],
            [
                'heading' => 'Confronto con segnali recenti',
                'body' => $dataBlock . ' In ottica storica, il confronto serve a misurare persistenza e ricorrenza nella stessa macro-area, non a predire un singolo evento futuro.',
            ],
            [
                'heading' => 'Lettura operativa',
                'body' => 'Per uso operativo quotidiano conviene verificare: trend magnitudo locale, profondita prevalente, variazione del numero eventi nelle ultime 24-72 ore e convergenza tra provider. Quando questi indicatori si muovono insieme, il monitoraggio va intensificato. Nella pratica, confrontare due o tre finestre temporali consecutive riduce il rischio di interpretare un picco breve come cambio strutturale.',
            ],
            [
                'heading' => 'Limiti e affidabilita',
                'body' => 'Questa retrospettiva non sostituisce fonti ufficiali di emergenza. I numeri possono essere aggiornati quando i provider ricalcolano magnitudo, coordinate o profondita. Il valore editoriale sta nel mettere in sequenza i dati utili in modo rapido e verificabile, mantenendo un tono prudente.',
            ],
        ];
    }

    return [
        [
            'heading' => 'Sintesi operativa',
            'body' => $summary,
        ],
        [
            'heading' => 'Dati chiave e contesto locale',
            'body' => $dataBlock,
        ],
        [
            'heading' => 'Cosa monitorare nelle prossime ore',
            'body' => 'Nella lettura operativa conviene controllare eventuali repliche con magnitudo in crescita, spostamento della zona attiva e variazione profondita. Un aumento combinato di questi fattori e un segnale piu utile del singolo numero di eventi. Conviene osservare anche la distanza media tra eventi successivi: quando si riduce insieme all aumento del conteggio, il quadro operativo merita attenzione aggiuntiva.',
        ],
        [
            'heading' => 'Limiti dell analisi',
            'body' => 'Il testo si basa su dati tecnici che possono riflettere ritardi o revisioni di catalogo. Per decisioni di sicurezza personale o territoriale fare sempre riferimento alle autorita ufficiali competenti. L obiettivo di questa scheda e supportare il monitoraggio informativo e la contestualizzazione, non sostituire un bollettino istituzionale.',
        ],
    ];
}

function qk_editorial_quality_score(array $event, array $sections, int $wordCount, array $context): array
{
    $score = 100;
    $checks = [];

    $hasMag = isset($event['magnitude']) && is_numeric($event['magnitude']);
    $hasDepth = isset($event['depth_km']) && is_numeric($event['depth_km']);
    $hasCoords = isset($event['latitude'], $event['longitude']) && is_numeric($event['latitude']) && is_numeric($event['longitude']);
    $hasSource = trim((string) ($event['source_url'] ?? '')) !== '';
    $hasSections = count($sections) >= 4;
    $hasContext = isset($context['nearby_count_7d']);

    if (!$hasMag) {
        $score -= 20;
    }
    if (!$hasDepth) {
        $score -= 15;
    }
    if (!$hasCoords) {
        $score -= 15;
    }
    if (!$hasSource) {
        $score -= 10;
    }
    if (!$hasSections) {
        $score -= 20;
    }
    if (!$hasContext) {
        $score -= 10;
    }
    if ($wordCount < 220) {
        $score -= 20;
    }

    $checks[] = ['name' => 'magnitude_present', 'ok' => $hasMag];
    $checks[] = ['name' => 'depth_present', 'ok' => $hasDepth];
    $checks[] = ['name' => 'coords_present', 'ok' => $hasCoords];
    $checks[] = ['name' => 'source_url_present', 'ok' => $hasSource];
    $checks[] = ['name' => 'sections_ready', 'ok' => $hasSections];
    $checks[] = ['name' => 'context_ready', 'ok' => $hasContext];
    $checks[] = ['name' => 'min_word_count', 'ok' => $wordCount >= 220];

    return [
        'score' => max(0, min(100, $score)),
        'checks' => $checks,
    ];
}

function qk_editorial_compute_context(array $event, string $archivePath, array $liveEvents): array
{
    $context = [
        'window_days' => 7,
        'recent_window_hours' => 48,
        'nearby_count_48h' => 0,
        'nearby_count_7d' => 0,
        'nearby_max_magnitude' => null,
    ];

    $targetTs = (int) ($event['event_time_ts'] ?? 0);
    $lat = is_numeric($event['latitude'] ?? null) ? (float) $event['latitude'] : null;
    $lon = is_numeric($event['longitude'] ?? null) ? (float) $event['longitude'] : null;

    if ($targetTs <= 0 || $lat === null || $lon === null) {
        return $context;
    }

    $windowStart48h = $targetTs - (3600 * 48);
    $windowStart = $targetTs - (86400 * 7);
    $windowEnd = $targetTs + 86400;

    $count = 0;
    $count48h = 0;
    $maxMag = null;

    if (is_file($archivePath) && class_exists('SQLite3')) {
        try {
            $db = new SQLite3($archivePath, SQLITE3_OPEN_READONLY);

            $latDelta = 3.0;
            $lonDelta = 3.0;
            $stmt = $db->prepare(
                'SELECT event_time_ts, magnitude, latitude, longitude FROM earthquake_events '
                . 'WHERE event_time_ts BETWEEN :start AND :end '
                . 'AND latitude BETWEEN :latMin AND :latMax '
                . 'AND longitude BETWEEN :lonMin AND :lonMax'
            );
            if ($stmt instanceof SQLite3Stmt) {
                $stmt->bindValue(':start', $windowStart, SQLITE3_INTEGER);
                $stmt->bindValue(':end', $windowEnd, SQLITE3_INTEGER);
                $stmt->bindValue(':latMin', $lat - $latDelta, SQLITE3_FLOAT);
                $stmt->bindValue(':latMax', $lat + $latDelta, SQLITE3_FLOAT);
                $stmt->bindValue(':lonMin', $lon - $lonDelta, SQLITE3_FLOAT);
                $stmt->bindValue(':lonMax', $lon + $lonDelta, SQLITE3_FLOAT);
                $result = $stmt->execute();
                if ($result instanceof SQLite3Result) {
                    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                        if (!is_array($row)) {
                            continue;
                        }
                        $rowLat = is_numeric($row['latitude'] ?? null) ? (float) $row['latitude'] : null;
                        $rowLon = is_numeric($row['longitude'] ?? null) ? (float) $row['longitude'] : null;
                        if ($rowLat === null || $rowLon === null) {
                            continue;
                        }
                        if (qk_editorial_distance_km($lat, $lon, $rowLat, $rowLon) > 250.0) {
                            continue;
                        }
                        $count++;
                        $rowTs = is_numeric($row['event_time_ts'] ?? null) ? (int) $row['event_time_ts'] : 0;
                        if ($rowTs >= $windowStart48h) {
                            $count48h++;
                        }
                        if (is_numeric($row['magnitude'] ?? null)) {
                            $mag = (float) $row['magnitude'];
                            $maxMag = $maxMag === null ? $mag : max($maxMag, $mag);
                        }
                    }
                }
            }
            $db->close();
        } catch (Throwable) {
            // no-op
        }
    }

    if ($count === 0) {
        foreach ($liveEvents as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rowTs = (int) ($row['event_time_ts'] ?? 0);
            if ($rowTs < $windowStart || $rowTs > $windowEnd) {
                continue;
            }
            $rowLat = is_numeric($row['latitude'] ?? null) ? (float) $row['latitude'] : null;
            $rowLon = is_numeric($row['longitude'] ?? null) ? (float) $row['longitude'] : null;
            if ($rowLat === null || $rowLon === null) {
                continue;
            }
            if (qk_editorial_distance_km($lat, $lon, $rowLat, $rowLon) > 250.0) {
                continue;
            }
            $count++;
            if ($rowTs >= $windowStart48h) {
                $count48h++;
            }
            if (is_numeric($row['magnitude'] ?? null)) {
                $mag = (float) $row['magnitude'];
                $maxMag = $maxMag === null ? $mag : max($maxMag, $mag);
            }
        }
    }

    $context['nearby_count_48h'] = $count48h;
    $context['nearby_count_7d'] = $count;
    $context['nearby_max_magnitude'] = $maxMag;

    return $context;
}

function qk_editorial_is_italy_region(array $event): bool
{
    $lat = isset($event['latitude']) && is_numeric($event['latitude']) ? (float) $event['latitude'] : null;
    $lon = isset($event['longitude']) && is_numeric($event['longitude']) ? (float) $event['longitude'] : null;
    if ($lat === null || $lon === null) {
        return false;
    }
    return $lat >= 35.0 && $lat <= 48.8 && $lon >= 6.0 && $lon <= 19.6;
}

function qk_editorial_is_sensitive_location(array $event): bool
{
    if (qk_editorial_is_italy_region($event)) {
        return true;
    }

    $place = strtolower((string) ($event['place'] ?? ''));
    if ($place === '') {
        return false;
    }

    foreach (['italy', 'italia', 'sicily', 'sicilia', 'campi flegrei', 'etna'] as $needle) {
        if (str_contains($place, $needle)) {
            return true;
        }
    }

    return false;
}

function qk_editorial_should_publish_latest(array $event): bool
{
    if (!is_numeric($event['magnitude'] ?? null)) {
        return false;
    }
    $mag = (float) $event['magnitude'];
    if ($mag >= 7.0) {
        return true;
    }
    if (qk_editorial_is_sensitive_location($event)) {
        return $mag >= 5.5;
    }
    return false;
}

function qk_editorial_should_publish_historical(array $event): bool
{
    if (!is_numeric($event['magnitude'] ?? null)) {
        return false;
    }
    $mag = (float) $event['magnitude'];
    if ($mag >= 7.0) {
        return true;
    }
    if (qk_editorial_is_sensitive_location($event)) {
        return $mag >= 5.0;
    }
    return false;
}

function qk_editorial_should_keep_article(array $article): bool
{
    $type = (string) ($article['type'] ?? 'event_live');
    $event = is_array($article['event'] ?? null) ? $article['event'] : [];
    if ($event === []) {
        return false;
    }

    $now = time();
    if ($type === 'historical_today') {
        return qk_editorial_is_valid_historical_today_event($event, $now, []);
    }
    if ($type === 'event_historical' || $type === 'historical') {
        $ageYears = qk_editorial_event_age_years($event, $now);
        if ($ageYears >= 10) {
            return true;
        }
        $mag = is_numeric($event['magnitude'] ?? null) ? (float) $event['magnitude'] : 0.0;
        return $ageYears >= 5 && $mag >= 7.5;
    }

    if ($type === 'event_live' || $type === 'latest') {
        return qk_editorial_should_publish_latest($event);
    }

    // Additional rubric types are managed by cycle limits/dedup, not by strict magnitude gates.
    return true;
}

function qk_editorial_preserve_publication_fields(array $next, array $current, int $now): array
{
    $publishedTs = (int) ($current['published_at_ts'] ?? 0);
    if ($publishedTs > 0) {
        $next['published_at_ts'] = $publishedTs;
        $next['published_at'] = gmdate('c', $publishedTs);
    } else {
        $publishedAt = trim((string) ($current['published_at'] ?? ''));
        if ($publishedAt !== '') {
            $parsed = strtotime($publishedAt);
            if (is_int($parsed) && $parsed > 0) {
                $next['published_at_ts'] = $parsed;
                $next['published_at'] = gmdate('c', $parsed);
            }
        }
    }

    $next['updated_at_ts'] = $now;
    $next['updated_at'] = gmdate('c', $now);

    return $next;
}

function qk_editorial_apply_non_live_weekly_cap(array $articlesBySlug, int $maxPerWeek = 2): array
{
    if ($maxPerWeek < 1 || $articlesBySlug === []) {
        return $articlesBySlug;
    }

    uasort($articlesBySlug, static function ($a, $b): int {
        $aTs = is_array($a) ? (int) ($a['published_at_ts'] ?? 0) : 0;
        $bTs = is_array($b) ? (int) ($b['published_at_ts'] ?? 0) : 0;
        if ($aTs === $bTs) {
            $aSlug = is_array($a) ? (string) ($a['slug'] ?? '') : '';
            $bSlug = is_array($b) ? (string) ($b['slug'] ?? '') : '';
            return strcmp($aSlug, $bSlug);
        }
        return $bTs <=> $aTs;
    });

    $kept = [];
    $countByWeek = [];

    foreach ($articlesBySlug as $slug => $article) {
        if (!is_string($slug) || $slug === '' || !is_array($article)) {
            continue;
        }

        $type = (string) ($article['type'] ?? 'event_live');
        if ($type === 'event_live' || $type === 'latest') {
            $kept[$slug] = $article;
            continue;
        }

        $publishedTs = (int) ($article['published_at_ts'] ?? 0);
        if ($publishedTs <= 0) {
            $publishedAt = trim((string) ($article['published_at'] ?? ''));
            if ($publishedAt !== '') {
                $parsed = strtotime($publishedAt);
                $publishedTs = is_int($parsed) ? $parsed : 0;
            }
        }
        if ($publishedTs <= 0) {
            $eventTs = (int) ($article['event']['event_time_ts'] ?? 0);
            $publishedTs = $eventTs > 0 ? $eventTs : time();
        }

        $weekKey = gmdate('o-W', $publishedTs);
        $used = (int) ($countByWeek[$weekKey] ?? 0);
        if ($used >= $maxPerWeek) {
            continue;
        }

        $countByWeek[$weekKey] = $used + 1;
        $kept[$slug] = $article;
    }

    return $kept;
}

function qk_editorial_shorten_place(string $place): string
{
    $trimmed = trim($place);
    if ($trimmed === '') {
        return 'area sconosciuta';
    }
    if (mb_strlen($trimmed) <= 56) {
        return $trimmed;
    }
    return mb_substr($trimmed, 0, 56) . '...';
}

function qk_editorial_slugify(string $text): string
{
    $base = strtolower(trim($text));
    $base = preg_replace('/[^a-z0-9]+/i', '-', $base);
    $base = trim((string) $base, '-');
    if ($base === '') {
        $base = 'articolo';
    }
    return substr($base, 0, 86);
}

function qk_editorial_term_slug(string $term): string
{
    $slug = qk_editorial_slugify($term);
    if ($slug === '') {
        return '';
    }
    return substr($slug, 0, 56);
}

function qk_editorial_format_mag(?float $mag): string
{
    if ($mag === null) {
        return 'n/d';
    }
    return number_format($mag, 1, '.', '');
}

function qk_editorial_predict_slug(string $type, array $event): string
{
    return qk_editorial_predict_slug_for_type($type, $event);
}

function qk_editorial_predict_slug_for_type(string $type, array $event): string
{
    $eventTs = (int) ($event['event_time_ts'] ?? 0);
    if ($eventTs <= 0) {
        return '';
    }
    $id = trim((string) ($event['id'] ?? ''));
    if ($id === '') {
        $id = 'evt-' . md5((string) ($event['event_time_utc'] ?? '') . '|' . (string) ($event['place'] ?? '') . '|' . (string) ($event['magnitude'] ?? ''));
    }
    $place = trim((string) ($event['place'] ?? 'Area sconosciuta'));
    $placeShort = qk_editorial_shorten_place($place);
    $dateIso = gmdate('Y-m-d', $eventTs);
    if ($type === 'event_historical' || $type === 'historical') {
        return 'retrospettiva-' . $dateIso . '-' . qk_editorial_slugify($id . '-' . $placeShort);
    }
    if ($type === 'sequence_active') {
        return 'sequenza-' . $dateIso . '-' . qk_editorial_slugify($id . '-' . $placeShort);
    }
    if ($type === 'zone_active') {
        return 'zona-attiva-' . $dateIso . '-' . qk_editorial_slugify($id . '-' . $placeShort);
    }
    if ($type === 'focus_italy') {
        return 'focus-italia-' . $dateIso . '-' . qk_editorial_slugify($id . '-' . $placeShort);
    }
    if ($type === 'retrospective') {
        return 'retrospettiva-followup-' . $dateIso . '-' . qk_editorial_slugify($id . '-' . $placeShort);
    }
    if ($type === 'comparison') {
        return 'confronto-' . $dateIso . '-' . qk_editorial_slugify($id . '-' . $placeShort);
    }
    if ($type === 'timeline_sequence') {
        return 'timeline-' . $dateIso . '-' . qk_editorial_slugify($id . '-' . $placeShort);
    }
    if ($type === 'historical_today') {
        return 'storico-oggi-' . $dateIso . '-' . qk_editorial_slugify($id . '-' . $placeShort);
    }
    return 'analisi-' . $dateIso . '-' . qk_editorial_slugify($id . '-' . $placeShort);
}

function qk_editorial_event_fingerprint(array $event): string
{
    $payload = [
        'id' => (string) ($event['id'] ?? ''),
        'event_time_utc' => (string) ($event['event_time_utc'] ?? ''),
        'event_time_ts' => (int) ($event['event_time_ts'] ?? 0),
        'place' => trim((string) ($event['place'] ?? '')),
        'magnitude' => is_numeric($event['magnitude'] ?? null) ? number_format((float) $event['magnitude'], 3, '.', '') : null,
        'depth_km' => is_numeric($event['depth_km'] ?? null) ? number_format((float) $event['depth_km'], 3, '.', '') : null,
        'latitude' => is_numeric($event['latitude'] ?? null) ? number_format((float) $event['latitude'], 5, '.', '') : null,
        'longitude' => is_numeric($event['longitude'] ?? null) ? number_format((float) $event['longitude'], 5, '.', '') : null,
        'source_provider' => (string) ($event['source_provider'] ?? ''),
        'source_url' => (string) ($event['source_url'] ?? ''),
    ];
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        $json = serialize($payload);
    }
    return sha1($json);
}

function qk_editorial_article_event_changed(?array $article, array $event): bool
{
    if (!is_array($article)) {
        return true;
    }
    $nextFp = qk_editorial_event_fingerprint($event);
    $currentFp = trim((string) ($article['event_fingerprint'] ?? ''));
    if ($currentFp !== '') {
        return !hash_equals($currentFp, $nextFp);
    }
    $currentEvent = is_array($article['event'] ?? null) ? $article['event'] : [];
    if ($currentEvent === []) {
        return true;
    }
    return !hash_equals(qk_editorial_event_fingerprint($currentEvent), $nextFp);
}

function qk_editorial_format_depth(?float $depth): string
{
    if ($depth === null) {
        return 'n/d';
    }
    return number_format($depth, 1, '.', '') . ' km';
}

function qk_editorial_format_coord(mixed $value): string
{
    if (!is_numeric($value)) {
        return 'n/d';
    }
    return number_format((float) $value, 3, '.', '');
}

function qk_editorial_distance_km(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $earthRadiusKm = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadiusKm * $c;
}
