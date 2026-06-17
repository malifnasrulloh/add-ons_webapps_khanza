<?php
/*
 * File: api/data_rl_4_1.php
 * Fungsi: API Laporan RL 4.1 Kompilasi Penyakit/Morbiditas Pasien Rawat Inap
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

// SQL: Fetch diagnosis primary for Inpatient
$sql = "
    SELECT 
        dp.kd_penyakit, 
        py.nm_penyakit, 
        p.jk, 
        ki.stts_pulang,
        pm.no_rkm_medis as pm_mati,
        TIMESTAMPDIFF(HOUR, p.tgl_lahir, rp.tgl_registrasi) as usia_jam,
        TIMESTAMPDIFF(DAY, p.tgl_lahir, rp.tgl_registrasi) as usia_hari,
        TIMESTAMPDIFF(MONTH, p.tgl_lahir, rp.tgl_registrasi) as usia_bulan,
        TIMESTAMPDIFF(YEAR, p.tgl_lahir, rp.tgl_registrasi) as usia_tahun
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
            'total_l' => 0, 'total_p' => 0, 'mati_l' => 0, 'mati_p' => 0
        ];
        for($i=0; $i<25; $i++) {
            $data_map[$kode]["u{$i}_l"] = 0;
            $data_map[$kode]["u{$i}_p"] = 0;
        }
    }

    $jk = ($row['jk'] == 'L') ? 'l' : 'p';
    $is_mati = (strpos(strtolower($row['stts_pulang']), 'meninggal') !== false || !empty($row['pm_mati']));
    
    // Age Grouping Logic (25 groups)
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
        $data_map[$kode]["u{$cat}_{$jk}"]++;
        $data_map[$kode]["total_{$jk}"]++;
        if ($is_mati) {
            $data_map[$kode]["mati_{$jk}"]++;
        }
    }
}

echo json_encode(['data' => array_values($data_map)]);
mysqli_close($koneksi);
?>
