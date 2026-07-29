<?php

namespace Database\Factories;

use App\Models\Buku;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeminjamanFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'transaksi_id' => Transaksi::factory(),
            'user_id' => User::factory(),
            'buku_id' => Buku::factory(),
            'tanggal_pinjam' => fake()->date(),
            'tanggal_jatuh_tempo' => fake()->date(),
            'status' => fake()->randomElement(["aktif","terlambat","selesai","hilang"]),
            'diproses_oleh' => User::factory()->create()->diproses_oleh,
        ];
    }
}
