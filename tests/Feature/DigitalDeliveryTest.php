<?php

namespace Tests\Feature;

use App\Models\Funnel;
use App\Models\Order;
use App\Models\OrderItemDelivery;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\ProductDigitalAsset;
use App\Models\Salespage;
use App\Notifications\DigitalDeliveryAvailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DigitalDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function publishedDigitalFunnel(): Funnel
    {
        $product = Product::factory()->digital()->published()->create(['price' => 45000]);
        ProductDigitalAsset::factory()->for($product)->create([
            'file_path' => 'digital-assets/1/ebook.pdf',
            'max_downloads' => 2,
        ]);

        $funnel = Funnel::factory()->published()->create([
            'product_id' => $product->id,
            'slug' => 'ebook',
        ]);

        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        return $funnel;
    }

    private function payOrder(Funnel $funnel): Order
    {
        $settings = PaymentSetting::factory()->create();

        Http::fake([
            '*/inquiry' => Http::response([
                'merchantCode' => 'DTEST',
                'reference' => 'DTEST-REF-1',
                'paymentUrl' => 'https://sandbox.duitku.com/pay/abc123',
                'statusCode' => '00',
                'statusMessage' => 'SUCCESS',
            ], 200),
        ]);

        $this->post("/f/{$funnel->slug}/checkout", [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
        ]);

        $order = Order::query()->firstOrFail();
        $this->get("/f/{$funnel->slug}/checkout/bayar");

        $amount = (int) round((float) $order->total);
        $signature = md5($settings->merchant_code.$amount.$order->order_number.$settings->api_key);

        $this->post('/webhooks/duitku', [
            'merchantCode' => $settings->merchant_code,
            'amount' => $amount,
            'merchantOrderId' => $order->order_number,
            'resultCode' => '00',
            'reference' => 'DTEST-REF-1',
            'signature' => $signature,
        ]);

        return $order->fresh();
    }

    public function test_paying_for_digital_product_generates_a_delivery_and_notifies_customer(): void
    {
        Notification::fake();
        $funnel = $this->publishedDigitalFunnel();

        $order = $this->payOrder($funnel);

        $delivery = OrderItemDelivery::query()->whereHas('orderItem', fn ($q) => $q->where('order_id', $order->id))->firstOrFail();

        $this->assertNotEmpty($delivery->download_token);
        $this->assertSame(2, $delivery->max_downloads);
        Notification::assertSentTo($order->customer, DigitalDeliveryAvailable::class);
    }

    public function test_duplicate_webhook_does_not_regenerate_delivery(): void
    {
        $funnel = $this->publishedDigitalFunnel();
        $order = $this->payOrder($funnel);

        $this->assertSame(
            1,
            OrderItemDelivery::query()->whereHas('orderItem', fn ($q) => $q->where('order_id', $order->id))->count()
        );
    }

    public function test_download_page_shows_assets(): void
    {
        $funnel = $this->publishedDigitalFunnel();
        $order = $this->payOrder($funnel);
        $delivery = OrderItemDelivery::query()->whereHas('orderItem', fn ($q) => $q->where('order_id', $order->id))->firstOrFail();

        $response = $this->get("/unduh/{$delivery->download_token}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('public/digital-download')
            ->where('order_number', $order->order_number)
        );
    }

    public function test_invalid_token_returns_404(): void
    {
        $response = $this->get('/unduh/not-a-real-token');

        $response->assertNotFound();
    }

    public function test_file_can_be_downloaded_within_limit(): void
    {
        Storage::fake('local');
        $funnel = $this->publishedDigitalFunnel();
        $order = $this->payOrder($funnel);
        $delivery = OrderItemDelivery::query()->whereHas('orderItem', fn ($q) => $q->where('order_id', $order->id))->firstOrFail();
        $asset = ProductDigitalAsset::query()->firstOrFail();
        Storage::disk('local')->put($asset->file_path, 'file contents');

        $response = $this->get("/unduh/{$delivery->download_token}/{$asset->id}");

        $response->assertOk();
        $this->assertSame(1, $delivery->fresh()->download_count);
    }

    public function test_download_blocked_after_reaching_max_downloads(): void
    {
        Storage::fake('local');
        $funnel = $this->publishedDigitalFunnel();
        $order = $this->payOrder($funnel);
        $delivery = OrderItemDelivery::query()->whereHas('orderItem', fn ($q) => $q->where('order_id', $order->id))->firstOrFail();
        $asset = ProductDigitalAsset::query()->firstOrFail();
        Storage::disk('local')->put($asset->file_path, 'file contents');

        $this->get("/unduh/{$delivery->download_token}/{$asset->id}");
        $this->get("/unduh/{$delivery->download_token}/{$asset->id}");
        $response = $this->get("/unduh/{$delivery->download_token}/{$asset->id}");

        $response->assertForbidden();
    }

    public function test_download_blocked_after_expiry(): void
    {
        Storage::fake('local');
        $funnel = $this->publishedDigitalFunnel();
        $order = $this->payOrder($funnel);
        $delivery = OrderItemDelivery::query()->whereHas('orderItem', fn ($q) => $q->where('order_id', $order->id))->firstOrFail();
        $delivery->update(['expires_at' => now()->subDay()]);
        $asset = ProductDigitalAsset::query()->firstOrFail();
        Storage::disk('local')->put($asset->file_path, 'file contents');

        $response = $this->get("/unduh/{$delivery->download_token}/{$asset->id}");

        $response->assertForbidden();
    }
}
