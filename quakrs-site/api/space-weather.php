<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

function space_weather_parse_time(mixed $value): ?int
{
    if (!is_string($value) || trim($value) === '') {
        return null;
    }

    $timestamp = strtotime($value);
    return is_int($timestamp) ? $timestamp : null;
}

function space_weather_parse_numeric(mixed $value): ?float
{
    if (is_int($value) || is_float($value)) {
        return (float) $value;
    }

    if (!is_string($value)) {
        return null;
    }

    $trimmed = trim($value);
    if ($trimmed === '' || !is_numeric($trimmed)) {
        return null;
    }

    return (float) $trimmed;
}

function space_weather_parse_kp_rows(mixed $payload): array
{
    if (!is_array($payload)) {
        return [];
    }

    $rows = [];
    foreach ($payload as $index => $row) {
        if ($index === 0 || !is_array($row)) {
            continue;
        }

        $timeTag = isset($row[0]) ? (string) $row[0] : '';
        $kpValue = isset($row[1]) ? space_weather_parse_numeric($row[1]) : null;
        if ($timeTag === '' || !is_float($kpValue)) {
            continue;
        }

        $ts = space_weather_parse_time($timeTag);
        if (!is_int($ts)) {
            continue;
        }

        $rows[] = [
            'time_ts' => $ts,
            'time_utc' => gmdate('c', $ts),
            'kp_index' => $kpValue,
        ];
    }

    usort($rows, static fn(array $a, array $b): int => $a['time_ts'] <=> $b['time_ts']);
    return $rows;
}

function space_weather_parse_series_rows(mixed $payload, array $timeKeys, array $valueKeys): array
{
    if (!is_array($payload) || $payload === []) {
        return [];
    }

    $rows = [];
    $first = $payload[0] ?? null;

    if (is_array($first) && isset($first[0]) && is_string($first[0])) {
        $header = [];
        foreach ($first as $idx => $name) {
            if (is_string($name)) {
                $header[strtolower($name)] = (int) $idx;
            }
        }

        $timeIndex = null;
        foreach ($timeKeys as $key) {
            $lookup = strtolower($key);
            if (array_key_exists($lookup, $header)) {
                $timeIndex = $header[$lookup];
                break;
            }
        }

        $valueIndex = null;
        foreach ($valueKeys as $key) {
            $lookup = strtolower($key);
            if (array_key_exists($lookup, $header)) {
                $valueIndex = $header[$lookup];
                break;
            }
        }

        if ($timeIndex === null || $valueIndex === null) {
            return [];
        }

        foreach ($payload as $index => $row) {
            if ($index === 0 || !is_array($row)) {
                continue;
            }

            $timeValue = $row[$timeIndex] ?? null;
            $numericValue = space_weather_parse_numeric($row[$valueIndex] ?? null);
            $ts = space_weather_parse_time($timeValue);
            if (!is_int($ts) || !is_float($numericValue)) {
                continue;
            }

            $rows[] = [
                'time_ts' => $ts,
                'time_utc' => gmdate('c', $ts),
                'value' => $numericValue,
            ];
        }
    } else {
        foreach ($payload as $row) {
            if (!is_array($row)) {
                continue;
            }

            $timeValue = null;
            foreach ($timeKeys as $timeKey) {
                if (array_key_exists($timeKey, $row)) {
                    $timeValue = $row[$timeKey];
                    break;
                }
            }

            $numericValue = null;
            foreach ($valueKeys as $valueKey) {
                if (array_key_exists($valueKey, $row)) {
                    $numericValue = space_weather_parse_numeric($row[$valueKey]);
                    break;
                }
            }

            $ts = space_weather_parse_time($timeValue);
            if (!is_int($ts) || !is_float($numericValue)) {
                continue;
            }

            $rows[] = [
                'time_ts' => $ts,
                'time_utc' => gmdate('c', $ts),
                'value' => $numericValue,
            ];
        }
    }

    usort($rows, static fn(array $a, array $b): int => $a['time_ts'] <=> $b['time_ts']);
    return $rows;
}

