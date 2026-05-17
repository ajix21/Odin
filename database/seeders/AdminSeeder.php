<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(['username' => 'admin'], [
            'name'      => 'Administrator',
            'email'     => 'admin@odin.local',
            'password'  => Hash::make('Admin@12345'),
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $keys = [
            ['key' => 'getcontact_token',           'is_secret' => true],
            ['key' => 'getcontact_final_key',        'is_secret' => true],
            ['key' => 'getcontact_client_device_id', 'is_secret' => true],
            ['key' => 'instagram_session_id',        'is_secret' => true],
            ['key' => 'ipinfo_token',                'is_secret' => true],
            ['key' => 'leakosint_api_token',         'is_secret' => true],
            ['key' => 'leakosint_api_url',           'is_secret' => false, 'value' => 'https://leakosintapi.com/'],
        ];

        foreach ($keys as $k) {
            Setting::firstOrCreate(
                ['key' => $k['key']],
                ['value' => $k['value'] ?? null, 'is_secret' => $k['is_secret']]
            );
        }
    }
}
