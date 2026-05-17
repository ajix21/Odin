<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'is_secret'];
    protected $casts    = ['is_secret' => 'boolean'];

    public static function getValue(string $key, string $default = ''): string
    {
        $s = static::where('key', $key)->first();
        if (!$s) return $default;
        try {
            return $s->is_secret ? decrypt($s->value) : ($s->value ?? $default);
        } catch (\Exception) {
            return $default;
        }
    }

    public static function setValue(string $key, string $value, bool $isSecret = false): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $isSecret ? encrypt($value) : $value, 'is_secret' => $isSecret]
        );
    }

    public static function allDecrypted(): array
    {
        return static::all()->mapWithKeys(function ($s) {
            $val = '';
            try { $val = $s->is_secret ? decrypt($s->value) : ($s->value ?? ''); } catch (\Exception) {}
            return [$s->key => ['value' => $val, 'is_secret' => $s->is_secret]];
        })->toArray();
    }
}
