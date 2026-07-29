<?php

namespace Database\Factories;

use App\Models\Punishment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PunishmentLogFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'punishment_id' => Punishment::factory(),
            'tanggal_diterapkan' => fake()->dateTime(),
            // TODO: GAP-SPEC - null jika punishment masih aktif/belum berakhir (asumsi logis)
            'tanggal_berakhir' => fake()->boolean(70) ? fake()->dateTime() : null,
        ];
    }
}
