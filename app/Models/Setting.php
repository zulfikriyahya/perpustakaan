<?php

namespace App\Models;

use App\Enums\GroupSetting;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class Setting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'key',
        'value',
        'group',
        'keterangan',
        'is_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'group' => GroupSetting::class,
            'is_encrypted' => 'boolean',
        ];
    }

    /**
     * Ambil nilai Setting berdasarkan key, dengan fallback default.
     * Di-cache 5 menit agar tidak query berulang di proses batch (cron, dsb).
     *
     * Jika baris ditandai is_encrypted, value didekripsi transparan di sini
     * SEBELUM di-cache (Redis internal aplikasi - sudah dipercaya sebagai
     * store, konsisten dengan pola cache Setting lain yang sudah ada).
     *
     * Jika dekripsi gagal (mis. APP_KEY berubah sejak value dienkripsi),
     * fallback ke $default dan dicatat sebagai warning - TIDAK melempar
     * exception, supaya satu Setting korup tidak menjatuhkan seluruh
     * request (mis. config() device saat startup).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting:{$key}", 300, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (! $setting || $setting->value === null) {
                return $default;
            }

            if ($setting->is_encrypted) {
                try {
                    return Crypt::decryptString($setting->value);
                } catch (\Exception $e) {
                    Log::warning("Setting::get: gagal dekripsi key '{$key}', fallback ke default.", ['error' => $e->getMessage()]);

                    return $default;
                }
            }

            return $setting->value;
        });
    }

    /**
     * Simpan Setting dengan value dienkripsi (Crypt::encryptString, kunci
     * APP_KEY) - dipakai KHUSUS untuk grup Kredensial (Aturan poin 17: WA
     * Gateway secret & Device Gateway API key). Jangan panggil untuk
     * Setting non-secret - enkripsi/dekripsi menambah overhead percuma.
     */
    public static function setEncrypted(string $key, string $value, GroupSetting $group, ?string $keterangan = null): static
    {
        return static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => Crypt::encryptString($value),
                'group' => $group,
                'keterangan' => $keterangan,
                'is_encrypted' => true,
            ]
        );
    }
}
