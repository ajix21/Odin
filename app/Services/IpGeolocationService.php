<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class IpGeolocationService
{
    public function lookup(string $ip): array
    {
        $result = ['ip' => $ip, 'success' => false];

        try {
            $res = Http::timeout(10)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,message,continent,continentCode,country,countryCode,region,regionName,city,district,zip,lat,lon,timezone,offset,currency,isp,org,as,asname,reverse,mobile,proxy,hosting,query',
            ]);

            if ($res->ok()) {
                $data = $res->json();
                if (($data['status'] ?? '') === 'success') {
                    $result = array_merge($result, $data, ['success' => true]);
                } else {
                    $result['error'] = $data['message'] ?? 'Lookup gagal.';
                }
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }
}
