<?php
/*
 * File: erm/cetak_hasil_lab.php
 * Fungsi: Preview Lab PK (Support Multi-Result / Banyak Pemeriksaan)
 */
session_start();
require_once('../../conf/conf.php');
$koneksi = bukakoneksi();

$no_rawat = isset($_GET['no_rawat']) ? validTeks4($_GET['no_rawat'], 20) : '';

// 1. CARI SEMUA WAKTU PEMERIKSAAN (DISTINCT)
// Ini kunci perbaikannya: Ambil semua jam unik, bukan LIMIT 1
$q_times = "SELECT DISTINCT tgl_periksa, jam 
            FROM periksa_lab 
            WHERE no_rawat = '$no_rawat' AND kategori = 'PK' 
            ORDER BY tgl_periksa ASC, jam ASC";
$res_times = mysqli_query($koneksi, $q_times);

if(mysqli_num_rows($res_times) == 0) die("<div style='text-align:center;margin-top:50px'><h3>Data Lab PK Tidak Ditemukan</h3></div>");

// Siapkan Array Penampung Data untuk Layout
$all_reports = [];

// Helper Assets (Sekali saja)
$q_set = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($q_set);
$logo_src = '../logo.php'; // Relative for preview

// Helper QR
function getQRLink($id, $nama, $tgl, $rs, $kab) {
    $content = "Dikeluarkan di $rs, $kab\nDitandatangani oleh $nama\nID $id\n$tgl";
    return "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($content);
}

// 2. LOOPING PENGAMBILAN DATA
while($time_row = mysqli_fetch_assoc($res_times)) {
    $tgl_periksa = $time_row['tgl_periksa'];
    $jam_periksa = $time_row['jam'];

    // A. HEADER DOKTER
    $q_header = "SELECT kd_dokter, nip FROM periksa_lab 
                 WHERE no_rawat = '$no_rawat' AND tgl_periksa = '$tgl_periksa' AND jam = '$jam_periksa' AND kategori = 'PK' LIMIT 1";
    $d_header = mysqli_fetch_assoc(mysqli_query($koneksi, $q_header));

    // B. DATA PASIEN
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

    // C. PERMINTAAN LAB
    $q_req = "SELECT noorder, tgl_permintaan, jam_permintaan 
              FROM permintaan_lab 
              WHERE no_rawat = '$no_rawat' AND tgl_hasil = '$tgl_periksa' AND jam_hasil = '$jam_periksa'";
    $d_req = mysqli_fetch_assoc(mysqli_query($koneksi, $q_req));
    $no_order = $d_req['noorder'] ?? '-';
    $tgl_order = $d_req['tgl_permintaan'] ?? $tgl_periksa;
    $jam_order = $d_req['jam_permintaan'] ?? $jam_periksa;

    // D. LOKASI
    $nama_lokasi = "-";
    $cek_inap = mysqli_query($koneksi, "SELECT b.nm_bangsal FROM kamar_inap ki JOIN kamar k ON ki.kd_kamar = k.kd_kamar JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal WHERE ki.no_rawat = '$no_rawat'");
    if($d_inap = mysqli_fetch_assoc($cek_inap)) $nama_lokasi = $d_inap['nm_bangsal'];
    else {
        $cek_poli = mysqli_query($koneksi, "SELECT pl.nm_poli FROM reg_periksa rp JOIN poliklinik pl ON rp.kd_poli = pl.kd_poli WHERE rp.no_rawat = '$no_rawat'");
        if($r_poli = mysqli_fetch_assoc($cek_poli)) $nama_lokasi = $r_poli['nm_poli'];
    }

    // E. DETAIL HASIL
    $q_detail = "SELECT 
                    d.id_template, d.kd_jenis_prw, 
                    tl.Pemeriksaan, d.nilai, tl.satuan, d.nilai_rujukan, d.keterangan,
                    jp.nm_perawatan as kategori
                 FROM detail_periksa_lab d
                 JOIN template_laboratorium tl ON d.id_template = tl.id_template
                 JOIN jns_perawatan_lab jp ON d.kd_jenis_prw = jp.kd_jenis_prw
                 WHERE d.no_rawat = '$no_rawat' AND d.tgl_periksa = '$tgl_periksa' AND d.jam = '$jam_periksa'
                 ORDER BY jp.kd_jenis_prw ASC, tl.urut ASC";
    $res_detail = mysqli_query($koneksi, $q_detail);
    $data_lab = [];
    while($row = mysqli_fetch_assoc($res_detail)) { $data_lab[] = $row; }

    // F. KESAN SARAN
    $q_saran = "SELECT saran, kesan FROM saran_kesan_lab WHERE no_rawat='$no_rawat' AND tgl_periksa='$tgl_periksa' AND jam='$jam_periksa'";
    $d_saran = mysqli_fetch_assoc(mysqli_query($koneksi, $q_saran));

    // G. QR CODE
    $qr_dokter = getQRLink($d_header['kd_dokter'], $data_pasien['dokter_penjab'], $tgl_periksa, $setting['nama_instansi'], $setting['kabupaten']);
    $qr_petugas = getQRLink($d_header['nip'], $data_pasien['nama_petugas'], $tgl_periksa, $setting['nama_instansi'], $setting['kabupaten']);

    // Simpan semua variabel penting ke array container
    $all_reports[] = [
        'tgl_periksa' => $tgl_periksa,
        'jam_periksa' => $jam_periksa,
        'tgl_order' => $tgl_order,
        'jam_order' => $jam_order,
        'no_order' => $no_order,
        'data_pasien' => $data_pasien,
        'nama_lokasi' => $nama_lokasi,
        'data_lab' => $data_lab,
        'd_saran' => $d_saran,
        'qr_dokter' => $qr_dokter,
        'qr_petugas' => $qr_petugas
    ];
}

// 3. RENDER VIEW
?>
<div style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
    <button onclick="window.location.href='simpan_hasil_lab.php?no_rawat=<?= urlencode($no_rawat) ?>'" 
            style="background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 50px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
        <i style="margin-right: 8px;">💾</i> SIMPAN SEMUA PDF
    </button>
    <br><br>
    <button onclick="window.close()" 
            style="background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 50px; cursor: pointer; width: 100%; font-weight: bold;">
        TUTUP
    </button>
</div>

<div style="width: 210mm; margin: 0 auto; background: #525659; padding-bottom: 50px;">
    
    <?php foreach($all_reports as $report): ?>
        <?php 
            extract($report); 
            // Variabel global yang dibutuhkan layout:
            // $logo_src, $setting (sudah ada diatas)
            // $no_rawat (sudah ada)
            // Variabel dari extract: $data_pasien, $nama_lokasi, $no_order, $tgl_order, etc...
        ?>
        
        <div style="background: white; padding: 10mm; margin-bottom: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.5);">
            <?php include 'layout_hasil_lab.php'; ?>
        </div>

    <?php endforeach; ?>

</div>

<style>
    body { background: #525659; margin: 0; padding: 20px; font-family: Tahoma, sans-serif; }
    @media print {
        body { background: white; padding: 0; }
        div[style*="fixed"] { display: none !important; }
        div[style*="width: 210mm"] { box-shadow: none; width: 100%; margin: 0; padding: 0; }
        /* Page break tiap laporan */
        div[style*="margin-bottom: 20px"] { page-break-after: always; margin-bottom: 0; box-shadow: none; }
    }
</style>