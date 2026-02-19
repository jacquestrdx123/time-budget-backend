<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class RegisterController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');
        $organizationName = $request->input('organization_name');

        if (! $name || ! $email || ! $password) {
            return response()->json(['detail' => 'name, email, and password are required'], 422);
        }

        $normalizedEmail = strtolower(trim($email));
        $existing = User::where('email', $normalizedEmail)->first();
        if ($existing) {
            return response()->json(['detail' => 'A user with this email already exists'], 409);
        }

        try {
            return DB::transaction(function () use ($name, $normalizedEmail, $password, $organizationName) {
                $tenant = Tenant::create([
                    'name' => $organizationName && trim((string) $organizationName) !== ''
                        ? trim((string) $organizationName)
                        : 'My Team',
                ]);

                (new SystemSettingsSeeder)->seedDefaultsForTenant($tenant->id);

                $user = User::create([
                    'tenant_id' => $tenant->id,
                    'name' => $name,
                    'email' => $normalizedEmail,
                    'password_hash' => Hash::make($password),
                    'role' => 'admin',
                ]);

                $token = JWTAuth::fromUser($user);

                return response()->json([
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'user' => $this->sanitizeUser($user),
                ], 201);
            });
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['detail' => 'Internal server error'], 500);
        }
    }

    private function sanitizeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];
    }
}
