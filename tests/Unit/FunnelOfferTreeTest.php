<?php

namespace Tests\Unit;

use App\Enums\OfferStage;
use App\Enums\OfferTriggerCondition;
use App\Models\Funnel;
use App\Models\FunnelOffer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FunnelOfferTreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_offer_for_stage_is_resolved(): void
    {
        $funnel = Funnel::factory()->create();

        $bump = FunnelOffer::factory()->for($funnel)->bump()->create([
            'sequence' => 1,
        ]);

        $this->assertTrue(
            $funnel->rootOfferForStage(OfferStage::Bump)->is($bump)
        );

        $this->assertNull($funnel->rootOfferForStage(OfferStage::Upsell));
    }

    public function test_declined_bump_resolves_to_next_bump_in_chain(): void
    {
        $funnel = Funnel::factory()->create();

        $bumpGula = FunnelOffer::factory()->for($funnel)->bump()->create([
            'headline' => 'Tambah Gula?',
            'sequence' => 1,
        ]);

        $bumpKentalManis = FunnelOffer::factory()
            ->childOf($bumpGula, OfferTriggerCondition::Declined)
            ->create([
                'headline' => 'Tambah Kental Manis?',
                'sequence' => 1,
            ]);

        $next = $bumpGula->nextOfferFor(OfferTriggerCondition::Declined);

        $this->assertNotNull($next);
        $this->assertTrue($next->is($bumpKentalManis));
        $this->assertNull($bumpGula->nextOfferFor(OfferTriggerCondition::Accepted));
    }

    public function test_accepted_bump_has_no_further_chain_by_default(): void
    {
        $funnel = Funnel::factory()->create();

        $bumpGula = FunnelOffer::factory()->for($funnel)->bump()->create();

        $this->assertNull($bumpGula->nextOfferFor(OfferTriggerCondition::Accepted));
        $this->assertNull($bumpGula->nextOfferFor(OfferTriggerCondition::Declined));
    }

    public function test_declined_upsell_resolves_to_downsell(): void
    {
        $funnel = Funnel::factory()->create();

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

        $next = $upsell->nextOfferFor(OfferTriggerCondition::Declined);

        $this->assertNotNull($next);
        $this->assertTrue($next->is($downsell));
        $this->assertSame(OfferStage::Downsell, $downsell->stage);
        $this->assertTrue($downsell->parent->is($upsell));
    }

    public function test_inactive_child_offer_is_not_resolved(): void
    {
        $funnel = Funnel::factory()->create();

        $bumpGula = FunnelOffer::factory()->for($funnel)->bump()->create();

        FunnelOffer::factory()
            ->childOf($bumpGula, OfferTriggerCondition::Declined)
            ->create(['is_active' => false]);

        $this->assertNull($bumpGula->nextOfferFor(OfferTriggerCondition::Declined));
    }

    public function test_offer_is_root_only_when_it_has_no_parent(): void
    {
        $funnel = Funnel::factory()->create();

        $root = FunnelOffer::factory()->for($funnel)->bump()->create();
        $child = FunnelOffer::factory()->childOf($root, OfferTriggerCondition::Declined)->create();

        $this->assertTrue($root->isRoot());
        $this->assertFalse($child->isRoot());
    }
}
