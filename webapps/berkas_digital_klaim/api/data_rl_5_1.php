<?php
/*
 * File: api/data_rl_5_1.php
 * Fungsi: API Laporan RL 5.1 Kompilasi Morbiditas Pasien Rawat Jalan
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

// SQL: Fetch diagnosis primary for Outpatient
$sql = "
    SELECT 
        dp.no_rawat,
        rp.no_rkm_medis,
        dp.kd_penyakit, 
        py.nm_penyakit, 
        p.jk, 
        rp.tgl_registrasi,
        TIMESTAMPDIFF(HOUR, p.tgl_lahir, rp.tgl_registrasi) as usia_jam,
        TIMESTAMPDIFF(DAY, p.tgl_lahir, rp.tgl_registrasi) as usia_hari,
        TIMESTAMPDIFF(MONTH, p.tgl_lahir, rp.tgl_registrasi) as usia_bulan,
        TIMESTAMPDIFF(YEAR, p.tgl_lahir, rp.tgl_registrasi) as usia_tahun,
        (SELECT COUNT(*) FROM diagnosa_pasien dp2 
         INNER JOIN reg_periksa rp2 ON dp2.no_rawat = rp2.no_rawat
         WHERE rp2.no_rkm_medis = rp.no_rkm_medis 
         AND dp2.kd_penyakit = dp.kd_penyakit 
         AND rp2.tgl_registrasi < rp.tgl_registrasi) as count_lama
    FROM diagnosa_pasien dp
    INNER JOIN reg_periksa rp ON dp.no_rawat = rp.no_rawat
    INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    INNER JOIN penyakit py ON dp.kd_penyakit = py.kd_penyakit
    WHERE dp.prioritas = '1'
    AND rp.status_lanjut = 'Ralan'
    AND rp.tgl_registrasi BETWEEN ? AND ?
";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "ss", $tgl_awal, $tgl_akhir);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$data_map = [];

while ($row = mysqli_fetch_assoc($res)) {
    $kode = $row['kd_penyakit'];
    if (!isset($data_map[$kode])) {
        $data_map[$kode] = [
            'kode' => $kode,
            'diagnosis' => $row['nm_penyakit'],
            'baru_l' => 0, 'baru_p' => 0, 'baru_total' => 0,
            'kunjungan_l' => 0, 'kunjungan_p' => 0, 'kunjungan_total' => 0
        ];
        for($i=0; $i<25; $i++) {
            $data_map[$kode]["u{$i}_l"] = 0;
            $data_map[$kode]["u{$i}_p"] = 0;
        }
    }

    $jk = ($row['jk'] == 'L') ? 'l' : 'p';
    $is_baru = ($row['count_lama'] == 0);
    
    // Age Grouping Logic
    $cat = -1;
    $uj = $row['usia_jam'];
    $uh = $row['usia_hari'];
    $ub = $row['usia_bulan'];
    $ut = $row['usia_tahun'];

    if ($uj < 1) $cat = 0;
    else if ($uj < 24) $cat = 1;
    else if ($uh <= 7) $cat = 2;
    else if ($uh <= 28) $cat = 3;
    else if ($uh > 28 && $ub < 3) $cat = 4;
    else if ($ub < 6) $cat = 5;
    else if ($ub < 12) $cat = 6;
    else if ($ut < 5) $cat = 7;
    else if ($ut < 10) $cat = 8;
    else if ($ut < 15) $cat = 9;
    else if ($ut < 20) $cat = 10;
    else if ($ut < 25) $cat = 11;
    else if ($ut < 30) $cat = 12;
    else if ($ut < 35) $cat = 13;
    else if ($ut < 40) $cat = 14;
    else if ($ut < 45) $cat = 15;
    else if ($ut < 50) $cat = 16;
    else if ($ut < 55) $cat = 17;
    else if ($ut < 60) $cat = 18;
    else if ($ut < 65) $cat = 19;
    else if ($ut < 70) $cat = 20;
    else if ($ut < 75) $cat = 21;
    else if ($ut < 80) $cat = 22;
    else if ($ut < 85) $cat = 23;
    else $cat = 24;

    if ($cat >= 0) {
        if ($is_baru) {
            $data_map[$kode]["u{$cat}_{$jk}"]++;
            $data_map[$kode]["baru_{$jk}"]++;
            $data_map[$kode]["baru_total"]++;
        }
        $data_map[$kode]["kunjungan_{$jk}"]++;
        $data_map[$kode]["kunjungan_total"]++;
    }
}

echo json_encode(['data' => array_values($data_map)]);
mysqli_close($koneksi);
?>
