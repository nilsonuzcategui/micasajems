<?php
declare(strict_types=1);

/**
 * Cliente Web Push usando solo PHP nativo (sin composer)
 * Implementa el protocolo RFC 8030 + VAPID (RFC 8292)
 *
 * Uso:
 *   $push = new WebPushClient(VAPID_PUBLIC, VAPID_PRIVATE, 'mailto:admin@example.com');
 *   $result = $push->send($subscription, $payload);
 */

namespace App;

final class WebPushClient
{
    private string $vapidPublic;
    private string $vapidPrivate;
    private string $subject;

    public function __construct(string $vapidPublic, string $vapidPrivate, string $subject)
    {
        $this->vapidPublic = $vapidPublic;
        $this->vapidPrivate = $vapidPrivate;
        $this->subject = $subject;
    }

    /**
     * Envía una notificación push a una suscripción.
     *
     * @param array $subscription Formato: ['endpoint' => 'https://...', 'keys' => ['p256dh' => '...', 'auth' => '...']]
     * @param string|array $payload Si es string, se envía como está. Si es array, se JSON-encodea.
     * @param int $ttl Segundos de vida del mensaje (default 86400 = 24h)
     * @return array ['success' => bool, 'status' => int, 'message' => string]
     */
    public function send(array $subscription, $payload, int $ttl = 86400): array
    {
        $endpoint = $subscription['endpoint'] ?? '';
        if ($endpoint === '') {
            return ['success' => false, 'status' => 0, 'message' => 'endpoint vacío'];
        }

        $p256dh = $subscription['keys']['p256dh'] ?? '';
        $auth = $subscription['keys']['auth'] ?? '';
        if ($p256dh === '' || $auth === '') {
            return ['success' => false, 'status' => 0, 'message' => 'claves de encriptación faltantes'];
        }

        // Parsear endpoint para sacar origen
        $endpointParts = parse_url($endpoint);
        if (!$endpointParts || !isset($endpointParts['host'])) {
            return ['success' => false, 'status' => 0, 'message' => 'endpoint inválido'];
        }
        $audience = ($endpointParts['scheme'] ?? 'https') . '://' . $endpointParts['host'];
        if (isset($endpointParts['port']) && !in_array((int)$endpointParts['port'], [80, 443], true)) {
            $audience .= ':' . $endpointParts['port'];
        }
        $endpointPath = ($endpointParts['path'] ?? '/') . (isset($endpointParts['query']) ? '?' . $endpointParts['query'] : '');

        // Preparar el payload
        $payloadStr = is_array($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE) : (string)$payload;
        $payloadBytes = $payloadStr === '' ? null : $payloadStr;

        // Encriptar payload si no está vacío
        if ($payloadBytes !== null) {
            try {
                $encrypted = $this->encryptPayload($payloadBytes, $p256dh, $auth);
            } catch (\Throwable $e) {
                return ['success' => false, 'status' => 0, 'message' => 'No se pudo encriptar el payload: ' . $e->getMessage()];
            }
            $ciphertext = $encrypted['ciphertext'];
            $salt = $encrypted['salt'];
            $localPublicKey = $encrypted['localPublicKey'];
        } else {
            $ciphertext = '';
            $salt = '';
            $localPublicKey = '';
        }

        // Generar JWT VAPID
        $vapidHeaders = $this->buildVapidHeaders($audience, $endpoint);

        // Construir headers
        $headers = [
            'TTL: ' . $ttl,
            'Content-Type: application/octet-stream',
        ];
        if ($payloadBytes !== null) {
            $headers[] = 'Content-Encoding: aes128gcm';
            $headers[] = 'Encryption: salt=' . base64_encode($salt);
            $headers[] = 'Crypto-Key: dh=' . base64_encode($localPublicKey);
        }
        foreach ($vapidHeaders as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }

        // Enviar con curl
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $ciphertext,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'status' => 0, 'message' => 'cURL: ' . $error];
        }

        // 201 = éxito, 410 = suscripción eliminada, 404/403 = inválida
        $success = $status >= 200 && $status < 300;
        return [
            'success' => $success,
            'status' => $status,
            'message' => $success ? 'OK' : ('HTTP ' . $status),
        ];
    }

    /**
     * Encripta el payload usando ECDH + AES-128-GCM según RFC 8188
     */
    private function encryptPayload(string $payload, string $p256dhB64, string $authB64): array
    {
        // Decodificar clave pública del suscriptor (65 bytes uncompressed)
        $p256dh = base64_decode(strtr($p256dhB64 . str_repeat('=', (4 - strlen($p256dhB64) % 4) % 4), '-_', '+/'));
        $auth = base64_decode(strtr($authB64 . str_repeat('=', (4 - strlen($authB64) % 4) % 4), '-_', '+/'));

        if (strlen($p256dh) !== 65) {
            throw new \RuntimeException('p256dh debe tener 65 bytes uncompressed');
        }

        // Agregar prefijo 0x04 si no está
        if ($p256dh[0] !== chr(4)) {
            $p256dh = chr(4) . $p256dh;
        }

        // Cargar clave pública del receptor
        $recipientKey = openssl_pkey_get_public($p256dh);
        if (!$recipientKey) {
            throw new \RuntimeException('No se pudo parsear p256dh');
        }

        // Generar par de claves Ephemeral
        $ephemeral = openssl_pkey_new(['curve_name' => 'prime256v1']);
        if (!$ephemeral) {
            throw new \RuntimeException('No se pudo generar clave efímera');
        }
        $ephemeralDetails = openssl_pkey_get_details($ephemeral);
        $ephemeralRaw = $ephemeralDetails['ec']['point'];

        // Si tiene 65 bytes con prefijo 0x04, quitarlo
        if (strlen($ephemeralRaw) === 65 && $ephemeralRaw[0] === chr(4)) {
            $ephemeralPubRaw = substr($ephemeralRaw, 1);
        } else {
            $ephemeralPubRaw = $ephemeralRaw;
        }

        // Derivar secreto compartido
        $sharedSecret = '';
        $result = openssl_open($auth, $sharedSecret, $recipientKey, $ephemeral);
        // openssl_open no es para ECDH, usar openssl_pkey_derive si está disponible (PHP 7.1+)
        if (function_exists('openssl_pkey_derive')) {
            $sharedSecret = openssl_pkey_derive($recipientKey, $ephemeral);
        } else {
            throw new \RuntimeException('Tu versión de PHP no soporta openssl_pkey_derive. Usá PHP 7.1+');
        }

        if (!$sharedSecret || strlen($sharedSecret) !== 32) {
            throw new \RuntimeException('No se pudo derivar secreto compartido');
        }

        // Derivar claves IKM usando auth secret
        $ikm = $this->hkdf($auth, $sharedSecret, $ephemeralPubRaw . "\x04" . $this->stripPrefix($p256dh), 32, "WebPush: info\x00" . $ephemeralPubRaw . "\x04" . $this->stripPrefix($p256dh));

        // Salt aleatorio
        $salt = random_bytes(16);

        // Derivar clave de cifrado y nonce
        $context = "P-256\x00";
        $cek = $this->hkdf($salt, $ikm, $context . "aesgcm" . "\x00\x01", 16, null);
        $nonce = $this->hkdf($salt, $ikm, $context . "nonce" . "\x00\x01", 12, null);

        // Agregar padding (2 bytes)
        $paddedPayload = $payload . "\x02";

        // Cifrar con AES-128-GCM
        $tag = '';
        $ciphertext = openssl_encrypt($paddedPayload, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);
        if ($ciphertext === false) {
            throw new \RuntimeException('Error en encriptación AES-GCM');
        }

        // Construir header RFC 8188
        // Salt (16) + Rs (4) + idlen (1) + keyid (idlen) + ciphertext
        $rs = pack('N', 4096);
        $idlen = chr(strlen($ephemeralPubRaw));
        $header = $salt . $rs . $idlen . $ephemeralPubRaw;

        // El tag de GCM se concatena al final
        $encrypted = $header . $ciphertext . $tag;

        return [
            'ciphertext' => $encrypted,
            'salt' => $salt,
            'localPublicKey' => $ephemeralPubRaw,
        ];
    }

    private function stripPrefix(string $key): string
    {
        if (strlen($key) === 65 && $key[0] === chr(4)) {
            return substr($key, 1);
        }
        return $key;
    }

    /**
     * HKDF-SHA256 según RFC 5869
     */
    private function hkdf(string $salt, string $ikm, string $info, int $length, ?string $context): string
    {
        // Asegurar que salt tenga 32 bytes
        if (strlen($salt) < 32) {
            $salt = str_pad($salt, 32, "\x00", STR_PAD_RIGHT);
        }

        // Extract: PRK = HMAC-SHA256(salt, IKM)
        $prk = hash_hmac('sha256', $ikm, $salt, true);

        // Expand: T(1) = HMAC(PRK, info || 0x01)
        $input = $info;
        if ($context !== null) {
            $input .= $context;
        }
        $t = hash_hmac('sha256', $input . "\x01", $prk, true);

        return substr($t, 0, $length);
    }

    /**
     * Construye los headers VAPID (Authorization y Crypto-Key si aplica)
     */
    private function buildVapidHeaders(string $audience, string $endpointPath): array
    {
        $expiration = time() + 12 * 3600;
        $header = ['typ' => 'JWT', 'alg' => 'ES256'];
        $payload = [
            'aud' => $audience,
            'exp' => $expiration,
            'sub' => $this->subject,
        ];

        $headerB64 = $this->b64url(json_encode($header));
        $payloadB64 = $this->b64url(json_encode($payload));
        $signingInput = $headerB64 . '.' . $payloadB64;

        $signature = $this->signES256($signingInput);
        if ($signature === null) {
            return [];
        }

        $jwt = $signingInput . '.' . $this->b64url($signature);

        $headers = [
            'Authorization' => 'vapid t=' . $jwt . ', k=' . $this->vapidPublic,
        ];

        return $headers;
    }

    /**
     * Firma ES256 usando openssl
     */
    private function signES256(string $data): ?string
    {
        $privKey = openssl_pkey_get_private($this->vapidPrivate);
        if (!$privKey) {
            return null;
        }

        $signature = '';
        $result = openssl_sign($data, $signature, $privKey, OPENSSL_ALGO_SHA256);
        if (!$result) {
            return null;
        }

        // Convertir de DER a raw r||s (64 bytes)
        return $this->derToRaw($signature);
    }

    /**
     * Convierte firma ECDSA DER a formato raw r||s
     */
    private function derToRaw(string $der): string
    {
        // Estructura DER: 0x30 [totalLen] 0x02 [rLen] [r] 0x02 [sLen] [s]
        $offset = 0;
        if ($der[$offset++] !== "\x30") {
            return $der;
        }
        $seqLen = ord($der[$offset++]);
        if ($seqLen & 0x80) {
            $offset += ($seqLen & 0x7f);
        }

        if ($der[$offset++] !== "\x02") {
            return $der;
        }
        $rLen = ord($der[$offset++]);
        $r = substr($der, $offset, $rLen);
        $offset += $rLen;

        if ($der[$offset++] !== "\x02") {
            return $der;
        }
        $sLen = ord($der[$offset++]);
        $s = substr($der, $offset, $sLen);

        // Limpiar prefijo 0x00 si está (para positivos)
        if (strlen($r) > 32 && $r[0] === "\x00") {
            $r = substr($r, 1);
        }
        if (strlen($s) > 32 && $s[0] === "\x00") {
            $s = substr($s, 1);
        }

        // Pad a 32 bytes
        $r = str_pad($r, 32, "\x00", STR_PAD_LEFT);
        $s = str_pad($s, 32, "\x00", STR_PAD_LEFT);

        return $r . $s;
    }

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}