function space_weather_storm_level(float $kp): string
{
    return match (true) {
        $kp >= 9.0 => 'G5 Extreme',
        $kp >= 8.0 => 'G4 Severe',
        $kp >= 7.0 => 'G3 Strong',
        $kp >= 6.0 => 'G2 Moderate',
        $kp >= 5.0 => 'G1 Minor',
        default => 'Quiet to unsettled',
    };
}

function space_weather_kp_band(float $kp): string
{
    return match (true) {
        $kp >= 7.0 => 'Severe',
        $kp >= 5.0 => 'Storming',
        $kp >= 3.0 => 'Active',
        $kp >= 2.0 => 'Unsettled',
        default => 'Quiet',
    };
}

function space_weather_xray_class(float $flux): string
{
    if ($flux >= 1.0e-4) {
        $scale = $flux / 1.0e-4;
        return 'X' . number_format($scale, 1);
    }

    if ($flux >= 1.0e-5) {
        $scale = $flux / 1.0e-5;
        return 'M' . number_format($scale, 1);
    }

    if ($flux >= 1.0e-6) {
        $scale = $flux / 1.0e-6;
        return 'C' . number_format($scale, 1);
    }

    if ($flux >= 1.0e-7) {
        $scale = $flux / 1.0e-7;
        return 'B' . number_format($scale, 1);
    }

    if ($flux <= 0.0) {
        return 'A0.0';
    }

    $scale = $flux / 1.0e-8;
    return 'A' . number_format($scale, 1);
}

function space_weather_extract_flare_events(array $xrayRows): array
{
    if (count($xrayRows) < 3) {
        return [];
    }

    $events = [];
    $total = count($xrayRows);

    for ($idx = 1; $idx < ($total - 1); $idx++) {
        $prev = (float) $xrayRows[$idx - 1]['value'];
        $curr = (float) $xrayRows[$idx]['value'];
        $next = (float) $xrayRows[$idx + 1]['value'];

        if (!($curr >= $prev && $curr >= $next)) {
            continue;
        }

        $class = space_weather_xray_class($curr);
        if (!preg_match('/^[CMX]/', $class)) {
            continue;
        }

        $events[] = [
            'time_utc' => (string) $xrayRows[$idx]['time_utc'],
            'flux' => $curr,
            'class' => $class,
        ];
    }

    usort($events, static fn(array $a, array $b): int => strcmp((string) $b['time_utc'], (string) $a['time_utc']));
    return array_slice($events, 0, 8);
}

function space_weather_series_window(array $rows, int $startTs, int $endTs): array
{
    $window = array_values(array_filter($rows, static function (array $row) use ($startTs, $endTs): bool {
        $ts = (int) ($row['time_ts'] ?? 0);
        return $ts >= $startTs && $ts <= $endTs;
    }));

    return $window === [] ? $rows : $window;
}

