<?php
/*
 * File: laporan_penunjang.php (NEW MODULE - v1.0.0)
 * Dashboard Penunjang Medis: Analisis Laboratorium & Radiologi
 * Menggunakan kueri PDO Prepared Statements yang aman dan terproteksi.
 */

$page_title = "Analisa Penunjang Medis (Lab & Rad)";
require_once('includes/header.php');
require_once('includes/functions.php');

$tgl_awal = isset($_GET['tgl_awal']) ? htmlspecialchars($_GET['tgl_awal']) : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? htmlspecialchars($_GET['tgl_akhir']) : date('Y-m-d');
$kd_pj = isset($_GET['kd_pj']) ? htmlspecialchars($_GET['kd_pj']) : '';
$status_lanjut = isset($_GET['status_lanjut']) ? htmlspecialchars($_GET['status_lanjut']) : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

$is_search = ($action == 'cari');

// 1. Dropdown Penjamin
$penjabs = [];
try {
    $stmt_pj = $koneksi_pdo->prepare("SELECT kd_pj, png_jawab FROM penjab WHERE status = '1' ORDER BY png_jawab ASC");
    $stmt_pj->execute();
    $penjabs = $stmt_pj->fetchAll();
} catch (PDOException $e) {
    error_log("Error loading penjamin: " . $e->getMessage());
}

$metrics = [
    'total_lab' => 0,
    'total_rad' => 0,
    'revenue_lab' => 0,
    'revenue_rad' => 0,
    'total_revenue' => 0
];

$data_lab = [];
$data_rad = [];
$chart_lab = ['labels' => [], 'data' => []];
$chart_rad = ['labels' => [], 'data' => []];

