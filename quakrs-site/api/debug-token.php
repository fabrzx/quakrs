<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$forceRefresh = isset($_GET['force_refresh']) && (string) $_GET['force_refresh'] === '1';
if (!$forceRefresh) {
    json_response(400, ['ok' => false, 'error' => 'Missing required query parameter: force_refresh=1']);
}

require_refresh_token($appConfig);

json_response(200, [
    'ok' => true,
    'message' => 'Refresh token validated',
    'generated_at' => gmdate('c'),
]);
