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
            'threshold_point_minus' => fake()->numberBetween(-10000, -1),
            // durasi_suspend_hari harus positif - dipakai sebagai
            // now()->addDays() di PointService::cekPunishment(). Nilai
            // negatif sebelumnya menghasilkan tanggal_berakhir di masa lalu,
            // membuat punishment otomatis "berakhir" saat baru dibuat.
            'durasi_suspend_hari' => fake()->numberBetween(1, 30),
            'aktif' => fake()->boolean(),
        ];
    }
}
