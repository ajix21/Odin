@extends('layouts.app')
@section('title', 'Instagram OSINT')
@section('page-title', 'Instagram OSINT')

@section('content')
<div class="page-header">
    <h1>📸 Instagram OSINT (Toutatis)</h1>
    <p>Deep OSINT pada akun Instagram — profil, kontak, statistik, dan postingan terbaru</p>
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
<div class="animate-fade-up" style="max-width:820px;">

    {{-- Error --}}
    @if(!($result['success'] ?? false))
    <div class="alert {{ ($result['rate_limited'] ?? false) ? 'alert-warning' : 'alert-error' }}">
        {{ ($result['rate_limited'] ?? false) ? '⏳' : '⚠' }} {{ $result['error'] }}
        @if($result['rate_limited'] ?? false)
        <div style="margin-top:6px;font-size:12px;opacity:.85;">Tunggu 1–2 menit lalu coba lagi, atau perbarui Session ID di Settings.</div>
        @endif
    </div>

    @else

    {{-- Notice data publik --}}
    @if(($result['source'] ?? '') === 'public')
    <div class="alert alert-warning" style="margin-bottom:16px;">
        🌐 <strong>Data Publik (Terbatas)</strong> — Instagram membatasi akses API saat ini. Bio, link, dan foto tidak tersedia. Tunggu beberapa menit lalu coba lagi, atau perbarui Session ID di
        <a href="{{ route('settings') }}" style="color:inherit;font-weight:600;">Settings</a>.
    </div>
    @endif

    {{-- ── Header Profil ── --}}
    <div class="card mb-4">
        <div style="padding:20px;display:flex;gap:18px;align-items:flex-start;background:linear-gradient(135deg,#fdf2f8,#fce7f3);border-radius:12px 12px 0 0;flex-wrap:wrap;">
            {{-- Avatar --}}
            <div style="flex-shrink:0;">
                @if($result['profile_pic'])
                <img src="{{ $result['profile_pic'] }}" alt="Profile"
                    style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid white;box-shadow:0 2px 12px rgba(0,0,0,.15);">
                @else
                <div style="width:80px;height:80px;border-radius:50%;background:#e879a0;display:flex;align-items:center;justify-content:center;font-size:32px;">📸</div>
                @endif
            </div>

            {{-- Info utama --}}
            <div style="flex:1;min-width:200px;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span style="font-weight:700;font-size:18px;">{{ ($result['full_name'] ?? '') ?: ($result['username'] ?? '') }}</span>
                    @if($result['is_verified'] ?? false) <span style="color:#2563EB;font-size:16px;" title="Verified">✓</span> @endif
                </div>
                <div style="font-size:13px;color:var(--c-text-3);margin-bottom:8px;">
                    <a href="https://www.instagram.com/{{ $result['username'] ?? '' }}/" target="_blank" rel="noopener noreferrer" style="color:var(--c-blue-500);text-decoration:none;">{{ '@' . ($result['username'] ?? '') }}</a>
                    @if($result['id'] ?? null) <span style="margin-left:6px;font-family:monospace;font-size:11px;opacity:.6;">ID: {{ $result['id'] }}</span> @endif
                </div>

                {{-- Badges --}}
                <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px;">
                    @if($result['is_private'] ?? false)  <span class="badge badge-yellow">🔒 Private</span>   @else <span class="badge badge-green">🔓 Public</span> @endif
                    @if($result['is_business'] ?? $result['business'] ?? false) <span class="badge badge-blue">💼 Business</span> @endif
                    @if($result['account_type'] ?? null) <span class="badge" style="background:#f1f5f9;color:#475569;">{{ $result['account_type'] }}</span> @endif
                    @if($result['category'] ?? null)    <span class="badge badge-purple">{{ $result['category'] }}</span> @endif
                    @if(($result['source'] ?? '') === 'public') <span class="badge" style="background:#fef3c7;color:#92400e;font-size:11px;">🌐 Data Publik</span> @endif
                </div>

                {{-- Bio --}}
                @if($result['bio'] ?? null)
                <div style="font-size:13.5px;white-space:pre-line;color:var(--c-text);line-height:1.5;max-width:460px;margin-bottom:6px;">{{ $result['bio'] }}</div>
                @endif
                @if($result['pronouns'] ?? null)
                <div style="font-size:12px;color:var(--c-text-3);margin-top:4px;">{{ $result['pronouns'] }}</div>
                @endif
                {{-- Website / external_url langsung di bawah bio --}}
                @if($result['external_url'] ?? null)
                @php $extUrl = $result['external_url']; $extSafe = preg_match('/^https?:\/\//i', $extUrl); @endphp
                <div style="font-size:13px;margin-top:4px;">
                    🔗
                    @if($extSafe)
                    <a href="{{ $extUrl }}" target="_blank" rel="noopener noreferrer" style="color:var(--c-blue-500);">{{ $extUrl }}</a>
                    @else
                    <span style="color:var(--c-warning);" title="URL tidak aman">{{ $extUrl }}</span>
                    @endif
                </div>
                @endif
            </div>

            {{-- Statistik ringkas --}}
            <div style="display:flex;gap:12px;flex-shrink:0;flex-wrap:wrap;">
                @foreach([
                    ['val'=>$result['posts']      ?? 0, 'lbl'=>'Posts'],
                    ['val'=>$result['followers']   ?? 0, 'lbl'=>'Followers'],
                    ['val'=>$result['following']   ?? 0, 'lbl'=>'Following'],
                    ['val'=>$result['highlights']  ?? 0, 'lbl'=>'Highlights'],
                ] as $stat)
                <div style="text-align:center;padding:10px 14px;background:white;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.06);min-width:60px;">
                    <div style="font-weight:700;font-size:17px;font-family:'Sora',sans-serif;">{{ number_format($stat['val']) }}</div>
                    <div style="font-size:11px;color:var(--c-text-3);">{{ $stat['lbl'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Kontak & Lokasi --}}
        @php
            $contacts = array_filter([
                'Email'   => $result['email']   ?? null,
                'Telepon' => $result['phone']   ?? null,
                'Alamat'  => $result['address'] ?? null,
            ]);
        @endphp
        @if($contacts)
        <div style="padding:12px 20px;border-top:1px solid var(--c-border);display:flex;flex-wrap:wrap;gap:6px 20px;background:var(--c-surface);border-radius:0 0 12px 12px;">
            @foreach($contacts as $label => $val)
            <div style="font-size:12.5px;">
                <span style="color:var(--c-text-3);">{{ $label }}:</span>
                <span class="font-mono" style="color:var(--c-text);">{{ $val }}</span>
            </div>
            @endforeach
            @if(($result['lat'] ?? null) && ($result['lng'] ?? null))
            <div style="font-size:12.5px;">
                <span style="color:var(--c-text-3);">Koordinat:</span>
                <span class="font-mono">{{ $result['lat'] }}, {{ $result['lng'] }}</span>
            </div>
            @endif
        </div>
        @endif
    </div>

    {{-- ── 3 Postingan Terbaru ── --}}
    @if(!empty($result['recent_posts'] ?? []))
    <div class="card">
        <div class="card-header">
            <h3>🖼️ Postingan Terbaru</h3>
            <a href="https://www.instagram.com/{{ $result['username'] ?? '' }}/" target="_blank" rel="noopener noreferrer"
               class="btn btn-secondary btn-sm" style="font-size:12px;">Buka Profil ↗</a>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0;border-top:1px solid var(--c-border);">
            @foreach($result['recent_posts'] as $i => $post)
            <div style="position:relative;aspect-ratio:1;overflow:hidden;{{ $i === 1 ? 'border-left:1px solid var(--c-border);border-right:1px solid var(--c-border);' : '' }}">
                {{-- Thumbnail --}}
                @if($post['image'])
                <img src="{{ $post['image'] }}" alt="Post"
                    style="width:100%;height:100%;object-fit:cover;display:block;">
                @else
                <div style="width:100%;height:100%;background:var(--c-bg);display:flex;align-items:center;justify-content:center;font-size:32px;">
                    {{ $post['is_video'] ? '🎬' : '🖼️' }}
                </div>
                @endif

                {{-- Overlay pada hover --}}
                <a href="{{ $post['url'] }}" target="_blank" rel="noopener noreferrer"
                   style="position:absolute;inset:0;background:rgba(0,0,0,0);display:flex;flex-direction:column;justify-content:flex-end;padding:10px;text-decoration:none;transition:background .2s;"
                   onmouseover="this.style.background='rgba(0,0,0,.55)';this.querySelector('.post-overlay').style.opacity=1"
                   onmouseout="this.style.background='rgba(0,0,0,0)';this.querySelector('.post-overlay').style.opacity=0">
                    <div class="post-overlay" style="opacity:0;transition:opacity .2s;">
                        <div style="color:white;font-size:12px;display:flex;gap:12px;margin-bottom:4px;">
                            <span>❤️ {{ number_format($post['likes']) }}</span>
                            <span>💬 {{ number_format($post['comments']) }}</span>
                            @if($post['is_video'])<span>🎬 Video</span>@endif
                            @if($post['type'] === 'GraphSidecar')<span>⊞ Album</span>@endif
                        </div>
                        @if($post['caption'])
                        <div style="color:rgba(255,255,255,.9);font-size:11px;line-height:1.4;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
                            {{ $post['caption'] }}
                        </div>
                        @endif
                    </div>
                </a>

                {{-- Type badge --}}
                @if($post['is_video'])
                <div style="position:absolute;top:8px;right:8px;color:white;font-size:16px;text-shadow:0 1px 3px rgba(0,0,0,.6);">🎬</div>
                @elseif($post['type'] === 'GraphSidecar')
                <div style="position:absolute;top:8px;right:8px;color:white;font-size:16px;text-shadow:0 1px 3px rgba(0,0,0,.6);">⊞</div>
                @endif

                {{-- Timestamp --}}
                @if($post['timestamp'])
                <div style="position:absolute;bottom:6px;left:8px;color:rgba(255,255,255,.85);font-size:10.5px;text-shadow:0 1px 3px rgba(0,0,0,.7);">
                    {{ \Carbon\Carbon::createFromTimestamp($post['timestamp'])->format('d/m/Y') }}
                </div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Caption ringkas di bawah grid --}}
        <div style="border-top:1px solid var(--c-border);">
            @foreach($result['recent_posts'] as $i => $post)
            @if($post['caption'])
            <div style="padding:10px 16px;{{ $i < count($result['recent_posts'])-1 ? 'border-bottom:1px solid var(--c-border);' : '' }}display:flex;gap:10px;align-items:flex-start;">
                <span style="font-size:12px;color:var(--c-text-3);flex-shrink:0;padding-top:1px;">{{ $i+1 }}.</span>
                <div style="font-size:12.5px;color:var(--c-text);line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $post['caption'] }}</div>
                <div style="flex-shrink:0;font-size:11.5px;color:var(--c-text-3);white-space:nowrap;">
                    ❤️ {{ number_format($post['likes']) }} &nbsp; 💬 {{ number_format($post['comments']) }}
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>

    @elseif(!($result['is_private'] ?? false))
    <div class="alert alert-info">ℹ Tidak ada postingan yang dapat diambil (akun kosong atau data publik terbatas).</div>
    @endif

    @endif
</div>
@endisset
@endsection
