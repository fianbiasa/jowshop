<?php

namespace App\Models;

use App\Enums\MetaCapiEventStatus;
use Database\Factories\MetaCapiEventLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $funnel_event_id
 * @property string $event_name
 * @property MetaCapiEventStatus $status
 * @property int|null $response_code
 * @property string|null $response_body
 * @property int $attempts
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['funnel_event_id', 'event_name', 'status', 'response_code', 'response_body', 'attempts'])]
class MetaCapiEventLog extends Model
{
    /** @use HasFactory<MetaCapiEventLogFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MetaCapiEventStatus::class,
        ];
    }

    /**
     * @return BelongsTo<FunnelEvent, $this>
     */
    public function funnelEvent(): BelongsTo
    {
        return $this->belongsTo(FunnelEvent::class);
    }
}
