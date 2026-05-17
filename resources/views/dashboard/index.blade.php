@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="page-header flex-between">
    <div>
        <h1>⊞ Dashboard</h1>
        <p>{{ now()->translatedFormat('l, d F Y') }} &mdash; Selamat datang, {{ auth()->user()->name }}</p>
    </div>
</div>

{{-- Stats --}}
<div class="grid-4 mb-6">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:#EFF6FF;">🔍</div>
        <div class="stat-card-value">{{ $stats['searches_today'] }}</div>
        <div class="stat-card-label">Search Hari Ini</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:#E0F2FE;">📊</div>
        <div class="stat-card-value">{{ $stats['searches_total'] }}</div>
        <div class="stat-card-label">Total Search</div>
    </div>
    @if(auth()->user()->isAdmin())
    <div class="stat-card">
        <div class="stat-card-icon" style="background:#D1FAE5;">👥</div>
        <div class="stat-card-value">{{ $stats['active_users'] }}</div>
        <div class="stat-card-label">User Aktif</div>
    </div>
    @else
    <div class="stat-card">
        <div class="stat-card-icon" style="background:#EDE9FE;">🏷️</div>
        <div class="stat-card-value">{{ strtoupper(auth()->user()->role) }}</div>
        <div class="stat-card-label">Role Anda</div>
    </div>
    @endif
    <div class="stat-card">
        <div class="stat-card-icon" style="background:#FEF3C7;">⭐</div>
        <div class="stat-card-value" style="font-size:16px;margin-top:6px;">
            {{ $stats['top_tool'] ? ucfirst($stats['top_tool']) : '—' }}
        </div>
        <div class="stat-card-label">Tool Terpopuler</div>
    </div>
</div>

{{-- Quick Access --}}
@if(auth()->user()->canSearch())
<div class="card mb-6">
    <div class="card-header"><h3>🚀 Akses Cepat</h3></div>
    <div class="card-body">
        <div class="quick-grid">
            <a href="{{ route('phone-lookup') }}" class="quick-item">
                <span class="quick-item-icon">📱</span>
                <span class="quick-item-label">Phone Lookup</span>
            </a>
            <a href="{{ route('leakosint') }}" class="quick-item">
                <span class="quick-item-icon">💧</span>
                <span class="quick-item-label">LeakOSINT</span>
            </a>
            <a href="{{ route('multicheck') }}" class="quick-item">
                <span class="quick-item-icon">🔎</span>
                <span class="quick-item-label">Username Check</span>
            </a>
            <a href="{{ route('email-osint') }}" class="quick-item">
                <span class="quick-item-icon">✉️</span>
                <span class="quick-item-label">Email OSINT</span>
            </a>
            <a href="{{ route('phone-info') }}" class="quick-item">
                <span class="quick-item-icon">📋</span>
                <span class="quick-item-label">Phone OSINT</span>
            </a>
            <a href="{{ route('ip-geo') }}" class="quick-item">
                <span class="quick-item-icon">🗺️</span>
                <span class="quick-item-label">IP Geolocation</span>
            </a>
            <a href="{{ route('whois') }}" class="quick-item">
                <span class="quick-item-icon">🌍</span>
                <span class="quick-item-label">WHOIS Domain</span>
            </a>
            <a href="{{ route('toutatis') }}" class="quick-item">
                <span class="quick-item-icon">📸</span>
                <span class="quick-item-label">Instagram</span>
            </a>
        </div>
    </div>
</div>
@endif

{{-- Activity --}}
<div class="{{ auth()->user()->isAdmin() ? 'grid-2' : '' }}" style="{{ auth()->user()->isAdmin() ? '' : '' }}">

    {{-- Recent Searches --}}
    <div class="card">
        <div class="card-header">
            <h3>🕐 Pencarian Terakhir</h3>
            <span class="text-sm text-muted">{{ $recentLogs->count() }} data</span>
        </div>
        @if($recentLogs->isEmpty())
        <div class="card-body text-muted text-sm" style="text-align:center;padding:32px;">
            Belum ada pencarian.
        </div>
        @else
        <div class="table-wrapper" style="border-radius:0;border:none;border-top:1px solid var(--c-border);">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tool</th>
                        <th>Query</th>
                        @if(auth()->user()->isAdmin())<th>User</th>@endif
                        <th>Status</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($recentLogs as $log)
                <tr>
                    <td>
                        <span class="badge {{ $log->tool === 'getcontact' ? 'badge-blue' : 'badge-purple' }}">
                            {{ $log->tool === 'getcontact' ? 'GetContact' : 'LeakOSINT' }}
                        </span>
                    </td>
                    <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $log->query }}</td>
                    @if(auth()->user()->isAdmin())
                    <td>{{ $log->user?->username ?? '—' }}</td>
                    @endif
                    <td>
                        <span class="badge {{ $log->status === 'success' ? 'badge-green' : 'badge-red' }}">
                            {{ $log->status }}
                        </span>
                    </td>
                    <td class="text-muted text-sm">{{ $log->created_at->diffForHumans() }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Recent Logins (admin only) --}}
    @if(auth()->user()->isAdmin())
    <div class="card">
        <div class="card-header">
            <h3>🔐 Login Terakhir</h3>
            <span class="text-sm text-muted">{{ $recentLogins->count() }} data</span>
        </div>
        @if($recentLogins->isEmpty())
        <div class="card-body text-muted text-sm" style="text-align:center;padding:32px;">
            Belum ada data login.
        </div>
        @else
        <div class="table-wrapper" style="border-radius:0;border:none;border-top:1px solid var(--c-border);">
            <table class="data-table">
                <thead>
                    <tr><th>Username</th><th>IP</th><th>Status</th><th>Waktu</th></tr>
                </thead>
                <tbody>
                @foreach($recentLogins as $login)
                <tr>
                    <td class="fw-6">{{ $login->username }}</td>
                    <td class="font-mono" style="font-size:12px;">{{ $login->ip_address }}</td>
                    <td>
                        <span class="badge {{ $login->success ? 'badge-green' : 'badge-red' }}">
                            {{ $login->success ? 'Berhasil' : 'Gagal' }}
                        </span>
                    </td>
                    <td class="text-muted text-sm">{{ $login->created_at->diffForHumans() }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endif

</div>
@endsection
