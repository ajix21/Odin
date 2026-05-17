# Project ODIN Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bangun platform OSINT terpadu "Project ODIN" dengan Laravel 11 menggabungkan GetContact, LeakOSINT, dan 6 tools OsintPlus dalam satu UI profesional.

**Architecture:** Laravel 11 monolith — semua 6 tools OsintPlus diport ke PHP Services menggunakan Guzzle HTTP. Auth RBAC (admin/operator/viewer) dari project Osint. Design system dari Getcontact-App (terang, biru elektrik, glassmorphism).

**Tech Stack:** Laravel 11, PHP 8.2+, MySQL, Tailwind CSS 4.0, Vite, giggsey/libphonenumber-for-php, io-developer/php-whois, SheetJS (client-side export)

---

## File Map

```
app/Http/Controllers/
  AuthController.php         — login, logout, brute-force
  DashboardController.php    — stats, recent activity
  AdminController.php        — CRUD users, audit logs
  SettingsController.php     — semua API keys (admin only)
  PhoneLookupController.php  — GetContact + history log
  LeakOsintController.php    — LeakOSINT + history log
  MulticheckController.php   — username multicheck
  EmailOsintController.php   — email OSINT
  PhoneInfoController.php    — phone info (libphonenumber)
  IpGeoController.php        — IP geolocation
  WhoisController.php        — WHOIS + DNS + SSL
  ToutatisController.php     — Instagram deep OSINT

app/Http/Middleware/
  CheckRole.php
  SecurityHeaders.php

app/Models/
  User.php, SearchLog.php, LoginAttempt.php, Setting.php

app/Services/
  GetContactService.php, LeakOsintService.php
  MulticheckService.php, EmailOsintService.php
  PhoneInfoService.php, IpGeolocationService.php
  WhoisService.php, ToutatisService.php

database/migrations/
  xxxx_create_users_table.php (modify default)
  xxxx_create_search_logs_table.php
  xxxx_create_login_attempts_table.php
  xxxx_create_settings_table.php

database/seeders/AdminSeeder.php

resources/views/
  layouts/app.blade.php
  auth/login.blade.php
  dashboard/index.blade.php
  tools/phone-lookup.blade.php
  tools/phone-info.blade.php
  tools/email-osint.blade.php
  tools/multicheck.blade.php
  tools/toutatis.blade.php
  tools/ip-geo.blade.php
  tools/whois.blade.php
  tools/leakosint.blade.php
  history/phone.blade.php
  history/leakosint.blade.php
  admin/users/index.blade.php
  admin/users/form.blade.php
  admin/logs.blade.php
  settings/index.blade.php

routes/web.php
resources/css/app.css
public/icon.png  (sudah ada)
```

---

## PHASE 1 — Foundation

### Task 1: Laravel Project Scaffold

**Files:**
- Create: seluruh project Laravel di `D:\Project Bot - Work\Project ODIN\`

- [ ] **Step 1: Buat project Laravel di dalam folder yang sudah ada**

```powershell
cd "D:\Project Bot - Work\Project ODIN"
composer create-project laravel/laravel . --prefer-dist
```

Jika muncul error "directory not empty", jalankan:
```powershell
composer create-project laravel/laravel temp_install --prefer-dist
xcopy /E /H /Y temp_install\* .
rmdir /S /Q temp_install
```

- [ ] **Step 2: Install PHP dependencies tambahan**

```powershell
composer require giggsey/libphonenumber-for-php:^8.13 io-developer/php-whois:^4.0
```

- [ ] **Step 3: Install NPM dependencies**

```powershell
npm install
npm install xlsx jspdf jspdf-autotable
```

- [ ] **Step 4: Configure .env**

Edit `.env`:
```env
APP_NAME="Project ODIN"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=project_odin
DB_USERNAME=root
DB_PASSWORD=

SESSION_LIFETIME=120
```

- [ ] **Step 5: Buat database dan pastikan icon tersedia**

```powershell
# Buat database MySQL
mysql -u root -e "CREATE DATABASE IF NOT EXISTS project_odin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# Pastikan icon ada di public/
ls public/icon.png
```

- [ ] **Step 6: Commit**

```powershell
git init
git add .
git commit -m "feat: initial Laravel 11 project scaffold for Project ODIN"
```

---

### Task 2: Database Migrations

**Files:**
- Modify: `database/migrations/xxxx_create_users_table.php`
- Create: `database/migrations/xxxx_create_search_logs_table.php`
- Create: `database/migrations/xxxx_create_login_attempts_table.php`
- Create: `database/migrations/xxxx_create_settings_table.php`

- [ ] **Step 1: Modifikasi users migration**

Buka file `database/migrations/0001_01_01_000000_create_users_table.php`, ganti isinya:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username', 100)->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'operator', 'viewer'])->default('viewer');
            $table->boolean('is_active')->default(true);
            $table->text('api_token')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            $table->index('role');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

- [ ] **Step 2: Buat search_logs migration**

```powershell
php artisan make:migration create_search_logs_table
```

Isi file yang dibuat:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('tool', ['getcontact', 'leakosint']);
            $table->text('query');
            $table->json('result_json')->nullable();
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->text('error_message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index('user_id');
            $table->index('tool');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};
```

- [ ] **Step 3: Buat login_attempts migration**

```powershell
php artisan make:migration create_login_attempts_table
```

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->boolean('success')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['username', 'ip_address']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
```

- [ ] **Step 4: Buat settings migration**

```powershell
php artisan make:migration create_settings_table
```

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->boolean('is_secret')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
```

- [ ] **Step 5: Hapus migration yang tidak dipakai**

Hapus file-file ini jika ada (dibuat Laravel secara default tapi tidak kita pakai):
- `database/migrations/0001_01_01_000001_create_cache_table.php`
- `database/migrations/0001_01_01_000002_create_jobs_table.php`

- [ ] **Step 6: Jalankan migrations**

```powershell
php artisan migrate
```

Expected output: 4 migrations berhasil (users, search_logs, login_attempts, settings).

- [ ] **Step 7: Commit**

```powershell
git add database/
git commit -m "feat: add database migrations for users, search_logs, login_attempts, settings"
```

---

### Task 3: Models

**Files:**
- Modify: `app/Models/User.php`
- Create: `app/Models/SearchLog.php`
- Create: `app/Models/LoginAttempt.php`
- Create: `app/Models/Setting.php`

- [ ] **Step 1: Tulis User model**

```php
<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'username', 'email', 'password',
        'role', 'is_active', 'api_token',
        'last_login_at', 'last_login_ip',
    ];

    protected $hidden = ['password', 'remember_token', 'api_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'is_active'         => 'boolean',
        'password'          => 'hashed',
    ];

    public function isAdmin(): bool    { return $this->role === 'admin'; }
    public function isOperator(): bool { return $this->role === 'operator'; }
    public function canSearch(): bool  { return in_array($this->role, ['admin', 'operator']); }

    public function searchLogs() { return $this->hasMany(SearchLog::class); }
}
```

- [ ] **Step 2: Tulis SearchLog model**

```php
<?php
// app/Models/SearchLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchLog extends Model
{
    protected $fillable = [
        'user_id', 'tool', 'query', 'result_json',
        'status', 'error_message', 'ip_address',
    ];

    protected $casts = ['result_json' => 'array'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
```

- [ ] **Step 3: Tulis LoginAttempt model**

```php
<?php
// app/Models/LoginAttempt.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    public $timestamps  = false;
    const CREATED_AT    = 'created_at';

    protected $fillable = ['username', 'ip_address', 'user_agent', 'success', 'created_at'];
    protected $casts    = ['success' => 'boolean', 'created_at' => 'datetime'];
}
```

- [ ] **Step 4: Tulis Setting model**

```php
<?php
// app/Models/Setting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'is_secret'];
    protected $casts    = ['is_secret' => 'boolean'];

    public static function getValue(string $key, string $default = ''): string
    {
        $s = static::where('key', $key)->first();
        if (!$s) return $default;
        try {
            return $s->is_secret ? decrypt($s->value) : ($s->value ?? $default);
        } catch (\Exception) {
            return $default;
        }
    }

    public static function setValue(string $key, string $value, bool $isSecret = false): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $isSecret ? encrypt($value) : $value, 'is_secret' => $isSecret]
        );
    }

    public static function allDecrypted(): array
    {
        return static::all()->mapWithKeys(function ($s) {
            $val = '';
            try { $val = $s->is_secret ? decrypt($s->value) : ($s->value ?? ''); } catch (\Exception) {}
            return [$s->key => ['value' => $val, 'is_secret' => $s->is_secret]];
        })->toArray();
    }
}
```

- [ ] **Step 5: Tulis unit test untuk Setting model**

```php
<?php
// tests/Unit/SettingModelTest.php
namespace Tests\Unit;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_getValue_returns_default_when_key_missing(): void
    {
        $this->assertEquals('default', Setting::getValue('missing_key', 'default'));
    }

    public function test_setValue_and_getValue_plain(): void
    {
        Setting::setValue('test_key', 'hello');
        $this->assertEquals('hello', Setting::getValue('test_key'));
    }

    public function test_setValue_and_getValue_secret(): void
    {
        Setting::setValue('secret_key', 'my_secret', true);
        $this->assertEquals('my_secret', Setting::getValue('secret_key'));
        $raw = Setting::where('key', 'secret_key')->value('value');
        $this->assertNotEquals('my_secret', $raw);
    }
}
```

- [ ] **Step 6: Jalankan test**

```powershell
php artisan test tests/Unit/SettingModelTest.php
```

Expected: 3 tests PASS.

- [ ] **Step 7: Commit**

```powershell
git add app/Models/ tests/Unit/
git commit -m "feat: add User, SearchLog, LoginAttempt, Setting models with unit tests"
```

---

### Task 4: Middleware

**Files:**
- Create: `app/Http/Middleware/CheckRole.php`
- Create: `app/Http/Middleware/SecurityHeaders.php`
- Modify: `bootstrap/app.php`

- [ ] **Step 1: Tulis CheckRole middleware**

```php
<?php
// app/Http/Middleware/CheckRole.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        if (!auth()->check() || !in_array(auth()->user()->role, $roles)) {
            abort(403, 'Akses ditolak.');
        }
        return $next($request);
    }
}
```

- [ ] **Step 2: Tulis SecurityHeaders middleware**

```php
<?php
// app/Http/Middleware/SecurityHeaders.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);
        return $response
            ->header('X-Frame-Options', 'DENY')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->header('X-XSS-Protection', '1; mode=block')
            ->header('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}
```

- [ ] **Step 3: Daftarkan middleware di bootstrap/app.php**

Buka `bootstrap/app.php`, tambahkan di dalam `->withMiddleware(...)`:

