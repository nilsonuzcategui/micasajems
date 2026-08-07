<?php
declare(strict_types=1);

namespace App;

use App\Controllers\ActividadController;
use App\Controllers\AuthController;
use App\Controllers\SuscripcionController;
use App\Middleware\AuthMiddleware;

final class Routes
{
    public static function register(Router $router): void
    {
        $auth = new AuthController();
        $act = new ActividadController();
        $sub = new SuscripcionController();

        // ============ API PÚBLICA ============
        $router->get('/api/actividades', [$act, 'index']);
        $router->get('/api/actividades/{id}', fn($p) => $act->show((int)$p['id']));
        $router->post('/api/suscripciones', [$sub, 'log']);

        // ============ AUTH ============
        $router->post('/api/admin/auth', [$auth, 'login']);
        $router->post('/api/admin/auth/logout', [$auth, 'logout'], [fn() => AuthMiddleware::require()]);
        $router->get('/api/admin/auth/me', [$auth, 'me'], [fn() => AuthMiddleware::require()]);

        // ============ CRUD ADMIN (protegido) ============
        $mw = [fn() => AuthMiddleware::require(), [AuthMiddleware::class, 'requireCsrf']];
        $router->post('/api/admin/actividades', [$act, 'store'], $mw);
        $router->put('/api/admin/actividades/{id}', fn($p) => $act->update((int)$p['id']), $mw);
        $router->delete('/api/admin/actividades/{id}', fn($p) => $act->destroy((int)$p['id']), $mw);

        // ============ VISTAS HTML ADMIN ============
        $router->get('/admin', fn() => View::renderAdmin('redirect'));
        $router->get('/admin/', fn() => View::renderAdmin('redirect'));
        $router->get('/admin/login', fn() => View::renderAdmin('login'));
        $router->post('/admin/login', function (): void {
            $username = trim((string)Request::input('username', ''));
            $password = (string)Request::input('password', '');
            $user = \App\Models\AdminUser::verifyPassword($username, $password);
            if (!$user) {
                Response::redirect(View::adminUrl('login?error=1'));
            }
            session_regenerate_id(true);
            $_SESSION['admin_user_id'] = (int)$user['id'];
            $_SESSION['admin_login_at'] = time();
            Response::redirect(View::adminUrl(''));
        });
        $router->get('/admin/logout', function (): void {
            $_SESSION = [];
            session_destroy();
            Response::redirect(View::adminUrl('login'));
        });

        $router->get('/admin/dashboard', fn() => View::renderAdmin('dashboard'), [fn() => AuthMiddleware::require()]);
        $router->get('/admin/actividades', fn() => View::renderAdmin('actividades_list'), [fn() => AuthMiddleware::require()]);
        $router->get('/admin/actividades/nueva', fn() => View::renderAdmin('actividad_form'), [fn() => AuthMiddleware::require()]);
        $router->get('/admin/actividades/editar/{id}', fn($p) => View::renderAdmin('actividad_form', ['id' => (int)$p['id']]), [fn() => AuthMiddleware::require()]);

        $router->post('/admin/actividades/guardar', function (): void {
            AuthMiddleware::require();
            AuthMiddleware::requireCsrf();
            $data = Request::all();
            $payload = [
                'titulo' => trim((string)($data['titulo'] ?? '')),
                'descripcion' => trim((string)($data['descripcion'] ?? '')) ?: null,
                'lugar' => trim((string)($data['lugar'] ?? '')),
                'fecha' => trim((string)($data['fecha'] ?? '')),
                'hora_inicio' => trim((string)($data['hora_inicio'] ?? '')),
                'hora_fin' => trim((string)($data['hora_fin'] ?? '')) ?: null,
                'categoria' => $data['categoria'] ?? 'culto',
                'destacado' => !empty($data['destacado']),
                'estado' => $data['estado'] ?? 'programada',
            ];

            if ($payload['titulo'] === '' || $payload['lugar'] === '' || $payload['fecha'] === '' || $payload['hora_inicio'] === '') {
                Response::redirect(View::adminUrl('actividades/nueva?error=datos_invalidos'));
            }

            try {
                $userId = (int)($_SESSION['admin_user_id'] ?? 0) ?: null;
                if (!empty($data['id'])) {
                    \App\Models\Actividad::update((int)$data['id'], $payload);
                } else {
                    \App\Models\Actividad::create($payload, $userId);
                }
                Response::redirect(View::adminUrl('actividades?ok=1'));
            } catch (\Throwable $e) {
                Response::redirect(View::adminUrl('actividades/nueva?error=' . urlencode($e->getMessage())));
            }
        }, [fn() => AuthMiddleware::require()]);

        $router->post('/admin/actividades/eliminar', function (): void {
            AuthMiddleware::require();
            AuthMiddleware::requireCsrf();
            $id = (int)Request::input('id', 0);
            if ($id > 0) {
                \App\Models\Actividad::delete($id);
            }
            Response::redirect(View::adminUrl('actividades?deleted=1'));
        }, [fn() => AuthMiddleware::require()]);
    }
}