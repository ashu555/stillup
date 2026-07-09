<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = $this->route('organization');

        return $this->user()?->can('create', [\App\Models\Project::class, $organization]) ?? false;
    }

    public function rules(): array
    {
        $organization = $this->route('organization');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('projects', 'slug')->where(
                    fn ($query) => $query->where('organization_id', $organization->id)
                ),
            ],
            'public_status_enabled' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('slug')) {
            $this->merge([
                'slug' => Str::slug($this->input('slug')),
            ]);
        }

        if ($this->has('public_status_enabled')) {
            $this->merge([
                'public_status_enabled' => filter_var(
                    $this->input('public_status_enabled'),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                ) ?? false,
            ]);
        }
    }
}
