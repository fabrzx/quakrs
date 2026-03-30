<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/Db.php';
require_once __DIR__ . '/lib/Store.php';
require_once __DIR__ . '/lib/Telegram.php';
require_once __DIR__ . '/lib/Formatter.php';

$reason = null;
$db = bot_db_open($botConfig, $reason);
if (!$db instanceof mysqli) {
    bot_json_response(503, ['ok' => false, 'error' => 'Bot DB unavailable']);
}

$raw = file_get_contents('php://input');
$update = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($update)) {
    bot_json_response(200, ['ok' => true]);
}

$message = is_array($update['message'] ?? null) ? $update['message'] : null;
$callback = is_array($update['callback_query'] ?? null) ? $update['callback_query'] : null;

if (!is_array($message) && is_array($callback)) {
    $message = is_array($callback['message'] ?? null) ? $callback['message'] : null;
}

if (!is_array($message)) {
    bot_json_response(200, ['ok' => true]);
}

$from = is_array($message['from'] ?? null) ? $message['from'] : [];
$chat = is_array($message['chat'] ?? null) ? $message['chat'] : [];

if (is_array($callback)) {
    $cbFrom = is_array($callback['from'] ?? null) ? $callback['from'] : [];
    if ($cbFrom !== []) {
        $from = $cbFrom;
    }
}

$chat = is_array($message['chat'] ?? null) ? $message['chat'] : [];
$text = trim((string) ($message['text'] ?? ''));

$user = bot_upsert_user($db, [
    'id' => (int) ($from['id'] ?? 0),
    'chat_id' => (int) ($chat['id'] ?? 0),
    'username' => (string) ($from['username'] ?? ''),
    'first_name' => (string) ($from['first_name'] ?? ''),
    'language_code' => (string) ($from['language_code'] ?? ''),
]);

if (!is_array($user)) {
    bot_json_response(200, ['ok' => true]);
}

$userId = (int) ($user['id'] ?? 0);
$chatId = (int) ($user['chat_id'] ?? 0);
$reply = '';
$replyMarkup = null;

if (is_array($callback)) {
    $cbId = (string) ($callback['id'] ?? '');
    $cbData = (string) ($callback['data'] ?? '');
    [$reply, $replyMarkup] = bot_handle_callback_action($db, $userId, $cbData);
    if ($reply === '__ACTION_LATEST__') {
        $reply = bot_build_latest_message($db, $userId, '/latest');
        $replyMarkup = null;
    }
    if ($cbId !== '') {
        bot_answer_callback_query($botConfig, $cbId, $reply !== '' ? 'ok' : '');
    }
    if ($reply !== '') {
        bot_send_message_with_markup($botConfig, $chatId, $reply, $replyMarkup);
    }
    $db->close();
    bot_json_response(200, ['ok' => true]);
}

