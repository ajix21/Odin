<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailOsintController;
use App\Http\Controllers\IpGeoController;
use App\Http\Controllers\LeakOsintController;
use App\Http\Controllers\MulticheckController;
use App\Http\Controllers\PhoneInfoController;
use App\Http\Controllers\PhoneLookupController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ToutatisController;
use App\Http\Controllers\WhoisController;
use Illuminate\Support\Facades\Route;

// ── Public ────────────────────────────────────────
Route::get('/login',   [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',  [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ── Authenticated ─────────────────────────────────
Route::middleware(['auth'])->group(function () {

    Route::get('/', fn () => redirect('/dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Tools — operator + admin only
    Route::middleware(['role:operator,admin'])->group(function () {
        Route::get('/phone-lookup',  [PhoneLookupController::class, 'index'])->name('phone-lookup');
        Route::post('/phone-lookup', [PhoneLookupController::class, 'search'])->name('phone-lookup.search')
             ->middleware('throttle:30,1');

        Route::get('/leakosint',  [LeakOsintController::class, 'index'])->name('leakosint');
        Route::post('/leakosint', [LeakOsintController::class, 'search'])->name('leakosint.search')
             ->middleware('throttle:30,1');

        Route::get('/multicheck',  [MulticheckController::class, 'index'])->name('multicheck');
        Route::post('/multicheck', [MulticheckController::class, 'check'])->name('multicheck.check')
             ->middleware('throttle:30,1');

        Route::get('/email-osint',  [EmailOsintController::class, 'index'])->name('email-osint');
        Route::post('/email-osint', [EmailOsintController::class, 'analyze'])->name('email-osint.analyze')
             ->middleware('throttle:30,1');

        Route::get('/phone-info',  [PhoneInfoController::class, 'index'])->name('phone-info');
        Route::post('/phone-info', [PhoneInfoController::class, 'analyze'])->name('phone-info.analyze')
             ->middleware('throttle:30,1');

        Route::get('/ip-geo',  [IpGeoController::class, 'index'])->name('ip-geo');
        Route::post('/ip-geo', [IpGeoController::class, 'lookup'])->name('ip-geo.lookup')
             ->middleware('throttle:30,1');

        Route::get('/whois',  [WhoisController::class, 'index'])->name('whois');
        Route::post('/whois', [WhoisController::class, 'lookup'])->name('whois.lookup')
             ->middleware('throttle:30,1');

        Route::get('/toutatis',  [ToutatisController::class, 'index'])->name('toutatis');
        Route::post('/toutatis', [ToutatisController::class, 'lookup'])->name('toutatis.lookup')
             ->middleware('throttle:30,1');
    });

    // History — semua authenticated, scope per role di controller
    Route::get('/history/phone',     [PhoneLookupController::class, 'history'])->name('history.phone');
    Route::get('/history/leakosint', [LeakOsintController::class,   'history'])->name('history.leakosint');

    // Admin only
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users',             [AdminController::class, 'users'])->name('users');
        Route::get('/users/create',      [AdminController::class, 'createUser'])->name('users.create');
        Route::post('/users',            [AdminController::class, 'storeUser'])->name('users.store');
        Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
        Route::put('/users/{user}',      [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}',   [AdminController::class, 'destroyUser'])->name('users.destroy');
        Route::get('/logs',              [AdminController::class, 'logs'])->name('logs');
    });

    Route::middleware(['role:admin'])->group(function () {
        Route::get('/settings',  [SettingsController::class, 'index'])->name('settings');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});
