#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/Db.php';
require_once __DIR__ . '/../lib/Store.php';
require_once __DIR__ . '/../lib/Normalizer.php';
require_once __DIR__ . '/../lib/Scoring.php';
require_once __DIR__ . '/../lib/Formatter.php';

$reason = null;
$db = bot_db_open($botConfig, $reason);
if (!$db instanceof mysqli) {
    bot_log($botConfig, 'bot_ingest: db unavailable: ' . ($reason ?? 'unknown'));
    fwrite(STDERR, "DB unavailable: " . ($reason ?? 'unknown') . PHP_EOL);
    exit(1);
}

$all = bot_normalize_all_events($botConfig);
$users = bot_list_active_users($db);
$processed = 0;
$alertCandidates = 0;

foreach ($all as $event) {
    if (!is_array($event)) {
        continue;
    }

    $score = bot_category_score((string) $event['category'], $event);
    $event['score'] = $score;
    $event['decision'] = bot_decision_from_score($score, 'balanced');
    $event['payload_hash'] = hash('sha256', json_encode($event['payload_json'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}');

    $saved = bot_insert_or_update_event($db, $event);
    if (!is_array($saved)) {
        continue;
    }

    $processed++;

    if (($saved['decision'] ?? '') !== 'alert') {
        continue;
    }
    $alertCandidates++;

    foreach ($users as $user) {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            continue;
        }

        $categories = json_decode((string) ($user['categories_json'] ?? '[]'), true);
        if (!is_array($categories) || !in_array((string) $saved['category'], $categories, true)) {
            continue;
        }

        $mode = (string) ($user['mode'] ?? 'essential');
        $decision = bot_decision_from_score((int) $saved['score'], $mode);
        if ($decision !== 'alert') {
            continue;
        }

        if ((string) $saved['category'] === 'earthquakes') {
            $minMag = is_numeric($user['eq_min_magnitude'] ?? null) ? (float) $user['eq_min_magnitude'] : 5.5;
            $mag = is_numeric($saved['magnitude'] ?? null) ? (float) $saved['magnitude'] : 0.0;
            if ($mag < $minMag) {
                continue;
            }
        }

        if (!bot_user_matches_focus_country($saved, (string) ($user['focus_country'] ?? ''))) {
            continue;
        }

        if (bot_has_event_notification($db, $userId, (int) $saved['id'], 'alert')) {
            continue;
        }

        $todayCount = bot_notifications_planned_today_count($db, $userId);
        $cap = match ($mode) {
            'monitor' => (int) $botConfig['max_daily_monitor'],
            'balanced' => (int) $botConfig['max_daily_balanced'],
            default => (int) $botConfig['max_daily_essential'],
        };

        if ($todayCount >= $cap) {
            continue;
        }

        $msg = bot_format_event_message($saved, (string) $botConfig['public_base_url']);
        bot_enqueue_notification($db, $userId, (int) $saved['id'], 'alert', $msg, 90);
    }
}

$db->close();

echo 'processed=' . $processed . ' alert_candidates=' . $alertCandidates . PHP_EOL;

function bot_user_matches_focus_country(array $event, string $focusCountry): bool
{
    if ($focusCountry === '') {
        return true;
    }

    if ($focusCountry === 'IT') {
        $title = strtolower((string) ($event['title'] ?? ''));
        $region = strtolower((string) ($event['region'] ?? ''));
        $text = $title . ' ' . $region;
        return str_contains($text, 'italy') || str_contains($text, 'italia') || str_contains($text, 'sicily') || str_contains($text, 'sicilia');
    }

    return true;
}
