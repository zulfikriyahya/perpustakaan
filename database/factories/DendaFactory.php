<?php

namespace Database\Factories;

use App\Enums\TipeDenda;
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
        $statusLunas = fake()->boolean();

        return [
            'peminjaman_id' => Peminjaman::factory(),
            'user_id' => User::factory(),
            'tipe' => fake()->randomElement(TipeDenda::cases()),
            'nominal' => fake()->randomFloat(2, 5000, 500000),
            'status_lunas' => $statusLunas,
            'tanggal_lunas' => $statusLunas ? fake()->dateTime() : null,
            'keterangan' => fake()->text(),
        ];
    }
}
