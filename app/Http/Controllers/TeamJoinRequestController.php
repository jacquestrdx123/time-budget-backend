<?php

namespace App\Http\Controllers;

use App\Models\TenantJoinRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamJoinRequestController extends Controller
{
    private function tenantId(Request $request): int
    {
        return (int) $request->user()->tenant_id;
    }

    public function index(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['detail' => 'Only admins can list join requests'], 403);
        }

        $requests = TenantJoinRequest::where('tenant_id', $this->tenantId($request))
            ->pending()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests->map(fn (TenantJoinRequest $r) => [
            'id' => $r->id,
            'name' => $r->name,
            'email' => $r->email,
            'status' => $r->status,
            'created_at' => $r->created_at?->toIso8601String(),
        ])->values()->all());
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['detail' => 'Only admins can approve join requests'], 403);
        }

        $tenantId = $this->tenantId($request);
        $joinRequest = TenantJoinRequest::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->pending()
            ->first();

        if (! $joinRequest) {
            return response()->json(['detail' => 'Join request not found or already processed'], 404);
        }

        if (User::where('tenant_id', $tenantId)->where('email', $joinRequest->email)->exists()) {
            $joinRequest->update(['status' => 'rejected']);

            return response()->json(['detail' => 'A user with this email already exists in your team'], 409);
        }

        $user = User::create([
            'tenant_id' => $tenantId,
            'name' => $joinRequest->name,
            'email' => $joinRequest->email,
            'password_hash' => $joinRequest->password_hash,
            'role' => 'member',
            'is_active' => true,
        ]);

        $joinRequest->delete();

        return response()->json([
            'id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => true,
        ], 201);
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['detail' => 'Only admins can reject join requests'], 403);
        }

        $tenantId = $this->tenantId($request);
        $joinRequest = TenantJoinRequest::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->pending()
            ->first();

        if (! $joinRequest) {
            return response()->json(['detail' => 'Join request not found or already processed'], 404);
        }

        $joinRequest->delete();

        return response()->json(null, 204);
    }
}
