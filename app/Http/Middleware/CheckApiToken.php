<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class CheckApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $bearer = $request->bearerToken();

        if (!$bearer) {
            return response()->json(['error' => 'Token diperlukan. Sertakan header: Authorization: Bearer {token}'], 401);
        }

        $hash = hash('sha256', $bearer);
        $user = User::where('access_token_hash', $hash)->where('is_active', true)->first();

        if (!$user) {
            return response()->json(['error' => 'Token tidak valid atau akun tidak aktif.'], 401);
        }

        auth()->setUser($user);

        return $next($request);
    }
}
