<?php
/*
 * File: api/data_rl_3_3.php
 * Fungsi: Laporan RL 3.3 Rekapitulasi Kegiatan Pelayanan Rawat Darurat
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

// Data Structure for RL 3.3
$keys = [
    'bedah', '1_1', '1_2', '1_3', '1_4', 
    'non_bedah', '2_1', '2_2', '2_3', '2_4', 
    'kebidanan', 'psikiatrik', 'bayi', 'anak', 'geriatri', 'total'
];

$result_data = [];
$default_cols = [
    't_rujukan'=>0, 't_non_rujukan'=>0, 
    'tl_dirawat'=>0, 'tl_dirujuk'=>0, 'tl_pulang'=>0, 
    'mati_l'=>0, 'mati_p'=>0, 'doa_l'=>0, 'doa_p'=>0, 
    'luka_l'=>0, 'luka_p'=>0, 'false_emergency'=>0
];

foreach ($keys as $k) {
    if(!in_array($k, ['1_1', '1_2', '1_3', '1_4', '2_1', '2_2', '2_3', '2_4'])) {
        $result_data[$k] = $default_cols;
    }
}
// Manually initialize sub-categories
foreach(['1_1', '1_2', '1_3', '1_4'] as $k) {
    $result_data[$k] = ['t_rujukan'=>0, 't_non_rujukan'=>0, 'tl_dirawat'=>0, 'tl_dirujuk'=>0, 'tl_pulang'=>0, 'mati_l'=>0, 'mati_p'=>0, 'doa_l'=>0, 'doa_p'=>0, 'luka_l'=>0, 'luka_p'=>0, 'false_emergency'=>0];
}
foreach(['2_1', '2_2', '2_3', '2_4'] as $k) {
    $result_data[$k] = ['t_rujukan'=>0, 't_non_rujukan'=>0, 'tl_dirawat'=>0, 'tl_dirujuk'=>0, 'tl_pulang'=>0, 'mati_l'=>0, 'mati_p'=>0, 'doa_l'=>0, 'doa_p'=>0, 'luka_l'=>0, 'luka_p'=>0, 'false_emergency'=>0];
}

// 1. Ambil data pasien IGD
$sql = "
    SELECT 
        rp.no_rawat,
        p.jk,
        p.tgl_lahir,
        rp.stts_pulang,
        rp.status_lanjut,
        IFNULL(rm.no_rujuk, '') as no_rujuk,
        pm.no_rkm_medis as pm_mati,
        pm.temp_meninggal,
        (SELECT kd_penyakit FROM diagnosa_pasien WHERE no_rawat = rp.no_rawat AND prioritas = 1 LIMIT 1) as icd10_utama
    FROM reg_periksa rp
    INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    LEFT JOIN rujuk_masuk rm ON rp.no_rawat = rm.no_rawat
    LEFT JOIN pasien_mati pm ON p.no_rkm_medis = pm.no_rkm_medis AND pm.tanggal = rp.tgl_registrasi
    WHERE rp.kd_poli = 'IGDK' 
    AND rp.tgl_registrasi BETWEEN ? AND ?
    AND rp.stts != 'Batal'
";

$stmt = mysqli_prepare($koneksi, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $tgl_awal, $tgl_akhir);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($res)) {
        
        $jk = $row['jk'];
        $icd = strtoupper(trim($row['icd10_utama'] ?? ''));
        $icd_prefix = substr($icd, 0, 3);
        
        // Cek Rujukan
        // Jika ada di rujuk_masuk ATAU status_lanjutnya mengindikasikan rujukan dr luar
        $is_rujukan = (!empty($row['no_rujuk'])) ? true : false; 
        
        // Tindak Lanjut & Mati
        $stts_pulang = strtolower($row['stts_pulang']);
        $lanjut = strtolower($row['status_lanjut']);
        
        $is_mati = (strpos($stts_pulang, 'meninggal') !== false || strpos($stts_pulang, 'mati') !== false || !empty($row['pm_mati']));
        $is_doa  = (strpos($stts_pulang, 'doa') !== false || strpos($stts_pulang, 'death on arrival') !== false || ($row['temp_meninggal'] == 'Lain-lain (Termasuk Doa)'));
        
        // Asumsi triase / stts (False emergency, luka, dll)
        $is_false_emergency = (strpos($stts_pulang, 'false') !== false || strpos($stts_pulang, 'hijau') !== false);
        $is_luka = (strpos($stts_pulang, 'luka') !== false || ($icd_prefix >= 'S00' && $icd_prefix <= 'T14'));

        // Cek Umur (Tahun & Bulan)
        $bday = new DateTime($row['tgl_lahir']);
        $today = new DateTime('now');
        $diff = $today->diff($bday);
        $umur_thn = $diff->y;
        $umur_bln = $diff->m + ($umur_thn * 12);
        
        // -------------------------------------------------------------
        // Menentukan Kategori (Kunci Utama Laporan)
        // -------------------------------------------------------------
        $kat = '2_4'; // Default ke Non Bedah Lainnya
        $parent_kat = 'non_bedah';

        if ($umur_bln >= 0 && $umur_bln <= 11) {
            $kat = 'bayi';
            $parent_kat = 'bayi';
        } else if ($umur_thn >= 1 && $umur_thn <= 17) {
            // Cek kekerasan thdp anak (Y07 dsb, V01-Y98)
            if ($icd_prefix >= 'X85' && $icd_prefix <= 'Y09') { // Assault
                $kat = '2_2'; // Kekerasan thdp Anak
                $parent_kat = 'non_bedah';
            } else {
                $kat = 'anak';
                $parent_kat = 'anak';
            }
        } else if ($umur_thn >= 60) {
            $kat = 'geriatri';
            $parent_kat = 'geriatri';
        } else {
            // Dewasa (18 - 59)
            // Cek Kebidanan (O00 - O99)
            if ($icd_prefix >= 'O00' && $icd_prefix <= 'O99') {
                $kat = 'kebidanan';
                $parent_kat = 'kebidanan';
            } 
            // Cek Psikiatrik (F00 - F99)
            else if ($icd_prefix >= 'F00' && $icd_prefix <= 'F99') {
                $kat = 'psikiatrik';
                $parent_kat = 'psikiatrik';
            }
            // Cek Bedah Kecelakaan Lalu Lintas (V01 - V99) dsb.
            // Simplified Mapping for Bedah
            else if ($icd_prefix >= 'V01' && $icd_prefix <= 'V89') {
                $kat = '1_1'; // Kecelakaan LL Darat
                $parent_kat = 'bedah';
            } else if ($icd_prefix >= 'V90' && $icd_prefix <= 'V94') {
                $kat = '1_2'; // LL Perairan
                $parent_kat = 'bedah';
            } else if ($icd_prefix >= 'V95' && $icd_prefix <= 'V97') {
                $kat = '1_3'; // LL Udara
                $parent_kat = 'bedah';
            } else if (($icd_prefix >= 'S00' && $icd_prefix <= 'T14') || ($icd_prefix >= 'W00' && $icd_prefix <= 'X59')) {
                $kat = '1_4'; // Bedah lainnya (non lalin)
                $parent_kat = 'bedah';
            }
            // Non Bedah
            else if ($jk == 'P' && ($icd_prefix >= 'X85' && $icd_prefix <= 'Y09')) {
                $kat = '2_1'; // Kekerasan thdp Perempuan
                $parent_kat = 'non_bedah';
            } else if ($icd_prefix >= 'X85' && $icd_prefix <= 'Y09') {
                $kat = '2_3'; // Kekerasan lainnya (laki-laki dewasa)
                $parent_kat = 'non_bedah';
            } else if ($is_doa) {
                // Aturan: Bagi yang ga bisa pilah DOA, masuk Non Bedah (2_4) -- atau sesuai dx DOA di RS ini
                $kat = '2_4';
                $parent_kat = 'non_bedah';
            } else {
                $kat = '2_4'; // Non Bedah Lainnya
                $parent_kat = 'non_bedah';
            }
        }

        // -------------------------------------------------------------
        // Fungsi Penambah Nilai ke array
        // -------------------------------------------------------------
        $addValues = function($k) use (&$result_data, $is_rujukan, $lanjut, $stts_pulang, $is_mati, $is_doa, $is_false_emergency, $is_luka, $jk) {
            
            if ($is_rujukan) $result_data[$k]['t_rujukan']++;
            else $result_data[$k]['t_non_rujukan']++;
            
            if ($is_doa) {
                if ($jk == 'L') $result_data[$k]['doa_l']++;
                else $result_data[$k]['doa_p']++;
            } else if ($is_mati) {
                if ($jk == 'L') $result_data[$k]['mati_l']++;
                else $result_data[$k]['mati_p']++;
            } else {
                // Hidup
                if (strpos($lanjut, 'ranap') !== false || strpos($stts_pulang, 'rawat') !== false) {
                    $result_data[$k]['tl_dirawat']++;
                } else if (strpos($lanjut, 'rujuk') !== false || strpos($stts_pulang, 'rujuk') !== false) {
                    $result_data[$k]['tl_dirujuk']++;
                } else {
                    $result_data[$k]['tl_pulang']++;
                }
            }
            
            if ($is_luka) {
                if ($jk == 'L') $result_data[$k]['luka_l']++;
                else $result_data[$k]['luka_p']++;
            }
            
            if ($is_false_emergency) {
                $result_data[$k]['false_emergency']++;
            }
        };

        // Add to specific kat
        $addValues($kat);
        
        // Add to parent kat (Bedah/Non Bedah) jika dia sub-kat
        if ($kat != $parent_kat) {
            $addValues($parent_kat);
        }
        
        // Add to Total (99)
        $addValues('total');
    }
    
    // Custom fix for False Emergency di kolom bedah (aturan RL: false emergency HANYA diisi pada selain bedah)
    $result_data['bedah']['false_emergency'] = 0;
    $result_data['1_1']['false_emergency'] = 0;
    $result_data['1_2']['false_emergency'] = 0;
    $result_data['1_3']['false_emergency'] = 0;
    $result_data['1_4']['false_emergency'] = 0;
    
    // Perbaikan total (kurangi jumlah false emergency bedah jika ada yg terbawa di total, meski sdh dicegah di atas)
    $tot_fe = 0;
    foreach(['non_bedah','kebidanan','psikiatrik','bayi','anak','geriatri'] as $g) {
         $tot_fe += $result_data[$g]['false_emergency'];
    }
    $result_data['total']['false_emergency'] = $tot_fe;

    mysqli_stmt_close($stmt);
}

// Convert format
echo json_encode(['data' => $result_data]);
?>
