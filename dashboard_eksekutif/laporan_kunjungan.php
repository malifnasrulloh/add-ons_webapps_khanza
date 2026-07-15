<?php
/*
 * File laporan_kunjungan.php (SECURITY HARDENED - PDO)
 * Laporan jumlah kunjungan pasien (Non-Batal) dengan filter Penjamin & Poliklinik.
 */

// 1. Setup
$page_title = "Laporan Kunjungan Pasien";
require_once('includes/header.php');
require_once('includes/functions.php');

// 2. Parameter Filter
$tgl_awal = isset($_GET['tgl_awal']) ? htmlspecialchars($_GET['tgl_awal']) : date('Y-m-d');
$jam_awal = isset($_GET['jam_awal']) ? htmlspecialchars($_GET['jam_awal']) : '00:00:00';
$tgl_akhir = isset($_GET['tgl_akhir']) ? htmlspecialchars($_GET['tgl_akhir']) : date('Y-m-d');
$jam_akhir = isset($_GET['jam_akhir']) ? htmlspecialchars($_GET['jam_akhir']) : '23:59:59';
$kd_pj = isset($_GET['kd_pj']) ? htmlspecialchars($_GET['kd_pj']) : ''; 
$kd_poli = isset($_GET['kd_poli']) ? htmlspecialchars($_GET['kd_poli']) : ''; 
$action = isset($_GET['action']) ? $_GET['action'] : ''; 

$datetime_awal = $tgl_awal . ' ' . $jam_awal;
$datetime_akhir = $tgl_akhir . ' ' . $jam_akhir;

// 3. Data Pendukung (Dropdown) - Menggunakan PDO
// --- Penjamin ---
$penjabs = [];
$sql_penjab = "SELECT kd_pj, png_jawab FROM penjab WHERE status = '1' ORDER BY png_jawab ASC";
$stmt_pj = $koneksi_pdo->prepare($sql_penjab);
$stmt_pj->execute();
$penjabs = $stmt_pj->fetchAll();

// --- Poliklinik ---
$polis = [];
$sql_poli = "SELECT kd_poli, nm_poli FROM poliklinik WHERE status = '1' ORDER BY nm_poli ASC";
$stmt_poli = $koneksi_pdo->prepare($sql_poli);
$stmt_poli->execute();
$polis = $stmt_poli->fetchAll();

// 4. Logika Pengambilan Data Tabel (Hanya jika ada action cari)
$data_ralan = [];
$data_ranap = [];
$is_search = ($action == 'cari');

if ($is_search) {
    // Base WHERE: Rentang tanggal & Tidak Batal
    $where_base = " WHERE CONCAT(reg_periksa.tgl_registrasi, ' ', reg_periksa.jam_reg) BETWEEN :awal AND :akhir AND reg_periksa.stts != 'Batal' ";
    $params = [
        ':awal' => $datetime_awal,
        ':akhir' => $datetime_akhir
    ];

    if (!empty($kd_pj)) {
        $where_base .= " AND reg_periksa.kd_pj = :kd_pj ";
        $params[':kd_pj'] = $kd_pj;
    }

    if (!empty($kd_poli)) {
        $where_base .= " AND reg_periksa.kd_poli = :kd_poli ";
        $params[':kd_poli'] = $kd_poli;
    }

    // --- Query Ralan ---
    $sql_ralan = "
        SELECT 
            reg_periksa.no_rawat, reg_periksa.tgl_registrasi, reg_periksa.jam_reg, 
            reg_periksa.no_rkm_medis, pasien.nm_pasien, 
            dokter.nm_dokter, poliklinik.nm_poli, penjab.png_jawab, reg_periksa.stts_daftar
        FROM reg_periksa
        INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
        INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
        INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
        INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
        $where_base AND reg_periksa.status_lanjut = 'Ralan'
        ORDER BY reg_periksa.tgl_registrasi DESC, reg_periksa.jam_reg DESC
    ";

    $stmt_ralan = $koneksi_pdo->prepare($sql_ralan);
    $stmt_ralan->execute($params);
    $data_ralan = $stmt_ralan->fetchAll();

    // --- Query Ranap ---
    $sql_ranap = "
        SELECT 
            reg_periksa.no_rawat, reg_periksa.tgl_registrasi, reg_periksa.jam_reg, 
            reg_periksa.no_rkm_medis, pasien.nm_pasien, 
            dokter.nm_dokter, poliklinik.nm_poli, penjab.png_jawab, reg_periksa.stts_daftar, reg_periksa.stts
        FROM reg_periksa
        INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
        INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
        INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
        INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
        $where_base AND reg_periksa.status_lanjut = 'Ranap'
        ORDER BY reg_periksa.tgl_registrasi DESC, reg_periksa.jam_reg DESC
    ";

    $stmt_ranap = $koneksi_pdo->prepare($sql_ranap);
    $stmt_ranap->execute($params);
    $data_ranap = $stmt_ranap->fetchAll();
}
?>

