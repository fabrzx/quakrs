<?php
declare(strict_types=1);

$config = [
    'timezone' => 'Europe/Rome',
    'cache_ttl_seconds' => 90,
    'italy_cache_ttl_seconds' => 300,
    'http_timeout_seconds' => 12,
    'max_events' => 1000,
    'refresh_token' => getenv('QUAKRS_REFRESH_TOKEN') ?: '',
    'public_base_url' => getenv('QUAKRS_PUBLIC_BASE_URL') ?: 'https://www.quakrs.com',
    'data_dir' => __DIR__ . '/../data',
    'logs_dir' => __DIR__ . '/../logs',
    'archive_mysql' => [
        'host' => getenv('QUAKRS_DB_HOST') ?: '',
        'port' => (int) (getenv('QUAKRS_DB_PORT') ?: 3306),
        'database' => getenv('QUAKRS_DB_NAME') ?: '',
        'user' => getenv('QUAKRS_DB_USER') ?: '',
        'password' => getenv('QUAKRS_DB_PASS') ?: '',
        'charset' => getenv('QUAKRS_DB_CHARSET') ?: 'utf8mb4',
        'table' => getenv('QUAKRS_DB_TABLE') ?: 'earthquake_events',
    ],
    'mysql_databases' => [
        'live' => [
            'host' => getenv('QUAKRS_DB_LIVE_HOST') ?: (getenv('QUAKRS_DB_HOST') ?: ''),
            'port' => (int) (getenv('QUAKRS_DB_LIVE_PORT') ?: (getenv('QUAKRS_DB_PORT') ?: 3306)),
            'database' => getenv('QUAKRS_DB_LIVE_NAME') ?: (getenv('QUAKRS_DB_NAME') ?: ''),
            'user' => getenv('QUAKRS_DB_LIVE_USER') ?: (getenv('QUAKRS_DB_USER') ?: ''),
            'password' => getenv('QUAKRS_DB_LIVE_PASS') ?: (getenv('QUAKRS_DB_PASS') ?: ''),
            'charset' => getenv('QUAKRS_DB_LIVE_CHARSET') ?: (getenv('QUAKRS_DB_CHARSET') ?: 'utf8mb4'),
            'table' => getenv('QUAKRS_DB_LIVE_TABLE') ?: (getenv('QUAKRS_DB_TABLE') ?: 'earthquake_events'),
        ],
        'archive' => [
            'host' => getenv('QUAKRS_DB_ARCHIVE_HOST') ?: (getenv('QUAKRS_DB_HOST') ?: ''),
            'port' => (int) (getenv('QUAKRS_DB_ARCHIVE_PORT') ?: (getenv('QUAKRS_DB_PORT') ?: 3306)),
            'database' => getenv('QUAKRS_DB_ARCHIVE_NAME') ?: (getenv('QUAKRS_DB_NAME') ?: ''),
            'user' => getenv('QUAKRS_DB_ARCHIVE_USER') ?: (getenv('QUAKRS_DB_USER') ?: ''),
            'password' => getenv('QUAKRS_DB_ARCHIVE_PASS') ?: (getenv('QUAKRS_DB_PASS') ?: ''),
            'charset' => getenv('QUAKRS_DB_ARCHIVE_CHARSET') ?: (getenv('QUAKRS_DB_CHARSET') ?: 'utf8mb4'),
            'table' => getenv('QUAKRS_DB_ARCHIVE_TABLE') ?: (getenv('QUAKRS_DB_TABLE') ?: 'earthquake_events'),
        ],
        'ingest' => [
            'host' => getenv('QUAKRS_DB_INGEST_HOST') ?: (getenv('QUAKRS_DB_HOST') ?: ''),
            'port' => (int) (getenv('QUAKRS_DB_INGEST_PORT') ?: (getenv('QUAKRS_DB_PORT') ?: 3306)),
            'database' => getenv('QUAKRS_DB_INGEST_NAME') ?: '',
            'user' => getenv('QUAKRS_DB_INGEST_USER') ?: (getenv('QUAKRS_DB_USER') ?: ''),
            'password' => getenv('QUAKRS_DB_INGEST_PASS') ?: (getenv('QUAKRS_DB_PASS') ?: ''),
            'charset' => getenv('QUAKRS_DB_INGEST_CHARSET') ?: (getenv('QUAKRS_DB_CHARSET') ?: 'utf8mb4'),
            'table' => getenv('QUAKRS_DB_INGEST_TABLE') ?: 'earthquake_events_raw',
        ],
        'stats' => [
            'host' => getenv('QUAKRS_DB_STATS_HOST') ?: (getenv('QUAKRS_DB_HOST') ?: ''),
            'port' => (int) (getenv('QUAKRS_DB_STATS_PORT') ?: (getenv('QUAKRS_DB_PORT') ?: 3306)),
            'database' => getenv('QUAKRS_DB_STATS_NAME') ?: '',
            'user' => getenv('QUAKRS_DB_STATS_USER') ?: (getenv('QUAKRS_DB_USER') ?: ''),
            'password' => getenv('QUAKRS_DB_STATS_PASS') ?: (getenv('QUAKRS_DB_PASS') ?: ''),
            'charset' => getenv('QUAKRS_DB_STATS_CHARSET') ?: (getenv('QUAKRS_DB_CHARSET') ?: 'utf8mb4'),
            'table' => getenv('QUAKRS_DB_STATS_TABLE') ?: 'earthquake_daily_stats',
        ],
        'spare' => [
            'host' => getenv('QUAKRS_DB_SPARE_HOST') ?: (getenv('QUAKRS_DB_HOST') ?: ''),
            'port' => (int) (getenv('QUAKRS_DB_SPARE_PORT') ?: (getenv('QUAKRS_DB_PORT') ?: 3306)),
            'database' => getenv('QUAKRS_DB_SPARE_NAME') ?: '',
            'user' => getenv('QUAKRS_DB_SPARE_USER') ?: (getenv('QUAKRS_DB_USER') ?: ''),
            'password' => getenv('QUAKRS_DB_SPARE_PASS') ?: (getenv('QUAKRS_DB_PASS') ?: ''),
            'charset' => getenv('QUAKRS_DB_SPARE_CHARSET') ?: (getenv('QUAKRS_DB_CHARSET') ?: 'utf8mb4'),
            'table' => getenv('QUAKRS_DB_SPARE_TABLE') ?: 'spare_placeholder',
        ],
    ],
];

$localPath = __DIR__ . '/app.local.php';
if (file_exists($localPath)) {
    $localConfig = require $localPath;
    if (is_array($localConfig)) {
        $config = array_replace_recursive($config, $localConfig);
    }
}

return $config;
