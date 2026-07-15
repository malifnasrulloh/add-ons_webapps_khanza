<?php
/*
 * File: api/data_kinerja_dokter.php
 * Fungsi: Menghitung volume pasien per dokter (Ralan + Ranap).
 * Output: JSON untuk Chart dan Tabel.
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once(dirname(__DIR__) . '/config/koneksi.php'); 

if (!isset($_SESSION['user_id'])) {
    http_response_code(403); echo json_encode(['error' => 'Akses ditolak.']); exit;
}

// 1. Ambil Parameter
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$kd_pj = isset($_GET['kd_pj']) ? $_GET['kd_pj'] : '';

// Array penampung data dokter
// Format: [ 'KD001' => ['nama' => 'Dr. A', 'ralan' => 10, 'ranap' => 5, 'billing' => 1000000] ]
$master_data = [];

// -----------------------------------------------------------
// A. AMBIL DATA RALAN (Dari reg_periksa)
// -----------------------------------------------------------
$sql_ralan = "
    SELECT 
        reg_periksa.kd_dokter, 
        dokter.nm_dokter,
        COUNT(reg_periksa.no_rawat) as jumlah,
        SUM((SELECT SUM(
            CASE 
                WHEN billing.status = 'TtlRetur Obat' THEN (billing.totalbiaya * -1)
                WHEN billing.status = 'TtlPotongan' THEN (billing.totalbiaya * -1)
                ELSE billing.totalbiaya 
            END
        ) FROM billing WHERE billing.no_rawat = reg_periksa.no_rawat)) AS total_billing
    FROM reg_periksa
    INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
    WHERE 
        reg_periksa.tgl_registrasi BETWEEN ? AND ?
        AND reg_periksa.stts != 'Batal'
        AND reg_periksa.status_lanjut = 'Ralan'
";

if ($kd_pj != '') {
    $sql_ralan .= " AND reg_periksa.kd_pj = ? ";
}

$sql_ralan .= " GROUP BY reg_periksa.kd_dokter ";

$stmt = $koneksi->prepare($sql_ralan);
if ($kd_pj != '') {
    $stmt->bind_param("sss", $tgl_awal, $tgl_akhir, $kd_pj);
} else {
    $stmt->bind_param("ss", $tgl_awal, $tgl_akhir);
}
$stmt->execute();
$res_ralan = $stmt->get_result();

while($row = $res_ralan->fetch_assoc()) {
    $kd = $row['kd_dokter'];
    if(!isset($master_data[$kd])) {
        $master_data[$kd] = [
            'nm_dokter' => $row['nm_dokter'],
            'ralan' => 0,
            'ranap' => 0,
            'billing' => 0,
            'total' => 0
        ];
    }
    $master_data[$kd]['ralan'] = (int)$row['jumlah'];
    $master_data[$kd]['billing'] += (float)$row['total_billing'];
}
$stmt->close();

// -----------------------------------------------------------
// B. AMBIL DATA RANAP (Dari dpjp_ranap)
// Kita join ke reg_periksa untuk filter tanggal registrasi/masuk
// -----------------------------------------------------------
$sql_ranap = "
    SELECT 
        dpjp_ranap.kd_dokter, 
        dokter.nm_dokter,
        COUNT(dpjp_ranap.no_rawat) as jumlah,
        SUM((SELECT SUM(
            CASE 
                WHEN billing.status = 'TtlRetur Obat' THEN (billing.totalbiaya * -1)
                WHEN billing.status = 'TtlPotongan' THEN (billing.totalbiaya * -1)
                ELSE billing.totalbiaya 
            END
        ) FROM billing WHERE billing.no_rawat = reg_periksa.no_rawat)) AS total_billing
    FROM dpjp_ranap
    INNER JOIN dokter ON dpjp_ranap.kd_dokter = dokter.kd_dokter
    INNER JOIN reg_periksa ON dpjp_ranap.no_rawat = reg_periksa.no_rawat
    WHERE 
        reg_periksa.tgl_registrasi BETWEEN ? AND ?
        AND reg_periksa.stts != 'Batal'
";

if ($kd_pj != '') {
    $sql_ranap .= " AND reg_periksa.kd_pj = ? ";
}

$sql_ranap .= " GROUP BY dpjp_ranap.kd_dokter ";

$stmt = $koneksi->prepare($sql_ranap);
if ($kd_pj != '') {
    $stmt->bind_param("sss", $tgl_awal, $tgl_akhir, $kd_pj);
} else {
    $stmt->bind_param("ss", $tgl_awal, $tgl_akhir);
}
$stmt->execute();
$res_ranap = $stmt->get_result();

while($row = $res_ranap->fetch_assoc()) {
    $kd = $row['kd_dokter'];
    // Jika dokter belum ada (misal: Dokter Spesialis yang jarang praktek poli tapi merawat inap)
    if(!isset($master_data[$kd])) {
        $master_data[$kd] = [
            'nm_dokter' => $row['nm_dokter'],
            'ralan' => 0,
            'ranap' => 0,
            'billing' => 0,
            'total' => 0
        ];
    }
    $master_data[$kd]['ranap'] = (int)$row['jumlah'];
    $master_data[$kd]['billing'] += (float)$row['total_billing'];
}
$stmt->close();

// -----------------------------------------------------------
// C. FORMAT DATA FINAL & SORTING
// -----------------------------------------------------------
$final_data = [];
$chart_labels = [];
$chart_ralan = [];
$chart_ranap = [];

foreach($master_data as $kd => $data) {
    $data['kd_dokter'] = $kd; // Tambahkan ini agar bisa dibaca JS
    $data['total'] = $data['ralan'] + $data['ranap'];
    $final_data[] = $data;
}

// Sort berdasarkan Total Terbanyak (Descending)
usort($final_data, function($a, $b) {
    return $b['total'] - $a['total'];
});

// Ambil Top 15 untuk Chart agar tidak penuh sesak
$top_15 = array_slice($final_data, 0, 15);
foreach($top_15 as $row) {
    $chart_labels[] = $row['nm_dokter'];
    $chart_ralan[] = $row['ralan'];
    $chart_ranap[] = $row['ranap'];
}

echo json_encode([
    'table' => $final_data,
    'chart' => [
        'labels' => $chart_labels,
        'ralan' => $chart_ralan,
        'ranap' => $chart_ranap
    ]
]);

$koneksi->close();
?>