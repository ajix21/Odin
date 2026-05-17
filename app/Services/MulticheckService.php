<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;

class MulticheckService
{
    private array $platforms = [
        'Instagram'   => ['url' => 'https://www.instagram.com/{username}/',          'check' => 'Page Not Found'],
        'Twitter'     => ['url' => 'https://twitter.com/{username}',                  'check' => 'This account doesn'],
        'TikTok'      => ['url' => 'https://www.tiktok.com/@{username}',              'check' => 'Couldn\'t find this account'],
        'GitHub'      => ['url' => 'https://github.com/{username}',                   'check' => 'Not Found'],
        'Reddit'      => ['url' => 'https://www.reddit.com/user/{username}/',         'check' => 'Sorry, nobody on Reddit goes by that name'],
        'YouTube'     => ['url' => 'https://www.youtube.com/@{username}',             'check' => 'This page isn\'t available'],
        'Pinterest'   => ['url' => 'https://www.pinterest.com/{username}/',           'check' => 'Uh oh'],
        'Twitch'      => ['url' => 'https://www.twitch.tv/{username}',                'check' => 'Sorry. Unless you\'ve got a time machine'],
        'Tumblr'      => ['url' => 'https://{username}.tumblr.com/',                  'check' => 'There\'s nothing here'],
        'Medium'      => ['url' => 'https://medium.com/@{username}',                  'check' => 'Page not found'],
        'DevTo'       => ['url' => 'https://dev.to/{username}',                       'check' => '404'],
        'Keybase'     => ['url' => 'https://keybase.io/{username}',                   'check' => 'Not found'],
        'Mastodon'    => ['url' => 'https://mastodon.social/@{username}',             'check' => 'The page you looked for doesn\'t exist'],
        'GitLab'      => ['url' => 'https://gitlab.com/{username}',                   'check' => 'The page could not be found'],
        'Linktree'    => ['url' => 'https://linktr.ee/{username}',                    'check' => 'Sorry, this page isn\'t available'],
    ];

    public function check(string $username): array
    {
        $client  = new Client(['timeout' => 10, 'verify' => false, 'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]]);
        $results = [];

        $requests = function () use ($client, $username) {
            foreach ($this->platforms as $name => $cfg) {
                $url = str_replace('{username}', $username, $cfg['url']);
                yield $name => new Request('GET', $url);
            }
        };

        $pool = new Pool($client, $requests(), [
            'concurrency' => 10,
            'fulfilled'   => function ($response, $name) use (&$results) {
                $body     = (string) $response->getBody();
                $notFound = $this->platforms[$name]['check'];
                $found    = stripos($body, $notFound) === false;
                $url      = str_replace('{username}', request()->input('username', ''), $this->platforms[$name]['url']);
                $results[$name] = ['found' => $found, 'url' => $url, 'status' => $response->getStatusCode()];
            },
            'rejected'    => function ($reason, $name) use (&$results) {
                $results[$name] = ['found' => false, 'url' => '#', 'status' => 0, 'error' => true];
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        return $results;
    }
}
