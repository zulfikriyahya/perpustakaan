<?php

namespace Database\Factories;

use App\Models\Rak;
use Illuminate\Database\Eloquent\Factories\Factory;

class BukuFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'judul' => fake()->word(),
            'cover' => fake()->word(),
            'penulis' => fake()->word(),
            'penerbit' => fake()->word(),
            'isbn' => fake()->word(),
            'barcode' => fake()->word(),
            'rak_id' => Rak::factory(),
            'harga_ganti' => fake()->randomFloat(2, 0, 99999999.99),
            'stok' => fake()->numberBetween(-10000, 10000),
            'deskripsi' => fake()->text(),
        ];
    }
}
