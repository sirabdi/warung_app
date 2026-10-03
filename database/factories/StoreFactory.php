<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    protected $model = Store::class;

    /** A paying store with a month left. */
    public function definition(): array
    {
        return [
            'name' => 'Warung '.fake()->firstName(),
            'phone' => '0812'.fake()->numerify('########'),
            'address' => fake()->streetAddress(),
            'subscription_ends_at' => now()->addMonth(),
        ];
    }

    /** Registered, never paid. */
    public function unpaid(): static
    {
        return $this->state(['subscription_ends_at' => null]);
    }

    public function expired(): static
    {
        return $this->state(['subscription_ends_at' => now()->subDay()]);
    }
}
