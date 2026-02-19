<?php

namespace App\Http\Controllers;

use App\Models\PersonalTodo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonalTodoController extends Controller
{
    private function tenantId(Request $request): int
    {
        return (int) $request->user()->tenant_id;
    }

    private function userId(Request $request): int
    {
        return (int) $request->user()->id;
    }

    public function index(Request $request): JsonResponse
    {
        $todos = PersonalTodo::where('tenant_id', $this->tenantId($request))
            ->where('user_id', $this->userId($request))
            ->get();

        return response()->json($todos);
    }

    public function show(Request $request, string $todoId): JsonResponse
    {
        $todo = PersonalTodo::where('id', $todoId)
            ->where('tenant_id', $this->tenantId($request))
            ->where('user_id', $this->userId($request))
            ->first();
        if (! $todo) {
            return response()->json(['detail' => 'Personal todo not found'], 404);
        }

        return response()->json($todo);
    }

    public function store(Request $request): JsonResponse
    {
        $title = $request->input('title');
        if (! $title) {
            return response()->json(['detail' => 'title is required'], 422);
        }

        $todo = PersonalTodo::create([
            'tenant_id' => $this->tenantId($request),
            'user_id' => $this->userId($request),
            'title' => $title,
            'description' => $request->input('description'),
            'status' => $request->input('status', 'pending'),
        ]);

        return response()->json($todo);
    }

    public function update(Request $request, string $todoId): JsonResponse
    {
        $todo = PersonalTodo::where('id', $todoId)
            ->where('tenant_id', $this->tenantId($request))
            ->where('user_id', $this->userId($request))
            ->first();
        if (! $todo) {
            return response()->json(['detail' => 'Personal todo not found'], 404);
        }

        $updates = [];
        foreach (['title', 'description', 'status'] as $key) {
            if ($request->has($key)) {
                $updates[$key] = $request->input($key);
            }
        }
        if (! empty($updates)) {
            $todo->update($updates);
        }
        $todo->refresh();

        return response()->json($todo);
    }

    public function destroy(Request $request, string $todoId): JsonResponse
    {
        $todo = PersonalTodo::where('id', $todoId)
            ->where('tenant_id', $this->tenantId($request))
            ->where('user_id', $this->userId($request))
            ->first();
        if (! $todo) {
            return response()->json(['detail' => 'Personal todo not found'], 404);
        }
        $todo->delete();

        return response()->json(null, 204);
    }
}
