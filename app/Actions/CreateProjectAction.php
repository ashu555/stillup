<?php

namespace App\Actions;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class CreateProjectAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {}

    public function execute(User $creator, Organization $organization, array $data): Project
    {
        return DB::transaction(function () use ($creator, $organization, $data) {
            $project = Project::query()->create([
                'organization_id' => $organization->id,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Project::uniqueSlugForOrganization(
                    $organization->id,
                    $data['name']
                ),
                'public_status_enabled' => (bool) ($data['public_status_enabled'] ?? false),
            ]);

            $this->auditLogger->log(
                action: 'project.created',
                user: $creator,
                organization: $organization,
                auditable: $project,
                properties: [
                    'name' => $project->name,
                    'slug' => $project->slug,
                    'public_status_enabled' => $project->public_status_enabled,
                ]
            );

            return $project->fresh();
        });
    }
}
