<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $email = $request->input('email');
        $password = $request->input('password');

        if (! $email || ! $password) {
            return response()->json(['detail' => 'email and password are required'], 422);
        }

        $normalizedEmail = strtolower(trim($email));
        $candidates = User::where('email', $normalizedEmail)->orderBy('id')->get();

        foreach ($candidates as $user) {
            if (Hash::check($password, $user->password_hash)) {
                if (! $user->is_active) {
                    return response()->json(['detail' => 'Your account is pending approval from your team admin.'], 403);
                }
                $token = JWTAuth::fromUser($user);

                return response()->json([
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'user' => $this->sanitizeUser($user),
                ]);
            }
        }

        return response()->json(['detail' => 'Invalid email or password'], 401);
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
}
