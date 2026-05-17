@extends('layouts.app')
@section('title', 'IP Geolocation')
@section('page-title', 'IP Geolocation')

@section('content')
<div class="page-header">
    <h1>🗺️ IP Geolocation</h1>
    <p>Lacak lokasi geografis dan informasi jaringan dari IP address</p>
</div>

<div class="tool-input-card" style="max-width:480px;">
    <form method="POST" action="{{ route('ip-geo.lookup') }}">
        @csrf
        <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">IP Address</label>
            <input type="text" name="ip"
                class="form-control font-mono {{ $errors->has('ip') ? 'is-invalid' : '' }}"
                value="{{ old('ip', $result['ip'] ?? '') }}"
                placeholder="8.8.8.8 atau 2001:db8::1"
                autofocus>
            @error('ip')<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary btn-full">🗺️ Lookup IP</button>
    </form>
</div>

@isset($result)
<div class="animate-fade-up" style="max-width:900px;">
    @if(!($result['success'] ?? false))
    <div class="alert alert-error">⚠ {{ $result['error'] ?? 'Lookup gagal.' }}</div>
    @else
    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h3>📍 Lokasi & Jaringan</h3></div>
            <div class="card-body">
                <div class="data-row"><span class="data-key">IP</span><span class="data-val font-mono fw-6">{{ $result['query'] }}</span></div>
                <div class="data-row"><span class="data-key">Negara</span><span class="data-val">{{ $result['country'] ?? '—' }} ({{ $result['countryCode'] ?? '' }})</span></div>
                <div class="data-row"><span class="data-key">Provinsi</span><span class="data-val">{{ $result['regionName'] ?? '—' }}</span></div>
                <div class="data-row"><span class="data-key">Kota</span><span class="data-val">{{ $result['city'] ?? '—' }}</span></div>
                <div class="data-row"><span class="data-key">Kode Pos</span><span class="data-val font-mono">{{ $result['zip'] ?? '—' }}</span></div>
                <div class="data-row"><span class="data-key">Koordinat</span><span class="data-val font-mono">{{ $result['lat'] ?? '—' }}, {{ $result['lon'] ?? '—' }}</span></div>
                <div class="data-row"><span class="data-key">Timezone</span><span class="data-val">{{ $result['timezone'] ?? '—' }}</span></div>
                <div class="data-row"><span class="data-key">ISP</span><span class="data-val">{{ $result['isp'] ?? '—' }}</span></div>
                <div class="data-row"><span class="data-key">Organisasi</span><span class="data-val">{{ $result['org'] ?? '—' }}</span></div>
                <div class="data-row"><span class="data-key">ASN</span><span class="data-val font-mono">{{ $result['as'] ?? '—' }}</span></div>
                <div style="margin-top:12px;display:flex;gap:6px;flex-wrap:wrap;">
                    @if($result['proxy'] ?? false)<span class="badge badge-red">🔴 Proxy/VPN</span>@endif
                    @if($result['hosting'] ?? false)<span class="badge badge-yellow">🟡 Hosting/DC</span>@endif
                    @if($result['mobile'] ?? false)<span class="badge badge-blue">📱 Mobile</span>@endif
                    @if(!($result['proxy'] ?? false) && !($result['hosting'] ?? false))<span class="badge badge-green">✓ Residential</span>@endif
                </div>
            </div>
        </div>
        @php
            $lat = is_numeric($result['lat'] ?? '') ? (float) $result['lat'] : null;
            $lon = is_numeric($result['lon'] ?? '') ? (float) $result['lon'] : null;
        @endphp
        @if($lat !== null && $lon !== null)
        <div class="card">
            <div class="card-header"><h3>🗺️ Peta</h3></div>
            <div style="height:300px;">
                <iframe
                    width="100%" height="100%"
                    style="border:none;"
                    src="https://maps.google.com/maps?q={{ $lat }},{{ $lon }}&z=10&output=embed"
                    loading="lazy"
                    sandbox="allow-scripts allow-same-origin">
                </iframe>
            </div>
        </div>
        @endif
    </div>
    @endif
</div>
@endisset
@endsection
