<?php
declare(strict_types=1);

namespace App\Services;

use App\Config;
use RuntimeException;

/**
 * Servicio para hablar con la API REST de SendPulse.
 *
 * - login(): obtiene token (grant_type=client_credentials)
 * - sendPush(): crea una campaña push y la envía a todos los suscriptores
 *   del sitio configurado (SendPulse los administra).
 *
 * Documentación: https://sendpulse.com/integrations/api
 */
final class SendPulseService
{
    private static ?string $cachedToken = null;

    /**
     * Devuelve el ID numérico del sitio Web Push configurado en SendPulse.
     * Se obtiene en el panel de SendPulse → Push → Sitios web → (tu sitio).
     */
    public static function getWebsiteId(): string
    {
        $id = Config::get('SENDPULSE_PUSH_WEBSITE_ID');
        if ($id !== null && $id !== '') {
            return (string)$id;
        }
        // Fallback legacy
        $legacy = Config::get('SENDPULSE_PUSH_ACCOUNT_ID');
        if ($legacy !== null && $legacy !== '') {
            return (string)$legacy;
        }
        return '';
    }

    public static function isConfigured(): bool
    {
        $apiId = (string)Config::get('SENDPULSE_API_ID', '');
        $apiSecret = (string)Config::get('SENDPULSE_API_SECRET', '');
        return $apiId !== '' && $apiSecret !== '' && self::getWebsiteId() !== '';
    }

    public static function token(): string
    {
        if (self::$cachedToken !== null) {
            return self::$cachedToken;
        }

        $apiId = (string)Config::get('SENDPULSE_API_ID', '');
        $apiSecret = (string)Config::get('SENDPULSE_API_SECRET', '');

        if ($apiId === '' || $apiSecret === '') {
            throw new RuntimeException('Credenciales SendPulse no configuradas (SENDPULSE_API_ID / SENDPULSE_API_SECRET)');
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
            throw new RuntimeException("SendPulse OAuth HTTP {$code}: {$response}");
        }

        $data = json_decode((string)$response, true);
        if (!is_array($data) || empty($data['access_token'])) {
            throw new RuntimeException('SendPulse: token no recibido');
        }

        return self::$cachedToken = (string)$data['access_token'];
    }

    /**
     * Crea una campaña push y la envía inmediatamente a todos los suscriptores
     * del sitio configurado en SendPulse.
     *
     * @return array{ campaign_id: int|string, sent: bool }
     */
    public static function sendPush(string $title, string $body, string $url = '', string $icon = ''): array
    {
        if (!self::isConfigured()) {
            throw new RuntimeException('SendPulse no está configurado completamente (faltan API ID/Secret o Website ID)');
        }

        $token = self::token();
        $websiteId = self::getWebsiteId();
        $base = (string)Config::get('SENDPULSE_API_URL', 'https://api.sendpulse.com');

        // 1) Crear la campaña
        $campaignPayload = [
            'title' => mb_substr($title, 0, 50),
            'body' => mb_substr($body, 0, 150),
            'website_id' => (int)$websiteId,
            'segmentation' => ['all'],
        ];
        if ($url !== '') {
            $campaignPayload['url'] = $url;
        }
        if ($icon !== '') {
            $campaignPayload['icon'] = $icon;
        }

        $campaignId = self::createCampaign($base, $token, $campaignPayload);

        // 2) Enviar la campaña inmediatamente
        $sent = self::sendCampaign($base, $token, (int)$campaignId);

        return [
            'campaign_id' => $campaignId,
            'sent' => $sent,
        ];
    }

    private static function createCampaign(string $base, string $token, array $payload): int
    {
        $ch = curl_init($base . '/push/campaigns');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("SendPulse createCampaign cURL: {$error}");
        }
        if ($code >= 400) {
            throw new RuntimeException("SendPulse createCampaign HTTP {$code}: {$response}");
        }

        $data = json_decode((string)$response, true);
        if (!is_array($data) || empty($data['data']['campaign_id'])) {
            throw new RuntimeException('SendPulse: respuesta inesperada al crear campaña: ' . (string)$response);
        }

        return (int)$data['data']['campaign_id'];
    }

    private static function sendCampaign(string $base, string $token, int $campaignId): bool
    {
        $ch = curl_init($base . '/push/campaigns/' . $campaignId . '/send');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '{}',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("SendPulse sendCampaign cURL: {$error}");
        }
        if ($code >= 400) {
            throw new RuntimeException("SendPulse sendCampaign HTTP {$code}: {$response}");
        }
        return true;
    }
}