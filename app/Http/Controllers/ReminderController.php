<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReminderController extends Controller
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
        $reminders = Reminder::where('tenant_id', $this->tenantId($request))
            ->where('user_id', $this->userId($request))
            ->orderBy('trigger_at')
            ->get();

        return response()->json($reminders);
    }

    public function show(Request $request, string $reminderId): JsonResponse
    {
        $reminder = Reminder::where('id', $reminderId)
            ->where('tenant_id', $this->tenantId($request))
            ->where('user_id', $this->userId($request))
            ->first();
        if (! $reminder) {
            return response()->json(['detail' => 'Reminder not found'], 404);
        }

        return response()->json($reminder);
    }

    public function store(Request $request): JsonResponse
    {
        $title = $request->input('title');
        $triggerAt = $request->input('trigger_at');
        if (! $title || ! $triggerAt) {
            return response()->json(['detail' => 'title and trigger_at are required'], 422);
        }

        $reminder = Reminder::create([
            'tenant_id' => $this->tenantId($request),
            'user_id' => $this->userId($request),
            'title' => $title,
            'description' => $request->input('description'),
            'trigger_at' => $triggerAt,
        ]);

        return response()->json($reminder, 201);
    }

    public function update(Request $request, string $reminderId): JsonResponse
    {
        $reminder = Reminder::where('id', $reminderId)
            ->where('tenant_id', $this->tenantId($request))
            ->where('user_id', $this->userId($request))
            ->first();
        if (! $reminder) {
            return response()->json(['detail' => 'Reminder not found'], 404);
        }

        $updates = [];
        foreach (['title', 'description', 'trigger_at'] as $key) {
            if ($request->has($key)) {
                $updates[$key] = $request->input($key);
            }
        }
        if (! empty($updates)) {
            $reminder->update($updates);
        }
        $reminder->refresh();

        return response()->json($reminder);
    }

    public function destroy(Request $request, string $reminderId): JsonResponse
    {
        $reminder = Reminder::where('id', $reminderId)
            ->where('tenant_id', $this->tenantId($request))
            ->where('user_id', $this->userId($request))
            ->first();
        if (! $reminder) {
            return response()->json(['detail' => 'Reminder not found'], 404);
        }
        $reminder->delete();

        return response()->json(null, 204);
    }
}
