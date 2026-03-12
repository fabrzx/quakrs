<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

function hotspot_status_from_title(string $title, bool $isNewEruptive): string
{
    if ($isNewEruptive) {
        return 'New Eruptive Activity';
    }

    $titleLower = strtolower($title);
    if (str_contains($titleLower, 'continuing eruptive')) {
        return 'Continuing Eruptive Activity';
    }
    if (str_contains($titleLower, 'unrest')) {
        return 'Volcanic Unrest';
    }

    return 'Elevated Volcanic Activity';
}

$cachePath = $appConfig['data_dir'] . '/hotspots_latest.json';
$now = time();
$cacheTtl = (int) $appConfig['cache_ttl_seconds'];
$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';

$cachedPayload = read_json_file($cachePath);
$cacheAge = is_array($cachedPayload) && isset($cachedPayload['generated_at_ts'])
    ? $now - (int) $cachedPayload['generated_at_ts']
    : null;

if (!$forceRefresh && is_array($cachedPayload)) {
    $cachedPayload['from_cache'] = true;
    $cachedPayload['stale_cache'] = !is_int($cacheAge) || $cacheAge > $cacheTtl;
    json_response(200, $cachedPayload);
}

$volcanoesPayload = read_json_file($appConfig['data_dir'] . '/volcanoes_latest.json');

if (!is_array($volcanoesPayload) || !isset($volcanoesPayload['events']) || !is_array($volcanoesPayload['events'])) {
    if (is_array($cachedPayload)) {
        $cachedPayload['from_cache'] = true;
        $cachedPayload['stale_cache'] = true;
        json_response(200, $cachedPayload);
    }

    json_response(502, [
        'ok' => false,
        'error' => 'Unable to build hotspots from volcano cache',
    ]);
}

$events = $volcanoesPayload['events'];
$hotspotsByVolcano = [];
$countryCounts = [];

foreach ($events as $event) {
    if (!is_array($event)) {
        continue;
    }

    $volcano = trim((string) ($event['volcano'] ?? $event['title'] ?? 'Unknown volcano'));
    $country = trim((string) ($event['country'] ?? 'Unknown'));
    $title = trim((string) ($event['title'] ?? ''));
    $eventTime = (string) ($event['event_time_utc'] ?? '');
    $isNewEruptive = (bool) ($event['is_new_eruptive'] ?? false);
    $sourceUrl = trim((string) ($event['source_url'] ?? ''));

    $countryCounts[$country] = ($countryCounts[$country] ?? 0) + 1;
    $key = strtolower($volcano . '|' . $country);

    if (!isset($hotspotsByVolcano[$key])) {
        $hotspotsByVolcano[$key] = [
            'volcano' => $volcano,
            'country' => $country,
            'reports' => 0,
            'new_eruptive_reports' => 0,
            'latest_event_utc' => $eventTime !== '' ? $eventTime : null,
            'status' => hotspot_status_from_title($title, $isNewEruptive),
            'source_url' => $sourceUrl,
        ];
    }

    $hotspotsByVolcano[$key]['reports'] += 1;
    if ($isNewEruptive) {
        $hotspotsByVolcano[$key]['new_eruptive_reports'] += 1;
        $hotspotsByVolcano[$key]['status'] = 'New Eruptive Activity';
    }

    $existingTs = isset($hotspotsByVolcano[$key]['latest_event_utc']) ? strtotime((string) $hotspotsByVolcano[$key]['latest_event_utc']) : 0;
    $incomingTs = $eventTime !== '' ? strtotime($eventTime) : 0;
    if (is_int($incomingTs) && $incomingTs > (is_int($existingTs) ? $existingTs : 0)) {
        $hotspotsByVolcano[$key]['latest_event_utc'] = $eventTime;
        if (!$isNewEruptive) {
            $hotspotsByVolcano[$key]['status'] = hotspot_status_from_title($title, false);
        }
        if ($sourceUrl !== '') {
            $hotspotsByVolcano[$key]['source_url'] = $sourceUrl;
        }
    }
}

$hotspots = array_values($hotspotsByVolcano);
usort($hotspots, static function (array $a, array $b): int {
    if ($b['new_eruptive_reports'] !== $a['new_eruptive_reports']) {
        return $b['new_eruptive_reports'] <=> $a['new_eruptive_reports'];
    }
    if ($b['reports'] !== $a['reports']) {
        return $b['reports'] <=> $a['reports'];
    }
    $aTs = isset($a['latest_event_utc']) ? strtotime((string) $a['latest_event_utc']) : 0;
    $bTs = isset($b['latest_event_utc']) ? strtotime((string) $b['latest_event_utc']) : 0;
    return $bTs <=> $aTs;
});
$hotspots = array_slice($hotspots, 0, 20);

arsort($countryCounts);
$topCountry = key($countryCounts);
$topCountryReports = $topCountry !== null ? (int) ($countryCounts[$topCountry] ?? 0) : 0;

$payload = [
    'ok' => true,
    'provider' => (string) ($volcanoesPayload['provider'] ?? 'Smithsonian GVP / USGS'),
    'generated_at_ts' => $now,
    'generated_at' => gmdate('c', $now),
    'hotspots_count' => count($hotspots),
    'countries_count' => count($countryCounts),
    'top_country' => $topCountry ?: null,
    'top_country_reports' => $topCountryReports,
    'linked_monitor' => '/volcanoes.php',
    'hotspots' => $hotspots,
    'from_cache' => false,
];

if (!write_json_file($cachePath, $payload)) {
    write_log($appConfig['logs_dir'], 'Failed writing hotspots cache JSON');
}

json_response(200, $payload);
