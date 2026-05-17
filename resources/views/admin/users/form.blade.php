@extends('layouts.app')
@section('title', $user ? 'Edit User' : 'Tambah User')
@section('page-title', 'Admin — ' . ($user ? 'Edit User' : 'Tambah User'))

@section('content')
<div class="page-header flex-between">
    <div>
        <h1>{{ $user ? '✏️ Edit User' : '➕ Tambah User' }}</h1>
        <p>{{ $user ? 'Perbarui data user ' . $user->username : 'Buat akun pengguna baru' }}</p>
    </div>
    <a href="{{ route('admin.users') }}" class="btn btn-secondary">← Kembali</a>
</div>

<div class="card" style="max-width:600px;">
    <div class="card-body">
        <form method="POST" action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}">
            @csrf
            @if($user) @method('PUT') @endif

            <div class="form-group">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text" name="name" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                    value="{{ old('name', $user?->name) }}" required>
                @error('name')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}"
                        value="{{ old('username', $user?->username) }}" required>
                    @error('username')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                        value="{{ old('email', $user?->email) }}" required>
                    @error('email')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Password {{ $user ? '(kosongkan jika tidak diubah)' : '*' }}</label>
                    <input type="password" name="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                        {{ $user ? '' : 'required' }}>
                    @error('password')<div class="form-error">{{ $message }}</div>@enderror
                    @if(!$user)<div class="form-hint">Min 8 karakter, huruf besar+kecil, angka, simbol.</div>@endif
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" class="form-control">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Role *</label>
                    <select name="role" class="form-control {{ $errors->has('role') ? 'is-invalid' : '' }}" required>
                        <option value="">-- Pilih Role --</option>
                        <option value="admin"    {{ old('role', $user?->role) === 'admin'    ? 'selected' : '' }}>Admin</option>
                        <option value="operator" {{ old('role', $user?->role) === 'operator' ? 'selected' : '' }}>Operator</option>
                        <option value="viewer"   {{ old('role', $user?->role) === 'viewer'   ? 'selected' : '' }}>Viewer</option>
                    </select>
                    @error('role')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                @if($user)
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <div style="display:flex;align-items:center;gap:10px;margin-top:8px;">
                        <input type="checkbox" name="is_active" value="1" id="is_active"
                            {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                        <label for="is_active" style="font-size:13px;cursor:pointer;">Akun Aktif</label>
                    </div>
                </div>
                @endif
            </div>

            <div class="form-group">
                <label class="form-label">API Token (opsional)</label>
                <input type="text" name="api_token" class="form-control font-mono"
                    style="font-size:12px;"
                    value="{{ old('api_token') }}"
                    placeholder="Token API GetContact atau lainnya">
                <div class="form-hint">Opsional. Jika diisi, digunakan untuk autentikasi tool tertentu.</div>
            </div>

            <div style="display:flex;gap:10px;margin-top:8px;">
                <button type="submit" class="btn btn-primary">
                    {{ $user ? '💾 Simpan Perubahan' : '➕ Buat User' }}
                </button>
                <a href="{{ route('admin.users') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
