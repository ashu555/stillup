<?php

namespace App\Http\Controllers;

use App\Actions\AcknowledgeIncidentAction;
use App\Actions\ResolveIncidentAction;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    public function index(Request $request, Organization $organization, Project $project): Response
    {
        abort_unless($project->organization_id === $organization->id, 404);

        $this->authorize('viewAny', [Incident::class, $project]);

        $status = $request->string('status')->toString();
        $allowed = collect(IncidentStatus::cases())->map->value->all();

        $query = $project->incidents()
            ->with(['monitor.httpConfig', 'acknowledgedBy'])
            ->orderByDesc('opened_at');

        if ($status !== '' && in_array($status, $allowed, true)) {
            $query->where('status', $status);
        }

        $incidents = $query->limit(100)->get()->map(fn (Incident $incident) => [
            'id' => $incident->id,
            'status' => $incident->status->value,
            'cause' => $incident->cause->value,
            'summary' => $incident->summary,
            'opened_at' => $incident->opened_at?->toIso8601String(),
            'acknowledged_at' => $incident->acknowledged_at?->toIso8601String(),
            'resolved_at' => $incident->resolved_at?->toIso8601String(),
            'monitor' => [
                'id' => $incident->monitor->id,
                'name' => $incident->monitor->name,
                'url' => $incident->monitor->httpConfig?->url,
            ],
            'acknowledged_by' => $incident->acknowledgedBy?->only(['id', 'name']),
        ]);

        return Inertia::render('Incidents/Index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
            ],
            'filters' => [
                'status' => in_array($status, $allowed, true) ? $status : null,
            ],
            'incidents' => $incidents,
        ]);
    }

    public function show(
        Organization $organization,
        Project $project,
        Incident $incident
    ): Response {
        abort_unless($project->organization_id === $organization->id, 404);
        abort_unless($incident->project_id === $project->id, 404);

        $this->authorize('view', $incident);

        $incident->load([
            'monitor.httpConfig',
            'acknowledgedBy',
            'resolvedBy',
            'events.user',
        ]);

        return Inertia::render('Incidents/Show', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
            ],
            'incident' => [
                'id' => $incident->id,
                'status' => $incident->status->value,
                'cause' => $incident->cause->value,
                'summary' => $incident->summary,
                'opened_at' => $incident->opened_at?->toIso8601String(),
                'acknowledged_at' => $incident->acknowledged_at?->toIso8601String(),
                'resolved_at' => $incident->resolved_at?->toIso8601String(),
                'last_notified_at' => $incident->last_notified_at?->toIso8601String(),
                'acknowledged_by' => $incident->acknowledgedBy?->only(['id', 'name']),
                'resolved_by' => $incident->resolvedBy?->only(['id', 'name']),
                'monitor' => [
                    'id' => $incident->monitor->id,
                    'name' => $incident->monitor->name,
                    'status' => $incident->monitor->status->value,
                    'url' => $incident->monitor->httpConfig?->url,
                ],
                'can' => [
                    'acknowledge' => request()->user()->can('acknowledge', $incident)
                        && $incident->status === IncidentStatus::Open,
                    'resolve' => request()->user()->can('resolve', $incident)
                        && $incident->isActive(),
                ],
            ],
            'events' => $incident->events
                ->sortBy('created_at')
                ->values()
                ->map(fn ($event) => [
                    'id' => $event->id,
                    'type' => $event->type->value,
                    'message' => $event->message,
                    'created_at' => $event->created_at?->toIso8601String(),
                    'user' => $event->user?->only(['id', 'name']),
                ]),
        ]);
    }

    public function acknowledge(
        Organization $organization,
        Project $project,
        Incident $incident,
        AcknowledgeIncidentAction $action
    ): RedirectResponse {
        abort_unless($project->organization_id === $organization->id, 404);
        abort_unless($incident->project_id === $project->id, 404);

        $this->authorize('acknowledge', $incident);
        $action->execute(request()->user(), $incident);

        return back()->with('success', 'Incident acknowledged.');
    }

    public function resolve(
        Organization $organization,
        Project $project,
        Incident $incident,
        ResolveIncidentAction $action
    ): RedirectResponse {
        abort_unless($project->organization_id === $organization->id, 404);
        abort_unless($incident->project_id === $project->id, 404);

        $this->authorize('resolve', $incident);
        $action->execute($incident, request()->user());

        return back()->with('success', 'Incident resolved.');
    }
}
