<?php
/*
 * File: api/data_rl_3_17.php
 * Fungsi: API Laporan RL 3.17 Rekapitulasi Kegiatan Pelayanan Farmasi RS - Pengadaan Obat
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

// 4 Golongan Obat Juknis RL 3.17
$golongan_list = [
    'Obat Generik Formularium Nasional',
    'Obat Generik Non Formularium Nasional',
    'Obat Non Generik Formularium Nasional',
    'Obat Non Generik Non Formularium Nasional'
];

$result_data = [];
foreach ($golongan_list as $g) {
    $result_data[$g] = ['golongan' => $g, 'jumlah_item' => 0, 'jumlah_tersedia' => 0];
}

// Logic: Aggregating from databarang and gudangbarang
// Categorization based on common keywords if specific flags not found.

$sql = "
    SELECT 
        db.nama_brng,
        (SELECT SUM(stok) FROM gudangbarang WHERE kode_brng = db.kode_brng) as total_stok
    FROM databarang db
    WHERE db.status = '1'
";

$res = mysqli_query($koneksi, $sql);

while ($row = mysqli_fetch_assoc($res)) {
    $n = strtoupper($row['nama_brng']);
    $stok = $row['total_stok'] ?? 0;
    
    $is_generik = (strpos($n, 'GENERIK') !== false || strpos($n, 'FORNAS') === false); // Simplified logic
    $is_fornas = (strpos($n, 'FORNAS') !== false);

    $target = '';
    if ($is_generik && $is_fornas) $target = 'Obat Generik Formularium Nasional';
    else if ($is_generik && !$is_fornas) $target = 'Obat Generik Non Formularium Nasional';
    else if (!$is_generik && $is_fornas) $target = 'Obat Non Generik Formularium Nasional';
    else $target = 'Obat Non Generik Non Formularium Nasional';

    if (isset($result_data[$target])) {
        $result_data[$target]['jumlah_item']++;
        if ($stok > 0) {
            $result_data[$target]['jumlah_tersedia']++;
        }
    }
}

echo json_encode(['data' => array_values($result_data)]);
mysqli_close($koneksi);
?>
