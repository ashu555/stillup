<?php

namespace App\Jobs;

use App\Actions\RecordCheckResultAction;
use App\Enums\MonitorStatus;
use App\Enums\MonitorType;
use App\Models\Monitor;
use App\Services\HttpMonitorChecker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunHttpMonitorCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $monitorId
    ) {}

    public function handle(
        HttpMonitorChecker $checker,
        RecordCheckResultAction $recordCheckResult
    ): void {
        $monitor = Monitor::query()
            ->with('httpConfig')
            ->find($this->monitorId);

        if (! $monitor) {
            return;
        }

        if ($monitor->type !== MonitorType::Http) {
            return;
        }

        if (! $monitor->is_enabled || $monitor->status === MonitorStatus::Paused) {
            return;
        }

        $result = $checker->check($monitor);
        $recordCheckResult->execute($monitor, $result);
    }
}
