<?php

namespace Database\Factories;

use App\Enums\StatusEksemplar;
use App\Models\Buku;
use App\Models\Rak;
use Illuminate\Database\Eloquent\Factories\Factory;

class EksemplarFactory extends Factory
{
    public function definition(): array
    {
        return [
            'buku_id' => Buku::factory(),
            'barcode' => fake()->unique()->ean13(),
            'rak_id' => Rak::factory(),
            'status' => StatusEksemplar::Tersedia,
        ];
    }
}
