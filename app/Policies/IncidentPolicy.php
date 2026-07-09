<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Incident;
use App\Models\Project;
use App\Models\User;

class IncidentPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->belongsToOrganization($project->organization);
    }

    public function view(User $user, Incident $incident): bool
    {
        return $user->belongsToOrganization($incident->project->organization);
    }

    public function acknowledge(User $user, Incident $incident): bool
    {
        return $user->hasOrganizationRole(
            $incident->project->organization,
            OrganizationRole::Owner,
            OrganizationRole::Admin,
            OrganizationRole::Member
        );
    }

    public function resolve(User $user, Incident $incident): bool
    {
        return $user->hasOrganizationRole(
            $incident->project->organization,
            OrganizationRole::Owner,
            OrganizationRole::Admin,
            OrganizationRole::Member
        );
    }
}
