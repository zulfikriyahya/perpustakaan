<?php

namespace Database\Factories;

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
            'jenis' => fake()->randomElement(["peminjaman","kunjungan","pembayaran_denda"]),
            'diproses_oleh' => User::factory()->create()->diproses_oleh,
            'tanggal' => fake()->dateTime(),
            'keterangan' => fake()->text(),
        ];
    }
}
