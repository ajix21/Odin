@extends('layouts.app')
@section('title', 'Riwayat LeakOSINT')
@section('page-title', 'Riwayat LeakOSINT')

@section('content')
<div class="page-header flex-between">
    <div>
        <h1>🕐 Riwayat LeakOSINT</h1>
        <p>Total {{ $logs->total() }} pencarian data breach</p>
    </div>
    <a href="{{ route('leakosint') }}" class="btn btn-primary btn-sm">+ Cari Baru</a>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Query</th>
                @if(auth()->user()->isAdmin())<th>User</th>@endif
                <th>Status</th>
                <th>DB Ditemukan</th>
                <th>IP</th>
                <th>Waktu</th>
            </tr>
        </thead>
        <tbody>
        @forelse($logs as $log)
        <tr>
            <td class="fw-6" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $log->query }}</td>
            @if(auth()->user()->isAdmin())
            <td>{{ $log->user?->username ?? '—' }}</td>
            @endif
            <td>
                <span class="badge {{ $log->status === 'success' ? 'badge-green' : 'badge-red' }}">
                    {{ $log->status }}
                </span>
            </td>
            <td>
                @if($log->result_json)
                    <span class="badge badge-purple">{{ count($log->result_json) }} db</span>
                @else
                    —
                @endif
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
