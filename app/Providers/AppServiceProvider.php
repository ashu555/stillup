<?php

namespace App\Providers;

use App\Models\Incident;
use App\Models\Monitor;
use App\Models\Organization;
use App\Models\Project;
use App\Policies\IncidentPolicy;
use App\Policies\MonitorPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\ProjectPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Monitor::class, MonitorPolicy::class);
        Gate::policy(Incident::class, IncidentPolicy::class);
    }
}