function space_weather_enrich_payload(array $payload): array
{
    $currentKp = isset($payload['kp_index_current']) && is_numeric($payload['kp_index_current']) ? (float) $payload['kp_index_current'] : null;
    $payload['kp_band_current'] = isset($payload['kp_band_current']) ? $payload['kp_band_current'] : ($currentKp !== null ? space_weather_kp_band($currentKp) : 'Unknown');
    $payload['readings_24h'] = isset($payload['readings_24h']) && is_array($payload['readings_24h']) ? $payload['readings_24h'] : (isset($payload['readings']) && is_array($payload['readings']) ? $payload['readings'] : []);
    $payload['forecast_readings_24h'] = isset($payload['forecast_readings_24h']) && is_array($payload['forecast_readings_24h']) ? $payload['forecast_readings_24h'] : [];
    $payload['kp_band_distribution'] = isset($payload['kp_band_distribution']) && is_array($payload['kp_band_distribution']) ? $payload['kp_band_distribution'] : [];
    $payload['kp_trend_24h'] = isset($payload['kp_trend_24h']) && is_array($payload['kp_trend_24h']) ? $payload['kp_trend_24h'] : [];

    $readingValues = array_values(array_filter(array_map(static function (mixed $row): ?float {
        if (!is_array($row) || !isset($row['kp_index']) || !is_numeric($row['kp_index'])) {
            return null;
        }
        return (float) $row['kp_index'];
    }, $payload['readings_24h']), static fn(?float $value): bool => $value !== null));

    if ($payload['kp_band_distribution'] === []) {
        $bins = [
            ['label' => 'Kp < 2', 'count' => 0],
            ['label' => 'Kp 2-3', 'count' => 0],
            ['label' => 'Kp 4-5', 'count' => 0],
            ['label' => 'Kp 6+', 'count' => 0],
        ];
        foreach ($readingValues as $kp) {
            if ($kp < 2.0) {
                $bins[0]['count']++;
                continue;
            }
            if ($kp < 4.0) {
                $bins[1]['count']++;
                continue;
            }
            if ($kp < 6.0) {
                $bins[2]['count']++;
                continue;
            }
            $bins[3]['count']++;
        }
        $payload['kp_band_distribution'] = $bins;
    }

    if ($payload['kp_trend_24h'] === []) {
        $first = $readingValues[0] ?? null;
        $last = $readingValues[count($readingValues) - 1] ?? null;
        $delta = ($first !== null && $last !== null) ? ($last - $first) : 0.0;
        $direction = match (true) {
            $delta > 0.34 => 'rising',
            $delta < -0.34 => 'falling',
            default => 'stable',
        };
        $average = $readingValues === [] ? 0.0 : array_sum($readingValues) / count($readingValues);
        $payload['kp_trend_24h'] = ['delta' => $delta, 'direction' => $direction, 'average' => $average];
    }

    $payload['sun_image_url'] = isset($payload['sun_image_url']) && is_string($payload['sun_image_url']) ? $payload['sun_image_url'] : null;
    $payload['xray_flux_current'] = isset($payload['xray_flux_current']) && is_numeric($payload['xray_flux_current']) ? (float) $payload['xray_flux_current'] : null;
    $payload['xray_flux_peak_24h'] = isset($payload['xray_flux_peak_24h']) && is_numeric($payload['xray_flux_peak_24h']) ? (float) $payload['xray_flux_peak_24h'] : null;
    $payload['xray_class_peak_24h'] = isset($payload['xray_class_peak_24h']) ? (string) $payload['xray_class_peak_24h'] : null;
    $payload['xray_series_24h'] = isset($payload['xray_series_24h']) && is_array($payload['xray_series_24h']) ? $payload['xray_series_24h'] : [];
    $payload['flare_events'] = isset($payload['flare_events']) && is_array($payload['flare_events']) ? $payload['flare_events'] : [];

    $payload['solar_wind_speed_current'] = isset($payload['solar_wind_speed_current']) && is_numeric($payload['solar_wind_speed_current']) ? (float) $payload['solar_wind_speed_current'] : null;
    $payload['solar_wind_speed_max_24h'] = isset($payload['solar_wind_speed_max_24h']) && is_numeric($payload['solar_wind_speed_max_24h']) ? (float) $payload['solar_wind_speed_max_24h'] : null;
    $payload['solar_wind_speed_series_24h'] = isset($payload['solar_wind_speed_series_24h']) && is_array($payload['solar_wind_speed_series_24h']) ? $payload['solar_wind_speed_series_24h'] : [];

    $payload['imf_bz_current'] = isset($payload['imf_bz_current']) && is_numeric($payload['imf_bz_current']) ? (float) $payload['imf_bz_current'] : null;
    $payload['imf_bz_min_24h'] = isset($payload['imf_bz_min_24h']) && is_numeric($payload['imf_bz_min_24h']) ? (float) $payload['imf_bz_min_24h'] : null;
    $payload['imf_bz_max_24h'] = isset($payload['imf_bz_max_24h']) && is_numeric($payload['imf_bz_max_24h']) ? (float) $payload['imf_bz_max_24h'] : null;
    $payload['imf_bz_series_24h'] = isset($payload['imf_bz_series_24h']) && is_array($payload['imf_bz_series_24h']) ? $payload['imf_bz_series_24h'] : [];

    return $payload;
}

