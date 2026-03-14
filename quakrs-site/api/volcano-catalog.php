<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$cachePath = $appConfig['data_dir'] . '/volcano_catalog_latest.json';
$now = time();
$cacheTtl = max(3600, min(7 * 86400, (int) ($appConfig['volcano_catalog_ttl_seconds'] ?? 86400)));
$catalogSchemaVersion = 2;
$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';

$cachedPayload = read_json_file($cachePath);
$cacheAge = is_array($cachedPayload) && isset($cachedPayload['generated_at_ts'])
    ? $now - (int) $cachedPayload['generated_at_ts']
    : null;

if (!$forceRefresh && is_array($cachedPayload) && is_int($cacheAge) && $cacheAge <= $cacheTtl) {
    $catalogRows = isset($cachedPayload['catalog']) && is_array($cachedPayload['catalog'])
        ? $cachedPayload['catalog']
        : [];
    $sample = (count($catalogRows) > 0 && is_array($catalogRows[0])) ? $catalogRows[0] : null;
    $hasEruptionSchema = is_array($sample) && array_key_exists('eruption_start_date', $sample) && array_key_exists('eruption_type', $sample);
    $sameSchemaVersion = (int) ($cachedPayload['schema_version'] ?? 0) === $catalogSchemaVersion;
    if ($hasEruptionSchema && $sameSchemaVersion) {
        $normalizedCached = normalize_cached_catalog_continents($cachedPayload);
        if ($normalizedCached !== $cachedPayload) {
            write_json_file($cachePath, $normalizedCached);
        }
        $cachedPayload = $normalizedCached;
        $cachedPayload['from_cache'] = true;
        $cachedPayload['stale_cache'] = false;
        json_response(200, $cachedPayload);
    }
}

function infer_continent(string $regionGroup, string $country, ?float $lat): string
{
    $group = strtolower(trim($regionGroup));
    $countryKey = strtolower(trim($country));

    $countryContinentMap = [
        'algeria' => 'Africa',
        'antarctica' => 'Antarctica',
        'argentina' => 'South America',
        'armenia' => 'Asia',
        'armenia-azerbaijan' => 'Asia',
        'australia' => 'Oceania',
        'bolivia' => 'South America',
        'burma (myanmar)' => 'Asia',
        'cabo verde' => 'Africa',
        'cameroon' => 'Africa',
        'canada' => 'North America',
        'chad' => 'Africa',
        'chile' => 'South America',
        'chile-argentina' => 'South America',
        'chile-bolivia' => 'South America',
        'china' => 'Asia',
        'china-north korea' => 'Asia',
        'colombia' => 'South America',
        'colombia-ecuador' => 'South America',
        'costa rica' => 'North America',
        'dr congo' => 'Africa',
        'dr congo-rwanda' => 'Africa',
        'djibouti' => 'Africa',
        'dominica' => 'North America',
        'ecuador' => 'South America',
        'el salvador' => 'North America',
        'equatorial guinea' => 'Africa',
        'eritrea' => 'Africa',
        'eritrea-djibouti' => 'Africa',
        'ethiopia' => 'Africa',
        'ethiopia-djibouti' => 'Africa',
        'ethiopia-eritrea' => 'Africa',
        'ethiopia-eritrea-djibouti' => 'Africa',
        'fiji' => 'Oceania',
        'france' => 'Europe',
        'france - claimed by vanuatu' => 'Oceania',
        'georgia' => 'Asia',
        'germany' => 'Europe',
        'greece' => 'Europe',
        'grenada' => 'North America',
        'guatemala' => 'North America',
        'guatemala-el salvador' => 'North America',
        'honduras' => 'North America',
        'iceland' => 'Europe',
        'india' => 'Asia',
        'indonesia' => 'Asia',
        'iran' => 'Asia',
        'italy' => 'Europe',
        'japan' => 'Asia',
        'japan - administered by russia' => 'Asia',
        'kenya' => 'Africa',
        'mexico' => 'North America',
        'mexico-guatemala' => 'North America',
        'mongolia' => 'Asia',
        'netherlands' => 'Europe',
        'new zealand' => 'Oceania',
        'nicaragua' => 'North America',
        'niger' => 'Africa',
        'norway' => 'Europe',
        'panama' => 'North America',
        'papua new guinea' => 'Oceania',
        'peru' => 'South America',
        'philippines' => 'Asia',
        'portugal' => 'Europe',
        'russia' => 'Asia',
        'saint kitts and nevis' => 'North America',
        'saint lucia' => 'North America',
        'saint vincent and the grenadines' => 'North America',
        'samoa' => 'Oceania',
        'saudi arabia' => 'Asia',
        'solomon islands' => 'Oceania',
        'south africa' => 'Africa',
        'south korea' => 'Asia',
        'spain' => 'Europe',
        'sudan' => 'Africa',
        'syria-jordan-saudi arabia' => 'Asia',
        'taiwan' => 'Asia',
        'tanzania' => 'Africa',
        'tonga' => 'Oceania',
        'turkiye' => 'Asia',
        'uganda' => 'Africa',
        'undersea features' => 'Unknown',
        'union of the comoros' => 'Africa',
        'united kingdom' => 'Europe',
        'united states' => 'North America',
        'vanuatu' => 'Oceania',
        'vietnam' => 'Asia',
        'yemen' => 'Asia',
    ];
    if (isset($countryContinentMap[$countryKey])) {
        return $countryContinentMap[$countryKey];
    }

    if (str_contains($group, 'north america') || str_contains($group, 'central america') || str_contains($group, 'caribbean')) {
        return 'North America';
    }
    if (str_contains($group, 'south america')) return 'South America';
    if (str_contains($group, 'afric')) return 'Africa';
    if (str_contains($group, 'antarctic')) return 'Antarctica';
    if (str_contains($group, 'europe') || str_contains($group, 'mediterranean')) return 'Europe';
    if (
        str_contains($group, 'asia') ||
        str_contains($group, 'asian') ||
        str_contains($group, 'east asia') ||
        str_contains($group, 'eastern asia') ||
        str_contains($group, 'western asia') ||
        str_contains($group, 'central asia') ||
        str_contains($group, 'southeast') ||
        str_contains($group, 'middle east') ||
        str_contains($group, 'arabia') ||
        str_contains($group, 'indian ocean') ||
        str_contains($group, 'northwestern pacific') ||
        str_contains($group, 'western pacific')
    ) {
        return 'Asia';
    }
    if (
        str_contains($group, 'southwest pacific') ||
        str_contains($group, 'australian') ||
        str_contains($group, 'new zealand') ||
        str_contains($group, 'melanesia') ||
        str_contains($group, 'polynesia')
    ) {
        return 'Oceania';
    }

    if (is_float($lat)) {
        if ($lat < -60) return 'Antarctica';
        if ($lat < -5) return 'Oceania';
        if ($lat < 15) return 'Africa';
        if ($lat < 40) return 'Asia';
        if ($lat < 66) return 'Europe';
        return 'North America';
    }

    return 'Unknown';
}

