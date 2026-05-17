@extends('layouts.app')
@section('title', 'Email OSINT')
@section('page-title', 'Email OSINT')

@section('content')
<div class="page-header">
    <h1>✉️ Email OSINT</h1>
    <p>Analisis informasi tersembunyi dari alamat email</p>
</div>

<div class="tool-input-card" style="max-width:480px;">
    <form method="POST" action="{{ route('email-osint.analyze') }}">
        @csrf
        <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Alamat Email</label>
            <input type="email" name="email"
                class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                value="{{ old('email', $result['email'] ?? '') }}"
                placeholder="target@example.com"
                autofocus>
            @error('email')<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary btn-full">🔍 Analisa Email</button>
    </form>
</div>

@isset($result)
<div class="result-card animate-fade-up" style="max-width:700px;">
    <div class="result-header" style="background:linear-gradient(135deg,#EFF6FF,#E0F2FE);">
        <span style="font-size:22px;">✉️</span>
        <div>
            <div style="font-weight:700;font-size:15px;">{{ $result['email'] }}</div>
            <div style="font-size:12.5px;color:var(--c-text-3);">{{ $result['domain'] ?? '' }}</div>
        </div>
        <div style="margin-left:auto;display:flex;gap:6px;align-items:center;">
            @if($result['valid'] ?? false)
                <span class="badge badge-green">✓ Valid</span>
            @else
                <span class="badge badge-red">✗ Invalid</span>
            @endif
            @if($result['disposable'] ?? false)
                <span class="badge badge-yellow">⚠ Disposable</span>
            @endif
        </div>
    </div>
    <div class="result-body">
        <div class="grid-2">
            <div>
                <div class="fw-6 mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.7px;color:var(--c-text-3);">Informasi Email</div>
                @php $disify = $result['disify'] ?? []; @endphp
                <div class="data-row"><span class="data-key">Format</span><span class="data-val">{{ ($disify['format'] ?? false) ? '✓ Valid' : '✗ Invalid' }}</span></div>
                <div class="data-row"><span class="data-key">DNS</span><span class="data-val">{{ ($disify['dns'] ?? false) ? '✓ Ada' : '✗ Tidak' }}</span></div>
                <div class="data-row"><span class="data-key">Disposable</span><span class="data-val">{{ ($disify['disposable'] ?? false) ? '⚠ Ya' : '✓ Tidak' }}</span></div>
                <div class="data-row"><span class="data-key">Domain</span><span class="data-val font-mono">{{ $result['domain'] ?? '—' }}</span></div>
            </div>
            <div>
                @if($result['gravatar'])
                <div class="fw-6 mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.7px;color:var(--c-text-3);">Gravatar</div>
                <img src="{{ $result['gravatar'] }}" alt="Gravatar" style="width:80px;height:80px;border-radius:50%;border:3px solid var(--c-border);">
                <div style="margin-top:8px;">
                    <a href="{{ $result['gravatar_profile'] }}" target="_blank" class="btn btn-secondary btn-sm">Lihat Profil Gravatar</a>
                </div>
                @else
                <div class="fw-6 mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.7px;color:var(--c-text-3);">Gravatar</div>
                <div class="text-muted text-sm">Tidak ada profil Gravatar.</div>
                @endif
            </div>
        </div>

        @if(!empty($result['mx']))
        <div style="margin-top:16px;">
            <div class="fw-6 mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.7px;color:var(--c-text-3);">MX Records</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px;">
                @foreach($result['mx'] as $mx)
                <span class="badge badge-cyan font-mono" style="font-size:11px;">{{ $mx }}</span>
                @endforeach
            </div>
        </div>
        @endif

        <div style="margin-top:16px;">
            <div class="fw-6 mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.7px;color:var(--c-text-3);">Investigasi Lanjut</div>
            <div class="flex gap-2" style="flex-wrap:wrap;">
                <a href="https://haveibeenpwned.com/account/{{ urlencode($result['email']) }}" target="_blank" class="btn btn-secondary btn-sm">🔒 HaveIBeenPwned</a>
                <a href="https://hunter.io/email-verifier/{{ urlencode($result['email']) }}" target="_blank" class="btn btn-secondary btn-sm">🎯 Hunter.io</a>
                <a href="https://www.google.com/search?q={{ urlencode('"'.$result['email'].'"') }}" target="_blank" class="btn btn-secondary btn-sm">🔍 Google</a>
            </div>
        </div>
    </div>
</div>
@endisset
@endsection