function space_weather_apply_static_defaults(array $payload, string $sunImageUrl, int $now): array
{
    if ($sunImageUrl !== '') {
        $payload['sun_image_url'] = $sunImageUrl . '?t=' . $now;
        $payload['sun_image_candidates'] = [
            $sunImageUrl . '?t=' . $now,
            $sunImageUrl . '?force_refresh=1&t=' . $now,
            'https://services.swpc.noaa.gov/images/animations/suvi/primary/171/latest.png?t=' . $now,
            'https://services.swpc.noaa.gov/images/animations/suvi/primary/304/latest.png?t=' . $now,
            'https://sdo.gsfc.nasa.gov/assets/img/latest/latest_512_0171.jpg?t=' . $now,
            'https://soho.nascom.nasa.gov/data/realtime/eit_304/512/latest.jpg?t=' . $now,
        ];
    }

    return $payload;
}

$cachePath = $appConfig['data_dir'] . '/space_weather_latest.json';
$now = time();
$cacheTtl = (int) $appConfig['cache_ttl_seconds'];
$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';

$cachedPayload = read_json_file($cachePath);
$cacheAge = is_array($cachedPayload) && isset($cachedPayload['generated_at_ts'])
    ? $now - (int) $cachedPayload['generated_at_ts']
    : null;
$cacheIsStale = !is_int($cacheAge) || $cacheAge > $cacheTtl;

$sunImageUrl = '/api/space-weather-sun.php';

if (!$forceRefresh && is_array($cachedPayload) && !$cacheIsStale) {
    $cachedPayload = space_weather_enrich_payload($cachedPayload);
    $cachedPayload = space_weather_apply_static_defaults($cachedPayload, $sunImageUrl, $now);
    $cachedPayload['from_cache'] = true;
    $cachedPayload['stale_cache'] = false;
    json_response(200, $cachedPayload);
}

$provider = (string) ($feedConfig['space_weather']['provider'] ?? 'NOAA SWPC');
$kpUrl = (string) ($feedConfig['space_weather']['kp_url'] ?? '');
$kpForecastUrl = (string) ($feedConfig['space_weather']['kp_forecast_url'] ?? '');
$xrayUrl = (string) ($feedConfig['space_weather']['xray_url'] ?? '');
$plasmaUrl = (string) ($feedConfig['space_weather']['solar_wind_plasma_url'] ?? '');
$magUrl = (string) ($feedConfig['space_weather']['solar_wind_mag_url'] ?? '');

$timeout = (int) $appConfig['http_timeout_seconds'];

$kpRaw = fetch_external_json($kpUrl, $timeout);
$forecastRaw = fetch_external_json($kpForecastUrl, $timeout);
$xrayRaw = fetch_external_json($xrayUrl, $timeout);
$plasmaRaw = fetch_external_json($plasmaUrl, $timeout);
$magRaw = fetch_external_json($magUrl, $timeout);

$kpRows = space_weather_parse_kp_rows($kpRaw);
$forecastRows = space_weather_parse_kp_rows($forecastRaw);

if ($kpRows === []) {
    write_log($appConfig['logs_dir'], "Space weather Kp feed fetch failed: {$kpUrl}");

    if (is_array($cachedPayload)) {
        $cachedPayload = space_weather_enrich_payload($cachedPayload);
        $cachedPayload = space_weather_apply_static_defaults($cachedPayload, $sunImageUrl, $now);
        $cachedPayload['from_cache'] = true;
        $cachedPayload['stale_cache'] = true;
        json_response(200, $cachedPayload);
    }

    json_response(502, [
        'ok' => false,
        'error' => 'Unable to load space weather feed',
    ]);
}

