<?php
declare(strict_types=1);

namespace App\Models;

use App\Database;

final class SuscriptorPush
{
    public static function log(?string $sendpulseId, string $source = 'unknown'): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO suscriptores_push (sendpulse_id, user_agent, ip, source)
             VALUES (:spid, :ua, :ip, :src)'
        );
        $stmt->execute([
            ':spid' => $sendpulseId,
            ':ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250),
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':src' => in_array($source, ['sendpulse', 'webpush', 'unknown'], true) ? $source : 'unknown',
        ]);
        return (int)Database::connection()->lastInsertId();
    }
}