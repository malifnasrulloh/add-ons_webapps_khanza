<?php
/*
 * File: erm/simpan_triase_igd.php
 * Fungsi: Backend Generator PDF Triase (Fix Data & No Preview)
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

// 1. ROUTER SKALA
$skala_terdeteksi = 0; $tabel_detail = ""; $tipe_triase = ""; 

if(mysqli_num_rows(mysqli_query($koneksi, "SELECT no_rawat FROM data_triase_igddetail_skala1 WHERE no_rawat='$no_rawat'")) > 0){
    $skala_terdeteksi = 1; $tabel_detail = "data_triase_igddetail_skala1"; $tipe_triase = "PRIMER";
} elseif(mysqli_num_rows(mysqli_query($koneksi, "SELECT no_rawat FROM data_triase_igddetail_skala2 WHERE no_rawat='$no_rawat'")) > 0){
    $skala_terdeteksi = 2; $tabel_detail = "data_triase_igddetail_skala2"; $tipe_triase = "PRIMER";
} elseif(mysqli_num_rows(mysqli_query($koneksi, "SELECT no_rawat FROM data_triase_igddetail_skala3 WHERE no_rawat='$no_rawat'")) > 0){
    $skala_terdeteksi = 3; $tabel_detail = "data_triase_igddetail_skala3"; $tipe_triase = "SEKUNDER";
} elseif(mysqli_num_rows(mysqli_query($koneksi, "SELECT no_rawat FROM data_triase_igddetail_skala4 WHERE no_rawat='$no_rawat'")) > 0){
    $skala_terdeteksi = 4; $tabel_detail = "data_triase_igddetail_skala4"; $tipe_triase = "SEKUNDER";
} elseif(mysqli_num_rows(mysqli_query($koneksi, "SELECT no_rawat FROM data_triase_igddetail_skala5 WHERE no_rawat='$no_rawat'")) > 0){
    $skala_terdeteksi = 5; $tabel_detail = "data_triase_igddetail_skala5"; $tipe_triase = "SEKUNDER";
} else {
    die("Data Triase Kosong");
}

// 2. CONFIG
$config = ['sub_judul' => '', 'kode_berkas' => '001', 'warna_bg' => '#FFFFFF', 'warna_txt' => '#000000'];
switch ($skala_terdeteksi) {
    case 1: $config['sub_judul'] = "TRIASE PRIMER Skala 1 (Resusitasi)"; $config['warna_bg'] = "#FF0000"; $config['warna_txt'] = "#FFFFFF"; break;
    case 2: $config['sub_judul'] = "TRIASE PRIMER Skala 2 (Emergency)"; $config['warna_bg'] = "#FF0000"; $config['warna_txt'] = "#FFFFFF"; break;
    case 3: $config['sub_judul'] = "TRIASE SEKUNDER Skala 3 (Urgent)"; $config['warna_bg'] = "#FFFF00"; $config['warna_txt'] = "#000000"; break;
    case 4: $config['sub_judul'] = "TRIASE SEKUNDER Skala 4 (Semi Urgent)"; $config['warna_bg'] = "#00FF00"; $config['warna_txt'] = "#000000"; break;
    case 5: $config['sub_judul'] = "TRIASE SEKUNDER Skala 5 (Non Urgent)"; $config['warna_bg'] = "#FFFFFF"; $config['warna_txt'] = "#000000"; break;
}

// 3. DATA UMUM & FALLBACK
$q_umum = "SELECT p.nm_pasien, p.no_rkm_medis, p.tgl_lahir, p.jk, p.alamat, rp.tgl_registrasi, rp.jam_reg, d.nm_dokter, tri.*, mtmk.macam_kasus FROM reg_periksa rp JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis LEFT JOIN data_triase_igd tri ON rp.no_rawat = tri.no_rawat LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter LEFT JOIN master_triase_macam_kasus mtmk ON tri.kode_kasus = mtmk.kode_kasus WHERE rp.no_rawat = '$no_rawat'";
$d_umum = mysqli_fetch_assoc(mysqli_query($koneksi, $q_umum));

if (empty($d_umum['suhu']) || $d_umum['suhu'] == '-' || empty($d_umum['tensi'])) {
    $q_ttv = mysqli_query($koneksi, "SELECT suhu_tubuh, tensi, nadi, respirasi, berat, keluhan FROM pemeriksaan_ralan WHERE no_rawat='$no_rawat' ORDER BY tgl_perawatan ASC, jam_rawat ASC LIMIT 1");
    if($d_ttv = mysqli_fetch_assoc($q_ttv)) {
        if(empty($d_umum['suhu'])) $d_umum['suhu'] = $d_ttv['suhu_tubuh'];
        if(empty($d_umum['tensi'])) $d_umum['tensi'] = $d_ttv['tensi'];
        if(empty($d_umum['nadi'])) $d_umum['nadi'] = $d_ttv['nadi'];
        if(empty($d_umum['napas'])) $d_umum['napas'] = $d_ttv['respirasi'];
        if(empty($d_umum['berat_badan'])) $d_umum['berat_badan'] = $d_ttv['berat'];
        if(empty($d_umum['keluhan_utama'])) $d_umum['keluhan_utama'] = $d_ttv['keluhan'];
    }
}

$tgl_triase_fix = $d_umum['tgl_kunjungan'];
if(empty($tgl_triase_fix) || $tgl_triase_fix == '0000-00-00 00:00:00'){
    $tgl_triase_fix = $d_umum['tgl_registrasi'] . " " . $d_umum['jam_reg'];
}

// 4. DATA KHUSUS
if ($tipe_triase == "PRIMER") {
    $d_khusus = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM data_triase_igdprimer WHERE no_rawat = '$no_rawat'"));
} else {
    $d_khusus = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM data_triase_igdsekunder WHERE no_rawat = '$no_rawat'"));
}
$nik_perawat = $d_khusus['nik'] ?? '';

// 5. ASSETS
$nama_perawat = "-";
if(!empty($nik_perawat)){
    $r_peg = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama FROM pegawai WHERE nik = '$nik_perawat'"));
    $nama_perawat = $r_peg['nama'] ?? '-';
}
$setting = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1"));
$logo_b64 = 'data:image/jpeg;base64,' . base64_encode($setting['logo']);

function formatTgl($dt) { if ($dt && $dt!='0000-00-00 00:00:00') return date('d-m-Y H:i:s', strtotime($dt)) . ' WIB'; return '-'; }
function hitungUmur($lahir) { return date_diff(date_create($lahir), date_create('today'))->y . " Th"; }

$qr_b64 = "";
if(!empty($nik_perawat)){
    $finger_code = $nik_perawat; 
    $r_finger = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SHA1(sidikjari.sidikjari) as finger FROM sidikjari WHERE id = (SELECT id FROM pegawai WHERE nik='$nik_perawat')"));
    if($r_finger && !empty($r_finger['finger'])) $finger_code = $r_finger['finger'];
    $tgl_tte = $d_khusus['tanggaltriase'] ?? date('Y-m-d H:i:s');
    $qr_content = "Dikeluarkan di " . $setting['nama_instansi'] . ", Kabupaten/Kota " . $setting['kabupaten'] . "\nDitandatangani secara elektronik oleh " . $nama_perawat . "\nID " . $finger_code . "\n" . $tgl_tte;
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($qr_content);
    $ctx = stream_context_create(['http'=> ['timeout' => 5]]);
    $qr_raw = @file_get_contents($qr_url, false, $ctx);
    if($qr_raw) $qr_b64 = 'data:image/png;base64,' . base64_encode($qr_raw);
}

// 6. CHECKLIST
$checklist_data = [];
$master_skala = "master_triase_skala" . $skala_terdeteksi;
$kode_skala   = "kode_skala" . $skala_terdeteksi;
$pengkajian   = "pengkajian_skala" . $skala_terdeteksi;
$q_check = "SELECT mtp.nama_pemeriksaan, mts.$pengkajian as hasil FROM $tabel_detail dtd JOIN $master_skala mts ON dtd.$kode_skala = mts.$kode_skala JOIN master_triase_pemeriksaan mtp ON mts.kode_pemeriksaan = mtp.kode_pemeriksaan WHERE dtd.no_rawat = '$no_rawat' ORDER BY mtp.kode_pemeriksaan ASC";
$res_check = mysqli_query($koneksi, $q_check);
while($row = mysqli_fetch_assoc($res_check)){ $checklist_data[] = ['kategori' => $row['nama_pemeriksaan'], 'nilai' => $row['hasil']]; }

// 7. RENDER
ob_start();
include 'layout_triase_main.php'; 
$html = ob_get_clean();

try {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $customPaper = array(0, 0, 595.28, 935.43);
    $dompdf->setPaper($customPaper, 'portrait');
    $dompdf->render();
    $output = $dompdf->output();

    $clean_rawat = str_replace(['/','\\'], '', $no_rawat);
    $filename = "TriaseIGD_{$clean_rawat}_" . date('YmdHis') . ".pdf";
    $abs_path = dirname(dirname(__DIR__)) . "/berkasrawat/pages/upload/" . $filename;
    $db_path = "pages/upload/" . $filename;

    if(file_put_contents($abs_path, $output)){
        mysqli_query($koneksi, "DELETE FROM berkas_digital_perawatan WHERE no_rawat='$no_rawat' AND kode='".$config['kode_berkas']."' AND lokasi_file LIKE '%TriaseIGD_%'");
        mysqli_query($koneksi, "INSERT INTO berkas_digital_perawatan (no_rawat, kode, lokasi_file) VALUES ('$no_rawat', '".$config['kode_berkas']."', '$db_path')");
        echo "<script>alert('Triase Berhasil Disimpan!'); window.close();</script>";
    } else {
        throw new Exception("Gagal menulis file PDF.");
    }
} catch (Exception $e) { die("Error PDF: " . $e->getMessage()); }
?>