$windowStart = $now - 86400;
$windowEnd = $now + 86400;

$latest = end($kpRows);
$latestKp = is_array($latest) && isset($latest['kp_index']) ? (float) $latest['kp_index'] : 0.0;
$latestTs = is_array($latest) ? (int) ($latest['time_ts'] ?? 0) : 0;

$kpWindow = space_weather_series_window($kpRows, $windowStart, $now);
$kpValues24h = array_map(static fn(array $row): float => (float) $row['kp_index'], $kpWindow);
$kpMax24h = max($kpValues24h);
$kpMin24h = min($kpValues24h);

$forecastWindow = array_values(array_filter($forecastRows, static function (array $row) use ($now, $windowEnd): bool {
    $rowTs = (int) $row['time_ts'];
    return $rowTs >= $now && $rowTs <= $windowEnd;
}));
$forecastKpMax24h = $forecastWindow === []
    ? null
    : max(array_map(static fn(array $row): float => (float) $row['kp_index'], $forecastWindow));

$first24h = $kpWindow[0] ?? null;
$last24h = end($kpWindow);
$trendDelta24h = (is_array($first24h) && is_array($last24h))
    ? ((float) $last24h['kp_index']) - ((float) $first24h['kp_index'])
    : 0.0;
$trendDirection = match (true) {
    $trendDelta24h > 0.34 => 'rising',
    $trendDelta24h < -0.34 => 'falling',
    default => 'stable',
};
$trendAverage24h = array_sum($kpValues24h) / max(1, count($kpValues24h));

$readings24h = array_values(array_map(static function (array $row): array {
    return [
        'time_utc' => (string) $row['time_utc'],
        'kp_index' => (float) $row['kp_index'],
    ];
}, $kpWindow));

$forecastReadings24h = array_values(array_map(static function (array $row): array {
    return [
        'time_utc' => (string) $row['time_utc'],
        'kp_index' => (float) $row['kp_index'],
    ];
}, $forecastWindow));

$kpBandDistribution = [
    ['label' => 'Kp < 2', 'count' => 0],
    ['label' => 'Kp 2-3', 'count' => 0],
    ['label' => 'Kp 4-5', 'count' => 0],
    ['label' => 'Kp 6+', 'count' => 0],
];

foreach ($kpWindow as $row) {
    $kp = (float) $row['kp_index'];
    if ($kp < 2.0) {
        $kpBandDistribution[0]['count']++;
        continue;
    }
    if ($kp < 4.0) {
        $kpBandDistribution[1]['count']++;
        continue;
    }
    if ($kp < 6.0) {
        $kpBandDistribution[2]['count']++;
        continue;
    }
    $kpBandDistribution[3]['count']++;
}

$recentReadings = array_slice(
    array_map(static function (array $row): array {
        return [
            'time_utc' => (string) $row['time_utc'],
            'kp_index' => (float) $row['kp_index'],
        ];
    }, $kpRows),
    -12
);

$xrayRows = space_weather_parse_series_rows($xrayRaw, ['time_tag', 'time', 'observed_time'], ['observed_flux', 'flux', 'xray']);
$xrayWindow = space_weather_series_window($xrayRows, $windowStart, $now);
$xrayValues = array_map(static fn(array $row): float => (float) $row['value'], $xrayWindow);
$xrayCurrent = $xrayWindow !== [] ? (float) $xrayWindow[count($xrayWindow) - 1]['value'] : null;
$xrayPeak = $xrayValues !== [] ? max($xrayValues) : null;
$xrayClassPeak = is_float($xrayPeak) ? space_weather_xray_class($xrayPeak) : null;
$xraySeries = array_map(static function (array $row): array {
    return [
        'time_utc' => (string) $row['time_utc'],
        'flux' => (float) $row['value'],
    ];
}, array_slice($xrayWindow, -180));
$flareEvents = space_weather_extract_flare_events($xrayWindow);