if ($is_search) {
    // Construct Query Filters
    $where_lab = " WHERE pl.tgl_periksa BETWEEN :tgl_awal AND :tgl_akhir ";
    $where_rad = " WHERE pr.tgl_periksa BETWEEN :tgl_awal AND :tgl_akhir ";
    
    $params = [
        ':tgl_awal' => $tgl_awal,
        ':tgl_akhir' => $tgl_akhir
    ];
    
    if (!empty($kd_pj)) {
        $where_lab .= " AND rp.kd_pj = :kd_pj ";
        $where_rad .= " AND rp.kd_pj = :kd_pj ";
        $params[':kd_pj'] = $kd_pj;
    }
    
    if (!empty($status_lanjut)) {
        $where_lab .= " AND pl.status = :status_lanjut ";
        $where_rad .= " AND pr.status = :status_lanjut ";
        $params[':status_lanjut'] = $status_lanjut;
    }
    
    try {
        // --- 1. Query Data Lab ---
        $sql_lab = "
            SELECT 
                pl.no_rawat, pl.tgl_periksa, pl.jam, pl.biaya, pl.status,
                j.nm_perawatan,
                p.no_rkm_medis, p.nm_pasien,
                d.nm_dokter AS nm_dokter_perujuk,
                dp.nm_dokter AS nm_dokter_pemeriksa,
                pj.png_jawab
            FROM periksa_lab pl
            INNER JOIN reg_periksa rp ON pl.no_rawat = rp.no_rawat
            INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
            INNER JOIN jns_perawatan_lab j ON pl.kd_jenis_prw = j.kd_jenis_prw
            LEFT JOIN dokter d ON pl.dokter_perujuk = d.kd_dokter
            LEFT JOIN dokter dp ON pl.kd_dokter = dp.kd_dokter
            LEFT JOIN penjab pj ON rp.kd_pj = pj.kd_pj
            $where_lab
            ORDER BY pl.tgl_periksa DESC, pl.jam DESC
        ";
        
        $stmt_lab = $koneksi_pdo->prepare($sql_lab);
        $stmt_lab->execute($params);
        $data_lab = $stmt_lab->fetchAll();
        
        // --- 2. Query Data Radiologi ---
        $sql_rad = "
            SELECT 
                pr.no_rawat, pr.tgl_periksa, pr.jam, pr.biaya, pr.status,
                jr.nm_perawatan,
                p.no_rkm_medis, p.nm_pasien,
                d.nm_dokter AS nm_dokter_perujuk,
                dp.nm_dokter AS nm_dokter_pemeriksa,
                pj.png_jawab
            FROM periksa_radiologi pr
            INNER JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
            INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
            INNER JOIN jns_perawatan_radiologi jr ON pr.kd_jenis_prw = jr.kd_jenis_prw
            LEFT JOIN dokter d ON pr.dokter_perujuk = d.kd_dokter
            LEFT JOIN dokter dp ON pr.kd_dokter = dp.kd_dokter
            LEFT JOIN penjab pj ON rp.kd_pj = pj.kd_pj
            $where_rad
            ORDER BY pr.tgl_periksa DESC, pr.jam DESC
        ";
        
        $stmt_rad = $koneksi_pdo->prepare($sql_rad);
        $stmt_rad->execute($params);
        $data_rad = $stmt_rad->fetchAll();
        
        // --- 3. Hitung Metrik & Summary ---
        $metrics['total_lab'] = count($data_lab);
        $metrics['total_rad'] = count($data_rad);
        
        foreach ($data_lab as $row) {
            $metrics['revenue_lab'] += $row['biaya'];
        }
        foreach ($data_rad as $row) {
            $metrics['revenue_rad'] += $row['biaya'];
        }
        $metrics['total_revenue'] = $metrics['revenue_lab'] + $metrics['revenue_rad'];
        
        // --- 4. Chart Data: Top 10 Lab ---
        $sql_chart_lab = "
            SELECT j.nm_perawatan, COUNT(*) AS jumlah
            FROM periksa_lab pl
            INNER JOIN jns_perawatan_lab j ON pl.kd_jenis_prw = j.kd_jenis_prw
            INNER JOIN reg_periksa rp ON pl.no_rawat = rp.no_rawat
            $where_lab
            GROUP BY pl.kd_jenis_prw
            ORDER BY jumlah DESC
            LIMIT 10
        ";
        $stmt_clab = $koneksi_pdo->prepare($sql_chart_lab);
        $stmt_clab->execute($params);
        $res_clab = $stmt_clab->fetchAll();
        foreach ($res_clab as $r) {
            $chart_lab['labels'][] = $r['nm_perawatan'];
            $chart_lab['data'][] = (int)$r['jumlah'];
        }
        
        // --- 5. Chart Data: Top 10 Radiologi ---
        $sql_chart_rad = "
            SELECT jr.nm_perawatan, COUNT(*) AS jumlah
            FROM periksa_radiologi pr
            INNER JOIN jns_perawatan_radiologi jr ON pr.kd_jenis_prw = jr.kd_jenis_prw
            INNER JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
            $where_rad
            GROUP BY pr.kd_jenis_prw
            ORDER BY jumlah DESC
            LIMIT 10
        ";
        $stmt_crad = $koneksi_pdo->prepare($sql_chart_rad);
        $stmt_crad->execute($params);
        $res_crad = $stmt_crad->fetchAll();
        foreach ($res_crad as $r) {
            $chart_rad['labels'][] = $r['nm_perawatan'];
            $chart_rad['data'][] = (int)$r['jumlah'];
        }
        
    } catch (PDOException $e) {
        error_log("Database error in penunjang dashboard: " . $e->getMessage());
    }
}
?>

