<?php
/*
 * File: api/data_rl_3_12.php
 * Fungsi: API Laporan RL 3.12 Rekapitulasi Kegiatan Pelayanan Pembedahan
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

// 16 Spesialisasi Juknis RL 3.12
$spesialisasi_list = [
    'Bedah', 'Obstetri dan Ginekologi', 'Bedah Saraf (Non Stroke)', 'THT', 'Mata',
    'Kulit dan Kelamin', 'Gigi dan Mulut', 'Bedah Anak', 'Kardiovaskular',
    'Bedah Orthopedi', 'Thoraks', 'Digestif', 'Urologi', 'Bedah Saraf (Stroke)',
    'Kanker', 'Lain-lain'
];

$result_data = [];
foreach ($spesialisasi_list as $sp) {
    $result_data[$sp] = [
        'spesialisasi' => $sp,
        'khusus' => 0, 'besar' => 0, 'sedang' => 0, 'kecil' => 0, 'total' => 0
    ];
}

function mapSpsToBedahSpesialisasi($nm_sps) {
    $n = strtoupper($nm_sps);
    if (strpos($n, 'BEDAH') !== false) {
        if (strpos($n, 'SARAF') !== false) return 'Bedah Saraf (Non Stroke)';
        if (strpos($n, 'ANAK') !== false) return 'Bedah Anak';
        if (strpos($n, 'ORTHO') !== false) return 'Bedah Orthopedi';
        if (strpos($n, 'THORAK') !== false || strpos($n, 'KARDIO') !== false) return 'Kardiovaskular';
        if (strpos($n, 'DIGESTIF') !== false) return 'Digestif';
        if (strpos($n, 'UROLOGI') !== false) return 'Urologi';
        if (strpos($n, 'MULUT') !== false) return 'Gigi dan Mulut';
        return 'Bedah';
    }
    if (strpos($n, 'KANDUNGAN') !== false || strpos($n, 'OBG') !== false || strpos($n, 'KEBIDANAN') !== false) return 'Obstetri dan Ginekologi';
    if (strpos($n, 'THT') !== false) return 'THT';
    if (strpos($n, 'MATA') !== false) return 'Mata';
    if (strpos($n, 'KULIT') !== false || strpos($n, 'KELAMIN') !== false || strpos($n, 'DV') !== false) return 'Kulit dan Kelamin';
    if (strpos($n, 'GIGI') !== false || strpos($n, 'MULUT') !== false) return 'Gigi dan Mulut';
    if (strpos($n, 'ONKOLOGI') !== false || strpos($n, 'KANKER') !== false) return 'Kanker';
    
    return 'Lain-lain';
}

$sql = "
    SELECT op.kategori, s.nm_sps, COUNT(*) as qty
    FROM operasi op
    INNER JOIN dokter d ON op.operator1 = d.kd_dokter
    INNER JOIN spesialis s ON d.kd_sps = s.kd_sps
    WHERE op.tgl_operasi BETWEEN ? AND ?
    GROUP BY op.kategori, s.nm_sps
";

$stmt = mysqli_prepare($koneksi, $sql);
$tgl_awal_full = $tgl_awal . " 00:00:00";
$tgl_akhir_full = $tgl_akhir . " 23:59:59";
mysqli_stmt_bind_param($stmt, "ss", $tgl_awal_full, $tgl_akhir_full);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($res)) {
    $sp = mapSpsToBedahSpesialisasi($row['nm_sps']);
    $kat = strtolower($row['kategori']);
    $qty = $row['qty'];

    if (isset($result_data[$sp])) {
        if (strpos($kat, 'khusus') !== false) $result_data[$sp]['khusus'] += $qty;
        else if (strpos($kat, 'besar') !== false) $result_data[$sp]['besar'] += $qty;
        else if (strpos($kat, 'sedang') !== false) $result_data[$sp]['sedang'] += $qty;
        else if (strpos($kat, 'kecil') !== false) $result_data[$sp]['kecil'] += $qty;
        
        $result_data[$sp]['total'] += $qty;
    }
}

echo json_encode(['data' => array_values($result_data)]);
mysqli_close($koneksi);
?>
