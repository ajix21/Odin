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
    <div class="form-group" style="margin-bottom:14px;">
        <label class="form-label">Nomor Telepon</label>
        <input type="text" id="input-phone"
            class="form-control"
            placeholder="08xx, 62xx, atau +62xx"
            autofocus
            onkeydown="if(event.key==='Enter') doSearch()">
        <div id="input-error" style="display:none;color:var(--c-danger);font-size:12.5px;margin-top:4px;"></div>
        <div class="form-hint">Format: 08123456789 atau +628123456789</div>
    </div>
    <button id="btn-search" onclick="doSearch()" class="btn btn-primary btn-full">🔍 Cari Nomor</button>
</div>

<div id="status-bar" style="display:none;max-width:520px;margin-bottom:16px;padding:10px 16px;border-radius:8px;font-size:13px;align-items:center;gap:10px;background:var(--c-surface);border:1px solid var(--c-border);">
    <div id="status-dot" style="width:8px;height:8px;border-radius:50%;flex-shrink:0;"></div>
    <span id="status-text"></span>
</div>

<div id="results-panel" style="display:none;max-width:700px;"></div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let lastResult = null;

function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function setStatus(type, msg) {
    const colors = { loading:'#f59e0b', ready:'#22c55e', error:'#ef4444' };
    const bar = document.getElementById('status-bar');
    bar.style.display = 'flex';
    document.getElementById('status-dot').style.background = colors[type] || 'var(--c-text-3)';
    document.getElementById('status-text').textContent = msg;
}

function downloadJson(data, filename) {
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    a.click();
    URL.revokeObjectURL(a.href);
}

async function doSearch() {
    const phoneEl = document.getElementById('input-phone');
    const errEl   = document.getElementById('input-error');
    const btn     = document.getElementById('btn-search');
    const panel   = document.getElementById('results-panel');
    const phone   = phoneEl.value.trim();

    errEl.style.display = 'none';
    if (!phone) {
        errEl.textContent = 'Nomor telepon wajib diisi.';
        errEl.style.display = 'block';
        phoneEl.focus();
        return;
    }

    btn.disabled = true;
    btn.textContent = '⏳ Mencari…';
    setStatus('loading', 'Menghubungi GetContact API…');
    panel.style.display = 'block';
    panel.innerHTML = '<div style="text-align:center;padding:32px;"><div style="display:inline-block;width:28px;height:28px;border:3px solid var(--c-border);border-top-color:var(--c-primary);border-radius:50%;animation:spin .7s linear infinite;"></div></div>';

    try {
        const resp = await fetch('{{ route("phone-lookup.search") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ phone })
        });

        const data = await resp.json();

        if (resp.status === 422) {
            const msg = Object.values(data.errors || {}).flat()[0] || data.message;
            errEl.textContent = msg;
            errEl.style.display = 'block';
            panel.style.display = 'none';
            setStatus('error', msg);
            return;
        }

        if (!resp.ok) {
            throw new Error(data.message || 'Server error ' + resp.status);
        }

        lastResult = data;
        renderResult(data);
        setStatus('ready', 'Selesai.');
    } catch (err) {
        panel.innerHTML = '<div class="alert alert-error">⚠ ' + escHtml(err.message) + '</div>';
        setStatus('error', err.message);
    } finally {
        btn.disabled = false;
        btn.textContent = '🔍 Cari Nomor';
    }
}

