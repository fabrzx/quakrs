<?php
declare(strict_types=1);

function qk_editorial_gpt_enabled(array $appConfig): bool
{
    $editorial = is_array($appConfig['editorial'] ?? null) ? $appConfig['editorial'] : [];
    $enabled = (bool) ($editorial['use_gpt'] ?? false);
    $apiKey = trim((string) ($editorial['openai_api_key'] ?? ''));
    return $enabled && $apiKey !== '';
}

function qk_editorial_gpt_compose(array $appConfig, string $promptKey, array $event, array $context, array $payloadData = []): ?array
{
    if (!qk_editorial_gpt_enabled($appConfig)) {
        return null;
    }

    $editorial = is_array($appConfig['editorial'] ?? null) ? $appConfig['editorial'] : [];
    $baseUrl = rtrim((string) ($editorial['openai_base_url'] ?? 'https://api.openai.com/v1'), '/');
    $endpoint = $baseUrl . '/responses';
    $apiKey = trim((string) ($editorial['openai_api_key'] ?? ''));
    $model = trim((string) ($editorial['openai_model'] ?? 'gpt-5.4'));
    $fallbackModel = trim((string) ($editorial['openai_fallback_model'] ?? 'gpt-4.1-mini'));
    $timeout = max(5, (int) ($editorial['openai_timeout_seconds'] ?? 20));
    $maxOutput = max(500, (int) ($editorial['openai_max_output_tokens'] ?? 1200));

    $facts = [
        'prompt_key' => $promptKey,
        'article_type' => $promptKey,
        'sensitive_location' => qk_editorial_is_sensitive_location($event),
        'event' => [
            'id' => (string) ($event['id'] ?? ''),
            'place' => (string) ($event['place'] ?? ''),
            'magnitude' => isset($event['magnitude']) && is_numeric($event['magnitude']) ? (float) $event['magnitude'] : null,
            'depth_km' => isset($event['depth_km']) && is_numeric($event['depth_km']) ? (float) $event['depth_km'] : null,
            'latitude' => isset($event['latitude']) && is_numeric($event['latitude']) ? (float) $event['latitude'] : null,
            'longitude' => isset($event['longitude']) && is_numeric($event['longitude']) ? (float) $event['longitude'] : null,
            'event_time_utc' => (string) ($event['event_time_utc'] ?? ''),
            'source_provider' => (string) ($event['source_provider'] ?? ''),
            'damage_info' => (string) ($event['damage_info'] ?? 'n.d.'),
            'casualties' => (string) ($event['casualties'] ?? 'n.d.'),
        ],
        'context' => [
            'recent_window_hours' => (int) ($context['recent_window_hours'] ?? 48),
            'nearby_count_48h' => (int) ($context['nearby_count_48h'] ?? 0),
            'window_days' => (int) ($context['window_days'] ?? 7),
            'nearby_count_7d' => (int) ($context['nearby_count_7d'] ?? 0),
            'nearby_max_magnitude' => isset($context['nearby_max_magnitude']) && is_numeric($context['nearby_max_magnitude']) ? (float) $context['nearby_max_magnitude'] : null,
        ],
        'payload_data' => $payloadData,
    ];

    $promptSpec = qk_editorial_prompt_spec($promptKey, $facts);
    $systemPrompt = $promptSpec['system'];
    $userPrompt = $promptSpec['user'];
    if (!is_string($systemPrompt) || !is_string($userPrompt) || trim($userPrompt) === '') {
        return null;
    }

    $input = [
        [
            'role' => 'system',
            'content' => [
                ['type' => 'input_text', 'text' => $systemPrompt],
            ],
        ],
        [
            'role' => 'user',
            'content' => [
                ['type' => 'input_text', 'text' => $userPrompt],
            ],
        ],
    ];
    $payload = qk_editorial_build_responses_payload($model, $maxOutput, $input);

    $raw = qk_editorial_http_post_json($endpoint, $payload, $apiKey, $timeout);
    if (!is_array($raw)) {
        $retryTimeout = min(120, $timeout + 30);
        $raw = qk_editorial_http_post_json($endpoint, $payload, $apiKey, $retryTimeout);
    }
    if (!is_array($raw)) {
        return null;
    }

    $outputText = qk_editorial_extract_response_text($raw);
    if ((!is_string($outputText) || trim($outputText) === '') && $fallbackModel !== '' && strcasecmp($fallbackModel, $model) !== 0) {
        $fallbackPayload = qk_editorial_build_responses_payload($fallbackModel, $maxOutput, $input);
        $rawFallback = qk_editorial_http_post_json($endpoint, $fallbackPayload, $apiKey, $timeout);
        if (is_array($rawFallback)) {
            $outputText = qk_editorial_extract_response_text($rawFallback);
        }
    }
    if (!is_string($outputText) || trim($outputText) === '') {
        return null;
    }

    $jsonBlock = qk_editorial_extract_json_block($outputText);
    if ($jsonBlock === null) {
        return null;
    }

    $decoded = json_decode($jsonBlock, true);
    if (!is_array($decoded)) {
        return null;
    }

    $validated = qk_editorial_validate_gpt_copy($decoded, $facts);
    return $validated;
}

