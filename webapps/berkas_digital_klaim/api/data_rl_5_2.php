<?php
/*
 * File: api/data_rl_5_2.php
 * Fungsi: API Laporan RL 5.2 10 Besar Kasus Baru Penyakit Rawat Jalan
 */

error_reporting(0);
ini_set('display_errors', 0);

require_once(__DIR__ . '/../../conf/conf.php');
header('Content-Type: application/json');
$koneksi = bukakoneksi();

session_start();
if (!isset($_SESSION['casemix_login'])) {
    http_response_code(403); echo json_encode(['error' => 'Akses ditolak.']); exit;
}

$tgl_awal   = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir  = $_GET['tgl_akhir'] ?? date('Y-m-d');

// SQL: Aggregate Top 10 New Cases for Outpatient
$sql = "
    SELECT 
        LEFT(dp.kd_penyakit, 3) as kelompok_icd,
        py.nm_penyakit,
        SUM(IF(p.jk = 'L', 1, 0)) as baru_l,
        SUM(IF(p.jk = 'P', 1, 0)) as baru_p,
        COUNT(*) as baru_total
    FROM diagnosa_pasien dp
    INNER JOIN reg_periksa rp ON dp.no_rawat = rp.no_rawat
    INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    INNER JOIN penyakit py ON dp.kd_penyakit = py.kd_penyakit
    WHERE dp.prioritas = '1'
    AND rp.status_lanjut = 'Ralan'
    AND rp.tgl_registrasi BETWEEN ? AND ?
    AND (SELECT COUNT(*) FROM diagnosa_pasien dp2 
         INNER JOIN reg_periksa rp2 ON dp2.no_rawat = rp2.no_rawat
         WHERE rp2.no_rkm_medis = rp.no_rkm_medis 
         AND dp2.kd_penyakit = dp.kd_penyakit 
         AND rp2.tgl_registrasi < rp.tgl_registrasi) = 0
    AND LEFT(dp.kd_penyakit, 3) NOT IN ('O80', 'O82')
    AND LEFT(dp.kd_penyakit, 1) NOT IN ('R', 'V', 'W', 'X', 'Y', 'Z')
    GROUP BY kelompok_icd
    ORDER BY baru_total DESC
    LIMIT 10
";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "ss", $tgl_awal, $tgl_akhir);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$output = [];
while ($row = mysqli_fetch_assoc($res)) {
    $output[] = [
        'kode_kelompok' => $row['kelompok_icd'],
        'diagnosis' => $row['nm_penyakit'],
        'baru_l' => (int)$row['baru_l'],
        'baru_p' => (int)$row['baru_p'],
        'baru_total' => (int)$row['baru_total']
    ];
}

echo json_encode(['data' => $output]);
mysqli_close($koneksi);
?>
