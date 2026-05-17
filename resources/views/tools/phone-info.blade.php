@extends('layouts.app')
@section('title', 'Phone OSINT')
@section('page-title', 'Phone OSINT')

@section('content')
<div class="page-header">
    <h1>📋 Phone OSINT</h1>
    <p>Informasi detail nomor telepon via libphonenumber</p>
</div>

<div class="tool-input-card" style="max-width:480px;">
    <form method="POST" action="{{ route('phone-info.analyze') }}">
        @csrf
        <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Nomor Telepon (format internasional)</label>
            <input type="text" name="phone"
                class="form-control {{ $errors->has('phone') ? 'is-invalid' : '' }}"
                value="{{ old('phone', $result['phone'] ?? '') }}"
                placeholder="+62811234567"
                autofocus>
            @error('phone')<div class="form-error">{{ $message }}</div>@enderror
            <div class="form-hint">Gunakan format internasional: +62, +1, +44, dll.</div>
        </div>
        <button type="submit" class="btn btn-primary btn-full">🔍 Analisa Nomor</button>
    </form>
</div>

@isset($result)
<div class="result-card animate-fade-up" style="max-width:600px;">
    @if(isset($result['error']))
    <div class="result-header" style="background:#FEF2F2;">
        <span style="color:var(--c-danger);">⚠</span>
        <span style="font-weight:600;color:var(--c-danger);">{{ $result['error'] }}</span>
    </div>
    @else
    <div class="result-header" style="background:linear-gradient(135deg,#EFF6FF,#E0F2FE);">
        <span style="font-size:22px;">📋</span>
        <div>
            <div style="font-weight:700;font-size:15px;" class="font-mono">{{ $result['international'] ?? $result['phone'] }}</div>
            <div style="font-size:12.5px;color:var(--c-text-3);">{{ $result['carrier'] ?? '' }}</div>
        </div>
        <span class="badge {{ $result['valid'] ? 'badge-green' : 'badge-red' }}" style="margin-left:auto;">
            {{ $result['valid'] ? '✓ Valid' : '✗ Invalid' }}
        </span>
    </div>
    <div class="result-body">
        <div class="data-row"><span class="data-key">Format Nasional</span><span class="data-val font-mono">{{ $result['national'] ?? '—' }}</span></div>
        <div class="data-row"><span class="data-key">Format E.164</span><span class="data-val font-mono">{{ $result['e164'] ?? '—' }}</span></div>
        <div class="data-row"><span class="data-key">Internasional</span><span class="data-val font-mono">{{ $result['international'] ?? '—' }}</span></div>
        <div class="data-row"><span class="data-key">Negara</span><span class="data-val">{{ $result['country'] ?? '—' }}</span></div>
        <div class="data-row"><span class="data-key">Operator/Wilayah</span><span class="data-val">{{ $result['carrier'] ?? '—' }}</span></div>
        <div class="data-row"><span class="data-key">Tipe Nomor</span><span class="data-val">{{ $result['type'] ?? '—' }}</span></div>
    </div>
    @endif
</div>
@endisset
@endsection
