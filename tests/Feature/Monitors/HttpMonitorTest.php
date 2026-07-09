<?php

namespace Tests\Feature\Monitors;

use App\Enums\MonitorStatus;
use App\Enums\OrganizationRole;
use App\Jobs\DispatchDueHttpMonitorsJob;
use App\Jobs\RunHttpMonitorCheckJob;
use App\Models\HttpMonitorConfig;
use App\Models\Monitor;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HttpMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_http_monitor(): void
    {
        [$user, $organization, $project] = $this->createMembership(OrganizationRole::Member);

        $response = $this->actingAs($user)->post(
            route('organizations.projects.monitors.store', [$organization, $project]),
            [
                'name' => 'Homepage',
                'url' => 'https://example.com',
                'method' => 'GET',
                'expected_status' => 200,
                'timeout_seconds' => 10,
                'interval_seconds' => 60,
                'keyword' => null,
            ]
        );

        $monitor = Monitor::query()->first();

        $this->assertNotNull($monitor);
        $response->assertRedirect(
            route('organizations.projects.monitors.show', [$organization, $project, $monitor])
        );

        $this->assertDatabaseHas('monitors', [
            'id' => $monitor->id,
            'project_id' => $project->id,
            'name' => 'Homepage',
            'status' => MonitorStatus::Pending->value,
            'type' => 'http',
        ]);

        $this->assertDatabaseHas('http_monitor_configs', [
            'monitor_id' => $monitor->id,
            'url' => 'https://example.com',
            'method' => 'GET',
            'expected_status' => 200,
        ]);
    }

    public function test_viewer_cannot_create_monitor(): void
    {
        [$user, $organization, $project] = $this->createMembership(OrganizationRole::Viewer);

        $response = $this->actingAs($user)->post(
            route('organizations.projects.monitors.store', [$organization, $project]),
            [
                'name' => 'Homepage',
                'url' => 'https://example.com',
                'method' => 'GET',
                'expected_status' => 200,
                'timeout_seconds' => 10,
                'interval_seconds' => 60,
            ]
        );

        $response->assertForbidden();
        $this->assertDatabaseCount('monitors', 0);
    }

    public function test_run_http_monitor_check_job_records_success(): void
    {
        [$user, $organization, $project] = $this->createMembership(OrganizationRole::Owner);
        $monitor = $this->createHttpMonitor($project, 'https://example.com');

        Http::fake([
            'https://example.com' => Http::response('ok', 200),
        ]);

        (new RunHttpMonitorCheckJob($monitor->id))->handle(
            app(\App\Services\HttpMonitorChecker::class),
            app(\App\Actions\RecordCheckResultAction::class)
        );

        $monitor->refresh();

        $this->assertSame(MonitorStatus::Up, $monitor->status);
        $this->assertNotNull($monitor->last_checked_at);
        $this->assertDatabaseHas('check_results', [
            'monitor_id' => $monitor->id,
            'success' => true,
            'status_code' => 200,
        ]);
    }

    public function test_run_http_monitor_check_job_records_failure(): void
    {
        [$user, $organization, $project] = $this->createMembership(OrganizationRole::Owner);
        $monitor = $this->createHttpMonitor($project, 'https://example.com');

        Http::fake([
            'https://example.com' => Http::response('nope', 500),
        ]);

        (new RunHttpMonitorCheckJob($monitor->id))->handle(
            app(\App\Services\HttpMonitorChecker::class),
            app(\App\Actions\RecordCheckResultAction::class)
        );

        $monitor->refresh();

        $this->assertSame(MonitorStatus::Down, $monitor->status);
        $this->assertDatabaseHas('check_results', [
            'monitor_id' => $monitor->id,
            'success' => false,
            'status_code' => 500,
        ]);
    }

    public function test_dispatch_due_http_monitors_job_only_dispatches_due_monitors(): void
    {
        Queue::fake();

        [$user, $organization, $project] = $this->createMembership(OrganizationRole::Owner);

        $due = $this->createHttpMonitor($project, 'https://due.example.com', [
            'last_checked_at' => null,
            'status' => MonitorStatus::Pending,
            'is_enabled' => true,
            'interval_seconds' => 60,
        ]);

        $notDue = $this->createHttpMonitor($project, 'https://fresh.example.com', [
            'last_checked_at' => now(),
            'status' => MonitorStatus::Up,
            'is_enabled' => true,
            'interval_seconds' => 3600,
        ]);

        $paused = $this->createHttpMonitor($project, 'https://paused.example.com', [
            'last_checked_at' => null,
            'status' => MonitorStatus::Paused,
            'is_enabled' => false,
            'interval_seconds' => 60,
        ]);

        (new DispatchDueHttpMonitorsJob)->handle();

        Queue::assertPushed(RunHttpMonitorCheckJob::class, function (RunHttpMonitorCheckJob $job) use ($due) {
            return $job->monitorId === $due->id;
        });

        Queue::assertNotPushed(RunHttpMonitorCheckJob::class, function (RunHttpMonitorCheckJob $job) use ($notDue) {
            return $job->monitorId === $notDue->id;
        });

        Queue::assertNotPushed(RunHttpMonitorCheckJob::class, function (RunHttpMonitorCheckJob $job) use ($paused) {
            return $job->monitorId === $paused->id;
        });
    }

    public function test_paused_http_monitor_is_skipped_by_dispatch_job(): void
    {
        Queue::fake();

        [, , $project] = $this->createMembership(OrganizationRole::Owner);

        $paused = $this->createHttpMonitor($project, 'https://paused.example.com', [
            'last_checked_at' => null,
            'status' => MonitorStatus::Paused,
            'is_enabled' => false,
            'interval_seconds' => 60,
        ]);

        (new DispatchDueHttpMonitorsJob)->handle();

        Queue::assertNothingPushed();
        Queue::assertNotPushed(RunHttpMonitorCheckJob::class, function (RunHttpMonitorCheckJob $job) use ($paused) {
            return $job->monitorId === $paused->id;
        });
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
}
