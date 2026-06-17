<?php
/*
 * File: api/data_rl_3_9.php
 * Fungsi: Laporan RL 3.9 Rekapitulasi Kegiatan Pelayanan Radiologi
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
    '1', '1.1', '1.2', '1.3', '1.4', '1.5', '1.6', '1.7', '1.8', '1.9',
    '2', '2.1', '2.2', '2.3', '2.4',
    '3', '3.1', '3.2', '3.3',
    '4', '4.1', '4.2', '4.3',
    '99'
];

$result_data = [];
foreach ($keys as $k) {
    $result_data[$k] = 0;
}

$sql = "
    SELECT 
        j.nm_perawatan
    FROM periksa_radiologi pr
    INNER JOIN jns_perawatan_radiologi j ON pr.kd_jenis_prw = j.kd_jenis_prw
    WHERE pr.tgl_periksa BETWEEN ? AND ?
    AND pr.status != 'Batal'
";

$stmt = mysqli_prepare($koneksi, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $tgl_awal, $tgl_akhir);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($res)) {
        
        $nama_pem = strtolower(trim($row['nm_perawatan']));
        $cat = '';
        
        // =======================
        // 4. IMAGING/PENCITRAAN
        // =======================
        if (strpos($nama_pem, 'usg') !== false || strpos($nama_pem, 'ultrasonografi') !== false) {
            $cat = '4.1';
            $result_data['4']++;
        }
        else if (strpos($nama_pem, 'mri') !== false || strpos($nama_pem, 'magnetic') !== false) {
            $cat = '4.2';
            $result_data['4']++;
        }
        
        // =======================
        // 1. RADIODIAGNOSTIK
        // =======================
        else if (strpos($nama_pem, 'ct scan') !== false || strpos($nama_pem, 'ct-scan') !== false) {
            $cat = '1.6';
            $result_data['1']++;
        }
        else if (strpos($nama_pem, 'gigi') !== false || strpos($nama_pem, 'panoramik') !== false || strpos($nama_pem, 'dental') !== false || strpos($nama_pem, 'cephalometri') !== false) {
            $cat = '1.5';
            $result_data['1']++;
        }
        else if (strpos($nama_pem, 'kontras') !== false || strpos($nama_pem, 'barium') !== false || strpos($nama_pem, 'bno ivp') !== false || strpos($nama_pem, 'appendicogram') !== false) {
            $cat = '1.2'; // Dengan bahan kontras
            $result_data['1']++;
        }
        else if (strpos($nama_pem, 'fluoroskopi') !== false || strpos($nama_pem, 'fluoroscopy') !== false || strpos($nama_pem, 'c-arm') !== false) {
            $cat = '1.4';
            $result_data['1']++;
        }
        else if (strpos($nama_pem, 'angiografi') !== false || strpos($nama_pem, 'angiography') !== false || strpos($nama_pem, 'cath') !== false) {
            $cat = '1.8';
            $result_data['1']++;
        }
        else if (strpos($nama_pem, 'thorax') !== false || strpos($nama_pem, 'abdomen') !== false || strpos($nama_pem, 'bno') !== false || strpos($nama_pem, 'rontgen') !== false || strpos($nama_pem, 'x-ray') !== false || strpos($nama_pem, 'foto') !== false || strpos($nama_pem, 'pelvis') !== false || strpos($nama_pem, 'ekstremitas') !== false || strpos($nama_pem, 'cruris') !== false || strpos($nama_pem, 'femur') !== false || strpos($nama_pem, 'pedis') !== false || strpos($nama_pem, 'manus') !== false || strpos($nama_pem, 'antebrachii') !== false || strpos($nama_pem, 'humerus') !== false || strpos($nama_pem, 'clavicula') !== false || strpos($nama_pem, 'cervical') !== false || strpos($nama_pem, 'lumbo') !== false || strpos($nama_pem, 'waters') !== false || strpos($nama_pem, 'schadel') !== false) {
            $cat = '1.1'; // Tanpa kontras (Default Rontgen rutin)
            $result_data['1']++;
        }
        
        // =======================
        // 2 & 3. RADIOTERAPI / KEDOKTERAN NUKLIR
        // =======================
        else if (strpos($nama_pem, 'linac') !== false) { $cat = '2.1'; $result_data['2']++; }
        else if (strpos($nama_pem, 'cobalt') !== false) { $cat = '2.2'; $result_data['2']++; }
        else if (strpos($nama_pem, 'brakhiterapi') !== false) { $cat = '2.3'; $result_data['2']++; }
        else if (strpos($nama_pem, 'radioterapi') !== false) { $cat = '2.4'; $result_data['2']++; } // Lain-lain radioterapi
        else if (strpos($nama_pem, 'nuklir diagnostik') !== false || strpos($nama_pem, 'pet scan') !== false) { $cat = '3.1'; $result_data['3']++; }
        else if (strpos($nama_pem, 'nuklir terapi') !== false) { $cat = '3.2'; $result_data['3']++; }
        else if (strpos($nama_pem, 'nuklir') !== false) { $cat = '3.3'; $result_data['3']++; }
        
        // =======================
        // OTHERS
        // =======================
        else {
            $cat = '1.9'; // Masukkan sebagai Radiodiagnostik Lain-lain jika masih di instalasi Radiologi tapi tdk tertebak.
            $result_data['1']++;
        }

        if (!empty($cat)) {
            $result_data[$cat]++;
        }
        
        $result_data['99']++; // Total Seluruh Kegiatan
    }
    
    mysqli_stmt_close($stmt);
}

echo json_encode(['data' => $result_data]);
?>
