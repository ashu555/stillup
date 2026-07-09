<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\PublicStatusService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class PublicStatusController extends Controller
{
    public function show(string $slug, PublicStatusService $service): Response
    {
        $project = $this->findPublicProject($slug);

        return Inertia::render('Status/Show', $service->build($project));
    }

    public function json(string $slug, PublicStatusService $service): JsonResponse
    {
        $project = $this->findPublicProject($slug);

        return response()->json($service->build($project));
    }

    private function findPublicProject(string $slug): Project
    {
        $project = Project::query()
            ->where('slug', $slug)
            ->where('public_status_enabled', true)
            ->first();

        abort_unless($project, 404);

        return $project;
    }
}
