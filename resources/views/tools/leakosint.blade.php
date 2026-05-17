@extends('layouts.app')
@section('title', 'LeakOSINT')
@section('page-title', 'LeakOSINT')

@section('content')
<div class="page-header flex-between">
    <div>
        <h1>💧 LeakOSINT</h1>
        <p>Cari data breach — email, username, nomor telepon, atau kata kunci lain</p>
    </div>
    <a href="{{ route('history.leakosint') }}" class="btn btn-secondary btn-sm">🕐 Riwayat</a>
</div>

<div class="tool-input-card" style="max-width:620px;">
    <div class="form-group">
        <label class="form-label">
            Query Pencarian
            <span style="font-size:11px;font-weight:400;color:var(--c-text-3);margin-left:6px;">pisahkan baris baru untuk multi-query</span>
        </label>
        <textarea id="leak-query" class="form-control" rows="3"
            style="resize:vertical;font-family:var(--font-mono,monospace);font-size:13px;"
            placeholder="email@example.com&#10;username&#10;+628123456789"></textarea>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:14px;">
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Limit</label>
            <select id="leak-limit" class="form-control">
                @foreach([10,50,100,250,500,1000,5000,10000] as $l)
                <option value="{{ $l }}" {{ $l == 100 ? 'selected' : '' }}>{{ $l }} hasil</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Bahasa</label>
            <select id="leak-lang" class="form-control">
                <option value="en">English</option>
                <option value="ru">Russian</option>
                <option value="de">German</option>
                <option value="fr">French</option>
                <option value="es">Spanish</option>
                <option value="it">Italian</option>
                <option value="pt">Portuguese</option>
                <option value="zh">Chinese</option>
                <option value="ar">Arabic</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Bot Name <span style="font-weight:400;opacity:.6;">(opsional)</span></label>
            <input type="text" id="leak-bot" class="form-control" placeholder="@botname">
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <button id="btn-leak" onclick="doLeakSearch()" class="btn btn-primary" style="flex:1;">
            🔍 Cari Data Breach
        </button>
        <button onclick="clearLeak()" class="btn btn-secondary">Reset</button>
    </div>
</div>

{{-- Status bar --}}
<div id="leak-status" style="display:none;max-width:620px;margin-bottom:16px;padding:10px 16px;border-radius:8px;font-size:13px;display:flex;align-items:center;gap:10px;background:var(--c-surface);border:1px solid var(--c-border);">
    <div id="leak-status-dot" style="width:8px;height:8px;border-radius:50%;background:var(--c-text-3);flex-shrink:0;"></div>
    <span id="leak-status-text">Siap.</span>
</div>

{{-- Results panel --}}
<div id="leak-results" style="display:none;max-width:1100px;">
    <div style="padding:14px 20px;border:1px solid var(--c-border);border-bottom:none;border-radius:12px 12px 0 0;display:flex;align-items:center;gap:14px;flex-wrap:wrap;background:var(--c-surface);">
        <div style="font-weight:700;font-size:14px;">💧 Hasil Pencarian</div>
        <span class="badge badge-purple" id="badge-total">0 records</span>
        <span class="badge badge-green" id="badge-sources">0 sources</span>
        <div id="leak-meta" style="display:flex;gap:14px;font-size:12px;color:var(--c-text-3);"></div>
        <div style="margin-left:auto;display:flex;gap:8px;">
            <button id="btn-excel" onclick="exportExcel()" class="btn btn-secondary btn-sm" disabled>📥 Excel</button>
            <button id="btn-pdf"   onclick="exportPDF()"   class="btn btn-secondary btn-sm" disabled>📄 PDF</button>
        </div>
    </div>

    <div id="leak-table-container" style="border:1px solid var(--c-border);border-top:none;border-radius:0 0 12px 12px;"></div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"
        integrity="sha512-qZvrmS2ekKPF2mSznTQsxqPgnpkI4DNTlrdUmTzrDgektczlKNRRhy5X5AAOnx5S09ydFYWWNSfcEqDTTHgtNA=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"
        integrity="sha512-hAs6jGNBfwcZNNFxHECHGkn1gHK22R59JDJAz+zWMhD0rHTRs0e/1D0JBqFOSl+49RRdmNZd/TmPbRf51V9VA=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"
        integrity="sha512-r22gChDnGvBylk90+2e/ycr3RVrDi8DIOkIGNhJlKfuyQM4tIRAI062MaV8sfjQKYVGjOBaZBOA87z+IhZE9DA=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let allData = [], allColumns = [], dbMeta = {};

function setStatus(type, msg) {
    const dot = document.getElementById('leak-status-dot');
    const bar = document.getElementById('leak-status');
    bar.style.display = 'flex';
    dot.style.background = { loading:'#f59e0b', ready:'#22c55e', error:'#ef4444' }[type] || 'var(--c-text-3)';
    document.getElementById('leak-status-text').textContent = msg;
}

