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
            'judul' => fake()->sentence(3),
            'cover' => fake()->word(),
            'penulis' => fake()->name(),
            'penerbit' => fake()->company(),
            'isbn' => fake()->unique()->isbn13(),
            'barcode' => fake()->unique()->ean13(),
            'rak_id' => Rak::factory(),
            'harga_ganti' => fake()->randomFloat(2, 0, 500000),
            'stok' => fake()->numberBetween(0, 20),
            'deskripsi' => fake()->text(),
        ];
    }
}
