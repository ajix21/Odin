<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ODIN') — Project ODIN</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

{{-- Brand / Sidebar Logo --}}
<a href="{{ route('dashboard') }}" class="navbar-brand">
    <img src="{{ asset('icon.png') }}" alt="ODIN">
    <span>ODIN</span>
</a>

{{-- Navbar --}}
<nav class="navbar">
    <span style="font-size:13px;color:var(--c-text-3);">@yield('page-title', 'Dashboard')</span>
    <div class="navbar-right">
        <div class="navbar-user">
            <div class="role-dot" style="background:{{ auth()->user()->isAdmin() ? '#EF4444' : (auth()->user()->isOperator() ? '#2563EB' : '#64748B') }}"></div>
            <span>{{ auth()->user()->name }}</span>
            <span class="badge {{ auth()->user()->isAdmin() ? 'badge-red' : (auth()->user()->isOperator() ? 'badge-blue' : 'badge-gray') }}" style="font-size:10px;">
                {{ strtoupper(auth()->user()->role) }}
            </span>
        </div>
    </div>
</nav>

{{-- Sidebar --}}
<aside class="sidebar" id="sidebar">

    {{-- Dashboard --}}
    <div class="sidebar-section">
        <a href="{{ route('dashboard') }}" class="sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span class="sidebar-icon">⊞</span> Dashboard
        </a>
        <a href="{{ route('history') }}" class="sidebar-item {{ request()->routeIs('history') ? 'active' : '' }}">
            <span class="sidebar-icon">🕐</span> Riwayat Pencarian
        </a>
    </div>

    <hr class="sidebar-divider">

    {{-- Telepon --}}
    <div class="sidebar-section">
        <div class="sidebar-label">📱 Telepon</div>
        @if(auth()->user()->canSearch())
        <a href="{{ route('phone-lookup') }}" class="sidebar-item {{ request()->routeIs('phone-lookup*') ? 'active' : '' }}">
            <span class="sidebar-icon">🔍</span> Phone Lookup
        </a>
        <a href="{{ route('phone-info') }}" class="sidebar-item {{ request()->routeIs('phone-info*') ? 'active' : '' }}">
            <span class="sidebar-icon">📋</span> Phone OSINT
        </a>
        @endif
        <a href="{{ route('history.phone') }}" class="sidebar-item {{ request()->routeIs('history.phone') ? 'active' : '' }}">
            <span class="sidebar-icon">🕐</span> Riwayat Phone
        </a>
    </div>

    <hr class="sidebar-divider">

    {{-- Identitas --}}
    @if(auth()->user()->canSearch())
    <div class="sidebar-section">
        <div class="sidebar-label">👤 Identitas</div>
        <a href="{{ route('email-osint') }}" class="sidebar-item {{ request()->routeIs('email-osint*') ? 'active' : '' }}">
            <span class="sidebar-icon">✉️</span> Email OSINT
        </a>
        <a href="{{ route('multicheck') }}" class="sidebar-item {{ request()->routeIs('multicheck*') ? 'active' : '' }}">
            <span class="sidebar-icon">🔎</span> Username Check
        </a>
        <a href="{{ route('toutatis') }}" class="sidebar-item {{ request()->routeIs('toutatis*') ? 'active' : '' }}">
            <span class="sidebar-icon">📸</span> Instagram
        </a>
    </div>

    <hr class="sidebar-divider">

    {{-- Jaringan --}}
    <div class="sidebar-section">
        <div class="sidebar-label">🌐 Jaringan</div>
        <a href="{{ route('ip-geo') }}" class="sidebar-item {{ request()->routeIs('ip-geo*') ? 'active' : '' }}">
            <span class="sidebar-icon">🗺️</span> IP Geolocation
        </a>
        <a href="{{ route('whois') }}" class="sidebar-item {{ request()->routeIs('whois*') ? 'active' : '' }}">
            <span class="sidebar-icon">🌍</span> WHOIS Domain
        </a>
    </div>

    <hr class="sidebar-divider">

    {{-- Data Breach --}}
    <div class="sidebar-section">
        <div class="sidebar-label">🔍 Data Breach</div>
        <a href="{{ route('leakosint') }}" class="sidebar-item {{ request()->routeIs('leakosint*') ? 'active' : '' }}">
            <span class="sidebar-icon">💧</span> LeakOSINT
        </a>
        <a href="{{ route('history.leakosint') }}" class="sidebar-item {{ request()->routeIs('history.leakosint') ? 'active' : '' }}">
            <span class="sidebar-icon">🕐</span> Riwayat Leak
        </a>
    </div>
    @endif

    {{-- Admin --}}
    @if(auth()->user()->isAdmin())
    <hr class="sidebar-divider">
    <div class="sidebar-section">
        <div class="sidebar-label">⚙️ Admin</div>
        <a href="{{ route('admin.users') }}" class="sidebar-item {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
            <span class="sidebar-icon">👥</span> Manajemen Users
        </a>
        <a href="{{ route('admin.logs') }}" class="sidebar-item {{ request()->routeIs('admin.logs') ? 'active' : '' }}">
            <span class="sidebar-icon">📊</span> Audit Logs
        </a>
        <a href="{{ route('settings') }}" class="sidebar-item {{ request()->routeIs('settings') ? 'active' : '' }}">
            <span class="sidebar-icon">🔑</span> Settings
        </a>
    </div>
    @endif

    <hr class="sidebar-divider">
    <div class="sidebar-section" style="padding-top:4px;">
        <a href="{{ route('profile') }}" class="sidebar-item {{ request()->routeIs('profile') ? 'active' : '' }}">
            <span class="sidebar-icon">👤</span> Profil Saya
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-btn">
                <span>⬡</span> Keluar
            </button>
        </form>
    </div>

</aside>

{{-- Main --}}
<main class="main-content">
    <div class="content-area animate-fade-up">
        @yield('content')
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.alert[data-auto-close]').forEach(function(el) {
        setTimeout(function() { el.style.opacity='0'; el.style.transition='opacity .4s'; setTimeout(()=>el.remove(),400); }, 4000);
    });
});
</script>
@stack('scripts')
</body>
</html>
