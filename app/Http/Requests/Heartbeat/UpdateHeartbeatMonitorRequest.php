<?php

namespace App\Http\Requests\Heartbeat;

use App\Enums\MonitorType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHeartbeatMonitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $monitor = $this->route('monitor');

        return ($this->user()?->can('update', $monitor) ?? false)
            && $monitor?->type === MonitorType::Heartbeat;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'expected_every_seconds' => ['required', 'integer', 'min:60', 'max:604800'],
            'grace_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
        ];
    }
}
