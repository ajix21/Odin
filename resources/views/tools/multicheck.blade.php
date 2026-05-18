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
            <div class="flex gap-2" style="flex-wrap:wrap;">
                <span class="badge badge-green">✓ ${found.length} Ditemukan</span>
                <span class="badge badge-gray">✗ ${notFound.length} Tidak Ada</span>
                <span class="badge" style="background:#fef3c7;color:#92400e;font-size:11px;" title="Platform dengan tanda ⚠ menggunakan bot-protection — hasil mungkin tidak akurat">⚠ ${entries.filter(([,d])=>!d.reliable).length} Tidak Pasti</span>
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

function buildCard(platform, data) {
    const unreliable = !data.reliable;
    const isError    = !!data.error;

    if (data.found) {
        const reliabilityNote = unreliable
            ? `<div style="font-size:10px;color:#92400e;margin-top:2px;" title="Platform ini menggunakan bot-protection, hasil mungkin tidak akurat">⚠ Belum terverifikasi</div>`
            : `<div style="font-size:10px;color:var(--c-text-3);margin-top:2px;">HTTP ${data.status}</div>`;

        return `
        <a href="${escHtml(data.url)}" target="_blank" rel="noopener noreferrer" style="text-decoration:none;">
            <div class="card" style="padding:14px 16px;border-left:3px solid var(--c-success);cursor:pointer;transition:all .15s;"
                 onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(16,185,129,.15)'"
                 onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div class="flex-between">
                    <span class="fw-6" style="font-size:13px;color:var(--c-text);">${escHtml(platform)}</span>
                    <span style="font-size:16px;">✅</span>
                </div>
                <div style="font-size:11px;color:var(--c-success);margin-top:4px;">Akun ditemukan →</div>
                ${reliabilityNote}
            </div>
        </a>`;
    }

    // Not found
    const note = isError
        ? `<div style="font-size:11px;color:#ef4444;margin-top:4px;">Gagal terhubung</div>`
        : unreliable
            ? `<div style="font-size:11px;color:var(--c-text-3);margin-top:4px;">Tidak ada / tidak pasti</div>`
            : `<div style="font-size:11px;color:var(--c-text-3);margin-top:4px;">Tidak tersedia</div>`;

    return `
    <div class="card" style="padding:14px 16px;border-left:3px solid var(--c-border);opacity:.75;">
        <div class="flex-between">
            <span class="fw-6" style="font-size:13px;color:var(--c-text);">${escHtml(platform)}</span>
            <span style="font-size:16px;">${isError ? '🔌' : '❌'}</span>
        </div>
        ${note}
    </div>`;
}
</script>
<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
@endpush
