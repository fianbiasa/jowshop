<?php

namespace Database\Factories;

use App\Enums\FunnelEventType;
use App\Models\FunnelEvent;
use App\Models\FunnelSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FunnelEvent>
 */
class FunnelEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'funnel_session_id' => FunnelSession::factory(),
            'event_type' => FunnelEventType::SalespageView,
            'external_event_id' => $this->faker->uuid(),
            'occurred_at' => now(),
        ];
    }
}
