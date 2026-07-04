<?php

namespace App\Http\Requests;

use App\Concerns\CheckoutValidationRules;
use App\Models\Funnel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
    use CheckoutValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Funnel $funnel */
        $funnel = $this->route('funnel');

        return $this->checkoutRules($funnel);
    }
}
