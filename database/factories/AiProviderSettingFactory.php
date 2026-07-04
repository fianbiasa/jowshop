<?php

namespace Database\Factories;

use App\Enums\AiProvider;
use App\Models\AiProviderSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiProviderSetting>
 */
class AiProviderSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'provider' => AiProvider::OpenAi,
            'label' => 'OpenAI Utama',
            'api_key' => 'sk-test-'.$this->faker->uuid(),
            'default_model' => 'gpt-4.1',
            'is_default' => true,
            'is_active' => true,
        ];
    }
}
