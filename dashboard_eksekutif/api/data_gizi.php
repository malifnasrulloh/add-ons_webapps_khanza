<?php
/*
 * File: api/data_gizi.php
 * Deskripsi: API untuk data distribusi gizi/diet pasien rawat inap.
 * Terproteksi auth_guard (auto_prepend).
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once(dirname(__DIR__) . '/config/koneksi.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak.']);
    exit;
}

// Lepas lock session agar tidak memblokir request konkuren lain
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

try {
    $params = [':tgl_awal' => $tgl_awal, ':tgl_akhir' => $tgl_akhir];

    // 1. Kueri Ringkasan (Summary)
    // Total Porsi Makanan Terdistribusi
    $sql_total = "
        SELECT COUNT(*) 
        FROM detail_beri_diet dbd
        WHERE dbd.tanggal BETWEEN :tgl_awal AND :tgl_akhir
    ";
    $stmt_tot = $koneksi_pdo->prepare($sql_total);
    $stmt_tot->execute($params);
    $total_porsi = (int)$stmt_tot->fetchColumn();

    // Rata-rata Porsi per Hari
    $days = (strtotime($tgl_akhir) - strtotime($tgl_awal)) / 86400 + 1;
    $avg_porsi = $days > 0 ? round($total_porsi / $days, 1) : 0;

    // Jumlah Varian Menu Diet Aktif
    $sql_diet_unik = "
        SELECT COUNT(DISTINCT dbd.kd_diet) 
        FROM detail_beri_diet dbd
        WHERE dbd.tanggal BETWEEN :tgl_awal AND :tgl_akhir
    ";
    $stmt_du = $koneksi_pdo->prepare($sql_diet_unik);
    $stmt_du->execute($params);
    $total_diet_unik = (int)$stmt_du->fetchColumn();

    $summary = [
        'total_porsi' => $total_porsi,
        'avg_porsi' => $avg_porsi,
        'total_diet_unik' => $total_diet_unik
    ];

    // 2. Kueri Top 10 Menu Diet (Bar Chart)
    $sql_chart = "
        SELECT d.nama_diet, COUNT(*) AS jumlah
        FROM detail_beri_diet dbd
        INNER JOIN diet d ON dbd.kd_diet = d.kd_diet
        WHERE dbd.tanggal BETWEEN :tgl_awal AND :tgl_akhir
        GROUP BY dbd.kd_diet
        ORDER BY jumlah DESC
        LIMIT 10
    ";
    $stmt_chart = $koneksi_pdo->prepare($sql_chart);
    $stmt_chart->execute($params);
    
    $chart_labels = [];
    $chart_values = [];
    while ($row = $stmt_chart->fetch(PDO::FETCH_ASSOC)) {
        $chart_labels[] = $row['nama_diet'];
        $chart_values[] = (int)$row['jumlah'];
    }

    // 3. Kueri Tren Harian Distribusi Porsi Makanan (Line Chart)
    $sql_trends = "
        SELECT dbd.tanggal, COUNT(*) AS jumlah
        FROM detail_beri_diet dbd
        WHERE dbd.tanggal BETWEEN :tgl_awal AND :tgl_akhir
        GROUP BY dbd.tanggal
        ORDER BY dbd.tanggal ASC
    ";
    $stmt_trends = $koneksi_pdo->prepare($sql_trends);
    $stmt_trends->execute($params);
    
    $trend_labels = [];
    $trend_values = [];
    while ($row = $stmt_trends->fetch(PDO::FETCH_ASSOC)) {
        $trend_labels[] = date('d/m', strtotime($row['tanggal']));
        $trend_values[] = (int)$row['jumlah'];
    }

    // 4. Kueri Rincian Distribusi Detail (DataTables)
    $sql_detail = "
        SELECT 
            dbd.tanggal,
            dbd.no_rawat,
            p.nm_pasien,
            b.nm_bangsal,
            dbd.kd_kamar,
            dbd.waktu,
            d.nama_diet
        FROM detail_beri_diet dbd
        INNER JOIN reg_periksa rp ON dbd.no_rawat = rp.no_rawat
        INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        INNER JOIN kamar k ON dbd.kd_kamar = k.kd_kamar
        INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
        INNER JOIN diet d ON dbd.kd_diet = d.kd_diet
        WHERE dbd.tanggal BETWEEN :tgl_awal AND :tgl_akhir
        ORDER BY dbd.tanggal DESC, dbd.waktu ASC
        LIMIT 2000 -- Batasan aman agar payload tidak terlalu bengkak jika rentang sangat panjang
    ";
    $stmt_det = $koneksi_pdo->prepare($sql_detail);
    $stmt_det->execute($params);
    $data_detail = $stmt_det->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'summary' => $summary,
        'chart' => [
            'labels' => $chart_labels,
            'values' => $chart_values
        ],
        'trends' => [
            'labels' => $trend_labels,
            'values' => $trend_values
        ],
        'data' => $data_detail
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal memuat data gizi: ' . $e->getMessage()
    ]);
}
?>
