<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait SalespageValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function salespageRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'array', 'min:1'],
            'content.*.type' => ['required', 'string', 'max:255'],
            'content.*.data' => ['nullable', 'array'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:255'],
            'is_published' => ['boolean'],
        ];
    }
}
