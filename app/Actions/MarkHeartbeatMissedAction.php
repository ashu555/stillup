<?php

namespace App\Actions;

use App\Enums\IncidentCause;
use App\Enums\MonitorStatus;
use App\Enums\MonitorType;
use App\Models\CheckResult;
use App\Models\Monitor;
use Illuminate\Support\Facades\DB;

class MarkHeartbeatMissedAction
{
    public function __construct(
        private readonly OpenIncidentAction $openIncident
    ) {}

    public function execute(Monitor $monitor): void
    {
        if ($monitor->type !== MonitorType::Heartbeat) {
            return;
        }

        if (! $monitor->is_enabled || $monitor->status === MonitorStatus::Paused) {
            return;
        }

        $config = $monitor->heartbeatConfig;

        if (! $config || $config->last_heartbeat_at === null || ! $config->isOverdue()) {
            return;
        }

        DB::transaction(function () use ($monitor) {
            $checkedAt = now();
            $wasAlreadyDown = $monitor->status === MonitorStatus::Down;

            CheckResult::query()->create([
                'monitor_id' => $monitor->id,
                'success' => false,
                'status_code' => null,
                'response_time_ms' => null,
                'error_message' => 'Missed heartbeat',
                'checked_at' => $checkedAt,
            ]);

            if (! $wasAlreadyDown) {
                $monitor->forceFill([
                    'status' => MonitorStatus::Down,
                    'last_checked_at' => $checkedAt,
                    'last_status_change_at' => $checkedAt,
                ])->save();

                $this->openIncident->execute(
                    monitor: $monitor->fresh(['project.organization', 'heartbeatConfig']),
                    summary: "Missed heartbeat for {$monitor->name}",
                    cause: IncidentCause::HeartbeatMiss,
                    meta: [
                        'expected_every_seconds' => $monitor->heartbeatConfig?->expected_every_seconds,
                        'grace_seconds' => $monitor->heartbeatConfig?->grace_seconds,
                        'last_heartbeat_at' => $monitor->heartbeatConfig?->last_heartbeat_at?->toIso8601String(),
                    ]
                );
            } else {
                $monitor->forceFill([
                    'last_checked_at' => $checkedAt,
                ])->save();
            }
        });
    }
}
