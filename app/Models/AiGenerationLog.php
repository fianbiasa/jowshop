<?php

namespace App\Models;

use Database\Factories\AiGenerationLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ai_provider_setting_id
 * @property int|null $salespage_id
 * @property string $prompt
 * @property string|null $response_excerpt
 * @property int|null $tokens_input
 * @property int|null $tokens_output
 * @property string|null $estimated_cost
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'ai_provider_setting_id',
    'salespage_id',
    'prompt',
    'response_excerpt',
    'tokens_input',
    'tokens_output',
    'estimated_cost',
    'status',
])]
class AiGenerationLog extends Model
{
    /** @use HasFactory<AiGenerationLogFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<AiProviderSetting, $this>
     */
    public function aiProviderSetting(): BelongsTo
    {
        return $this->belongsTo(AiProviderSetting::class);
    }

    /**
     * @return BelongsTo<Salespage, $this>
     */
    public function salespage(): BelongsTo
    {
        return $this->belongsTo(Salespage::class);
    }
}
