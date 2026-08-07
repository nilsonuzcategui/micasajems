<?php
/**
 * CLI: corre las migraciones SQL sobre la BD configurada en .env
 *
 * Uso:
 *   php bin/migrate.php           → corre todas las migraciones pendientes
 *   php bin/migrate.php status    → muestra estado actual de las tablas
 *   php bin/migrate.php seed      → corre los datos de ejemplo (004)
 *
 * Salida coloreada cuando la terminal lo soporta.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/polyfill.php';
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Database.php';

use App\Config;
use App\Database;

$root = dirname(__DIR__);
Config::load($root . '/.env');

$colorGreen = "\033[32m";
$colorRed = "\033[31m";
$colorYellow = "\033[33m";
$colorReset = "\033[0m";
$useColor = function_exists('posix_isatty') && posix_isatty(STDOUT);

function c(string $color, string $text): string
{
    global $useColor;
    return $useColor ? $color . $text . "\033[0m" : $text;
}

$action = $argv[1] ?? 'migrate';
$migrationsDir = $root . '/database/migrations';

echo c("\033[36m", "JEMS Migrations\n");
echo "DB: " . Config::get('DB_NAME') . " @ " . Config::get('DB_HOST') . "\n";
echo str_repeat('-', 50) . "\n";

try {
    $pdo = Database::connection();
} catch (\Throwable $e) {
    echo c($colorRed, "✗ No se pudo conectar a la BD: ") . $e->getMessage() . "\n";
    echo "  Verificá las credenciales DB_HOST, DB_NAME, DB_USER, DB_PASS en .env\n";
    exit(1);
}

function getTables(\PDO $pdo): array
{
    $rows = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);
    $out = [];
    foreach ($rows as $r) {
        if (isset($r[0])) {
            $out[] = $r[0];
        }
    }
    return $out;
}

switch ($action) {
    case 'status':
        $tables = getTables($pdo);
        echo "Tablas existentes:\n";
        if (empty($tables)) {
            echo "  (ninguna)\n";
        } else {
            foreach ($tables as $t) {
                echo "  - $t\n";
            }
        }
        echo "\nMigraciones disponibles en $migrationsDir:\n";
        foreach (glob($migrationsDir . '/*.sql') as $f) {
            echo "  - " . basename($f) . "\n";
        }
        break;

    case 'seed':
        $seedFile = $migrationsDir . '/004_seed_ejemplo.sql';
        if (!is_file($seedFile)) {
            echo c($colorRed, "✗ Archivo de seed no encontrado\n");
            exit(1);
        }
        runSqlFile($pdo, $seedFile, '004_seed_ejemplo.sql');
        echo c($colorGreen, "✓ Seed aplicado\n");
        break;

    case 'migrate':
    default:
        $files = glob($migrationsDir . '/[0-9][0-9][0-9]_*.sql');
        sort($files);
        foreach ($files as $f) {
            $name = basename($f);
            if (str_contains($name, '004_seed')) {
                continue; // el seed se corre aparte
            }
            runSqlFile($pdo, $f, $name);
        }
        echo "\n" . c($colorGreen, "✓ Todas las migraciones aplicadas\n");
        echo "\nPróximos pasos:\n";
        echo "  1. Verificá: php bin/migrate.php status\n";
        echo "  2. (Opcional) Datos de ejemplo: php bin/migrate.php seed\n";
        echo "  3. Login admin: https://admin.micasajems.com/login (admin / Jems2026!)\n";
        echo "  4. Cambiá la clave del admin (UPDATE admin_users SET password_hash = ...)\n";
        break;
}

function runSqlFile(\PDO $pdo, string $file, string $name): void
{
    global $colorGreen, $colorRed, $colorYellow;
    $sql = file_get_contents($file);
    if ($sql === false) {
        echo c($colorRed, "✗ No se pudo leer $name\n");
        return;
    }
    try {
        $pdo->exec($sql);
        echo c($colorGreen, "✓ ") . "$name\n";
    } catch (\PDOException $e) {
        $msg = $e->getMessage();
        if (str_contains($msg, 'already exists')) {
            echo c($colorYellow, "⚠ ") . "$name (ya aplicada)\n";
        } else {
            echo c($colorRed, "✗ ") . "$name: " . $msg . "\n";
        }
    }
}