@extends('layouts.app')
@section('title', 'Audit Logs')
@section('page-title', 'Admin — Audit Logs')

@section('content')
<div class="page-header">
    <h1>📊 Audit Logs</h1>
    <p>Riwayat aktivitas pencarian dan login seluruh pengguna</p>
</div>

{{-- Search Logs --}}
<div class="card mb-6">
    <div class="card-header">
        <h3>🔍 Search Logs</h3>
        <span class="text-sm text-muted">Total: {{ $searchLogs->total() }}</span>
    </div>
    <div class="table-wrapper" style="border-radius:0;border:none;border-top:1px solid var(--c-border);">
        <table class="data-table">
            <thead>
                <tr><th>User</th><th>Tool</th><th>Query</th><th>Status</th><th>IP</th><th>Waktu</th></tr>
            </thead>
            <tbody>
            @forelse($searchLogs as $log)
            <tr>
                <td class="fw-6">{{ $log->user?->username ?? '—' }}</td>
                <td>
                    <span class="badge {{ $log->tool === 'getcontact' ? 'badge-blue' : 'badge-purple' }}">
                        {{ $log->tool === 'getcontact' ? 'GetContact' : 'LeakOSINT' }}
                    </span>
                </td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $log->query }}</td>
                <td>
                    <span class="badge {{ $log->status === 'success' ? 'badge-green' : 'badge-red' }}">
                        {{ $log->status }}
                    </span>
                </td>
                <td class="font-mono" style="font-size:11.5px;">{{ $log->ip_address }}</td>
                <td class="text-muted text-sm">{{ $log->created_at->format('d/m/y H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;color:var(--c-text-3);padding:24px;">Belum ada search log.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:12px 14px;">{{ $searchLogs->appends(['login_page' => request('login_page')])->links() }}</div>
</div>

{{-- Login Logs --}}
<div class="card">
    <div class="card-header">
        <h3>🔐 Login Logs</h3>
        <span class="text-sm text-muted">Total: {{ $loginLogs->total() }}</span>
    </div>
    <div class="table-wrapper" style="border-radius:0;border:none;border-top:1px solid var(--c-border);">
        <table class="data-table">
            <thead>
                <tr><th>Username</th><th>IP Address</th><th>User Agent</th><th>Status</th><th>Waktu</th></tr>
            </thead>
            <tbody>
            @forelse($loginLogs as $login)
            <tr>
                <td class="fw-6">{{ $login->username }}</td>
                <td class="font-mono" style="font-size:11.5px;">{{ $login->ip_address }}</td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--c-text-3);font-size:12px;">
                    {{ $login->user_agent }}
                </td>
                <td>
                    <span class="badge {{ $login->success ? 'badge-green' : 'badge-red' }}">
                        {{ $login->success ? 'Berhasil' : 'Gagal' }}
                    </span>
                </td>
                <td class="text-muted text-sm">{{ $login->created_at->format('d/m/y H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;color:var(--c-text-3);padding:24px;">Belum ada login log.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:12px 14px;">{{ $loginLogs->appends(['search_page' => request('search_page')])->links() }}</div>
</div>
@endsection
