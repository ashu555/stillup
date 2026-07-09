<?php

namespace Database\Seeders;

use App\Actions\CreateOrganizationAction;
use App\Actions\CreateProjectAction;
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
            app(CreateProjectAction::class)->execute($user, $organization, [
                'name' => 'Production',
                'slug' => 'production',
                'public_status_enabled' => true,
            ]);
        }
    }
}
