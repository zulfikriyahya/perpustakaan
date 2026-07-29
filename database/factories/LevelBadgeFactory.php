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
        return [
            'nama_badge' => fake()->word(),
            'min_point' => fake()->numberBetween(-10000, 10000),
            'max_point' => fake()->numberBetween(-10000, 10000),
            'icon' => fake()->word(),
            'urutan' => fake()->numberBetween(-10000, 10000),
        ];
    }
}
