<?php

namespace App\Observers;

use App\Enums\GroupSetting;
use App\Enums\RoleUser;
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
 *
 * Juga menyinkronkan Spatie role berdasarkan User.role (RoleUser enum), agar
 * akses Filament/Shield selalu konsisten dengan kolom role aplikasi -
 * mapping 1:1 nama enum value <-> nama Spatie role (mis. 'pustakawan' ->
 * 'pustakawan'), KECUALI 'admin' -> 'super_admin' (dikonfirmasi user: admin
 * == super_admin). User hanya boleh punya SATU role Spatie hasil sync ini
 * di satu waktu (syncRoles, bukan assignRole) - role lain yang di-assign
 * manual di luar mapping ini (jika ada) akan ikut tercabut saat sync jalan.
 */
class UserObserver
{
    /**
     * Mapping RoleUser enum -> nama Spatie role.
     */
    private const ROLE_MAP = [
        RoleUser::Admin->value => 'super_admin',
        RoleUser::Pustakawan->value => 'pustakawan',
        RoleUser::Siswa->value => 'siswa',
        RoleUser::Pegawai->value => 'pegawai',
    ];

    public function created(User $user): void
    {
        if ($user->no_kartu_rfid) {
            $this->bumpVersion();
        }

        $this->syncRoleFromEnum($user);
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged('no_kartu_rfid')) {
            $this->bumpVersion();
        }

        if ($user->wasChanged('role')) {
            $this->syncRoleFromEnum($user);
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
            ['value' => (string) $next, 'group' => GroupSetting::Device]
        );

        // Setting::get() di-cache 5 menit (lihat Setting model) - hapus cache
        // supaya device langsung melihat versi baru, bukan menunggu TTL habis.
        Cache::forget('setting:rfid_db_ver');
    }

    protected function syncRoleFromEnum(User $user): void
    {
        $roleName = self::ROLE_MAP[$user->role->value] ?? null;

        if ($roleName === null) {
            // TODO: GAP-SPEC - enum case baru ditambahkan tapi belum dipetakan
            // di ROLE_MAP. Tidak melakukan apa pun daripada menebak role.
            return;
        }

        $user->syncRoles([$roleName]);
    }
}
