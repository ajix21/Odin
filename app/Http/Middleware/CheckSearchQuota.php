<?php

namespace App\Http\Middleware;

use App\Models\SearchLog;
use Closure;
use Illuminate\Http\Request;

class CheckSearchQuota
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->daily_search_limit !== null) {
            $used = SearchLog::where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->count();

            if ($used >= $user->daily_search_limit) {
                $msg = "Kuota pencarian harian ({$user->daily_search_limit} query) telah habis. Coba lagi besok.";

                if ($request->expectsJson()) {
                    return response()->json(['error' => $msg], 429);
                }

                return back()->withErrors(['quota' => $msg]);
            }
        }

        return $next($request);
    }
}
