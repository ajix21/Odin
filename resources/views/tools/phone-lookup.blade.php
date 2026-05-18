@extends('layouts.app')
@section('title', 'Phone Lookup')
@section('page-title', 'Phone Lookup')

@section('content')
<div class="page-header flex-between">
    <div>
        <h1>📱 Phone Lookup</h1>
        <p>Cari informasi nomor telepon via GetContact API</p>
    </div>
    <a href="{{ route('history') }}?tool=getcontact" class="btn btn-secondary btn-sm">🕐 Riwayat</a>
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
        <button type="submit" class="btn btn-primary btn-full">🔍 Cari Nomor</button>
    </form>
</div>

@isset($result)
<div class="animate-fade-up" style="max-width:700px;">

    @if(!$result['success'])
    <div class="alert alert-error">⚠ {{ $result['error'] }}</div>

    @else
    {{-- ── Header identitas ── --}}
    <div class="card mb-4">
        <div style="padding:20px;display:flex;align-items:center;gap:16px;background:linear-gradient(135deg,#EFF6FF,#E0F2FE);border-radius:12px 12px 0 0;">
            @if($result['profile_image'])
            <img src="{{ $result['profile_image'] }}" alt="foto"
                style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:3px solid white;box-shadow:0 2px 10px rgba(0,0,0,.15);flex-shrink:0;">
            @else
            <div style="width:60px;height:60px;border-radius:50%;background:var(--c-blue-600);display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;color:white;">📱</div>
            @endif

            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:17px;color:var(--c-text);">
                    {{ $result['display_name'] ?? $result['phone_number'] }}
                </div>
                <div class="font-mono" style="font-size:12.5px;color:var(--c-text-3);margin-top:2px;">
                    {{ $result['phone_number'] }}
                    @if($result['country']) &middot; {{ $result['country'] }} @endif
                    @if($result['email']) &middot; {{ $result['email'] }} @endif
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:8px;">
                    @if($result['badge'])
                        <span class="badge badge-blue">🏅 {{ $result['badge'] }}</span>
                    @endif
                    @if($result['subscription'])
                        <span class="badge badge-purple">⭐ {{ ucfirst($result['subscription']) }}</span>
                    @endif
                    @if($result['access_type'])
                        <span class="badge" style="background:#f1f5f9;color:#64748b;font-size:11px;">{{ $result['access_type'] }}</span>
                    @endif
                </div>
            </div>

            {{-- Spam ── --}}
            @if($result['spam_degree'])
            @php
                $spamStyle = match($result['spam_degree']) {
                    'High'   => ['bg'=>'#FEF2F2','border'=>'#FECACA','text'=>'#991B1B'],
                    'Medium' => ['bg'=>'#FFFBEB','border'=>'#FDE68A','text'=>'#92400E'],
                    default  => ['bg'=>'#F0FDF4','border'=>'#BBF7D0','text'=>'#166534'],
                };
            @endphp
            <div style="text-align:center;padding:10px 14px;background:{{ $spamStyle['bg'] }};border:1px solid {{ $spamStyle['border'] }};border-radius:10px;flex-shrink:0;">
                <div style="font-size:20px;">🚫</div>
                <div style="font-weight:700;color:{{ $spamStyle['text'] }};font-size:12px;">{{ $result['spam_degree'] }}</div>
                <div style="font-size:11px;color:{{ $spamStyle['text'] }};opacity:.8;">{{ $result['spam_count'] }}× laporan</div>
            </div>
            @else
            <div style="text-align:center;padding:10px 14px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;flex-shrink:0;">
                <div style="font-size:20px;">✅</div>
                <div style="font-weight:700;color:#166534;font-size:12px;">Bersih</div>
                <div style="font-size:11px;color:#166534;opacity:.8;">0 laporan</div>
            </div>
            @endif
        </div>

        {{-- ── Quota & Status ── --}}
        <div style="padding:11px 20px;border-top:1px solid var(--c-border);background:var(--c-surface);border-radius:0 0 12px 12px;display:flex;flex-wrap:wrap;align-items:center;gap:14px;">

            {{-- Status premium --}}
            @php
                $qStatus   = $result['quota_status'] ?? 'free';
                $isPremium = strtolower($qStatus) !== 'free';
            @endphp
            <span class="badge {{ $isPremium ? 'badge-purple' : '' }}" style="{{ $isPremium ? '' : 'background:#f1f5f9;color:#64748b;' }}">
                {{ $isPremium ? '⭐' : '🔓' }} {{ ucfirst(strtolower($qStatus)) }}
            </span>

            {{-- Progress kuota --}}
            @if($result['quota_remaining'] !== null && $result['quota_limit'] !== null)
            @php
                $pct      = $result['quota_limit'] > 0 ? round(($result['quota_remaining'] / $result['quota_limit']) * 100) : 0;
                $barColor = $pct > 50 ? '#22c55e' : ($pct > 20 ? '#f59e0b' : '#ef4444');
            @endphp
            <span style="font-size:12px;color:var(--c-text-3);white-space:nowrap;">🔄 Kuota:</span>
            <div style="flex:1;min-width:80px;background:var(--c-border);border-radius:99px;height:7px;overflow:hidden;">
                <div style="width:{{ $pct }}%;background:{{ $barColor }};height:100%;border-radius:99px;transition:width .4s;"></div>
            </div>
            <span class="font-mono" style="font-size:12.5px;font-weight:700;color:{{ $barColor }};white-space:nowrap;">
                {{ number_format($result['quota_remaining']) }} / {{ number_format($result['quota_limit']) }}
            </span>

            @elseif($result['quota_remaining'] !== null)
            <span style="font-size:12px;color:var(--c-text-3);">🔄 Kuota:</span>
            <span class="font-mono" style="font-size:12.5px;font-weight:700;color:#22c55e;">
                {{ number_format($result['quota_remaining']) }} sisa
            </span>
            @endif

            {{-- Masa aktif --}}
            @if($result['quota_reset'])
            @php
                try { $resetDate = \Carbon\Carbon::parse($result['quota_reset'])->format('d/m/Y'); }
                catch(\Exception $e) { $resetDate = $result['quota_reset']; }
            @endphp
            <span style="font-size:12px;color:var(--c-text-3);white-space:nowrap;">
                📅 Aktif s/d: <strong style="color:var(--c-text);">{{ $resetDate }}</strong>
            </span>
            @endif

        </div>
    </div>

    {{-- ── Tags ── --}}
    @if(!empty($result['tags']))
    <div class="card">
        <div class="card-header">
            <h3>🏷️ Tags</h3>
            <span class="badge badge-purple">{{ $result['tag_count'] }} tag</span>
        </div>
        <div class="card-body">
            <div style="display:flex;flex-wrap:wrap;gap:7px;">
                @foreach($result['tags'] as $tag)
                @php
                    $tagText  = is_array($tag) ? ($tag['tag']   ?? '') : $tag;
                    $tagCount = is_array($tag) ? ($tag['count'] ?? null) : null;
                @endphp
                <span class="badge badge-blue" style="font-size:13px;padding:5px 10px;">
                    {{ $tagText }}{{ $tagCount ? ' x'.$tagCount : '' }}
                </span>
                @endforeach
            </div>
        </div>
    </div>

    @elseif($result['tag_count'] > 0)
    <div class="card">
        <div class="card-header">
            <h3>🏷️ Tags</h3>
            <span class="badge badge-purple">{{ $result['tag_count'] }} tag</span>
        </div>
        <div class="card-body" style="color:var(--c-text-3);font-size:13px;">
            Nomor ini memiliki {{ $result['tag_count'] }} tag, namun detail memerlukan akses premium.
        </div>
    </div>
    @endif

    @endif {{-- end success --}}
</div>
@endisset
@endsection
