<?php

namespace App\Models;

use App\Enums\OrderItemType;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int|null $funnel_offer_id
 * @property OrderItemType $offer_type
 * @property int $quantity
 * @property string $unit_price
 * @property Carbon|null $stock_decremented_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['order_id', 'product_id', 'funnel_offer_id', 'offer_type', 'quantity', 'unit_price', 'stock_decremented_at'])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'offer_type' => OrderItemType::class,
            'unit_price' => 'decimal:2',
            'stock_decremented_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
    public function funnelOffer(): BelongsTo
    {
        return $this->belongsTo(FunnelOffer::class);
    }

    /**
     * @return HasOne<OrderItemDelivery, $this>
     */
    public function delivery(): HasOne
    {
        return $this->hasOne(OrderItemDelivery::class);
    }
}
