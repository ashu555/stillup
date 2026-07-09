<?php

namespace App\Jobs;

use App\Actions\MarkHeartbeatMissedAction;
use App\Enums\MonitorStatus;
use App\Enums\MonitorType;
use App\Models\Monitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DetectMissedHeartbeatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(MarkHeartbeatMissedAction $markMissed): void
    {
        Monitor::query()
            ->heartbeat()
            ->runnable()
            ->with('heartbeatConfig')
            ->whereHas('heartbeatConfig', function ($query) {
                $query->whereNotNull('last_heartbeat_at');
            })
            ->orderBy('id')
            ->chunkById(100, function ($monitors) use ($markMissed): void {
                foreach ($monitors as $monitor) {
                    /** @var Monitor $monitor */
                    if ($monitor->type !== MonitorType::Heartbeat) {
                        continue;
                    }

                    if ($monitor->status === MonitorStatus::Paused || ! $monitor->is_enabled) {
                        continue;
                    }

                    if (! $monitor->heartbeatConfig?->isOverdue()) {
                        continue;
                    }

                    $markMissed->execute($monitor);
                }
            });
    }
}
