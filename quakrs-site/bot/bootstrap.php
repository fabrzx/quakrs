<?php
declare(strict_types=1);

$appConfig = require __DIR__ . '/../config/app.php';
$appToken = (string) (($appConfig['telegram']['bot_token'] ?? null) ?? ($appConfig['telegram_bot_token'] ?? ''));

date_default_timezone_set((string) ($appConfig['timezone'] ?? 'Europe/Rome'));

$botConfig = [
    'token' => (string) (getenv('QUAKRS_TELEGRAM_BOT_TOKEN') ?: $appToken),
    'api_base' => (string) (getenv('QUAKRS_TELEGRAM_API_BASE') ?: 'https://api.telegram.org'),
    'public_base_url' => (string) ($appConfig['public_base_url'] ?? 'https://www.quakrs.com'),
    'default_mode' => (string) (getenv('QUAKRS_TELEGRAM_DEFAULT_MODE') ?: 'essential'),
    'max_daily_essential' => (int) (getenv('QUAKRS_TELEGRAM_MAX_DAILY_ESSENTIAL') ?: 4),
    'max_daily_balanced' => (int) (getenv('QUAKRS_TELEGRAM_MAX_DAILY_BALANCED') ?: 8),
    'max_daily_monitor' => (int) (getenv('QUAKRS_TELEGRAM_MAX_DAILY_MONITOR') ?: 20),
    'chat_channel_id' => (string) (getenv('QUAKRS_TELEGRAM_CHANNEL_ID') ?: ''),
    'db' => [
        'host' => (string) (getenv('QUAKRS_DB_BOT_HOST') ?: (getenv('QUAKRS_DB_HOST') ?: '')),
        'port' => (int) (getenv('QUAKRS_DB_BOT_PORT') ?: (getenv('QUAKRS_DB_PORT') ?: 3306)),
        'database' => (string) (getenv('QUAKRS_DB_BOT_NAME') ?: (getenv('QUAKRS_DB_NAME') ?: '')),
        'user' => (string) (getenv('QUAKRS_DB_BOT_USER') ?: (getenv('QUAKRS_DB_USER') ?: '')),
        'password' => (string) (getenv('QUAKRS_DB_BOT_PASS') ?: (getenv('QUAKRS_DB_PASS') ?: '')),
        'charset' => (string) (getenv('QUAKRS_DB_BOT_CHARSET') ?: (getenv('QUAKRS_DB_CHARSET') ?: 'utf8mb4')),
    ],
    'paths' => [
        'data' => __DIR__ . '/../data',
        'logs' => __DIR__ . '/../logs',
    ],
];

$liveDbCfg = is_array($appConfig['mysql_databases']['live'] ?? null) ? $appConfig['mysql_databases']['live'] : [];
if (
    (string) ($botConfig['db']['host'] ?? '') === '' ||
    (string) ($botConfig['db']['database'] ?? '') === '' ||
    (string) ($botConfig['db']['user'] ?? '') === ''
) {
    $botConfig['db']['host'] = (string) ($liveDbCfg['host'] ?? $botConfig['db']['host'] ?? '');
    $botConfig['db']['port'] = (int) ($liveDbCfg['port'] ?? $botConfig['db']['port'] ?? 3306);
    $botConfig['db']['database'] = (string) ($liveDbCfg['database'] ?? $botConfig['db']['database'] ?? '');
    $botConfig['db']['user'] = (string) ($liveDbCfg['user'] ?? $botConfig['db']['user'] ?? '');
    $botConfig['db']['password'] = (string) ($liveDbCfg['password'] ?? $botConfig['db']['password'] ?? '');
    $botConfig['db']['charset'] = (string) ($liveDbCfg['charset'] ?? $botConfig['db']['charset'] ?? 'utf8mb4');
}

function bot_json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function bot_log(array $botConfig, string $message): void
{
    $dir = (string) ($botConfig['paths']['logs'] ?? (__DIR__ . '/../logs'));
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $line = sprintf("[%s] %s\n", date('c'), $message);
    @file_put_contents($dir . '/bot.log', $line, FILE_APPEND | LOCK_EX);
}

function bot_read_json(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }
    $raw = @file_get_contents($path);
    if (!is_string($raw)) {
        return null;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function bot_parse_iso_time(?string $iso): ?string
{
    if (!is_string($iso) || trim($iso) === '') {
        return null;
    }
    $ts = strtotime($iso);
    if (!is_int($ts) || $ts <= 0) {
        return null;
    }
    return date('Y-m-d H:i:s', $ts);
}

function bot_default_categories(): array
{
    return ['earthquakes', 'volcanoes', 'tsunami', 'space_weather'];
}
