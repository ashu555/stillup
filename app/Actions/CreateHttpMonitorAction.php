<?php

namespace App\Actions;

use App\Enums\MonitorStatus;
use App\Enums\MonitorType;
use App\Models\Monitor;
use App\Models\Project;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateHttpMonitorAction
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
            $monitor = Monitor::query()->create([
                'project_id' => $project->id,
                'type' => MonitorType::Http,
                'name' => $data['name'],
                'is_enabled' => true,
                'interval_seconds' => (int) $data['interval_seconds'],
                'status' => MonitorStatus::Pending,
                'last_status_change_at' => now(),
            ]);

            $monitor->httpConfig()->create([
                'url' => $data['url'],
                'method' => strtoupper($data['method'] ?? 'GET'),
                'expected_status' => (int) ($data['expected_status'] ?? 200),
                'timeout_seconds' => (int) ($data['timeout_seconds'] ?? 10),
                'keyword' => $data['keyword'] ?? null,
            ]);

            $this->auditLogger->log(
                action: 'monitor.created',
                user: $actor,
                organization: $project->organization,
                auditable: $monitor,
                properties: [
                    'name' => $monitor->name,
                    'type' => $monitor->type->value,
                    'url' => $data['url'],
                    'interval_seconds' => $monitor->interval_seconds,
                ]
            );

            return $monitor->fresh(['httpConfig', 'project.organization']);
        });
    }
}
