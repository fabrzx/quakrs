#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/Db.php';
require_once __DIR__ . '/../lib/Store.php';
require_once __DIR__ . '/../lib/Telegram.php';

$reason = null;
$db = bot_db_open($botConfig, $reason);
if (!$db instanceof mysqli) {
    bot_log($botConfig, 'bot_dispatch: db unavailable: ' . ($reason ?? 'unknown'));
    fwrite(STDERR, "DB unavailable\n");
    exit(1);
}

$batch = bot_fetch_queue_batch($db, 40);
$sent = 0;
$failed = 0;

foreach ($batch as $job) {
    $queueId = (int) ($job['id'] ?? 0);
    $userId = (int) ($job['user_id'] ?? 0);
    $eventId = isset($job['event_id']) ? (int) $job['event_id'] : null;
    $type = (string) ($job['notification_type'] ?? 'alert');
    $chatId = (int) ($job['chat_id'] ?? 0);

    if ($queueId <= 0 || $userId <= 0 || $chatId === 0) {
        continue;
    }

    if (!bot_mark_queue_processing($db, $queueId)) {
        continue;
    }

    $payload = json_decode((string) ($job['payload_json'] ?? '{}'), true);
    $text = is_array($payload) ? (string) ($payload['text'] ?? '') : '';
    if ($text === '') {
        bot_mark_queue_failed($db, $queueId, 'Empty payload text');
        $failed++;
        continue;
    }

    $resp = bot_send_message($botConfig, $chatId, $text);
    if (!empty($resp['ok'])) {
        $messageId = isset($resp['result']['message_id']) ? (int) $resp['result']['message_id'] : null;
        bot_mark_queue_sent($db, $queueId);
        bot_record_notification($db, $userId, $eventId, $type, $text, $messageId, 'sent');
        $sent++;
        continue;
    }

    $err = (string) ($resp['description'] ?? ($resp['error'] ?? 'Telegram send failed'));
    bot_mark_queue_failed($db, $queueId, $err);
    bot_record_notification($db, $userId, $eventId, $type, $text, null, 'failed');
    $failed++;
}

$db->close();

echo 'sent=' . $sent . ' failed=' . $failed . PHP_EOL;
