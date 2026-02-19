<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (! $user) {
                return $this->unauthorized();
            }
            auth()->setUser($user);

            return $next($request);
        } catch (JWTException) {
            return $this->unauthorized();
        }
    }

    private function unauthorized(): Response
    {
        return response()->json(['detail' => 'Invalid or expired token'], 401);
    }
}