function normalize_cached_catalog_continents(array $payload): array
{
    $catalog = isset($payload['catalog']) && is_array($payload['catalog']) ? $payload['catalog'] : [];
    if ($catalog === []) {
        return $payload;
    }

    $changed = false;
    foreach ($catalog as $idx => $row) {
        if (!is_array($row)) {
            continue;
        }
        $country = isset($row['country']) ? (string) $row['country'] : '';
        $regionGroup = isset($row['region_group']) ? (string) $row['region_group'] : '';
        $lat = isset($row['latitude']) && is_numeric($row['latitude']) ? (float) $row['latitude'] : null;
        $normalized = infer_continent($regionGroup, $country, $lat);
        $current = isset($row['continent']) ? (string) $row['continent'] : '';
        if ($normalized !== $current) {
            $catalog[$idx]['continent'] = $normalized;
            $changed = true;
        }
    }

    if (!$changed) {
        return $payload;
    }

    $payload['catalog'] = $catalog;
    return $payload;
}

function parse_float_value(string $value): ?float
{
    $trimmed = trim($value);
    if ($trimmed === '' || !is_numeric($trimmed)) {
        return null;
    }
    return (float) $trimmed;
}

function clean_html_cell(string $html): string
{
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text ?? '') ?? '';
    return trim($text);
}

function normalize_merge_part(string $value): string
{
    $trimmed = trim($value);
    if ($trimmed === '') {
        return '';
    }
    $lower = function_exists('mb_strtolower')
        ? mb_strtolower($trimmed, 'UTF-8')
        : strtolower($trimmed);
    $normalized = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $lower ?? '') ?? '';
    return trim($normalized);
}

