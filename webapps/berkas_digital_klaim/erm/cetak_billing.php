<?php
/*
 * File: erm/cetak_billing.php
 * Fungsi: Preview Billing (SQL FIX: ORDER BY noindex)
 */
session_start();
require_once('../../conf/conf.php');
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

if(!$data_pasien) die("<div style='text-align:center; margin-top:20px'>Data Registrasi Tidak Ditemukan</div>");

// 2. LOKASI
$nama_lokasi = "-";
if($data_pasien['status_lanjut'] == 'Ranap') {
    $d_inap = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT b.nm_bangsal, k.kd_kamar FROM kamar_inap ki JOIN kamar k ON ki.kd_kamar = k.kd_kamar JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal WHERE ki.no_rawat = '$no_rawat' ORDER BY ki.tgl_masuk DESC LIMIT 1"));
    $nama_lokasi = $d_inap ? $d_inap['nm_bangsal'] . " (" . $d_inap['kd_kamar'] . ")" : "Rawat Inap";
} else {
    $d_poli = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT pl.nm_poli FROM reg_periksa rp JOIN poliklinik pl ON rp.kd_poli = pl.kd_poli WHERE rp.no_rawat = '$no_rawat'"));
    $nama_lokasi = $d_poli ? $d_poli['nm_poli'] : "Rawat Jalan";
}

// 3. QUERY BILLING (THE FIX: ORDER BY noindex)
$q_bill = "SELECT no, nm_perawatan, pemisah, biaya, jumlah, tambahan, totalbiaya, tgl_byr 
           FROM billing 
           WHERE no_rawat = '$no_rawat' 
           ORDER BY noindex ASC"; // <-- INI KUNCINYA
$res_bill = mysqli_query($koneksi, $q_bill);

$data_billing = [];
$tgl_bayar = date('Y-m-d'); 
$total_tagihan = 0;

while($row = mysqli_fetch_assoc($res_bill)) {
    $data_billing[] = $row;
    $total_tagihan += $row['totalbiaya'];
    
    // Ambil tanggal bayar valid
    if(!empty($row['tgl_byr']) && $row['tgl_byr'] != '0000-00-00') {
        $tgl_bayar = $row['tgl_byr'];
    }
}

if(empty($data_billing)) die("<div style='text-align:center; margin-top:20px'>Data Billing Kosong</div>");

// 4. ASSETS
$q_set = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($q_set);
$logo_src = '../logo.php'; 

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

// QR Code
$nama_petugas = "Petugas Kasir";
$qr_txt = "Dikeluarkan oleh ".$setting['nama_instansi']." pada tanggal $tgl_bayar di ".$setting['kabupaten']." oleh '$nama_petugas'";
$qr_api = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($qr_txt);

?>
<!DOCTYPE html>
<html>
<head>
    <style>
        body { background: #525659; margin: 0; padding: 20px; font-family: Tahoma, sans-serif; }
        
        /* TOMBOL MODERN */
        .floating-menu { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .btn-save { 
            background: #28a745; color: white; padding: 12px 25px; 
            border: none; border-radius: 50px; cursor: pointer; 
            font-weight: bold; font-size: 14px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.3); 
            transition: 0.3s;
        }
        .btn-save:hover { background: #218838; transform: translateY(-2px); }
        
        .btn-close { 
            background: #dc3545; color: white; padding: 10px 20px; 
            border: none; border-radius: 50px; cursor: pointer; 
            font-weight: bold; width: 100%; margin-top: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }
        .btn-close:hover { background: #c82333; }

        @media print {
            body { background: white; padding: 0; }
            .floating-menu { display: none; }
            div[style*="width: 210mm"] { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>

<div class="floating-menu">
    <button onclick="window.location.href='simpan_billing.php?no_rawat=<?= urlencode($no_rawat) ?>'" class="btn-save">
        <i style="margin-right: 5px;">💾</i> SIMPAN PDF
    </button>
    <button onclick="window.close()" class="btn-close">TUTUP</button>
</div>

<div style="width: 210mm; min-height: 297mm; margin: 0 auto; background: white; padding: 10mm; box-shadow: 0 0 15px rgba(0,0,0,0.5);">
    <?php include 'layout_billing.php'; ?>
</div>

</body>
</html>