<?php

namespace Database\Factories;

use App\Enums\OrderItemType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'offer_type' => OrderItemType::Main,
            'quantity' => 1,
            'unit_price' => $this->faker->randomFloat(2, 10_000, 300_000),
        ];
    }
}
