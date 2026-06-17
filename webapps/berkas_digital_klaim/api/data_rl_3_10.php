<?php
/*
 * File: api/data_rl_3_10.php
 * Fungsi: API Laporan RL 3.10 Rekapitulasi Kegiatan Pelayanan Rujukan (REVISED)
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

// 20 Spesialisasi Juknis
$spesialisasi_list = [
    'Penyakit Dalam', 'Bedah', 'Kesehatan Anak', 'Kesehatan Remaja', 'Obstetri',
    'Ginekologi', 'Keluarga Berencana', 'Saraf (Non Stroke)', 'Jiwa', 'THT',
    'Mata', 'Kulit dan Kelamin', 'Gigi dan Mulut', 'Radiologi', 'Paru',
    'Kardiologi', 'Kanker', 'Uronefrologi', 'Saraf (Stroke)', 'Spesialisasi Lain'
];

$result_data = [];
foreach ($spesialisasi_list as $sp) {
    $result_data[$sp] = [
        'spesialisasi' => $sp,
        'masuk_pusk' => 0, 'masuk_rs' => 0, 'masuk_lain' => 0, 'masuk_total' => 0,
        'balik_pusk' => 0, 'balik_rs' => 0, 'balik_lain' => 0, 'balik_total' => 0,
        'keluar_rujukan' => 0, 'keluar_sendiri' => 0, 'keluar_total' => 0,
        'terima_kembali' => 0
    ];
}

function mapPoliToSpesialisasi($nm_poli) {
    $n = strtoupper($nm_poli);
    if (strpos($n, 'DALAM') !== false || strpos($n, 'INTERNA') !== false) return 'Penyakit Dalam';
    if (strpos($n, 'BEDAH') !== false && strpos($n, 'SARAF') === false && strpos($n, 'ORTHO') === false) return 'Bedah';
    if (strpos($n, 'ANAK') !== false) return 'Kesehatan Anak';
    if (strpos($n, 'REMAJA') !== false) return 'Kesehatan Remaja';
    if (strpos($n, 'OBG') !== false || strpos($n, 'KANDUNGAN') !== false || strpos($n, 'KEBIDANAN') !== false) return 'Obstetri';
    if (strpos($n, 'GINEKOLOGI') !== false) return 'Ginekologi';
    if (strpos($n, 'KB') !== false) return 'Keluarga Berencana';
    if (strpos($n, 'SARAF') !== false && strpos($n, 'STROKE') === false) return 'Saraf (Non Stroke)';
    if (strpos($n, 'JIWA') !== false || strpos($n, 'PSIKI') !== false) return 'Jiwa';
    if (strpos($n, 'THT') !== false) return 'THT';
    if (strpos($n, 'MATA') !== false) return 'Mata';
    if (strpos($n, 'KULIT') !== false || strpos($n, 'KELAMIN') !== false || strpos($n, 'DV') !== false) return 'Kulit dan Kelamin';
    if (strpos($n, 'GIGI') !== false || strpos($n, 'MULUT') !== false) return 'Gigi dan Mulut';
    if (strpos($n, 'RAD') !== false) return 'Radiologi';
    if (strpos($n, 'PARU') !== false) return 'Paru';
    if (strpos($n, 'KAR') !== false || strpos($n, 'JANTUNG') !== false) return 'Kardiologi';
    if (strpos($n, 'ONK') !== false || strpos($n, 'KANKER') !== false) return 'Kanker';
    if (strpos($n, 'URO') !== false || strpos($n, 'NEF') !== false) return 'Uronefrologi';
    if (strpos($n, 'STROKE') !== false) return 'Saraf (Stroke)';
    return 'Spesialisasi Lain';
}

// 1. Ambil Rujukan Masuk
$sql_masuk = "
    SELECT rm.perujuk, rm.no_balasan, pl.nm_poli, sep.asal_rujukan
    FROM rujuk_masuk rm
    INNER JOIN reg_periksa rp ON rm.no_rawat = rp.no_rawat
    INNER JOIN poliklinik pl ON rp.kd_poli = pl.kd_poli
    LEFT JOIN bridging_sep sep ON rm.no_rawat = sep.no_rawat
    WHERE rp.tgl_registrasi BETWEEN ? AND ?
";
$stmt = mysqli_prepare($koneksi, $sql_masuk);
mysqli_stmt_bind_param($stmt, "ss", $tgl_awal, $tgl_akhir);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $sp = mapPoliToSpesialisasi($row['nm_poli']);
    $p = strtoupper($row['perujuk'] ?? '');
    
    $cat = 'lain';
    if (($row['asal_rujukan'] ?? '') == '1. Faskes 1' || strpos($p, 'PUSKESMAS') !== false || strpos($p, 'PKM') !== false) $cat = 'pusk';
    else if (($row['asal_rujukan'] ?? '') == '2. Faskes 2(RS)' || strpos($p, 'RS') !== false || strpos($p, 'RUMAH SAKIT') !== false) $cat = 'rs';

    $result_data[$sp]['masuk_'.$cat]++;
    $result_data[$sp]['masuk_total']++;
}

// 2. Rujukan Masuk Dikembalikan (Menggunakan tabel surat_rujukan_balik sesuai referensi Java)
$sql_balik = "
    SELECT srb.ppk1, pl.nm_poli
    FROM surat_rujukan_balik srb
    INNER JOIN reg_periksa rp ON srb.no_rawat = rp.no_rawat
    INNER JOIN poliklinik pl ON rp.kd_poli = pl.kd_poli
    WHERE rp.tgl_registrasi BETWEEN ? AND ?
";
$stmt_b = mysqli_prepare($koneksi, $sql_balik);
mysqli_stmt_bind_param($stmt_b, "ss", $tgl_awal, $tgl_akhir);
mysqli_stmt_execute($stmt_b);
$res_b = mysqli_stmt_get_result($stmt_b);
while ($row_b = mysqli_fetch_assoc($res_b)) {
    $sp = mapPoliToSpesialisasi($row_b['nm_poli']);
    $p = strtoupper($row_b['ppk1'] ?? '');
    
    $cat = 'lain';
    if (strpos($p, 'PUSKESMAS') !== false || strpos($p, 'PKM') !== false) $cat = 'pusk';
    else if (strpos($p, 'RS') !== false || strpos($p, 'RUMAH SAKIT') !== false) $cat = 'rs';

    $result_data[$sp]['balik_'.$cat]++;
    $result_data[$sp]['balik_total']++;
}

// 3. Ambil Rujukan Keluar
$sql_keluar = "
    SELECT r.rujuk_ke, pl.nm_poli,
    (SELECT COUNT(*) FROM rujuk_masuk rm WHERE rm.no_rawat = r.no_rawat) as is_rujukan
    FROM rujuk r
    INNER JOIN reg_periksa rp ON r.no_rawat = rp.no_rawat
    INNER JOIN poliklinik pl ON rp.kd_poli = pl.kd_poli
    WHERE r.tgl_rujuk BETWEEN ? AND ?
";
$stmt2 = mysqli_prepare($koneksi, $sql_keluar);
mysqli_stmt_bind_param($stmt2, "ss", $tgl_awal, $tgl_akhir);
mysqli_stmt_execute($stmt2);
$res2 = mysqli_stmt_get_result($stmt2);
while ($row = mysqli_fetch_assoc($res2)) {
    $sp = mapPoliToSpesialisasi($row['nm_poli']);
    if ($row['is_rujukan'] > 0) $result_data[$sp]['keluar_rujukan']++;
    else $result_data[$sp]['keluar_sendiri']++;
    $result_data[$sp]['keluar_total']++;
}

// 4. Pasien Rujukan Diterima Kembali (Refined Logic)
// Pasien yang register di periode ini, tapi pernah dirujuk keluar di masa lalu (3 bulan terakhir)
$sql_terima = "
    SELECT pl.nm_poli, COUNT(*) as jml
    FROM reg_periksa rp
    INNER JOIN poliklinik pl ON rp.kd_poli = pl.kd_poli
    INNER JOIN rujuk r ON rp.no_rkm_medis = r.no_rkm_medis
    WHERE rp.tgl_registrasi BETWEEN ? AND ?
    AND r.tgl_rujuk < rp.tgl_registrasi
    AND r.tgl_rujuk >= DATE_SUB(?, INTERVAL 3 MONTH)
    GROUP BY pl.nm_poli
";
$stmt3 = mysqli_prepare($koneksi, $sql_terima);
mysqli_stmt_bind_param($stmt3, "sss", $tgl_awal, $tgl_akhir, $tgl_awal);
mysqli_stmt_execute($stmt3);
$res3 = mysqli_stmt_get_result($stmt3);
while ($row_t = mysqli_fetch_assoc($res3)) {
    $sp = mapPoliToSpesialisasi($row_t['nm_poli']);
    $result_data[$sp]['terima_kembali'] += $row_t['jml'];
}

echo json_encode(['data' => array_values($result_data)]);
mysqli_close($koneksi);
?>
