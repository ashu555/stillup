<?php

namespace Database\Seeders;

use App\Actions\CreateHeartbeatMonitorAction;
use App\Actions\CreateHttpMonitorAction;
use App\Actions\CreateOrganizationAction;
use App\Actions\CreateProjectAction;
use App\Enums\MonitorType;
use App\Models\Monitor;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
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
        }

        $this->seedDemoMonitors($user, $project);
    }

    private function seedDemoMonitors(User $user, Project $project): void
    {
        $action = app(CreateHttpMonitorAction::class);

        $hasExample = Monitor::query()
            ->where('project_id', $project->id)
            ->whereHas('httpConfig', fn ($q) => $q->where('url', 'https://example.com'))
            ->exists();

        if (! $hasExample) {
            $action->execute($user, $project, [
                'name' => 'Example.com',
                'url' => 'https://example.com',
                'method' => 'GET',
                'expected_status' => 200,
                'timeout_seconds' => 10,
                'interval_seconds' => 60,
                'keyword' => null,
            ]);
        }

        $hasBad = Monitor::query()
            ->where('project_id', $project->id)
            ->whereHas('httpConfig', fn ($q) => $q->where('url', 'https://stillup-invalid.example.invalid'))
            ->exists();

        if (! $hasBad) {
            $action->execute($user, $project, [
                'name' => 'Broken endpoint (demo)',
                'url' => 'https://stillup-invalid.example.invalid',
                'method' => 'GET',
                'expected_status' => 200,
                'timeout_seconds' => 5,
                'interval_seconds' => 60,
                'keyword' => null,
            ]);
        }
    }
}
