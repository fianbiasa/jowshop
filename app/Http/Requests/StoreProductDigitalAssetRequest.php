<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductDigitalAssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Product $product */
        $product = $this->route('product');

        return $this->user()->can('update', $product);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['nullable', 'required_without:external_url', 'file', 'max:512000'],
            'external_url' => ['nullable', 'required_without:file', 'url', 'max:2048'],
            'license_type' => ['required', Rule::in(['none', 'license_key'])],
            'max_downloads' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
