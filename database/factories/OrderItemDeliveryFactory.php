<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\OrderItemDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrderItemDelivery>
 */
class OrderItemDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'download_token' => Str::random(48),
            'max_downloads' => 5,
            'download_count' => 0,
            'expires_at' => now()->addDays(30),
            'delivered_at' => now(),
        ];
    }
}
