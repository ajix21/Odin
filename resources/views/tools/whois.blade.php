@extends('layouts.app')
@section('title', 'WHOIS Domain')
@section('page-title', 'WHOIS Domain')

@section('content')
<div class="page-header">
    <h1>🌍 WHOIS Domain</h1>
    <p>Informasi registrasi domain, DNS records, dan sertifikat SSL</p>
</div>

<div class="tool-input-card" style="max-width:480px;">
    <form method="POST" action="{{ route('whois.lookup') }}">
        @csrf
        <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Domain</label>
            <input type="text" name="domain"
                class="form-control font-mono {{ $errors->has('domain') ? 'is-invalid' : '' }}"
                value="{{ old('domain', $result['domain'] ?? '') }}"
                placeholder="example.com"
                autofocus>
            @error('domain')<div class="form-error">{{ $message }}</div>@enderror
            <div class="form-hint">Tanpa http:// — contoh: google.com</div>
        </div>
        <button type="submit" class="btn btn-primary btn-full">🌍 Lookup WHOIS</button>
    </form>
</div>

@isset($result)
<div class="animate-fade-up" style="max-width:800px;">

    {{-- WHOIS Info --}}
    @if($result['success'])
    <div class="card mb-4">
        <div class="card-header"><h3>📋 Informasi WHOIS</h3></div>
        <div class="card-body">
            <div class="data-row"><span class="data-key">Domain</span><span class="data-val font-mono fw-6">{{ $result['domain'] }}</span></div>
            <div class="data-row"><span class="data-key">Registrar</span><span class="data-val">{{ $result['registrar'] ?? '—' }}</span></div>
            <div class="data-row"><span class="data-key">Owner</span><span class="data-val">{{ $result['owner'] ?? '—' }}</span></div>
            <div class="data-row"><span class="data-key">Terdaftar</span><span class="data-val font-mono">{{ $result['created'] ?? '—' }}</span></div>
            <div class="data-row"><span class="data-key">Diperbarui</span><span class="data-val font-mono">{{ $result['updated'] ?? '—' }}</span></div>
            <div class="data-row"><span class="data-key">Kedaluwarsa</span>
                <span class="data-val font-mono" style="color:{{ isset($result['expires']) && $result['expires'] < now()->format('Y-m-d') ? 'var(--c-danger)' : 'inherit' }}">
                    {{ $result['expires'] ?? '—' }}
                </span>
            </div>
            @if(!empty($result['nameservers']))
            <div class="data-row">
                <span class="data-key">Nameservers</span>
                <span class="data-val">
                    @foreach($result['nameservers'] as $ns)
                    <div class="font-mono" style="font-size:12.5px;">{{ $ns }}</div>
                    @endforeach
                </span>
            </div>
            @endif
        </div>
    </div>
    @elseif(isset($result['whois_error']))
    <div class="alert alert-warning mb-4">⚠ WHOIS: {{ $result['whois_error'] }}</div>
    @endif

    {{-- DNS Records --}}
    @if(!empty($result['dns']))
    <div class="card mb-4">
        <div class="card-header"><h3>🌐 DNS Records</h3></div>
        <div class="card-body">
            @foreach(['A'=>'A Record','MX'=>'MX Record','NS'=>'NS Record','TXT'=>'TXT Record'] as $type => $label)
            @if(!empty($result['dns'][$type]))
            <div style="margin-bottom:12px;">
                <div class="fw-6 mb-2" style="font-size:12px;color:var(--c-text-3);text-transform:uppercase;letter-spacing:.7px;">{{ $label }}</div>
                @foreach($result['dns'][$type] as $rec)
                <div class="font-mono" style="font-size:12px;padding:4px 8px;background:var(--c-bg);border-radius:4px;margin-bottom:3px;">
                    {{ $rec['ip'] ?? $rec['target'] ?? $rec['entries'][0] ?? json_encode($rec) }}
                </div>
                @endforeach
            </div>
            @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- SSL --}}
    @if(!empty($result['ssl']))
    <div class="card">
        <div class="card-header"><h3>🔒 SSL Certificate</h3></div>
        <div class="card-body">
            <div class="data-row"><span class="data-key">Subject</span><span class="data-val font-mono">{{ $result['ssl']['subject'] ?? '—' }}</span></div>
            <div class="data-row"><span class="data-key">Issuer</span><span class="data-val">{{ $result['ssl']['issuer'] ?? '—' }}</span></div>
            <div class="data-row"><span class="data-key">Valid From</span><span class="data-val font-mono">{{ $result['ssl']['valid_from'] ?? '—' }}</span></div>
            <div class="data-row"><span class="data-key">Valid To</span><span class="data-val font-mono">{{ $result['ssl']['valid_to'] ?? '—' }}</span></div>
            @if($result['ssl']['san'])
            <div class="data-row"><span class="data-key">SAN</span><span class="data-val font-mono" style="font-size:11.5px;word-break:break-all;">{{ $result['ssl']['san'] }}</span></div>
            @endif
        </div>
    </div>
    @endif

</div>
@endisset
@endsection
