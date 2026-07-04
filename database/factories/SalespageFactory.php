<?php

namespace Database\Factories;

use App\Models\Funnel;
use App\Models\Salespage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Salespage>
 */
class SalespageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'funnel_id' => Funnel::factory(),
            'title' => ucwords($this->faker->word().' '.$this->faker->word()),
            'content' => [
                ['type' => 'headline', 'data' => ['text' => $this->faker->sentence()]],
                ['type' => 'benefit_list', 'data' => ['items' => $this->faker->sentences(3)]],
                ['type' => 'cta', 'data' => ['label' => 'Beli Sekarang']],
            ],
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => now(),
        ]);
    }
}
