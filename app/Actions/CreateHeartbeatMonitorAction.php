<?php

namespace App\Actions;

use App\Enums\MonitorStatus;
use App\Enums\MonitorType;
use App\Models\HeartbeatMonitorConfig;
use App\Models\Monitor;
use App\Models\Project;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateHeartbeatMonitorAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {}

    public function execute(User $actor, Project $project, array $data): Monitor
    {
        if (! $actor->can('create', [Monitor::class, $project])) {
            throw ValidationException::withMessages([
                'project' => 'You are not allowed to create monitors for this project.',
            ]);
        }

        return DB::transaction(function () use ($actor, $project, $data) {
            $expectedEvery = (int) $data['expected_every_seconds'];

            $monitor = Monitor::query()->create([
                'project_id' => $project->id,
                'type' => MonitorType::Heartbeat,
                'name' => $data['name'],
                'is_enabled' => true,
                'interval_seconds' => $expectedEvery,
                'status' => MonitorStatus::Pending,
                'last_status_change_at' => now(),
            ]);

            $monitor->heartbeatConfig()->create([
                'token' => HeartbeatMonitorConfig::generateToken(),
                'expected_every_seconds' => $expectedEvery,
                'grace_seconds' => (int) ($data['grace_seconds'] ?? 60),
                'last_heartbeat_at' => null,
            ]);

            $this->auditLogger->log(
                action: 'monitor.created',
                user: $actor,
                organization: $project->organization,
                auditable: $monitor,
                properties: [
                    'name' => $monitor->name,
                    'type' => $monitor->type->value,
                    'expected_every_seconds' => $expectedEvery,
                    'grace_seconds' => (int) ($data['grace_seconds'] ?? 60),
                ]
            );

            return $monitor->fresh(['heartbeatConfig', 'project.organization']);
        });
    }
}