if ($text === '/start') {
    $reply = "quakrs // boot\n"
        . "engine smart_filter\n"
        . "noise low\n"
        . "input 4 channels\n"
        . "state online";
    $replyMarkup = bot_settings_keyboard($db, $userId);
} elseif ($text === '/help') {
    $reply = "quakrs // help\n"
        . "[consulta]\n"
        . "/latest\n"
        . "/earthquakes\n"
        . "/volcanoes\n"
        . "/tsunami\n"
        . "/spaceweather\n"
        . "/dailybrief\n\n"
        . "[imposta]\n"
        . "/mode essential|balanced|monitor\n"
        . "/subscriptions show|+cat|-cat\n"
        . "/threshold <mag>\n"
        . "/area global|italia\n\n"
        . "[stato]\n"
        . "/profile\n/settings\n\n"
        . "[livelli m]\n"
        . "🟪>=7 🟥>=6 🟧>=5 🟨>=3 🟦>=2 ⬜<2\n\n"
        . "[categorie]\n"
        . "🟩eq  🟪volc  🟦tsu  🟨space";
    $replyMarkup = bot_quick_nav_keyboard();
} elseif (str_starts_with($text, '/mode')) {
    $parts = preg_split('/\s+/', $text);
    $mode = strtolower((string) ($parts[1] ?? ''));
    if (in_array($mode, ['essential', 'balanced', 'monitor'], true) && bot_set_user_mode($db, $userId, $mode)) {
        $reply = 'ok: mode ' . $mode;
        $replyMarkup = bot_settings_keyboard($db, $userId);
    } else {
        $reply = 'errore mode' . "\n" . 'usa: /mode essential|balanced|monitor';
    }
} elseif (str_starts_with($text, '/threshold')) {
    $parts = preg_split('/\s+/', $text);
    $rawVal = (string) ($parts[1] ?? '');
    $val = is_numeric($rawVal) ? (float) $rawVal : -1.0;
    if ($val >= 0.0 && $val <= 10.0 && bot_set_user_threshold($db, $userId, $val)) {
        $reply = 'ok: soglia terremoti m' . number_format($val, 1);
        $replyMarkup = bot_settings_keyboard($db, $userId);
    } else {
        $reply = 'errore soglia' . "\n" . 'usa: /threshold 4.5';
    }
} elseif (str_starts_with($text, '/area')) {
    $parts = preg_split('/\s+/', $text);
    $area = strtolower((string) ($parts[1] ?? ''));
    if (bot_set_user_area($db, $userId, $area)) {
        $reply = ($area === 'italia' || $area === 'it')
            ? 'ok: area italia'
            : 'ok: area globale';
        $replyMarkup = bot_settings_keyboard($db, $userId);
    } else {
        $reply = 'errore area' . "\n" . 'usa: /area global|italia';
    }
} elseif (str_starts_with($text, '/subscriptions') || $text === '/profile') {
    $pref = bot_get_user_pref($db, $userId);
    $cats = is_array($pref) ? json_decode((string) ($pref['categories_json'] ?? '[]'), true) : [];
    if (!is_array($cats)) {
        $cats = [];
    }
    if (str_starts_with($text, '/subscriptions') && $text !== '/subscriptions') {
        $parts = preg_split('/\s+/', $text);
        $op = (string) ($parts[1] ?? '');
        $allCats = ['earthquakes', 'volcanoes', 'tsunami', 'space_weather'];
        if ($op !== '' && $op !== 'show') {
            $sign = substr($op, 0, 1);
            $cat = ltrim($op, '+-');
            if ($cat === 'spaceweather') {
                $cat = 'space_weather';
            }
            if (in_array($cat, $allCats, true)) {
                $isEnabled = in_array($cat, $cats, true);
                $targetEnable = $sign === '+' ? true : ($sign === '-' ? false : !$isEnabled);
                if (($targetEnable && !$isEnabled) || (!$targetEnable && $isEnabled)) {
                    bot_toggle_category($db, $userId, $cat);
                    $pref = bot_get_user_pref($db, $userId);
                    $cats = is_array($pref) ? json_decode((string) ($pref['categories_json'] ?? '[]'), true) : [];
                    if (!is_array($cats)) {
                        $cats = [];
                    }
                }
            }
        }
    }
    $reply = "quakrs // profile\n"
        . 'mode: ' . (string) ($pref['mode'] ?? 'essential') . "\n"
        . 'categorie: ' . bot_render_categories_compact($cats) . "\n"
        . 'eq min: m' . (string) ($pref['eq_min_magnitude'] ?? '5.5') . "\n"
        . 'area: ' . (((string) ($pref['focus_country'] ?? '') === 'IT') ? 'italia' : 'globale') . "\n"
        . 'digest: ' . (((int) ($pref['digest_enabled'] ?? 1) === 1) ? 'on' : 'off');
    $replyMarkup = bot_settings_keyboard($db, $userId);
} elseif ($text === '/latest' || $text === '/earthquakes' || $text === '/volcanoes' || $text === '/tsunami' || $text === '/spaceweather') {
    $reply = bot_build_latest_message($db, $userId, $text);
    $replyMarkup = null;
} elseif ($text === '/settings') {
    $pref = bot_get_user_pref($db, $userId);
    $reply = "quakrs // settings\n"
        . 'mode: ' . (string) ($pref['mode'] ?? 'essential') . "\n"
        . 'area: ' . (((string) ($pref['focus_country'] ?? '') === 'IT') ? 'italia' : 'globale') . "\n"
        . 'eq min: m' . (string) ($pref['eq_min_magnitude'] ?? '5.5') . "\n"
        . "controlli: bottoni";
    $replyMarkup = bot_settings_keyboard($db, $userId);
} elseif ($text === '/dailybrief') {
    $digest = bot_latest_digest($db);
    $reply = is_array($digest) ? (string) ($digest['content_text'] ?? 'Digest unavailable') : 'Digest non disponibile.';
    $replyMarkup = null;
} else {
    $reply = "errore comando\nusa /help";
    $replyMarkup = bot_quick_nav_keyboard();
}

