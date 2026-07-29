<?php

namespace Database\Factories;

use App\Enums\GroupSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'value' => fake()->text(),
            'group' => fake()->randomElement(GroupSetting::cases()),
            'keterangan' => fake()->word(),
        ];
    }
}

