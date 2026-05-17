<?php

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
