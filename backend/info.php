<?php
// Standalone test - NO usa nada del backend, solo PHP básico
header('Content-Type: text/plain; charset=utf-8');
echo "PHP " . PHP_VERSION . " OK\n";
echo "SAPI: " . PHP_SAPI . "\n";
echo "Time: " . date('c') . "\n";
echo "Loaded extensions: " . implode(', ', get_loaded_extensions()) . "\n";
echo "\n";
echo "Test de extensiones críticas:\n";
foreach (['pdo', 'pdo_mysql', 'mysqli', 'mbstring', 'curl', 'json', 'openssl'] as $ext) {
    echo "  {$ext}: " . (extension_loaded($ext) ? 'YES' : 'NO') . "\n";
}
echo "\n";
echo "Test de archivos críticos:\n";
$files = ['index.php', '.env', '.htaccess', 'src/Bootstrap.php', 'src/Router.php', 'src/Routes.php'];
foreach ($files as $f) {
    $path = __DIR__ . '/' . $f;
    echo "  {$f}: " . (is_readable($path) ? "READABLE (" . filesize($path) . " bytes)" : 'NO READABLE') . "\n";
}