<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $cost = fake()->numberBetween(1, 20) * 500;

        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'category_id' => Category::factory(),
            'cost_price' => $cost,
            'sell_price' => $cost + 1000,
            'stock' => 20,
        ];
    }
}
