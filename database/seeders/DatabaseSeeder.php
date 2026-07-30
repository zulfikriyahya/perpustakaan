<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(SettingSeeder::class);

        User::factory()->create([
            'nama' => 'Admin Perpustakaan',
            'role' => 'super_admin',
            'no_telepon' => '62895351856267',
            'password' => Hash::make('password'),
        ]);

        $this->call(ShieldSeeder::class);
    }
}
