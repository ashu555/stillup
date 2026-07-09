<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return $user->belongsToOrganization($organization);
    }

    public function view(User $user, Project $project): bool
    {
        return $user->belongsToOrganization($project->organization);
    }

    public function create(User $user, Organization $organization): bool
    {
        return $user->hasOrganizationRole(
            $organization,
            OrganizationRole::Owner,
            OrganizationRole::Admin,
            OrganizationRole::Member
        );
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasOrganizationRole(
            $project->organization,
            OrganizationRole::Owner,
            OrganizationRole::Admin,
            OrganizationRole::Member
        );
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasOrganizationRole(
            $project->organization,
            OrganizationRole::Owner,
            OrganizationRole::Admin
        );
    }
}
