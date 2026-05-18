@extends('layouts.app')
@section('title', 'Riwayat Pencarian')
@section('page-title', 'Riwayat Pencarian')

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
$toolNames = [
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
@endphp

@section('content')
<div class="page-header flex-between">
    <div>
        <h1>🕐 Riwayat Pencarian</h1>
        <p>Seluruh riwayat aktivitas pencarian{{ auth()->user()->isAdmin() ? ' semua pengguna' : ' Anda' }}</p>
    </div>
    <a href="{{ route('history.export', request()->query()) }}" class="btn btn-sm" style="background:var(--c-surface);border:1px solid var(--c-border);">
        ⬇ Export CSV
    </a>
</div>

{{-- Filter --}}
<div class="card mb-4" style="padding:16px 20px;">
    <form method="GET" action="{{ route('history') }}" class="flex" style="flex-wrap:wrap;gap:10px;align-items:flex-end;">
        <div>
            <label class="form-label" style="font-size:11px;">Cari Query</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Kata kunci..." class="form-control" style="width:180px;">
        </div>
        <div>
            <label class="form-label" style="font-size:11px;">Tool</label>
            <select name="tool" class="form-control" style="width:160px;">
                <option value="">Semua Tool</option>
                @foreach($tools as $t)
                <option value="{{ $t }}" {{ request('tool') === $t ? 'selected' : '' }}>
                    {{ $toolNames[$t] ?? ucfirst($t) }}
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size:11px;">Status</label>
            <select name="status" class="form-control" style="width:120px;">
                <option value="">Semua</option>
                <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success</option>
                <option value="failed"  {{ request('status') === 'failed'  ? 'selected' : '' }}>Failed</option>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size:11px;">Dari Tanggal</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" style="width:150px;">
        </div>
        <div>
            <label class="form-label" style="font-size:11px;">Sampai</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" style="width:150px;">
        </div>
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            @if(request()->hasAny(['q','tool','status','date_from','date_to']))
            <a href="{{ route('history') }}" class="btn btn-sm" style="background:var(--c-surface);border:1px solid var(--c-border);">Reset</a>
            @endif
        </div>
    </form>
</div>

{{-- Table --}}
<div class="table-wrapper">
    <div style="padding:12px 16px;border-bottom:1px solid var(--c-border);font-size:12px;color:var(--c-text-3);">
        Menampilkan {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }} data
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Tool</th>
                <th>Query</th>
                @if(auth()->user()->isAdmin())<th>User</th>@endif
                <th>Status</th>
                <th>IP</th>
                <th>Waktu</th>
            </tr>
        </thead>
        <tbody>
        @forelse($logs as $log)
        @php [$tLabel,$tBadge] = $toolLabels[$log->tool] ?? [ucfirst($log->tool),'badge-gray']; @endphp
        <tr>
            <td><span class="badge {{ $tBadge }}">{{ $tLabel }}</span></td>
            <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" class="fw-6">{{ $log->query }}</td>
            @if(auth()->user()->isAdmin())
            <td>{{ $log->user?->username ?? '—' }}</td>
            @endif
            <td>
                <span class="badge {{ $log->status === 'success' ? 'badge-green' : 'badge-red' }}">{{ $log->status }}</span>
            </td>
            <td class="font-mono text-sm text-muted">{{ $log->ip_address }}</td>
            <td class="text-muted text-sm" title="{{ $log->created_at->format('d/m/Y H:i:s') }}">
                {{ $log->created_at->diffForHumans() }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="{{ auth()->user()->isAdmin() ? 6 : 5 }}" style="text-align:center;color:var(--c-text-3);padding:32px;">
                Tidak ada data untuk filter ini.
            </td>
        </tr>
        @endforelse
        </tbody>
    </table>
</div>
<div style="margin-top:14px;">{{ $logs->links() }}</div>
@endsection
