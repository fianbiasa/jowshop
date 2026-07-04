<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $funnel_id
 * @property int $customer_id
 * @property int|null $address_id
 * @property int|null $visitor_id
 * @property string $order_number
 * @property string $subtotal
 * @property string $discount_total
 * @property string $shipping_cost
 * @property string $total
 * @property OrderStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'funnel_id',
    'customer_id',
    'address_id',
    'visitor_id',
    'order_number',
    'subtotal',
    'discount_total',
    'shipping_cost',
    'total',
    'status',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total' => 'decimal:2',
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
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * @return BelongsTo<Visitor, $this>
     */
    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasOne<Shipment, $this>
     */
    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }

    /**
     * Total weight (in grams) of all physical items currently on this order.
     */
    public function totalPhysicalWeightGrams(): int
    {
        $this->loadMissing('items.product');

        return (int) $this->items
            ->filter(fn (OrderItem $item) => $item->product->isPhysical())
            ->sum(fn (OrderItem $item) => (int) $item->product->weight_grams * $item->quantity);
    }

    public static function generateOrderNumber(): string
    {
        return 'ORD-'.strtoupper(Str::random(8));
    }

    /**
     * Recalculate subtotal/total from the current order items.
     */
    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->get()->reduce(
            fn (float $carry, OrderItem $item): float => $carry + ((float) $item->unit_price * $item->quantity),
            0.0,
        );

        $this->subtotal = (string) $subtotal;
        $this->total = (string) ($subtotal - (float) $this->discount_total + (float) $this->shipping_cost);
        $this->save();
    }
}