<div class="container-fluid">
    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-flask text-primary me-2"></i> Analisa Penunjang Medis (Laboratorium & Radiologi)</h1>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title text-primary"><i class="fas fa-filter me-2"></i>Filter Laporan</h5>
            <form action="laporan_penunjang.php" method="GET">
                <input type="hidden" name="action" value="cari">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" class="form-control" name="tgl_awal" value="<?php echo $tgl_awal; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" class="form-control" name="tgl_akhir" value="<?php echo $tgl_akhir; ?>" required>
                    </div>
                    <div class="col-md-3">
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
                    <div class="col-md-3">
                        <label class="form-label">Jenis Perawatan</label>
                        <select name="status_lanjut" class="form-select">
                            <option value="">-- Semua Perawatan --</option>
                            <option value="Ralan" <?php echo ($status_lanjut == 'Ralan') ? 'selected' : ''; ?>>Rawat Jalan (Ralan)</option>
                            <option value="Ranap" <?php echo ($status_lanjut == 'Ranap') ? 'selected' : ''; ?>>Rawat Inap (Ranap)</option>
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
    
    <!-- KPI WIDGETS -->
    <div class="row mb-4 g-3">
        <div class="col-md-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Transaksi Laboratorium</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo number_format($metrics['total_lab'], 0, ',', '.'); ?> <span class="small text-muted" style="font-size:0.75rem;">Pemeriksaan</span></div>
                            <div class="text-xs text-muted mt-2">Nominal Pendapatan: Rp <?php echo number_format($metrics['revenue_lab'], 0, ',', '.'); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-flask fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Transaksi Radiologi</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo number_format($metrics['total_rad'], 0, ',', '.'); ?> <span class="small text-muted" style="font-size:0.75rem;">Pemeriksaan</span></div>
                            <div class="text-xs text-muted mt-2">Nominal Pendapatan: Rp <?php echo number_format($metrics['revenue_rad'], 0, ',', '.'); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-radiation fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Pendapatan Penunjang</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">Rp <?php echo number_format($metrics['total_revenue'], 0, ',', '.'); ?></div>
                            <div class="text-xs text-muted mt-2">Lab (<?php echo round($metrics['total_revenue'] ? ($metrics['revenue_lab'] / $metrics['total_revenue'] * 100) : 0); ?>%) | Rad (<?php echo round($metrics['total_revenue'] ? ($metrics['revenue_rad'] / $metrics['total_revenue'] * 100) : 0); ?>%)</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CHARTS ROW -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-flask me-2"></i>Top 10 Pemeriksaan Laboratorium</h6></div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height:320px;">
                        <canvas id="chartLab"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-success"><i class="fas fa-radiation me-2"></i>Top 10 Pemeriksaan Radiologi</h6></div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height:320px;">
                        <canvas id="chartRad"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (is_ai_active()): ?>
    <!-- AI DIAGNOSTICS ADVISOR -->
    <div class="card bg-dark border-secondary mt-4 shadow-sm mb-4">
        <div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold"><i class="fas fa-brain me-2"></i>Analisis Kinerja Diagnostik AI (AI Diagnostics Advisor)</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePenunjangPrompt">
                    <i class="fas fa-sliders-h me-1"></i> Tune Prompt
                </button>
                <button id="btnAnalyzePenunjang" class="btn btn-sm btn-success fw-bold">
                    <i class="fas fa-magic me-1"></i> Jalankan Analisis AI
                </button>
            </div>
        </div>
        <div class="card-body text-light">
            <!-- Collapsible Prompt Tuning Area -->
            <div class="collapse mb-3" id="collapsePenunjangPrompt">
                <div class="p-3 rounded border border-secondary bg-black bg-opacity-50">
                    <label class="form-label text-warning small fw-bold">System Prompt (Instruksi Analisis Penunjang Medis):</label>
                    <textarea id="aiPenunjangPrompt" class="form-control form-control-sm bg-dark text-light border-secondary" rows="4">Anda adalah Konsultan Pengembangan Penunjang Medis & Layanan Diagnostik RS (AI Diagnostics Advisor). Analisis data transaksi pemeriksaan laboratorium dan radiologi berikut, lalu buatlah Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:
