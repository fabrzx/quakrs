<?php
declare(strict_types=1);

function bot_category_score(string $category, array $event): int
{
    return match ($category) {
        'earthquakes' => bot_score_earthquake($event),
        'volcanoes' => bot_score_volcano($event),
        'tsunami' => bot_score_tsunami($event),
        'space_weather' => bot_score_space_weather($event),
        default => 0,
    };
}

function bot_decision_from_score(int $score, string $mode): string
{
    $digestThreshold = 40;
    $alertThreshold = 70;

    if ($mode === 'essential') {
        $digestThreshold = 45;
        $alertThreshold = 78;
    } elseif ($mode === 'monitor') {
        $digestThreshold = 35;
        $alertThreshold = 62;
    }

    if ($score >= $alertThreshold) {
        return 'alert';
    }
    if ($score >= $digestThreshold) {
        return 'digest';
    }
    return 'ignore';
}

function bot_score_earthquake(array $event): int
{
    $mag = is_numeric($event['magnitude'] ?? null) ? (float) $event['magnitude'] : 0.0;
    $depth = is_numeric($event['depth_km'] ?? null) ? (float) $event['depth_km'] : null;
    $place = strtolower((string) ($event['title'] ?? ''));
    $isItaly = bot_text_has_any($place, ['italy', 'italia', 'sicily', 'sicilia', 'calabria', 'naples', 'roma', 'rome', 'abruzzo', 'lazio', 'campania']);
    $nearUrban = bot_text_has_any($place, ['near', 'km', 'city', 'region', 'of']);
    $shallow = $depth !== null && $depth <= 20.0;

    $score = 0;
    $score += (int) min(35, max(0, ($mag - 2.0) * 8));

    if ($depth !== null) {
        if ($depth <= 15.0) {
            $score += 10;
        } elseif ($depth <= 40.0) {
            $score += 6;
        } elseif ($depth <= 90.0) {
            $score += 3;
        }
    }

    if ($isItaly) {
        $score += 8;
    }

    if ($nearUrban && $mag >= 4.6) {
        $score += 8;
    }

    // Hard relevance boosts aligned with Quakrs policy.
    if ($mag >= 6.0) {
        $score += 20;
    } elseif ($mag >= 5.5 && ($shallow || $nearUrban)) {
        $score += 20;
    } elseif ($isItaly && $mag >= 3.5) {
        $score += 24;
    } elseif ($isItaly && $mag >= 3.0 && $shallow && $nearUrban) {
        $score += 20;
    } elseif ($mag >= 5.5) {
        $score += 12;
    } elseif ($mag >= 5.0) {
        $score += 7;
    }

    return max(0, min(100, $score));
}

function bot_score_volcano(array $event): int
{
    $title = strtolower((string) ($event['title'] ?? ''));
    $summary = strtolower((string) ($event['summary'] ?? ''));

    $score = 18;

    if (!empty($event['is_new_eruptive'])) {
        $score += 35;
    }
    if (str_contains($title, 'new unrest') || str_contains($title, 'new eruptive')) {
        $score += 20;
    }
    if (str_contains($title, 'continuing eruptive')) {
        $score += 12;
    }
    if (preg_match('/\b(alert level|aviation color code|advisory|watch|orange|red)\b/i', $summary)) {
        $score += 18;
    }

    return max(0, min(100, $score));
}

function bot_score_tsunami(array $event): int
{
    $title = strtolower((string) ($event['title'] ?? ''));
    $summary = strtolower((string) ($event['summary'] ?? ''));
    $severity = strtolower((string) ($event['severity_label'] ?? ''));

    $score = 10;

    if (preg_match('/warning/', $severity . ' ' . $title . ' ' . $summary)) {
        $score += 55;
    } elseif (preg_match('/advisory|watch/', $severity . ' ' . $title . ' ' . $summary)) {
        $score += 40;
    }

    if (preg_match('/confirmed|observed|gauge|wave/', $summary)) {
        $score += 15;
    }

    return max(0, min(100, $score));
}

function bot_score_space_weather(array $event): int
{
    $kp = is_numeric($event['kp_index_current'] ?? null) ? (float) $event['kp_index_current'] : 0.0;
    $kpMax = is_numeric($event['kp_index_max_24h'] ?? null) ? (float) $event['kp_index_max_24h'] : 0.0;
    $xrayClass = strtoupper((string) ($event['xray_class_peak_24h'] ?? ''));

    $score = 5;

    if ($kpMax >= 6.0 || $kp >= 6.0) {
        $score += 50;
    } elseif ($kpMax >= 5.0) {
        $score += 35;
    } elseif ($kpMax >= 4.0) {
        $score += 20;
    }

    if (preg_match('/^X[0-9]/', $xrayClass)) {
        $score += 45;
    } elseif (preg_match('/^M[0-9]/', $xrayClass)) {
        $score += 28;
    } elseif (preg_match('/^C[0-9]/', $xrayClass)) {
        $score += 8;
    }

    return max(0, min(100, $score));
}

function bot_text_has_any(string $haystack, array $needles): bool
{
    foreach ($needles as $needle) {
        if ($needle !== '' && str_contains($haystack, $needle)) {
            return true;
        }
    }
    return false;
}
