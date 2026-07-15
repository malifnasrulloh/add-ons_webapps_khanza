<?php
/*
 * File: api/data_rl_4_2.php
 * Fungsi: API Laporan RL 4.2 10 Besar Penyakit Rawat Inap (REVISED SQL)
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

// SQL: Aggregate Top 10 Primary Diagnosis for Inpatient
// Fixed: Using LIKE instead of strpos in SQL
$sql = "
    SELECT 
        LEFT(dp.kd_penyakit, 3) as kelompok_icd,
        py.nm_penyakit,
        SUM(IF(p.jk = 'L', 1, 0)) as hidup_mati_l,
        SUM(IF(p.jk = 'P', 1, 0)) as hidup_mati_p,
        COUNT(*) as hidup_mati_total,
        SUM(IF((ki.stts_pulang LIKE '%Meninggal%' OR pm.no_rkm_medis IS NOT NULL) AND p.jk = 'L', 1, 0)) as mati_l,
        SUM(IF((ki.stts_pulang LIKE '%Meninggal%' OR pm.no_rkm_medis IS NOT NULL) AND p.jk = 'P', 1, 0)) as mati_p
    FROM diagnosa_pasien dp
    INNER JOIN reg_periksa rp ON dp.no_rawat = rp.no_rawat
    INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    INNER JOIN penyakit py ON dp.kd_penyakit = py.kd_penyakit
    INNER JOIN kamar_inap ki ON rp.no_rawat = ki.no_rawat
    LEFT JOIN pasien_mati pm ON p.no_rkm_medis = pm.no_rkm_medis AND pm.tanggal BETWEEN ki.tgl_masuk AND ki.tgl_keluar
    WHERE dp.prioritas = '1'
    AND rp.status_lanjut = 'Ranap'
    AND ki.tgl_keluar BETWEEN ? AND ?
    AND ki.stts_pulang NOT IN ('-', 'Pindah Kamar')
    AND LEFT(dp.kd_penyakit, 3) NOT IN ('O80', 'O82')
    AND LEFT(dp.kd_penyakit, 1) NOT IN ('R', 'V', 'W', 'X', 'Y', 'Z')
    GROUP BY kelompok_icd
    ORDER BY hidup_mati_total DESC
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
        'hidup_mati_l' => (int)$row['hidup_mati_l'],
        'hidup_mati_p' => (int)$row['hidup_mati_p'],
        'hidup_mati_total' => (int)$row['hidup_mati_total'],
        'mati_l' => (int)$row['mati_l'],
        'mati_p' => (int)$row['mati_p'],
        'mati_total' => (int)($row['mati_l'] + $row['mati_p'])
    ];
}

echo json_encode(['data' => $output]);
mysqli_close($koneksi);
?>
