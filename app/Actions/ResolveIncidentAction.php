<?php

namespace App\Actions;

use App\Enums\IncidentEventType;
use App\Enums\IncidentStatus;
use App\Events\IncidentResolved;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResolveIncidentAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {}

    public function execute(
        Incident $incident,
        ?User $resolvedBy = null,
        ?string $message = null
    ): Incident {
        if ($resolvedBy && ! $resolvedBy->can('resolve', $incident)) {
            throw ValidationException::withMessages([
                'incident' => 'You are not allowed to resolve this incident.',
            ]);
        }

        if ($incident->status === IncidentStatus::Resolved) {
            return $incident;
        }

        $incident->loadMissing('project.organization');

        return DB::transaction(function () use ($incident, $resolvedBy, $message) {
            $incident->forceFill([
                'status' => IncidentStatus::Resolved,
                'resolved_at' => now(),
                'resolved_by' => $resolvedBy?->id,
            ])->save();

            $eventMessage = $message
                ?? ($resolvedBy
                    ? "Manually resolved by {$resolvedBy->name}."
                    : 'Automatically resolved after monitor recovered.');

            $incident->events()->create([
                'type' => IncidentEventType::Resolved,
                'message' => $eventMessage,
                'user_id' => $resolvedBy?->id,
                'meta' => null,
                'created_at' => now(),
            ]);

            $this->auditLogger->log(
                action: 'incident.resolved',
                user: $resolvedBy,
                organization: $incident->project->organization,
                auditable: $incident,
                properties: [
                    'resolved_by' => $resolvedBy?->id,
                    'auto' => $resolvedBy === null,
                ]
            );

            $fresh = $incident->fresh(['monitor.httpConfig', 'project.organization', 'resolvedBy']);
            IncidentResolved::dispatch($fresh, $resolvedBy);

            return $fresh;
        });
    }

    public function resolveActiveForMonitor(Monitor $monitor, ?string $message = null): ?Incident
    {
        $incident = Incident::query()
            ->where('monitor_id', $monitor->id)
            ->active()
            ->first();

        if (! $incident) {
            return null;
        }

        return $this->execute($incident, null, $message);
    }
}
