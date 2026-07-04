<?php

namespace Database\Factories;

use App\Enums\FunnelStatus;
use App\Enums\ProductType;
use App\Models\Funnel;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Funnel>
 */
class FunnelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucwords($this->faker->word().' '.$this->faker->word());

        return [
            'created_by' => User::factory(),
            'product_id' => Product::factory()->state(['type' => ProductType::Digital]),
            'name' => $name,
            'slug' => str($name)->slug()->append('-'.$this->faker->unique()->numberBetween(1, 999999))->toString(),
            'status' => FunnelStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FunnelStatus::Published,
        ]);
    }
}
