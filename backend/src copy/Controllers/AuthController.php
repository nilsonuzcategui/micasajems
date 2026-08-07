<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\AdminUser;
use App\Request;
use App\Response;
use App\Config;

final class AuthController
{
    public function login(): void
    {
        $username = trim((string)Request::input('username', ''));
        $password = (string)Request::input('password', '');

        if ($username === '' || $password === '') {
            Response::error('Usuario y contraseña son obligatorios', 422);
        }

        $user = AdminUser::verifyPassword($username, $password);
        if (!$user) {
            Response::error('Credenciales inválidas', 401);
        }

        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = (int)$user['id'];
        $_SESSION['admin_login_at'] = time();

        Response::ok([
            'user' => $user,
            'csrf_token' => Request::csrfToken(),
        ]);
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        Response::ok(['message' => 'Sesión cerrada']);
    }

    public function me(): void
    {
        $userId = $_SESSION['admin_user_id'] ?? null;
        if (!$userId) {
            Response::error('No autenticado', 401);
        }
        $user = AdminUser::findById((int)$userId);
        if (!$user) {
            Response::error('Sesión inválida', 401);
        }
        Response::ok([
            'user' => $user,
            'csrf_token' => Request::csrfToken(),
        ]);
    }
}