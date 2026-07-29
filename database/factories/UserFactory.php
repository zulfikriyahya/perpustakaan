<?php

namespace Database\Factories;

use App\Models\LevelBadge;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'avatar' => fake()->word(),
            'role' => fake()->randomElement(["siswa","pegawai","pustakawan","admin"]),
            'nis' => fake()->word(),
            'nip' => fake()->word(),
            'kelas' => fake()->word(),
            'jabatan' => fake()->word(),
            'no_telepon' => fake()->word(),
            'no_kartu_rfid' => fake()->word(),
            'status_suspend' => fake()->boolean(),
            'akumulasi_point' => fake()->numberBetween(-10000, 10000),
            'level_badge_id' => LevelBadge::factory(),
        ];
    }
}
