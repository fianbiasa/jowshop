<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductDigitalAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductDigitalAsset>
 */
class ProductDigitalAssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory()->digital(),
            'file_path' => 'digital-assets/'.$this->faker->uuid().'.pdf',
            'license_type' => 'none',
            'max_downloads' => 5,
        ];
    }
}
