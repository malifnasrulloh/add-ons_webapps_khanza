<?php
/*
 * File: erm/simpan_asesmen_igd.php
 * Fungsi: Generator PDF Asesmen IGD (Backend)
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

// 1. QUERY DATA
$sql = "SELECT 
    rp.no_rawat, p.no_rkm_medis, p.nm_pasien, p.tgl_lahir, p.jk,
    pg.tanggal, pg.kd_dokter, d.nm_dokter,
    pg.anamnesis, pg.hubungan, pg.keluhan_utama, 
    pg.rps, pg.rpd, pg.rpk, pg.rpo, pg.alergi,
    pg.keadaan, pg.gcs, pg.kesadaran, 
    pg.td, pg.nadi, pg.rr, pg.suhu, pg.spo, pg.bb, pg.tb,
    pg.kepala, pg.mata, pg.gigi, pg.leher, pg.thoraks, pg.abdomen, pg.genital, pg.ekstremitas,
    pg.ket_fisik, pg.ket_lokalis,
    pg.ekg, pg.rad, pg.lab, pg.diagnosis, pg.tata
FROM penilaian_medis_igd pg
JOIN reg_periksa rp ON pg.no_rawat = rp.no_rawat
JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
JOIN dokter d ON pg.kd_dokter = d.kd_dokter
WHERE pg.no_rawat = '$no_rawat' LIMIT 1";

$hasil = mysqli_query($koneksi, $sql);
$data = mysqli_fetch_assoc($hasil);

if(!$data) die("Data Kosong");

// 2. HELPER (GAMBAR BASE64)
$q_set = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($q_set);

// Logo Base64
$logo_src = 'data:image/jpeg;base64,' . base64_encode($setting['logo']);

// Anatomi Base64 (Wajib path absolute server)
$path_anatomi = dirname(__DIR__) . '/gambar/semua.png';
if (file_exists($path_anatomi)) {
    $type = pathinfo($path_anatomi, PATHINFO_EXTENSION);
    $img_data = file_get_contents($path_anatomi);
    $anatomi_src = 'data:image/' . $type . ';base64,' . base64_encode($img_data);
} else {
    $anatomi_src = ''; // Kosong jika file tidak ada
}

// QR Code Base64
$kd_dokter = $data['kd_dokter'];
$q_finger = mysqli_query($koneksi, "SELECT SHA1(sidikjari.sidikjari) as finger FROM sidikjari INNER JOIN pegawai ON pegawai.id = sidikjari.id WHERE pegawai.nik = '$kd_dokter'");
$finger_code = $kd_dokter;
if($r_finger = mysqli_fetch_assoc($q_finger)) { 
    if(!empty($r_finger['finger'])) $finger_code = $r_finger['finger']; 
}

$tgl_asesmen = $data['tanggal'];
$qr_content = "Dikeluarkan di " . $setting['nama_instansi'] . ", Kabupaten/Kota " . $setting['kabupaten'] . "\nDitandatangani secara elektronik oleh " . $data['nm_dokter'] . "\nID " . $finger_code . "\n" . $tgl_asesmen;
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($qr_content);
$ctx = stream_context_create(['http'=> ['timeout' => 5]]);
$qr_raw = @file_get_contents($qr_url, false, $ctx);
$qr_api = $qr_raw ? 'data:image/png;base64,' . base64_encode($qr_raw) : '';

// Formatter
function tgl_indo($tanggal){
    if(empty($tanggal) || $tanggal=='0000-00-00') return "-";
    $bulan = array (1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
    $pecahkan = explode('-', $tanggal);
    return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}
function formatJam($datetime) {
    if(empty($datetime)) return "-";
    return date('H:i:s', strtotime($datetime)) . " WIB";
}

// 3. RENDER
ob_start();
include 'layout_asesmen_igd.php';
$html = ob_get_clean();

try {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
	$customPaper = array(0, 0, 595.28, 935.43);
	$dompdf->setPaper($customPaper, 'portrait');
    //$dompdf->setPaper('legal', 'portrait'); // A4 atau Legal
    $dompdf->render();
    $output = $dompdf->output();

    $clean_rawat = str_replace(['/','\\'], '', $no_rawat);
    $filename = "AsesmenIGD_{$clean_rawat}_" . date('YmdHis') . ".pdf";
    $abs_path = dirname(dirname(__DIR__)) . "/berkasrawat/pages/upload/" . $filename;
    $db_path = "pages/upload/" . $filename;
    $kode_berkas = '001';

    if(file_put_contents($abs_path, $output)){
        // Smart Delete file AsesmenIGD lama
        mysqli_query($koneksi, "DELETE FROM berkas_digital_perawatan WHERE no_rawat='$no_rawat' AND kode='$kode_berkas' AND lokasi_file LIKE '%AsesmenIGD_%'");
        // Insert Baru
        mysqli_query($koneksi, "INSERT INTO berkas_digital_perawatan (no_rawat, kode, lokasi_file) VALUES ('$no_rawat', '$kode_berkas', '$db_path')");
        
        echo "<script>alert('Asesmen IGD Berhasil Disimpan!'); window.close();</script>";
    } else {
        throw new Exception("Gagal menulis file.");
    }
} catch (Exception $e) {
    die("Error PDF: " . $e->getMessage());
}
?>