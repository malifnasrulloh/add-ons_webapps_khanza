<?php
/*
 * File: erm/simpan_billing.php
 * Fungsi: Generator PDF Billing (SQL FIX: ORDER BY noindex)
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['casemix_login'])) die("Akses Ditolak");

require_once('../../conf/conf.php');
require_once('../vendor/autoload.php');

use Dompdf\Dompdf;
use Dompdf\Options;

$koneksi = bukakoneksi();
$no_rawat = isset($_GET['no_rawat']) ? validTeks4($_GET['no_rawat'], 20) : '';

// 1. DATA HEADER
$q_pasien = "SELECT 
    p.nm_pasien, p.no_rkm_medis, p.alamat,
    rp.no_rawat, rp.tgl_registrasi, rp.jam_reg, rp.status_lanjut,
    d.nm_dokter, pj.png_jawab
FROM reg_periksa rp
JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
JOIN dokter d ON rp.kd_dokter = d.kd_dokter
JOIN penjab pj ON rp.kd_pj = pj.kd_pj
WHERE rp.no_rawat = '$no_rawat'";
$data_pasien = mysqli_fetch_assoc(mysqli_query($koneksi, $q_pasien));

if(!$data_pasien) die("Data Pasien Tidak Ditemukan");

$nama_lokasi = "-";
if($data_pasien['status_lanjut'] == 'Ranap') {
    $d_inap = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT b.nm_bangsal, k.kd_kamar FROM kamar_inap ki JOIN kamar k ON ki.kd_kamar = k.kd_kamar JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal WHERE ki.no_rawat = '$no_rawat' ORDER BY ki.tgl_masuk DESC LIMIT 1"));
    $nama_lokasi = $d_inap ? $d_inap['nm_bangsal'] . " (" . $d_inap['kd_kamar'] . ")" : "Rawat Inap";
} else {
    $d_poli = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT pl.nm_poli FROM reg_periksa rp JOIN poliklinik pl ON rp.kd_poli = pl.kd_poli WHERE rp.no_rawat = '$no_rawat'"));
    $nama_lokasi = $d_poli ? $d_poli['nm_poli'] : "Rawat Jalan";
}

// 2. QUERY BILLING (FIXED ORDER)
$q_billing = "SELECT no, nm_perawatan, pemisah, biaya, jumlah, tambahan, totalbiaya, tgl_byr 
              FROM billing 
              WHERE no_rawat = '$no_rawat' 
              ORDER BY noindex ASC"; // FIXED HERE
$res_billing = mysqli_query($koneksi, $q_billing);
$data_billing = [];
$tgl_bayar = date('Y-m-d');
$total_tagihan = 0;

while($row = mysqli_fetch_assoc($res_billing)) {
    $data_billing[] = $row;
    $total_tagihan += $row['totalbiaya'];
    if(!empty($row['tgl_byr']) && $row['tgl_byr'] != '0000-00-00') $tgl_bayar = $row['tgl_byr'];
}

if(empty($data_billing)) die("Data Billing Kosong");

// 3. ASSETS
$q_set = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($q_set);
$logo_src = 'data:image/jpeg;base64,' . base64_encode($setting['logo']);

$nama_petugas = "Petugas Kasir";
$qr_txt = "Dikeluarkan oleh ".$setting['nama_instansi']." pada tanggal $tgl_bayar di ".$setting['kabupaten']." oleh '$nama_petugas'";
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($qr_txt);
$ctx = stream_context_create(['http'=> ['timeout' => 5]]);
$raw = @file_get_contents($qr_url, false, $ctx);
$qr_api = $raw ? 'data:image/png;base64,' . base64_encode($raw) : '';

// Formatter
function formatUang($nilai) {
    if($nilai == 0) return "";
    return number_format($nilai, 0, ',', '.');
}
function tgl_indo($tanggal){
    $bulan = array (1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
    $pecahkan = explode('-', $tanggal);
    return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

// 4. RENDER
ob_start();
include 'layout_billing.php';
$html = ob_get_clean();

try {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
	$customPaper = array(0, 0, 595.28, 935.43);
	$dompdf->setPaper($customPaper, 'portrait');
    //$dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $output = $dompdf->output();

    $clean_rawat = str_replace(['/','\\'], '', $no_rawat);
    $filename = "Billing_{$clean_rawat}_" . date('YmdHis') . ".pdf";
    $abs_path = dirname(dirname(__DIR__)) . "/berkasrawat/pages/upload/" . $filename;
    $db_path = "pages/upload/" . $filename;
    $kode_berkas = '001';

    if(file_put_contents($abs_path, $output)){
        mysqli_query($koneksi, "DELETE FROM berkas_digital_perawatan WHERE no_rawat='$no_rawat' AND kode='$kode_berkas' AND lokasi_file LIKE '%Billing_%'");
        mysqli_query($koneksi, "INSERT INTO berkas_digital_perawatan (no_rawat, kode, lokasi_file) VALUES ('$no_rawat', '$kode_berkas', '$db_path')");
        
        echo "<script>alert('Billing Berhasil Disimpan!'); window.close();</script>";
    }
} catch (Exception $e) {
    die("Error PDF: " . $e->getMessage());
}
?>