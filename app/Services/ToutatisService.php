<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ToutatisService
{
    private string $sessionId;

    public function __construct()
    {
        $this->sessionId = Setting::getValue('instagram_session_id');
    }

    public function isConfigured(): bool
    {
        return !empty($this->sessionId);
    }

    public function lookup(string $username): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Instagram Session ID belum dikonfigurasi di Settings.'];
        }

        $cacheKey = "toutatis:{$username}";
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $result = $this->fetchProfile($username);

        if ($result['success']) {
            Cache::put($cacheKey, $result, now()->addMinutes(5));
        }

        return $result;
    }

    private function fetchProfile(string $username): array
    {
        $headers = [
            'User-Agent' => 'Instagram 64.0.0.14.96',
            'Cookie'     => "sessionid={$this->sessionId}",
            'Accept'     => 'application/json',
        ];

        try {
            $res = Http::withHeaders($headers)->timeout(15)
                ->get("https://www.instagram.com/api/v1/users/web_profile_info/?username={$username}");

            if (!$res->ok()) {
                return ['success' => false, 'error' => "Instagram API error: HTTP {$res->status()}"];
            }

            $data = $res->json();
            $user = $data['data']['user'] ?? null;

            if (!$user) {
                return ['success' => false, 'error' => 'Username tidak ditemukan atau akun privat.'];
            }

            return [
                'success'       => true,
                'username'      => $user['username'],
                'full_name'     => $user['full_name'],
                'bio'           => $user['biography'],
                'is_private'    => $user['is_private'],
                'is_verified'   => $user['is_verified'],
                'profile_pic'   => $user['profile_pic_url_hd'] ?? $user['profile_pic_url'],
                'followers'     => $user['edge_followed_by']['count'] ?? 0,
                'following'     => $user['edge_follow']['count'] ?? 0,
                'posts'         => $user['edge_owner_to_timeline_media']['count'] ?? 0,
                'external_url'  => $user['external_url'],
                'email'         => isset($user['public_email']) ? $this->obfuscate($user['public_email']) : null,
                'phone'         => isset($user['public_phone_number']) ? $this->obfuscate($user['public_phone_number']) : null,
                'business'      => $user['is_business_account'] ?? false,
                'category'      => $user['category_name'] ?? null,
                'id'            => $user['id'],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function obfuscate(string $value): string
    {
        if (str_contains($value, '@')) {
            [$local, $domain] = explode('@', $value, 2);
            return substr($local, 0, 2) . str_repeat('*', max(strlen($local) - 4, 2)) . substr($local, -2) . '@' . $domain;
        }
        return substr($value, 0, 3) . str_repeat('*', max(strlen($value) - 6, 2)) . substr($value, -3);
    }
}