function qk_editorial_prompt_spec(string $promptKey, array $facts): array
{
    $templates = qk_editorial_load_prompt_templates();
    $key = trim($promptKey);
    if ($key === 'latest') {
        $key = 'event_live';
    } elseif ($key === 'historical') {
        $key = 'event_historical';
    }
    $template = trim((string) ($templates[$key] ?? ''));
    if ($template === '') {
        return ['system' => '', 'user' => ''];
    }

    $event = is_array($facts['event'] ?? null) ? $facts['event'] : [];
    $context = is_array($facts['context'] ?? null) ? $facts['context'] : [];
    $payload = is_array($facts['payload_data'] ?? null) ? $facts['payload_data'] : [];

    $eventSummary = [
        'id' => (string) ($event['id'] ?? ''),
        'place' => (string) ($event['place'] ?? ''),
        'magnitude' => isset($event['magnitude']) && is_numeric($event['magnitude']) ? (float) $event['magnitude'] : null,
        'depth_km' => isset($event['depth_km']) && is_numeric($event['depth_km']) ? (float) $event['depth_km'] : null,
        'latitude' => isset($event['latitude']) && is_numeric($event['latitude']) ? (float) $event['latitude'] : null,
        'longitude' => isset($event['longitude']) && is_numeric($event['longitude']) ? (float) $event['longitude'] : null,
        'event_time_utc' => (string) ($event['event_time_utc'] ?? ''),
        'source_provider' => (string) ($event['source_provider'] ?? ''),
        'damage_info' => (string) ($event['damage_info'] ?? ''),
        'casualties' => (string) ($event['casualties'] ?? ''),
    ];

    $eventData = is_array($payload['event_data'] ?? null) ? $payload['event_data'] : $eventSummary;
    $clusterData = is_array($payload['cluster_data'] ?? null) ? $payload['cluster_data'] : [];
    $areaData = is_array($payload['area_data'] ?? null) ? $payload['area_data'] : [];
    $italyData = is_array($payload['italy_data'] ?? null) ? $payload['italy_data'] : [];
    $sequenceData = is_array($payload['sequence_data'] ?? null) ? $payload['sequence_data'] : [];
    $event1Data = is_array($payload['event1'] ?? null) ? $payload['event1'] : [];
    $event2Data = is_array($payload['event2'] ?? null) ? $payload['event2'] : [];
    $relatedEvents = is_array($payload['related_events'] ?? null) ? $payload['related_events'] : [];
    $sequenceInfo = is_array($payload['sequence_info'] ?? null) ? $payload['sequence_info'] : [];

    $replacements = [
        '{magnitude}' => qk_editorial_prompt_value(isset($event['magnitude']) ? (string) $event['magnitude'] : ''),
        '{place}' => qk_editorial_prompt_value((string) ($event['place'] ?? '')),
        '{lat}' => qk_editorial_prompt_value(isset($event['latitude']) ? (string) $event['latitude'] : ''),
        '{lon}' => qk_editorial_prompt_value(isset($event['longitude']) ? (string) $event['longitude'] : ''),
        '{depth}' => qk_editorial_prompt_value(isset($event['depth_km']) ? (string) $event['depth_km'] : ''),
        '{datetime}' => qk_editorial_prompt_value((string) ($event['event_time_utc'] ?? '')),
        '{source}' => qk_editorial_prompt_value((string) ($event['source_provider'] ?? '')),
        '{damage_info}' => qk_editorial_prompt_value((string) ($event['damage_info'] ?? 'n.d.')),
        '{recent_events}' => qk_editorial_prompt_value((string) ($context['nearby_count_48h'] ?? 0)),
        '{weekly_context}' => qk_editorial_prompt_value((string) ($context['nearby_count_7d'] ?? 0)),
        '{event_data}' => qk_editorial_prompt_json($eventData),
        '{sequence_info}' => qk_editorial_prompt_json([
            'nearby_count_48h' => (int) ($context['nearby_count_48h'] ?? 0),
            'nearby_count_7d' => (int) ($context['nearby_count_7d'] ?? 0),
            'nearby_max_magnitude' => isset($context['nearby_max_magnitude']) && is_numeric($context['nearby_max_magnitude']) ? (float) $context['nearby_max_magnitude'] : null,
        ]),
        '{related_events}' => qk_editorial_prompt_json($relatedEvents !== [] ? $relatedEvents : [
            'recent_window_hours' => (int) ($context['recent_window_hours'] ?? 48),
            'window_days' => (int) ($context['window_days'] ?? 7),
            'nearby_count_48h' => (int) ($context['nearby_count_48h'] ?? 0),
            'nearby_count_7d' => (int) ($context['nearby_count_7d'] ?? 0),
            'nearby_max_magnitude' => isset($context['nearby_max_magnitude']) && is_numeric($context['nearby_max_magnitude']) ? (float) $context['nearby_max_magnitude'] : null,
        ]),
        '{cluster_data}' => qk_editorial_prompt_json($clusterData),
        '{area_data}' => qk_editorial_prompt_json($areaData),
        '{italy_data}' => qk_editorial_prompt_json($italyData),
        '{sequence_data}' => qk_editorial_prompt_json($sequenceData),
        '{event1}' => qk_editorial_prompt_json($event1Data),
        '{event2}' => qk_editorial_prompt_json($event2Data),
    ];
    if ($sequenceInfo !== []) {
        $replacements['{sequence_info}'] = qk_editorial_prompt_json($sequenceInfo);
    }
    $filledTemplate = strtr($template, $replacements);

    $system = 'Usa sempre il prompt base fornito dall utente come fonte di stile. '
        . 'Non inventare dati e non aggiungere campi extra.';
    $user = $filledTemplate . "\n\n"
        . 'Restituisci SOLO JSON valido con questa forma esatta: '
        . '{"title":"...","excerpt":"...","sections":[{"heading":"...","body":"..."}],"glossary":[{"term":"...","definition":"..."}]}. '
        . 'Vincoli: nessun markdown, nessun testo fuori dal JSON, 6-8 sezioni coerenti con il prompt base, '
        . 'glossary con 4-8 termini.';

    return ['system' => $system, 'user' => $user];
}

