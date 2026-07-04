<?php

namespace App\Models;

use Database\Factories\ProductDigitalAssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_id
 * @property string|null $file_path
 * @property string|null $external_url
 * @property string $license_type
 * @property int|null $max_downloads
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['file_path', 'external_url', 'license_type', 'max_downloads'])]
class ProductDigitalAsset extends Model
{
    /** @use HasFactory<ProductDigitalAssetFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
