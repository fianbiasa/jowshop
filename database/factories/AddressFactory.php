<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'recipient_name' => $this->faker->name(),
            'phone' => $this->faker->numerify('08##########'),
            'province' => $this->faker->randomElement(['DKI Jakarta', 'Jawa Barat', 'Jawa Tengah', 'Jawa Timur', 'Bali']),
            'city' => $this->faker->city(),
            'district' => $this->faker->citySuffix(),
            'postal_code' => $this->faker->numerify('#####'),
            'address_line' => $this->faker->streetAddress(),
        ];
    }
}
