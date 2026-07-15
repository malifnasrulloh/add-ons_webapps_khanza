<?php
/*
 * File: api/data_rl_3_5.php
 * Fungsi: API Laporan RL 3.5 Rekapitulasi Kunjungan
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

// Get Kabupaten Settings
$q_set = mysqli_query($koneksi, "SELECT kabupaten FROM setting LIMIT 1");
$r_set = mysqli_fetch_assoc($q_set);
$kab_rs = strtoupper(trim(str_replace(['KAB', 'KOTA', 'KABUPATEN'], '', strtoupper($r_set['kabupaten'] ?? ''))));

$list_kegiatan = [
    1 => 'Penyakit Dalam', 2 => 'Bedah', 3 => 'Kesehatan Anak (Neonatal)', 4 => 'Kesehatan Anak (Lainnya)',
    5 => 'Obstetri & Ginekologi (Ibu Hamil)', 6 => 'Obstetri & Ginekologi (Lainnya)', 7 => 'Keluarga Berencana',
    8 => 'Jiwa', 9 => 'Napza', 10 => 'Psikologi', 11 => 'THT', 12 => 'Mata', 13 => 'Kulit dan Kelamin',
    14 => 'Gigi & Mulut', 15 => 'Geriatri', 16 => 'Kardiologi', 17 => 'Radiologi', 18 => 'Bedah Orthopedi',
    19 => 'Paru - Paru', 20 => 'Kanker', 21 => 'Uronefrologi', 22 => 'Kusta', 23 => 'Umum', 24 => 'Rawat Darurat',
    25 => 'Rehabilitasi Medik', 26 => 'Akupungtur Medik', 27 => 'Konsultasi Gizi', 28 => 'Day Care',
    29 => 'Medical Check Up', 30 => 'Bedah Saraf (Stroke)', 31 => 'Bedah Saraf (Lainnya)', 32 => 'Saraf (Stroke)',
    33 => 'Saraf (Lainnya)', 34 => 'Lain - Lain'
];

$result_data = [];
$default_cols = ['dalam_l' => 0, 'dalam_p' => 0, 'luar_l' => 0, 'luar_p' => 0, 'total' => 0];

for ($i = 1; $i <= 34; $i++) {
    $result_data[$i] = ['no' => $i, 'kegiatan' => $list_kegiatan[$i]] + $default_cols;
}

function mapPoliToIdx($nm_poli, $usia_hari, $usia_tahun) {
    $n = strtoupper($nm_poli);
    if (strpos($n, 'IGD') !== false || strpos($n, 'DARURAT') !== false) return 24;
    if (strpos($n, 'MCU') !== false || strpos($n, 'CHECK UP') !== false) return 29;
    
    // Geriatri check
    if ($usia_tahun >= 60) return 15;

    if (strpos($n, 'DALAM') !== false || strpos($n, 'INTERNA') !== false) return 1;
    if (strpos($n, 'BEDAH') !== false) {
        if (strpos($n, 'ORTHO') !== false) return 18;
        if (strpos($n, 'SARAF') !== false) return (strpos($n, 'STROKE') !== false) ? 30 : 31;
        if (strpos($n, 'ANAK') !== false) return 2; // Bedah Anak mapping to Bedah for simplicity if specific not found
        return 2;
    }
    if (strpos($n, 'ANAK') !== false) {
        if ($usia_hari <= 28) return 3;
        return 4;
    }
    if (strpos($n, 'OBG') !== false || strpos($n, 'KANDUNGAN') !== false || strpos($n, 'KEBIDANAN') !== false) {
        if (strpos($n, 'HAMIL') !== false) return 5;
        return 6;
    }
    if (strpos($n, 'KB') !== false) return 7;
    if (strpos($n, 'JIWA') !== false || strpos($n, 'PSIKI') !== false) return 8;
    if (strpos($n, 'NAPZA') !== false) return 9;
    if (strpos($n, 'PSIKOLOG') !== false) return 10;
    if (strpos($n, 'THT') !== false) return 11;
    if (strpos($n, 'MATA') !== false) return 12;
    if (strpos($n, 'KULIT') !== false || strpos($n, 'KELAMIN') !== false) return 13;
    if (strpos($n, 'GIGI') !== false || strpos($n, 'MULUT') !== false) return 14;
    if (strpos($n, 'KAR') !== false || strpos($n, 'JANTUNG') !== false) return 16;
    if (strpos($n, 'RAD') !== false) return 17;
    if (strpos($n, 'PARU') !== false) return 19;
    if (strpos($n, 'KANKER') !== false || strpos($n, 'ONK') !== false) return 20;
    if (strpos($n, 'URO') !== false || strpos($n, 'NEF') !== false) return 21;
    if (strpos($n, 'KUSTA') !== false) return 22;
    if (strpos($n, 'UMUM') !== false) return 23;
    if (strpos($n, 'REHAB') !== false || strpos($n, 'FISIO') !== false) return 25;
    if (strpos($n, 'AKUPUNKTUR') !== false) return 26;
    if (strpos($n, 'GIZI') !== false) return 27;
    if (strpos($n, 'DAY CARE') !== false) return 28;
    if (strpos($n, 'SARAF') !== false || strpos($n, 'NEURO') !== false) {
        return (strpos($n, 'STROKE') !== false) ? 32 : 33;
    }

    return 34;
}

$sql = "
    SELECT 
        rp.tgl_registrasi,
        p.jk,
        p.tgl_lahir,
        kb.nm_kab,
        pl.nm_poli,
        TIMESTAMPDIFF(DAY, p.tgl_lahir, rp.tgl_registrasi) as usia_hari,
        TIMESTAMPDIFF(YEAR, p.tgl_lahir, rp.tgl_registrasi) as usia_tahun
    FROM reg_periksa rp
    INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    INNER JOIN poliklinik pl ON rp.kd_poli = pl.kd_poli
    LEFT JOIN kabupaten kb ON p.kd_kab = kb.kd_kab
    WHERE rp.tgl_registrasi BETWEEN ? AND ? 
    AND rp.stts != 'Batal' 
    AND rp.status_lanjut = 'Ralan'
    GROUP BY rp.tgl_registrasi, rp.no_rkm_medis
";

$stmt = mysqli_prepare($koneksi, $sql);
$hari_buka = [];
$total_kunjungan = 0;

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $tgl_awal, $tgl_akhir);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($res)) {
        $hari_buka[$row['tgl_registrasi']] = true;
        
        $idx = mapPoliToIdx($row['nm_poli'], $row['usia_hari'], $row['usia_tahun']);
        $jk = $row['jk'];
        
        $nm_kab_pasien = strtoupper($row['nm_kab'] ?? '');
        $is_dalam_kota = (strpos($nm_kab_pasien, $kab_rs) !== false);

        if ($is_dalam_kota) {
            if ($jk == 'L') $result_data[$idx]['dalam_l']++;
            else $result_data[$idx]['dalam_p']++;
        } else {
            if ($jk == 'L') $result_data[$idx]['luar_l']++;
            else $result_data[$idx]['luar_p']++;
        }
        $result_data[$idx]['total']++;
        $total_kunjungan++;
    }
}

// Calculations for Total, Average Days Open, Average Visits
$data_final = array_values($result_data);
$jml_hari_buka = count($hari_buka);

$total_row = ['no' => 99, 'kegiatan' => 'TOTAL', 'dalam_l' => 0, 'dalam_p' => 0, 'luar_l' => 0, 'luar_p' => 0, 'total' => 0];
foreach($data_final as $r) {
    $total_row['dalam_l'] += $r['dalam_l'];
    $total_row['dalam_p'] += $r['dalam_p'];
    $total_row['luar_l'] += $r['luar_l'];
    $total_row['luar_p'] += $r['luar_p'];
    $total_row['total'] += $r['total'];
}
$data_final[] = $total_row;
$data_final[] = ['no' => 66, 'kegiatan' => 'Rata-Rata Hari Poliklinik Buka', 'total' => $jml_hari_buka];
$data_final[] = ['no' => 77, 'kegiatan' => 'Rata-Rata Kunjungan per Hari', 'total' => ($jml_hari_buka > 0) ? round($total_kunjungan / $jml_hari_buka) : 0];

echo json_encode(['data' => $data_final]);
mysqli_close($koneksi);
?>
