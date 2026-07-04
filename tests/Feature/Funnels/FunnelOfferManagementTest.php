<?php

namespace Tests\Feature\Funnels;

use App\Enums\DiscountType;
use App\Enums\OfferStage;
use App\Enums\OfferTriggerCondition;
use App\Models\Funnel;
use App\Models\FunnelOffer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FunnelOfferManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_bump_offer_can_be_created(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();
        $product = Product::factory()->for($user, 'creator')->create();

        $response = $this->actingAs($user)->post(route('funnels.offers.store', $funnel), [
            'product_id' => $product->id,
            'parent_offer_id' => null,
            'trigger_condition' => OfferTriggerCondition::Initial->value,
            'stage' => OfferStage::Bump->value,
            'sequence' => 0,
            'headline' => 'Tambah Gula?',
            'price_override' => 5000,
            'discount_type' => DiscountType::None->value,
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('funnels.edit', $funnel));

        $this->assertDatabaseHas('funnel_offers', [
            'funnel_id' => $funnel->id,
            'headline' => 'Tambah Gula?',
            'parent_offer_id' => null,
        ]);
    }

    public function test_declined_child_offer_can_be_created_under_a_bump(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();
        $product = Product::factory()->for($user, 'creator')->create();
        $bumpGula = FunnelOffer::factory()->for($funnel)->bump()->create();

        $response = $this->actingAs($user)->post(route('funnels.offers.store', $funnel), [
            'product_id' => $product->id,
            'parent_offer_id' => $bumpGula->id,
            'trigger_condition' => OfferTriggerCondition::Declined->value,
            'stage' => OfferStage::Bump->value,
            'sequence' => 0,
            'headline' => 'Tambah Kental Manis?',
            'discount_type' => DiscountType::None->value,
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('funnel_offers', [
            'parent_offer_id' => $bumpGula->id,
            'trigger_condition' => OfferTriggerCondition::Declined->value,
            'headline' => 'Tambah Kental Manis?',
        ]);
    }

    public function test_root_offer_must_use_initial_trigger_condition(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();
        $product = Product::factory()->for($user, 'creator')->create();

        $response = $this->actingAs($user)->post(route('funnels.offers.store', $funnel), [
            'product_id' => $product->id,
            'parent_offer_id' => null,
            'trigger_condition' => OfferTriggerCondition::Accepted->value,
            'stage' => OfferStage::Bump->value,
            'sequence' => 0,
            'headline' => 'Offer tidak valid',
            'discount_type' => DiscountType::None->value,
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors('trigger_condition');
    }

    public function test_child_offer_cannot_use_initial_trigger_condition(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();
        $product = Product::factory()->for($user, 'creator')->create();
        $bumpGula = FunnelOffer::factory()->for($funnel)->bump()->create();

        $response = $this->actingAs($user)->post(route('funnels.offers.store', $funnel), [
            'product_id' => $product->id,
            'parent_offer_id' => $bumpGula->id,
            'trigger_condition' => OfferTriggerCondition::Initial->value,
            'stage' => OfferStage::Bump->value,
            'sequence' => 0,
            'headline' => 'Offer tidak valid',
            'discount_type' => DiscountType::None->value,
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors('trigger_condition');
    }

    public function test_parent_offer_must_belong_to_the_same_funnel(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();
        $otherFunnel = Funnel::factory()->for($user, 'creator')->create();
        $product = Product::factory()->for($user, 'creator')->create();
        $offerFromOtherFunnel = FunnelOffer::factory()->for($otherFunnel)->bump()->create();

        $response = $this->actingAs($user)->post(route('funnels.offers.store', $funnel), [
            'product_id' => $product->id,
            'parent_offer_id' => $offerFromOtherFunnel->id,
            'trigger_condition' => OfferTriggerCondition::Declined->value,
            'stage' => OfferStage::Bump->value,
            'sequence' => 0,
            'headline' => 'Offer tidak valid',
            'discount_type' => DiscountType::None->value,
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors('parent_offer_id');
    }

    public function test_offer_can_be_updated(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();
        $offer = FunnelOffer::factory()->for($funnel)->bump()->create(['headline' => 'Lama']);

        $response = $this->actingAs($user)->put(route('funnels.offers.update', [$funnel, $offer]), [
            'product_id' => $offer->product_id,
            'parent_offer_id' => null,
            'trigger_condition' => OfferTriggerCondition::Initial->value,
            'stage' => OfferStage::Bump->value,
            'sequence' => 0,
            'headline' => 'Baru',
            'discount_type' => DiscountType::None->value,
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Baru', $offer->fresh()->headline);
    }

    public function test_deleting_offer_cascades_to_its_children(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();
        $bumpGula = FunnelOffer::factory()->for($funnel)->bump()->create();
        $bumpKentalManis = FunnelOffer::factory()
            ->childOf($bumpGula, OfferTriggerCondition::Declined)
            ->create();

        $response = $this->actingAs($user)->delete(route('funnels.offers.destroy', [$funnel, $bumpGula]));

        $response->assertRedirect(route('funnels.edit', $funnel));
        $this->assertDatabaseMissing('funnel_offers', ['id' => $bumpGula->id]);
        $this->assertDatabaseMissing('funnel_offers', ['id' => $bumpKentalManis->id]);
    }

    public function test_discount_value_is_required_when_discount_type_is_not_none(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();
        $product = Product::factory()->for($user, 'creator')->create();

        $response = $this->actingAs($user)->post(route('funnels.offers.store', $funnel), [
            'product_id' => $product->id,
            'parent_offer_id' => null,
            'trigger_condition' => OfferTriggerCondition::Initial->value,
            'stage' => OfferStage::Bump->value,
            'sequence' => 0,
            'headline' => 'Diskon tanpa nilai',
            'discount_type' => DiscountType::Percentage->value,
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors('discount_value');
    }
}
