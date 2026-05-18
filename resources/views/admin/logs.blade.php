@extends('layouts.app')
@section('title', 'Audit Logs')
@section('page-title', 'Admin — Audit Logs')

@php
$toolLabels = [
    'getcontact'  => ['GetContact','badge-blue'],
    'leakosint'   => ['LeakOSINT','badge-purple'],
    'toutatis'    => ['Instagram','badge-pink'],
    'whois'       => ['WHOIS','badge-cyan'],
    'ip-geo'      => ['IP Geo','badge-green'],
    'email-osint' => ['Email OSINT','badge-yellow'],
    'multicheck'  => ['Username Check','badge-orange'],
    'phone-info'  => ['Phone OSINT','badge-blue'],
    'phone'       => ['Phone Lookup','badge-blue'],
];
$toolKeys = array_keys($toolLabels);
@endphp

@section('content')
<div class="page-header flex-between">
    <div>
        <h1>📊 Audit Logs</h1>
        <p>Riwayat aktivitas pencarian dan login seluruh pengguna</p>
    </div>
    <a href="{{ route('admin.logs.export', request()->query()) }}" class="btn btn-sm" style="background:var(--c-surface);border:1px solid var(--c-border);">
        ⬇ Export CSV
    </a>
</div>

{{-- Search Logs --}}
<div class="card mb-6">
    <div class="card-header flex-between">
        <h3>🔍 Search Logs</h3>
        <span class="text-sm text-muted">Total: {{ $searchLogs->total() }}</span>
    </div>

    {{-- Filter --}}
    <div style="padding:14px 16px;border-bottom:1px solid var(--c-border);background:var(--c-bg);">
        <form method="GET" action="{{ route('admin.logs') }}" class="flex" style="flex-wrap:wrap;gap:10px;align-items:flex-end;">
            <div>
                <label class="form-label" style="font-size:11px;">Cari Query</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Kata kunci..." class="form-control" style="width:160px;">
            </div>
            <div>
                <label class="form-label" style="font-size:11px;">User</label>
                <select name="user_id" class="form-control" style="width:140px;">
                    <option value="">Semua User</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->username }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" style="font-size:11px;">Tool</label>
                <select name="tool" class="form-control" style="width:150px;">
                    <option value="">Semua Tool</option>
                    @foreach($toolKeys as $t)
                    <option value="{{ $t }}" {{ request('tool') === $t ? 'selected' : '' }}>
                        {{ $toolLabels[$t][0] }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" style="font-size:11px;">Status</label>
                <select name="status" class="form-control" style="width:110px;">
                    <option value="">Semua</option>
                    <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success</option>
                    <option value="failed"  {{ request('status') === 'failed'  ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
            <div>
                <label class="form-label" style="font-size:11px;">Dari</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" style="width:145px;">
            </div>
            <div>
                <label class="form-label" style="font-size:11px;">Sampai</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" style="width:145px;">
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                @if(request()->hasAny(['q','tool','status','user_id','date_from','date_to']))
                <a href="{{ route('admin.logs') }}" class="btn btn-sm" style="background:var(--c-surface);border:1px solid var(--c-border);">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <div class="table-wrapper" style="border-radius:0;border:none;border-top:1px solid var(--c-border);">
        <table class="data-table">
            <thead>
                <tr><th>User</th><th>Tool</th><th>Query</th><th>Status</th><th>IP</th><th>Waktu</th></tr>
            </thead>
            <tbody>
            @forelse($searchLogs as $log)
            @php [$tLabel,$tBadge] = $toolLabels[$log->tool] ?? [ucfirst($log->tool),'']; @endphp
            <tr>
                <td class="fw-6">{{ $log->user?->username ?? '—' }}</td>
                <td><span class="badge {{ $tBadge }}">{{ $tLabel }}</span></td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $log->query }}</td>
                <td>
                    <span class="badge {{ $log->status === 'success' ? 'badge-green' : 'badge-red' }}">{{ $log->status }}</span>
                </td>
                <td class="font-mono" style="font-size:11.5px;">{{ $log->ip_address }}</td>
                <td class="text-muted text-sm">{{ $log->created_at->format('d/m/y H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;color:var(--c-text-3);padding:24px;">Tidak ada data.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:12px 14px;">{{ $searchLogs->appends(request()->except('search_page'))->links() }}</div>
</div>

{{-- Login Logs --}}
<div class="card">
    <div class="card-header flex-between">
        <h3>🔐 Login Logs</h3>
        <span class="text-sm text-muted">Total: {{ $loginLogs->total() }}</span>
    </div>

    {{-- Filter --}}
    <div style="padding:14px 16px;border-bottom:1px solid var(--c-border);background:var(--c-bg);">
        <form method="GET" action="{{ route('admin.logs') }}" class="flex" style="flex-wrap:wrap;gap:10px;align-items:flex-end;">
            <input type="hidden" name="search_page" value="{{ request('search_page') }}">
            <div>
                <label class="form-label" style="font-size:11px;">Username</label>
                <input type="text" name="login_username" value="{{ request('login_username') }}" placeholder="Username..." class="form-control" style="width:180px;">
            </div>
            <div>
                <label class="form-label" style="font-size:11px;">Status</label>
                <select name="login_status" class="form-control" style="width:130px;">
                    <option value="">Semua</option>
                    <option value="success" {{ request('login_status') === 'success' ? 'selected' : '' }}>Berhasil</option>
                    <option value="failed"  {{ request('login_status') === 'failed'  ? 'selected' : '' }}>Gagal</option>
                </select>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                @if(request()->hasAny(['login_username','login_status']))
                <a href="{{ route('admin.logs') }}" class="btn btn-sm" style="background:var(--c-surface);border:1px solid var(--c-border);">Reset</a>
                @endif
            </div>
        </form>
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
            <tr><td colspan="5" style="text-align:center;color:var(--c-text-3);padding:24px;">Tidak ada data.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:12px 14px;">{{ $loginLogs->appends(request()->except('login_page'))->links() }}</div>
</div>
@endsection
