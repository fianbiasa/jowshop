<?php

namespace App\Models;

use Database\Factories\BrandingSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string|null $logo_path
 * @property string|null $address
 * @property string|null $email
 * @property string|null $phone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['logo_path', 'address', 'email', 'phone'])]
class BrandingSetting extends Model
{
    /** @use HasFactory<BrandingSettingFactory> */
    use HasFactory;

    public function logoUrl(): ?string
    {
        return $this->logo_path !== null
            ? Storage::disk('public')->url($this->logo_path)
            : null;
    }
}
