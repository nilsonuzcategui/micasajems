<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\Database;
use App\Response;
use App\WebPushClient;

final class PushController
{
    public function send(): void
    {
        // Solo accesible desde admin
        $userId = $_SESSION['admin_user_id'] ?? null;
        if (!$userId) {
            Response::error('No autenticado', 401);
        }

        $all = (array)\App\Request::all();
        $title = $all['title'] ?? 'Nueva actividad';
        $body = $all['body'] ?? '';
        $url = $all['url'] ?? '/';

        $result = $this->broadcast([
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ]);

        Response::ok($result);
    }

    /**
     * Envía una notificación a todos los suscriptores activos.
     * Devuelve estadísticas de éxito/fallo.
     */
    public function broadcast(array $payload): array
    {
        $vapidPublic = (string)Config::get('VAPID_PUBLIC_KEY', '');
        $vapidPrivate = (string)Config::get('VAPID_PRIVATE_KEY', '');
        $subject = (string)Config::get('VAPID_SUBJECT', 'mailto:admin@micasajems.com');

        if ($vapidPublic === '' || $vapidPrivate === '') {
            return ['success' => 0, 'failed' => 0, 'error' => 'VAPID keys no configuradas'];
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->query(
                'SELECT id, endpoint, p256dh, auth FROM push_subscriptions WHERE activo = 1'
            );
            $subscriptions = $stmt->fetchAll();
        } catch (\Throwable $e) {
            return ['success' => 0, 'failed' => 0, 'error' => 'Error de BD: ' . $e->getMessage()];
        }

        if (empty($subscriptions)) {
            return ['success' => 0, 'failed' => 0, 'total' => 0, 'message' => 'No hay suscriptores'];
        }

        $client = new WebPushClient($vapidPublic, $vapidPrivate, $subject);
        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($subscriptions as $sub) {
            $result = $client->send(
                [
                    'endpoint' => $sub['endpoint'],
                    'keys' => [
                        'p256dh' => $sub['p256dh'],
                        'auth' => $sub['auth'],
                    ],
                ],
                $payload
            );

            if ($result['success']) {
                $success++;
                // Resetear contador de fallos
                $pdo->prepare('UPDATE push_subscriptions SET last_sent_at = NOW(), fail_count = 0 WHERE id = :id')
                    ->execute([':id' => $sub['id']]);
            } else {
                $failed++;
                $errors[] = ['id' => $sub['id'], 'status' => $result['status'], 'message' => $result['message']];

                // Si la suscripción es inválida (404, 410), desactivarla
                if (in_array($result['status'], [404, 410], true)) {
                    $pdo->prepare('UPDATE push_subscriptions SET activo = 0 WHERE id = :id')
                        ->execute([':id' => $sub['id']]);
                } else {
                    // Incrementar contador de fallos
                    $pdo->prepare('UPDATE push_subscriptions SET fail_count = fail_count + 1 WHERE id = :id')
                        ->execute([':id' => $sub['id']]);
                }
            }
        }

        return [
            'total' => count($subscriptions),
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
}