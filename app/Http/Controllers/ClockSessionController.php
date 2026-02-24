<?php

namespace App\Http\Controllers;

use App\Models\ClockSession;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClockSessionController extends Controller
{
    private function tenantId(Request $request): int
    {
        return (int) $request->user()->tenant_id;
    }

    private function toShiftLike(ClockSession $row): array
    {
        return [
            'id' => $row->id,
            'user_id' => $row->user_id,
            'tenant_id' => $row->tenant_id,
            'project_id' => $row->project_id,
            'shift_id' => $row->shift_id,
            'start_time' => $row->clocked_in_at?->format('Y-m-d\TH:i:s.000\Z'),
            'end_time' => $row->clocked_out_at?->format('Y-m-d\TH:i:s.000\Z'),
            'clocked_in_at' => $row->clocked_in_at?->format('Y-m-d\TH:i:s.000\Z'),
            'clocked_out_at' => $row->clocked_out_at?->format('Y-m-d\TH:i:s.000\Z'),
            'created_at' => $row->created_at?->format('Y-m-d\TH:i:s.000\Z'),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $query = ClockSession::where('tenant_id', $this->tenantId($request));

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if ($request->filled('date')) {
            $date = $request->input('date');
            $query->where('clocked_in_at', '>=', "{$date}T00:00:00.000")
                ->where('clocked_in_at', '<=', "{$date}T23:59:59.999");
        } elseif ($request->filled('date_from') && $request->filled('date_to')) {
            $from = $request->input('date_from');
            $to = $request->input('date_to');
            $query->where('clocked_in_at', '>=', "{$from}T00:00:00.000")
                ->where('clocked_in_at', '<=', "{$to}T23:59:59.999");
        }

        $rows = $query->orderByDesc('clocked_in_at')->get();

        return response()->json($rows->map(fn (ClockSession $r) => $this->toShiftLike($r))->values()->all());
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $session = ClockSession::where('id', $id)->where('tenant_id', $this->tenantId($request))->first();
        if (! $session) {
            return response()->json(['detail' => 'Clock session not found'], 404);
        }

        return response()->json($this->toShiftLike($session));
    }

    /**
     * Update a clock session. Only admins may edit clock ins.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['detail' => 'Only admins can edit clock ins'], 403);
        }

        $session = ClockSession::where('id', $id)->where('tenant_id', $this->tenantId($request))->first();
        if (! $session) {
            return response()->json(['detail' => 'Clock session not found'], 404);
        }

        $updates = [];

        if ($request->has('project_id')) {
            $projectId = $request->input('project_id');
            if ($projectId !== null && ! Project::where('id', $projectId)->where('tenant_id', $this->tenantId($request))->exists()) {
                return response()->json(['detail' => 'Project not found'], 404);
            }
            $updates['project_id'] = $projectId;
        }

        if ($request->filled('clocked_in_at')) {
            try {
                $updates['clocked_in_at'] = Carbon::parse($request->input('clocked_in_at'))->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                return response()->json(['detail' => 'Invalid clocked_in_at'], 422);
            }
        }

        if ($request->has('clocked_out_at')) {
            $val = $request->input('clocked_out_at');
            if ($val === null || $val === '') {
                $updates['clocked_out_at'] = null;
            } else {
                try {
                    $updates['clocked_out_at'] = Carbon::parse($val)->format('Y-m-d H:i:s');
                } catch (\Throwable) {
                    return response()->json(['detail' => 'Invalid clocked_out_at'], 422);
                }
            }
        }

        if ($request->has('shift_id')) {
            $updates['shift_id'] = $request->input('shift_id') ?: null;
        }

        if (! empty($updates)) {
            $session->update($updates);
            $session->refresh();
        }

        return response()->json($this->toShiftLike($session));
    }
}
