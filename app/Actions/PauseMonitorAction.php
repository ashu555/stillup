<?php

namespace App\Actions;

use App\Enums\MonitorStatus;
use App\Models\Monitor;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

class PauseMonitorAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {}

    public function execute(User $actor, Monitor $monitor): Monitor
    {
        if (! $actor->can('pause', $monitor)) {
            throw ValidationException::withMessages([
                'monitor' => 'You are not allowed to pause this monitor.',
            ]);
        }

        if ($monitor->status === MonitorStatus::Paused) {
            return $monitor;
        }

        $monitor->forceFill([
            'status' => MonitorStatus::Paused,
            'is_enabled' => false,
            'last_status_change_at' => now(),
        ])->save();

        $this->auditLogger->log(
            action: 'monitor.paused',
            user: $actor,
            organization: $monitor->project->organization,
            auditable: $monitor,
        );

        return $monitor->fresh();
    }
}
