<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

function bot_normalize_all_events(array $botConfig): array
{
    return array_merge(
        bot_normalize_earthquakes($botConfig),
        bot_normalize_volcanoes($botConfig),
        bot_normalize_tsunami($botConfig),
        bot_normalize_space_weather($botConfig)
    );
}

function bot_normalize_earthquakes(array $botConfig): array
{
    $payload = bot_read_json((string) ($botConfig['paths']['data'] ?? '') . '/earthquakes_latest.json');
    $events = is_array($payload['events'] ?? null) ? $payload['events'] : [];
    $out = [];

    foreach ($events as $row) {
        if (!is_array($row)) {
            continue;
        }
        $time = bot_parse_iso_time((string) ($row['event_time_utc'] ?? ''));
        if (!is_string($time)) {
            continue;
        }
        $provider = (string) ($row['source_provider'] ?? 'unknown');
        $id = (string) ($row['id'] ?? '');
        if ($id === '') {
            continue;
        }

        $canonicalKey = bot_earthquake_canonical_key($row, $time);
        if ($canonicalKey === '') {
            continue;
        }

        $out[] = [
            'category' => 'earthquakes',
            // Cross-source canonical key to avoid duplicate alerts for same quake.
            'event_key' => $canonicalKey,
            'provider' => $provider,
            'event_time' => $time,
            'title' => (string) ($row['place'] ?? 'Earthquake event'),
            'summary' => '',
            'country' => '',
            'region' => (string) ($row['place'] ?? ''),
            'latitude' => is_numeric($row['latitude'] ?? null) ? (float) $row['latitude'] : null,
            'longitude' => is_numeric($row['longitude'] ?? null) ? (float) $row['longitude'] : null,
            'magnitude' => is_numeric($row['magnitude'] ?? null) ? (float) $row['magnitude'] : null,
            'depth_km' => is_numeric($row['depth_km'] ?? null) ? abs((float) $row['depth_km']) : null,
            'severity_label' => null,
            'source_url' => (string) ($row['source_url'] ?? ''),
            'payload_json' => $row,
        ];
    }

    return $out;
}

function bot_earthquake_canonical_key(array $row, string $normalizedTime): string
{
    $ts = strtotime($normalizedTime);
    if (!is_int($ts) || $ts <= 0) {
        return '';
    }

    $lat = is_numeric($row['latitude'] ?? null) ? (float) $row['latitude'] : null;
    $lon = is_numeric($row['longitude'] ?? null) ? (float) $row['longitude'] : null;
    $mag = is_numeric($row['magnitude'] ?? null) ? (float) $row['magnitude'] : null;
    $depth = is_numeric($row['depth_km'] ?? null) ? abs((float) $row['depth_km']) : null;
    if ($lat === null || $lon === null || $mag === null) {
        return '';
    }

    $bucketSize = $mag >= 5.0 ? 60 : 20;
    $timeBucket = (int) floor($ts / $bucketSize);
    $latBucket = number_format($lat, 2, '.', '');
    $lonBucket = number_format($lon, 2, '.', '');
    $magBucket = number_format($mag, 1, '.', '');
    $depthBucket = $depth !== null ? (string) ((int) floor($depth / 10.0)) : 'na';

    return implode(':', ['eqc', $timeBucket, $latBucket, $lonBucket, $magBucket, $depthBucket]);
}

function bot_normalize_volcanoes(array $botConfig): array
{
    $payload = bot_read_json((string) ($botConfig['paths']['data'] ?? '') . '/volcanoes_latest.json');
    $events = is_array($payload['events'] ?? null) ? $payload['events'] : [];
    $out = [];

    foreach ($events as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = (string) ($row['id'] ?? '');
        $time = bot_parse_iso_time((string) ($row['event_time_utc'] ?? ''));
        if ($id === '' || !is_string($time)) {
            continue;
        }

        $out[] = [
            'category' => 'volcanoes',
            'event_key' => 'volc:' . sha1($id),
            'provider' => 'Smithsonian GVP',
            'event_time' => $time,
            'title' => (string) ($row['title'] ?? 'Volcano report'),
            'summary' => (string) ($row['summary'] ?? ''),
            'country' => (string) ($row['country'] ?? ''),
            'region' => (string) ($row['volcano'] ?? ''),
            'latitude' => null,
            'longitude' => null,
            'magnitude' => null,
            'depth_km' => null,
            'severity_label' => !empty($row['is_new_eruptive']) ? 'new_eruptive' : 'update',
            'source_url' => (string) ($row['source_url'] ?? ''),
            'payload_json' => $row,
            'is_new_eruptive' => !empty($row['is_new_eruptive']),
        ];
    }

    return $out;
}

