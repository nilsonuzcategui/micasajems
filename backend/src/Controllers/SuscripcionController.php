<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\SuscriptorPush;
use App\Request;
use App\Response;
use App\Database;

final class SuscripcionController
{
    public function log(): void
    {
        $all = Request::all();
        $sendpulseId = isset($all['sendpulse_id']) ? trim((string)$all['sendpulse_id']) : null;
        $source = isset($all['source']) ? trim((string)$all['source']) : 'unknown';

        // Si vienen los campos de Web Push nativo, guardar en push_subscriptions
        if (!empty($all['endpoint']) && !empty($all['keys']['p256dh']) && !empty($all['keys']['auth'])) {
            $this->saveWebPushSubscription($all, $source);
            return;
        }

        // Fallback: log simple (compatibilidad con SendPulse viejo)
        $id = SuscriptorPush::log($sendpulseId ?: null, $source);
        Response::ok(['id' => $id, 'message' => 'Suscripción registrada', 'source' => $source]);
    }

    private function saveWebPushSubscription(array $data, string $source): void
    {
        $endpoint = trim((string)$data['endpoint']);
        $p256dh = trim((string)$data['keys']['p256dh']);
        $auth = trim((string)$data['keys']['auth']);
        $userAgent = isset($data['user_agent']) ? substr((string)$data['user_agent'], 0, 250) : null;

        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            Response::error('Datos de suscripción incompletos', 422);
        }

        $endpointHash = hash('sha256', $endpoint);
        $source = in_array($source, ['sendpulse', 'webpush', 'unknown'], true) ? $source : 'unknown';

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare(
                'INSERT INTO push_subscriptions (endpoint, endpoint_hash, p256dh, auth, user_agent, ip, source)
                 VALUES (:ep, :epHash, :p256dh, :auth, :ua, :ip, :src)
                 ON DUPLICATE KEY UPDATE
                    p256dh = VALUES(p256dh),
                    auth = VALUES(auth),
                    user_agent = VALUES(user_agent),
                    ip = VALUES(ip),
                    source = VALUES(source),
                    activo = 1,
                    fail_count = 0'
            );
            $stmt->execute([
                ':ep' => $endpoint,
                ':epHash' => $endpointHash,
                ':p256dh' => $p256dh,
                ':auth' => $auth,
                ':ua' => $userAgent,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':src' => $source,
            ]);
            Response::ok([
                'id' => (int)$pdo->lastInsertId(),
                'message' => 'Push subscription guardada',
                'source' => $source,
            ]);
        } catch (\Throwable $e) {
            Response::error('No se pudo guardar la suscripción: ' . $e->getMessage(), 500);
        }
    }
}