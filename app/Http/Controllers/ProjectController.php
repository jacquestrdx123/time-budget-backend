<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    private function tenantId(Request $request): int
    {
        return (int) $request->user()->tenant_id;
    }

    public function index(Request $request): JsonResponse
    {
        $projects = Project::where('tenant_id', $this->tenantId($request))->get();

        return response()->json($projects);
    }

    public function show(Request $request, string $projectId): JsonResponse
    {
        $project = Project::where('id', $projectId)->where('tenant_id', $this->tenantId($request))->first();
        if (! $project) {
            return response()->json(['detail' => 'Project not found'], 404);
        }

        return response()->json($project);
    }

    public function tasks(Request $request, string $projectId): JsonResponse
    {
        $project = Project::where('id', $projectId)->where('tenant_id', $this->tenantId($request))->first();
        if (! $project) {
            return response()->json(['detail' => 'Project not found'], 404);
        }
        $tasks = $project->tasks;

        return response()->json($tasks);
    }

    public function shifts(Request $request, string $projectId): JsonResponse
    {
        $project = Project::where('id', $projectId)->where('tenant_id', $this->tenantId($request))->first();
        if (! $project) {
            return response()->json(['detail' => 'Project not found'], 404);
        }
        $shifts = $project->shifts()->orderBy('start_time')->get();

        return response()->json($shifts);
    }

    public function store(Request $request): JsonResponse
    {
        $name = $request->input('name');
        if (! $name) {
            return response()->json(['detail' => 'name is required'], 422);
        }
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if ($startDate && $endDate && $endDate < $startDate) {
            return response()->json(['detail' => 'end_date must be on or after start_date'], 422);
        }

        $project = Project::create([
            'tenant_id' => $this->tenantId($request),
            'name' => $name,
            'description' => $request->input('description'),
            'start_date' => $startDate ?: null,
            'end_date' => $endDate ?: null,
        ]);

        return response()->json($project);
    }

    public function update(Request $request, string $projectId): JsonResponse
    {
        $project = Project::where('id', $projectId)->where('tenant_id', $this->tenantId($request))->first();
        if (! $project) {
            return response()->json(['detail' => 'Project not found'], 404);
        }

        $updates = [];
        if ($request->has('name')) {
            $updates['name'] = $request->input('name');
        }
        if ($request->has('description')) {
            $updates['description'] = $request->input('description');
        }
        if ($request->has('start_date')) {
            $updates['start_date'] = $request->input('start_date') ?: null;
        }
        if ($request->has('end_date')) {
            $updates['end_date'] = $request->input('end_date') ?: null;
        }

        $start = $updates['start_date'] ?? $project->start_date?->format('Y-m-d');
        $end = $updates['end_date'] ?? $project->end_date?->format('Y-m-d');
        if ($start && $end && $end < $start) {
            return response()->json(['detail' => 'end_date must be on or after start_date'], 422);
        }

        if (! empty($updates)) {
            $project->update($updates);
        }
        $project->refresh();

        return response()->json($project);
    }

    public function destroy(Request $request, string $projectId): JsonResponse
    {
        $project = Project::where('id', $projectId)->where('tenant_id', $this->tenantId($request))->first();
        if (! $project) {
            return response()->json(['detail' => 'Project not found'], 404);
        }
        $project->delete();

        return response()->json(null, 204);
    }
}
