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
            'name'                => 'required|string|max:255',
            'username'            => 'required|string|max:100|unique:users',
            'email'               => 'required|email|unique:users',
            'password'            => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'role'                => 'required|in:admin,operator,viewer',
            'api_token'           => 'nullable|string|max:200',
            'daily_search_limit'  => 'nullable|integer|min:1|max:9999',
        ]);

        User::create([
            'name'               => $request->name,
            'username'           => $request->username,
            'email'              => $request->email,
            'password'           => Hash::make($request->password),
            'role'               => $request->role,
            'api_token'          => $request->api_token ?: null,
            'daily_search_limit' => $request->daily_search_limit ?: null,
            'is_active'          => true,
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
            'name'               => 'required|string|max:255',
            'username'           => 'required|string|max:100|unique:users,username,' . $user->id,
            'email'              => 'required|email|unique:users,email,' . $user->id,
            // required_with ensures password must be filled if confirmation is filled
            'password'           => ['nullable', 'required_with:password_confirmation', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'role'               => 'required|in:admin,operator,viewer',
            'is_active'          => 'boolean',
            'api_token'          => 'nullable|string|max:200',
            'daily_search_limit' => 'nullable|integer|min:1|max:9999',
        ]);

        $data = $request->only(['name', 'username', 'email', 'role', 'api_token']);
        $data['is_active']          = $request->boolean('is_active');
        $data['daily_search_limit'] = $request->daily_search_limit ?: null;

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

    public function logs(Request $request)
    {
        $searchQuery = SearchLog::with('user');

        if ($request->filled('tool'))      { $searchQuery->where('tool', $request->tool); }
        if ($request->filled('status'))    { $searchQuery->where('status', $request->status); }
        if ($request->filled('user_id'))   { $searchQuery->where('user_id', $request->user_id); }
        if ($request->filled('q'))         { $searchQuery->where('query', 'like', '%' . $request->q . '%'); }
        if ($request->filled('date_from')) { $searchQuery->whereDate('created_at', '>=', $request->date_from); }
        if ($request->filled('date_to'))   { $searchQuery->whereDate('created_at', '<=', $request->date_to); }

        $loginQuery = LoginAttempt::query();
        if ($request->filled('login_username')) {
            $loginQuery->where('username', 'like', '%' . $request->login_username . '%');
        }
        if ($request->filled('login_status')) {
            $loginQuery->where('success', $request->login_status === 'success');
        }

        $searchLogs = $searchQuery->latest()->paginate(30, ['*'], 'search_page')->withQueryString();
        $loginLogs  = $loginQuery->latest()->paginate(50, ['*'], 'login_page')->withQueryString();
        $users      = User::orderBy('username')->get(['id', 'username']);

        return view('admin.logs', compact('searchLogs', 'loginLogs', 'users'));
    }

    public function exportLogs(Request $request)
    {
        $query = SearchLog::with('user');
        if ($request->filled('tool'))      { $query->where('tool', $request->tool); }
        if ($request->filled('status'))    { $query->where('status', $request->status); }
        if ($request->filled('user_id'))   { $query->where('user_id', $request->user_id); }
        if ($request->filled('q'))         { $query->where('query', 'like', '%' . $request->q . '%'); }
        if ($request->filled('date_from')) { $query->whereDate('created_at', '>=', $request->date_from); }
        if ($request->filled('date_to'))   { $query->whereDate('created_at', '<=', $request->date_to); }

        $logs     = $query->latest()->limit(10000)->get();
        $filename = 'audit_search_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Waktu', 'User', 'Tool', 'Query', 'Status', 'IP']);
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user?->username ?? '—',
                    $log->tool,
                    $log->query,
                    $log->status,
                    $log->ip_address,
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
