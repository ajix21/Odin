@extends('layouts.app')
@section('title', 'Instagram OSINT')
@section('page-title', 'Instagram OSINT')

@section('content')
<div class="page-header">
    <h1>📸 Instagram OSINT (Toutatis)</h1>
    <p>Deep OSINT pada akun Instagram — profil, kontak, statistik, dan postingan terbaru</p>
</div>

<div class="tool-input-card" style="max-width:480px;">
    <div class="form-group" style="margin-bottom:14px;">
        <label class="form-label">Username Instagram</label>
        <div style="position:relative;">
            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--c-text-3);font-size:13px;">@</span>
            <input type="text" id="tou-username"
                class="form-control"
                style="padding-left:26px;"
                placeholder="username"
                autofocus
                onkeydown="if(event.key==='Enter') doLookup()">
        </div>
    </div>
    <button id="btn-tou" onclick="doLookup()" class="btn btn-primary btn-full">📸 Lookup Instagram</button>
</div>

{{-- Status bar --}}
<div id="tou-status" style="display:none;max-width:480px;margin-bottom:16px;padding:10px 16px;border-radius:8px;font-size:13px;align-items:center;gap:10px;background:var(--c-surface);border:1px solid var(--c-border);">
    <div id="tou-status-dot" style="width:8px;height:8px;border-radius:50%;background:var(--c-text-3);flex-shrink:0;"></div>
    <span id="tou-status-text">Siap.</span>
</div>

{{-- Results panel --}}
<div id="tou-results" style="display:none;max-width:820px;"></div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let touLastResult = null;

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
    const bar = document.getElementById('tou-status');
    bar.style.display = 'flex';
    document.getElementById('tou-status-dot').style.background = colors[type] || 'var(--c-text-3)';
    document.getElementById('tou-status-text').textContent = msg;
}

function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function fmtNum(n) {
    return Number(n || 0).toLocaleString('id-ID');
}

