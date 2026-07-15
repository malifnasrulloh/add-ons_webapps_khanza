<?php
/*
 * File: laporan_proyeksi_keuntungan.php
 * Tampilan: Dashboard Profit Obat (Pasien vs Jual Bebas)
 */

$page_title = "Proyeksi Keuntungan Obat";
require_once('includes/header.php');
require_once('includes/functions.php');

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01'); 
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// Ambil Data Penjamin untuk Filter
$penjabs = [];
$sql_pj = "SELECT kd_pj, png_jawab FROM penjab WHERE status='1' ORDER BY png_jawab";
$res_pj = $koneksi->query($sql_pj);
while($row = $res_pj->fetch_assoc()){ $penjabs[] = $row; }
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<div class="container-fluid">
    
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title text-primary mb-3">Filter Data Keuangan Obat</h5>
            <form id="filterForm" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Dari Tanggal</label>
                    <input type="date" class="form-control" id="tgl_awal" value="<?php echo $tgl_awal; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="tgl_akhir" value="<?php echo $tgl_akhir; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Jenis Kunjungan</label>
                    <select class="form-select" id="status_lanjut">
                        <option value="">-- Semua --</option>
                        <option value="Ralan">Rawat Jalan</option>
                        <option value="Ranap">Rawat Inap</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Status Bayar (Pasien)</label>
                    <select class="form-select" id="status_bayar">
                        <option value="">-- Semua Status --</option>
                        <option value="Lunas">Sudah Lunas (Tunai)</option>
                        <option value="Piutang">Masih Piutang</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Penjamin (Pasien)</label>
                    <select class="form-select select2-single" id="kd_pj">
                        <option value="">-- Semua Penjamin --</option>
                        <?php foreach($penjabs as $p): ?>
                            <option value="<?php echo $p['kd_pj']; ?>"><?php echo $p['png_jawab']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-success w-100" onclick="loadData()">
                        <i class="fas fa-search me-2"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Omzet (Netto)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="val-omzet">...</div>
                            <small class="text-muted">*Sudah dikurangi retur</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-cash-register fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Modal (HPP)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="val-modal">...</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-boxes fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Keuntungan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="val-profit">...</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-chart-line fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Profit Margin</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="val-margin">0%</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-percentage fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Grafik Tren Keuntungan Harian</h6>
        </div>
        <div class="card-body">
            <div class="chart-area" style="height: 350px;">
                <canvas id="chartProfit"></canvas>
            </div>
        </div>
    </div>

    <?php if (is_ai_active()): ?>
    <!-- AI PROFIT ADVISOR CONTAINER -->
    <div class="card bg-dark border-secondary mt-4 shadow-sm mb-4">
        <div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold"><i class="fas fa-brain me-2"></i>Analisis Profitabilitas Farmasi AI (Profit Advisor)</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProfitPrompt">
                    <i class="fas fa-sliders-h me-1"></i> Tune Prompt
                </button>
                <button id="btnAnalyzeProfit" class="btn btn-sm btn-success fw-bold">
                    <i class="fas fa-magic me-1"></i> Jalankan Analisis AI
                </button>
            </div>
        </div>
        <div class="card-body text-light">
            <!-- Collapsible Prompt Tuning Area -->
            <div class="collapse mb-3" id="collapseProfitPrompt">
                <div class="p-3 rounded border border-secondary bg-black bg-opacity-50">
                    <label class="form-label text-warning small fw-bold">System Prompt (Instruksi Analisis Profitabilitas):</label>
                    <textarea id="aiProfitPrompt" class="form-control form-control-sm bg-dark text-light border-secondary" rows="4">Anda adalah Konsultan Keuangan Apotek & Analis Bisnis Farmasi RS. Analisis data omzet, modal (HPP), keuntungan, profit margin, tren harian, serta rincian penjualan obat berikut (resep pasien vs apotek jual bebas) dan susun Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:
1. Evaluasi Kinerja Profitabilitas (apakah profit margin sudah optimal dan capaian omzet dibanding modal HPP).
2. Analisis Kontribusi Penjualan (bandingkan kontribusi profit antara resep pasien rujukan vs penjualan bebas apotek/walk-in).
3. Rekomendasi Strategi Pricing & Penjualan (saran penyesuaian margin obat, promosi obat bebas tertentu, efisiensi rantai pengadaan obat bermargin tipis, atau pengetatan piutang obat).</textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Setel prompt khusus ini untuk menyesuaikan gaya laporan keuangan farmasi yang dihasilkan AI.</small>
                        <button class="btn btn-xs btn-outline-warning text-warning" onclick="resetProfitPrompt()"><i class="fas fa-undo me-1"></i>Reset Prompt Default</button>
                    </div>
                </div>
            </div>

            <!-- Display Container Output -->
            <div id="aiProfitReportContainer" class="p-3 rounded border border-secondary bg-black bg-opacity-25 text-light" style="min-height: 120px; max-height: 500px; overflow-y: auto;">
                <div class="text-muted small text-center py-4">
                    <i class="fas fa-robot fa-2x mb-2 text-primary d-block"></i>
                    Klik tombol <strong>"Jalankan Analisis AI"</strong> di atas untuk memproses ringkasan profitabilitas farmasi secara otomatis.
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Keuntungan obat dianalisis berdasarkan status bayar dan penjamin terpilih.</small>
                <button class="btn btn-sm btn-outline-info" onclick="exportToWord('aiProfitReportContainer', 'Laporan_Analisis_Profit_Farmasi_AI.doc')">
                    <i class="fas fa-file-word me-1"></i> Ekspor Laporan ke Word (.doc)
                </button>
            </div>

            <!-- AI Interactive Chat Assistant -->
            <div class="mt-4 pt-3 border-top border-secondary">
                <h6 class="fw-bold text-info mb-2"><i class="fas fa-comments me-2"></i>Tanya Jawab & Diskusi Profit Keuangan dengan AI Assistant</h6>
                <div id="profitChatHistory" class="p-3 rounded border border-secondary bg-black bg-opacity-50 mb-2" style="max-height: 300px; overflow-y: auto; min-height: 100px;">
                    <div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>
                </div>
                <form id="profitChatForm">
                    <div class="input-group input-group-sm">
                        <input type="text" id="profitChatInput" class="form-control bg-dark text-light border-secondary" placeholder="Tanyakan detail margin (misal: Mengapa margin obat bebas lebih besar dari obat resep?)..." required>
                        <button class="btn btn-primary" type="submit" id="btnSendProfitChat">
                            <i class="fas fa-paper-plane me-1"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-pills me-2"></i>Rincian Penjualan Obat</h6>
        </div>
        <div class="card-body">
            <!-- Nav Tabs -->
            <ul class="nav nav-tabs mb-3" id="profitTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="resep-tab" data-bs-toggle="tab" data-bs-target="#resep-pane" type="button" role="tab" aria-controls="resep-pane" aria-selected="true">
                        <i class="fas fa-user-injured me-1"></i> Penjualan Obat Pasien (Resep)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="bebas-tab" data-bs-toggle="tab" data-bs-target="#bebas-pane" type="button" role="tab" aria-controls="bebas-pane" aria-selected="false">
                        <i class="fas fa-shopping-cart me-1"></i> Penjualan Obat Bebas (Apotek)
                    </button>
                </li>
            </ul>
            <!-- Tab Contents -->
            <div class="tab-content" id="profitTabsContent">
                <div class="tab-pane fade show active" id="resep-pane" role="tabpanel" aria-labelledby="resep-tab" tabindex="0">
                    <div class="table-responsive">
                        <table id="tablePasien" class="table table-striped table-hover table-sm text-sm" width="100%">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>No. Rawat</th>
                                    <th>Nama Obat</th>
                                    <th>Asal Resep</th>
                                    <th>Dokter Peresep</th>
                                    <th>Jml</th>
                                    <th class="text-end">Tagihan</th>
                                    <th class="text-end">Laba</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="bebas-pane" role="tabpanel" aria-labelledby="bebas-tab" tabindex="0">
                    <div class="table-responsive">
                        <table id="tableBebas" class="table table-striped table-hover table-sm text-sm" width="100%">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>No. Nota</th>
                                    <th>Nama Obat</th>
                                    <th>Jml</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Laba</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php ob_start(); ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    var tablePasien, tableBebas, chartProfit;
    var _profitResponseData = null;

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    }

    $(document).ready(function() {
        // Init Select2
        $('.select2-single').select2({
            theme: "bootstrap-5",
            placeholder: "Pilih Penjamin",
            allowClear: true
        });

        // Init DataTables
        tablePasien = $('#tablePasien').DataTable({
            "responsive": true, "pageLength": 10, "dom": 'Bfrtip',
            buttons: [ {
                extend: 'excelHtml5', 
                title: 'Laporan Pemberian Obat Ke Pasien',
                className: 'btn-sm btn-success',
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            // 1. FORMAT RUPIAH (Kolom 6 & 7)
                            if (column === 6 || column === 7) {
                                return typeof data === 'string' ? data.replace(/[^\d,-]/g, '').replace(',', '.') : data;
                            }

                            // 2. BERSIHKAN HTML
                            if (typeof data === 'string') {
                                let text = data.replace(/<br\s*\/?>/gi, " - ");
                                return text.replace(/<[^>]+>/g, "").trim();
                            }

                            return data;
                        }
                    }
                }
            } ],
			"order": [[ 0, "desc" ]],
            "columns": [
                { "data": "tanggal", render: function(d){ return d.split(' ')[0]; } },
                { "data": "no_rawat", render: function(d,t,r){ return '<small>'+d+'<br>'+r.nm_pasien+'</small>'; } },
                { "data": "nama_brng" },
                { "data": "asal_resep" },
                { "data": "dokter_peresep" },
                { "data": "jml", className: "text-center" },
                { "data": "subtotal_jual", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0, '') },
                { "data": "profit", className: "text-end fw-bold text-success", render: $.fn.dataTable.render.number('.', ',', 0, '') }
            ]
        });

        tableBebas = $('#tableBebas').DataTable({
            "responsive": true, "pageLength": 10, "dom": 'Bfrtip',
            //"buttons": [ {extend: 'excel', title: 'Laporan Obat Bebas', className: 'btn-sm btn-info'} ],
            buttons: [ {
                extend: 'excelHtml5', 
                title: 'Laporan Obat Bebas', // Sesuaikan judul per tabel (Obat Pasien / Obat Bebas)
                className: 'btn-sm btn-success',
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            // 1. FORMAT RUPIAH (Kolom 4 & 5)
                            if (column === 4 || column === 5) {
                                return typeof data === 'string' ? data.replace(/[^\d,-]/g, '').replace(',', '.') : data;
                            }

                            // 2. BERSIHKAN HTML (Untuk kolom No. Rawat/Nota yang ada <br> dan <small>)
                            if (typeof data === 'string') {
                                // Ganti tag <br> dengan tanda strip " - " agar teks tidak menempel
                                let text = data.replace(/<br\s*\/?>/gi, " - ");
                                // Hapus semua tag HTML lain (<small>, <span>, dll)
                                return text.replace(/<[^>]+>/g, "").trim();
                            }

                            return data;
                        }
                    }
                }
            } ],
			"order": [[ 0, "desc" ]],
            "columns": [
                { "data": "tanggal" },
                { "data": "nota_jual", render: function(d,t,r){ return '<small>'+d+'<br>'+(r.pembeli || '-')+'</small>'; } },
                { "data": "nama_brng" },
                { "data": "jumlah", className: "text-center" },
                { "data": "subtotal_jual", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0, '') },
                { "data": "profit", className: "text-end fw-bold text-success", render: $.fn.dataTable.render.number('.', ',', 0, '') }
            ]
        });

                // Adjust column headers on tab switch
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
        });

        // Data akan dimuat saat user klik tombol Tampilkan
    });

    function loadData() {
        var params = {
            tgl_awal: $('#tgl_awal').val(),
            tgl_akhir: $('#tgl_akhir').val(),
            status_bayar: $('#status_bayar').val(),
            status_lanjut: $('#status_lanjut').val(),
            kd_pj: $('#kd_pj').val()
        };

        $.ajax({
            url: 'api/data_proyeksi_keuntungan.php',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(response) {
                _profitResponseData = response;
                // 1. Update Summary Cards
                $('#val-omzet').text(formatRupiah(response.summary.omzet));
                $('#val-modal').text(formatRupiah(response.summary.modal));
                $('#val-profit').text(formatRupiah(response.summary.profit));
                
                let margin = 0;
                if(response.summary.omzet > 0) {
                    margin = (response.summary.profit / response.summary.omzet) * 100;
                }
                $('#val-margin').text(margin.toFixed(2) + '%');

                // 2. Update Tables
                tablePasien.clear().rows.add(response.pasien).draw();
                tableBebas.clear().rows.add(response.bebas).draw();

                // 3. Update Chart
                renderChart(response.chart);
            },
            error: function() { alert("Gagal memuat data."); }
        });
    }

    function renderChart(data) {
        var ctx = document.getElementById("chartProfit").getContext('2d');
        if(chartProfit) chartProfit.destroy();

        chartProfit = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: "Omzet",
                        data: data.omzet,
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.05)',
                        tension: 0.3, fill: false
                    },
                    {
                        label: "Keuntungan",
                        data: data.profit,
                        borderColor: '#1cc88a',
                        backgroundColor: 'rgba(28, 200, 138, 0.1)',
                        tension: 0.3, fill: true
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    tooltip: { callbacks: { label: function(c) { return c.dataset.label + ': ' + formatRupiah(c.raw); } } }
                },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // --- AI PROFIT ADVISOR JS PIPELINE ---
    var currentProfitReportContext = "";
    var profitChatHistoryData = [];
    const defaultProfitPromptText = "Anda adalah Konsultan Keuangan Apotek & Analis Bisnis Farmasi RS. Analisis data omzet, modal (HPP), keuntungan, profit margin, tren harian, serta rincian penjualan obat berikut (resep pasien vs apotek jual bebas) dan susun Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:\n1. Evaluasi Kinerja Profitabilitas (apakah profit margin sudah optimal dan capaian omzet dibanding modal HPP).\n2. Analisis Kontribusi Penjualan (bandingkan kontribusi profit antara resep pasien rujukan vs penjualan bebas apotek/walk-in).\n3. Rekomendasi Strategi Pricing & Penjualan (saran penyesuaian margin obat, promosi obat bebas tertentu, efisiensi rantai pengadaan obat bermargin tipis, atau pengetatan piutang obat).";

    function resetProfitPrompt() {
        $('#aiProfitPrompt').val(defaultProfitPromptText);
    }

    function parseMarkdownToHtml(md) {
        if (!md) return '';
        return md
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/^### (.*?)$/gm, '<h5 class="fw-bold text-info mt-3">$1</h5>')
            .replace(/^## (.*?)$/gm, '<h4 class="fw-bold text-primary mt-4 border-bottom border-secondary pb-1">$1</h4>')
            .replace(/^# (.*?)$/gm, '<h3 class="fw-bold text-primary mt-4">$1</h3>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/^\s*[-*+]\s+(.*?)$/gm, '<li>$1</li>')
            .replace(/(<li>.*?<\/li>)/gs, '<ul class="mb-2">$1</ul>')
            .replace(/<\/ul>\s*<ul class="mb-2">/g, '')
            .replace(/^\s*([^#<>\s\-*+].*?)$/gm, '<p class="mb-2">$1</p>')
            .replace(/\n\n/g, '<br>');
    }

    function exportToWord(elementId, fileName) {
        var content = document.getElementById(elementId).innerHTML;
        var header = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>" +
                     "<head><meta charset='utf-8'><title>Laporan Ekspor</title>" +
                     "<style>body { font-family: Arial, sans-serif; line-height: 1.6; } h1, h2, h3 { color: #0284c7; }</style></head><body>";
        var footer = "</body></html>";
        
        var blob = new Blob(['\ufeff', header + content + footer], { type: 'application/msword' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = fileName || 'Laporan.doc';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    $(document).on('click', '#btnAnalyzeProfit', function() {
        if (!_profitResponseData) {
            alert('Silakan tampilkan data proyeksi keuntungan terlebih dahulu.');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menganalisis...');
        $('#aiProfitReportContainer').html('<div class="text-center py-4"><div class="spinner-border text-info mb-2"></div><div class="small text-muted">AI sedang menganalisis profitabilitas obat...</div></div>');

        // Kirim sample data 30 obat resep & 30 obat bebas bernilai tinggi
        var samplePasien = (_profitResponseData.pasien || []).map(function(p) {
            return { 
                tgl: p.tanggal, 
                no_rawat: p.no_rawat, 
                pasien: p.nm_pasien, 
                obat: p.nama_brng, 
                asal_resep: p.asal_resep,
                dokter_peresep: p.dokter_peresep,
                jml: p.jml, 
                tagihan: p.subtotal_jual, 
                laba: p.profit 
            };
        });

        var sampleBebas = (_profitResponseData.bebas || []).map(function(b) {
            return { tgl: b.tanggal, nota: b.nota_jual, obat: b.nama_brng, jml: b.jumlah, tagihan: b.subtotal_jual, laba: b.profit };
        });

        var profitRawData = {
            periode: $('#tgl_awal').val() + ' s.d ' + $('#tgl_akhir').val(),
            penjamin: $('#kd_pj option:selected').text(),
            status_bayar: $('#status_bayar option:selected').text(),
            summary: _profitResponseData.summary || {},
            sample_obat_resep: samplePasien,
            sample_obat_bebas: sampleBebas
        };

        var formData = new URLSearchParams();
        formData.append('action', 'batch_summary');
        formData.append('raw_data', JSON.stringify([profitRawData]));
        formData.append('custom_prompt', $('#aiProfitPrompt').val().trim());
        formData.append('stream', '1');

        fetch('api/ai_analyzer.php', {
            method: 'POST',
            body: formData,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).then(async response => {
            const reader = response.body.getReader();
            const decoder = new TextDecoder("utf-8");
            let fullText = "";
            let isError = false;
            let isThinking = false;
            const aiThinkingContainer = document.getElementById('aiProfitReportContainer');
            let buffer = "";

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, {stream: true});
                const lines = buffer.split('\n');
                buffer = lines.pop();

                for (let line of lines) {
                    if (line === 'event: thinking') {
                        isThinking = true;
                        continue;
                    }
                    if (isThinking && line.startsWith('data: ')) {
                        isThinking = false;
                        try {
                            const td = JSON.parse(line.substring(6));
                            if (typeof aiThinkingContainer !== 'undefined' && aiThinkingContainer) {
                                aiThinkingContainer.innerHTML = buildThinkingHTML(td.row_count || 0, td.message || '');
                            }
                        } catch(e) {}
                        continue;
                    }

                    line = line.trim();
                    if (line.startsWith('data: ')) {
                        const dataStr = line.substring(6);
                        if (dataStr === '[DONE]') continue;
                        try {
                            const data = JSON.parse(dataStr);
                            if (data.message) {
                                isError = true;
                                $('#aiProfitReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + data.message + '</div>');
                            }
                            if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                fullText += data.choices[0].delta.content;
                                $('#aiProfitReportContainer').html(parseMarkdownToHtml(fullText));
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');

            if (!isError && fullText) {
                currentProfitReportContext = fullText;
                profitChatHistoryData = [];
                $('#profitChatHistory').html('<div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>');
            }
        }).catch(err => {
            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');
            $('#aiProfitReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: Gagal menghubungi server (' + err.message + ')</div>');
        });
    });

    $(document).on('submit', '#profitChatForm', function(e) {
        e.preventDefault();
        const input = $('#profitChatInput');
        const messageText = input.val().trim();
        if (!messageText || !currentProfitReportContext) return;

        if (profitChatHistoryData.length === 0) {
            $('#profitChatHistory').empty();
        }

        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        $('#profitChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-primary border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-primary"><i class="fas fa-user me-1"></i>Anda</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light">' + parseMarkdownToHtml(messageText) + '</div>' +
            '</div>'
        );
        $('#profitChatHistory').scrollTop($('#profitChatHistory')[0].scrollHeight);

        input.val('');
        $('#profitChatInput, #btnSendProfitChat').prop('disabled', true);

        var replyId = 'profit_reply_' + Date.now();
        $('#profitChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-info border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-info"><i class="fas fa-robot me-1"></i>AI Profit Assistant</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light" id="' + replyId + '"><i class="fas fa-spinner fa-spin text-info me-1"></i> Mengetik...</div>' +
            '</div>'
        );
        $('#profitChatHistory').scrollTop($('#profitChatHistory')[0].scrollHeight);

        var samplePasien = (_profitResponseData.pasien || []).map(function(p) {
            return { 
                tgl: p.tanggal, 
                no_rawat: p.no_rawat, 
                pasien: p.nm_pasien, 
                obat: p.nama_brng, 
                asal_resep: p.asal_resep,
                dokter_peresep: p.dokter_peresep,
                jml: p.jml, 
                tagihan: p.subtotal_jual, 
                laba: p.profit 
            };
        });

        var sampleBebas = (_profitResponseData.bebas || []).map(function(b) {
            return { tgl: b.tanggal, nota: b.nota_jual, obat: b.nama_brng, jml: b.jumlah, tagihan: b.subtotal_jual, laba: b.profit };
        });

        var profitRawData = {
            periode: $('#tgl_awal').val() + ' s.d ' + $('#tgl_akhir').val(),
            penjamin: $('#kd_pj option:selected').text(),
            status_bayar: $('#status_bayar option:selected').text(),
            summary: _profitResponseData ? _profitResponseData.summary : {},
            sample_obat_resep: samplePasien,
            sample_obat_bebas: sampleBebas
        };

        var chatData = new URLSearchParams();
        chatData.append('action', 'chat_discuss');
        chatData.append('message', messageText);
        chatData.append('report_context', currentProfitReportContext);
        chatData.append('raw_data', JSON.stringify([profitRawData]));
        chatData.append('custom_prompt', $('#aiProfitPrompt').val().trim());
        chatData.append('history', JSON.stringify(profitChatHistoryData));
        chatData.append('stream', '1');

        fetch('api/ai_analyzer.php', {
            method: 'POST',
            body: chatData,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).then(async response => {
            const reader = response.body.getReader();
            const decoder = new TextDecoder("utf-8");
            let fullReply = "";
            let isError = false;
            let isThinking = false;
            const aiThinkingContainer = document.getElementById('aiProfitReportContainer');
            let buffer = "";

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, {stream: true});
                const lines = buffer.split('\n');
                buffer = lines.pop();

                for (let line of lines) {
                    if (line === 'event: thinking') {
                        isThinking = true;
                        continue;
                    }
                    if (isThinking && line.startsWith('data: ')) {
                        isThinking = false;
                        try {
                            const td = JSON.parse(line.substring(6));
                            if (typeof aiThinkingContainer !== 'undefined' && aiThinkingContainer) {
                                aiThinkingContainer.innerHTML = buildThinkingHTML(td.row_count || 0, td.message || '');
                            }
                        } catch(e) {}
                        continue;
                    }

                    line = line.trim();
                    if (line.startsWith('data: ')) {
                        const dataStr = line.substring(6);
                        if (dataStr === '[DONE]') continue;
                        try {
                            const data = JSON.parse(dataStr);
                            if (data.message) {
                                isError = true;
                                $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> ' + data.message + '</span>');
                            }
                            if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                fullReply += data.choices[0].delta.content;
                                $('#' + replyId).html(parseMarkdownToHtml(fullReply));
                                $('#profitChatHistory').scrollTop($('#profitChatHistory')[0].scrollHeight);
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            $('#profitChatInput, #btnSendProfitChat').prop('disabled', false);

            if (!isError && fullReply) {
                profitChatHistoryData.push({ role: 'user', content: messageText });
                profitChatHistoryData.push({ role: 'assistant', content: fullReply });
            }
        }).catch(err => {
            $('#profitChatInput, #btnSendProfitChat').prop('disabled', false);
            $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Error koneksi</span>');
        });
    });

</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>