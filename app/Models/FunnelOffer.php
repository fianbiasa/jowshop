<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\OfferStage;
use App\Enums\OfferTriggerCondition;
use Database\Factories\FunnelOfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $funnel_id
 * @property int $product_id
 * @property int|null $parent_offer_id
 * @property OfferTriggerCondition $trigger_condition
 * @property OfferStage $stage
 * @property int $sequence
 * @property string $headline
 * @property string|null $description
 * @property string|null $media_url
 * @property string|null $price_override
 * @property DiscountType $discount_type
 * @property string|null $discount_value
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'product_id',
    'parent_offer_id',
    'trigger_condition',
    'stage',
    'sequence',
    'headline',
    'description',
    'media_url',
    'price_override',
    'discount_type',
    'discount_value',
    'is_active',
])]
class FunnelOffer extends Model
{
    /** @use HasFactory<FunnelOfferFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trigger_condition' => OfferTriggerCondition::class,
            'stage' => OfferStage::class,
            'discount_type' => DiscountType::class,
            'price_override' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Funnel, $this>
     */
    public function funnel(): BelongsTo
    {
        return $this->belongsTo(Funnel::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<FunnelOffer, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(FunnelOffer::class, 'parent_offer_id');
    }

    /**
     * @return HasMany<FunnelOffer, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(FunnelOffer::class, 'parent_offer_id');
    }

    public function isRoot(): bool
    {
        return $this->parent_offer_id === null;
    }

    /**
     * Get the child offer that should be shown next given how the visitor
     * responded to this offer (accepted or declined).
     */
    public function nextOfferFor(OfferTriggerCondition $response): ?self
    {
        return $this->children()
            ->where('trigger_condition', $response)
            ->where('is_active', true)
            ->orderBy('sequence')
            ->first();
    }

    /**
     * The price to charge if this offer is accepted: the override price (or
     * the underlying product's price) with the configured discount applied.
     */
    public function effectivePrice(): float
    {
        if (! $this->relationLoaded('product')) {
            $this->load('product');
        }

        $price = (float) ($this->price_override ?? $this->product->price);

        return match ($this->discount_type) {
            DiscountType::None => $price,
            DiscountType::Percentage => max(0, $price * (1 - ((float) $this->discount_value / 100))),
            DiscountType::Fixed => max(0, $price - (float) $this->discount_value),
        };
    }
}
