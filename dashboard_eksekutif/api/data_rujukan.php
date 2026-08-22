<?php
/*
 * File: api/data_rujukan.php
 * Deskripsi: API untuk data rujukan masuk, top faskes perujuk, dan kategori rujukan.
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
    // Total Rujukan Masuk
    $sql_total = "
        SELECT COUNT(*) 
        FROM rujuk_masuk rm
        INNER JOIN reg_periksa rp ON rm.no_rawat = rp.no_rawat
        WHERE rp.tgl_registrasi BETWEEN :tgl_awal AND :tgl_akhir
    ";
    $stmt_tot = $koneksi_pdo->prepare($sql_total);
    $stmt_tot->execute($params);
    $total_rujukan = (int)$stmt_tot->fetchColumn();

    // Jumlah Faskes Perujuk Unik
    $sql_faskes_unik = "
        SELECT COUNT(DISTINCT rm.perujuk) 
        FROM rujuk_masuk rm
        INNER JOIN reg_periksa rp ON rm.no_rawat = rp.no_rawat
        WHERE rp.tgl_registrasi BETWEEN :tgl_awal AND :tgl_akhir
          AND rm.perujuk != '-' AND rm.perujuk != ''
    ";
    $stmt_fu = $koneksi_pdo->prepare($sql_faskes_unik);
    $stmt_fu->execute($params);
    $total_faskes_unik = (int)$stmt_fu->fetchColumn();

    // Kategori Rujukan Terbanyak
    $sql_kat_top = "
        SELECT rm.kategori_rujuk, COUNT(*) as jml
        FROM rujuk_masuk rm
        INNER JOIN reg_periksa rp ON rm.no_rawat = rp.no_rawat
        WHERE rp.tgl_registrasi BETWEEN :tgl_awal AND :tgl_akhir
          AND rm.kategori_rujuk != '-'
        GROUP BY rm.kategori_rujuk
        ORDER BY jml DESC
        LIMIT 1
    ";
    $stmt_kt = $koneksi_pdo->prepare($sql_kat_top);
    $stmt_kt->execute($params);
    $res_kt = $stmt_kt->fetch();
    $kategori_top = $res_kt ? $res_kt['kategori_rujuk'] . " (" . $res_kt['jml'] . ")" : "-";

    $summary = [
        'total_rujukan' => $total_rujukan,
        'total_faskes_unik' => $total_faskes_unik,
        'kategori_top' => $kategori_top
    ];

    // 2. Kueri Top 10 Faskes Perujuk (Bar Chart)
    $sql_chart = "
        SELECT rm.perujuk, COUNT(*) AS jumlah
        FROM rujuk_masuk rm
        INNER JOIN reg_periksa rp ON rm.no_rawat = rp.no_rawat
        WHERE rp.tgl_registrasi BETWEEN :tgl_awal AND :tgl_akhir
          AND rm.perujuk != '-' AND rm.perujuk != ''
        GROUP BY rm.perujuk
        ORDER BY jumlah DESC
        LIMIT 10
    ";
    $stmt_chart = $koneksi_pdo->prepare($sql_chart);
    $stmt_chart->execute($params);
    
    $chart_labels = [];
    $chart_values = [];
    while ($row = $stmt_chart->fetch(PDO::FETCH_ASSOC)) {
        $chart_labels[] = $row['perujuk'];
        $chart_values[] = (int)$row['jumlah'];
    }

    // 3. Kueri Kategori Rujukan (Donut Chart)
    $sql_cat = "
        SELECT rm.kategori_rujuk, COUNT(*) AS jumlah
        FROM rujuk_masuk rm
        INNER JOIN reg_periksa rp ON rm.no_rawat = rp.no_rawat
        WHERE rp.tgl_registrasi BETWEEN :tgl_awal AND :tgl_akhir
        GROUP BY rm.kategori_rujuk
        ORDER BY jumlah DESC
    ";
    $stmt_cat = $koneksi_pdo->prepare($sql_cat);
    $stmt_cat->execute($params);
    
    $cat_labels = [];
    $cat_values = [];
    while ($row = $stmt_cat->fetch(PDO::FETCH_ASSOC)) {
        $label = $row['kategori_rujuk'] == '-' ? 'Lain-lain' : $row['kategori_rujuk'];
        $cat_labels[] = $label;
        $cat_values[] = (int)$row['jumlah'];
    }

    // 4. Kueri Rincian Pasien Detail (DataTables)
    $sql_detail = "
        SELECT 
            rp.tgl_registrasi,
            rm.no_rawat,
            p.nm_pasien,
            pj.png_jawab,
            rm.perujuk,
            rm.dokter_perujuk,
            rm.kategori_rujuk,
            rm.no_rujuk,
            COALESCE(peny.nm_penyakit, '-') AS nm_penyakit
        FROM rujuk_masuk rm
        INNER JOIN reg_periksa rp ON rm.no_rawat = rp.no_rawat
        INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        INNER JOIN penjab pj ON rp.kd_pj = pj.kd_pj
        LEFT JOIN penyakit peny ON rm.kd_penyakit = peny.kd_penyakit
        WHERE rp.tgl_registrasi BETWEEN :tgl_awal AND :tgl_akhir
        ORDER BY rp.tgl_registrasi DESC
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
        'categories' => [
            'labels' => $cat_labels,
            'values' => $cat_values
        ],
        'data' => $data_detail
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal memuat data rujukan: ' . $e->getMessage()
    ]);
}
?>
