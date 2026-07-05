<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Funnel;
use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\Salespage;
use App\Notifications\DigitalDeliveryAvailable;
use App\Notifications\OrderConfirmed;
use App\Notifications\PaymentFailed;
use App\Notifications\PaymentInstructions;
use App\Notifications\PaymentSuccessful;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderNotificationsTest extends TestCase
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

    private function payWithMethod(Funnel $funnel, string $method = 'VC'): void
    {
        $this->fakeDuitku();

        $this->post("/f/{$funnel->slug}/checkout/bayar", ['payment_method' => $method]);
    }

    private function callbackSignature(PaymentSetting $settings, string $merchantOrderId, int $amount): string
    {
        return md5($settings->merchant_code.$amount.$merchantOrderId.$settings->api_key);
    }

    private function sendWebhook(PaymentSetting $settings, Order $order, string $resultCode = '00'): void
    {
        $amount = (int) round((float) $order->total);

        $this->post('/webhooks/duitku', [
            'merchantCode' => $settings->merchant_code,
            'amount' => $amount,
            'merchantOrderId' => $order->order_number,
            'resultCode' => $resultCode,
            'reference' => 'DTEST-REF-1',
            'paymentCode' => 'VC',
            'signature' => $this->callbackSignature($settings, $order->order_number, $amount),
        ]);
    }

    public function test_order_confirmation_email_is_sent_on_checkout(): void
    {
        Notification::fake();

        $funnel = $this->publishedFunnel();
        $order = $this->checkoutOrder($funnel);

        Notification::assertSentTo($order->customer, OrderConfirmed::class);
    }

    public function test_order_confirmation_email_is_sent_for_physical_checkout_too(): void
    {
        Notification::fake();

        $funnel = $this->publishedFunnel('physical');
        $order = $this->checkoutOrderPhysical($funnel);

        Notification::assertSentTo($order->customer, OrderConfirmed::class);
    }

    public function test_payment_instructions_email_is_sent_when_choosing_payment_method(): void
    {
        Notification::fake();
        PaymentSetting::factory()->create();

        $funnel = $this->publishedFunnel();
        $order = $this->checkoutOrder($funnel);
        $this->payWithMethod($funnel);

        Notification::assertSentTo(
            $order->customer,
            PaymentInstructions::class,
            fn (PaymentInstructions $notification) => $notification->paymentUrl === 'https://sandbox.duitku.com/pay/abc123',
        );
    }

    public function test_payment_successful_email_is_sent_for_physical_order_on_webhook_success(): void
    {
        Notification::fake();
        $settings = PaymentSetting::factory()->create();

        $funnel = $this->publishedFunnel('physical');
        $order = $this->checkoutOrderPhysical($funnel);
        $this->payWithMethod($funnel);

        $this->sendWebhook($settings, $order);

        Notification::assertSentTo($order->customer, PaymentSuccessful::class);
    }

    public function test_payment_successful_email_is_not_duplicated_for_digital_only_order(): void
    {
        Notification::fake();
        $settings = PaymentSetting::factory()->create();

        $funnel = $this->publishedFunnel();
        $order = $this->checkoutOrder($funnel);
        $this->payWithMethod($funnel);

        $this->sendWebhook($settings, $order);

        Notification::assertSentTo($order->customer, DigitalDeliveryAvailable::class);
        Notification::assertNotSentTo($order->customer, PaymentSuccessful::class);
    }

    public function test_payment_failed_email_is_sent_on_webhook_failure(): void
    {
        Notification::fake();
        $settings = PaymentSetting::factory()->create();

        $funnel = $this->publishedFunnel();
        $order = $this->checkoutOrder($funnel);
        $this->payWithMethod($funnel);

        $this->sendWebhook($settings, $order, '01');

        Notification::assertSentTo($order->customer, PaymentFailed::class);
    }

    public function test_resume_payment_link_bootstraps_session_and_redirects_to_payment_picker(): void
    {
        $funnel = $this->publishedFunnel();
        $order = $this->checkoutOrder($funnel);

        // Fresh request with no checkout session at all (simulates the
        // customer clicking the email link on a different browser/device).
        $this->flushSession();

        $response = $this->get($order->resumePaymentUrl());

        $response->assertRedirect(route('public.checkout.pay', $funnel));
    }

    public function test_resume_payment_link_with_wrong_token_returns_404(): void
    {
        $funnel = $this->publishedFunnel();
        $order = $this->checkoutOrder($funnel);

        $response = $this->get(route('order.resume-payment', [
            'order' => $order->order_number,
            'token' => 'not-the-real-token',
        ]));

        $response->assertNotFound();
    }

    public function test_resume_payment_link_for_already_paid_order_redirects_to_lookup(): void
    {
        $funnel = $this->publishedFunnel();
        $order = $this->checkoutOrder($funnel);
        $order->update(['status' => OrderStatus::Paid]);

        $response = $this->get($order->resumePaymentUrl());

        $response->assertRedirect(route('order-lookup.create'));
    }
}
