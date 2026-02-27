<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\TeamJoinRequestMail;
use App\Models\Tenant;
use App\Models\TenantJoinRequest;
use App\Models\User;
use App\Services\DomainService;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

class RegisterController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');
        $tenantName = $request->input('organization_name') ?? $request->input('tenant_name');

        if (! $name || ! $email || ! $password) {
            return response()->json(['detail' => 'name, email, and password are required'], 422);
        }

        $tenantName = $tenantName !== null ? trim((string) $tenantName) : '';
        if ($tenantName === '') {
            return response()->json(['detail' => 'Organization / team name is required'], 422);
        }

        $normalizedEmail = strtolower(trim($email));
        $domain = DomainService::extractDomain($normalizedEmail);

        if (! DomainService::isExcludedDomain($domain)) {
            $tenantByDomain = DomainService::findTenantByDomain($domain);
            if ($tenantByDomain) {
                if (User::where('tenant_id', $tenantByDomain->id)->where('email', $normalizedEmail)->exists()) {
                    return response()->json(['detail' => 'A user with this email already exists in this organization'], 409);
                }
                $existingRequest = TenantJoinRequest::where('tenant_id', $tenantByDomain->id)
                    ->where('email', $normalizedEmail)
                    ->where('status', 'pending')
                    ->first();
                if ($existingRequest) {
                    return response()->json(['detail' => 'A join request for this email is already pending. Please wait for an admin to respond.'], 409);
                }

                try {
                    TenantJoinRequest::create([
                        'tenant_id' => $tenantByDomain->id,
                        'name' => $name,
                        'email' => $normalizedEmail,
                        'password_hash' => Hash::make($password),
                        'status' => 'pending',
                    ]);

                    $admins = User::where('tenant_id', $tenantByDomain->id)->where('role', 'admin')->get();
                    foreach ($admins as $admin) {
                        Mail::to($admin->email)->send(new TeamJoinRequestMail(
                            $name,
                            $normalizedEmail,
                            $tenantByDomain->name ?? 'Your organization'
                        ));
                    }

                    return response()->json([
                        'message' => 'Your domain is already associated with an organization. We\'ve notified the admins. You\'ll be able to sign in once they approve your request.',
                        'code' => 'domain_exists_request_sent',
                    ], 201);
                } catch (\Throwable $e) {
                    report($e);

                    return response()->json(['detail' => 'Internal server error'], 500);
                }
            }
        }

        $tenant = Tenant::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($tenantName)])->first();

        if ($tenant) {
            if (User::where('tenant_id', $tenant->id)->where('email', $normalizedEmail)->exists()) {
                return response()->json(['detail' => 'A user with this email already exists in this organization'], 409);
            }

            try {
                $user = User::create([
                    'tenant_id' => $tenant->id,
                    'name' => $name,
                    'email' => $normalizedEmail,
                    'password_hash' => Hash::make($password),
                    'role' => 'member',
                    'is_active' => false,
                ]);

                return response()->json([
                    'message' => 'Account created. Please wait for your admin to activate your account.',
                ], 201);
            } catch (\Throwable $e) {
                report($e);

                return response()->json(['detail' => 'Internal server error'], 500);
            }
        }

        $existing = User::where('email', $normalizedEmail)->first();
        if ($existing) {
            return response()->json(['detail' => 'A user with this email already exists'], 409);
        }

        try {
            return DB::transaction(function () use ($name, $normalizedEmail, $password, $tenantName) {
                $tenant = Tenant::create([
                    'name' => $tenantName,
                ]);

                (new SystemSettingsSeeder)->seedDefaultsForTenant($tenant->id);

                $user = User::create([
                    'tenant_id' => $tenant->id,
                    'name' => $name,
                    'email' => $normalizedEmail,
                    'password_hash' => Hash::make($password),
                    'role' => 'admin',
                    'is_active' => true,
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
            'is_active' => (bool) $user->is_active,
        ];
    }
}
