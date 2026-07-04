<?php

namespace Database\Factories;

use App\Models\AiGenerationLog;
use App\Models\AiProviderSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiGenerationLog>
 */
class AiGenerationLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ai_provider_setting_id' => AiProviderSetting::factory(),
            'prompt' => $this->faker->sentence(),
            'response_excerpt' => $this->faker->paragraph(),
            'tokens_input' => $this->faker->numberBetween(50, 500),
            'tokens_output' => $this->faker->numberBetween(50, 500),
            'status' => 'success',
        ];
    }
}
