@extends('layouts.app')
@section('title', 'Username Check')
@section('page-title', 'Username Check')

@section('content')
<div class="page-header">
    <h1>🔎 Username Check</h1>
    <p>Cek keberadaan username di 15 platform sosial media sekaligus</p>
</div>

<div class="tool-input-card" style="max-width:480px;">
    <form method="POST" action="{{ route('multicheck.check') }}">
        @csrf
        <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Username</label>
            <input type="text" name="username"
                class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}"
                value="{{ old('username', $username ?? '') }}"
                placeholder="contoh: johndoe"
                autofocus>
            @error('username')<div class="form-error">{{ $message }}</div>@enderror
            <div class="form-hint">Hanya huruf, angka, titik, underscore, dan dash.</div>
        </div>
        <button type="submit" class="btn btn-primary btn-full">
            🔎 Cek Username
        </button>
    </form>
</div>

@isset($results)
<div class="animate-fade-up">
    <div class="flex-between mb-4">
        <div class="fw-6" style="font-size:14px;">
            Hasil untuk <span class="font-mono" style="color:var(--c-blue-500);">{{ $username }}</span>
        </div>
        <div class="flex gap-2">
            <span class="badge badge-green">✓ {{ collect($results)->where('found', true)->count() }} Ditemukan</span>
            <span class="badge badge-gray">✗ {{ collect($results)->where('found', false)->count() }} Tidak Ada</span>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;">
        @foreach($results as $platform => $data)
        <a href="{{ $data['found'] ? $data['url'] : '#' }}"
           target="{{ $data['found'] ? '_blank' : '' }}"
           class="{{ $data['found'] ? '' : 'pointer-events-none' }}"
           style="text-decoration:none;">
            <div class="card" style="padding:14px 16px;border-left:3px solid {{ $data['found'] ? 'var(--c-success)' : 'var(--c-border)' }};transition:all .15s;{{ $data['found'] ? 'cursor:pointer;' : 'opacity:.7;' }}"
                 onmouseover="{{ $data['found'] ? "this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(16,185,129,.15)'" : '' }}"
                 onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div class="flex-between">
                    <span class="fw-6" style="font-size:13px;color:var(--c-text);">{{ $platform }}</span>
                    <span style="font-size:16px;">{{ $data['found'] ? '✅' : '❌' }}</span>
                </div>
                @if($data['found'])
                <div style="font-size:11px;color:var(--c-success);margin-top:4px;">Akun ditemukan →</div>
                @else
                <div style="font-size:11px;color:var(--c-text-3);margin-top:4px;">Tidak tersedia</div>
                @endif
            </div>
        </a>
        @endforeach
    </div>
</div>
@endisset
@endsection
