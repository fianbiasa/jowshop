<?php

namespace App\Models;

use App\Enums\PaymentEnvironment;
use Database\Factories\PaymentSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $merchant_code
 * @property string $api_key
 * @property PaymentEnvironment $environment
 * @property array<int, string>|null $enabled_methods
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['merchant_code', 'api_key', 'environment', 'enabled_methods', 'is_active'])]
#[Hidden(['merchant_code', 'api_key'])]
class PaymentSetting extends Model
{
    /** @use HasFactory<PaymentSettingFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'merchant_code' => 'encrypted',
            'api_key' => 'encrypted',
            'environment' => PaymentEnvironment::class,
            'enabled_methods' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function baseApiUrl(): string
    {
        return $this->environment === PaymentEnvironment::Production
            ? 'https://passport.duitku.com/webapi/api/merchant/v2'
            : 'https://sandbox.duitku.com/webapi/api/merchant/v2';
    }
}
