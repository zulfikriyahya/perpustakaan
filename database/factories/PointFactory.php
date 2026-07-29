<?php

namespace Database\Factories;

use App\Enums\EventTypePoint;
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
            'event_type' => fake()->randomElement(EventTypePoint::cases()),
            'nilai' => fake()->numberBetween(-100, 100),
            'ref_type' => fake()->randomElement(['peminjaman', 'pengembalian', 'kunjungan']),
            'ref_id' => fake()->uuid(),
            'keterangan' => fake()->word(),
        ];
    }
}
