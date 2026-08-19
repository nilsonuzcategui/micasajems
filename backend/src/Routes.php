<?php
declare(strict_types=1);

namespace App;

use App\Controllers\ActividadController;
use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Controllers\PushController;
use App\Controllers\SuscripcionController;
use App\Middleware\AuthMiddleware;
use App\Config;

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
                    if (!Request::validateCsrf()) {
                        $_SESSION['flash_error'] = 'csrf_invalido';
                        $back = !empty($data['id']) ? 'actividades/editar/' . (int)$data['id'] : 'actividades/nueva';
                        Response::redirect(View::adminUrl($back));
                    }
                    $data = Request::all();

                    // Validación por campo (los mensajes vuelven al form vía $_SESSION)
                    $fieldErrors = [];
                    $titulo = trim((string)($data['titulo'] ?? ''));
                    if ($titulo === '') {
                        $fieldErrors['titulo'] = 'El título es obligatorio.';
                    } elseif (mb_strlen($titulo) > 150) {
                        $fieldErrors['titulo'] = 'Máximo 150 caracteres.';
                    }
                    $lugar = trim((string)($data['lugar'] ?? ''));
                    if ($lugar === '') {
                        $fieldErrors['lugar'] = 'El lugar es obligatorio.';
                    } elseif (mb_strlen($lugar) > 200) {
                        $fieldErrors['lugar'] = 'Máximo 200 caracteres.';
                    }
                    $fecha = trim((string)($data['fecha'] ?? ''));
                    if ($fecha === '' || !\App\Controllers\ActividadController::isValidDateStatic($fecha)) {
                        $fieldErrors['fecha'] = 'Fecha inválida (YYYY-MM-DD).';
                    }
                    $horaInicio = trim((string)($data['hora_inicio'] ?? ''));
                    if ($horaInicio === '' || !\App\Controllers\ActividadController::isValidTimeStatic($horaInicio)) {
                        $fieldErrors['hora_inicio'] = 'Hora de inicio inválida (HH:MM).';
                    }
                    $horaFin = $data['hora_fin'] ?? null;
                    if ($horaFin !== null && $horaFin !== '' && !\App\Controllers\ActividadController::isValidTimeStatic((string)$horaFin)) {
                        $fieldErrors['hora_fin'] = 'Hora de fin inválida (HH:MM).';
                    }

                    if (!empty($fieldErrors)) {
                        $_SESSION['field_errors'] = $fieldErrors;
                        $_SESSION['form_data'] = $data;
                        $back = !empty($data['id']) ? 'actividades/editar/' . (int)$data['id'] : 'actividades/nueva';
                        $_SESSION['flash_error'] = 'datos_invalidos';
                        Response::redirect(View::adminUrl($back));
                    }

                    $payload = [
                        'titulo' => $titulo,
                        'descripcion' => trim((string)($data['descripcion'] ?? '')) ?: null,
                        'lugar' => $lugar,
                        'fecha' => $fecha,
                        'hora_inicio' => $horaInicio,
                        'hora_fin' => ($horaFin !== null && $horaFin !== '') ? $horaFin : null,
                        'categoria' => in_array($data['categoria'] ?? '', \App\Models\Actividad::CATEGORIAS, true) ? $data['categoria'] : 'culto',
                        'destacado' => !empty($data['destacado']),
                        'estado' => in_array($data['estado'] ?? '', \App\Models\Actividad::ESTADOS, true) ? $data['estado'] : 'programada',
                    ];

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

                        // Limpieza de errores en sesión tras éxito
                        unset($_SESSION['field_errors'], $_SESSION['form_data']);

                        // Si el admin pidió notificar, disparar push a todos los suscriptores
                        $pushResult = null;
                        if (!empty($data['notificar_push'])) {
                            try {
                                $push = new \App\Controllers\PushController();
                                $frontendUrl = rtrim((string)Config::get('FRONTEND_URL', 'https://micasajems.com'), '/');
                                $body = $payload['lugar'] . ' · ' . $payload['fecha'] . ' ' . $payload['hora_inicio'];
                                $pushResult = $push->broadcast([
                                    'title' => 'Nueva actividad: ' . $payload['titulo'],
                                    'body' => $body,
                                    'url' => $frontendUrl . '/#actividades',
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
                        $_SESSION['flash_error'] = 'Error al guardar: ' . $e->getMessage();
                        $back = !empty($data['id']) ? 'actividades/editar/' . (int)$data['id'] : 'actividades/nueva';
                        Response::redirect(View::adminUrl($back));
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