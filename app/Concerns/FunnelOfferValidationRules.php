<?php

namespace App\Concerns;

use App\Enums\DiscountType;
use App\Enums\OfferStage;
use App\Enums\OfferTriggerCondition;
use App\Models\Funnel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait FunnelOfferValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function funnelOfferRules(Funnel $funnel): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'parent_offer_id' => [
                'nullable',
                'integer',
                Rule::exists('funnel_offers', 'id')->where('funnel_id', $funnel->id),
            ],
            'trigger_condition' => [
                'required',
                Rule::enum(OfferTriggerCondition::class),
                Rule::when(
                    $this->input('parent_offer_id') === null,
                    ['in:'.OfferTriggerCondition::Initial->value],
                    ['in:'.OfferTriggerCondition::Accepted->value.','.OfferTriggerCondition::Declined->value],
                ),
            ],
            'stage' => ['required', Rule::enum(OfferStage::class)],
            'sequence' => ['required', 'integer', 'min:0'],
            'headline' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'media_url' => ['nullable', 'url', 'max:2048'],
            'price_override' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['required', Rule::enum(DiscountType::class)],
            'discount_value' => ['required_unless:discount_type,'.DiscountType::None->value, 'nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