function qk_editorial_load_prompt_templates(): array
{
    foreach (qk_editorial_prompt_templates_paths() as $path) {
        if (!is_file($path)) {
            continue;
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            continue;
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return [];
}

function qk_editorial_prompt_templates_paths(): array
{
    return [
        dirname(__DIR__) . '/prompts/quakrs_prompts.json',
        dirname(__DIR__, 2) . '/prompts/quakrs_prompts.json',
    ];
}

function qk_editorial_prompt_json(array $data): string
{
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return is_string($json) && $json !== '' ? $json : '{}';
}

function qk_editorial_prompt_value(string $value): string
{
    $trimmed = trim($value);
    return $trimmed !== '' ? $trimmed : 'n.d.';
}

function qk_editorial_build_responses_payload(string $model, int $maxOutput, array $input): array
{
    $schema = [
        'type' => 'object',
        'additionalProperties' => false,
        'required' => ['title', 'excerpt', 'sections', 'glossary'],
        'properties' => [
            'title' => ['type' => 'string'],
            'excerpt' => ['type' => 'string'],
            'sections' => [
                'type' => 'array',
                'minItems' => 6,
                'maxItems' => 8,
                'items' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['heading', 'body'],
                    'properties' => [
                        'heading' => ['type' => 'string'],
                        'body' => ['type' => 'string'],
                    ],
                ],
            ],
            'glossary' => [
                'type' => 'array',
                'minItems' => 4,
                'maxItems' => 8,
                'items' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['term', 'definition'],
                    'properties' => [
                        'term' => ['type' => 'string'],
                        'definition' => ['type' => 'string'],
                    ],
                ],
            ],
        ],
    ];

    $payload = [
        'model' => $model,
        'max_output_tokens' => $maxOutput,
        'input' => $input,
        'text' => [
            'format' => [
                'type' => 'json_schema',
                'name' => 'editorial_article',
                'strict' => true,
                'schema' => $schema,
            ],
        ],
    ];

    if (stripos($model, 'gpt-5') === 0) {
        $payload['reasoning'] = ['effort' => 'low'];
    }

    return $payload;
}

