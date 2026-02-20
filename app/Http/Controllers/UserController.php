<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private function tenantId(Request $request): int
    {
        return (int) $request->user()->tenant_id;
    }

    private function sanitizeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $users = User::where('tenant_id', $this->tenantId($request))->get();

        return response()->json($users->map(fn (User $u) => $this->sanitizeUser($u))->values()->all());
    }

    public function show(Request $request, string $userId): JsonResponse
    {
        $user = User::where('id', $userId)->where('tenant_id', $this->tenantId($request))->first();
        if (! $user) {
            return response()->json(['detail' => 'User not found'], 404);
        }

        return response()->json($this->sanitizeUser($user));
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['detail' => 'Only admins can create users'], 403);
        }

        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');
        if (! $name || ! $email || ! $password) {
            return response()->json(['detail' => 'name, email, and password are required'], 422);
        }

        $tenantId = $this->tenantId($request);
        $normalizedEmail = strtolower(trim($email));
        if (User::where('tenant_id', $tenantId)->where('email', $normalizedEmail)->exists()) {
            return response()->json(['detail' => 'A user with this email already exists in your team'], 409);
        }

        $user = User::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'email' => $normalizedEmail,
            'password_hash' => Hash::make($password),
            'role' => 'member',
        ]);

        return response()->json($this->sanitizeUser($user));
    }

    public function update(Request $request, string $userId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $targetUser = User::where('id', $userId)->where('tenant_id', $tenantId)->first();
        if (! $targetUser) {
            return response()->json(['detail' => 'User not found'], 404);
        }

        $isSelf = (string) $targetUser->id === (string) $request->user()->id;
        if (! $isSelf && $request->user()->role !== 'admin') {
            return response()->json(['detail' => 'Only admins can update other users'], 403);
        }

        $updates = [];
        if ($request->has('name')) {
            $updates['name'] = $request->input('name');
        }
        if ($request->has('email')) {
            $updates['email'] = strtolower(trim($request->input('email')));
        }
        if ($request->has('role') && $request->user()->role === 'admin') {
            $role = $request->input('role');
            if (in_array($role, ['admin', 'member'], true)) {
                $updates['role'] = $role;
            }
        }
        if ($request->has('is_active') && $request->user()->role === 'admin') {
            $updates['is_active'] = (bool) $request->input('is_active');
        }

        if (! empty($updates)) {
            if (isset($updates['role']) && $updates['role'] === 'member' && $targetUser->role === 'admin') {
                if ($isSelf) {
                    return response()->json(['detail' => 'You cannot demote yourself.'], 400);
                }
                $adminCount = User::where('tenant_id', $tenantId)->where('role', 'admin')->count();
                if ($adminCount <= 1) {
                    return response()->json(['detail' => 'Cannot demote the last admin. Assign another admin first.'], 400);
                }
            }
            if (isset($updates['email'])) {
                $conflict = User::where('tenant_id', $tenantId)->where('email', $updates['email'])->first();
                if ($conflict && (int) $conflict->id !== (int) $userId) {
                    return response()->json(['detail' => 'A user with this email already exists in your team'], 409);
                }
            }
            $targetUser->update($updates);
        }

        $targetUser->refresh();

        return response()->json($this->sanitizeUser($targetUser));
    }

    public function destroy(Request $request, string $userId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $targetUser = User::where('id', $userId)->where('tenant_id', $tenantId)->first();
        if (! $targetUser) {
            return response()->json(['detail' => 'User not found'], 404);
        }

        $isSelf = (string) $targetUser->id === (string) $request->user()->id;
        if (! $isSelf && $request->user()->role !== 'admin') {
            return response()->json(['detail' => 'Only admins can delete other users'], 403);
        }

        if ($targetUser->role === 'admin') {
            $adminCount = User::where('tenant_id', $tenantId)->where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return response()->json(['detail' => 'Cannot delete the last admin. Assign another admin first.'], 400);
            }
        }

        $targetUser->delete();

        return response()->json(null, 204);
    }
}
