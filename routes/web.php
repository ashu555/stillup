<?php

use App\Http\Controllers\IncidentController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return redirect()->route('organizations.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('/organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('/organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
    Route::put('/organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');

    Route::scopeBindings()->group(function () {
        Route::get('/organizations/{organization}/projects', [ProjectController::class, 'index'])
            ->name('organizations.projects.index');
        Route::get('/organizations/{organization}/projects/create', [ProjectController::class, 'create'])
            ->name('organizations.projects.create');
        Route::post('/organizations/{organization}/projects', [ProjectController::class, 'store'])
            ->name('organizations.projects.store');
        Route::get('/organizations/{organization}/projects/{project}', [ProjectController::class, 'show'])
            ->name('organizations.projects.show');
        Route::put('/organizations/{organization}/projects/{project}', [ProjectController::class, 'update'])
            ->name('organizations.projects.update');

        Route::get('/organizations/{organization}/projects/{project}/monitors', [MonitorController::class, 'index'])
            ->name('organizations.projects.monitors.index');
        Route::get('/organizations/{organization}/projects/{project}/monitors/create', [MonitorController::class, 'create'])
            ->name('organizations.projects.monitors.create');
        Route::post('/organizations/{organization}/projects/{project}/monitors', [MonitorController::class, 'store'])
            ->name('organizations.projects.monitors.store');
        Route::get('/organizations/{organization}/projects/{project}/monitors/{monitor}', [MonitorController::class, 'show'])
            ->name('organizations.projects.monitors.show');
        Route::patch('/organizations/{organization}/projects/{project}/monitors/{monitor}', [MonitorController::class, 'update'])
            ->name('organizations.projects.monitors.update');
        Route::post('/organizations/{organization}/projects/{project}/monitors/{monitor}/pause', [MonitorController::class, 'pause'])
            ->name('organizations.projects.monitors.pause');
        Route::post('/organizations/{organization}/projects/{project}/monitors/{monitor}/resume', [MonitorController::class, 'resume'])
            ->name('organizations.projects.monitors.resume');

        Route::get('/organizations/{organization}/projects/{project}/incidents', [IncidentController::class, 'index'])
            ->name('organizations.projects.incidents.index');
        Route::get('/organizations/{organization}/projects/{project}/incidents/{incident}', [IncidentController::class, 'show'])
            ->name('organizations.projects.incidents.show');
        Route::post('/organizations/{organization}/projects/{project}/incidents/{incident}/acknowledge', [IncidentController::class, 'acknowledge'])
            ->name('organizations.projects.incidents.acknowledge');
        Route::post('/organizations/{organization}/projects/{project}/incidents/{incident}/resolve', [IncidentController::class, 'resolve'])
            ->name('organizations.projects.incidents.resolve');
    });
});

require __DIR__.'/auth.php';