```php
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\SecurityHeaders;

->withMiddleware(function (\Illuminate\Foundation\Configuration\Middleware $middleware) {
    $middleware->append(SecurityHeaders::class);
    $middleware->alias([
        'role' => CheckRole::class,
    ]);
    $middleware->throttleWithRedis();
})
```

- [ ] **Step 4: Commit**

```powershell
git add app/Http/Middleware/ bootstrap/app.php
git commit -m "feat: add CheckRole and SecurityHeaders middleware"
```

---

### Task 5: Auth System

**Files:**
- Create: `app/Http/Controllers/AuthController.php`
- Create: `tests/Feature/AuthControllerTest.php`

- [ ] **Step 1: Tulis AuthController**

```php
<?php
// app/Http/Controllers/AuthController.php
namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private int $maxAttempts    = 5;
    private int $lockoutMinutes = 15;

    public function showLogin()
    {
        return auth()->check() ? redirect('/dashboard') : view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:100',
            'password' => 'required|string|min:6',
        ]);

        $ip       = $request->ip();
        $username = $request->input('username');

        $failsByIp   = LoginAttempt::where('ip_address', $ip)->where('success', false)
            ->where('created_at', '>=', now()->subMinutes($this->lockoutMinutes))->count();
        $failsByUser = LoginAttempt::where('username', $username)->where('success', false)
            ->where('created_at', '>=', now()->subMinutes($this->lockoutMinutes))->count();

        if ($failsByIp >= $this->maxAttempts || $failsByUser >= $this->maxAttempts) {
            $this->logAttempt($username, $ip, $request->userAgent(), false);
            return back()->withErrors(['username' => "Terlalu banyak percobaan. Coba lagi dalam {$this->lockoutMinutes} menit."])
                         ->withInput(['username' => $username]);
        }

        $user = User::where('username', $username)->orWhere('email', $username)->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            $this->logAttempt($username, $ip, $request->userAgent(), false);
            return back()->withErrors(['username' => 'Username atau password salah.'])
                         ->withInput(['username' => $username]);
        }

        if (!$user->is_active) {
            $this->logAttempt($username, $ip, $request->userAgent(), false);
            return back()->withErrors(['username' => 'Akun dinonaktifkan. Hubungi administrator.'])
                         ->withInput(['username' => $username]);
        }

        Auth::login($user, $request->boolean('remember'));
        $this->logAttempt($username, $ip, $request->userAgent(), true);
        $user->update(['last_login_at' => now(), 'last_login_ip' => $ip]);
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    private function logAttempt(string $username, string $ip, ?string $ua, bool $success): void
    {
        LoginAttempt::create([
            'username'   => $username,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'success'    => $success,
            'created_at' => now(),
        ]);
    }
}
```

- [ ] **Step 2: Tulis feature test untuk auth**

```php
<?php
// tests/Feature/AuthControllerTest.php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'username'  => 'testuser',
            'role'      => 'operator',
            'is_active' => true,
        ], $attrs));
    }

    public function test_login_page_loads(): void
    {
        $this->get('/login')->assertOk()->assertViewIs('auth.login');
    }

    public function test_authenticated_user_redirected_from_login(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->get('/login')->assertRedirect('/dashboard');
    }

    public function test_login_with_valid_credentials(): void
    {
        $this->makeUser(['password' => bcrypt('password123')]);
        $this->post('/login', ['username' => 'testuser', 'password' => 'password123'])
             ->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_login_with_invalid_password(): void
    {
        $this->makeUser();
        $this->post('/login', ['username' => 'testuser', 'password' => 'wrongpass'])
             ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->makeUser(['is_active' => false, 'password' => bcrypt('password123')]);
        $this->post('/login', ['username' => 'testuser', 'password' => 'password123'])
             ->assertSessionHasErrors('username');
    }

    public function test_logout(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
```

- [ ] **Step 3: Jalankan test (akan FAIL karena routes belum ada)**

```powershell
php artisan test tests/Feature/AuthControllerTest.php
```

Expected: FAIL — "Route not found". Normal, routes belum dibuat.

- [ ] **Step 4: Commit**

```powershell
git add app/Http/Controllers/AuthController.php tests/Feature/
git commit -m "feat: add AuthController with brute-force protection"
```

---

### Task 6: Admin Seeder