function bot_normalize_tsunami(array $botConfig): array
{
    $payload = bot_read_json((string) ($botConfig['paths']['data'] ?? '') . '/tsunami_latest.json');
    $alerts = is_array($payload['alerts'] ?? null) ? $payload['alerts'] : [];
    $out = [];

    foreach ($alerts as $row) {
        if (!is_array($row)) {
            continue;
        }

        $id = (string) ($row['id'] ?? sha1(json_encode($row, JSON_UNESCAPED_SLASHES)));
        $eventTimeRaw = (string) ($row['sent'] ?? ($row['effective'] ?? ($payload['generated_at'] ?? '')));
        $time = bot_parse_iso_time($eventTimeRaw);
        if (!is_string($time)) {
            continue;
        }

        $title = (string) ($row['headline'] ?? ($row['event'] ?? 'Tsunami update'));
        $severity = (string) ($row['severity'] ?? ($row['urgency'] ?? 'info'));
        $desc = (string) ($row['description'] ?? '');

        $out[] = [
            'category' => 'tsunami',
            'event_key' => 'tsu:' . sha1($id),
            'provider' => 'NOAA/NWS',
            'event_time' => $time,
            'title' => $title,
            'summary' => $desc,
            'country' => '',
            'region' => (string) ($row['areaDesc'] ?? ''),
            'latitude' => null,
            'longitude' => null,
            'magnitude' => null,
            'depth_km' => null,
            'severity_label' => $severity,
            'source_url' => (string) ($row['uri'] ?? ''),
            'payload_json' => $row,
        ];
    }

    return $out;
}

function bot_normalize_space_weather(array $botConfig): array
{
    $payload = bot_read_json((string) ($botConfig['paths']['data'] ?? '') . '/space_weather_latest.json');
    if (!is_array($payload) || empty($payload['ok'])) {
        return [];
    }

    $time = bot_parse_iso_time((string) ($payload['generated_at'] ?? ''));
    if (!is_string($time)) {
        return [];
    }

    $kpCurrent = is_numeric($payload['kp_index_current'] ?? null) ? (float) $payload['kp_index_current'] : null;
    $kpMax = is_numeric($payload['kp_index_max_24h'] ?? null) ? (float) $payload['kp_index_max_24h'] : null;
    $xrayClass = (string) ($payload['xray_class_peak_24h'] ?? '');

    $summary = 'Kp now ' . ($kpCurrent !== null ? number_format($kpCurrent, 2) : 'n/a')
        . ', Kp max 24h ' . ($kpMax !== null ? number_format($kpMax, 2) : 'n/a')
        . ', X-ray peak ' . ($xrayClass !== '' ? $xrayClass : 'n/a');

    return [[
        'category' => 'space_weather',
        'event_key' => 'sw:' . date('YmdHi', strtotime($time)),
        'provider' => 'NOAA SWPC',
        'event_time' => $time,
        'title' => 'Space weather status update',
        'summary' => $summary,
        'country' => '',
        'region' => 'Global',
        'latitude' => null,
        'longitude' => null,
        'magnitude' => null,
        'depth_km' => null,
        'severity_label' => (string) ($payload['storm_level'] ?? ''),
        'source_url' => '',
        'payload_json' => $payload,
        'kp_index_current' => $kpCurrent,
        'kp_index_max_24h' => $kpMax,
        'xray_class_peak_24h' => $xrayClass,
    ]];
}
