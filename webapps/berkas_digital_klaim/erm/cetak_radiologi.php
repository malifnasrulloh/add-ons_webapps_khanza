<?php
/*
 * File: erm/cetak_radiologi.php
 * Fungsi: Preview Radiologi dengan Fitur SELEKSI FOTO
 */
session_start();
require_once('../../conf/conf.php');
$koneksi = bukakoneksi();

$no_rawat = isset($_GET['no_rawat']) ? validTeks4($_GET['no_rawat'], 20) : '';

// 1. QUERY SEMUA WAKTU
$q_times = "SELECT DISTINCT tgl_periksa, jam 
            FROM periksa_radiologi 
            WHERE no_rawat = '$no_rawat' 
            ORDER BY tgl_periksa ASC, jam ASC";
$res_times = mysqli_query($koneksi, $q_times);

if(mysqli_num_rows($res_times) == 0) die("<div style='text-align:center;margin-top:50px'><h3>Data Radiologi Kosong</h3></div>");

// 2. SIAPKAN DATA
$data_laporan = [];
$q_set = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($q_set);
$logo_src = '../logo.php'; // Relative for preview

// Helper QR (Untuk Preview pake API link)
function getQRLink($id, $nama, $tgl, $rs, $kab) {
    // Di preview kita pakai dummy/link API biar cepat, validasi sidikjari diabaikan dulu utk preview visual
    $content = "Dikeluarkan di $rs, $kab\nDitandatangani oleh $nama\nID $id\n$tgl";
    return "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($content);
}

while($time = mysqli_fetch_assoc($res_times)) {
    $tgl = $time['tgl_periksa'];
    $jam = $time['jam'];
    
    // Header & Pasien
    $q_pas = "SELECT p.nm_pasien, p.no_rkm_medis, p.tgl_lahir, p.jk, p.umur, p.alamat, kel.nm_kel, kec.nm_kec, kab.nm_kab,
              d.nm_dokter as dokter_penjab, pg.nama as nama_petugas, dr.nm_dokter as dokter_pengirim,
              pr.kd_dokter, pr.nip
              FROM periksa_radiologi pr JOIN reg_periksa rp ON pr.no_rawat=rp.no_rawat JOIN pasien p ON rp.no_rkm_medis=p.no_rkm_medis 
              JOIN kelurahan kel ON p.kd_kel=kel.kd_kel JOIN kecamatan kec ON p.kd_kec=kec.kd_kec JOIN kabupaten kab ON p.kd_kab=kab.kd_kab
              JOIN dokter d ON pr.kd_dokter=d.kd_dokter JOIN petugas pg ON pr.nip=pg.nip LEFT JOIN dokter dr ON pr.dokter_perujuk=dr.kd_dokter
              WHERE pr.no_rawat='$no_rawat' AND pr.tgl_periksa='$tgl' AND pr.jam='$jam' LIMIT 1";
    $d_pas = mysqli_fetch_assoc(mysqli_query($koneksi, $q_pas));
    
    // Pemeriksaan
    $list_periksa = [];
    $res_tind = mysqli_query($koneksi, "SELECT jpr.nm_perawatan FROM periksa_radiologi pr JOIN jns_perawatan_radiologi jpr ON pr.kd_jenis_prw=jpr.kd_jenis_prw WHERE pr.no_rawat='$no_rawat' AND pr.tgl_periksa='$tgl' AND pr.jam='$jam'");
    while($r = mysqli_fetch_assoc($res_tind)) $list_periksa[] = $r['nm_perawatan'];
    
    // Hasil Expertise
    $d_hasil = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT hasil FROM hasil_radiologi WHERE no_rawat='$no_rawat' AND tgl_periksa='$tgl' AND jam='$jam'"));
    
    // Foto-Foto (Ambil Semua Path)
    $q_img = "SELECT lokasi_gambar FROM gambar_radiologi WHERE no_rawat='$no_rawat' AND tgl_periksa='$tgl' AND jam='$jam'";
    $res_img = mysqli_query($koneksi, $q_img);
    $images = [];
    while($row_img = mysqli_fetch_assoc($res_img)) {
        // Kita simpan path relative untuk ditampilkan di browser
        // Asumsi struktur: /webapps/radiologi/pages/upload/gambar.jpg
        // Di browser akses via: ../../radiologi/pages/upload/gambar.jpg (sesuaikan path relatif server)
        $images[] = $row_img['lokasi_gambar']; 
    }

    $data_laporan[] = [
        'tgl' => $tgl,
        'jam' => $jam,
        'pasien' => $d_pas,
        'periksa' => implode(", ", $list_periksa),
        'hasil' => $d_hasil['hasil'] ?? '-',
        'images' => $images, // Array of paths
        'qr_dokter' => getQRLink($d_pas['kd_dokter'], $d_pas['dokter_penjab'], $tgl, $setting['nama_instansi'], $setting['kabupaten']),
        'qr_petugas' => getQRLink($d_pas['nip'], $d_pas['nama_petugas'], $tgl, $setting['nama_instansi'], $setting['kabupaten'])
    ];
}

// Flag mode Preview (untuk Layout)
$is_pdf = false; 

?>
<!DOCTYPE html>
<html>
<head>
<title>Preview Radiologi</title>
<style>
    body { background: #525659; margin: 0; padding: 20px; font-family: Tahoma, sans-serif; }
    /* Styling Checkbox Overlay */
    .img-wrapper { position: relative; display: inline-block; margin: 5px; border: 1px solid #ccc; padding: 5px; background: #fff; }
    .img-preview { max-width: 200px; height: auto; display: block; }
    .chk-overlay { display: block; background: #eee; padding: 5px; text-align: center; border-top: 1px solid #ccc; }
    
    /* Tombol Floating */
    .floating-menu { position: fixed; top: 20px; right: 20px; z-index: 9999; }
    .btn-save { background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 50px; cursor: pointer; font-weight: bold; font-size: 14px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
    .btn-close { background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 50px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 10px; }
</style>
</head>
<body>

<form action="simpan_radiologi.php" method="POST" target="_blank">
    <input type="hidden" name="no_rawat" value="<?= $no_rawat ?>">

    <div class="floating-menu">
        <button type="submit" class="btn-save">
            <i style="margin-right: 5px;">💾</i> SIMPAN PDF TERPILIH
        </button>
        <button type="button" onclick="window.close()" class="btn-close">TUTUP</button>
    </div>

    <div style="width: 210mm; margin: 0 auto; background: white; padding: 10mm; box-shadow: 0 0 10px rgba(0,0,0,0.5);">
        <div style="text-align: center; background: #fffae6; border: 1px solid #ffeeba; padding: 10px; margin-bottom: 20px; color: #856404;">
            <small><b>INFO:</b> Centang foto yang ingin dimasukkan ke dalam PDF. Hilangkan centang pada foto yang blur/gagal.</small>
        </div>
        
        <?php include 'layout_radiologi.php'; ?>
    </div>
</form>

</body>
</html>