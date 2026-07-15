<?php
$page_title = "Rasio Keuangan";
require_once('includes/header.php');
?>
<style>
    .rasio-header {
        background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(139,92,246,0.15));
        border: 1px solid rgba(99,102,241,0.3);
        border-radius: 16px; padding: 20px 24px; margin-bottom: 24px;
        display: flex; align-items: center; gap: 16px;
    }
    .rasio-header .icon-box {
        width: 52px; height: 52px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; color: #fff;
        box-shadow: 0 4px 15px rgba(99,102,241,0.4); flex-shrink: 0;
    }
    .rasio-header h1 { font-size: 1.4rem; font-weight: 700; margin: 0; }
    .rasio-header p  { font-size: 0.82rem; margin: 0; opacity: 0.7; }

    .filter-glass {
        background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);
        border-radius: 14px; padding: 18px 20px; margin-bottom: 20px; backdrop-filter: blur(8px);
    }

    /* Scorecards */
    .rasio-card {
        background: rgba(30,41,59,0.7); border: 1px solid rgba(255,255,255,0.08);
        border-radius: 14px; padding: 20px; transition: transform 0.2s; height: 100%;
        display: flex; flex-direction: column; position: relative; overflow: hidden;
    }
    .rasio-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.3); }
    .rasio-card .icon-bg { position: absolute; right: -10px; bottom: -10px; font-size: 5rem; opacity: 0.03; }
    .rasio-card .card-title { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; opacity: 0.8; margin-bottom: 8px; }
    .rasio-card .card-value { font-size: 1.8rem; font-weight: 700; margin-bottom: 5px; }
    .rasio-card .card-status { font-size: 0.8rem; padding: 3px 8px; border-radius: 4px; display: inline-block; font-weight: 600; }
    
    .status-good { background: rgba(74,222,128,0.2); color: #4ade80; }
    .status-warn { background: rgba(250,204,21,0.2); color: #facc15; }
    .status-bad  { background: rgba(248,113,113,0.2); color: #f87171; }

    /* Info Edukatif */
    .info-edukatif { margin-bottom: 20px; }
    .btn-info-toggle {
        background: rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.4);
        color: #c4b5fd; border-radius: 20px; padding: 8px 16px; font-size: 0.85rem; font-weight: 600;
        transition: all 0.2s; width: 100%; text-align: left;
    }
    .btn-info-toggle:hover { background: rgba(139, 92, 246, 0.25); color: #fff; }
    .info-content {
        background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px; padding: 20px; margin-top: 10px; font-size: 0.85rem; line-height: 1.6; color: #cbd5e1;
    }
    .info-content h6 { color: #e2e8f0; font-weight: 700; margin-top: 15px; margin-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px; }
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

<div class="rasio-header">
    <div class="icon-box"><i class="fas fa-percent"></i></div>
    <div>
        <h1>Analisis Rasio Keuangan</h1>
        <p>Indikator kesehatan finansial: Likuiditas, Solvabilitas, dan Profitabilitas</p>
    </div>
</div>

<div class="info-edukatif">
    <button class="btn btn-info-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEdu">
        <i class="fas fa-lightbulb text-warning me-2"></i>Panduan Membaca Analisis Rasio (Klik untuk Buka/Tutup)
    </button>
    <div class="collapse" id="collapseEdu">
        <div class="info-content">
            <p>Rasio Keuangan adalah cara membandingkan dua angka dari laporan keuangan untuk menilai seberapa "sehat" rumah sakit ini beroperasi.</p>
            <div class="row">
                <div class="col-md-4">
                    <h6>💧 Likuiditas (Current Ratio)</h6>
                    <ul>
                        <li><strong>Apa ini:</strong> Kemampuan RS membayar hutang jangka pendek menggunakan aset lancar (kas, bank, piutang).</li>
                        <li><strong>Rumus:</strong> <code>Aset Lancar / Kewajiban Lancar</code></li>
                        <li><strong>Cara Baca:</strong> Angka <strong>2.0x</strong> berarti untuk setiap Rp 1 hutang, RS punya Rp 2 aset untuk membayarnya.</li>
                        <li><strong>Ideal:</strong> <span class="text-success">&gt; 1.5x</span> (Aman) | <span class="text-danger">&lt; 1.0x</span> (Bahaya gagal bayar).</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6>⚖️ Solvabilitas (DER)</h6>
                    <ul>
                        <li><strong>Apa ini:</strong> Debt to Equity Ratio (DER) mengukur seberapa besar RS dibiayai oleh hutang dibanding modal sendiri.</li>
                        <li><strong>Rumus:</strong> <code>Total Hutang / Total Ekuitas</code></li>
                        <li><strong>Cara Baca:</strong> Angka <strong>0.5x</strong> berarti hutang RS hanya setengah dari modal utamanya.</li>
                        <li><strong>Ideal:</strong> <span class="text-success">&lt; 1.0x</span> (Hutang terkendali) | <span class="text-danger">&gt; 2.0x</span> (Terlalu banyak hutang).</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6>📈 Profitabilitas (ROE/ROA)</h6>
                    <ul>
                        <li><strong>Apa ini:</strong> Mengukur seberapa efisien uang yang ditanamkan/dioperasikan menghasilkan Laba Bersih.</li>
                        <li><strong>Return on Equity (ROE):</strong> <code>Laba Bersih / Ekuitas</code> (Keuntungan atas modal).</li>
                        <li><strong>Return on Asset (ROA):</strong> <code>Laba Bersih / Total Aset</code> (Keuntungan dari seluruh kekayaan RS).</li>
                        <li><strong>Ideal:</strong> Semakin tinggi semakin bagus (biasanya <span class="text-success">&gt; 10%</span>).</li>
                    </ul>
                </div>
            </div>
            <div class="alert alert-info py-1 px-2 mt-3 mb-0" style="font-size:0.75rem;"><i class="fas fa-mouse-pointer me-1"></i>Anda dapat <strong>mengklik baris tabel</strong> di bawah untuk membedah rekening apa saja yang membentuk nilai Aset, Ekuitas, atau Kewajiban tersebut.</div>
        </div>
    </div>
</div>

<div class="filter-glass">
    <div class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Dari Tanggal (Untuk Laba Rugi)</label>
            <input type="date" id="inp-tgl1" class="form-control form-control-sm" value="<?php echo date('Y-m-01'); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Posisi Tanggal Akhir</label>
            <input type="date" id="inp-tgl2" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="col-md-4">
            <button id="btn-load" class="btn btn-sm px-4 fw-semibold" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;" onclick="loadRasio()">
                <i class="fas fa-calculator me-1"></i> Hitung Rasio
            </button>
        </div>
    </div>
</div>

<div id="loader" class="text-center py-5" style="display:none;">
    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
    <p class="mt-3 text-muted">Mengkalkulasi rasio keuangan...</p>
</div>

<div id="dashboard-content" style="display: none;">
    <div class="row g-3 mb-4">
        <!-- Current Ratio -->
        <div class="col-md-4">
            <div class="rasio-card">
                <i class="fas fa-water icon-bg text-info"></i>
                <div class="card-title text-info">Current Ratio (Likuiditas)</div>
                <div class="card-value" id="val-cr">0.00x</div>
                <div><span id="stat-cr" class="card-status"></span></div>
            </div>
        </div>
        <!-- DER -->
        <div class="col-md-4">
            <div class="rasio-card">
                <i class="fas fa-balance-scale-left icon-bg text-warning"></i>
                <div class="card-title text-warning">Debt to Equity Ratio</div>
                <div class="card-value" id="val-der">0.00x</div>
                <div><span id="stat-der" class="card-status"></span></div>
            </div>
        </div>
        <!-- ROE -->
        <div class="col-md-4">
            <div class="rasio-card">
                <i class="fas fa-chart-line icon-bg text-success"></i>
                <div class="card-title text-success">Return on Equity (ROE)</div>
                <div class="card-value" id="val-roe">0.00%</div>
                <div><span id="stat-roe" class="card-status"></span></div>
            </div>
        </div>
    </div>

    <!-- Data Komponen Pembentuk Rasio -->
    <div class="card shadow-sm">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold" style="color:#a78bfa;"><i class="fas fa-cubes me-2"></i>Komponen Perhitungan Rasio</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead style="background:rgba(255,255,255,0.05);">
                        <tr>
                            <th style="width:250px;">Komponen Laporan</th>
                            <th class="text-end" style="width:200px;">Nilai (Rp)</th>
                            <th>Keterangan / Sumber Rekening</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="cursor:pointer;" onclick="openBreakdownModal('11', 'neraca', 'Aset Lancar')">
                            <td class="fw-bold" style="color:#38bdf8;">Aset Lancar</td>
                            <td class="text-end font-monospace" id="comp-aset-lancar">0</td>
                            <td class="text-muted small">Rekening 11% (Kas, Bank, Piutang, Persediaan)</td>
                        </tr>
                        <tr style="cursor:pointer;" onclick="openBreakdownModal('21', 'neraca', 'Kewajiban Lancar')">
                            <td class="fw-bold" style="color:#f87171;">Kewajiban Lancar</td>
                            <td class="text-end font-monospace" id="comp-kewajiban-lancar">0</td>
                            <td class="text-muted small">Rekening 21% (Hutang jangka pendek)</td>
                        </tr>
                        <tr style="cursor:pointer;" onclick="openBreakdownModal('2', 'neraca', 'Total Kewajiban (Hutang)')">
                            <td class="fw-bold" style="color:#f87171;">Total Kewajiban (Hutang)</td>
                            <td class="text-end font-monospace" id="comp-total-kewajiban">0</td>
                            <td class="text-muted small">Rekening 2% (Seluruh hutang/liabilitas)</td>
                        </tr>
                        <tr style="cursor:pointer;" onclick="openBreakdownModal('3', 'neraca', 'Total Ekuitas')">
                            <td class="fw-bold" style="color:#fbbf24;">Total Ekuitas</td>
                            <td class="text-end font-monospace" id="comp-total-ekuitas">0</td>
                            <td class="text-muted small">Rekening 3% (Modal dasar, sumbangan)</td>
                        </tr>
                        <tr style="cursor:pointer;" onclick="openBreakdownModal('4', 'labarugi', 'Total Pendapatan (Revenue)')">
                            <td class="fw-bold" style="color:#4ade80;">Total Pendapatan (Revenue)</td>
                            <td class="text-end font-monospace" id="comp-revenue">0</td>
                            <td class="text-muted small">Rekening 4% (Periode tgl1 s/d tgl2)</td>
                        </tr>
                        <tr style="cursor:pointer;" onclick="openBreakdownModal('5', 'labarugi', 'Total Beban (Expense)')">
                            <td class="fw-bold" style="color:#f87171;">Total Beban (Expense)</td>
                            <td class="text-end font-monospace" id="comp-expense">0</td>
                            <td class="text-muted small">Rekening 5% (Periode tgl1 s/d tgl2)</td>
                        </tr>
                        <tr style="background:rgba(255,255,255,0.02);">
                            <td class="fw-bold text-white">Laba Bersih (Net Profit)</td>
                            <td class="text-end font-monospace fw-bold text-white" id="comp-net-profit">0</td>
                            <td class="text-muted small">Revenue dikurangi Expense</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- BREAKDOWN MODAL -->
<div class="modal fade" id="breakdown-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary"><i class="fas fa-list-ul me-2"></i>Rincian Komponen: <span id="bd-title"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="alert alert-info m-3 p-2 small">
                    <i class="fas fa-info-circle me-1"></i> Klik pada baris rekening di bawah ini untuk membuka histori lengkap (Buku Besar).
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
                        <tbody id="bd-tbody">
                            <tr><td colspan="3" class="text-center py-5">Memuat data rincian...</td></tr>
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
                <button class="btn btn-outline-info btn-sm" onclick="exportBubesCSV()"><i class="fas fa-file-excel me-1"></i>Export</button>
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
var _bdModal = null;
var _bbModal = null;
var _bubesData = null;
var _bubesTitle = "";

function fRp(angka) {
    if (angka === null || isNaN(angka)) return '0';
    var abs = Math.abs(Math.round(angka));
    var s = abs.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    return angka < 0 ? '(' + s + ')' : s;
}

function loadRasio() {
    var tgl1 = $('#inp-tgl1').val();
    var tgl2 = $('#inp-tgl2').val();
    if(!tgl1 || !tgl2) return;
    
    $('#dashboard-content').hide();
    $('#loader').show();
    $('#btn-load').prop('disabled', true);

    $.ajax({
        url: 'api/data_akuntansi_rasio.php',
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
            
            var r = res.data;
            
            // Populasikan Tabel Komponen
            $('#comp-aset-lancar').text(fRp(r.aset_lancar));
            $('#comp-kewajiban-lancar').text(fRp(r.kewajiban_lancar));
            $('#comp-total-kewajiban').text(fRp(r.total_kewajiban));
            $('#comp-total-ekuitas').text(fRp(r.total_ekuitas));
            $('#comp-revenue').text(fRp(r.revenue));
            $('#comp-expense').text(fRp(r.expense));
            $('#comp-net-profit').text(fRp(r.net_profit));

            // CR
            $('#val-cr').text(r.current_ratio.toFixed(2) + 'x');
            var statCR = r.current_ratio >= 1.5 ? ['Aman (> 1.5x)', 'status-good'] : (r.current_ratio >= 1.0 ? ['Hati-hati', 'status-warn'] : ['Bahaya (< 1.0x)', 'status-bad']);
            $('#stat-cr').text(statCR[0]).attr('class', 'card-status ' + statCR[1]);

            // DER
            $('#val-der').text(r.der.toFixed(2) + 'x');
            var statDER = r.der < 1.0 ? ['Hutang Terkendali', 'status-good'] : (r.der <= 2.0 ? ['Wajar', 'status-warn'] : ['Hutang Tinggi', 'status-bad']);
            $('#stat-der').text(statDER[0]).attr('class', 'card-status ' + statDER[1]);

            // ROE
            $('#val-roe').text(r.roe.toFixed(2) + '%');
            var statROE = r.roe >= 10 ? ['Sangat Menguntungkan', 'status-good'] : (r.roe > 0 ? ['Profit Tipis', 'status-warn'] : ['Rugi / Merugikan', 'status-bad']);
            $('#stat-roe').text(statROE[0]).attr('class', 'card-status ' + statROE[1]);

        },
        error: function(err) {
            $('#loader').hide();
            $('#btn-load').prop('disabled', false);
            $('#loader').html('<div class="alert alert-danger">Kesalahan jaringan.</div>').show();
        }
    });
}

function openBreakdownModal(kdPrefix, type, titleText) {
    if (!_bdModal) _bdModal = new bootstrap.Modal(document.getElementById('breakdown-modal'));
    
    $('#bd-title').text(titleText);
    $('#bd-tbody').html('<tr><td colspan="3" class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm"></div> Memuat rincian ' + titleText + '...</td></tr>');
    _bdModal.show();

    $.ajax({
        url: 'api/data_akuntansi_rasio_detail.php',
        type: 'GET',
        data: {
            tgl1: $('#inp-tgl1').val(),
            tgl2: $('#inp-tgl2').val(),
            kd_rek: kdPrefix,
            type: type
        },
        dataType: 'json',
        success: function(res) {
            if(!res.success) {
                $('#bd-tbody').html('<tr><td colspan="3" class="text-center text-danger">Gagal memuat rincian.</td></tr>');
                return;
            }
            var html = '';
            var tot = 0;
            if(res.data.length === 0) {
                html = '<tr><td colspan="3" class="text-center text-muted py-3">Tidak ada data untuk komponen ini.</td></tr>';
            } else {
                res.data.forEach(function(r) {
                    tot += r.saldo;
                    html += '<tr style="cursor:pointer;" onclick="openBubesModal(\''+r.kd_rek+'\', \''+r.nm_rek+'\')">';
                    html += '<td><code>'+r.kd_rek+'</code></td>';
                    html += '<td>'+r.nm_rek+'</td>';
                    html += '<td class="text-end font-monospace '+(r.saldo < 0 ? 'text-danger' : '')+'">'+fRp(r.saldo)+'</td>';
                    html += '</tr>';
                });
                html += '<tr style="background:rgba(255,255,255,0.05);"><td colspan="2" class="text-end fw-bold">TOTAL COMPONENT</td><td class="text-end font-monospace fw-bold text-white">'+fRp(tot)+'</td></tr>';
            }
            $('#bd-tbody').html(html);
        },
        error: function() {
            $('#bd-tbody').html('<tr><td colspan="3" class="text-center text-danger">Terjadi kesalahan jaringan.</td></tr>');
        }
    });
}

function openBubesModal(kd_rek, nm_rek) {
    var tgl1 = $('#inp-tgl1').val();
    var tgl2 = $('#inp-tgl2').val();
    
    // Tentukan apakah perlu mengabaikan saldo awal (untuk tipe Labarugi di Rasio)
    var isLabaRugi = nm_rek.includes("Pendapatan") || nm_rek.includes("Revenue") || 
                     nm_rek.includes("Beban") || nm_rek.includes("Expense") ||
                     nm_rek.includes("Laba");
    var ignoreSa = isLabaRugi ? 1 : 0;
    
    if (!_bbModal) _bbModal = new bootstrap.Modal(document.getElementById('bubes-modal'));
    if (_bdModal) _bdModal.hide(); // Sembunyikan breakdown agar z-index aman
    
    _bubesTitle = nm_rek + " (" + kd_rek + ")";
    $('#bubes-modal-title').text(_bubesTitle);
    $('#bubes-tbl-body').html('<tr><td colspan="8" class="text-center py-5"><div class="spinner-border spinner-border-sm text-warning"></div> Memuat histori '+nm_rek+'...</td></tr>');
    _bbModal.show();

    $.ajax({
        url: 'api/akuntansi_bubes.php',
        type: 'GET',
        data: { kd_rek: kd_rek, tgl1: tgl1, tgl2: tgl2, ignore_sa: ignoreSa },
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
                    html += '<tr class="'+(isAwal ? 'row-awal' : '')+'">';
                    html += '<td>'+r.tgl_jurnal+' '+r.jam_jurnal+'</td>';
                    html += '<td>'+r.no_jurnal+'</td>';
                    html += '<td>'+r.no_bukti+'</td>';
                    html += '<td>'+r.keterangan+'</td>';
                    
                    if (isAwal) {
                        html += '<td class="text-end col-s-awal">'+fRp(r.saldo_awal)+'</td>';
                        html += '<td class="text-end text-muted-zero">-</td>';
                        html += '<td class="text-end text-muted-zero">-</td>';
                        html += '<td class="text-end fw-bold '+ (r.saldo_awal < 0 ? 'saldo-neg' : 'saldo-pos') +'">'+fRp(r.saldo_awal)+'</td>';
                    } else {
                        var cD = parseFloat(r.debet) > 0 ? '' : ' text-muted-zero';
                        var cK = parseFloat(r.kredit) > 0 ? '' : ' text-muted-zero';
                        
                        html += '<td class="text-end col-s-awal">'+fRp(r.saldo_awal)+'</td>';
                        html += '<td class="text-end col-debet'+cD+'">'+(parseFloat(r.debet)>0 ? fRp(r.debet) : '-')+'</td>';
                        html += '<td class="text-end col-kredit'+cK+'">'+(parseFloat(r.kredit)>0 ? fRp(r.kredit) : '-')+'</td>';
                        html += '<td class="text-end col-s-akhir '+ (r.saldo_akhir < 0 ? 'saldo-neg' : 'saldo-pos') +'">'+fRp(r.saldo_akhir)+'</td>';
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

$(document).ready(function() {
    // loadRasio(); // Uncomment this if you want it to load automatically on ready
});
</script>
<?php $page_js = ob_get_clean(); ?>
<?php require_once('includes/footer.php'); ?>