bot_send_message_with_markup($botConfig, $chatId, $reply, $replyMarkup);
$db->close();

bot_json_response(200, ['ok' => true]);

function bot_format_latest_line(array $ev): string
{
    $cat = (string) ($ev['category'] ?? '');
    $title = (string) ($ev['title'] ?? 'Event');
    $time = bot_compact_time((string) ($ev['event_time'] ?? ''));

    return match ($cat) {
        'earthquakes' => 'eq  ' . bot_eq_mag_badge((float) ($ev['magnitude'] ?? 0.0)) . ' m' . number_format((float) ($ev['magnitude'] ?? 0.0), 1) . '  ' . $title . '  ' . $time,
        'volcanoes' => 'volc ' . bot_shorten_volcano_title($title) . '  ' . $time,
        'tsunami' => 'tsu ' . $title . ((string) ($ev['severity_label'] ?? '') !== '' ? ' [' . strtolower((string) $ev['severity_label']) . ']' : '') . '  ' . $time,
        'space_weather' => 'space  ' . ((string) ($ev['summary'] ?? '') !== '' ? (string) $ev['summary'] : $title) . '  ' . $time,
        default => 'evt ' . $title . '  ' . $time,
    };
}

function bot_latest_is_fresh(array $ev, string $category, bool $categorySpecific): bool
{
    $ts = strtotime((string) ($ev['event_time'] ?? ''));
    if (!is_int($ts) || $ts <= 0) {
        return false;
    }
    $ageHours = (time() - $ts) / 3600;
    if ($ageHours < 0) {
        return true;
    }

    if ($category === 'earthquakes') {
        return $ageHours <= ($categorySpecific ? 72 : 36);
    }
    if ($category === 'space_weather') {
        return $ageHours <= ($categorySpecific ? 48 : 24);
    }
    if ($category === 'tsunami') {
        return $ageHours <= ($categorySpecific ? 168 : 72);
    }
    if ($category === 'volcanoes') {
        return $ageHours <= ($categorySpecific ? 336 : 168);
    }
    return $ageHours <= 72;
}

function bot_shorten_volcano_title(string $title): string
{
    $out = preg_replace('/\s*-\s*Report for.*$/i', '', $title);
    if (!is_string($out) || trim($out) === '') {
        return $title;
    }
    return trim($out);
}

function bot_compact_time(string $raw): string
{
    $ts = strtotime($raw);
    if (!is_int($ts) || $ts <= 0) {
        return $raw;
    }
    return strtolower(gmdate('d M H:i', $ts)) . 'z';
}

function bot_quick_nav_keyboard(): array
{
    return [
        'inline_keyboard' => [
            [
                ['text' => '🟩 latest', 'callback_data' => 'nav:latest'],
                ['text' => '🟦 profile', 'callback_data' => 'nav:profile'],
                ['text' => '🟧 settings', 'callback_data' => 'nav:settings'],
            ],
        ],
    ];
}

