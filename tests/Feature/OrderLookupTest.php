<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Funnel;
use App\Models\Order;
use App\Models\Product;
use App\Models\Salespage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLookupTest extends TestCase
{
    use RefreshDatabase;

    private function orderFor(string $email): Order
    {
        $customer = Customer::factory()->create(['email' => $email]);
        $product = Product::factory()->digital()->published()->create();
        $funnel = Funnel::factory()->published()->create(['product_id' => $product->id]);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        return Order::factory()->create([
            'funnel_id' => $funnel->id,
            'customer_id' => $customer->id,
        ]);
    }

    public function test_correct_email_and_order_number_shows_the_order(): void
    {
        $order = $this->orderFor('budi@example.com');

        $response = $this->post(route('order-lookup.store'), [
            'email' => 'budi@example.com',
            'order_number' => $order->order_number,
        ]);

        $response->assertRedirect(route('order-lookup.show', $order->order_number));

        $show = $this->get(route('order-lookup.show', $order->order_number));
        $show->assertOk();
        $show->assertInertia(fn ($page) => $page
            ->component('public/order-lookup-result')
            ->where('order.order_number', $order->order_number)
        );
    }

    public function test_wrong_email_is_rejected(): void
    {
        $order = $this->orderFor('budi@example.com');

        $response = $this->post(route('order-lookup.store'), [
            'email' => 'someone-else@example.com',
            'order_number' => $order->order_number,
        ]);

        $response->assertSessionHasErrors('order_number');
    }

    public function test_wrong_order_number_is_rejected(): void
    {
        $this->orderFor('budi@example.com');

        $response = $this->post(route('order-lookup.store'), [
            'email' => 'budi@example.com',
            'order_number' => 'ORD-DOESNOTEXIST',
        ]);

        $response->assertSessionHasErrors('order_number');
    }

    public function test_show_redirects_to_form_without_prior_verification(): void
    {
        $order = $this->orderFor('budi@example.com');

        $response = $this->get(route('order-lookup.show', $order->order_number));

        $response->assertRedirect(route('order-lookup.create'));
    }

    public function test_verifying_one_order_does_not_grant_access_to_another(): void
    {
        $orderA = $this->orderFor('budi@example.com');
        $orderB = Order::factory()->create([
            'funnel_id' => $orderA->funnel_id,
            'customer_id' => $orderA->customer_id,
        ]);

        $this->post(route('order-lookup.store'), [
            'email' => 'budi@example.com',
            'order_number' => $orderA->order_number,
        ]);

        $response = $this->get(route('order-lookup.show', $orderB->order_number));

        $response->assertRedirect(route('order-lookup.create'));
    }
}
