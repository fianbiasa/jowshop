<?php

namespace App\Http\Requests;

use App\Concerns\FunnelOfferValidationRules;
use App\Models\Funnel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFunnelOfferRequest extends FormRequest
{
    use FunnelOfferValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->funnel());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->funnelOfferRules($this->funnel());
    }

    /**
     * Get the funnel the offer belongs to from the route.
     */
    protected function funnel(): Funnel
    {
        /** @var Funnel $funnel */
        $funnel = $this->route('funnel');

        return $funnel;
    }
}
