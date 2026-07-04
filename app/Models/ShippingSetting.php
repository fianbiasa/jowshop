<?php

namespace App\Models;

use App\Enums\ShippingProvider;
use Database\Factories\ShippingSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property ShippingProvider $provider
 * @property string $api_key
 * @property string $origin_area_id
 * @property string|null $origin_label
 * @property array<int, string>|null $enabled_couriers
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['provider', 'api_key', 'origin_area_id', 'origin_label', 'enabled_couriers', 'is_active'])]
#[Hidden(['api_key'])]
class ShippingSetting extends Model
{
    /** @use HasFactory<ShippingSettingFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => ShippingProvider::class,
            'api_key' => 'encrypted',
            'enabled_couriers' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function baseApiUrl(): string
    {
        return 'https://rajaongkir.komerce.id/api/v1';
    }
}
