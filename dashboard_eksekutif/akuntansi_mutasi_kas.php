<?php
/**
 * akuntansi_mutasi_kas.php
 * Monitoring Mutasi Kas Internal — Perpindahan fisik uang antar kantong Kas/Bank.
 */
$page_title = "Monitoring Mutasi Kas Internal";
require_once('includes/header.php');
?>
<style>
/* ─── PAGE HEADER ─────────────────────────────────────────────────────────── */
.mk-header {
    background: linear-gradient(135deg, rgba(6,182,212,0.15), rgba(99,102,241,0.15));
    border: 1px solid rgba(6,182,212,0.3);
    border-radius: 16px; padding: 20px 24px; margin-bottom: 24px;
    display: flex; align-items: center; gap: 16px;
}
.mk-header .icon-box {
    width: 52px; height: 52px;
    background: linear-gradient(135deg, #06b6d4, #6366f1);
    border-radius: 14px; display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; color: #fff;
    box-shadow: 0 4px 15px rgba(6,182,212,0.4); flex-shrink: 0;
}
.mk-header h1 { font-size: 1.4rem; font-weight: 700; margin: 0; }
.mk-header p  { font-size: 0.82rem; margin: 0; opacity: 0.7; }

/* ─── FILTER ──────────────────────────────────────────────────────────────── */
.filter-glass {
    background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);
    border-radius: 14px; padding: 18px 20px; margin-bottom: 20px; backdrop-filter: blur(8px);
}

