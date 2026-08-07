<?php
declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = Config::get('DB_HOST', '127.0.0.1');
        $port = Config::getInt('DB_PORT', 3306);
        $name = Config::get('DB_NAME', '');
        $user = Config::get('DB_USER', 'root');
        $pass = Config::get('DB_PASS', '');
        $charset = Config::get('DB_CHARSET', 'utf8mb4');

        if ($name === '') {
            throw new RuntimeException('DB_NAME no está definido en .env');
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            self::$pdo->exec("SET time_zone = '-04:30'");
        } catch (PDOException $e) {
            if (Config::getBool('APP_DEBUG', false)) {
                throw new RuntimeException('Error de conexión a BD: ' . $e->getMessage());
            }
            throw new RuntimeException('No se pudo conectar a la base de datos');
        }

        return self::$pdo;
    }
}