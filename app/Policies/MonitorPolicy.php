<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Monitor;
use App\Models\Project;
use App\Models\User;

class MonitorPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->belongsToOrganization($project->organization);
    }

    public function view(User $user, Monitor $monitor): bool
    {
        return $user->belongsToOrganization($monitor->project->organization);
    }

    public function create(User $user, Project $project): bool
    {
        return $user->hasOrganizationRole(
            $project->organization,
            OrganizationRole::Owner,
            OrganizationRole::Admin,
            OrganizationRole::Member
        );
    }

    public function update(User $user, Monitor $monitor): bool
    {
        return $user->hasOrganizationRole(
            $monitor->project->organization,
            OrganizationRole::Owner,
            OrganizationRole::Admin,
            OrganizationRole::Member
        );
    }

    public function delete(User $user, Monitor $monitor): bool
    {
        return $user->hasOrganizationRole(
            $monitor->project->organization,
            OrganizationRole::Owner,
            OrganizationRole::Admin
        );
    }

    public function pause(User $user, Monitor $monitor): bool
    {
        return $this->update($user, $monitor);
    }

    public function resume(User $user, Monitor $monitor): bool
    {
        return $this->update($user, $monitor);
    }
}
