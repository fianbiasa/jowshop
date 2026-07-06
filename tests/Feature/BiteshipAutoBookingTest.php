<?php

namespace Tests\Feature;

use App\Enums\ShippingProvider;
use App\Models\Funnel;
use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\Salespage;
use App\Models\Shipment;
use App\Models\ShippingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BiteshipAutoBookingTest extends TestCase
{
    use RefreshDatabase;

    private function biteshipSettings(array $overrides = []): ShippingSetting
    {
        return ShippingSetting::factory()->create(array_merge([
            'provider' => ShippingProvider::Biteship,
            'auto_book_shipping' => true,
            'origin_contact_name' => 'Budi Toko',
            'origin_contact_phone' => '081200000000',
            'origin_address' => 'Jl. Gudang No. 1',
            'origin_postal_code' => '28125',
        ], $overrides));
    }

    /**
     * Fakes both providers' rate-calculation endpoint shapes, since this is
     * used by checkoutUpToPaymentMethod() before the test has necessarily
     * decided which provider is active (some tests use the default
     * Komerce-provider factory instead of Biteship, on purpose).
     */
    private function fakeCostCalculation(): void
    {
        Http::fake([
            '*/rates/couriers' => Http::response([
                'success' => true,
                'pricing' => [
                    ['courier_code' => 'jne', 'courier_service_code' => 'reg', 'courier_service_name' => 'Reguler', 'description' => 'Reguler', 'price' => 15000, 'duration' => '2-3 days'],
                ],
            ], 200),
            '*/calculate/domestic-cost' => Http::response([
                'data' => [
                    ['code' => 'jne', 'service' => 'reg', 'description' => 'Reguler', 'cost' => 15000, 'etd' => '2-3'],
                ],
            ], 200),
        ]);
    }

    private function fakeBiteshipOrderCreation(): void
    {
        Http::fake([
            '*/orders' => Http::response([
                'success' => true,
                'id' => '5dd599ebdefcd4158eb8470b',
                'courier' => ['waybill_id' => 'WYB-1112223333443', 'tracking_id' => '6de509ebdefgh4158ij3451c'],
                'status' => 'confirmed',
            ], 200),
        ]);
    }

    private function fakeDuitku(): void
    {
        Http::fake([
            '*/paymentmethod/getpaymentmethod' => Http::response([
                'paymentFee' => [
                    ['paymentMethod' => 'VC', 'paymentName' => 'CREDIT CARD', 'paymentImage' => 'https://images.duitku.com/hotlink-ok/VC.PNG', 'totalFee' => '0'],
                ],
                'responseCode' => '00',
                'responseMessage' => 'SUCCESS',
            ], 200),
            '*/inquiry' => Http::response([
                'merchantCode' => 'DTEST',
                'reference' => 'DTEST-REF-1',
                'paymentUrl' => 'https://sandbox.duitku.com/pay/abc123',
                'statusCode' => '00',
                'statusMessage' => 'SUCCESS',
            ], 200),
        ]);
    }

    private function callbackSignature(PaymentSetting $settings, string $merchantOrderId, int $amount): string
    {
        return md5($settings->merchant_code.$amount.$merchantOrderId.$settings->api_key);
    }

    private function sendWebhook(PaymentSetting $settings, Order $order): void
    {
        $amount = (int) round((float) $order->total);

        $this->post('/webhooks/duitku', [
            'merchantCode' => $settings->merchant_code,
            'amount' => $amount,
            'merchantOrderId' => $order->order_number,
            'resultCode' => '00',
            'reference' => 'DTEST-REF-1',
            'paymentCode' => 'VC',
            'signature' => $this->callbackSignature($settings, $order->order_number, $amount),
        ]);
    }

    /**
     * Drives checkout all the way through shipping selection (so a real
     * `Shipment` row exists) and picks a payment method — the webhook
     * itself is fired separately by each test via sendWebhook().
     */
    private function checkoutUpToPaymentMethod(): Order
    {
        $product = Product::factory()->physical()->published()->create([
            'price' => 45000,
            'weight_grams' => 500,
        ]);

        $funnel = Funnel::factory()->published()->create([
            'product_id' => $product->id,
            'slug' => 'kopi',
        ]);

        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        $this->fakeCostCalculation();

        $this->post("/f/{$funnel->slug}/checkout", [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
            'district' => 'Kebayoran Baru',
            'postal_code' => '10310',
            'destination_area_id' => 'IDNP6IDNC147IDND832IDZ10310',
            'destination_label' => 'Menteng, Jakarta Pusat, DKI Jakarta. 10310',
            'address_line' => 'Jl. Sudirman No. 1',
        ]);

        $this->get("/f/{$funnel->slug}/checkout/pengiriman");

        $this->post("/f/{$funnel->slug}/checkout/pengiriman", [
            'courier' => 'jne',
            'service' => 'reg',
        ]);

        $this->fakeDuitku();
        $this->post("/f/{$funnel->slug}/checkout/bayar", ['payment_method' => 'VC']);

        return Order::query()->firstOrFail();
    }

    public function test_shipment_is_booked_automatically_when_payment_succeeds(): void
    {
        $paymentSettings = PaymentSetting::factory()->create();
        $this->biteshipSettings();

        $order = $this->checkoutUpToPaymentMethod();

        $this->fakeBiteshipOrderCreation();
        $this->sendWebhook($paymentSettings, $order);

        $shipment = Shipment::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertSame('WYB-1112223333443', $shipment->tracking_number);
        $this->assertSame('processing', $shipment->status->value);
        Http::assertSent(fn ($request) => str_contains((string) $request->url(), '/orders')
            && $request['courier_company'] === 'jne'
            && $request['courier_type'] === 'reg'
            && $request['reference_id'] === $order->order_number);
    }

    public function test_shipment_is_not_booked_when_auto_book_shipping_is_disabled(): void
    {
        $paymentSettings = PaymentSetting::factory()->create();
        $this->biteshipSettings(['auto_book_shipping' => false]);

        $order = $this->checkoutUpToPaymentMethod();

        $this->fakeBiteshipOrderCreation();
        $this->sendWebhook($paymentSettings, $order);

        $shipment = Shipment::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertNull($shipment->tracking_number);
        Http::assertNotSent(fn ($request) => str_contains((string) $request->url(), '/orders'));
    }

    public function test_shipment_is_not_booked_when_provider_is_not_biteship(): void
    {
        $paymentSettings = PaymentSetting::factory()->create();
        // Default factory provider is Komerce.
        ShippingSetting::factory()->create();

        $order = $this->checkoutUpToPaymentMethod();

        $this->fakeBiteshipOrderCreation();
        $this->sendWebhook($paymentSettings, $order);

        $shipment = Shipment::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertNull($shipment->tracking_number);
        Http::assertNotSent(fn ($request) => str_contains((string) $request->url(), '/orders'));
    }

    public function test_payment_webhook_still_succeeds_when_biteship_booking_fails(): void
    {
        $paymentSettings = PaymentSetting::factory()->create();
        $this->biteshipSettings();

        $order = $this->checkoutUpToPaymentMethod();

        Http::fake(['*/orders' => Http::response(['success' => false, 'error' => 'Insufficient balance'], 400)]);
        $this->sendWebhook($paymentSettings, $order);

        $order->refresh();
        $shipment = Shipment::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertSame('paid', $order->status->value);
        $this->assertNull($shipment->tracking_number);
    }
}
