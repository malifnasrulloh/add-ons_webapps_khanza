<?php
// File: api/save_grouper.php
error_reporting(0);
ini_set('display_errors', 0);

require_once(__DIR__ . '/../csrf.php');

if(file_exists(__DIR__ . '/../../conf/conf.php')) {
    require_once(__DIR__ . '/../../conf/conf.php');
} else {
    require_once(__DIR__ . '/../conf/conf.php');
}

header('Content-Type: application/json');

$koneksi = bukakoneksi();
if (!$koneksi) {
    echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal']);
    exit;
}

if (!isset($_SESSION['casemix_login'])) {
    // Tetap 200 agar ditangkap AJAX success tapi status error
    echo json_encode(['status' => 'error', 'message' => 'Sesi habis. Silakan login ulang.']);
    exit;
}

$caseId = isset($_POST['case']) ? str_replace('-', '/', $_POST['case']) : '';
$kode   = isset($_POST['kode']) ? trim($_POST['kode']) : '';
$tarif  = isset($_POST['tarif']) ? floatval($_POST['tarif']) : 0;

if (empty($caseId)) {
    echo json_encode(['status' => 'error', 'message' => 'No. Rawat tidak valid']);
    exit;
}

if ($tarif <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Nominal harus lebih dari 0']);
    exit;
}

// Cek Existing
$cek = mysqli_query($koneksi, "SELECT no_rawat FROM perkiraan_biaya_ranap WHERE no_rawat='$caseId'");

if (mysqli_num_rows($cek) > 0) {
    $q = "UPDATE perkiraan_biaya_ranap SET kd_penyakit=?, tarif=? WHERE no_rawat=?";
    $stmt = mysqli_prepare($koneksi, $q);
    mysqli_stmt_bind_param($stmt, "sds", $kode, $tarif, $caseId);
} else {
    $q = "INSERT INTO perkiraan_biaya_ranap (kd_penyakit, tarif, no_rawat) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $q);
    mysqli_stmt_bind_param($stmt, "sds", $kode, $tarif, $caseId);
}

if ($stmt && mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'success', 'message' => 'Data tersimpan!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . mysqli_error($koneksi)]);
}

mysqli_close($koneksi);
?>