<?php
/**
 * akuntansi_bubes.php
 * Halaman Buku Besar — Modul Accounting.
 * Replika KeuanganBubes.java dengan UI premium.
 * 
 * Filter: Rekening (wajib pilih), Tahun, Bulan (opsional), Tanggal (opsional).
 * Output: Saldo Awal, tabel mutasi bergulir dengan Saldo per baris.
 */
$page_title = "Buku Besar";
require_once('includes/header.php');
?>
<style>
    .accounting-header {
        background: linear-gradient(135deg, rgba(139,92,246,0.15), rgba(59,130,246,0.15));
        border: 1px solid rgba(139,92,246,0.3);
        border-radius: 16px; padding: 20px 24px; margin-bottom: 24px;
        display: flex; align-items: center; gap: 16px;
    }
    .accounting-header .icon-box {
        width: 52px; height: 52px;
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; color: #fff;
        box-shadow: 0 4px 15px rgba(139,92,246,0.4); flex-shrink: 0;
    }
    .accounting-header h1 { font-size: 1.4rem; font-weight: 700; margin: 0; }
    .accounting-header p  { font-size: 0.82rem; margin: 0; opacity: 0.7; }

    .filter-glass {
        background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);
        border-radius: 14px; padding: 18px 20px; margin-bottom: 20px;
        backdrop-filter: blur(8px);
    }

    /* Saldo Info Card */
    .saldo-info-card {
        background: linear-gradient(135deg, rgba(139,92,246,0.2), rgba(59,130,246,0.15));
        border: 1px solid rgba(139,92,246,0.3);
        border-radius: 12px; padding: 14px 20px; margin-bottom: 16px;
        display: flex; gap: 24px; align-items: center; flex-wrap: wrap;
    }
    .saldo-info-card .info-item { text-align: center; }
    .saldo-info-card .info-item .label { font-size: 0.7rem; text-transform: uppercase; opacity: 0.6; letter-spacing: 0.05em; }
    .saldo-info-card .info-item .value { font-size: 1.1rem; font-weight: 700; color: #c4b5fd; }
    .saldo-info-card .info-item .value.akhir { color: #4ade80; }

    /* Tabel Buku Besar */
    .tbl-bubes thead th {
        background: linear-gradient(135deg, rgba(139,92,246,0.3), rgba(59,130,246,0.2)) !important;
        color: #e2e8f0 !important; font-size: 0.78rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.05em;
        border-color: rgba(255,255,255,0.1) !important; white-space: nowrap;
    }
    .tbl-bubes .num-right { text-align: right; font-variant-numeric: tabular-nums; }
    .saldo-pos { color: #4ade80; }
    .saldo-neg { color: #f87171; }

    .rek-chip {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3);
        border-radius: 20px; padding: 4px 12px; font-size: 0.82rem; cursor: pointer;
        transition: all 0.2s; min-width: 180px;
    }
    .rek-chip:hover { background: rgba(139,92,246,0.25); }
    .rek-chip .rek-code { font-weight: 700; color: #c4b5fd; }
    .tbl-loading { text-align: center; padding: 60px 20px; opacity: 0.5; }
    .row-awal td { font-weight: 700; background: rgba(139,92,246,0.1) !important; color: #c4b5fd !important; }

    /* Baris data */
    .tr-entry { transition: background 0.15s; cursor: pointer; }
    .tr-entry:hover { background: rgba(139,92,246,0.06) !important; }
    .tr-entry.selected { background: rgba(139,92,246,0.12) !important; }

    /* Beautifikasi Kolom Finansial */
    .col-s-awal { background-color: rgba(255, 255, 255, 0.02) !important; color: #cbd5e1; font-family: 'Consolas', 'Courier New', monospace; }
    .col-debet { background-color: rgba(56, 189, 248, 0.04) !important; color: #38bdf8; font-family: 'Consolas', 'Courier New', monospace; font-weight: 600; }
    .col-kredit { background-color: rgba(74, 222, 128, 0.04) !important; color: #4ade80; font-family: 'Consolas', 'Courier New', monospace; font-weight: 600; }
    .col-s-akhir { background-color: rgba(139, 92, 246, 0.05) !important; color: #c4b5fd; font-family: 'Consolas', 'Courier New', monospace; font-weight: 700; }
    
    .tr-entry:hover .col-debet { background-color: rgba(56, 189, 248, 0.09) !important; }
    .tr-entry:hover .col-kredit { background-color: rgba(74, 222, 128, 0.09) !important; }
    .tr-entry:hover .col-s-akhir { background-color: rgba(139, 92, 246, 0.1) !important; }

    .text-muted-zero { opacity: 0.3; font-weight: 400; color: #94a3b8 !important; }

    /* Drill-down modal */
    #detail-modal .modal-content { max-height: 90vh; overflow-y: auto; }
    .detail-header-card { background: rgba(56,189,248,0.08); border: 1px solid rgba(56,189,248,0.2); border-radius: 10px; padding: 12px 16px; margin-bottom: 12px; font-size: 0.85rem; }
    .detail-header-card .d-label { font-size: 0.7rem; text-transform: uppercase; opacity: 0.6; letter-spacing: 0.05em; }
    .detail-header-card .d-value { font-weight: 600; color: #e2e8f0; }
    .badge-balanced { background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.4); color: #4ade80; border-radius: 6px; padding: 3px 10px; font-size: 0.75rem; font-weight: 700; }
    .badge-unbalanced { background: rgba(239,68,68,0.2); border: 1px solid rgba(239,68,68,0.4); color: #f87171; border-radius: 6px; padding: 3px 10px; font-size: 0.75rem; font-weight: 700; }
    .tbl-detail thead th { background: rgba(56,189,248,0.15) !important; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; }
    .tbl-detail .debet-val { color: #38bdf8; font-weight: 600; }
    .tbl-detail .kredit-val { color: #4ade80; font-weight: 600; }
</style>

    <div class="accounting-header">
        <div class="icon-box"><i class="fas fa-book"></i></div>
        <div>
            <h1>Buku Besar</h1>
            <p>Mutasi saldo per rekening akuntansi dengan saldo bergulir (running balance)</p>
        </div>
    </div>

    <!-- FILTER -->
    <div class="filter-glass">
        <div class="row g-3 align-items-end">
            <!-- Rekening (wajib pilih) -->
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Rekening <span class="text-danger">*</span></label>
                <div id="rek-chip-wrap" class="rek-chip" onclick="openRekeningModal()" title="Klik untuk pilih rekening">
                    <i class="fas fa-search-dollar" style="color:#c4b5fd; font-size:0.8rem;"></i>
                    <span id="rek-chip-text" class="text-muted">-- Klik untuk pilih rekening --</span>
                </div>
                <input type="hidden" id="inp-kdrek" value="">
                <input type="hidden" id="inp-nmrek" value="">
            </div>
            <!-- Tahun -->
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Tahun</label>
                <select id="inp-tahun" class="form-select form-select-sm">
                    <?php for ($y=date('Y'); $y>=date('Y')-10; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo ($y==date('Y')?'selected':''); ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <!-- Bulan -->
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Bulan <small class="text-muted">(Opsional)</small></label>
                <select id="inp-bulan" class="form-select form-select-sm">
                    <option value="">Semua Bulan</option>
                    <?php
                    $bulan_names = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April',
                                    '05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus',
                                    '09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
                    foreach ($bulan_names as $num => $nm):
                    ?>
                    <option value="<?php echo $num; ?>"><?php echo $nm; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Tanggal -->
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Tanggal <small class="text-muted">(Opsional)</small></label>
                <select id="inp-tanggal" class="form-select form-select-sm">
                    <option value="">Semua Tanggal</option>
                    <?php for ($t=1; $t<=31; $t++): ?>
                    <option value="<?php echo str_pad($t,2,'0',STR_PAD_LEFT); ?>"><?php echo str_pad($t,2,'0',STR_PAD_LEFT); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <!-- Actions -->
            <div class="col-md-2">
                <button id="btn-load" class="btn btn-sm w-100" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:#fff;border:none;" onclick="loadData()">
                    <i class="fas fa-search me-1"></i> Tampilkan
                </button>
            </div>
        </div>
    </div>

    <!-- Saldo Info -->
    <div class="saldo-info-card" id="saldo-info" style="display:none;">
        <div class="info-item">
            <div class="label">Rekening</div>
            <div class="value" id="inf-rek">-</div>
        </div>
        <div class="info-item">
            <div class="label">Balance Normal</div>
            <div class="value" id="inf-balance">-</div>
        </div>
        <div class="info-item">
            <div class="label">Saldo Awal Periode</div>
            <div class="value" id="inf-sawal">Rp 0</div>
        </div>
        <div class="info-item">
            <div class="label">Saldo Akhir Periode</div>
            <div class="value akhir" id="inf-sakhir">Rp 0</div>
        </div>
        <div class="info-item">
            <div class="label">Jumlah Mutasi</div>
            <div class="value" id="inf-count">0 baris</div>
        </div>
    </div>

    <!-- Tabel -->
    <div class="card shadow-sm" id="card-table" style="display:none;">
        <div class="card-header py-2 d-flex align-items-center justify-content-between">
            <h6 class="m-0 fw-bold" style="color:#c4b5fd;">
                <i class="fas fa-book me-2"></i>Mutasi Buku Besar
            </h6>
            <button class="btn btn-outline-secondary btn-sm" onclick="exportExcel()">
                <i class="fas fa-file-excel me-1"></i> Export CSV
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 tbl-bubes" id="tbl-bubes">
                    <thead>
                        <tr>
                            <th style="width:160px;">Tanggal &amp; Jam</th>
                            <th style="width:100px;">No. Jurnal</th>
                            <th style="width:100px;">No. Bukti</th>
                            <th>Keterangan</th>
                            <th class="num-right" style="width:130px;">Saldo Awal (Rp)</th>
                            <th class="num-right" style="width:120px;">Debet (Rp)</th>
                            <th class="num-right" style="width:120px;">Kredit (Rp)</th>
                            <th class="num-right" style="width:130px;">Saldo Akhir (Rp)</th>
                        </tr>
                    </thead>
                    <tbody id="tbl-body">
                        <tr><td colspan="8" class="tbl-loading"><i class="fas fa-info-circle me-2"></i>Pilih rekening dan klik Tampilkan</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Rekening -->
    <div class="modal fade" id="rek-modal" tabindex="-1" aria-labelledby="rekModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rekModalLabel"><i class="fas fa-search-dollar me-2 text-warning"></i>Pilih Rekening</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-2">
                    <input type="text" id="rek-search" class="form-control form-control-sm mb-2" placeholder="Ketik kode atau nama rekening...">
                    <div style="max-height:400px;overflow-y:auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>Kode</th><th>Nama Rekening</th><th>Tipe</th><th>Balance</th><th class="text-end">Saldo Awal</th></tr></thead>
                            <tbody id="rek-tbl-body"><tr><td colspan="5" class="text-center text-muted py-4">Ketik untuk mencari...</td></tr></tbody>
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
                    <button class="btn btn-outline-info btn-sm" onclick="exportDetailCSV()"><i class="fas fa-file-excel me-1"></i>Export CSV</button>
                </div>
            </div>
        </div>
    </div>
<?php ob_start(); ?>
<script>
var _rekModal = null;
var _allData  = [];

function fRp(angka) {
    if (angka === null || angka === undefined || isNaN(angka)) return '-';
    var neg = angka < 0;
    var abs = Math.abs(Math.round(angka));
    var s   = abs.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    return (neg ? '(Rp ' + s + ')' : 'Rp ' + s);
}

function openRekeningModal() {
    if (!_rekModal) { _rekModal = new bootstrap.Modal(document.getElementById('rek-modal')); }
    document.getElementById('rek-search').value = '';
    cariRekening('');
    _rekModal.show();
    setTimeout(function(){ document.getElementById('rek-search').focus(); }, 400);
}

function cariRekening(q) {
    var tahun = document.getElementById('inp-tahun').value;
    fetch('api/akuntansi_cari_rekening.php?q=' + encodeURIComponent(q) + '&thn=' + tahun)
        .then(function(r){ return r.json(); })
        .then(function(d) {
            var tbody = document.getElementById('rek-tbl-body');
            if (!d.success || d.results.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Tidak ditemukan.</td></tr>';
                return;
            }
            tbody.innerHTML = d.results.map(function(r) {
                return '<tr onclick="pilihRekening(\'' + r.kd_rek + '\',\'' + r.nm_rek.replace(/'/g,"\\'") + '\')" style="cursor:pointer;">'
                    + '<td><strong>' + r.kd_rek + '</strong></td>'
                    + '<td>' + r.nm_rek + '</td>'
                    + '<td><span class="badge bg-secondary">' + r.tipe + '</span></td>'
                    + '<td><span class="badge ' + (r.balance==='D'?'bg-primary':'bg-success') + '">' + r.balance + '</span></td>'
                    + '<td class="text-end">' + fRp(r.saldo_awal) + '</td></tr>';
            }).join('');
        });
}

function pilihRekening(kd, nm) {
    document.getElementById('inp-kdrek').value = kd;
    document.getElementById('inp-nmrek').value = nm;
    document.getElementById('rek-chip-text').innerHTML = '<span class="rek-code">' + kd + '</span>&nbsp;— ' + nm;
    if (_rekModal) { _rekModal.hide(); }
}

function loadData() {
    var kd  = document.getElementById('inp-kdrek').value;
    if (!kd) { alert('Silakan pilih rekening terlebih dahulu!'); openRekeningModal(); return; }
    var thn = document.getElementById('inp-tahun').value;
    var bln = document.getElementById('inp-bulan').value;
    var tgl = document.getElementById('inp-tanggal').value;

    var btn = document.getElementById('btn-load');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

    var tbody = document.getElementById('tbl-body');
    tbody.innerHTML = '<tr><td colspan="8" class="tbl-loading"><div class="spinner-border spinner-border-sm" style="color:#c4b5fd;"></div><br>Memuat...</td></tr>';
    document.getElementById('card-table').style.display = 'block';
    document.getElementById('saldo-info').style.display = 'none';

    var params = 'kd_rek=' + encodeURIComponent(kd) + '&tahun=' + encodeURIComponent(thn)
               + '&bulan=' + encodeURIComponent(bln) + '&tanggal=' + encodeURIComponent(tgl);

    fetch('api/akuntansi_bubes.php?' + params)
        .then(function(r){ return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search me-1"></i> Tampilkan';
            renderTable(data);
        })
        .catch(function(e) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search me-1"></i> Tampilkan';
            tbody.innerHTML = '<tr><td colspan="8" class="tbl-loading text-danger">Error: ' + e.message + '</td></tr>';
        });
}

function renderTable(data) {
    var tbody = document.getElementById('tbl-body');
    if (!data.success) {
        tbody.innerHTML = '<tr><td colspan="8" class="tbl-loading text-danger">' + data.message + '</td></tr>';
        return;
    }

    // Saldo info
    var displayRek = (data.kd_rek || '') + (data.nm_rek ? ' — ' + data.nm_rek : '');
    document.getElementById('inf-rek').textContent     = displayRek || '-';
    
    var balText = '-';
    if (data.balance === 'D') balText = 'Debet (D)';
    else if (data.balance === 'K') balText = 'Kredit (K)';
    
    document.getElementById('inf-balance').textContent = balText;
    document.getElementById('inf-sawal').textContent   = fRp(data.saldo_awal);
    document.getElementById('inf-sakhir').textContent  = fRp(data.saldo_akhir);
    document.getElementById('inf-count').textContent   = (data.row_count || 0) + ' baris';
    document.getElementById('saldo-info').style.display = 'flex';

    if (!data.rows || data.rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="tbl-loading"><i class="fas fa-inbox me-2"></i>Tidak ada mutasi untuk periode ini.</td></tr>';
        return;
    }

    _allData = data.rows;

    // Saldo awal baris pertama (sebagai pembuka)
    var html = '<tr class="row-awal">';
    html += '<td colspan="4" class="fw-bold"><i class="fas fa-flag-checkered me-1"></i>Saldo Awal Periode</td>';
    html += '<td class="num-right fw-bold" colspan="3"></td>';
    html += '<td class="num-right fw-bold ' + (data.saldo_awal >= 0 ? 'saldo-pos' : 'saldo-neg') + '">' + fRp(data.saldo_awal) + '</td>';
    html += '</tr>';

    data.rows.forEach(function(row) {
        var saldoClass = row.saldo_akhir >= 0 ? 'saldo-pos' : 'saldo-neg';
        html += '<tr class="tr-entry" data-nojur="' + (row.no_jurnal||'') + '" data-nobukti="' + (row.no_bukti||'') + '">';
        html += '<td class="small">' + row.tgl_jurnal + '</td>';
        html += '<td><code class="small">' + row.no_jurnal + '</code></td>';
        html += '<td class="small">' + row.no_bukti + '</td>';
        html += '<td class="small text-muted">' + row.keterangan + '</td>';
        html += '<td class="num-right small col-s-awal">' + fRp(row.saldo_awal) + '</td>';
        html += '<td class="num-right small col-debet">' + (row.debet > 0 ? fRp(row.debet) : '<span class="text-muted-zero">-</span>') + '</td>';
        html += '<td class="num-right small col-kredit">' + (row.kredit > 0 ? fRp(row.kredit) : '<span class="text-muted-zero">-</span>') + '</td>';
        html += '<td class="num-right small fw-bold col-s-akhir ' + saldoClass + '">' + fRp(row.saldo_akhir) + '</td>';
        html += '</tr>';
    });

    // Baris saldo akhir
    html += '<tr class="row-awal">';
    html += '<td colspan="4" class="fw-bold"><i class="fas fa-flag me-1"></i>Saldo Akhir Periode</td>';
    html += '<td class="num-right" colspan="3"></td>';
    html += '<td class="num-right fw-bold ' + (data.saldo_akhir >= 0 ? 'saldo-pos' : 'saldo-neg') + '">' + fRp(data.saldo_akhir) + '</td>';
    html += '</tr>';

    tbody.innerHTML = html;
}

function exportExcel() {
    if (_allData.length === 0) { alert('Tidak ada data.'); return; }
    var kd = document.getElementById('inp-kdrek').value;
    var nm = document.getElementById('inp-nmrek').value;
    var csv = 'Tanggal,No Jurnal,No Bukti,Keterangan,Saldo Awal,Debet,Kredit,Saldo Akhir\n';
    _allData.forEach(function(r) {
        csv += '"'+r.tgl_jurnal+'","'+r.no_jurnal+'","'+r.no_bukti+'","'+r.keterangan+'",' + r.saldo_awal + ',' + r.debet + ',' + r.kredit + ',' + r.saldo_akhir + '\n';
    });
    var blob = new Blob(['\uFEFF'+csv], {type:'text/csv;charset=utf-8;'});
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'BukuBesar_' + kd + '_' + document.getElementById('inp-tahun').value + '.csv';
    link.click();
}

/* ─── Drill-down Detail Modal ─── */
var _detailModal = null;
var _lastDetail = null;

function openDetail(no_jurnal) {
    if (!no_jurnal) return;
    if (!_detailModal) _detailModal = new bootstrap.Modal(document.getElementById('detail-modal'));

    var body = document.getElementById('detail-modal-body');
    document.getElementById('detail-modal-title').innerHTML = '<i class="fas fa-search me-2 text-info"></i>Detail Jurnal: <code>' + no_jurnal + '</code>';
    body.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-info"></div> Memuat detail...</div>';
    _detailModal.show();

    /* Highlight baris yang dipilih */
    document.querySelectorAll('.tr-entry.selected').forEach(function(r){ r.classList.remove('selected'); });
    document.querySelectorAll('.tr-entry[data-nojur="' + no_jurnal + '"]').forEach(function(r){ r.classList.add('selected'); });

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


document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('rek-search').addEventListener('input', function() { cariRekening(this.value); });
    // Disable tanggal jika bulan belum dipilih
    document.getElementById('inp-bulan').addEventListener('change', function() {
        document.getElementById('inp-tanggal').disabled = !this.value;
        if (!this.value) { document.getElementById('inp-tanggal').value = ''; }
    });
    document.getElementById('inp-tanggal').disabled = true;
    cariRekening('');

    // Event Delegation untuk Drill-down Buku Besar
    $('#tbl-bubes').off('click.drilldown').on('click.drilldown', 'tbody tr.tr-entry', function() {
        var noj = $(this).data('nojur') || $(this).attr('data-nojur');
        if (noj) openDetail(noj);
    });
});
</script>
<?php $page_js = ob_get_clean(); ?>
<?php require_once('includes/footer.php'); ?>
