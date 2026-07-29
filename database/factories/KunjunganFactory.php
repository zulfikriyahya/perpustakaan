<?php

namespace Database\Factories;

use App\Enums\SourceKunjungan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class KunjunganFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tanggal' => fake()->date(),
            'jam_tap' => fake()->time(),
            'source' => fake()->randomElement(SourceKunjungan::cases()),
        ];
    }
}
