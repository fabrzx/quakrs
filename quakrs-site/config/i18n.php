<?php
declare(strict_types=1);

if (!function_exists('qk_supported_locales')) {
    function qk_supported_locales(): array
    {
        return [
            'en' => 'EN',
            'it' => 'IT',
        ];
    }
}

if (!function_exists('qk_boot_i18n')) {
    function qk_boot_i18n(): void
    {
        static $booted = false;
        if ($booted) {
            return;
        }
        $booted = true;

        $supported = qk_supported_locales();
        $defaultLocale = 'en';

        $queryLocale = isset($_GET['lang']) ? strtolower(trim((string) $_GET['lang'])) : '';
        $cookieLocale = isset($_COOKIE['qk_lang']) ? strtolower(trim((string) $_COOKIE['qk_lang'])) : '';

        $selectedLocale = $defaultLocale;
        if (isset($supported[$queryLocale])) {
            $selectedLocale = $queryLocale;
        } elseif (isset($supported[$cookieLocale])) {
            $selectedLocale = $cookieLocale;
        }

        if ($queryLocale !== '' && isset($supported[$queryLocale])) {
            $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            setcookie('qk_lang', $queryLocale, [
                'expires' => time() + 31536000,
                'path' => '/',
                'secure' => $isHttps,
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
            $_COOKIE['qk_lang'] = $queryLocale;
        }

        $langDir = dirname(__DIR__) . '/lang';
        $english = require $langDir . '/en.php';
        $localePath = $langDir . '/' . $selectedLocale . '.php';
        $localized = is_file($localePath) ? require $localePath : [];

        if (!is_array($english)) {
            $english = [];
        }
        if (!is_array($localized)) {
            $localized = [];
        }

        $GLOBALS['qk_locale'] = $selectedLocale;
        $GLOBALS['qk_translations'] = array_merge($english, $localized);
    }
}

if (!function_exists('qk_locale')) {
    function qk_locale(): string
    {
        qk_boot_i18n();
        $locale = $GLOBALS['qk_locale'] ?? 'en';
        return is_string($locale) ? $locale : 'en';
    }
}

if (!function_exists('qk_t')) {
    function qk_t(string $key, ?string $fallback = null): string
    {
        qk_boot_i18n();
        $translations = $GLOBALS['qk_translations'] ?? [];
        if (is_array($translations) && isset($translations[$key]) && is_string($translations[$key])) {
            return $translations[$key];
        }
        return $fallback ?? $key;
    }
}

if (!function_exists('qk_locale_switch_url')) {
    function qk_locale_switch_url(string $locale): string
    {
        $supported = qk_supported_locales();
        if (!isset($supported[$locale])) {
            $locale = 'en';
        }

        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        if ($requestUri === '') {
            $requestUri = '/';
        }

        $path = (string) parse_url($requestUri, PHP_URL_PATH);
        if ($path === '') {
            $path = '/';
        }

        $query = [];
        parse_str((string) parse_url($requestUri, PHP_URL_QUERY), $query);
        if (!is_array($query)) {
            $query = [];
        }
        $query['lang'] = $locale;

        $queryString = http_build_query($query);
        return $path . ($queryString !== '' ? '?' . $queryString : '');
    }
}

if (!function_exists('qk_localized_url')) {
    function qk_localized_url(string $url, ?string $locale = null): string
    {
        $targetLocale = $locale ?? qk_locale();
        $supported = qk_supported_locales();
        if (!isset($supported[$targetLocale])) {
            $targetLocale = 'en';
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        if ($path === '') {
            $path = '/';
        }

        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        if (!is_array($query)) {
            $query = [];
        }

        if ($targetLocale === 'en') {
            unset($query['lang']);
        } else {
            $query['lang'] = $targetLocale;
        }

        $queryString = http_build_query($query);
        return $path . ($queryString !== '' ? '?' . $queryString : '');
    }
}
