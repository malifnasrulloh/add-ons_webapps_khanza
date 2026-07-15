<?php
/**
 * akuntansi_keuangan.php
 * Halaman Laporan Keuangan (Laba Rugi, Perubahan Modal, Neraca).
 * Replika DlgLabaRugi.java dengan desain premium 3-tab.
 */
$page_title = "Laporan Keuangan";
require_once('includes/header.php');
?>
<style>
    .accounting-header {
        background: linear-gradient(135deg, rgba(236,72,153,0.15), rgba(168,85,247,0.15));
        border: 1px solid rgba(236,72,153,0.3);
        border-radius: 16px; padding: 20px 24px; margin-bottom: 24px;
        display: flex; align-items: center; gap: 16px;
    }
    .accounting-header .icon-box {
        width: 52px; height: 52px;
        background: linear-gradient(135deg, #ec4899, #a855f7);
        border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; color: #fff;
        box-shadow: 0 4px 15px rgba(236,72,153,0.4); flex-shrink: 0;
    }
    .accounting-header h1 { font-size: 1.4rem; font-weight: 700; margin: 0; }
    .accounting-header p  { font-size: 0.82rem; margin: 0; opacity: 0.7; }

    .filter-glass {
        background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);
        border-radius: 14px; padding: 18px 20px; margin-bottom: 20px; backdrop-filter: blur(8px);
    }

    /* Tab Custom */
    .keu-tabs { display: flex; gap: 4px; margin-bottom: 16px; background: rgba(0,0,0,0.2); border-radius: 12px; padding: 4px; }
    .keu-tab-btn {
        flex: 1; padding: 10px 16px; border: none; background: transparent; border-radius: 9px;
        color: rgba(255,255,255,0.6); font-size: 0.82rem; font-weight: 600; cursor: pointer;
        transition: all 0.25s; display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .keu-tab-btn.active { background: linear-gradient(135deg, #ec4899, #a855f7); color: #fff; box-shadow: 0 2px 12px rgba(236,72,153,0.4); }
    .keu-tab-btn:not(.active):hover { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.8); }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    /* KPI Summary di atas tiap tab */
    .kpi-summary {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 10px; margin-bottom: 16px;
    }
    .kpi-keu {
        border-radius: 10px; padding: 14px; text-align: center;
        border: 1px solid rgba(255,255,255,0.08);
    }
    .kpi-keu .k-lbl { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.06em; opacity: 0.6; margin-bottom: 4px; }
    .kpi-keu .k-val { font-size: 1.05rem; font-weight: 700; }
    .k-pend  { background: rgba(16,185,129,0.1); }  .k-pend .k-val  { color: #34d399; }
    .k-biaya { background: rgba(239,68,68,0.1); }   .k-biaya .k-val { color: #f87171; }
    .k-laba  { background: rgba(245,158,11,0.1); }  .k-laba .k-val  { color: #fbbf24; }
    .k-aktiva { background: rgba(59,130,246,0.1); } .k-aktiva .k-val { color: #60a5fa; }
    .k-modal  { background: rgba(139,92,246,0.1); } .k-modal .k-val  { color: #c4b5fd; }

    /* Tabel Laporan */
    .tbl-keu { font-size: 0.85rem; }
    .tbl-keu .row-header td {
        background: rgba(236,72,153,0.08) !important; font-weight: 700; font-size: 0.9rem;
        color: #f9a8d4 !important; letter-spacing: 0.02em;
        border-top: 2px solid rgba(236,72,153,0.2) !important;
        cursor: pointer; user-select: none;
    }
    .tbl-keu .row-header:hover td { background: rgba(236,72,153,0.14) !important; }
    .tbl-keu .row-header .toggle-ic { transition: transform .25s; display:inline-block; font-size:.75rem; margin-right:6px; }
    .tbl-keu .row-header.collapsed .toggle-ic { transform: rotate(-90deg); }
    /* Data rows */
    .tbl-keu .row-data td { padding: 5px 12px !important; }
    .tbl-keu .row-data { cursor:pointer; transition:background .12s; }
    .tbl-keu .row-data:hover td { background: rgba(255,255,255,.03) !important; }
    .tbl-keu .row-data.collapsed-child .toggle-ic { transform: rotate(-90deg); }
    .tbl-keu .row-data .toggle-ic { transition: transform .25s; display:inline-block; font-size:.7rem; margin-right:4px; opacity:.7; }
    /* Sub-data rows */
    .tbl-keu .row-sub-data td { padding: 4px 12px !important; font-size: 0.82rem; }
    .tbl-keu .row-subheader td { font-size: 0.7rem; text-transform: uppercase; opacity: 0.55; letter-spacing: 0.07em; background: transparent !important; padding: 4px 12px !important; }
    .tbl-keu .row-subtotal td { font-weight: 700; background: rgba(59,130,246,0.07) !important; color: #93c5fd !important; border-top: 1px solid rgba(255,255,255,0.08) !important; }
    .tbl-keu .row-grandtotal td { font-weight: 700; font-size: 1rem; background: rgba(245,158,11,0.12) !important; color: #fbbf24 !important; border-top: 2px solid rgba(245,158,11,0.3) !important; }
    .tbl-keu .row-error td { font-weight: 700; background: rgba(239,68,68,0.1) !important; color: #fca5a5 !important; }
    .tbl-keu .row-spacer td { height: 8px; background: transparent !important; padding: 0 !important; border: none !important; }
    .tbl-keu .num-right { text-align: right; font-variant-numeric: tabular-nums; }
    .tbl-keu .neg-val { color: #f87171; }
    .tbl-loading { text-align: center; padding: 60px 20px; opacity: 0.5; }
    .tip-text { font-size:.75rem; opacity:.55; margin-bottom:8px; }

    /* Animasi untuk expand/collapse rows */
    @keyframes fadeSlideIn {
        from { opacity: 0; transform: translateY(-4px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .row-animating { animation: fadeSlideIn 0.18s ease-out forwards; }
    .tbl-keu tbody tr { transition: opacity 0.15s ease; }

    /* GROUP-DATA: parent nodes dengan anak (bisa collapse) */
    .tbl-keu .row-group-data td {
        padding: 6px 12px !important;
        background: rgba(255,255,255,.04) !important;
        font-weight: 700;
    }
    .tbl-keu .row-group-data { cursor: pointer; }
    .tbl-keu .row-group-data:hover td { background: rgba(99,102,241,.08) !important; }
    .tbl-keu .row-group-data .toggle-ic { transition: transform .25s cubic-bezier(.4,0,.2,1); display:inline-block; font-size:.7rem; margin-right:5px; color:#a5b4fc; }
    .tbl-keu .row-group-data.collapsed .toggle-ic { transform: rotate(-90deg); }
    /* Indent levels */
    /* dihapus, diganti inline style dinamis di JS */
</style>

    <div class="accounting-header">
        <div class="icon-box"><i class="fas fa-chart-pie"></i></div>
        <div>
            <h1>Laporan Keuangan</h1>
            <p>Laba Rugi · Perubahan Modal · Neraca Keuangan — Replika DlgLabaRugi Khanza<br>
            <small class="tip-text"><i class="fas fa-hand-pointer me-1"></i>Klik baris header/rekening untuk collapse/expand detail</small></p>
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
            <div class="col-md-4">
                <button id="btn-load" class="btn btn-sm px-4 fw-semibold" style="background:linear-gradient(135deg,#ec4899,#a855f7);color:#fff;border:none;" onclick="loadData()">
                    <i class="fas fa-calculator me-1"></i> Hitung Laporan Keuangan
                </button>
                <button class="btn btn-outline-secondary btn-sm ms-2" onclick="window.print()">
                    <i class="fas fa-print"></i>
                </button>
                <button class="btn btn-outline-info btn-sm ms-1" onclick="exportExcel()">
                    <i class="fas fa-file-excel"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- LOADING STATE -->
    <div id="loading-state" style="display:none; text-align:center; padding:60px 0;">
        <div class="spinner-border" style="color:#ec4899; width:3rem; height:3rem;"></div>
        <p class="mt-3" style="color:#f9a8d4;">Menghitung laporan keuangan...</p>
        <small class="text-muted">Proses rekursif mungkin membutuhkan beberapa detik</small>
    </div>

    <!-- TAB NAVIGATION -->
    <div id="main-content" style="display:none;">
        <!-- Tab Buttons -->
        <div class="keu-tabs">
            <button class="keu-tab-btn active" onclick="switchTab('tab-lr')" id="btn-tab-lr">
                <i class="fas fa-balance-scale"></i> Laba / Rugi
            </button>
            <button class="keu-tab-btn" onclick="switchTab('tab-modal')" id="btn-tab-modal">
                <i class="fas fa-landmark"></i> Perubahan Modal
            </button>
            <button class="keu-tab-btn" onclick="switchTab('tab-neraca')" id="btn-tab-neraca">
                <i class="fas fa-columns"></i> Neraca
            </button>
        </div>

        <!-- ============= TAB 1: LABA RUGI ============= -->
        <div class="tab-panel active" id="tab-lr">
            <!-- KPI -->
            <div class="kpi-summary">
                <div class="kpi-keu k-pend">
                    <div class="k-lbl"><i class="fas fa-arrow-down me-1"></i>Total Pendapatan</div>
                    <div class="k-val" id="k-pend">Rp 0</div>
                </div>
                <div class="kpi-keu k-biaya">
                    <div class="k-lbl"><i class="fas fa-arrow-up me-1"></i>Total Biaya</div>
                    <div class="k-val" id="k-biaya">Rp 0</div>
                </div>
                <div class="kpi-keu k-laba">
                    <div class="k-lbl"><i class="fas fa-chart-line me-1"></i>Laba Bersih</div>
                    <div class="k-val" id="k-laba">Rp 0</div>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-header py-2">
                    <h6 class="m-0 fw-bold" style="color:#f9a8d4;">
                        <i class="fas fa-balance-scale me-2"></i>Laporan Laba / Rugi
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 tbl-keu">
                            <thead style="background:rgba(236,72,153,0.1);">
                                <tr><th class="text-center" style="width:50px;">#</th><th>Nama Rekening</th><th class="num-right" style="width:180px;">Saldo Akhir (Rp)</th></tr>
                            </thead>
                            <tbody id="body-lr"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============= TAB 2: MODAL ============= -->
        <div class="tab-panel" id="tab-modal">
            <div class="kpi-summary">
                <div class="kpi-keu k-modal">
                    <div class="k-lbl"><i class="fas fa-landmark me-1"></i>Total Modal Awal</div>
                    <div class="k-val" id="k-modal">Rp 0</div>
                </div>
                <div class="kpi-keu k-laba">
                    <div class="k-lbl"><i class="fas fa-chart-line me-1"></i>Laba / Rugi</div>
                    <div class="k-val" id="k-modal-laba">Rp 0</div>
                </div>
                <div class="kpi-keu k-aktiva">
                    <div class="k-lbl"><i class="fas fa-wallet me-1"></i>Modal Akhir</div>
                    <div class="k-val" id="k-modal-akhir">Rp 0</div>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-header py-2">
                    <h6 class="m-0 fw-bold" style="color:#f9a8d4;"><i class="fas fa-landmark me-2"></i>Laporan Perubahan Modal</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 tbl-keu">
                            <thead style="background:rgba(236,72,153,0.1);">
                                <tr><th class="text-center" style="width:50px;">#</th><th>Nama Rekening</th><th class="num-right" style="width:180px;">Saldo Akhir (Rp)</th></tr>
                            </thead>
                            <tbody id="body-modal"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============= TAB 3: NERACA ============= -->
        <div class="tab-panel" id="tab-neraca">
            <div class="kpi-summary">
                <div class="kpi-keu k-aktiva">
                    <div class="k-lbl"><i class="fas fa-cubes me-1"></i>Total Aktiva</div>
                    <div class="k-val" id="k-aktiva">Rp 0</div>
                </div>
                <div class="kpi-keu k-biaya">
                    <div class="k-lbl"><i class="fas fa-hand-holding-usd me-1"></i>Total Pasiva</div>
                    <div class="k-val" id="k-pasiva">Rp 0</div>
                </div>
                <div class="kpi-keu" id="kpi-neraca-balance" style="background:rgba(16,185,129,0.1);">
                    <div class="k-lbl"><i class="fas fa-balance-scale me-1"></i>Selisih (A-P)</div>
                    <div class="k-val" id="k-selisih" style="color:#4ade80;">Rp 0</div>
                </div>
            </div>
            <!-- Neraca: 2 kolom Aktiva & Pasiva -->
            <div class="row g-3">
                <div class="col-xl-6">
                    <div class="card shadow-sm">
                        <div class="card-header py-2">
                            <h6 class="m-0 fw-bold" style="color:#60a5fa;"><i class="fas fa-cubes me-2"></i>Aktiva</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0 tbl-keu">
                                    <thead style="background:rgba(59,130,246,0.1);">
                                        <tr><th class="text-center" style="width:50px;">#</th><th>Nama Rekening</th><th class="num-right" style="width:180px;">Saldo Akhir (Rp)</th></tr>
                                    </thead>
                                    <tbody id="body-neraca-aktiva"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="card shadow-sm">
                        <div class="card-header py-2">
                            <h6 class="m-0 fw-bold" style="color:#f9a8d4;"><i class="fas fa-hand-holding-usd me-2"></i>Pasiva (Kewajiban + Modal)</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0 tbl-keu">
                                    <thead style="background:rgba(236,72,153,0.1);">
                                        <tr><th class="text-center" style="width:50px;">#</th><th>Nama Rekening</th><th class="num-right" style="width:180px;">Saldo Akhir (Rp)</th></tr>
                                    </thead>
                                    <tbody id="body-neraca-pasiva"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Initial empty state -->
    <div id="empty-state" class="tbl-loading">
        <i class="fas fa-calculator fa-3x mb-3" style="color:rgba(236,72,153,0.5);"></i>
        <p>Atur periode dan klik <strong>Hitung Laporan Keuangan</strong> untuk memulai.</p>
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

<!-- DETAIL JURNAL MODAL -->
<div class="modal fade" id="detail-modal" tabindex="-1" style="z-index:1060;"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="detail-modal-title"><i class="fas fa-search me-2 text-info"></i>Detail Jurnal</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="detail-modal-body"></div><div class="modal-footer py-2 gap-2"><button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button><button class="btn btn-outline-warning btn-sm" id="btn-trace-bukti" onclick="openTraceBukti()" style="display:none"><i class="fas fa-route me-1"></i>Trace No.Bukti</button><button class="btn btn-outline-info btn-sm" onclick="exportDetailCSV()"><i class="fas fa-file-excel me-1"></i>Export Detail</button></div></div></div></div>
<?php ob_start(); ?>
<style>
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
</style>
<script>
var _lastData = null;

function fRp(angka) {
    if (angka === null || angka === undefined || isNaN(angka)) return 'Rp 0';
    var neg = angka < 0;
    var abs = Math.abs(Math.round(angka));
    var s   = abs.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    return (neg ? '(Rp ' + s + ')' : 'Rp ' + s);
}

function switchTab(id) {
    document.querySelectorAll('.tab-panel').forEach(function(p){ p.classList.remove('active'); });
    document.querySelectorAll('.keu-tab-btn').forEach(function(b){ b.classList.remove('active'); });
    document.getElementById(id).classList.add('active');
    document.getElementById('btn-' + id).classList.add('active');
}

function loadData() {
    var tgl1 = document.getElementById('inp-tgl1').value;
    var tgl2 = document.getElementById('inp-tgl2').value;
    if (!tgl1 || !tgl2) { alert('Tanggal harus diisi!'); return; }

    var btn = document.getElementById('btn-load');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menghitung...';

    document.getElementById('empty-state').style.display = 'none';
    document.getElementById('main-content').style.display = 'none';
    document.getElementById('loading-state').style.display = 'block';

    fetch('api/akuntansi_labarugi.php?tgl1=' + encodeURIComponent(tgl1) + '&tgl2=' + encodeURIComponent(tgl2))
        .then(function(r){ return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-calculator me-1"></i> Hitung Laporan Keuangan';
            document.getElementById('loading-state').style.display = 'none';

            if (!data.success) {
                alert('Error: ' + data.message);
                document.getElementById('empty-state').style.display = 'block';
                return;
            }
            _lastData = data;
            renderAll(data);
            document.getElementById('main-content').style.display = 'block';
        })
        .catch(function(e) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-calculator me-1"></i> Hitung Laporan Keuangan';
            document.getElementById('loading-state').style.display = 'none';
            document.getElementById('empty-state').style.display = 'block';
            alert('Error jaringan: ' + e.message);
        });
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

        html += '<tr class="tr-entry" style="cursor:pointer;" onclick="openDetail(\'' + row.no_jurnal + '\')" title="Klik untuk lihat pasangan jurnal & trace no.bukti">';
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

document.addEventListener('DOMContentLoaded', function() {      });

/**
 * Render tabel dengan fitur COLLAPSIBLE per section.
 * Row types: header (clickable → toggle section), data (clickable → toggle children),
 *            sub-data, subheader, subtotal, grandtotal, error, spacer.
 * Data API kini menyertakan section_id & parent_id.
 */
function buildTableHtml(rows) {
    if (!rows || rows.length === 0) {
        return '<tr><td colspan="3" class="tbl-loading"><i class="fas fa-inbox me-2"></i>Tidak ada data.</td></tr>';
    }

    var rowNum = 1;
    var html   = '';

    rows.forEach(function(row) {
        var type    = row.type;
        var label   = (row.rek || '');
        var jumlah  = row.jumlah;
        var sid     = row.section_id || '';
        var pid     = row.parent_id  || '';
        var depth   = row.depth || 0;
        var indentPx = 12 + (depth * 20);
        var indentLeaf = indentPx + 16; /* 16px untuk menggeser pas seukuran icon panah */
        var dataAttrs= '';
        if (sid) dataAttrs += ' data-sid="' + sid + '"';
        if (pid) dataAttrs += ' data-pid="' + pid + '"';

        if (type === 'spacer') {
            html += '<tr class="row-spacer"><td colspan="3"></td></tr>';
            return;
        }
        if (type === 'subheader') {
            html += '<tr class="row-subheader"><td class="text-center"></td><td>' + label + '</td><td class="num-right">' + (jumlah || 'Saldo Akhir') + '</td></tr>';
            return;
        }
        if (type === 'subtotal') {
            html += '<tr class="row-subtotal"><td class="text-center"></td><td>' + label + '</td><td class="num-right">' + fRp(jumlah) + '</td></tr>';
            return;
        }
        if (type === 'grandtotal') {
            html += '<tr class="row-grandtotal"><td class="text-center"></td><td>' + label + '</td><td class="num-right">' + fRp(jumlah) + '</td></tr>';
            return;
        }
        if (type === 'error') {
            html += '<tr class="row-error"><td class="text-center"></td><td>' + label + '</td><td class="num-right">' + fRp(jumlah) + '</td></tr>';
            return;
        }

        /* HEADER — section toggle */
        if (type === 'header') {
            html += '<tr class="row-header"' + dataAttrs + '>';
            html += '<td colspan="2"><i class="fas fa-chevron-down toggle-ic"></i>' + label + '</td>';
            html += '<td class="num-right"></td></tr>';
            rowNum = 1;
            return;
        }

        /* GROUP-DATA — parent node, has children, shows aggregate total */
        if (type === 'group-data') {
            var numClsG = (!jumlah || jumlah >= 0) ? '' : ' neg-val';
            html += '<tr class="row-group-data"' + dataAttrs + '>';
            html += '<td class="text-muted small text-center">' + (depth === 0 ? rowNum : '') + '</td>';
            html += '<td style="padding-left:' + indentPx + 'px !important;"><i class="fas fa-chevron-down toggle-ic"></i>' + label + '</td>';
            html += '<td class="num-right' + numClsG + '">' + fRp(jumlah) + '</td></tr>';
            if (depth === 0) rowNum++;
            return;
        }

        /* DATA — top-level leaf (no children) */
        if (type === 'data') {
            var numCls = (!jumlah || jumlah >= 0) ? '' : ' neg-val';
            var rekStr = label || '';
            var parts = rekStr.split(' ');
            var kd_rek = parts[0] || '';
            var nm_rek = parts.slice(1).join(' ');

            html += '<tr class="row-data"' + dataAttrs + ' onclick="openBubes(\'' + kd_rek + '\', \'' + nm_rek.replace(/'/g, "\\'") + '\')">';
            html += '<td class="text-muted small text-center">' + rowNum + '</td>';
            html += '<td style="padding-left:' + indentLeaf + 'px !important;">' + label + '</td>';
            html += '<td class="num-right' + numCls + '">' + fRp(jumlah) + '</td></tr>';
            rowNum++;
            return;
        }

        /* SUB-DATA — leaf node at depth > 0 */
        if (type === 'sub-data') {
            var numCls2 = (!jumlah || jumlah >= 0) ? '' : ' neg-val';
            var rekStr2 = label || '';
            var parts2 = rekStr2.split(' ');
            var kd_rek2 = parts2[0] || '';
            var nm_rek2 = parts2.slice(1).join(' ');

            html += '<tr class="row-sub-data"' + dataAttrs + ' onclick="openBubes(\'' + kd_rek2 + '\', \'' + nm_rek2.replace(/'/g, "\\'") + '\')">';
            html += '<td class="text-muted small text-center">' + rowNum + '</td>';
            html += '<td style="padding-left:' + indentLeaf + 'px !important;">' + label + '</td>';
            html += '<td class="num-right' + numCls2 + '">' + fRp(jumlah) + '</td></tr>';
            rowNum++;
            return;
        }
    });

    return html;
}

/**
 * Attach collapse events ke semua row-header & row-data di table tbody.
 * Dipanggil setelah innerHTML diisi.
 */
function attachCollapseEvents(tbodyId) {
    var tbody = document.getElementById(tbodyId);
    if (!tbody) return;

    function toggleRows(parentSid, hide) {
        tbody.querySelectorAll('tr[data-pid="' + parentSid + '"]').forEach(function(c) {
            if (hide) {
                c.style.opacity = '0';
                c.style.transition = 'opacity 0.15s';
                setTimeout(function(){ c.style.display = 'none'; c.style.opacity = ''; c.style.transition = ''; }, 150);
            } else {
                c.style.display = '';
                c.style.opacity = '0';
                c.style.transition = 'opacity 0.18s';
                /* Use requestAnimationFrame for smooth fade-in */
                requestAnimationFrame(function(){
                    requestAnimationFrame(function(){
                        c.style.opacity = '1';
                        setTimeout(function(){ c.style.opacity=''; c.style.transition=''; }, 200);
                    });
                });
                /* If child was also collapsed, keep its children hidden */
                var csid = c.dataset.sid;
                if (csid && c.classList.contains('collapsed')) {
                    toggleRows(csid, true);
                }
            }
        });
    }

    /* Section headers toggle */
    tbody.querySelectorAll('tr.row-header').forEach(function(tr) {
        tr.addEventListener('click', function() {
            var sid    = this.dataset.sid;
            var isOpen = !this.classList.contains('collapsed');
            this.classList.toggle('collapsed', isOpen);
            /* Cascade: hide/show all immediate children */
            toggleRows(sid, isOpen);
        });
    });

    /* GROUP-DATA rows toggle — parent nodes with children */
    tbody.querySelectorAll('tr.row-group-data').forEach(function(tr) {
        tr.addEventListener('click', function() {
            var sid      = this.dataset.sid;
            if (!sid) return;
            var children = tbody.querySelectorAll('tr[data-pid="' + sid + '"]');
            if (children.length === 0) return;
            var isOpen   = !this.classList.contains('collapsed');
            this.classList.toggle('collapsed', isOpen);
            toggleRows(sid, isOpen);
        });
    });

    /* DATA rows (legacy leaf-toggle kept for tabs without group-data) */
    tbody.querySelectorAll('tr.row-data').forEach(function(tr) {
        tr.addEventListener('click', function() {
            var sid      = this.dataset.sid;
            if (!sid) return;
            var children = tbody.querySelectorAll('tr[data-pid="' + sid + '"]');
            if (children.length === 0) return;
            var isOpen   = !this.classList.contains('collapsed');
            this.classList.toggle('collapsed', isOpen);
            toggleRows(sid, isOpen);
        });
    });
}

/**
 * Pisahkan tab3 menjadi Aktiva dan Pasiva berdasarkan section_id prefix
 */
function splitNeracaRows(tab3) {
    var aktiva = [], pasiva = [];
    tab3.forEach(function(row) {
        var sid = row.section_id || '';
        var pid = row.parent_id  || '';
        /* Aktiva section: prefix NER_AKT */
        if (sid.indexOf('NER_AKT') === 0 || pid.indexOf('NER_AKT') === 0) {
            aktiva.push(row);
        } else if (sid === 'NER_AKT' || pid === 'NER_AKT') {
            aktiva.push(row);
        } else {
            pasiva.push(row);
        }
    });
    return { aktiva: aktiva, pasiva: pasiva };
}

function renderAll(data) {
    // Tab 1 — Laba Rugi
    document.getElementById('k-pend').textContent  = fRp(data.total_pendapatan);
    document.getElementById('k-biaya').textContent = fRp(data.total_biaya);
    var labaEl = document.getElementById('k-laba');
    labaEl.textContent = fRp(data.laba_bersih);
    labaEl.style.color = data.laba_bersih >= 0 ? '#4ade80' : '#f87171';
    document.getElementById('body-lr').innerHTML = buildTableHtml(data.tab1);
    attachCollapseEvents('body-lr');

    // Tab 2 — Modal
    document.getElementById('k-modal').textContent      = fRp(data.total_modal);
    document.getElementById('k-modal-laba').textContent = fRp(data.laba_bersih);
    document.getElementById('k-modal-akhir').textContent= fRp(data.modal_akhir);
    document.getElementById('body-modal').innerHTML = buildTableHtml(data.tab2);
    attachCollapseEvents('body-modal');

    // Tab 3 — Neraca
    document.getElementById('k-aktiva').textContent = fRp(data.total_aktiva);
    document.getElementById('k-pasiva').textContent = fRp(data.total_pasiva);
    var selEl = document.getElementById('k-selisih');
    selEl.textContent = fRp(data.selisih_neraca);
    selEl.style.color = data.selisih_neraca === 0 ? '#4ade80' : '#fbbf24';

    var split = splitNeracaRows(data.tab3);
    document.getElementById('body-neraca-aktiva').innerHTML = buildTableHtml(split.aktiva);
    document.getElementById('body-neraca-pasiva').innerHTML = buildTableHtml(split.pasiva);
    attachCollapseEvents('body-neraca-aktiva');
    attachCollapseEvents('body-neraca-pasiva');
}

function exportExcel() {
    if (!_lastData) { alert('Belum ada data. Klik Hitung terlebih dahulu.'); return; }
    var csv = '';
    // Export Tab 1
    csv += '"=== LABA RUGI ==="\n';
    if (_lastData.tab1) {
        _lastData.tab1.forEach(function(r) {
            if (r.type === 'spacer') { csv += '\n'; return; }
            var label = (r.rek || r.label || '');
            var jml   = r.jumlah !== null ? r.jumlah : '';
            csv += '"' + label + '",' + jml + '\n';
        });
    }
    csv += '\n"=== MODAL ==="\n';
    if (_lastData.tab2) {
        _lastData.tab2.forEach(function(r) {
            if (r.type === 'spacer') { csv += '\n'; return; }
            var label = (r.rek || r.label || '');
            var jml   = r.jumlah !== null ? r.jumlah : '';
            csv += '"' + label + '",' + jml + '\n';
        });
    }

    var blob = new Blob(['\uFEFF'+csv], {type:'text/csv;charset=utf-8;'});
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'LaporanKeuangan_' + document.getElementById('inp-tgl1').value + '.csv';
    link.click();
}
var _detailModal=null,_lastDetail=null;
function openDetail(nj){
    if(!nj)return;
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
            +'<td class="text-end small">'+(r.debet>0?'<span style="color:#38bdf8;font-weight:600">'+fRp(r.debet)+'</span>':'<span class="text-muted">-</span>')+'</td>'
            +'<td class="text-end small">'+(r.kredit>0?'<span style="color:#4ade80;font-weight:600">'+fRp(r.kredit)+'</span>':'<span class="text-muted">-</span>')+'</td>'
            +'<td class="small text-muted">'+(r.keterangan||'-')+'</td></tr>';
    });
    html+='</tbody><tfoot><tr style="font-weight:700"><td colspan="3">TOTAL</td>'
        +'<td class="text-end" style="color:#38bdf8">'+fRp(ttl_d)+'</td>'
        +'<td class="text-end" style="color:#4ade80">'+fRp(ttl_k)+'</td>'
        +'<td>'+(balanced?'':'<span class="text-warning small">Selisih: '+fRp(ttl_d-ttl_k)+'</span>')+'</td>'
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
                +' D:<span style="color:#38bdf8">'+fRp(d.grand_debet)+'</span> K:<span style="color:#4ade80">'+fRp(d.grand_kredit)+'</span>'
                +(d.grand_balanced?' <span class="badge-bal">BALANCED</span>':' <span class="badge-unbal">TIDAK BALANCED</span>')+'</div>';
            d.groups.forEach(function(g,i){
                html+='<div style="border:1px solid rgba(255,255,255,.1);border-radius:8px;margin-bottom:8px;overflow:hidden">'
                    +'<div style="background:rgba(56,189,248,.1);padding:8px 12px;font-size:.82rem;font-weight:600;cursor:pointer" onclick="toggleTS(\'ts'+i+'\')">'
                    +'<i class="fas fa-chevron-down me-2" id="ts-ic-'+i+'" style="font-size:.7rem"></i><code>'+g.header.no_jurnal+'</code> '+g.header.tgl_jurnal
                    +' | '+g.entry_count+' baris D:<span style="color:#38bdf8"> '+fRp(g.ttl_debet)+'</span> K:<span style="color:#4ade80"> '+fRp(g.ttl_kredit)+'</span>'
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
    s.textContent='#detail-modal .modal-content{max-height:90vh;overflow-y:auto}.djh-card{background:rgba(56,189,248,.08);border:1px solid rgba(56,189,248,.2);border-radius:10px;padding:12px 16px;margin-bottom:12px;font-size:.85rem}.dj-lbl{font-size:.7rem;text-transform:uppercase;opacity:.6;letter-spacing:.05em}.dj-val{font-weight:600;color:#e2e8f0}.badge-bal{background:rgba(16,185,129,.2);border:1px solid rgba(16,185,129,.4);color:#4ade80;border-radius:6px;padding:3px 10px;font-size:.75rem;font-weight:700}.badge-unbal{background:rgba(239,68,68,.2);border:1px solid rgba(239,68,68,.4);color:#f87171;border-radius:6px;padding:3px 10px;font-size:.75rem;font-weight:700}.tbl-det thead th{background:rgba(56,189,248,.15)!important;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em}.tr-entry:hover td{background:rgba(255,193,7,.06)!important;cursor:pointer}';
    document.head.appendChild(s);})();
</script>
<?php $page_js = ob_get_clean(); ?>
<?php require_once('includes/footer.php'); ?>