function renderResult(r) {
    const panel = document.getElementById('results-panel');

    if (!r.success) {
        panel.innerHTML = '<div class="alert alert-error">⚠ ' + escHtml(r.error) + '</div>';
        return;
    }

    // ── Spam badge ──
    const spamDegree = r.spam_degree || '';
    let spamBg = '#F0FDF4', spamBorder = '#BBF7D0', spamText = '#166534';
    if (spamDegree === 'High')   { spamBg = '#FEF2F2'; spamBorder = '#FECACA'; spamText = '#991B1B'; }
    if (spamDegree === 'Medium') { spamBg = '#FFFBEB'; spamBorder = '#FDE68A'; spamText = '#92400E'; }

    const spamHtml = spamDegree
        ? `<div style="text-align:center;padding:10px 14px;background:${spamBg};border:1px solid ${spamBorder};border-radius:10px;flex-shrink:0;">
               <div style="font-size:20px;">🚫</div>
               <div style="font-weight:700;color:${spamText};font-size:12px;">${escHtml(spamDegree)}</div>
               <div style="font-size:11px;color:${spamText};opacity:.8;">${escHtml(r.spam_count)}× laporan</div>
           </div>`
        : `<div style="text-align:center;padding:10px 14px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;flex-shrink:0;">
               <div style="font-size:20px;">✅</div>
               <div style="font-weight:700;color:#166534;font-size:12px;">Bersih</div>
               <div style="font-size:11px;color:#166534;opacity:.8;">0 laporan</div>
           </div>`;

    // ── Avatar ──
    const avatarHtml = r.profile_image
        ? `<img src="${escHtml(r.profile_image)}" alt="foto" style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:3px solid white;box-shadow:0 2px 10px rgba(0,0,0,.15);flex-shrink:0;">`
        : `<div style="width:60px;height:60px;border-radius:50%;background:var(--c-blue-600);display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;color:white;">📱</div>`;

    // ── Badges (badge, subscription, access_type) ──
    let badgesHtml = '';
    if (r.badge)         badgesHtml += `<span class="badge badge-blue">🏅 ${escHtml(r.badge)}</span>`;
    if (r.subscription)  badgesHtml += `<span class="badge badge-purple">⭐ ${escHtml(r.subscription.charAt(0).toUpperCase() + r.subscription.slice(1))}</span>`;
    if (r.access_type)   badgesHtml += `<span class="badge" style="background:#f1f5f9;color:#64748b;font-size:11px;">${escHtml(r.access_type)}</span>`;

    // ── Meta line (phone · country · email) ──
    let metaParts = [escHtml(r.phone_number)];
    if (r.country) metaParts.push(escHtml(r.country));
    if (r.email)   metaParts.push(escHtml(r.email));

    // ── Quota bar ──
    const qStatus   = (r.quota_status || 'free');
    const isPremium = qStatus.toLowerCase() !== 'free';
    const premBadge = isPremium
        ? `<span class="badge badge-purple">⭐ ${escHtml(qStatus.charAt(0).toUpperCase() + qStatus.slice(1).toLowerCase())}</span>`
        : `<span class="badge" style="background:#f1f5f9;color:#64748b;">🔓 Free</span>`;

    let quotaHtml = '';
    if (r.quota_remaining !== null && r.quota_remaining !== undefined && r.quota_limit !== null && r.quota_limit !== undefined) {
        const pct      = r.quota_limit > 0 ? Math.round((r.quota_remaining / r.quota_limit) * 100) : 0;
        const barColor = pct > 50 ? '#22c55e' : (pct > 20 ? '#f59e0b' : '#ef4444');
        quotaHtml = `
            <span style="font-size:12px;color:var(--c-text-3);white-space:nowrap;">🔄 Kuota:</span>
            <div style="flex:1;min-width:80px;background:var(--c-border);border-radius:99px;height:7px;overflow:hidden;">
                <div style="width:${pct}%;background:${barColor};height:100%;border-radius:99px;transition:width .4s;"></div>
            </div>
            <span class="font-mono" style="font-size:12.5px;font-weight:700;color:${barColor};white-space:nowrap;">
                ${Number(r.quota_remaining).toLocaleString()} / ${Number(r.quota_limit).toLocaleString()}
            </span>`;
    } else if (r.quota_remaining !== null && r.quota_remaining !== undefined) {
        quotaHtml = `
            <span style="font-size:12px;color:var(--c-text-3);">🔄 Kuota:</span>
            <span class="font-mono" style="font-size:12.5px;font-weight:700;color:#22c55e;">${Number(r.quota_remaining).toLocaleString()} sisa</span>`;
    }

    let resetHtml = '';
    if (r.quota_reset) {
        resetHtml = `<span style="font-size:12px;color:var(--c-text-3);white-space:nowrap;">📅 Aktif s/d: <strong style="color:var(--c-text);">${escHtml(r.quota_reset)}</strong></span>`;
    }

    // ── Tags ──
    let tagsHtml = '';
    if (r.tags && r.tags.length > 0) {
        const tagItems = r.tags.map(tag => {
            const tagText  = (typeof tag === 'object') ? (tag.tag   || '') : tag;
            const tagCount = (typeof tag === 'object') ? (tag.count || null) : null;
            return `<span class="badge badge-blue" style="font-size:13px;padding:5px 10px;">${escHtml(tagText)}${tagCount ? ' x' + escHtml(String(tagCount)) : ''}</span>`;
        }).join('');
        tagsHtml = `
        <div class="card">
            <div class="card-header">
                <h3>🏷️ Tags</h3>
                <span class="badge badge-purple">${escHtml(String(r.tag_count))} tag</span>
            </div>
            <div class="card-body">
                <div style="display:flex;flex-wrap:wrap;gap:7px;">${tagItems}</div>
            </div>
        </div>`;
    } else if (r.tag_count > 0) {
        tagsHtml = `
        <div class="card">
            <div class="card-header">
                <h3>🏷️ Tags</h3>
                <span class="badge badge-purple">${escHtml(String(r.tag_count))} tag</span>
            </div>
            <div class="card-body" style="color:var(--c-text-3);font-size:13px;">
                Nomor ini memiliki ${escHtml(String(r.tag_count))} tag, namun detail memerlukan akses premium.
            </div>
        </div>`;
    }

    // ── Download ──
    const cleanPhone = (r.phone_number || '').replace(/[^0-9]/g, '');
    const dlBtn = `<button onclick="downloadJson(lastResult,'phone_${cleanPhone}.json')" class="btn btn-secondary btn-sm" style="margin-top:16px;">⬇ Download JSON</button>`;

    panel.innerHTML = `
    <div class="animate-fade-up">
        <div class="card mb-4">
            <div style="padding:20px;display:flex;align-items:center;gap:16px;background:linear-gradient(135deg,#EFF6FF,#E0F2FE);border-radius:12px 12px 0 0;">
                ${avatarHtml}
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;font-size:17px;color:var(--c-text);">${escHtml(r.display_name || r.phone_number)}</div>
                    <div class="font-mono" style="font-size:12.5px;color:var(--c-text-3);margin-top:2px;">${metaParts.join(' &middot; ')}</div>
                    <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:8px;">${badgesHtml}</div>
                </div>
                ${spamHtml}
            </div>
            <div style="padding:11px 20px;border-top:1px solid var(--c-border);background:var(--c-surface);border-radius:0 0 12px 12px;display:flex;flex-wrap:wrap;align-items:center;gap:14px;">
                ${premBadge}
                ${quotaHtml}
                ${resetHtml}
            </div>
        </div>
        ${tagsHtml}
        ${dlBtn}
    </div>`;
}
</script>
<style>@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }</style>
@endpush
