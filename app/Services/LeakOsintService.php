<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class LeakOsintService
{
    public function search(string $query, int $limit = 100, string $lang = 'en'): array
    {
        $token  = Setting::getValue('leakosint_api_token');
        $apiUrl = Setting::getValue('leakosint_api_url', 'https://leakosintapi.com/');

        if (empty($token)) {
            throw new \RuntimeException('LeakOSINT API token belum dikonfigurasi di Settings.');
        }

        $response = Http::timeout(30)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($apiUrl, [
                'token'   => $token,
                'request' => $query,
                'limit'   => $limit,
                'lang'    => $lang,
            ]);

        if (!$response->ok()) {
            throw new \RuntimeException("LeakOSINT API error: HTTP {$response->status()}");
        }

        return $response->json();
    }
}
