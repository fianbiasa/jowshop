<?php

namespace App\Concerns;

use App\Enums\ShipmentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ShipmentValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function shipmentRules(): array
    {
        return [
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ShipmentStatus::class)],
        ];
    }
}
