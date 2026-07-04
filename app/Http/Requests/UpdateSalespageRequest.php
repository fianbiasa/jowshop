<?php

namespace App\Http\Requests;

use App\Concerns\SalespageValidationRules;
use App\Models\Funnel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSalespageRequest extends FormRequest
{
    use SalespageValidationRules;

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
        return $this->salespageRules();
    }
}
