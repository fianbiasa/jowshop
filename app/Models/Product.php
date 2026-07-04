<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $created_by
 * @property string $name
 * @property string $slug
 * @property ProductType $type
 * @property string|null $description
 * @property string $price
 * @property string|null $thumbnail_path
 * @property string|null $sku
 * @property ProductStatus $status
 * @property int|null $weight_grams
 * @property string|null $length_cm
 * @property string|null $width_cm
 * @property string|null $height_cm
 * @property int|null $stock
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'slug',
    'type',
    'description',
    'price',
    'thumbnail_path',
    'sku',
    'status',
    'weight_grams',
    'length_cm',
    'width_cm',
    'height_cm',
    'stock',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'status' => ProductStatus::class,
            'price' => 'decimal:2',
            'length_cm' => 'decimal:2',
            'width_cm' => 'decimal:2',
            'height_cm' => 'decimal:2',
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
     * @return HasMany<ProductDigitalAsset, $this>
     */
    public function digitalAssets(): HasMany
    {
        return $this->hasMany(ProductDigitalAsset::class);
    }

    /**
     * @return HasMany<Funnel, $this>
     */
    public function funnels(): HasMany
    {
        return $this->hasMany(Funnel::class);
    }

    /**
     * @return HasMany<FunnelOffer, $this>
     */
    public function funnelOffers(): HasMany
    {
        return $this->hasMany(FunnelOffer::class);
    }

    public function isDigital(): bool
    {
        return $this->type === ProductType::Digital;
    }

    public function isPhysical(): bool
    {
        return $this->type === ProductType::Physical;
    }
}
