<?php

namespace Database\Seeders;

use App\Actions\CreateHeartbeatMonitorAction;
use App\Actions\CreateHttpMonitorAction;
use App\Actions\CreateOrganizationAction;
use App\Actions\CreateProjectAction;
use App\Enums\IncidentCause;
use App\Enums\IncidentEventType;
use App\Enums\IncidentStatus;
use App\Enums\MonitorStatus;
use App\Enums\MonitorType;
use App\Models\CheckResult;
use App\Models\Incident;
use App\Models\IncidentEvent;
use App\Models\Monitor;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'demo@stillup.test'],
            [
                'name' => 'Demo User',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $organization = Organization::query()->where('slug', 'acme')->first();

        if (! $organization) {
            $organization = app(CreateOrganizationAction::class)->execute($user, [
                'name' => 'Acme Monitoring',
                'slug' => 'acme',
            ]);
        } elseif (! $user->belongsToOrganization($organization)) {
            $organization->users()->attach($user->id, ['role' => 'owner']);
        }

        $project = Project::query()
            ->where('organization_id', $organization->id)
            ->where('slug', 'production')
            ->first();

        if (! $project) {
            $project = app(CreateProjectAction::class)->execute($user, $organization, [
                'name' => 'Production',
                'slug' => 'production',
                'public_status_enabled' => true,
            ]);
        } else {
            $project->update(['public_status_enabled' => true]);
        }

        $healthy = $this->ensureHttpMonitor($user, $project, [
            'name' => 'Example.com',
            'url' => 'https://example.com',
            'method' => 'GET',
            'expected_status' => 200,
            'timeout_seconds' => 10,
            'interval_seconds' => 60,
            'keyword' => null,
        ], MonitorStatus::Up);

        $this->ensureHttpMonitor($user, $project, [
            'name' => 'Broken endpoint (demo)',
            'url' => 'https://stillup-invalid.example.invalid',
            'method' => 'GET',
            'expected_status' => 200,
            'timeout_seconds' => 5,
            'interval_seconds' => 60,
            'keyword' => null,
        ], MonitorStatus::Pending);

        $heartbeat = $this->ensureHeartbeatMonitor($user, $project);

        $this->ensureResolvedIncidentHistory($healthy, $project);

        $token = $heartbeat->heartbeatConfig?->token ?? '(missing)';
        $pingUrl = rtrim((string) config('app.url'), '/').'/heartbeat/'.$token;

        $this->command?->newLine();
        $this->command?->info('Stillup demo data ready');
        $this->command?->line('  Login:     demo@stillup.test / password');
        $this->command?->line('  Org:       Acme Monitoring (acme)');
        $this->command?->line('  Project:   Production (production)');
        $this->command?->line('  Status:    '.rtrim((string) config('app.url'), '/').'/status/production');
        $this->command?->line('  Heartbeat: '.$pingUrl);
        $this->command?->line('  Curl:      curl -X POST '.$pingUrl);
        $this->command?->line('  Tip:       Pause/edit “Broken endpoint (demo)” or wait for scheduler to open an incident.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureHttpMonitor(
        User $user,
        Project $project,
        array $data,
        MonitorStatus $status
    ): Monitor {
        $monitor = Monitor::query()
            ->where('project_id', $project->id)
            ->where('type', MonitorType::Http)
            ->where('name', $data['name'])
            ->first();

        if (! $monitor) {
            $monitor = app(CreateHttpMonitorAction::class)->execute($user, $project, $data);
        }

        $monitor->update([
            'status' => $status,
            'last_checked_at' => $status === MonitorStatus::Up ? now()->subMinute() : $monitor->last_checked_at,
            'is_enabled' => true,
        ]);

        if ($status === MonitorStatus::Up && $monitor->checkResults()->doesntExist()) {
            CheckResult::query()->create([
                'monitor_id' => $monitor->id,
                'success' => true,
                'status_code' => 200,
                'response_time_ms' => 120,
                'error_message' => null,
                'checked_at' => now()->subMinute(),
            ]);
        }

        return $monitor->fresh(['httpConfig', 'checkResults']);
    }

    private function ensureHeartbeatMonitor(User $user, Project $project): Monitor
    {
        $monitor = Monitor::query()
            ->where('project_id', $project->id)
            ->where('type', MonitorType::Heartbeat)
            ->where('name', 'Nightly backup (demo)')
            ->first();

        if (! $monitor) {
            $monitor = app(CreateHeartbeatMonitorAction::class)->execute($user, $project, [
                'name' => 'Nightly backup (demo)',
                'expected_every_seconds' => 300,
                'grace_seconds' => 60,
            ]);
        }

        return $monitor->fresh('heartbeatConfig');
    }

    private function ensureResolvedIncidentHistory(Monitor $monitor, Project $project): void
    {
        $exists = Incident::query()
            ->where('monitor_id', $monitor->id)
            ->where('status', IncidentStatus::Resolved)
            ->where('summary', 'Demo: brief HTTP blip (resolved)')
            ->exists();

        if ($exists) {
            return;
        }

        DB::transaction(function () use ($monitor, $project): void {
            $openedAt = now()->subDays(2)->subHour();
            $resolvedAt = now()->subDays(2);

            $incident = Incident::query()->create([
                'monitor_id' => $monitor->id,
                'project_id' => $project->id,
                'status' => IncidentStatus::Resolved,
                'cause' => IncidentCause::HttpFailure,
                'summary' => 'Demo: brief HTTP blip (resolved)',
                'opened_at' => $openedAt,
                'resolved_at' => $resolvedAt,
            ]);

            IncidentEvent::query()->create([
                'incident_id' => $incident->id,
                'type' => IncidentEventType::Opened,
                'message' => 'HTTP check failed (demo history).',
                'meta' => ['demo' => true],
                'created_at' => $openedAt,
            ]);

            IncidentEvent::query()->create([
                'incident_id' => $incident->id,
                'type' => IncidentEventType::Resolved,
                'message' => 'Recovered automatically (demo history).',
                'meta' => ['demo' => true],
                'created_at' => $resolvedAt,
            ]);
        });
    }
}
