<?php
/*
 * File: api/data_rl_3_15.php
 * Fungsi: API Laporan RL 3.15 Rekapitulasi Kegiatan Pelayanan Kesehatan Jiwa
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

$tgl_awal   = $_GET['tgl_awal'] ?? date('Y-01-01');
$tgl_akhir  = $_GET['tgl_akhir'] ?? date('Y-12-31');

// 8 Jenis Kegiatan Juknis RL 3.15
$kegiatan_list = [
    'Pemeriksaan Psikiatri', 'Penatalaksanaan Medikamentosa', 'Psikoterapi',
    'Konseling', 'Elektro Medik', 'Terapi Perilaku', 'Rehabilitasi Medik Psikiatrik', 'Assessment'
];

$result_data = [];
foreach ($kegiatan_list as $kg) {
    $result_data[$kg] = ['jenis_kegiatan' => $kg, 'laki' => 0, 'perempuan' => 0, 'jumlah' => 0];
}

$sql = "
    SELECT nm_perawatan, jk, COUNT(*) as qty
    FROM (
        SELECT jp.nm_perawatan, p.jk FROM rawat_jl_dr rj 
        INNER JOIN jns_perawatan jp ON rj.kd_jenis_prw = jp.kd_jenis_prw 
        INNER JOIN reg_periksa rp ON rj.no_rawat = rp.no_rawat
        INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        WHERE rj.tgl_perawatan BETWEEN ? AND ?
        UNION ALL
        SELECT jp.nm_perawatan, p.jk FROM rawat_jl_pr rj 
        INNER JOIN jns_perawatan jp ON rj.kd_jenis_prw = jp.kd_jenis_prw 
        INNER JOIN reg_periksa rp ON rj.no_rawat = rp.no_rawat
        INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        WHERE rj.tgl_perawatan BETWEEN ? AND ?
        UNION ALL
        SELECT jp.nm_perawatan, p.jk FROM rawat_jl_drpr rj 
        INNER JOIN jns_perawatan jp ON rj.kd_jenis_prw = jp.kd_jenis_prw 
        INNER JOIN reg_periksa rp ON rj.no_rawat = rp.no_rawat
        INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        WHERE rj.tgl_perawatan BETWEEN ? AND ?
        UNION ALL
        SELECT jp.nm_perawatan, p.jk FROM rawat_inap_dr ri 
        INNER JOIN jns_perawatan_inap jp ON ri.kd_jenis_prw = jp.kd_jenis_prw 
        INNER JOIN reg_periksa rp ON ri.no_rawat = rp.no_rawat
        INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        WHERE ri.tgl_perawatan BETWEEN ? AND ?
        UNION ALL
        SELECT jp.nm_perawatan, p.jk FROM rawat_inap_pr ri 
        INNER JOIN jns_perawatan_inap jp ON ri.kd_jenis_prw = jp.kd_jenis_prw 
        INNER JOIN reg_periksa rp ON ri.no_rawat = rp.no_rawat
        INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        WHERE ri.tgl_perawatan BETWEEN ? AND ?
        UNION ALL
        SELECT jp.nm_perawatan, p.jk FROM rawat_inap_drpr ri 
        INNER JOIN jns_perawatan_inap jp ON ri.kd_jenis_prw = jp.kd_jenis_prw 
        INNER JOIN reg_periksa rp ON ri.no_rawat = rp.no_rawat
        INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        WHERE ri.tgl_perawatan BETWEEN ? AND ?
    ) as gabungan
    GROUP BY nm_perawatan, jk
";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "ssssssssssss", $tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($res)) {
    $n = strtoupper($row['nm_perawatan']);
    $jk = ($row['jk'] == 'L') ? 'laki' : 'perempuan';
    $q = $row['qty'];

    $target = '';
    if (strpos($n, 'PSIKIATRI') !== false || strpos($n, 'JIWA') !== false) $target = 'Pemeriksaan Psikiatri';
    else if (strpos($n, 'MEDIKAMENTOSA') !== false) $target = 'Penatalaksanaan Medikamentosa';
    else if (strpos($n, 'PSIKOTERAPI') !== false) $target = 'Psikoterapi';
    else if (strpos($n, 'KONSELING') !== false) $target = 'Konseling';
    else if (strpos($n, 'ELEKTRO MEDIK') !== false || strpos($n, 'ECT') !== false) $target = 'Elektro Medik';
    else if (strpos($n, 'PERILAKU') !== false) $target = 'Terapi Perilaku';
    else if (strpos($n, 'REHABILITASI PSIKIATRIK') !== false) $target = 'Rehabilitasi Medik Psikiatrik';
    else if (strpos($n, 'ASSESSMENT') !== false || strpos($n, 'ASESMEN') !== false) $target = 'Assessment';

    if ($target != '') {
        $result_data[$target][$jk] += $q;
        $result_data[$target]['jumlah'] += $q;
    }
}

echo json_encode(['data' => array_values($result_data)]);
mysqli_close($koneksi);
?>
