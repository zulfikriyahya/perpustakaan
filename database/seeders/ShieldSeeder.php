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

                'ViewAny:Denda',
                'View:Denda',
                'Update:Denda',
                'ViewAny:Kunjungan',
                'View:Kunjungan',
                'ViewAny:Transaksi',
                'View:Transaksi',

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
