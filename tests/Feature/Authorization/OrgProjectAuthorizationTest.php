<?php

namespace Tests\Feature\Authorization;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgProjectAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_own_organization_and_project(): void
    {
        [$user, $organization, $project] = $this->membership(OrganizationRole::Member);

        $this->actingAs($user)
            ->get(route('organizations.show', $organization))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('organizations.projects.show', [$organization, $project]))
            ->assertOk();
    }

    public function test_outsider_cannot_view_organization_or_project(): void
    {
        $outsider = User::factory()->create();
        [, $organization, $project] = $this->membership(OrganizationRole::Owner);

        $this->actingAs($outsider)
            ->get(route('organizations.show', $organization))
            ->assertForbidden();

        $this->actingAs($outsider)
            ->get(route('organizations.projects.show', [$organization, $project]))
            ->assertForbidden();
    }

    public function test_viewer_cannot_create_project(): void
    {
        [$viewer, $organization] = $this->membership(OrganizationRole::Viewer);

        $this->actingAs($viewer)
            ->post(route('organizations.projects.store', $organization), [
                'name' => 'Staging',
                'slug' => 'staging',
                'public_status_enabled' => false,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('projects', [
            'organization_id' => $organization->id,
            'slug' => 'staging',
        ]);
    }

    public function test_member_can_create_project(): void
    {
        [$member, $organization] = $this->membership(OrganizationRole::Member);

        $this->actingAs($member)
            ->post(route('organizations.projects.store', $organization), [
                'name' => 'Staging',
                'slug' => 'staging',
                'public_status_enabled' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'organization_id' => $organization->id,
            'slug' => 'staging',
        ]);
    }

    /**
     * @return array{0: User, 1: Organization, 2?: Project}
     */
    private function membership(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user->id, ['role' => $role->value]);
        $project = Project::factory()->create([
            'organization_id' => $organization->id,
        ]);

        return [$user, $organization, $project];
    }
}
