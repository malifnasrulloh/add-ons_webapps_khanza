<?php
/*
 * File: laporan_pengadaan.php (NEW MODULE - v1.0.0)
 * Dashboard Pengadaan Farmasi & Analisis Vendor
 * Menggunakan kueri PDO Prepared Statements yang aman dan terproteksi.
 */

$page_title = "Pengadaan Farmasi & Analisis Vendor";
require_once('includes/header.php');
require_once('includes/functions.php');

$tgl_awal = isset($_GET['tgl_awal']) ? htmlspecialchars($_GET['tgl_awal']) : date('Y-m-d', strtotime('-30 days'));
$tgl_akhir = isset($_GET['tgl_akhir']) ? htmlspecialchars($_GET['tgl_akhir']) : date('Y-m-d');
$kode_suplier = isset($_GET['kode_suplier']) ? htmlspecialchars($_GET['kode_suplier']) : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

$is_search = ($action == 'cari');

// 1. Dropdown Supplier
$suppliers = [];
try {
    $stmt_sup = $koneksi_pdo->prepare("SELECT kode_suplier, nama_suplier FROM datasuplier ORDER BY nama_suplier ASC");
    $stmt_sup->execute();
    $suppliers = $stmt_sup->fetchAll();
} catch (PDOException $e) {
    error_log("Error loading suppliers: " . $e->getMessage());
}

$metrics = [
    'total_pengadaan' => 0,
    'total_belum_lunas' => 0,
    'vendor_aktif' => 0,
    'total_transaksi' => 0,
    'faktur_lunas_cnt' => 0,
    'faktur_belum_cnt' => 0
];

$data_pengadaan = [];
$chart_vendor = ['labels' => [], 'data' => []];
$chart_status = ['labels' => ['Sudah Dibayar', 'Belum Dibayar', 'Belum Lunas', 'Titip Faktur'], 'data' => [0, 0, 0, 0]];

if ($is_search) {
    // Construct Query Filters
    $where = " WHERE p.tgl_pesan BETWEEN :tgl_awal AND :tgl_akhir ";
    $params = [
        ':tgl_awal' => $tgl_awal,
        ':tgl_akhir' => $tgl_akhir
    ];
    
    if (!empty($kode_suplier)) {
        $where .= " AND p.kode_suplier = :kode_suplier ";
        $params[':kode_suplier'] = $kode_suplier;
    }
    
    try {
        // --- 1. Query Rincian Pengadaan ---
        $sql_pengadaan = "
            SELECT 
                p.no_faktur, p.no_order, p.tgl_pesan, p.tgl_faktur, p.tgl_tempo, p.tagihan, p.status,
                s.nama_suplier, s.kota, s.no_telp
            FROM pemesanan p
            INNER JOIN datasuplier s ON p.kode_suplier = s.kode_suplier
            $where
            ORDER BY p.tgl_pesan DESC
        ";
        
        $stmt_peng = $koneksi_pdo->prepare($sql_pengadaan);
        $stmt_peng->execute($params);
        $data_pengadaan = $stmt_peng->fetchAll();
        
        // --- 2. Hitung Metrik Summary ---
        $metrics['total_transaksi'] = count($data_pengadaan);
        $active_vendors = [];
        
        foreach ($data_pengadaan as $row) {
            $metrics['total_pengadaan'] += $row['tagihan'];
            
            // Filter Vendor Aktif
            if (!in_array($row['nama_suplier'], $active_vendors)) {
                $active_vendors[] = $row['nama_suplier'];
            }
            
            // Hitung Status
            if ($row['status'] == 'Sudah Dibayar') {
                $metrics['faktur_lunas_cnt']++;
                $chart_status['data'][0]++;
            } else {
                $metrics['faktur_belum_cnt']++;
                $metrics['total_belum_lunas'] += $row['tagihan'];
                
                if ($row['status'] == 'Belum Dibayar') {
                    $chart_status['data'][1]++;
                } elseif ($row['status'] == 'Belum Lunas') {
                    $chart_status['data'][2]++;
                } elseif ($row['status'] == 'Titip Faktur') {
                    $chart_status['data'][3]++;
                }
            }
        }
        $metrics['vendor_aktif'] = count($active_vendors);
        
        // --- 3. Chart Data: Top 10 Supplier ---
        $sql_chart_vendor = "
            SELECT s.nama_suplier, SUM(p.tagihan) AS total_belanja
            FROM pemesanan p
            INNER JOIN datasuplier s ON p.kode_suplier = s.kode_suplier
            $where
            GROUP BY p.kode_suplier
            ORDER BY total_belanja DESC
            LIMIT 10
        ";
        $stmt_cvendor = $koneksi_pdo->prepare($sql_chart_vendor);
        $stmt_cvendor->execute($params);
        $res_cvendor = $stmt_cvendor->fetchAll();
        foreach ($res_cvendor as $r) {
            $chart_vendor['labels'][] = $r['nama_suplier'];
            $chart_vendor['data'][] = (double)$r['total_belanja'];
        }
        
    } catch (PDOException $e) {
        error_log("Database error in procurement dashboard: " . $e->getMessage());
    }
}
?>

