<?php
declare(strict_types=1);

require_once __DIR__ . '/Db.php';

function bot_upsert_user(mysqli $db, array $tg): ?array
{
    $telegramId = (int) ($tg['id'] ?? 0);
    $chatId = (int) ($tg['chat_id'] ?? 0);
    if ($telegramId <= 0 || $chatId === 0) {
        return null;
    }

    $username = (string) ($tg['username'] ?? '');
    $firstName = (string) ($tg['first_name'] ?? '');
    $language = (string) ($tg['language_code'] ?? '');

    $sql = "INSERT INTO telegram_users (telegram_user_id, chat_id, username, first_name, language_code)
            VALUES (?, ?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''))
            ON DUPLICATE KEY UPDATE chat_id=VALUES(chat_id), username=VALUES(username), first_name=VALUES(first_name), language_code=VALUES(language_code), is_active=1";
    if (!bot_db_execute($db, $sql, 'iisss', [$telegramId, $chatId, $username, $firstName, $language])) {
        return null;
    }

    $row = bot_db_fetch_one($db, 'SELECT * FROM telegram_users WHERE telegram_user_id = ? LIMIT 1', 'i', [$telegramId]);
    if (!is_array($row)) {
        return null;
    }

    $prefSql = "INSERT IGNORE INTO telegram_user_preferences (user_id, mode, categories_json, eq_min_magnitude)
                VALUES (?, 'essential', JSON_ARRAY('earthquakes','volcanoes','tsunami','space_weather'), 5.5)";
    bot_db_execute($db, $prefSql, 'i', [(int) $row['id']]);

    return $row;
}

function bot_get_user_pref(mysqli $db, int $userId): ?array
{
    return bot_db_fetch_one($db, 'SELECT * FROM telegram_user_preferences WHERE user_id = ? LIMIT 1', 'i', [$userId]);
}

function bot_set_user_mode(mysqli $db, int $userId, string $mode): bool
{
    if (!in_array($mode, ['essential', 'balanced', 'monitor'], true)) {
        return false;
    }
    return bot_db_execute($db, 'UPDATE telegram_user_preferences SET mode = ? WHERE user_id = ?', 'si', [$mode, $userId]);
}

function bot_set_user_threshold(mysqli $db, int $userId, float $minMagnitude): bool
{
    if ($minMagnitude < 0.0 || $minMagnitude > 10.0) {
        return false;
    }
    return bot_db_execute($db, 'UPDATE telegram_user_preferences SET eq_min_magnitude = ? WHERE user_id = ?', 'di', [$minMagnitude, $userId]);
}

function bot_set_user_area(mysqli $db, int $userId, string $area): bool
{
    $areaNorm = strtolower(trim($area));
    if ($areaNorm === 'italia' || $areaNorm === 'it') {
        return bot_db_execute($db, 'UPDATE telegram_user_preferences SET focus_country = ? WHERE user_id = ?', 'si', ['IT', $userId]);
    }
    if ($areaNorm === 'global' || $areaNorm === 'world') {
        return bot_db_execute($db, 'UPDATE telegram_user_preferences SET focus_country = NULL WHERE user_id = ?', 'i', [$userId]);
    }
    return false;
}

function bot_toggle_category(mysqli $db, int $userId, string $category): bool
{
    $pref = bot_get_user_pref($db, $userId);
    if (!is_array($pref)) {
        return false;
    }
    $current = json_decode((string) ($pref['categories_json'] ?? '[]'), true);
    $cats = is_array($current) ? array_values(array_unique(array_map('strval', $current))) : [];

    if (in_array($category, $cats, true)) {
        $cats = array_values(array_filter($cats, static fn (string $c): bool => $c !== $category));
    } else {
        $cats[] = $category;
    }

    if ($cats === []) {
        $cats = ['earthquakes'];
    }

    return bot_db_execute(
        $db,
        'UPDATE telegram_user_preferences SET categories_json = ? WHERE user_id = ?',
        'si',
        [json_encode(array_values($cats), JSON_UNESCAPED_SLASHES), $userId]
    );
}

