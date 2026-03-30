<?php
declare(strict_types=1);

function bot_telegram_call(array $botConfig, string $method, array $payload): array
{
    $token = (string) ($botConfig['token'] ?? '');
    if ($token === '') {
        return ['ok' => false, 'error' => 'Bot token missing'];
    }

    $base = rtrim((string) ($botConfig['api_base'] ?? 'https://api.telegram.org'), '/');
    $url = $base . '/bot' . $token . '/' . $method;

    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'curl unavailable'];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
    ]);

    $resp = curl_exec($ch);
    $errno = curl_errno($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno !== 0 || !is_string($resp)) {
        return ['ok' => false, 'error' => 'telegram request failed'];
    }

    $decoded = json_decode($resp, true);
    if (!is_array($decoded)) {
        return ['ok' => false, 'error' => 'invalid telegram response', 'http' => $http];
    }

    return $decoded;
}

function bot_send_message(array $botConfig, int|string $chatId, string $text): array
{
    return bot_send_message_with_markup($botConfig, $chatId, $text, null);
}

function bot_send_message_with_markup(array $botConfig, int|string $chatId, string $text, ?array $replyMarkup): array
{
    $payload = [
        'chat_id' => $chatId,
        'text' => $text,
        'disable_web_page_preview' => true,
    ];
    if (is_array($replyMarkup) && $replyMarkup !== []) {
        $payload['reply_markup'] = $replyMarkup;
    }
    return bot_telegram_call($botConfig, 'sendMessage', $payload);
}

function bot_answer_callback_query(array $botConfig, string $callbackQueryId, string $text = ''): array
{
    $payload = [
        'callback_query_id' => $callbackQueryId,
    ];
    if ($text !== '') {
        $payload['text'] = $text;
        $payload['show_alert'] = false;
    }
    return bot_telegram_call($botConfig, 'answerCallbackQuery', $payload);
}
