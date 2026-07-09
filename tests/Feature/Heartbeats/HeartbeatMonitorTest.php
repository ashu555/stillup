<?php

namespace Tests\Feature\Heartbeats;

use App\Actions\MarkHeartbeatMissedAction;
use App\Enums\IncidentCause;
use App\Enums\IncidentStatus;
use App\Enums\MonitorStatus;
use App\Enums\MonitorType;
use App\Enums\OrganizationRole;
use App\Jobs\DetectMissedHeartbeatsJob;
use App\Models\HeartbeatMonitorConfig;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Notifications\IncidentOpenedNotification;
use App\Notifications\IncidentResolvedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HeartbeatMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_heartbeat_monitor(): void
    {
        [$user, $organization, $project] = $this->createMembership(OrganizationRole::Member);

        $response = $this->actingAs($user)->post(
            route('organizations.projects.monitors.store-heartbeat', [$organization, $project]),
            [
                'name' => 'Cron job',
                'expected_every_seconds' => 300,
                'grace_seconds' => 60,
            ]
        );

        $monitor = Monitor::query()->first();
        $this->assertNotNull($monitor);
        $response->assertRedirect(
            route('organizations.projects.monitors.show', [$organization, $project, $monitor])
        );

        $this->assertSame(MonitorType::Heartbeat, $monitor->type);
        $this->assertSame(MonitorStatus::Pending, $monitor->status);
        $this->assertSame(300, $monitor->interval_seconds);
        $this->assertNotNull($monitor->heartbeatConfig?->token);
        $this->assertSame(64, strlen($monitor->heartbeatConfig->token));
    }

    public function test_viewer_cannot_create_heartbeat_monitor(): void
    {
        [$user, $organization, $project] = $this->createMembership(OrganizationRole::Viewer);

        $response = $this->actingAs($user)->post(
            route('organizations.projects.monitors.store-heartbeat', [$organization, $project]),
            [
                'name' => 'Cron job',
                'expected_every_seconds' => 300,
                'grace_seconds' => 60,
            ]
        );

        $response->assertForbidden();
        $this->assertDatabaseCount('monitors', 0);
    }

    public function test_public_ping_records_heartbeat_and_sets_up(): void
    {
        [, , $project] = $this->createMembership(OrganizationRole::Owner);
        $monitor = $this->createHeartbeatMonitor($project);

        $response = $this->post('/heartbeat/'.$monitor->heartbeatConfig->token);

        $response->assertNoContent();
        $monitor->refresh();

        $this->assertSame(MonitorStatus::Up, $monitor->status);
        $this->assertNotNull($monitor->heartbeatConfig->last_heartbeat_at);
        $this->assertDatabaseHas('check_results', [
            'monitor_id' => $monitor->id,
            'success' => true,
        ]);
    }

    public function test_never_pinged_heartbeat_stays_pending_and_does_not_open_incident(): void
    {
        Notification::fake();

        [, , $project] = $this->createMembership(OrganizationRole::Owner);
        $monitor = $this->createHeartbeatMonitor($project, [
            'created_at' => now()->subHour(),
        ], [
            'last_heartbeat_at' => null,
            'expected_every_seconds' => 60,
            'grace_seconds' => 0,
        ]);

        (new DetectMissedHeartbeatsJob)->handle(app(MarkHeartbeatMissedAction::class));

        $this->assertSame(MonitorStatus::Pending, $monitor->fresh()->status);
        $this->assertDatabaseCount('incidents', 0);
        Notification::assertNothingSent();
    }

    public function test_overdue_heartbeat_marks_down_and_opens_incident(): void
    {
        Notification::fake();

        [$owner, , $project] = $this->createMembership(OrganizationRole::Owner);
        $monitor = $this->createHeartbeatMonitor($project, [
            'status' => MonitorStatus::Up,
        ], [
            'last_heartbeat_at' => now()->subMinutes(10),
            'expected_every_seconds' => 60,
            'grace_seconds' => 60,
        ]);

        (new DetectMissedHeartbeatsJob)->handle(app(MarkHeartbeatMissedAction::class));

        $monitor->refresh();
        $this->assertSame(MonitorStatus::Down, $monitor->status);

        $incident = Incident::query()->first();
        $this->assertNotNull($incident);
        $this->assertSame(IncidentCause::HeartbeatMiss, $incident->cause);
        $this->assertSame(IncidentStatus::Open, $incident->status);
        Notification::assertSentTo($owner, IncidentOpenedNotification::class);
    }

    public function test_second_miss_does_not_open_duplicate_incident(): void
    {
        Notification::fake();

        [, , $project] = $this->createMembership(OrganizationRole::Owner);
        $monitor = $this->createHeartbeatMonitor($project, [
            'status' => MonitorStatus::Up,
        ], [
            'last_heartbeat_at' => now()->subMinutes(10),
            'expected_every_seconds' => 60,
            'grace_seconds' => 0,
        ]);

        (new DetectMissedHeartbeatsJob)->handle(app(MarkHeartbeatMissedAction::class));
        (new DetectMissedHeartbeatsJob)->handle(app(MarkHeartbeatMissedAction::class));

        $this->assertDatabaseCount('incidents', 1);
        Notification::assertSentTimes(IncidentOpenedNotification::class, 1);
    }

    public function test_ping_after_down_resolves_incident(): void
    {
        Notification::fake();

        [$owner, , $project] = $this->createMembership(OrganizationRole::Owner);
        $monitor = $this->createHeartbeatMonitor($project, [
            'status' => MonitorStatus::Down,
        ], [
            'last_heartbeat_at' => now()->subMinutes(10),
            'expected_every_seconds' => 60,
            'grace_seconds' => 0,
        ]);

        Incident::factory()->create([
            'monitor_id' => $monitor->id,
            'project_id' => $project->id,
            'status' => IncidentStatus::Open,
            'cause' => IncidentCause::HeartbeatMiss,
            'summary' => 'Missed heartbeat',
        ]);

        $this->post('/heartbeat/'.$monitor->heartbeatConfig->token)->assertNoContent();

        $this->assertSame(MonitorStatus::Up, $monitor->fresh()->status);
        $this->assertSame(IncidentStatus::Resolved, Incident::query()->first()->status);
        Notification::assertSentTo($owner, IncidentResolvedNotification::class);
    }

    public function test_paused_monitor_ping_returns_204_without_state_change(): void
    {
        [, , $project] = $this->createMembership(OrganizationRole::Owner);
        $monitor = $this->createHeartbeatMonitor($project, [
            'status' => MonitorStatus::Paused,
            'is_enabled' => false,
        ], [
            'last_heartbeat_at' => null,
        ]);

        $this->post('/heartbeat/'.$monitor->heartbeatConfig->token)->assertNoContent();

        $monitor->refresh();
        $this->assertSame(MonitorStatus::Paused, $monitor->status);
        $this->assertNull($monitor->heartbeatConfig->last_heartbeat_at);
        $this->assertDatabaseCount('check_results', 0);
    }

    public function test_disabled_monitor_ping_returns_403(): void
    {
        [, , $project] = $this->createMembership(OrganizationRole::Owner);
        $monitor = $this->createHeartbeatMonitor($project, [
            'status' => MonitorStatus::Up,
            'is_enabled' => false,
        ]);

        $this->post('/heartbeat/'.$monitor->heartbeatConfig->token)
            ->assertForbidden();
    }

    public function test_paused_heartbeat_is_skipped_by_miss_detector(): void
    {
        Notification::fake();

        [, , $project] = $this->createMembership(OrganizationRole::Owner);
        $monitor = $this->createHeartbeatMonitor($project, [
            'status' => MonitorStatus::Paused,
            'is_enabled' => false,
        ], [
            'last_heartbeat_at' => now()->subHour(),
            'expected_every_seconds' => 60,
            'grace_seconds' => 0,
        ]);

        (new DetectMissedHeartbeatsJob)->handle(app(MarkHeartbeatMissedAction::class));

        $this->assertSame(MonitorStatus::Paused, $monitor->fresh()->status);
        $this->assertDatabaseCount('incidents', 0);
        Notification::assertNothingSent();
    }

    /**
     * @return array{0: User, 1: Organization, 2: Project}
     */
    private function createMembership(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user->id, ['role' => $role->value]);
        $project = Project::factory()->create([
            'organization_id' => $organization->id,
        ]);

        return [$user, $organization, $project];
    }

    private function createHeartbeatMonitor(
        Project $project,
        array $monitorOverrides = [],
        array $configOverrides = []
    ): Monitor {
        $monitor = Monitor::factory()->create(array_merge([
            'project_id' => $project->id,
            'type' => MonitorType::Heartbeat,
            'status' => MonitorStatus::Pending,
            'interval_seconds' => 300,
        ], $monitorOverrides));

        HeartbeatMonitorConfig::factory()->create(array_merge([
            'monitor_id' => $monitor->id,
            'expected_every_seconds' => $monitor->interval_seconds,
            'grace_seconds' => 60,
        ], $configOverrides));

        return $monitor->fresh('heartbeatConfig');
    }
}
