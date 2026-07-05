<?php

namespace App\Http\Requests;

use App\Enums\SalespageStyle;
use App\Models\Funnel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateSalespageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Funnel $funnel */
        $funnel = $this->route('funnel');

        return $this->user()->can('update', $funnel);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ai_provider_setting_id' => ['required', 'integer', 'exists:ai_provider_settings,id'],
            'style' => ['required', Rule::enum(SalespageStyle::class)],
            'brief' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
