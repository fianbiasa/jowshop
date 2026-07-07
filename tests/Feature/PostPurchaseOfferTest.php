<?php

namespace Tests\Feature;

use App\Enums\FunnelEventType;
use App\Enums\OfferStage;
use App\Enums\OfferTriggerCondition;
use App\Enums\OrderItemType;
use App\Models\Funnel;
use App\Models\FunnelOffer;
use App\Models\FunnelSession;
use App\Models\MetaCapiSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\Salespage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostPurchaseOfferTest extends TestCase
{
    use RefreshDatabase;

    private function publishedFunnel(string $type = 'digital'): Funnel
    {
        $product = Product::factory()->{$type}()->published()->create(['price' => 45000]);

        $funnel = Funnel::factory()->published()->create([
            'product_id' => $product->id,
            'slug' => 'kopi',
        ]);

        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        return $funnel;
    }

    private function fakeDuitkuPaymentMethods(): void
    {
        Http::fake([
            '*/paymentmethod/getpaymentmethod' => Http::response([
                'paymentFee' => [
                    ['paymentMethod' => 'VC', 'paymentName' => 'CREDIT CARD', 'paymentImage' => 'https://images.duitku.com/hotlink-ok/VC.PNG', 'totalFee' => '0'],
                ],
                'responseCode' => '00',
                'responseMessage' => 'SUCCESS',
            ], 200),
        ]);
    }

    private function fakeDuitkuInquiry(): void
    {
        Http::fake([
            '*/inquiry' => Http::response([
                'statusCode' => '00',
                'statusMessage' => 'SUCCESS',
                'reference' => 'DTEST-REF',
                'paymentUrl' => 'https://sandbox.duitku.com/pay/abc123',
            ], 200),
        ]);
    }

    /**
     * Assumes the caller has already faked both the payment-method list and
     * the inquiry endpoints (via fakeDuitkuPaymentMethods()/fakeDuitkuInquiry()
     * or an equivalent custom fake).
     */
    private function payAndConfirmMainOrder(Funnel $funnel): Order
    {
        $this->post("/f/{$funnel->slug}/checkout", [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
        ]);

        $order = Order::query()->firstOrFail();

        $this->post("/f/{$funnel->slug}/checkout/bayar", ['payment_method' => 'VC']);

        $settings = PaymentSetting::query()->firstOrFail();
        $amount = (int) round((float) $order->total);

        $this->post('/webhooks/duitku', [
            'merchantCode' => $settings->merchant_code,
            'amount' => $amount,
            'merchantOrderId' => $order->order_number,
            'resultCode' => '00',
            'reference' => 'DTEST-REF-MAIN',
            'signature' => md5($settings->merchant_code.$amount.$order->order_number.$settings->api_key),
        ]);

        return $order->fresh();
    }

    public function test_bump_offer_page_uses_the_funnels_salespage_style(): void
    {
        $product = Product::factory()->digital()->published()->create(['price' => 45000]);
        $funnel = Funnel::factory()->published()->create(['product_id' => $product->id, 'slug' => 'kopi']);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id, 'style' => 'bold']);
        $bump = FunnelOffer::factory()->for($funnel)->bump()->create(['sequence' => 1]);

        $this->post("/f/{$funnel->slug}/checkout", [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
        ]);

        $response = $this->get("/f/{$funnel->slug}/checkout/offers/{$bump->id}");

        $response->assertInertia(fn ($page) => $page
            ->component('public/checkout-offer')
            ->where('style', 'bold')
        );
    }

    public function test_upsell_offer_page_uses_the_funnels_salespage_style(): void
    {
        PaymentSetting::factory()->create();
        $this->fakeDuitkuPaymentMethods();
        $this->fakeDuitkuInquiry();

        $product = Product::factory()->digital()->published()->create(['price' => 45000]);
        $funnel = Funnel::factory()->published()->create(['product_id' => $product->id, 'slug' => 'kopi']);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id, 'style' => 'editorial']);
        $upsell = FunnelOffer::factory()->for($funnel)->upsell()->create(['sequence' => 1]);

        $this->payAndConfirmMainOrder($funnel);
        $this->get("/f/{$funnel->slug}/checkout/kembali");

        $response = $this->get("/f/{$funnel->slug}/upsell/{$upsell->id}");

        $response->assertInertia(fn ($page) => $page
            ->component('public/checkout-upsell')
            ->where('style', 'editorial')
        );
    }

    public function test_accepting_an_upsell_uses_the_client_supplied_event_id_for_meta_dedup(): void
    {
        PaymentSetting::factory()->create();
        $this->fakeDuitkuPaymentMethods();
        $this->fakeDuitkuInquiry();

        $funnel = $this->publishedFunnel();
        $upsell = FunnelOffer::factory()->for($funnel)->upsell()->create(['sequence' => 1]);

        $this->payAndConfirmMainOrder($funnel);
        $this->get("/f/{$funnel->slug}/checkout/kembali");

        $eventId = (string) Str::uuid();

        $this->post("/f/{$funnel->slug}/upsell/{$upsell->id}", [
            'response' => 'accepted',
            'event_id' => $eventId,
        ]);

        $session = FunnelSession::query()->firstOrFail();
        $event = $session->events()->where('event_type', FunnelEventType::UpsellAccepted)->firstOrFail();

        $this->assertSame($eventId, $event->external_event_id);
    }

    public function test_upsell_offer_page_exposes_pixel_id_falling_back_to_global_meta_capi_settings(): void
    {
        MetaCapiSetting::factory()->create(['pixel_id' => '999888777', 'is_active' => true]);
        PaymentSetting::factory()->create();
        $this->fakeDuitkuPaymentMethods();
        $this->fakeDuitkuInquiry();

        $funnel = $this->publishedFunnel();
        $upsell = FunnelOffer::factory()->for($funnel)->upsell()->create(['sequence' => 1]);

        $this->payAndConfirmMainOrder($funnel);
        $this->get("/f/{$funnel->slug}/checkout/kembali");

        $response = $this->get("/f/{$funnel->slug}/upsell/{$upsell->id}");

        $response->assertInertia(fn ($page) => $page->where('pixelId', '999888777'));
    }

    public function test_return_page_includes_next_step_props_and_grants_order_lookup_access(): void
    {
        PaymentSetting::factory()->create();
        $this->fakeDuitkuPaymentMethods();
        $this->fakeDuitkuInquiry();

        $funnel = $this->publishedFunnel('digital');
        $order = $this->payAndConfirmMainOrder($funnel);

        $response = $this->get("/f/{$funnel->slug}/checkout/kembali");

        $response->assertInertia(fn ($page) => $page
            ->component('public/checkout-return')
            ->where('order.items.0.is_digital', true)
            ->where('customerEmail', 'budi@example.com')
            ->where('orderLookupUrl', route('order-lookup.show', $order->order_number))
        );

        // The checkout session already owns this order, so the thank-you
        // page should be able to grant order-lookup access without the
        // buyer re-entering their email/order number.
        $lookupResponse = $this->get("/pesanan-saya/{$order->order_number}");
        $lookupResponse->assertOk();
        $lookupResponse->assertInertia(fn ($page) => $page->component('public/order-lookup-result'));
    }

    public function test_return_page_uses_the_funnels_salespage_style(): void
    {
        PaymentSetting::factory()->create();
        $this->fakeDuitkuPaymentMethods();
        $this->fakeDuitkuInquiry();

        $product = Product::factory()->digital()->published()->create(['price' => 45000]);
        $funnel = Funnel::factory()->published()->create(['product_id' => $product->id, 'slug' => 'kopi']);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id, 'style' => 'ledger']);

        $this->payAndConfirmMainOrder($funnel);

        $response = $this->get("/f/{$funnel->slug}/checkout/kembali");

        $response->assertInertia(fn ($page) => $page
            ->component('public/checkout-return')
            ->where('style', 'ledger')
        );
    }

    public function test_return_page_without_upsell_offers_finalizes_immediately(): void
    {
        PaymentSetting::factory()->create();
        $this->fakeDuitkuPaymentMethods();
        $this->fakeDuitkuInquiry();

        $funnel = $this->publishedFunnel();
        $this->payAndConfirmMainOrder($funnel);

        $response = $this->get("/f/{$funnel->slug}/checkout/kembali");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('public/checkout-return'));

        $session = FunnelSession::query()->firstOrFail();
        $this->assertTrue(
            $session->events()->where('event_type', FunnelEventType::ThankyouView)->exists()
        );
    }

    public function test_return_page_can_be_refreshed_without_404ing(): void
    {
        PaymentSetting::factory()->create();
        $this->fakeDuitkuPaymentMethods();
        $this->fakeDuitkuInquiry();

        $funnel = $this->publishedFunnel();
        $order = $this->payAndConfirmMainOrder($funnel);

        $first = $this->get("/f/{$funnel->slug}/checkout/kembali");
        $first->assertOk();

        // Refreshing (or the browser retrying) the same URL must keep
        // working — it must not depend on session state that was only ever
        // meant to be cleared once the whole checkout+offer flow is done.
        $second = $this->get("/f/{$funnel->slug}/checkout/kembali");
        $second->assertOk();
        $second->assertInertia(fn ($page) => $page
            ->component('public/checkout-return')
            ->where('order.order_number', $order->order_number)
        );
    }

    public function test_return_page_redirects_to_root_upsell_when_order_is_paid(): void
    {
        PaymentSetting::factory()->create();
        $this->fakeDuitkuPaymentMethods();
        $this->fakeDuitkuInquiry();

        $funnel = $this->publishedFunnel();
        $upsell = FunnelOffer::factory()->for($funnel)->upsell()->create(['sequence' => 1]);

        $this->payAndConfirmMainOrder($funnel);

        $response = $this->get("/f/{$funnel->slug}/checkout/kembali");

        $response->assertRedirect("/f/{$funnel->slug}/upsell/{$upsell->id}");
    }

    public function test_unpaid_order_cannot_view_upsell_offer(): void
    {
        $funnel = $this->publishedFunnel();
        $upsell = FunnelOffer::factory()->for($funnel)->upsell()->create();

        $this->post("/f/{$funnel->slug}/checkout", [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
        ]);

        $response = $this->get("/f/{$funnel->slug}/upsell/{$upsell->id}");

        $response->assertNotFound();
    }

    public function test_declining_upsell_moves_to_downsell_without_payment(): void
    {
        PaymentSetting::factory()->create();
        $this->fakeDuitkuPaymentMethods();
        $this->fakeDuitkuInquiry();

        $funnel = $this->publishedFunnel();

        $upsell = FunnelOffer::factory()->for($funnel)->upsell()->create([
            'headline' => 'Upgrade ke 1kg?',
            'sequence' => 1,
        ]);
        $downsell = FunnelOffer::factory()
            ->childOf($upsell, OfferTriggerCondition::Declined)
            ->create([
                'stage' => OfferStage::Downsell,
                'headline' => '250gr saja?',
                'sequence' => 1,
            ]);

        $this->payAndConfirmMainOrder($funnel);
        $this->get("/f/{$funnel->slug}/checkout/kembali");

        $response = $this->post("/f/{$funnel->slug}/upsell/{$upsell->id}", [
            'response' => 'declined',
        ]);

        $response->assertRedirect("/f/{$funnel->slug}/upsell/{$downsell->id}");

        $order = Order::query()->firstOrFail();
        $this->assertSame(1, $order->items()->count());
    }

    public function test_declining_entire_upsell_chain_finalizes_without_payment(): void
    {
        PaymentSetting::factory()->create();
        $this->fakeDuitkuPaymentMethods();
        $this->fakeDuitkuInquiry();

        $funnel = $this->publishedFunnel();
        $upsell = FunnelOffer::factory()->for($funnel)->upsell()->create(['sequence' => 1]);

        $this->payAndConfirmMainOrder($funnel);
        $this->get("/f/{$funnel->slug}/checkout/kembali");

        $response = $this->post("/f/{$funnel->slug}/upsell/{$upsell->id}", [
            'response' => 'declined',
        ]);

        $response->assertRedirect("/f/{$funnel->slug}/checkout/kembali");

        $finalResponse = $this->get("/f/{$funnel->slug}/checkout/kembali");
        $finalResponse->assertOk();
        $finalResponse->assertInertia(fn ($page) => $page->component('public/checkout-return'));

        $order = Order::query()->firstOrFail();
        $this->assertSame(1, $order->items()->count());
    }

    public function test_accepting_upsell_creates_incremental_payment_and_redirects_to_duitku(): void
    {
        $settings = PaymentSetting::factory()->create();
        $this->fakeDuitkuPaymentMethods();
        $this->fakeDuitkuInquiry();

        $funnel = $this->publishedFunnel();
        $upsell = FunnelOffer::factory()->for($funnel)->upsell()->create([
            'price_override' => 50000,
            'sequence' => 1,
        ]);

        $order = $this->payAndConfirmMainOrder($funnel);
        $this->get("/f/{$funnel->slug}/checkout/kembali");

        $response = $this->post("/f/{$funnel->slug}/upsell/{$upsell->id}", [
            'response' => 'accepted',
        ]);

        $response->assertRedirect('https://sandbox.duitku.com/pay/abc123');

        $this->assertSame(2, $order->items()->count());
        $this->assertSame('95000.00', $order->fresh()->total); // 45000 + 50000

        $payment = Payment::query()->where('merchant_order_id', "{$order->order_number}-O{$upsell->id}")->firstOrFail();
        $this->assertSame(50000.0, (float) $payment->amount);
    }

    public function test_accepting_upsell_reuses_the_main_orders_payment_method(): void
    {
        PaymentSetting::factory()->create();
        $this->fakeDuitkuPaymentMethods();
        $this->fakeDuitkuInquiry();

        $funnel = $this->publishedFunnel();
        $upsell = FunnelOffer::factory()->for($funnel)->upsell()->create([
            'price_override' => 50000,
            'sequence' => 1,
        ]);

        $order = $this->payAndConfirmMainOrder($funnel);
        $this->get("/f/{$funnel->slug}/checkout/kembali");

        $this->post("/f/{$funnel->slug}/upsell/{$upsell->id}", [
            'response' => 'accepted',
        ]);

        $mainPaymentMethod = $order->payments()->oldest()->first()->payment_method;
        $this->assertSame('VC', $mainPaymentMethod);

        $upsellPayment = Payment::query()->where('merchant_order_id', "{$order->order_number}-O{$upsell->id}")->firstOrFail();
        $this->assertSame($mainPaymentMethod, $upsellPayment->payment_method);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/inquiry')
            && ($request->data()['merchantOrderId'] ?? null) === "{$order->order_number}-O{$upsell->id}"
            && $request->data()['paymentMethod'] === $mainPaymentMethod);
    }

    public function test_accepting_upsell_shows_friendly_error_when_duitku_request_fails(): void
    {
        PaymentSetting::factory()->create();

        $funnel = $this->publishedFunnel();
        $upsell = FunnelOffer::factory()->for($funnel)->upsell()->create([
            'price_override' => 50000,
            'sequence' => 1,
        ]);

        // The main order's /inquiry call succeeds; only the upsell's
        // incremental payment (merchantOrderId contains "-O{offerId}")
        // fails, so this exercises the failure path in isolation.
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/paymentmethod/getpaymentmethod')) {
                return Http::response([
                    'paymentFee' => [
                        ['paymentMethod' => 'VC', 'paymentName' => 'CREDIT CARD', 'paymentImage' => 'https://images.duitku.com/hotlink-ok/VC.PNG', 'totalFee' => '0'],
                    ],
                    'responseCode' => '00',
                    'responseMessage' => 'SUCCESS',
                ], 200);
            }

            $merchantOrderId = (string) ($request->data()['merchantOrderId'] ?? '');

            if (str_contains($merchantOrderId, '-O')) {
                return Http::response(['Message' => 'Not Found'], 404);
            }

            return Http::response([
                'merchantCode' => 'DTEST',
                'reference' => 'DTEST-REF-1',
                'paymentUrl' => 'https://sandbox.duitku.com/pay/abc123',
                'statusCode' => '00',
                'statusMessage' => 'SUCCESS',
            ], 200);
        });

        $this->payAndConfirmMainOrder($funnel);
        $this->get("/f/{$funnel->slug}/checkout/kembali");

        $response = $this->post("/f/{$funnel->slug}/upsell/{$upsell->id}", [
            'response' => 'accepted',
        ]);

        $response->assertStatus(503);
    }

    public function test_full_chain_upsell_declined_then_downsell_accepted_and_paid(): void
    {
        $settings = PaymentSetting::factory()->create();
        $this->fakeDuitkuPaymentMethods();
        $this->fakeDuitkuInquiry();

        $funnel = $this->publishedFunnel();

        $upsell = FunnelOffer::factory()->for($funnel)->upsell()->create([
            'headline' => 'Upgrade ke 1kg?',
            'price_override' => 50000,
            'sequence' => 1,
        ]);
        $downsell = FunnelOffer::factory()
            ->childOf($upsell, OfferTriggerCondition::Declined)
            ->create([
                'stage' => OfferStage::Downsell,
                'headline' => '250gr saja?',
                'price_override' => 15000,
                'sequence' => 1,
            ]);

        $order = $this->payAndConfirmMainOrder($funnel);
        $this->get("/f/{$funnel->slug}/checkout/kembali");

        // Decline the upsell...
        $this->get("/f/{$funnel->slug}/upsell/{$upsell->id}");
        $this->post("/f/{$funnel->slug}/upsell/{$upsell->id}", ['response' => 'declined']);

        // ...accept the downsell.
        $this->get("/f/{$funnel->slug}/upsell/{$downsell->id}");
        $this->post("/f/{$funnel->slug}/upsell/{$downsell->id}", ['response' => 'accepted']);

        $downsellPaymentAmount = (int) round((float) $downsell->fresh()->effectivePrice());
        $downsellMerchantOrderId = "{$order->order_number}-O{$downsell->id}";

        $this->post('/webhooks/duitku', [
            'merchantCode' => $settings->merchant_code,
            'amount' => $downsellPaymentAmount,
            'merchantOrderId' => $downsellMerchantOrderId,
            'resultCode' => '00',
            'reference' => 'DTEST-REF-DOWNSELL',
            'signature' => md5($settings->merchant_code.$downsellPaymentAmount.$downsellMerchantOrderId.$settings->api_key),
        ]);

        $response = $this->get("/f/{$funnel->slug}/checkout/kembali");
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('public/checkout-return'));

        $order->refresh();
        $this->assertSame(2, $order->items()->count());
        $this->assertSame('60000.00', $order->total); // 45000 main + 15000 downsell

        $downsellItem = $order->items()->where('funnel_offer_id', $downsell->id)->firstOrFail();
        $this->assertSame(OrderItemType::Downsell, $downsellItem->offer_type);

        $session = FunnelSession::query()->firstOrFail();
        $this->assertTrue($session->events()->where('event_type', FunnelEventType::UpsellDeclined)->exists());
        $this->assertTrue($session->events()->where('event_type', FunnelEventType::DownsellAccepted)->exists());
        $this->assertTrue($session->events()->where('event_type', FunnelEventType::ThankyouView)->exists());
    }

    public function test_incremental_payment_webhook_does_not_double_decrement_stock(): void
    {
        $settings = PaymentSetting::factory()->create();
        $this->fakeDuitkuPaymentMethods();
        $this->fakeDuitkuInquiry();

        $product = Product::factory()->physical()->published()->create(['price' => 45000, 'stock' => 10]);
        $funnel = Funnel::factory()->published()->create(['product_id' => $product->id, 'slug' => 'kopi']);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        $upsellProduct = Product::factory()->physical()->published()->create(['stock' => 5]);
        $upsell = FunnelOffer::factory()->for($funnel)->upsell()->create([
            'product_id' => $upsellProduct->id,
            'price_override' => 20000,
            'sequence' => 1,
        ]);

        $this->post("/f/{$funnel->slug}/checkout", [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
            'district' => 'Kebayoran Baru',
            'postal_code' => '12110',
            'destination_area_id' => '17550',
            'destination_label' => 'Kebayoran Baru, Jakarta Selatan, DKI Jakarta, 12110',
            'address_line' => 'Jl. Sudirman No. 1',
        ]);
        $order = Order::query()->firstOrFail();

        $this->post("/f/{$funnel->slug}/checkout/bayar", ['payment_method' => 'VC']);
        $amount = (int) round((float) $order->total);
        $this->post('/webhooks/duitku', [
            'merchantCode' => $settings->merchant_code,
            'amount' => $amount,
            'merchantOrderId' => $order->order_number,
            'resultCode' => '00',
            'signature' => md5($settings->merchant_code.$amount.$order->order_number.$settings->api_key),
        ]);

        $this->assertSame(9, $product->fresh()->stock);

        $this->get("/f/{$funnel->slug}/checkout/kembali");
        $this->post("/f/{$funnel->slug}/upsell/{$upsell->id}", ['response' => 'accepted']);

        $upsellMerchantOrderId = "{$order->order_number}-O{$upsell->id}";
        $this->post('/webhooks/duitku', [
            'merchantCode' => $settings->merchant_code,
            'amount' => 20000,
            'merchantOrderId' => $upsellMerchantOrderId,
            'resultCode' => '00',
            'signature' => md5($settings->merchant_code.'20000'.$upsellMerchantOrderId.$settings->api_key),
        ]);

        // The main product's stock should NOT be decremented again...
        $this->assertSame(9, $product->fresh()->stock);
        // ...but the upsell product's stock should now be decremented exactly once.
        $this->assertSame(4, $upsellProduct->fresh()->stock);
    }
}
