<?php
$page_title = "Analisa Dead Stock (Farmasi)";
require_once('includes/header.php');
require_once('includes/functions.php');

// Ambil daftar bangsal untuk filter
$bangsals = [];
$sql_bangsal = "SELECT kd_bangsal, nm_bangsal FROM bangsal WHERE status='1' ORDER BY nm_bangsal";
$res_bangsal = $koneksi->query($sql_bangsal);
if($res_bangsal) {
    while($row = $res_bangsal->fetch_assoc()){
        $bangsals[] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-skull-crossbones text-danger"></i> Analisa Dead Stock & Slow Moving</h1>
    </div>
    
    <div class="alert alert-warning py-2 shadow-sm mb-4" role="alert">
        <i class="fas fa-info-circle me-1"></i> <strong>Peringatan!</strong> Laporan ini menunjukkan obat/alkes yang masih memiliki stok fisik, namun <strong>tidak memiliki satupun riwayat pengeluaran (transaksi keluar)</strong> dalam rentang waktu yang diatur.
    </div>

    <!-- Filter Card -->
    <div class="card shadow mb-4 border-left-danger">
        <div class="card-body">
            <form id="filterForm" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label font-weight-bold">Rentang Waktu Mati (Tidak Ada Transaksi)</label>
                    <select class="form-select" id="rentang_waktu" onchange="toggleCustomDate()">
                        <option value="1bulan">1 Bulan Terakhir</option>
                        <option value="3bulan" selected>3 Bulan Terakhir</option>
                        <option value="6bulan">6 Bulan Terakhir</option>
                        <option value="1tahun">> 1 Tahun Terakhir</option>
                        <option value="custom">Custom (Pilih Tanggal)</option>
                    </select>
                </div>
                
                <!-- Custom Date (Hidden by default) -->
                <div class="col-md-2 custom-date-container" style="display:none;">
                    <label class="form-label font-weight-bold">Dari Tanggal</label>
                    <input type="date" class="form-control" id="tgl_awal" value="<?php echo date('Y-m-d', strtotime('-3 months')); ?>">
                </div>
                <div class="col-md-2 custom-date-container" style="display:none;">
                    <label class="form-label font-weight-bold">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="tgl_akhir" value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label font-weight-bold">Lokasi / Depo Gudang</label>
                    <select class="form-select" id="kd_bangsal">
                        <option value="">-- Semua Lokasi / Depo --</option>
                        <?php foreach($bangsals as $b): ?>
                            <option value="<?= $b['kd_bangsal']; ?>"><?= $b['nm_bangsal']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label font-weight-bold">Pencarian Obat</label>
                    <input type="text" class="form-control" id="keyword" placeholder="Nama/Kode...">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger w-100" onclick="loadData()"><i class="fas fa-search me-1"></i> Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Widget KPI -->
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Item Dead Stock</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><span id="kpi-item">0</span> <small class="text-xs">Macam</small></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box-open fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Volume Stok Fisik Menganggur</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><span id="kpi-vol">0</span> <small class="text-xs">Satuan</small></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cubes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 bg-gradient-light">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Estimasi Nilai Aset Mengendap (HPP)</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800" id="kpi-aset">Rp 0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-primary" style="opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Top 10 Aset Terbesar -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-danger">Top 10 Aset Terbesar yang Mengendap</h6>
        </div>
        <div class="card-body">
            <canvas id="barChartDead" style="min-height: 350px; max-height: 350px;"></canvas>
        </div>
    </div>

    <?php if (is_ai_active()): ?>
    <!-- AI DEAD STOCK ANALYZER CONTAINER -->
    <div class="card bg-dark border-secondary mt-4 shadow-sm mb-4">
        <div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold"><i class="fas fa-brain me-2"></i>Analisis Aset Mengendap AI (Dead Stock Advisor)</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDeadPrompt">
                    <i class="fas fa-sliders-h me-1"></i> Tune Prompt
                </button>
                <button id="btnAnalyzeDead" class="btn btn-sm btn-success fw-bold">
                    <i class="fas fa-magic me-1"></i> Jalankan Analisis AI
                </button>
            </div>
        </div>
        <div class="card-body text-light">
            <!-- Collapsible Prompt Tuning Area -->
            <div class="collapse mb-3" id="collapseDeadPrompt">
                <div class="p-3 rounded border border-secondary bg-black bg-opacity-50">
                    <label class="form-label text-warning small fw-bold">System Prompt (Instruksi Analisis Dead Stock):</label>
                    <textarea id="aiDeadPrompt" class="form-control form-control-sm bg-dark text-light border-secondary" rows="4">Anda adalah Konsultan Pengadaan Farmasi & Manajemen Aset Rumah Sakit yang ahli. Analisis data obat mati / mengendap (dead stock & slow moving) berikut (mencakup total item, volume stok fisik, estimasi nilai aset mengendap, dan 10 besar obat dengan aset terbesar) dan susun Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:
1. Analisis Risiko Keuangan (seberapa besar dana yang tertahan di depo/gudang dan potensi kerugian kadaluarsa).
2. Identifikasi Obat Kritis (sorotan khusus terhadap top 10 obat dengan aset mengendap terbesar).
3. Rekomendasi Aksi Penyelamatan Modal (saran retur supplier, distribusi antar depo, pembuatan bundle resep, atau penghematan anggaran pengadaan obat sejenis berikutnya).</textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Setel prompt khusus ini untuk menyesuaikan gaya laporan inventaris farmasi yang dihasilkan AI.</small>
                        <button class="btn btn-xs btn-outline-warning text-warning" onclick="resetDeadPrompt()"><i class="fas fa-undo me-1"></i>Reset Prompt Default</button>
                    </div>
                </div>
            </div>

            <!-- Display Container Output -->
            <div id="aiDeadReportContainer" class="p-3 rounded border border-secondary bg-black bg-opacity-25 text-light" style="min-height: 120px; max-height: 500px; overflow-y: auto;">
                <div class="text-muted small text-center py-4">
                    <i class="fas fa-robot fa-2x mb-2 text-primary d-block"></i>
                    Klik tombol <strong>"Jalankan Analisis AI"</strong> di atas untuk memproses ringkasan analisis aset inventaris mati secara otomatis.
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Inventaris mati dianalisis berdasarkan lokasi depo dan rentang waktu cutoff terpilih.</small>
                <button class="btn btn-sm btn-outline-info" onclick="exportToWord('aiDeadReportContainer', 'Laporan_Analisis_Dead_Stock_AI.doc')">
                    <i class="fas fa-file-word me-1"></i> Ekspor Laporan ke Word (.doc)
                </button>
            </div>

            <!-- AI Interactive Chat Assistant -->
            <div class="mt-4 pt-3 border-top border-secondary">
                <h6 class="fw-bold text-info mb-2"><i class="fas fa-comments me-2"></i>Tanya Jawab & Diskusi Pengadaan Farmasi dengan AI Assistant</h6>
                <div id="deadChatHistory" class="p-3 rounded border border-secondary bg-black bg-opacity-50 mb-2" style="max-height: 300px; overflow-y: auto; min-height: 100px;">
                    <div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>
                </div>
                <form id="deadChatForm">
                    <div class="input-group input-group-sm">
                        <input type="text" id="deadChatInput" class="form-control bg-dark text-light border-secondary" placeholder="Tanyakan detail obat (misal: Obat apa saja yang akan kadaluarsa 6 bulan lagi?)..." required>
                        <button class="btn btn-primary" type="submit" id="btnSendDeadChat">
                            <i class="fas fa-paper-plane me-1"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- DataTables -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-danger">Rincian Obat Dead / Slow Moving</h6>
            <span class="text-muted small">Tanpa Transaksi Keluar sejak: <b id="lbl-cutoff">...</b></span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTableDead" width="100%" cellspacing="0">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th>Kode Obat</th>
                            <th>Nama Obat / Barang</th>
                            <th>Lokasi (Depo)</th>
                            <th class="text-center">Sisa Stok</th>
                            <th class="text-end">HPP Dasar</th>
                            <th class="text-end">Total Nilai Aset (Rp)</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Riwayat Obat -->
<div class="modal fade" id="modalRiwayat" tabindex="-1" aria-labelledby="modalRiwayatLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalRiwayatLabel"><i class="fas fa-history me-1"></i> Riwayat Transaksi Obat</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="card mb-3 shadow-sm border-left-info">
                    <div class="card-body py-2">
                        <div class="row align-items-center">
                            <div class="col-md-5">
                                <small class="text-muted d-block mb-1">Obat / Barang:</small>
                                <strong id="det_nama_brng" class="text-primary fs-5">-</strong>
                                <span class="badge bg-secondary ms-2" id="det_kode_brng">-</span>
                            </div>
                            <div class="col-md-3 border-start">
                                <small class="text-muted d-block mb-1">Lokasi Depo:</small>
                                <strong id="det_nm_bangsal">-</strong>
                            </div>
                            <div class="col-md-4 border-start">
                                <small class="text-muted d-block mb-1">Rentang Riwayat:</small>
                                <strong id="det_rentang_tgl">-</strong>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive bg-white p-3 rounded shadow-sm">
                    <table class="table table-bordered table-sm table-striped" id="tableDetailRiwayat" width="100%" cellspacing="0">
                        <thead class="table-dark">
                            <tr>
                                <th>Tgl / Jam</th>
                                <th>Lokasi</th>
                                <th>Awal</th>
                                <th class="text-success"><i class="fas fa-arrow-down"></i> Masuk</th>
                                <th class="text-danger"><i class="fas fa-arrow-up"></i> Keluar</th>
                                <th>Akhir</th>
                                <th>Keterangan (No. Faktur)</th>
                                <th>Petugas</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>

<script>
    var tableDead;
    var tableRiwayat;
    var barChartInstance;
    var _deadStockResponseData = null;

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    }

    function toggleCustomDate() {
        var val = $('#rentang_waktu').val();
        if(val === 'custom') {
            $('.custom-date-container').show();
        } else {
            $('.custom-date-container').hide();
        }
    }

    $(document).ready(function() {
        tableRiwayat = $('#tableDetailRiwayat').DataTable({
            "responsive": true,
            "pageLength": 10,
            "ordering": false,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
            "columns": [
                { "data": "tgl_jurnal" },
                { "data": "nm_bangsal" },
                { "data": "stok_awal", className: "text-center" },
                { "data": "stok_masuk", className: "text-center text-success fw-bold" },
                { "data": "stok_keluar", className: "text-center text-danger fw-bold" },
                { "data": "stok_akhir", className: "text-center fw-bold" },
                { "data": "keterangan", className: "small" },
                { "data": "nm_petugas", className: "small text-muted" }
            ]
        });

        tableDead = $('#dataTableDead').DataTable({
            "responsive": true,
            "pageLength": 10,
            "order": [[5, "desc"]], // Urutkan nilai aset terbesar
            "dom": 'Bfrtip',
            "buttons": [
                {
                    extend: 'excelHtml5',
                    title: 'Laporan Dead Stock Farmasi',
                    className: 'btn btn-success btn-sm',
                    exportOptions: { 
                        columns: ':visible',
                        format: {
                            // Agar excel mendeteksi angka
                            body: function(data, row, column, node) {
                                // Kolom Stok (3) dan Nilai uang (4, 5)
                                if (column === 3) {
                                    return (data === null || data === undefined) ? '' : String(data).replace(/[^\d.-]/g, '');
                                }
                                if (column === 4 || column === 5) {
                                    return (data === null || data === undefined) ? '' : String(data).replace(/[^\d,-]/g, '').replace(',', '.');
                                }
                                // Bersihkan HTML jika ada
                                var strData = String(data);
                                if (strData.indexOf('<') > -1) {
                                    return strData.replace(/<[^>]+>/g, "").trim();
                                }
                                return data;
                            }
                        }
                    }
                },
                {
                    extend: 'print',
                    title: 'Laporan Dead Stock Farmasi',
                    className: 'btn btn-secondary btn-sm',
                    exportOptions: { columns: ':visible' }
                }
            ],
            "columns": [
                { "data": "kode_brng" },
                { "data": "nama_brng", "className": "fw-bold" },
                { "data": "nm_bangsal", "className": "small text-muted" },
                { 
                    "data": "stok_val", 
                    "className": "text-center fw-bold text-danger",
                    // Simpan nilai asli di attribute untuk sorting/export yg aman jika perlu, 
                    // tapi kita pakai render data numeric
                    render: function(data, type, row) {
                        if (type === 'display') return data;
                        return data;
                    }
                },
                { 
                    "data": "hpp_val", 
                    "className": "text-end",
                    render: function(data, type, row) {
                        return type === 'display' ? formatRupiah(data) : data;
                    }
                },
                { 
                    "data": "aset_val", 
                    "className": "text-end fw-bold text-primary bg-light",
                    render: function(data, type, row) {
                        return type === 'display' ? formatRupiah(data) : data;
                    }
                },
                {
                    "data": null,
                    "className": "text-center",
                    "orderable": false,
                    "render": function(data, type, row) {
                        return '<button class="btn btn-outline-info btn-xs" onclick="lihatRiwayat(\''+row.kode_brng+'\',\''+row.nama_brng.replace(/'/g, "\\'")+'\',\''+row.kd_bangsal+'\',\''+row.nm_bangsal+'\')"><i class="fas fa-history me-1"></i>Riwayat</button>';
                    }
                }
            ]
        });

        // Data akan dimuat saat user klik tombol Tampilkan
    });

    function loadData() {
        var rentang = $('#rentang_waktu').val();
        var tgl1 = $('#tgl_awal').val();
        var tgl2 = $('#tgl_akhir').val();
        var bangsal = $('#kd_bangsal').val();
        var search = $('#keyword').val();

        $('#kpi-item, #kpi-vol, #kpi-aset').text('...');
        $('#lbl-cutoff').text('Loading...');

        $.ajax({
            url: 'api/data_dead_stock.php',
            type: 'GET',
            data: { 
                rentang: rentang, 
                tgl_awal: tgl1, 
                tgl_akhir: tgl2,
                kd_bangsal: bangsal,
                keyword: search 
            },
            dataType: 'json',
            success: function(res) {
                _deadStockResponseData = res;
                // Update KPI Cards
                $('#kpi-item').text(res.summary.total_item);
                // Formatting custom decimal / ribuan for volume
                $('#kpi-vol').text(new Intl.NumberFormat('id-ID').format(res.summary.total_stok));
                $('#kpi-aset').text(formatRupiah(res.summary.total_aset));
                
                $('#lbl-cutoff').text(res.summary.cutoff_start + ' s.d. ' + res.summary.cutoff_end);

                // Update Table
                tableDead.clear();
                tableDead.rows.add(res.data);
                tableDead.draw();

                renderChart(res.chart);
            },
            error: function(err) {
                alert("Gagal memuat data Dead Stock.");
                console.error(err);
            }
        });
    }

    // Fungsi Render Chart Top 10
    function renderChart(chartData) {
        if (barChartInstance) { barChartInstance.destroy(); }
        
        var ctxBar = document.getElementById('barChartDead').getContext('2d');
        barChartInstance = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Nilai Aset Mengendap (Rp)',
                        data: chartData.data,
                        backgroundColor: 'rgba(231, 74, 59, 0.8)', // Danger color
                        borderColor: '#e74a3b',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + formatRupiah(context.raw);
                            }
                        }
                    },
                    legend: {
                        display: false // Sembunyikan legenda karena cuma 1 dataset
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatRupiah(value);
                            }
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
    }

    // Fungsi Melihat Riwayat Detail Obat (terhubung ke api_riwayat_obat.php)
    var isCheckingRiwayat = false;
    function lihatRiwayat(kode_brng, nama_brng, kd_bangsal, nm_bangsal) {
        if(isCheckingRiwayat) return;
        
        // Ambil range pencarian dari input dead stock untuk sinkronisasi
        var rentang = $('#rentang_waktu').val();
        var tgl1 = $('#tgl_awal').val();
        var tgl2 = $('#tgl_akhir').val();
        
        // Format label modal
        $('#det_kode_brng').text(kode_brng);
        $('#det_nama_brng').text(nama_brng);
        $('#det_nm_bangsal').text(nm_bangsal || 'Gudang Pusat');
        $('#det_rentang_tgl').html('<i class="fas fa-spinner fa-spin"></i>');
        
        tableRiwayat.clear().draw();
        var modal = new bootstrap.Modal(document.getElementById('modalRiwayat'));
        modal.show();

        isCheckingRiwayat = true;
        
        // Panggil endpoint kita untuk cari tgl cutoff yang sebenarnya
        $.ajax({
            url: 'api/data_dead_stock.php',
            type: 'GET',
            data: { rentang: rentang, tgl_awal: tgl1, tgl_akhir: tgl2, limit: 1 },
            dataType: 'json',
            success: function(res_sync) {
                var cutoff_start = res_sync.summary.cutoff_start;
                var cutoff_end = res_sync.summary.cutoff_end;
                
                $('#det_rentang_tgl').text(cutoff_start + ' s.d ' + cutoff_end);
                
                // Ambil riwayatnya
                $.ajax({
                    url: 'api/data_riwayat_obat.php',
                    type: 'GET',
                    data: { 
                        kode_brng: kode_brng, 
                        kd_bangsal: kd_bangsal,
                        tgl_awal: cutoff_start, 
                        tgl_akhir: cutoff_end 
                    },
                    dataType: 'json',
                    success: function(res_riwayat) {
                        isCheckingRiwayat = false;
                        if(res_riwayat.data) {
                            tableRiwayat.rows.add(res_riwayat.data).draw();
                        } else {
                            if(res_riwayat.error && res_riwayat.error.includes("Akses ditolak")) {
                                alert("Sesi login Anda telah habis, silakan login kembali.");
                            } else {
                                alert("Riwayat tidak ditemukan atau terjadi kesalahan server.");
                            }
                        }
                    },
                    error: function(err) {
                        isCheckingRiwayat = false;
                        alert("Gagal menghubungi server riwayat.");
                    }
                });
            },
            error: function() {
                isCheckingRiwayat = false;
                $('#det_rentang_tgl').text('Gagal sinkron tanggal');
            }
        });
    }

    // --- AI DEAD STOCK ADVISOR JS PIPELINE ---
    var currentDeadReportContext = "";
    var deadChatHistoryData = [];
    const defaultDeadPromptText = "Anda adalah Konsultan Pengadaan Farmasi & Manajemen Aset Rumah Sakit yang ahli. Analisis data obat mati / mengendap (dead stock & slow moving) berikut (mencakup total item, volume stok fisik, estimasi nilai aset mengendap, dan 10 besar obat dengan aset terbesar) dan susun Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:\n1. Analisis Risiko Keuangan (seberapa besar dana yang tertahan di depo/gudang dan potensi kerugian kadaluarsa).\n2. Identifikasi Obat Kritis (sorotan khusus terhadap top 10 obat dengan aset mengendap terbesar).\n3. Rekomendasi Aksi Penyelamatan Modal (saran retur supplier, distribusi antar depo, pembuatan bundle resep, atau penghematan anggaran pengadaan obat sejenis berikutnya).";

    function resetDeadPrompt() {
        $('#aiDeadPrompt').val(defaultDeadPromptText);
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

    $(document).on('click', '#btnAnalyzeDead', function() {
        if (!_deadStockResponseData) {
            alert('Silakan tampilkan data dead stock terlebih dahulu.');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menganalisis...');
        $('#aiDeadReportContainer').html('<div class="text-center py-4"><div class="spinner-border text-info mb-2"></div><div class="small text-muted">AI sedang menganalisis dead stock farmasi...</div></div>');

        // Kirim sample data 50 obat mati bernilai tertinggi
        var sampleObat = (_deadStockResponseData.data || []).map(function(o) {
            return {
                kode: o.kode_brng,
                nama: o.nama_brng,
                satuan: o.satuan || '',
                depo: o.nm_bangsal,
                stok: o.stok_val,
                hpp: o.hpp_val,
                nilai_aset: o.aset_val
            };
        });

        var deadRawData = {
            periode_cutoff: _deadStockResponseData.summary.cutoff_start + ' s.d ' + _deadStockResponseData.summary.cutoff_end,
            lokasi: $('#kd_bangsal option:selected').text(),
            summary: {
                total_item: _deadStockResponseData.summary.total_item,
                total_stok: _deadStockResponseData.summary.total_stok,
                total_nilai_aset: _deadStockResponseData.summary.total_aset
            },
            top_10_grafik: _deadStockResponseData.chart || {},
            sample_dead_stock: sampleObat
        };

        var formData = new URLSearchParams();
        formData.append('action', 'batch_summary');
        formData.append('raw_data', JSON.stringify([deadRawData]));
        formData.append('custom_prompt', $('#aiDeadPrompt').val().trim());
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
            const aiThinkingContainer = document.getElementById('aiDeadReportContainer');
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
                                $('#aiDeadReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + data.message + '</div>');
                            }
                            if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                fullText += data.choices[0].delta.content;
                                $('#aiDeadReportContainer').html(parseMarkdownToHtml(fullText));
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');

            if (!isError && fullText) {
                currentDeadReportContext = fullText;
                deadChatHistoryData = [];
                $('#deadChatHistory').html('<div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>');
            }
        }).catch(err => {
            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');
            $('#aiDeadReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: Gagal menghubungi server (' + err.message + ')</div>');
        });
    });

    $(document).on('submit', '#deadChatForm', function(e) {
        e.preventDefault();
        const input = $('#deadChatInput');
        const messageText = input.val().trim();
        if (!messageText || !currentDeadReportContext) return;

        if (deadChatHistoryData.length === 0) {
            $('#deadChatHistory').empty();
        }

        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        $('#deadChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-primary border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-primary"><i class="fas fa-user me-1"></i>Anda</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light">' + parseMarkdownToHtml(messageText) + '</div>' +
            '</div>'
        );
        $('#deadChatHistory').scrollTop($('#deadChatHistory')[0].scrollHeight);

        input.val('');
        $('#deadChatInput, #btnSendDeadChat').prop('disabled', true);

        var replyId = 'dead_reply_' + Date.now();
        $('#deadChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-info border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-info"><i class="fas fa-robot me-1"></i>AI Farmasi Assistant</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light" id="' + replyId + '"><i class="fas fa-spinner fa-spin text-info me-1"></i> Mengetik...</div>' +
            '</div>'
        );
        $('#deadChatHistory').scrollTop($('#deadChatHistory')[0].scrollHeight);

        var sampleObat = (_deadStockResponseData.data || []).map(function(o) {
            return {
                kode: o.kode_brng,
                nama: o.nama_brng,
                satuan: o.satuan || '',
                depo: o.nm_bangsal,
                stok: o.stok_val,
                hpp: o.hpp_val,
                nilai_aset: o.aset_val
            };
        });

        var deadRawData = {
            periode_cutoff: _deadStockResponseData ? _deadStockResponseData.summary.cutoff_start + ' s.d ' + _deadStockResponseData.summary.cutoff_end : '',
            lokasi: $('#kd_bangsal option:selected').text(),
            summary: _deadStockResponseData ? _deadStockResponseData.summary : {},
            sample_dead_stock: sampleObat
        };

        var chatData = new URLSearchParams();
        chatData.append('action', 'chat_discuss');
        chatData.append('message', messageText);
        chatData.append('report_context', currentDeadReportContext);
        chatData.append('raw_data', JSON.stringify([deadRawData]));
        chatData.append('custom_prompt', $('#aiDeadPrompt').val().trim());
        chatData.append('history', JSON.stringify(deadChatHistoryData));
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
            const aiThinkingContainer = document.getElementById('aiDeadReportContainer');
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
                                $('#deadChatHistory').scrollTop($('#deadChatHistory')[0].scrollHeight);
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            $('#deadChatInput, #btnSendDeadChat').prop('disabled', false);

            if (!isError && fullReply) {
                deadChatHistoryData.push({ role: 'user', content: messageText });
                deadChatHistoryData.push({ role: 'assistant', content: fullReply });
            }
        }).catch(err => {
            $('#deadChatInput, #btnSendDeadChat').prop('disabled', false);
            $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Error koneksi</span>');
        });
    });

</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>
