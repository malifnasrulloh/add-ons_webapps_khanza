<?php
/**
 * akuntansi_dashboard.php
 * Executive Summary Dashboard untuk Modul Accounting
 */
$page_title = "Accounting Executive Summary";
require_once('includes/header.php');
?>
<style>
    .exec-header {
        background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(59,130,246,0.15));
        border: 1px solid rgba(16,185,129,0.3);
        border-radius: 16px; padding: 20px 24px; margin-bottom: 24px;
        display: flex; align-items: center; gap: 16px;
    }
    .exec-header .icon-box {
        width: 52px; height: 52px;
        background: linear-gradient(135deg, #10b981, #3b82f6);
        border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; color: #fff;
        box-shadow: 0 4px 15px rgba(16,185,129,0.4); flex-shrink: 0;
    }
    .exec-header h1 { font-size: 1.4rem; font-weight: 700; margin: 0; }
    .exec-header p  { font-size: 0.82rem; margin: 0; opacity: 0.7; }

    .filter-glass {
        background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);
        border-radius: 14px; padding: 18px 20px; margin-bottom: 20px; backdrop-filter: blur(8px);
    }

    .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:992px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
    @media(max-width:576px) { .kpi-grid { grid-template-columns: 1fr; } }
    
    .kpi-card {
        background: rgba(30,41,59,0.7); border: 1px solid rgba(255,255,255,0.08);
        border-radius: 14px; padding: 20px; transition: transform 0.2s, box-shadow 0.2s;
        display: flex; flex-direction: column; justify-content: center; position: relative; overflow: hidden;
        cursor: pointer; /* make it look clickable */
    }
    .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.3); background: rgba(99,102,241,0.1); }
    .kpi-card .kpi-icon { position: absolute; right: -10px; bottom: -10px; font-size: 4rem; opacity: 0.05; }
    .kpi-card .kpi-title { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.7; margin-bottom: 8px; font-weight: 600; }
    .kpi-card .kpi-value { font-size: 1.4rem; font-weight: 700; }
    .kpi-card .kpi-sub { font-size: 0.75rem; margin-top: 4px; }

    /* specific KPI styling */
    .kpi-revenue .kpi-value { color: #4ade80; }
    .kpi-revenue .kpi-icon { color: #4ade80; opacity: 0.1; }
    .kpi-expense .kpi-value { color: #f87171; }
    .kpi-expense .kpi-icon { color: #f87171; opacity: 0.1; }
    .kpi-profit .kpi-value { color: #38bdf8; }
    .kpi-profit .kpi-icon { color: #38bdf8; opacity: 0.1; }
    .kpi-cash .kpi-value { color: #fbbf24; }
    .kpi-cash .kpi-icon { color: #fbbf24; opacity: 0.1; }

    .chart-container {
        background: rgba(30,41,59,0.7); border: 1px solid rgba(255,255,255,0.08);
        border-radius: 14px; padding: 20px; height: 100%;
    }

    /* Gauge Chart Container */
    .gauge-container {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        background: rgba(30,41,59,0.7); border: 1px solid rgba(255,255,255,0.08);
        border-radius: 14px; padding: 20px; height: 100%;
    }
    
    /* Info Edukatif */
    .info-edukatif { margin-bottom: 20px; }
    .btn-info-toggle {
        background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.4);
        color: #38bdf8; border-radius: 20px; padding: 8px 16px; font-size: 0.85rem; font-weight: 600;
        transition: all 0.2s; width: 100%; text-align: left;
    }
    .btn-info-toggle:hover { background: rgba(56, 189, 248, 0.25); color: #fff; }
    .info-content {
        background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px; padding: 20px; margin-top: 10px; font-size: 0.85rem; line-height: 1.6; color: #cbd5e1;
    }
    .info-content h6 { color: #e2e8f0; font-weight: 700; margin-top: 15px; margin-bottom: 8px; }
    .info-content ul { padding-left: 20px; margin-bottom: 0; }
    .info-content li { margin-bottom: 5px; }

    /* Modal Bubes Shared CSS */
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
</style>

<div class="exec-header">
    <div class="icon-box"><i class="fas fa-chart-line"></i></div>
    <div>
        <h1>Accounting Executive Summary</h1>
        <p>Ringkasan performa finansial berdasarkan periode waktu dan tren 12 bulan terakhir</p>
    </div>
</div>

<div class="info-edukatif">
    <button class="btn btn-info-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEdu">
        <i class="fas fa-lightbulb text-warning me-2"></i>Panduan Membaca Laporan Dashboard (Klik untuk Buka/Tutup)
    </button>
    <div class="collapse" id="collapseEdu">
        <div class="info-content">
            <p>Dashboard ini menyajikan gambaran besar kondisi keuangan rumah sakit. Anda dapat mengklik angka-angka besar pada kartu (Total Revenue, dll) untuk melihat rincian sumber dana (Drill-Down).</p>
            <div class="row">
                <div class="col-md-6">
                    <h6>📌 Penjelasan Komponen</h6>
                    <ul>
                        <li><strong class="text-success">Total Revenue:</strong> Seluruh pendapatan kotor dari layanan medis maupun non-medis sebelum dikurangi biaya operasional.</li>
                        <li><strong class="text-danger">Total Expenses:</strong> Seluruh beban biaya (gaji, operasional, bahan medis, dll) yang dikeluarkan rumah sakit.</li>
                        <li><strong class="text-info">Net Profit:</strong> Laba bersih, yaitu Total Revenue dikurangi Total Expenses. Jika negatif, berarti rumah sakit merugi di periode tersebut.</li>
                        <li><strong class="text-warning">Current Cash Balance:</strong> Posisi kas dan bank saat ini (uang cair yang siap dipakai).</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>📊 Indikator Kesehatan</h6>
                    <ul>
                        <li><strong>Net Profit Margin:</strong> Menunjukkan seberapa efisien rumah sakit mengubah pendapatan menjadi laba. Margin 10% berarti dari Rp 100 pendapatan, ada untung bersih Rp 10.</li>
                        <li><strong>Tren Garis:</strong> Idealnya garis hijau (Revenue) selalu berada di atas garis merah (Expense) dengan jarak yang terus melebar.</li>
                        <li><strong>Klik untuk Detail:</strong> Setiap kartu KPI bisa Anda klik untuk melihat daftar rekening penyumbang nilai terbesar beserta detail riwayat transaksinya.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FILTER TANGGAL -->
<div class="filter-glass">
    <div class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Dari Tanggal</label>
            <input type="date" id="inp-tgl1" class="form-control form-control-sm" value="<?php echo date('Y-m-01'); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Sampai Tanggal</label>
            <input type="date" id="inp-tgl2" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="col-md-4">
            <button id="btn-load" class="btn btn-sm px-4 fw-semibold" style="background:linear-gradient(135deg,#38bdf8,#6366f1);color:#fff;border:none;" onclick="loadDashboard()">
                <i class="fas fa-sync-alt me-1"></i> Terapkan Filter
            </button>
        </div>
    </div>
</div>

<div id="loader" class="text-center py-5">
    <div class="spinner-border text-info" style="width: 3rem; height: 3rem;"></div>
    <p class="mt-3 text-muted">Menganalisis data finansial...</p>
</div>

<div id="dashboard-content" style="display: none;">
    <!-- KPI Scorecard -->
    <div class="kpi-grid">
        <div class="kpi-card kpi-revenue" onclick="showBreakdownModal('revenue', 'Total Revenue')">
            <i class="fas fa-arrow-down kpi-icon"></i>
            <div class="kpi-title">Total Revenue</div>
            <div class="kpi-value" id="val-revenue">Rp 0</div>
            <div class="kpi-sub text-muted">Klik untuk rincian pendapatan</div>
        </div>
        <div class="kpi-card kpi-expense" onclick="showBreakdownModal('expenses', 'Total Expenses')">
            <i class="fas fa-arrow-up kpi-icon"></i>
            <div class="kpi-title">Total Expenses</div>
            <div class="kpi-value" id="val-expense">Rp 0</div>
            <div class="kpi-sub text-muted">Klik untuk rincian beban biaya</div>
        </div>
        <div class="kpi-card kpi-profit" onclick="confirmRedirectKeuangan()">
            <i class="fas fa-piggy-bank kpi-icon"></i>
            <div class="kpi-title">Net Profit</div>
            <div class="kpi-value" id="val-profit">Rp 0</div>
            <div class="kpi-sub" id="val-margin" style="color: #94a3b8;">Margin: 0%</div>
        </div>
        <div class="kpi-card kpi-cash" onclick="showBreakdownModal('cash', 'Current Cash Balance')">
            <i class="fas fa-wallet kpi-icon"></i>
            <div class="kpi-title">Current Cash Balance</div>
            <div class="kpi-value" id="val-cash">Rp 0</div>
            <div class="kpi-sub text-muted">Klik untuk rincian saldo kas/bank</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-3">
        <div class="col-xl-8">
            <div class="chart-container">
                <h6 class="fw-bold mb-3" style="color:#e2e8f0;"><i class="fas fa-chart-area me-2"></i>Revenue vs Expense Trend (12 Bulan)</h6>
                <canvas id="trendChart" height="100"></canvas>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="gauge-container">
                <h6 class="fw-bold mb-3 text-center" style="color:#e2e8f0;"><i class="fas fa-tachometer-alt me-2"></i>Net Profit Margin (%)</h6>
                <canvas id="gaugeChart" height="200"></canvas>
                <div class="text-center mt-3">
                    <span id="gauge-text" style="font-size: 1.5rem; font-weight: 700; color: #38bdf8;">0%</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BREAKDOWN MODAL -->
<div class="modal fade" id="breakdown-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary"><i class="fas fa-list me-2"></i>Rincian <span id="bd-title">Data</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="alert alert-info m-3 p-2 small">
                    <i class="fas fa-info-circle me-1"></i> Klik pada baris rekening untuk melihat histori transaksi buku besar secara detail.
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead style="background:rgba(255,255,255,0.05);">
                            <tr>
                                <th>Kode</th>
                                <th>Nama Rekening</th>
                                <th class="text-end">Jumlah (Rp)</th>
                            </tr>
                        </thead>
                        <tbody id="bd-tbody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- BUKU BESAR MODAL (SHARED) -->
<div class="modal fade" id="bubes-modal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-book me-2 text-warning"></i>Buku Besar: <span id="bubes-modal-title" class="fw-bold text-warning"></span>
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
            <div class="modal-footer py-2 gap-2 flex-wrap">
                <button class="btn btn-outline-info btn-sm" onclick="exportBubesCSV()"><i class="fas fa-file-excel me-1"></i>Export Buku Besar</button>
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<!-- DETAIL JURNAL MODAL -->
<div class="modal fade" id="detail-modal" tabindex="-1" style="z-index:1070;"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="detail-modal-title"><i class="fas fa-search me-2 text-info"></i>Detail Jurnal</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="detail-modal-body"></div><div class="modal-footer py-2 gap-2"><button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button><button class="btn btn-outline-warning btn-sm" id="btn-trace-bukti" onclick="openTraceBukti()" style="display:none"><i class="fas fa-route me-1"></i>Trace No.Bukti</button><button class="btn btn-outline-info btn-sm" onclick="exportDetailCSV()"><i class="fas fa-file-excel me-1"></i>Export Detail</button></div></div></div></div>

<?php ob_start(); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Global Data
var _dashboardData = null;
var _bubesData = null; // untuk export CSV
var _bubesTitle = "";
var _bdModal = null;
var _bbModal = null;

function fRpCompact(angka) {
    if (angka === null || isNaN(angka)) return 'Rp 0';
    var abs = Math.abs(angka);
    var s = '';
    if (abs >= 1000000000) { s = (abs / 1000000000).toFixed(2) + ' Miliar'; }
    else if (abs >= 1000000) { s = (abs / 1000000).toFixed(2) + ' Juta'; }
    else { s = abs.toLocaleString('id-ID'); }
    return angka < 0 ? '(Rp ' + s + ')' : 'Rp ' + s;
}

function fRpFull(angka) {
    if (angka === null || angka === undefined || isNaN(angka)) return '0';
    var neg = angka < 0;
    var abs = Math.abs(Math.round(angka));
    var s   = abs.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    return (neg ? '(' + s + ')' : s);
}

function loadDashboard() {
    var tgl1 = $('#inp-tgl1').val();
    var tgl2 = $('#inp-tgl2').val();

    if (!tgl1 || !tgl2) return;

    $('#loader').show();
    $('#dashboard-content').hide();
    $('#btn-load').prop('disabled', true);

    $.ajax({
        url: 'api/data_akuntansi_dashboard.php',
        type: 'GET',
        data: { tgl1: tgl1, tgl2: tgl2 },
        dataType: 'json',
        success: function(res) {
            $('#loader').hide();
            $('#btn-load').prop('disabled', false);
            if (!res.success) {
                $('#loader').html('<div class="alert alert-danger">Gagal memuat data: ' + res.message + '</div>').show();
                return;
            }
            
            _dashboardData = res;
            $('#dashboard-content').fadeIn();
            
            // Populate KPIs
            $('#val-revenue').text(fRpCompact(res.kpi.revenue));
            $('#val-expense').text(fRpCompact(res.kpi.expenses));
            $('#val-profit').text(fRpCompact(res.kpi.net_profit));
            $('#val-margin').text('Margin: ' + res.kpi.profit_margin + '%');
            $('#val-cash').text(fRpCompact(res.kpi.current_cash));

            // Render Charts
            renderTrendChart(res.chart);
            renderGaugeChart(res.kpi.profit_margin);
        },
        error: function(err) {
            $('#loader').hide();
            $('#btn-load').prop('disabled', false);
            $('#loader').html('<div class="alert alert-danger">Kesalahan jaringan saat memuat dashboard.</div>').show();
        }
    });
}

function showBreakdownModal(type, title) {
    if (!_dashboardData || !_dashboardData.breakdown) return;
    
    var dataList = [];
    if (type === 'revenue') dataList = _dashboardData.breakdown.revenue || [];
    else if (type === 'expenses') dataList = _dashboardData.breakdown.expenses || [];
    else if (type === 'cash') dataList = _dashboardData.breakdown.cash || [];

    $('#bd-title').text(title);
    
    var html = '';
    if (dataList.length === 0) {
        html = '<tr><td colspan="3" class="text-center py-4 text-muted">Data tidak ditemukan pada rentang tanggal ini.</td></tr>';
    } else {
        dataList.forEach(function(item) {
            // Field mapping (some arrays use 'subtotal', cash uses 'saldo')
            var val = item.subtotal !== undefined ? item.subtotal : item.saldo;
            html += '<tr style="cursor:pointer;" onclick="openBubesModal(\''+item.kd_rek+'\', \''+item.nm_rek+'\')">';
            html += '<td><code>'+item.kd_rek+'</code></td>';
            html += '<td>'+item.nm_rek+'</td>';
            html += '<td class="text-end font-monospace">'+fRpFull(val)+'</td>';
            html += '</tr>';
        });
    }
    
    $('#bd-tbody').html(html);
    
    if (!_bdModal) {
        _bdModal = new bootstrap.Modal(document.getElementById('breakdown-modal'));
    }
    _bdModal.show();
}

function openBubesModal(kd_rek, nm_rek) {
    var tgl1 = $('#inp-tgl1').val();
    var tgl2 = $('#inp-tgl2').val();
    
    if (!_bbModal) {
        _bbModal = new bootstrap.Modal(document.getElementById('bubes-modal'));
    }
    
    // Hide breakdown modal if open (stacking modals can cause z-index issues)
    if (_bdModal) _bdModal.hide();
    
    _bubesTitle = nm_rek + " (" + kd_rek + ")";
    $('#bubes-modal-title').text(_bubesTitle);
    $('#bubes-tbl-body').html('<tr><td colspan="8" class="text-center py-5"><div class="spinner-border spinner-border-sm text-warning"></div> Memuat histori '+nm_rek+'...</td></tr>');
    _bbModal.show();

    $.ajax({
        url: 'api/akuntansi_bubes.php',
        type: 'GET',
        data: { kd_rek: kd_rek, tgl1: tgl1, tgl2: tgl2 },
        dataType: 'json',
        success: function(res) {
            if (!res.success) {
                $('#bubes-tbl-body').html('<tr><td colspan="8" class="text-center text-danger py-4">'+res.message+'</td></tr>');
                return;
            }
            
            _bubesData = res.rows;
            var html = '';
            if (res.rows.length === 0) {
                html = '<tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada histori transaksi pada rentang tanggal terpilih.</td></tr>';
            } else {
                res.rows.forEach(function(r) {
                    var isAwal = (r.no_jurnal === 'SALDO AWAL');
                    html += '<tr class="'+(isAwal?'row-awal':'bubes-entry')+'"'+(!isAwal?' style="cursor:pointer;" onclick="openDetail(\''+r.no_jurnal+'\')"	title="Klik untuk lihat pasangan jurnal"':'')+'>'; 
                    html += '<td>'+r.tgl_jurnal+' '+r.jam_jurnal+'</td>';
                    html += '<td>'+r.no_jurnal+'</td>';
                    html += '<td>'+r.no_bukti+'</td>';
                    html += '<td>'+r.keterangan+'</td>';
                    
                    if (isAwal) {
                        html += '<td class="text-end col-s-awal">'+fRpFull(r.saldo_awal)+'</td>';
                        html += '<td class="text-end text-muted-zero">-</td>';
                        html += '<td class="text-end text-muted-zero">-</td>';
                        html += '<td class="text-end fw-bold '+ (r.saldo_awal < 0 ? 'saldo-neg' : 'saldo-pos') +'">'+fRpFull(r.saldo_awal)+'</td>';
                    } else {
                        var cD = parseFloat(r.debet) > 0 ? '' : ' text-muted-zero';
                        var cK = parseFloat(r.kredit) > 0 ? '' : ' text-muted-zero';
                        
                        html += '<td class="text-end col-s-awal">'+fRpFull(r.saldo_awal)+'</td>';
                        html += '<td class="text-end col-debet'+cD+'">'+(parseFloat(r.debet)>0 ? fRpFull(r.debet) : '-')+'</td>';
                        html += '<td class="text-end col-kredit'+cK+'">'+(parseFloat(r.kredit)>0 ? fRpFull(r.kredit) : '-')+'</td>';
                        html += '<td class="text-end col-s-akhir '+ (r.saldo_akhir < 0 ? 'saldo-neg' : 'saldo-pos') +'">'+fRpFull(r.saldo_akhir)+'</td>';
                    }
                    html += '</tr>';
                });
            }
            $('#bubes-tbl-body').html(html);
        },
        error: function(err) {
            $('#bubes-tbl-body').html('<tr><td colspan="8" class="text-center text-danger py-4">Terjadi kesalahan saat memuat data buku besar.</td></tr>');
        }
    });
}

function exportBubesCSV() {
    if (!_bubesData || _bubesData.length === 0) { alert('Tidak ada data buku besar untuk diexport.'); return; }
    
    var csv = 'Tanggal,Jam,No. Jurnal,No. Bukti,Keterangan,Saldo Awal,Debet,Kredit,Saldo Akhir\n';
    _bubesData.forEach(function(r) {
        var ket = r.keterangan ? r.keterangan.replace(/"/g, '""') : '';
        csv += '"'+r.tgl_jurnal+'","'+r.jam_jurnal+'","'+r.no_jurnal+'","'+r.no_bukti+'","'+ket+'",'
             + r.saldo_awal+','+r.debet+','+r.kredit+','+r.saldo_akhir+'\n';
    });
    
    var blob = new Blob(['\uFEFF'+csv], {type: 'text/csv;charset=utf-8;'});
    var link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "Buku_Besar_" + _bubesTitle.replace(/[^a-z0-9]/gi, '_').toLowerCase() + ".csv";
    link.click();
}

var _trendChart = null;
function renderTrendChart(data) {
    if (_trendChart) _trendChart.destroy();
    var ctx = document.getElementById('trendChart').getContext('2d');
    _trendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Revenue',
                    data: data.revenue,
                    borderColor: '#4ade80',
                    backgroundColor: 'rgba(74, 222, 128, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Expense',
                    data: data.expense,
                    borderColor: '#f87171',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { labels: { color: '#cbd5e1' } },
                tooltip: {
                    callbacks: { label: function(c) { return c.dataset.label + ': ' + fRpCompact(c.raw); } }
                }
            },
            onClick: function(e) { confirmRedirectKeuangan(); },
            scales: {
                x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { ticks: { color: '#94a3b8', callback: function(val) { return fRpCompact(val); } }, grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        }
    });
}

var _gaugeChart = null;
function renderGaugeChart(margin) {
    if (_gaugeChart) _gaugeChart.destroy();
    $('#gauge-text').text(margin + '%');
    
    // Gauge Logic (Doughnut half)
    var val = margin < 0 ? 0 : (margin > 100 ? 100 : margin);
    var color = margin >= 20 ? '#4ade80' : (margin > 0 ? '#fbbf24' : '#f87171');

    var ctx = document.getElementById('gaugeChart').getContext('2d');
    _gaugeChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Margin', 'Sisa'],
            datasets: [{
                data: [val, 100 - val],
                backgroundColor: [color, 'rgba(255,255,255,0.05)'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            circumference: 180,
            rotation: -90,
            cutout: '80%',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false }
            },
            onClick: function(e) { confirmRedirectKeuangan(); }
        }
    });
}

function confirmRedirectKeuangan() {
    if (confirm("Mau cek neraca laba / rugi?")) {
        window.location.href = 'akuntansi_keuangan.php';
    }
}

$(document).ready(function() {
    loadDashboard();
});

var _detailModal=null,_lastDetail=null;
function openDetail(nj){
    if(!nj||nj==='SALDO AWAL')return;
    if(!_detailModal)_detailModal=new bootstrap.Modal(document.getElementById('detail-modal'));
    var body=document.getElementById('detail-modal-body');
    document.getElementById('detail-modal-title').innerHTML='<i class="fas fa-search me-2 text-info"></i>Detail Jurnal: <code>'+nj+'</code>';
    body.innerHTML='<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-info"></div> Memuat...</div>';
    document.getElementById('btn-trace-bukti').style.display='none';
    _detailModal.show();
    fetch('api/akuntansi_jurnal_detail.php?no_jurnal='+encodeURIComponent(nj))
        .then(function(r){return r.json();})
        .then(function(d){
            if(!d.success){body.innerHTML='<div class="alert alert-danger">'+d.message+'</div>';return;}
            _lastDetail=d;var h=d.header;
            var btn=document.getElementById('btn-trace-bukti');
            if(h.no_bukti&&h.no_bukti!=='-'){btn.style.display='';btn.dataset.nobukti=h.no_bukti;}
            body.innerHTML=buildJurnalHtml(h,d.detail,d.ttl_debet,d.ttl_kredit,d.balanced);
        }).catch(function(e){body.innerHTML='<div class="alert alert-danger">Error: '+e.message+'</div>';});
}
function buildJurnalHtml(h,detail,ttl_d,ttl_k,balanced){
    var bb=balanced?'<span class="badge-bal"><i class="fas fa-check me-1"></i>BALANCED</span>':'<span class="badge-unbal"><i class="fas fa-exclamation me-1"></i>TIDAK BALANCED</span>';
    var html='<div class="djh-card"><div class="row g-2">'
        +'<div class="col-md-3"><div class="dj-lbl">No. Jurnal</div><div class="dj-val"><code>'+h.no_jurnal+'</code></div></div>'
        +'<div class="col-md-2"><div class="dj-lbl">Tanggal</div><div class="dj-val">'+h.tgl_jurnal+' '+(h.jam_jurnal||'')+'</div></div>'
        +'<div class="col-md-2"><div class="dj-lbl">No. Bukti</div><div class="dj-val"><strong style="color:#fbbf24">'+(h.no_bukti||'-')+'</strong></div></div>'
        +'<div class="col-md-3"><div class="dj-lbl">Keterangan</div><div class="dj-val">'+(h.keterangan||'-')+'</div></div>'
        +'<div class="col-md-2"><div class="dj-lbl">Status</div><div class="dj-val mt-1">'+bb+'</div></div>'
        +'</div></div>';
    html+='<div class="table-responsive"><table class="table table-sm table-hover mb-0 tbl-det">'
        +'<thead><tr><th>Kode</th><th>Nama Rekening</th><th>Tipe</th><th class="text-end">Debet</th><th class="text-end">Kredit</th><th>Ket.</th></tr></thead><tbody>';
    detail.forEach(function(r){
        html+='<tr><td><code class="small">'+r.kd_rek+'</code></td><td class="small">'+r.nm_rek+'</td>'
            +'<td><span class="badge bg-secondary">'+r.tipe+'</span> <span class="badge '+(r.balance==='D'?'bg-primary':'bg-success')+'">'+r.balance+'</span></td>'
            +'<td class="text-end small">'+(r.debet>0?'<span style="color:#38bdf8;font-weight:600">'+fRpFull(r.debet)+'</span>':'<span class="text-muted">-</span>')+'</td>'
            +'<td class="text-end small">'+(r.kredit>0?'<span style="color:#4ade80;font-weight:600">'+fRpFull(r.kredit)+'</span>':'<span class="text-muted">-</span>')+'</td>'
            +'<td class="small text-muted">'+(r.keterangan||'-')+'</td></tr>';
    });
    html+='</tbody><tfoot><tr style="font-weight:700"><td colspan="3">TOTAL</td>'
        +'<td class="text-end" style="color:#38bdf8">'+fRpFull(ttl_d)+'</td>'
        +'<td class="text-end" style="color:#4ade80">'+fRpFull(ttl_k)+'</td>'
        +'<td>'+(balanced?'':'<span class="text-warning small">Selisih: '+fRpFull(ttl_d-ttl_k)+'</span>')+'</td>'
        +'</tr></tfoot></table></div>';
    return html;
}
function openTraceBukti(){
    var btn=document.getElementById('btn-trace-bukti');
    var nb=(btn&&btn.dataset.nobukti)?btn.dataset.nobukti:'';
    if(!nb||nb==='-'){alert('No. Bukti tidak tersedia.');return;}
    var body=document.getElementById('detail-modal-body');
    document.getElementById('detail-modal-title').innerHTML='<i class="fas fa-route me-2" style="color:#fbbf24"></i>Audit Trail No.Bukti: <code>'+nb+'</code>';
    body.innerHTML='<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-warning"></div> Menelusuri...</div>';
    btn.style.display='none';
    fetch('api/akuntansi_jurnal_detail.php?trace_bukti='+encodeURIComponent(nb))
        .then(function(r){return r.json();})
        .then(function(d){
            if(!d.success){body.innerHTML='<div class="alert alert-danger">'+d.message+'</div>';return;}
            var html='<div class="alert" style="background:rgba(251,191,36,.1);border:1px solid rgba(251,191,36,.3);border-radius:8px;padding:10px 14px;margin-bottom:12px;">'
                +'<i class="fas fa-info-circle me-2" style="color:#fbbf24"></i><strong>'+d.jurnal_count+' jurnal</strong> No.Bukti <code>'+d.no_bukti+'</code>'
                +' D:<span style="color:#38bdf8">'+fRpFull(d.grand_debet)+'</span> K:<span style="color:#4ade80">'+fRpFull(d.grand_kredit)+'</span>'
                +(d.grand_balanced?' <span class="badge-bal">BALANCED</span>':' <span class="badge-unbal">TIDAK BALANCED</span>')+'</div>';
            d.groups.forEach(function(g,i){
                html+='<div style="border:1px solid rgba(255,255,255,.1);border-radius:8px;margin-bottom:8px;overflow:hidden">'
                    +'<div style="background:rgba(56,189,248,.1);padding:8px 12px;font-size:.82rem;font-weight:600;cursor:pointer" onclick="toggleTS(\'ts'+i+'\')">'
                    +'<i class="fas fa-chevron-down me-2" id="ts-ic-'+i+'" style="font-size:.7rem"></i><code>'+g.header.no_jurnal+'</code> '+g.header.tgl_jurnal
                    +' | '+g.entry_count+' baris D:<span style="color:#38bdf8"> '+fRpFull(g.ttl_debet)+'</span> K:<span style="color:#4ade80"> '+fRpFull(g.ttl_kredit)+'</span>'
                    +(g.balanced?' <span class="badge-bal" style="font-size:.65rem">OK</span>':' <span class="badge-unbal" style="font-size:.65rem">!</span>')
                    +'</div><div id="ts'+i+'">'+buildJurnalHtml(g.header,g.detail,g.ttl_debet,g.ttl_kredit,g.balanced)+'</div></div>';
            });
            body.innerHTML=html;
        }).catch(function(e){body.innerHTML='<div class="alert alert-danger">Error: '+e.message+'</div>';});
}
function toggleTS(id){
    var el=document.getElementById(id),ic=document.getElementById('ts-ic-'+id.replace('ts',''));
    if(!el)return;var h=el.style.display==='none';
    el.style.display=h?'':'none';if(ic)ic.style.transform=h?'':'rotate(-90deg)';
}
function exportDetailCSV(){
    if(!_lastDetail)return;var h=_lastDetail.header;
    var csv='Kode,Nama,Tipe,Balance,Debet,Kredit,Keterangan\n';
    _lastDetail.detail.forEach(function(r){csv+='"'+r.kd_rek+'","'+r.nm_rek+'","'+r.tipe+'","'+r.balance+'",'+r.debet+','+r.kredit+',"'+(r.keterangan||'').replace(/"/g,'""')+'"\n';});
    var blob=new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8;'});
    var a=document.createElement('a');a.href=URL.createObjectURL(blob);
    a.download='Detail_'+h.no_jurnal+'.csv';document.body.appendChild(a);a.click();document.body.removeChild(a);
}
(function(){var s=document.createElement('style');
    s.textContent='#detail-modal .modal-content{max-height:90vh;overflow-y:auto}.djh-card{background:rgba(56,189,248,.08);border:1px solid rgba(56,189,248,.2);border-radius:10px;padding:12px 16px;margin-bottom:12px;font-size:.85rem}.dj-lbl{font-size:.7rem;text-transform:uppercase;opacity:.6;letter-spacing:.05em}.dj-val{font-weight:600;color:#e2e8f0}.badge-bal{background:rgba(16,185,129,.2);border:1px solid rgba(16,185,129,.4);color:#4ade80;border-radius:6px;padding:3px 10px;font-size:.75rem;font-weight:700}.badge-unbal{background:rgba(239,68,68,.2);border:1px solid rgba(239,68,68,.4);color:#f87171;border-radius:6px;padding:3px 10px;font-size:.75rem;font-weight:700}.tbl-det thead th{background:rgba(56,189,248,.15)!important;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em}.bubes-entry:hover td{background:rgba(255,193,7,.06)!important;cursor:pointer}';
    document.head.appendChild(s);})();
</script>
<?php $page_js = ob_get_clean(); ?>
<?php require_once('includes/footer.php'); ?>