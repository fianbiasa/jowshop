<?php

namespace App\Models;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $customer_id
 * @property string $recipient_name
 * @property string $phone
 * @property string $province
 * @property string $city
 * @property string $district
 * @property string $postal_code
 * @property string|null $destination_area_id
 * @property string|null $destination_label
 * @property string $address_line
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'customer_id',
    'recipient_name',
    'phone',
    'province',
    'city',
    'district',
    'postal_code',
    'destination_area_id',
    'destination_label',
    'address_line',
    'notes',
])]
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
