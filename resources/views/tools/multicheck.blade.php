@extends('layouts.app')
@section('title', 'Username Check')
@section('page-title', 'Username Check')

@section('content')
<div class="page-header">
    <h1>🔎 Username Check</h1>
    <p>Cek keberadaan username di 18 platform sosial media sekaligus</p>
</div>

<div class="tool-input-card" style="max-width:480px;">
    <div class="form-group" style="margin-bottom:14px;">
        <label class="form-label">Username</label>
        <input type="text" id="mc-username"
            class="form-control"
            placeholder="contoh: johndoe"
            autofocus
            onkeydown="if(event.key==='Enter') doCheck()">
        <div class="form-hint">Hanya huruf, angka, titik, underscore, dan dash.</div>
    </div>
    <button id="btn-mc" onclick="doCheck()" class="btn btn-primary btn-full">🔎 Cek Username</button>
</div>

{{-- Status bar --}}
<div id="mc-status" style="display:none;max-width:480px;margin-bottom:16px;padding:10px 16px;border-radius:8px;font-size:13px;align-items:center;gap:10px;background:var(--c-surface);border:1px solid var(--c-border);">
    <div id="mc-status-dot" style="width:8px;height:8px;border-radius:50%;flex-shrink:0;"></div>
    <span id="mc-status-text"></span>
</div>

{{-- Results panel --}}
<div id="mc-results" style="display:none;"></div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let mcLastResult = null;

function downloadJson(data, filename) {
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    a.click();
    URL.revokeObjectURL(a.href);
}

function setStatus(type, msg) {
    const colors = { loading: '#f59e0b', ready: '#22c55e', error: '#ef4444' };
    const bar = document.getElementById('mc-status');
    bar.style.display = 'flex';
    document.getElementById('mc-status-dot').style.background = colors[type] || 'var(--c-text-3)';
    document.getElementById('mc-status-text').textContent = msg;
}

function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function doCheck() {
    const username = document.getElementById('mc-username').value.trim();
    if (!username) { setStatus('error', 'Error: Username wajib diisi.'); return; }
    if (!/^[a-zA-Z0-9._-]+$/.test(username)) {
        setStatus('error', 'Error: Hanya huruf, angka, titik, underscore, dan dash.');
        return;
    }

    const btn    = document.getElementById('btn-mc');
    const panel  = document.getElementById('mc-results');
    btn.disabled = true;
    setStatus('loading', `Memeriksa @${username} di semua platform...`);
    panel.style.display = 'block';
    panel.innerHTML = `
        <div style="padding:48px 24px;text-align:center;color:var(--c-text-3);">
            <div style="font-size:24px;margin-bottom:12px;animation:spin 1s linear infinite;display:inline-block;">⏳</div>
            <div style="font-size:13px;">Mengirim permintaan ke 18 platform secara paralel...</div>
        </div>`;

    try {
        const resp = await fetch('{{ route("multicheck.check") }}', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body:    JSON.stringify({ username }),
        });
        const data = await resp.json();
        if (!resp.ok) throw new Error(data.message || `HTTP ${resp.status}`);
        mcLastResult = data;
        renderResults(data);
    } catch (err) {
        panel.innerHTML = `
            <div style="padding:32px 24px;text-align:center;">
                <div style="font-size:32px;margin-bottom:12px;">⚠</div>
                <div style="font-size:13px;color:var(--c-text-3);">${escHtml(err.message)}</div>
            </div>`;
        setStatus('error', 'Error: ' + err.message);
    } finally {
        btn.disabled = false;
    }
}

