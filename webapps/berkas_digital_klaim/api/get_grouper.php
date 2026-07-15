<?php
// File: api/get_grouper.php
// Matikan error reporting di production agar JSON tidak rusak, 
// TAPI saat debug Error 500, kita nyalakan sebentar jika masih error.
error_reporting(0); 
ini_set('display_errors', 0);

// [PERBAIKAN FATAL DISINI]
// Mundur 2 langkah: dari 'api' -> 'berkas_digital_klaim' -> 'webapps' -> masuk 'conf'
if(file_exists(__DIR__ . '/../../conf/conf.php')) {
    require_once(__DIR__ . '/../../conf/conf.php');
} else {
    // Fallback jika struktur folder berbeda (misal folder conf ada di dalam modul)
    require_once(__DIR__ . '/../conf/conf.php');
}

header('Content-Type: application/json; charset=utf-8');

// Cek koneksi sebelum sesi
$koneksi = bukakoneksi(); 
if (!$koneksi) {
    http_response_code(500);
    echo json_encode(['results' => [['id' => '', 'text' => 'Database Connection Failed']]]);
    exit;
}

session_start();
if (!isset($_SESSION['casemix_login'])) {
    echo json_encode(['results' => [['id' => '', 'text' => 'Sesi habis, silakan login ulang']]]);
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$results = [];

// 1. LOGIKA INPUT MANUAL
// Hapus titik/koma agar jadi angka murni (5.000.000 -> 5000000)
$clean_num = str_replace(['.', ','], '', $search);

if (is_numeric($clean_num) && $clean_num > 0) {
    $results[] = [
        'id' => $clean_num . ':Manual Input', 
        'text' => 'Manual: Rp ' . number_format($clean_num, 0, ',', '.') . ' (Gunakan Angka Ini)',
        'selected' => true
    ];
}

// 2. DATABASE
if (!empty($search)) {
    // Pastikan nama tabel benar (grouper_bpjs)
    $q = "SELECT grouper as kd_penyakit, nominal as tarif FROM grouper_bpjs 
          WHERE grouper LIKE ? OR nominal LIKE ? LIMIT 20";

    $stmt = mysqli_prepare($koneksi, $q);
    if ($stmt) {
        $param = "%$search%";
        mysqli_stmt_bind_param($stmt, "ss", $param, $param);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($res)) {
            $results[] = [
                'id' => $row['tarif'] . ':' . $row['kd_penyakit'],
                'text' => $row['kd_penyakit'] . ' - Rp ' . number_format($row['tarif'], 0, ',', '.')
            ];
        }
    }
} else {
    $results[] = ['id' => '', 'text' => 'Ketik kode grouper atau nominal...'];
}

echo json_encode(['results' => $results]);
?>