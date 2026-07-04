<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Funnel;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 10_000, 300_000);

        return [
            'funnel_id' => Funnel::factory(),
            'customer_id' => Customer::factory(),
            'order_number' => Order::generateOrderNumber(),
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'shipping_cost' => 0,
            'total' => $subtotal,
            'status' => OrderStatus::Pending,
        ];
    }
}
