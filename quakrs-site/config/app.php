<?php
declare(strict_types=1);

$config = [
    'timezone' => 'Europe/Rome',
    'cache_ttl_seconds' => 90,
    'http_timeout_seconds' => 12,
    'max_events' => 1000,
    'refresh_token' => getenv('QUAKRS_REFRESH_TOKEN') ?: '',
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
];

$localPath = __DIR__ . '/app.local.php';
if (file_exists($localPath)) {
    $localConfig = require $localPath;
    if (is_array($localConfig)) {
        $config = array_replace_recursive($config, $localConfig);
    }
}

return $config;
