<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Enums\OfferStage;
use App\Enums\OfferTriggerCondition;
use App\Models\Funnel;
use App\Models\FunnelOffer;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FunnelOffer>
 */
class FunnelOfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'funnel_id' => Funnel::factory(),
            'product_id' => Product::factory(),
            'parent_offer_id' => null,
            'trigger_condition' => OfferTriggerCondition::Initial,
            'stage' => OfferStage::Bump,
            'sequence' => 0,
            'headline' => ucfirst($this->faker->word().' '.$this->faker->word().' '.$this->faker->word()),
            'description' => $this->faker->sentence(),
            'price_override' => $this->faker->randomFloat(2, 5_000, 50_000),
            'discount_type' => DiscountType::None,
            'is_active' => true,
        ];
    }

    public function bump(): static
    {
        return $this->state(fn (array $attributes) => ['stage' => OfferStage::Bump]);
    }

    public function upsell(): static
    {
        return $this->state(fn (array $attributes) => ['stage' => OfferStage::Upsell]);
    }

    public function downsell(): static
    {
        return $this->state(fn (array $attributes) => ['stage' => OfferStage::Downsell]);
    }

    public function childOf(FunnelOffer $parent, OfferTriggerCondition $condition): static
    {
        return $this->state(fn (array $attributes) => [
            'funnel_id' => $parent->funnel_id,
            'parent_offer_id' => $parent->id,
            'trigger_condition' => $condition,
            'stage' => $parent->stage,
        ]);
    }
}
