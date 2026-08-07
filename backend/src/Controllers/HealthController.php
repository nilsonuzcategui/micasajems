<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\Database;
use App\Response;

final class HealthController
{
    public function check(): void
    {
        $status = [
            'ok' => true,
            'app' => 'JEMS Admin API',
            'env' => Config::get('APP_ENV', 'unknown'),
            'time' => date('c'),
            'checks' => [],
        ];

        // Check PHP version
        $status['checks']['php'] = [
            'version' => PHP_VERSION,
            'ok' => version_compare(PHP_VERSION, '7.4.0', '>='),
        ];

        // Check storage/logs is writable
        $logFile = APP_STORAGE . '/logs/php_errors.log';
        $logWritable = is_writable(APP_STORAGE . '/logs') || @mkdir(APP_STORAGE . '/logs', 0755, true);
        $status['checks']['storage'] = [
            'path' => APP_STORAGE,
            'writable' => $logWritable,
            'ok' => $logWritable,
        ];

        // Check database connection
        try {
            $stmt = Database::connection()->query('SELECT 1');
            $result = $stmt->fetchColumn();
            $status['checks']['database'] = [
                'host' => Config::get('DB_HOST'),
                'name' => Config::get('DB_NAME'),
                'ok' => $result == 1,
            ];
        } catch (\Throwable $e) {
            $status['ok'] = false;
            $status['checks']['database'] = [
                'host' => Config::get('DB_HOST'),
                'name' => Config::get('DB_NAME'),
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }

        // Check required extensions
        $required = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'curl'];
        $missing = array_filter($required, static fn($e) => !extension_loaded($e));
        $status['checks']['extensions'] = [
            'required' => $required,
            'missing' => array_values($missing),
            'ok' => empty($missing),
        ];

        $status['ok'] = $status['ok'] && $status['checks']['database']['ok'] && $status['checks']['extensions']['ok'];

        Response::json($status, $status['ok'] ? 200 : 500);
    }
}