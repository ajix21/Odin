<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use App\Models\SearchLog;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $today = now()->startOfDay();

        $logsQuery = $user->isAdmin()
            ? SearchLog::query()
            : SearchLog::where('user_id', $user->id);

        $stats = [
            'searches_today' => (clone $logsQuery)->where('created_at', '>=', $today)->count(),
            'searches_total' => (clone $logsQuery)->count(),
            'active_users'   => $user->isAdmin() ? User::where('is_active', true)->count() : null,
            'top_tool'       => (clone $logsQuery)->selectRaw('tool, count(*) as cnt')
                                    ->groupBy('tool')->orderByDesc('cnt')->value('tool'),
        ];

        $recentLogs = (clone $logsQuery)->with('user')->latest()->limit(10)->get();

        $recentLogins = $user->isAdmin()
            ? LoginAttempt::latest('created_at')->limit(10)->get()
            : collect();

        return view('dashboard.index', compact('stats', 'recentLogs', 'recentLogins'));
    }
}
