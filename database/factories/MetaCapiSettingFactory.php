<?php

namespace Database\Factories;

use App\Models\MetaCapiSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MetaCapiSetting>
 */
class MetaCapiSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pixel_id' => $this->faker->numerify('##########'),
            'access_token' => $this->faker->uuid(),
            'test_event_code' => null,
            'is_active' => true,
        ];
    }
}
