<?php
declare(strict_types=1);

function earthquake_archive_key(array $event): string
{
    $id = strtolower(trim((string) ($event['id'] ?? '')));
    if ($id !== '') {
        return $id;
    }

    $lat = isset($event['latitude']) && is_numeric($event['latitude'])
        ? number_format((float) $event['latitude'], 2, '.', '')
        : 'na';
    $lon = isset($event['longitude']) && is_numeric($event['longitude'])
        ? number_format((float) $event['longitude'], 2, '.', '')
        : 'na';
    $mag = isset($event['magnitude']) && is_numeric($event['magnitude'])
        ? number_format((float) $event['magnitude'], 1, '.', '')
        : 'na';
    $time = isset($event['event_time_utc']) ? (string) $event['event_time_utc'] : 'na';
    $timeMinute = $time !== 'na' ? substr($time, 0, 16) : 'na';

    return implode('|', [$lat, $lon, $mag, $timeMinute]);
}

function earthquake_mysql_role_config(array $appConfig, string $role): array
{
    $normalizedRole = strtolower(trim($role));
    $roleCfgs = is_array($appConfig['mysql_databases'] ?? null) ? $appConfig['mysql_databases'] : [];
    $legacyCfg = is_array($appConfig['archive_mysql'] ?? null) ? $appConfig['archive_mysql'] : [];
    $cfg = is_array($roleCfgs[$normalizedRole] ?? null) ? $roleCfgs[$normalizedRole] : [];
    if (count($cfg) === 0 && $normalizedRole === 'archive') {
        $cfg = $legacyCfg;
    }

    $pickString = static function (array $primary, array $fallback, string $key, string $default = ''): string {
        $primaryValue = trim((string) ($primary[$key] ?? ''));
        if ($primaryValue !== '') {
            return $primaryValue;
        }
        $fallbackValue = trim((string) ($fallback[$key] ?? ''));
        if ($fallbackValue !== '') {
            return $fallbackValue;
        }
        return $default;
    };

    $host = $pickString($cfg, $legacyCfg, 'host');
    $port = (int) ($cfg['port'] ?? ($legacyCfg['port'] ?? 3306));
    $database = $pickString($cfg, $legacyCfg, 'database');
    $user = $pickString($cfg, $legacyCfg, 'user');
    $password = (string) (($cfg['password'] ?? '') !== '' ? $cfg['password'] : ($legacyCfg['password'] ?? ''));
    $charset = $pickString($cfg, $legacyCfg, 'charset', 'utf8mb4');
    $tableRaw = $pickString($cfg, $legacyCfg, 'table', 'earthquake_events');
    $table = preg_match('/^[a-zA-Z0-9_]+$/', $tableRaw) ? $tableRaw : 'earthquake_events';

    return [
        'role' => $normalizedRole,
        'host' => $host,
        'port' => $port,
        'database' => $database,
        'user' => $user,
        'password' => $password,
        'charset' => $charset,
        'table' => $table,
    ];
}

function earthquake_mysql_role_tables(array $appConfig, string $role): array
{
    $cfg = earthquake_mysql_role_config($appConfig, $role);
    $tables = [];
    $primary = preg_match('/^[a-zA-Z0-9_]+$/', (string) ($cfg['table'] ?? '')) === 1
        ? (string) $cfg['table']
        : 'earthquake_events';
    $tables[] = $primary;

    $roleCfgs = is_array($appConfig['mysql_databases'] ?? null) ? $appConfig['mysql_databases'] : [];
    $roleCfg = is_array($roleCfgs[strtolower(trim($role))] ?? null) ? $roleCfgs[strtolower(trim($role))] : [];
    $shadowRaw = trim((string) ($roleCfg['shadow_table'] ?? ''));
    if ($shadowRaw !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $shadowRaw) === 1) {
        $tables[] = $shadowRaw;
    }

    return array_values(array_unique($tables));
}

function earthquake_archive_mysql_config(array $appConfig): array
{
    return earthquake_mysql_role_config($appConfig, 'archive');
}

