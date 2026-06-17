<?php
/*
 * File: api/data_rl_3_13.php
 * Fungsi: API Laporan RL 3.13 Rekapitulasi Kegiatan Pelayanan Rehabilitasi Medik
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

// 7 Kategori Utama Juknis RL 3.13
$kategori_list = [
    'Medis', 'Fisioterapi', 'Okupasiterapi', 'Terapi Wicara', 
    'Psikologi', 'Sosial Medik', 'Ortotik Prostetik'
];

$result_data = [];
foreach ($kategori_list as $kg) {
    $result_data[$kg] = ['jenis_tindakan' => $kg, 'jumlah' => 0];
}

$sql = "
    SELECT nm_perawatan, COUNT(*) as qty
    FROM (
        SELECT jp.nm_perawatan FROM rawat_jl_dr rj INNER JOIN jns_perawatan jp ON rj.kd_jenis_prw = jp.kd_jenis_prw WHERE rj.tgl_perawatan BETWEEN ? AND ?
        UNION ALL
        SELECT jp.nm_perawatan FROM rawat_jl_pr rj INNER JOIN jns_perawatan jp ON rj.kd_jenis_prw = jp.kd_jenis_prw WHERE rj.tgl_perawatan BETWEEN ? AND ?
        UNION ALL
        SELECT jp.nm_perawatan FROM rawat_jl_drpr rj INNER JOIN jns_perawatan jp ON rj.kd_jenis_prw = jp.kd_jenis_prw WHERE rj.tgl_perawatan BETWEEN ? AND ?
        UNION ALL
        SELECT jp.nm_perawatan FROM rawat_inap_dr ri INNER JOIN jns_perawatan_inap jp ON ri.kd_jenis_prw = jp.kd_jenis_prw WHERE ri.tgl_perawatan BETWEEN ? AND ?
        UNION ALL
        SELECT jp.nm_perawatan FROM rawat_inap_pr ri INNER JOIN jns_perawatan_inap jp ON ri.kd_jenis_prw = jp.kd_jenis_prw WHERE ri.tgl_perawatan BETWEEN ? AND ?
        UNION ALL
        SELECT jp.nm_perawatan FROM rawat_inap_drpr ri INNER JOIN jns_perawatan_inap jp ON ri.kd_jenis_prw = jp.kd_jenis_prw WHERE ri.tgl_perawatan BETWEEN ? AND ?
    ) as gabungan
    GROUP BY nm_perawatan
";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "ssssssssssss", $tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($res)) {
    $n = strtoupper($row['nm_perawatan']);
    $q = $row['qty'];

    if (strpos($n, 'GAIT') !== false || strpos($n, 'EMG') !== false || strpos($n, 'URO DINAMIC') !== false || strpos($n, 'SPIROMETER') !== false || strpos($n, 'BICYCLE') !== false || strpos($n, 'TREAD MILL') !== false) {
        $result_data['Medis']['jumlah'] += $q;
    }
    else if (strpos($n, 'FISIOTERAPI') !== false || strpos($n, 'DIATHERMY') !== false || strpos($n, 'IRR') !== false || strpos($n, 'MWD') !== false || strpos($n, 'TEN') !== false || strpos($n, 'TRAKSI') !== false || strpos($n, 'EXERCISE') !== false) {
        $result_data['Fisioterapi']['jumlah'] += $q;
    }
    else if (strpos($n, 'OKUPASI') !== false || strpos($n, 'SNOOSLEN') !== false || strpos($n, 'ADL') !== false) {
        $result_data['Okupasiterapi']['jumlah'] += $q;
    }
    else if (strpos($n, 'WICARA') !== false || strpos($n, 'MENELAN') !== false) {
        $result_data['Terapi Wicara']['jumlah'] += $q;
    }
    else if (strpos($n, 'PSIKOLOG') !== false) {
        $result_data['Psikologi']['jumlah'] += $q;
    }
    else if (strpos($n, 'SOSIAL MEDIK') !== false) {
        $result_data['Sosial Medik']['jumlah'] += $q;
    }
    else if (strpos($n, 'ORTOTIK') !== false || strpos($n, 'PROSTETIK') !== false || strpos($n, 'ALAT BANTU') !== false || strpos($n, 'KORSET') !== false || strpos($n, 'KAFO') !== false || strpos($n, 'AFO') !== false) {
        $result_data['Ortotik Prostetik']['jumlah'] += $q;
    }
}

echo json_encode(['data' => array_values($result_data)]);
mysqli_close($koneksi);
?>