1. Analisis Volume & Utilisasi Alat: Tinjau jenis pemeriksaan lab/rad yang paling banyak dikerjakan. Berikan perspektif apakah alat penunjang medis termanfaatkan dengan baik.
2. Analisis Pendapatan: Analisis profitabilitas unit penunjang, perbandingan kontribusi lab vs rad, serta segmentasi ralan vs ranap.
3. Rekomendasi Aksi Taktis: Saran penambahan alat baru yang memiliki demand tinggi, perbaikan alur rujukan dokter internal ke laboratorium/radiologi, atau efisiensi biaya operational.</textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Setel prompt khusus ini untuk menyesuaikan gaya laporan alur layanan yang dihasilkan AI.</small>
                        <button class="btn btn-xs btn-outline-warning text-warning" onclick="resetPenunjangPrompt()"><i class="fas fa-undo me-1"></i>Reset Prompt Default</button>
                    </div>
                </div>
            </div>

            <!-- Display Container Output -->
            <div id="aiPenunjangReportContainer" class="p-3 rounded border border-secondary bg-black bg-opacity-25 text-light" style="min-height: 120px; max-height: 500px; overflow-y: auto;">
                <div class="text-muted small text-center py-4">
                    <i class="fas fa-robot fa-2x mb-2 text-primary d-block"></i>
                    Klik tombol <strong>"Jalankan Analisis AI"</strong> di atas untuk memproses ringkasan analisis penunjang medis secara otomatis.
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Data penunjang dianalisis berdasarkan parameter filter terpilih.</small>
                <button class="btn btn-sm btn-outline-info" onclick="exportToWord('aiPenunjangReportContainer', 'Laporan_Analisis_Penunjang_Medis_AI.doc')">
                    <i class="fas fa-file-word me-1"></i> Ekspor Laporan ke Word (.doc)
                </button>
            </div>

            <!-- AI Interactive Chat Assistant -->
            <div class="mt-4 pt-3 border-top border-secondary">
                <h6 class="fw-bold text-info mb-2"><i class="fas fa-comments me-2"></i>Tanya Jawab & Diskusi Kinerja Penunjang dengan AI Assistant</h6>
                <div id="penunjangChatHistory" class="p-3 rounded border border-secondary bg-black bg-opacity-50 mb-2" style="max-height: 300px; overflow-y: auto; min-height: 100px;">
                    <div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>
                </div>
                <form id="penunjangChatForm">
                    <div class="input-group input-group-sm">
                        <input type="text" id="penunjangChatInput" class="form-control bg-dark text-light border-secondary" placeholder="Tanyakan detail penunjang (misal: Rata-rata biaya per pemeriksaan lab?)..." required>
                        <button class="btn btn-primary" type="submit" id="btnSendPenunjangChat">
                            <i class="fas fa-paper-plane me-1"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- TABS DETAIL DATA -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="penunjangTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="lab-tab" data-bs-toggle="tab" data-bs-target="#lab-pane" type="button" role="tab" aria-controls="lab-pane" aria-selected="true">
                        Laboratorium (<?php echo $metrics['total_lab']; ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="rad-tab" data-bs-toggle="tab" data-bs-target="#rad-pane" type="button" role="tab" aria-controls="rad-pane" aria-selected="false">
                        Radiologi (<?php echo $metrics['total_rad']; ?>)
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="penunjangTabContent">
                <!-- Tab Laboratorium -->
                <div class="tab-pane fade show active" id="lab-pane" role="tabpanel" aria-labelledby="lab-tab">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-sm dt-table" width="100%">
                            <thead>
                                <tr>
                                    <th>No. Rawat</th>
                                    <th>Tgl Periksa</th>
                                    <th>Jam</th>
                                    <th>No. RM</th>
                                    <th>Pasien</th>
                                    <th>Pemeriksaan</th>
                                    <th>Dokter Perujuk</th>
                                    <th>Petugas/Pemeriksa</th>
                                    <th>Penjamin</th>
                                    <th class="text-end">Biaya (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data_lab as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['no_rawat']); ?></td>
                                    <td><?php echo htmlspecialchars($row['tgl_periksa']); ?></td>
                                    <td><?php echo htmlspecialchars($row['jam']); ?></td>
                                    <td><?php echo htmlspecialchars($row['no_rkm_medis']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nm_pasien']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['nm_perawatan']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['nm_dokter_perujuk'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['nm_dokter_pemeriksa'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['png_jawab']); ?></td>
                                    <td class="text-end fw-bold text-primary"><?php echo number_format($row['biaya'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Radiologi -->
                <div class="tab-pane fade" id="rad-pane" role="tabpanel" aria-labelledby="rad-tab">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-sm dt-table" width="100%">
                            <thead>
                                <tr>
                                    <th>No. Rawat</th>
                                    <th>Tgl Periksa</th>
                                    <th>Jam</th>
                                    <th>No. RM</th>
                                    <th>Pasien</th>
                                    <th>Pemeriksaan</th>
                                    <th>Dokter Perujuk</th>
                                    <th>Spesialis Rad</th>
                                    <th>Penjamin</th>
                                    <th class="text-end">Biaya (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data_rad as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['no_rawat']); ?></td>
                                    <td><?php echo htmlspecialchars($row['tgl_periksa']); ?></td>
                                    <td><?php echo htmlspecialchars($row['jam']); ?></td>
                                    <td><?php echo htmlspecialchars($row['no_rkm_medis']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nm_pasien']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['nm_perawatan']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['nm_dokter_perujuk'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['nm_dokter_pemeriksa'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['png_jawab']); ?></td>
                                    <td class="text-end fw-bold text-success"><?php echo number_format($row['biaya'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center p-5">
                <h4 class="text-primary">Silakan pilih filter periode tanggal penunjang dan klik "Tampilkan"</h4>
                <p class="text-muted mb-0">Pengambilan data masif dinonaktifkan secara default demi mengamankan performa *concurrency* database SIMRS.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<?php ob_start(); ?>
<script>
    var _labRawData = <?php echo $is_search ? json_encode($data_lab) : '[]'; ?>;
    var _radRawData = <?php echo $is_search ? json_encode($data_rad) : '[]'; ?>;
    var currentPenunjangReportContext = "";
    var penunjangChatHistoryData = [];

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
                    title: 'Laporan Penunjang Diagnostik - ' + $('input[name="tgl_awal"]').val() + ' sd ' + $('input[name="tgl_akhir"]').val()
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

        <?php if ($is_search): ?>
        // Load Chart Lab (Horizontal Bar)
        var labCtx = document.getElementById("chartLab").getContext("2d");
        new Chart(labCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_lab['labels']); ?>,
                datasets: [{
                    label: 'Volume Pemeriksaan',
                    data: <?php echo json_encode($chart_lab['data']); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { beginAtZero: true }
                }
            }
        });

        // Load Chart Radiologi (Horizontal Bar)
        var radCtx = document.getElementById("chartRad").getContext("2d");
        new Chart(radCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_rad['labels']); ?>,
                datasets: [{
                    label: 'Volume Pemeriksaan',
                    data: <?php echo json_encode($chart_rad['data']); ?>,
                    backgroundColor: 'rgba(46, 204, 113, 0.7)',
                    borderColor: 'rgba(46, 204, 113, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { beginAtZero: true }
                }
            }
        });
        <?php endif; ?>
    });

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

    function resetPenunjangPrompt() {
        var defaultPrompt = "Anda adalah Konsultan Pengembangan Penunjang Medis & Layanan Diagnostik RS (AI Diagnostics Advisor). Analisis data transaksi pemeriksaan laboratorium dan radiologi berikut, lalu buatlah Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:\n1. Analisis Volume & Utilisasi Alat: Tinjau jenis pemeriksaan lab/rad yang paling banyak dikerjakan. Berikan perspektif apakah alat penunjang medis termanfaatkan dengan baik.\n2. Analisis Pendapatan: Analisis profitabilitas unit penunjang, perbandingan kontribusi lab vs rad, serta segmentasi ralan vs ranap.\n3. Rekomendasi Aksi Taktis: Saran penambahan alat baru yang memiliki demand tinggi, perbaikan alur rujukan dokter internal ke laboratorium/radiologi, atau efisiensi biaya operational.";
        $('#aiPenunjangPrompt').val(defaultPrompt);
    }

    $(document).on('click', '#btnAnalyzePenunjang', function() {
        if (_labRawData.length === 0 && _radRawData.length === 0) {
            alert('Silakan tampilkan data terlebih dahulu.');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menganalisis...');
        $('#aiPenunjangReportContainer').html('<div class="text-center py-4"><div class="spinner-border text-primary mb-2"></div><div class="small text-muted">AI sedang menganalisis data penunjang medis...</div></div>');

        // Slice data pasien maks 15 baris per kategori untuk efisiensi token
        var sampleLab = _labRawData.map(function(p) {
            return {
                tgl: p.tgl_periksa,
                pasien: p.nm_pasien,
                tindakan: p.nm_perawatan,
                perujuk: p.nm_dokter_perujuk,
                biaya: p.biaya
            };
        });

        var sampleRad = _radRawData.map(function(p) {
            return {
                tgl: p.tgl_periksa,
                pasien: p.nm_pasien,
                tindakan: p.nm_perawatan,
                perujuk: p.nm_dokter_perujuk,
                biaya: p.biaya
            };
        });

        var penunjangRawData = {
            periode: $('input[name="tgl_awal"]').val() + ' s.d ' + $('input[name="tgl_akhir"]').val(),
            penjamin: $('select[name="kd_pj"] option:selected').text(),
            status_perawatan: $('select[name="status_lanjut"] option:selected').text(),
            total_lab_volume: _labRawData.length,
            total_rad_volume: _radRawData.length,
            total_revenue_lab: <?php echo $is_search ? $metrics['revenue_lab'] : 0; ?>,
            total_revenue_rad: <?php echo $is_search ? $metrics['revenue_rad'] : 0; ?>,
            sample_lab: sampleLab,
            sample_rad: sampleRad
        };

        var formData = new URLSearchParams();
        formData.append('action', 'batch_summary');
        formData.append('raw_data', JSON.stringify([penunjangRawData]));
        formData.append('custom_prompt', $('#aiPenunjangPrompt').val().trim());
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
            const aiThinkingContainer = document.getElementById('aiPenunjangReportContainer');
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
                                $('#aiPenunjangReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + data.message + '</div>');
                            }
                            if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                fullText += data.choices[0].delta.content;
                                $('#aiPenunjangReportContainer').html(parseMarkdownToHtml(fullText));
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');

            if (!isError && fullText) {
                currentPenunjangReportContext = fullText;
                penunjangChatHistoryData = [];
                $('#penunjangChatHistory').html('<div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>');
            }
        }).catch(err => {
            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');
            $('#aiPenunjangReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: Gagal menghubungi server (' + err.message + ')</div>');
        });
    });

    $(document).on('submit', '#penunjangChatForm', function(e) {
        e.preventDefault();
        const input = $('#penunjangChatInput');
        const messageText = input.val().trim();
        if (!messageText || !currentPenunjangReportContext) return;

        if (penunjangChatHistoryData.length === 0) {
            $('#penunjangChatHistory').empty();
        }

        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        $('#penunjangChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-primary border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-primary"><i class="fas fa-user me-1"></i>Anda</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light">' + parseMarkdownToHtml(messageText) + '</div>' +
            '</div>'
        );
        $('#penunjangChatHistory').scrollTop($('#penunjangChatHistory')[0].scrollHeight);

        input.val('');
        $('#penunjangChatInput, #btnSendPenunjangChat').prop('disabled', true);

        var replyId = 'pen_reply_' + Date.now();
        $('#penunjangChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-info border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-info"><i class="fas fa-robot me-1"></i>AI Assistant</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light" id="' + replyId + '"><i class="fas fa-spinner fa-spin text-info me-1"></i> Mengetik...</div>' +
            '</div>'
        );
        $('#penunjangChatHistory').scrollTop($('#penunjangChatHistory')[0].scrollHeight);

        var sampleLab = _labRawData.map(function(p) {
            return {
                tgl: p.tgl_periksa,
                pasien: p.nm_pasien,
                tindakan: p.nm_perawatan,
                perujuk: p.nm_dokter_perujuk,
                biaya: p.biaya
            };
        });

        var sampleRad = _radRawData.map(function(p) {
            return {
                tgl: p.tgl_periksa,
                pasien: p.nm_pasien,
                tindakan: p.nm_perawatan,
                perujuk: p.nm_dokter_perujuk,
                biaya: p.biaya
            };
        });

        var penunjangRawData = {
            periode: $('input[name="tgl_awal"]').val() + ' s.d ' + $('input[name="tgl_akhir"]').val(),
            penjamin: $('select[name="kd_pj"] option:selected').text(),
            status_perawatan: $('select[name="status_lanjut"] option:selected').text(),
            total_lab_volume: _labRawData.length,
            total_rad_volume: _radRawData.length,
            total_revenue_lab: <?php echo $is_search ? $metrics['revenue_lab'] : 0; ?>,
            total_revenue_rad: <?php echo $is_search ? $metrics['revenue_rad'] : 0; ?>,
            sample_lab: sampleLab,
            sample_rad: sampleRad
        };

        var chatData = new URLSearchParams();
        chatData.append('action', 'chat_discuss');
        chatData.append('message', messageText);
        chatData.append('report_context', currentPenunjangReportContext);
        chatData.append('raw_data', JSON.stringify([penunjangRawData]));
        chatData.append('custom_prompt', $('#aiPenunjangPrompt').val().trim());
        chatData.append('history', JSON.stringify(penunjangChatHistoryData));
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
            const aiThinkingContainer = document.getElementById('aiPenunjangReportContainer');
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
                                $('#penunjangChatHistory').scrollTop($('#penunjangChatHistory')[0].scrollHeight);
                            }
                        } catch(e) {}
                    }
                }
            }

            $('#penunjangChatInput, #btnSendPenunjangChat').prop('disabled', false);
            $('#penunjangChatHistory').scrollTop($('#penunjangChatHistory')[0].scrollHeight);

            if (fullReply) {
                penunjangChatHistoryData.push({ role: 'user', content: messageText });
                penunjangChatHistoryData.push({ role: 'assistant', content: fullReply });
            }
        }).catch(err => {
            $('#penunjangChatInput, #btnSendPenunjangChat').prop('disabled', false);
            $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Error: Gagal mendapatkan respon dari server (' + err.message + ')</span>');
        });
    });
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>
