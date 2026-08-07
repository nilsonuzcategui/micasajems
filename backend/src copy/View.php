<?php
declare(strict_types=1);

namespace App;

use App\Middleware\AuthMiddleware;

final class View
{
    public static function adminUrl(string $path = ''): string
    {
        $base = (string)Config::get('ADMIN_URL', '/admin');
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    public static function renderAdmin(string $view, array $data = []): void
    {
        $data['flash_error'] = $_GET['error'] ?? null;
        $data['flash_ok'] = $_GET['ok'] ?? null;
        $data['flash_deleted'] = $_GET['deleted'] ?? null;
        $data['current_user'] = AuthMiddleware::user();
        $data['csrf_token'] = Request::csrfToken();
        $data['app_name'] = 'JEMS Admin';

        if ($view === 'redirect') {
            if (AuthMiddleware::user()) {
                Response::redirect(self::adminUrl('dashboard'));
            }
            Response::redirect(self::adminUrl('login'));
        }

        $file = APP_SRC . '/Views/admin/' . $view . '.php';
        if (!is_file($file)) {
            Response::error('Vista no encontrada', 500);
        }
        $data['view_file'] = $file;
        $data['title'] = $data['title'] ?? ucfirst(str_replace('_', ' ', $view));
        extract($data, EXTR_SKIP);
        require APP_SRC . '/Views/layouts/header.php';
        require $file;
        require APP_SRC . '/Views/layouts/footer.php';
        exit;
    }
}