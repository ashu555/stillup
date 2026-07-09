<?php

namespace App\Actions;

use App\DTOs\HttpCheckResultDto;
use App\Enums\MonitorStatus;
use App\Models\CheckResult;
use App\Models\Monitor;
use Illuminate\Support\Facades\DB;

class RecordCheckResultAction
{
    public function execute(Monitor $monitor, HttpCheckResultDto $result): CheckResult
    {
        return DB::transaction(function () use ($monitor, $result) {
            $checkedAt = now();

            $checkResult = CheckResult::query()->create([
                'monitor_id' => $monitor->id,
                'success' => $result->success,
                'status_code' => $result->statusCode,
                'response_time_ms' => $result->responseTimeMs,
                'error_message' => $result->errorMessage,
                'checked_at' => $checkedAt,
            ]);

            $newStatus = $result->success ? MonitorStatus::Up : MonitorStatus::Down;
            $statusChanged = $monitor->status !== $newStatus;

            $monitor->forceFill([
                'last_checked_at' => $checkedAt,
                'status' => $newStatus,
                'last_status_change_at' => $statusChanged ? $checkedAt : $monitor->last_status_change_at,
            ])->save();

            return $checkResult;
        });
    }
}
