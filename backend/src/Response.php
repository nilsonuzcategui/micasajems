<?php
declare(strict_types=1);

namespace App;

final class Response
{
    public static function json($data, int $status = 200): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            if ($origin !== '') {
                header("Access-Control-Allow-Origin: {$origin}");
                header('Access-Control-Allow-Credentials: true');
                header('Vary: Origin');
            }
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function ok($data = null): void
    {
        self::json(['ok' => true, 'data' => $data]);
    }

    public static function error(string $message, int $status = 400, array $extra = []): void
    {
        self::json(array_merge(['ok' => false, 'error' => $message], $extra), $status);
    }

    public static function handleOptions(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            self::json(null, 204);
        }
    }

    public static function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}