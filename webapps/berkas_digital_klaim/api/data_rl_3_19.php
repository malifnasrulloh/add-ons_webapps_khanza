<?php
/*
 * File: api/data_rl_3_19.php
 * Fungsi: API Laporan RL 3.19 Rekapitulasi Cara Bayar
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

// 11 Kategori Cara Bayar Juknis RL 3.19
$kategori_list = [
    'Membayar Sendiri',
    'Asuransi JKN (BPJS Kesehatan)',
    'Asuransi Pemerintah Daerah (Jamkesda)',
    'Asuransi Pemerintah Lainnya',
    'Asuransi Swasta',
    'Keringanan (Cost Sharing)',
    'Gratis - Kartu Sehat',
    'Gratis - Keterangan Tidak Mampu',
    'Gratis - Lain-Lain'
];

$result_data = [];
foreach ($kategori_list as $k) {
    $result_data[$k] = [
        'cara_bayar' => $k,
        'ranap_keluar' => 0, 'ranap_lama' => 0,
        'ralan_lab' => 0, 'ralan_rad' => 0, 'ralan_lain' => 0
    ];
}

function mapPenjabToKategori($kategori_pj, $png_jawab) {
    $k = strtoupper($kategori_pj);
    $p = strtoupper($png_jawab);

    if ($k == 'TUNAI') return 'Membayar Sendiri';
    if ($k == 'BPJS') return 'Asuransi JKN (BPJS Kesehatan)';
    if ($k == 'JAMSOS' || strpos($p, 'JAMKESDA') !== false) return 'Asuransi Pemerintah Daerah (Jamkesda)';
    if ($k == 'BPJSTK' || $k == 'KEMKES' || strpos($p, 'Pemerintah') !== false) return 'Asuransi Pemerintah Lainnya';
    if ($k == 'ASURANSI' || $k == 'PERUSAHAAN') return 'Asuransi Swasta';
    
    if (strpos($p, 'GRATIS') !== false) {
        if (strpos($p, 'KARTU SEHAT') !== false) return 'Gratis - Kartu Sehat';
        if (strpos($p, 'SKTM') !== false || strpos($p, 'TIDAK MAMPU') !== false) return 'Gratis - Keterangan Tidak Mampu';
        return 'Gratis - Lain-Lain';
    }

    return 'Membayar Sendiri'; // Default
}

// 1. Data Rawat Inap (Pasien Keluar & Lama Dirawat)
$sql_ranap = "
    SELECT pj.kategori, pj.png_jawab, COUNT(*) as keluar, SUM(ki.lama) as lama
    FROM kamar_inap ki
    INNER JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
    INNER JOIN penjab pj ON rp.kd_pj = pj.kd_pj
    WHERE ki.tgl_keluar BETWEEN ? AND ?
    AND ki.stts_pulang NOT IN ('-', 'Pindah Kamar')
    GROUP BY pj.kd_pj
";
$stmt1 = mysqli_prepare($koneksi, $sql_ranap);
mysqli_stmt_bind_param($stmt1, "ss", $tgl_awal, $tgl_akhir);
mysqli_stmt_execute($stmt1);
$res1 = mysqli_stmt_get_result($stmt1);
while($row = mysqli_fetch_assoc($res1)) {
    $cat = mapPenjabToKategori($row['kategori'], $row['png_jawab']);
    $result_data[$cat]['ranap_keluar'] += $row['keluar'];
    $result_data[$cat]['ranap_lama'] += $row['lama'];
}

// 2. Data Rawat Jalan (Laboratorium)
$sql_lab = "
    SELECT pj.kategori, pj.png_jawab, COUNT(DISTINCT pl.no_rawat) as qty
    FROM periksa_lab pl
    INNER JOIN reg_periksa rp ON pl.no_rawat = rp.no_rawat
    INNER JOIN penjab pj ON rp.kd_pj = pj.kd_pj
    WHERE pl.tgl_periksa BETWEEN ? AND ?
    AND rp.status_lanjut = 'Ralan'
    GROUP BY pj.kd_pj
";
$stmt2 = mysqli_prepare($koneksi, $sql_lab);
mysqli_stmt_bind_param($stmt2, "ss", $tgl_awal, $tgl_akhir);
mysqli_stmt_execute($stmt2);
$res2 = mysqli_stmt_get_result($stmt2);
while($row = mysqli_fetch_assoc($res2)) {
    $cat = mapPenjabToKategori($row['kategori'], $row['png_jawab']);
    $result_data[$cat]['ralan_lab'] += $row['qty'];
}

// 3. Data Rawat Jalan (Radiologi)
$sql_rad = "
    SELECT pj.kategori, pj.png_jawab, COUNT(DISTINCT pr.no_rawat) as qty
    FROM periksa_radiologi pr
    INNER JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
    INNER JOIN penjab pj ON rp.kd_pj = pj.kd_pj
    WHERE pr.tgl_periksa BETWEEN ? AND ?
    AND rp.status_lanjut = 'Ralan'
    GROUP BY pj.kd_pj
";
$stmt3 = mysqli_prepare($koneksi, $sql_rad);
mysqli_stmt_bind_param($stmt3, "ss", $tgl_awal, $tgl_akhir);
mysqli_stmt_execute($stmt3);
$res3 = mysqli_stmt_get_result($stmt3);
while($row = mysqli_fetch_assoc($res3)) {
    $cat = mapPenjabToKategori($row['kategori'], $row['png_jawab']);
    $result_data[$cat]['ralan_rad'] += $row['qty'];
}

// 4. Data Rawat Jalan (Lain-lain: Poliklinik & IGD)
$sql_lain = "
    SELECT pj.kategori, pj.png_jawab, COUNT(*) as qty
    FROM reg_periksa rp
    INNER JOIN penjab pj ON rp.kd_pj = pj.kd_pj
    WHERE rp.tgl_registrasi BETWEEN ? AND ?
    AND rp.status_lanjut = 'Ralan'
    GROUP BY pj.kd_pj
";
$stmt4 = mysqli_prepare($koneksi, $sql_lain);
mysqli_stmt_bind_param($stmt4, "ss", $tgl_awal, $tgl_akhir);
mysqli_stmt_execute($stmt4);
$res4 = mysqli_stmt_get_result($stmt4);
while($row = mysqli_fetch_assoc($res4)) {
    $cat = mapPenjabToKategori($row['kategori'], $row['png_jawab']);
    $result_data[$cat]['ralan_lain'] += $row['qty'];
}

echo json_encode(['data' => array_values($result_data)]);
mysqli_close($koneksi);
?>
