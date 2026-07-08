<?php

namespace Database\Factories;

use App\Models\CdnSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CdnSetting>
 */
class CdnSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pull_zone_url' => 'https://'.$this->faker->domainWord().'.b-cdn.net',
            'is_active' => true,
        ];
    }
}
