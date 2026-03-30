<?php
declare(strict_types=1);

function bot_db_open(array $botConfig, ?string &$reason = null): ?mysqli
{
    $cfg = is_array($botConfig['db'] ?? null) ? $botConfig['db'] : [];
    $host = (string) ($cfg['host'] ?? '');
    $database = (string) ($cfg['database'] ?? '');
    $user = (string) ($cfg['user'] ?? '');

    if ($host === '' || $database === '' || $user === '') {
        $reason = 'Bot DB not configured';
        return null;
    }

    if (!function_exists('mysqli_init')) {
        $reason = 'mysqli unavailable';
        return null;
    }

    $db = mysqli_init();
    if (!$db instanceof mysqli) {
        $reason = 'mysqli init failed';
        return null;
    }

    $db->options(MYSQLI_OPT_CONNECT_TIMEOUT, 6);
    $ok = @$db->real_connect(
        $host,
        $user,
        (string) ($cfg['password'] ?? ''),
        $database,
        (int) ($cfg['port'] ?? 3306)
    );

    if ($ok !== true) {
        $reason = 'MySQL connect failed';
        return null;
    }

    $db->set_charset((string) ($cfg['charset'] ?? 'utf8mb4'));
    return $db;
}

function bot_db_fetch_one(mysqli $db, string $sql, string $types = '', array $params = []): ?array
{
    $stmt = $db->prepare($sql);
    if (!$stmt instanceof mysqli_stmt) {
        return null;
    }

    if ($types !== '' && $params !== []) {
        if (!$stmt->bind_param($types, ...$params)) {
            $stmt->close();
            return null;
        }
    }

    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $result = $stmt->get_result();
    $row = $result instanceof mysqli_result ? $result->fetch_assoc() : null;
    if ($result instanceof mysqli_result) {
        $result->free();
    }
    $stmt->close();

    return is_array($row) ? $row : null;
}

function bot_db_execute(mysqli $db, string $sql, string $types = '', array $params = []): bool
{
    $stmt = $db->prepare($sql);
    if (!$stmt instanceof mysqli_stmt) {
        return false;
    }

    if ($types !== '' && $params !== []) {
        if (!$stmt->bind_param($types, ...$params)) {
            $stmt->close();
            return false;
        }
    }

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}
