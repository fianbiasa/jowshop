<?php

namespace Tests\Feature;

use App\Enums\FunnelEventType;
use App\Enums\MetaCapiEventStatus;
use App\Jobs\SendMetaConversionEvent;
use App\Models\Funnel;
use App\Models\FunnelSession;
use App\Models\MetaCapiEventLog;
use App\Models\MetaCapiSetting;
use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\Salespage;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaConversionsApiTest extends TestCase
{
    use RefreshDatabase;

    private function publishedFunnel(string $type = 'digital', ?string $pixelId = null): Funnel
    {
        $product = Product::factory()->{$type}()->published()->create(['price' => 45000]);

        $funnel = Funnel::factory()->published()->create([
            'product_id' => $product->id,
            'slug' => 'kopi',
            'pixel_settings' => $pixelId !== null ? ['fb_pixel_id' => $pixelId] : null,
        ]);

        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        return $funnel;
    }

    public function test_no_capi_call_is_made_without_active_settings(): void
    {
        Http::fake();
        $funnel = $this->publishedFunnel();

        $this->get("/f/{$funnel->slug}");

        Http::assertNothingSent();
        $this->assertSame(0, MetaCapiEventLog::query()->count());
    }

    public function test_salespage_view_sends_view_content_event(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);
        MetaCapiSetting::factory()->create(['pixel_id' => '999888777']);
        $funnel = $this->publishedFunnel();

        $this->get("/f/{$funnel->slug}");

        Http::assertSent(fn ($request) => str_contains($request->url(), '999888777/events')
            && $request['data'][0]['event_name'] === 'ViewContent');

        $log = MetaCapiEventLog::query()->firstOrFail();
        $this->assertSame('ViewContent', $log->event_name);
        $this->assertSame(MetaCapiEventStatus::Sent, $log->status);
        $this->assertSame(1, $log->attempts);
    }

    public function test_funnel_specific_pixel_id_overrides_default(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);
        MetaCapiSetting::factory()->create(['pixel_id' => 'default-pixel']);
        $funnel = $this->publishedFunnel('digital', 'funnel-specific-pixel');

        $this->get("/f/{$funnel->slug}");

        Http::assertSent(fn ($request) => str_contains($request->url(), 'funnel-specific-pixel/events'));
    }

    public function test_checkout_view_sends_initiate_checkout_event(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);
        MetaCapiSetting::factory()->create();
        $funnel = $this->publishedFunnel();

        $this->get("/f/{$funnel->slug}/checkout");

        $log = MetaCapiEventLog::query()->firstOrFail();
        $this->assertSame('InitiateCheckout', $log->event_name);
    }

    public function test_non_ad_relevant_event_is_not_sent(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);
        MetaCapiSetting::factory()->create();
        $visitor = Visitor::factory()->create();
        $session = FunnelSession::factory()->for($visitor)->create();
        $event = $session->recordEvent(FunnelEventType::BumpView);

        SendMetaConversionEvent::dispatch($event->id);

        Http::assertNothingSent();
        $this->assertSame(0, MetaCapiEventLog::query()->count());
    }

    public function test_payment_success_sends_purchase_event_with_hashed_advanced_matching(): void
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
                'paymentUrl' => 'https://sandbox.duitku.com/pay/abc123',
                'statusCode' => '00',
            ], 200),
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);
        $settings = PaymentSetting::factory()->create();
        MetaCapiSetting::factory()->create(['pixel_id' => '111222333']);

        $funnel = $this->publishedFunnel();
        $this->post("/f/{$funnel->slug}/checkout", [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
        ]);
        $order = Order::query()->firstOrFail();
        $this->post("/f/{$funnel->slug}/checkout/bayar", ['payment_method' => 'VC']);

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

        $expectedEmailHash = hash('sha256', 'budi@example.com');
        $expectedPhoneHash = hash('sha256', '081234567890');

        Http::assertSent(function ($request) use ($expectedEmailHash, $expectedPhoneHash) {
            if (! str_contains($request->url(), '111222333/events')) {
                return false;
            }

            $data = $request['data'][0];

            return $data['event_name'] === 'Purchase'
                && $data['custom_data']['currency'] === 'IDR'
                && $data['user_data']['em'][0] === $expectedEmailHash
                && $data['user_data']['ph'][0] === $expectedPhoneHash;
        });

        $log = MetaCapiEventLog::query()->where('event_name', 'Purchase')->firstOrFail();
        $this->assertSame(MetaCapiEventStatus::Sent, $log->status);
    }

    public function test_failed_capi_response_is_logged_as_failed(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => 'invalid token'], 400)]);
        MetaCapiSetting::factory()->create();
        $funnel = $this->publishedFunnel();

        $this->get("/f/{$funnel->slug}");

        $log = MetaCapiEventLog::query()->firstOrFail();
        $this->assertSame(MetaCapiEventStatus::Failed, $log->status);
        $this->assertSame(400, $log->response_code);
    }

    public function test_repeat_dispatch_for_same_event_does_not_create_duplicate_log_rows(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);
        MetaCapiSetting::factory()->create();
        $visitor = Visitor::factory()->create();
        $session = FunnelSession::factory()->for($visitor)->create();
        $event = $session->recordEvent(FunnelEventType::SalespageView);

        SendMetaConversionEvent::dispatch($event->id);
        SendMetaConversionEvent::dispatch($event->id);

        $this->assertSame(1, MetaCapiEventLog::query()->count());
        $this->assertSame(2, MetaCapiEventLog::query()->firstOrFail()->attempts);
    }

    public function test_settings_page_hides_access_token_from_summary(): void
    {
        MetaCapiSetting::factory()->create(['access_token' => 'super-secret']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('meta-capi-settings.edit'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('settings/meta-capi')
            ->where('metaCapiSetting.is_configured', true)
            ->missing('metaCapiSetting.access_token')
        );
    }
}
