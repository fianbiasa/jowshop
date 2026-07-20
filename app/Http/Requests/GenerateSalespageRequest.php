<?php

namespace App\Http\Requests;

use App\Enums\LandingPageType;
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
            'landing_page_type' => ['nullable', Rule::enum(LandingPageType::class)],
            'source_url' => ['nullable', 'url', 'max:2048'],
            // Checked by extension (not Laravel's `mimes` rule) because the
            // MIME-to-extension guesser used by `mimes` doesn't reliably
            // recognize .md files (often sniffed as generic text/plain
            // without "md" in its guessed-extensions list), which would
            // reject legitimate uploads.
            'source_document' => [
                'nullable',
                'file',
                'max:10240',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! in_array(strtolower($value->getClientOriginalExtension()), ['txt', 'md', 'pdf'], true)) {
                        $fail('Dokumen harus berformat .txt, .md, atau .pdf.');
                    }
                },
            ],
        ];
    }
}