function earthquake_archive_table_sql(string $table): string
{
    return sprintf(
        'CREATE TABLE IF NOT EXISTS `%s` (
            event_key VARCHAR(191) PRIMARY KEY,
            event_id VARCHAR(191) NULL,
            event_time_utc VARCHAR(40) NOT NULL,
            event_time_ts BIGINT NOT NULL,
            place VARCHAR(512) NULL,
            magnitude DECIMAL(4,2) NULL,
            depth_km DECIMAL(7,2) NULL,
            latitude DECIMAL(10,6) NULL,
            longitude DECIMAL(10,6) NULL,
            source_provider VARCHAR(64) NULL,
            source_providers_json TEXT NULL,
            source_url VARCHAR(512) NULL,
            first_seen_ts INT UNSIGNED NOT NULL,
            last_seen_ts INT UNSIGNED NOT NULL,
            INDEX idx_event_time_ts (event_time_ts),
            INDEX idx_magnitude (magnitude),
            INDEX idx_source_provider (source_provider)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        $table
    );
}

function earthquake_stats_table_sql(string $table): string
{
    return sprintf(
        'CREATE TABLE IF NOT EXISTS `%s` (
            date_utc DATE PRIMARY KEY,
            updated_at_ts INT UNSIGNED NOT NULL,
            events_24h INT UNSIGNED NOT NULL DEFAULT 0,
            providers_json TEXT NULL,
            max_magnitude DECIMAL(4,2) NULL,
            generated_at_utc VARCHAR(40) NOT NULL,
            INDEX idx_updated_at_ts (updated_at_ts)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        $table
    );
}

function earthquake_mysql_open(array $appConfig, string $role, ?string &$reason = null): ?mysqli
{
    if (!function_exists('mysqli_init')) {
        $reason = 'mysqli extension unavailable';
        return null;
    }

    $cfg = earthquake_mysql_role_config($appConfig, $role);
    if ($cfg['host'] === '' || $cfg['database'] === '' || $cfg['user'] === '') {
        $reason = sprintf('missing MySQL %s configuration', $cfg['role']);
        return null;
    }

    $mysqli = mysqli_init();
    if (!$mysqli instanceof mysqli) {
        $reason = 'mysqli init failed';
        return null;
    }

    try {
        $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 6);
        $connected = @$mysqli->real_connect(
            $cfg['host'],
            $cfg['user'],
            $cfg['password'],
            $cfg['database'],
            $cfg['port']
        );
        if ($connected !== true) {
            $reason = 'MySQL connect failed';
            return null;
        }

        $mysqli->set_charset($cfg['charset']);
        $tables = earthquake_mysql_role_tables($appConfig, $cfg['role']);
        foreach ($tables as $tableName) {
            $createSql = $cfg['role'] === 'stats'
                ? earthquake_stats_table_sql($tableName)
                : earthquake_archive_table_sql($tableName);
            if ($mysqli->query($createSql) !== true) {
                $reason = sprintf('MySQL schema init failed (%s:%s)', $cfg['role'], $tableName);
                return null;
            }
        }
    } catch (Throwable $e) {
        $reason = sprintf('MySQL exception (%s): %s', $cfg['role'], $e->getMessage());
        return null;
    }

    return $mysqli;
}

function earthquake_archive_open(array $appConfig, ?string &$reason = null): ?mysqli
{
    return earthquake_mysql_open($appConfig, 'archive', $reason);
}

