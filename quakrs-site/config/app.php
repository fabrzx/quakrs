<?php
declare(strict_types=1);

$config = [
    'timezone' => 'Europe/Rome',
    'cache_ttl_seconds' => 90,
    'italy_cache_ttl_seconds' => 300,
    'http_timeout_seconds' => 12,
    'max_events' => 1000,
    'refresh_token' => getenv('QUAKRS_REFRESH_TOKEN') ?: '',
    'editorial' => [
        'use_gpt' => in_array(strtolower((string) (getenv('QUAKRS_EDITORIAL_USE_GPT') ?: '0')), ['1', 'true', 'yes', 'on'], true),
        'openai_api_key' => getenv('QUAKRS_OPENAI_API_KEY') ?: (getenv('OPENAI_API_KEY') ?: ''),
        'openai_model' => getenv('QUAKRS_OPENAI_MODEL') ?: 'gpt-5.4',
        'openai_fallback_model' => getenv('QUAKRS_OPENAI_FALLBACK_MODEL') ?: 'gpt-4.1-mini',
        'openai_base_url' => getenv('QUAKRS_OPENAI_BASE_URL') ?: 'https://api.openai.com/v1',
        'openai_timeout_seconds' => (int) (getenv('QUAKRS_OPENAI_TIMEOUT_SECONDS') ?: 45),
        'openai_max_output_tokens' => (int) (getenv('QUAKRS_OPENAI_MAX_OUTPUT_TOKENS') ?: 7000),
        'allow_recent_historical_today' => in_array(strtolower((string) (getenv('QUAKRS_EDITORIAL_ALLOW_RECENT_HISTORICAL_TODAY') ?: '0')), ['1', 'true', 'yes', 'on'], true),
        'historical_today_cutoff_year' => (int) (getenv('QUAKRS_EDITORIAL_HISTORICAL_TODAY_CUTOFF_YEAR') ?: 2010),
    ],
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
