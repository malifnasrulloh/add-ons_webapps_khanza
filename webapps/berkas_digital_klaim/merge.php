<?php
/*
 * File: merge.php (V.Final - Ghostscript Engine with Path Input)
 * Fungsi: Menggabungkan berkas berdasarkan PATH fisik (Input dari lihat_berkas.php)
 */

session_start();
require_once('../conf/conf.php');
require_once('fpdf.php'); // Menggunakan FPDF milik Anda

// 1. SETUP & VALIDASI
$storage_path = "../berkasrawat/"; 
$temp_dir = __DIR__ . "/tmp/"; 

if (!file_exists($temp_dir)) {
    if (!mkdir($temp_dir, 0777, true) && !is_dir($temp_dir)) {
        die("Gagal membuat direktori temporary.");
    }
}

$no_rawat = isset($_REQUEST['no_rawat']) ? validTeks4($_REQUEST['no_rawat'], 20) : '';
// Tangkap Array Path dari lihat_berkas.php
$selected_paths = isset($_POST['selected_files']) ? $_POST['selected_files'] : [];

if (empty($no_rawat)) die("No Rawat tidak ditemukan.");
if (empty($selected_paths)) {
    echo "<script>alert('Tidak ada file yang dipilih!'); window.close();</script>";
    exit;
}

// 2. PROSES MERGE (GHOSTSCRIPT HYBRID)
$files_to_merge = [];
$temp_files_created = [];
$counter = 1;

// Loop langsung array path dari input user (Tanpa Query DB lagi)
foreach ($selected_paths as $rel_path) {
    // Sanitasi path traversal sederhana
    $rel_path = str_replace('..', '', $rel_path);
    
    // Path fisik lengkap: ../berkasrawat/pages/upload/namafile.pdf
    $original_file = $storage_path . $rel_path;
    
    // Skip jika file fisik hilang
    if(!file_exists($original_file)) continue;

    $ext = strtolower(pathinfo($original_file, PATHINFO_EXTENSION));
    $uniq = uniqid();
    $temp_filename = $temp_dir . $uniq . "_part_" . $counter . ".pdf";

    if(in_array($ext, ['jpg', 'jpeg', 'png'])) {
        // --- GAMBAR KE PDF (FPDF) ---
        try {
            $pdf = new FPDF();
            $pdf->AddPage();
            // Fit Image A4 (210mm) - margin 10mm = 190mm width
            $pdf->Image($original_file, 10, 10, 190); 
            $pdf->Output('F', $temp_filename);
            
            $files_to_merge[] = $temp_filename;
            $temp_files_created[] = $temp_filename;
        } catch (Exception $e) { }
    } elseif ($ext == 'pdf') {
        // --- PDF ASLI ---
        // Copy ke tmp agar aman saat diproses GS
        $safe_pdf = $temp_dir . $uniq . "_safe_" . $counter . ".pdf";
        copy($original_file, $safe_pdf);
        $files_to_merge[] = $safe_pdf;
        $temp_files_created[] = $safe_pdf;
    }
    $counter++;
}

if(empty($files_to_merge)) die("Gagal memproses file fisik. Pastikan file ada di server.");

// Output Final Path
$clean_no_rawat = str_replace(['/','\\'], '-', $no_rawat);
$final_output = $temp_dir . "MERGED_" . $clean_no_rawat . "_" . date('His') . ".pdf";

// Command Ghostscript (Linux)
// -dAutoRotatePages=/None untuk mencegah orientasi berubah aneh
$files_str = implode(' ', $files_to_merge);
$command = "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=\"{$final_output}\" {$files_str}";

// Eksekusi
exec($command, $output, $return_var);

// 3. DOWNLOAD & CLEANUP
if (file_exists($final_output)) {
    // Kirim Header Download
    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Berkas_'.$clean_no_rawat.'.pdf"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($final_output));
    
    // Bersihkan buffer output sebelumnya (jika ada echo tak sengaja)
    if (ob_get_length()) ob_clean();
    flush();
    
    readfile($final_output);

    // Hapus file temporary
    foreach($temp_files_created as $f) { if(file_exists($f)) unlink($f); }
    unlink($final_output);
    exit;
} else {
    echo "Terjadi kesalahan saat menggabungkan PDF (Ghostscript Error code: $return_var). <br> Command: $command";
}
?>