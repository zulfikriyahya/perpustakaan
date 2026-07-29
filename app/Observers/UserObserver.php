<?php

namespace App\Observers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Menaikkan Setting 'rfid_db_ver' setiap kali kartu RFID user berubah, supaya
 * device (ESP32 Attendance Machine) bisa mendeteksi versi baru lewat
 * GET /api/perpustakaan/rfid-list/version dan mengunduh ulang daftar kartu.
 *
 * TODO: GAP-SPEC - "perubahan" didefinisikan sebagai: no_kartu_rfid diisi/diubah,
 * ATAU user dengan kartu terisi di-soft-delete/dipulihkan/dihapus permanen
 * (kartu tersebut harus hilang dari daftar aktif di device). Perubahan pada
 * kolom lain (nama, kelas, dst) TIDAK memicu bump versi.
 */
class UserObserver
{
    public function created(User $user): void
    {
        if ($user->no_kartu_rfid) {
            $this->bumpVersion();
        }
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged('no_kartu_rfid')) {
            $this->bumpVersion();
        }
    }

    public function deleted(User $user): void
    {
        if ($user->no_kartu_rfid) {
            $this->bumpVersion();
        }
    }

    public function restored(User $user): void
    {
        if ($user->no_kartu_rfid) {
            $this->bumpVersion();
        }
    }

    protected function bumpVersion(): void
    {
        $current = (int) Setting::get('rfid_db_ver', 0);
        $next = $current + 1;

        Setting::query()->updateOrCreate(
            ['key' => 'rfid_db_ver'],
            ['value' => (string) $next, 'group' => \App\Enums\GroupSetting::Device]
        );

        // Setting::get() di-cache 5 menit (lihat Setting model) - hapus cache
        // supaya device langsung melihat versi baru, bukan menunggu TTL habis.
        Cache::forget('setting:rfid_db_ver');
    }
}
