<?php

namespace App\Actions;

use App\Models\Monitor;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateHttpMonitorAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {}

    public function execute(User $actor, Monitor $monitor, array $data): Monitor
    {
        if (! $actor->can('update', $monitor)) {
            throw ValidationException::withMessages([
                'monitor' => 'You are not allowed to update this monitor.',
            ]);
        }

        return DB::transaction(function () use ($actor, $monitor, $data) {
            $monitor->update([
                'name' => $data['name'],
                'interval_seconds' => (int) $data['interval_seconds'],
            ]);

            $monitor->httpConfig()->updateOrCreate(
                ['monitor_id' => $monitor->id],
                [
                    'url' => $data['url'],
                    'method' => strtoupper($data['method'] ?? 'GET'),
                    'expected_status' => (int) ($data['expected_status'] ?? 200),
                    'timeout_seconds' => (int) ($data['timeout_seconds'] ?? 10),
                    'keyword' => $data['keyword'] ?? null,
                ]
            );

            $this->auditLogger->log(
                action: 'monitor.updated',
                user: $actor,
                organization: $monitor->project->organization,
                auditable: $monitor,
                properties: $data
            );

            return $monitor->fresh(['httpConfig', 'project.organization']);
        });
    }
}
