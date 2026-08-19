<?php
/**
 * CLI: genera un par de claves VAPID para Web Push
 *
 * Salida: imprime las claves en formato suitable para .env
 *
 * Uso: php bin/generate-vapid.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/polyfill.php';
require_once __DIR__ . '/../src/Config.php';

$tmpDir = sys_get_temp_dir();
$privFile = $tmpDir . '/vapid_priv_' . bin2hex(random_bytes(4)) . '.pem';
$pubFile = $tmpDir . '/vapid_pub_' . bin2hex(random_bytes(4)) . '.pem';

echo "Generando par de claves VAPID (prime256v1)...\n";

// Intentar con openssl command primero (más portable)
$whichOpenssl = 'openssl';
$cmd = "$whichOpenssl ecparam -name prime256v1 -genkey -noout -out " . escapeshellarg($privFile) . " 2>&1";
exec($cmd, $out, $code);

if ($code !== 0) {
    // Fallback: usar openssl_pkey_new
    $key = openssl_pkey_new(['curve_name' => 'prime256v1']);
    if (!$key) {
        fwrite(STDERR, "Error: no se pudo generar la clave. OpenSSL no soporta EC.\n");
        exit(1);
    }
    openssl_pkey_export($key, $privPem);
    $details = openssl_pkey_get_details($key);
    $pubPem = $details['key'];
    file_put_contents($privFile, $privPem);
    file_put_contents($pubFile, $pubPem);
} else {
    // Extraer la pública
    $cmd = "$whichOpenssl ec -in " . escapeshellarg($privFile) . " -pubout -out " . escapeshellarg($pubFile) . " 2>&1";
    exec($cmd, $out, $code);
    if ($code !== 0) {
        fwrite(STDERR, "Error extrayendo clave pública: " . implode("\n", $out) . "\n");
        @unlink($privFile);
        exit(1);
    }
}

$pubPem = file_get_contents($pubFile);

// Extraer solo los 64 bytes del punto EC (X + Y) en base64url
$derB64 = '';
foreach (explode("\n", trim($pubPem)) as $line) {
    if (strpos($line, '-----') === false) {
        $derB64 .= trim($line);
    }
}
$der = base64_decode($derB64);

$pubRaw = null;
for ($i = strlen($der) - 65; $i >= 0; $i--) {
    if ($der[$i] === chr(4)) {
        $candidate = substr($der, $i + 1, 64);
        if (strlen($candidate) === 64) {
            $pubRaw = $candidate;
            break;
        }
    }
}

if ($pubRaw === null) {
    fwrite(STDERR, "Error: no se pudo extraer la clave pública raw\n");
    @unlink($privFile);
    @unlink($pubFile);
    exit(1);
}

$pubB64Url = rtrim(strtr(base64_encode($pubRaw), '+/', '-_'), '=');
$privPem = file_get_contents($privFile);

@unlink($privFile);
@unlink($pubFile);

echo "\n";
echo "================================================\n";
echo "VAPID Keys generadas\n";
echo "================================================\n";
echo "\n";
echo "Agregá estas líneas a tu backend/.env:\n\n";
echo "VAPID_PUBLIC_KEY=" . $pubB64Url . "\n";
echo "VAPID_PRIVATE_KEY=\"" . str_replace("\n", "\\n", trim($privPem)) . "\"\n";
echo "VAPID_SUBJECT=mailto:admin@micasajems.com\n";
echo "\n";
echo "Y estas a tu micasajems/.env (raíz):\n\n";
echo "PUBLIC_VAPID_KEY=" . $pubB64Url . "\n";
echo "\n";
echo "⚠️  IMPORTANTE: No compartas la clave privada. Solo va al backend.\n";