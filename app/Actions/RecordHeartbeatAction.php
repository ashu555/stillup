<?php

namespace App\Actions;

use App\Enums\IncidentCause;
use App\Enums\MonitorStatus;
use App\Enums\MonitorType;
use App\Models\CheckResult;
use App\Models\HeartbeatMonitorConfig;
use App\Models\Monitor;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RecordHeartbeatAction
{
    public function __construct(
        private readonly ResolveIncidentAction $resolveIncident
    ) {}

    /**
     * @return array{monitor: Monitor, status: int}
     */
    public function execute(string $token): array
    {
        /** @var HeartbeatMonitorConfig|null $config */
        $config = HeartbeatMonitorConfig::query()
            ->where('token', $token)
            ->with('monitor')
            ->first();

        if (! $config || ! $config->monitor) {
            throw new HttpException(404, 'Heartbeat token not found.');
        }

        $monitor = $config->monitor;

        if ($monitor->type !== MonitorType::Heartbeat) {
            throw new HttpException(404, 'Heartbeat token not found.');
        }

        // Paused: accept ping with no state change (do not treat as disabled).
        if ($monitor->status === MonitorStatus::Paused) {
            return ['monitor' => $monitor, 'status' => 204];
        }

        if (! $monitor->is_enabled) {
            throw new HttpException(403, 'Monitor is disabled.');
        }

        $monitor = DB::transaction(function () use ($monitor, $config) {
            $pingedAt = now();
            $previousStatus = $monitor->status;

            $config->forceFill([
                'last_heartbeat_at' => $pingedAt,
            ])->save();

            CheckResult::query()->create([
                'monitor_id' => $monitor->id,
                'success' => true,
                'status_code' => null,
                'response_time_ms' => null,
                'error_message' => null,
                'checked_at' => $pingedAt,
            ]);

            $statusChanged = $previousStatus !== MonitorStatus::Up;

            $monitor->forceFill([
                'status' => MonitorStatus::Up,
                'last_checked_at' => $pingedAt,
                'last_status_change_at' => $statusChanged ? $pingedAt : $monitor->last_status_change_at,
            ])->save();

            if (in_array($previousStatus, [MonitorStatus::Down, MonitorStatus::Degraded], true)) {
                $this->resolveIncident->resolveActiveForMonitor(
                    $monitor,
                    'Automatically resolved after heartbeat received.'
                );
            }

            return $monitor->fresh(['heartbeatConfig']);
        });

        return ['monitor' => $monitor, 'status' => 204];
    }
}
