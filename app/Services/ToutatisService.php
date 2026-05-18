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

    private const API_ENDPOINTS = [
        'https://www.instagram.com/api/v1/users/web_profile_info/?username={u}',
        'https://i.instagram.com/api/v1/users/web_profile_info/?username={u}',
    ];

    public function lookup(string $username): array
    {
        $cacheKey = "toutatis:{$username}";
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        // Coba private API jika session tersedia dan tidak sedang rate-limit
        if ($this->isConfigured() && !Cache::has("toutatis_rl:{$username}")) {
            $result = $this->fetchPrivate($username);
            if ($result['success']) {
                Cache::put($cacheKey, $result, now()->addMinutes(5));
                return $result;
            }
            // Jika 429, langsung fallback ke publik
            if (!($result['rate_limited'] ?? false)) {
                return $result; // error lain (401/403/not found) — tidak perlu fallback
            }
        }

        // Fallback: scraping publik via Facebook crawler UA (tanpa auth)
        $result = $this->fetchPublic($username);
        if ($result['success']) {
            Cache::put($cacheKey, $result, now()->addMinutes(5));
        }
        return $result;
    }

    private function fetchPrivate(string $username): array
    {
        // web_profile_info dengan browser UA + sessionid sudah mengembalikan biography lengkap
        $headers = [
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Cookie'          => "sessionid={$this->sessionId}",
            'Accept'          => '*/*',
            'Accept-Language' => 'en-US,en;q=0.9',
            'X-IG-App-ID'     => '936619743392459',
            'Referer'         => "https://www.instagram.com/{$username}/",
        ];

        foreach (self::API_ENDPOINTS as $tpl) {
            $url = str_replace('{u}', $username, $tpl);
            try {
                $res = Http::withHeaders($headers)->timeout(15)->get($url);

                \Illuminate\Support\Facades\Log::debug('Toutatis private HTTP', [
                    'url'    => $url,
                    'status' => $res->status(),
                    'body_preview' => substr($res->body(), 0, 200),
                ]);

                if ($res->status() === 429) {
                    $retry = (int) ($res->header('Retry-After') ?: 120);
                    Cache::put("toutatis_rl:{$username}", true, now()->addSeconds($retry));
                    continue;
                }
                if (in_array($res->status(), [401, 403])) {
                    return ['success' => false, 'error' => 'Session ID tidak valid atau kedaluwarsa. Perbarui di Settings.'];
                }
                if (!$res->ok()) continue;

                $user = $res->json('data.user');
                if (!$user) {
                    return ['success' => false, 'error' => 'Username tidak ditemukan atau akun privat.'];
                }

                \Illuminate\Support\Facades\Log::debug('Toutatis private user keys', [
                    'keys'      => array_keys($user),
                    'biography' => $user['biography'] ?? '(key missing)',
                    'full_name' => $user['full_name'] ?? '(key missing)',
                    'username'  => $user['username']  ?? '(key missing)',
                ]);

                $result = $this->buildResult($username, $user, 'private');

                // Tambahkan obfuscated registration email/phone via lookup endpoint
                $lookup = $this->fetchLookup($username);
                $result['obfuscated_email'] = $lookup['obfuscated_email'] ?? null;
                $result['obfuscated_phone'] = $lookup['obfuscated_phone'] ?? null;

                return $result;

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Toutatis private error', ['msg' => $e->getMessage()]);
            }
        }

        return ['success' => false, 'error' => 'Rate limit aktif.', 'rate_limited' => true];
    }

    private function fetchLookup(string $username): array
    {
        $empty = ['obfuscated_email' => null, 'obfuscated_phone' => null];

        $body = 'signed_body=SIGNATURE.' . urlencode(json_encode(
            ['q' => $username, 'skip_recovery' => '1'],
            JSON_UNESCAPED_SLASHES
        ));

        $headers = [
            'User-Agent'      => 'Instagram 314.0.0.35.109 Android (30/11; 420dpi; 1080x2148; samsung; SM-G975U; beyond2q; qcom; en_US; 548756459)',
            'Content-Type'    => 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-IG-App-ID'     => '124024574287414',
            'Accept-Language' => 'en-US',
            'Accept-Encoding' => 'gzip, deflate',
            'Cookie'          => "sessionid={$this->sessionId}",
        ];

        // 3 percobaan dengan jeda 3s → 6s jika rate-limited
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $res = Http::withHeaders($headers)
                    ->timeout(8)
                    ->post('https://i.instagram.com/api/v1/users/lookup/', $body);

                \Illuminate\Support\Facades\Log::debug('Toutatis lookup HTTP', [
                    'attempt'      => $attempt + 1,
                    'status'       => $res->status(),
                    'body_preview' => substr($res->body(), 0, 200),
                ]);

                if ($res->status() === 429) {
                    if ($attempt < 2) {
                        sleep(3 * (2 ** $attempt)); // 3s → 6s
                        continue;
                    }
                    return $empty;
                }

                if (!$res->ok()) {
                    return $empty;
                }

                $data = $res->json();
                return [
                    'obfuscated_email' => $data['obfuscated_email'] ?? null,
                    'obfuscated_phone' => $data['obfuscated_phone'] ?? null,
                ];

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Toutatis lookup error', ['msg' => $e->getMessage()]);
                return $empty;
            }
        }

        return $empty;
    }

    private function fetchPublic(string $username): array
    {
        try {
            $res = Http::withHeaders([
                'User-Agent'      => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate',
            ])->timeout(15)->get("https://www.instagram.com/{$username}/");

            if ($res->status() === 404) {
                return ['success' => false, 'error' => 'Username tidak ditemukan.'];
            }
            if (!$res->ok()) {
                return ['success' => false, 'error' => "Gagal memuat halaman publik: HTTP {$res->status()}"];
            }

            $html = $res->body();

            $get = fn(string $prop) => preg_match(
                '/property="' . preg_quote($prop, '/') . '"[^>]+content="([^"]+)"/',
                $html, $m
            ) ? html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5) : null;

            $title       = $get('og:title');
            $description = $get('og:description');
            $image       = $get('og:image');

            if (!$title && !$description) {
                return ['success' => false, 'error' => 'Tidak dapat membaca data publik (akun privat atau belum terdaftar).'];
            }

            // Parse nama dari og:title
            $fullName = $username;
            if ($title && preg_match('/^(.+?)\s*\(@[^)]+\)/', $title, $m)) {
                $fullName = trim($m[1]);
            }

            // Parse stats dari og:description: "27K Followers, 348 Following, 3,569 Posts - ..."
            $followers = $following = $posts = 0;
            if ($description && preg_match('/([\d,\.]+[KkMm]?)\s+Followers?,\s*([\d,\.]+[KkMm]?)\s+Following,\s*([\d,\.]+[KkMm]?)\s+Posts?/i', $description, $m)) {
                $followers = $this->parseCount($m[1]);
                $following = $this->parseCount($m[2]);
                $posts     = $this->parseCount($m[3]);
            }

            return [
                'success'       => true,
                'source'        => 'public',
                'id'            => null,
                'username'      => $username,
                'full_name'     => $fullName,
                'bio'           => null,
                'pronouns'      => null,
                'is_private'    => false,
                'is_verified'   => false,
                'is_business'   => false,
                'account_type'  => null,
                'profile_pic'   => $image,
                'followers'     => $followers,
                'following'     => $following,
                'posts'         => $posts,
                'highlights'    => 0,
                'external_url'  => null,
                'email'         => null,
                'phone'         => null,
                'address'       => null,
                'lat'           => null,
                'lng'           => null,
                'category'      => null,
                'recent_posts'  => [],
            ];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Toutatis public error', ['msg' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Gagal mengambil data publik Instagram.'];
        }
    }

    private function buildResult(string $username, array $user, string $source): array
    {
        // Parse business address jika ada
        $address = null;
        if (!empty($user['business_address_json'])) {
            $addr = is_array($user['business_address_json'])
                ? $user['business_address_json']
                : json_decode($user['business_address_json'], true);
            $parts = array_filter([
                $addr['street_address'] ?? null,
                $addr['city_name']      ?? ($user['city_name'] ?? null),
                $addr['zip_code']       ?? null,
            ]);
            $address = implode(', ', $parts) ?: null;
        } elseif (!empty($user['city_name'])) {
            $address = $user['city_name'];
        }

        // Account type label
        $accountTypeMap = [1 => 'Personal', 2 => 'Creator', 3 => 'Business'];
        $accountType    = $accountTypeMap[$user['account_type'] ?? 0] ?? null;

        // Pronouns
        $pronouns = !empty($user['pronouns']) ? implode(', ', (array) $user['pronouns']) : null;

        // Recent posts — ambil 3 pertama dari timeline yang sudah ada di respons
        $recentPosts = [];
        foreach (array_slice($user['edge_owner_to_timeline_media']['edges'] ?? [], 0, 3) as $edge) {
            $n  = $edge['node'] ?? [];
            $sc = $n['shortcode'] ?? null;
            $recentPosts[] = [
                'shortcode' => $sc,
                'url'       => $sc ? "https://www.instagram.com/p/{$sc}/" : null,
                'image'     => $n['display_url'] ?? ($n['thumbnail_src'] ?? null),
                'caption'   => $n['edge_media_to_caption']['edges'][0]['node']['text'] ?? null,
                'likes'     => $n['edge_liked_by']['count']        ?? 0,
                'comments'  => $n['edge_media_to_comment']['count'] ?? 0,
                'is_video'  => $n['is_video'] ?? false,
                'type'      => $n['__typename'] ?? 'GraphImage',
                'timestamp' => $n['taken_at_timestamp'] ?? null,
            ];
        }

        return [
            'success'       => true,
            'source'        => $source,

            // Identitas
            'id'            => $user['id']        ?? null,
            'username'      => $user['username'],
            'full_name'     => $user['full_name']  ?? null,
            'bio'           => $user['biography']  ?? null,
            'pronouns'      => $pronouns,
            'external_url'  => $user['external_url'] ?? null,
            'profile_pic'   => $user['profile_pic_url_hd'] ?? ($user['profile_pic_url'] ?? null),

            // Status
            'is_private'    => $user['is_private']          ?? false,
            'is_verified'   => $user['is_verified']          ?? false,
            'is_business'   => $user['is_business_account'] ?? false,
            'account_type'  => $accountType,
            'category'      => $user['category_name'] ?? null,

            // Kontak (obfuscated)
            'email'         => !empty($user['public_email'])
                                ? $this->obfuscate($user['public_email']) : null,
            'phone'         => !empty($user['public_phone_number'])
                                ? $this->obfuscate($user['public_phone_number']) : null,

            // Lokasi
            'address'       => $address,
            'lat'           => $user['latitude']  ?? null,
            'lng'           => $user['longitude'] ?? null,

            // Statistik
            'followers'     => $user['edge_followed_by']['count']            ?? 0,
            'following'     => $user['edge_follow']['count']                 ?? 0,
            'posts'         => $user['edge_owner_to_timeline_media']['count'] ?? 0,
            'highlights'    => $user['highlight_reel_count']                 ?? 0,

            // Postingan terbaru
            'recent_posts'  => $recentPosts,
        ];
    }

    private function parseCount(string $raw): int
    {
        $raw   = str_replace(',', '', trim($raw));
        $multi = 1;
        if (preg_match('/([0-9.]+)([KkMm])/i', $raw, $m)) {
            $multi = strtolower($m[2]) === 'k' ? 1000 : 1000000;
            return (int) round((float) $m[1] * $multi);
        }
        return (int) $raw;
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
