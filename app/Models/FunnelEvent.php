<?php

namespace App\Models;

use App\Enums\FunnelEventType;
use Database\Factories\FunnelEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $funnel_session_id
 * @property int|null $funnel_offer_id
 * @property FunnelEventType $event_type
 * @property string $external_event_id
 * @property array<string, mixed>|null $metadata
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['funnel_offer_id', 'event_type', 'external_event_id', 'metadata', 'occurred_at'])]
class FunnelEvent extends Model
{
    /** @use HasFactory<FunnelEventFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => FunnelEventType::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<FunnelSession, $this>
     */
    public function funnelSession(): BelongsTo
    {
        return $this->belongsTo(FunnelSession::class);
    }

    /**
     * @return BelongsTo<FunnelOffer, $this>
     */
    public function funnelOffer(): BelongsTo
    {
        return $this->belongsTo(FunnelOffer::class);
    }
}
