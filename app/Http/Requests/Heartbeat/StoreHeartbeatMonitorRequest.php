<?php

namespace App\Http\Requests\Heartbeat;

use App\Models\Monitor;
use Illuminate\Foundation\Http\FormRequest;

class StoreHeartbeatMonitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $this->user()?->can('create', [Monitor::class, $project]) ?? false;
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
