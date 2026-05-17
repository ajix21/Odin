<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneInfoService
{
    public function analyze(string $phone): array
    {
        $result = ['phone' => $phone, 'valid' => false];

        try {
            $util   = PhoneNumberUtil::getInstance();
            $parsed = $util->parse($phone, null);

            if ($util->isValidNumber($parsed)) {
                $result['valid']        = true;
                $result['national']     = $util->format($parsed, PhoneNumberFormat::NATIONAL);
                $result['international'] = $util->format($parsed, PhoneNumberFormat::INTERNATIONAL);
                $result['e164']         = $util->format($parsed, PhoneNumberFormat::E164);
                $result['country']      = $util->getRegionCodeForNumber($parsed);
                $result['type']         = $util->getNumberType($parsed);
                $result['carrier']      = \libphonenumber\geocoding\PhoneNumberOfflineGeocoder::getInstance()
                                            ->getDescriptionForNumber($parsed, 'en');
            }
        } catch (NumberParseException) {
            $result['error'] = 'Format nomor tidak valid.';
            return $result;
        }

        // IPInfo lookup untuk geolocation berdasarkan country code
        $token = Setting::getValue('ipinfo_token');
        if ($token && !empty($result['country'])) {
            try {
                $res = Http::timeout(8)
                    ->withToken($token)
                    ->get("https://ipinfo.io/country/{$result['country']}");
                if ($res->ok()) {
                    $result['country_info'] = $res->json();
                }
            } catch (\Exception) {}
        }

        return $result;
    }
}
