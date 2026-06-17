<?php
/*
 * File: api/data_rl_3_11.php
 * Fungsi: API Laporan RL 3.11 Rekapitulasi Kegiatan Pelayanan Gigi dan Mulut
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

// 16 Jenis Kegiatan Juknis RL 3.11
$kegiatan_list = [
    'Tumpatan Gigi Tetap', 'Tumpatan Gigi Sulung', 'Pengobatan Pulpa',
    'Pencabutan Gigi Tetap', 'Pencabutan Gigi Sulung', 'Pengobatan Periodontal',
    'Pengobatan Abses', 'Pembersihan Karang Gigi', 'Prothese Lengkap',
    'Prothese Sebagian', 'Prothese Cekat', 'Orthodonti', 'Jacket/Bridge',
    'Bedah Mulut', 'Implan Gigi', 'Penyakit Mulut'
];

$result_data = [];
foreach ($kegiatan_list as $kg) {
    $result_data[$kg] = ['jenis_kegiatan' => $kg, 'jumlah' => 0];
}

// Logic: Aggregating from rawat_jl_dr, rawat_jl_pr, rawat_jl_drpr
// Since many SIMRS Khanza use different names, we use flexible keyword matching.

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

    if (strpos($n, 'TUMPAT') !== false) {
        if (strpos($n, 'SULUNG') !== false || strpos($n, 'DECIDUI') !== false) $result_data['Tumpatan Gigi Sulung']['jumlah'] += $q;
        else $result_data['Tumpatan Gigi Tetap']['jumlah'] += $q;
    }
    else if (strpos($n, 'PULPA') !== false || strpos($n, 'PULPOTOMI') !== false || strpos($n, 'PULPEKTOMI') !== false) {
        $result_data['Pengobatan Pulpa']['jumlah'] += $q;
    }
    else if (strpos($n, 'CABUT') !== false || strpos($n, 'EXTRAKSI') !== false || strpos($n, 'EKSTRAKSI') !== false) {
        if (strpos($n, 'SULUNG') !== false || strpos($n, 'DECIDUI') !== false) $result_data['Pencabutan Gigi Sulung']['jumlah'] += $q;
        else $result_data['Pencabutan Gigi Tetap']['jumlah'] += $q;
    }
    else if (strpos($n, 'SCALING') !== false || strpos($n, 'KARANG') !== false) {
        $result_data['Pembersihan Karang Gigi']['jumlah'] += $q;
    }
    else if (strpos($n, 'ABSES') !== false || strpos($n, 'INCISI') !== false) {
        $result_data['Pengobatan Abses']['jumlah'] += $q;
    }
    else if (strpos($n, 'PERIODONTAL') !== false || strpos($n, 'GINGIVA') !== false) {
        $result_data['Pengobatan Periodontal']['jumlah'] += $q;
    }
    else if (strpos($n, 'ORTHO') !== false) {
        $result_data['Orthodonti']['jumlah'] += $q;
    }
    else if (strpos($n, 'PROTHESE') !== false || strpos($n, 'GIGI TIRUAN') !== false) {
        if (strpos($n, 'LENGKAP') !== false) $result_data['Prothese Lengkap']['jumlah'] += $q;
        else if (strpos($n, 'CEKAT') !== false) $result_data['Prothese Cekat']['jumlah'] += $q;
        else $result_data['Prothese Sebagian']['jumlah'] += $q;
    }
    else if (strpos($n, 'JACKET') !== false || strpos($n, 'BRIDGE') !== false || strpos($n, 'CROWN') !== false) {
        $result_data['Jacket/Bridge']['jumlah'] += $q;
    }
    else if (strpos($n, 'BEDAH MULUT') !== false || strpos($n, 'ODONTEKTOMI') !== false) {
        $result_data['Bedah Mulut']['jumlah'] += $q;
    }
    else if (strpos($n, 'IMPLAN') !== false) {
        $result_data['Implan Gigi']['jumlah'] += $q;
    }
    else if (strpos($n, 'STOMATITIS') !== false || strpos($n, 'PENYAKIT MULUT') !== false) {
        $result_data['Penyakit Mulut']['jumlah'] += $q;
    }
}

echo json_encode(['data' => array_values($result_data)]);
mysqli_close($koneksi);
?>
