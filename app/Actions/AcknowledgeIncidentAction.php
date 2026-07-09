<?php

namespace App\Actions;

use App\Enums\IncidentEventType;
use App\Enums\IncidentStatus;
use App\Events\IncidentAcknowledged;
use App\Models\Incident;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcknowledgeIncidentAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {}

    public function execute(User $actor, Incident $incident): Incident
    {
        if (! $actor->can('acknowledge', $incident)) {
            throw ValidationException::withMessages([
                'incident' => 'You are not allowed to acknowledge this incident.',
            ]);
        }

        if ($incident->status === IncidentStatus::Acknowledged) {
            return $incident;
        }

        if ($incident->status === IncidentStatus::Resolved) {
            throw ValidationException::withMessages([
                'incident' => 'Resolved incidents cannot be acknowledged.',
            ]);
        }

        $incident->loadMissing('project.organization');

        return DB::transaction(function () use ($actor, $incident) {
            $incident->forceFill([
                'status' => IncidentStatus::Acknowledged,
                'acknowledged_at' => now(),
                'acknowledged_by' => $actor->id,
            ])->save();

            $incident->events()->create([
                'type' => IncidentEventType::Acknowledged,
                'message' => "Acknowledged by {$actor->name}.",
                'user_id' => $actor->id,
                'meta' => null,
                'created_at' => now(),
            ]);

            $this->auditLogger->log(
                action: 'incident.acknowledged',
                user: $actor,
                organization: $incident->project->organization,
                auditable: $incident,
                properties: [
                    'acknowledged_by' => $actor->id,
                ]
            );

            $fresh = $incident->fresh(['acknowledgedBy', 'monitor', 'project']);
            IncidentAcknowledged::dispatch($fresh, $actor);

            return $fresh;
        });
    }
}
