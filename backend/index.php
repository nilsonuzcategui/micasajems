<?php
declare(strict_types=1);

use App\Bootstrap;
use App\Router;
use App\Routes;

require_once __DIR__ . '/src/Bootstrap.php';
Bootstrap::init(__DIR__);

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
// Strip el directorio del script (ej: /admin) del URI
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$uri = $_SERVER['REQUEST_URI'] ?? '/';
if ($basePath !== '' && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}
if ($uri === '' || $uri === false) {
    $uri = '/';
}

$router = new Router();
Routes::register($router);
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $uri);