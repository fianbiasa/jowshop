<?php

namespace App\Models;

use Database\Factories\MetaCapiSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $pixel_id
 * @property string $access_token
 * @property string|null $test_event_code
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['pixel_id', 'access_token', 'test_event_code', 'is_active'])]
#[Hidden(['access_token'])]
class MetaCapiSetting extends Model
{
    /** @use HasFactory<MetaCapiSettingFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }
}
