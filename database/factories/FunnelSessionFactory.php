<?php

namespace Database\Factories;

use App\Enums\FunnelSessionStatus;
use App\Models\Funnel;
use App\Models\FunnelSession;
use App\Models\Visitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FunnelSession>
 */
class FunnelSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visitor_id' => Visitor::factory(),
            'funnel_id' => Funnel::factory(),
            'status' => FunnelSessionStatus::Active,
            'started_at' => now(),
        ];
    }
}