function parse_current_eruptions(string $html): array
{
    $result = [
        'by_number' => [],
        'by_name' => [],
    ];

    if (!preg_match('/<tbody>(.*?)<\/tbody>/si', $html, $tbodyMatch)) {
        return $result;
    }

    $rowMatches = [];
    preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', (string) ($tbodyMatch[1] ?? ''), $rowMatches);
    foreach ($rowMatches[1] ?? [] as $rowRaw) {
        $cellMatches = [];
        preg_match_all('/<td[^>]*>(.*?)<\/td>/si', (string) $rowRaw, $cellMatches);
        $cells = $cellMatches[1] ?? [];
        if (!is_array($cells) || count($cells) < 5) {
            continue;
        }

        $volcanoCell = (string) $cells[0];
        $country = clean_html_cell((string) $cells[1]);
        $eruptionStartDate = clean_html_cell((string) $cells[2]);
        $lastKnownActivity = clean_html_cell((string) $cells[3]);
        $eruptionType = clean_html_cell((string) $cells[4]);
        $volcano = clean_html_cell($volcanoCell);
        if ($volcano === '') {
            continue;
        }

        $volcanoNumber = '';
        if (preg_match('/volcano\.cfm\?vn=(\d+)/i', $volcanoCell, $vnMatch)) {
            $volcanoNumber = (string) $vnMatch[1];
        }

        $profileUrl = '';
        if ($volcanoNumber !== '') {
            $profileUrl = 'https://volcano.si.edu/volcano.cfm?vn=' . rawurlencode($volcanoNumber);
        }

        $entry = [
            'eruption_start_date' => $eruptionStartDate !== '' ? $eruptionStartDate : null,
            'eruption_type' => $eruptionType !== '' ? $eruptionType : null,
            'eruption_last_known_activity' => $lastKnownActivity !== '' ? $lastKnownActivity : null,
            'eruption_profile_url' => $profileUrl !== '' ? $profileUrl : null,
        ];

        if ($volcanoNumber !== '') {
            $result['by_number'][$volcanoNumber] = $entry;
        }

        $mergeKey = normalize_merge_part($volcano) . '|' . normalize_merge_part($country);
        if ($mergeKey !== '|') {
            $result['by_name'][$mergeKey] = $entry;
        }
    }

    return $result;
}

$catalogUrl = (string) ($feedConfig['volcano_catalog']['url'] ?? 'https://volcano.si.edu/database/list_volcano_holocene_excel.cfm');
$provider = (string) ($feedConfig['volcano_catalog']['provider'] ?? 'Smithsonian GVP VOTW');
$externalRaw = fetch_external_text($catalogUrl, max(20, (int) $appConfig['http_timeout_seconds']));
$currentEruptionsUrl = (string) ($feedConfig['volcano_current_eruptions']['url'] ?? 'https://volcano.si.edu/gvp_currenteruptions.cfm');

if (!is_string($externalRaw) || $externalRaw === '') {
    write_log($appConfig['logs_dir'], "Volcano catalog fetch failed: {$catalogUrl}");
    if (is_array($cachedPayload)) {
        $normalizedCached = normalize_cached_catalog_continents($cachedPayload);
        if ($normalizedCached !== $cachedPayload) {
            write_json_file($cachePath, $normalizedCached);
        }
        $cachedPayload = $normalizedCached;
        $cachedPayload['from_cache'] = true;
        $cachedPayload['stale_cache'] = true;
        json_response(200, $cachedPayload);
    }
    json_response(502, [
        'ok' => false,
        'error' => 'Unable to load volcano catalog',
    ]);
}

libxml_use_internal_errors(true);
$rowsMatches = [];
preg_match_all('/<Row>(.*?)<\/Row>/si', $externalRaw, $rowsMatches);
if (empty($rowsMatches[1])) {
    write_log($appConfig['logs_dir'], "Volcano catalog parse failed: {$catalogUrl}");
    if (is_array($cachedPayload)) {
        $cachedPayload['from_cache'] = true;
        $cachedPayload['stale_cache'] = true;
        json_response(200, $cachedPayload);
    }
    json_response(502, [
        'ok' => false,
        'error' => 'Invalid volcano catalog format',
    ]);
}

$headerSeen = false;
$catalog = [];
$currentEruptions = ['by_number' => [], 'by_name' => []];
$currentEruptionsRaw = fetch_external_text($currentEruptionsUrl, max(20, (int) $appConfig['http_timeout_seconds']));
if (is_string($currentEruptionsRaw) && $currentEruptionsRaw !== '') {
    $currentEruptions = parse_current_eruptions($currentEruptionsRaw);
} else {
    write_log($appConfig['logs_dir'], "Current eruptions feed fetch failed: {$currentEruptionsUrl}");
}

