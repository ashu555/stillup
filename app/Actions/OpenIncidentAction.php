<?php

namespace App\Actions;

use App\Enums\IncidentCause;
use App\Enums\IncidentEventType;
use App\Enums\IncidentStatus;
use App\Events\IncidentOpened;
use App\Models\Incident;
use App\Models\Monitor;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class OpenIncidentAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {}

    public function execute(
        Monitor $monitor,
        string $summary,
        IncidentCause $cause = IncidentCause::HttpFailure,
        array $meta = []
    ): ?Incident {
        $monitor->loadMissing(['project.organization', 'httpConfig']);

        $existing = Incident::query()
            ->where('monitor_id', $monitor->id)
            ->active()
            ->first();

        if ($existing) {
            return null;
        }

        return DB::transaction(function () use ($monitor, $summary, $cause, $meta) {
            $incident = Incident::query()->create([
                'monitor_id' => $monitor->id,
                'project_id' => $monitor->project_id,
                'status' => IncidentStatus::Open,
                'cause' => $cause,
                'summary' => $summary,
                'opened_at' => now(),
            ]);

            $incident->events()->create([
                'type' => IncidentEventType::Opened,
                'message' => $summary,
                'user_id' => null,
                'meta' => $meta ?: null,
                'created_at' => now(),
            ]);

            $this->auditLogger->log(
                action: 'incident.opened',
                user: null,
                organization: $monitor->project->organization,
                auditable: $incident,
                properties: [
                    'monitor_id' => $monitor->id,
                    'cause' => $cause->value,
                    'summary' => $summary,
                ]
            );

            $fresh = $incident->fresh(['monitor.httpConfig', 'project.organization']);

            IncidentOpened::dispatch($fresh);

            return $fresh;
        });
    }
}
