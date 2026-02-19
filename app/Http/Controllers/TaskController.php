<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    private function tenantId(Request $request): int
    {
        return (int) $request->user()->tenant_id;
    }

    public function index(Request $request): JsonResponse
    {
        $tasks = Task::where('tenant_id', $this->tenantId($request))->get();

        return response()->json($tasks);
    }

    public function show(Request $request, string $taskId): JsonResponse
    {
        $task = Task::where('id', $taskId)->where('tenant_id', $this->tenantId($request))->first();
        if (! $task) {
            return response()->json(['detail' => 'Task not found'], 404);
        }

        return response()->json($task);
    }

    public function store(Request $request): JsonResponse
    {
        $title = $request->input('title');
        $projectId = $request->input('project_id');
        if (! $title || ! $projectId) {
            return response()->json(['detail' => 'title and project_id are required'], 422);
        }
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if ($startDate && $endDate && $endDate < $startDate) {
            return response()->json(['detail' => 'end_date must be on or after start_date'], 422);
        }

        $tenantId = $this->tenantId($request);
        $project = Project::where('id', $projectId)->where('tenant_id', $tenantId)->first();
        if (! $project) {
            return response()->json(['detail' => 'Project not found'], 404);
        }

        $task = Task::create([
            'tenant_id' => $tenantId,
            'title' => $title,
            'description' => $request->input('description'),
            'status' => $request->input('status', 'pending'),
            'project_id' => $projectId,
            'user_id' => $request->input('user_id'),
            'start_date' => $startDate ?: null,
            'end_date' => $endDate ?: null,
        ]);

        return response()->json($task);
    }

    public function update(Request $request, string $taskId): JsonResponse
    {
        $task = Task::where('id', $taskId)->where('tenant_id', $this->tenantId($request))->first();
        if (! $task) {
            return response()->json(['detail' => 'Task not found'], 404);
        }

        $allowed = ['title', 'description', 'status', 'project_id', 'user_id', 'start_date', 'end_date'];
        $updates = [];
        foreach ($allowed as $key) {
            if ($request->has($key)) {
                $updates[$key] = $request->input($key);
            }
        }
        if (isset($updates['start_date'])) {
            $updates['start_date'] = $updates['start_date'] ?: null;
        }
        if (isset($updates['end_date'])) {
            $updates['end_date'] = $updates['end_date'] ?: null;
        }

        $start = $updates['start_date'] ?? $task->start_date?->format('Y-m-d');
        $end = $updates['end_date'] ?? $task->end_date?->format('Y-m-d');
        if ($start && $end && $end < $start) {
            return response()->json(['detail' => 'end_date must be on or after start_date'], 422);
        }

        if (! empty($updates)) {
            $task->update($updates);
        }
        $task->refresh();

        return response()->json($task);
    }

    public function destroy(Request $request, string $taskId): JsonResponse
    {
        $task = Task::where('id', $taskId)->where('tenant_id', $this->tenantId($request))->first();
        if (! $task) {
            return response()->json(['detail' => 'Task not found'], 404);
        }
        $task->delete();

        return response()->json(null, 204);
    }
}
