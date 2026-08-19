<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\Database;
use App\Response;
use App\Services\SendPulseService;
use App\WebPushClient;

final class PushController
{
    public function send(): void
    {
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
     * Envía una notificación push.
     *
     * Estrategia:
     *  1) Si SendPulse está configurado → delega en SendPulse (gestiona suscriptores).
     *  2) Si no, usa Web Push nativo (VAPID) sobre los registros de push_subscriptions.
     */
    public function broadcast(array $payload): array
    {
        $title = (string)($payload['title'] ?? 'Nueva actividad');
        $body = (string)($payload['body'] ?? '');
        $url = (string)($payload['url'] ?? '/');

        // 1) SendPulse
        if (SendPulseService::isConfigured()) {
            try {
                $res = SendPulseService::sendPush($title, $body, $url);
                return [
                    'channel' => 'sendpulse',
                    'success' => $res['sent'] ? 1 : 0,
                    'failed' => $res['sent'] ? 0 : 1,
                    'campaign_id' => $res['campaign_id'],
                ];
            } catch (\Throwable $e) {
                error_log('[Push] SendPulse falló: ' . $e->getMessage());
                // Caemos al fallback VAPID si está disponible
            }
        }

        // 2) Fallback Web Push nativo (VAPID)
        return $this->broadcastVAPID($payload);
    }

    private function broadcastVAPID(array $payload): array
    {
        $vapidPublic = (string)Config::get('VAPID_PUBLIC_KEY', '');
        $vapidPrivate = (string)Config::get('VAPID_PRIVATE_KEY', '');
        $subject = (string)Config::get('VAPID_SUBJECT', 'mailto:admin@micasajems.com');

        if ($vapidPublic === '' || $vapidPrivate === '') {
            return [
                'channel' => 'none',
                'success' => 0,
                'failed' => 0,
                'total' => 0,
                'error' => 'SendPulse no configurado y VAPID keys ausentes',
            ];
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->query(
                'SELECT id, endpoint, p256dh, auth FROM push_subscriptions WHERE activo = 1'
            );
            $subscriptions = $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [
                'channel' => 'vapid',
                'success' => 0,
                'failed' => 0,
                'error' => 'Error de BD: ' . $e->getMessage(),
            ];
        }

        if (empty($subscriptions)) {
            return [
                'channel' => 'vapid',
                'success' => 0,
                'failed' => 0,
                'total' => 0,
                'message' => 'No hay suscriptores',
            ];
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
                $pdo->prepare('UPDATE push_subscriptions SET last_sent_at = NOW(), fail_count = 0 WHERE id = :id')
                    ->execute([':id' => $sub['id']]);
            } else {
                $failed++;
                $errors[] = ['id' => $sub['id'], 'status' => $result['status'], 'message' => $result['message']];

                if (in_array($result['status'], [404, 410], true)) {
                    $pdo->prepare('UPDATE push_subscriptions SET activo = 0 WHERE id = :id')
                        ->execute([':id' => $sub['id']]);
                } else {
                    $pdo->prepare('UPDATE push_subscriptions SET fail_count = fail_count + 1 WHERE id = :id')
                        ->execute([':id' => $sub['id']]);
                }
            }
        }

        return [
            'channel' => 'vapid',
            'total' => count($subscriptions),
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
}