<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
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
            'gateway' => 'duitku',
            'merchant_order_id' => Order::generateOrderNumber(),
            'amount' => $this->faker->randomFloat(2, 10_000, 300_000),
            'status' => PaymentStatus::Pending,
        ];
    }
}
