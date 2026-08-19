<?php
declare(strict_types=1);

namespace App;

use App\Controllers\ActividadController;
use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Controllers\PushController;
use App\Controllers\SuscripcionController;
use App\Middleware\AuthMiddleware;

final class Routes
{
    public static function register(Router $router): void
    {
        $auth = new AuthController();
        $act = new ActividadController();
        $sub = new SuscripcionController();
        $health = new HealthController();
        $push = new PushController();

        // ============ HEALTH CHECK ============
        $router->get('/api/health', [$health, 'check']);

        // ============ API PÚBLICA ============
        $router->get('/api/actividades', [$act, 'index']);
        $router->get('/api/actividades/{id}', fn($p) => $act->show((int)$p['id']));
        $router->post('/api/suscripciones', [$sub, 'log']);

        // ============ AUTH API ============
        $router->post('/api/admin/auth', [$auth, 'login']);
        $router->post('/api/admin/auth/logout', [$auth, 'logout'], [fn() => AuthMiddleware::require()]);
        $router->get('/api/admin/auth/me', [$auth, 'me'], [fn() => AuthMiddleware::require()]);

        // ============ CRUD ADMIN API (protegido) ============
        $mwApi = [fn() => AuthMiddleware::require(), [AuthMiddleware::class, 'requireCsrf']];
        $router->post('/api/admin/actividades', [$act, 'store'], $mwApi);
        $router->put('/api/admin/actividades/{id}', fn($p) => $act->update((int)$p['id']), $mwApi);
        $router->delete('/api/admin/actividades/{id}', fn($p) => $act->destroy((int)$p['id']), $mwApi);

        // ============ PUSH NOTIFICATIONS (admin) ============
        $router->post('/api/admin/push/send', [$push, 'send'], [fn() => AuthMiddleware::require()]);

        // ============ VISTAS HTML ADMIN ============
        // Se registran con y sin prefijo /admin para soportar:
        //   - Subdominio dedicado (admin.micasajems.com/) → sin prefijo
        //   - Path dentro de dominio (micasajems.com/admin/) → con prefijo

        $adminViews = [
            'GET' => [
                '/'              => fn() => View::renderAdmin('redirect'),
                '/login'         => fn() => View::renderAdmin('login'),
                '/logout'        => function (): void {
                    $_SESSION = [];
                    session_destroy();
                    Response::redirect(View::adminUrl('login'));
                },
                '/dashboard'     => [function (): void {
                    Response::redirect(View::adminUrl('actividades'));
                }, [fn() => AuthMiddleware::require()]],
                '/actividades'   => [fn() => View::renderAdmin('actividades_list'), [fn() => AuthMiddleware::require()]],
                '/actividades/nueva' => [fn() => View::renderAdmin('actividad_form'), [fn() => AuthMiddleware::require()]],
                '/actividades/editar/{id}' => [fn($p) => View::renderAdmin('actividad_form', ['id' => (int)$p['id']]), [fn() => AuthMiddleware::require()]],
            ],
            'POST' => [
                '/login' => function (): void {
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
                },
                '/actividades/guardar' => [function (): void {
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
                        $isEdit = !empty($data['id']);
                        $savedId = null;
                        if ($isEdit) {
                            \App\Models\Actividad::update((int)$data['id'], $payload);
                            $savedId = (int)$data['id'];
                        } else {
                            $savedId = \App\Models\Actividad::create($payload, $userId);
                        }

                        // Si el admin pidió notificar, disparar push a todos los suscriptores
                        $pushResult = null;
                        if (!empty($data['notificar_push'])) {
                            try {
                                $push = new \App\Controllers\PushController();
                                $body = $payload['lugar'] . ' · ' . $payload['fecha'] . ' ' . $payload['hora_inicio'];
                                $pushResult = $push->broadcast([
                                    'title' => 'Nueva actividad: ' . $payload['titulo'],
                                    'body' => $body,
                                    'url' => '/#actividades',
                                ]);
                            } catch (\Throwable $e) {
                                error_log('[Push] Error al notificar: ' . $e->getMessage());
                            }
                        }

                        $redirectTo = 'actividades?ok=1';
                        if ($pushResult !== null) {
                            $redirectTo .= '&push_total=' . (int)($pushResult['total'] ?? 0)
                                . '&push_ok=' . (int)($pushResult['success'] ?? 0)
                                . '&push_fail=' . (int)($pushResult['failed'] ?? 0);
                        }
                        Response::redirect(View::adminUrl($redirectTo));
                    } catch (\Throwable $e) {
                        Response::redirect(View::adminUrl('actividades/nueva?error=' . urlencode($e->getMessage())));
                    }
                }, [fn() => AuthMiddleware::require()]],
                '/actividades/eliminar' => [function (): void {
                    AuthMiddleware::require();
                    AuthMiddleware::requireCsrf();
                    $id = (int)Request::input('id', 0);
                    if ($id > 0) {
                        \App\Models\Actividad::delete($id);
                    }
                    Response::redirect(View::adminUrl('actividades?deleted=1'));
                }, [fn() => AuthMiddleware::require()]],
            ],
        ];

        foreach ($adminViews as $method => $routes) {
            foreach ($routes as $path => $handler) {
                // Formato: [closure, [middlewares]]  o  closure
                if (is_array($handler) && isset($handler[1])) {
                    [$closure, $mw] = $handler;
                } else {
                    $closure = $handler;
                    $mw = [];
                }

                // Registra la ruta sin prefijo (subdominio)
                $router->$method($path, $closure, $mw);

                // Registra también con prefijo /admin (path-based)
                if ($path === '/') {
                    $router->$method('/admin', $closure, $mw);
                    $router->$method('/admin/', $closure, $mw);
                } else {
                    $router->$method('/admin' . $path, $closure, $mw);
                }
            }
        }
    }
}