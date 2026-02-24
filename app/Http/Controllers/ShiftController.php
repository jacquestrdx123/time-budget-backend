<?php

namespace App\Http\Controllers;

use App\Models\ClockSession;
use App\Models\Project;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    private function tenantId(Request $request): int
    {
        return (int) $request->user()->tenant_id;
    }

    private function parseDateTime(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        try {
            $d = $value instanceof \DateTimeInterface ? $value : Carbon::parse($value);

            return $d->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function clockSessionToShiftLike(?ClockSession $session): ?array
    {
        if (! $session) {
            return null;
        }

        return [
            'id' => $session->id,
            'user_id' => $session->user_id,
            'tenant_id' => $session->tenant_id,
            'project_id' => $session->project_id,
            'shift_id' => $session->shift_id,
            'start_time' => $session->clocked_in_at?->format('Y-m-d\TH:i:s.000\Z'),
            'end_time' => $session->clocked_out_at?->format('Y-m-d\TH:i:s.000\Z'),
            'clocked_in_at' => $session->clocked_in_at?->format('Y-m-d\TH:i:s.000\Z'),
            'clocked_out_at' => $session->clocked_out_at?->format('Y-m-d\TH:i:s.000\Z'),
            'created_at' => $session->created_at?->format('Y-m-d\TH:i:s.000\Z'),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $query = Shift::where('tenant_id', $tenantId);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if ($request->filled('date')) {
            $date = $request->input('date');
            $query->where('start_time', '>=', "{$date}T00:00:00.000")
                ->where('start_time', '<=', "{$date}T23:59:59.999");
        } elseif ($request->filled('date_from') && $request->filled('date_to')) {
            $from = $request->input('date_from');
            $to = $request->input('date_to');
            $query->where('start_time', '>=', "{$from}T00:00:00.000")
                ->where('start_time', '<=', "{$to}T23:59:59.999");
        }

        $shifts = $query->orderBy('start_time')->get();

        return response()->json($shifts);
    }

    public function active(Request $request, string $userId): JsonResponse
    {
        $session = ClockSession::where('user_id', $userId)
            ->where('tenant_id', $this->tenantId($request))
            ->whereNull('clocked_out_at')
            ->orderByDesc('clocked_in_at')
            ->first();

        return response()->json($this->clockSessionToShiftLike($session));
    }

    public function show(Request $request, string $shiftId): JsonResponse
    {
        $shift = Shift::where('id', $shiftId)->where('tenant_id', $this->tenantId($request))->first();
        if (! $shift) {
            return response()->json(['detail' => 'Shift not found'], 404);
        }

        return response()->json($shift);
    }

    public function store(Request $request): JsonResponse
    {
        $startTime = $request->input('start_time');
        $userId = $request->input('user_id');
        $projectId = $request->input('project_id');
        $isBreak = $request->boolean('is_break') || $request->input('is_break') === 1;

        if (! $startTime || ! $userId) {
            return response()->json(['detail' => 'start_time and user_id are required'], 422);
        }
        if (! $isBreak && ! $projectId) {
            return response()->json(['detail' => 'project_id is required for non-break shifts'], 422);
        }

        $tenantId = $this->tenantId($request);
        if (! User::where('id', $userId)->where('tenant_id', $tenantId)->exists()) {
            return response()->json(['detail' => 'User not found'], 404);
        }
        if (! $isBreak && ! Project::where('id', $projectId)->where('tenant_id', $tenantId)->exists()) {
            return response()->json(['detail' => 'Project not found'], 404);
        }

        $shift = Shift::create([
            'tenant_id' => $tenantId,
            'start_time' => $this->parseDateTime($startTime),
            'end_time' => $this->parseDateTime($request->input('end_time')),
            'user_id' => $userId,
            'project_id' => $isBreak ? ($projectId ?: null) : $projectId,
            'is_break' => $isBreak,
            'break_type' => $isBreak ? $request->input('break_type') : null,
        ]);

        return response()->json($shift);
    }

    /**
     * Clock in: create a clock session (actual work) only.
     * Does NOT create a Shift. Shifts are planned work and are created via store().
     */
    public function clockIn(Request $request): JsonResponse
    {
        $userId = $request->input('user_id');
        $projectId = $request->input('project_id');
        if (! $userId || ! $projectId) {
            return response()->json(['detail' => 'user_id and project_id are required'], 422);
        }

        $tenantId = $this->tenantId($request);
        if (! User::where('id', $userId)->where('tenant_id', $tenantId)->exists()) {
            return response()->json(['detail' => 'User not found'], 404);
        }
        if (! Project::where('id', $projectId)->where('tenant_id', $tenantId)->exists()) {
            return response()->json(['detail' => 'Project not found'], 404);
        }

        $existing = ClockSession::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->whereNull('clocked_out_at')
            ->first();
        if ($existing) {
            return response()->json(['detail' => 'Already clocked in. Please clock out first.'], 409);
        }

        // Optional: link this session to an existing planned shift (never create a shift here)
        $shiftId = null;
        if ($request->filled('shift_id')) {
            $shift = Shift::where('id', $request->input('shift_id'))->where('tenant_id', $tenantId)->first();
            if ($shift) {
                $shiftId = $shift->id;
            }
        }

        $session = ClockSession::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'shift_id' => $shiftId,
            'project_id' => $projectId,
            'clocked_in_at' => now(),
            'clocked_out_at' => null,
        ]);

        return response()->json($this->clockSessionToShiftLike($session->fresh()));
    }

    public function clockOut(Request $request): JsonResponse
    {
        $userId = $request->input('user_id');
        if (! $userId) {
            return response()->json(['detail' => 'user_id is required'], 422);
        }

        $active = ClockSession::where('user_id', $userId)
            ->where('tenant_id', $this->tenantId($request))
            ->whereNull('clocked_out_at')
            ->first();
        if (! $active) {
            return response()->json(['detail' => 'No active clock session to clock out from.'], 404);
        }

        $active->update(['clocked_out_at' => now()]);
        $active->refresh();

        return response()->json($this->clockSessionToShiftLike($active));
    }

    public function update(Request $request, string $shiftId): JsonResponse
    {
        $shift = Shift::where('id', $shiftId)->where('tenant_id', $this->tenantId($request))->first();
        if (! $shift) {
            return response()->json(['detail' => 'Shift not found'], 404);
        }

        $updates = [];
        foreach (['start_time', 'end_time', 'user_id', 'project_id', 'is_break', 'break_type'] as $key) {
            if ($request->has($key)) {
                $updates[$key] = $request->input($key);
            }
        }
        if (isset($updates['start_time'])) {
            $updates['start_time'] = $this->parseDateTime($updates['start_time']);
        }
        if (isset($updates['end_time'])) {
            $updates['end_time'] = $this->parseDateTime($updates['end_time']);
        }
        if (isset($updates['is_break'])) {
            $updates['is_break'] = $updates['is_break'] === true || $updates['is_break'] === 1;
            if (! $updates['is_break']) {
                $updates['break_type'] = null;
            }
        }

        if (! empty($updates)) {
            $shift->update($updates);
        }
        $shift->refresh();

        return response()->json($shift);
    }

    public function destroy(Request $request, string $shiftId): JsonResponse
    {
        $shift = Shift::where('id', $shiftId)->where('tenant_id', $this->tenantId($request))->first();
        if (! $shift) {
            return response()->json(['detail' => 'Shift not found'], 404);
        }
        $shift->delete();

        return response()->json(null, 204);
    }
}
