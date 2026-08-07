<?php
declare(strict_types=1);

namespace App\Models;

use App\Database;
use PDO;

final class AdminUser
{
    public static function findByUsername(string $username): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM admin_users WHERE username = :u AND activo = 1 LIMIT 1'
        );
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, username, email, nombre, rol, ultimo_acceso FROM admin_users WHERE id = :id AND activo = 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function verifyPassword(string $username, string $password): ?array
    {
        $user = self::findByUsername($username);
        if (!$user) {
            return null;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }
        self::touchLastLogin((int)$user['id']);
        unset($user['password_hash']);
        return $user;
    }

    public static function touchLastLogin(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE admin_users SET ultimo_acceso = NOW() WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}