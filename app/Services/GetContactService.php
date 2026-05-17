<?php

namespace App\Services;

use App\Models\Setting;

class GetContactService
{
    const API_BASE_URL     = 'https://pbssrv-centralevents.com';
    const EP_SEARCH        = '/v2.8/search';
    const EP_NUMBER_DETAIL = '/v2.8/number-detail';
    const HMAC_SECRET_KEY  = '31426764382a642f3a6665497235466f3d236d5d785b722b4c657457442a495b494524324866782a2364292478587a78662d7a7b7578593f71703e2b7e365762';
    const ANDROID_OS       = 'android 9';
    const APP_VERSION      = '8.4.0';
    const LANG             = 'en_US';
    const COUNTRY_CODE     = 'id';
    const DEFAULT_DEVICE_ID = '174680a6f1765b5f';

    private string $token;
    private string $finalKey;
    private string $clientDeviceId;

    public function __construct()
    {
        $this->token          = Setting::getValue('getcontact_token');
        $this->finalKey       = Setting::getValue('getcontact_final_key');
        $this->clientDeviceId = Setting::getValue('getcontact_client_device_id', self::DEFAULT_DEVICE_ID);
    }

    public function isConfigured(): bool
    {
        return !empty($this->token) && !empty($this->finalKey);
    }

    public function normalizePhone(string $input): string
    {
        $input = preg_replace('/\s+/', '', $input);
        if (preg_match('/^\+62/', $input)) return $input;
        if (preg_match('/^62/', $input))   return '+' . $input;
        if (preg_match('/^0/', $input))    return '+62' . substr($input, 1);
        return '+62' . $input;
    }

    private function signature(string $timestamp, string $message): string
    {
        return base64_encode(
            hash_hmac('sha256', "$timestamp-$message", hex2bin(self::HMAC_SECRET_KEY), true)
        );
    }

    private function encrypt(string $data): string
    {
        return base64_encode(
            openssl_encrypt($data, 'aes-256-ecb', hex2bin($this->finalKey), OPENSSL_RAW_DATA)
        );
    }

    private function decrypt(string $data): ?string
    {
        $result = openssl_decrypt(
            base64_decode($data), 'aes-256-ecb', hex2bin($this->finalKey), OPENSSL_RAW_DATA
        );
        return $result !== false ? $result : null;
    }

    private function callApi(string $endpoint, array $body): object
    {
        $bodyJson  = json_encode((object) $body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = (new \DateTime())->format('Uv');
        $deviceId  = $this->clientDeviceId ?: self::DEFAULT_DEVICE_ID;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => self::API_BASE_URL . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => '{"data": "' . $this->encrypt($bodyJson) . '"}',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-os: '               . self::ANDROID_OS,
                'x-app-version: '      . self::APP_VERSION,
                'x-client-device-id: ' . $deviceId,
                'x-lang: '             . self::LANG,
                'x-token: '            . $this->token,
                'x-req-timestamp: '    . $timestamp,
                'x-country-code: id',
                'x-encrypted: 1',
                'x-req-signature: '    . $this->signature($timestamp, $bodyJson),
            ],
            CURLOPT_HEADER         => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response   = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $body       = $response !== false ? substr($response, $headerSize) : null;
        curl_close($ch);

        return (object) ['httpCode' => $httpCode, 'body' => $body];
    }

    private function search(string $phone): ?array
    {
        $res = $this->callApi(self::EP_SEARCH, [
            'countryCode' => self::COUNTRY_CODE,
            'phoneNumber' => $phone,
            'source'      => 'search',
            'token'       => $this->token,
        ]);

        if ($res->httpCode !== 200 || !$res->body) return null;
        $parsed    = json_decode($res->body, false);
        $decrypted = $this->decrypt($parsed->data ?? '');
        $data      = json_decode($decrypted, true);
        return $data['result'] ?? null;
    }

    private function numberDetail(string $phone): ?array
    {
        $res = $this->callApi(self::EP_NUMBER_DETAIL, [
            'countryCode' => self::COUNTRY_CODE,
            'phoneNumber' => $phone,
            'source'      => 'profile',
            'token'       => $this->token,
        ]);

        if ($res->httpCode !== 200 || !$res->body) return null;
        $parsed    = json_decode($res->body, false);
        $decrypted = $this->decrypt($parsed->data ?? '');
        $data      = json_decode($decrypted, true);
        return $data['result'] ?? null;
    }

    public function lookup(string $rawPhone): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'phone' => $rawPhone, 'error' => 'GetContact credential belum dikonfigurasi. Buka Settings.'];
        }

        $phone        = $this->normalizePhone($rawPhone);
        $searchResult = $this->search($phone);

        if (!$searchResult) {
            return ['success' => false, 'phone' => $phone, 'error' => 'Tidak ada hasil atau credential memerlukan verifikasi captcha.'];
        }

        $detailResult = $this->numberDetail($phone);

        return [
            'success'      => true,
            'phone'        => $phone,
            'profile'      => $searchResult['profile']            ?? [],
            'badge'        => $searchResult['badge']              ?? null,
            'spam'         => $searchResult['spamInfo']['degree'] ?? null,
            'tag_count'    => $searchResult['tagCount']           ?? 0,
            'tags'         => $detailResult['tags']               ?? [],
            'subscription' => $searchResult['subscriptionInfo']   ?? [],
        ];
    }
}
