<?php

namespace Database\Factories;

use App\Models\ShippingArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingArea>
 */
class ShippingAreaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $province = fake()->unique()->city();
        $city = fake()->city();
        $district = fake()->citySuffix();
        $subdistrict = fake()->streetName();
        $zip = fake()->numerify('#####');

        return [
            'id' => fake()->unique()->numberBetween(10000, 99999),
            'rajaongkir_province_id' => fake()->numberBetween(1, 34),
            'rajaongkir_city_id' => fake()->numberBetween(1, 514),
            'rajaongkir_district_id' => fake()->numberBetween(1, 7000),
            'province_name' => $province,
            'city_name' => $city,
            'district_name' => $district,
            'subdistrict_name' => $subdistrict,
            'zip_code' => $zip,
            'label' => "{$subdistrict}, {$district}, {$city}, {$province}, {$zip}",
        ];
    }
}
