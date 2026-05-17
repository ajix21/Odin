@extends('layouts.app')
@section('title', 'Riwayat Phone Lookup')
@section('page-title', 'Riwayat Phone Lookup')

@section('content')
<div class="page-header flex-between">
    <div>
        <h1>🕐 Riwayat Phone Lookup</h1>
        <p>Total {{ $logs->total() }} pencarian GetContact</p>
    </div>
    <a href="{{ route('phone-lookup') }}" class="btn btn-primary btn-sm">+ Cari Baru</a>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Nomor</th>
                <th>Nama Hasil</th>
                @if(auth()->user()->isAdmin())<th>User</th>@endif
                <th>Status</th>
                <th>IP</th>
                <th>Waktu</th>
            </tr>
        </thead>
        <tbody>
        @forelse($logs as $log)
        <tr>
            <td class="font-mono fw-6" style="font-size:13px;">{{ $log->query }}</td>
            <td>{{ $log->result_json['display_name'] ?? ($log->result_json['profile']['displayName'] ?? '—') }}</td>
            @if(auth()->user()->isAdmin())
            <td>{{ $log->user?->username ?? '—' }}</td>
            @endif
            <td>
                <span class="badge {{ $log->status === 'success' ? 'badge-green' : 'badge-red' }}">
                    {{ $log->status }}
                </span>
            </td>
            <td class="font-mono text-sm text-muted">{{ $log->ip_address }}</td>
            <td class="text-muted text-sm">{{ $log->created_at->diffForHumans() }}</td>
        </tr>
        @empty
        <tr><td colspan="{{ auth()->user()->isAdmin() ? 6 : 5 }}" style="text-align:center;color:var(--c-text-3);padding:32px;">
            Belum ada riwayat pencarian.
        </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div style="margin-top:14px;">{{ $logs->links() }}</div>
@endsection
