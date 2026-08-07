<?php
declare(strict_types=1);

namespace App;

final class Response
{
    /**
     * Lista de orígenes permitidos para CORS.
     * En producción se bloquea a orígenes conocidos; en dev se permite cualquiera.
     */
    private static function corsAllowedOrigins(): array
    {
        $appUrl = (string)Config::get('APP_URL', '');
        $frontendUrl = (string)Config::get('FRONTEND_URL', '');
        $configured = (string)Config::get('CORS_ALLOWED_ORIGINS', '');
        $origins = [];
        foreach ([$appUrl, $frontendUrl] as $u) {
            if ($u === '') {
                continue;
            }
            $origins[] = $u;
            $origins[] = str_replace('https://', 'https://www.', $u);
            $origins[] = str_replace('https://www.', 'https://', $u);
            $origins[] = str_replace('http://', 'http://www.', $u);
        }
        if ($configured !== '') {
            foreach (explode(',', $configured) as $o) {
                $origins[] = trim($o);
            }
        }
        // Fallback dev: permitir localhost
        $origins[] = 'http://localhost:4321';
        $origins[] = 'http://127.0.0.1:4321';
        return array_values(array_unique(array_filter($origins)));
    }

    public static function json($data, int $status = 200): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');

            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            $allowed = self::corsAllowedOrigins();
            $isAllowed = in_array($origin, $allowed, true);

            // En producción refleja solo orígenes autorizados; en dev permite cualquiera
            $isDev = Config::get('APP_ENV') === 'development';
            if ($origin !== '' && ($isAllowed || $isDev)) {
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