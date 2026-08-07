<?php
declare(strict_types=1);

namespace App\Services;

use App\Config;
use RuntimeException;

/**
 * Servicio mínimo para hablar con la API REST de SendPulse.
 * - login(): obtiene token (grant_type=client_credentials)
 * - sendPush(): dispara campaña push (no usado por ahora; SendPulse maneja la lista de suscriptores)
 *
 * Documentación: https://sendpulse.com/integrations/api
 */
final class SendPulseService
{
    private static ?string $cachedToken = null;

    public static function getAccountId(): string
    {
        $id = Config::get('SENDPULSE_PUSH_ACCOUNT_ID');
        if ($id === null || $id === '') {
            return (string)Config::get('SENDPULSE_API_ID', '');
        }
        return $id;
    }

    public static function token(): string
    {
        if (self::$cachedToken !== null) {
            return self::$cachedToken;
        }

        $apiId = (string)Config::get('SENDPULSE_API_ID', '');
        $apiSecret = (string)Config::get('SENDPULSE_API_SECRET', '');

        if ($apiId === '' || $apiSecret === '') {
            throw new RuntimeException('Credenciales SendPulse no configuradas');
        }

        $url = (string)Config::get('SENDPULSE_API_URL', 'https://api.sendpulse.com') . '/smtp/oauth/access_token';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'client_credentials',
                'client_id' => $apiId,
                'client_secret' => $apiSecret,
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("SendPulse error cURL: {$error}");
        }
        if ($code >= 400) {
            throw new RuntimeException("SendPulse HTTP {$code}: {$response}");
        }

        $data = json_decode((string)$response, true);
        if (!is_array($data) || empty($data['access_token'])) {
            throw new RuntimeException('SendPulse: token no recibido');
        }

        return self::$cachedToken = (string)$data['access_token'];
    }
}