@extends('layouts.app')
@section('title', 'Phone Lookup')
@section('page-title', 'Phone Lookup')

@section('content')
<div class="page-header flex-between">
    <div>
        <h1>📱 Phone Lookup</h1>
        <p>Cari informasi nomor telepon via GetContact API</p>
    </div>
    <a href="{{ route('history.phone') }}" class="btn btn-secondary btn-sm">🕐 Riwayat</a>
</div>

<div class="tool-input-card" style="max-width:520px;">
    <form method="POST" action="{{ route('phone-lookup.search') }}">
        @csrf
        <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Nomor Telepon</label>
            <input type="text" name="phone"
                class="form-control {{ $errors->has('phone') ? 'is-invalid' : '' }}"
                value="{{ old('phone', isset($result) ? $result['phone'] : '') }}"
                placeholder="08xx, 62xx, atau +62xx"
                autofocus>
            @error('phone')<div class="form-error">{{ $message }}</div>@enderror
            <div class="form-hint">Format: 08123456789 atau +628123456789</div>
        </div>
        <button type="submit" class="btn btn-primary btn-full">
            🔍 Cari Nomor
        </button>
    </form>
</div>

@isset($result)
<div class="result-card animate-fade-up" style="max-width:700px;">
    @if(!$result['success'])
    <div class="result-header" style="background:#FEF2F2;">
        <span style="color:var(--c-danger);font-size:18px;">⚠</span>
        <div>
            <div style="font-weight:600;color:var(--c-danger);">Pencarian Gagal</div>
            <div style="font-size:12.5px;color:#991B1B;">{{ $result['error'] }}</div>
        </div>
    </div>
    @else
    <div class="result-header" style="background:linear-gradient(135deg,#EFF6FF,#E0F2FE);">
        <span style="font-size:22px;">📱</span>
        <div>
            <div style="font-weight:700;font-size:15px;color:var(--c-text);">
                {{ $result['profile']['name'] ?? 'Tanpa Nama' }}
            </div>
            <div class="font-mono" style="font-size:13px;color:var(--c-text-3);">{{ $result['phone'] }}</div>
        </div>
        @if($result['spam'])
        <span class="badge {{ $result['spam'] === 'High' ? 'badge-red' : ($result['spam'] === 'Medium' ? 'badge-yellow' : 'badge-green') }}" style="margin-left:auto;">
            🚫 Spam: {{ $result['spam'] }}
        </span>
        @endif
    </div>
    <div class="result-body">
        {{-- Profile Info --}}
        @if(!empty($result['profile']))
        <div class="mb-4">
            <div class="fw-6 mb-3" style="font-size:13px;color:var(--c-text-3);text-transform:uppercase;letter-spacing:.7px;">Info Profil</div>
            @foreach(['name'=>'Nama','email'=>'Email','country'=>'Negara','phoneNumber'=>'Nomor'] as $k => $label)
            @if(!empty($result['profile'][$k]))
            <div class="data-row">
                <span class="data-key">{{ $label }}</span>
                <span class="data-val">{{ $result['profile'][$k] }}</span>
            </div>
            @endif
            @endforeach
        </div>
        @endif

        {{-- Tags --}}
        @if(!empty($result['tags']))
        <div class="mb-4">
            <div class="fw-6 mb-3" style="font-size:13px;color:var(--c-text-3);text-transform:uppercase;letter-spacing:.7px;">
                Tags ({{ $result['tag_count'] }})
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:6px;">
                @foreach($result['tags'] as $tag)
                <span class="badge badge-blue">{{ $tag['tag'] ?? $tag }}</span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Badge --}}
        @if($result['badge'])
        <div class="data-row">
            <span class="data-key">Badge</span>
            <span class="data-val badge badge-cyan">{{ $result['badge'] }}</span>
        </div>
        @endif
    </div>
    @endif
</div>
@endisset
@endsection
