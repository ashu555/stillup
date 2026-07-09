<?php

namespace App\Services;

use App\Enums\IncidentStatus;
use App\Enums\MonitorStatus;
use App\Models\CheckResult;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $organizationIds = $user->organizations()->pluck('organizations.id');

        $monitors = Monitor::query()
            ->with(['project.organization', 'httpConfig', 'heartbeatConfig', 'activeIncident'])
            ->whereHas('project', fn ($query) => $query->whereIn('organization_id', $organizationIds))
            ->get();

        $incidents = Incident::query()
            ->with(['monitor', 'project.organization'])
            ->whereHas('project', fn ($query) => $query->whereIn('organization_id', $organizationIds))
            ->orderByDesc('opened_at')
            ->get();

        $openIncidents = $incidents->where('status', IncidentStatus::Open)->values();
        $acknowledgedIncidents = $incidents->where('status', IncidentStatus::Acknowledged)->values();

        $downMonitors = $monitors
            ->where('status', MonitorStatus::Down)
            ->values();

        $recentFailures = CheckResult::query()
            ->with(['monitor.project.organization'])
            ->where('success', false)
            ->whereHas('monitor.project', fn ($query) => $query->whereIn('organization_id', $organizationIds))
            ->orderByDesc('checked_at')
            ->limit(10)
            ->get()
            ->map(fn (CheckResult $result) => [
                'id' => $result->id,
                'error_message' => $result->error_message,
                'checked_at' => $result->checked_at?->toIso8601String(),
                'monitor' => [
                    'id' => $result->monitor->id,
                    'name' => $result->monitor->name,
                ],
                'organization' => [
                    'slug' => $result->monitor->project->organization->slug,
                ],
                'project' => [
                    'slug' => $result->monitor->project->slug,
                ],
            ]);

        $firstOrg = $user->organizations()->with(['projects' => fn ($q) => $q->orderBy('name')])->first();
        $firstProject = $firstOrg?->projects?->first();

        return [
            'counts' => [
                'monitors' => [
                    'up' => $monitors->where('status', MonitorStatus::Up)->count(),
                    'down' => $monitors->where('status', MonitorStatus::Down)->count(),
                    'paused' => $monitors->where('status', MonitorStatus::Paused)->count(),
                    'pending' => $monitors->where('status', MonitorStatus::Pending)->count(),
                    'degraded' => $monitors->where('status', MonitorStatus::Degraded)->count(),
                    'total' => $monitors->count(),
                ],
                'incidents' => [
                    'open' => $openIncidents->count(),
                    'acknowledged' => $acknowledgedIncidents->count(),
                ],
            ],
            'needs_attention' => [
                'monitors' => $downMonitors->map(fn (Monitor $monitor) => [
                    'id' => $monitor->id,
                    'name' => $monitor->name,
                    'type' => $monitor->type->value,
                    'status' => $monitor->status->value,
                    'organization' => [
                        'name' => $monitor->project->organization->name,
                        'slug' => $monitor->project->organization->slug,
                    ],
                    'project' => [
                        'name' => $monitor->project->name,
                        'slug' => $monitor->project->slug,
                    ],
                ])->all(),
                'incidents' => $openIncidents->take(10)->map(fn (Incident $incident) => [
                    'id' => $incident->id,
                    'summary' => $incident->summary,
                    'status' => $incident->status->value,
                    'opened_at' => $incident->opened_at?->toIso8601String(),
                    'organization' => [
                        'slug' => $incident->project->organization->slug,
                    ],
                    'project' => [
                        'name' => $incident->project->name,
                        'slug' => $incident->project->slug,
                    ],
                    'monitor' => [
                        'name' => $incident->monitor?->name,
                    ],
                ])->all(),
            ],
            'recent_incidents' => $incidents->take(5)->map(fn (Incident $incident) => [
                'id' => $incident->id,
                'summary' => $incident->summary,
                'status' => $incident->status->value,
                'opened_at' => $incident->opened_at?->toIso8601String(),
                'organization' => [
                    'slug' => $incident->project->organization->slug,
                ],
                'project' => [
                    'name' => $incident->project->name,
                    'slug' => $incident->project->slug,
                ],
            ])->all(),
            'recent_failures' => $recentFailures,
            'quick_links' => $firstProject && $firstOrg ? [
                'organization_slug' => $firstOrg->slug,
                'project_slug' => $firstProject->slug,
            ] : null,
        ];
    }
}
