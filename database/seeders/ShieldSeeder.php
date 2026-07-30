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
 *                Pengembalian (proses pinjam-kembali, termasuk Action
 *                "Proses Pengembalian"/"Laporkan Hilang" - keduanya di-guard
 *                oleh ViewAny:Peminjaman, bukan permission terpisah, sesuai
 *                konfirmasi: hanya Admin & Pustakawan yang boleh akses sama
 *                sekali ke Resource ini).
 * - siswa/pegawai: TIDAK diberi permission Resource apa pun di panel ini.
 *   Kebutuhan "lihat point/badge/histori/denda pribadi" akan dipenuhi lewat
 *   halaman scoped-ke-user terpisah (bukan Resource CRUD Filament biasa) -
 *   BELUM dibuat di iterasi ini.
 *   TODO: GAP-SPEC - role Spatie 'siswa' dan 'pegawai' dibuat sebagai
 *   placeholder kosong sekarang supaya UserObserver/assignment role di masa
 *   depan tidak perlu migration ulang, tapi belum ada guna praktis sampai
 *   halaman "milik saya" dibuat dan permission-nya di-generate.
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

                // Peminjaman: create dipakai form manual fallback (lihat
                // PeminjamanResource\Pages\CreatePeminjaman). Update/Delete
                // TIDAK diberikan - status Peminjaman HANYA boleh berubah
                // lewat PeminjamanService (Action Proses Pengembalian/
                // Laporkan Hilang, atau cron harian), tidak ada halaman Edit
                // di Resource ini sama sekali.
                'ViewAny:Peminjaman',
                'View:Peminjaman',
                'Create:Peminjaman',

                // Pengembalian: READ-ONLY di Resource (canCreate() => false),
                // permission Create/Update/Delete tidak dipakai UI tapi tetap
                // di-generate Shield - sengaja TIDAK diberikan ke pustakawan
                // supaya tidak ada jalan lain mengubah data selain lewat
                // PeminjamanService::prosesPengembalian().
                'ViewAny:Pengembalian',
                'View:Pengembalian',
            ])->get()
        );

        // Placeholder kosong - lihat TODO: GAP-SPEC di atas class.
        Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'pegawai', 'guard_name' => 'web']);

        // Sinkronkan ulang semua user existing yang role App-nya 'admin'
        // ke Spatie role 'super_admin', jaga-jaga kalau di-run setelah ada
        // user baru sebelum Observer sempat jalan (mis. hasil import/seed).
        \App\Models\User::where('role', RoleUser::Admin)->each(
            fn($user) => $user->syncRoles(['super_admin'])
        );
    }
}
