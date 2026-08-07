<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Models\AdminUser;
use App\Response;

final class AuthMiddleware
{
    public static function require(): array
    {
        $userId = $_SESSION['admin_user_id'] ?? null;
        if (!$userId) {
            Response::error('No autenticado', 401);
        }
        $user = AdminUser::findById((int)$userId);
        if (!$user) {
            unset($_SESSION['admin_user_id']);
            Response::error('Sesión inválida', 401);
        }
        return $user;
    }

    public static function requireCsrf(): void
    {
        if (!\App\Request::validateCsrf()) {
            Response::error('Token CSRF inválido', 419);
        }
    }

    public static function user(): ?array
    {
        $userId = $_SESSION['admin_user_id'] ?? null;
        if (!$userId) {
            return null;
        }
        return AdminUser::findById((int)$userId);
    }
}