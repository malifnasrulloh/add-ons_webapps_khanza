<?php
/*
 * File: erm/simpan_hasil_lab.php
 * Fungsi: Generator PDF Lab (Support Multi-Order / Banyak Hasil dalam 1 Rawat)
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
$kode_berkas = '001';

// 1. CARI SEMUA WAKTU (Looping Trigger)
$q_times = "SELECT DISTINCT tgl_periksa, jam 
            FROM periksa_lab 
            WHERE no_rawat = '$no_rawat' AND kategori = 'PK' 
            ORDER BY tgl_periksa ASC, jam ASC";
$res_times = mysqli_query($koneksi, $q_times);

if(mysqli_num_rows($res_times) == 0) die("Data Lab Kosong");

$counter = 0;

// HELPER ASSETS (Sekali di luar loop)
$q_set = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($q_set);
$logo_src = 'data:image/jpeg;base64,' . base64_encode($setting['logo']);

// Helper Function
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

// 2. LOOPING SETIAP PEMERIKSAAN
while($time_row = mysqli_fetch_assoc($res_times)) {
    $counter++;
    
    // Set Parameter Waktu untuk Iterasi ini
    $tgl_periksa = $time_row['tgl_periksa'];
    $jam_periksa = $time_row['jam'];

    // --- RE-QUERY DATA SPESIFIK JAM INI ---
    
    // Header & Dokter
    $q_header = "SELECT kd_dokter, nip FROM periksa_lab 
                 WHERE no_rawat = '$no_rawat' AND tgl_periksa = '$tgl_periksa' AND jam = '$jam_periksa' AND kategori = 'PK' LIMIT 1";
    $d_header = mysqli_fetch_assoc(mysqli_query($koneksi, $q_header));

    // Pasien Info
    $q_pasien = "SELECT 
        p.nm_pasien, p.no_rkm_medis, p.tgl_lahir, p.jk, p.umur,
        concat(p.alamat,', ',kel.nm_kel,', ',kec.nm_kec,', ',kab.nm_kab) as alamat_lengkap,
        d.nm_dokter as dokter_penjab,
        pg.nama as nama_petugas,
        dr_perujuk.nm_dokter as dokter_pengirim
    FROM periksa_lab pl
    JOIN reg_periksa rp ON pl.no_rawat = rp.no_rawat
    JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    JOIN kelurahan kel ON p.kd_kel = kel.kd_kel
    JOIN kecamatan kec ON p.kd_kec = kec.kd_kec
    JOIN kabupaten kab ON p.kd_kab = kab.kd_kab
    JOIN dokter d ON pl.kd_dokter = d.kd_dokter
    JOIN petugas pg ON pl.nip = pg.nip
    LEFT JOIN dokter dr_perujuk ON pl.dokter_perujuk = dr_perujuk.kd_dokter
    WHERE pl.no_rawat = '$no_rawat' AND pl.tgl_periksa = '$tgl_periksa' AND pl.jam = '$jam_periksa'";
    $data_pasien = mysqli_fetch_assoc(mysqli_query($koneksi, $q_pasien));

    // Permintaan Lab
    $q_req = "SELECT noorder, tgl_permintaan, jam_permintaan FROM permintaan_lab 
              WHERE no_rawat = '$no_rawat' AND tgl_hasil = '$tgl_periksa' AND jam_hasil = '$jam_periksa'";
    $d_req = mysqli_fetch_assoc(mysqli_query($koneksi, $q_req));
    $no_order = $d_req['noorder'] ?? '-';
    $tgl_order = $d_req['tgl_permintaan'] ?? $tgl_periksa;
    $jam_order = $d_req['jam_permintaan'] ?? $jam_periksa;

    // Lokasi
    $nama_lokasi = "-";
    $cek_inap = mysqli_query($koneksi, "SELECT b.nm_bangsal FROM kamar_inap ki JOIN kamar k ON ki.kd_kamar = k.kd_kamar JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal WHERE ki.no_rawat = '$no_rawat'");
    if($d_inap = mysqli_fetch_assoc($cek_inap)) $nama_lokasi = $d_inap['nm_bangsal'];
    else {
        $cek_poli = mysqli_query($koneksi, "SELECT pl.nm_poli FROM reg_periksa rp JOIN poliklinik pl ON rp.kd_poli = pl.kd_poli WHERE rp.no_rawat = '$no_rawat'");
        if($r_poli = mysqli_fetch_assoc($cek_poli)) $nama_lokasi = $r_poli['nm_poli'];
    }

    // Detail Hasil
    $q_detail = "SELECT d.id_template, d.kd_jenis_prw, tl.Pemeriksaan, d.nilai, tl.satuan, d.nilai_rujukan, d.keterangan, jp.nm_perawatan as kategori
                 FROM detail_periksa_lab d
                 JOIN template_laboratorium tl ON d.id_template = tl.id_template
                 JOIN jns_perawatan_lab jp ON d.kd_jenis_prw = jp.kd_jenis_prw
                 WHERE d.no_rawat = '$no_rawat' AND d.tgl_periksa = '$tgl_periksa' AND d.jam = '$jam_periksa'
                 ORDER BY jp.kd_jenis_prw ASC, tl.urut ASC";
    $res_detail = mysqli_query($koneksi, $q_detail);
    $data_lab = [];
    while($row = mysqli_fetch_assoc($res_detail)) { $data_lab[] = $row; }

    // Saran Kesan
    $q_saran = "SELECT saran, kesan FROM saran_kesan_lab WHERE no_rawat='$no_rawat' AND tgl_periksa='$tgl_periksa' AND jam='$jam_periksa'";
    $d_saran = mysqli_fetch_assoc(mysqli_query($koneksi, $q_saran));

    // QR
    $qr_dokter = getQRBase64($koneksi, $d_header['kd_dokter'], $data_pasien['dokter_penjab'], $tgl_periksa, $setting['nama_instansi'], $setting['kabupaten']);
    $qr_petugas = getQRBase64($koneksi, $d_header['nip'], $data_pasien['nama_petugas'], $tgl_periksa, $setting['nama_instansi'], $setting['kabupaten']);

    // --- RENDER PDF ---
    ob_start();
    include 'layout_hasil_lab.php'; // Layout menerima variabel dari scope loop ini
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

    // --- PENAMAAN FILE UNIK BERDASARKAN WAKTU PERIKSA ---
    // Format: LabPK_{NoRawat}_{TglPeriksa}_{JamPeriksa}.pdf
    // Jam diganti : jadi - agar valid nama file
    $clean_rawat = str_replace(['/','\\'], '', $no_rawat);
    $clean_jam = str_replace(':', '', $jam_periksa); 
    $filename = "LabPK_{$clean_rawat}_{$tgl_periksa}_{$clean_jam}.pdf";
    
    $abs_path = dirname(dirname(__DIR__)) . "/berkasrawat/pages/upload/" . $filename;
    $db_path = "pages/upload/" . $filename;

    if(file_put_contents($abs_path, $output)){
        // Hapus file LAMA hanya jika nama filenya SAMA PERSIS (Revisi untuk pemeriksaan yg sama)
        // Kita HAPUS query DELETE LIKE yang lama!
        mysqli_query($koneksi, "DELETE FROM berkas_digital_perawatan WHERE no_rawat='$no_rawat' AND kode='$kode_berkas' AND lokasi_file = '$db_path'");
        
        // Insert
        mysqli_query($koneksi, "INSERT INTO berkas_digital_perawatan (no_rawat, kode, lokasi_file) VALUES ('$no_rawat', '$kode_berkas', '$db_path')");
    }
}

// Selesai Loop
echo "<script>alert('Berhasil! $counter Dokumen Hasil Lab telah tersimpan.'); window.close();</script>";
?>