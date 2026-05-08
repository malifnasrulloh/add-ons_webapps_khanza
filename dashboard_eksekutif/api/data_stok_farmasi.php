<?php
/*
 * File: api/data_stok_farmasi.php
 * Fungsi: 
 * 1. Cek konfigurasi harga (Dasar/Beli).
 * 2. Agregasi stok dari gudangbarang.
 * 3. Menghitung valuasi aset.
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once(dirname(__DIR__) . '/config/koneksi.php'); 

if (!isset($_SESSION['user_id'])) {
    http_response_code(403); echo json_encode(['error' => 'Akses ditolak.']); exit;
}

// 1. Ambil Parameter Filter
$kd_bangsal = isset($_GET['kd_bangsal']) ? $_GET['kd_bangsal'] : '';
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

$summary = [
    'total_aset' => 0,
    'total_item' => 0,
    'stok_kritis' => 0
];
$data = [];

try {
    // 2. Cek Konfigurasi Harga (Sesuai DlgSisaStok.java)
    $harga_field = "dasar"; // Default
    $sql_conf = "SELECT hargadasar FROM set_harga_obat LIMIT 1";
    $stmt_conf = $koneksi_pdo->query($sql_conf);
    if ($row_conf = $stmt_conf->fetch(PDO::FETCH_ASSOC)) {
        if ($row_conf['hargadasar'] == 'Harga Beli') {
            $harga_field = "h_beli";
        } else {
            $harga_field = "dasar";
        }
    }

    // 3. Bangun Query Utama
    // Kita gunakan GROUP_CONCAT untuk melihat obat ini ada di gudang mana saja dalam satu baris
    $where = " WHERE databarang.status = '1' "; // Hanya barang aktif
    $params = [];

    if (!empty($kd_bangsal)) {
        $where .= " AND gudangbarang.kd_bangsal = :kd_bangsal ";
        $params[':kd_bangsal'] = $kd_bangsal;
    }

    if (!empty($keyword)) {
        $where .= " AND (databarang.kode_brng LIKE :keyword1 OR databarang.nama_brng LIKE :keyword2) ";
        $keyword_param = "%" . $keyword . "%";
        $params[':keyword1'] = $keyword_param;
        $params[':keyword2'] = $keyword_param;
    }

    $sql = "
        SELECT 
            databarang.kode_brng,
            databarang.nama_brng,
            kodesatuan.satuan,
            databarang.$harga_field as harga_dasar,
            SUM(gudangbarang.stok) as total_stok,
            (SUM(gudangbarang.stok) * databarang.$harga_field) as total_aset,
            GROUP_CONCAT(DISTINCT bangsal.nm_bangsal SEPARATOR ', ') as lokasi_stok
        FROM databarang
        INNER JOIN gudangbarang ON databarang.kode_brng = gudangbarang.kode_brng
        INNER JOIN kodesatuan ON databarang.kode_sat = kodesatuan.kode_sat
        INNER JOIN bangsal ON gudangbarang.kd_bangsal = bangsal.kd_bangsal
        $where
        GROUP BY databarang.kode_brng
        HAVING total_stok != 0 -- Opsional: Sembunyikan stok 0 agar tabel bersih
        ORDER BY total_stok DESC
        LIMIT 500 -- Batasi agar browser HP tidak crash
    ";

    $stmt = $koneksi_pdo->prepare($sql);
    $stmt->execute($params);

    $total_aset_global = 0;
    $total_item = 0;
    $stok_kritis = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
        $total_aset_global += (float)$row['total_aset'];
        $total_item++;
        if ((float)$row['total_stok'] < 10) { // Asumsi stok kritis < 10
            $stok_kritis++;
        }
    }
    
    $summary = [
        'total_aset' => $total_aset_global,
        'total_item' => $total_item,
        'stok_kritis' => $stok_kritis
    ];

} catch (PDOException $e) {
    // Return empty valid JSON
}

echo json_encode([
    'summary' => $summary,
    'data' => $data
]);
?>