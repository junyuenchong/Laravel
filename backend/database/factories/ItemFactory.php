<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-#####')),
            'description' => $this->faker->optional()->sentence(12),
            'price_cents' => $this->faker->numberBetween(100, 200000),
            'is_active' => $this->faker->boolean(85),
        ];
    }
}