/* ─── SALDO CARDS ─────────────────────────────────────────────────────────── */
.saldo-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 12px; margin-bottom: 24px;
}
.saldo-card {
    background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
    border-radius: 14px; padding: 16px; cursor: pointer;
    transition: all 0.22s cubic-bezier(.4,0,.2,1);
    position: relative; overflow: hidden;
}
.saldo-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    border-radius: 14px 14px 0 0;
}
.saldo-card.kas-card::before  { background: linear-gradient(90deg, #06b6d4, #0891b2); }
.saldo-card.bank-card::before { background: linear-gradient(90deg, #6366f1, #8b5cf6); }
.saldo-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    border-color: rgba(6,182,212,0.4);
}
.saldo-card .sc-grup {
    font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.08em;
    opacity: 0.55; margin-bottom: 4px;
}
.saldo-card .sc-nama {
    font-size: 0.88rem; font-weight: 700; color: #e2e8f0; margin-bottom: 10px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.saldo-card .sc-saldo {
    font-size: 1.15rem; font-weight: 800; font-variant-numeric: tabular-nums;
    font-family: 'Consolas', monospace;
}
.sc-saldo.pos { color: #4ade80; }
.sc-saldo.neg { color: #f87171; }
.saldo-card .sc-meta {
    display: flex; gap: 8px; margin-top: 8px; font-size: 0.72rem; flex-wrap: wrap;
}
.sc-meta .sc-d { color: #38bdf8; }
.sc-meta .sc-k { color: #f87171; }
.sc-meta span  { opacity: 0.65; }

/* ─── KPI SUMMARY ─────────────────────────────────────────────────────────── */
.mk-kpi-row {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 10px; margin-bottom: 20px;
}
.mk-kpi {
    background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px; padding: 14px; text-align: center;
}
.mk-kpi .kpi-lbl { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.06em; opacity: 0.55; margin-bottom: 4px; }
.mk-kpi .kpi-val { font-size: 1.05rem; font-weight: 700; }
.kpi-cyan  .kpi-val { color: #22d3ee; }
.kpi-indigo .kpi-val { color: #818cf8; }
.kpi-green  .kpi-val { color: #4ade80; }
.kpi-orange .kpi-val { color: #fb923c; }

/* ─── TABS ────────────────────────────────────────────────────────────────── */
.mk-tabs { display: flex; gap: 4px; margin-bottom: 14px; background: rgba(0,0,0,0.2); border-radius: 10px; padding: 4px; }
.mk-tab-btn {
    flex: 1; padding: 9px 12px; border: none; background: transparent; border-radius: 7px;
    color: rgba(255,255,255,0.55); font-size: 0.8rem; font-weight: 600; cursor: pointer;
    transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px;
}
.mk-tab-btn.active { background: linear-gradient(135deg, #06b6d4, #6366f1); color: #fff; box-shadow: 0 2px 10px rgba(6,182,212,0.35); }
.mk-tab-btn:not(.active):hover { background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.8); }
.mk-tab-panel { display: none; }
.mk-tab-panel.active { display: block; }

/* ─── FLOW TABLE ──────────────────────────────────────────────────────────── */
.tbl-flow { font-size: 0.85rem; }
.tbl-flow thead th {
    background: linear-gradient(135deg, rgba(6,182,212,0.15), rgba(99,102,241,0.1)) !important;
    color: #94a3b8; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em;
    border-bottom: 1px solid rgba(255,255,255,0.08) !important; padding: 10px 12px !important;
}
.tbl-flow tbody tr { cursor: pointer; transition: background 0.12s; }
.tbl-flow tbody tr:hover td { background: rgba(6,182,212,0.07) !important; }
.tbl-flow .arrow-cell {
    text-align: center; color: #6366f1; font-size: 0.9rem;
    padding: 8px 4px !important;
}
.tbl-flow .from-cell { color: #22d3ee; font-weight: 600; }
.tbl-flow .to-cell   { color: #818cf8; font-weight: 600; }
.tbl-flow .amt-cell  { font-family: 'Consolas', monospace; color: #fbbf24; text-align: right; font-weight: 700; }
.tbl-flow .trx-cell  { color: #94a3b8; text-align: center; font-size: 0.78rem; }
.tbl-flow .rank-cell { color: #64748b; text-align: center; font-size: 0.78rem; }

/* ─── CHART CONTAINER ─────────────────────────────────────────────────────── */
.chart-glass {
    background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.08);
    border-radius: 14px; padding: 20px;
}

/* ─── EMPTY & LOADING ─────────────────────────────────────────────────────── */
.mk-loading { text-align: center; padding: 60px 20px; opacity: 0.5; }
.mk-empty   { text-align: center; padding: 60px 20px; }

/* ─── MODAL TRANSFER LIST (Level 2) ──────────────────────────────────────── */
.tbl-trx-list thead th {
    background: rgba(6,182,212,0.12) !important;
    font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8;
}
.tbl-trx-list tbody tr { cursor: pointer; transition: background 0.12s; }
.tbl-trx-list tbody tr:hover td { background: rgba(251,191,36,0.06) !important; }
.badge-from { background: rgba(34,211,238,0.15); color: #22d3ee; border: 1px solid rgba(34,211,238,0.3); border-radius: 6px; padding: 2px 8px; font-size: 0.72rem; }
.badge-to   { background: rgba(129,140,248,0.15); color: #818cf8; border: 1px solid rgba(129,140,248,0.3); border-radius: 6px; padding: 2px 8px; font-size: 0.72rem; }

/* ─── BUKU BESAR MODAL (Level 3) ─────────────────────────────────────────── */
.row-awal td  { font-weight: 700; background: rgba(139,92,246,0.1) !important; color: #c4b5fd !important; }
.saldo-pos    { color: #4ade80; }
.saldo-neg    { color: #f87171; }
.col-s-awal   { color: #cbd5e1; font-family: 'Consolas', monospace; }
.col-debet    { color: #38bdf8; font-family: 'Consolas', monospace; font-weight: 600; }
.col-kredit   { color: #4ade80; font-family: 'Consolas', monospace; font-weight: 600; }
.col-s-akhir  { color: #c4b5fd; font-family: 'Consolas', monospace; font-weight: 700; }
.text-muted-zero { opacity: 0.3; color: #94a3b8 !important; }

/* ─── DETAIL JURNAL MODAL (Level 4) ──────────────────────────────────────── */
.djh-card {
    background: rgba(56,189,248,.08); border: 1px solid rgba(56,189,248,.2);
    border-radius: 10px; padding: 12px 16px; margin-bottom: 12px; font-size: .85rem;
}
.djh-card .dj-lbl { font-size: .7rem; text-transform: uppercase; opacity: .6; letter-spacing: .05em; }
.djh-card .dj-val { font-weight: 600; color: #e2e8f0; }
.badge-bal   { background: rgba(16,185,129,.2); border: 1px solid rgba(16,185,129,.4); color: #4ade80; border-radius: 6px; padding: 3px 10px; font-size: .75rem; font-weight: 700; }
.badge-unbal { background: rgba(239,68,68,.2);  border: 1px solid rgba(239,68,68,.4);  color: #f87171; border-radius: 6px; padding: 3px 10px; font-size: .75rem; font-weight: 700; }
</style>

<!-- ═══════════════════════════════════════════════════════ PAGE HEADER ═════ -->
<div class="mk-header">
    <div class="icon-box"><i class="fas fa-exchange-alt"></i></div>
    <div>
        <h1>Monitoring Mutasi Kas Internal</h1>
        <p>Lacak perpindahan fisik uang antar kantong Kas &amp; Bank — Setoran, Transfer, &amp; Pemindahan Dana<br>
        <small><i class="fas fa-hand-pointer me-1"></i>Klik baris untuk drill-down hingga Detail Jurnal &amp; Audit Trail</small></p>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════ FILTER ═══════════ -->
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
        <div class="col-md-4 d-flex gap-2 flex-wrap">
            <button id="btn-load" class="btn btn-sm px-4 fw-semibold"
                    style="background:linear-gradient(135deg,#06b6d4,#6366f1);color:#fff;border:none;"
                    onclick="loadMutasiKas()">
                <i class="fas fa-search me-1"></i> Analisis Mutasi
            </button>
            <button class="btn btn-outline-secondary btn-sm" onclick="window.print()" title="Cetak">
                <i class="fas fa-print"></i>
            </button>
            <button class="btn btn-outline-info btn-sm" onclick="exportFlowCSV()" title="Export CSV">
                <i class="fas fa-file-excel"></i>
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════ LOADING ══════════ -->
<div id="mk-loader" class="mk-loading" style="display:none;">
    <div class="spinner-border" style="color:#06b6d4;width:3rem;height:3rem;"></div>
    <p class="mt-3" style="color:#22d3ee;">Menelusuri aliran dana...</p>
</div>

<!-- ═══════════════════════════════════════════════════════ EMPTY STATE ══════ -->
<div id="mk-empty" class="mk-empty">
    <i class="fas fa-exchange-alt fa-3x mb-3" style="color:rgba(6,182,212,0.4);"></i>
    <p>Atur periode dan klik <strong>Analisis Mutasi</strong> untuk melacak perpindahan kas.</p>
</div>

<!-- ═══════════════════════════════════════════════════════ MAIN CONTENT ═════ -->
<div id="mk-content" style="display:none;">

    <!-- KPI SUMMARY -->
    <div class="mk-kpi-row">
        <div class="mk-kpi kpi-cyan">
            <div class="kpi-lbl"><i class="fas fa-exchange-alt me-1"></i>Total Mutasi</div>
            <div class="kpi-val" id="kpi-total-mutasi">Rp 0</div>
        </div>
        <div class="mk-kpi kpi-indigo">
            <div class="kpi-lbl"><i class="fas fa-receipt me-1"></i>Jumlah Transaksi</div>
            <div class="kpi-val" id="kpi-total-trx">0</div>
        </div>
        <div class="mk-kpi kpi-green">
            <div class="kpi-lbl"><i class="fas fa-wallet me-1"></i>Akun Kas Aktif</div>
            <div class="kpi-val" id="kpi-kas-aktif">0</div>
        </div>
        <div class="mk-kpi kpi-orange">
            <div class="kpi-lbl"><i class="fas fa-route me-1"></i>Jalur Transfer</div>
            <div class="kpi-val" id="kpi-jalur">0</div>
        </div>
    </div>

    <!-- SALDO CARDS -->
    <div class="d-flex align-items-center mb-2">
        <h6 class="fw-bold mb-0" style="color:#22d3ee;">
            <i class="fas fa-coins me-2"></i>Posisi Saldo Kas &amp; Bank
        </h6>
        <small class="text-muted ms-2">— klik kartu untuk lihat Buku Besar</small>
    </div>
    <div id="saldo-cards-grid" class="saldo-cards-grid"></div>

    <!-- TABS: Visualisasi -->
    <div class="mk-tabs">
        <button class="mk-tab-btn active" onclick="switchMkTab('tab-flow')" id="btn-tab-flow">
            <i class="fas fa-table"></i> Daftar Aliran
        </button>
        <button class="mk-tab-btn" onclick="switchMkTab('tab-chart')" id="btn-tab-chart">
            <i class="fas fa-chart-bar"></i> Top Chart
        </button>
        <button class="mk-tab-btn" onclick="switchMkTab('tab-trend')" id="btn-tab-trend">
            <i class="fas fa-chart-line"></i> Tren Bulanan
        </button>
    </div>

    <!-- TAB 1: DAFTAR ALIRAN -->
    <div class="mk-tab-panel active" id="tab-flow">
        <div class="card shadow-sm">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold" style="color:#22d3ee;">
                    <i class="fas fa-random me-2"></i>Daftar Perpindahan Kas Internal
                </h6>
                <small class="text-muted"><i class="fas fa-mouse-pointer me-1"></i>Klik baris untuk detail transaksi</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 tbl-flow">
                        <thead>
                            <tr>
                                <th style="width:35px;">#</th>
                                <th>Dari (Asal Dana)</th>
                                <th style="width:40px;"></th>
                                <th>Ke (Tujuan Dana)</th>
                                <th class="text-center" style="width:90px;">Jml Trx</th>
                                <th class="text-end" style="width:180px;">Total Mutasi (Rp)</th>
                            </tr>
                        </thead>
                        <tbody id="flow-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: TOP CHART -->
    <div class="mk-tab-panel" id="tab-chart">
        <div class="chart-glass">
            <h6 class="fw-bold mb-3" style="color:#818cf8;">
                <i class="fas fa-chart-bar me-2"></i>Top 15 Jalur Transfer Terbesar
            </h6>
            <canvas id="flowChart" height="220"></canvas>
        </div>
    </div>

    <!-- TAB 3: TREN BULANAN -->
    <div class="mk-tab-panel" id="tab-trend">
        <div class="chart-glass">
            <h6 class="fw-bold mb-3" style="color:#22d3ee;">
                <i class="fas fa-chart-line me-2"></i>Tren Total Mutasi per Bulan
            </h6>
            <canvas id="trendChart" height="160"></canvas>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════ MODAL LEVEL 2: Daftar Jurnal ═════════ -->
<div class="modal fade" id="trx-modal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="trx-modal-title">
                    <i class="fas fa-list-alt me-2 text-info"></i>Daftar Transaksi Transfer
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="px-3 py-2" id="trx-modal-info"></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 tbl-trx-list">
                        <thead>
                            <tr>
                                <th style="width:130px;">Tanggal</th>
                                <th style="width:130px;">No. Jurnal</th>
                                <th style="width:110px;">No. Bukti</th>
                                <th>Keterangan</th>
                                <th class="text-end" style="width:150px;">Jumlah (Rp)</th>
                            </tr>
                        </thead>
                        <tbody id="trx-modal-tbody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2">
                <span class="text-muted small me-auto" id="trx-modal-total"></span>
                <button class="btn btn-outline-info btn-sm" onclick="exportTrxCSV()">
                    <i class="fas fa-file-excel me-1"></i>Export
                </button>
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════ MODAL LEVEL 3: Buku Besar ════════════ -->
<div class="modal fade" id="bubes-modal" tabindex="-1" style="z-index:1055;">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bubes-modal-title">
                    <i class="fas fa-book me-2 text-warning"></i>Buku Besar
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead style="background:linear-gradient(135deg,rgba(139,92,246,.3),rgba(59,130,246,.2));color:#e2e8f0;font-size:.78rem;text-transform:uppercase;letter-spacing:.05em;">
                            <tr>
                                <th style="padding:8px 12px;width:160px;">Tanggal &amp; Jam</th>
                                <th style="width:120px;">No. Jurnal</th>
                                <th style="width:110px;">No. Bukti</th>
                                <th>Keterangan</th>
                                <th class="text-end" style="width:130px;">Saldo Awal</th>
                                <th class="text-end" style="width:120px;">Debet</th>
                                <th class="text-end" style="width:120px;">Kredit</th>
                                <th class="text-end" style="width:130px;padding-right:12px;">Saldo Akhir</th>
                            </tr>
                        </thead>
                        <tbody id="bubes-tbody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2 gap-2">
                <button class="btn btn-outline-info btn-sm" onclick="exportBubesCSV()">
                    <i class="fas fa-file-excel me-1"></i>Export Buku Besar
                </button>
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════ MODAL LEVEL 4: Detail Jurnal ═════════ -->
<div class="modal fade" id="detail-modal" tabindex="-1" style="z-index:1060;">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detail-modal-title">
                    <i class="fas fa-search me-2 text-info"></i>Detail Jurnal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detail-modal-body">
                <div class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-info"></div> Memuat...
                </div>
            </div>
            <div class="modal-footer py-2 gap-2">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <button class="btn btn-outline-warning btn-sm" id="btn-trace" onclick="openTraceBukti()" style="display:none;">
                    <i class="fas fa-route me-1"></i>Trace No.Bukti
                </button>
                <button class="btn btn-outline-info btn-sm" onclick="exportDetailCSV()">
                    <i class="fas fa-file-excel me-1"></i>Export
                </button>
            </div>
        </div>
    </div>
</div>
<?php ob_start(); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var _mkData=null, _trxData=null, _bubesData=null, _lastDetail=null;
var _modalTrx=null, _modalBubes=null, _modalDetail=null;
var _flowChart=null, _trendChart=null;
var _curFromKd='', _curToKd='', _curBubesKd='';

function fRp(n){
    if(n===null||n===undefined||isNaN(n))return 'Rp 0';
    var neg=n<0, abs=Math.abs(Math.round(n));
    var s=abs.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    return neg?'(Rp '+s+')':'Rp '+s;
}
function fRpShort(n){
    var abs=Math.abs(n);
    if(abs>=1e9) return (n<0?'-':'')+(abs/1e9).toFixed(2)+' M';
    if(abs>=1e6) return (n<0?'-':'')+(abs/1e6).toFixed(2)+' Jt';
    return fRp(n);
}
function switchMkTab(id){
    document.querySelectorAll('.mk-tab-panel').forEach(function(p){p.classList.remove('active');});
    document.querySelectorAll('.mk-tab-btn').forEach(function(b){b.classList.remove('active');});
    document.getElementById(id).classList.add('active');
    document.getElementById('btn-'+id).classList.add('active');
}

function loadMutasiKas(){
    var tgl1=$('#inp-tgl1').val(), tgl2=$('#inp-tgl2').val();
    if(!tgl1||!tgl2) return;
    $('#mk-empty,#mk-content').hide();
    $('#mk-loader').show();
    $('#btn-load').prop('disabled',true).html('<span class="spinner-border spinner-border-sm me-1"></span>Memuat...');
    $.ajax({
        url:'api/akuntansi_mutasi_kas.php',
        data:{tgl1:tgl1,tgl2:tgl2},
        dataType:'json',
        success:function(res){
            $('#mk-loader').hide();
            $('#btn-load').prop('disabled',false).html('<i class="fas fa-search me-1"></i> Analisis Mutasi');
            if(!res.success){$('#mk-empty').show();return;}
            _mkData=res;
            renderKpi(res);
            renderSaldoCards(res.saldo_cards);
            renderFlowTable(res.flow_matrix);
            renderFlowChart(res.flow_matrix);
            renderTrendChart(res.chart_trend);
            $('#mk-content').fadeIn();
        },
        error:function(){
            $('#mk-loader').hide();
            $('#btn-load').prop('disabled',false).html('<i class="fas fa-search me-1"></i> Analisis Mutasi');
            $('#mk-empty').show();
        }
    });
}

function renderKpi(res){
    $('#kpi-total-mutasi').text(fRpShort(res.grand_total_mutasi));
    $('#kpi-total-trx').text(res.grand_total_trx.toLocaleString('id-ID'));
    $('#kpi-kas-aktif').text(res.saldo_cards.length);
    $('#kpi-jalur').text(res.flow_matrix.length);
}

function renderSaldoCards(cards){
    var html='';
    cards.forEach(function(c){
        var isPos=c.saldo_berjalan>=0;
        var isKas=c.grup.toUpperCase().indexOf('KAS')>=0;
        var cls=isKas?'kas-card':'bank-card';
        var icon=isKas?'fa-coins':'fa-university';
        html+='<div class="saldo-card '+cls+'" onclick="openBubesFromCard(\''+c.kd_rek+'\',\''+c.nm_rek.replace(/'/g,"\\'")+'\')" title="Klik lihat Buku Besar '+c.nm_rek+'">';
        html+='<div class="sc-grup"><i class="fas '+icon+' me-1"></i>'+c.grup+'</div>';
        html+='<div class="sc-nama" title="'+c.nm_rek+'">'+c.nm_rek+'</div>';
        html+='<div class="sc-saldo '+(isPos?'pos':'neg')+'">'+fRp(c.saldo_berjalan)+'</div>';
        html+='<div class="sc-meta">';
        html+='<span class="sc-d"><i class="fas fa-arrow-down me-1"></i>'+fRpShort(c.total_debet)+'</span>';
        html+='<span class="sc-k"><i class="fas fa-arrow-up me-1"></i>'+fRpShort(c.total_kredit)+'</span>';
        html+='</div></div>';
    });
    $('#saldo-cards-grid').html(html||'<p class="text-muted small p-2">Tidak ada akun aktif pada periode ini.</p>');
}

function renderFlowTable(matrix){
    var html='';
    if(!matrix||matrix.length===0){
        html='<tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-inbox me-2"></i>Tidak ada transaksi mutasi kas internal pada periode ini.</td></tr>';
        $('#flow-tbody').html(html); return;
    }
    matrix.forEach(function(r,i){
        html+='<tr onclick="openTrxModal(\''+r.from_kd+'\',\''+r.from_nm.replace(/'/g,"\\'")+'\',\''+r.to_kd+'\',\''+r.to_nm.replace(/'/g,"\\'")+'\')"> ';
        html+='<td class="rank-cell">'+(i+1)+'</td>';
        html+='<td class="from-cell"><i class="fas fa-dot-circle me-1" style="font-size:.65rem;"></i>'+r.from_nm+'<br><small style="opacity:.5;font-size:.7rem;">'+r.from_kd+'</small></td>';
        html+='<td class="arrow-cell"><i class="fas fa-arrow-right"></i></td>';
        html+='<td class="to-cell"><i class="fas fa-map-marker-alt me-1" style="font-size:.65rem;"></i>'+r.to_nm+'<br><small style="opacity:.5;font-size:.7rem;">'+r.to_kd+'</small></td>';
        html+='<td class="trx-cell">'+r.jml_trx+' trx</td>';
        html+='<td class="amt-cell">'+fRp(r.total_mutasi)+'</td>';
        html+='</tr>';
    });
    $('#flow-tbody').html(html);
}

function renderFlowChart(matrix){
    if(_flowChart){_flowChart.destroy();}
    var top=matrix.slice(0,15);
    var labels=top.map(function(r){
        var l=r.from_nm+' â†’ '+r.to_nm;
        return l.length>35?l.substring(0,35)+'â€¦':l;
    });
    var vals=top.map(function(r){return r.total_mutasi;});
    var ctx=document.getElementById('flowChart').getContext('2d');
    _flowChart=new Chart(ctx,{
        type:'bar',
        data:{labels:labels,datasets:[{
            label:'Total Mutasi (Rp)',data:vals,
            backgroundColor:'rgba(99,102,241,0.6)',borderColor:'rgba(99,102,241,1)',borderWidth:1
        }]},
        options:{
            indexAxis:'y',responsive:true,
            plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return fRp(c.raw);}}}},
            scales:{
                x:{ticks:{color:'#94a3b8',callback:function(v){return fRpShort(v);}},grid:{color:'rgba(255,255,255,0.05)'}},
                y:{ticks:{color:'#cbd5e1',font:{size:10}}}
            }
        }
    });
}

function renderTrendChart(trend){
    if(_trendChart){_trendChart.destroy();}
    if(!trend||trend.length===0) return;
    var labels=trend.map(function(t){return t.bulan;});
    var vals=trend.map(function(t){return t.total_mutasi;});
    var ctx=document.getElementById('trendChart').getContext('2d');
    _trendChart=new Chart(ctx,{
        type:'line',
        data:{labels:labels,datasets:[{
            label:'Total Mutasi',data:vals,
            borderColor:'#06b6d4',backgroundColor:'rgba(6,182,212,0.1)',
            tension:0.4,fill:true,pointBackgroundColor:'#06b6d4',pointRadius:5
        }]},
        options:{
            responsive:true,
            plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return fRp(c.raw);}}}},
            scales:{
                x:{ticks:{color:'#94a3b8'},grid:{color:'rgba(255,255,255,0.05)'}},
                y:{ticks:{color:'#94a3b8',callback:function(v){return fRpShort(v);}},grid:{color:'rgba(255,255,255,0.05)'}}
            }
        }
    });
}
</script>
<script>
/* â”€â”€â”€ DRILL-DOWN LEVEL 2: Modal Daftar Jurnal Transfer â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function openTrxModal(from_kd, from_nm, to_kd, to_nm){
    _curFromKd=from_kd; _curToKd=to_kd;
    if(!_modalTrx) _modalTrx=new bootstrap.Modal(document.getElementById('trx-modal'));
    var tgl1=$('#inp-tgl1').val(), tgl2=$('#inp-tgl2').val();
    $('#trx-modal-title').html('<i class="fas fa-list-alt me-2 text-info"></i><span class="badge-from">'+from_nm+'</span> <i class="fas fa-arrow-right mx-2" style="color:#6366f1"></i> <span class="badge-to">'+to_nm+'</span>');
    $('#trx-modal-info').html('<small class="text-muted">Periode: '+tgl1+' s/d '+tgl2+'</small>');
    $('#trx-modal-tbody').html('<tr><td colspan="5" class="text-center py-4"><div class="spinner-border spinner-border-sm text-info"></div> Memuat...</td></tr>');
    $('#trx-modal-total').text('');
    _modalTrx.show();
    $.ajax({
        url:'api/akuntansi_mutasi_kas_list.php',
        data:{from_kd:from_kd,to_kd:to_kd,tgl1:tgl1,tgl2:tgl2},
        dataType:'json',
        success:function(res){
            if(!res.success){$('#trx-modal-tbody').html('<tr><td colspan="5" class="text-center text-danger py-4">'+res.message+'</td></tr>');return;}
            _trxData=res;
            var html='';
            if(!res.rows||res.rows.length===0){
                html='<tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada transaksi ditemukan.</td></tr>';
            } else {
                res.rows.forEach(function(r){
                    html+='<tr onclick="openBubesFromTrx(\''+r.no_jurnal+'\')" title="Klik lihat Buku Besar">';
                    html+='<td class="small">'+r.tgl_jurnal+' <span style="opacity:.5">'+r.jam_jurnal+'</span></td>';
                    html+='<td><code class="small">'+r.no_jurnal+'</code></td>';
                    html+='<td class="small" style="color:#fbbf24">'+r.no_bukti+'</td>';
                    html+='<td class="small text-muted" style="max-width:250px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'+r.keterangan+'</td>';
                    html+='<td class="text-end small fw-bold" style="color:#4ade80;font-family:monospace">'+fRp(r.jumlah_masuk)+'</td>';
                    html+='</tr>';
                });
            }
            $('#trx-modal-tbody').html(html);
            $('#trx-modal-total').html('<strong>Total: </strong><span style="color:#4ade80;font-family:monospace">'+fRp(res.total_mutasi)+'</span> dari <strong>'+res.row_count+'</strong> transaksi');
        }
    });
}

/* â”€â”€â”€ DRILL-DOWN LEVEL 3: Buku Besar (dari card saldo) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function openBubesFromCard(kd_rek, nm_rek){
    _curBubesKd=kd_rek;
    var tgl1=$('#inp-tgl1').val(), tgl2=$('#inp-tgl2').val();
    openBubesModal(kd_rek, nm_rek, tgl1, tgl2);
}

/* â”€â”€â”€ DRILL-DOWN LEVEL 3: Buku Besar (dari modal transaksi) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function openBubesFromTrx(no_jurnal){
    /* Dari daftar jurnal, buka buku besar rekening ASAL */
    var tgl1=$('#inp-tgl1').val(), tgl2=$('#inp-tgl2').val();
    openBubesModal(_curFromKd, _curFromKd, tgl1, tgl2);
    /* Langsung buka detail jurnal spesifik */
    setTimeout(function(){ openDetail(no_jurnal); }, 400);
}

function openBubesModal(kd_rek, nm_rek, tgl1, tgl2){
    if(!_modalBubes) _modalBubes=new bootstrap.Modal(document.getElementById('bubes-modal'));
    $('#bubes-modal-title').html('<i class="fas fa-book me-2 text-warning"></i>Buku Besar: <code class="text-white">'+kd_rek+'</code> â€” '+nm_rek);
    $('#bubes-tbody').html('<tr><td colspan="8" class="text-center py-5"><div class="spinner-border spinner-border-sm text-warning"></div> Memuat histori...</td></tr>');
    _modalBubes.show();
    $.ajax({
        url:'api/akuntansi_bubes.php',
        data:{kd_rek:kd_rek,tgl1:tgl1,tgl2:tgl2},
        dataType:'json',
        success:function(res){
            if(!res.success){$('#bubes-tbody').html('<tr><td colspan="8" class="text-center text-danger py-4">'+res.message+'</td></tr>');return;}
            _bubesData=res;
            renderBubesTable(res);
        }
    });
}

function renderBubesTable(data){
    var html='<tr class="row-awal"><td colspan="4" style="padding-left:12px"><i class="fas fa-flag-checkered me-1"></i>Saldo Awal Periode</td><td colspan="3" class="text-end"></td><td class="text-end fw-bold '+(data.saldo_awal>=0?'saldo-pos':'saldo-neg')+'" style="padding-right:12px">'+fRp(data.saldo_awal)+'</td></tr>';
    if(!data.rows||data.rows.length===0){
        html+='<tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada mutasi pada periode ini.</td></tr>';
    } else {
        data.rows.forEach(function(r){
            var cD=parseFloat(r.debet)>0?'':' text-muted-zero';
            var cK=parseFloat(r.kredit)>0?'':' text-muted-zero';
            html+='<tr style="cursor:pointer" onclick="openDetail(\''+r.no_jurnal+'\')" title="Klik lihat detail jurnal">';
            html+='<td class="small" style="padding-left:12px">'+r.tgl_jurnal+'</td>';
            html+='<td><code class="small">'+r.no_jurnal+'</code></td>';
            html+='<td class="small">'+r.no_bukti+'</td>';
            html+='<td class="small text-muted">'+r.keterangan+'</td>';
            html+='<td class="text-end small col-s-awal">'+fRp(r.saldo_awal)+'</td>';
            html+='<td class="text-end small col-debet'+cD+'">'+(parseFloat(r.debet)>0?fRp(r.debet):'-')+'</td>';
            html+='<td class="text-end small col-kredit'+cK+'">'+(parseFloat(r.kredit)>0?fRp(r.kredit):'-')+'</td>';
            html+='<td class="text-end small fw-bold col-s-akhir '+(r.saldo_akhir>=0?'saldo-pos':'saldo-neg')+'" style="padding-right:12px">'+fRp(r.saldo_akhir)+'</td>';
            html+='</tr>';
        });
    }
    html+='<tr class="row-awal"><td colspan="4" style="padding-left:12px"><i class="fas fa-flag me-1"></i>Saldo Akhir Periode</td><td colspan="3" class="text-end"></td><td class="text-end fw-bold '+(data.saldo_akhir>=0?'saldo-pos':'saldo-neg')+'" style="padding-right:12px">'+fRp(data.saldo_akhir)+'</td></tr>';
    $('#bubes-tbody').html(html);
}

/* â”€â”€â”€ DRILL-DOWN LEVEL 4: Detail Jurnal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function openDetail(no_jurnal){
    if(!no_jurnal) return;
    if(!_modalDetail) _modalDetail=new bootstrap.Modal(document.getElementById('detail-modal'),{backdrop:false});
    $('#detail-modal-title').html('<i class="fas fa-search me-2 text-info"></i>Detail Jurnal: <code>'+no_jurnal+'</code>');
    $('#detail-modal-body').html('<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-info"></div> Memuat...</div>');
    $('#btn-trace').hide();
    _modalDetail.show();
    fetch('api/akuntansi_jurnal_detail.php?no_jurnal='+encodeURIComponent(no_jurnal))
        .then(function(r){return r.json();})
        .then(function(d){
            if(!d.success){$('#detail-modal-body').html('<div class="alert alert-danger">'+d.message+'</div>');return;}
            _lastDetail=d;
            var h=d.header;
            if(h.no_bukti&&h.no_bukti!=='-'){$('#btn-trace').show().data('nobukti',h.no_bukti);}
            $('#detail-modal-body').html(buildJurnalHtml(h,d.detail,d.ttl_debet,d.ttl_kredit,d.balanced));
        });
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
    html+='<div class="table-responsive"><table class="table table-sm table-hover mb-0">'
        +'<thead style="background:rgba(56,189,248,.1);font-size:.78rem;text-transform:uppercase;letter-spacing:.04em"><tr><th>Kode</th><th>Nama Rekening</th><th>Tipe</th><th class="text-end">Debet</th><th class="text-end">Kredit</th><th>Ket.</th></tr></thead><tbody>';
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
    var nb=$('#btn-trace').data('nobukti');
    if(!nb||nb==='-'){return;}
    $('#detail-modal-title').html('<i class="fas fa-route me-2" style="color:#fbbf24"></i>Audit Trail No.Bukti: <code>'+nb+'</code>');
    $('#detail-modal-body').html('<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-warning"></div> Menelusuri...</div>');
    $('#btn-trace').hide();
    fetch('api/akuntansi_jurnal_detail.php?trace_bukti='+encodeURIComponent(nb))
        .then(function(r){return r.json();})
        .then(function(d){
            if(!d.success){$('#detail-modal-body').html('<div class="alert alert-danger">'+d.message+'</div>');return;}
            var html='<div class="alert" style="background:rgba(251,191,36,.1);border:1px solid rgba(251,191,36,.3);border-radius:8px;padding:10px 14px;margin-bottom:12px">'
                +'<i class="fas fa-info-circle me-2" style="color:#fbbf24"></i><strong>'+d.jurnal_count+' jurnal</strong> untuk No.Bukti <code>'+d.no_bukti+'</code>'
                +' &nbsp;| D:<span style="color:#38bdf8"> '+fRp(d.grand_debet)+'</span>'
                +' &nbsp;| K:<span style="color:#4ade80"> '+fRp(d.grand_kredit)+'</span>'
                +(d.grand_balanced?' &nbsp;<span class="badge-bal">BALANCED</span>':' &nbsp;<span class="badge-unbal">!</span>')+'</div>';
            d.groups.forEach(function(g,i){
                html+='<div style="border:1px solid rgba(255,255,255,.1);border-radius:8px;margin-bottom:10px;overflow:hidden">'
                    +'<div style="background:rgba(56,189,248,.1);padding:8px 14px;font-size:.82rem;font-weight:600;cursor:pointer" onclick="var e=document.getElementById(\'ts'+i+'\');e.style.display=e.style.display===\'none\'?\'\':\'none\'">'
                    +'<code>'+g.header.no_jurnal+'</code> &nbsp;'+g.header.tgl_jurnal
                    +' &nbsp;| D:<span style="color:#38bdf8"> '+fRp(g.ttl_debet)+'</span>'
                    +' K:<span style="color:#4ade80"> '+fRp(g.ttl_kredit)+'</span>'
                    +(g.balanced?' <span class="badge-bal" style="font-size:.65rem">OK</span>':' <span class="badge-unbal" style="font-size:.65rem">!</span>')
                    +'</div><div id="ts'+i+'">'+buildJurnalHtml(g.header,g.detail,g.ttl_debet,g.ttl_kredit,g.balanced)+'</div></div>';
            });
            $('#detail-modal-body').html(html);
        });
}

/* â”€â”€â”€ EXPORT FUNCTIONS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function exportFlowCSV(){
    if(!_mkData||!_mkData.flow_matrix){return;}
    var csv='Dari,Ke,Jumlah Transaksi,Total Mutasi\n';
    _mkData.flow_matrix.forEach(function(r){
        csv+='"'+r.from_nm+'","'+r.to_nm+'",'+r.jml_trx+','+r.total_mutasi+'\n';
    });
    dlCSV(csv,'Mutasi_Kas_'+$('#inp-tgl1').val()+'.csv');
}
function exportTrxCSV(){
    if(!_trxData||!_trxData.rows){return;}
    var csv='Tanggal,No Jurnal,No Bukti,Keterangan,Jumlah\n';
    _trxData.rows.forEach(function(r){
        csv+='"'+r.tgl_jurnal+'","'+r.no_jurnal+'","'+r.no_bukti+'","'+(r.keterangan||'').replace(/"/g,'""')+'",'+r.jumlah_masuk+'\n';
    });
    dlCSV(csv,'Transfer_'+_trxData.from_nm+'_ke_'+_trxData.to_nm+'.csv');
}
function exportBubesCSV(){
    if(!_bubesData||!_bubesData.rows){return;}
    var csv='Tanggal,No Jurnal,No Bukti,Keterangan,Saldo Awal,Debet,Kredit,Saldo Akhir\n';
    _bubesData.rows.forEach(function(r){
        csv+='"'+r.tgl_jurnal+'","'+r.no_jurnal+'","'+r.no_bukti+'","'+(r.keterangan||'').replace(/"/g,'""')+'",'+r.saldo_awal+','+r.debet+','+r.kredit+','+r.saldo_akhir+'\n';
    });
    dlCSV(csv,'BukuBesar.csv');
}
function exportDetailCSV(){
    if(!_lastDetail){return;}
    var h=_lastDetail.header;
    var csv='No Jurnal: '+h.no_jurnal+'\nNo Bukti: '+h.no_bukti+'\n\nKode,Nama Rekening,Tipe,Balance,Debet,Kredit\n';
    _lastDetail.detail.forEach(function(r){
        csv+='"'+r.kd_rek+'","'+r.nm_rek+'","'+r.tipe+'","'+r.balance+'",'+r.debet+','+r.kredit+'\n';
    });
    dlCSV(csv,'DetailJurnal_'+h.no_jurnal+'.csv');
}
function dlCSV(csv,fname){
    var blob=new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8;'});
    var a=document.createElement('a'); a.href=URL.createObjectURL(blob);
    a.download=fname; document.body.appendChild(a); a.click(); document.body.removeChild(a);
}
</script>
<?php $page_js = ob_get_clean(); ?>
<?php require_once('includes/footer.php'); ?>
