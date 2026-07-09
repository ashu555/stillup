<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Organization::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('organizations', 'slug')],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('slug')) {
            $this->merge([
                'slug' => Str::slug($this->input('slug')),
            ]);
        }
    }
}
