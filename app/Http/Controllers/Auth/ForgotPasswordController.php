<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    public function __invoke(Request $request, PasswordResetService $passwordReset): JsonResponse
    {
        $email = $request->input('email');
        if (! $email || ! is_string($email) || trim($email) === '') {
            return response()->json(['detail' => 'Email is required'], 422);
        }

        $message = 'If an account exists with this email, you will receive a password reset link shortly.';
        $normalizedEmail = strtolower(trim($email));
        $user = User::where('email', $normalizedEmail)->first();

        if ($user) {
            $token = $passwordReset->createToken($user->id);
            $frontendUrl = rtrim(config('timebudget.frontend_url'), '/');
            $resetLink = "{$frontendUrl}/reset-password?token={$token}";

            if (config('timebudget.email_enabled')) {
                Mail::to($user->email)->send(new PasswordResetMail($resetLink));
            }
        }

        return response()->json(['detail' => $message]);
    }
}