function bot_settings_keyboard(mysqli $db, int $userId): array
{
    $pref = bot_get_user_pref($db, $userId);
    $mode = (string) ($pref['mode'] ?? 'essential');
    $focusCountry = (string) ($pref['focus_country'] ?? '');
    $cats = is_array($pref) ? json_decode((string) ($pref['categories_json'] ?? '[]'), true) : [];
    if (!is_array($cats)) {
        $cats = [];
    }

    $catBtn = static function (string $label, string $cat) use ($cats): array {
        $on = in_array($cat, $cats, true);
        return ['text' => ($on ? '● ' : '○ ') . $label, 'callback_data' => 'cat:toggle:' . $cat];
    };

    return [
        'inline_keyboard' => [
            [
                ['text' => ($mode === 'essential' ? '● ' : '○ ') . '🟩 essenziale', 'callback_data' => 'mode:essential'],
                ['text' => ($mode === 'balanced' ? '● ' : '○ ') . '🟨 bilanciato', 'callback_data' => 'mode:balanced'],
                ['text' => ($mode === 'monitor' ? '● ' : '○ ') . '🟧 monitor', 'callback_data' => 'mode:monitor'],
            ],
            [
                ['text' => ($focusCountry === 'IT' ? '● ' : '○ ') . '🌍 area italia', 'callback_data' => 'area:it'],
                ['text' => ($focusCountry !== 'IT' ? '● ' : '○ ') . '🌐 area globale', 'callback_data' => 'area:global'],
            ],
            [
                $catBtn('🟩 eq', 'earthquakes'),
                $catBtn('🟪 volc', 'volcanoes'),
            ],
            [
                $catBtn('🟦 tsu', 'tsunami'),
                $catBtn('🟨 space', 'space_weather'),
            ],
            [
                ['text' => '🟩 latest', 'callback_data' => 'nav:latest'],
                ['text' => '🟦 profile', 'callback_data' => 'nav:profile'],
            ],
        ],
    ];
}

function bot_handle_callback_action(mysqli $db, int $userId, string $data): array
{
    if ($data === '') {
        return ['', null];
    }

    if (str_starts_with($data, 'mode:')) {
        $mode = substr($data, 5);
        if (in_array($mode, ['essential', 'balanced', 'monitor'], true) && bot_set_user_mode($db, $userId, $mode)) {
            return ['ok: mode ' . $mode, bot_settings_keyboard($db, $userId)];
        }
        return ['errore mode', bot_settings_keyboard($db, $userId)];
    }

    if (str_starts_with($data, 'area:')) {
        $area = substr($data, 5);
        if (bot_set_user_area($db, $userId, $area === 'it' ? 'italia' : 'global')) {
            return ['ok: area ' . ($area === 'it' ? 'italia' : 'globale'), bot_settings_keyboard($db, $userId)];
        }
        return ['errore area', bot_settings_keyboard($db, $userId)];
    }

    if (str_starts_with($data, 'cat:toggle:')) {
        $cat = substr($data, 11);
        if (in_array($cat, ['earthquakes', 'volcanoes', 'tsunami', 'space_weather'], true)) {
            bot_toggle_category($db, $userId, $cat);
            return ['ok: categorie aggiornate', bot_settings_keyboard($db, $userId)];
        }
        return ['errore categoria', bot_settings_keyboard($db, $userId)];
    }

    if ($data === 'nav:settings') {
        $pref = bot_get_user_pref($db, $userId);
        $msg = "quakrs // settings\n"
            . 'mode: ' . (string) ($pref['mode'] ?? 'essential') . "\n"
            . 'area: ' . (((string) ($pref['focus_country'] ?? '') === 'IT') ? 'italia' : 'globale') . "\n"
            . 'eq min: m' . (string) ($pref['eq_min_magnitude'] ?? '5.5');
        return [$msg, bot_settings_keyboard($db, $userId)];
    }

    if ($data === 'nav:profile') {
        $pref = bot_get_user_pref($db, $userId);
        $cats = is_array($pref) ? json_decode((string) ($pref['categories_json'] ?? '[]'), true) : [];
        if (!is_array($cats)) {
            $cats = [];
        }
        $msg = "quakrs // profile\n"
            . 'mode: ' . (string) ($pref['mode'] ?? 'essential') . "\n"
            . 'categorie: ' . bot_render_categories_compact($cats) . "\n"
            . 'eq min: m' . (string) ($pref['eq_min_magnitude'] ?? '5.5') . "\n"
            . 'area: ' . (((string) ($pref['focus_country'] ?? '') === 'IT') ? 'italia' : 'globale');
        return [$msg, bot_settings_keyboard($db, $userId)];
    }

    if ($data === 'nav:latest') {
        return ['__ACTION_LATEST__', bot_quick_nav_keyboard()];
    }

    return ['', null];
}

