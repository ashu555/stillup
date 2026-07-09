<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('project')) ?? false;
    }

    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('projects', 'slug')
                    ->where(fn ($query) => $query->where('organization_id', $project->organization_id))
                    ->ignore($project?->id),
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
