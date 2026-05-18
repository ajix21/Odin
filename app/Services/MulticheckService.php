<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;

class MulticheckService
{
    /**
     * Konfigurasi platform.
     *
     * url      — URL profil untuk ditampilkan ke pengguna
     * req      — URL yang sebenarnya di-request untuk verifikasi (opsional; default = url)
     * type     — metode verifikasi:
     *              'api_200'       : found jika HTTP status == 200
     *              'api_nonempty'  : found jika JSON response array tidak kosong (GitLab)
     *              'api_them'      : found jika JSON 'them[0]' bukan null (Keybase)
     *              'http_404'      : found jika HTTP status != 404
     *              'content'       : found jika body TIDAK mengandung string 'not_found'
     * not_found — string penanda "tidak ada akun" (hanya untuk type='content')
     * reliable  — false jika platform menggunakan bot-protection / JS rendering
     */
    private array $platforms = [
        'Instagram'   => [
            'url'       => 'https://www.instagram.com/{username}/',
            'type'      => 'content',
            'not_found' => 'Page Not Found',
            'reliable'  => false,
        ],
        'X (Twitter)' => [
            'url'       => 'https://x.com/{username}',
            'type'      => 'content',
            'not_found' => 'This account doesn',
            'reliable'  => false,
        ],
        'TikTok'      => [
            'url'      => 'https://www.tiktok.com/@{username}',
            'req'      => 'https://www.tiktok.com/oembed?url=https://www.tiktok.com/@{username}',
            'type'     => 'api_200',
            'reliable' => true,
        ],
        'GitHub'      => [
            'url'      => 'https://github.com/{username}',
            'req'      => 'https://api.github.com/users/{username}',
            'type'     => 'api_200',
            'reliable' => true,
        ],
        'Reddit'      => [
            'url'       => 'https://www.reddit.com/user/{username}/',
            'type'      => 'content',
            'not_found' => 'Sorry, nobody on Reddit goes by that name',
            'reliable'  => true,
        ],
        'YouTube'     => [
            'url'       => 'https://www.youtube.com/@{username}',
            'type'      => 'content',
            'not_found' => 'This page isn\'t available',
            'reliable'  => false,
        ],
        'Pinterest'   => [
            'url'       => 'https://www.pinterest.com/{username}/',
            'type'      => 'content',
            'not_found' => 'Uh oh',
            'reliable'  => false,
        ],
        'Twitch'      => [
            'url'       => 'https://www.twitch.tv/{username}',
            'type'      => 'content',
            'not_found' => 'Sorry. Unless you\'ve got a time machine',
            'reliable'  => true,
        ],
        'Tumblr'      => [
            'url'      => 'https://www.tumblr.com/{username}',
            'type'     => 'http_404',
            'reliable' => true,
        ],
        'Medium'      => [
            'url'       => 'https://medium.com/@{username}',
            'type'      => 'content',
            'not_found' => 'Page not found',
            'reliable'  => false,  // sering mengembalikan 403 (bot-block)
        ],
        'Dev.to'      => [
            'url'      => 'https://dev.to/{username}',
            'req'      => 'https://dev.to/api/users/by_username?url={username}',
            'type'     => 'api_200',
            'reliable' => true,
        ],
        'Keybase'     => [
            'url'      => 'https://keybase.io/{username}',
            'req'      => 'https://keybase.io/_/api/1.0/user/lookup.json?usernames={username}',
            'type'     => 'api_them',
            'reliable' => true,
        ],
        'Mastodon'    => [
            'url'      => 'https://mastodon.social/@{username}',
            'req'      => 'https://mastodon.social/api/v1/accounts/lookup?acct={username}',
            'type'     => 'api_200',
            'reliable' => true,
        ],
        'GitLab'      => [
            'url'      => 'https://gitlab.com/{username}',
            'req'      => 'https://gitlab.com/api/v4/users?username={username}',
            'type'     => 'api_nonempty',
            'reliable' => true,
        ],
        'Linktree'    => [
            'url'       => 'https://linktr.ee/{username}',
            'type'      => 'content',
            'not_found' => 'Sorry, this page isn\'t available',
            'reliable'  => true,
        ],
        'Spotify'     => [
            'url'      => 'https://open.spotify.com/user/{username}',
            'type'     => 'http_404',
            'reliable' => true,
        ],
        'Snapchat'    => [
            'url'       => 'https://www.snapchat.com/add/{username}',
            'type'      => 'content',
            'not_found' => 'Sorry, this page isn\'t available',
            'reliable'  => true,
        ],
        'SoundCloud'  => [
            'url'      => 'https://soundcloud.com/{username}',
            'type'     => 'http_404',
            'reliable' => true,
        ],
    ];