async function doLookup() {
    const raw      = document.getElementById('tou-username').value.trim();
    const username = raw.replace(/^@/, '');
    if (!username) { setStatus('error', 'Error: Username wajib diisi.'); return; }
    if (!/^[a-zA-Z0-9._]+$/.test(username)) {
        setStatus('error', 'Error: Username hanya boleh huruf, angka, titik, dan underscore.');
        return;
    }

    const btn = document.getElementById('btn-tou');
    btn.disabled = true;
    setStatus('loading', `Mencari profil @${username}...`);

    const panel = document.getElementById('tou-results');
    panel.style.display = 'block';
    panel.innerHTML = `
        <div style="padding:48px 24px;text-align:center;color:var(--c-text-3);">
            <div style="font-size:24px;margin-bottom:12px;animation:spin 1s linear infinite;display:inline-block;">⏳</div>
            <div style="font-size:13px;">Mengambil data Instagram untuk @${escHtml(username)}...</div>
        </div>`;

    try {
        const resp = await fetch('{{ route("toutatis.lookup") }}', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body:    JSON.stringify({ username }),
        });
        const data = await resp.json();
        touLastResult = data;
        renderResult(data);
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

function renderResult(r) {
    const panel = document.getElementById('tou-results');

    if (!r.success) {
        const isRl = !!r.rate_limited;
        panel.innerHTML = `
            <div class="alert ${isRl ? 'alert-warning' : 'alert-error'}">
                ${isRl ? '⏳' : '⚠'} ${escHtml(r.error)}
                ${isRl ? '<div style="margin-top:6px;font-size:12px;opacity:.85;">Tunggu 1–2 menit lalu coba lagi, atau perbarui Session ID di Settings.</div>' : ''}
            </div>`;
        setStatus('error', r.error);
        return;
    }

    let html = '<div class="animate-fade-up">';

    if (r.source === 'public') {
        html += `<div class="alert alert-warning" style="margin-bottom:16px;">
            🌐 <strong>Data Publik (Terbatas)</strong> — Instagram membatasi akses API saat ini.
            Bio, link, dan foto tidak tersedia. Tunggu beberapa menit lalu coba lagi, atau perbarui Session ID di
            <a href="{{ route('settings') }}" style="color:inherit;font-weight:600;">Settings</a>.
        </div>`;
    }

    html += buildProfileCard(r);

    if (r.recent_posts && r.recent_posts.length > 0) {
        html += buildPostsCard(r.recent_posts, r.username);
    } else if (!r.is_private) {
        html += `<div class="alert alert-info">ℹ Tidak ada postingan yang dapat diambil (akun kosong atau data publik terbatas).</div>`;
    }

    html += `<div style="margin-top:12px;text-align:right;">
        <button onclick="downloadJson(touLastResult,'toutatis_${r.username}.json')"
            class="btn btn-sm" style="background:var(--c-surface);border:1px solid var(--c-border);">
            ⬇ Download JSON
        </button>
    </div>`;
    html += '</div>';
    panel.innerHTML = html;
    setStatus('ready', `Profil @${escHtml(r.username)} berhasil dimuat (sumber: ${r.source})`);
}

function buildProfileCard(r) {
    const avatar = r.profile_pic
        ? `<img src="${escHtml(r.profile_pic)}" alt="Profile" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid white;box-shadow:0 2px 12px rgba(0,0,0,.15);">`
        : `<div style="width:80px;height:80px;border-radius:50%;background:#e879a0;display:flex;align-items:center;justify-content:center;font-size:32px;">📸</div>`;

    let badges = r.is_private
        ? `<span class="badge badge-yellow">🔒 Private</span>`
        : `<span class="badge badge-green">🔓 Public</span>`;
    if (r.is_business)   badges += `<span class="badge badge-blue">💼 Business</span>`;
    if (r.account_type)  badges += `<span class="badge" style="background:#f1f5f9;color:#475569;">${escHtml(r.account_type)}</span>`;
    if (r.category)      badges += `<span class="badge badge-purple">${escHtml(r.category)}</span>`;
    if (r.source === 'public') badges += `<span class="badge" style="background:#fef3c7;color:#92400e;font-size:11px;">🌐 Data Publik</span>`;

    let extUrl = '';
    if (r.external_url) {
        const safe = /^https?:\/\//i.test(r.external_url);
        extUrl = safe
            ? `<div style="font-size:13px;margin-top:4px;">🔗 <a href="${escHtml(r.external_url)}" target="_blank" rel="noopener noreferrer" style="color:var(--c-blue-500);">${escHtml(r.external_url)}</a></div>`
            : `<div style="font-size:13px;margin-top:4px;">🔗 <span style="color:var(--c-warning);">${escHtml(r.external_url)}</span></div>`;
    }

    const stats = [
        { val: r.posts,      lbl: 'Posts' },
        { val: r.followers,  lbl: 'Followers' },
        { val: r.following,  lbl: 'Following' },
        { val: r.highlights, lbl: 'Highlights' },
    ].map(s => `
        <div style="text-align:center;padding:10px 14px;background:white;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.06);min-width:60px;">
            <div style="font-weight:700;font-size:17px;font-family:'Sora',sans-serif;">${fmtNum(s.val)}</div>
            <div style="font-size:11px;color:var(--c-text-3);">${escHtml(s.lbl)}</div>
        </div>`).join('');

    const contactMap = [
        ['Email Publik',   r.email],
        ['Telepon Publik', r.phone],
        ['Email (Reg.)',   r.obfuscated_email],
        ['Telepon (Reg.)', r.obfuscated_phone],
        ['Alamat',         r.address],
    ];
    const contactItems = contactMap
        .filter(([, v]) => v)
        .map(([k, v]) => `<div style="font-size:12.5px;"><span style="color:var(--c-text-3);">${escHtml(k)}:</span> <span class="font-mono" style="color:var(--c-text);">${escHtml(v)}</span></div>`)
        .join('');
    const coordRow = (r.lat && r.lng)
        ? `<div style="font-size:12.5px;"><span style="color:var(--c-text-3);">Koordinat:</span> <span class="font-mono">${escHtml(String(r.lat))}, ${escHtml(String(r.lng))}</span></div>`
        : '';
    const contactSection = (contactItems || coordRow)
        ? `<div style="padding:12px 20px;border-top:1px solid var(--c-border);display:flex;flex-wrap:wrap;gap:6px 20px;background:var(--c-surface);border-radius:0 0 12px 12px;">${contactItems}${coordRow}</div>`
        : '';

    return `
    <div class="card mb-4">
        <div style="padding:20px;display:flex;gap:18px;align-items:flex-start;background:linear-gradient(135deg,#fdf2f8,#fce7f3);border-radius:12px 12px 0 0;flex-wrap:wrap;">
            <div style="flex-shrink:0;">${avatar}</div>
            <div style="flex:1;min-width:200px;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span style="font-weight:700;font-size:18px;">${escHtml(r.full_name || r.username)}</span>
                    ${r.is_verified ? '<span style="color:#2563EB;font-size:16px;" title="Verified">✓</span>' : ''}
                </div>
                <div style="font-size:13px;color:var(--c-text-3);margin-bottom:8px;">
                    <a href="https://www.instagram.com/${escHtml(r.username)}/" target="_blank" rel="noopener noreferrer" style="color:var(--c-blue-500);text-decoration:none;">@${escHtml(r.username)}</a>
                    ${r.id ? `<span style="margin-left:6px;font-family:monospace;font-size:11px;opacity:.6;">ID: ${escHtml(String(r.id))}</span>` : ''}
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px;">${badges}</div>
                ${r.bio ? `<div style="font-size:13.5px;white-space:pre-line;color:var(--c-text);line-height:1.5;max-width:460px;margin-bottom:6px;">${escHtml(r.bio)}</div>` : ''}
                ${r.pronouns ? `<div style="font-size:12px;color:var(--c-text-3);margin-top:4px;">${escHtml(r.pronouns)}</div>` : ''}
                ${extUrl}
            </div>
            <div style="display:flex;gap:12px;flex-shrink:0;flex-wrap:wrap;">${stats}</div>
        </div>
        ${contactSection}
    </div>`;
}

function buildPostsCard(posts, username) {
    const grid = posts.map((p, i) => {
        const border = i === 1 ? 'border-left:1px solid var(--c-border);border-right:1px solid var(--c-border);' : '';
        const img = p.image
            ? `<img src="${escHtml(p.image)}" alt="Post" style="width:100%;height:100%;object-fit:cover;display:block;">`
            : `<div style="width:100%;height:100%;background:var(--c-bg);display:flex;align-items:center;justify-content:center;font-size:32px;">${p.is_video ? '🎬' : '🖼️'}</div>`;

        const typeIcon = p.is_video ? '🎬' : (p.type === 'GraphSidecar' ? '⊞' : '');

        let ts = '';
        if (p.timestamp) {
            const d = new Date(p.timestamp * 1000);
            ts = `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`;
        }

        return `
        <div style="position:relative;aspect-ratio:1;overflow:hidden;${border}">
            ${img}
            <a href="${escHtml(p.url || '#')}" target="_blank" rel="noopener noreferrer"
               style="position:absolute;inset:0;background:rgba(0,0,0,0);display:flex;flex-direction:column;justify-content:flex-end;padding:10px;text-decoration:none;transition:background .2s;"
               onmouseover="this.style.background='rgba(0,0,0,.55)';this.querySelector('.po').style.opacity=1"
               onmouseout="this.style.background='rgba(0,0,0,0)';this.querySelector('.po').style.opacity=0">
                <div class="po" style="opacity:0;transition:opacity .2s;">
                    <div style="color:white;font-size:12px;display:flex;gap:12px;margin-bottom:4px;">
                        <span>❤️ ${fmtNum(p.likes)}</span>
                        <span>💬 ${fmtNum(p.comments)}</span>
                        ${p.is_video ? '<span>🎬 Video</span>' : ''}
                        ${p.type === 'GraphSidecar' ? '<span>⊞ Album</span>' : ''}
                    </div>
                    ${p.caption ? `<div style="color:rgba(255,255,255,.9);font-size:11px;line-height:1.4;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">${escHtml(p.caption)}</div>` : ''}
                </div>
            </a>
            ${typeIcon ? `<div style="position:absolute;top:8px;right:8px;color:white;font-size:16px;text-shadow:0 1px 3px rgba(0,0,0,.6);">${typeIcon}</div>` : ''}
            ${ts ? `<div style="position:absolute;bottom:6px;left:8px;color:rgba(255,255,255,.85);font-size:10.5px;text-shadow:0 1px 3px rgba(0,0,0,.7);">${escHtml(ts)}</div>` : ''}
        </div>`;
    }).join('');

    const captions = posts.map((p, i) => {
        if (!p.caption) return '';
        const border = i < posts.length - 1 ? 'border-bottom:1px solid var(--c-border);' : '';
        return `
        <div style="padding:10px 16px;${border}display:flex;gap:10px;align-items:flex-start;">
            <span style="font-size:12px;color:var(--c-text-3);flex-shrink:0;padding-top:1px;">${i + 1}.</span>
            <div style="font-size:12.5px;color:var(--c-text);line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">${escHtml(p.caption)}</div>
            <div style="flex-shrink:0;font-size:11.5px;color:var(--c-text-3);white-space:nowrap;">❤️ ${fmtNum(p.likes)} &nbsp; 💬 ${fmtNum(p.comments)}</div>
        </div>`;
    }).join('');

    return `
    <div class="card">
        <div class="card-header">
            <h3>🖼️ Postingan Terbaru</h3>
            <a href="https://www.instagram.com/${escHtml(username)}/" target="_blank" rel="noopener noreferrer"
               class="btn btn-secondary btn-sm" style="font-size:12px;">Buka Profil ↗</a>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0;border-top:1px solid var(--c-border);">${grid}</div>
        ${captions ? `<div style="border-top:1px solid var(--c-border);">${captions}</div>` : ''}
    </div>`;
}
</script>
<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
@endpush
