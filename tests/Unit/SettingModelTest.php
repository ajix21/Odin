<?php

namespace Tests\Unit;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_getValue_returns_default_when_key_missing(): void
    {
        $this->assertEquals('default', Setting::getValue('missing_key', 'default'));
    }

    public function test_setValue_and_getValue_plain(): void
    {
        Setting::setValue('test_key', 'hello');
        $this->assertEquals('hello', Setting::getValue('test_key'));
    }

    public function test_setValue_and_getValue_secret(): void
    {
        Setting::setValue('secret_key', 'my_secret', true);
        $this->assertEquals('my_secret', Setting::getValue('secret_key'));
        $raw = Setting::where('key', 'secret_key')->value('value');
        $this->assertNotEquals('my_secret', $raw);
    }
}
