<?php

namespace Tests\Feature\Incidents;

use App\Enums\IncidentEventType;
use App\Enums\IncidentStatus;
use App\Enums\MonitorStatus;
use App\Enums\OrganizationRole;
use App\Jobs\RunHttpMonitorCheckJob;
use App\Models\AuditLog;
use App\Models\HttpMonitorConfig;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Notifications\IncidentOpenedNotification;
use App\Notifications\IncidentResolvedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class IncidentTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_check_opens_one_incident_and_sends_mail(): void
    {
        Notification::fake();

        [$owner, $organization, $project] = $this->createMembership(OrganizationRole::Owner);
        $monitor = $this->createHttpMonitor($project, 'https://example.com', [
            'status' => MonitorStatus::Up,
        ]);

        Http::fake([
            'https://example.com' => Http::response('fail', 500),
        ]);

        $this->runCheck($monitor);

        $this->assertDatabaseCount('incidents', 1);
        $incident = Incident::query()->first();
        $this->assertSame(IncidentStatus::Open, $incident->status);
        $this->assertSame(MonitorStatus::Down, $monitor->fresh()->status);

        Notification::assertSentTo($owner, IncidentOpenedNotification::class);
        $this->assertNotNull($incident->fresh()->last_notified_at);
        $this->assertDatabaseHas('incident_events', [
            'incident_id' => $incident->id,
            'type' => IncidentEventType::Opened->value,
        ]);
        $this->assertDatabaseHas('incident_events', [
            'incident_id' => $incident->id,
            'type' => IncidentEventType::Notified->value,
        ]);
    }

    public function test_another_failed_check_does_not_open_second_incident(): void
    {
        Notification::fake();

        [, , $project] = $this->createMembership(OrganizationRole::Owner);
        $monitor = $this->createHttpMonitor($project, 'https://example.com', [
            'status' => MonitorStatus::Up,
        ]);

        Http::fake([
            'https://example.com' => Http::response('fail', 500),
        ]);

        $this->runCheck($monitor);
        $this->runCheck($monitor->fresh());

        $this->assertDatabaseCount('incidents', 1);
        Notification::assertSentTimes(IncidentOpenedNotification::class, 1);
    }

    public function test_successful_check_after_down_auto_resolves_and_sends_recovery_mail(): void
    {
        Notification::fake();

        [$owner, , $project] = $this->createMembership(OrganizationRole::Owner);
        $monitor = $this->createHttpMonitor($project, 'https://example.com', [
            'status' => MonitorStatus::Up,
        ]);

        Http::fake([
            'https://example.com' => Http::sequence()
                ->push('fail', 500)
                ->push('ok', 200),
        ]);

        $this->runCheck($monitor);
        $incident = Incident::query()->first();
        $this->assertSame(IncidentStatus::Open, $incident->status);

        $this->runCheck($monitor->fresh());

        $this->assertSame(IncidentStatus::Resolved, $incident->fresh()->status);
        $this->assertNull($incident->fresh()->resolved_by);
        $this->assertSame(MonitorStatus::Up, $monitor->fresh()->status);
        Notification::assertSentTo($owner, IncidentResolvedNotification::class);
    }

    public function test_member_can_acknowledge_open_incident(): void
    {
        [$member, $organization, $project] = $this->createMembership(OrganizationRole::Member);
        $monitor = $this->createHttpMonitor($project, 'https://example.com');
        $incident = Incident::factory()->create([
            'monitor_id' => $monitor->id,
            'project_id' => $project->id,
            'status' => IncidentStatus::Open,
        ]);

        $response = $this->actingAs($member)->post(
            route('organizations.projects.incidents.acknowledge', [$organization, $project, $incident])
        );

        $response->assertRedirect();
        $this->assertSame(IncidentStatus::Acknowledged, $incident->fresh()->status);
        $this->assertSame($member->id, $incident->fresh()->acknowledged_by);
    }

    public function test_viewer_cannot_acknowledge(): void
    {
        [$viewer, $organization, $project] = $this->createMembership(OrganizationRole::Viewer);
        $monitor = $this->createHttpMonitor($project, 'https://example.com');
        $incident = Incident::factory()->create([
            'monitor_id' => $monitor->id,
            'project_id' => $project->id,
            'status' => IncidentStatus::Open,
        ]);

        $response = $this->actingAs($viewer)->post(
            route('organizations.projects.incidents.acknowledge', [$organization, $project, $incident])
        );

        $response->assertForbidden();
        $this->assertSame(IncidentStatus::Open, $incident->fresh()->status);
    }

    public function test_acknowledge_writes_incident_event_and_audit_log(): void
    {
        [$member, $organization, $project] = $this->createMembership(OrganizationRole::Member);
        $monitor = $this->createHttpMonitor($project, 'https://example.com');
        $incident = Incident::factory()->create([
            'monitor_id' => $monitor->id,
            'project_id' => $project->id,
            'status' => IncidentStatus::Open,
        ]);

        $this->actingAs($member)->post(
            route('organizations.projects.incidents.acknowledge', [$organization, $project, $incident])
        );

        $this->assertDatabaseHas('incident_events', [
            'incident_id' => $incident->id,
            'type' => IncidentEventType::Acknowledged->value,
            'user_id' => $member->id,
        ]);

        $this->assertTrue(
            AuditLog::query()
                ->where('action', 'incident.acknowledged')
                ->where('user_id', $member->id)
                ->where('auditable_type', Incident::class)
                ->where('auditable_id', $incident->id)
                ->exists()
        );
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

    private function createHttpMonitor(Project $project, string $url, array $monitorOverrides = []): Monitor
    {
        $monitor = Monitor::factory()->create(array_merge([
            'project_id' => $project->id,
        ], $monitorOverrides));

        HttpMonitorConfig::factory()->create([
            'monitor_id' => $monitor->id,
            'url' => $url,
        ]);

        return $monitor->fresh('httpConfig');
    }

    private function runCheck(Monitor $monitor): void
    {
        (new RunHttpMonitorCheckJob($monitor->id))->handle(
            app(\App\Services\HttpMonitorChecker::class),
            app(\App\Actions\RecordCheckResultAction::class)
        );
    }
}
