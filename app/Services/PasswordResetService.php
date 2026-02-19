<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PasswordResetService
{
    private const RESET_TOKEN_EXPIRY_HOURS = 1;

    public function createToken(int $userId): string
    {
        $token = Str::random(64);
        $expiresAt = now()->addHours(self::RESET_TOKEN_EXPIRY_HOURS);

        DB::table('password_reset_tokens')->insert([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt,
            'created_at' => now(),
        ]);

        return $token;
    }

    public function consumeToken(string $token): ?User
    {
        $row = DB::table('password_reset_tokens')->where('token', $token)->first();
        if (! $row) {
            return null;
        }
        if (now()->greaterThan($row->expires_at)) {
            DB::table('password_reset_tokens')->where('token', $token)->delete();

            return null;
        }

        $user = User::find($row->user_id);
        if (! $user) {
            return null;
        }

        DB::table('password_reset_tokens')->where('token', $token)->delete();

        return $user;
    }
}