function renderResults({ username, results }) {
    const panel    = document.getElementById('mc-results');
    const entries  = Object.entries(results);
    const found    = entries.filter(([, d]) => d.found);
    const notFound = entries.filter(([, d]) => !d.found);

    setStatus('ready', `@${username} — ditemukan di ${found.length} dari ${entries.length} platform`);

    let html = `<div class="animate-fade-up">
        <div class="flex-between mb-4" style="flex-wrap:wrap;gap:8px;">
            <div class="fw-6" style="font-size:14px;">
                Hasil untuk <span class="font-mono" style="color:var(--c-blue-500);">@${escHtml(username)}</span>
            </div>
            <div class="flex gap-2" style="flex-wrap:wrap;align-items:center;">
                <span class="badge badge-green">✓ ${found.length} Ditemukan</span>
                <span class="badge badge-gray">✗ ${notFound.length} Tidak Ada</span>
                <span class="badge" style="background:#fef3c7;color:#92400e;font-size:11px;" title="Platform dengan tanda ⚠ menggunakan bot-protection — hasil mungkin tidak akurat">⚠ ${entries.filter(([,d])=>!d.reliable).length} Tidak Pasti</span>
                <button onclick="downloadJson(mcLastResult,'multicheck_${username}.json')"
                    class="btn btn-sm" style="background:var(--c-surface);border:1px solid var(--c-border);font-size:11px;padding:3px 10px;">
                    ⬇ JSON
                </button>
            </div>
        </div>`;

    // Ditemukan section
    if (found.length > 0) {
        html += `<div style="margin-bottom:8px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--c-text-3);">✓ Ditemukan (${found.length})</div>`;
        html += `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;margin-bottom:20px;">`;
        for (const [platform, data] of found) {
            html += buildCard(platform, data);
        }
        html += `</div>`;
    }

    // Tidak ada section
    if (notFound.length > 0) {
        html += `<div style="margin-bottom:8px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--c-text-3);">✗ Tidak Ditemukan (${notFound.length})</div>`;
        html += `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;">`;
        for (const [platform, data] of notFound) {
            html += buildCard(platform, data);
        }
        html += `</div>`;
    }

    html += `</div>`;
    panel.innerHTML = html;
}

// Badge warna per metode verifikasi
const METHOD_BADGE = {
    'API':     'background:#dbeafe;color:#1d4ed8;',
    'HTTP':    'background:#dcfce7;color:#166534;',
    'Content': 'background:#fef9c3;color:#854d0e;',
};

function methodBadge(method) {
    const style = METHOD_BADGE[method] || 'background:#f1f5f9;color:#475569;';
    return `<span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;${style}">${escHtml(method)}</span>`;
}

function buildCard(platform, data) {
    const isError    = !!data.error;
    const unreliable = !data.reliable;

    if (data.found) {
        const subtitle = unreliable
            ? `<div style="font-size:11px;color:#b45309;margin-top:3px;" title="Platform ini menggunakan bot-protection — hasil mungkin tidak akurat">⚠ Belum terverifikasi pasti</div>`
            : `<div style="font-size:11px;color:var(--c-success);margin-top:3px;">Akun ditemukan →</div>`;

        return `
        <a href="${escHtml(data.url)}" target="_blank" rel="noopener noreferrer" style="text-decoration:none;">
            <div class="card" style="padding:14px 16px;border-left:3px solid var(--c-success);cursor:pointer;transition:all .15s;"
                 onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(16,185,129,.15)'"
                 onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div class="flex-between" style="gap:6px;">
                    <span class="fw-6" style="font-size:13px;color:var(--c-text);">${escHtml(platform)}</span>
                    <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
                        ${methodBadge(data.method)}
                        <span style="font-size:15px;">✅</span>
                    </div>
                </div>
                ${subtitle}
            </div>
        </a>`;
    }

    // Not found
    const isBlocked = !!data.blocked;
    const subtitle = isError
        ? `<div style="font-size:11px;color:#ef4444;margin-top:3px;">Gagal terhubung</div>`
        : isBlocked
            ? `<div style="font-size:11px;color:#b45309;margin-top:3px;" title="Platform memblokir request bot (HTTP ${data.status})">⚠ Terblokir (HTTP ${data.status})</div>`
            : unreliable
                ? `<div style="font-size:11px;color:var(--c-text-3);margin-top:3px;">Tidak ada / tidak pasti</div>`
                : `<div style="font-size:11px;color:var(--c-text-3);margin-top:3px;">Tidak tersedia</div>`;

    const icon = isError ? '🔌' : isBlocked ? '⛔' : '❌';

    return `
    <div class="card" style="padding:14px 16px;border-left:3px solid var(--c-border);opacity:.75;">
        <div class="flex-between" style="gap:6px;">
            <span class="fw-6" style="font-size:13px;color:var(--c-text);">${escHtml(platform)}</span>
            <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
                ${(isError || isBlocked) ? '' : methodBadge(data.method)}
                <span style="font-size:15px;">${icon}</span>
            </div>
        </div>
        ${subtitle}
    </div>`;
}
</script>
<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
@endpush
