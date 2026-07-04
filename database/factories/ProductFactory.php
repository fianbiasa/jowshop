<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucwords($this->faker->word().' '.$this->faker->word().' '.$this->faker->word());

        return [
            'created_by' => User::factory(),
            'name' => $name,
            'slug' => str($name)->slug()->append('-'.$this->faker->unique()->numberBetween(1, 999999))->toString(),
            'type' => ProductType::Digital,
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 10_000, 500_000),
            'sku' => strtoupper($this->faker->bothify('SKU-####??')),
            'status' => ProductStatus::Draft,
        ];
    }

    public function digital(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::Digital,
            'weight_grams' => null,
            'stock' => null,
        ]);
    }

    public function physical(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::Physical,
            'weight_grams' => $this->faker->numberBetween(100, 5000),
            'stock' => $this->faker->numberBetween(1, 100),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Published,
        ]);
    }
}
