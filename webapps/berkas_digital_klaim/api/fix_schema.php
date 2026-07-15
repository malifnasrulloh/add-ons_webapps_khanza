<?php
// File: api/fix_schema.php
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

if (!isset($_SESSION['casemix_login']) || $_SESSION['casemix_role'] !== 'Super Admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Hanya untuk Super Admin.']);
    exit;
}

// Cari nama constraint yang mereferensi tabel penyakit dari kolom kd_penyakit
$sql_find = "SELECT CONSTRAINT_NAME 
             FROM information_schema.KEY_COLUMN_USAGE 
             WHERE TABLE_NAME = 'perkiraan_biaya_ranap' 
             AND COLUMN_NAME = 'kd_penyakit' 
             AND REFERENCED_TABLE_NAME = 'penyakit'";

$res_find = mysqli_query($koneksi, $sql_find);
$found = 0;
$errors = [];

if ($res_find && mysqli_num_rows($res_find) > 0) {
    while ($row = mysqli_fetch_assoc($res_find)) {
        $cname = $row['CONSTRAINT_NAME'];
        $sql_alter = "ALTER TABLE perkiraan_biaya_ranap DROP FOREIGN KEY $cname";
        if (mysqli_query($koneksi, $sql_alter)) {
            $found++;
        } else {
            $errors[] = mysqli_error($koneksi);
        }
    }
}

// Juga pastikan kolom kd_penyakit bisa menerima input bebas (varchar 15 biasanya sudah cukup, tapi kita hilangkan restriksi integritasnya)
// Query di atas sudah cukup untuk melepas constraint.

if ($found > 0) {
    echo json_encode(['status' => 'success', 'message' => "Berhasil melepas $found constraint penyakit. Sekarang Anda bisa memasukkan kode grouper manual."]);
} else {
    if (empty($errors)) {
        echo json_encode(['status' => 'info', 'message' => "Tidak ditemukan constraint penyakit pada tabel perkiraan_biaya_ranap. Tabel sudah siap digunakan."]);
    } else {
        echo json_encode(['status' => 'error', 'message' => "Gagal melepas constraint: " . implode(", ", $errors)]);
    }
}

mysqli_close($koneksi);
?>