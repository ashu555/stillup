<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrganizationAction;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Organization::class);

        $organizations = $request->user()
            ->organizations()
            ->withCount('projects')
            ->orderBy('name')
            ->get()
            ->map(fn (Organization $organization) => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'role' => $organization->pivot->role,
                'projects_count' => $organization->projects_count,
            ]);

        return Inertia::render('Organizations/Index', [
            'organizations' => $organizations,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Organization::class);

        return Inertia::render('Organizations/Create');
    }

    public function store(
        StoreOrganizationRequest $request,
        CreateOrganizationAction $action
    ): RedirectResponse {
        $organization = $action->execute($request->user(), $request->validated());

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', 'Organization created.');
    }

    public function show(Organization $organization): Response
    {
        $this->authorize('view', $organization);

        $organization->load(['projects' => fn ($query) => $query->orderBy('name')]);

        $role = request()->user()->roleIn($organization);

        return Inertia::render('Organizations/Show', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'role' => $role?->value,
                'can' => [
                    'update' => request()->user()->can('update', $organization),
                    'createProject' => request()->user()->can('create', [\App\Models\Project::class, $organization]),
                ],
                'projects' => $organization->projects->map(fn ($project) => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'slug' => $project->slug,
                    'public_status_enabled' => $project->public_status_enabled,
                ]),
            ],
        ]);
    }

    public function update(
        UpdateOrganizationRequest $request,
        Organization $organization,
        AuditLogger $auditLogger
    ): RedirectResponse {
        $organization->update($request->validated());

        $auditLogger->log(
            action: 'organization.updated',
            user: $request->user(),
            organization: $organization,
            auditable: $organization,
            properties: $request->validated()
        );

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', 'Organization updated.');
    }
}
