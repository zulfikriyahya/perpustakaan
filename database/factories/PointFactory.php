<?php

namespace Database\Factories;

use App\Models\Ref;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PointFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_type' => fake()->randomElement(["kunjungan","peminjaman","pengembalian","kerusakan","kehilangan"]),
            'nilai' => fake()->numberBetween(-10000, 10000),
            'ref_type' => fake()->word(),
            'ref_id' => Ref::factory(),
            'keterangan' => fake()->word(),
        ];
    }
}
