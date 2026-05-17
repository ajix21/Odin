<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    private array $settingsConfig = [
        'getcontact_token'            => ['label' => 'GetContact Token',           'secret' => true,  'group' => 'GetContact'],
        'getcontact_final_key'        => ['label' => 'GetContact Final Key',        'secret' => true,  'group' => 'GetContact'],
        'getcontact_client_device_id' => ['label' => 'GetContact Client Device ID', 'secret' => true,  'group' => 'GetContact'],
        'leakosint_api_token'         => ['label' => 'LeakOSINT API Token',         'secret' => true,  'group' => 'LeakOSINT'],
        'leakosint_api_url'           => ['label' => 'LeakOSINT API URL',           'secret' => false, 'group' => 'LeakOSINT'],
        'ipinfo_token'                => ['label' => 'IPInfo Token',                'secret' => true,  'group' => 'Phone OSINT'],
        'instagram_session_id'        => ['label' => 'Instagram Session ID',        'secret' => true,  'group' => 'Toutatis'],
    ];

    public function index()
    {
        $current = Setting::allDecrypted();
        $config  = $this->settingsConfig;
        return view('settings.index', compact('current', 'config'));
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach ($this->settingsConfig as $key => $cfg) {
            $rules[$key] = 'nullable|string|max:1000';
        }
        $request->validate($rules);

        foreach ($this->settingsConfig as $key => $cfg) {
            if ($request->has($key)) {
                $value = $request->input($key, '');
                if ($cfg['secret'] && empty($value)) continue;
                Setting::setValue($key, $value, $cfg['secret']);
            }
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
