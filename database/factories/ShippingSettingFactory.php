<?php

namespace Database\Factories;

use App\Enums\ShippingProvider;
use App\Models\ShippingSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingSetting>
 */
class ShippingSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => ShippingProvider::Komerce,
            'api_key' => $this->faker->uuid(),
            'origin_area_id' => '17549',
            'origin_label' => 'Kebayoran Baru, Jakarta Selatan, DKI Jakarta',
            'is_active' => true,
        ];
    }
}
