@extends('layouts.app')
@section('title', 'Instagram OSINT')
@section('page-title', 'Instagram OSINT')

@section('content')
<div class="page-header">
    <h1>📸 Instagram OSINT (Toutatis)</h1>
    <p>Deep OSINT pada akun Instagram — profil, kontak, dan metadata</p>
</div>

<div class="tool-input-card" style="max-width:480px;">
    <form method="POST" action="{{ route('toutatis.lookup') }}">
        @csrf
        <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Username Instagram</label>
            <div style="position:relative;">
                <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--c-text-3);font-size:13px;">@</span>
                <input type="text" name="username"
                    class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}"
                    style="padding-left:26px;"
                    value="{{ old('username', $result['username'] ?? '') }}"
                    placeholder="username"
                    autofocus>
            </div>
            @error('username')<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary btn-full">📸 Lookup Instagram</button>
    </form>
</div>

@isset($result)
<div class="animate-fade-up" style="max-width:700px;">
    @if(!($result['success'] ?? false))
    <div class="alert alert-error">⚠ {{ $result['error'] }}</div>
    @else
    <div class="card">
        <div class="result-header" style="background:linear-gradient(135deg,#fdf2f8,#fce7f3);">
            @if($result['profile_pic'])
            <img src="{{ $result['profile_pic'] }}" alt="Profile"
                style="width:64px;height:64px;border-radius:50%;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,.15);">
            @endif
            <div>
                <div style="font-weight:700;font-size:16px;display:flex;align-items:center;gap:6px;">
                    {{ $result['full_name'] ?: $result['username'] }}
                    @if($result['is_verified'])<span title="Verified" style="color:#2563EB;">✓</span>@endif
                </div>
                <div style="font-size:12.5px;color:var(--c-text-3);">@{{ $result['username'] }}</div>
                @if($result['category'])<div style="font-size:12px;margin-top:2px;"><span class="badge badge-purple">{{ $result['category'] }}</span></div>@endif
            </div>
            <div style="margin-left:auto;text-align:right;">
                @if($result['is_private'])
                <span class="badge badge-yellow">🔒 Private</span>
                @else
                <span class="badge badge-green">🔓 Public</span>
                @endif
                @if($result['business'])<div style="margin-top:4px;"><span class="badge badge-blue">💼 Business</span></div>@endif
            </div>
        </div>

        <div class="card-body">
            {{-- Stats --}}
            <div class="grid-3 mb-4" style="text-align:center;">
                <div style="padding:12px;background:var(--c-bg);border-radius:8px;">
                    <div style="font-family:'Sora';font-size:22px;font-weight:700;">{{ number_format($result['posts']) }}</div>
                    <div style="font-size:11px;color:var(--c-text-3);">Postingan</div>
                </div>
                <div style="padding:12px;background:var(--c-bg);border-radius:8px;">
                    <div style="font-family:'Sora';font-size:22px;font-weight:700;">{{ number_format($result['followers']) }}</div>
                    <div style="font-size:11px;color:var(--c-text-3);">Followers</div>
                </div>
                <div style="padding:12px;background:var(--c-bg);border-radius:8px;">
                    <div style="font-family:'Sora';font-size:22px;font-weight:700;">{{ number_format($result['following']) }}</div>
                    <div style="font-size:11px;color:var(--c-text-3);">Following</div>
                </div>
            </div>

            {{-- Bio --}}
            @if($result['bio'])
            <div class="mb-4">
                <div class="fw-6 mb-2" style="font-size:12px;text-transform:uppercase;letter-spacing:.7px;color:var(--c-text-3);">Bio</div>
                <div style="background:var(--c-bg);padding:12px;border-radius:8px;font-size:13.5px;white-space:pre-line;">{{ $result['bio'] }}</div>
            </div>
            @endif

            {{-- Contact (obfuscated) --}}
            @if($result['email'] || $result['phone'] || $result['external_url'])
            <div>
                <div class="fw-6 mb-2" style="font-size:12px;text-transform:uppercase;letter-spacing:.7px;color:var(--c-text-3);">Kontak (Obfuscated)</div>
                @if($result['email'])
                <div class="data-row"><span class="data-key">Email</span><span class="data-val font-mono" style="color:var(--c-warning);">{{ $result['email'] }}</span></div>
                @endif
                @if($result['phone'])
                <div class="data-row"><span class="data-key">Telepon</span><span class="data-val font-mono" style="color:var(--c-warning);">{{ $result['phone'] }}</span></div>
                @endif
                @if($result['external_url'])
                <div class="data-row"><span class="data-key">Website</span><span class="data-val"><a href="{{ $result['external_url'] }}" target="_blank" style="color:var(--c-blue-500);">{{ $result['external_url'] }}</a></span></div>
                @endif
            </div>
            @endif

            <div class="data-row mt-4"><span class="data-key text-muted text-sm">User ID</span><span class="data-val font-mono text-muted text-sm">{{ $result['id'] }}</span></div>
        </div>
    </div>
    @endif
</div>
@endisset
@endsection
