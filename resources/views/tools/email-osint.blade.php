@extends('layouts.app')
@section('title', 'Email OSINT')
@section('page-title', 'Email OSINT')

@section('content')
<div class="page-header">
    <h1>✉️ Email OSINT</h1>
    <p>Analisis informasi tersembunyi dari alamat email</p>
</div>

<div class="tool-input-card" style="max-width:480px;">
    <div class="form-group" style="margin-bottom:14px;">
        <label class="form-label">Alamat Email</label>
        <input type="email" id="input-email"
            class="form-control"
            placeholder="target@example.com"
            autofocus
            onkeydown="if(event.key==='Enter') doSearch()">
        <div id="input-error" style="display:none;color:var(--c-danger);font-size:12.5px;margin-top:4px;"></div>
    </div>
    <button id="btn-search" onclick="doSearch()" class="btn btn-primary btn-full">🔍 Analisa Email</button>
</div>

<div id="status-bar" style="display:none;max-width:480px;margin-bottom:16px;padding:10px 16px;border-radius:8px;font-size:13px;align-items:center;gap:10px;background:var(--c-surface);border:1px solid var(--c-border);">
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
    const emailEl = document.getElementById('input-email');
    const errEl   = document.getElementById('input-error');
    const btn     = document.getElementById('btn-search');
    const panel   = document.getElementById('results-panel');
    const email   = emailEl.value.trim();

    errEl.style.display = 'none';
    if (!email) {
        errEl.textContent = 'Alamat email wajib diisi.';
        errEl.style.display = 'block';
        emailEl.focus();
        return;
    }

    btn.disabled = true;
    btn.textContent = '⏳ Menganalisa…';
    setStatus('loading', 'Menganalisa email…');
    panel.style.display = 'block';
    panel.innerHTML = '<div style="text-align:center;padding:32px;"><div style="display:inline-block;width:28px;height:28px;border:3px solid var(--c-border);border-top-color:var(--c-primary);border-radius:50%;animation:spin .7s linear infinite;"></div></div>';

    try {
        const resp = await fetch('{{ route("email-osint.analyze") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ email })
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
        btn.textContent = '🔍 Analisa Email';
    }
}

function renderResult(r) {
    const panel = document.getElementById('results-panel');

    // ── Header badges ──
    const validBadge      = r.valid
        ? '<span class="badge badge-green">✓ Valid</span>'
        : '<span class="badge badge-red">✗ Invalid</span>';
    const disposableBadge = r.disposable
        ? '<span class="badge badge-yellow">⚠ Disposable</span>'
        : '';

    // ── Disify section ──
    const disify = r.disify || {};
    const fmtBool = (v, yes, no) => v ? yes : no;

    // ── Gravatar ──
    let gravatarHtml = '';
    if (r.gravatar) {
        gravatarHtml = `
            <div class="fw-6 mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.7px;color:var(--c-text-3);">Gravatar</div>
            <img src="${escHtml(r.gravatar)}" alt="Gravatar" style="width:80px;height:80px;border-radius:50%;border:3px solid var(--c-border);">
            <div style="margin-top:8px;">
                <a href="${escHtml(r.gravatar_profile || '')}" target="_blank" class="btn btn-secondary btn-sm">Lihat Profil Gravatar</a>
            </div>`;
    } else {
        gravatarHtml = `
            <div class="fw-6 mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.7px;color:var(--c-text-3);">Gravatar</div>
            <div class="text-muted text-sm">Tidak ada profil Gravatar.</div>`;
    }

    // ── MX Records ──
    let mxHtml = '';
    if (r.mx && r.mx.length > 0) {
        const mxBadges = r.mx.map(m => `<span class="badge badge-cyan font-mono" style="font-size:11px;">${escHtml(m)}</span>`).join('');
        mxHtml = `
        <div style="margin-top:16px;">
            <div class="fw-6 mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.7px;color:var(--c-text-3);">MX Records</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px;">${mxBadges}</div>
        </div>`;
    }

    // ── Investigation links ──
    const emailEnc  = encodeURIComponent(r.email || '');
    const googleEnc = encodeURIComponent('"' + (r.email || '') + '"');

    // ── Download ──
    const domain  = (r.domain || (r.email || '').split('@')[1] || 'email').replace(/[^a-zA-Z0-9._-]/g,'');
    const dlBtn   = `<button onclick="downloadJson(lastResult,'email_${domain}.json')" class="btn btn-secondary btn-sm" style="margin-top:16px;">⬇ Download JSON</button>`;

    panel.innerHTML = `
    <div class="result-card animate-fade-up" style="max-width:700px;">
        <div class="result-header" style="background:linear-gradient(135deg,#EFF6FF,#E0F2FE);">
            <span style="font-size:22px;">✉️</span>
            <div>
                <div style="font-weight:700;font-size:15px;">${escHtml(r.email)}</div>
                <div style="font-size:12.5px;color:var(--c-text-3);">${escHtml(r.domain || '')}</div>
            </div>
            <div style="margin-left:auto;display:flex;gap:6px;align-items:center;">
                ${validBadge}
                ${disposableBadge}
            </div>
        </div>
        <div class="result-body">
            <div class="grid-2">
                <div>
                    <div class="fw-6 mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.7px;color:var(--c-text-3);">Informasi Email</div>
                    <div class="data-row"><span class="data-key">Format</span><span class="data-val">${fmtBool(disify.format, '✓ Valid', '✗ Invalid')}</span></div>
                    <div class="data-row"><span class="data-key">DNS</span><span class="data-val">${fmtBool(disify.dns, '✓ Ada', '✗ Tidak')}</span></div>
                    <div class="data-row"><span class="data-key">Disposable</span><span class="data-val">${fmtBool(disify.disposable, '⚠ Ya', '✓ Tidak')}</span></div>
                    <div class="data-row"><span class="data-key">Domain</span><span class="data-val font-mono">${escHtml(r.domain || '—')}</span></div>
                </div>
                <div>${gravatarHtml}</div>
            </div>
            ${mxHtml}
            <div style="margin-top:16px;">
                <div class="fw-6 mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.7px;color:var(--c-text-3);">Investigasi Lanjut</div>
                <div class="flex gap-2" style="flex-wrap:wrap;">
                    <a href="https://haveibeenpwned.com/account/${emailEnc}" target="_blank" class="btn btn-secondary btn-sm">🔒 HaveIBeenPwned</a>
                    <a href="https://hunter.io/email-verifier/${emailEnc}" target="_blank" class="btn btn-secondary btn-sm">🎯 Hunter.io</a>
                    <a href="https://www.google.com/search?q=${googleEnc}" target="_blank" class="btn btn-secondary btn-sm">🔍 Google</a>
                </div>
            </div>
            ${dlBtn}
        </div>
    </div>`;
}
</script>
<style>@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }</style>
@endpush