function qk_editorial_http_post_json(string $url, array $payload, string $apiKey, int $timeoutSeconds): ?array
{
    if (!function_exists('curl_init')) {
        return null;
    }

    $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!is_string($body)) {
        return null;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
        CURLOPT_TIMEOUT => $timeoutSeconds,
    ]);

    $response = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hasError = curl_errno($ch) !== 0;

    if ($hasError || !is_string($response) || $response === '' || $code < 200 || $code >= 300) {
        return null;
    }

    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : null;
}

function qk_editorial_extract_response_text(array $response): ?string
{
    if (isset($response['output_text']) && is_string($response['output_text'])) {
        return $response['output_text'];
    }

    $parts = [];
    $output = $response['output'] ?? null;
    if (is_array($output)) {
        foreach ($output as $item) {
            if (!is_array($item)) {
                continue;
            }
            $content = $item['content'] ?? null;
            if (!is_array($content)) {
                continue;
            }
            foreach ($content as $block) {
                if (!is_array($block)) {
                    continue;
                }
                if (isset($block['text']) && is_string($block['text'])) {
                    $parts[] = $block['text'];
                    continue;
                }
                if (isset($block['text']) && is_array($block['text']) && is_string($block['text']['value'] ?? null)) {
                    $parts[] = (string) $block['text']['value'];
                }
            }
        }
    }

    if ($parts === []) {
        return null;
    }

    return trim(implode("\n", $parts));
}

function qk_editorial_extract_json_block(string $text): ?string
{
    $start = strpos($text, '{');
    $end = strrpos($text, '}');
    if ($start === false || $end === false || $end <= $start) {
        return null;
    }
    return substr($text, $start, $end - $start + 1);
}

function qk_editorial_validate_gpt_copy(array $candidate, array $facts): ?array
{
    $title = qk_editorial_sanitize_gpt_text((string) ($candidate['title'] ?? ''));
    $excerpt = qk_editorial_sanitize_gpt_text((string) ($candidate['excerpt'] ?? ''));
    $sections = $candidate['sections'] ?? null;

    if ($title === '' || $excerpt === '' || !is_array($sections) || count($sections) < 6) {
        return null;
    }

    $cleanSections = [];
    foreach (array_slice($sections, 0, 8) as $section) {
        if (!is_array($section)) {
            return null;
        }
        $heading = qk_editorial_sanitize_gpt_text((string) ($section['heading'] ?? ''));
        $body = qk_editorial_sanitize_gpt_text((string) ($section['body'] ?? ''));
        if ($heading === '' || $body === '') {
            return null;
        }
        $cleanSections[] = [
            'heading' => $heading,
            'body' => $body,
        ];
    }

    if (count($cleanSections) < 6) {
        return null;
    }

    $glossaryRaw = is_array($candidate['glossary'] ?? null) ? $candidate['glossary'] : [];
    $glossary = [];
    foreach ($glossaryRaw as $item) {
        if (!is_array($item)) {
            continue;
        }
        $term = qk_editorial_sanitize_gpt_text((string) ($item['term'] ?? ''));
        $definition = qk_editorial_sanitize_gpt_text((string) ($item['definition'] ?? ''));
        if ($term === '' || $definition === '') {
            continue;
        }
        if (mb_strlen($term) > 48 || mb_strlen($definition) > 220) {
            continue;
        }
        $glossary[] = [
            'term' => $term,
            'definition' => $definition,
        ];
        if (count($glossary) >= 8) {
            break;
        }
    }

    return [
        'title' => $title,
        'excerpt' => $excerpt,
        'sections' => $cleanSections,
        'glossary' => $glossary,
    ];
}

function qk_editorial_sanitize_gpt_text(string $text): string
{
    $clean = trim($text);
    if ($clean === '') {
        return '';
    }

    $replacements = [
        'automatico' => 'editoriale',
        'automatica' => 'editoriale',
        'automatici' => 'editoriali',
        'automatiche' => 'editoriali',
        'auto-check' => 'verifica',
        'pipeline' => 'flusso',
        'nei feed' => 'nei dati disponibili',
        'generated by ai' => 'elaborato editorialmente',
        'ai-generated' => 'elaborato editorialmente',
    ];

    foreach ($replacements as $from => $to) {
        $clean = str_ireplace($from, $to, $clean);
    }

    return trim($clean);
}
