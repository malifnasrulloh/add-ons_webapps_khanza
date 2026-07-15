<?php
/*
 * File: erm/cetak_asesmen_igd.php
 * Fungsi: Preview Asesmen Awal Medis IGD
 */
session_start();
require_once('../../conf/conf.php');
$koneksi = bukakoneksi();

$no_rawat = isset($_GET['no_rawat']) ? validTeks4($_GET['no_rawat'], 20) : '';

// 1. QUERY DATA (Strict sesuai kode Java)
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

if(!$data) die("<div style='text-align:center;margin-top:50px'><h3>Data Asesmen Medis Tidak Ditemukan</h3><p>Pastikan dokter sudah mengisi Asesmen Awal Medis IGD.</p></div>");

// 2. HELPER DATA
$q_set = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($q_set);

// Gambar Logo (Relative Path untuk Preview)
$logo_src = '../logo.php'; 
// Gambar Anatomi (Relative Path)
$anatomi_src = '../gambar/semua.png';

// QR Code Preview (API)
$kd_dokter = $data['kd_dokter'];
$q_finger = mysqli_query($koneksi, "SELECT SHA1(sidikjari.sidikjari) as finger FROM sidikjari INNER JOIN pegawai ON pegawai.id = sidikjari.id WHERE pegawai.nik = '$kd_dokter'");
$finger_code = $kd_dokter;
if($r_finger = mysqli_fetch_assoc($q_finger)) { 
    if(!empty($r_finger['finger'])) $finger_code = $r_finger['finger']; 
}

$tgl_asesmen = $data['tanggal'];
$qr_content = "Dikeluarkan di " . $setting['nama_instansi'] . ", Kabupaten/Kota " . $setting['kabupaten'] . "\nDitandatangani secara elektronik oleh " . $data['nm_dokter'] . "\nID " . $finger_code . "\n" . $tgl_asesmen;
$qr_api = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($qr_content);

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

// 3. RENDER VIEW
?>
<div style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
    <button onclick="window.location.href='simpan_asesmen_igd.php?no_rawat=<?= urlencode($no_rawat) ?>'" 
            style="background: #28a745; color: white; font-weight: bold; padding: 12px 24px; border: none; border-radius: 50px; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
        <i style="margin-right: 8px;">💾</i> SIMPAN PDF
    </button>
    <br><br>
    <button onclick="window.close()" 
            style="background: #dc3545; color: white; font-weight: bold; padding: 10px 20px; border: none; border-radius: 50px; cursor: pointer; width: 100%;">
        TUTUP
    </button>
</div>

<div style="width: 210mm; margin: 0 auto; background: white; padding: 10mm; box-shadow: 0 0 10px rgba(0,0,0,0.5);">
    <?php include 'layout_asesmen_igd.php'; ?>
</div>

<style>
    body { background: #525659; margin: 0; padding: 20px; font-family: Tahoma, sans-serif; }
    @media print {
        body { background: white; padding: 0; }
        div[style*="fixed"] { display: none !important; }
        div[style*="width: 210mm"] { box-shadow: none; width: 100%; margin: 0; padding: 0; }
    }
</style>