<div class="container-fluid">
    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-truck text-success me-2"></i> Pengadaan Farmasi & Analisis Vendor</h1>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title text-success"><i class="fas fa-filter me-2"></i>Filter Pengadaan</h5>
            <form action="laporan_pengadaan.php" method="GET">
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
                    <div class="col-md-4">
                        <label class="form-label">Supplier / Vendor</label>
                        <select name="kode_suplier" class="form-select">
                            <option value="">-- Semua Supplier --</option>
                            <?php foreach($suppliers as $s): ?>
                                <option value="<?php echo $s['kode_suplier']; ?>" <?php echo ($kode_suplier == $s['kode_suplier']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['nama_suplier']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100">
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
        <div class="col-md-3 col-6">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Belanja Pengadaan</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">Rp <?php echo number_format($metrics['total_pengadaan'], 0, ',', '.'); ?></div>
                            <div class="text-xs text-muted mt-2"><?php echo number_format($metrics['total_transaksi'], 0, ',', '.'); ?> Faktur Masuk</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-basket fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-6">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Utang Dagang (A/P)</div>
                            <div class="h4 mb-0 font-weight-bold text-danger">Rp <?php echo number_format($metrics['total_belum_lunas'], 0, ',', '.'); ?></div>
                            <div class="text-xs text-muted mt-2"><?php echo number_format($metrics['faktur_belum_cnt'], 0, ',', '.'); ?> Faktur Belum Lunas</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Supplier / Vendor Aktif</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo $metrics['vendor_aktif']; ?> <span class="small text-muted" style="font-size:0.75rem;">PBF</span></div>
                            <div class="text-xs text-muted mt-2">Dari total 212 data supplier</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-handshake fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Rasio Pembayaran Faktur</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">
                                <?php echo round($metrics['total_transaksi'] ? ($metrics['faktur_lunas_cnt'] / $metrics['total_transaksi'] * 100) : 0); ?>%
                            </div>
                            <div class="text-xs text-muted mt-2">Lunas: <?php echo $metrics['faktur_lunas_cnt']; ?> | Belum: <?php echo $metrics['faktur_belum_cnt']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-pie fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CHARTS ROW -->
    <div class="row mb-4">
        <div class="col-lg-7 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-success"><i class="fas fa-handshake me-2"></i>Top 10 Suplier Berdasarkan Total Pembelian (Pareto Belanja)</h6></div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height:320px;">
                        <canvas id="chartVendor"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-success"><i class="fas fa-wallet me-2"></i>Status Pembayaran Faktur (Volume)</h6></div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height:320px;">
                        <canvas id="chartStatus"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (is_ai_active()): ?>
    <!-- AI PROCUREMENT ADVISOR -->
    <div class="card bg-dark border-secondary mt-4 shadow-sm mb-4">
        <div class="card-header bg-gradient bg-success text-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold"><i class="fas fa-brain me-2"></i>Analisis Pengadaan & Suplier AI (AI Procurement Advisor)</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProcPrompt">
                    <i class="fas fa-sliders-h me-1"></i> Tune Prompt
                </button>
                <button id="btnAnalyzeProc" class="btn btn-sm btn-success fw-bold">
                    <i class="fas fa-magic me-1"></i> Jalankan Analisis AI
                </button>
            </div>
        </div>
        <div class="card-body text-light">
            <!-- Collapsible Prompt Tuning Area -->
            <div class="collapse mb-3" id="collapseProcPrompt">
                <div class="p-3 rounded border border-secondary bg-black bg-opacity-50">
                    <label class="form-label text-warning small fw-bold">System Prompt (Instruksi Analisis Pengadaan & Vendor):</label>
                    <textarea id="aiProcPrompt" class="form-control form-control-sm bg-dark text-light border-secondary" rows="4">Anda adalah Konsultan Supply Chain Farmasi & Manajemen Aset Rumah Sakit (AI Procurement Advisor). Analisis data transaksi pengadaan obat dan alkes serta kinerja suplier berikut, lalu buatlah Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:
1. Pareto Belanja Vendor: Analisis suplier mana saja yang menerima pengeluaran modal terbesar. Berikan perspektif apakah ada konsentrasi risiko vendor.
2. Analisis Cash Outflow & Kredit: Analisis rasio faktur terbayar vs belum terbayar, total utang dagang jatuh tempo, dan implikasinya terhadap likuiditas kas.
3. Rekomendasi Aksi Taktis: Saran renegosiasi termin pembayaran (tempo), diversifikasi vendor untuk item obat krusial, atau strategi pembiayaan utang farmasi agar diskon pembelian termanfaatkan maksimal.</textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Setel prompt khusus ini untuk menyesuaikan gaya laporan yang dihasilkan AI.</small>
                        <button class="btn btn-xs btn-outline-warning text-warning" onclick="resetProcPrompt()"><i class="fas fa-undo me-1"></i>Reset Prompt Default</button>
                    </div>
                </div>
            </div>

            <!-- Display Container Output -->
            <div id="aiProcReportContainer" class="p-3 rounded border border-secondary bg-black bg-opacity-25 text-light" style="min-height: 120px; max-height: 500px; overflow-y: auto;">
                <div class="text-muted small text-center py-4">
                    <i class="fas fa-robot fa-2x mb-2 text-success d-block"></i>
                    Klik tombol <strong>"Jalankan Analisis AI"</strong> di atas untuk memproses ringkasan pengadaan & kinerja vendor secara otomatis.
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Data pengadaan dianalisis berdasarkan parameter filter terpilih.</small>
                <button class="btn btn-sm btn-outline-info" onclick="exportToWord('aiProcReportContainer', 'Laporan_Analisis_Pengadaan_Farmasi_AI.doc')">
                    <i class="fas fa-file-word me-1"></i> Ekspor Laporan ke Word (.doc)
                </button>
            </div>

            <!-- AI Interactive Chat Assistant -->
            <div class="mt-4 pt-3 border-top border-secondary">
                <h6 class="fw-bold text-info mb-2"><i class="fas fa-comments me-2"></i>Tanya Jawab & Diskusi Pengadaan & Suplier dengan AI Assistant</h6>
                <div id="procChatHistory" class="p-3 rounded border border-secondary bg-black bg-opacity-50 mb-2" style="max-height: 300px; overflow-y: auto; min-height: 100px;">
                    <div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>
                </div>
                <form id="procChatForm">
                    <div class="input-group input-group-sm">
                        <input type="text" id="procChatInput" class="form-control bg-dark text-light border-secondary" placeholder="Tanyakan detail pengadaan (misal: Berapa nominal tagihan jatuh tempo terdekat?)..." required>
                        <button class="btn btn-success" type="submit" id="btnSendProcChat">
                            <i class="fas fa-paper-plane me-1"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- DETAIL DATA TABLE -->
    <div class="card shadow-sm mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-file-invoice me-2"></i>Rincian Faktur Pemesanan / Pengadaan</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-sm dt-table" width="100%">
                    <thead>
                        <tr>
                            <th>No. Faktur</th>
                            <th>No. Order</th>
                            <th>Supplier / Vendor</th>
                            <th>Tgl. Pesan</th>
                            <th>Tgl. Faktur</th>
                            <th>Tgl. Jatuh Tempo</th>
                            <th class="text-end">Nilai Tagihan (Rp)</th>
                            <th class="text-center">Status Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data_pengadaan as $row): 
                            $status_class = "bg-danger";
                            if ($row['status'] == 'Sudah Dibayar') {
                                $status_class = "bg-success";
                            } elseif ($row['status'] == 'Belum Lunas') {
                                $status_class = "bg-warning text-dark";
                            } elseif ($row['status'] == 'Titip Faktur') {
                                $status_class = "bg-info text-dark";
                            }
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['no_faktur']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['no_order']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_suplier']); ?></td>
                            <td><?php echo htmlspecialchars($row['tgl_pesan']); ?></td>
                            <td><?php echo htmlspecialchars($row['tgl_faktur']); ?></td>
                            <td><?php echo htmlspecialchars($row['tgl_tempo']); ?></td>
                            <td class="text-end fw-bold text-success"><?php echo number_format($row['tagihan'], 0, ',', '.'); ?></td>
                            <td class="text-center"><span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php else: ?>
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center p-5">
                <h4 class="text-success">Silakan pilih filter tanggal pengadaan dan klik "Tampilkan"</h4>
                <p class="text-muted mb-0">Data pengadaan farmasi dimuat sesuai filter untuk mengoptimalkan kinerja *runtime* memori PHP.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<?php ob_start(); ?>