foreach ($rowsMatches[1] as $rowRaw) {
    $cells = [];
    $nextCol = 1;
    $cellMatches = [];
    preg_match_all('/<Cell([^>]*)>(.*?)<\/Cell>/si', $rowRaw, $cellMatches, PREG_SET_ORDER);
    foreach ($cellMatches as $cellParts) {
        $attrs = (string) ($cellParts[1] ?? '');
        $body = (string) ($cellParts[2] ?? '');
        if (preg_match('/ss:Index="(\d+)"/i', $attrs, $idxMatch)) {
            $nextCol = (int) $idxMatch[1];
        }
        $href = '';
        if (preg_match('/ss:HRef="([^"]+)"/i', $attrs, $hrefMatch)) {
            $href = html_entity_decode((string) $hrefMatch[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
        }
        $value = '';
        if (preg_match('/<Data[^>]*>(.*?)<\/Data>/si', $body, $valueMatch)) {
            $value = trim(html_entity_decode(strip_tags((string) $valueMatch[1]), ENT_QUOTES | ENT_XML1, 'UTF-8'));
        }
        $cells[$nextCol] = ['value' => $value, 'href' => $href];
        $nextCol += 1;
    }

    if (!$headerSeen) {
        if (($cells[1]['value'] ?? '') === 'Volcano Number') {
            $headerSeen = true;
        }
        continue;
    }

    $volcanoName = trim((string) ($cells[2]['value'] ?? ''));
    if ($volcanoName === '') {
        continue;
    }

    $volcanoNumber = trim((string) ($cells[1]['value'] ?? ''));
    $country = trim((string) ($cells[3]['value'] ?? ''));
    $regionGroup = trim((string) ($cells[4]['value'] ?? ''));
    $region = trim((string) ($cells[5]['value'] ?? ''));
    $landform = trim((string) ($cells[6]['value'] ?? ''));
    $primaryType = trim((string) ($cells[7]['value'] ?? ''));
    $activityEvidence = trim((string) ($cells[8]['value'] ?? ''));
    $lastKnownEruption = trim((string) ($cells[9]['value'] ?? ''));
    $lat = parse_float_value((string) ($cells[10]['value'] ?? ''));
    $lon = parse_float_value((string) ($cells[11]['value'] ?? ''));
    $elevationM = parse_float_value((string) ($cells[12]['value'] ?? ''));
    $tectonicSetting = trim((string) ($cells[13]['value'] ?? ''));
    $dominantRockType = trim((string) ($cells[14]['value'] ?? ''));
    $profileUrl = trim((string) ($cells[1]['href'] ?? ''));

    if ($profileUrl === '' && $volcanoNumber !== '') {
        $profileUrl = 'https://volcano.si.edu/volcano.cfm?vn=' . rawurlencode($volcanoNumber);
    }

    $mergeKey = normalize_merge_part($volcanoName) . '|' . normalize_merge_part($country);
    $eruption = null;
    if ($volcanoNumber !== '' && isset($currentEruptions['by_number'][$volcanoNumber]) && is_array($currentEruptions['by_number'][$volcanoNumber])) {
        $eruption = $currentEruptions['by_number'][$volcanoNumber];
    } elseif (isset($currentEruptions['by_name'][$mergeKey]) && is_array($currentEruptions['by_name'][$mergeKey])) {
        $eruption = $currentEruptions['by_name'][$mergeKey];
    }

    $catalog[] = [
        'volcano_number' => $volcanoNumber,
        'volcano' => $volcanoName,
        'country' => $country !== '' ? $country : 'Unknown',
        'continent' => infer_continent($regionGroup, $country, $lat),
        'region_group' => $regionGroup,
        'region' => $region,
        'landform' => $landform,
        'primary_type' => $primaryType,
        'activity_evidence' => $activityEvidence,
        'last_known_eruption' => $lastKnownEruption,
        'latitude' => $lat,
        'longitude' => $lon,
        'elevation_m' => $elevationM,
        'tectonic_setting' => $tectonicSetting,
        'dominant_rock_type' => $dominantRockType,
        'profile_url' => $profileUrl,
        'eruption_start_date' => is_array($eruption) ? ($eruption['eruption_start_date'] ?? null) : null,
        'eruption_type' => is_array($eruption) ? ($eruption['eruption_type'] ?? null) : null,
        'eruption_last_known_activity' => is_array($eruption) ? ($eruption['eruption_last_known_activity'] ?? null) : null,
        'is_currently_erupting' => is_array($eruption),
    ];
}

usort($catalog, static function (array $a, array $b): int {
    $countryCmp = strcasecmp((string) ($a['country'] ?? ''), (string) ($b['country'] ?? ''));
    if ($countryCmp !== 0) return $countryCmp;
    return strcasecmp((string) ($a['volcano'] ?? ''), (string) ($b['volcano'] ?? ''));
});

$payload = [
    'ok' => true,
    'schema_version' => $catalogSchemaVersion,
    'provider' => $provider,
    'generated_at_ts' => $now,
    'generated_at' => gmdate('c', $now),
    'catalog_count' => count($catalog),
    'catalog' => $catalog,
    'from_cache' => false,
];

if (!write_json_file($cachePath, $payload)) {
    write_log($appConfig['logs_dir'], 'Failed writing volcano catalog cache JSON');
}

json_response(200, $payload);
