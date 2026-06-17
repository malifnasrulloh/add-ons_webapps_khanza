<?php
/*
 * File: erm/simpan_radiologi.php
 * Fungsi: Generator PDF - Menerima Input Foto Selektif
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
$no_rawat = isset($_POST['no_rawat']) ? validTeks4($_POST['no_rawat'], 20) : '';
// Ambil Array Foto yang dipilih user dari form
$selected_imgs = isset($_POST['selected_imgs']) ? $_POST['selected_imgs'] : []; 
$kode_berkas = '001';

// 1. CARI SEMUA WAKTU
$q_times = "SELECT DISTINCT tgl_periksa, jam 
            FROM periksa_radiologi 
            WHERE no_rawat = '$no_rawat' 
            ORDER BY tgl_periksa ASC, jam ASC";
$res_times = mysqli_query($koneksi, $q_times);

if(mysqli_num_rows($res_times) == 0) die("Data Radiologi Kosong");

// Helper & Assets
$q_set = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($q_set);
$logo_src = 'data:image/jpeg;base64,' . base64_encode($setting['logo']);

if(!function_exists('getQRBase64')) {
    function getQRBase64($koneksi, $id, $nama, $tgl, $rs, $kab) {
        $q = mysqli_query($koneksi, "SELECT SHA1(sidikjari.sidikjari) as finger FROM sidikjari JOIN pegawai ON pegawai.id = sidikjari.id WHERE pegawai.nik = '$id'");
        $finger = ($r = mysqli_fetch_assoc($q)) ? $r['finger'] : $id;
        $content = "Dikeluarkan di $rs, $kab\nDitandatangani oleh $nama\nID $finger\n$tgl";
        $url = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($content);
        $ctx = stream_context_create(['http'=> ['timeout' => 5]]);
        $raw = @file_get_contents($url, false, $ctx);
        return $raw ? 'data:image/png;base64,' . base64_encode($raw) : '';
    }
}

$counter = 0;

// LOOP UTAMA
while($time = mysqli_fetch_assoc($res_times)) {
    $counter++;
    $tgl = $time['tgl_periksa'];
    $jam = $time['jam'];
    
    // Key Unik untuk mencocokkan dengan $_POST (harus sama logicnya dengan di cetak_radiologi.php)
    $unique_key = str_replace(['-', ':', ' '], '', $tgl . $jam);

    // -- SIAPKAN DATA --
    $data_laporan = []; // Reset array
    
    // Header & Pasien
    $d_head = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT kd_dokter, nip FROM periksa_radiologi WHERE no_rawat='$no_rawat' AND tgl_periksa='$tgl' AND jam='$jam' LIMIT 1"));
    $d_pas = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT p.nm_pasien, p.no_rkm_medis, p.tgl_lahir, p.jk, p.umur, p.alamat, kel.nm_kel, kec.nm_kec, kab.nm_kab, d.nm_dokter as dokter_penjab, pg.nama as nama_petugas, dr.nm_dokter as dokter_pengirim FROM periksa_radiologi pr JOIN reg_periksa rp ON pr.no_rawat=rp.no_rawat JOIN pasien p ON rp.no_rkm_medis=p.no_rkm_medis JOIN kelurahan kel ON p.kd_kel=kel.kd_kel JOIN kecamatan kec ON p.kd_kec=kec.kd_kec JOIN kabupaten kab ON p.kd_kab=kab.kd_kab JOIN dokter d ON pr.kd_dokter=d.kd_dokter JOIN petugas pg ON pr.nip=pg.nip LEFT JOIN dokter dr ON pr.dokter_perujuk=dr.kd_dokter WHERE pr.no_rawat='$no_rawat' AND pr.tgl_periksa='$tgl' AND pr.jam='$jam' LIMIT 1"));

    // List Periksa
    $list_periksa = [];
    $res_tind = mysqli_query($koneksi, "SELECT jpr.nm_perawatan FROM periksa_radiologi pr JOIN jns_perawatan_radiologi jpr ON pr.kd_jenis_prw=jpr.kd_jenis_prw WHERE pr.no_rawat='$no_rawat' AND pr.tgl_periksa='$tgl' AND pr.jam='$jam'");
    while($r = mysqli_fetch_assoc($res_tind)) $list_periksa[] = $r['nm_perawatan'];

    // Hasil
    $d_hasil = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT hasil FROM hasil_radiologi WHERE no_rawat='$no_rawat' AND tgl_periksa='$tgl' AND jam='$jam'"));

    // --- LOGIC FOTO SELEKTIF ---
    $images_base64 = [];
    
    // Cek apakah user memilih foto untuk jam periksa ini?
    if(isset($selected_imgs[$unique_key]) && is_array($selected_imgs[$unique_key])) {
        // User memilih foto tertentu
        foreach($selected_imgs[$unique_key] as $img_rel_path) {
            // Path Absolute Server: /var/www/html/webapps/radiologi/pages/upload/gambar.jpg
            $full_path = dirname(dirname(__DIR__)) . "/radiologi/" . $img_rel_path;
            
            if(file_exists($full_path)) {
                $type = pathinfo($full_path, PATHINFO_EXTENSION);
                $data = file_get_contents($full_path);
                $images_base64[] = 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }
    } 
    // Note: Jika user uncheck semua, $images_base64 kosong, PDF tetap terbuat tanpa foto.

    $data_laporan[] = [
        'tgl' => $tgl,
        'jam' => $jam,
        'pasien' => $d_pas,
        'periksa' => implode(", ", $list_periksa),
        'hasil' => $d_hasil['hasil'] ?? '-',
        'images' => $images_base64, // Array Base64
        'qr_dokter' => getQRBase64($koneksi, $d_head['kd_dokter'], $d_pas['dokter_penjab'], $tgl, $setting['nama_instansi'], $setting['kabupaten']),
        'qr_petugas' => getQRBase64($koneksi, $d_head['nip'], $d_pas['nama_petugas'], $tgl, $setting['nama_instansi'], $setting['kabupaten'])
    ];

    // -- RENDER PDF --
    $is_pdf = true; // Flag untuk layout
    ob_start();
    include 'layout_radiologi.php';
    $html = ob_get_clean();

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
    $clean_jam = str_replace(':', '', $jam);
    $filename = "Radiologi_{$clean_rawat}_{$tgl}_{$clean_jam}.pdf";
    $abs_path = dirname(dirname(__DIR__)) . "/berkasrawat/pages/upload/" . $filename;
    $db_path = "pages/upload/" . $filename;

    if(file_put_contents($abs_path, $output)){
        // Hapus file spesifik ini saja (replace)
        mysqli_query($koneksi, "DELETE FROM berkas_digital_perawatan WHERE no_rawat='$no_rawat' AND kode='$kode_berkas' AND lokasi_file = '$db_path'");
        mysqli_query($koneksi, "INSERT INTO berkas_digital_perawatan (no_rawat, kode, lokasi_file) VALUES ('$no_rawat', '$kode_berkas', '$db_path')");
    }
}

echo "<script>alert('Berhasil! Dokumen Radiologi Terpilih telah tersimpan.'); window.close();</script>";
?>