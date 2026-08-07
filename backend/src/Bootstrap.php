<?php
declare(strict_types=1);

namespace App;

final class Bootstrap
{
    public static function init(string $rootDir): void
    {
        self::defineConstants($rootDir);
        require_once APP_SRC . '/polyfill.php';
        require_once APP_SRC . '/Config.php';
        self::ensureStorageDirectories();
        self::loadEnv($rootDir);
        self::configureSession();
        self::configureErrors();
        self::setHeaders();
        self::registerErrorHandler();
        self::autoload();
    }

    private static function defineConstants(string $rootDir): void
    {
        if (!defined('APP_ROOT')) {
            define('APP_ROOT', $rootDir);
        }
        if (!defined('APP_SRC')) {
            define('APP_SRC', APP_ROOT . '/src');
        }
        if (!defined('APP_STORAGE')) {
            define('APP_STORAGE', APP_ROOT . '/storage');
        }
        date_default_timezone_set('America/Caracas');
    }

    private static function ensureStorageDirectories(): void
    {
        foreach ([APP_STORAGE, APP_STORAGE . '/logs', APP_STORAGE . '/cache'] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
    }

    private static function loadEnv(string $rootDir): void
    {
        Config::load($rootDir . '/.env');
    }

    private static function configureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $name = Config::get('SESSION_NAME', 'jems_admin_session');
        session_name($name);
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        session_set_cookie_params([
            'lifetime' => Config::getInt('SESSION_LIFETIME', 7200),
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    private static function configureErrors(): void
    {
        $debug = Config::getBool('APP_DEBUG', false);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', APP_STORAGE . '/logs/php_errors.log');
        error_reporting(E_ALL);
    }

    private static function setHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    private static function registerErrorHandler(): void
    {
        set_exception_handler([self::class, 'handleException']);
    }

    public static function handleException(\Throwable $e): void
    {
        $debug = Config::getBool('APP_DEBUG', false);

        // Log siempre
        $logFile = APP_STORAGE . '/logs/php_errors.log';
        $message = sprintf(
            "[%s] Uncaught %s: %s\n  at %s:%d\n  trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        @file_put_contents($logFile, $message, FILE_APPEND);

        // Si headers ya se enviaron (estamos renderizando HTML), no podemos responder JSON
        if (headers_sent()) {
            if (!Config::getBool('APP_DEBUG', false)) {
                echo '<!doctype html><meta charset="utf-8"><title>Error</title><h1>Error interno del servidor</h1>';
            } else {
                echo '<pre>' . htmlspecialchars($message) . '</pre>';
            }
            exit(1);
        }

        // Si la ruta parece API, responder JSON
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $isApi = strpos($uri, '/api/') === 0;
        $isAdminApi = strpos($uri, '/api/admin/') !== false;

        if ($isApi) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            $payload = ['ok' => false, 'error' => 'Error interno del servidor'];
            if ($debug) {
                $payload['debug'] = [
                    'type' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ];
            }
            echo json_encode($payload, JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Para rutas HTML, mostrar error genérico
        http_response_code(500);
        if ($debug) {
            echo '<!doctype html><meta charset="utf-8"><title>Error</title>'
                . '<h1>Error interno</h1><pre>' . htmlspecialchars($message) . '</pre>';
        } else {
            echo '<!doctype html><meta charset="utf-8"><title>Error</title>'
                . '<h1>Error interno del servidor</h1>'
                . '<p>Revisá los logs en <code>storage/logs/php_errors.log</code></p>';
        }
        exit;
    }

    private static function autoload(): void
    {
        spl_autoload_register(static function (string $class): void {
            if (!str_starts_with($class, 'App\\')) {
                return;
            }
            $relative = substr($class, strlen('App\\'));
            $relativePath = str_replace('\\', '/', $relative);
            $file = APP_SRC . '/' . $relativePath . '.php';
            if (is_file($file)) {
                require_once $file;
                return;
            }
            $fileLower = APP_SRC . '/' . strtolower($relativePath) . '.php';
            if (is_file($fileLower)) {
                require_once $fileLower;
                return;
            }
            $fileLower2 = APP_SRC . '/' . strtolower($relative) . '.php';
            if (is_file($fileLower2)) {
                require_once $fileLower2;
                return;
            }
            $found = self::recursiveFind(APP_SRC, $relativePath . '.php');
            if ($found !== null) {
                require_once $found;
            }
        });
    }

    private static function recursiveFind(string $dir, string $filename): ?string
    {
        $items = @scandir($dir);
        if ($items === false) {
            return null;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $dir . '/' . $item;
            if (is_file($full) && strtolower($item) === strtolower($filename)) {
                return $full;
            }
            if (is_dir($full)) {
                $found = self::recursiveFind($full, $filename);
                if ($found !== null) {
                    return $found;
                }
            }
        }
        return null;
    }
}