<?php

namespace App\Http\Controllers;

use App\Actions\CreateHeartbeatMonitorAction;
use App\Actions\CreateHttpMonitorAction;
use App\Actions\PauseMonitorAction;
use App\Actions\ResumeMonitorAction;
use App\Actions\UpdateHeartbeatMonitorAction;
use App\Actions\UpdateHttpMonitorAction;
use App\Enums\MonitorType;
use App\Http\Requests\Heartbeat\StoreHeartbeatMonitorRequest;
use App\Http\Requests\Heartbeat\UpdateHeartbeatMonitorRequest;
use App\Http\Requests\Monitor\StoreHttpMonitorRequest;
use App\Http\Requests\Monitor\UpdateHttpMonitorRequest;
use App\Models\Monitor;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MonitorController extends Controller
{
    public function index(Organization $organization, Project $project): Response
    {
        abort_unless($project->organization_id === $organization->id, 404);

        $this->authorize('viewAny', [Monitor::class, $project]);

        $monitors = $project->monitors()
            ->with(['httpConfig', 'heartbeatConfig', 'activeIncident'])
            ->orderBy('name')
            ->get()
            ->map(fn (Monitor $monitor) => $this->monitorSummary($monitor));

        return Inertia::render('Monitors/Index', [
            'organization' => $this->organizationPayload($organization),
            'project' => $this->projectPayload($project),
            'monitors' => $monitors,
            'can' => [
                'create' => request()->user()->can('create', [Monitor::class, $project]),
            ],
        ]);
    }

    public function create(Organization $organization, Project $project): Response
    {
        abort_unless($project->organization_id === $organization->id, 404);

        $this->authorize('create', [Monitor::class, $project]);

        return Inertia::render('Monitors/Create', [
            'organization' => $this->organizationPayload($organization),
            'project' => $this->projectPayload($project),
        ]);
    }

    public function createHeartbeat(Organization $organization, Project $project): Response
    {
        abort_unless($project->organization_id === $organization->id, 404);

        $this->authorize('create', [Monitor::class, $project]);

        return Inertia::render('Monitors/CreateHeartbeat', [
            'organization' => $this->organizationPayload($organization),
            'project' => $this->projectPayload($project),
        ]);
    }

    public function store(
        StoreHttpMonitorRequest $request,
        Organization $organization,
        Project $project,
        CreateHttpMonitorAction $action
    ): RedirectResponse {
        abort_unless($project->organization_id === $organization->id, 404);

        $monitor = $action->execute($request->user(), $project, $request->validated());

        return redirect()
            ->route('organizations.projects.monitors.show', [$organization, $project, $monitor])
            ->with('success', 'HTTP monitor created.');
    }

    public function storeHeartbeat(
        StoreHeartbeatMonitorRequest $request,
        Organization $organization,
        Project $project,
        CreateHeartbeatMonitorAction $action
    ): RedirectResponse {
        abort_unless($project->organization_id === $organization->id, 404);

        $monitor = $action->execute($request->user(), $project, $request->validated());

        return redirect()
            ->route('organizations.projects.monitors.show', [$organization, $project, $monitor])
            ->with('success', 'Heartbeat monitor created. Copy the ping URL below.');
    }

    public function show(
        Organization $organization,
        Project $project,
        Monitor $monitor
    ): Response {
        abort_unless($project->organization_id === $organization->id, 404);
        abort_unless($monitor->project_id === $project->id, 404);

        $this->authorize('view', $monitor);

        $monitor->load(['httpConfig', 'heartbeatConfig', 'activeIncident']);

        $checkResults = $monitor->checkResults()
            ->orderByDesc('checked_at')
            ->limit(25)
            ->get()
            ->map(fn ($result) => [
                'id' => $result->id,
                'success' => $result->success,
                'status_code' => $result->status_code,
                'response_time_ms' => $result->response_time_ms,
                'error_message' => $result->error_message,
                'checked_at' => $result->checked_at?->toIso8601String(),
            ]);

        $activeIncident = $monitor->activeIncident;
        $canSeeToken = request()->user()->can('update', $monitor);

        return Inertia::render('Monitors/Show', [
            'organization' => $this->organizationPayload($organization),
            'project' => $this->projectPayload($project),
            'monitor' => [
                ...$this->monitorSummary($monitor, $canSeeToken),
                'config' => $this->configPayload($monitor, $canSeeToken),
                'can' => [
                    'update' => request()->user()->can('update', $monitor),
                    'pause' => request()->user()->can('pause', $monitor),
                    'resume' => request()->user()->can('resume', $monitor),
                    'delete' => request()->user()->can('delete', $monitor),
                ],
            ],
            'activeIncident' => $activeIncident ? [
                'id' => $activeIncident->id,
                'status' => $activeIncident->status->value,
                'summary' => $activeIncident->summary,
                'opened_at' => $activeIncident->opened_at?->toIso8601String(),
            ] : null,
            'checkResults' => $checkResults,
        ]);
    }

    public function update(
        UpdateHttpMonitorRequest $request,
        Organization $organization,
        Project $project,
        Monitor $monitor,
        UpdateHttpMonitorAction $action
    ): RedirectResponse {
        abort_unless($project->organization_id === $organization->id, 404);
        abort_unless($monitor->project_id === $project->id, 404);
        abort_unless($monitor->type === MonitorType::Http, 404);

        $action->execute($request->user(), $monitor, $request->validated());

        return redirect()
            ->route('organizations.projects.monitors.show', [$organization, $project, $monitor])
            ->with('success', 'Monitor updated.');
    }

    public function updateHeartbeat(
        UpdateHeartbeatMonitorRequest $request,
        Organization $organization,
        Project $project,
        Monitor $monitor,
        UpdateHeartbeatMonitorAction $action
    ): RedirectResponse {
        abort_unless($project->organization_id === $organization->id, 404);
        abort_unless($monitor->project_id === $project->id, 404);

        $action->execute($request->user(), $monitor, $request->validated());

        return redirect()
            ->route('organizations.projects.monitors.show', [$organization, $project, $monitor])
            ->with('success', 'Heartbeat monitor updated.');
    }

    public function pause(
        Organization $organization,
        Project $project,
        Monitor $monitor,
        PauseMonitorAction $action
    ): RedirectResponse {
        abort_unless($project->organization_id === $organization->id, 404);
        abort_unless($monitor->project_id === $project->id, 404);

        $this->authorize('pause', $monitor);
        $action->execute(request()->user(), $monitor);

        return back()->with('success', 'Monitor paused.');
    }

    public function resume(
        Organization $organization,
        Project $project,
        Monitor $monitor,
        ResumeMonitorAction $action
    ): RedirectResponse {
        abort_unless($project->organization_id === $organization->id, 404);
        abort_unless($monitor->project_id === $project->id, 404);

        $this->authorize('resume', $monitor);
        $action->execute(request()->user(), $monitor);

        return back()->with('success', 'Monitor resumed.');
    }

    private function organizationPayload(Organization $organization): array
    {
        return [
            'id' => $organization->id,
            'name' => $organization->name,
            'slug' => $organization->slug,
        ];
    }

    private function projectPayload(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'slug' => $project->slug,
        ];
    }

    private function monitorSummary(Monitor $monitor, bool $includeToken = false): array
    {
        return [
            'id' => $monitor->id,
            'name' => $monitor->name,
            'type' => $monitor->type->value,
            'status' => $monitor->status->value,
            'is_enabled' => $monitor->is_enabled,
            'interval_seconds' => $monitor->interval_seconds,
            'last_checked_at' => $monitor->last_checked_at?->toIso8601String(),
            'last_status_change_at' => $monitor->last_status_change_at?->toIso8601String(),
            'url' => $monitor->type === MonitorType::Http
                ? $monitor->httpConfig?->url
                : ($includeToken ? $monitor->heartbeatConfig?->pingUrl() : null),
            'active_incident' => $monitor->relationLoaded('activeIncident') && $monitor->activeIncident
                ? [
                    'id' => $monitor->activeIncident->id,
                    'status' => $monitor->activeIncident->status->value,
                ]
                : null,
        ];
    }

    private function configPayload(Monitor $monitor, bool $canSeeToken): ?array
    {
        if ($monitor->type === MonitorType::Http && $monitor->httpConfig) {
            return [
                'kind' => 'http',
                'url' => $monitor->httpConfig->url,
                'method' => $monitor->httpConfig->method,
                'expected_status' => $monitor->httpConfig->expected_status,
                'timeout_seconds' => $monitor->httpConfig->timeout_seconds,
                'keyword' => $monitor->httpConfig->keyword,
            ];
        }

        if ($monitor->type === MonitorType::Heartbeat && $monitor->heartbeatConfig) {
            return [
                'kind' => 'heartbeat',
                'expected_every_seconds' => $monitor->heartbeatConfig->expected_every_seconds,
                'grace_seconds' => $monitor->heartbeatConfig->grace_seconds,
                'last_heartbeat_at' => $monitor->heartbeatConfig->last_heartbeat_at?->toIso8601String(),
                'token' => $canSeeToken ? $monitor->heartbeatConfig->token : null,
                'ping_url' => $canSeeToken ? $monitor->heartbeatConfig->pingUrl() : null,
            ];
        }

        return null;
    }
}
