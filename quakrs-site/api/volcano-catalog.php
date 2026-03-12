<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$cachePath = $appConfig['data_dir'] . '/volcano_catalog_latest.json';
$now = time();
$cacheTtl = max(3600, min(7 * 86400, (int) ($appConfig['volcano_catalog_ttl_seconds'] ?? 86400)));
$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';

$cachedPayload = read_json_file($cachePath);
$cacheAge = is_array($cachedPayload) && isset($cachedPayload['generated_at_ts'])
    ? $now - (int) $cachedPayload['generated_at_ts']
    : null;

if (!$forceRefresh && is_array($cachedPayload) && is_int($cacheAge) && $cacheAge <= $cacheTtl) {
    $cachedPayload['from_cache'] = true;
    $cachedPayload['stale_cache'] = false;
    json_response(200, $cachedPayload);
}

function infer_continent(string $regionGroup, string $country, ?float $lat): string
{
    $group = strtolower(trim($regionGroup));
    $countryKey = strtolower(trim($country));

    if (str_contains($group, 'europe')) return 'Europe';
    if (str_contains($group, 'north american')) return 'North America';
    if (str_contains($group, 'south american')) return 'South America';
    if (str_contains($group, 'afric')) return 'Africa';
    if (str_contains($group, 'antarctic')) return 'Antarctica';
    if (str_contains($group, 'middle east')) return 'Asia';
    if (str_contains($group, 'indian ocean')) return 'Asia';
    if (str_contains($group, 'southeast asian')) return 'Asia';
    if (str_contains($group, 'east asian')) return 'Asia';
    if (str_contains($group, 'west and central asian')) return 'Asia';
    if (str_contains($group, 'southwest pacific')) return 'Oceania';
    if (str_contains($group, 'australian')) return 'Oceania';
    if (str_contains($group, 'new zealand')) return 'Oceania';

    if ($countryKey === 'united states') {
        return is_float($lat) && $lat < -5 ? 'Oceania' : 'North America';
    }
    if ($countryKey === 'france') {
        return is_float($lat) && $lat < -10 ? 'Oceania' : 'Europe';
    }
    if ($countryKey === 'united kingdom') {
        return is_float($lat) && $lat < -30 ? 'Antarctica' : 'Europe';
    }
    if ($countryKey === 'chile') return 'South America';
    if ($countryKey === 'indonesia') return 'Asia';
    if ($countryKey === 'japan') return 'Asia';
    if ($countryKey === 'russia') {
        return is_float($lat) && $lat < 0 ? 'Asia' : 'Europe';
    }
    if ($countryKey === 'antarctica') return 'Antarctica';

    if (is_float($lat)) {
        if ($lat < -60) return 'Antarctica';
        if ($lat < -5) return 'South America';
        if ($lat < 12) return 'Asia';
        if ($lat < 35) return 'Africa';
        return 'Europe';
    }

    return 'Unknown';
}

function parse_float_value(string $value): ?float
{
    $trimmed = trim($value);
    if ($trimmed === '' || !is_numeric($trimmed)) {
        return null;
    }
    return (float) $trimmed;
}

$catalogUrl = (string) ($feedConfig['volcano_catalog']['url'] ?? 'https://volcano.si.edu/database/list_volcano_holocene_excel.cfm');
$provider = (string) ($feedConfig['volcano_catalog']['provider'] ?? 'Smithsonian GVP VOTW');
$externalRaw = fetch_external_text($catalogUrl, max(20, (int) $appConfig['http_timeout_seconds']));

if (!is_string($externalRaw) || $externalRaw === '') {
    write_log($appConfig['logs_dir'], "Volcano catalog fetch failed: {$catalogUrl}");
    if (is_array($cachedPayload)) {
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
    ];
}

usort($catalog, static function (array $a, array $b): int {
    $countryCmp = strcasecmp((string) ($a['country'] ?? ''), (string) ($b['country'] ?? ''));
    if ($countryCmp !== 0) return $countryCmp;
    return strcasecmp((string) ($a['volcano'] ?? ''), (string) ($b['volcano'] ?? ''));
});

$payload = [
    'ok' => true,
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