function bot_build_latest_message(mysqli $db, int $userId, string $text): string
{
    $events = bot_latest_events($db, 300);
    $lines = [];
    $pref = bot_get_user_pref($db, $userId);
    $eqMinMag = is_numeric($pref['eq_min_magnitude'] ?? null) ? (float) $pref['eq_min_magnitude'] : 5.5;
    $filter = match ($text) {
        '/earthquakes' => 'earthquakes',
        '/volcanoes' => 'volcanoes',
        '/tsunami' => 'tsunami',
        '/spaceweather' => 'space_weather',
        default => '',
    };
    $caps = [
        'earthquakes' => $filter === '' ? 4 : 10,
        'volcanoes' => $filter === '' ? 2 : 8,
        'tsunami' => $filter === '' ? 2 : 8,
        'space_weather' => 1,
    ];
    $taken = [
        'earthquakes' => 0,
        'volcanoes' => 0,
        'tsunami' => 0,
        'space_weather' => 0,
    ];
    $seen = [];
    $grouped = [
        'earthquakes' => [],
        'volcanoes' => [],
        'tsunami' => [],
        'space_weather' => [],
    ];

    foreach ($events as $ev) {
        $cat = (string) ($ev['category'] ?? '');
        if ($filter !== '' && $cat !== $filter) {
            continue;
        }
        if (!isset($grouped[$cat])) {
            continue;
        }
        if (!bot_latest_is_fresh($ev, $cat, $filter !== '')) {
            continue;
        }

        if ($cat === 'earthquakes') {
            $mag = is_numeric($ev['magnitude'] ?? null) ? (float) $ev['magnitude'] : 0.0;
            if ($mag < $eqMinMag) {
                continue;
            }
        }

        $dedupeKey = ($cat === 'space_weather')
            ? 'space_weather'
            : $cat . '|' . (string) ($ev['title'] ?? '') . '|' . (string) ($ev['event_time'] ?? '');
        if (isset($seen[$dedupeKey])) {
            continue;
        }
        $seen[$dedupeKey] = true;

        if ($taken[$cat] >= ($caps[$cat] ?? 0)) {
            continue;
        }
        $taken[$cat]++;
        $grouped[$cat][] = bot_format_latest_line($ev);
    }

    if ($filter !== '') {
        $lines = $grouped[$filter] ?? [];
    } else {
        foreach (['earthquakes', 'volcanoes', 'tsunami', 'space_weather'] as $cat) {
            if (($grouped[$cat] ?? []) === []) {
                continue;
            }
            $header = match ($cat) {
                'earthquakes' => '[🟩 earthquakes]',
                'volcanoes' => '[🟪 volcanoes]',
                'tsunami' => '[🟦 tsunami]',
                'space_weather' => '[🟨 space weather]',
                default => ucfirst($cat) . ':',
            };
            $lines[] = $header;
            foreach ($grouped[$cat] as $row) {
                $lines[] = $row;
            }
            $lines[] = '';
        }
        if ($lines !== [] && end($lines) === '') {
            array_pop($lines);
        }
    }

    return $lines !== [] ? implode("\n", $lines) : 'Nessun evento rilevante disponibile.';
}

function bot_render_categories_compact(array $cats): string
{
    if ($cats === []) {
        return 'nessuna';
    }
    $map = [
        'earthquakes' => '🟩eq',
        'volcanoes' => '🟪volc',
        'tsunami' => '🟦tsu',
        'space_weather' => '🟨space',
    ];
    $out = [];
    foreach ($cats as $cat) {
        $key = (string) $cat;
        $out[] = $map[$key] ?? strtolower($key);
    }
    return implode(' ', $out);
}
