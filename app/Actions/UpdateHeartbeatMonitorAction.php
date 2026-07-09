<?php

namespace App\Actions;

use App\Enums\MonitorType;
use App\Models\Monitor;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateHeartbeatMonitorAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {}

    public function execute(User $actor, Monitor $monitor, array $data): Monitor
    {
        if ($monitor->type !== MonitorType::Heartbeat) {
            throw ValidationException::withMessages([
                'monitor' => 'This monitor is not a heartbeat monitor.',
            ]);
        }

        if (! $actor->can('update', $monitor)) {
            throw ValidationException::withMessages([
                'monitor' => 'You are not allowed to update this monitor.',
            ]);
        }

        return DB::transaction(function () use ($actor, $monitor, $data) {
            $expectedEvery = (int) $data['expected_every_seconds'];

            $monitor->update([
                'name' => $data['name'],
                'interval_seconds' => $expectedEvery,
            ]);

            $monitor->heartbeatConfig()->updateOrCreate(
                ['monitor_id' => $monitor->id],
                [
                    'expected_every_seconds' => $expectedEvery,
                    'grace_seconds' => (int) ($data['grace_seconds'] ?? 60),
                ]
            );

            $this->auditLogger->log(
                action: 'monitor.updated',
                user: $actor,
                organization: $monitor->project->organization,
                auditable: $monitor,
                properties: $data
            );

            return $monitor->fresh(['heartbeatConfig', 'project.organization']);
        });
    }
}
