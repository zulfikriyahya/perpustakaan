<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LevelBadgeFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // TODO: GAP-SPEC - min_point dijamin < max_point (asumsi logis; sebelumnya di-random independen dan bisa terbalik)
        $min = fake()->numberBetween(0, 5000);

        return [
            'nama_badge' => fake()->word(),
            'min_point' => $min,
            'max_point' => $min + fake()->numberBetween(100, 5000),
            'icon' => fake()->word(),
            'urutan' => fake()->numberBetween(0, 10),
        ];
    }
}