function bot_insert_or_update_event(mysqli $db, array $event): ?array
{
    $payloadJson = json_encode($event['payload_json'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!is_string($payloadJson)) {
        $payloadJson = '{}';
    }

    $sql = "INSERT INTO bot_events
      (category, event_key, provider, event_time, title, summary, country, region, latitude, longitude, magnitude, depth_km, severity_label, source_url, payload_json, score, decision, payload_hash)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE
      title=VALUES(title), summary=VALUES(summary), country=VALUES(country), region=VALUES(region),
      latitude=VALUES(latitude), longitude=VALUES(longitude), magnitude=VALUES(magnitude), depth_km=VALUES(depth_km),
      severity_label=VALUES(severity_label), source_url=VALUES(source_url), payload_json=VALUES(payload_json),
      score=VALUES(score), decision=VALUES(decision), payload_hash=VALUES(payload_hash), updated_at=CURRENT_TIMESTAMP";

    $ok = bot_db_execute(
        $db,
        $sql,
        'ssssssssddddsssiss',
        [
            (string) $event['category'],
            (string) $event['event_key'],
            (string) $event['provider'],
            (string) $event['event_time'],
            (string) $event['title'],
            (string) ($event['summary'] ?? ''),
            (string) ($event['country'] ?? ''),
            (string) ($event['region'] ?? ''),
            (float) ($event['latitude'] ?? 0.0),
            (float) ($event['longitude'] ?? 0.0),
            (float) ($event['magnitude'] ?? 0.0),
            (float) ($event['depth_km'] ?? 0.0),
            (string) ($event['severity_label'] ?? ''),
            (string) ($event['source_url'] ?? ''),
            $payloadJson,
            (int) $event['score'],
            (string) $event['decision'],
            (string) $event['payload_hash'],
        ]
    );

    if (!$ok) {
        return null;
    }

    return bot_db_fetch_one($db, 'SELECT * FROM bot_events WHERE event_key = ? LIMIT 1', 's', [(string) $event['event_key']]);
}

function bot_list_active_users(mysqli $db): array
{
    $result = $db->query("SELECT u.id, u.chat_id, p.mode, p.categories_json, p.eq_min_magnitude, p.digest_enabled, p.focus_country
                          FROM telegram_users u
                          JOIN telegram_user_preferences p ON p.user_id = u.id
                          WHERE u.is_active = 1");
    if (!$result instanceof mysqli_result) {
        return [];
    }
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        if (is_array($row)) {
            $rows[] = $row;
        }
    }
    $result->free();
    return $rows;
}

function bot_notifications_today_count(mysqli $db, int $userId): int
{
    $row = bot_db_fetch_one(
        $db,
        'SELECT COUNT(*) AS c FROM event_notifications WHERE user_id = ? AND sent_at >= CURDATE()',
        'i',
        [$userId]
    );
    return (int) ($row['c'] ?? 0);
}

function bot_notifications_planned_today_count(mysqli $db, int $userId): int
{
    $sent = bot_notifications_today_count($db, $userId);
    $row = bot_db_fetch_one(
        $db,
        "SELECT COUNT(*) AS c
         FROM notification_queue
         WHERE user_id = ? AND status IN ('pending','processing','failed') AND scheduled_at >= CURDATE()",
        'i',
        [$userId]
    );
    return $sent + (int) ($row['c'] ?? 0);
}

function bot_has_event_notification(mysqli $db, int $userId, int $eventId, string $type): bool
{
    $row = bot_db_fetch_one(
        $db,
        'SELECT id FROM event_notifications WHERE user_id = ? AND event_id = ? AND notification_type = ? LIMIT 1',
        'iis',
        [$userId, $eventId, $type]
    );
    return is_array($row);
}

function bot_enqueue_notification(mysqli $db, int $userId, ?int $eventId, string $type, string $text, int $priority = 50): bool
{
    $payload = json_encode(['text' => $text], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($payload)) {
        return false;
    }

    return bot_db_execute(
        $db,
        'INSERT INTO notification_queue (user_id, event_id, notification_type, priority, payload_json, scheduled_at) VALUES (?, NULLIF(?, 0), ?, ?, ?, NOW())',
        'iisis',
        [$userId, (int) ($eventId ?? 0), $type, $priority, $payload]
    );
}

function bot_fetch_queue_batch(mysqli $db, int $limit = 30): array
{
    $sql = "SELECT q.*, u.chat_id
            FROM notification_queue q
            JOIN telegram_users u ON u.id = q.user_id
            WHERE q.status = 'pending' AND q.scheduled_at <= NOW()
            ORDER BY q.priority DESC, q.id ASC
            LIMIT " . max(1, min(200, $limit));

    $result = $db->query($sql);
    if (!$result instanceof mysqli_result) {
        return [];
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        if (is_array($row)) {
            $rows[] = $row;
        }
    }
    $result->free();
    return $rows;
}

function bot_mark_queue_processing(mysqli $db, int $queueId): bool
{
    return bot_db_execute($db, "UPDATE notification_queue SET status='processing', attempts=attempts+1 WHERE id = ? AND status='pending'", 'i', [$queueId]);
}

function bot_mark_queue_sent(mysqli $db, int $queueId): bool
{
    return bot_db_execute($db, "UPDATE notification_queue SET status='sent', updated_at=NOW() WHERE id = ?", 'i', [$queueId]);
}

function bot_mark_queue_failed(mysqli $db, int $queueId, string $error): bool
{
    return bot_db_execute(
        $db,
        "UPDATE notification_queue SET status = IF(attempts >= 5, 'dead', 'failed'), last_error = ?, scheduled_at = DATE_ADD(NOW(), INTERVAL POW(2, LEAST(attempts, 4)) MINUTE), updated_at=NOW() WHERE id = ?",
        'si',
        [mb_substr($error, 0, 480), $queueId]
    );
}

function bot_record_notification(mysqli $db, int $userId, ?int $eventId, string $type, string $text, ?int $telegramMessageId, string $status): bool
{
    $hash = hash('sha256', $userId . '|' . ($eventId ?? 0) . '|' . $type . '|' . $text);
    return bot_db_execute(
        $db,
        'INSERT INTO event_notifications (event_id, user_id, notification_type, message_hash, telegram_message_id, status) VALUES (NULLIF(?, 0), ?, ?, ?, NULLIF(?, 0), ?)',
        'iissis',
        [(int) ($eventId ?? 0), $userId, $type, $hash, (int) ($telegramMessageId ?? 0), $status]
    );
}

function bot_latest_events(mysqli $db, int $limit = 10): array
{
    $result = $db->query(
        "SELECT *
         FROM bot_events
         WHERE decision IN ('digest','alert')
         ORDER BY event_time DESC
         LIMIT " . max(1, min(500, $limit))
    );
    if (!$result instanceof mysqli_result) {
        return [];
    }
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        if (is_array($row)) {
            $rows[] = $row;
        }
    }
    $result->free();
    return $rows;
}

function bot_latest_digest(mysqli $db): ?array
{
    return bot_db_fetch_one($db, "SELECT * FROM digest_runs WHERE status='completed' ORDER BY digest_date DESC, id DESC LIMIT 1");
}
