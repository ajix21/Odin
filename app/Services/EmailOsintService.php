<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EmailOsintService
{
    public function analyze(string $email): array
    {
        $result = ['email' => $email, 'valid' => false, 'mx' => [], 'gravatar' => null, 'disify' => null];

        // Disify validation
        try {
            $res = Http::timeout(10)->get("https://www.disify.com/api/email/{$email}");
            if ($res->ok()) {
                $data = $res->json();
                $result['disify'] = $data;
                $result['valid']  = $data['format'] ?? false;
                $result['disposable'] = $data['disposable'] ?? false;
                $result['dns']    = $data['dns'] ?? false;
            }
        } catch (\Exception) {}

        // Gravatar
        $hash = md5(strtolower(trim($email)));
        $gravatarUrl = "https://www.gravatar.com/avatar/{$hash}?d=404";
        try {
            $res = Http::timeout(8)->get($gravatarUrl);
            if ($res->ok()) {
                $result['gravatar'] = "https://www.gravatar.com/avatar/{$hash}?s=200";
                $result['gravatar_profile'] = "https://www.gravatar.com/{$hash}.json";
            }
        } catch (\Exception) {}

        // MX records
        try {
            $domain = substr($email, strpos($email, '@') + 1);
            $mx = [];
            getmxrr($domain, $mx);
            $result['mx']     = $mx;
            $result['domain'] = $domain;
        } catch (\Exception) {}

        return $result;
    }
}
