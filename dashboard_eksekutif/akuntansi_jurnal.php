<?php
/**
 * akuntansi_jurnal.php
 * Jurnal Harian — Modul Accounting.
 * FIXES v2:
 *   - Hapus double-binding input event (penyebab hang)
 *   - Tambah drill-down modal saat klik baris → detail per no_jurnal
 *   - Tambah collapsible group per tanggal (accordion effect)
 */
$page_title = "Jurnal Harian";
require_once('includes/header.php');
?>
<style>
.accounting-header {
    background: linear-gradient(135deg,rgba(16,185,129,.15),rgba(59,130,246,.15));
    border:1px solid rgba(16,185,129,.3); border-radius:16px;
    padding:20px 24px; margin-bottom:24px; display:flex; align-items:center; gap:16px;
}
.accounting-header .icon-box {
    width:52px;height:52px;
    background:linear-gradient(135deg,#10b981,#059669);border-radius:14px;
    display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#fff;
    box-shadow:0 4px 15px rgba(16,185,129,.4);flex-shrink:0;
}
.accounting-header h1{font-size:1.4rem;font-weight:700;margin:0}
.accounting-header p{font-size:.82rem;margin:0;opacity:.7}

.filter-glass{
    background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);
    border-radius:14px;padding:18px 20px;margin-bottom:20px;backdrop-filter:blur(8px);
}
.kpi-bar{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:20px}
.kpi-card{background:rgba(30,41,59,.7);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:14px 18px;text-align:center;transition:transform .2s}
.kpi-card:hover{transform:translateY(-2px)}
.kpi-card .kpi-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;opacity:.6;margin-bottom:4px}
.kpi-card .kpi-value{font-size:1.2rem;font-weight:700}
.kpi-card.debet  .kpi-value{color:#38bdf8}
.kpi-card.kredit .kpi-value{color:#4ade80}
.kpi-card.balance .kpi-value{color:#f59e0b}

/* Rek chip */
.rek-chip{display:inline-flex;align-items:center;gap:6px;background:rgba(56,189,248,.15);border:1px solid rgba(56,189,248,.3);border-radius:20px;padding:4px 10px;font-size:.8rem;cursor:pointer;transition:all .2s}
.rek-chip:hover{background:rgba(56,189,248,.25)}
.rek-chip .rek-code{font-weight:700;color:#38bdf8}

/* Tabel jurnal */
.tbl-jurnal thead th{
    background:linear-gradient(135deg,rgba(16,185,129,.3),rgba(59,130,246,.2))!important;
    color:#e2e8f0!important;font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;
    border-color:rgba(255,255,255,.1)!important;white-space:nowrap;
}
.tbl-jurnal .num-right{text-align:right;font-variant-numeric:tabular-nums}
/* Baris grup per tanggal */
.tr-date-group{
    background:rgba(16,185,129,.12)!important;cursor:pointer;user-select:none;
}
.tr-date-group td{font-weight:700;font-size:.82rem;color:#4ade80!important;padding:8px 12px!important}
.tr-date-group:hover{background:rgba(16,185,129,.2)!important}
.tr-date-group .toggle-icon{transition:transform .25s;display:inline-block}
.tr-date-group.collapsed .toggle-icon{transform:rotate(-90deg)}
/* Baris data */
.tr-entry{transition:background .15s;cursor:pointer}
.tr-entry:hover{background:rgba(56,189,248,.06)!important}
.tr-entry.selected{background:rgba(56,189,248,.12)!important}
/* Baris total per tanggal */
.tr-date-total td{font-weight:700;background:rgba(16,185,129,.06)!important;color:#34d399!important;border-top:1px solid rgba(16,185,129,.2)!important;font-size:.82rem}
/* Loading */
.tbl-loading{text-align:center;padding:60px 20px;opacity:.5}

/* Drill-down modal */
#detail-modal .modal-content{max-height:90vh;overflow-y:auto}
.detail-header-card{background:rgba(56,189,248,.08);border:1px solid rgba(56,189,248,.2);border-radius:10px;padding:12px 16px;margin-bottom:12px;font-size:.85rem}
.detail-header-card .d-label{font-size:.7rem;text-transform:uppercase;opacity:.6;letter-spacing:.05em}
.detail-header-card .d-value{font-weight:600;color:#e2e8f0}
.badge-balanced{background:rgba(16,185,129,.2);border:1px solid rgba(16,185,129,.4);color:#4ade80;border-radius:6px;padding:3px 10px;font-size:.75rem;font-weight:700}
.badge-unbalanced{background:rgba(239,68,68,.2);border:1px solid rgba(239,68,68,.4);color:#f87171;border-radius:6px;padding:3px 10px;font-size:.75rem;font-weight:700}
.tbl-detail thead th{background:rgba(56,189,248,.15)!important;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em}
.tbl-detail .debet-val{color:#38bdf8;font-weight:600}
.tbl-detail .kredit-val{color:#4ade80;font-weight:600}

.export-bar{display:flex;gap:8px;align-items:center;flex-wrap:wrap}

/* CSS DataTables RowGroup Customization */
@import url('https://cdn.datatables.net/rowgroup/1.4.0/css/rowGroup.bootstrap5.min.css');

/* Beautifikasi Kolom Finansial */
table.dataTable tbody td.col-debet { background-color: rgba(56, 189, 248, 0.04) !important; font-family: 'Consolas', 'Courier New', monospace; font-size: 0.85rem; font-weight: 600; color: #38bdf8; }
table.dataTable tbody td.col-kredit { background-color: rgba(74, 222, 128, 0.04) !important; font-family: 'Consolas', 'Courier New', monospace; font-size: 0.85rem; font-weight: 600; color: #4ade80; }
table.dataTable tbody tr:hover td.col-debet { background-color: rgba(56, 189, 248, 0.09) !important; }
table.dataTable tbody tr:hover td.col-kredit { background-color: rgba(74, 222, 128, 0.09) !important; }
.text-muted-zero { opacity: 0.3; font-weight: 400; color: #94a3b8 !important; }
</style>

<!-- PAGE HEADER -->
<div class="accounting-header">
    <div class="icon-box"><i class="fas fa-book-open"></i></div>
    <div>
        <h1>Jurnal Harian</h1>
        <p>Catatan transaksi keuangan harian · Klik baris untuk melihat detail per jurnal</p>
    </div>
</div>

<!-- FILTER -->
<div class="filter-glass">
    <div class="row g-3 align-items-end">
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Dari Tanggal</label>
            <input type="date" id="inp-tgl1" class="form-control form-control-sm" value="<?php echo date('Y-m-01'); ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Sampai Tanggal</label>
            <input type="date" id="inp-tgl2" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">No. Jurnal</label>
            <input type="text" id="inp-nojur" class="form-control form-control-sm" placeholder="Kosongkan = semua" maxlength="8">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Filter Rekening</label>
            <div id="rek-chip-wrap" class="rek-chip" id="rek-chip-btn">
                <i class="fas fa-search-dollar text-info" style="font-size:.8rem"></i>
                <span id="rek-chip-text" class="text-muted">Semua Rekening</span>
            </div>
            <input type="hidden" id="inp-kdrek">
            <input type="hidden" id="inp-nmrek">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Keyword</label>
            <input type="text" id="inp-keyword" class="form-control form-control-sm" placeholder="No.Bukti / Keterangan..." maxlength="100">
        </div>
    </div>
    <div class="row mt-3">
        <div class="col">
            <div class="export-bar">
                <button id="btn-load" class="btn btn-success btn-sm px-4">
                    <i class="fas fa-search me-1"></i> Tampilkan Data
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="btn-reset">
                    <i class="fas fa-undo me-1"></i> Reset
                </button>
                <!-- Fitur expand/collapse dihapus -->
                <div class="ms-auto export-bar">
                    <button class="btn btn-outline-info btn-sm" id="btn-export">
                        <i class="fas fa-file-excel me-1"></i> Export CSV
                    </button>
                    <button class="btn btn-outline-danger btn-sm" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- KPI BAR -->
<div class="kpi-bar" id="kpi-bar" style="display:none">
    <div class="kpi-card debet">
        <div class="kpi-label"><i class="fas fa-arrow-down me-1"></i>Total Debet</div>
        <div class="kpi-value" id="kpi-debet">Rp 0</div>
    </div>
    <div class="kpi-card kredit">
        <div class="kpi-label"><i class="fas fa-arrow-up me-1"></i>Total Kredit</div>
        <div class="kpi-value" id="kpi-kredit">Rp 0</div>
    </div>
    <div class="kpi-card balance">
        <div class="kpi-label"><i class="fas fa-balance-scale me-1"></i>Selisih D-K</div>
        <div class="kpi-value" id="kpi-balance">Rp 0</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label"><i class="fas fa-list me-1"></i>Jumlah Jurnal</div>
        <div class="kpi-value" id="kpi-entri" style="color:#e2e8f0">0</div>
    </div>
</div>

<!-- TABLE -->
<div class="card shadow-sm" id="card-table" style="display:none">
    <div class="card-header py-2 d-flex align-items-center justify-content-between">
        <h6 class="m-0 fw-bold" style="color:#38bdf8"><i class="fas fa-table me-2"></i>Data Jurnal Harian</h6>
        <small class="text-muted" id="tbl-info-text"></small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 tbl-jurnal" id="tbl-jurnal">
                <thead>
                    <tr>
                        <th style="width:36px"></th>
                        <th style="width:90px">Tanggal</th>
                        <th style="width:80px">No. Jurnal</th>
                        <th style="width:90px">No. Bukti</th>
                        <th style="width:80px">Kode Akun</th>
                        <th style="width:240px">Nama Akun</th>
                        <th>Keterangan</th>
                        <th class="num-right" style="width:130px">Debet (Rp)</th>
                        <th class="num-right" style="width:130px">Kredit (Rp)</th>
                    </tr>
                </thead>
                <tbody id="tbl-body">
                    <tr><td colspan="9" class="tbl-loading">
                        <i class="fas fa-info-circle me-2"></i>Atur filter dan klik Tampilkan Data
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- REKENING SELECTOR MODAL -->
<div class="modal fade" id="rek-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-search-dollar me-2 text-info"></i>Pilih Rekening</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-2">
                <input type="text" id="rek-search" class="form-control form-control-sm mb-2" placeholder="Ketik kode atau nama rekening...">
                <div style="max-height:400px;overflow-y:auto">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>Kode</th><th>Nama Rekening</th><th>Tipe</th><th>Balance</th><th class="text-end">Saldo Awal</th></tr></thead>
                        <tbody id="rek-tbl-body"><tr><td colspan="5" class="text-center text-muted py-4">Ketik untuk mencari rekening...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-danger btn-sm" id="btn-clear-rek"><i class="fas fa-times me-1"></i>Hapus Filter</button>
            </div>
        </div>
    </div>
</div>

<!-- DRILL-DOWN DETAIL MODAL -->
<div class="modal fade" id="detail-modal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detail-modal-title">
                    <i class="fas fa-search me-2 text-info"></i>Detail Jurnal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detail-modal-body">
                <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-info"></div> Memuat...</div>
            </div>
            <div class="modal-footer py-2 gap-2 flex-wrap">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <button class="btn btn-outline-warning btn-sm" id="btn-trace-bukti" onclick="openTraceBukti()" style="display:none">
                    <i class="fas fa-route me-1"></i>Trace No.Bukti
                </button>
                <button class="btn btn-outline-info btn-sm" onclick="exportDetailCSV()"><i class="fas fa-file-excel me-1"></i>Export</button>
            </div>
        </div>
    </div>
</div>
<?php ob_start(); ?>
<!-- DataTables RowGroup Plugin — dipasang sebelum script utama -->
<script src="https://cdn.datatables.net/rowgroup/1.4.0/js/dataTables.rowGroup.min.js"></script>

<script>
/* ============================================================
   Jurnal Harian v3 — DataTables + Event Delegation (Clean)
   ============================================================ */
var _allData     = [];
var _lastDetail  = null;
var _rekModal    = null;
var _detailModal = null;
var dataTableInst = null;

/* ─── Format Rupiah ─── */
function fRp(v) {
    if (v === null || v === undefined || v === '') return '-';
    var neg = v < 0, abs = Math.abs(Math.round(v));
    var s = abs.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return neg ? '(Rp ' + s + ')' : 'Rp ' + s;
}

function formatKeterangan(ket) {
    if (!ket) return '-';
    return ket;
}

/* ─── Rekening Modal ─── */
function openRekeningModal() {
    if (!_rekModal) _rekModal = new bootstrap.Modal(document.getElementById('rek-modal'));
    document.getElementById('rek-search').value = '';
    cariRekening('');
    _rekModal.show();
    setTimeout(function(){ document.getElementById('rek-search').focus(); }, 350);
}

function cariRekening(q) {
    fetch('api/akuntansi_cari_rekening.php?q=' + encodeURIComponent(q) + '&thn=' + new Date().getFullYear())
        .then(function(r){ return r.json(); })
        .then(function(d) {
            var tb = document.getElementById('rek-tbl-body');
            if (!d.success || !d.results.length) {
                tb.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Tidak ditemukan.</td></tr>';
                return;
            }
            tb.innerHTML = d.results.map(function(r) {
                return '<tr onclick="pilihRekening(\'' + r.kd_rek + '\',\'' + r.nm_rek.replace(/\\/g,'\\\\').replace(/'/g,"\\'") + '\')" style="cursor:pointer;">'
                    + '<td><strong>' + r.kd_rek + '</strong></td>'
                    + '<td>' + r.nm_rek + '</td>'
                    + '<td><span class="badge bg-secondary">' + r.tipe + '</span></td>'
                    + '<td><span class="badge ' + (r.balance==='D'?'bg-primary':'bg-success') + '">' + r.balance + '</span></td>'
                    + '<td class="text-end">' + fRp(r.saldo_awal) + '</td></tr>';
            }).join('');
        }).catch(function(){});
}

function pilihRekening(kd, nm) {
    document.getElementById('inp-kdrek').value = kd;
    document.getElementById('inp-nmrek').value = nm;
    document.getElementById('rek-chip-text').innerHTML = '<span class="rek-code">' + kd + '</span>&nbsp;' + nm;
    document.getElementById('rek-chip-text').className = '';
    if (_rekModal) _rekModal.hide();
}

function clearRekening() {
    document.getElementById('inp-kdrek').value = '';
    document.getElementById('inp-nmrek').value = '';
    document.getElementById('rek-chip-text').textContent = 'Semua Rekening';
    document.getElementById('rek-chip-text').className = 'text-muted';
    if (_rekModal) _rekModal.hide();
}

/* ─── Load Data ─── */
function loadData() {
    var tgl1 = document.getElementById('inp-tgl1').value;
    var tgl2 = document.getElementById('inp-tgl2').value;
    if (!tgl1 || !tgl2) { alert('Tanggal harus diisi!'); return; }

    var btn = document.getElementById('btn-load');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memuat...';

    /* Hancurkan instance DataTables lama jika ada */
    if (dataTableInst) {
        dataTableInst.destroy();
        dataTableInst = null;
    }

    document.getElementById('tbl-body').innerHTML = '<tr><td colspan="9" class="tbl-loading"><div class="spinner-border spinner-border-sm text-success"></div><br>Mengambil data...</td></tr>';
    document.getElementById('card-table').style.display = 'block';
    document.getElementById('kpi-bar').style.display = 'none';

    var params = new URLSearchParams({
        tgl1:    tgl1,
        tgl2:    tgl2,
        no_jur:  document.getElementById('inp-nojur').value,
        nm_rek:  document.getElementById('inp-nmrek').value,
        keyword: document.getElementById('inp-keyword').value,
    });

    fetch('api/akuntansi_jurnal.php?' + params.toString())
        .then(function(r){ return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search me-1"></i> Tampilkan Data';

            if (!data.success) {
                document.getElementById('tbl-body').innerHTML = '<tr><td colspan="9" class="tbl-loading text-danger"><i class="fas fa-exclamation-triangle me-1"></i>' + (data.message||'Error') + '</td></tr>';
                return;
            }
            if (!data.rows || data.rows.length === 0) {
                document.getElementById('tbl-body').innerHTML = '<tr><td colspan="9" class="tbl-loading"><i class="fas fa-inbox me-2"></i>Tidak ada data jurnal untuk filter yang dipilih.</td></tr>';
                return;
            }

            _allData = data.rows;

            /* KPI */
            document.getElementById('kpi-debet').textContent   = fRp(data.ttldebet);
            document.getElementById('kpi-kredit').textContent  = fRp(data.ttlkredit);
            document.getElementById('kpi-balance').textContent = fRp(Math.abs(data.ttldebet - data.ttlkredit));
            document.getElementById('kpi-entri').textContent   = data.rows.length + ' baris';
            document.getElementById('tbl-info-text').textContent = data.rows.length + ' entri';
            document.getElementById('kpi-bar').style.display   = 'grid';

            initDataTable(data.rows);
        })
        .catch(function(e) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search me-1"></i> Tampilkan Data';
            document.getElementById('tbl-body').innerHTML = '<tr><td colspan="9" class="tbl-loading text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Error: ' + e.message + '</td></tr>';
        });
}

/* ─── Inisialisasi DataTables ─── */
function initDataTable(rows) {
    /* Kosongkan tbody dulu */
    document.getElementById('tbl-body').innerHTML = '';

    dataTableInst = $('#tbl-jurnal').DataTable({
        data: rows,
        destroy: true,
        pageLength: 50,
        lengthMenu: [[10, 50, 100, 500, -1], [10, 50, 100, 500, 'Semua']],
        order: [[1, 'asc']],
        columns: [
            { data: null, orderable: false, searchable: false,
              render: function() { return '<i class="fas fa-search-plus text-info" style="font-size:.8rem"></i>'; }
            },
            { data: 'tgl_jam' },
            { data: 'no_jurnal', render: function(d){ return d ? '<code class="small">'+d+'</code>' : ''; } },
            { data: 'no_bukti',  render: function(d){ return d ? '<strong style="color:#fbbf24">'+d+'</strong>' : ''; } },
            { data: 'kd_rek',   render: function(d){ return d ? '<code class="small">'+d+'</code>' : ''; } },
            { data: 'nm_rek' },
            { data: 'keterangan', render: function(d){ return formatKeterangan(d); } },
            { data: 'debet',  className: 'text-end col-debet',
              render: function(d){ return d > 0 ? fRp(d) : '<span class="text-muted-zero">-</span>'; }
            },
            { data: 'kredit', className: 'text-end col-kredit',
              render: function(d){ return d > 0 ? fRp(d) : '<span class="text-muted-zero">-</span>'; }
            }
        ],
        rowGroup: {
            dataSrc: 'tgl_jurnal',
            startRender: function(rows, group) {
                var dTotal = rows.data().pluck('debet').reduce(function(a,b){ return a + (parseFloat(b)||0); }, 0);
                var kTotal = rows.data().pluck('kredit').reduce(function(a,b){ return a + (parseFloat(b)||0); }, 0);
                return $('<tr/>')
                    .append('<td colspan="7" style="background:rgba(16,185,129,.12);color:#10b981;font-weight:700;padding:8px 12px"><i class="fas fa-calendar-day me-2"></i>' + group + ' <span class="badge bg-secondary ms-2" style="font-size:.7rem">' + rows.count() + ' baris</span></td>')
                    .append('<td class="text-end fw-bold" style="background:rgba(16,185,129,.12);color:#38bdf8">' + fRp(dTotal) + '</td>')
                    .append('<td class="text-end fw-bold" style="background:rgba(16,185,129,.12);color:#4ade80">' + fRp(kTotal) + '</td>');
            }
        },
        language: {
            search: 'Quick Search:',
            lengthMenu: 'Tampilkan _MENU_ baris',
            info: 'Menampilkan _START_ s/d _END_ dari _TOTAL_ entri',
            paginate: { first:'Awal', last:'Akhir', next:'Lanjut', previous:'Kembali' },
            emptyTable: '<span class="text-muted">Tidak ada data.</span>'
        },
        /* createdRow: pasang class dan data-attr pada setiap baris */
        createdRow: function(row, data) {
            $(row)
                .addClass('tr-entry')
                .attr('data-nojur',   data.no_jurnal || '')
                .attr('data-nobukti', data.no_bukti  || '')
                .css('cursor', 'pointer');
        }
    });

    /*
     * Event Delegation — dipasang pada elemen TABLE (tidak pernah di-destroy
     * saat DataTables render ulang), dengan selector tbody tr.tr-entry.
     * Menggunakan .off().on() agar tidak double-bind saat loadData dipanggil ulang.
     */
    $('#tbl-jurnal')
        .off('click.jurnalDrilldown')
        .on('click.jurnalDrilldown', 'tbody tr.tr-entry', function() {
            var noj = $(this).data('nojur') || $(this).attr('data-nojur');
            if (noj) openDetail(noj);
        });
}

/* ─── Drill-down Detail Modal ─── */
function openDetail(no_jurnal) {
    if (!no_jurnal) return;
    if (!_detailModal) _detailModal = new bootstrap.Modal(document.getElementById('detail-modal'));

    var body = document.getElementById('detail-modal-body');
    document.getElementById('detail-modal-title').innerHTML = '<i class="fas fa-search me-2 text-info"></i>Detail Jurnal: <code>' + no_jurnal + '</code>';
    body.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-info"></div> Memuat detail...</div>';
    _detailModal.show();

    fetch('api/akuntansi_jurnal_detail.php?no_jurnal=' + encodeURIComponent(no_jurnal))
        .then(function(r){ return r.json(); })
        .then(function(d) {
            if (!d.success) {
                body.innerHTML = '<div class="alert alert-danger">' + d.message + '</div>';
                return;
            }
            _lastDetail = d;
            renderDetailModal(d);
        })
        .catch(function(e) {
            body.innerHTML = '<div class="alert alert-danger">Error: ' + e.message + '</div>';
        });
}

function renderDetailModal(d) {
    var h = d.header;
    var btnTrace = document.getElementById('btn-trace-bukti');
    if (btnTrace) {
        if (h.no_bukti && h.no_bukti !== '-') {
            btnTrace.style.display = '';
            btnTrace.dataset.nobukti = h.no_bukti;
        } else {
            btnTrace.style.display = 'none';
        }
    }
    document.getElementById('detail-modal-body').innerHTML = buildSingleJurnalHtml(h, d.detail, d.ttl_debet, d.ttl_kredit, d.balanced);
}

function buildSingleJurnalHtml(h, detail, ttl_d, ttl_k, balanced) {
    var balBadge = balanced
        ? '<span class="badge-balanced"><i class="fas fa-check me-1"></i>BALANCED</span>'
        : '<span class="badge-unbalanced"><i class="fas fa-exclamation me-1"></i>TIDAK BALANCED</span>';

    var html = '<div class="detail-header-card"><div class="row g-3">'
        + '<div class="col-md-3"><div class="d-label">No. Jurnal</div><div class="d-value"><code>' + h.no_jurnal + '</code></div></div>'
        + '<div class="col-md-2"><div class="d-label">Tanggal</div><div class="d-value">' + h.tgl_jurnal + ' ' + (h.jam_jurnal||'') + '</div></div>'
        + '<div class="col-md-2"><div class="d-label">No. Bukti</div><div class="d-value"><strong style="color:#fbbf24">' + h.no_bukti + '</strong></div></div>'
        + '<div class="col-md-3"><div class="d-label">Keterangan</div><div class="d-value">' + h.keterangan + '</div></div>'
        + '<div class="col-md-2"><div class="d-label">Status</div><div class="d-value mt-1">' + balBadge + '</div></div>'
        + '</div>';

    if (h.user_input !== '-' || h.jenis_jurnal !== '-') {
        html += '<div class="row g-2 mt-1">'
            + '<div class="col-md-2"><div class="d-label">User</div><div class="d-value">' + h.user_input + '</div></div>'
            + '<div class="col-md-2"><div class="d-label">Jam Input</div><div class="d-value">' + h.jam_input + '</div></div>'
            + '<div class="col-md-3"><div class="d-label">Jenis</div><div class="d-value">' + h.jenis_jurnal + '</div></div>'
            + '</div>';
    }
    html += '</div>';

    html += '<div class="table-responsive"><table class="table table-sm table-hover mb-0 tbl-detail">'
        + '<thead><tr><th>Kode</th><th>Nama Rekening</th><th>Tipe</th><th class="text-end">Debet</th><th class="text-end">Kredit</th><th>Ket.</th></tr></thead><tbody>';

    detail.forEach(function(row) {
        html += '<tr>'
            + '<td><code class="small">' + row.kd_rek + '</code></td>'
            + '<td class="small">' + row.nm_rek + '</td>'
            + '<td><span class="badge bg-secondary">' + row.tipe + '</span> <span class="badge ' + (row.balance==='D'?'bg-primary':'bg-success') + '">' + row.balance + '</span></td>'
            + '<td class="text-end small">' + (row.debet>0 ? '<span class="debet-val">'+fRp(row.debet)+'</span>' : '<span class="text-muted">-</span>') + '</td>'
            + '<td class="text-end small">' + (row.kredit>0 ? '<span class="kredit-val">'+fRp(row.kredit)+'</span>' : '<span class="text-muted">-</span>') + '</td>'
            + '<td class="small text-muted">' + row.keterangan + '</td></tr>';
    });

    html += '</tbody><tfoot><tr style="font-weight:700;background:rgba(255,255,255,.04)">'
        + '<td colspan="3">TOTAL</td>'
        + '<td class="text-end debet-val">' + fRp(ttl_d) + '</td>'
        + '<td class="text-end kredit-val">' + fRp(ttl_k) + '</td>'
        + '<td>' + (balanced ? '' : '<span class="text-warning small">Selisih: '+fRp(ttl_d-ttl_k)+'</span>') + '</td>'
        + '</tr></tfoot></table></div>';

    return html;
}

/* ─── Trace by No.Bukti ─── */
function openTraceBukti() {
    var btn     = document.getElementById('btn-trace-bukti');
    var noBukti = (btn && btn.dataset.nobukti) ? btn.dataset.nobukti : '';
    if (!noBukti || noBukti === '-') { alert('No. Bukti tidak tersedia.'); return; }

    var body = document.getElementById('detail-modal-body');
    document.getElementById('detail-modal-title').innerHTML =
        '<i class="fas fa-route me-2" style="color:#fbbf24"></i>Audit Trail — No.Bukti: <code>' + noBukti + '</code>';
    body.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-warning"></div> Menelusuri semua jurnal...</div>';
    if (btn) btn.style.display = 'none';

    fetch('api/akuntansi_jurnal_detail.php?trace_bukti=' + encodeURIComponent(noBukti))
        .then(function(r){ return r.json(); })
        .then(function(d) {
            if (!d.success) { body.innerHTML = '<div class="alert alert-danger">' + d.message + '</div>'; return; }
            renderTraceGroups(d);
        })
        .catch(function(e) { body.innerHTML = '<div class="alert alert-danger">Error: ' + e.message + '</div>'; });
}

function renderTraceGroups(d) {
    var html = '<div class="alert" style="background:rgba(251,191,36,.1);border:1px solid rgba(251,191,36,.3);border-radius:8px;padding:10px 14px;margin-bottom:12px;">'
        + '<i class="fas fa-info-circle me-2" style="color:#fbbf24"></i>'
        + '<strong>' + d.jurnal_count + ' jurnal</strong> ditemukan untuk No.Bukti <code>' + d.no_bukti + '</code>'
        + ' &nbsp;|&nbsp; Debet: <span style="color:#38bdf8">' + fRp(d.grand_debet) + '</span>'
        + ' &nbsp;|&nbsp; Kredit: <span style="color:#4ade80">' + fRp(d.grand_kredit) + '</span>'
        + (d.grand_balanced ? ' &nbsp;<span class="badge-balanced">BALANCED</span>' : ' &nbsp;<span class="badge-unbalanced">TIDAK BALANCED</span>')
        + '</div>';

    d.groups.forEach(function(g, i) {
        html += '<div style="border:1px solid rgba(255,255,255,.1);border-radius:8px;margin-bottom:10px;overflow:hidden">'
            + '<div style="background:rgba(56,189,248,.1);padding:8px 14px;font-size:.82rem;font-weight:600;cursor:pointer" onclick="toggleTraceSection(\'ts'+i+'\')">'
            + '<i class="fas fa-chevron-down me-2" id="ts-ic-'+i+'" style="font-size:.7rem;transition:transform .2s"></i>'
            + '<code>' + g.header.no_jurnal + '</code> &nbsp;'
            + g.header.tgl_jurnal + ' ' + g.header.jam_jurnal
            + ' &nbsp;|&nbsp; ' + g.entry_count + ' baris'
            + ' &nbsp;|&nbsp; D:<span style="color:#38bdf8"> ' + fRp(g.ttl_debet) + '</span>'
            + ' K:<span style="color:#4ade80"> ' + fRp(g.ttl_kredit) + '</span>'
            + (g.balanced ? ' &nbsp;<span class="badge-balanced" style="font-size:.65rem">OK</span>' : ' &nbsp;<span class="badge-unbalanced" style="font-size:.65rem">!</span>')
            + '</div>'
            + '<div id="ts'+i+'">' + buildSingleJurnalHtml(g.header, g.detail, g.ttl_debet, g.ttl_kredit, g.balanced) + '</div>'
            + '</div>';
    });
    document.getElementById('detail-modal-body').innerHTML = html;
}

function toggleTraceSection(id) {
    var el = document.getElementById(id);
    var ic = document.getElementById('ts-ic-' + id.replace('ts',''));
    if (!el) return;
    var hidden = el.style.display === 'none';
    el.style.display = hidden ? '' : 'none';
    if (ic) ic.style.transform = hidden ? '' : 'rotate(-90deg)';
}

/* ─── Export ─── */
function exportDetailCSV() {
    if (!_lastDetail) return;
    var h   = _lastDetail.header;
    var csv = '"No Jurnal","Tgl","No Bukti","Kode","Nama Rekening","Debet","Kredit"\n';
    _lastDetail.detail.forEach(function(r) {
        csv += '"'+h.no_jurnal+'","'+h.tgl_jurnal+'","'+h.no_bukti+'","'+r.kd_rek+'","'+r.nm_rek+'",'+r.debet+','+r.kredit+'\n';
    });
    var blob = new Blob(['\uFEFF'+csv], {type:'text/csv;charset=utf-8;'});
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'Detail_' + h.no_jurnal + '.csv';
    a.click();
}

function exportExcel() {
    if (!_allData.length) { alert('Tidak ada data.'); return; }
    var csv = 'Tanggal,No Jurnal,No Bukti,Kode Akun,Nama Akun,Keterangan,Debet,Kredit\n';
    _allData.forEach(function(r) {
        csv += '"'+(r.tgl_jam||'')+'","'+(r.no_jurnal||'')+'","'+(r.no_bukti||'')+'","'
             + (r.kd_rek||'')+'","'+(r.nm_rek||'')+'","'+(r.keterangan||'+')+'",'
             + (r.debet||0)+','+(r.kredit||0)+'\n';
    });
    var blob = new Blob(['\uFEFF'+csv], {type:'text/csv;charset=utf-8;'});
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'JurnalHarian_' + document.getElementById('inp-tgl1').value + '_sd_' + document.getElementById('inp-tgl2').value + '.csv';
    a.click();
}

/* ─── Event bindings (DOMContentLoaded, 1x saja) ─── */
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('btn-load').addEventListener('click', loadData);
    document.getElementById('btn-reset').addEventListener('click', function() {
        var now = new Date();
        var y   = now.getFullYear();
        var m   = String(now.getMonth()+1).padStart(2,'0');
        var d   = String(now.getDate()).padStart(2,'0');
        document.getElementById('inp-tgl1').value = y+'-'+m+'-01';
        document.getElementById('inp-tgl2').value = y+'-'+m+'-'+d;
        document.getElementById('inp-nojur').value   = '';
        document.getElementById('inp-keyword').value = '';
        clearRekening();
        document.getElementById('card-table').style.display = 'none';
        document.getElementById('kpi-bar').style.display    = 'none';
        _allData = [];
    });
    document.getElementById('btn-export').addEventListener('click', exportExcel);
    document.getElementById('btn-clear-rek').addEventListener('click', clearRekening);

    /* Rekening chip — gunakan ID yang benar sesuai HTML */
    var chipWrap = document.getElementById('rek-chip-wrap');
    if (chipWrap) chipWrap.addEventListener('click', openRekeningModal);

    document.getElementById('rek-search').addEventListener('input', function() { cariRekening(this.value); });
    document.getElementById('inp-keyword').addEventListener('keypress', function(e){ if(e.key==='Enter') loadData(); });
    document.getElementById('inp-nojur').addEventListener('keypress',  function(e){ if(e.key==='Enter') loadData(); });
});
</script>
<?php $page_js = ob_get_clean(); ?>
<?php require_once('includes/footer.php'); ?>

