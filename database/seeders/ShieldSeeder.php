<?php

namespace Database\Seeders;

use App\Enums\RoleUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        // Permission manual untuk halaman non-Resource (LaporanBulanan,
        // PengaturanSistem) - Shield tidak auto-generate permission untuk
        // Filament Page biasa (hanya untuk Resource).
        Permission::firstOrCreate([
            'name' => 'ViewAny:LaporanBulanan',
            'guard_name' => 'web',
        ]);

        // BARU iterasi ini - sengaja TIDAK dimasukkan ke daftar permission
        // pustakawan di bawah, karena scope Setting = Admin (dok Logic
        // Module §1). Hanya super_admin yang otomatis dapat lewat
        // syncPermissions(Permission::all()).
        Permission::firstOrCreate([
            'name' => 'ViewAny:PengaturanSistem',
            'guard_name' => 'web',
        ]);

        // BARU iterasi ini - permission manual untuk Eksemplar, karena
        // Eksemplar bukan Filament Resource sendiri (hanya RelationManager
        // di bawah BukuResource) sehingga Shield tidak auto-generate
        // permission untuknya. Dibutuhkan agar EksemplarPolicy (yang
        // dipakai EksemplarsRelationManager - termasuk tombol Import/
        // Export Eksemplar yang sekarang HANYA ada di sini, tidak lagi
        // duplikat di BukuResource header) benar-benar bisa memberi akses,
        // bukan selalu menolak karena permission belum pernah dibuat.
        foreach (
            [
                'ViewAny:Eksemplar',
                'View:Eksemplar',
                'Create:Eksemplar',
                'Update:Eksemplar',
                'Delete:Eksemplar',
                'DeleteAny:Eksemplar',
                'Restore:Eksemplar',
                'RestoreAny:Eksemplar',
                'ForceDelete:Eksemplar',
                'ForceDeleteAny:Eksemplar',
                'Replicate:Eksemplar',
                'Reorder:Eksemplar',
            ] as $permissionName
        ) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

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

                // BARU iterasi ini - Pustakawan diberi CRUD penuh untuk
                // Eksemplar, sepadan dengan akses Buku (Pustakawan adalah
                // pengelola operasional harian koleksi fisik per dok
                // Logic Module §1). Termasuk akses tombol Import/Export
                // Eksemplar di EksemplarsRelationManager.
                'ViewAny:Eksemplar',
                'View:Eksemplar',
                'Create:Eksemplar',
                'Update:Eksemplar',
                'Delete:Eksemplar',
                'DeleteAny:Eksemplar',
                'Restore:Eksemplar',
                'RestoreAny:Eksemplar',
                'ForceDelete:Eksemplar',
                'ForceDeleteAny:Eksemplar',
                'Replicate:Eksemplar',
                'Reorder:Eksemplar',

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

                'ViewAny:Denda',
                'View:Denda',
                'Update:Denda',
                'ViewAny:Kunjungan',
                'View:Kunjungan',
                'ViewAny:Transaksi',
                'View:Transaksi',

                'ViewAny:RiwayatKelasSiswa',
                'View:RiwayatKelasSiswa',

                // BARU iterasi ini - LevelBadge/Reward/Punishment adalah
                // master data (threshold badge & aturan reward/punishment),
                // diberi CRUD penuh sama seperti Buku/Kategori/Rak -
                // dikonfirmasi Pustakawan dapat akses ke 5 resource baru
                // (poin & reward) ini.
                'ViewAny:LevelBadge',
                'View:LevelBadge',
                'Create:LevelBadge',
                'Update:LevelBadge',
                'Delete:LevelBadge',
                'DeleteAny:LevelBadge',
                'Restore:LevelBadge',
                'RestoreAny:LevelBadge',
                'ForceDelete:LevelBadge',
                'ForceDeleteAny:LevelBadge',
                'Replicate:LevelBadge',
                'Reorder:LevelBadge',

                'ViewAny:Reward',
                'View:Reward',
                'Create:Reward',
                'Update:Reward',
                'Delete:Reward',
                'DeleteAny:Reward',
                'Restore:Reward',
                'RestoreAny:Reward',
                'ForceDelete:Reward',
                'ForceDeleteAny:Reward',
                'Replicate:Reward',
                'Reorder:Reward',

                'ViewAny:Punishment',
                'View:Punishment',
                'Create:Punishment',
                'Update:Punishment',
                'Delete:Punishment',
                'DeleteAny:Punishment',
                'Restore:Punishment',
                'RestoreAny:Punishment',
                'ForceDelete:Punishment',
                'ForceDeleteAny:Punishment',
                'Replicate:Punishment',
                'Reorder:Punishment',

                // RewardLog/PunishmentLog - read-only (dihasilkan otomatis
                // oleh PointService), Pustakawan hanya diberi akses lihat,
                // sama pola dengan Denda/Kunjungan di atas.
                'ViewAny:RewardLog',
                'View:RewardLog',
                'ViewAny:PunishmentLog',
                'View:PunishmentLog',

                'ViewAny:LaporanBulanan',

                // Catatan: 'ViewAny:PengaturanSistem' SENGAJA tidak
                // ditambahkan di sini - lihat komentar di atas.
            ])->get()
        );

        Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'pegawai', 'guard_name' => 'web']);

        User::where('role', RoleUser::Admin)->each(
            fn ($user) => $user->syncRoles(['super_admin'])
        );
    }
}
