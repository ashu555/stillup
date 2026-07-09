<?php

namespace App\Actions;

use App\DTOs\HttpCheckResultDto;
use App\Enums\IncidentCause;
use App\Enums\MonitorStatus;
use App\Models\CheckResult;
use App\Models\Monitor;
use Illuminate\Support\Facades\DB;

class RecordCheckResultAction
{
    public function __construct(
        private readonly OpenIncidentAction $openIncident,
        private readonly ResolveIncidentAction $resolveIncident
    ) {}

    public function execute(Monitor $monitor, HttpCheckResultDto $result): CheckResult
    {
        return DB::transaction(function () use ($monitor, $result) {
            $checkedAt = now();
            $previousStatus = $monitor->status;
            $newStatus = $result->success ? MonitorStatus::Up : MonitorStatus::Down;
            $statusChanged = $previousStatus !== $newStatus;

            $checkResult = CheckResult::query()->create([
                'monitor_id' => $monitor->id,
                'success' => $result->success,
                'status_code' => $result->statusCode,
                'response_time_ms' => $result->responseTimeMs,
                'error_message' => $result->errorMessage,
                'checked_at' => $checkedAt,
            ]);

            $monitor->forceFill([
                'last_checked_at' => $checkedAt,
                'status' => $newStatus,
                'last_status_change_at' => $statusChanged ? $checkedAt : $monitor->last_status_change_at,
            ])->save();

            if ($this->transitionedToDown($previousStatus, $newStatus)) {
                $statusLabel = $result->statusCode !== null
                    ? (string) $result->statusCode
                    : 'connection error';

                $this->openIncident->execute(
                    monitor: $monitor->fresh(['project.organization', 'httpConfig']),
                    summary: "HTTP check failed for {$monitor->name} (status {$statusLabel})",
                    cause: IncidentCause::HttpFailure,
                    meta: [
                        'status_code' => $result->statusCode,
                        'error_message' => $result->errorMessage,
                        'check_result_id' => $checkResult->id,
                    ]
                );
            }

            if ($this->transitionedToUpFromFailure($previousStatus, $newStatus)) {
                $this->resolveIncident->resolveActiveForMonitor(
                    $monitor,
                    'Automatically resolved after monitor recovered.'
                );
            }

            return $checkResult;
        });
    }

    private function transitionedToDown(MonitorStatus $previous, MonitorStatus $next): bool
    {
        if ($next !== MonitorStatus::Down) {
            return false;
        }

        return in_array($previous, [
            MonitorStatus::Pending,
            MonitorStatus::Up,
            MonitorStatus::Degraded,
        ], true);
    }

    private function transitionedToUpFromFailure(MonitorStatus $previous, MonitorStatus $next): bool
    {
        if ($next !== MonitorStatus::Up) {
            return false;
        }

        return in_array($previous, [
            MonitorStatus::Down,
            MonitorStatus::Degraded,
        ], true);
    }
}
