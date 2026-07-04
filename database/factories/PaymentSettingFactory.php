<?php

namespace Database\Factories;

use App\Enums\PaymentEnvironment;
use App\Models\PaymentSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentSetting>
 */
class PaymentSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'merchant_code' => 'DS'.$this->faker->numerify('#####'),
            'api_key' => $this->faker->uuid(),
            'environment' => PaymentEnvironment::Sandbox,
            'is_active' => true,
        ];
    }
}
