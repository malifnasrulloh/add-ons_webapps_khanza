<?php
/*
 * File: api/data_rl_3_4.php
 * Fungsi: Laporan RL 3.4 Rekapitulasi Pengunjung
 */

error_reporting(0);
ini_set('display_errors', 0);

if(file_exists(__DIR__ . '/../../conf/conf.php')) {
    require_once(__DIR__ . '/../../conf/conf.php');
} else {
    require_once(__DIR__ . '/../conf/conf.php');
}

header('Content-Type: application/json');
$koneksi = bukakoneksi();

session_start();
if (!isset($_SESSION['casemix_login'])) {
    http_response_code(403); echo json_encode(['error' => 'Akses ditolak.']); exit;
}

$tgl_awal   = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir  = $_GET['tgl_akhir'] ?? date('Y-m-d');

// 1. Hitung Pengunjung Baru
// Pasien yang tercatat mendaftar sbg BARU pada periode tersebut
// Karena 1 orang hanya 1x jadi pengunjung (baik dtg 1x atau 3x di bulan tsb),
// Maka kita hitung Count Distinct RM nya
$baru = 0;
$sql_baru = "SELECT COUNT(DISTINCT no_rkm_medis) as jml_baru 
             FROM reg_periksa 
             WHERE tgl_registrasi BETWEEN ? AND ? 
             AND stts_daftar = 'Baru' AND stts != 'Batal'";

$stmt_baru = mysqli_prepare($koneksi, $sql_baru);
if ($stmt_baru) {
    mysqli_stmt_bind_param($stmt_baru, "ss", $tgl_awal, $tgl_akhir);
    mysqli_stmt_execute($stmt_baru);
    $res_baru = mysqli_stmt_get_result($stmt_baru);
    $row_baru = mysqli_fetch_assoc($res_baru);
    $baru = (int)($row_baru['jml_baru'] ?? 0);
    mysqli_stmt_close($stmt_baru);
}

// 2. Hitung Pengunjung Lama
// Pasien yang mendaftar sbg LAMA, TETAPI belum pernah mendaftar sbg BARU di periode yang sama
$lama = 0;
$sql_lama = "SELECT COUNT(DISTINCT rp_lama.no_rkm_medis) as jml_lama 
             FROM reg_periksa rp_lama
             WHERE rp_lama.tgl_registrasi BETWEEN ? AND ? 
             AND rp_lama.stts_daftar = 'Lama' 
             AND rp_lama.stts != 'Batal'
             AND rp_lama.no_rkm_medis NOT IN (
                 SELECT DISTINCT no_rkm_medis 
                 FROM reg_periksa 
                 WHERE tgl_registrasi BETWEEN ? AND ? 
                 AND stts_daftar = 'Baru' AND stts != 'Batal'
             )";

$stmt_lama = mysqli_prepare($koneksi, $sql_lama);
if ($stmt_lama) {
    mysqli_stmt_bind_param($stmt_lama, "ssss", $tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir);
    mysqli_stmt_execute($stmt_lama);
    $res_lama = mysqli_stmt_get_result($stmt_lama);
    $row_lama = mysqli_fetch_assoc($res_lama);
    $lama = (int)($row_lama['jml_lama'] ?? 0);
    mysqli_stmt_close($stmt_lama);
}

// 3. Format Response
$result_data = [];

$result_data[] = [
    'no' => 1,
    'jenis_pengunjung' => 'Pengunjung Baru',
    'jumlah' => $baru
];

$result_data[] = [
    'no' => 2,
    'jenis_pengunjung' => 'Pengunjung Lama',
    'jumlah' => $lama
];

$result_data[] = [
    'no' => 99,
    'jenis_pengunjung' => 'TOTAL',
    'jumlah' => $baru + $lama
];

echo json_encode(['data' => $result_data]);
?>
