<?php

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
    });
});

require __DIR__.'/auth.php';
