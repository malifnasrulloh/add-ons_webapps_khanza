<?php
$page_title = "Neraca Saldo Visual";
require_once('includes/header.php');
?>
<style>
    .ns-header {
        background: linear-gradient(135deg, rgba(56,189,248,0.15), rgba(99,102,241,0.15));
        border: 1px solid rgba(56,189,248,0.3);
        border-radius: 16px; padding: 20px 24px; margin-bottom: 24px;
        display: flex; align-items: center; gap: 16px;
    }
    .ns-header .icon-box {
        width: 52px; height: 52px;
        background: linear-gradient(135deg, #38bdf8, #6366f1);
        border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; color: #fff;
        box-shadow: 0 4px 15px rgba(56,189,248,0.4); flex-shrink: 0;
    }
    .ns-header h1 { font-size: 1.4rem; font-weight: 700; margin: 0; }
    .ns-header p  { font-size: 0.82rem; margin: 0; opacity: 0.7; }

    .filter-glass {
        background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);
        border-radius: 14px; padding: 18px 20px; margin-bottom: 20px; backdrop-filter: blur(8px);
    }

    .table-ns thead th {
        background: rgba(56,189,248,0.15) !important;
        font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: none;
    }
    .table-ns tbody td {
        font-size: 0.85rem; vertical-align: middle;
    }
    .table-ns .num-val { font-family: monospace; font-size: 0.9rem; text-align: right; }
    
    .heat-bar-bg {
        background: rgba(255,255,255,0.1); border-radius: 4px; height: 8px; width: 100%; overflow: hidden; margin-top: 4px;
    }
    .heat-bar-fill {
        background: linear-gradient(90deg, #38bdf8, #ef4444); height: 100%; transition: width 0.5s ease;
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

<div class="ns-header">
    <div class="icon-box"><i class="fas fa-balance-scale"></i></div>
    <div>
        <h1>Neraca Saldo Visual & Heatmap</h1>
        <p>Menampilkan saldo seluruh rekening dengan indikator aktivitas transaksi (Heatmap)</p>
    </div>
</div>

<div class="info-edukatif">
    <button class="btn btn-info-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEdu">
        <i class="fas fa-lightbulb text-warning me-2"></i>Panduan Membaca Laporan Neraca Saldo (Klik untuk Buka/Tutup)
    </button>
    <div class="collapse" id="collapseEdu">
        <div class="info-content">
            <p>Neraca Saldo (Trial Balance) adalah daftar yang berisi seluruh akun/rekening buku besar beserta saldo akhirnya pada suatu periode tertentu. Tujuannya adalah untuk memastikan bahwa total sisi Debet dan Kredit seimbang (Balance).</p>
            <div class="row">
                <div class="col-md-6">
                    <h6>📌 Apa itu Activity Heatmap?</h6>
                    <ul>
                        <li><strong class="text-info">Heatmap (Batang Gradasi):</strong> Menunjukkan seberapa sering rekening tersebut digunakan/bertransaksi dalam periode yang dipilih.</li>
                        <li><strong class="text-danger">Batang Panjang/Merah:</strong> Rekening sangat aktif (banyak transaksi terjadi di rekening ini).</li>
                        <li><strong class="text-primary">Batang Pendek/Biru:</strong> Rekening jarang digunakan (sedikit transaksi).</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>📊 Cara Penggunaan (Drill-Down)</h6>
                    <ul>
                        <li><strong>Klik Baris Rekening:</strong> Jika Anda melihat saldo akhir yang mencurigakan atau ingin tahu dari mana saldo tersebut berasal, cukup <strong>klik pada baris rekening tersebut</strong>.</li>
                        <li>Sistem akan otomatis membuka <strong>Buku Besar</strong> yang menampilkan seluruh rincian transaksi pembentuk saldo tersebut dari hari ke hari.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

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
            <button id="btn-load" class="btn btn-sm px-4 fw-semibold" style="background:linear-gradient(135deg,#38bdf8,#6366f1);color:#fff;border:none;" onclick="loadNS()">
                <i class="fas fa-search me-1"></i> Tampilkan Data
            </button>
            <button class="btn btn-outline-info btn-sm ms-2" onclick="exportCSV()"><i class="fas fa-file-excel"></i></button>
        </div>
    </div>
</div>

<div id="loader" class="text-center py-5" style="display:none;">
    <div class="spinner-border text-info" style="width: 3rem; height: 3rem;"></div>
    <p class="mt-3 text-muted">Menyusun Neraca Saldo...</p>
</div>

<div id="dashboard-content" style="display: none;">
    <div class="card shadow-sm">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold" style="color:#60a5fa;"><i class="fas fa-list me-2"></i>Daftar Rekening Aktif</h6>
            <span class="badge bg-info" id="badge-total">0 Rekening</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 table-ns">
                    <thead>
                        <tr>
                            <th style="width:100px;">Kode</th>
                            <th style="width:250px;">Nama Rekening</th>
                            <th class="text-end">Saldo Awal</th>
                            <th class="text-end">Debet</th>
                            <th class="text-end">Kredit</th>
                            <th class="text-end">Saldo Akhir</th>
                            <th style="width:150px;" class="text-center">Activity Heatmap</th>
                        </tr>
                    </thead>
                    <tbody id="tbl-body"></tbody>
                    <tfoot id="tbl-foot" style="background: rgba(255,255,255,0.05); font-weight: bold;"></tfoot>
                </table>
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

<!-- DETAIL JURNAL MODAL (Level 3 & 4) -->
<div class="modal fade" id="detail-modal" tabindex="-1" style="z-index:1060;">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detail-modal-title"><i class="fas fa-search me-2 text-info"></i>Detail Jurnal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detail-modal-body"></div>
            <div class="modal-footer py-2 gap-2 flex-wrap">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <button class="btn btn-outline-warning btn-sm" id="btn-trace-bukti" onclick="openTraceBukti()" style="display:none">
                    <i class="fas fa-route me-1"></i>Trace No.Bukti
                </button>
                <button class="btn btn-outline-info btn-sm" onclick="exportDetailCSV()"><i class="fas fa-file-excel me-1"></i>Export Detail</button>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
var _rawNS = [];
var _bubesData = null;
var _bubesTitle = "";
var _bbModal = null;

function fRp(angka) {
    if (angka === null || isNaN(angka)) return '0';
    var abs = Math.abs(angka);
    var s = abs.toLocaleString('id-ID');
    return angka < 0 ? '(' + s + ')' : s;
}

function fRpFull(angka) {
    if (angka === null || angka === undefined || isNaN(angka)) return '0';
    var neg = angka < 0;
    var abs = Math.abs(Math.round(angka));
    var s   = abs.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    return (neg ? '(' + s + ')' : s);
}

function loadNS() {
    var tgl1 = $('#inp-tgl1').val();
    var tgl2 = $('#inp-tgl2').val();
    if(!tgl1 || !tgl2) return;
    
    $('#dashboard-content').hide();
    $('#loader').show();
    $('#btn-load').prop('disabled', true);

    $.ajax({
        url: 'api/data_akuntansi_neraca_saldo.php',
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
            $('#dashboard-content').fadeIn();
            
            _rawNS = res.data;
            $('#badge-total').text(res.data.length + ' Rekening');
            renderTable(res.data, res.max_activity);
        },
        error: function(err) {
            $('#loader').hide();
            $('#btn-load').prop('disabled', false);
            $('#loader').html('<div class="alert alert-danger">Kesalahan jaringan.</div>').show();
        }
    });
}

function renderTable(data, maxActivity) {
    var html = '';
    var totSA = 0, totD = 0, totK = 0, totSAk = 0;

    data.forEach(function(r) {
        var pct = (r.activity_count / maxActivity) * 100;
        var actLabel = r.activity_count + ' tx';

        totSA += r.saldo_awal;
        totD += r.debet;
        totK += r.kredit;
        totSAk += r.saldo_akhir;

        html += '<tr style="cursor:pointer;" onclick="openBubesModal(\''+r.kd_rek+'\', \''+r.nm_rek+'\')" title="Klik untuk lihat rincian Buku Besar">';
        html += '<td><code>'+r.kd_rek+'</code></td>';
        html += '<td>'+r.nm_rek+'</td>';
        html += '<td class="num-val" style="color:#cbd5e1;">'+fRp(r.saldo_awal)+'</td>';
        html += '<td class="num-val" style="color:#38bdf8;">'+(r.debet > 0 ? fRp(r.debet) : '-')+'</td>';
        html += '<td class="num-val" style="color:#4ade80;">'+(r.kredit > 0 ? fRp(r.kredit) : '-')+'</td>';
        html += '<td class="num-val" style="color:#c4b5fd;">'+fRp(r.saldo_akhir)+'</td>';
        html += '<td><div style="font-size:0.7rem;text-align:right;opacity:0.8">'+actLabel+'</div><div class="heat-bar-bg"><div class="heat-bar-fill" style="width:'+pct+'%;"></div></div></td>';
        html += '</tr>';
    });

    $('#tbl-body').html(html);

    var footHtml = '<tr><td colspan="2" class="text-end">TOTAL</td>';
    footHtml += '<td class="num-val text-white">'+fRp(totSA)+'</td>';
    footHtml += '<td class="num-val" style="color:#38bdf8;">'+fRp(totD)+'</td>';
    footHtml += '<td class="num-val" style="color:#4ade80;">'+fRp(totK)+'</td>';
    footHtml += '<td class="num-val text-white">'+fRp(totSAk)+'</td>';
    footHtml += '<td></td></tr>';
    $('#tbl-foot').html(footHtml);
}

function openBubesModal(kd_rek, nm_rek) {
    var tgl1 = $('#inp-tgl1').val();
    var tgl2 = $('#inp-tgl2').val();
    
    if (!_bbModal) {
        _bbModal = new bootstrap.Modal(document.getElementById('bubes-modal'));
    }
    
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
                    html += '<tr class="'+(isAwal ? 'row-awal' : 'bubes-entry')+'"' + (!isAwal ? ' style="cursor:pointer;" onclick="openDetail(\''+r.no_jurnal+'\')" title="Klik untuk lihat pasangan jurnal & trace no.bukti"' : '') + '>';
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

function exportCSV() {
    if (!_rawNS.length) { alert('Tidak ada data.'); return; }
    var csv = 'Kode,Nama Rekening,Saldo Awal,Debet,Kredit,Saldo Akhir,Jml Transaksi\n';
    _rawNS.forEach(function(r) {
        csv += '"'+r.kd_rek+'","'+r.nm_rek+'",'+r.saldo_awal+','+r.debet+','+r.kredit+','+r.saldo_akhir+','+r.activity_count+'\n';
    });
    var blob = new Blob(['\uFEFF'+csv], {type:'text/csv;charset=utf-8;'});
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'Neraca_Saldo_Visual_' + $('#inp-tgl1').val() + '.csv';
    a.click();
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

$(document).ready(function() {
    // loadNS();
});

/* ─── DRILL-DOWN LEVEL 3 & 4 ─── */
var _detailModal = null;
var _lastDetail = null;

function openDetail(no_jurnal) {
    if (!no_jurnal || no_jurnal === 'SALDO AWAL') return;
    if (!_detailModal) _detailModal = new bootstrap.Modal(document.getElementById('detail-modal'));
    var body = document.getElementById('detail-modal-body');
    document.getElementById('detail-modal-title').innerHTML = '<i class="fas fa-search me-2 text-info"></i>Detail Jurnal: <code>' + no_jurnal + '</code>';
    body.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-info"></div> Memuat detail...</div>';
    document.getElementById('btn-trace-bukti').style.display = 'none';
    _detailModal.show();
    fetch('api/akuntansi_jurnal_detail.php?no_jurnal=' + encodeURIComponent(no_jurnal))
        .then(function(r){ return r.json(); })
        .then(function(d) {
            if (!d.success) { body.innerHTML = '<div class="alert alert-danger">' + d.message + '</div>'; return; }
            _lastDetail = d;
            var h = d.header;
            var btn = document.getElementById('btn-trace-bukti');
            if (h.no_bukti && h.no_bukti !== '-') { btn.style.display = ''; btn.dataset.nobukti = h.no_bukti; }
            body.innerHTML = buildSingleJurnalHtml(h, d.detail, d.ttl_debet, d.ttl_kredit, d.balanced);
        })
        .catch(function(e) { body.innerHTML = '<div class="alert alert-danger">Error: ' + e.message + '</div>'; });
}

function buildSingleJurnalHtml(h, detail, ttl_d, ttl_k, balanced) {
    var balBadge = balanced ? '<span class="badge-balanced"><i class="fas fa-check me-1"></i>BALANCED</span>' : '<span class="badge-unbalanced"><i class="fas fa-exclamation me-1"></i>TIDAK BALANCED</span>';
    var html = '<div class="detail-header-card"><div class="row g-3">'
        + '<div class="col-md-3"><div class="d-label">No. Jurnal</div><div class="d-value"><code>' + h.no_jurnal + '</code></div></div>'
        + '<div class="col-md-2"><div class="d-label">Tanggal</div><div class="d-value">' + h.tgl_jurnal + ' ' + (h.jam_jurnal||'') + '</div></div>'
        + '<div class="col-md-2"><div class="d-label">No. Bukti</div><div class="d-value"><strong style="color:#fbbf24">' + (h.no_bukti||'-') + '</strong></div></div>'
        + '<div class="col-md-3"><div class="d-label">Keterangan</div><div class="d-value">' + (h.keterangan||'-') + '</div></div>'
        + '<div class="col-md-2"><div class="d-label">Status</div><div class="d-value mt-1">' + balBadge + '</div></div>'
        + '</div></div>';
    html += '<div class="table-responsive"><table class="table table-sm table-hover mb-0 tbl-detail">'
        + '<thead><tr><th>Kode</th><th>Nama Rekening</th><th>Tipe</th><th class="text-end">Debet</th><th class="text-end">Kredit</th><th>Ket.</th></tr></thead><tbody>';
    detail.forEach(function(row) {
        html += '<tr><td><code class="small">' + row.kd_rek + '</code></td><td class="small">' + row.nm_rek + '</td>'
            + '<td><span class="badge bg-secondary">' + row.tipe + '</span> <span class="badge ' + (row.balance==='D'?'bg-primary':'bg-success') + '">' + row.balance + '</span></td>'
            + '<td class="text-end small">' + (row.debet>0 ? '<span style="color:#38bdf8;font-weight:600">'+fRpFull(row.debet)+'</span>' : '<span class="text-muted">-</span>') + '</td>'
            + '<td class="text-end small">' + (row.kredit>0 ? '<span style="color:#4ade80;font-weight:600">'+fRpFull(row.kredit)+'</span>' : '<span class="text-muted">-</span>') + '</td>'
            + '<td class="small text-muted">' + (row.keterangan||'-') + '</td></tr>';
    });
    html += '</tbody><tfoot><tr style="font-weight:700;background:rgba(255,255,255,.04)"><td colspan="3">TOTAL</td>'
        + '<td class="text-end" style="color:#38bdf8">' + fRpFull(ttl_d) + '</td>'
        + '<td class="text-end" style="color:#4ade80">' + fRpFull(ttl_k) + '</td>'
        + '<td>' + (balanced ? '' : '<span class="text-warning small">Selisih: '+fRpFull(ttl_d-ttl_k)+'</span>') + '</td>'
        + '</tr></tfoot></table></div>';
    return html;
}

function openTraceBukti() {
    var btn = document.getElementById('btn-trace-bukti');
    var noBukti = (btn && btn.dataset.nobukti) ? btn.dataset.nobukti : '';
    if (!noBukti || noBukti === '-') { alert('No. Bukti tidak tersedia.'); return; }
    var body = document.getElementById('detail-modal-body');
    document.getElementById('detail-modal-title').innerHTML = '<i class="fas fa-route me-2" style="color:#fbbf24"></i>Audit Trail — No.Bukti: <code>' + noBukti + '</code>';
    body.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-warning"></div> Menelusuri semua jurnal...</div>';
    btn.style.display = 'none';
    fetch('api/akuntansi_jurnal_detail.php?trace_bukti=' + encodeURIComponent(noBukti))
        .then(function(r){ return r.json(); })
        .then(function(d) {
            if (!d.success) { body.innerHTML = '<div class="alert alert-danger">' + d.message + '</div>'; return; }
            var html = '<div class="alert" style="background:rgba(251,191,36,.1);border:1px solid rgba(251,191,36,.3);border-radius:8px;padding:10px 14px;margin-bottom:12px;">'
                + '<i class="fas fa-info-circle me-2" style="color:#fbbf24"></i><strong>' + d.jurnal_count + ' jurnal</strong> untuk No.Bukti <code>' + d.no_bukti + '</code>'
                + ' Debet: <span style="color:#38bdf8">' + fRpFull(d.grand_debet) + '</span> Kredit: <span style="color:#4ade80">' + fRpFull(d.grand_kredit) + '</span>'
                + (d.grand_balanced ? ' <span class="badge-balanced">BALANCED</span>' : ' <span class="badge-unbalanced">TIDAK BALANCED</span>') + '</div>';
            d.groups.forEach(function(g, i) {
                html += '<div style="border:1px solid rgba(255,255,255,.1);border-radius:8px;margin-bottom:10px;overflow:hidden">'
                    + '<div style="background:rgba(56,189,248,.1);padding:8px 14px;font-size:.82rem;font-weight:600;cursor:pointer" onclick="toggleTS(\'ts'+i+'\')">'
                    + '<i class="fas fa-chevron-down me-2" id="ts-ic-'+i+'" style="font-size:.7rem"></i><code>' + g.header.no_jurnal + '</code> ' + g.header.tgl_jurnal
                    + ' | ' + g.entry_count + ' baris D:<span style="color:#38bdf8"> ' + fRpFull(g.ttl_debet) + '</span> K:<span style="color:#4ade80"> ' + fRpFull(g.ttl_kredit) + '</span>'
                    + (g.balanced ? ' <span class="badge-balanced" style="font-size:.65rem">OK</span>' : ' <span class="badge-unbalanced" style="font-size:.65rem">!</span>')
                    + '</div><div id="ts'+i+'">' + buildSingleJurnalHtml(g.header, g.detail, g.ttl_debet, g.ttl_kredit, g.balanced) + '</div></div>';
            });
            body.innerHTML = html;
        })
        .catch(function(e) { body.innerHTML = '<div class="alert alert-danger">Error: ' + e.message + '</div>'; });
}

function toggleTS(id) {
    var el = document.getElementById(id), ic = document.getElementById('ts-ic-' + id.replace('ts',''));
    if (!el) return;
    var h = el.style.display === 'none';
    el.style.display = h ? '' : 'none';
    if (ic) ic.style.transform = h ? '' : 'rotate(-90deg)';
}

function exportDetailCSV() {
    if (!_lastDetail) return;
    var h = _lastDetail.header;
    var csv = 'Kode,Nama Rekening,Tipe,Saldo,Debet,Kredit,Keterangan\n';
    _lastDetail.detail.forEach(function(r) {
        csv += '"'+r.kd_rek+'","'+r.nm_rek+'","'+r.tipe+'","'+r.balance+'",'+r.debet+','+r.kredit+',"'+(r.keterangan||'').replace(/"/g,'""')+'"\n';
    });
    var blob = new Blob(['\uFEFF'+csv], {type:'text/csv;charset=utf-8;'});
    var a = document.createElement('a'); a.href = URL.createObjectURL(blob);
    a.download = 'Detail_' + h.no_jurnal + '.csv'; document.body.appendChild(a); a.click(); document.body.removeChild(a);
}

(function(){
    var s = document.createElement('style');
    s.textContent = '#detail-modal .modal-content{max-height:90vh;overflow-y:auto}.detail-header-card{background:rgba(56,189,248,.08);border:1px solid rgba(56,189,248,.2);border-radius:10px;padding:12px 16px;margin-bottom:12px;font-size:.85rem}.detail-header-card .d-label{font-size:.7rem;text-transform:uppercase;opacity:.6;letter-spacing:.05em}.detail-header-card .d-value{font-weight:600;color:#e2e8f0}.badge-balanced{background:rgba(16,185,129,.2);border:1px solid rgba(16,185,129,.4);color:#4ade80;border-radius:6px;padding:3px 10px;font-size:.75rem;font-weight:700}.badge-unbalanced{background:rgba(239,68,68,.2);border:1px solid rgba(239,68,68,.4);color:#f87171;border-radius:6px;padding:3px 10px;font-size:.75rem;font-weight:700}.tbl-detail thead th{background:rgba(56,189,248,.15)!important;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em}.bubes-entry:hover td{background:rgba(255,193,7,.06)!important;cursor:pointer}';
    document.head.appendChild(s);
})();
</script>
<?php $page_js = ob_get_clean(); ?>
<?php require_once('includes/footer.php'); ?>