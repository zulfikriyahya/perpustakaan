<?php

namespace Database\Factories;

use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DendaFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'peminjaman_id' => Peminjaman::factory(),
            'user_id' => User::factory(),
            'tipe' => fake()->randomElement(["keterlambatan","kerusakan","kehilangan"]),
            'nominal' => fake()->randomFloat(2, 0, 99999999.99),
            'status_lunas' => fake()->boolean(),
            'tanggal_lunas' => fake()->dateTime(),
            'keterangan' => fake()->text(),
        ];
    }
}
