<?php

namespace App\Jobs;

use App\Enums\MonitorType;
use App\Models\Monitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchDueHttpMonitorsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Monitor::query()
            ->http()
            ->runnable()
            ->with('httpConfig')
            ->orderBy('id')
            ->chunkById(100, function ($monitors): void {
                foreach ($monitors as $monitor) {
                    /** @var Monitor $monitor */
                    if ($monitor->type !== MonitorType::Http) {
                        continue;
                    }

                    if (! $monitor->isDue()) {
                        continue;
                    }

                    RunHttpMonitorCheckJob::dispatch($monitor->id);
                }
            });
    }
}
