@extends('layouts.app')
@section('title', 'Manajemen Users')
@section('page-title', 'Admin — Users')

@section('content')
<div class="page-header flex-between">
    <div>
        <h1>👥 Manajemen Users</h1>
        <p>Kelola semua akun pengguna sistem</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">+ Tambah User</a>
</div>

@if(session('success'))
<div class="alert alert-success" data-auto-close>✓ {{ session('success') }}</div>
@endif
@if($errors->has('error'))
<div class="alert alert-error">⚠ {{ $errors->first('error') }}</div>
@endif

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Total Search</th>
                <th>Last Login</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        @forelse($users as $u)
        <tr>
            <td class="fw-6">{{ $u->name }}</td>
            <td class="font-mono" style="font-size:12.5px;">{{ $u->username }}</td>
            <td>{{ $u->email }}</td>
            <td>
                <span class="badge {{ $u->role === 'admin' ? 'badge-red' : ($u->role === 'operator' ? 'badge-blue' : 'badge-gray') }}">
                    {{ strtoupper($u->role) }}
                </span>
            </td>
            <td>
                <span class="badge {{ $u->is_active ? 'badge-green' : 'badge-red' }}">
                    {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </td>
            <td>{{ $u->search_logs_count }}</td>
            <td class="text-muted text-sm">
                {{ $u->last_login_at ? $u->last_login_at->diffForHumans() : 'Belum pernah' }}
            </td>
            <td>
                <div class="flex gap-2">
                    <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-secondary btn-sm">Edit</a>
                    @if($u->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}"
                          onsubmit="return confirm('Hapus user {{ $u->username }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;color:var(--c-text-3);padding:32px;">Belum ada user.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:14px;">{{ $users->links() }}</div>
@endsection
