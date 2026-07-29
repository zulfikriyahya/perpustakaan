<?php

namespace App\Models;

use App\Enums\GroupSetting;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'key',
        'value',
        'group',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'group' => GroupSetting::class,
        ];
    }

    /**
     * Ambil nilai Setting berdasarkan key, dengan fallback default.
     * Di-cache 5 menit agar tidak query berulang di proses batch (cron, dsb).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting:{$key}", 300, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }
}