function clearLeak() {
    document.getElementById('leak-query').value = '';
    allData = []; allColumns = []; dbMeta = {};
    document.getElementById('leak-results').style.display = 'none';
    document.getElementById('leak-status').style.display = 'none';
    document.getElementById('btn-excel').disabled = true;
    document.getElementById('btn-pdf').disabled = true;
}

async function doLeakSearch() {
    const query   = document.getElementById('leak-query').value.trim();
    const limit   = parseInt(document.getElementById('leak-limit').value);
    const lang    = document.getElementById('leak-lang').value;
    const botName = document.getElementById('leak-bot').value.trim();

    if (!query) { setStatus('error', 'Error: Query pencarian wajib diisi.'); return; }

    const btn = document.getElementById('btn-leak');
    btn.disabled = true;
    setStatus('loading', 'Mengirim permintaan ke LeakOSINT API...');
    document.getElementById('leak-results').style.display = 'block';
    document.getElementById('leak-table-container').innerHTML = `
        <div style="padding:48px 24px;text-align:center;color:var(--c-text-3);">
            <div style="font-size:24px;margin-bottom:12px;animation:spin 1s linear infinite;display:inline-block;">⏳</div>
            <div style="font-size:13px;">Memproses pencarian melalui LeakOSINT API...</div>
        </div>`;

    const payload = { request: query, limit, lang };
    if (botName) payload.bot_name = botName;

    try {
        const resp = await fetch('{{ route("leakosint.query") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await resp.json();
        if (!resp.ok) throw new Error(data.error || `HTTP ${resp.status}`);

        processResults(data);
        setStatus('ready', `Selesai — ${allData.length} record dari ${Object.keys(dbMeta).length} sumber.`);
    } catch (err) {
        document.getElementById('leak-table-container').innerHTML = `
            <div style="padding:32px 24px;text-align:center;">
                <div style="font-size:32px;margin-bottom:12px;">⚠</div>
                <div style="font-size:13px;color:var(--c-text-3);">${escHtml(err.message)}</div>
            </div>`;
        setStatus('error', 'Error: ' + err.message);
    } finally {
        btn.disabled = false;
    }
}

function processResults(data) {
    allData = []; dbMeta = {};

    if (data.List && typeof data.List === 'object' && !Array.isArray(data.List)) {
        for (const [dbName, dbContent] of Object.entries(data.List)) {
            if (dbContent.InfoLeak) dbMeta[dbName] = dbContent.InfoLeak;
            if (dbContent.Data && Array.isArray(dbContent.Data)) {
                dbContent.Data.forEach(row => allData.push({ _source: dbName, ...flattenObj(row) }));
            }
        }
    }

    if (allData.length === 0 && (data.NumOfResults !== undefined || data.message)) {
        const summary = {};
        if (data.NumOfResults !== undefined) summary['Jumlah Hasil'] = data.NumOfResults;
        if (data.message) summary['Pesan'] = data.message;
        allData.push(summary);
    }

    const colSet = new Set(['_source']);
    for (const row of allData) for (const k of Object.keys(row)) if (k !== '_source') colSet.add(k);
    allColumns = [...colSet].filter(c => allData.some(r => r[c] !== undefined && r[c] !== ''));

    document.getElementById('badge-total').textContent   = (data.NumOfResults ?? allData.length).toLocaleString() + ' records';
    document.getElementById('badge-sources').textContent = Object.keys(dbMeta).length + ' sources';

    const metaItems = [];
    if (data.price !== undefined)             metaItems.push(`💳 ${data.price} credit`);
    if (data.free_requests_left !== undefined) metaItems.push(`🔄 Sisa: ${data.free_requests_left}`);
    if (data['search time'])                  metaItems.push(`⏱ ${data['search time']}s`);
    document.getElementById('leak-meta').innerHTML = metaItems.map(t => `<span>${t}</span>`).join('');

    renderTableCards();
    document.getElementById('btn-excel').disabled = false;
    document.getElementById('btn-pdf').disabled   = false;
}

function flattenObj(obj, prefix) {
    prefix = prefix || '';
    const result = {};
    for (const [k, v] of Object.entries(obj)) {
        const key = prefix ? prefix + '.' + k : k;
        if (v !== null && typeof v === 'object' && !Array.isArray(v)) Object.assign(result, flattenObj(v, key));
        else if (Array.isArray(v)) result[key] = v.join(', ');
        else result[key] = v;
    }
    return result;
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function renderTableCards() {
    const container = document.getElementById('leak-table-container');
    if (allData.length === 0) {
        container.innerHTML = `<div style="padding:48px 24px;text-align:center;color:var(--c-text-3);"><div style="font-size:32px;margin-bottom:8px;">—</div><div>Tidak ada data ditemukan.</div></div>`;
        return;
    }

    const grouped = {};
    allData.forEach(row => { if (!grouped[row._source]) grouped[row._source] = []; grouped[row._source].push(row); });

    let html = '';
    for (const [source, rows] of Object.entries(grouped)) {
        let cols = new Set();
        rows.forEach(r => Object.keys(r).forEach(k => { if (k !== '_source') cols.add(k); }));
        cols = [...cols];

        html += `<div style="margin-bottom:0;border-top:1px solid var(--c-border);">`;
        html += `<div style="padding:12px 20px;display:flex;justify-content:space-between;align-items:center;background:var(--c-surface);">
                   <div style="font-weight:700;color:var(--c-warning,#f59e0b);font-size:13.5px;text-transform:uppercase;letter-spacing:.05em;">🗄 ${escHtml(source || '—')}</div>
                   <span class="badge badge-purple">${rows.length} records</span>
                 </div>`;
        if (dbMeta[source]) {
            html += `<div style="padding:8px 20px;border-top:1px solid var(--c-border);font-size:11.5px;color:var(--c-text-3);line-height:1.6;background:var(--c-bg);border-left:3px solid var(--c-warning,#f59e0b);">${escHtml(dbMeta[source])}</div>`;
        }
        html += `<div style="overflow-x:auto;"><table style="width:100%;border-collapse:collapse;font-size:12.5px;">
                   <thead><tr style="background:var(--c-surface);border-bottom:1px solid var(--c-border);">
                     <th style="padding:9px 14px;text-align:center;font-size:10px;color:var(--c-text-3);text-transform:uppercase;width:36px;white-space:nowrap;">#</th>`;
        cols.forEach(col => {
            html += `<th style="padding:9px 14px;text-align:left;font-size:10px;color:var(--c-text-3);text-transform:uppercase;white-space:nowrap;">${escHtml(col)}</th>`;
        });
        html += `</tr></thead><tbody>`;
        rows.forEach((row, i) => {
            html += `<tr style="border-bottom:1px solid var(--c-border);" onmouseover="this.style.background='var(--c-surface)'" onmouseout="this.style.background='transparent'">
                       <td style="padding:8px 14px;color:var(--c-text-3);text-align:center;vertical-align:top;">${i+1}</td>`;
            cols.forEach(col => {
                const val = row[col] !== undefined ? String(row[col]) : '—';
                let style = 'color:var(--c-text);';
                if (/^(email|phone|password|hash|pass|pwd|pw)$/i.test(col)) style = 'color:#14b8a6;font-weight:600;';
                html += `<td style="padding:8px 14px;white-space:normal;word-break:break-word;min-width:120px;max-width:280px;vertical-align:top;${style}">${escHtml(val)}</td>`;
            });
            html += `</tr>`;
        });
        html += `</tbody></table></div></div>`;
    }
    container.innerHTML = html;
}

function exportExcel() {
    if (!allData.length) return;
    const headers = allColumns.map(c => c === '_source' ? 'DATABASE SOURCE' : c.toUpperCase().replace(/[._]/g,' '));
    const rows    = allData.map(row => allColumns.map(col => row[col] !== undefined ? row[col] : ''));
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
    ws['!cols'] = allColumns.map(() => ({ wch: 22 }));
    XLSX.utils.book_append_sheet(wb, ws, 'LeakOSINT Results');
    const meta = [
        ['LeakOSINT Search Export'],
        ['Tanggal', new Date().toLocaleString('id-ID')],
        ['Query', document.getElementById('leak-query').value.trim()],
        ['Total Record', allData.length],
        ['Sumber Database', Object.keys(dbMeta).length],
    ];
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(meta), 'Info');
    XLSX.writeFile(wb, 'leakosint_' + new Date().toISOString().slice(0,10) + '.xlsx');
}

function exportPDF() {
    if (!allData.length) return;
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
    const query = document.getElementById('leak-query').value.trim().replace(/\n/g,' | ');
    const today = new Date().toLocaleDateString('id-ID', { day:'2-digit', month:'long', year:'numeric' });

    doc.setFontSize(14); doc.setFont('helvetica','bold');
    doc.text('LeakOSINT Search Report', 14, 13);
    doc.setFontSize(8); doc.setFont('helvetica','normal'); doc.setTextColor(100,116,128);
    doc.text(`Tanggal: ${today}  |  Query: ${query}  |  Total: ${allData.length} records`, 14, 21);

    const headers = allColumns.map(c => c === '_source' ? 'DATABASE SOURCE' : c.toUpperCase().replace(/[._]/g,' '));
    const rows    = allData.map(row => allColumns.map(col => {
        const v = row[col] !== undefined ? String(row[col]) : '';
        return v.length > 60 ? v.slice(0,57) + '...' : v;
    }));

    doc.autoTable({
        head: [headers], body: rows, startY: 26,
        styles: { font:'courier', fontSize:7, cellPadding:2.5 },
        headStyles: { fillColor:[79,70,229], textColor:[255,255,255], fontStyle:'bold' },
        alternateRowStyles: { fillColor:[248,247,255] },
        margin: { left:14, right:14 },
        didDrawPage: (d) => {
            doc.setFontSize(7); doc.setTextColor(150);
            doc.text(`LeakOSINT Export — Halaman ${d.pageNumber}  |  ${today}`, 14, doc.internal.pageSize.height - 6);
        }
    });
    doc.save('leakosint_' + new Date().toISOString().slice(0,10) + '.pdf');
}
</script>
<style>
@keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
</style>
@endpush
