<?php

namespace App\Models;

use App\Enums\SalespageStyle;
use Database\Factories\SalespageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $funnel_id
 * @property string $title
 * @property array<int, array<string, mixed>> $content
 * @property SalespageStyle $style
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property string|null $og_image_path
 * @property bool $generated_by_ai
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'funnel_id',
    'title',
    'content',
    'style',
    'seo_title',
    'seo_description',
    'og_image_path',
    'generated_by_ai',
    'published_at',
])]
class Salespage extends Model
{
    /** @use HasFactory<SalespageFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'style' => SalespageStyle::class,
            'generated_by_ai' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Funnel, $this>
     */
    public function funnel(): BelongsTo
    {
        return $this->belongsTo(Funnel::class);
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }
}