<script>
    var _procRawData = <?php echo $is_search ? json_encode($data_pengadaan) : '[]'; ?>;
    var currentProcReportContext = "";
    var procChatHistoryData = [];

    $(document).ready(function() {
        // Init DataTables
        $('.dt-table').DataTable({
            "responsive": true,
            "order": [[ 3, "desc" ]],
            "pageLength": 10,
            "lengthChange": true,
            "dom": 'Bfrtip',
            "buttons": [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i> Export Excel',
                    className: 'btn btn-success btn-sm',
                    title: 'Laporan Pengadaan Farmasi - ' + $('input[name="tgl_awal"]').val() + ' sd ' + $('input[name="tgl_akhir"]').val()
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
        // Load Chart Vendor (Horizontal Bar)
        var vendorCtx = document.getElementById("chartVendor").getContext("2d");
        new Chart(vendorCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_vendor['labels']); ?>,
                datasets: [{
                    label: 'Total Belanja (Rp)',
                    data: <?php echo json_encode($chart_vendor['data']); ?>,
                    backgroundColor: 'rgba(25, 135, 84, 0.7)',
                    borderColor: 'rgba(25, 135, 84, 1)',
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

        // Load Chart Status (Donut Chart)
        var statusCtx = document.getElementById("chartStatus").getContext("2d");
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($chart_status['labels']); ?>,
                datasets: [{
                    data: <?php echo json_encode($chart_status['data']); ?>,
                    backgroundColor: ['#198754', '#dc3545', '#ffc107', '#0dcaf0'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom' }
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

    function resetProcPrompt() {
        var defaultPrompt = "Anda adalah Konsultan Supply Chain Farmasi & Manajemen Aset Rumah Sakit (AI Procurement Advisor). Analisis data transaksi pengadaan obat dan alkes serta kinerja suplier berikut, lalu buatlah Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:\n1. Pareto Belanja Vendor: Analisis suplier mana saja yang menerima pengeluaran modal terbesar. Berikan perspektif apakah ada konsentrasi risiko vendor.\n2. Analisis Cash Outflow & Kredit: Analisis rasio faktur terbayar vs belum terbayar, total utang dagang jatuh tempo, dan implikasinya terhadap likuiditas kas.\n3. Rekomendasi Aksi Taktis: Saran renegosiasi termin pembayaran (tempo), diversifikasi vendor untuk item obat krusial, atau strategi pembiayaan utang farmasi agar diskon pembelian termanfaatkan maksimal.";
        $('#aiProcPrompt').val(defaultPrompt);
    }

    $(document).on('click', '#btnAnalyzeProc', function() {
        if (_procRawData.length === 0) {
            alert('Silakan tampilkan data terlebih dahulu.');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menganalisis...');
        $('#aiProcReportContainer').html('<div class="text-center py-4"><div class="spinner-border text-success mb-2"></div><div class="small text-muted">AI sedang menganalisis data pengadaan & suplier...</div></div>');

        // Slice data pasien maks 25 baris untuk efisiensi token
        var sampleProc = _procRawData.map(function(p) {
            return {
                faktur: p.no_faktur,
                suplier: p.nama_suplier,
                tgl: p.tgl_pesan,
                tagihan: p.tagihan,
                status: p.status
            };
        });

        var procRawData = {
            periode: $('input[name="tgl_awal"]').val() + ' s.d ' + $('input[name="tgl_akhir"]').val(),
            suplier: $('select[name="kode_suplier"] option:selected').text(),
            total_transaksi: _procRawData.length,
            total_pengadaan: <?php echo $is_search ? $metrics['total_pengadaan'] : 0; ?>,
            total_belum_lunas: <?php echo $is_search ? $metrics['total_belum_lunas'] : 0; ?>,
            vendor_aktif: <?php echo $is_search ? $metrics['vendor_aktif'] : 0; ?>,
            sample_data: sampleProc
        };

        var formData = new URLSearchParams();
        formData.append('action', 'batch_summary');
        formData.append('raw_data', JSON.stringify([procRawData]));
        formData.append('custom_prompt', $('#aiProcPrompt').val().trim());
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
            const aiThinkingContainer = document.getElementById('aiProcReportContainer');
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
                                $('#aiProcReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + data.message + '</div>');
                            }
                            if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                fullText += data.choices[0].delta.content;
                                $('#aiProcReportContainer').html(parseMarkdownToHtml(fullText));
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');

            if (!isError && fullText) {
                currentProcReportContext = fullText;
                procChatHistoryData = [];
                $('#procChatHistory').html('<div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>');
            }
        }).catch(err => {
            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');
            $('#aiProcReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: Gagal menghubungi server (' + err.message + ')</div>');
        });
    });

    $(document).on('submit', '#procChatForm', function(e) {
        e.preventDefault();
        const input = $('#procChatInput');
        const messageText = input.val().trim();
        if (!messageText || !currentProcReportContext) return;

        if (procChatHistoryData.length === 0) {
            $('#procChatHistory').empty();
        }

        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        $('#procChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-primary border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-primary"><i class="fas fa-user me-1"></i>Anda</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light">' + parseMarkdownToHtml(messageText) + '</div>' +
            '</div>'
        );
        $('#procChatHistory').scrollTop($('#procChatHistory')[0].scrollHeight);

        input.val('');
        $('#procChatInput, #btnSendProcChat').prop('disabled', true);

        var replyId = 'proc_reply_' + Date.now();
        $('#procChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-info border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-info"><i class="fas fa-robot me-1"></i>AI Assistant</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light" id="' + replyId + '"><i class="fas fa-spinner fa-spin text-info me-1"></i> Mengetik...</div>' +
            '</div>'
        );
        $('#procChatHistory').scrollTop($('#procChatHistory')[0].scrollHeight);

        var sampleProc = _procRawData.map(function(p) {
            return {
                faktur: p.no_faktur,
                suplier: p.nama_suplier,
                tgl: p.tgl_pesan,
                tagihan: p.tagihan,
                status: p.status
            };
        });

        var procRawData = {
            periode: $('input[name="tgl_awal"]').val() + ' s.d ' + $('input[name="tgl_akhir"]').val(),
            suplier: $('select[name="kode_suplier"] option:selected').text(),
            total_transaksi: _procRawData.length,
            total_pengadaan: <?php echo $is_search ? $metrics['total_pengadaan'] : 0; ?>,
            total_belum_lunas: <?php echo $is_search ? $metrics['total_belum_lunas'] : 0; ?>,
            vendor_aktif: <?php echo $is_search ? $metrics['vendor_aktif'] : 0; ?>,
            sample_data: sampleProc
        };

        var chatData = new URLSearchParams();
        chatData.append('action', 'chat_discuss');
        chatData.append('message', messageText);
        chatData.append('report_context', currentProcReportContext);
        chatData.append('raw_data', JSON.stringify([procRawData]));
        chatData.append('custom_prompt', $('#aiProcPrompt').val().trim());
        chatData.append('history', JSON.stringify(procChatHistoryData));
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
            const aiThinkingContainer = document.getElementById('aiProcReportContainer');
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
                                $('#procChatHistory').scrollTop($('#procChatHistory')[0].scrollHeight);
                            }
                        } catch(e) {}
                    }
                }
            }

            $('#procChatInput, #btnSendProcChat').prop('disabled', false);
            $('#procChatHistory').scrollTop($('#procChatHistory')[0].scrollHeight);

            if (fullReply) {
                procChatHistoryData.push({ role: 'user', content: messageText });
                procChatHistoryData.push({ role: 'assistant', content: fullReply });
            }
        }).catch(err => {
            $('#procChatInput, #btnSendProcChat').prop('disabled', false);
            $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Error: Gagal mendapatkan respon dari server (' + err.message + ')</span>');
        });
    });
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>