**Files:**
- Create: `database/seeders/AdminSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Create: `database/factories/UserFactory.php`

- [ ] **Step 1: Tulis AdminSeeder**

```php
<?php
// database/seeders/AdminSeeder.php
namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(['username' => 'admin'], [
            'name'      => 'Administrator',
            'email'     => 'admin@odin.local',
            'password'  => Hash::make('Admin@12345'),
            'role'      => 'admin',
            'is_active' => true,
        ]);

        // Seed default settings keys (empty — diisi via Settings page)
        $keys = [
            ['key' => 'getcontact_token',          'is_secret' => true],
            ['key' => 'getcontact_final_key',       'is_secret' => true],
            ['key' => 'getcontact_client_device_id','is_secret' => true],
            ['key' => 'instagram_session_id',       'is_secret' => true],
            ['key' => 'ipinfo_token',               'is_secret' => true],
            ['key' => 'leakosint_api_token',        'is_secret' => true],
            ['key' => 'leakosint_api_url',          'is_secret' => false, 'value' => 'https://leakosintapi.com/'],
        ];

        foreach ($keys as $k) {
            Setting::firstOrCreate(
                ['key' => $k['key']],
                ['value' => $k['value'] ?? null, 'is_secret' => $k['is_secret']]
            );
        }
    }
}
```

- [ ] **Step 2: Update DatabaseSeeder**

```php
<?php
// database/seeders/DatabaseSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminSeeder::class);
    }
}
```

- [ ] **Step 3: Tulis UserFactory untuk testing**

```php
<?php
// database/factories/UserFactory.php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'      => fake()->name(),
            'username'  => fake()->unique()->userName(),
            'email'     => fake()->unique()->safeEmail(),
            'password'  => Hash::make('password'),
            'role'      => 'operator',
            'is_active' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }

    public function viewer(): static
    {
        return $this->state(['role' => 'viewer']);
    }
}
```

- [ ] **Step 4: Jalankan seeder**

```powershell
php artisan db:seed
```

Expected: User admin dan 7 settings rows dibuat.

- [ ] **Step 5: Commit**

```powershell
git add database/seeders/ database/factories/
git commit -m "feat: add AdminSeeder with default user and settings keys"
```

---

### Task 7: Routes

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Tulis semua routes**

```php
<?php
// routes/web.php
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
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout',[AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ── Authenticated ─────────────────────────────────
Route::middleware(['auth'])->group(function () {

    Route::get('/', fn() => redirect('/dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Tools (operator + admin only)
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

    // History (semua authenticated user, tapi scope per role)
    Route::get('/history/phone',    [PhoneLookupController::class, 'history'])->name('history.phone');
    Route::get('/history/leakosint',[LeakOsintController::class, 'history'])->name('history.leakosint');

    // Admin only
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users',            [AdminController::class, 'users'])->name('users');
        Route::get('/users/create',     [AdminController::class, 'createUser'])->name('users.create');
        Route::post('/users',           [AdminController::class, 'storeUser'])->name('users.store');
        Route::get('/users/{user}/edit',[AdminController::class, 'editUser'])->name('users.edit');
        Route::put('/users/{user}',     [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}',  [AdminController::class, 'destroyUser'])->name('users.destroy');
        Route::get('/logs',             [AdminController::class, 'logs'])->name('logs');
    });

    Route::middleware(['role:admin'])->group(function () {
        Route::get('/settings',  [SettingsController::class, 'index'])->name('settings');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});
```

- [ ] **Step 2: Jalankan test auth sekarang routes sudah ada**

```powershell
php artisan test tests/Feature/AuthControllerTest.php
```

Expected: Semua test PASS (view belum ada tapi redirect test harus PASS).

- [ ] **Step 3: Commit**

```powershell
git add routes/web.php
git commit -m "feat: define all application routes with RBAC middleware"
```

---

### Task 8: Design System + Layout

**Files:**
- Create: `resources/css/app.css`
- Create: `resources/views/layouts/app.blade.php`
- Modify: `vite.config.js`

- [ ] **Step 1: Setup Tailwind CSS**

Edit `vite.config.js`:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
```

- [ ] **Step 2: Invoke frontend-design skill untuk layout + CSS**

Invoke skill `frontend-design:frontend-design` dengan instruksi berikut:

```
Build resources/css/app.css dan resources/views/layouts/app.blade.php
untuk Project ODIN (Laravel Blade).

DESIGN SYSTEM:
- Light theme, background #F0F5FF, surface #FFFFFF
- Primary blue: #2563EB, hover: #3B82F6, accent cyan: #0EA5E9
- Fonts: Sora (heading via Google Fonts), Plus Jakarta Sans (body), JetBrains Mono (data)
- Glassmorphism navbar (backdrop-filter blur), shadow cards
- CSS custom properties: --c-blue-500 sampai --c-blue-50, --c-surface, --c-bg, --c-text, --c-border, --c-success, --c-danger, --c-warning

LAYOUT (app.blade.php):
- Navbar atas: glassmorphism, logo icon.png + "ODIN" teks Sora bold, badge nama user + role di kanan
- Sidebar kiri 240px fixed: logo mini, kategori navigasi dengan group labels
  - Dashboard (icon grid)
  - 📱 Telepon: Phone Lookup, Phone OSINT
  - 👤 Identitas: Email OSINT, Username Check, Instagram
  - 🌐 Jaringan: IP Geolocation, WHOIS Domain
  - 🔍 Data Breach: LeakOSINT
  - ⚙️ Admin (hanya jika role=admin): Users, Audit Logs, Settings
- Content area: padding 24px, @yield('content')
- Active state: item sidebar highlight biru dengan indicator left border
- Blade directives: @auth, @if(auth()->user()->isAdmin()) untuk conditional admin menu

CSS app.css harus define:
- Custom properties di :root
- .card class (white bg, rounded-xl, shadow-sm, border)
- .btn-primary (biru solid, hover effect, rounded-lg)
- .badge class untuk role/status chips
- Fade-up animation untuk page transitions
- Responsive: sidebar collapse di mobile
```

- [ ] **Step 3: Build assets**

```powershell
npm run build
```

Expected: Tidak ada error, `public/build/` berisi manifest.json.

- [ ] **Step 4: Commit**

```powershell
git add resources/ vite.config.js public/build/
git commit -m "feat: implement design system, app layout, and sidebar navigation"
```

---

### Task 9: Login View + Dashboard

**Files:**
- Create: `resources/views/auth/login.blade.php`
- Create: `app/Http/Controllers/DashboardController.php`
- Create: `resources/views/dashboard/index.blade.php`

- [ ] **Step 1: Invoke frontend-design skill untuk login view**

```
Build resources/views/auth/login.blade.php (Laravel Blade, standalone — tidak extend app layout).

Halaman login Project ODIN:
- Centered card di tengah layar, background gradient biru gelap (#0C1B33 → #1B3F8A)
- Logo icon.png (80px) + teks "ODIN" Sora bold putih di atas card
- Subtitle: "Open Source Intelligence Platform"
- Card putih, rounded-2xl, shadow-2xl, padding 40px
- Form fields: Username/Email, Password (dengan toggle show/hide)
- Remember me checkbox
- Submit button biru solid full width "Masuk ke ODIN"
- Error display: @if($errors->any()) merah di atas form
- CSRF token: @csrf
- Action: POST /login
```

- [ ] **Step 2: Tulis DashboardController**

```php
<?php
// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use App\Models\SearchLog;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user         = auth()->user();
        $today        = now()->startOfDay();

        // Stats: admin lihat semua, operator/viewer lihat milik sendiri
        $logsQuery = $user->isAdmin()
            ? SearchLog::query()
            : SearchLog::where('user_id', $user->id);

        $stats = [
            'searches_today'   => (clone $logsQuery)->where('created_at', '>=', $today)->count(),
            'searches_total'   => (clone $logsQuery)->count(),
            'active_users'     => $user->isAdmin() ? User::where('is_active', true)->count() : null,
            'top_tool'         => (clone $logsQuery)->selectRaw('tool, count(*) as cnt')
                                    ->groupBy('tool')->orderByDesc('cnt')->value('tool'),
        ];

        $recentLogs = (clone $logsQuery)->with('user')->latest()->limit(10)->get();

        $recentLogins = $user->isAdmin()
            ? LoginAttempt::latest('created_at')->limit(10)->get()
            : collect();

        return view('dashboard.index', compact('stats', 'recentLogs', 'recentLogins'));
    }
}
```

- [ ] **Step 3: Invoke frontend-design skill untuk dashboard**

```
Build resources/views/dashboard/index.blade.php (extends layouts.app, @section('content')).

Dashboard Project ODIN:
- Page header: "Dashboard" dengan subtitle tanggal hari ini
- Stats cards row (4 cards):
  - Total Search Hari Ini (icon search, biru)
  - Total Search All Time (icon chart, cyan)
  - Active Users (icon users, hanya jika admin, hijau)
  - Tool Terpopuler (icon star, amber)
- Recent Activity section (2 kolom jika admin, 1 kolom jika bukan):
  - Tabel Recent Searches: tool badge (getcontact=biru, leakosint=ungu), query, status, waktu
  - Tabel Recent Logins (hanya admin): username, IP, success/fail badge, waktu
- Quick Access: grid 4x2 kartu tool dengan icon dan nama, link ke masing-masing tool
  Phone Lookup, LeakOSINT, Multicheck, Email OSINT, Phone OSINT, IP Geo, WHOIS, Toutatis
- Gunakan variabel Blade: $stats, $recentLogs, $recentLogins
```

- [ ] **Step 4: Test halaman login dan dashboard**

```powershell
php artisan serve
```

Buka `http://localhost:8000/login` — pastikan form tampil.
Login dengan admin/Admin@12345 — pastikan redirect ke dashboard.

- [ ] **Step 5: Commit**

```powershell
git add app/Http/Controllers/DashboardController.php resources/views/
git commit -m "feat: add login view and dashboard with stats and activity feed"
```

---

### Task 10: Admin Panel

**Files:**
- Create: `app/Http/Controllers/AdminController.php`
- Create: `resources/views/admin/users/index.blade.php`
- Create: `resources/views/admin/users/form.blade.php`
- Create: `resources/views/admin/logs.blade.php`

- [ ] **Step 1: Tulis AdminController**

```php
<?php
// app/Http/Controllers/AdminController.php
namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    public function users()
    {
        $users = User::withCount('searchLogs')->latest()->paginate(30);
        return view('admin.users.index', compact('users'));
    }

    public function createUser()
    {
        return view('admin.users.form', ['user' => null]);
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'username'  => 'required|string|max:100|unique:users',
            'email'     => 'required|email|unique:users',
            'password'  => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'role'      => 'required|in:admin,operator,viewer',
            'api_token' => 'nullable|string|max:200',
        ]);

        User::create([
            'name'      => $request->name,
            'username'  => $request->username,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'api_token' => $request->api_token ?: null,
            'is_active' => true,
        ]);

        return redirect()->route('admin.users')->with('success', 'User berhasil dibuat.');
    }

    public function editUser(User $user)
    {
        return view('admin.users.form', compact('user'));
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'username'  => 'required|string|max:100|unique:users,username,' . $user->id,
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'password'  => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'role'      => 'required|in:admin,operator,viewer',
            'is_active' => 'boolean',
            'api_token' => 'nullable|string|max:200',
        ]);

        $data = $request->only(['name', 'username', 'email', 'role', 'api_token']);
        $data['is_active'] = $request->boolean('is_active');
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        return redirect()->route('admin.users')->with('success', 'User berhasil diperbarui.');
    }

    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Tidak bisa menghapus akun sendiri.']);
        }
        $user->delete();
        return redirect()->route('admin.users')->with('success', 'User berhasil dihapus.');
    }

    public function logs()
    {
        $searchLogs = SearchLog::with('user')->latest()->paginate(30, ['*'], 'search_page');
        $loginLogs  = LoginAttempt::latest('created_at')->paginate(50, ['*'], 'login_page');
        return view('admin.logs', compact('searchLogs', 'loginLogs'));
    }
}
```

- [ ] **Step 2: Invoke frontend-design skill untuk admin views**

```
Build 3 Blade views (masing-masing extends layouts.app):

1. resources/views/admin/users/index.blade.php
   - Page header "Manajemen Users" + tombol "+ Tambah User" (biru, kanan)
   - Tabel: Name, Username, Email, Role (badge berwarna: admin=merah, operator=biru, viewer=abu), 
     Status (aktif=hijau/nonaktif=merah), Total Search, Last Login, Actions (Edit, Hapus)
   - Konfirmasi hapus via JavaScript confirm()
   - Pagination Laravel
   - Flash success/error message

2. resources/views/admin/users/form.blade.php
   - Page header "Tambah User" atau "Edit User" (tergantung $user null atau tidak)
   - Form: Name, Username, Email, Password + Confirm (nullable saat edit), Role dropdown, 
     Is Active toggle (saat edit), API Token (opsional)
   - Validation error display per field
   - Tombol Submit + Cancel (link back)

3. resources/views/admin/logs.blade.php
   - Page header "Audit Logs"
   - 2 section dengan tab atau accordion:
     A. Search Logs: tabel (User, Tool badge, Query, Status, IP, Waktu), pagination $searchLogs
     B. Login Logs: tabel (Username, IP, User Agent truncated, Success/Fail badge, Waktu), pagination $loginLogs
```

- [ ] **Step 3: Tulis feature test untuk AdminController**

```php
<?php
// tests/Feature/AdminControllerTest.php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_non_admin_cannot_access_users_page(): void
    {
        $op = User::factory()->create(['role' => 'operator']);
        $this->actingAs($op)->get('/admin/users')->assertForbidden();
    }

    public function test_admin_can_view_users(): void
    {
        $this->actingAs($this->admin())->get('/admin/users')->assertOk();
    }

    public function test_admin_can_create_user(): void
    {
        $this->actingAs($this->admin())->post('/admin/users', [
            'name'                  => 'Test User',
            'username'              => 'testop',
            'email'                 => 'testop@example.com',
            'password'              => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role'                  => 'operator',
        ])->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', ['username' => 'testop']);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->delete("/admin/users/{$admin->id}")->assertSessionHasErrors();
    }
}
```

- [ ] **Step 4: Jalankan test**

```powershell
php artisan test tests/Feature/AdminControllerTest.php
```

Expected: 4 tests PASS.

- [ ] **Step 5: Commit**

```powershell
git add app/Http/Controllers/AdminController.php resources/views/admin/ tests/Feature/AdminControllerTest.php
git commit -m "feat: add admin panel with user management and audit logs"
```

---

### Task 11: Settings Page

**Files:**
- Create: `app/Http/Controllers/SettingsController.php`
- Create: `resources/views/settings/index.blade.php`

- [ ] **Step 1: Tulis SettingsController**

```php
<?php
// app/Http/Controllers/SettingsController.php
namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    private array $settingsConfig = [
        'getcontact_token'           => ['label' => 'GetContact Token',           'secret' => true,  'group' => 'GetContact'],
        'getcontact_final_key'       => ['label' => 'GetContact Final Key',        'secret' => true,  'group' => 'GetContact'],
        'getcontact_client_device_id'=> ['label' => 'GetContact Client Device ID', 'secret' => true,  'group' => 'GetContact'],
        'leakosint_api_token'        => ['label' => 'LeakOSINT API Token',         'secret' => true,  'group' => 'LeakOSINT'],
        'leakosint_api_url'          => ['label' => 'LeakOSINT API URL',           'secret' => false, 'group' => 'LeakOSINT'],
        'ipinfo_token'               => ['label' => 'IPInfo Token',                'secret' => true,  'group' => 'Phone OSINT'],
        'instagram_session_id'       => ['label' => 'Instagram Session ID',        'secret' => true,  'group' => 'Toutatis'],
    ];

    public function index()
    {
        $current = Setting::allDecrypted();
        $config  = $this->settingsConfig;
        return view('settings.index', compact('current', 'config'));
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach ($this->settingsConfig as $key => $cfg) {
            $rules[$key] = 'nullable|string|max:1000';
        }
        $request->validate($rules);

        foreach ($this->settingsConfig as $key => $cfg) {
            if ($request->has($key)) {
                $value = $request->input($key, '');
                Setting::setValue($key, $value, $cfg['secret']);
            }
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
```

- [ ] **Step 2: Invoke frontend-design skill untuk settings view**

```
Build resources/views/settings/index.blade.php (extends layouts.app).

Halaman Settings — hanya admin:
- Page header "Pengaturan & Credentials" dengan ikon kunci
- Peringatan kuning: "Credential disimpan terenkripsi. Kosongkan field untuk tidak mengubah nilai."
- Form grouped per tool (card per group: GetContact, LeakOSINT, Phone OSINT, Toutatis):
  - Setiap group: header card dengan nama tool + icon
  - Setiap field: label, input type="password" (karena secret) atau text, 
    placeholder "Biarkan kosong jika tidak ingin mengubah" untuk field secret
  - Field non-secret (leakosint_api_url): input type="text" dengan nilai saat ini
- Tombol "Simpan Pengaturan" biru solid di bawah, full width
- Flash success message
- Gunakan variabel: $current (array key=>['value','is_secret']), $config
```

- [ ] **Step 3: Commit**

```powershell
git add app/Http/Controllers/SettingsController.php resources/views/settings/
git commit -m "feat: add centralized settings page for all API credentials"
```

---

## PHASE 2 — Core Tools with History

### Task 12: GetContact Service + Phone Lookup Tool

**Files:**
- Create: `app/Services/GetContactService.php`
- Create: `app/Http/Controllers/PhoneLookupController.php`
- Create: `resources/views/tools/phone-lookup.blade.php`
- Create: `resources/views/history/phone.blade.php`
- Create: `tests/Feature/PhoneLookupControllerTest.php`

- [ ] **Step 1: Tulis GetContactService**

Diport langsung dari `Getcontact-App/app/Services/GetContactService.php`, dengan adaptasi:
- Hapus dependency ke model `Credential`
- Baca credential dari `Setting::getValue()`

```php
<?php
// app/Services/GetContactService.php
namespace App\Services;

use App\Models\Setting;

class GetContactService
{
    const API_BASE_URL    = 'https://pbssrv-centralevents.com';
    const EP_SEARCH       = '/v2.8/search';
    const EP_NUMBER_DETAIL = '/v2.8/number-detail';
    const HMAC_SECRET_KEY = '31426764382a642f3a6665497235466f3d236d5d785b722b4c657457442a495b494524324866782a2364292478587a78662d7a7b7578593f71703e2b7e365762';
    const ANDROID_OS      = 'android 9';
    const APP_VERSION     = '8.4.0';
    const LANG            = 'en_US';
    const COUNTRY_CODE    = 'id';
    const DEFAULT_DEVICE_ID = '174680a6f1765b5f';

    private string $token;
    private string $finalKey;
    private string $clientDeviceId;

    public function __construct()
    {
        $this->token          = Setting::getValue('getcontact_token');
        $this->finalKey       = Setting::getValue('getcontact_final_key');
        $this->clientDeviceId = Setting::getValue('getcontact_client_device_id', self::DEFAULT_DEVICE_ID);
    }

    public function isConfigured(): bool
    {
        return !empty($this->token) && !empty($this->finalKey);
    }

    public function normalizePhone(string $input): string
    {
        $input = preg_replace('/\s+/', '', $input);
        if (preg_match('/^\+62/', $input)) return $input;
        if (preg_match('/^62/', $input))   return '+' . $input;
        if (preg_match('/^0/', $input))    return '+62' . substr($input, 1);
        return '+62' . $input;
    }

    private function signature(string $timestamp, string $message): string
    {
        return base64_encode(
            hash_hmac('sha256', "$timestamp-$message", hex2bin(self::HMAC_SECRET_KEY), true)
        );
    }

    private function encrypt(string $data): string
    {
        return base64_encode(
            openssl_encrypt($data, 'aes-256-ecb', hex2bin($this->finalKey), OPENSSL_RAW_DATA)
        );
    }

    private function decrypt(string $data): ?string
    {
        $result = openssl_decrypt(
            base64_decode($data), 'aes-256-ecb', hex2bin($this->finalKey), OPENSSL_RAW_DATA
        );
        return $result !== false ? $result : null;
    }

    private function callApi(string $endpoint, array $body): object
    {
        $bodyJson  = json_encode((object) $body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = (new \DateTime())->format('Uv');
        $deviceId  = $this->clientDeviceId ?: self::DEFAULT_DEVICE_ID;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => self::API_BASE_URL . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => '{"data": "' . $this->encrypt($bodyJson) . '"}',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-os: '               . self::ANDROID_OS,
                'x-app-version: '      . self::APP_VERSION,
                'x-client-device-id: ' . $deviceId,
                'x-lang: '             . self::LANG,
                'x-token: '            . $this->token,
                'x-req-timestamp: '    . $timestamp,
                'x-country-code: id',
                'x-encrypted: 1',
                'x-req-signature: '    . $this->signature($timestamp, $bodyJson),
            ],
            CURLOPT_HEADER         => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response   = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $body       = $response !== false ? substr($response, $headerSize) : null;
        curl_close($ch);

        return (object) ['httpCode' => $httpCode, 'body' => $body];
    }

    private function search(string $phone): ?array
    {
        $res = $this->callApi(self::EP_SEARCH, [
            'countryCode' => self::COUNTRY_CODE,
            'phoneNumber' => $phone,
            'source'      => 'search',
            'token'       => $this->token,
        ]);

        if ($res->httpCode !== 200 || !$res->body) return null;
        $parsed    = json_decode($res->body, false);
        $decrypted = $this->decrypt($parsed->data ?? '');
        $data      = json_decode($decrypted, true);
        return $data['result'] ?? null;
    }

    private function numberDetail(string $phone): ?array
    {
        $res = $this->callApi(self::EP_NUMBER_DETAIL, [
            'countryCode' => self::COUNTRY_CODE,
            'phoneNumber' => $phone,
            'source'      => 'profile',
            'token'       => $this->token,
        ]);

        if ($res->httpCode !== 200 || !$res->body) return null;
        $parsed    = json_decode($res->body, false);
        $decrypted = $this->decrypt($parsed->data ?? '');
        $data      = json_decode($decrypted, true);
        return $data['result'] ?? null;
    }

    public function lookup(string $rawPhone): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'phone' => $rawPhone, 'error' => 'GetContact credential belum dikonfigurasi. Buka Settings.'];
        }

        $phone        = $this->normalizePhone($rawPhone);
        $searchResult = $this->search($phone);

        if (!$searchResult) {
            return ['success' => false, 'phone' => $phone, 'error' => 'Tidak ada hasil atau credential memerlukan verifikasi captcha.'];
        }

        $detailResult = $this->numberDetail($phone);

        return [
            'success'      => true,
            'phone'        => $phone,
            'profile'      => $searchResult['profile']            ?? [],
            'badge'        => $searchResult['badge']              ?? null,
            'spam'         => $searchResult['spamInfo']['degree'] ?? null,
            'tag_count'    => $searchResult['tagCount']           ?? 0,
            'tags'         => $detailResult['tags']               ?? [],
            'subscription' => $searchResult['subscriptionInfo']   ?? [],
        ];
    }
}
```

- [ ] **Step 2: Tulis PhoneLookupController**

```php
<?php
// app/Http/Controllers/PhoneLookupController.php
namespace App\Http\Controllers;

use App\Models\SearchLog;
use App\Services\GetContactService;
use Illuminate\Http\Request;

class PhoneLookupController extends Controller
{
    public function __construct(private GetContactService $gc) {}

    public function index()
    {
        return view('tools.phone-lookup');
    }

    public function search(Request $request)
    {
        $request->validate(['phone' => 'required|string|max:20']);

        $result = $this->gc->lookup($request->input('phone'));
        $status = $result['success'] ? 'success' : 'failed';

        SearchLog::create([
            'user_id'       => auth()->id(),
            'tool'          => 'getcontact',
            'query'         => $request->input('phone'),
            'result_json'   => $result,
            'status'        => $status,
            'error_message' => $result['error'] ?? null,
            'ip_address'    => $request->ip(),
        ]);

        return view('tools.phone-lookup', compact('result'));
    }

    public function history()
    {
        $user  = auth()->user();
        $query = SearchLog::where('tool', 'getcontact')->with('user');
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        $logs = $query->latest()->paginate(30);
        return view('history.phone', compact('logs'));
    }
}
```

- [ ] **Step 3: Invoke frontend-design skill untuk phone-lookup view**

```
Build resources/views/tools/phone-lookup.blade.php (extends layouts.app).

Phone Lookup Tool — GetContact API:
- Page header: icon 📱, judul "Phone Lookup", subtitle "Cari informasi nomor telepon via GetContact"
- Input card: label "Nomor Telepon", input text placeholder "08xx, 62xx, atau +62xx",
  tombol "Cari" biru solid full width dengan icon search
- Jika variabel $result ada (setelah submit):
  - Jika $result['success'] == false: error card merah dengan pesan $result['error']
  - Jika success: tampilkan:
    A. Profile card: foto profile (jika ada), nama lengkap, nomor, email, negara
    B. Spam badge: degree (High=merah, Medium=kuning, Low=hijau)
    C. Tags grid: setiap tag sebagai chip biru, tampilkan jumlah tag_count
    D. Subscription info: plan name jika ada
- History link di bawah: "Lihat Riwayat Pencarian →"
```

- [ ] **Step 4: Invoke frontend-design skill untuk history/phone view**

```
Build resources/views/history/phone.blade.php (extends layouts.app).

Riwayat Phone Lookup:
- Page header "Riwayat Phone Lookup" + jumlah total
- Tabel: Nomor ($log->query), Status badge (success=hijau, failed=merah), 
  User (jika admin), Nama hasil (dari result_json.profile.name jika ada), 
  Waktu (human diff)
- Klik baris expand detail (atau link ke modal) menampilkan result_json formatted
- Pagination Laravel
```

- [ ] **Step 5: Tulis feature test**

```php
<?php
// tests/Feature/PhoneLookupControllerTest.php
namespace Tests\Feature;

use App\Models\User;
use App\Services\GetContactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PhoneLookupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_lookup_page_loads_for_operator(): void
    {
        $user = User::factory()->create(['role' => 'operator']);
        $this->actingAs($user)->get('/phone-lookup')->assertOk();
    }

    public function test_viewer_cannot_access_phone_lookup(): void
    {
        $user = User::factory()->viewer()->create();
        $this->actingAs($user)->get('/phone-lookup')->assertForbidden();
    }

    public function test_successful_search_logs_to_db(): void
    {
        $user = User::factory()->create(['role' => 'operator']);

        $mockResult = ['success' => true, 'phone' => '+628123456789', 'profile' => ['name' => 'Test']];
        $this->mock(GetContactService::class, function ($mock) use ($mockResult) {
            $mock->shouldReceive('lookup')->once()->andReturn($mockResult);
        });

        $this->actingAs($user)->post('/phone-lookup', ['phone' => '08123456789']);

        $this->assertDatabaseHas('search_logs', [
            'user_id' => $user->id,
            'tool'    => 'getcontact',
            'query'   => '08123456789',
            'status'  => 'success',
        ]);
    }
}
```

- [ ] **Step 6: Jalankan test**

```powershell
php artisan test tests/Feature/PhoneLookupControllerTest.php
```

Expected: 3 tests PASS.

- [ ] **Step 7: Commit**

```powershell
git add app/Services/GetContactService.php app/Http/Controllers/PhoneLookupController.php resources/views/tools/phone-lookup.blade.php resources/views/history/phone.blade.php tests/
git commit -m "feat: implement GetContact service, Phone Lookup tool, and history"
```

---

### Task 13: LeakOSINT Service + Tool

**Files:**
- Create: `app/Services/LeakOsintService.php`
- Create: `app/Http/Controllers/LeakOsintController.php`
- Create: `resources/views/tools/leakosint.blade.php`
- Create: `resources/views/history/leakosint.blade.php`

- [ ] **Step 1: Tulis LeakOsintService**

```php
<?php
// app/Services/LeakOsintService.php
namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class LeakOsintService
{
    public function search(string $query, int $limit = 100, string $lang = 'en'): array
    {
        $token  = Setting::getValue('leakosint_api_token');
        $apiUrl = Setting::getValue('leakosint_api_url', 'https://leakosintapi.com/');

        if (empty($token)) {
            throw new \RuntimeException('LeakOSINT API token belum dikonfigurasi di Settings.');
        }

        $response = Http::timeout(30)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($apiUrl, [
                'token'   => $token,
                'request' => $query,
                'limit'   => $limit,
                'lang'    => $lang,
            ]);

        if (!$response->ok()) {
            throw new \RuntimeException("LeakOSINT API error: HTTP {$response->status()}");
        }

        return $response->json();
    }
}
```

- [ ] **Step 2: Tulis LeakOsintController**

```php
<?php
// app/Http/Controllers/LeakOsintController.php
namespace App\Http\Controllers;

use App\Models\SearchLog;
use App\Services\LeakOsintService;
use Illuminate\Http\Request;

class LeakOsintController extends Controller
{
    public function __construct(private LeakOsintService $service) {}

    public function index()
    {
        return view('tools.leakosint');
    }

    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:5000',
            'limit' => 'integer|in:10,50,100,250,500,1000,5000,10000',
            'lang'  => 'string|in:en,ru,de,fr,es,it,pt,zh,ar',
        ]);

        try {
            $data   = $this->service->search(
                $request->input('query'),
                (int) $request->input('limit', 100),
                $request->input('lang', 'en')
            );
            $status = 'success';
            $error  = null;
        } catch (\Exception $e) {
            $data   = [];
            $status = 'failed';
            $error  = $e->getMessage();
        }

        SearchLog::create([
            'user_id'       => auth()->id(),
            'tool'          => 'leakosint',
            'query'         => $request->input('query'),
            'result_json'   => $data ?: null,
            'status'        => $status,
            'error_message' => $error,
            'ip_address'    => $request->ip(),
        ]);

        return view('tools.leakosint', compact('data', 'error'));
    }

    public function history()
    {
        $user  = auth()->user();
        $query = SearchLog::where('tool', 'leakosint')->with('user');
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        $logs = $query->latest()->paginate(30);
        return view('history.leakosint', compact('logs'));
    }
}
```

- [ ] **Step 3: Invoke frontend-design skill untuk leakosint views**

```
Build 2 Blade views:

1. resources/views/tools/leakosint.blade.php (extends layouts.app):
   - Page header: icon 🔍, "LeakOSINT Search", subtitle "Cari data breach dari berbagai database"
   - Input card:
     - Textarea "Query" (email, nomor, username), rows=4
     - Row: select "Limit" (10,50,100,250,500,1000,5000,10000 — default 100), 
             select "Bahasa" (en,ru,de,fr,es,it,pt,zh,ar — default en)
     - Tombol "Cari" biru solid
   - Jika $error: error card merah
   - Jika $data tidak null:
     - Summary: NumOfResults, NumOfDatabase, search time dari $data
     - Per database dalam $data['List']: card dengan nama DB, jumlah record
       Tabel data dengan kolom dinamis dari keys record pertama
     - Tombol Export Excel (SheetJS, client-side) dan Export PDF (jsPDF, client-side)
     - Tombol "Lihat JSON Raw" toggle textarea

2. resources/views/history/leakosint.blade.php (extends layouts.app):
   - Tabel: Query, Status badge, User (jika admin), Hasil (num results dari result_json), Waktu
   - Pagination
```

- [ ] **Step 4: Tulis feature test**

```php
<?php
// tests/Feature/LeakOsintControllerTest.php
namespace Tests\Feature;

use App\Models\User;
use App\Services\LeakOsintService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeakOsintControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_leakosint_page_loads(): void
    {
        $user = User::factory()->create(['role' => 'operator']);
        $this->actingAs($user)->get('/leakosint')->assertOk();
    }

    public function test_search_creates_log_on_success(): void
    {
        $user     = User::factory()->create(['role' => 'operator']);
        $mockData = ['NumOfResults' => 5, 'NumOfDatabase' => 2, 'List' => []];

        $this->mock(LeakOsintService::class, fn($m) =>
            $m->shouldReceive('search')->once()->andReturn($mockData)
        );

        $this->actingAs($user)->post('/leakosint', ['query' => 'test@example.com', 'limit' => 100, 'lang' => 'en']);

        $this->assertDatabaseHas('search_logs', [
            'user_id' => $user->id,
            'tool'    => 'leakosint',
            'status'  => 'success',
        ]);
    }

    public function test_search_creates_failed_log_on_exception(): void
    {
        $user = User::factory()->create(['role' => 'operator']);

        $this->mock(LeakOsintService::class, fn($m) =>
            $m->shouldReceive('search')->once()->andThrow(new \RuntimeException('API error'))
        );

        $this->actingAs($user)->post('/leakosint', ['query' => 'test@example.com', 'limit' => 100, 'lang' => 'en']);

        $this->assertDatabaseHas('search_logs', [
            'tool'   => 'leakosint',
            'status' => 'failed',
        ]);
    }
}
```

- [ ] **Step 5: Jalankan test**

```powershell
php artisan test tests/Feature/LeakOsintControllerTest.php
```

Expected: 3 tests PASS.

- [ ] **Step 6: Commit**

```powershell
git add app/Services/LeakOsintService.php app/Http/Controllers/LeakOsintController.php resources/views/tools/leakosint.blade.php resources/views/history/ tests/
git commit -m "feat: implement LeakOSINT service, search tool, and history"
```

---

## PHASE 3 — OSINT Tools

### Task 14: Multicheck Service + Tool

**Files:**
- Create: `app/Services/MulticheckService.php`
- Create: `app/Http/Controllers/MulticheckController.php`
- Create: `resources/views/tools/multicheck.blade.php`

- [ ] **Step 1: Tulis MulticheckService**

```php
<?php
// app/Services/MulticheckService.php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class MulticheckService
{
    private array $platforms = [
        ['name' => 'GitHub',     'cat' => 'dev',       'url' => 'https://github.com/{}'],
        ['name' => 'Reddit',     'cat' => 'social',    'url' => 'https://www.reddit.com/user/{}/'],
        ['name' => 'Twitter/X',  'cat' => 'social',    'url' => 'https://twitter.com/{}'],
        ['name' => 'YouTube',    'cat' => 'social',    'url' => 'https://www.youtube.com/@{}'],
        ['name' => 'Twitch',     'cat' => 'gaming',    'url' => 'https://www.twitch.tv/{}'],
        ['name' => 'Pinterest',  'cat' => 'social',    'url' => 'https://www.pinterest.com/{}/'],
        ['name' => 'Snapchat',   'cat' => 'social',    'url' => 'https://www.snapchat.com/add/{}'],
        ['name' => 'Medium',     'cat' => 'blog',      'url' => 'https://medium.com/@{}'],
        ['name' => 'Telegram',   'cat' => 'messenger', 'url' => 'https://t.me/{}',          'absent_text' => 'tgme_page_description'],
        ['name' => 'Steam',      'cat' => 'gaming',    'url' => 'https://steamcommunity.com/id/{}', 'absent_text' => 'error_ctn'],
        ['name' => 'Keybase',    'cat' => 'security',  'url' => 'https://keybase.io/{}'],
        ['name' => 'Dev.to',     'cat' => 'dev',       'url' => 'https://dev.to/{}'],
        ['name' => 'Linktree',   'cat' => 'social',    'url' => 'https://linktr.ee/{}'],
        ['name' => 'HackerNews', 'cat' => 'dev',       'url' => 'https://news.ycombinator.com/user?id={}', 'absent_text' => 'No such user'],
        ['name' => 'GitLab',     'cat' => 'dev',       'url' => 'https://gitlab.com/{}'],
    ];

    public function check(string $username): array
    {
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

        $responses = Http::pool(function ($pool) use ($username, $ua) {
            return collect($this->platforms)->map(function ($p) use ($pool, $username, $ua) {
                $url = str_replace('{}', $username, $p['url']);
                return $pool->as($p['name'])->timeout(7)->withHeaders(['User-Agent' => $ua])->get($url);
            })->all();
        });

        $results = [];
        foreach ($this->platforms as $p) {
            $name  = $p['name'];
            $url   = str_replace('{}', $username, $p['url']);
            $resp  = $responses[$name] ?? null;

            if (!$resp || $resp instanceof \Throwable) {
                $results[] = ['name' => $name, 'cat' => $p['cat'], 'status' => 'error', 'url' => $url];
                continue;
            }

            $code = $resp->status();
            if ($code === 200) {
                if (isset($p['absent_text']) && str_contains($resp->body(), $p['absent_text'])) {
                    $results[] = ['name' => $name, 'cat' => $p['cat'], 'status' => 'not_found', 'url' => $url];
                } else {
                    $results[] = ['name' => $name, 'cat' => $p['cat'], 'status' => 'found', 'url' => $url];
                }
            } elseif ($code === 404) {
                $results[] = ['name' => $name, 'cat' => $p['cat'], 'status' => 'not_found', 'url' => $url];
            } else {
                $results[] = ['name' => $name, 'cat' => $p['cat'], 'status' => 'unknown', 'url' => $url, 'http' => $code];
            }
        }

        $found    = array_values(array_filter($results, fn($r) => $r['status'] === 'found'));
        $notFound = array_values(array_filter($results, fn($r) => $r['status'] === 'not_found'));
        $unknown  = array_values(array_filter($results, fn($r) => !in_array($r['status'], ['found', 'not_found'])));
        usort($found,    fn($a, $b) => strcmp($a['name'], $b['name']));
        usort($notFound, fn($a, $b) => strcmp($a['name'], $b['name']));

        return [
            'username'    => $username,
            'found'       => $found,
            'not_found'   => $notFound,
            'unknown'     => $unknown,
            'total'       => count($this->platforms),
            'found_count' => count($found),
        ];
    }
}
```

- [ ] **Step 2: Tulis MulticheckController**

```php
<?php
// app/Http/Controllers/MulticheckController.php
namespace App\Http\Controllers;

use App\Services\MulticheckService;
use Illuminate\Http\Request;

class MulticheckController extends Controller
{
    public function __construct(private MulticheckService $service) {}

    public function index()
    {
        return view('tools.multicheck');
    }

    public function check(Request $request)
    {
        $request->validate(['username' => 'required|string|max:50|regex:/^[\w.\-]+$/']);

        try {
            $result = $this->service->check($request->input('username'));
            $error  = null;
        } catch (\Exception $e) {
            $result = null;
            $error  = $e->getMessage();
        }

        return view('tools.multicheck', compact('result', 'error'));
    }
}
```

- [ ] **Step 3: Invoke frontend-design skill untuk multicheck view**

```
Build resources/views/tools/multicheck.blade.php (extends layouts.app).

Username Multicheck Tool:
- Page header: icon 👤, "Username Check", subtitle "Cek keberadaan username di 15 platform"
- Input card: label "Username", input text (tanpa @), tombol "Cek Sekarang"
- Jika $error: error card merah
- Jika $result tidak null:
  - Summary bar: "Ditemukan X dari 15 platform" dengan progress bar biru
  - Grid 3 kolom kartu platform:
    - Found: card hijau dengan border hijau, ikon centang, nama platform, kategori badge, 
      link "Lihat Profil →" ke URL
    - Not Found: card abu-abu muted, ikon X, nama platform
    - Unknown/Error: card kuning, ikon tanya, nama + status
  - Filter tabs: Semua | Ditemukan | Tidak Ditemukan
```

- [ ] **Step 4: Commit**

```powershell
git add app/Services/MulticheckService.php app/Http/Controllers/MulticheckController.php resources/views/tools/multicheck.blade.php
git commit -m "feat: implement Username Multicheck service and tool"
```

---

### Task 15: Email OSINT Service + Tool

**Files:**
- Create: `app/Services/EmailOsintService.php`
- Create: `app/Http/Controllers/EmailOsintController.php`
- Create: `resources/views/tools/email-osint.blade.php`

- [ ] **Step 1: Tulis EmailOsintService**

```php
<?php
// app/Services/EmailOsintService.php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class EmailOsintService
{
    public function analyze(string $email): array
    {
        $email  = strtolower(trim($email));
        $domain = explode('@', $email)[1] ?? '';
        $result = [
            'email'    => $email,
            'domain'   => $domain,
            'is_gmail' => in_array($domain, ['gmail.com', 'googlemail.com']),
            'sources'  => [],
        ];

        // Disify
        try {
            $dis = Http::timeout(10)->get("https://www.disify.com/api/email/{$email}");
            if ($dis->ok()) {
                $d = $dis->json();
                $result += [
                    'format_valid'     => $d['format']      ?? false,
                    'disposable'       => $d['disposable']  ?? false,
                    'has_dns'          => $d['dns']         ?? false,
                    'whitelisted'      => $d['whitelist']   ?? false,
                    'is_role_email'    => $d['role']        ?? false,
                    'is_free_provider' => $d['free']        ?? false,
                    'mx_records'       => $d['mx_info']     ?? [],
                ];
                $result['sources'][] = 'disify';
            }
        } catch (\Exception) {}

        // Gravatar
        $md5 = md5($email);
        try {
            $gr = Http::timeout(8)->get("https://www.gravatar.com/avatar/{$md5}?d=404");
            $result['gravatar'] = ['exists' => $gr->ok(), 'url' => "https://www.gravatar.com/avatar/{$md5}?s=200"];
            $result['sources'][] = 'gravatar';
        } catch (\Exception) {
            $result['gravatar'] = ['exists' => false, 'url' => null];
        }

        $result['links'] = [
            'google_search'    => 'https://www.google.com/search?q=' . urlencode("\"$email\""),
            'linkedin_search'  => 'https://www.linkedin.com/search/results/people/?keywords=' . urlencode($email),
            'twitter_search'   => 'https://twitter.com/search?q=' . urlencode($email),
            'gravatar_profile' => "https://en.gravatar.com/{$md5}",
            'dehashed'         => 'https://www.dehashed.com/search?query=' . urlencode($email),
        ];

        return $result;
    }
}
```

- [ ] **Step 2: Tulis EmailOsintController**

```php
<?php
// app/Http/Controllers/EmailOsintController.php
namespace App\Http\Controllers;

use App\Services\EmailOsintService;
use Illuminate\Http\Request;

class EmailOsintController extends Controller
{
    public function __construct(private EmailOsintService $service) {}

    public function index()
    {
        return view('tools.email-osint');
    }

    public function analyze(Request $request)
    {
        $request->validate(['email' => 'required|email|max:255']);

        try {
            $result = $this->service->analyze($request->input('email'));
            $error  = null;
        } catch (\Exception $e) {
            $result = null;
            $error  = $e->getMessage();
        }

        return view('tools.email-osint', compact('result', 'error'));
    }
}
```

- [ ] **Step 3: Invoke frontend-design skill untuk email-osint view**

```
Build resources/views/tools/email-osint.blade.php (extends layouts.app).

Email OSINT Tool:
- Page header: icon ✉️, "Email OSINT", subtitle "Validasi dan analisis email address"
- Input card: label "Email Address", input email, tombol "Analisis"
- Jika $result:
  - Info card: email, domain, is_gmail badge, sumber data
  - Properties grid (2 kolom):
    Format Valid, Disposable (merah jika true), Has DNS, Whitelisted, 
    Role Email, Free Provider — setiap item dengan ikon centang/silang berwarna
  - MX Records section: list of MX records jika ada
  - Gravatar section: jika exists tampilkan avatar image + "Profil ditemukan", jika tidak "Tidak ada Gravatar"
  - Investigation Links: tombol-tombol outlined untuk Google Search, LinkedIn, Twitter, Gravatar, Dehashed
```

- [ ] **Step 4: Commit**

```powershell
git add app/Services/EmailOsintService.php app/Http/Controllers/EmailOsintController.php resources/views/tools/email-osint.blade.php
git commit -m "feat: implement Email OSINT service and tool"
```

---

### Task 16: Phone Info Service + Tool

**Files:**
- Create: `app/Services/PhoneInfoService.php`
- Create: `app/Http/Controllers/PhoneInfoController.php`
- Create: `resources/views/tools/phone-info.blade.php`

- [ ] **Step 1: Tulis PhoneInfoService**

```php
<?php
// app/Services/PhoneInfoService.php
namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\geocoding\PhoneNumberOfflineGeocoder;
use libphonenumber\PhoneNumberToCarrierMapper;
use libphonenumber\PhoneNumberToTimeZonesMapper;

class PhoneInfoService
{
    private static array $typeMap = [
        PhoneNumberType::MOBILE               => 'MOBILE',
        PhoneNumberType::FIXED_LINE           => 'FIXED_LINE',
        PhoneNumberType::FIXED_LINE_OR_MOBILE => 'FIXED/MOBILE',
        PhoneNumberType::TOLL_FREE            => 'TOLL_FREE',
        PhoneNumberType::VOIP                 => 'VOIP',
        PhoneNumberType::PAGER                => 'PAGER',
        PhoneNumberType::PREMIUM_RATE         => 'PREMIUM_RATE',
    ];

    public function analyze(string $number): array
    {
        $util   = PhoneNumberUtil::getInstance();
        $result = ['raw' => $number, 'sources' => []];

        try {
            $parsed  = $util->parse($number, null);
            $numType = $util->getNumberType($parsed);
            $e164    = $util->format($parsed, PhoneNumberFormat::E164);

            $result += [
                'valid'         => $util->isValidNumber($parsed),
                'possible'      => $util->isPossibleNumber($parsed),
                'international' => $util->format($parsed, PhoneNumberFormat::INTERNATIONAL),
                'national'      => $util->format($parsed, PhoneNumberFormat::NATIONAL),
                'e164'          => $e164,
                'country_code'  => $parsed->getCountryCode(),
                'country'       => PhoneNumberOfflineGeocoder::getInstance()->getDescriptionForNumber($parsed, 'en'),
                'region'        => $util->getRegionCodeForNumber($parsed),
                'carrier'       => PhoneNumberToCarrierMapper::getInstance()->getNameForNumber($parsed, 'en'),
                'timezones'     => PhoneNumberToTimeZonesMapper::getInstance()->getTimeZonesForNumber($parsed),
                'line_type'     => self::$typeMap[$numType] ?? 'UNKNOWN',
            ];
            $result['sources'][] = 'phonenumbers';

            $token   = Setting::getValue('ipinfo_token');
            $headers = $token ? ['Authorization' => "Bearer {$token}"] : [];
            try {
                $r = Http::timeout(10)->withHeaders($headers)->get("https://ipinfo.io/{$e164}/json");
                if ($r->ok()) {
                    $result['ipinfo'] = $r->json();
                    $result['sources'][] = 'ipinfo.io';
                }
            } catch (\Exception) {}

        } catch (\Exception $e) {
            $result['parse_error'] = $e->getMessage();
        }

        return $result;
    }
}
```

- [ ] **Step 2: Tulis PhoneInfoController**

```php
<?php
// app/Http/Controllers/PhoneInfoController.php
namespace App\Http\Controllers;

use App\Services\PhoneInfoService;
use Illuminate\Http\Request;

class PhoneInfoController extends Controller
{
    public function __construct(private PhoneInfoService $service) {}

    public function index()
    {
        return view('tools.phone-info');
    }

    public function analyze(Request $request)
    {
        $request->validate(['phone' => 'required|string|max:20']);

        try {
            $result = $this->service->analyze($request->input('phone'));
            $error  = null;
        } catch (\Exception $e) {
            $result = null;
            $error  = $e->getMessage();
        }

        return view('tools.phone-info', compact('result', 'error'));
    }
}
```

- [ ] **Step 3: Invoke frontend-design skill untuk phone-info view**

```
Build resources/views/tools/phone-info.blade.php (extends layouts.app).

Phone OSINT Tool:
- Page header: icon 📡, "Phone OSINT", subtitle "Analisis detail nomor telepon"
- Input: label "Nomor Telepon", input text dengan hint "Format E.164 (+62...) atau lokal (08...)"
- Jika $result dan tidak ada parse_error:
  - Card detail: E164, International, National, format
  - Info grid: Country, Region, Carrier, Line Type, Timezones, Valid (badge)
  - IPInfo section jika $result['ipinfo'] ada: City, Country, ISP, Organization
- Jika parse_error: error card merah
```

- [ ] **Step 4: Commit**

```powershell
git add app/Services/PhoneInfoService.php app/Http/Controllers/PhoneInfoController.php resources/views/tools/phone-info.blade.php
git commit -m "feat: implement Phone Info service and tool with libphonenumber"
```

---

### Task 17: IP Geolocation Service + Tool

**Files:**
- Create: `app/Services/IpGeolocationService.php`
- Create: `app/Http/Controllers/IpGeoController.php`
- Create: `resources/views/tools/ip-geo.blade.php`

- [ ] **Step 1: Tulis IpGeolocationService**

```php
<?php
// app/Services/IpGeolocationService.php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class IpGeolocationService
{
    public function lookup(string $ip): array
    {
        $ip = filter_var(trim($ip), FILTER_VALIDATE_IP);
        if (!$ip) {
            throw new \InvalidArgumentException('Format IP address tidak valid.');
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new \InvalidArgumentException('IP private atau reserved tidak diizinkan.');
        }

        $fields = 'status,message,continent,continentCode,country,countryCode,region,regionName,city,district,zip,lat,lon,timezone,offset,currency,isp,org,as,asname,reverse,mobile,proxy,hosting,query';
        $r      = Http::timeout(10)->get("http://ip-api.com/json/{$ip}?fields={$fields}");

        if (!$r->ok()) {
            throw new \RuntimeException("ip-api.com error: HTTP {$r->status()}");
        }

        $d = $r->json();
        if (($d['status'] ?? '') === 'fail') {
            throw new \RuntimeException($d['message'] ?? 'IP tidak valid atau reserved');
        }

        return [
            'ip'          => $d['query'],
            'continent'   => $d['continent']   ?? null,
            'country'     => $d['country']     ?? null,
            'country_code'=> $d['countryCode'] ?? null,
            'region'      => $d['regionName']  ?? null,
            'city'        => $d['city']        ?? null,
            'district'    => $d['district']    ?: null,
            'zip'         => $d['zip']         ?: null,
            'lat'         => $d['lat']         ?? null,
            'lon'         => $d['lon']         ?? null,
            'timezone'    => $d['timezone']    ?? null,
            'currency'    => $d['currency']    ?? null,
            'isp'         => $d['isp']         ?? null,
            'org'         => $d['org']         ?? null,
            'asn'         => $d['as']          ?? null,
            'asname'      => $d['asname']      ?? null,
            'reverse_dns' => $d['reverse']     ?: null,
            'is_mobile'   => $d['mobile']      ?? false,
            'is_proxy'    => $d['proxy']       ?? false,
            'is_hosting'  => $d['hosting']     ?? false,
            'map_url'     => "https://www.google.com/maps?q={$d['lat']},{$d['lon']}",
            'source'      => 'ip-api.com',
        ];
    }
}
```

- [ ] **Step 2: Tulis IpGeoController**

```php
<?php
// app/Http/Controllers/IpGeoController.php
namespace App\Http\Controllers;

use App\Services\IpGeolocationService;
use Illuminate\Http\Request;

class IpGeoController extends Controller
{
    public function __construct(private IpGeolocationService $service) {}

    public function index()
    {
        return view('tools.ip-geo');
    }

    public function lookup(Request $request)
    {
        $request->validate(['ip' => 'required|string|max:45']);

        try {
            $result = $this->service->lookup($request->input('ip'));
            $error  = null;
        } catch (\Exception $e) {
            $result = null;
            $error  = $e->getMessage();
        }

        return view('tools.ip-geo', compact('result', 'error'));
    }
}
```

- [ ] **Step 3: Invoke frontend-design skill untuk ip-geo view**

```
Build resources/views/tools/ip-geo.blade.php (extends layouts.app).

IP Geolocation Tool:
- Page header: icon 🌍, "IP Geolocation", subtitle "Lacak lokasi dan informasi IP address"
- Input: label "IP Address", input text placeholder "8.8.8.8 atau 2001:4860::1"
- Jika $result:
  - Google Maps embed via iframe src="https://maps.google.com/maps?q={lat},{lon}&output=embed&zoom=10"
    (tinggi 250px, rounded-xl)
  - Info grid 2 kolom:
    IP, Kontinen, Negara, Region, Kota, ZIP, Timezone, Currency
    ISP, Organisasi, ASN, Reverse DNS
  - Flags row: Mobile (badge biru jika true), Proxy (badge merah jika true), Hosting (badge kuning jika true)
  - Tombol "Buka di Google Maps" link ke map_url
```

- [ ] **Step 4: Tulis unit test untuk IpGeolocationService**

```php
<?php
// tests/Unit/IpGeolocationServiceTest.php
namespace Tests\Unit;

use App\Services\IpGeolocationService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IpGeolocationServiceTest extends TestCase
{
    private IpGeolocationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new IpGeolocationService();
    }

    public function test_rejects_private_ip(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->lookup('192.168.1.1');
    }

    public function test_rejects_invalid_ip(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->lookup('not-an-ip');
    }

    public function test_successful_lookup(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success', 'query' => '8.8.8.8',
                'country' => 'United States', 'countryCode' => 'US',
                'city' => 'Mountain View', 'lat' => 37.4, 'lon' => -122.1,
                'isp' => 'Google LLC', 'proxy' => false, 'mobile' => false, 'hosting' => true,
            ], 200),
        ]);

        $result = $this->service->lookup('8.8.8.8');
        $this->assertEquals('8.8.8.8', $result['ip']);
        $this->assertEquals('United States', $result['country']);
        $this->assertTrue($result['is_hosting']);
    }
}
```

- [ ] **Step 5: Jalankan test**

```powershell
php artisan test tests/Unit/IpGeolocationServiceTest.php
```

Expected: 3 tests PASS.

- [ ] **Step 6: Commit**

```powershell
git add app/Services/IpGeolocationService.php app/Http/Controllers/IpGeoController.php resources/views/tools/ip-geo.blade.php tests/
git commit -m "feat: implement IP Geolocation service and tool with SSRF protection"
```

---

### Task 18: WHOIS Service + Tool

**Files:**
- Create: `app/Services/WhoisService.php`
- Create: `app/Http/Controllers/WhoisController.php`
- Create: `resources/views/tools/whois.blade.php`

- [ ] **Step 1: Tulis WhoisService**

```php
<?php
// app/Services/WhoisService.php
namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Iodev\Whois\Factory as WhoisFactory;

class WhoisService
{
    public function lookup(string $input): array
    {
        $domain = preg_replace('#^https?://#', '', trim($input));
        $domain = strtolower(explode('/', $domain)[0]);
        $domain = explode('?', $domain)[0];

        if (!$domain || !str_contains($domain, '.')) {
            throw new \InvalidArgumentException('Nama domain tidak valid.');
        }

        $result = ['domain' => $domain, 'sources' => []];
        $whois  = WhoisFactory::get()->createWhois();

        // WHOIS
        try {
            $info = $whois->loadDomainInfo($domain);
            if ($info) {
                $result += [
                    'registrar'       => $info->registrar,
                    'creation_date'   => $info->creationDate   ? date('Y-m-d H:i:s', $info->creationDate)   : null,
                    'expiration_date' => $info->expirationDate ? date('Y-m-d H:i:s', $info->expirationDate) : null,
                    'updated_date'    => $info->updatedDate    ? date('Y-m-d H:i:s', $info->updatedDate)    : null,
                    'name_servers'    => array_map('strtoupper', $info->nameServers ?? []),
                    'states'          => $info->states         ?? [],
                    'owner'           => $info->owner,
                ];
                $result['sources'][] = 'whois';
            }
        } catch (\Exception $e) {
            $result['whois_error'] = $e->getMessage();
        }

        // DNS + IP
        try {
            $ip = gethostbyname($domain);
            if ($ip !== $domain) {
                $isPublic = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
                $result['ip_address'] = $ip;
                $result['resolves']   = true;
                $result['sources'][]  = 'dns';

                if (!$isPublic) {
                    $result['ssrf_blocked'] = true;
                } else {
                    $token   = Setting::getValue('ipinfo_token');
                    $headers = $token ? ['Authorization' => "Bearer {$token}"] : [];
                    try {
                        $r = Http::timeout(8)->withHeaders($headers)->get("https://ipinfo.io/{$ip}/json");
                        if ($r->ok()) {
                            $d = $r->json();
                            $result['ip_info'] = [
                                'ip'       => $d['ip']       ?? null,
                                'city'     => $d['city']     ?? null,
                                'region'   => $d['region']   ?? null,
                                'country'  => $d['country']  ?? null,
                                'org'      => $d['org']      ?? null,
                                'timezone' => $d['timezone'] ?? null,
                                'loc'      => $d['loc']      ?? null,
                            ];
                            $result['sources'][] = 'ipinfo.io';
                        }
                    } catch (\Exception) {}
                }
            } else {
                $result['resolves'] = false;
            }
        } catch (\Exception $e) {
            $result['resolves']  = false;
            $result['dns_error'] = $e->getMessage();
        }

        // SSL
        try {
            $ctx    = stream_context_create(['ssl' => ['capture_peer_cert' => true, 'verify_peer' => false, 'verify_peer_name' => false]]);
            $socket = @stream_socket_client("ssl://{$domain}:443", $errno, $errstr, 8, STREAM_CLIENT_CONNECT, $ctx);
            if ($socket) {
                $params = stream_context_get_params($socket);
                $cert   = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
                if ($cert) {
                    $sans = [];
                    if (isset($cert['extensions']['subjectAltName'])) {
                        preg_match_all('/DNS:([^,\s]+)/', $cert['extensions']['subjectAltName'], $m);
                        $sans = $m[1] ?? [];
                    }
                    $result['ssl'] = [
                        'issuer'    => $cert['issuer']            ?? [],
                        'subject'   => $cert['subject']           ?? [],
                        'not_after' => isset($cert['validTo_time_t']) ? date('Y-m-d H:i:s', $cert['validTo_time_t']) : null,
                        'sans'      => $sans,
                    ];
                }
                fclose($socket);
            }
        } catch (\Exception) {
            $result['ssl'] = null;
        }

        return $result;
    }
}
```

- [ ] **Step 2: Tulis WhoisController**

```php
<?php
// app/Http/Controllers/WhoisController.php
namespace App\Http\Controllers;

use App\Services\WhoisService;
use Illuminate\Http\Request;

class WhoisController extends Controller
{
    public function __construct(private WhoisService $service) {}

    public function index()
    {
        return view('tools.whois');
    }

    public function lookup(Request $request)
    {
        $request->validate(['domain' => 'required|string|max:253']);

        try {
            $result = $this->service->lookup($request->input('domain'));
            $error  = null;
        } catch (\Exception $e) {
            $result = null;
            $error  = $e->getMessage();
        }

        return view('tools.whois', compact('result', 'error'));
    }
}
```

- [ ] **Step 3: Invoke frontend-design skill untuk whois view**

```
Build resources/views/tools/whois.blade.php (extends layouts.app).

WHOIS Domain Tool:
- Page header: icon 🌐, "WHOIS Domain", subtitle "Cek informasi registrasi domain"
- Input: label "Domain", input text placeholder "example.com atau https://example.com"
- Jika $result:
  - Accordion/collapsible sections (default open):
    A. "Registrasi" — Registrar, Creation Date, Expiration Date, Updated Date, 
       Status badges, Owner, Name Servers list
    B. "DNS & IP" — IP Address, Resolves (badge), IP Info (city, country, ISP, org) jika ada,
       SSRF blocked warning jika ada
    C. "SSL Certificate" — Issuer (CN), Subject (CN), Valid Until (badge merah jika expired),
       SANs list — tampilkan "Tidak ada SSL" jika ssl = null
  - Sources badges di bawah: "whois", "dns", "ipinfo.io"
```

- [ ] **Step 4: Commit**

```powershell
git add app/Services/WhoisService.php app/Http/Controllers/WhoisController.php resources/views/tools/whois.blade.php
git commit -m "feat: implement WHOIS service and tool with DNS and SSL cert check"
```

---

### Task 19: Toutatis Service + Tool

**Files:**
- Create: `app/Services/ToutatisService.php`
- Create: `app/Http/Controllers/ToutatisController.php`
- Create: `resources/views/tools/toutatis.blade.php`

- [ ] **Step 1: Tulis ToutatisService**

```php
<?php
// app/Services/ToutatisService.php
namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ToutatisService
{
    private const CACHE_TTL  = 300;
    private const MAX_RETRY  = 3;
    private const BASE_DELAY = 4;

    private string $sessionId;

    public function __construct()
    {
        $this->sessionId = urldecode(Setting::getValue('instagram_session_id'));
    }

    public function isConfigured(): bool
    {
        return !empty($this->sessionId);
    }

    public function lookup(string $username): array
    {
        $username = strtolower(ltrim($username, '@'));

        if (!$this->isConfigured()) {
            throw new \RuntimeException('Instagram Session ID belum dikonfigurasi di Settings.');
        }

        $cacheKey = "toutatis:{$username}";
        if ($cached = Cache::get($cacheKey)) {
            return array_merge($cached, ['_cached' => true]);
        }

        $userId = $this->resolveUserId($username);
        $u      = $this->getMobileInfo($userId);
        $lookup = $this->advancedLookup($username);

        $phoneStr = null;
        if (!empty($u['public_phone_number'])) {
            $phoneStr = "+{$u['public_phone_country_code']} {$u['public_phone_number']}";
        }

        $result = [
            'username'           => $u['username']          ?? $username,
            'user_id'            => $userId,
            'full_name'          => $u['full_name']         ?? null,
            'biography'          => $u['biography']         ?? null,
            'account_type'       => $u['account_type']      ?? null,
            'is_private'         => $u['is_private']        ?? false,
            'is_verified'        => $u['is_verified']       ?? false,
            'is_business'        => $u['is_business']       ?? false,
            'is_whatsapp_linked' => $u['is_whatsapp_linked']?? false,
            'is_memorialized'    => $u['is_memorialized']   ?? false,
            'follower_count'     => $u['follower_count']    ?? 0,
            'following_count'    => $u['following_count']   ?? 0,
            'media_count'        => $u['media_count']       ?? 0,
            'external_url'       => $u['external_url']      ?? null,
            'public_email'       => $u['public_email']      ?? null,
            'public_phone'       => $phoneStr,
            'profile_pic_url'    => ($u['hd_profile_pic_url_info'] ?? [])['url'] ?? ($u['profile_pic_url'] ?? null),
            'instagram_url'      => "https://www.instagram.com/{$username}/",
            'lookup'             => $lookup,
            '_cached'            => false,
        ];

        if (!isset($lookup['lookup_status'])) {
            Cache::put($cacheKey, $result, self::CACHE_TTL);
        }

        return $result;
    }

    private function resolveUserId(string $username): string
    {
        $parts = explode(':', $this->sessionId);
        $dsId  = $parts[0] ?? '';

        $response = Http::timeout(15)
            ->withHeaders(['User-Agent' => 'Instagram 64.0.0.14.96'])
            ->withCookies(['sessionid' => $this->sessionId, 'ds_user_id' => $dsId], 'instagram.com')
            ->get("https://i.instagram.com/api/v1/users/web_profile_info/?username={$username}");

        if ($response->status() === 404) {
            throw new \RuntimeException("Profile '{$username}' tidak ditemukan.");
        }
        if (!$response->ok()) {
            throw new \RuntimeException("Instagram API error: HTTP {$response->status()}");
        }

        $userId = $response->json('data.user.id');
        if (!$userId) {
            throw new \RuntimeException("Profile '{$username}' tidak ditemukan.");
        }

        return (string) $userId;
    }

    private function getMobileInfo(string $userId): array
    {
        $response = Http::timeout(15)
            ->withHeaders(['User-Agent' => 'Instagram 64.0.0.14.96'])
            ->withCookies(['sessionid' => $this->sessionId], 'instagram.com')
            ->get("https://i.instagram.com/api/v1/users/{$userId}/info/");

        if (!$response->ok()) {
            throw new \RuntimeException("Instagram mobile API error: HTTP {$response->status()}");
        }

        return $response->json('user', []);
    }

    private function advancedLookup(string $username): array
    {
        $body = 'signed_body=SIGNATURE.' . urlencode(
            json_encode(['q' => $username, 'skip_recovery' => '1'], JSON_UNESCAPED_SLASHES)
        );

        for ($attempt = 0; $attempt < self::MAX_RETRY; $attempt++) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders([
                        'Accept-Language' => 'en-US',
                        'User-Agent'      => 'Instagram 314.0.0.35.109 Android (30/11; 420dpi; 1080x2148; samsung; SM-G975U; beyond2q; qcom; en_US; 548756459)',
                        'Content-Type'    => 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-IG-App-ID'     => '124024574287414',
                    ])
                    ->withCookies(['sessionid' => $this->sessionId], 'instagram.com')
                    ->withBody($body, 'application/x-www-form-urlencoded')
                    ->post('https://i.instagram.com/api/v1/users/lookup/');

                if ($response->status() === 429) {
                    if ($attempt < self::MAX_RETRY - 1) {
                        sleep(self::BASE_DELAY * (2 ** $attempt));
                    } else {
                        return ['lookup_status' => 'rate limited — coba beberapa menit lagi'];
                    }
                    continue;
                }

                if ($response->ok()) {
                    $ld = $response->json();
                    return [
                        'obfuscated_email' => $ld['obfuscated_email'] ?? null,
                        'obfuscated_phone' => $ld['obfuscated_phone'] ?? null,
                    ];
                }

                return ['lookup_status' => "gagal (HTTP {$response->status()})"];

            } catch (\Exception $e) {
                return ['lookup_status' => "error: {$e->getMessage()}"];
            }
        }

        return ['lookup_status' => 'max retries exceeded'];
    }
}
```

- [ ] **Step 2: Tulis ToutatisController**

```php
<?php
// app/Http/Controllers/ToutatisController.php
namespace App\Http\Controllers;

use App\Services\ToutatisService;
use Illuminate\Http\Request;

class ToutatisController extends Controller
{
    public function __construct(private ToutatisService $service) {}

    public function index()
    {
        return view('tools.toutatis');
    }

    public function lookup(Request $request)
    {
        $request->validate(['username' => 'required|string|max:30|regex:/^[\w.]+$/']);

        try {
            $result = $this->service->lookup($request->input('username'));
            $error  = null;
        } catch (\Exception $e) {
            $result = null;
            $error  = $e->getMessage();
        }

        return view('tools.toutatis', compact('result', 'error'));
    }
}
```

- [ ] **Step 3: Invoke frontend-design skill untuk toutatis view**

```
Build resources/views/tools/toutatis.blade.php (extends layouts.app).

Toutatis — Instagram Deep OSINT Tool:
- Page header: icon 📸, "Instagram Deep OSINT (Toutatis)", subtitle "Analisis mendalam profil Instagram"
- Peringatan kuning: "Membutuhkan Instagram Session ID yang valid di Settings"
- Input: label "Username Instagram", input text placeholder "username (tanpa @)"
- Jika $result:
  - Profile card besar: foto profil (img src=$result['profile_pic_url']), 
    nama lengkap (besar), @username, bio, link instagram
  - Stats row: Followers, Following, Posts (format number)
  - Badges: Private/Public, Verified (centang biru), Business, WhatsApp Linked
  - Contact card (jika ada): Public Email, Public Phone
  - Obfuscated Data card: Obfuscated Email, Obfuscated Phone dari $result['lookup']
  - Jika $result['lookup']['lookup_status'] ada: info warning dengan pesan status
  - Cache indicator: jika $result['_cached'] == true tampilkan badge "Cached"
- Jika $error: error card merah
```

- [ ] **Step 4: Final test run — semua test**

```powershell
php artisan test
```

Expected: Semua test PASS (atau hanya fail pada test yang butuh network — ini normal).

- [ ] **Step 5: Build assets final**

```powershell
npm run build
```

- [ ] **Step 6: Commit final**

```powershell
git add app/Services/ToutatisService.php app/Http/Controllers/ToutatisController.php resources/views/tools/toutatis.blade.php
git commit -m "feat: implement Toutatis Instagram deep OSINT service and tool"
```

- [ ] **Step 7: Final commit Project ODIN**

```powershell
git add .
git commit -m "feat: Project ODIN complete — 8 OSINT tools unified in Laravel 11"
```

---

## Self-Review Checklist

- [x] **Auth system** — AuthController + brute-force + RBAC ✓
- [x] **4 tabel database** — users, search_logs, login_attempts, settings ✓
- [x] **Setting model** — encrypt/decrypt, allDecrypted() ✓
- [x] **GetContact** — ported langsung dari source, hapus dependency Credential model ✓
- [x] **LeakOSINT** — ported dari Osint SearchController ✓
- [x] **6 OsintPlus tools** — Multicheck, Email, PhoneInfo, IPGeo, WHOIS, Toutatis ✓
- [x] **History** — hanya getcontact dan leakosint ✓
- [x] **Admin panel** — users CRUD + audit logs ✓
- [x] **Settings page** — semua 7 credential keys terpusat ✓
- [x] **frontend-design skill** — di-invoke untuk semua views ✓
- [x] **SSRF protection** — di IpGeolocationService dan WhoisService ✓
- [x] **Rate limiting** — throttle:30,1 pada semua POST tool routes ✓
- [x] **Tests** — unit dan feature tests untuk komponen kritis ✓
- [x] **Docker** — tidak termasuk (sesuai spec: "dapat menyusul") ✓
