<?php

namespace App\Models;

use App\Enums\FunnelEventType;
use App\Enums\FunnelSessionStatus;
use Database\Factories\FunnelSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $visitor_id
 * @property int $funnel_id
 * @property int|null $order_id
 * @property FunnelSessionStatus $status
 * @property Carbon $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['visitor_id', 'funnel_id', 'order_id', 'status', 'started_at', 'completed_at'])]
class FunnelSession extends Model
{
    /** @use HasFactory<FunnelSessionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FunnelSessionStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Visitor, $this>
     */
    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    /**
     * @return BelongsTo<Funnel, $this>
     */
    public function funnel(): BelongsTo
    {
        return $this->belongsTo(Funnel::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return HasMany<FunnelEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(FunnelEvent::class);
    }

    /**
     * Record a funnel event for this session, used later for Meta Pixel/CAPI
     * deduplication. Generates the external_event_id server-side unless the
     * caller supplies one (e.g. a client-generated ID for an accept action
     * that also fires the browser pixel immediately, before the server
     * event exists — see CheckoutOfferController::respond()).
     *
     * @param  array<string, mixed>  $metadata
     */
    public function recordEvent(FunnelEventType $type, ?FunnelOffer $offer = null, array $metadata = [], ?string $externalEventId = null): FunnelEvent
    {
        return $this->events()->create([
            'funnel_offer_id' => $offer?->id,
            'event_type' => $type,
            'external_event_id' => $externalEventId ?? (string) Str::uuid(),
            'metadata' => $metadata === [] ? null : $metadata,
            'occurred_at' => now(),
        ]);
    }

    public function hasEvent(FunnelEventType $type, ?FunnelOffer $offer = null): bool
    {
        return $this->events()
            ->where('event_type', $type)
            ->when($offer, fn ($query) => $query->where('funnel_offer_id', $offer->id))
            ->exists();
    }
}
