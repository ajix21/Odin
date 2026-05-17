<?php

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

        $request->session()->regenerate();
        Auth::login($user, $request->boolean('remember'));
        $this->logAttempt($username, $ip, $request->userAgent(), true);
        $user->update(['last_login_at' => now(), 'last_login_ip' => $ip]);

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
            'user_agent' => $ua ? substr($ua, 0, 500) : null,
            'success'    => $success,
            'created_at' => now(),
        ]);
    }
}
