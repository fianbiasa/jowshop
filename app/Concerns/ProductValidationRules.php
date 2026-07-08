<?php

namespace App\Concerns;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProductValidationRules
{
    /**
     * Get the validation rules used to validate products.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function productRules(?int $productId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                $productId === null
                    ? Rule::unique(Product::class)
                    : Rule::unique(Product::class)->ignore($productId),
            ],
            'type' => ['required', Rule::enum(ProductType::class)],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
            'remove_thumbnail' => ['boolean'],
            'sku' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'weight_grams' => ['required_if:type,'.ProductType::Physical->value, 'nullable', 'integer', 'min:1'],
            'length_cm' => ['nullable', 'numeric', 'min:0'],
            'width_cm' => ['nullable', 'numeric', 'min:0'],
            'height_cm' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required_if:type,'.ProductType::Physical->value, 'nullable', 'integer', 'min:0'],
        ];
    }
}
