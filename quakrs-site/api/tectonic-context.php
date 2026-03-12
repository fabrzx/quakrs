<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

function clamp_float(float $value, float $min, float $max): float
{
    return max($min, min($max, $value));
}

function fallback_plates_geojson(): array
{
    $lines = [
        [[-75, -55], [-75, 60]],
        [[-110, -55], [-110, 60]],
        [[160, -55], [160, 60]],
        [[-20, -55], [-20, 70]],
        [[30, -35], [40, 20]],
        [[40, 20], [90, 45]],
    ];

    $features = [];
    foreach ($lines as $idx => $line) {
        $features[] = [
            'type' => 'Feature',
            'properties' => ['name' => 'Fallback plate boundary ' . ($idx + 1)],
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [$line[0][0], $line[0][1]],
                    [$line[1][0], $line[1][1]],
                ],
            ],
        ];
    }

    return [
        'type' => 'FeatureCollection',
        'features' => $features,
    ];
}

function empty_faults_geojson(): array
{
    return [
        'type' => 'FeatureCollection',
        'features' => [],
    ];
}

function collect_coordinates(array $coordinates, array &$out): void
{
    if (count($coordinates) >= 2 && is_numeric($coordinates[0]) && is_numeric($coordinates[1])) {
        $out[] = [(float) $coordinates[1], (float) $coordinates[0]];
        return;
    }

    foreach ($coordinates as $row) {
        if (is_array($row)) {
            collect_coordinates($row, $out);
        }
    }
}

function haversine_km(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $toRad = static fn(float $v): float => ($v * M_PI) / 180.0;
    $dLat = $toRad($lat2 - $lat1);
    $dLon = $toRad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2 + cos($toRad($lat1)) * cos($toRad($lat2)) * sin($dLon / 2) ** 2;
    return 6371.0 * (2 * atan2(sqrt($a), sqrt(1 - $a)));
}

function nearest_feature_distance_km(array $feature, float $lat, float $lon): float
{
    $geometry = $feature['geometry'] ?? null;
    if (!is_array($geometry)) {
        return INF;
    }
    $coordinates = $geometry['coordinates'] ?? null;
    if (!is_array($coordinates)) {
        return INF;
    }

    $points = [];
    collect_coordinates($coordinates, $points);
    if (count($points) === 0) {
        return INF;
    }

    $step = count($points) > 900 ? 6 : (count($points) > 300 ? 4 : 2);
    $best = INF;
    for ($i = 0; $i < count($points); $i += $step) {
        $p = $points[$i];
        $km = haversine_km($lat, $lon, $p[0], $p[1]);
        if ($km < $best) {
            $best = $km;
        }
    }
    return $best;
}

function normalize_geojson(mixed $payload): ?array
{
    if (!is_array($payload)) {
        return null;
    }
    if (($payload['type'] ?? null) !== 'FeatureCollection') {
        return null;
    }
    if (!isset($payload['features']) || !is_array($payload['features'])) {
        return null;
    }
    return [
        'type' => 'FeatureCollection',
        'features' => $payload['features'],
    ];
}

function load_or_refresh_dataset(
    string $cachePath,
    string $sourceUrl,
    bool $forceRefresh,
    int $ttlSeconds,
    int $timeoutSeconds
): array {
    $cached = normalize_geojson(read_json_file($cachePath));
    $cacheMtime = file_exists($cachePath) ? (int) filemtime($cachePath) : 0;
    $cacheAge = $cacheMtime > 0 ? (time() - $cacheMtime) : null;
    $cacheFresh = is_array($cached) && is_int($cacheAge) && $cacheAge <= $ttlSeconds;

    if (!$forceRefresh && $cacheFresh) {
        return [
            'data' => $cached,
            'from_cache' => true,
            'stale_cache' => false,
            'generated_at_ts' => $cacheMtime,
        ];
    }

    $fetched = normalize_geojson(fetch_external_json($sourceUrl, $timeoutSeconds));
    if (is_array($fetched)) {
        write_json_file($cachePath, $fetched);
        $now = time();
        return [
            'data' => $fetched,
            'from_cache' => false,
            'stale_cache' => false,
            'generated_at_ts' => $now,
        ];
    }

    if (is_array($cached)) {
        return [
            'data' => $cached,
            'from_cache' => true,
            'stale_cache' => true,
            'generated_at_ts' => $cacheMtime > 0 ? $cacheMtime : time(),
        ];
    }

    return [
        'data' => null,
        'from_cache' => false,
        'stale_cache' => true,
        'generated_at_ts' => time(),
    ];
}

