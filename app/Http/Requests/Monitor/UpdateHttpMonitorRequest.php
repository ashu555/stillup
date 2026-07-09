<?php

namespace App\Http\Requests\Monitor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHttpMonitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('monitor')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048'],
            'method' => ['required', Rule::in(['GET', 'HEAD', 'POST'])],
            'expected_status' => ['required', 'integer', 'min:100', 'max:599'],
            'timeout_seconds' => ['required', 'integer', 'min:1', 'max:60'],
            'interval_seconds' => ['required', 'integer', 'min:60', 'max:86400'],
            'keyword' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('method')) {
            $this->merge([
                'method' => strtoupper((string) $this->input('method')),
            ]);
        }

        if ($this->has('keyword') && blank($this->input('keyword'))) {
            $this->merge(['keyword' => null]);
        }
    }
}
