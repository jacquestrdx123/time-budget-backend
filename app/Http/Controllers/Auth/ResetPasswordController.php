<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    public function __invoke(Request $request, PasswordResetService $passwordReset): JsonResponse
    {
        $token = $request->input('token');
        $password = $request->input('password');

        if (! $token || ! $password) {
            return response()->json(['detail' => 'Token and password are required'], 422);
        }
        if (strlen($password) < 6) {
            return response()->json(['detail' => 'Password must be at least 6 characters'], 422);
        }

        $user = $passwordReset->consumeToken($token);
        if (! $user) {
            return response()->json(['detail' => 'Invalid or expired reset link. Please request a new one.'], 422);
        }

        $user->update(['password_hash' => Hash::make($password)]);

        return response()->json(['detail' => 'Your password has been reset. You can now sign in.']);
    }
}
