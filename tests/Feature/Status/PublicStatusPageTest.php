<?php

namespace Tests\Feature\Status;

use App\Enums\MonitorStatus;
use App\Enums\MonitorType;
use App\Enums\OrganizationRole;
use App\Models\HeartbeatMonitorConfig;
use App\Models\HttpMonitorConfig;
use App\Models\Monitor;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicStatusPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_status_returns_404_when_disabled(): void
    {
        $project = Project::factory()->create([
            'slug' => 'production',
            'public_status_enabled' => false,
        ]);

        $this->get(route('status.show', $project->slug))->assertNotFound();
        $this->get(route('status.json', $project->slug))->assertNotFound();
    }

    public function test_public_status_returns_200_when_enabled_and_hides_secrets(): void
    {
        $project = Project::factory()->create([
            'slug' => 'production',
            'public_status_enabled' => true,
            'name' => 'Production',
        ]);

        $http = Monitor::factory()->create([
            'project_id' => $project->id,
            'type' => MonitorType::Http,
            'name' => 'Homepage',
            'status' => MonitorStatus::Up,
            'is_enabled' => true,
        ]);
        HttpMonitorConfig::factory()->create([
            'monitor_id' => $http->id,
            'url' => 'https://secret.example.com/internal',
        ]);

        $heartbeat = Monitor::factory()->create([
            'project_id' => $project->id,
            'type' => MonitorType::Heartbeat,
            'name' => 'Backup job',
            'status' => MonitorStatus::Up,
            'is_enabled' => true,
        ]);
        HeartbeatMonitorConfig::factory()->create([
            'monitor_id' => $heartbeat->id,
            'token' => 'supersecrettoken1234567890abcdefsupersecrettoken1234567890ab',
            'last_heartbeat_at' => now(),
        ]);

        $response = $this->get(route('status.show', $project->slug));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Status/Show')
            ->where('project.name', 'Production')
            ->where('overall_status', 'operational')
            ->has('monitors', 2)
            ->where('monitors.0.name', 'Backup job')
            ->where('monitors.0.type', 'heartbeat')
            ->etc()
        );

        $props = $response->original->getData()['page']['props'];
        $this->assertArrayNotHasKey('url', $props['monitors'][0]);
        $this->assertArrayNotHasKey('token', $props['monitors'][0]);
        $this->assertArrayNotHasKey('token', $props['monitors'][1] ?? []);

        $content = $response->getContent();
        $this->assertStringNotContainsString('supersecrettoken', $content);
        $this->assertStringNotContainsString('https://secret.example.com/internal', $content);

        $json = $this->getJson(route('status.json', $project->slug));
        $json->assertOk()
            ->assertJsonPath('project.slug', 'production')
            ->assertJsonMissing(['token' => 'supersecrettoken1234567890abcdefsupersecrettoken1234567890ab'])
            ->assertJsonMissing(['url' => 'https://secret.example.com/internal']);
    }

    public function test_overall_status_is_major_outage_when_monitor_is_down(): void
    {
        $project = Project::factory()->create([
            'slug' => 'production',
            'public_status_enabled' => true,
        ]);

        Monitor::factory()->create([
            'project_id' => $project->id,
            'type' => MonitorType::Http,
            'status' => MonitorStatus::Down,
            'is_enabled' => true,
        ]);

        $this->getJson(route('status.json', $project->slug))
            ->assertOk()
            ->assertJsonPath('overall_status', 'major_outage');
    }

    public function test_viewer_cannot_toggle_public_status_page(): void
    {
        $viewer = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($viewer->id, ['role' => OrganizationRole::Viewer->value]);
        $project = Project::factory()->create([
            'organization_id' => $organization->id,
            'public_status_enabled' => false,
        ]);

        $this->actingAs($viewer)->put(
            route('organizations.projects.update', [$organization, $project]),
            [
                'name' => $project->name,
                'slug' => $project->slug,
                'public_status_enabled' => true,
            ]
        )->assertForbidden();

        $this->assertFalse($project->fresh()->public_status_enabled);
    }

    public function test_member_can_enable_public_status_page(): void
    {
        $member = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($member->id, ['role' => OrganizationRole::Member->value]);
        $project = Project::factory()->create([
            'organization_id' => $organization->id,
            'public_status_enabled' => false,
        ]);

        $this->actingAs($member)->put(
            route('organizations.projects.update', [$organization, $project]),
            [
                'name' => $project->name,
                'slug' => $project->slug,
                'public_status_enabled' => true,
            ]
        )->assertRedirect();

        $this->assertTrue($project->fresh()->public_status_enabled);
    }
}
