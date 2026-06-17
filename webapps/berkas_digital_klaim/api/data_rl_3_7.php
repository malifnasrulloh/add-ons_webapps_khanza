<?php
/*
 * File: api/data_rl_3_7.php
 * Fungsi: Laporan RL 3.7 Rekapitulasi Kegiatan Pelayanan Neonatal, Bayi, dan Balita (Juknis 2025)
 */

error_reporting(0);
ini_set('display_errors', 0);

if(file_exists(__DIR__ . '/../../conf/conf.php')) {
    require_once(__DIR__ . '/../../conf/conf.php');
} else {
    require_once(__DIR__ . '/../conf/conf.php');
}

header('Content-Type: application/json');
$koneksi = bukakoneksi();

session_start();
if (!isset($_SESSION['casemix_login'])) {
    http_response_code(403); echo json_encode(['error' => 'Akses ditolak.']); exit;
}

$tgl_awal   = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir  = $_GET['tgl_akhir'] ?? date('Y-m-d');

$keys = [
    '1.1.1', '1.1.2', '1.1.3', '1.2.1', '1.2.2', '1.2.3', '1.3.1', '1.3.2', '1.3.3',
    '2.1', '2.2', '3.1', '3.2',
    '4.1', '4.2', '4.3', '4.4', '4.5', '4.6', '4.7', '4.8',
    '5', '6', '7', 
    '8.1', '8.2', '8.3', '9.1', '9.2', '10',
    '11.1', '11.2', '11.3', '11.4', '11.5', '11.6', '11.7',
    '12.1', '12.2', '12.3', '12.4', '12.5', '12.6', '12.7', '12.8',
    '13.1', '13.2', '13.3', '14.1', '14.2', '15.1', '15.2', '15.3',
    '16.1', '16.2', '16.3', '16.4', '16.5', '16.6',
    '17.1', '17.2', '17.3'
];

$result_data = [];
$default_cols = [
    'r_medis_rs'=>0, 'r_medis_bidan'=>0, 'r_medis_pkm'=>0, 'r_medis_faskes_lain'=>0,
    'r_medis_hidup'=>0, 'r_medis_mati'=>0, 'total_rm'=>0,
    'r_non_medis_hidup'=>0, 'r_non_medis_mati'=>0, 'total_rnm'=>0,
    'non_rujukan_hidup'=>0, 'non_rujukan_mati'=>0, 'total_nr'=>0,
    'dirujuk'=>0
];

foreach ($keys as $k) {
    $result_data[$k] = $default_cols;
}

// 1. Ambil data kunjungan yang berkaitan dengan Bayi & Balita
$sql = "
    SELECT 
        rp.no_rawat,
        p.tgl_lahir,
        rp.tgl_registrasi,
        rp.stts_pulang,
        rp.status_lanjut,
        IFNULL(rm.perujuk, '') as perujuk,
        IFNULL(rm.no_rujuk, '') as no_rujuk,
        GROUP_CONCAT(DISTINCT dp.kd_penyakit SEPARATOR ',') as diags,
        (SELECT GROUP_CONCAT(po.nm_perawatan) FROM rawat_jl_dr rjd INNER JOIN jns_perawatan po ON rjd.kd_jenis_prw = po.kd_jenis_prw WHERE rjd.no_rawat = rp.no_rawat) as treatments,
        (SELECT GROUP_CONCAT(pao.kode_brng) FROM pemberian_obat pao WHERE pao.no_rawat = rp.no_rawat) as drugs,
        (SELECT status_lahir FROM catatan_persalinan cp WHERE cp.no_rawat = rp.no_rawat LIMIT 1) as status_lahir_cp,
        pb.umur_kehamilan, pb.bblahir, pb.macam_persalinan
    FROM reg_periksa rp
    INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    LEFT JOIN rujuk_masuk rm ON rp.no_rawat = rm.no_rawat
    LEFT JOIN diagnosa_pasien dp ON rp.no_rawat = dp.no_rawat
    LEFT JOIN penilaian_bayi_baru_lahir pb ON rp.no_rawat = pb.no_rawat
    WHERE rp.tgl_registrasi BETWEEN ? AND ?
    AND rp.stts != 'Batal'
    GROUP BY rp.no_rawat
