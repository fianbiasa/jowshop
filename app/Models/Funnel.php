<?php

namespace App\Models;

use App\Enums\FunnelStatus;
use App\Enums\OfferStage;
use Database\Factories\FunnelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $created_by
 * @property int $product_id
 * @property string $name
 * @property string $slug
 * @property FunnelStatus $status
 * @property array<string, mixed>|null $thank_you_content
 * @property array<string, mixed>|null $pixel_settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['product_id', 'name', 'slug', 'status', 'thank_you_content', 'pixel_settings'])]
class Funnel extends Model
{
    /** @use HasFactory<FunnelFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FunnelStatus::class,
            'thank_you_content' => 'array',
            'pixel_settings' => 'array',
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
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasOne<Salespage, $this>
     */
    public function salespage(): HasOne
    {
        return $this->hasOne(Salespage::class);
    }

    /**
     * @return HasMany<FunnelOffer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(FunnelOffer::class);
    }

    /**
     * @return HasMany<FunnelSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(FunnelSession::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isPublished(): bool
    {
        return $this->status === FunnelStatus::Published;
    }

    public function fbPixelId(): ?string
    {
        return $this->pixel_settings['fb_pixel_id'] ?? null;
    }

    /**
     * Get the first offer to show for a given stage (bump/upsell/downsell),
     * i.e. the root of that stage's offer tree.
     */
    public function rootOfferForStage(OfferStage $stage): ?FunnelOffer
    {
        return $this->offers()
            ->whereNull('parent_offer_id')
            ->where('stage', $stage)
            ->where('is_active', true)
            ->orderBy('sequence')
            ->first();
    }

    /**
     * Get the list of unmet requirements before this funnel can be published.
     * Empty array means the funnel is ready to publish.
     *
     * @return array<int, string>
     */
    public function publishRequirementErrors(): array
    {
        $errors = [];

        if (! $this->relationLoaded('salespage')) {
            $this->load('salespage');
        }

        if ($this->salespage === null) {
            $errors[] = 'Funnel harus memiliki salespage sebelum dipublish.';
        }

        if (! $this->relationLoaded('product')) {
            $this->load('product');
        }

        if ($this->product?->isDigital()) {
            if (! $this->product->relationLoaded('digitalAssets')) {
                $this->product->load('digitalAssets');
            }

            if ($this->product->digitalAssets->isEmpty()) {
                $errors[] = 'Produk digital utama harus memiliki minimal satu file/aset digital sebelum funnel dipublish.';
            }
        }

        return $errors;
    }
}
