<?php
/**
 * akuntansi_cashflow.php
 * Halaman Cash Flow / Arus Kas — Modul Accounting.
 * Replika DlgCashflow.java dengan desain premium.
 * 
 * Menampilkan: A (Kas Awal) + B (Kas Masuk) - C (Kas Keluar) = Total Kas
 * Disertai Chart visualisasi komposisi arus kas.
 */
$page_title = "Cash Flow / Arus Kas";
require_once('includes/header.php');
?>
<style>
    .accounting-header {
        background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(239,68,68,0.1));
        border: 1px solid rgba(245,158,11,0.3);
        border-radius: 16px; padding: 20px 24px; margin-bottom: 24px;
        display: flex; align-items: center; gap: 16px;
    }
    .accounting-header .icon-box {
        width: 52px; height: 52px;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; color: #fff;
        box-shadow: 0 4px 15px rgba(245,158,11,0.4); flex-shrink: 0;
    }
    .accounting-header h1 { font-size: 1.4rem; font-weight: 700; margin: 0; }
    .accounting-header p  { font-size: 0.82rem; margin: 0; opacity: 0.7; }

    .filter-glass {
        background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);
        border-radius: 14px; padding: 18px 20px; margin-bottom: 20px; backdrop-filter: blur(8px);
    }

    /* KPI Cards */
    .kpi-grid {
        display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 20px;
    }
    @media(max-width:768px){ .kpi-grid { grid-template-columns: repeat(2,1fr); } }
    .kpi-cf {
        border-radius: 12px; padding: 16px; text-align: center;
        transition: transform 0.2s;
    }
    .kpi-cf:hover { transform: translateY(-2px); }
    .kpi-cf .lbl { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.06em; opacity: 0.65; margin-bottom: 6px; }
    .kpi-cf .val { font-size: 1.15rem; font-weight: 700; }
    .kf-awal   { background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3); }
    .kf-awal .val { color: #60a5fa; }
    .kf-masuk  { background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); }
    .kf-masuk .val { color: #34d399; }
    .kf-keluar { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); }
    .kf-keluar .val { color: #f87171; }
    .kf-total  { background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.3); }
    .col-s-awal { color: #94a3b8; }
    .col-s-akhir { font-family: monospace; font-size: 0.95rem; }
    .tr-entry:hover td { background: rgba(255,255,255,0.04); }

    /* Drill-down detail modal */
    #detail-modal .modal-content{max-height:90vh;overflow-y:auto}
    .detail-header-card{background:rgba(56,189,248,.08);border:1px solid rgba(56,189,248,.2);border-radius:10px;padding:12px 16px;margin-bottom:12px;font-size:.85rem}
    .detail-header-card .d-label{font-size:.7rem;text-transform:uppercase;opacity:.6;letter-spacing:.05em}
    .detail-header-card .d-value{font-weight:600;color:#e2e8f0}
    .badge-balanced{background:rgba(16,185,129,.2);border:1px solid rgba(16,185,129,.4);color:#4ade80;border-radius:6px;padding:3px 10px;font-size:.75rem;font-weight:700}
    .badge-unbalanced{background:rgba(239,68,68,.2);border:1px solid rgba(239,68,68,.4);color:#f87171;border-radius:6px;padding:3px 10px;font-size:.75rem;font-weight:700}
    .tbl-detail thead th{background:rgba(56,189,248,.15)!important;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em}
    .tbl-detail .debet-val{color:#38bdf8;font-weight:600}
    .tbl-detail .kredit-val{color:#4ade80;font-weight:600}

    /* Laporan Table */
    .tbl-cf { font-size: 0.85rem; }
    .tbl-cf .row-header td { font-weight: 700; font-size: 0.9rem; background: rgba(245,158,11,0.08) !important; letter-spacing: 0.03em; }
    .tbl-cf .row-subheader td { font-size: 0.72rem; text-transform: uppercase; opacity: 0.6; letter-spacing: 0.06em; background: transparent !important; }
    .tbl-cf .row-data td { padding: 5px 12px !important; }
    .tbl-cf .row-subtotal td { font-weight: 700; background: rgba(59,130,246,0.08) !important; border-top: 1px solid rgba(255,255,255,0.1) !important; color: #93c5fd !important; }
    .tbl-cf .row-grandtotal td { font-weight: 700; font-size: 1rem; background: rgba(245,158,11,0.15) !important; color: #fbbf24 !important; border-top: 2px solid rgba(245,158,11,0.3) !important; }
    .tbl-cf .num-right { text-align: right; font-variant-numeric: tabular-nums; }
    .tbl-cf .row-spacer td { height: 8px; background: transparent !important; padding: 0 !important; border: none !important; }

    .tbl-loading { text-align: center; padding: 60px 20px; opacity: 0.5; }

    /* Baris data klikable */
    .tr-entry { transition: background 0.15s; cursor: pointer; }
    .tr-entry:hover { background: rgba(245,158,11,0.06) !important; }
    
    /* Bubes Modal */
    .row-awal td { font-weight: 700; background: rgba(139,92,246,0.1) !important; color: #c4b5fd !important; }
    .saldo-pos { color: #4ade80; }
    .saldo-neg { color: #f87171; }
    
    .col-s-awal { background-color: rgba(255, 255, 255, 0.02) !important; color: #cbd5e1; font-family: 'Consolas', 'Courier New', monospace; }
    .col-debet { background-color: rgba(56, 189, 248, 0.04) !important; color: #38bdf8; font-family: 'Consolas', 'Courier New', monospace; font-weight: 600; }
    .col-kredit { background-color: rgba(74, 222, 128, 0.04) !important; color: #4ade80; font-family: 'Consolas', 'Courier New', monospace; font-weight: 600; }
    .col-s-akhir { background-color: rgba(139, 92, 246, 0.05) !important; color: #c4b5fd; font-family: 'Consolas', 'Courier New', monospace; font-weight: 700; }
    
    .tbl-bubes tbody tr:hover .col-debet { background-color: rgba(56, 189, 248, 0.09) !important; }
    .tbl-bubes tbody tr:hover .col-kredit { background-color: rgba(74, 222, 128, 0.09) !important; }
    .tbl-bubes tbody tr:hover .col-s-akhir { background-color: rgba(139, 92, 246, 0.1) !important; }
    .text-muted-zero { opacity: 0.3; font-weight: 400; color: #94a3b8 !important; }

    /* Chart container */
    .chart-wrap { border-radius: 12px; padding: 16px; }
</style>

    <div class="accounting-header">
        <div class="icon-box"><i class="fas fa-water"></i></div>
        <div>
            <h1>Cash Flow / Arus Kas</h1>
            <p>Laporan arus kas masuk dan keluar: A (Kas Awal) + B (Masuk) – C (Keluar)</p>
        </div>
    </div>

    <!-- FILTER -->
    <div class="filter-glass">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Dari Tanggal</label>
                <input type="date" id="inp-tgl1" class="form-control form-control-sm"
                       value="<?php echo date('Y-01-01'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Sampai Tanggal</label>
                <input type="date" id="inp-tgl2" class="form-control form-control-sm"
                       value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-3">
                <button id="btn-load" class="btn btn-warning btn-sm px-4 fw-semibold" onclick="loadData()">
                    <i class="fas fa-search me-1"></i> Tampilkan
                </button>
                <button class="btn btn-outline-secondary btn-sm ms-2" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print
                </button>
            </div>
            <div class="col-md-3 text-end">
                <button class="btn btn-outline-info btn-sm" onclick="exportExcel()">
                    <i class="fas fa-file-excel me-1"></i> Export CSV
                </button>
            </div>
        </div>
    </div>

    <!-- KPI -->
    <div class="kpi-grid" id="kpi-area" style="display:none;">
        <div class="kpi-cf kf-awal">
            <div class="lbl"><i class="fas fa-piggy-bank me-1"></i>Kas Awal</div>
            <div class="val" id="kpi-awal">Rp 0</div>
        </div>
        <div class="kpi-cf kf-masuk">
            <div class="lbl"><i class="fas fa-arrow-down me-1"></i>Total Kas Masuk</div>
            <div class="val" id="kpi-masuk">Rp 0</div>
        </div>
        <div class="kpi-cf kf-keluar">
            <div class="lbl"><i class="fas fa-arrow-up me-1"></i>Total Kas Keluar</div>
            <div class="val" id="kpi-keluar">Rp 0</div>
        </div>
        <div class="kpi-cf kf-total">
            <div class="lbl"><i class="fas fa-wallet me-1"></i>Total Kas Akhir</div>
            <div class="val" id="kpi-total">Rp 0</div>
        </div>
    </div>

    <div class="row">
        <!-- Laporan Table -->
        <div class="col-xl-8">
            <div class="card shadow-sm" id="card-table" style="display:none;">
                <div class="card-header py-2">
                    <h6 class="m-0 fw-bold" style="color:#fbbf24;">
                        <i class="fas fa-stream me-2"></i>Laporan Arus Kas
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 tbl-cf" id="tbl-cf">
                            <thead>
                                <tr style="background:rgba(245,158,11,0.1);">
                                    <th style="width:40px;">#</th>
                                    <th>Uraian</th>
                                    <th class="num-right" style="width:160px;">Jumlah (Rp)</th>
                                </tr>
                            </thead>
                            <tbody id="tbl-body">
                                <tr><td colspan="3" class="tbl-loading"><i class="fas fa-info-circle me-2"></i>Atur period dan klik Tampilkan</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Donut -->
        <div class="col-xl-4" id="col-chart" style="display:none;">
            <div class="card shadow-sm h-100">
                <div class="card-header py-2">
                    <h6 class="m-0 fw-bold" style="color:#fbbf24;"><i class="fas fa-chart-pie me-2"></i>Komposisi Arus Kas</h6>
                </div>
                <div class="card-body">
                    <canvas id="chart-cf" height="220"></canvas>
                    <div class="mt-3 text-center" id="chart-legend" style="font-size:0.8rem;"></div>
                </div>
            </div>
            </div>
        </div>
    </div>

    <!-- BUKU BESAR MODAL -->
    <div class="modal fade" id="bubes-modal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bubes-modal-title">
                        <i class="fas fa-book me-2 text-warning"></i>Buku Besar Rekening
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0 tbl-bubes">
                            <thead style="background:linear-gradient(135deg,rgba(139,92,246,.3),rgba(59,130,246,.2));color:#e2e8f0;font-size:.78rem;text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid rgba(255,255,255,.1);">
                                <tr>
                                    <th style="width:160px;padding:8px 12px;">Tanggal &amp; Jam</th>
                                    <th style="width:100px;">No. Jurnal</th>
                                    <th style="width:100px;">No. Bukti</th>
                                    <th>Keterangan</th>
                                    <th class="text-end" style="width:130px;">Saldo Awal (Rp)</th>
                                    <th class="text-end" style="width:120px;">Debet (Rp)</th>
                                    <th class="text-end" style="width:120px;">Kredit (Rp)</th>
                                    <th class="text-end" style="width:130px;padding-right:12px;">Saldo Akhir (Rp)</th>
                                </tr>
                            </thead>
                            <tbody id="bubes-tbl-body">
                                <tr><td colspan="8" class="text-center py-5"><div class="spinner-border spinner-border-sm text-warning"></div> Memuat...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- DRILL-DOWN DETAIL MODAL -->
    <div class="modal fade" id="detail-modal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" style="z-index: 1060;">
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
</div>

<?php ob_start(); ?>
<script>
var _allData = [];
var _cfChart = null;

function fRp(angka) {
    if (angka === null || angka === undefined || isNaN(angka)) return 'Rp 0';
    var neg = angka < 0;
    var abs = Math.abs(Math.round(angka));
    var s   = abs.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    return (neg ? '(Rp ' + s + ')' : 'Rp ' + s);
}

function loadData() {
    var tgl1 = document.getElementById('inp-tgl1').value;
    var tgl2 = document.getElementById('inp-tgl2').value;
    if (!tgl1 || !tgl2) { alert('Tanggal harus diisi!'); return; }

    var btn = document.getElementById('btn-load');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

    var tbody = document.getElementById('tbl-body');
    tbody.innerHTML = '<tr><td colspan="3" class="tbl-loading"><div class="spinner-border spinner-border-sm" style="color:#fbbf24;"></div><br>Menghitung arus kas...</td></tr>';
    document.getElementById('card-table').style.display = 'block';
    document.getElementById('kpi-area').style.display = 'none';
    document.getElementById('col-chart').style.display = 'none';

    fetch('api/akuntansi_cashflow.php?tgl1=' + encodeURIComponent(tgl1) + '&tgl2=' + encodeURIComponent(tgl2))
        .then(function(r){ return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search me-1"></i> Tampilkan';
            renderTable(data);
        })
        .catch(function(e) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search me-1"></i> Tampilkan';
            tbody.innerHTML = '<tr><td colspan="3" class="tbl-loading text-danger">Error: ' + e.message + '</td></tr>';
        });
}

function renderTable(data) {
    if (!data.success) {
        document.getElementById('tbl-body').innerHTML = '<tr><td colspan="3" class="tbl-loading text-danger">' + data.message + '</td></tr>';
        return;
    }

    _allData = data.laporan;

    // KPI update
    document.getElementById('kpi-awal').textContent   = fRp(data.kasawal);
    document.getElementById('kpi-masuk').textContent  = fRp(data.penerimaan);
    document.getElementById('kpi-keluar').textContent = fRp(data.pengeluaran);
    document.getElementById('kpi-total').textContent  = fRp(data.total_kas);
    document.getElementById('kpi-area').style.display = 'grid';

    var rowNum = 1;
    var html = '';

    data.laporan.forEach(function(row) {
        if (row.type === 'spacer') {
            html += '<tr class="row-spacer"><td colspan="3"></td></tr>';
            return;
        }
        if (row.type === 'header') {
            html += '<tr class="row-header"><td colspan="3">' + (row.label || row.rekening || '') + ' ' + (row.rekening || '') + '</td></tr>';
            rowNum = 1;
            return;
        }
        if (row.type === 'subheader') {
            html += '<tr class="row-subheader"><td></td><td>' + row.rekening + '</td><td class="num-right">' + row.jumlah + '</td></tr>';
            return;
        }
        if (row.type === 'subtotal') {
            html += '<tr class="row-subtotal"><td></td><td>' + row.rekening + '</td><td class="num-right">' + fRp(row.jumlah) + '</td></tr>';
            return;
        }
        if (row.type === 'grandtotal') {
            html += '<tr class="row-grandtotal"><td></td><td>' + row.rekening + '</td><td class="num-right">' + fRp(row.jumlah) + '</td></tr>';
            return;
        }
        
        // Ekstrak kode rekening dari string (misal: "1. 410105 PENDAPATAN...")
        // Teks "row.rekening" berisi urutan nomor + kd_rek + nama. Kita potong berdasar spasi.
        var rekStr = row.rekening || '';
        var parts = rekStr.split(' ');
        var kd_rek = '';
        var nm_rek = '';
        if (parts.length >= 3) {
            kd_rek = parts[1];
            nm_rek = parts.slice(2).join(' ');
        }

        // data row
        html += '<tr class="row-data tr-entry" data-kdrek="' + kd_rek + '" data-nmrek="' + nm_rek.replace(/"/g, '&quot;') + '" onclick="openBubes(\'' + kd_rek + '\', \'' + nm_rek.replace(/'/g, "\\'") + '\')">'
              + '<td class="text-muted small">' + rowNum + '</td>'
              + '<td>' + row.rekening + '</td>'
              + '<td class="num-right" style="color:#60a5fa;font-weight:600">' + fRp(row.jumlah) + '</td></tr>';
        rowNum++;
    });

    document.getElementById('tbl-body').innerHTML = html;

    // Chart
    renderChart(data);
    document.getElementById('col-chart').style.display = 'block';
}

function renderChart(data) {
    if (_cfChart) { _cfChart.destroy(); }
    var ctx = document.getElementById('chart-cf').getContext('2d');
    _cfChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Kas Awal', 'Kas Masuk', 'Kas Keluar'],
            datasets: [{
                data: [
                    Math.abs(data.kasawal),
                    Math.abs(data.penerimaan),
                    Math.abs(data.pengeluaran)
                ],
                backgroundColor: [
                    'rgba(96,165,250,0.8)',
                    'rgba(52,211,153,0.8)',
                    'rgba(248,113,113,0.8)'
                ],
                borderColor: [
                    'rgba(96,165,250,1)',
                    'rgba(52,211,153,1)',
                    'rgba(248,113,113,1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            onClick: function(evt, elements) {
                if (elements.length > 0) {
                    var idx = elements[0].index;
                    filterTableByCat(idx);
                } else {
                    filterTableByCat(-1); // Reset filter
                }
            },
            plugins: {
                legend: { position: 'bottom', labels: { color: '#e2e8f0', padding: 16, font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        label: function(ctx) { return ctx.label + ': ' + fRp(ctx.parsed); }
                    }
                }
            },
            cutout: '60%'
        }
    });
}

function filterTableByCat(idx) {
    var tbody = document.getElementById('tbl-body');
    var trs = tbody.getElementsByTagName('tr');
    var currentGroup = -1; 
    
    // Asumsi teks row-header: "KAS AWAL", "MASUK", "KELUAR"
    for(var i=0; i<trs.length; i++) {
        var tr = trs[i];
        if (tr.classList.contains('row-header')) {
            var txt = tr.innerText.toUpperCase();
            if (txt.indexOf('AWAL') !== -1) currentGroup = 0;
            else if (txt.indexOf('MASUK') !== -1 || txt.indexOf('TERIMA') !== -1) currentGroup = 1;
            else if (txt.indexOf('KELUAR') !== -1 || txt.indexOf('PENGELUARAN') !== -1) currentGroup = 2;
        }
        
        if (idx === -1) {
            tr.style.display = '';
        } else {
            // Tampilkan baris yang masuk ke grup terkait, atau spacer
            if (currentGroup === idx || tr.classList.contains('row-spacer')) {
                tr.style.display = '';
            } else {
                tr.style.display = 'none';
            }
        }
    }
}

function exportExcel() {
    if (_allData.length === 0) { alert('Tidak ada data.'); return; }
    var csv = 'Uraian,Jumlah\n';
    _allData.forEach(function(r) {
        if (r.type === 'spacer') return;
        var label = (r.label||'') + ' ' + (r.rekening||'');
        var jumlah = r.jumlah !== null ? r.jumlah : '';
        csv += '"' + label.trim() + '",' + jumlah + '\n';
    });
    var blob = new Blob(['\uFEFF'+csv], {type:'text/csv;charset=utf-8;'});
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'CashFlow_' + document.getElementById('inp-tgl1').value + '_sd_' + document.getElementById('inp-tgl2').value + '.csv';
    link.click();
}

/* ─── BUKU BESAR MODAL (DRILL-DOWN) ─── */
var _bubesModal = null;

function openBubes(kd_rek, nm_rek) {
    if (!kd_rek) return;
    if (!_bubesModal) _bubesModal = new bootstrap.Modal(document.getElementById('bubes-modal'));

    var tgl1 = document.getElementById('inp-tgl1').value;
    var tgl2 = document.getElementById('inp-tgl2').value;

    document.getElementById('bubes-modal-title').innerHTML = '<i class="fas fa-book me-2 text-warning"></i>Buku Besar: <code class="text-white">' + kd_rek + '</code> — ' + nm_rek;
    var tbody = document.getElementById('bubes-tbl-body');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5"><div class="spinner-border spinner-border-sm text-warning"></div> Memuat rincian transaksi...</td></tr>';
    _bubesModal.show();

    fetch('api/akuntansi_bubes.php?kd_rek=' + encodeURIComponent(kd_rek) + '&tgl1=' + encodeURIComponent(tgl1) + '&tgl2=' + encodeURIComponent(tgl2))
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (!d.success) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">' + d.message + '</td></tr>';
                return;
            }
            renderBubesModal(d);
        })
        .catch(function(e) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">Error: ' + e.message + '</td></tr>';
        });
}

function renderBubesModal(data) {
    var tbody = document.getElementById('bubes-tbl-body');
    if (!data.rows || data.rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><i class="fas fa-inbox me-2"></i>Tidak ada mutasi untuk periode ini.</td></tr>';
        return;
    }

    var html = '<tr class="row-awal">';
    html += '<td colspan="4" class="fw-bold" style="padding-left:12px"><i class="fas fa-flag-checkered me-1"></i>Saldo Awal Periode</td>';
    html += '<td class="text-end fw-bold" colspan="3"></td>';
    html += '<td class="text-end fw-bold ' + (data.saldo_awal >= 0 ? 'saldo-pos' : 'saldo-neg') + '" style="padding-right:12px">' + fRp(data.saldo_awal) + '</td>';
    html += '</tr>';

    var ttl_debet = 0;
    var ttl_kredit = 0;

    data.rows.forEach(function(row) {
        var saldoClass = row.saldo_akhir >= 0 ? 'saldo-pos' : 'saldo-neg';
        ttl_debet += parseFloat(row.debet) || 0;
        ttl_kredit += parseFloat(row.kredit) || 0;

        html += '<tr class="tr-entry" style="cursor:pointer;" onclick="openDetail(\'' + row.no_jurnal + '\')" title="Klik untuk lihat Pasangan Jurnal / Trace No.Bukti">';
        html += '<td class="small" style="padding-left:12px">' + row.tgl_jurnal + '</td>';
        html += '<td><code class="small">' + row.no_jurnal + '</code></td>';
        html += '<td class="small">' + row.no_bukti + '</td>';
        html += '<td class="small text-muted">' + row.keterangan + '</td>';
        html += '<td class="text-end small col-s-awal">' + fRp(row.saldo_awal) + '</td>';
        html += '<td class="text-end small col-debet">' + (row.debet > 0 ? fRp(row.debet) : '<span class="text-muted-zero">-</span>') + '</td>';
        html += '<td class="text-end small col-kredit">' + (row.kredit > 0 ? fRp(row.kredit) : '<span class="text-muted-zero">-</span>') + '</td>';
        html += '<td class="text-end small fw-bold col-s-akhir ' + saldoClass + '" style="padding-right:12px">' + fRp(row.saldo_akhir) + '</td>';
        html += '</tr>';
    });

    var net_mutasi = data.balance === 'K' ? (ttl_kredit - ttl_debet) : (ttl_debet - ttl_kredit);
    html += '<tr style="background:rgba(255,255,255,0.03);border-top:2px solid rgba(255,255,255,0.1)">';
    html += '<td colspan="5" class="text-end fw-bold">TOTAL MUTASI PERIODE INI</td>';
    html += '<td class="text-end fw-bold" style="color:#38bdf8;white-space:nowrap;">' + fRp(ttl_debet) + '</td>';
    html += '<td class="text-end fw-bold" style="color:#4ade80;white-space:nowrap;">' + fRp(ttl_kredit) + '</td>';
    html += '<td class="text-end fw-bold" style="padding-right:12px;color:#fbbf24;white-space:nowrap;">Net: ' + fRp(net_mutasi) + '</td>';
    html += '</tr>';

    html += '<tr class="row-awal">';
    html += '<td colspan="4" class="fw-bold" style="padding-left:12px"><i class="fas fa-flag me-1"></i>Saldo Akhir Periode</td>';
    html += '<td class="text-end" colspan="3"></td>';
    html += '<td class="text-end fw-bold ' + (data.saldo_akhir >= 0 ? 'saldo-pos' : 'saldo-neg') + '" style="padding-right:12px;white-space:nowrap;">' + fRp(data.saldo_akhir) + '</td>';
    html += '</tr>';

    tbody.innerHTML = html;
}

/* ─── Drill-down Detail Modal ─── */
var _detailModal = null;
var _lastDetail = null;

function openDetail(no_jurnal) {
    if (!no_jurnal) return;
    if (!_detailModal) _detailModal = new bootstrap.Modal(document.getElementById('detail-modal'));

    // Sembunyikan bubes modal sementara agar z-index aman, atau biarkan overlap jika z-index modal sudah diatur.
    // Di sini kita biarkan overlap saja.
    var body = document.getElementById('detail-modal-body');
    document.getElementById('detail-modal-title').innerHTML = '<i class="fas fa-search me-2 text-info"></i>Detail Jurnal: <code>' + no_jurnal + '</code>';
    body.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-info"></div> Memuat detail pasangan jurnal...</div>';
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
            + g.header.tgl_jurnal + ' ' + (g.header.jam_jurnal || '')
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
    var csv = "Detail Jurnal No: " + h.no_jurnal + "\n" +
              "Tanggal: " + h.tgl_jurnal + " " + (h.jam_jurnal||'') + "\n" +
              "No. Bukti: " + h.no_bukti + "\n" +
              "Keterangan: " + h.keterangan.replace(/"/g, '""') + "\n\n" +
              "Kode Rekening,Nama Rekening,Tipe,Saldo,Debet,Kredit,Keterangan Baris\n";
    
    _lastDetail.detail.forEach(function(r) {
        csv += '"' + r.kd_rek + '","' + r.nm_rek + '","' + r.tipe + '","' + r.balance + '","' + r.debet + '","' + r.kredit + '","' + r.keterangan.replace(/"/g, '""') + '"\n';
    });
    csv += "TOTAL,,,," + _lastDetail.ttl_debet + "," + _lastDetail.ttl_kredit + ",\n";
    
    var blob = new Blob(['\uFEFF'+csv], {type: 'text/csv;charset=utf-8;'});
    var link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = 'Detail_' + h.no_jurnal + '.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

document.addEventListener('DOMContentLoaded', function() {
    // Auto-load untuk tahun ini
    // loadData();
});
</script>
<?php $page_js = ob_get_clean(); ?>
<?php require_once('includes/footer.php'); ?>