";

$stmt = mysqli_prepare($koneksi, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $tgl_awal, $tgl_akhir);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($res)) {
        
        $diags = strtoupper($row['diags'] ?? '');
        $arr_diag = explode(',', $diags);
        $treats = strtoupper($row['treatments'] ?? '');
        $drugs = $row['drugs'] ?? '';
        
        // Cek Umur Saat Kunjungan
        $bday = new DateTime($row['tgl_lahir']);
        $today = new DateTime($row['tgl_registrasi']);
        $diff = $today->diff($bday);
        $umur_hari = $diff->days;
        $umur_thn = $diff->y;
        $umur_bln_total = $diff->m + ($umur_thn * 12);
        
        if ($umur_bln_total >= 60) continue;

        $cat = [];

        // 1. Lahir Hidup (dari penilaian_bayi_baru_lahir)
        if (!empty($row['bblahir'])) {
            $bb = (int) $row['bblahir'];
            $uk_str = $row['umur_kehamilan'] ?? '';
            // Ekstrak angka minggu dari string (misal "38 Minggu")
            preg_match('/(\d+)/', $uk_str, $matches);
            $uk = isset($matches[1]) ? (int)$matches[1] : 38; // Default non-prematur if not clear

            $k_lahir = '';
            if ($uk < 37) {
                if ($bb >= 1500 && $bb < 2500) $k_lahir = '1.1.1';
                else if ($bb >= 1000 && $bb < 1500) $k_lahir = '1.1.2';
                else if ($bb < 1000) $k_lahir = '1.1.3';
            } else if ($uk >= 37 && $uk <= 41) {
                if ($bb >= 1500 && $bb < 2500) $k_lahir = '1.2.1';
                else if ($bb >= 2500 && $bb < 4000) $k_lahir = '1.2.2';
                else if ($bb >= 4000) $k_lahir = '1.2.3';
            } else {
                if ($bb >= 1500 && $bb < 2500) $k_lahir = '1.3.1';
                else if ($bb >= 2500 && $bb < 4000) $k_lahir = '1.3.2';
                else if ($bb >= 4000) $k_lahir = '1.3.3';
            }
            if ($k_lahir != '') $cat[$k_lahir] = true;
        }

        // 2. Lahir Mati
        if ($row['status_lahir_cp'] == 'Mati') {
            $cat['2.2'] = true; // Default Intrapartum
        }

        // 3. Kematian Neonatal
        if (preg_match('/meninggal|mati/', strtolower($row['stts_pulang']))) {
            if ($umur_hari >= 0 && $umur_hari <= 7) $cat['3.1'] = true;
            else if ($umur_hari >= 8 && $umur_hari <= 28) $cat['3.2'] = true;
        }

        // 4. Komplikasi Neonatal & 9. Gizi Buruk
        $is_komp_neo = false;
        foreach($arr_diag as $icd) {
            $prefix = substr(trim($icd), 0, 3);
            if ($umur_hari <= 28) {
                if ($prefix == 'P20' || $prefix == 'P21') { $cat['4.1'] = true; $is_komp_neo = true; }
                else if ($prefix >= 'P10' && $prefix <= 'P15') { $cat['4.2'] = true; $is_komp_neo = true; }
                else if ($prefix == 'P07' || $prefix == 'P05') { $cat['4.3'] = true; $is_komp_neo = true; }
                else if ($prefix == 'A33') { $cat['4.4'] = true; $is_komp_neo = true; }
                else if ($prefix >= 'Q00' && $prefix <= 'Q99') { $cat['4.5'] = true; $is_komp_neo = true; }
                else if ($prefix == 'U07') { $cat['4.6'] = true; $is_komp_neo = true; }
                else if ($prefix == 'P36') { $cat['4.7'] = true; $is_komp_neo = true; }
                else if (strpos($prefix, 'P') === 0) { $cat['4.8'] = true; $is_komp_neo = true; }
            }
            if ($prefix >= 'E40' && $prefix <= 'E46') {
                if ($umur_bln_total <= 5) {
                    $cat['9.1'] = true;
                    if ($row['status_lanjut'] == 'Ranap') $cat['17.1'] = true;
                } else {
                    $cat['9.2'] = true;
                    if ($row['status_lanjut'] == 'Ranap') $cat['17.2'] = true;
                    else $cat['17.3'] = true;
                }
            }
        }

        // 7. SHK
        if (strpos($treats, 'SHK') !== false) $cat['7'] = true;

        // 8. Kunjungan Bayi & Balita
        if ($umur_hari <= 28) $cat['8.1'] = true;
        else if ($umur_bln_total >= 1 && $umur_bln_total <= 11) $cat['8.2'] = true;
        else if ($umur_bln_total >= 12 && $umur_bln_total <= 59) $cat['8.3'] = true;

        // 12 & 16. Imunisasi & Vitamin
        if ($umur_bln_total < 12) {
            if (strpos($treats, 'BCG') !== false) $cat['12.2'] = true;
            if (strpos($treats, 'POLIO') !== false) $cat['12.3'] = true;
            if (strpos($treats, 'DPT') !== false) $cat['12.4'] = true;
            if (strpos($treats, 'CAMPAK') !== false) $cat['12.6'] = true;
            if (strpos($drugs, 'B0000100060') !== false) $cat['12.7'] = true; // Vit A 100k
        } else {
            if (strpos($treats, 'CAMPAK') !== false) $cat['16.1'] = true;
            if (strpos($drugs, 'B00001000612') !== false) $cat['16.2'] = true; // Vit A 200k
        }

        // Skrining (11)
        if (strpos($treats, 'PENDENGARAN') !== false) $cat['11.6'] = true;
        if (strpos($treats, 'PENGLIHATAN') !== false) $cat['11.7'] = true;

        if (empty($cat)) continue;

        // Identifikasi Rujukan
        $perujuk = strtolower($row['perujuk']);
        $is_rujukan_medis = false; $is_rujukan_non = false;
        $src_rs = false; $src_bidan = false; $src_pkm = false; $src_faskes = false;
        if (!empty($row['no_rujuk'])) {
            if (preg_match('/polisi|hukum|jaksa|pengadilan/', $perujuk)) $is_rujukan_non = true;
            else {
                $is_rujukan_medis = true;
                if (preg_match('/rs|rumah sakit/', $perujuk)) $src_rs = true;
                else if (preg_match('/bidan|bpm|amd.keb/', $perujuk)) $src_bidan = true;
                else if (preg_match('/pkm|puskesmas/', $perujuk)) $src_pkm = true;
                else $src_faskes = true;
            }
        }
        $is_mati = preg_match('/meninggal|mati/', strtolower($row['stts_pulang'])) || !empty($row['pm_mati']);
        $is_dirujuk = preg_match('/rujuk/', strtolower($row['stts_pulang'])) || preg_match('/rujuk/', strtolower($row['status_lanjut']));

        foreach ($cat as $k => $val) {
            if ($is_rujukan_medis) {
                if ($src_rs) $result_data[$k]['r_medis_rs']++;
                else if ($src_bidan) $result_data[$k]['r_medis_bidan']++;
                else if ($src_pkm) $result_data[$k]['r_medis_pkm']++;
                else $result_data[$k]['r_medis_faskes_lain']++;
                if ($is_mati) $result_data[$k]['r_medis_mati']++; else $result_data[$k]['r_medis_hidup']++;
                $result_data[$k]['total_rm']++;
            } else if ($is_rujukan_non) {
                if ($is_mati) $result_data[$k]['r_non_medis_mati']++; else $result_data[$k]['r_non_medis_hidup']++;
                $result_data[$k]['total_rnm']++;
            } else {
                if ($is_mati) $result_data[$k]['non_rujukan_mati']++; else $result_data[$k]['non_rujukan_hidup']++;
                $result_data[$k]['total_nr']++;
            }
            if ($is_dirujuk) $result_data[$k]['dirujuk']++;
        }
    }
    mysqli_stmt_close($stmt);
}

echo json_encode(['data' => $result_data]);
?>
k']++;
        }
    }
    mysqli_stmt_close($stmt);
}

echo json_encode(['data' => $result_data]);
?>
