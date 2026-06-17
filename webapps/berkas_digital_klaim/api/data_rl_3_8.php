<?php
/*
 * File: api/data_rl_3_8.php
 * Fungsi: Laporan RL 3.8 Rekapitulasi Kegiatan Pelayanan Laboratorium
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

// Data structure
$keys = [
    '1.1', '1.2', '1.3', '1.4', '1.5', '1.6', '1.7', '1.8', '1.9',
    '2.1', '2.2', '2.3', '2.4', '2.5', '2.6', '2.7', '2.8', '2.9', '2.10',
    '2.11', '2.12', '2.13', '2.14', '2.15', '2.16', '2.17', '2.18', '2.19', '2.20',
    '2.21', '2.22', '2.23', '2.24', '2.25', '2.26',
    '3.1', '3.2', '3.3', '3.4', '3.5', '3.6', '3.7', '3.8', '3.9', '3.10',
    '3.11', '3.12', '3.13', '3.14', '3.15', '3.16', '3.17',
    '4.1', '4.2', '4.3', '4.4', '4.5',
    '5.1', '5.2', '5.3', '5.4', '5.5', '5.6', '5.7', '5.8',
    '6.1', '6.2', '6.3', '6.4', '6.5', '6.6',
    '7', '8', '9',
    '10.1', '10.2', '10.3', '10.4', '10.5', '10.6',
    '11.1', '11.2', '11.3', '11.4', '11.5', '11.6', '11.7', '11.8',
    '12.1', '12.2', '12.3',
    '13.1', '14.1',
    '15.1', '15.2', '15.3', '15.4',
    '16.1', '16.2', '16.3',
    '17', 
    '18.1', '18.2', '18.3',
    '19.1', '19.2', '19.3',
    '20.1', '20.2',
    '21.1', '22', '23'
];

$result_data = [];
$default_cols = [
    'jml_l'=>0, 'jml_p'=>0, 'total_val_l'=>0, 'total_val_p'=>0, 'avg_l'=>0, 'avg_p'=>0
];

foreach ($keys as $k) {
    if(strpos($k, '.') !== false) {
        $parent = explode('.', $k)[0];
        if(!isset($result_data[$parent])) {
            // Placeholder parent if needed
        }
    }
    $result_data[$k] = $default_cols;
}

// Format Nilai (Ubah koma jadi titik buat desimal kalo di database pakai koma string dll)
function parseGiziVal($str) {
    $str = str_replace(',', '.', trim($str));
    // Check if numeric after cleaning
    if (is_numeric($str)) {
        return (float) $str;
    }
    return null;
}

// 1. Ambil data periksa lab detail & PK/PA/MB (Di SIK Khanza, table detail_periksa_lab merujuk lab PK)
// Kita hubungkan ke template_laboratorium & rekam medis pasien utk jenis kelamin.

$sql = "
    SELECT 
        d.nilai,
        d.keterangan,
        t.Pemeriksaan,
        p.jk
    FROM detail_periksa_lab d
    INNER JOIN template_laboratorium t ON d.id_template = t.id_template
    INNER JOIN reg_periksa rp ON d.no_rawat = rp.no_rawat
    INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    WHERE d.tgl_periksa BETWEEN ? AND ?
";

$stmt = mysqli_prepare($koneksi, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $tgl_awal, $tgl_akhir);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($res)) {
        $val_str = trim($row['nilai']);
        $val_num = parseGiziVal($val_str);
        $nama_pem = strtolower(trim($row['Pemeriksaan']));
        $jk = $row['jk']; // L atau P
        
        $cat = '';
        
        // =======================
        // A. PATOLOGI KLINIK 
        // =======================
        
        // 1. Hematologi
        if (strpos($nama_pem, 'hemoglobin') !== false || $nama_pem == 'hb' || $nama_pem == 'hgb') $cat = '1.1';
        else if (strpos($nama_pem, 'hematokrit') !== false || $nama_pem == 'ht' || strpos($nama_pem, 'hct') !== false) $cat = '1.2';
        else if (strpos($nama_pem, 'lekosit') !== false || $nama_pem == 'wbc' || strpos($nama_pem, 'leukosit') !== false) $cat = '1.3';
        else if (strpos($nama_pem, 'eritrosit') !== false || $nama_pem == 'rgb' || strpos($nama_pem, 'rbc') !== false) $cat = '1.4';
        else if (strpos($nama_pem, 'eosinop') !== false) $cat = '1.5';
        else if (strpos($nama_pem, 'jenis') !== false && strpos($nama_pem, 'leko') !== false) $cat = '1.6'; // Diff count
        else if (strpos($nama_pem, 'endap darah') !== false || $nama_pem == 'led' || $nama_pem == 'esr') $cat = '1.7';
        else if (strpos($nama_pem, 'retik') !== false) $cat = '1.8';
        else if (strpos($nama_pem, 'trombosit') !== false || $nama_pem == 'plt') $cat = '1.9';
        
        // 2. Kimia Klinik
        else if (strpos($nama_pem, 'protein total') !== false) $cat = '2.1';
        else if (strpos($nama_pem, 'albumin') !== false) $cat = '2.2';
        else if (strpos($nama_pem, 'globulin') !== false) $cat = '2.3';
        else if (strpos($nama_pem, 'bilirubin') !== false) $cat = '2.4';
        else if (strpos($nama_pem, 'sgot') !== false || strpos($nama_pem, 'ast') !== false) $cat = '2.5';
        else if (strpos($nama_pem, 'sgpt') !== false || strpos($nama_pem, 'alt') !== false) $cat = '2.6';
        else if (strpos($nama_pem, 'ureum') !== false || strpos($nama_pem, 'bun') !== false) $cat = '2.7';
        else if (strpos($nama_pem, 'kreatinin') !== false) $cat = '2.8';
        else if (strpos($nama_pem, 'asam urat') !== false) $cat = '2.9';
        else if (strpos($nama_pem, 'trigliserida') !== false) $cat = '2.10';
        else if ($nama_pem == 'kolesterol total' || $nama_pem == 'kolesterol') $cat = '2.11';
        else if (strpos($nama_pem, 'hdl') !== false) $cat = '2.12';
        else if (strpos($nama_pem, 'ldl') !== false) $cat = '2.13';
        else if (strpos($nama_pem, 'glukosa') !== false || strpos($nama_pem, 'gula darah') !== false) $cat = '2.14';
        else if (strpos($nama_pem, 'hba1c') !== false) $cat = '2.15';
        else if (strpos($nama_pem, 'alkali') !== false) $cat = '2.16';
        else if (strpos($nama_pem, 'gamma') !== false) $cat = '2.17';
        else if ($nama_pem == 'ldh') $cat = '2.18';
        else if (strpos($nama_pem, 'g 6 pd') !== false) $cat = '2.19';
        else if (strpos($nama_pem, 'amilase') !== false) $cat = '2.20';
        else if (strpos($nama_pem, 'lipase') !== false) $cat = '2.21';
        else if (strpos($nama_pem, 'cholinesterase') !== false) $cat = '2.22';
        else if (strpos($nama_pem, 'ck') !== false) $cat = '2.23';
        else if (strpos($nama_pem, 'tibc') !== false) $cat = '2.24';
        else if (strpos($nama_pem, 'natrium') !== false || strpos($nama_pem, 'kalium') !== false || strpos($nama_pem, 'chlorida') !== false || strpos($nama_pem, 'elektrolit') !== false) $cat = '2.25';
        else if (strpos($nama_pem, 'gas darah') !== false || $nama_pem == 'agd' || $nama_pem == 'bga') $cat = '2.26';
        
        // 3. Imunologi Klinik
        else if (strpos($nama_pem, 'widal') !== false || strpos($nama_pem, 'salmonella') !== false) $cat = '3.1';
        else if (strpos($nama_pem, 'anti sars') !== false || strpos($nama_pem, 'antibodi sars') !== false) $cat = '3.2';
        else if (strpos($nama_pem, 'antigen sars') !== false || strpos($nama_pem, 'swab ag') !== false) $cat = '3.3';
        else if (strpos($nama_pem, 'dengue') !== false && (strpos($nama_pem, 'igg') !== false || strpos($nama_pem, 'igm') !== false)) $cat = '3.4';
        else if (strpos($nama_pem, 'hbs ag') !== false || strpos($nama_pem, 'hbsag') !== false) $cat = '3.5';
        else if (strpos($nama_pem, 'anti hbs') !== false) $cat = '3.6';
        else if (strpos($nama_pem, 'anti hbc') !== false) $cat = '3.7';
        else if (strpos($nama_pem, 'anti hbe') !== false) $cat = '3.8';
        else if (strpos($nama_pem, 'hbe ag') !== false) $cat = '3.9';
        else if (strpos($nama_pem, 'anti hcv') !== false || strpos($nama_pem, 'hcv') !== false) $cat = '3.10';
        else if (strpos($nama_pem, 'anti hav') !== false || strpos($nama_pem, 'hav') !== false) $cat = '3.11';
        else if (strpos($nama_pem, 'hiv') !== false) $cat = '3.12';
        else if (strpos($nama_pem, 'ns1') !== false) $cat = '3.13';
        else if (strpos($nama_pem, 'malaria') !== false) $cat = '3.14';
        else if (strpos($nama_pem, 't3') !== false || strpos($nama_pem, 't4') !== false) $cat = '3.15';
        else if (strpos($nama_pem, 'tsh') !== false) $cat = '3.17';
        
        // 4. Urinalisis
        else if (strpos($nama_pem, 'protein urin') !== false || strpos($nama_pem, 'albumin urin') !== false) $cat = '4.1';
        else if (strpos($nama_pem, 'urobilinogen') !== false) $cat = '4.2';
        else if (strpos($nama_pem, 'bilirubin urin') !== false) $cat = '4.3';
        else if (strpos($nama_pem, 'sedimen') !== false) $cat = '4.4';
        else if (strpos($nama_pem, 'napza') !== false || strpos($nama_pem, 'narkoba') !== false || strpos($nama_pem, 'amphetamin') !== false) $cat = '4.5';
        
        // 5. Hemostasis
        else if (strpos($nama_pem, 'masa perdarahan') !== false || $nama_pem == 'bt') $cat = '5.1';
        else if (strpos($nama_pem, 'masa pembekuan') !== false || $nama_pem == 'ct') $cat = '5.2';
        else if (strpos($nama_pem, 'prothrombin') !== false || $nama_pem == 'pt') $cat = '5.3';
        else if (strpos($nama_pem, 'aptt') !== false) $cat = '5.4';
        else if (strpos($nama_pem, 'thrombin') !== false) $cat = '5.5';
        else if (strpos($nama_pem, 'fibrinogen') !== false) $cat = '5.6';
        else if (strpos($nama_pem, 'd-dimer') !== false) $cat = '5.7';
        else if (strpos($nama_pem, 'lupus') !== false) $cat = '5.8';

        // =======================
        // B. MIKROBIOLOGI / C. PARASITOLOGI
        // (Beberapa rs menggabungkannya di PK, bbrp di MB) - Kita baca dari PK dulu sbg fallback
        // =======================
        if (empty($cat)) {
            if (strpos($nama_pem, 'bta') !== false || strpos($nama_pem, 'tbc') !== false || strpos($nama_pem, 'dahak') !== false) {
                 // 6. Mikroskopis TBC BTA
                 $v = strtolower($val_str);
                 if (strpos($v, 'negatif') !== false || $v == '-') $cat = '6.1';
                 else if (strpos($v, '1-9') !== false) $cat = '6.2';
                 else if (strpos($v, '1+') !== false || $v == '+1') $cat = '6.3';
                 else if (strpos($v, '2+') !== false || $v == '+2') $cat = '6.4';
                 else if (strpos($v, '3+') !== false || $v == '+3') $cat = '6.5';
                 else $cat = '6.6'; // Tidak Dilakukan / lain
                 
                 // If TCM (TBC RO) - usually string matches differently
                 if (strpos($nama_pem, 'tcm') !== false || strpos($nama_pem, 'genexpert') !== false) {
                    if (strpos($v, 'negatif') !== false || strpos($v, 'not detected') !== false) $cat = '11.1';
                    else if (strpos($v, 'rif sen') !== false || (strpos($v, 'detected') !== false && strpos($v, 'not resistance') !== false)) $cat = '11.2';
                    else if (strpos($v, 'rif res') !== false || (strpos($v, 'detected') !== false && strpos($v, 'resistance detected') !== false)) $cat = '11.3';
                    else if (strpos($v, 'invalid') !== false) $cat = '11.5';
                    else if (strpos($v, 'error') !== false) $cat = '11.6';
                    else $cat = '11.8';
                 }
            }
            else if (strpos($nama_pem, 'pcr sars') !== false || strpos($nama_pem, 'pcr covid') !== false) $cat = '10.2'; // PCR Covid
        }

        // Perekaman
        if (!empty($cat) && isset($result_data[$cat])) {
            
            // Increment Count
            if ($jk == 'L') {
                $result_data[$cat]['jml_l']++;
                if ($val_num !== null) $result_data[$cat]['total_val_l'] += $val_num;
            } else {
                $result_data[$cat]['jml_p']++;
                if ($val_num !== null) $result_data[$cat]['total_val_p'] += $val_num;
            }
        }
        
    }
    mysqli_stmt_close($stmt);
}

// 2. Ambil data dari detail_periksa_labpa (Patologi Anatomi) jika menggunakan modul PA Khanza
$sql_pa = "
    SELECT 
        d.diagnosa_klinis as keterangan,
        t.Pemeriksaan,
        p.jk
    FROM detail_periksa_labpa d
    INNER JOIN template_laboratorium t ON d.id_template = t.id_template
    INNER JOIN reg_periksa rp ON d.no_rawat = rp.no_rawat
    INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    WHERE d.tgl_periksa BETWEEN ? AND ?
";
$stmt_pa = mysqli_prepare($koneksi, $sql_pa);
if ($stmt_pa) {
    mysqli_stmt_bind_param($stmt_pa, "ss", $tgl_awal, $tgl_akhir);
    mysqli_stmt_execute($stmt_pa);
    $res_pa = mysqli_stmt_get_result($stmt_pa);
    while ($row_pa = mysqli_fetch_assoc($res_pa)) {
        $nama_pem = strtolower(trim($row_pa['Pemeriksaan']));
        $jk = $row_pa['jk'];
        $cat = '';
        
        // D. PATOLOGI ANATOMI
        if (strpos($nama_pem, 'sitopatologi') !== false) {
            if (strpos($nama_pem, 'pap') !== false) $cat = '18.1';
            else if (strpos($nama_pem, 'non gin') !== false) $cat = '18.2';
            else if (strpos($nama_pem, 'cairan') !== false) $cat = '18.3';
        } 
        else if (strpos($nama_pem, 'histopatologi') !== false) {
            if (strpos($nama_pem, 'kecil') !== false) $cat = '19.1';
            else if (strpos($nama_pem, 'sedang') !== false) $cat = '19.2';
            else if (strpos($nama_pem, 'besar') !== false) $cat = '19.3';
        }
        else if (strpos($nama_pem, 'fnab') !== false || strpos($nama_pem, 'aspirasi jarum') !== false) $cat = '17';
        else if (strpos($nama_pem, 'potong beku') !== false || strpos($nama_pem, 'vries') !== false) $cat = '22';
        else if (strpos($nama_pem, 'otopsi') !== false) $cat = '23';

        if (!empty($cat) && isset($result_data[$cat])) {
            if ($jk == 'L') $result_data[$cat]['jml_l']++;
            else $result_data[$cat]['jml_p']++;
        }
    }
    mysqli_stmt_close($stmt_pa);
}


// Finalize Averages
foreach ($result_data as $cat => $data) {
    if ($data['jml_l'] > 0) {
        $result_data[$cat]['avg_l'] = round($data['total_val_l'] / $data['jml_l'], 2);
    }
    if ($data['jml_p'] > 0) {
        $result_data[$cat]['avg_p'] = round($data['total_val_p'] / $data['jml_p'], 2);
    }
}

echo json_encode(['data' => $result_data]);
?>
