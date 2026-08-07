<?php
declare(strict_types=1);

namespace App;

final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function input(string $key, $default = null)
    {
        $source = self::method() === 'GET' ? $_GET : $_POST;
        if (isset($source[$key])) {
            return $source[$key];
        }
        $json = self::jsonBody();
        return $json[$key] ?? $default;
    }

    public static function all(): array
    {
        $json = self::jsonBody();
        if (self::method() === 'GET') {
            return array_merge($_GET, $json);
        }
        return array_merge($_POST, $json);
    }

    public static function jsonBody(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || $raw === '') {
            return $cache = [];
        }
        $decoded = json_decode($raw, true);
        return $cache = is_array($decoded) ? $decoded : [];
    }

    public static function query(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrf(): bool
    {
        $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $body = self::input('csrf_token');
        $token = $header ?: $body;
        return is_string($token)
            && !empty($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}