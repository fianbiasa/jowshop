<?php

namespace Database\Factories;

use App\Enums\MetaCapiEventStatus;
use App\Models\FunnelEvent;
use App\Models\MetaCapiEventLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MetaCapiEventLog>
 */
class MetaCapiEventLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'funnel_event_id' => FunnelEvent::factory(),
            'event_name' => 'PageView',
            'status' => MetaCapiEventStatus::Pending,
            'attempts' => 0,
        ];
    }
}
