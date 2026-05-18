<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ToutatisService
{
    /** @var array<int,string> Session ID yang dikonfigurasi (index 0–2) */
    private array $sessions;

    public function __construct()
    {
        $this->sessions = array_values(array_filter([
            Setting::getValue('instagram_session_id'),
            Setting::getValue('instagram_session_id_2'),
            Setting::getValue('instagram_session_id_3'),
        ]));
    }

    public function isConfigured(): bool
    {
        return count($this->sessions) > 0;
    }

    /**
     * Kembalikan session pertama yang tidak sedang rate-limited untuk endpoint tertentu.
     * $type: 'profile' atau 'lookup'
     */
    private function pickSession(string $type): ?string
    {
        foreach ($this->sessions as $i => $sid) {
            if (!Cache::has("toutatis_rl_{$type}:s{$i}")) {
                return $sid;
            }
        }
        return null; // semua session sedang rate-limited
    }

    private function markRateLimited(string $type, string $session, int $seconds = 120): void
    {
        $idx = array_search($session, $this->sessions, true);
        if ($idx !== false) {
            Cache::put("toutatis_rl_{$type}:s{$idx}", true, now()->addSeconds($seconds));
        }
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

        // Coba private API — rotasi antar session yang tersedia
        if ($this->isConfigured()) {
            $result = $this->fetchPrivate($username);
            if ($result['success']) {
                Cache::put($cacheKey, $result, now()->addMinutes(5));
                return $result;
            }
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
        // Coba tiap session hingga salah satu berhasil
        foreach ($this->sessions as $session) {
            if (Cache::has("toutatis_rl_profile:s" . array_search($session, $this->sessions, true))) {
                continue;
            }

            $dsUserId = explode('%3A', $session)[0];
            $headers  = [
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Cookie'          => "sessionid={$session}; ds_user_id={$dsUserId}",
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
                        $this->markRateLimited('profile', $session, $retry);
                        break; // coba session berikutnya
                    }
                    if (in_array($res->status(), [401, 403])) {
                        return ['success' => false, 'error' => 'Session ID tidak valid atau kedaluwarsa. Perbarui di Settings.'];
                    }
                    if (!$res->ok()) continue;

                    $user = $res->json('data.user');
                    if (!$user) {
                        return ['success' => false, 'error' => 'Username tidak ditemukan atau akun privat.'];
                    }

                    $result = $this->buildResult($username, $user, 'private');

                    // Fetch recent posts via feed API (web_profile_info no longer includes post content)
                    if (!empty($result['id'])) {
                        $posts = $this->fetchRecentPosts($result['id'], $headers);
                        if (!empty($posts)) {
                            $result['recent_posts'] = $posts;
                        }
                    }

                    $lookup = $this->fetchLookup($username);
                    $result['obfuscated_email'] = $lookup['obfuscated_email'] ?? null;
                    $result['obfuscated_phone'] = $lookup['obfuscated_phone'] ?? null;

                    return $result;

                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Toutatis private error', ['msg' => $e->getMessage()]);
                }
            }
        }

        return ['success' => false, 'error' => 'Semua session sedang rate-limited. Coba lagi dalam beberapa menit.', 'rate_limited' => true];
    }

    private function fetchLookup(string $username): array
    {
        $empty       = ['obfuscated_email' => null, 'obfuscated_phone' => null];
        $signedBody  = 'SIGNATURE.' . json_encode(
            ['q' => $username, 'skip_recovery' => '1'],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        // Rotasi tiap session — pakai yang tidak sedang rate-limited untuk lookup
        foreach ($this->sessions as $i => $session) {
            if (Cache::has("toutatis_rl_lookup:s{$i}")) {
                continue;
            }

            $dsUserId = explode('%3A', $session)[0];

            try {
                $res = Http::withHeaders([
                    'User-Agent'      => 'Instagram 314.0.0.35.109 Android (30/11; 420dpi; 1080x2148; samsung; SM-G975U; beyond2q; qcom; en_US; 548756459)',
                    'X-IG-App-ID'     => '124024574287414',
                    'Accept-Language' => 'en-US',
                    'Accept-Encoding' => 'gzip, deflate',
                    'Host'            => 'i.instagram.com',
                    'Cookie'          => "sessionid={$session}; ds_user_id={$dsUserId}",
                ])
                ->asForm()
                ->timeout(5)
                ->post('https://i.instagram.com/api/v1/users/lookup/', [
                    'signed_body' => $signedBody,
                ]);

                \Illuminate\Support\Facades\Log::debug('Toutatis lookup HTTP', [
                    'session_idx'  => $i,
                    'status'       => $res->status(),
                    'body_preview' => substr($res->body(), 0, 300),
                ]);

                if ($res->status() === 429) {
                    $retry = (int) ($res->header('Retry-After') ?: 120);
                    Cache::put("toutatis_rl_lookup:s{$i}", true, now()->addSeconds($retry));
                    continue; // coba session berikutnya
                }

                if (!$res->ok()) {
                    continue;
                }

                $data = $res->json();
                return [
                    'obfuscated_email' => $data['obfuscated_email'] ?? null,
                    'obfuscated_phone' => $data['obfuscated_phone'] ?? null,
                ];

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Toutatis lookup error', [
                    'session_idx' => $i,
                    'msg'         => $e->getMessage(),
                ]);
            }
        }

        return $empty;
    }

    private function fetchRecentPosts(string $userId, array $headers): array
    {
        try {
            $res = Http::withHeaders($headers)
                ->timeout(15)
                ->get("https://www.instagram.com/api/v1/feed/user/{$userId}/?count=3");

            \Illuminate\Support\Facades\Log::debug('Toutatis feed HTTP', [
                'userId'       => $userId,
                'status'       => $res->status(),
                'body_preview' => substr($res->body(), 0, 300),
            ]);

            if (!$res->ok()) {
                return [];
            }

            $items = $res->json('items') ?? [];
            $posts = [];

            foreach (array_slice($items, 0, 3) as $item) {
                $sc        = $item['code'] ?? null;
                $mediaType = $item['media_type'] ?? 1;
                $isVideo   = $mediaType === 2;
                $typename  = match ($mediaType) {
                    2       => 'GraphVideo',
                    8       => 'GraphSidecar',
                    default => 'GraphImage',
                };

                $image = $item['image_versions2']['candidates'][0]['url']
                    ?? ($item['carousel_media'][0]['image_versions2']['candidates'][0]['url'] ?? null);

                $posts[] = [
                    'shortcode' => $sc,
                    'url'       => $sc ? "https://www.instagram.com/p/{$sc}/" : null,
                    'image'     => $image,
                    'caption'   => $item['caption']['text'] ?? null,
                    'likes'     => $item['like_count']    ?? 0,
                    'comments'  => $item['comment_count'] ?? 0,
                    'is_video'  => $isVideo,
                    'type'      => $typename,
                    'timestamp' => $item['taken_at'] ?? null,
                ];
            }

            return $posts;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Toutatis feed error', [
                'userId' => $userId,
                'msg'    => $e->getMessage(),
            ]);
            return [];
        }
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

            // Kontak publik bisnis (web_profile_info menggunakan business_email/business_phone_number)
            'email'         => $user['business_email'] ?? ($user['public_email'] ?? null) ?: null,
            'phone'         => !empty($user['business_phone_number'])
                                ? "+{$user['business_country_code']} {$user['business_phone_number']}"
                                : ($user['public_phone_number'] ?? null),

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
