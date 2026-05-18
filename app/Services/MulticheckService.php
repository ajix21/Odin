<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;

class MulticheckService
{
    /**
     * 'check'  — string yang muncul di body ketika akun TIDAK ditemukan
     * 'status' — jika true, gunakan HTTP 404 sebagai penentu utama (lebih akurat)
     * 'reliable' — false jika platform memblokir bot / selalu redirect ke login
     */
    private array $platforms = [
        'Instagram' => ['url' => 'https://www.instagram.com/{username}/',         'check' => 'Page Not Found',                          'status' => true,  'reliable' => false],
        'X (Twitter)'=>['url' => 'https://x.com/{username}',                      'check' => 'This account doesn',                      'status' => false, 'reliable' => false],
        'TikTok'    => ['url' => 'https://www.tiktok.com/@{username}',            'check' => 'Couldn\'t find this account',              'status' => false, 'reliable' => false],
        'GitHub'    => ['url' => 'https://github.com/{username}',                  'check' => 'Not Found',                               'status' => true,  'reliable' => true ],
        'Reddit'    => ['url' => 'https://www.reddit.com/user/{username}/',        'check' => 'Sorry, nobody on Reddit goes by that name','status' => true,  'reliable' => true ],
        'YouTube'   => ['url' => 'https://www.youtube.com/@{username}',            'check' => 'This page isn\'t available',              'status' => false, 'reliable' => false],
        'Pinterest' => ['url' => 'https://www.pinterest.com/{username}/',          'check' => 'Uh oh',                                   'status' => false, 'reliable' => false],
        'Twitch'    => ['url' => 'https://www.twitch.tv/{username}',               'check' => 'Sorry. Unless you\'ve got a time machine','status' => false, 'reliable' => true ],
        'Tumblr'    => ['url' => 'https://www.tumblr.com/{username}',              'check' => 'There\'s nothing here',                   'status' => true,  'reliable' => true ],
        'Medium'    => ['url' => 'https://medium.com/@{username}',                 'check' => 'Page not found',                          'status' => false, 'reliable' => true ],
        'Dev.to'    => ['url' => 'https://dev.to/{username}',                      'check' => '404',                                     'status' => true,  'reliable' => true ],
        'Keybase'   => ['url' => 'https://keybase.io/{username}',                  'check' => 'Not found',                               'status' => true,  'reliable' => true ],
        'Mastodon'  => ['url' => 'https://mastodon.social/@{username}',            'check' => 'The page you looked for doesn\'t exist',  'status' => true,  'reliable' => true ],
        'GitLab'    => ['url' => 'https://gitlab.com/{username}',                  'check' => 'The page could not be found',             'status' => true,  'reliable' => true ],
        'Linktree'  => ['url' => 'https://linktr.ee/{username}',                   'check' => 'Sorry, this page isn\'t available',       'status' => false, 'reliable' => true ],
        'Spotify'   => ['url' => 'https://open.spotify.com/user/{username}',       'check' => 'Page not found',                          'status' => true,  'reliable' => true ],
        'Snapchat'  => ['url' => 'https://www.snapchat.com/add/{username}',        'check' => 'Sorry, this page isn\'t available',       'status' => false, 'reliable' => true ],
        'SoundCloud'=> ['url' => 'https://soundcloud.com/{username}',              'check' => '404',                                     'status' => true,  'reliable' => true ],
    ];

    public function check(string $username): array
    {
        $client  = new Client([
            'timeout'         => 12,
            'verify'          => true,
            'allow_redirects' => ['max' => 5, 'track_redirects' => false],
            'headers'         => ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'],
        ]);
        $results = [];

        $requests = function () use ($client, $username) {
            foreach ($this->platforms as $name => $cfg) {
                $url = str_replace('{username}', rawurlencode($username), $cfg['url']);
                yield $name => new Request('GET', $url);
            }
        };

        $pool = new Pool($client, $requests(), [
            'concurrency' => 12,
            'fulfilled'   => function ($response, $name) use (&$results, $username) {
                $cfg     = $this->platforms[$name];
                $status  = $response->getStatusCode();
                $url     = str_replace('{username}', $username, $cfg['url']);
                $found   = false;

                if ($cfg['status'] && $status === 404) {
                    // HTTP 404 = definitif tidak ada akun
                    $found = false;
                } else {
                    $body  = (string) $response->getBody();
                    $found = stripos($body, $cfg['check']) === false;
                }

                $results[$name] = [
                    'found'    => $found,
                    'url'      => $url,
                    'status'   => $status,
                    'reliable' => $cfg['reliable'],
                ];
            },
            'rejected' => function ($reason, $name) use (&$results, $username) {
                $results[$name] = [
                    'found'    => false,
                    'url'      => str_replace('{username}', $username, $this->platforms[$name]['url']),
                    'status'   => 0,
                    'reliable' => false,
                    'error'    => true,
                ];
            },
        ]);

        $pool->promise()->wait();

        // Urutkan: ditemukan dulu, lalu tidak ditemukan
        uasort($results, fn($a, $b) => $b['found'] <=> $a['found']);

        return $results;
    }
}
