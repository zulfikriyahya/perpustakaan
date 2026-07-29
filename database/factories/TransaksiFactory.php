<?php

namespace Database\Factories;

use App\Enums\JenisTransaksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransaksiFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'jenis' => fake()->randomElement(JenisTransaksi::cases()),
            'diproses_oleh' => User::factory(),
            'tanggal' => fake()->dateTime(),
            'keterangan' => fake()->text(),
        ];
    }
}