function earthquake_archive_ingest(mysqli $db, array $events, int $nowTs, string|array $table): int
{
    if (is_array($table)) {
        $written = 0;
        foreach ($table as $tableName) {
            if (!is_string($tableName) || preg_match('/^[a-zA-Z0-9_]+$/', $tableName) !== 1) {
                continue;
            }
            $written += earthquake_archive_ingest($db, $events, $nowTs, $tableName);
        }
        return $written;
    }

    $sql = sprintf(
        'INSERT INTO `%s` (
            event_key, event_id, event_time_utc, event_time_ts, place, magnitude, depth_km, latitude, longitude,
            source_provider, source_providers_json, source_url, first_seen_ts, last_seen_ts
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            event_id = COALESCE(NULLIF(VALUES(event_id), \'\'), event_id),
            event_time_utc = VALUES(event_time_utc),
            event_time_ts = VALUES(event_time_ts),
            place = COALESCE(NULLIF(VALUES(place), \'\'), place),
            magnitude = COALESCE(VALUES(magnitude), magnitude),
            depth_km = COALESCE(VALUES(depth_km), depth_km),
            latitude = COALESCE(VALUES(latitude), latitude),
            longitude = COALESCE(VALUES(longitude), longitude),
            source_provider = COALESCE(NULLIF(VALUES(source_provider), \'\'), source_provider),
            source_providers_json = COALESCE(NULLIF(VALUES(source_providers_json), \'\'), source_providers_json),
            source_url = COALESCE(NULLIF(VALUES(source_url), \'\'), source_url),
            last_seen_ts = VALUES(last_seen_ts)',
        $table
    );

    $stmt = $db->prepare($sql);
    if (!$stmt instanceof mysqli_stmt) {
        return 0;
    }

    $written = 0;
    foreach ($events as $event) {
        if (!is_array($event)) {
            continue;
        }

        $eventTime = (string) ($event['event_time_utc'] ?? '');
        if ($eventTime === '') {
            continue;
        }
        $eventDt = date_create_immutable($eventTime, new DateTimeZone('UTC'));
        if (!$eventDt instanceof DateTimeImmutable) {
            continue;
        }
        // format('U') returns a Unix timestamp string and works for pre-1970 dates too.
        $eventTsText = $eventDt->setTimezone(new DateTimeZone('UTC'))->format('U');
        if ($eventTsText === '' || !preg_match('/^-?\d+$/', $eventTsText)) {
            continue;
        }

        $providers = is_array($event['source_providers'] ?? null)
            ? array_values(array_unique(array_filter($event['source_providers'], static fn (mixed $v): bool => is_string($v) && $v !== '')))
            : [];
        if (count($providers) === 0) {
            $provider = trim((string) ($event['source_provider'] ?? ''));
            if ($provider !== '') {
                $providers = [$provider];
            }
        }

        $eventKey = earthquake_archive_key($event);
        $eventId = (string) ($event['id'] ?? '');
        $place = (string) ($event['place'] ?? '');
        $magnitude = isset($event['magnitude']) && is_numeric($event['magnitude']) ? (string) ((float) $event['magnitude']) : null;
        $depthKm = isset($event['depth_km']) && is_numeric($event['depth_km']) ? (string) abs((float) $event['depth_km']) : null;
        $latitude = isset($event['latitude']) && is_numeric($event['latitude']) ? (string) ((float) $event['latitude']) : null;
        $longitude = isset($event['longitude']) && is_numeric($event['longitude']) ? (string) ((float) $event['longitude']) : null;
        $sourceProvider = (string) ($event['source_provider'] ?? '');
        $sourceProvidersJson = json_encode($providers, JSON_UNESCAPED_SLASHES);
        $sourceUrl = (string) ($event['source_url'] ?? '');
        $nowText = (string) $nowTs;

        if ($sourceProvidersJson === false) {
            $sourceProvidersJson = '[]';
        }

        $ok = $stmt->bind_param(
            'ssssssssssssss',
            $eventKey,
            $eventId,
            $eventTime,
            $eventTsText,
            $place,
            $magnitude,
            $depthKm,
            $latitude,
            $longitude,
            $sourceProvider,
            $sourceProvidersJson,
            $sourceUrl,
            $nowText,
            $nowText
        );
        if ($ok !== true) {
            continue;
        }
        if ($stmt->execute() === true) {
            $written += 1;
        }
    }

    $stmt->close();
    return $written;
}

function earthquake_stats_upsert_daily(
    mysqli $db,
    string $table,
    int $nowTs,
    int $events24h,
    array $providers,
    ?float $maxMagnitude
): bool {
    $providersJson = json_encode(array_values(array_unique(array_filter($providers, static fn (mixed $v): bool => is_string($v) && trim($v) !== ''))), JSON_UNESCAPED_SLASHES);
    if (!is_string($providersJson) || $providersJson === '') {
        $providersJson = '[]';
    }
    $dateUtc = gmdate('Y-m-d', $nowTs);
    $generatedAt = gmdate('c', $nowTs);
    $maxMagText = $maxMagnitude !== null ? (string) $maxMagnitude : null;
    $eventsInt = max(0, $events24h);
    $nowInt = max(0, $nowTs);

    $sql = sprintf(
        'INSERT INTO `%s` (date_utc, updated_at_ts, events_24h, providers_json, max_magnitude, generated_at_utc)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            updated_at_ts = VALUES(updated_at_ts),
            events_24h = VALUES(events_24h),
            providers_json = VALUES(providers_json),
            max_magnitude = VALUES(max_magnitude),
            generated_at_utc = VALUES(generated_at_utc)',
        $table
    );
    $stmt = $db->prepare($sql);
    if (!$stmt instanceof mysqli_stmt) {
        return false;
    }
    $ok = $stmt->bind_param('siisss', $dateUtc, $nowInt, $eventsInt, $providersJson, $maxMagText, $generatedAt) && $stmt->execute();
    $stmt->close();
    return $ok;
}
