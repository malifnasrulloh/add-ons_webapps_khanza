<?php
/*
 * File: api/data_rl_3_16.php
 * Fungsi: API Laporan RL 3.16 Rekapitulasi Kegiatan Pelayanan Keluarga Berencana
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

// 8 Metode Kontrasepsi Juknis RL 3.16
$metode_list = [
    'Tubektomi/MOW/Sterilisasi wanita', 'Vasektomi/MOP/Sterilisasi pria',
    'Implan', 'Alat Kontrasepsi Dalam Rahim (AKDR) / Intra Uterine Device (IUD)',
    'Suntik', 'Pil', 'Kondom', 'MAL (Metode Amenore Laktasi)'
];

$result_data = [];
foreach ($metode_list as $m) {
    $result_data[$m] = [
        'metode' => $m,
        'pasca_salin' => 0, 'pasca_gugur' => 0, 'interval' => 0, 'total_pelayanan' => 0,
        'komplikasi' => 0, 'kegagalan' => 0, 'efek_samping' => 0, 'drop_out' => 0
    ];
}

$sql = "
    SELECT nm_perawatan, nm_poli, COUNT(*) as qty
    FROM (
        SELECT jp.nm_perawatan, pl.nm_poli FROM rawat_jl_dr rj 
        INNER JOIN jns_perawatan jp ON rj.kd_jenis_prw = jp.kd_jenis_prw 
        INNER JOIN poliklinik pl ON rj.kd_poli = pl.kd_poli
        WHERE rj.tgl_perawatan BETWEEN ? AND ?
        UNION ALL
        SELECT jp.nm_perawatan, pl.nm_poli FROM rawat_jl_pr rj 
        INNER JOIN jns_perawatan jp ON rj.kd_jenis_prw = jp.kd_jenis_prw 
        INNER JOIN poliklinik pl ON rj.kd_poli = pl.kd_poli
        WHERE rj.tgl_perawatan BETWEEN ? AND ?
        UNION ALL
        SELECT jp.nm_perawatan, pl.nm_poli FROM rawat_jl_drpr rj 
        INNER JOIN jns_perawatan jp ON rj.kd_jenis_prw = jp.kd_jenis_prw 
        INNER JOIN poliklinik pl ON rj.kd_poli = pl.kd_poli
        WHERE rj.tgl_perawatan BETWEEN ? AND ?
        UNION ALL
        SELECT jp.nm_perawatan, b.nm_bangsal FROM rawat_inap_dr ri 
        INNER JOIN jns_perawatan_inap jp ON ri.kd_jenis_prw = jp.kd_jenis_prw 
        INNER JOIN kamar k ON ri.kd_kamar = k.kd_kamar
        INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
        WHERE ri.tgl_perawatan BETWEEN ? AND ?
        UNION ALL
        SELECT jp.nm_perawatan, b.nm_bangsal FROM rawat_inap_pr ri 
        INNER JOIN jns_perawatan_inap jp ON ri.kd_jenis_prw = jp.kd_jenis_prw 
        INNER JOIN kamar k ON ri.kd_kamar = k.kd_kamar
        INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
        WHERE ri.tgl_perawatan BETWEEN ? AND ?
        UNION ALL
        SELECT jp.nm_perawatan, b.nm_bangsal FROM rawat_inap_drpr ri 
        INNER JOIN jns_perawatan_inap jp ON ri.kd_jenis_prw = jp.kd_jenis_prw 
        INNER JOIN kamar k ON ri.kd_kamar = k.kd_kamar
        INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
        WHERE ri.tgl_perawatan BETWEEN ? AND ?
    ) as gabungan
    GROUP BY nm_perawatan, nm_poli
";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "ssssssssssss", $tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($res)) {
    $n = strtoupper($row['nm_perawatan']);
    $p = strtoupper($row['nm_poli']);
    $q = $row['qty'];

    $target = '';
    if (strpos($n, 'MOW') !== false || strpos($n, 'TUBEKTOMI') !== false) $target = 'Tubektomi/MOW/Sterilisasi wanita';
    else if (strpos($n, 'MOP') !== false || strpos($n, 'VASEKTOMI') !== false) $target = 'Vasektomi/MOP/Sterilisasi pria';
    else if (strpos($n, 'IMPLAN') !== false) $target = 'Implan';
    else if (strpos($n, 'AKDR') !== false || strpos($n, 'IUD') !== false) $target = 'Alat Kontrasepsi Dalam Rahim (AKDR) / Intra Uterine Device (IUD)';
    else if (strpos($n, 'SUNTIK') !== false) $target = 'Suntik';
    else if (strpos($n, 'PIL') !== false) $target = 'Pil';
    else if (strpos($n, 'KONDOM') !== false) $target = 'Kondom';
    else if (strpos($n, 'MAL') !== false) $target = 'MAL (Metode Amenore Laktasi)';

    if ($target != '') {
        // Logic for Pasca Salin / Pasca Gugur / Interval
        if (strpos($p, 'VK') !== false || strpos($p, 'BERSALIN') !== false || strpos($p, 'NIFAS') !== false) {
            $result_data[$target]['pasca_salin'] += $q;
        } else if (strpos($n, 'ABORTUS') !== false || strpos($n, 'KURET') !== false) {
            $result_data[$target]['pasca_gugur'] += $q;
        } else {
            $result_data[$target]['interval'] += $q;
        }
        $result_data[$target]['total_pelayanan'] += $q;
    }
}

echo json_encode(['data' => array_values($result_data)]);
mysqli_close($koneksi);
?>
