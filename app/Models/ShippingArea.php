<?php

namespace App\Models;

use Database\Factories\ShippingAreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A local mirror of one RajaOngkir sub-district (kelurahan/desa), flattened
 * with its full province/city/district ancestry so checkout destination
 * search never has to hit the provider's API. `id` is RajaOngkir's own
 * sub-district id (not auto-generated) — it's the same id space accepted by
 * the calculate-cost endpoint's origin/destination params.
 *
 * @property int $id
 * @property int $rajaongkir_province_id
 * @property int $rajaongkir_city_id
 * @property int $rajaongkir_district_id
 * @property string $province_name
 * @property string $city_name
 * @property string $district_name
 * @property string $subdistrict_name
 * @property string $zip_code
 * @property string $label
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'id',
    'rajaongkir_province_id',
    'rajaongkir_city_id',
    'rajaongkir_district_id',
    'province_name',
    'city_name',
    'district_name',
    'subdistrict_name',
    'zip_code',
    'label',
])]
class ShippingArea extends Model
{
    /** @use HasFactory<ShippingAreaFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'int';
}