$plasmaRows = space_weather_parse_series_rows($plasmaRaw, ['time_tag', 'time'], ['speed', 'speed_km_s']);
$plasmaWindow = space_weather_series_window($plasmaRows, $windowStart, $now);
$plasmaValues = array_map(static fn(array $row): float => (float) $row['value'], $plasmaWindow);
$solarWindCurrent = $plasmaWindow !== [] ? (float) $plasmaWindow[count($plasmaWindow) - 1]['value'] : null;
$solarWindMax = $plasmaValues !== [] ? max($plasmaValues) : null;
$solarWindSeries = array_map(static function (array $row): array {
    return [
        'time_utc' => (string) $row['time_utc'],
        'speed' => (float) $row['value'],
    ];
}, array_slice($plasmaWindow, -180));

$magRows = space_weather_parse_series_rows($magRaw, ['time_tag', 'time'], ['bz_gsm', 'bz']);
$magWindow = space_weather_series_window($magRows, $windowStart, $now);
$magValues = array_map(static fn(array $row): float => (float) $row['value'], $magWindow);
$imfBzCurrent = $magWindow !== [] ? (float) $magWindow[count($magWindow) - 1]['value'] : null;
$imfBzMin = $magValues !== [] ? min($magValues) : null;
$imfBzMax = $magValues !== [] ? max($magValues) : null;
$imfBzSeries = array_map(static function (array $row): array {
    return [
        'time_utc' => (string) $row['time_utc'],
        'bz' => (float) $row['value'],
    ];
}, array_slice($magWindow, -180));

$payload = [
    'ok' => true,
    'provider' => $provider,
    'generated_at_ts' => $now,
    'generated_at' => gmdate('c', $now),
    'kp_index_current' => $latestKp,
    'kp_index_max_24h' => $kpMax24h,
    'kp_index_min_24h' => $kpMin24h,
    'storm_level' => space_weather_storm_level($latestKp),
    'kp_band_current' => space_weather_kp_band($latestKp),
    'last_observation_utc' => $latestTs > 0 ? gmdate('c', $latestTs) : null,
    'forecast_kp_max_24h' => $forecastKpMax24h,
    'kp_trend_24h' => [
        'delta' => $trendDelta24h,
        'direction' => $trendDirection,
        'average' => $trendAverage24h,
    ],
    'readings_24h' => $readings24h,
    'forecast_readings_24h' => $forecastReadings24h,
    'kp_band_distribution' => $kpBandDistribution,
    'readings' => $recentReadings,

    'sun_image_url' => $sunImageUrl . '?t=' . $now,
    'sun_image_candidates' => [
        $sunImageUrl . '?t=' . $now,
        $sunImageUrl . '?force_refresh=1&t=' . $now,
        'https://services.swpc.noaa.gov/images/animations/suvi/primary/171/latest.png?t=' . $now,
        'https://services.swpc.noaa.gov/images/animations/suvi/primary/304/latest.png?t=' . $now,
        'https://sdo.gsfc.nasa.gov/assets/img/latest/latest_512_0171.jpg?t=' . $now,
        'https://soho.nascom.nasa.gov/data/realtime/eit_304/512/latest.jpg?t=' . $now,
    ],
    'xray_flux_current' => $xrayCurrent,
    'xray_flux_peak_24h' => $xrayPeak,
    'xray_class_peak_24h' => $xrayClassPeak,
    'xray_series_24h' => $xraySeries,
    'flare_events' => $flareEvents,

    'solar_wind_speed_current' => $solarWindCurrent,
    'solar_wind_speed_max_24h' => $solarWindMax,
    'solar_wind_speed_series_24h' => $solarWindSeries,

    'imf_bz_current' => $imfBzCurrent,
    'imf_bz_min_24h' => $imfBzMin,
    'imf_bz_max_24h' => $imfBzMax,
    'imf_bz_series_24h' => $imfBzSeries,

    'from_cache' => false,
];

if (!write_json_file($cachePath, $payload)) {
    write_log($appConfig['logs_dir'], 'Failed writing space weather cache JSON');
}

json_response(200, $payload);
