<?php
declare(strict_types=1);

use App\Bootstrap;
use App\Router;
use App\Routes;

require_once __DIR__ . '/src/Bootstrap.php';
Bootstrap::init(__DIR__);

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$uri = $_SERVER['REQUEST_URI'] ?? '/';
if ($basePath !== '' && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}
if ($uri === '' || $uri === false) {
    $uri = '/';
}

$path = parse_url($uri, PHP_URL_PATH) ?: '/';

// =====================================================
// Servir archivos estáticos directamente (CSS, JS, imágenes)
// En producción esto también lo hace Apache vía .htaccess,
// pero tener el fallback en PHP garantiza que funcione igual
// si el .htaccess no está bien configurado.
// =====================================================
if ($path !== '/' && PHP_SAPI !== 'cli') {
    $filePath = __DIR__ . $path;
    if (is_file($filePath)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $allowed = [
            'css'  => 'text/css; charset=utf-8',
            'js'   => 'application/javascript; charset=utf-8',
            'mjs'  => 'application/javascript; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2'=> 'font/woff2',
            'ttf'  => 'font/ttf',
            'eot'  => 'application/vnd.ms-fontobject',
            'txt'  => 'text/plain; charset=utf-8',
            'map'  => 'application/json; charset=utf-8',
        ];
        if (isset($allowed[$ext])) {
            header('Content-Type: ' . $allowed[$ext]);
            header('Cache-Control: public, max-age=3600');
            readfile($filePath);
            exit;
        }
    }
}

$router = new Router();
Routes::register($router);
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $uri);