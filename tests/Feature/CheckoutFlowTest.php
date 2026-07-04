<?php

namespace Tests\Feature;

use App\Enums\FunnelEventType;
use App\Enums\FunnelSessionStatus;
use App\Enums\OfferTriggerCondition;
use App\Models\Customer;
use App\Models\Funnel;
use App\Models\FunnelOffer;
use App\Models\FunnelSession;
use App\Models\Order;
use App\Models\Product;
use App\Models\Salespage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    private function publishedFunnel(string $type = 'digital', string $slug = 'kopi'): Funnel
    {
        $product = Product::factory()
            ->{$type}()
            ->published()
            ->create(['price' => 45000]);

        $funnel = Funnel::factory()->published()->create([
            'product_id' => $product->id,
            'slug' => $slug,
        ]);

        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        return $funnel;
    }

    private function buyerPayload(): array
    {
        return [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
        ];
    }

    public function test_unpublished_funnel_checkout_returns_404(): void
    {
        $funnel = Funnel::factory()->create(['slug' => 'draft-funnel']);

        $response = $this->get('/f/draft-funnel/checkout');

        $response->assertNotFound();
    }

    public function test_digital_product_checkout_does_not_require_address(): void
    {
        $funnel = $this->publishedFunnel('digital');

        $response = $this->post("/f/{$funnel->slug}/checkout", $this->buyerPayload());

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, Order::query()->count());
    }

    public function test_physical_product_checkout_requires_address(): void
    {
        $funnel = $this->publishedFunnel('physical');

        $response = $this->post("/f/{$funnel->slug}/checkout", $this->buyerPayload());

        $response->assertSessionHasErrors(['province', 'city', 'district', 'postal_code', 'address_line']);
    }

    public function test_physical_product_checkout_succeeds_with_full_address(): void
    {
        $funnel = $this->publishedFunnel('physical');

        $response = $this->post("/f/{$funnel->slug}/checkout", [
            ...$this->buyerPayload(),
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
            'district' => 'Kebayoran Baru',
            'postal_code' => '12110',
            'destination_area_id' => '17550',
            'destination_label' => 'Kebayoran Baru, Jakarta Selatan, DKI Jakarta, 12110',
            'address_line' => 'Jl. Sudirman No. 1',
        ]);

        $response->assertSessionHasNoErrors();

        $order = Order::query()->firstOrFail();
        $this->assertNotNull($order->address_id);
        $this->assertSame('Jl. Sudirman No. 1', $order->address->address_line);
    }

    public function test_physical_product_out_of_stock_blocks_checkout(): void
    {
        $product = Product::factory()->physical()->published()->create(['stock' => 0]);
        $funnel = Funnel::factory()->published()->create(['product_id' => $product->id, 'slug' => 'kopi']);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        $response = $this->post("/f/{$funnel->slug}/checkout", [
            ...$this->buyerPayload(),
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
            'district' => 'Kebayoran Baru',
            'postal_code' => '12110',
            'destination_area_id' => '17550',
            'destination_label' => 'Kebayoran Baru, Jakarta Selatan, DKI Jakarta, 12110',
            'address_line' => 'Jl. Sudirman No. 1',
        ]);

        $response->assertSessionHasErrors('stock');
        $this->assertSame(0, Order::query()->count());
    }

    public function test_checkout_creates_customer_and_main_order_item(): void
    {
        $funnel = $this->publishedFunnel('digital');

        $this->post("/f/{$funnel->slug}/checkout", $this->buyerPayload());

        $customer = Customer::query()->where('email', 'budi@example.com')->firstOrFail();
        $order = Order::query()->where('customer_id', $customer->id)->firstOrFail();

        $this->assertSame(1, $order->items()->count());
        $this->assertSame('45000.00', $order->subtotal);
    }

    public function test_visitor_is_linked_to_customer_after_checkout(): void
    {
        $funnel = $this->publishedFunnel('digital');

        $this->get("/f/{$funnel->slug}");
        $this->post("/f/{$funnel->slug}/checkout", $this->buyerPayload());

        $customer = Customer::query()->where('email', 'budi@example.com')->firstOrFail();
        $order = Order::query()->firstOrFail();

        $this->assertSame($customer->id, $order->visitor->customer_id);
    }

    public function test_checkout_without_bumps_redirects_straight_to_finish(): void
    {
        $funnel = $this->publishedFunnel('digital');

        $response = $this->post("/f/{$funnel->slug}/checkout", $this->buyerPayload());

        $response->assertRedirect("/f/{$funnel->slug}/checkout/bayar");
    }

    public function test_checkout_with_root_bump_redirects_to_offer(): void
    {
        $funnel = $this->publishedFunnel('digital');
        $bump = FunnelOffer::factory()->for($funnel)->bump()->create(['sequence' => 1]);

        $response = $this->post("/f/{$funnel->slug}/checkout", $this->buyerPayload());

        $response->assertRedirect("/f/{$funnel->slug}/checkout/offers/{$bump->id}");
    }

    public function test_resuming_checkout_redirects_to_current_step_instead_of_form(): void
    {
        $funnel = $this->publishedFunnel('digital');
        $bump = FunnelOffer::factory()->for($funnel)->bump()->create(['sequence' => 1]);

        $this->post("/f/{$funnel->slug}/checkout", $this->buyerPayload());

        $response = $this->get("/f/{$funnel->slug}/checkout");

        $response->assertRedirect("/f/{$funnel->slug}/checkout/offers/{$bump->id}");
    }

    public function test_bump_chain_declined_then_accepted_matches_prd_scenario(): void
    {
        $funnel = $this->publishedFunnel('physical');

        $bumpGula = FunnelOffer::factory()->for($funnel)->bump()->create([
            'headline' => 'Tambah Gula?',
            'price_override' => 5000,
            'sequence' => 1,
        ]);

        $bumpKentalManis = FunnelOffer::factory()
            ->childOf($bumpGula, OfferTriggerCondition::Declined)
            ->create([
                'headline' => 'Tambah Kental Manis?',
                'price_override' => 7000,
                'sequence' => 1,
            ]);

        $this->post("/f/{$funnel->slug}/checkout", [
            ...$this->buyerPayload(),
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
            'district' => 'Kebayoran Baru',
            'postal_code' => '12110',
            'destination_area_id' => '17550',
            'destination_label' => 'Kebayoran Baru, Jakarta Selatan, DKI Jakarta, 12110',
            'address_line' => 'Jl. Sudirman No. 1',
        ]);

        // Browser follows the redirect and views the sugar bump...
        $this->get("/f/{$funnel->slug}/checkout/offers/{$bumpGula->id}");

        // ...and declines it.
        $declineResponse = $this->post("/f/{$funnel->slug}/checkout/offers/{$bumpGula->id}", [
            'response' => 'declined',
        ]);
        $declineResponse->assertRedirect("/f/{$funnel->slug}/checkout/offers/{$bumpKentalManis->id}");

        // Browser follows the redirect and views the condensed milk bump...
        $this->get("/f/{$funnel->slug}/checkout/offers/{$bumpKentalManis->id}");

        // ...then accepts it.
        $acceptResponse = $this->post("/f/{$funnel->slug}/checkout/offers/{$bumpKentalManis->id}", [
            'response' => 'accepted',
        ]);
        $acceptResponse->assertRedirect("/f/{$funnel->slug}/checkout/pengiriman");

        $order = Order::query()->firstOrFail();
        $this->assertSame(2, $order->items()->count());
        $this->assertSame('52000.00', $order->total); // 45000 + 7000

        $session = FunnelSession::query()->firstOrFail();
        $eventTypes = $session->events()->pluck('event_type')->map(fn ($type) => $type->value)->all();

        $this->assertContains(FunnelEventType::BumpView->value, $eventTypes);
        $this->assertContains(FunnelEventType::BumpDeclined->value, $eventTypes);
        $this->assertContains(FunnelEventType::BumpAccepted->value, $eventTypes);
        $this->assertSame(
            2,
            $session->events()->where('event_type', FunnelEventType::BumpView)->count()
        );
    }

    public function test_declining_bump_with_no_further_chain_redirects_to_finish(): void
    {
        $funnel = $this->publishedFunnel('digital');
        $bump = FunnelOffer::factory()->for($funnel)->bump()->create(['sequence' => 1]);

        $this->post("/f/{$funnel->slug}/checkout", $this->buyerPayload());

        $response = $this->post("/f/{$funnel->slug}/checkout/offers/{$bump->id}", [
            'response' => 'declined',
        ]);

        $response->assertRedirect("/f/{$funnel->slug}/checkout/bayar");

        $order = Order::query()->firstOrFail();
        $this->assertSame(1, $order->items()->count());
    }

    public function test_offer_from_another_funnel_returns_404(): void
    {
        $funnel = $this->publishedFunnel('digital');
        $otherFunnel = $this->publishedFunnel('digital', 'lain');
        $foreignOffer = FunnelOffer::factory()->for($otherFunnel)->bump()->create();

        $this->post("/f/{$funnel->slug}/checkout", $this->buyerPayload());

        $response = $this->get("/f/{$funnel->slug}/checkout/offers/{$foreignOffer->id}");

        $response->assertNotFound();
    }

    public function test_accepting_bump_twice_does_not_duplicate_order_item(): void
    {
        $funnel = $this->publishedFunnel('digital');
        $bump = FunnelOffer::factory()->for($funnel)->bump()->create(['sequence' => 1]);

        $this->post("/f/{$funnel->slug}/checkout", $this->buyerPayload());
        $this->post("/f/{$funnel->slug}/checkout/offers/{$bump->id}", ['response' => 'accepted']);
        $this->post("/f/{$funnel->slug}/checkout/offers/{$bump->id}", ['response' => 'accepted']);

        $order = Order::query()->firstOrFail();
        $this->assertSame(2, $order->items()->count());
    }

    public function test_pay_without_an_order_in_session_returns_404(): void
    {
        $funnel = $this->publishedFunnel('digital');

        $response = $this->get("/f/{$funnel->slug}/checkout/bayar");

        $response->assertNotFound();
    }
}
