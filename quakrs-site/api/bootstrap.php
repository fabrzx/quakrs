<?php
declare(strict_types=1);

$appConfig = require __DIR__ . '/../config/app.php';
$feedConfig = require __DIR__ . '/../config/feeds.php';

date_default_timezone_set($appConfig['timezone']);

if (!is_dir($appConfig['data_dir'])) {
    mkdir($appConfig['data_dir'], 0775, true);
}

if (!is_dir($appConfig['logs_dir'])) {
    mkdir($appConfig['logs_dir'], 0775, true);
}

function json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function read_json_file(string $path): ?array
{
    if (!file_exists($path)) {
        return null;
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        return null;
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function write_json_file(string $path, array $payload): bool
{
    $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        return false;
    }

    return file_put_contents($path, $encoded . PHP_EOL, LOCK_EX) !== false;
}

function write_log(string $logsDir, string $message): void
{
    $line = sprintf("[%s] %s\n", date('c'), $message);
    @file_put_contents($logsDir . '/api.log', $line, FILE_APPEND | LOCK_EX);
}

function fetch_external_text(string $url, int $timeoutSeconds): ?string
{
    $parsed = parse_url($url);
    $scheme = is_array($parsed) ? strtolower((string) ($parsed['scheme'] ?? '')) : '';
    if ($scheme !== 'http' && $scheme !== 'https') {
        return null;
    }

    $body = null;
    $statusCode = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_USERAGENT => 'QuakrsAPI/1.0',
        ]);

        $curlBody = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $hasError = curl_errno($ch) !== 0;

        if (!$hasError && is_string($curlBody)) {
            $body = $curlBody;
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $timeoutSeconds,
                'header' => "User-Agent: QuakrsAPI/1.0\r\n",
            ],
        ]);
        $streamBody = @file_get_contents($url, false, $context);
        if (is_string($streamBody)) {
            $body = $streamBody;
            $statusCode = 200;
        }
    }

    if (is_string($body) && ($statusCode === 0 || ($statusCode >= 200 && $statusCode < 300))) {
        return $body;
    }

    // Fallback path for environments where PHP DNS/SSL stacks fail while system curl works.
    if (function_exists('shell_exec')) {
        $safeUrl = escapeshellarg($url);
        $timeout = max(2, min(60, $timeoutSeconds));
        $command = sprintf(
            'curl -fsSL --connect-timeout %d --max-time %d -A %s %s 2>/dev/null',
            $timeout,
            $timeout,
            escapeshellarg('QuakrsAPI/1.0'),
            $safeUrl
        );
        $shellBody = shell_exec($command);
        if (is_string($shellBody) && $shellBody !== '') {
            return $shellBody;
        }
    }

    return null;
}

function fetch_external_json(string $url, int $timeoutSeconds): ?array
{
    $body = fetch_external_text($url, $timeoutSeconds);
    if (!is_string($body)) {
        return null;
    }

    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : null;
}