function filter_nearest_features(array $geojson, float $lat, float $lon, float $radiusKm, int $maxFeatures): array
{
    $rows = [];
    foreach ($geojson['features'] as $feature) {
        if (!is_array($feature)) {
            continue;
        }
        $km = nearest_feature_distance_km($feature, $lat, $lon);
        if (!is_finite($km)) {
            continue;
        }
        $rows[] = ['feature' => $feature, 'km' => $km];
    }

    usort($rows, static fn(array $a, array $b): int => $a['km'] <=> $b['km']);

    $selected = [];
    foreach ($rows as $row) {
        if ($row['km'] <= $radiusKm) {
            $selected[] = $row['feature'];
            if (count($selected) >= $maxFeatures) {
                break;
            }
        }
    }

    if (count($selected) === 0) {
        $selected = array_map(static fn(array $row): array => $row['feature'], array_slice($rows, 0, $maxFeatures));
    }

    return [
        'type' => 'FeatureCollection',
        'features' => $selected,
    ];
}

$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';
$scope = isset($_GET['scope']) ? strtolower(trim((string) $_GET['scope'])) : 'global';
$scope = in_array($scope, ['global', 'local'], true) ? $scope : 'global';

$latRaw = isset($_GET['lat']) ? (float) $_GET['lat'] : NAN;
$lonRaw = isset($_GET['lon']) ? (float) $_GET['lon'] : NAN;
$hasFocus = is_finite($latRaw) && is_finite($lonRaw);

if ($hasFocus && $scope !== 'local') {
    $scope = 'local';
}

$lat = $hasFocus ? clamp_float($latRaw, -89.99, 89.99) : 0.0;
$lon = $hasFocus ? clamp_float($lonRaw, -179.99, 179.99) : 0.0;
$radiusKm = isset($_GET['radius_km']) ? clamp_float((float) $_GET['radius_km'], 100.0, 2000.0) : 900.0;
$maxPlates = max(10, min(4000, (int) ($_GET['max_plates'] ?? ($scope === 'local' ? 24 : 1600))));
$maxFaults = max(10, min(8000, (int) ($_GET['max_faults'] ?? ($scope === 'local' ? 40 : 2600))));

$platesUrl = 'https://raw.githubusercontent.com/fraxen/tectonicplates/master/GeoJSON/PB2002_boundaries.json';
$faultsUrl = 'https://raw.githubusercontent.com/GEMScienceTools/gem-global-active-faults/master/geojson/gem_active_faults_harmonized.geojson';
$platesCachePath = $appConfig['data_dir'] . '/tectonic_plates_latest.json';
$faultsCachePath = $appConfig['data_dir'] . '/tectonic_faults_latest.json';
$ttlSeconds = 14 * 24 * 60 * 60;
$timeoutSeconds = max(8, (int) $appConfig['http_timeout_seconds']);

$platesState = load_or_refresh_dataset($platesCachePath, $platesUrl, $forceRefresh, $ttlSeconds, $timeoutSeconds);
$faultsState = load_or_refresh_dataset($faultsCachePath, $faultsUrl, $forceRefresh, $ttlSeconds, $timeoutSeconds);

$platesData = is_array($platesState['data']) ? $platesState['data'] : fallback_plates_geojson();
$faultsData = is_array($faultsState['data']) ? $faultsState['data'] : empty_faults_geojson();
$fallbackMode = !is_array($platesState['data']) || !is_array($faultsState['data']);

if ($scope === 'local' && $hasFocus) {
    $platesData = filter_nearest_features($platesData, $lat, $lon, $radiusKm, $maxPlates);
    $faultsData = filter_nearest_features($faultsData, $lat, $lon, $radiusKm, $maxFaults);
} else {
    $platesData = [
        'type' => 'FeatureCollection',
        'features' => array_slice($platesData['features'], 0, $maxPlates),
    ];
    $faultsData = [
        'type' => 'FeatureCollection',
        'features' => array_slice($faultsData['features'], 0, $maxFaults),
    ];
}

$generatedAtTs = max((int) ($platesState['generated_at_ts'] ?? 0), (int) ($faultsState['generated_at_ts'] ?? 0), time());
json_response(200, [
    'ok' => true,
    'provider' => 'Fraxen PB2002 + GEM Global Active Faults',
    'scope' => $scope,
    'focus' => $hasFocus ? ['lat' => $lat, 'lon' => $lon, 'radius_km' => $radiusKm] : null,
    'generated_at_ts' => $generatedAtTs,
    'generated_at' => gmdate('c', $generatedAtTs),
    'from_cache' => (bool) ($platesState['from_cache'] ?? false) && (bool) ($faultsState['from_cache'] ?? false),
    'stale_cache' => (bool) ($platesState['stale_cache'] ?? false) || (bool) ($faultsState['stale_cache'] ?? false),
    'fallback_mode' => $fallbackMode,
    'plates_count' => count($platesData['features']),
    'faults_count' => count($faultsData['features']),
    'plates' => $platesData,
    'faults' => $faultsData,
]);