    // Header per kategori request agar tidak mengganggu API endpoint
    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    private const HEADERS_PAGE = [
        'User-Agent'      => self::UA,
        'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language' => 'en-US,en;q=0.9',
    ];

    private const HEADERS_API = [
        'User-Agent' => self::UA,
        'Accept'     => 'application/json',
    ];

    public function check(string $username): array
    {
        $client  = new Client([
            'timeout'         => 12,
            'verify'          => true,
            'http_errors'     => false,  // 4xx/5xx tetap masuk fulfilled, bukan rejected
            'allow_redirects' => ['max' => 5, 'track_redirects' => false],
        ]);
        $results = [];

        $requests = function () use ($username) {
            foreach ($this->platforms as $name => $cfg) {
                $isApi   = isset($cfg['req']);
                $tpl     = $cfg['req'] ?? $cfg['url'];
                $url     = str_replace('{username}', rawurlencode($username), $tpl);
                $headers = $isApi ? self::HEADERS_API : self::HEADERS_PAGE;
                yield $name => new Request('GET', $url, $headers);
            }
        };

        $pool = new Pool($client, $requests(), [
            'concurrency' => 12,
            'fulfilled'   => function ($response, $name) use (&$results, $username) {
                $cfg    = $this->platforms[$name];
                $status = $response->getStatusCode();
                $url    = str_replace('{username}', $username, $cfg['url']);

                $needsBody = in_array($cfg['type'], ['content', 'api_nonempty', 'api_them']);
                $body      = $needsBody ? (string) $response->getBody() : '';
                $found     = $this->determineFound($cfg, $status, $body);
                $blocked   = in_array($status, [403, 429, 500, 502, 503]);

                $results[$name] = [
                    'found'    => $found,
                    'url'      => $url,
                    'status'   => $status,
                    'reliable' => $cfg['reliable'],
                    'method'   => $this->methodLabel($cfg),
                    'blocked'  => $blocked,
                ];
            },
            'rejected' => function ($reason, $name) use (&$results, $username) {
                $cfg = $this->platforms[$name];
                $results[$name] = [
                    'found'    => false,
                    'url'      => str_replace('{username}', $username, $cfg['url']),
                    'status'   => 0,
                    'reliable' => $cfg['reliable'],
                    'error'    => true,
                    'method'   => $this->methodLabel($cfg),
                ];
            },
        ]);

        $pool->promise()->wait();

        uasort($results, fn($a, $b) => $b['found'] <=> $a['found']);

        return $results;
    }

    private function determineFound(array $cfg, int $status, string $body): bool
    {
        // 403/429/5xx = bot-blocked, tidak bisa tentukan → anggap tidak ada
        $blocked = in_array($status, [403, 429, 500, 502, 503]);

        return match ($cfg['type']) {
            'api_200'      => $status === 200,
            'http_404'     => $status === 200,  // 404 = tidak ada, status lain (200) = ada
            'content'      => !$blocked && $status !== 404 && stripos($body, $cfg['not_found']) === false,
            'api_nonempty' => $status === 200 && !empty(json_decode($body, true)),
            'api_them'     => $status === 200 && !empty(json_decode($body, true)['them'][0] ?? null),
            default        => false,
        };
    }

    private function methodLabel(array $cfg): string
    {
        return match ($cfg['type']) {
            'api_200', 'api_nonempty', 'api_them' => 'API',
            'http_404'                             => 'HTTP',
            'content'                              => 'Content',
            default                                => '—',
        };
    }
}
