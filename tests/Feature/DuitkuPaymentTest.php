<?php

namespace Tests\Feature;

use App\Enums\FunnelEventType;
use App\Enums\FunnelSessionStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Funnel;
use App\Models\FunnelSession;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\Salespage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DuitkuPaymentTest extends TestCase
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

    private function checkoutOrder(Funnel $funnel): Order
    {
        $this->post("/f/{$funnel->slug}/checkout", [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
        ]);

        return Order::query()->firstOrFail();
    }

    private function fakeDuitkuPaymentMethods(): void
    {
        Http::fake([
            '*/paymentmethod/getpaymentmethod' => Http::response([
                'paymentFee' => [
                    ['paymentMethod' => 'VC', 'paymentName' => 'CREDIT CARD', 'paymentImage' => 'https://images.duitku.com/hotlink-ok/VC.PNG', 'totalFee' => '0'],
                    ['paymentMethod' => 'BC', 'paymentName' => 'BCA VA', 'paymentImage' => 'https://images.duitku.com/hotlink-ok/BCA.SVG', 'totalFee' => '0'],
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
                'merchantCode' => 'DTEST',
                'reference' => 'DTEST-REF-1',
                'paymentUrl' => 'https://sandbox.duitku.com/pay/abc123',
                'statusCode' => '00',
                'statusMessage' => 'SUCCESS',
            ], 200),
        ]);
    }

    /**
     * Fakes both the payment-method list and inquiry endpoints, then posts
     * the customer's chosen method to the pay step. Replaces what used to
     * be a single GET to /checkout/bayar before payment-method selection
     * existed.
     */
    private function payWithMethod(Funnel $funnel, string $method = 'VC'): TestResponse
    {
        $this->fakeDuitkuPaymentMethods();
        $this->fakeDuitkuInquiry();

        return $this->post("/f/{$funnel->slug}/checkout/bayar", [
            'payment_method' => $method,
        ]);
    }

    private function callbackSignature(PaymentSetting $settings, string $merchantOrderId, int $amount): string
    {
        return md5($settings->merchant_code.$amount.$merchantOrderId.$settings->api_key);
    }

    public function test_pay_without_configured_payment_settings_returns_503(): void
    {
        $funnel = $this->publishedFunnel();
        $this->checkoutOrder($funnel);

        $response = $this->get("/f/{$funnel->slug}/checkout/bayar");

        $response->assertStatus(503);
    }

    public function test_pay_shows_available_payment_methods(): void
    {
        PaymentSetting::factory()->create();
        $this->fakeDuitkuPaymentMethods();

        $funnel = $this->publishedFunnel();
        $this->checkoutOrder($funnel);

        $response = $this->get("/f/{$funnel->slug}/checkout/bayar");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('public/checkout-payment')
            ->has('methods', 2)
            ->where('methods.0.code', 'VC')
        );
    }

    public function test_pay_creates_payment_and_redirects_to_duitku(): void
    {
        PaymentSetting::factory()->create();

        $funnel = $this->publishedFunnel();
        $order = $this->checkoutOrder($funnel);

        $response = $this->payWithMethod($funnel, 'VC');

        $response->assertRedirect('https://sandbox.duitku.com/pay/abc123');

        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame($order->order_number, $payment->merchant_order_id);
        $this->assertSame('VC', $payment->payment_method);

        Http::assertSent(fn (ClientRequest $request) => str_contains($request->url(), '/inquiry')
            && $request->data()['paymentMethod'] === 'VC');
    }

    public function test_pay_shows_friendly_error_when_duitku_request_fails(): void
    {
        PaymentSetting::factory()->create();
        $this->fakeDuitkuPaymentMethods();
        Http::fake(['*/inquiry' => Http::response(['Message' => 'Not Found'], 404)]);

        $funnel = $this->publishedFunnel();
        $this->checkoutOrder($funnel);

        $response = $this->post("/f/{$funnel->slug}/checkout/bayar", ['payment_method' => 'VC']);

        $response->assertStatus(503);
    }

    public function test_pay_shows_friendly_error_when_duitku_is_unreachable(): void
    {
        PaymentSetting::factory()->create();
        $this->fakeDuitkuPaymentMethods();
        Http::fake(['*/inquiry' => Http::failedConnection()]);

        $funnel = $this->publishedFunnel();
        $this->checkoutOrder($funnel);

        $response = $this->post("/f/{$funnel->slug}/checkout/bayar", ['payment_method' => 'VC']);

        $response->assertStatus(503);
    }

    public function test_return_page_shows_current_order_status(): void
    {
        PaymentSetting::factory()->create();

        $funnel = $this->publishedFunnel();
        $this->checkoutOrder($funnel);
        $this->payWithMethod($funnel);

        $response = $this->get("/f/{$funnel->slug}/checkout/kembali");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('public/checkout-return')
            ->where('order.status', 'pending')
        );
    }

    public function test_webhook_with_valid_signature_marks_payment_and_order_paid(): void
    {
        $settings = PaymentSetting::factory()->create();

        $funnel = $this->publishedFunnel();
        $order = $this->checkoutOrder($funnel);
        $this->payWithMethod($funnel);

        $amount = (int) round((float) $order->total);

        $response = $this->post('/webhooks/duitku', [
            'merchantCode' => $settings->merchant_code,
            'amount' => $amount,
            'merchantOrderId' => $order->order_number,
            'resultCode' => '00',
            'reference' => 'DTEST-REF-1',
            'paymentCode' => 'VC',
            'signature' => $this->callbackSignature($settings, $order->order_number, $amount),
        ]);

        $response->assertOk();

        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertNotNull($payment->paid_at);

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
    }

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        $settings = PaymentSetting::factory()->create();

        $funnel = $this->publishedFunnel();
        $order = $this->checkoutOrder($funnel);
        $this->payWithMethod($funnel);

        $response = $this->post('/webhooks/duitku', [
            'merchantCode' => $settings->merchant_code,
            'amount' => (int) round((float) $order->total),
            'merchantOrderId' => $order->order_number,
            'resultCode' => '00',
            'signature' => 'not-a-real-signature',
        ]);

        $response->assertForbidden();

        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_webhook_is_idempotent_for_duplicate_callbacks(): void
    {
        $settings = PaymentSetting::factory()->create();

        $funnel = $this->publishedFunnel('physical');
        $product = $funnel->product;
        $product->update(['stock' => 10]);

        $order = $this->checkoutOrderPhysical($funnel);
        $this->payWithMethod($funnel);

        $amount = (int) round((float) $order->total);
        $payload = [
            'merchantCode' => $settings->merchant_code,
            'amount' => $amount,
            'merchantOrderId' => $order->order_number,
            'resultCode' => '00',
            'reference' => 'DTEST-REF-1',
            'signature' => $this->callbackSignature($settings, $order->order_number, $amount),
        ];

        $this->post('/webhooks/duitku', $payload);
        $this->post('/webhooks/duitku', $payload);

        $this->assertSame(9, $product->fresh()->stock);

        $session = FunnelSession::query()->firstOrFail();
        $this->assertSame(
            1,
            $session->events()->where('event_type', FunnelEventType::PaymentSuccess)->count()
        );
    }

    public function test_webhook_success_decrements_physical_product_stock(): void
    {
        $settings = PaymentSetting::factory()->create();

        $funnel = $this->publishedFunnel('physical');
        $product = $funnel->product;
        $product->update(['stock' => 5]);

        $order = $this->checkoutOrderPhysical($funnel);
        $this->payWithMethod($funnel);

        $amount = (int) round((float) $order->total);

        $this->post('/webhooks/duitku', [
            'merchantCode' => $settings->merchant_code,
            'amount' => $amount,
            'merchantOrderId' => $order->order_number,
            'resultCode' => '00',
            'signature' => $this->callbackSignature($settings, $order->order_number, $amount),
        ]);

        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_webhook_success_marks_funnel_session_converted(): void
    {
        $settings = PaymentSetting::factory()->create();

        $funnel = $this->publishedFunnel();
        $order = $this->checkoutOrder($funnel);
        $this->payWithMethod($funnel);

        $amount = (int) round((float) $order->total);

        $this->post('/webhooks/duitku', [
            'merchantCode' => $settings->merchant_code,
            'amount' => $amount,
            'merchantOrderId' => $order->order_number,
            'resultCode' => '00',
            'signature' => $this->callbackSignature($settings, $order->order_number, $amount),
        ]);

        $session = FunnelSession::query()->firstOrFail();
        $this->assertSame(FunnelSessionStatus::Converted, $session->status);
        $this->assertSame($order->id, $session->order_id);
    }

    public function test_webhook_failure_records_payment_failed_and_does_not_mark_order_paid(): void
    {
        $settings = PaymentSetting::factory()->create();

        $funnel = $this->publishedFunnel();
        $order = $this->checkoutOrder($funnel);
        $this->payWithMethod($funnel);

        $amount = (int) round((float) $order->total);

        $response = $this->post('/webhooks/duitku', [
            'merchantCode' => $settings->merchant_code,
            'amount' => $amount,
            'merchantOrderId' => $order->order_number,
            'resultCode' => '01',
            'signature' => $this->callbackSignature($settings, $order->order_number, $amount),
        ]);

        $response->assertOk();

        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame(PaymentStatus::Failed, $payment->status);
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);

        $session = FunnelSession::query()->firstOrFail();
        $this->assertTrue(
            $session->events()->where('event_type', FunnelEventType::PaymentFailed)->exists()
        );
    }

    public function test_webhook_for_unknown_order_returns_404(): void
    {
        $settings = PaymentSetting::factory()->create();

        $response = $this->post('/webhooks/duitku', [
            'merchantCode' => $settings->merchant_code,
            'amount' => 45000,
            'merchantOrderId' => 'ORD-DOESNOTEXIST',
            'resultCode' => '00',
            'signature' => $this->callbackSignature($settings, 'ORD-DOESNOTEXIST', 45000),
        ]);

        $response->assertNotFound();
    }

    private function checkoutOrderPhysical(Funnel $funnel): Order
    {
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

        return Order::query()->firstOrFail();
    }
}
