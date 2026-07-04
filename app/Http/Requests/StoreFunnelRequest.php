<?php

namespace App\Http\Requests;

use App\Concerns\FunnelValidationRules;
use App\Models\Funnel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFunnelRequest extends FormRequest
{
    use FunnelValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Funnel::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->funnelRules();
    }
}
