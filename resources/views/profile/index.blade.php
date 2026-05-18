@extends('layouts.app')
@section('title', 'Profil Saya')
@section('page-title', 'Profil Saya')

@section('content')
<div class="page-header">
    <h1>👤 Profil Saya</h1>
    <p>Kelola informasi akun dan ubah password</p>
</div>

@if(session('success'))
<div class="alert alert-success" data-auto-close style="margin-bottom:16px;">{{ session('success') }}</div>
@endif

<div style="max-width:520px;">
    <form method="POST" action="{{ route('profile.update') }}">
        @csrf
        @method('PUT')

        <div class="card mb-4">
            <div class="card-header"><h3>Informasi Akun</h3></div>
            <div class="card-body" style="padding:20px;display:flex;flex-direction:column;gap:14px;">

                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" value="{{ $user->username }}" class="form-control" disabled style="opacity:.6;cursor:not-allowed;">
                    <div class="form-hint">Username tidak dapat diubah.</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                           class="form-control @error('name') is-invalid @enderror"
                           required maxlength="255">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                           class="form-control @error('email') is-invalid @enderror"
                           required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Role</label>
                    <div style="display:flex;align-items:center;gap:8px;margin-top:6px;">
                        <span class="badge {{ $user->isAdmin() ? 'badge-red' : ($user->isOperator() ? 'badge-blue' : 'badge-gray') }}">
                            {{ strtoupper($user->role) }}
                        </span>
                        <span class="text-muted text-sm">Ditetapkan oleh administrator</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h3>Ubah Password</h3></div>
            <div class="card-body" style="padding:20px;display:flex;flex-direction:column;gap:14px;">
                <div class="form-hint" style="margin-top:0;">Kosongkan jika tidak ingin mengubah password.</div>

                <div class="form-group">
                    <label class="form-label">Password Saat Ini</label>
                    <input type="password" name="current_password"
                           class="form-control @error('current_password') is-invalid @enderror"
                           autocomplete="current-password">
                    @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           autocomplete="new-password">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-hint">Min. 8 karakter, huruf besar, angka, dan simbol.</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation"
                           class="form-control"
                           autocomplete="new-password">
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </form>

    <div class="card mt-4" style="padding:16px 20px;">
        <div style="font-size:12px;color:var(--c-text-3);">
            <div>Login terakhir: <strong>{{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : '—' }}</strong></div>
            <div>IP terakhir: <strong class="font-mono">{{ $user->last_login_ip ?? '—' }}</strong></div>
            <div>Bergabung: <strong>{{ $user->created_at->format('d F Y') }}</strong></div>
        </div>
    </div>
</div>
@endsection
