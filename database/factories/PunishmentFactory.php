<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PunishmentFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->word(),
            'deskripsi' => fake()->text(),
            'threshold_point_minus' => fake()->numberBetween(-10000, 10000),
            'durasi_suspend_hari' => fake()->numberBetween(-10000, 10000),
            'aktif' => fake()->boolean(),
        ];
    }
}
