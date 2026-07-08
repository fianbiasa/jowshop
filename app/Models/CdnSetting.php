<?php

namespace App\Models;

use Database\Factories\CdnSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $pull_zone_url
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['pull_zone_url', 'is_active'])]
class CdnSetting extends Model
{
    /** @use HasFactory<CdnSettingFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Whether the CDN should actually be used for generated URLs — active
     * and pointed somewhere, not just toggled on.
     */
    public function isConfigured(): bool
    {
        return $this->is_active && filled($this->pull_zone_url);
    }
}
