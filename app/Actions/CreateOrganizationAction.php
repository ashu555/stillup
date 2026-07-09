<?php

namespace App\Actions;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class CreateOrganizationAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {}

    public function execute(User $creator, array $data): Organization
    {
        return DB::transaction(function () use ($creator, $data) {
            $organization = Organization::query()->create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Organization::uniqueSlug($data['name']),
            ]);

            $organization->users()->attach($creator->id, [
                'role' => OrganizationRole::Owner->value,
            ]);

            $this->auditLogger->log(
                action: 'organization.created',
                user: $creator,
                organization: $organization,
                auditable: $organization,
                properties: [
                    'name' => $organization->name,
                    'slug' => $organization->slug,
                ]
            );

            return $organization->fresh();
        });
    }
}
