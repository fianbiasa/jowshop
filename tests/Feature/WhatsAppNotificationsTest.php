<?php

namespace Tests\Feature;

use App\Models\Funnel;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemDelivery;
use App\Models\Product;
use App\Models\Salespage;
use App\Models\Shipment;
use App\Models\WhatsAppSetting;
use App\Notifications\DigitalDeliveryAvailable;
use App\Notifications\PaymentFailed;
use App\Notifications\PaymentInstructions;
use App\Notifications\PaymentReminder;
use App\Notifications\PaymentSuccessful;
use App\Notifications\ShipmentTrackingAvailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private const STARSENDER_URL = 'https://api.starsender.online/api/send';

    private function fakeStarsender(): void
    {
        Http::fake([
            self::STARSENDER_URL => Http::response(['success' => true, 'message' => 'Success sent message']),
        ]);
    }

    private function publishedFunnel(): Funnel
    {
        $product = Product::factory()->digital()->published()->create(['price' => 45000]);

        $funnel = Funnel::factory()->published()->create([
            'product_id' => $product->id,
            'slug' => 'kopi',
        ]);

        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        return $funnel;
    }

    private function checkoutOrder(Funnel $funnel): Order
    {
        $this->post("/f/{$funnel->slug}/checkout", [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
        ]);

        return Order::query()->firstOrFail();
    }

    public function test_order_confirmation_is_sent_to_whatsapp_on_checkout(): void
    {
        $this->fakeStarsender();
        WhatsAppSetting::factory()->create(['api_key' => 'device-key-123']);

        $funnel = $this->publishedFunnel();
        $order = $this->checkoutOrder($funnel);

        Http::assertSent(function (Request $request) use ($order) {
            return $request->url() === self::STARSENDER_URL
                && $request->hasHeader('Authorization', 'device-key-123')
                && $request['messageType'] === 'text'
                && $request['to'] === '081234567890'
                && str_contains($request['body'], $order->order_number)
                && str_contains($request['body'], 'Rp45.000')
                && str_contains($request['body'], $order->resumePaymentUrl());
        });
    }

    public function test_whatsapp_message_is_not_sent_when_no_setting_is_configured(): void
    {
        $this->fakeStarsender();

        $funnel = $this->publishedFunnel();
        $this->checkoutOrder($funnel);

        Http::assertNotSent(fn (Request $request) => $request->url() === self::STARSENDER_URL);
    }

    public function test_whatsapp_message_is_not_sent_when_setting_is_inactive(): void
    {
        $this->fakeStarsender();
        WhatsAppSetting::factory()->inactive()->create();

        $funnel = $this->publishedFunnel();
        $this->checkoutOrder($funnel);

        Http::assertNotSent(fn (Request $request) => $request->url() === self::STARSENDER_URL);
    }

    public function test_whatsapp_message_is_not_sent_when_customer_has_no_phone(): void
    {
        $this->fakeStarsender();
        WhatsAppSetting::factory()->create();

        $order = Order::factory()->create();
        $order->customer->update(['phone' => null]);

        $order->customer->refresh()->notify(new PaymentSuccessful($order));

        Http::assertNotSent(fn (Request $request) => $request->url() === self::STARSENDER_URL);
    }

    public function test_customer_phone_number_is_normalized_to_digits(): void
    {
        $this->fakeStarsender();
        WhatsAppSetting::factory()->create();

        $order = Order::factory()->create();
        $order->customer->update(['phone' => '+62 812-3456-7890']);

        $order->customer->refresh()->notify(new PaymentSuccessful($order));

        Http::assertSent(fn (Request $request) => $request->url() === self::STARSENDER_URL
            && $request['to'] === '6281234567890');
    }

    public function test_payment_instructions_whatsapp_message_contains_payment_url(): void
    {
        $this->fakeStarsender();
        WhatsAppSetting::factory()->create();

        $order = Order::factory()->create();

        $order->customer->notify(new PaymentInstructions($order, 'https://sandbox.duitku.com/pay/abc123'));

        Http::assertSent(fn (Request $request) => $request->url() === self::STARSENDER_URL
            && str_contains($request['body'], 'https://sandbox.duitku.com/pay/abc123')
            && str_contains($request['body'], $order->order_number));
    }

    public function test_payment_failed_whatsapp_message_contains_resume_payment_url(): void
    {
        $this->fakeStarsender();
        WhatsAppSetting::factory()->create();

        $order = Order::factory()->create();

        $order->customer->notify(new PaymentFailed($order));

        Http::assertSent(fn (Request $request) => $request->url() === self::STARSENDER_URL
            && str_contains($request['body'], $order->resumePaymentUrl()));
    }

    public function test_payment_reminder_whatsapp_copy_escalates_with_reminder_number(): void
    {
        $this->fakeStarsender();
        WhatsAppSetting::factory()->create();

        $order = Order::factory()->create();

        $order->customer->notify(new PaymentReminder($order, 3));

        Http::assertSent(fn (Request $request) => $request->url() === self::STARSENDER_URL
            && str_contains($request['body'], 'Ini kesempatan terakhirmu!')
            && str_contains($request['body'], $order->resumePaymentUrl()));
    }

    public function test_shipment_tracking_whatsapp_message_contains_tracking_number(): void
    {
        $this->fakeStarsender();
        WhatsAppSetting::factory()->create();

        $shipment = Shipment::factory()->create(['tracking_number' => 'JNE1234567890']);

        $shipment->order->customer->notify(new ShipmentTrackingAvailable($shipment));

        Http::assertSent(fn (Request $request) => $request->url() === self::STARSENDER_URL
            && str_contains($request['body'], 'JNE1234567890')
            && str_contains($request['body'], $shipment->order->order_number));
    }

    public function test_digital_delivery_whatsapp_message_contains_download_link_and_license_key(): void
    {
        $this->fakeStarsender();
        WhatsAppSetting::factory()->create();

        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);
        $delivery = OrderItemDelivery::factory()->create([
            'order_item_id' => $orderItem->id,
            'license_key' => 'LISENSI-XYZ-123',
        ]);

        $order->customer->notify(new DigitalDeliveryAvailable($order, collect([$delivery])));

        Http::assertSent(fn (Request $request) => $request->url() === self::STARSENDER_URL
            && str_contains($request['body'], route('digital-download.show', $delivery->download_token))
            && str_contains($request['body'], 'LISENSI-XYZ-123'));
    }
}
