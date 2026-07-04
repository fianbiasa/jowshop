<?php

namespace App\Models;

use Database\Factories\OrderItemDeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_item_id
 * @property string $download_token
 * @property string|null $license_key
 * @property int|null $max_downloads
 * @property int $download_count
 * @property Carbon|null $expires_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['order_item_id', 'download_token', 'license_key', 'max_downloads', 'download_count', 'expires_at', 'delivered_at'])]
class OrderItemDelivery extends Model
{
    /** @use HasFactory<OrderItemDeliveryFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasReachedDownloadLimit(): bool
    {
        return $this->max_downloads !== null && $this->download_count >= $this->max_downloads;
    }
}
