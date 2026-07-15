<?php
/*
 * File: ajax_process_item.php
 * Fungsi: Merge otomatis semua berkas milik 1 pasien untuk bulk download (SECURED)
 */
require_once('csrf.php');

if (!isset($_SESSION['casemix_login'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once('../conf/conf.php');
require_once('fpdf.php'); 

$storage_path = "../berkasrawat/"; 
$temp_dir = __DIR__ . "/tmp_bulk/"; 

if (!file_exists($temp_dir)) { mkdir($temp_dir, 0777, true); }

$no_rawat = isset($_POST['no_rawat']) ? $_POST['no_rawat'] : '';
$nm_pasien = isset($_POST['nm_pasien']) ? preg_replace('/[^A-Za-z0-9 ]/', '', $_POST['nm_pasien']) : 'Pasien';

if (empty($no_rawat)) {
    echo json_encode(['status' => 'error', 'message' => 'No Rawat kosong']);
    exit;
}

$koneksi = bukakoneksi();

// PREPARED STATEMENT UNTUK MENCEGAH SQL INJECTION
$q_berkas = "SELECT lokasi_file FROM berkas_digital_perawatan WHERE no_rawat = ? ORDER BY kode ASC";
$stmt = mysqli_prepare($koneksi, $q_berkas);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'DB Error']);
    exit;
}
mysqli_stmt_bind_param($stmt, "s", $no_rawat);
mysqli_stmt_execute($stmt);
$res_berkas = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($res_berkas) == 0) {
    echo json_encode(['status' => 'skip']); exit;
}

$files_to_merge = [];
$temp_files_created = [];
$counter = 1;

while($row = mysqli_fetch_assoc($res_berkas)) {
    // Cegah path traversal
    $lokasi = str_replace(['../', '..\\'], '', $row['lokasi_file']);
    $original_file = realpath($storage_path . $lokasi);
    
    if(!$original_file || !file_exists($original_file)) continue;
    // Pastikan file benar-benar ada di dalam berkasrawat
    if (strpos($original_file, realpath($storage_path)) !== 0) continue;

    $ext = strtolower(pathinfo($original_file, PATHINFO_EXTENSION));
    $uniq = uniqid();
    $temp_part = $temp_dir . $uniq . "_" . $counter . ".pdf";

    if(in_array($ext, ['jpg', 'jpeg', 'png'])) {
        try {
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->Image($original_file, 10, 10, 190); 
            $pdf->Output('F', $temp_part);
            $files_to_merge[] = escapeshellarg($temp_part); // SECURE ARGUMENT
            $temp_files_created[] = $temp_part;
        } catch (Exception $e) { }
    } elseif ($ext == 'pdf') {
        copy($original_file, $temp_part);
        $files_to_merge[] = escapeshellarg($temp_part); // SECURE ARGUMENT
        $temp_files_created[] = $temp_part;
    }
    $counter++;
}

if(count($files_to_merge) > 0) {
    $clean_rawat = str_replace(['/','\\'], '-', $no_rawat);
    $final_name = $clean_rawat . "_" . str_replace(' ', '_', $nm_pasien) . ".pdf";
    $final_path = $temp_dir . $final_name;

    // Merge via Ghostscript dengan Escapeshellarg
    $files_str = implode(' ', $files_to_merge);
    $final_path_escaped = escapeshellarg($final_path);
    
    $command = "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile={$final_path_escaped} {$files_str}";
    exec($command);

    foreach($temp_files_created as $f) { if(file_exists($f)) unlink($f); }

    if(file_exists($final_path)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'file' => $final_path]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'GS failed']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No files merged']);
}
?>