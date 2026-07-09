<?php

namespace App\Http\Controllers;

use App\Actions\CreateProjectAction;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Organization $organization): Response
    {
        $this->authorize('viewAny', [Project::class, $organization]);

        $projects = $organization->projects()
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'public_status_enabled' => $project->public_status_enabled,
            ]);

        return Inertia::render('Projects/Index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'can' => [
                    'createProject' => request()->user()->can('create', [Project::class, $organization]),
                ],
            ],
            'projects' => $projects,
        ]);
    }

    public function create(Organization $organization): Response
    {
        $this->authorize('create', [Project::class, $organization]);

        return Inertia::render('Projects/Create', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
        ]);
    }

    public function store(
        StoreProjectRequest $request,
        Organization $organization,
        CreateProjectAction $action
    ): RedirectResponse {
        $project = $action->execute($request->user(), $organization, $request->validated());

        return redirect()
            ->route('organizations.projects.show', [$organization, $project])
            ->with('success', 'Project created.');
    }

    public function show(Organization $organization, Project $project): Response
    {
        abort_unless($project->organization_id === $organization->id, 404);

        $this->authorize('view', $project);

        return Inertia::render('Projects/Show', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'public_status_enabled' => $project->public_status_enabled,
                'can' => [
                    'update' => request()->user()->can('update', $project),
                ],
            ],
        ]);
    }

    public function update(
        UpdateProjectRequest $request,
        Organization $organization,
        Project $project,
        AuditLogger $auditLogger
    ): RedirectResponse {
        abort_unless($project->organization_id === $organization->id, 404);

        $project->update($request->validated());

        $auditLogger->log(
            action: 'project.updated',
            user: $request->user(),
            organization: $organization,
            auditable: $project,
            properties: $request->validated()
        );

        return redirect()
            ->route('organizations.projects.show', [$organization, $project])
            ->with('success', 'Project updated.');
    }
}
