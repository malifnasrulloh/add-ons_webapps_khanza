<?php
/*
 * File: api/data_rl_3_14.php
 * Fungsi: API Laporan RL 3.14 Rekapitulasi Kegiatan Pelayanan Khusus
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

// 18 Jenis Kegiatan Juknis RL 3.14
$kegiatan_list = [
    'Elektro Kardiographi (EKG)', 'Elektro Myographi (EMG)', 'Echo Cardiographi (ECG)',
    'Endoskopi (semua bentuk)', 'Hemodialisa', 'Densometri Tulang', 'Pungsi',
    'Spirometri', 'Tes Kulit/Alergi/Histamin', 'Topometri', 'Akupunktur Medik',
    'Akupunktur Tradisional', 'Akupressur', 'Herbal/Jamu', 'Pijat Baduta',
    'Kunjungan Rumah (Homecare)', 'Tidak lanjut lesi pra Kanker Leher Rahim', 'Lain-Lain'
];

$result_data = [];
foreach ($kegiatan_list as $kg) {
    $result_data[$kg] = ['jenis_kegiatan' => $kg, 'jumlah' => 0];
}

// 1. Data Hemodialisa (Dari tabel khusus)
$sql_hd = "SELECT COUNT(*) as qty FROM hemodialisa WHERE tgl_hd BETWEEN ? AND ?";
$stmt_hd = mysqli_prepare($koneksi, $sql_hd);
mysqli_stmt_bind_param($stmt_hd, "ss", $tgl_awal, $tgl_akhir);
mysqli_stmt_execute($stmt_hd);
$res_hd = mysqli_stmt_get_result($stmt_hd);
if($row_hd = mysqli_fetch_assoc($res_hd)) {
    $result_data['Hemodialisa']['jumlah'] = $row_hd['qty'];
}

// 2. Data Kegiatan Lainnya (Pencocokan Keyword di jns_perawatan)
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

    if (strpos($n, 'EKG') !== false) $result_data['Elektro Kardiographi (EKG)']['jumlah'] += $q;
    else if (strpos($n, 'EMG') !== false) $result_data['Elekt Myographi (EMG)']['jumlah'] += $q;
    else if (strpos($n, 'ECHO') !== false) $result_data['Echo Cardiographi (ECG)']['jumlah'] += $q;
    else if (strpos($n, 'ENDOSKOPI') !== false) $result_data['Endoskopi (semua bentuk)']['jumlah'] += $q;
    else if (strpos($n, 'DENSOMETRI') !== false) $result_data['Densometri Tulang']['jumlah'] += $q;
    else if (strpos($n, 'PUNGSI') !== false) $result_data['Pungsi']['jumlah'] += $q;
    else if (strpos($n, 'SPIROMETRI') !== false) $result_data['Spirometri']['jumlah'] += $q;
    else if (strpos($n, 'TES KULIT') !== false || strpos($n, 'ALERGI') !== false) $result_data['Tes Kulit/Alergi/Histamin']['jumlah'] += $q;
    else if (strpos($n, 'TOPOMETRI') !== false) $result_data['Topometri']['jumlah'] += $q;
    else if (strpos($n, 'AKUPUNKTUR') !== false) {
        if (strpos($n, 'MEDIK') !== false) $result_data['Akupunktur Medik']['jumlah'] += $q;
        else $result_data['Akupunktur Tradisional']['jumlah'] += $q;
    }
    else if (strpos($n, 'AKUPRESSUR') !== false) $result_data['Akupressur']['jumlah'] += $q;
    else if (strpos($n, 'HERBAL') !== false || strpos($n, 'JAMU') !== false) $result_data['Herbal/Jamu']['jumlah'] += $q;
    else if (strpos($n, 'PIJAT') !== false) $result_data['Pijat Baduta']['jumlah'] += $q;
    else if (strpos($n, 'HOMECARE') !== false || strpos($n, 'KUNJUNGAN RUMAH') !== false) $result_data['Kunjungan Rumah (Homecare)']['jumlah'] += $q;
    else if (strpos($n, 'IVA') !== false || strpos($n, 'PRA KANKER') !== false) $result_data['Tidak lanjut lesi pra Kanker Leher Rahim']['jumlah'] += $q;
}

echo json_encode(['data' => array_values($result_data)]);
mysqli_close($koneksi);
?>