<div class="container-fluid">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title text-primary">Filter Laporan Kunjungan</h5>
            <form action="laporan_kunjungan.php" method="GET">
                <input type="hidden" name="action" value="cari">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" class="form-control" name="tgl_awal" value="<?php echo $tgl_awal; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Jam</label>
                        <input type="time" class="form-control" name="jam_awal" value="<?php echo $jam_awal; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" class="form-control" name="tgl_akhir" value="<?php echo $tgl_akhir; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Jam</label>
                        <input type="time" class="form-control" name="jam_akhir" value="<?php echo $jam_akhir; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Poliklinik</label>
                        <select name="kd_poli" class="form-select">
                            <option value="">-- Semua Poliklinik --</option>
                            <?php foreach($polis as $pl): ?>
                                <option value="<?php echo $pl['kd_poli']; ?>" <?php echo ($kd_poli == $pl['kd_poli']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pl['nm_poli']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Penjamin</label>
                        <select name="kd_pj" class="form-select">
                            <option value="">-- Semua Penjamin --</option>
                            <?php foreach($penjabs as $p): ?>
                                <option value="<?php echo $p['kd_pj']; ?>" <?php echo ($kd_pj == $p['kd_pj']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['png_jawab']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-search me-2"></i> Tampilkan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($is_search): ?>
    
    <div class="alert alert-info shadow-sm mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="mb-0">Total Kunjungan: <strong><?php echo count($data_ralan) + count($data_ranap); ?></strong></h5>
                <small>Rawat Jalan: <?php echo count($data_ralan); ?> | Rawat Inap: <?php echo count($data_ranap); ?></small>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-4 col-md-12 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Proporsi Kunjungan per Penjamin</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2" style="height: 300px;">
                        <canvas id="chartPieKunjungan"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8 col-md-12 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tren Kunjungan Harian</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 300px;">
                        <canvas id="chartLineKunjungan"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="ralan-tab" data-bs-toggle="tab" data-bs-target="#ralan" type="button" role="tab" aria-controls="ralan" aria-selected="true">
                        Rawat Jalan (<?php echo count($data_ralan); ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ranap-tab" data-bs-toggle="tab" data-bs-target="#ranap" type="button" role="tab" aria-controls="ranap" aria-selected="false">
                        Rawat Inap (<?php echo count($data_ranap); ?>)
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="myTabContent">
                
                <div class="tab-pane fade show active" id="ralan" role="tabpanel" aria-labelledby="ralan-tab">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-sm dt-table" width="100%">
                            <thead>
                                <tr>
                                    <th>No. Rawat</th>
                                    <th>Tgl Reg</th>
                                    <th>Jam</th>
                                    <th>No. RM</th>
                                    <th>Pasien</th>
                                    <th>Poliklinik</th>
                                    <th>Dokter</th>
                                    <th>Penjamin</th>
                                    <th>Jns Kunjungan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data_ralan as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['no_rawat']); ?></td>
                                    <td><?php echo htmlspecialchars($row['tgl_registrasi']); ?></td>
                                    <td><?php echo htmlspecialchars($row['jam_reg']); ?></td>
                                    <td><?php echo htmlspecialchars($row['no_rkm_medis']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nm_pasien']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nm_poli']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nm_dokter']); ?></td>
                                    <td><?php echo htmlspecialchars($row['png_jawab']); ?></td>
                                    <td><?php echo htmlspecialchars($row['stts_daftar']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="ranap" role="tabpanel" aria-labelledby="ranap-tab">
                    <?php if (!empty($kd_poli)): ?>
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="fas fa-info-circle me-1"></i> Data ini terfilter berdasarkan <strong>Asal Poli/IGD</strong> yang diregistrasikan di awal kunjungan oleh admisi.
                    </div>
                    <?php endif; ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-sm dt-table" width="100%">
                            <thead>
                                <tr>
                                    <th>No. Rawat</th>
                                    <th>Tgl Masuk</th>
                                    <th>Jam</th>
                                    <th>No. RM</th>
                                    <th>Pasien</th>
                                    <th>Asal Poli/IGD</th>
                                    <th>Dokter</th>
                                    <th>Penjamin</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data_ranap as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['no_rawat']); ?></td>
                                    <td><?php echo htmlspecialchars($row['tgl_registrasi']); ?></td>
                                    <td><?php echo htmlspecialchars($row['jam_reg']); ?></td>
                                    <td><?php echo htmlspecialchars($row['no_rkm_medis']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nm_pasien']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nm_poli']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nm_dokter']); ?></td>
                                    <td><?php echo htmlspecialchars($row['png_jawab']); ?></td>
                                    <td><?php echo htmlspecialchars($row['stts']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php if (is_ai_active()): ?>
    <!-- AI KUNJUNGAN ANALYZER CONTAINER -->
    <div class="card bg-dark border-secondary mt-4 shadow-sm mb-4">
        <div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold"><i class="fas fa-brain me-2"></i>Analisis Kunjungan Pasien AI (AI Patient Volume & Marketing Advisor)</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseKunjunganPrompt">
                    <i class="fas fa-sliders-h me-1"></i> Tune Prompt
                </button>
                <button id="btnAnalyzeKunjungan" class="btn btn-sm btn-success fw-bold">
                    <i class="fas fa-magic me-1"></i> Jalankan Analisis AI
                </button>
            </div>
        </div>
        <div class="card-body text-light">
            <!-- Collapsible Prompt Tuning Area -->
            <div class="collapse mb-3" id="collapseKunjunganPrompt">
                <div class="p-3 rounded border border-secondary bg-black bg-opacity-50">
                    <label class="form-label text-warning small fw-bold">System Prompt (Instruksi Analisis Kunjungan Pasien):</label>
                    <textarea id="aiKunjunganPrompt" class="form-control form-control-sm bg-dark text-light border-secondary" rows="4">Anda adalah Konsultan Pemasaran & Strategi Manajemen RS (AI Patient Volume & Marketing Advisor). Analisis data kunjungan pasien rawat jalan dan rawat inap berikut, lalu buatlah Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:
1. Tren Kunjungan: Analisis perbandingan volume Rawat Jalan vs Rawat Inap serta segmentasi berdasarkan penjamin (BPJS, Umum, Asuransi).
2. Analisis Spasial & Demografi: Analisis poliklinik dan dokter yang paling diminati, serta asal rujukan/kunjungan pasien.
3. Rekomendasi Aksi Taktis: Berikan strategi pemasaran, optimasi jadwal poli ramai, dan peningkatan efisiensi layanan admisi.</textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Setel prompt khusus ini untuk menyesuaikan gaya laporan alur layanan yang dihasilkan AI.</small>
                        <button class="btn btn-xs btn-outline-warning text-warning" onclick="resetKunjunganPrompt()"><i class="fas fa-undo me-1"></i>Reset Prompt Default</button>
                    </div>
                </div>
            </div>

            <!-- Display Container Output -->
            <div id="aiKunjunganReportContainer" class="p-3 rounded border border-secondary bg-black bg-opacity-25 text-light" style="min-height: 120px; max-height: 500px; overflow-y: auto;">
                <div class="text-muted small text-center py-4">
                    <i class="fas fa-robot fa-2x mb-2 text-primary d-block"></i>
                    Klik tombol <strong>"Jalankan Analisis AI"</strong> di atas untuk memproses ringkasan analisis kunjungan pasien secara otomatis.
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Volume kunjungan dianalisis berdasarkan periode filter terpilih.</small>
                <button class="btn btn-sm btn-outline-info" onclick="exportToWord('aiKunjunganReportContainer', 'Laporan_Analisis_Kunjungan_Pasien_AI.doc')">
                    <i class="fas fa-file-word me-1"></i> Ekspor Laporan ke Word (.doc)
                </button>
            </div>

            <!-- AI Interactive Chat Assistant -->
            <div class="mt-4 pt-3 border-top border-secondary">
                <h6 class="fw-bold text-info mb-2"><i class="fas fa-comments me-2"></i>Tanya Jawab & Diskusi Kunjungan Pasien dengan AI Assistant</h6>
                <div id="kunjunganChatHistory" class="p-3 rounded border border-secondary bg-black bg-opacity-50 mb-2" style="max-height: 300px; overflow-y: auto; min-height: 100px;">
                    <div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>
                </div>
                <form id="kunjunganChatForm">
                    <div class="input-group input-group-sm">
                        <input type="text" id="kunjunganChatInput" class="form-control bg-dark text-light border-secondary" placeholder="Tanyakan detail kunjungan (misal: Poli mana yang paling ramai pada periode ini?)..." required>
                        <button class="btn btn-primary" type="submit" id="btnSendKunjunganChat">
                            <i class="fas fa-paper-plane me-1"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center p-5">
                <h4 class="text-primary">Silakan pilih filter tanggal dan klik "Tampilkan"</h4>
                <p class="text-muted mb-0">Data tidak dimuat otomatis untuk menjaga performa aplikasi.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<?php ob_start(); ?>
<script>
    var _kunjunganRalanData = <?php echo $is_search ? json_encode($data_ralan) : '[]'; ?>;
    var _kunjunganRanapData = <?php echo $is_search ? json_encode($data_ranap) : '[]'; ?>;
    var currentKunjunganReportContext = "";
    var kunjunganChatHistoryData = [];

    $(document).ready(function() {
        // Init DataTables
        $('.dt-table').DataTable({
            "responsive": true,
            "order": [[ 1, "desc" ], [ 2, "desc" ]],
            "pageLength": 10,
            "lengthChange": true,
            "dom": 'Bfrtip',
            "buttons": [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i> Export Excel',
                    className: 'btn btn-success btn-sm',
                    title: 'Laporan Kunjungan Pasien - ' + $('input[name="tgl_awal"]').val() + ' sd ' + $('input[name="tgl_akhir"]').val()
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i> Export PDF',
                    className: 'btn btn-danger btn-sm',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    title: 'Laporan Kunjungan Pasien'
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Print',
                    className: 'btn btn-secondary btn-sm'
                }
            ],
            "language": {
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ baris",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                "paginate": {
                    "first": "Awal",
                    "last": "Akhir",
                    "next": "Lanjut",
                    "previous": "Kembali"
                }
            }
        });

        // Load Charts
        <?php if ($is_search): ?>
        loadCharts();
        <?php endif; ?>
    });

    function loadCharts() {
        var params = {
            tgl_awal: $('input[name="tgl_awal"]').val(),
            jam_awal: $('input[name="jam_awal"]').val(),
            tgl_akhir: $('input[name="tgl_akhir"]').val(),
            jam_akhir: $('input[name="jam_akhir"]').val(),
            kd_poli: $('select[name="kd_poli"]').val(),
            kd_pj: $('select[name="kd_pj"]').val()
        };

        $.ajax({
            url: 'api/data_kunjungan_chart.php',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(data) {
                renderPieChart(data.pie);
                renderLineChart(data.line);
            },
            error: function(xhr, status, error) {
                console.error("Gagal memuat data chart:", error);
                console.log("Response:", xhr.responseText);
            }
        });
    }

    function renderPieChart(pieData) {
        var ctx = document.getElementById("chartPieKunjungan");
        if(!ctx) return;
        
        var backgroundColors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69', '#2e59d9', '#17a673', '#2c9faf'];
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: pieData.labels,
                datasets: [{
                    data: pieData.data,
                    backgroundColor: backgroundColors,
                    hoverBackgroundColor: backgroundColors,
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + ' Pasien';
                            }
                        }
                    }
                },
                cutout: '70%',
            },
        });
    }

    function renderLineChart(lineData) {
        var ctx = document.getElementById("chartLineKunjungan");
        if(!ctx) return;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: lineData.labels,
                datasets: lineData.datasets
            },
            options: {
                maintainAspectRatio: false,
                layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
                plugins: {
                    legend: { display: true, position: 'top' },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { maxTicksLimit: 7 }
                    },
                    y: {
                        ticks: {
                            maxTicksLimit: 5,
                            padding: 10,
                            callback: function(value) { return value; } 
                        },
                        grid: { 
                            color: "rgb(234, 236, 244)", 
                            drawBorder: false, 
                            borderDash: [2], 
                            zeroLineBorderDash: [2] 
                        }
                    },
                }
            }
        });
    }

    function parseMarkdownToHtml(markdown) {
        if (!markdown) return '';
        return markdown
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/^\s*-\s+(.*)$/gm, '<li class="ms-3">$1</li>')
            .replace(/^\s*#\s+(.*)$/gm, '<h5 class="text-warning mt-2">$1</h5>')
            .replace(/^\s*##\s+(.*)$/gm, '<h6 class="text-info mt-2">$1</h6>')
            .replace(/^\s*###\s+(.*)$/gm, '<h6 class="text-white mt-2">$1</h6>')
            .replace(/\n/g, '<br>');
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

    function resetKunjunganPrompt() {
        var defaultPrompt = "Anda adalah Konsultan Pemasaran & Strategi Manajemen RS (AI Patient Volume & Marketing Advisor). Analisis data kunjungan pasien rawat jalan dan rawat inap berikut, lalu buatlah Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:\n1. Tren Kunjungan: Analisis perbandingan volume Rawat Jalan vs Rawat Inap serta segmentasi berdasarkan penjamin (BPJS, Umum, Asuransi).\n2. Analisis Spasial & Demografi: Analisis poliklinik dan dokter yang paling diminati, serta asal rujukan/kunjungan pasien.\n3. Rekomendasi Aksi Taktis: Berikan strategi pemasaran, optimasi jadwal poli ramai, dan peningkatan efisiensi layanan admisi.";
        $('#aiKunjunganPrompt').val(defaultPrompt);
    }

    $(document).on('click', '#btnAnalyzeKunjungan', function() {
        if (_kunjunganRalanData.length === 0 && _kunjunganRanapData.length === 0) {
            alert('Silakan tampilkan data terlebih dahulu.');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menganalisis...');
        $('#aiKunjunganReportContainer').html('<div class="text-center py-4"><div class="spinner-border text-primary mb-2"></div><div class="small text-muted">AI sedang menganalisis data kunjungan pasien...</div></div>');

        // Slice data pasien maks 25 baris per kategori
        var sampleRalan = _kunjunganRalanData.map(function(p) {
            return {
                no_rawat: p.no_rawat,
                tgl: p.tgl_registrasi,
                no_rm: p.no_rkm_medis,
                pasien: p.nm_pasien,
                poli: p.nm_poli,
                dokter: p.nm_dokter,
                penjamin: p.png_jawab,
                jenis: p.stts_daftar
            };
        });

        var sampleRanap = _kunjunganRanapData.map(function(p) {
            return {
                no_rawat: p.no_rawat,
                tgl: p.tgl_registrasi,
                no_rm: p.no_rkm_medis,
                pasien: p.nm_pasien,
                asal_poli: p.nm_poli,
                dokter: p.nm_dokter,
                penjamin: p.png_jawab,
                status: p.stts
            };
        });

        var kunjunganRawData = {
            periode: $('input[name="tgl_awal"]').val() + ' s.d ' + $('input[name="tgl_akhir"]').val(),
            filter_poliklinik: $('select[name="kd_poli"] option:selected').text(),
            filter_penjamin: $('select[name="kd_pj"] option:selected').text(),
            total_ralan: _kunjunganRalanData.length,
            total_ranap: _kunjunganRanapData.length,
            sample_data_ralan: sampleRalan,
            sample_data_ranap: sampleRanap
        };

        var formData = new URLSearchParams();
        formData.append('action', 'batch_summary');
        formData.append('raw_data', JSON.stringify([kunjunganRawData]));
        formData.append('custom_prompt', $('#aiKunjunganPrompt').val().trim());
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
            const aiThinkingContainer = document.getElementById('aiKunjunganReportContainer');
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
                                $('#aiKunjunganReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + data.message + '</div>');
                            }
                            if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                fullText += data.choices[0].delta.content;
                                $('#aiKunjunganReportContainer').html(parseMarkdownToHtml(fullText));
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');

            if (!isError && fullText) {
                currentKunjunganReportContext = fullText;
                kunjunganChatHistoryData = [];
                $('#kunjunganChatHistory').html('<div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>');
            }
        }).catch(err => {
            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');
            $('#aiKunjunganReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: Gagal menghubungi server (' + err.message + ')</div>');
        });
    });

    $(document).on('submit', '#kunjunganChatForm', function(e) {
        e.preventDefault();
        const input = $('#kunjunganChatInput');
        const messageText = input.val().trim();
        if (!messageText || !currentKunjunganReportContext) return;

        if (kunjunganChatHistoryData.length === 0) {
            $('#kunjunganChatHistory').empty();
        }

        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        $('#kunjunganChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-primary border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-primary"><i class="fas fa-user me-1"></i>Anda</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light">' + parseMarkdownToHtml(messageText) + '</div>' +
            '</div>'
        );
        $('#kunjunganChatHistory').scrollTop($('#kunjunganChatHistory')[0].scrollHeight);

        input.val('');
        $('#kunjunganChatInput, #btnSendKunjunganChat').prop('disabled', true);

        var replyId = 'kunj_reply_' + Date.now();
        $('#kunjunganChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-info border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-info"><i class="fas fa-robot me-1"></i>AI Assistant</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light" id="' + replyId + '"><i class="fas fa-spinner fa-spin text-info me-1"></i> Mengetik...</div>' +
            '</div>'
        );
        $('#kunjunganChatHistory').scrollTop($('#kunjunganChatHistory')[0].scrollHeight);

        var sampleRalan = _kunjunganRalanData.map(function(p) {
            return {
                no_rawat: p.no_rawat,
                tgl: p.tgl_registrasi,
                no_rm: p.no_rkm_medis,
                pasien: p.nm_pasien,
                poli: p.nm_poli,
                dokter: p.nm_dokter,
                penjamin: p.png_jawab,
                jenis: p.stts_daftar
            };
        });

        var sampleRanap = _kunjunganRanapData.map(function(p) {
            return {
                no_rawat: p.no_rawat,
                tgl: p.tgl_registrasi,
                no_rm: p.no_rkm_medis,
                pasien: p.nm_pasien,
                asal_poli: p.nm_poli,
                dokter: p.nm_dokter,
                penjamin: p.png_jawab,
                status: p.stts
            };
        });

        var kunjunganRawData = {
            periode: $('input[name="tgl_awal"]').val() + ' s.d ' + $('input[name="tgl_akhir"]').val(),
            filter_poliklinik: $('select[name="kd_poli"] option:selected').text(),
            filter_penjamin: $('select[name="kd_pj"] option:selected').text(),
            total_ralan: _kunjunganRalanData.length,
            total_ranap: _kunjunganRanapData.length,
            sample_data_ralan: sampleRalan,
            sample_data_ranap: sampleRanap
        };

        var chatData = new URLSearchParams();
        chatData.append('action', 'chat_discuss');
        chatData.append('message', messageText);
        chatData.append('report_context', currentKunjunganReportContext);
        chatData.append('raw_data', JSON.stringify([kunjunganRawData]));
        chatData.append('custom_prompt', $('#aiKunjunganPrompt').val().trim());
        chatData.append('history', JSON.stringify(kunjunganChatHistoryData));
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
            const aiThinkingContainer = document.getElementById('aiKunjunganReportContainer');
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
                            if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                fullReply += data.choices[0].delta.content;
                                $('#' + replyId).html(parseMarkdownToHtml(fullReply));
                            }
                        } catch(e) {}
                    }
                }
            }

            $('#kunjunganChatInput, #btnSendKunjunganChat').prop('disabled', false);
            $('#kunjunganChatHistory').scrollTop($('#kunjunganChatHistory')[0].scrollHeight);

            if (fullReply) {
                kunjunganChatHistoryData.push({ role: 'user', content: messageText });
                kunjunganChatHistoryData.push({ role: 'assistant', content: fullReply });
            }
        }).catch(err => {
            $('#kunjunganChatInput, #btnSendKunjunganChat').prop('disabled', false);
            $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Error: Gagal mendapatkan respon dari server (' + err.message + ')</span>');
        });
    });
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>