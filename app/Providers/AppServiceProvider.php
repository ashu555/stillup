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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

        RateLimiter::for('heartbeat', function (Request $request) {
            $token = (string) $request->route('token');

            return Limit::perMinute(120)->by($token !== '' ? $token : $request->ip());
        });

        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Monitor::class, MonitorPolicy::class);
        Gate::policy(Incident::class, IncidentPolicy::class);
    }
}
