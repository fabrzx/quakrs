#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/Db.php';
require_once __DIR__ . '/../lib/Store.php';
require_once __DIR__ . '/../lib/Formatter.php';

$reason = null;
$db = bot_db_open($botConfig, $reason);
if (!$db instanceof mysqli) {
    bot_log($botConfig, 'bot_daily_digest: db unavailable: ' . ($reason ?? 'unknown'));
    fwrite(STDERR, "DB unavailable\n");
    exit(1);
}

$date = date('Y-m-d');
$users = bot_list_active_users($db);
$enqueued = 0;
$globalText = null;

foreach ($users as $user) {
    $userId = (int) ($user['id'] ?? 0);
    if ($userId <= 0) {
        continue;
    }

    if ((int) ($user['digest_enabled'] ?? 1) !== 1) {
        continue;
    }

    $text = bot_build_digest_for_user($db, $user, (string) $botConfig['public_base_url']);
    if ($text === '') {
        continue;
    }

    if ($globalText === null) {
        $globalText = $text;
    }

    bot_enqueue_notification($db, $userId, null, 'digest', $text, 40);
    $enqueued++;
}

$saveText = $globalText ?? "QUAKRS // DAILY BRIEF " . date('d M Y') . "\n\nNO RELEVANT UPDATES IN LAST 24H.\nOPEN " . (string) $botConfig['public_base_url'];
bot_db_execute(
    $db,
    "INSERT INTO digest_runs (digest_date, variant, status, content_text) VALUES (?, 'global', 'completed', ?)
     ON DUPLICATE KEY UPDATE status='completed', content_text=VALUES(content_text), created_at=CURRENT_TIMESTAMP",
    'ss',
    [$date, $saveText]
);

$db->close();

echo 'digest_date=' . $date . ' enqueued=' . $enqueued . PHP_EOL;

function bot_build_digest_for_user(mysqli $db, array $user, string $baseUrl): string
{
    $categories = json_decode((string) ($user['categories_json'] ?? '[]'), true);
    if (!is_array($categories) || $categories === []) {
        $categories = ['earthquakes', 'volcanoes', 'tsunami', 'space_weather'];
    }

    $focusCountry = (string) ($user['focus_country'] ?? '');
    $eqMinMag = is_numeric($user['eq_min_magnitude'] ?? null) ? (float) $user['eq_min_magnitude'] : 5.5;
    $events = bot_digest_events_last_24h($db, 600);

    $filtered = [];
    foreach ($events as $ev) {
        $cat = (string) ($ev['category'] ?? '');
        if (!in_array($cat, $categories, true)) {
            continue;
        }
        if (!bot_digest_matches_focus($ev, $focusCountry)) {
            continue;
        }
        if ($cat === 'earthquakes') {
            $mag = is_numeric($ev['magnitude'] ?? null) ? (float) $ev['magnitude'] : 0.0;
            if ($mag < $eqMinMag) {
                continue;
            }
        }
        $filtered[] = $ev;
    }

    $sections = bot_digest_sections($filtered, $categories);
    return bot_format_daily_digest($sections, $baseUrl, date('d M Y'));
}

function bot_digest_events_last_24h(mysqli $db, int $limit = 500): array
{
    $sql = "SELECT *
            FROM bot_events
            WHERE event_time >= DATE_SUB(NOW(), INTERVAL 1 DAY)
              AND decision IN ('digest','alert')
            ORDER BY event_time DESC
            LIMIT " . max(10, min(2000, $limit));
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

function bot_digest_matches_focus(array $event, string $focusCountry): bool
{
    if ($focusCountry === '') {
        return true;
    }
    if ($focusCountry === 'IT') {
        $txt = strtolower((string) (($event['title'] ?? '') . ' ' . ($event['region'] ?? '')));
        return str_contains($txt, 'italy') || str_contains($txt, 'italia') || str_contains($txt, 'sicily') || str_contains($txt, 'sicilia');
    }
    return true;
}

function bot_digest_sections(array $events, array $categories): array
{
    $byCat = [
        'earthquakes' => [],
        'volcanoes' => [],
        'tsunami' => [],
        'space_weather' => [],
    ];

    foreach ($events as $ev) {
        $cat = (string) ($ev['category'] ?? '');
        if (isset($byCat[$cat])) {
            $byCat[$cat][] = $ev;
        }
    }

    $sections = [];
    foreach ($categories as $cat) {
        if (!isset($byCat[$cat])) {
            continue;
        }
        $items = $byCat[$cat];
        if ($cat === 'earthquakes') {
            if ($items === []) {
                $sections[$cat] = 'TOTAL 0' . "\n" . 'MAX   M0.0' . "\n" . 'AREA  N/A';
                continue;
            }
            $maxMag = 0.0;
            $topArea = '';
            $areas = [];
            foreach ($items as $item) {
                $mag = is_numeric($item['magnitude'] ?? null) ? (float) $item['magnitude'] : 0.0;
                if ($mag > $maxMag) {
                    $maxMag = $mag;
                }
                $area = (string) ($item['region'] ?? ($item['title'] ?? 'Unknown'));
                $areas[$area] = ($areas[$area] ?? 0) + 1;
            }
            arsort($areas);
            $topArea = (string) array_key_first($areas);
            $sections[$cat] = 'TOTAL ' . count($items)
                . "\nMAX   M" . number_format($maxMag, 1)
                . "\nAREA  " . ($topArea !== '' ? $topArea : 'N/A');
            continue;
        }

        if ($items === []) {
            $sections[$cat] = 'TOTAL 0' . "\n" . 'PEAK  0/100';
            continue;
        }

        $maxScore = 0;
        foreach ($items as $item) {
            $score = (int) ($item['score'] ?? 0);
            if ($score > $maxScore) {
                $maxScore = $score;
            }
        }
        $sections[$cat] = 'TOTAL ' . count($items) . "\nPEAK  {$maxScore}/100";
    }

    return $sections;
}
