<?php
/*
 * File: download_zip.php
 * Fungsi: Membungkus file PDF hasil generate menjadi ZIP (SECURED)
 */
require_once('csrf.php');

if (!isset($_SESSION['casemix_login'])) {
    http_response_code(403);
    die("Access Denied");
}

$files_json = isset($_POST['files']) ? $_POST['files'] : '';
$file_list = json_decode($files_json, true);

if (!$file_list || !is_array($file_list) || empty($file_list)) {
    die("Tidak ada file untuk di-zip.");
}

$allowed_dir = realpath(__DIR__ . "/tmp_bulk");
if (!$allowed_dir) {
    die("Direktori tmp_bulk tidak ditemukan.");
}

$zip = new ZipArchive();
$zip_name = "Berkas_Klaim_" . date('Ymd_His') . ".zip";
$zip_path = __DIR__ . "/tmp_bulk/" . $zip_name;

if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
    die("Gagal membuat file zip.");
}

$valid_files = [];

foreach ($file_list as $file_path) {
    $real_path = realpath($file_path);
    // VALIDASI PATH TRAVERSAL: Pastikan file benar-benar berada di folder tmp_bulk
    // Validasi Ekstensi: Pastikan hanya .pdf
    if ($real_path !== false && strpos($real_path, $allowed_dir) === 0) {
        $ext = strtolower(pathinfo($real_path, PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            $zip->addFile($real_path, basename($real_path));
            $valid_files[] = $real_path;
        }
    }
}

$zip->close();

// Download Process
if (file_exists($zip_path)) {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zip_name . '"');
    header('Content-Length: ' . filesize($zip_path));
    readfile($zip_path);

    // Cleanup: Hapus file ZIP dan semua PDF sementara yang VALID
    unlink($zip_path);
    foreach ($valid_files as $f) {
        if(file_exists($f)) unlink($f);
    }
} else {
    echo "Gagal memproses file ZIP.";
}
?>