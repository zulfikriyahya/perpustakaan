<?php

namespace Database\Factories;

use App\Enums\KondisiBuku;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PengembalianFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'peminjaman_id' => Peminjaman::factory(),
            'tanggal_kembali' => fake()->date(),
            'kondisi' => fake()->randomElement(KondisiBuku::cases()),
            'catatan' => fake()->text(),
            'diproses_oleh' => User::factory(),
        ];
    }
}
