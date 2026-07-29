<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SettingSeeder::class);

        User::factory()->create([
            'nama' => 'Admin Perpustakaan',
            'role' => 'admin',
            'no_telepon' => '628123456789',
            'password' => Hash::make('password'),
        ]);
    }
}
