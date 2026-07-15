<?php
/*
 * File: api/data_rl_3_6.php
 * Fungsi: Laporan RL 3.6 Rekapitulasi Kegiatan Pelayanan Kebidanan (Juknis 2025)
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

// Daftar Indikator RL 3.6 sesuai Juknis
$keys = [
    '1', '2', '3.1', '3.2', '3.3', '3.4',
    '4.1', '4.2', '4.3', '4.4', '4.5', '4.6', '4.7',
    '5.1', '5.2', '6',
    '7.1', '7.2', '7.3', '7.4', '7.5', '7.6', '7.7', '7.8', '7.9',
    '8.1', '8.2', '9', '10'
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

// 1. Ambil data kunjungan yang berkaitan dengan Kebidanan
// Menggunakan diagnosa (O, Z34, Z35, Z39) ATAU tindakan SC
$sql = "
    SELECT 
        rp.no_rawat,
        rp.stts_pulang,
        rp.status_lanjut,
        rp.stts_daftar,
        IFNULL(rm.perujuk, '') as perujuk,
        IFNULL(rm.no_rujuk, '') as no_rujuk,
        GROUP_CONCAT(DISTINCT dp.kd_penyakit SEPARATOR ',') as diags,
        (SELECT GROUP_CONCAT(po.nm_perawatan) FROM operasi op INNER JOIN paket_operasi po ON op.kode_paket = po.kode_paket WHERE op.no_rawat = rp.no_rawat) as operations,
        (SELECT GROUP_CONCAT(pao.kode_brng) FROM pemberian_obat pao WHERE pao.no_rawat = rp.no_rawat) as drugs,
        (SELECT imunisasi FROM penilaian_awal_keperawatan_kebidanan pkk WHERE pkk.no_rawat = rp.no_rawat LIMIT 1) as imunisasi_pkk
    FROM reg_periksa rp
    INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    LEFT JOIN rujuk_masuk rm ON rp.no_rawat = rm.no_rawat
    LEFT JOIN diagnosa_pasien dp ON rp.no_rawat = dp.no_rawat
    WHERE rp.tgl_registrasi BETWEEN ? AND ?
    AND rp.stts != 'Batal' AND p.jk = 'P'
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
        $ops = strtoupper($row['operations'] ?? '');
        $drugs = $row['drugs'] ?? '';
        
        $cat = []; // Kategori yg terpenuhi
        
        $has_preg_code = false;
        $is_persalinan = false;
        $is_antenatal = false;
        $is_nifas = false;
        $is_komp_obs = false;
        $is_komp_non = false;
        $is_aborsi = false;

        // Check Diagnosis
        foreach($arr_diag as $icd) {
            $prefix = substr(trim($icd), 0, 3);
            if ($prefix == '') continue;

            if (strpos($prefix, 'O') === 0 || $prefix == 'Z34' || $prefix == 'Z35' || $prefix == 'Z39') {
                $has_preg_code = true;
            }

            // Antenatal
            if ($prefix == 'Z34' || $prefix == 'Z35') { $cat['2'] = true; $is_antenatal = true; }
            
            // Persalinan
            if ($prefix >= 'O80' && $prefix <= 'O84') {
                $is_persalinan = true;
                if ($prefix == 'O80') $cat['3.1'] = true;
                else if ($prefix == 'O81' || $prefix == 'O83') $cat['3.3'] = true;
                else if ($prefix == 'O82') $cat['3.4'] = true;
                else if ($prefix == 'O84') $cat['3.2'] = true;
            }

            // Nifas
            if ($prefix == 'Z39') { $cat['9'] = true; $is_nifas = true; }

            // Komplikasi Obstetri
            if ($prefix == 'O46') { $cat['4.1'] = true; $is_komp_obs = true; }
            else if ($prefix == 'O72') { $cat['4.2'] = true; $is_komp_obs = true; }
            else if ($prefix == 'O14') { $cat['4.3'] = true; $is_komp_obs = true; }
            else if ($prefix == 'O15') { $cat['4.4'] = true; $is_komp_obs = true; }
            else if ($prefix == 'O86' || substr($icd,0,4) == 'O411' || $prefix == 'O23') { $cat['4.5'] = true; $is_komp_obs = true; }
            else if ($prefix >= 'O00' && $prefix <= 'O08' && $prefix != 'O04') { $cat['4.6'] = true; $is_komp_obs = true; }
            else if (strpos($prefix, 'O') === 0 && !($prefix >= 'O80' && $prefix <= 'O84') && $prefix != 'O04') {
                // If not already flagged by specific ones
                $is_komp_obs = true; 
            }

            // Aborsi
            if ($prefix == 'O04') { $cat['5.1'] = true; $is_aborsi = true; }

            // Komplikasi Non-Obs
            if ($prefix >= 'B20' && $prefix <= 'B24') { $cat['7.1'] = true; $is_komp_non = true; }
            else if ($prefix >= 'B15' && $prefix <= 'B19') { $cat['7.2'] = true; $is_komp_non = true; }
            else if ($prefix >= 'A50' && $prefix <= 'A53') { $cat['7.3'] = true; $is_komp_non = true; }
            else if ($prefix >= 'A15' && $prefix <= 'A19') { $cat['7.4'] = true; $is_komp_non = true; }
            else if ($prefix >= 'I00' && $prefix <= 'I99') { $cat['7.5'] = true; $is_komp_non = true; }
            else if ($prefix >= 'D50' && $prefix <= 'D64') { $cat['7.6'] = true; $is_komp_non = true; }
            else if ($prefix >= 'E10' && $prefix <= 'E14') { $cat['7.7'] = true; $is_komp_non = true; }
            else if ($prefix == 'U07' || substr($icd,0,4) == 'U071' || substr($icd,0,4) == 'U072') { $cat['7.8'] = true; $is_komp_non = true; }
            
            // Risiko Prematur
            if ($prefix == 'O60') {
                if (strpos($drugs, 'B000006082') !== false) { // Dexamethasone Inj
                    $cat['8.1'] = true;
                } else {
                    $cat['8.2'] = true;
                }
            }
        }

        // SC from Operations
        if (strpos($ops, 'SECTIO') !== false || strpos($ops, 'SC') !== false) {
            $cat['3.4'] = true;
            $is_persalinan = true;
        }

        // Catch All "Lainnya" for Obs
        if ($is_komp_obs) {
            $specific_obs = ['4.1', '4.2', '4.3', '4.4', '4.5', '4.6'];
            $found_specific = false;
            foreach($specific_obs as $sk) if(isset($cat[$sk])) $found_specific = true;
            if (!$found_specific) $cat['4.7'] = true;
        }

        // Catch All "Lainnya" for Non-Obs
        // If has O/Z code and HAS a non-O/Z code
        if ($has_preg_code) {
            foreach($arr_diag as $icd) {
                $p = substr(trim($icd), 0, 1);
                if ($p != 'O' && $p != 'Z' && $p != '') {
                    $is_komp_non = true;
                    $specific_non = ['7.1', '7.2', '7.3', '7.4', '7.5', '7.6', '7.7', '7.8'];
                    $found_specific = false;
                    foreach($specific_non as $sk) if(isset($cat[$sk])) $found_specific = true;
                    if (!$found_specific) $cat['7.9'] = true;
                    break;
                }
            }
        }

        // Skrining Tetanus (Indicator 6)
        if ($row['imunisasi_pkk'] == 'Ya') {
            $cat['6'] = true;
        }

        // Vitamin A (Indicator 10)
        $vit_a_codes = ['B0000100060', 'B00001000612', 'APT0005791', 'B00001000601'];
        foreach($vit_a_codes as $vc) {
            if (strpos($drugs, $vc) !== false) {
                $cat['10'] = true;
                break;
            }
        }

        // Buku KIA (Indicator 1) - Proxy: First ANC visit
        if ($is_antenatal && $row['stts_daftar'] == 'Baru') {
            $cat['1'] = true;
        }

        // If no category triggered, skip
        if (empty($cat)) continue;

        // -------------
        // Identifikasi Rujukan & Outcome
        // -------------
        $perujuk = strtolower($row['perujuk']);
        $is_rujukan_medis = false;
        $is_rujukan_non = false;
        $src_rs = false; $src_bidan = false; $src_pkm = false; $src_faskes = false;
        
        if (!empty($row['no_rujuk'])) {
            if (preg_match('/polisi|hukum|jaksa|pengadilan/', $perujuk)) {
                $is_rujukan_non = true;
            } else {
                $is_rujukan_medis = true;
                if (preg_match('/rs|rumah sakit/', $perujuk)) $src_rs = true;
                else if (preg_match('/bidan|bpm|amd.keb/', $perujuk)) $src_bidan = true;
                else if (preg_match('/pkm|puskesmas/', $perujuk)) $src_pkm = true;
                else $src_faskes = true;
            }
        }
        
        $stts_pulang = strtolower($row['stts_pulang']);
        $lanjut = strtolower($row['status_lanjut']);
        $is_mati = (preg_match('/meninggal|mati/', $stts_pulang) || !empty($row['pm_mati']));
        $is_dirujuk = (preg_match('/rujuk/', $stts_pulang) || preg_match('/rujuk/', $lanjut));
        
        // Populate Result
        foreach ($cat as $k => $val) {
            if ($is_rujukan_medis) {
                if ($src_rs) $result_data[$k]['r_medis_rs']++;
                else if ($src_bidan) $result_data[$k]['r_medis_bidan']++;
                else if ($src_pkm) $result_data[$k]['r_medis_pkm']++;
                else $result_data[$k]['r_medis_faskes_lain']++;
                
                if ($is_mati) $result_data[$k]['r_medis_mati']++;
                else $result_data[$k]['r_medis_hidup']++;
                $result_data[$k]['total_rm']++;
            } else if ($is_rujukan_non) {
                if ($is_mati) $result_data[$k]['r_non_medis_mati']++;
                else $result_data[$k]['r_non_medis_hidup']++;
                $result_data[$k]['total_rnm']++;
            } else {
                if ($is_mati) $result_data[$k]['non_rujukan_mati']++;
                else $result_data[$k]['non_rujukan_hidup']++;
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
