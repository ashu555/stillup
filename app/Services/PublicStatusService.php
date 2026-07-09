<?php

namespace App\Services;

use App\Enums\IncidentStatus;
use App\Enums\MonitorStatus;
use App\Enums\OverallStatus;
use App\Models\Project;
use Illuminate\Support\Collection;

class PublicStatusService
{
    /**
     * @return array<string, mixed>
     */
    public function build(Project $project): array
    {
        $project->loadMissing([
            'monitors' => fn ($query) => $query
                ->where('is_enabled', true)
                ->with(['heartbeatConfig', 'activeIncident'])
                ->orderBy('name'),
            'incidents' => fn ($query) => $query
                ->active()
                ->with('monitor:id,name')
                ->orderByDesc('opened_at'),
        ]);

        $monitors = $project->monitors;
        $activeIncidents = $project->incidents;
        $overall = $this->overallStatus($monitors, $activeIncidents);

        return [
            'project' => [
                'name' => $project->name,
                'slug' => $project->slug,
            ],
            'overall_status' => $overall->value,
            'overall_status_label' => $overall->label(),
            'monitors' => $monitors->map(fn ($monitor) => [
                'name' => $monitor->name,
                'type' => $monitor->type->value,
                'status' => $monitor->status->value,
                'last_checked_at' => $monitor->last_checked_at?->toIso8601String(),
                'last_heartbeat_at' => $monitor->heartbeatConfig?->last_heartbeat_at?->toIso8601String(),
            ])->values()->all(),
            'incidents' => $activeIncidents->map(fn ($incident) => [
                'summary' => $incident->summary,
                'status' => $incident->status->value,
                'opened_at' => $incident->opened_at?->toIso8601String(),
                'monitor_name' => $incident->monitor?->name,
            ])->values()->all(),
            'refreshed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  Collection<int, \App\Models\Monitor>  $monitors
     * @param  Collection<int, \App\Models\Incident>  $activeIncidents
     */
    public function overallStatus(Collection $monitors, Collection $activeIncidents): OverallStatus
    {
        $hasOpenIncident = $activeIncidents->contains(
            fn ($incident) => $incident->status === IncidentStatus::Open
        );

        $hasAcknowledgedIncident = $activeIncidents->contains(
            fn ($incident) => $incident->status === IncidentStatus::Acknowledged
        );

        $hasDown = $monitors->contains(fn ($monitor) => $monitor->status === MonitorStatus::Down);
        $hasDegraded = $monitors->contains(fn ($monitor) => $monitor->status === MonitorStatus::Degraded);

        if ($hasDown || $hasOpenIncident) {
            return OverallStatus::MajorOutage;
        }

        if ($hasDegraded || $hasAcknowledgedIncident) {
            return OverallStatus::Degraded;
        }

        if ($monitors->isEmpty()) {
            return OverallStatus::Operational;
        }

        $allPending = $monitors->every(fn ($monitor) => $monitor->status === MonitorStatus::Pending);

        if ($allPending) {
            return OverallStatus::Pending;
        }

        return OverallStatus::Operational;
    }
}
