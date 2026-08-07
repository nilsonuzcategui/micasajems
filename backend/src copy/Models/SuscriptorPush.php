<?php
declare(strict_types=1);

namespace App\Models;

use App\Database;

final class SuscriptorPush
{
    public static function log(?string $sendpulseId): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO suscriptores_push (sendpulse_id, user_agent, ip)
             VALUES (:spid, :ua, :ip)'
        );
        $stmt->execute([
            ':spid' => $sendpulseId,
            ':ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250),
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
        return (int)Database::connection()->lastInsertId();
    }
}