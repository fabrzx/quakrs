<?php
declare(strict_types=1);

function bot_format_event_message(array $event, string $baseUrl): string
{
    $category = (string) ($event['category'] ?? '');
    return match ($category) {
        'earthquakes' => bot_format_earthquake_message($event, $baseUrl),
        'volcanoes' => bot_format_volcano_message($event, $baseUrl),
        'tsunami' => bot_format_tsunami_message($event, $baseUrl),
        'space_weather' => bot_format_space_weather_message($event, $baseUrl),
        default => "quakrs // alert\n" . ($event['title'] ?? 'event update'),
    };
}

function bot_format_earthquake_message(array $event, string $baseUrl): string
{
    $magVal = isset($event['magnitude']) && is_numeric($event['magnitude']) ? (float) $event['magnitude'] : 0.0;
    $mag = isset($event['magnitude']) ? number_format($magVal, 1) : 'n/a';
    $magBadge = bot_eq_mag_badge($magVal);
    $depth = isset($event['depth_km']) ? number_format((float) $event['depth_km'], 1) : 'n/a';
    $time = (string) ($event['event_time'] ?? '');
    $region = (string) ($event['title'] ?? 'Unknown region');
    $score = (int) ($event['score'] ?? 0);

    return "quakrs // earthquake\n"
        . "mag    {$magBadge} M{$mag}\n"
        . "area   {$region}\n"
        . "depth  {$depth} km\n"
        . "time   {$time}\n"
        . "score  {$score}/100\n"
        . "link   {$baseUrl}/earthquakes.php";
}

function bot_format_volcano_message(array $event, string $baseUrl): string
{
    $title = (string) ($event['title'] ?? 'Volcano update');
    $time = (string) ($event['event_time'] ?? '');
    return "quakrs // volcano\n"
        . "event  {$title}\n"
        . "time   {$time}\n"
        . "link   {$baseUrl}/volcanoes.php";
}

function bot_format_tsunami_message(array $event, string $baseUrl): string
{
    $title = (string) ($event['title'] ?? 'Tsunami update');
    $severity = (string) ($event['severity_label'] ?? 'update');
    $time = (string) ($event['event_time'] ?? '');
    return "quakrs // tsunami\n"
        . "event  {$title}\n"
        . "level  {$severity}\n"
        . "time   {$time}\n"
        . "link   {$baseUrl}/tsunami.php";
}

function bot_format_space_weather_message(array $event, string $baseUrl): string
{
    $title = (string) ($event['title'] ?? 'Space weather update');
    $summary = (string) ($event['summary'] ?? '');
    $time = (string) ($event['event_time'] ?? '');
    return "quakrs // space weather\n"
        . "event  {$title}\n"
        . "data   {$summary}\n"
        . "time   {$time}\n"
        . "link   {$baseUrl}/space-weather.php";
}

function bot_format_daily_digest(array $sections, string $baseUrl, string $dateLabel): string
{
    $eq = $sections['earthquakes'] ?? 'No major updates.';
    $volc = $sections['volcanoes'] ?? 'No major updates.';
    $tsu = $sections['tsunami'] ?? 'No major updates.';
    $sw = $sections['space_weather'] ?? 'No major updates.';

    return "quakrs // daily brief {$dateLabel}\n\n"
        . "[earthquakes]\n{$eq}\n\n"
        . "[volcanoes]\n{$volc}\n\n"
        . "[tsunami]\n{$tsu}\n\n"
        . "[space weather]\n{$sw}\n\n"
        . "link {$baseUrl}";
}

function bot_eq_mag_badge(float $mag): string
{
    if ($mag >= 7.0) {
        return '🟪';
    }
    if ($mag >= 6.0) {
        return '🟥';
    }
    if ($mag >= 5.0) {
        return '🟧';
    }
    if ($mag >= 3.0) {
        return '🟨';
    }
    if ($mag >= 2.0) {
        return '🟦';
    }
    return '⬜';
}
