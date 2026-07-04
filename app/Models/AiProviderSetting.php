<?php

namespace App\Models;

use App\Enums\AiProvider;
use Database\Factories\AiProviderSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $created_by
 * @property AiProvider $provider
 * @property string $label
 * @property string $api_key
 * @property string $default_model
 * @property bool $is_default
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['provider', 'label', 'api_key', 'default_model', 'is_default', 'is_active'])]
#[Hidden(['api_key'])]
class AiProviderSetting extends Model
{
    /** @use HasFactory<AiProviderSettingFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => AiProvider::class,
            'api_key' => 'encrypted',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<AiGenerationLog, $this>
     */
    public function generationLogs(): HasMany
    {
        return $this->hasMany(AiGenerationLog::class);
    }
}
