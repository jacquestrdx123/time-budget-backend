<?php

namespace App\Http\Controllers;

use App\Models\ClockSession;
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
}
