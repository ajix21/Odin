<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Per-user rate limiter for search tools (30 req/min)
        RateLimiter::for('tools', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json(
                        ['error' => 'Terlalu banyak permintaan. Coba lagi dalam 1 menit.'],
                        429
                    );
                });
        });

        // Per-user rate limiter for API (60 req/min)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json(
                        ['error' => 'Rate limit terlampaui. Maksimal 60 request/menit.'],
                        429
                    );
                });
        });
    }
}
