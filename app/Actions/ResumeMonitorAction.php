<?php

namespace App\Actions;

use App\Enums\MonitorStatus;
use App\Enums\MonitorType;
use App\Models\Monitor;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

class ResumeMonitorAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {}

    public function execute(User $actor, Monitor $monitor): Monitor
    {
        if (! $actor->can('resume', $monitor)) {
            throw ValidationException::withMessages([
                'monitor' => 'You are not allowed to resume this monitor.',
            ]);
        }

        if ($monitor->status !== MonitorStatus::Paused && $monitor->is_enabled) {
            return $monitor;
        }

        $monitor->forceFill([
            'status' => MonitorStatus::Pending,
            'is_enabled' => true,
            'last_status_change_at' => now(),
        ])->save();

        // Reset heartbeat baseline so resume does not immediately trip a miss.
        if ($monitor->type === MonitorType::Heartbeat) {
            $monitor->loadMissing('heartbeatConfig');
            $monitor->heartbeatConfig?->forceFill([
                'last_heartbeat_at' => now(),
            ])->save();
        }

        $this->auditLogger->log(
            action: 'monitor.resumed',
            user: $actor,
            organization: $monitor->project->organization,
            auditable: $monitor,
        );

        return $monitor->fresh(['heartbeatConfig', 'httpConfig']);
    }
}
