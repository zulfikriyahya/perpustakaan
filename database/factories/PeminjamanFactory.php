<?php

namespace Database\Factories;

use App\Enums\StatusPeminjaman;
use App\Models\Eksemplar;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeminjamanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'transaksi_id' => Transaksi::factory(),
            'user_id' => User::factory(),
            'eksemplar_id' => Eksemplar::factory(),
            'tanggal_pinjam' => fake()->date(),
            'tanggal_jatuh_tempo' => fake()->date(),
            'status' => fake()->randomElement(StatusPeminjaman::cases()),
            'diproses_oleh' => User::factory(),
        ];
    }
}
