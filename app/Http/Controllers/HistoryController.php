<?php

namespace App\Http\Controllers;

use App\Models\SearchLog;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    private const TOOL_LABELS = [
        'getcontact'  => 'GetContact',
        'leakosint'   => 'LeakOSINT',
        'toutatis'    => 'Instagram',
        'whois'       => 'WHOIS',
        'ip-geo'      => 'IP Geo',
        'email-osint' => 'Email OSINT',
        'multicheck'  => 'Username Check',
        'phone-info'  => 'Phone OSINT',
        'phone'       => 'Phone Lookup',
    ];

    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = $user->isAdmin()
            ? SearchLog::with('user')
            : SearchLog::where('user_id', $user->id);

        $this->applyFilters($query, $request);

        $logs  = $query->latest()->paginate(50)->withQueryString();
        $tools = array_keys(self::TOOL_LABELS);

        return view('history.index', compact('logs', 'tools'));
    }

    public function export(Request $request)
    {
        $user  = auth()->user();
        $query = $user->isAdmin()
            ? SearchLog::with('user')
            : SearchLog::where('user_id', $user->id);

        $this->applyFilters($query, $request);

        $logs     = $query->latest()->limit(5000)->get();
        $filename = 'riwayat_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($logs, $user) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel

            $cols = $user->isAdmin()
                ? ['Waktu', 'User', 'Tool', 'Query', 'Status', 'IP']
                : ['Waktu', 'Tool', 'Query', 'Status', 'IP'];
            fputcsv($handle, $cols);

            foreach ($logs as $log) {
                $toolLabel = self::TOOL_LABELS[$log->tool] ?? ucfirst($log->tool);
                $row = $user->isAdmin()
                    ? [$log->created_at->format('Y-m-d H:i:s'), $log->user?->username ?? '—', $toolLabel, $log->query, $log->status, $log->ip_address]
                    : [$log->created_at->format('Y-m-d H:i:s'), $toolLabel, $log->query, $log->status, $log->ip_address];
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('tool')) {
            $query->where('tool', $request->tool);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $query->where('query', 'like', '%' . $request->q . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
    }
}
