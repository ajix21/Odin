@extends('layouts.app')
@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
<div class="page-header">
    <h1>🔑 Pengaturan & Credentials</h1>
    <p>Konfigurasi API key dan credential untuk semua tools ODIN</p>
</div>

@if(session('success'))
<div class="alert alert-success" data-auto-close>✓ {{ session('success') }}</div>
@endif

<div class="alert alert-warning">
    ⚠ <div>Credential disimpan <strong>terenkripsi</strong>. Kosongkan field untuk tidak mengubah nilai yang tersimpan.</div>
</div>

<form method="POST" action="{{ route('settings.update') }}">
    @csrf

    @php
        $groups = collect($config)->groupBy('group');
        $groupIcons = [
            'GetContact'  => '📱',
            'LeakOSINT'   => '💧',
            'Phone OSINT' => '📋',
            'Toutatis'    => '📸',
        ];
    @endphp

    @foreach($groups as $groupName => $items)
    <div class="card mb-4">
        <div class="card-header">
            <h3>{{ $groupIcons[$groupName] ?? '⚙️' }} {{ $groupName }}</h3>
        </div>
        <div class="card-body">
            @foreach($items as $key => $cfg)
            <div class="form-group">
                <label class="form-label">{{ $cfg['label'] }}</label>
                @if($cfg['secret'])
                <input
                    type="password"
                    name="{{ $key }}"
                    class="form-control font-mono"
                    style="font-size:12.5px;"
                    placeholder="{{ isset($current[$key]) && $current[$key]['value'] ? '••••••••••••••• (sudah dikonfigurasi)' : 'Masukkan ' . $cfg['label'] }}"
                    autocomplete="new-password">
                @if(isset($current[$key]) && $current[$key]['value'])
                <div class="form-hint">✓ Sudah dikonfigurasi. Kosongkan untuk tidak mengubah.</div>
                @else
                <div class="form-hint">Belum dikonfigurasi.</div>
                @endif
                @else
                <input
                    type="text"
                    name="{{ $key }}"
                    class="form-control font-mono"
                    style="font-size:12.5px;"
                    value="{{ old($key, $current[$key]['value'] ?? '') }}"
                    placeholder="Masukkan {{ $cfg['label'] }}">
                @endif
                @error($key)<div class="form-error">{{ $message }}</div>@enderror
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

    <button type="submit" class="btn btn-primary btn-full" style="max-width:300px;">
        💾 Simpan Pengaturan
    </button>
</form>
@endsection
