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

<div class="tool-input-card" style="max-width:580px;">
    <form method="POST" action="{{ route('leakosint.search') }}">
        @csrf
        <div class="form-group">
            <label class="form-label">Query Pencarian</label>
            <input type="text" name="query"
                class="form-control {{ $errors->has('query') ? 'is-invalid' : '' }}"
                value="{{ old('query') }}"
                placeholder="email@example.com, username, nomor telepon..."
                autofocus>
            @error('query')<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="grid-2" style="gap:12px;margin-bottom:14px;">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Limit Hasil</label>
                <select name="limit" class="form-control">
                    @foreach([10,50,100,250,500,1000] as $l)
                    <option value="{{ $l }}" {{ old('limit', 100) == $l ? 'selected' : '' }}>{{ $l }} hasil</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Bahasa</label>
                <select name="lang" class="form-control">
                    <option value="en">English</option>
                    <option value="ru">Russian</option>
                    <option value="de">German</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-full">
            🔍 Cari Data Breach
        </button>
    </form>
</div>

@isset($status)
@if($status === 'failed')
<div class="alert alert-error animate-fade-up" style="max-width:580px;">
    ⚠ {{ $error }}
</div>
@elseif(!empty($data))
<div class="animate-fade-up">
    {{-- Export buttons --}}
    <div class="flex-between mb-4">
        <div class="fw-6" style="font-size:14px;">
            💧 Hasil Pencarian
            <span class="badge badge-purple" style="margin-left:6px;">{{ count($data) }} database</span>
        </div>
        <div class="flex gap-2">
            <button onclick="exportExcel()" class="btn btn-secondary btn-sm">📥 Export Excel</button>
            <button onclick="exportPDF()" class="btn btn-secondary btn-sm">📄 Export PDF</button>
        </div>
    </div>

    @foreach($data as $dbName => $dbData)
    @if(is_array($dbData) && isset($dbData['Data']))
    <div class="card mb-4" id="db-{{ Str::slug($dbName) }}">
        <div class="card-header">
            <h3 style="font-size:13.5px;">🗄 {{ $dbName }}</h3>
            <span class="badge badge-purple">{{ count($dbData['Data']) }} records</span>
        </div>
        <div class="table-wrapper" style="border:none;border-radius:0;border-top:1px solid var(--c-border);">
            <table class="data-table" id="table-{{ Str::slug($dbName) }}">
                <thead>
                    <tr>
                        @foreach(array_keys($dbData['Data'][0] ?? []) as $col)
                        <th>{{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($dbData['Data'] as $row)
                    <tr>
                        @foreach($row as $val)
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;">
                            {{ is_array($val) ? json_encode($val) : $val }}
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    @endforeach
</div>

<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script>
function exportExcel() {
    const wb = XLSX.utils.book_new();
    document.querySelectorAll('table[id^="table-"]').forEach(function(tbl) {
        const ws = XLSX.utils.table_to_sheet(tbl);
        XLSX.utils.book_append_sheet(wb, ws, tbl.id.replace('table-','').substring(0,31));
    });
    XLSX.writeFile(wb, 'leakosint-results.xlsx');
}
function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation:'landscape' });
    let y = 14;
    document.querySelectorAll('table[id^="table-"]').forEach(function(tbl, i) {
        if(i > 0) { doc.addPage(); y = 14; }
        doc.autoTable({ html: tbl, startY: y, styles:{ fontSize: 7 } });
    });
    doc.save('leakosint-results.pdf');
}
</script>
@else
<div class="alert alert-info animate-fade-up" style="max-width:580px;">
    ℹ Tidak ada hasil ditemukan untuk query tersebut.
</div>
@endif
@endisset
@endsection
