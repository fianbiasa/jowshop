<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use App\Notifications\ShipmentTrackingAvailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_orders(): void
    {
        $order = Order::factory()->create();

        $response = $this->get(route('orders.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('orders.show', $order));
        $response->assertRedirect(route('login'));
    }

    public function test_order_index_is_displayed(): void
    {
        $user = User::factory()->create();
        Order::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('orders.index'));

        $response->assertOk();
    }

    public function test_order_index_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        Order::factory()->create(['status' => OrderStatus::Paid]);
        Order::factory()->create(['status' => OrderStatus::Pending]);

        $response = $this->actingAs($user)->get(route('orders.index', ['status' => 'paid']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.status', 'paid')
            ->where('filters.status', 'paid')
        );
    }

    public function test_order_index_status_filter_all_shows_every_order(): void
    {
        $user = User::factory()->create();
        Order::factory()->create(['status' => OrderStatus::Paid]);
        Order::factory()->create(['status' => OrderStatus::Pending]);

        $response = $this->actingAs($user)->get(route('orders.index', ['status' => 'all']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->has('orders.data', 2));
    }

    public function test_order_detail_is_displayed(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertOk();
    }

    public function test_updating_shipment_tracking_number_notifies_customer(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $order = Order::factory()->create();
        $shipment = Shipment::factory()->for($order)->create(['tracking_number' => null]);

        $response = $this->actingAs($user)->put(route('orders.shipment.update', $order), [
            'tracking_number' => 'JNE123456789',
            'status' => ShipmentStatus::Shipped->value,
        ]);

        $response->assertRedirect(route('orders.show', $order));

        $shipment->refresh();
        $this->assertSame('JNE123456789', $shipment->tracking_number);
        $this->assertSame(ShipmentStatus::Shipped, $shipment->status);
        $this->assertNotNull($shipment->shipped_at);

        Notification::assertSentTo($order->customer, ShipmentTrackingAvailable::class);
    }

    public function test_updating_shipment_without_changing_tracking_number_does_not_resend_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $order = Order::factory()->create();
        $shipment = Shipment::factory()->for($order)->create(['tracking_number' => 'JNE123456789']);

        $this->actingAs($user)->put(route('orders.shipment.update', $order), [
            'tracking_number' => 'JNE123456789',
            'status' => ShipmentStatus::Delivered->value,
        ]);

        Notification::assertNotSentTo($order->customer, ShipmentTrackingAvailable::class);
    }
}
