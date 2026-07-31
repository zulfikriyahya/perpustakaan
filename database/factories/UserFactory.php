<?php

namespace Database\Factories;

use App\Enums\RoleUser;
use App\Enums\StatusAkademik;
use App\Models\LevelBadge;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'avatar' => fake()->word(),
            'nama' => fake()->name(),
            'role' => fake()->randomElement(RoleUser::cases()),
            'nisn' => fake()->unique()->numerify('NISN######'),
            'nip' => fake()->unique()->numerify('NIP##########'),
            // kelas_tahun_pelajaran_id sengaja dibiarkan null (default) -
            // belum ada data master Kelas/TahunPelajaran/KTP di seeder,
            // assignment kelas dilakukan manual lewat Resource setelah
            // data akademik (Jurusan/TahunPelajaran/Kelas/KTP) dibuat.
            'status_akademik' => StatusAkademik::Aktif,
            'jabatan' => fake()->word(),
            'no_telepon' => fake()->unique()->numerify('628##########'),
            'no_kartu_rfid' => fake()->unique()->numerify('########'),
            'password' => Hash::make('password'),
            'status_suspend' => fake()->boolean(),
            'akumulasi_point' => fake()->numberBetween(-10000, 10000),
            'level_badge_id' => LevelBadge::factory(),
        ];
    }
}
