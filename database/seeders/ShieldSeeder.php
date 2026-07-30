<?php

namespace Database\Seeders;

use App\Enums\RoleUser;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Mapping Role Spatie -> Permission, sesuai scope akses di dokumen
 * Logic Module Perpustakaan v1.0 poin 1 (dikonfirmasi user):
 *
 * - super_admin (User.role = admin): akses penuh semua permission.
 * - pustakawan : full CRUD Buku/Kategori/Rak (master data) + Peminjaman/
 *                Pengembalian (proses pinjam-kembali) + Denda (tandai
 *                lunas - TODO: ASUMSI, lihat DendaResource) + lihat
 *                Kunjungan/Transaksi (operasional harian, read-only).
 * - siswa/pegawai: TIDAK diberi permission Resource apa pun di panel ini.
 *
 * ITERASI INI (Denda/Kunjungan/Transaksi/Setting): Delete/DeleteAny untuk
 * ketiga Resource log (Denda/Kunjungan/Transaksi) SENGAJA tidak diberikan
 * ke pustakawan - hanya super_admin (dikonfirmasi user: "read-only tapi
 * Admin boleh hapus"). SettingResource sepenuhnya ditahan dulu (belum
 * dibuat) sampai SettingObserver.php diverifikasi.
 */
class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        // guard 'web' adalah default Spatie & Filament panel ini.
        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);
        $superAdmin->syncPermissions(Permission::all());

        $pustakawan = Role::firstOrCreate([
            'name' => 'pustakawan',
            'guard_name' => 'web',
        ]);
        $pustakawan->syncPermissions(
            Permission::whereIn('name', [
                'ViewAny:Buku',
                'View:Buku',
                'Create:Buku',
                'Update:Buku',
                'Delete:Buku',
                'DeleteAny:Buku',
                'Restore:Buku',
                'RestoreAny:Buku',
                'ForceDelete:Buku',
                'ForceDeleteAny:Buku',
                'Replicate:Buku',
                'Reorder:Buku',

                'ViewAny:Kategori',
                'View:Kategori',
                'Create:Kategori',
                'Update:Kategori',
                'Delete:Kategori',
                'DeleteAny:Kategori',
                'Restore:Kategori',
                'RestoreAny:Kategori',
                'ForceDelete:Kategori',
                'ForceDeleteAny:Kategori',
                'Replicate:Kategori',
                'Reorder:Kategori',

                'ViewAny:Rak',
                'View:Rak',
                'Create:Rak',
                'Update:Rak',
                'Delete:Rak',
                'DeleteAny:Rak',
                'Restore:Rak',
                'RestoreAny:Rak',
                'ForceDelete:Rak',
                'ForceDeleteAny:Rak',
                'Replicate:Rak',
                'Reorder:Rak',

                'ViewAny:Peminjaman',
                'View:Peminjaman',
                'Create:Peminjaman',

                'ViewAny:Pengembalian',
                'View:Pengembalian',
                'Update:Pengembalian',

                // BARU iterasi ini - lihat catatan class di atas.
                'ViewAny:Denda',
                'View:Denda',
                'Update:Denda',
                'ViewAny:Kunjungan',
                'View:Kunjungan',
                'ViewAny:Transaksi',
                'View:Transaksi',
            ])->get()
        );

        // Placeholder kosong - role belum punya guna praktis (lihat catatan lama).
        Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'pegawai', 'guard_name' => 'web']);

        \App\Models\User::where('role', RoleUser::Admin)->each(
            fn($user) => $user->syncRoles(['super_admin'])
        );
    }
}
