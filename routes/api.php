<?php

use App\Http\Controllers\ApiController;
use App\Http\Middleware\CheckApiToken;
use App\Http\Middleware\CheckSearchQuota;
use Illuminate\Support\Facades\Route;

Route::middleware([CheckApiToken::class])->prefix('v1')->group(function () {

    Route::get('/me', [ApiController::class, 'me']);

    // Tool endpoints — also check daily quota
    Route::middleware([CheckSearchQuota::class])->group(function () {
        Route::post('/multicheck', [ApiController::class, 'multicheck'])->middleware('throttle:tools');
        Route::post('/ip-geo',    [ApiController::class, 'ipGeo'])->middleware('throttle:tools');
        Route::post('/whois',     [ApiController::class, 'whois'])->middleware('throttle:tools');
    });
});
