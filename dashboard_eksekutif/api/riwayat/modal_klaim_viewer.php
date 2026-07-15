<?php
session_start();
// File: modal_klaim_viewer.php

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

$no_rawat = isset($_GET['no_rawat']) ? $_GET['no_rawat'] : '';
if(empty($no_rawat)) die('<p>no_rawat tidak valid.</p>');
$no_rawat = mysqli_real_escape_string($koneksi, $no_rawat);

// Ambil setting RS
$setting_rs = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($setting_rs);
$logo_b64 = 'data:image/jpeg;base64,' . base64_encode($setting['logo']);
$logo_src = $logo_b64;

// Konfigurasi tambahan triase dan bg
$config = [
    'warna_bg' => '#ff0000',
    'warna_txt'=> '#ffffff',
    'sub_judul'=> 'GAWAT DARURAT'
];

function tgl_indo($tgl) {
    if(empty($tgl) || $tgl == '0000-00-00') return '-';
    $b = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $p = explode('-', substr($tgl, 0, 10));
    return $p[2].' '.$b[(int)$p[1]].' '.$p[0];
}
function formatUang($n) {
    return $n == 0 ? '' : 'Rp '.number_format($n, 0, ',', '.');
}
function tglIndoPendek($tanggal) {
    if(empty($tanggal) || $tanggal == '0000-00-00') return "-";
    return date('d-m-Y', strtotime($tanggal));
}
function getNamaValue($koneksi, $tabel, $kolom_kd, $kode, $kolom_nm) {
    if(empty($kode) || $kode == '-') return "-";
    $q = mysqli_query($koneksi, "SELECT $kolom_nm FROM $tabel WHERE $kolom_kd = '$kode'");
    if($r = mysqli_fetch_assoc($q)) return $r[$kolom_nm];
    return "-";
}
?>

<style>
    /* RESET & PAGE CONTAINERS */
    .berkas-viewer-body {
        font-family: Tahoma, Arial, sans-serif; 
        font-size: 11px; 
        color: #000; 
        line-height: 1.3;
        background: #525659;
        margin: 0;
        padding: 0;
    }
    
    .page-container {
        background: white;
        width: 210mm;
        min-height: 297mm;
        display: block;
        margin: 0 auto 20px auto;
        padding: 10mm 15mm;
        box-shadow: 0 0 10px rgba(0,0,0,0.5);
        position: relative;
        overflow: hidden;
    }

    /* KOP SURAT UMUM */
    .kop-table { width: 100%; border-bottom: 2px solid #000; margin-bottom: 10px; padding-bottom: 5px; }
    .kop-table.double-border { border-bottom: 3px double #000; }
    .rs-name { font-size: 14px; font-weight: bold; text-transform: uppercase; }
    .rs-name.besar { font-size: 20px; }
    .rs-detail { font-size: 10px; }
    .judul-dokumen { text-align: center; font-weight: bold; font-size: 12px; margin: 10px 0; text-decoration: underline; letter-spacing: 1px; }

    /* HELPER CLASSES */
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-bold { font-weight: bold; }
    .text-italic { font-style: italic; }
    .fs-10 { font-size: 10px; }
    .fs-12 { font-size: 12px; }
    .fs-14 { font-size: 14px; }
    
    .w-50 { width: 50%; }
    .w-100 { width: 100%; }
    
    /* TABEL INFO */
    .info-table { width: 100%; margin-bottom: 5px; border-collapse: collapse; }
    .info-table td { vertical-align: top; padding: 2px 0; border: none; }
    .label-col { width: 100px; }
    .colon { width: 10px; text-align: center; }

    /* TABEL KONTEN (Resume) */
    .content-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
    .content-table td { vertical-align: top; padding: 3px 5px; border-bottom: 1px dotted #ccc; }
    .content-label { width: 25%; font-weight: bold; vertical-align: top; }
    .nested-table { width: 100%; border-collapse: collapse; }
    .nested-table td { border: none; padding: 1px 0; }

    /* TABEL KOTAK TRIASE / ASESMEN */
    table.box-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; table-layout: fixed; }
    table.box-table td, table.box-table th { border: 1px solid #000; padding: 3px 5px; vertical-align: top; word-wrap: break-word; }
    .bg-header { background-color: #EFEFEF; font-weight: bold; padding: 5px; border: 1px solid #000; font-size: 11px; }
    .bg-triase { background-color: <?=$config['warna_bg']?>; color: <?=$config['warna_txt']?>; font-weight: bold; text-align: center; }
    .bg-section { background-color: #E8E8AD; font-weight: bold; text-align: center; }

    /* LAPORAN OPERASI */
    .op-header-title { font-size: 14px; font-weight: bold; font-style: italic; margin: 5px 0; text-transform: uppercase; }
    .op-gray-bar { background-color: #d3d3d3; font-weight: bold; text-align: center; padding: 3px; border-top: 1px solid #000; border-bottom: 1px solid #000; font-size: 11px; margin-top: 5px; }
    .op-gray-sub-bar { background-color: #d3d3d3; padding: 2px 5px; border-top: 1px solid #000; border-bottom: 1px solid #000; font-size: 10px; font-weight: normal; }
    .op-field-label { font-weight: normal; font-size: 10px; }
    .op-field-value { font-style: italic; margin-left: 15px; margin-bottom: 2px; font-weight: normal; }
    .op-report-content { padding: 5px; font-style: italic; min-height: 200px; border-bottom: 1px solid #000; }
    .op-border-left { border-left: 1px solid #000; }
    
    .ttd-area { margin-top: 20px; width: 100%; }

    .berkas-not-found { color: #888; font-style: italic; padding: 100px 20px; border: 1px dashed #ccc; text-align: center; margin-bottom: 20px; font-size: 14px; }

    /* FORCE BRIGHT PAPER THEME FOR PRINT VIEW - OVERRIDE GLOBAL DARK MODE */
    html.theme-glass-solid .modal-body .berkas-viewer-body,
    html.theme-glass-animated .modal-body .berkas-viewer-body,
    .berkas-viewer-body {
        background: #525659 !important;
        color: #000000 !important;
    }
    
    html.theme-glass-solid .modal-body .page-container,
    html.theme-glass-animated .modal-body .page-container,
    .page-container {
        background-color: #ffffff !important;
        background: #ffffff !important;
        color: #000000 !important;
    }

    html.theme-glass-solid .modal-body .page-container *,
    html.theme-glass-animated .modal-body .page-container *,
    .page-container * {
        color: #000000 !important;
        border-color: #000000 !important;
    }
    
    html.theme-glass-solid .modal-body .page-container table,
    html.theme-glass-animated .modal-body .page-container table,
    .page-container table {
        border-color: #000000 !important;
    }

    html.theme-glass-solid .modal-body .page-container td,
    html.theme-glass-animated .modal-body .page-container td,
    .page-container td,
    html.theme-glass-solid .modal-body .page-container th,
    html.theme-glass-animated .modal-body .page-container th,
    .page-container th {
        color: #000000 !important;
        background-color: transparent !important;
    }
    
    /* Preserve triase status colors in headers */
    html.theme-glass-solid .modal-body .page-container .bg-triase,
    html.theme-glass-animated .modal-body .page-container .bg-triase,
    .bg-triase {
        background-color: #EFEFEF !important;
        color: #000000 !important;
    }
    
    html.theme-glass-solid .modal-body .page-container .bg-section,
    html.theme-glass-animated .modal-body .page-container .bg-section,
    .bg-section {
        background-color: #E8E8AD !important;
        color: #000000 !important;
    }
    
    html.theme-glass-solid .modal-body .page-container .bg-header,
    html.theme-glass-animated .modal-body .page-container .bg-header,
    .bg-header {
        background-color: #EFEFEF !important;
        color: #000000 !important;
    }
    
    html.theme-glass-solid .modal-body .page-container .op-gray-bar,
    html.theme-glass-animated .modal-body .page-container .op-gray-bar,
    .op-gray-bar {
        background-color: #d3d3d3 !important;
        color: #000000 !important;
    }
    
    html.theme-glass-solid .modal-body .page-container .op-gray-sub-bar,
    html.theme-glass-animated .modal-body .page-container .op-gray-sub-bar,
    .op-gray-sub-bar {
        background-color: #d3d3d3 !important;
        color: #000000 !important;
    }
</style>

<div class="berkas-viewer-body">

<!-- BERKAS 1: Resume Rawat Inap (Ranap) -->
<?php
$sql = "SELECT 
    reg_periksa.no_rawat, reg_periksa.no_rkm_medis, 
    pasien.nm_pasien, pasien.tgl_lahir, pasien.jk, pasien.alamat, pasien.pekerjaan,
    resume_pasien_ranap.kd_dokter, dokter.nm_dokter, 
    resume_pasien_ranap.*,
    reg_periksa.kd_pj, penjab.png_jawab, 
    kamar_inap.tgl_keluar, kamar_inap.tgl_masuk,
    bangsal.nm_bangsal, kamar.kd_kamar
FROM resume_pasien_ranap
INNER JOIN reg_periksa ON resume_pasien_ranap.no_rawat = reg_periksa.no_rawat
INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
INNER JOIN dokter ON resume_pasien_ranap.kd_dokter = dokter.kd_dokter
INNER JOIN penjab ON penjab.kd_pj = reg_periksa.kd_pj
LEFT JOIN kamar_inap ON resume_pasien_ranap.no_rawat = kamar_inap.no_rawat
LEFT JOIN kamar ON kamar_inap.kd_kamar = kamar.kd_kamar
LEFT JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal
WHERE reg_periksa.no_rawat = '$no_rawat'
ORDER BY kamar_inap.tgl_keluar DESC LIMIT 1";

$hasil = mysqli_query($koneksi, $sql);
$data = mysqli_fetch_assoc($hasil);

if($data): 
    $lahir = new DateTime($data['tgl_lahir']);
    $regis = new DateTime($data['tgl_masuk']);
    $usia = $regis->diff($lahir);
    $umur_str = $usia->y . " Th " . $usia->m . " Bln " . $usia->d . " Hr";

    $kd_dokter = $data['kd_dokter'];
    $finger_code = $kd_dokter;
    $q_finger = mysqli_query($koneksi, "SELECT SHA1(sidikjari.sidikjari) as finger FROM sidikjari INNER JOIN pegawai ON pegawai.id = sidikjari.id WHERE pegawai.nik = '$kd_dokter'");
    if($r_finger = mysqli_fetch_assoc($q_finger)) { if(!empty($r_finger['finger'])) $finger_code = $r_finger['finger']; }

    $tgl_keluar_fix = !empty($data['tgl_keluar']) && $data['tgl_keluar'] != '0000-00-00' ? $data['tgl_keluar'] : date('Y-m-d');
    $qr_content = "Dikeluarkan di " . $setting['nama_instansi'] . ", Kabupaten/Kota " . $setting['kabupaten'] . "\nDitandatangani oleh " . $data['nm_dokter'] . "\nID " . $finger_code . "\n" . $tgl_keluar_fix;
    $qr_api = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($qr_content);
?>
<div class="page-container" style="min-height: 350mm;">
    <table class="kop-table">
        <tr>
            <td width="60" align="center"><img src="<?= $logo_src ?>" width="50"></td>
            <td align="left" style="padding-left: 10px;">
                <div class="rs-name"><?= $setting['nama_instansi'] ?></div>
                <div class="rs-detail"><?= $setting['alamat_instansi'] ?>, <?= $setting['kabupaten'] ?>, <?= $setting['propinsi'] ?></div>
                <div class="rs-detail">Telp: <?= $setting['kontak'] ?> | E-mail: <?= $setting['email'] ?></div>
            </td>
        </tr>
    </table>
    <div class="judul-dokumen">RESUME MEDIS PASIEN</div>
    <table class="info-table">
        <tr>
            <td width="50%">
                <table width="100%">
                    <tr><td class="label-col">Nama Pasien</td><td class="colon">:</td><td><b><?= $data['nm_pasien'] ?></b></td></tr>
                    <tr><td class="label-col">Umur</td><td class="colon">:</td><td><?= $umur_str ?></td></tr>
                    <tr><td class="label-col">Ruang</td><td class="colon">:</td><td><?= $data['nm_bangsal'] ?? '-' ?></td></tr>
                    <tr><td class="label-col">Jenis Kelamin</td><td class="colon">:</td><td><?= $data['jk']=='L'?'Laki-Laki':'Perempuan' ?></td></tr>
                    <tr><td class="label-col">Pekerjaan</td><td class="colon">:</td><td><?= $data['pekerjaan'] ?></td></tr>
                </table>
            </td>
            <td width="50%">
                <table width="100%">
                    <tr><td class="label-col">No. Rekam Medis</td><td class="colon">:</td><td><b><?= $data['no_rkm_medis'] ?></b></td></tr>
                    <tr><td class="label-col">Tgl Lahir</td><td class="colon">:</td><td><?= tgl_indo($data['tgl_lahir']) ?></td></tr>
                    <tr><td class="label-col">Tanggal Masuk</td><td class="colon">:</td><td><?= tgl_indo($data['tgl_masuk']) ?></td></tr>
                    <tr><td class="label-col">Tanggal Keluar</td><td class="colon">:</td><td><?= tgl_indo($data['tgl_keluar']) ?></td></tr>
                </table>
            </td>
        </tr>
        <tr><td colspan="2"><table width="100%"><tr><td class="label-col">Alamat</td><td class="colon">:</td><td><?= $data['alamat'] ?></td></tr></table></td></tr>
    </table>

    <table class="content-table">
        <tr><td class="content-label">Diagnosa Awal Masuk</td><td width="2%">:</td><td><?= $data['diagnosa_awal'] ?></td></tr>
        <tr><td class="content-label">Alasan Masuk Dirawat</td><td>:</td><td><?= $data['alasan'] ?></td></tr>
        <tr><td class="content-label">Keluhan Utama & RW</td><td>:</td><td><?= nl2br($data['keluhan_utama']) ?><br><?= nl2br($data['jalannya_penyakit']) ?></td></tr>
        <tr><td class="content-label">Pemeriksaan Fisik</td><td>:</td><td><?= nl2br($data['pemeriksaan_fisik']) ?></td></tr>
        <tr><td class="content-label">Rad Penunjang</td><td>:</td><td><?= nl2br($data['pemeriksaan_penunjang']) ?></td></tr>
        <tr><td class="content-label">Lab Penunjang</td><td>:</td><td><?= nl2br($data['hasil_laborat']) ?></td></tr>
        <tr><td class="content-label">Tindakan/Operasi</td><td>:</td><td><?= nl2br($data['tindakan_dan_operasi']) ?></td></tr>
        <tr><td class="content-label">Obat di RS</td><td>:</td><td><?= nl2br($data['obat_di_rs']) ?></td></tr>
        <tr>
            <td colspan="3" style="padding: 0;">
                <table width="100%" style="margin-top: 5px;">
                    <tr><td width="25%"><b>Diagnosa Akhir :</b></td><td></td></tr>
                    <tr><td style="padding-left: 20px;">- Diagnosa Utama</td><td>: <b><?= $data['kd_diagnosa_utama'] ?> - <?= $data['diagnosa_utama'] ?></b></td></tr>
                    <tr><td style="padding-left: 20px; vertical-align: top;">- Diagnosa Sekunder</td>
                        <td>
                            <table class="nested-table">
                                <tr><td width="10">: 1.</td><td><?= $data['diagnosa_sekunder'] ?></td></tr>
                                <tr><td>&nbsp;&nbsp;2.</td><td><?= $data['diagnosa_sekunder2'] ?></td></tr>
                                <tr><td>&nbsp;&nbsp;3.</td><td><?= $data['diagnosa_sekunder3'] ?></td></tr>
                                <tr><td>&nbsp;&nbsp;4.</td><td><?= $data['diagnosa_sekunder4'] ?></td></tr>
                            </table>
                        </td>
                    </tr>
                    <tr><td style="padding-left: 20px;">- Prosedur Utama</td><td>: <?= $data['kd_prosedur_utama'] ?> - <?= $data['prosedur_utama'] ?></td></tr>
                    <tr><td style="padding-left: 20px; vertical-align: top;">- Prosedur Sekunder</td>
                        <td>
                            <table class="nested-table">
                                <tr><td width="10">: 1.</td><td><?= $data['kd_prosedur_sekunder'] ?> - <?= $data['prosedur_sekunder'] ?></td></tr>
                                <tr><td>&nbsp;&nbsp;2.</td><td><?= $data['kd_prosedur_sekunder2'] ?> - <?= $data['prosedur_sekunder2'] ?></td></tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr><td class="content-label">Alergi</td><td>:</td><td><?= nl2br($data['alergi']) ?></td></tr>
        <tr><td class="content-label">Diet</td><td>:</td><td><?= nl2br($data['diet']) ?></td></tr>
        <tr><td class="content-label">Lab Pending</td><td>:</td><td><?= nl2br($data['lab_belum']) ?></td></tr>
        <tr><td class="content-label">Edukasi</td><td>:</td><td><?= nl2br($data['edukasi']) ?></td></tr>
    </table>

    <table class="content-table" style="border: none;">
        <tr>
            <td width="15%"><b>Keadaan Pulang</b></td><td width="2%">:</td><td width="30%"><?= $data['keadaan'] ?></td>
            <td width="15%"><b>Cara Keluar</b></td><td width="2%">:</td><td><?= $data['cara_keluar'] ?></td>
        </tr>
    </table>

    <div style="margin-top: 10px; border-bottom: 1px dotted #ccc; padding-bottom: 5px;">
        <b>Obat Pulang :</b><br><?= nl2br($data['obat_pulang']) ?>
    </div>

    <table class="ttd-area">
        <tr>
            <td width="60%"></td>
            <td align="center">
                <?= $setting['kabupaten'] ?>, <?= tgl_indo($tgl_keluar_fix) ?><br>
                Dokter Penanggung Jawab<br>
                <img src="<?= $qr_api ?>" style="width: 90px; margin: 10px;"><br>
                <b style="text-decoration: underline;"><?= $data['nm_dokter'] ?></b>
            </td>
        </tr>
    </table>
</div>
<?php endif; ?>

<!-- BERKAS 2: Resume Rawat Jalan (Ralan) -->
<?php
$sql = "SELECT 
    reg_periksa.no_rawat, reg_periksa.no_rkm_medis, reg_periksa.tgl_registrasi,
    pasien.nm_pasien, pasien.tgl_lahir, pasien.jk, pasien.alamat, pasien.pekerjaan,
    resume_pasien.kd_dokter, dokter.nm_dokter, 
    resume_pasien.*,
    poliklinik.nm_poli, penjab.png_jawab
FROM resume_pasien
INNER JOIN reg_periksa ON resume_pasien.no_rawat = reg_periksa.no_rawat
INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
INNER JOIN dokter ON resume_pasien.kd_dokter = dokter.kd_dokter
INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
WHERE reg_periksa.no_rawat = '$no_rawat' LIMIT 1";

$hasil = mysqli_query($koneksi, $sql);
$data = mysqli_fetch_assoc($hasil);

if($data): 
    $lahir = new DateTime($data['tgl_lahir']);
    $regis = new DateTime($data['tgl_registrasi']);
    $usia = $regis->diff($lahir);
    $umur_str = $usia->y . " Th " . $usia->m . " Bln " . $usia->d . " Hr";

    $kd_dokter = $data['kd_dokter'];
    $finger_code = $kd_dokter;
    $q_finger = mysqli_query($koneksi, "SELECT SHA1(sidikjari.sidikjari) as finger FROM sidikjari INNER JOIN pegawai ON pegawai.id = sidikjari.id WHERE pegawai.nik = '$kd_dokter'");
    if($r_finger = mysqli_fetch_assoc($q_finger)) { if(!empty($r_finger['finger'])) $finger_code = $r_finger['finger']; }

    $tgl_qr = $data['tgl_registrasi'];
    $qr_content = "Dikeluarkan di " . $setting['nama_instansi'] . ", Kabupaten/Kota " . $setting['kabupaten'] . "\nDitandatangani oleh " . $data['nm_dokter'] . "\nID " . $finger_code . "\n" . $tgl_qr;
    $qr_api = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($qr_content);
?>
<div class="page-container">
    <table class="kop-table">
        <tr>
            <td width="60" align="center"><img src="<?= $logo_src ?>" width="50"></td>
            <td align="left" style="padding-left: 10px;">
                <div class="rs-name"><?= $setting['nama_instansi'] ?></div>
                <div class="rs-detail"><?= $setting['alamat_instansi'] ?>, <?= $setting['kabupaten'] ?>, <?= $setting['propinsi'] ?></div>
                <div class="rs-detail">Telp: <?= $setting['kontak'] ?> | E-mail: <?= $setting['email'] ?></div>
            </td>
        </tr>
    </table>
    <div class="judul-dokumen">RESUME MEDIS RAWAT JALAN</div>
    <table class="info-table">
        <tr>
            <td width="50%">
                <table width="100%">
                    <tr><td class="label-col">Nama Pasien</td><td class="colon">:</td><td><b><?= $data['nm_pasien'] ?></b></td></tr>
                    <tr><td class="label-col">Umur</td><td class="colon">:</td><td><?= $umur_str ?></td></tr>
                    <tr><td class="label-col">Poli / Unit</td><td class="colon">:</td><td><?= $data['nm_poli'] ?></td></tr>
                    <tr><td class="label-col">Jenis Kelamin</td><td class="colon">:</td><td><?= $data['jk']=='L'?'Laki-Laki':'Perempuan' ?></td></tr>
                </table>
            </td>
            <td width="50%">
                <table width="100%">
                    <tr><td class="label-col">No. Rekam Medis</td><td class="colon">:</td><td><b><?= $data['no_rkm_medis'] ?></b></td></tr>
                    <tr><td class="label-col">Tgl Lahir</td><td class="colon">:</td><td><?= tgl_indo($data['tgl_lahir']) ?></td></tr>
                    <tr><td class="label-col">Tgl Periksa</td><td class="colon">:</td><td><?= tgl_indo($data['tgl_registrasi']) ?></td></tr>
                </table>
            </td>
        </tr>
        <tr><td colspan="2"><table width="100%"><tr><td class="label-col">Alamat</td><td class="colon">:</td><td><?= $data['alamat'] ?></td></tr></table></td></tr>
    </table>

    <table class="content-table">
        <tr><td class="content-label">Keluhan Utama</td><td width="2%">:</td><td><?= nl2br($data['keluhan_utama']) ?></td></tr>
        <tr><td class="content-label">Riwayat Penyakit</td><td>:</td><td><?= nl2br($data['jalannya_penyakit']) ?></td></tr>
        <tr><td class="content-label">Pemeriksaan Penunjang</td><td>:</td><td><?= nl2br($data['pemeriksaan_penunjang']) ?></td></tr>
        <tr><td class="content-label">Hasil Laboratorium</td><td>:</td><td><?= nl2br($data['hasil_laborat']) ?></td></tr>
        <tr>
            <td colspan="3" style="padding: 0;">
                <table width="100%" style="margin-top: 5px;">
                    <tr><td width="25%"><b>Diagnosa :</b></td><td></td></tr>
                    <tr><td style="padding-left: 20px;">- Utama</td><td>: <b><?= $data['kd_diagnosa_utama'] ?> - <?= $data['diagnosa_utama'] ?></b></td></tr>
                    <tr><td style="padding-left: 20px; vertical-align: top;">- Sekunder</td>
                        <td>
                            <table class="nested-table">
                                <?php if(!empty($data['diagnosa_sekunder'])): ?><tr><td width="10">1.</td><td><?= $data['kd_diagnosa_sekunder'] ?> - <?= $data['diagnosa_sekunder'] ?></td></tr><?php endif; ?>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr><td class="content-label">Kondisi Pulang</td><td>:</td><td><?= $data['kondisi_pulang'] ?></td></tr>
    </table>

    <div style="margin-top: 10px; border-bottom: 1px dotted #ccc; padding-bottom: 5px;">
        <b>Terapi / Obat Pulang :</b><br><?= nl2br($data['obat_pulang']) ?>
    </div>

    <table class="ttd-area">
        <tr>
            <td width="60%"></td>
            <td align="center">
                <?= $setting['kabupaten'] ?>, <?= tgl_indo($tgl_qr) ?><br>
                Dokter Penanggung Jawab<br>
                <img src="<?= $qr_api ?>" style="width: 90px; margin: 10px;"><br>
                <b style="text-decoration: underline;"><?= $data['nm_dokter'] ?></b>
            </td>
        </tr>
    </table>
</div>
<?php endif; ?>

<!-- BERKAS 3: Triase IGD -->
<?php
$skala = 0;
$tbl_detail = '';
for($s=1; $s<=5; $s++) {
    if(mysqli_num_rows(mysqli_query($koneksi, "SELECT no_rawat FROM data_triase_igddetail_skala$s WHERE no_rawat='$no_rawat'")) > 0) {
        $skala = $s; $tbl_detail = "data_triase_igddetail_skala$s"; break;
    }
}
if($skala > 0):
    $tipe = ($skala <= 2) ? 'PRIMER' : 'SEKUNDER';

    // --- CONFIG DISPLAY (identical to cetak_triase_igd.php) ---
    $config_triase = ['sub_judul' => '', 'warna_bg' => '#FFFFFF', 'warna_txt' => '#000000'];
    switch ($skala) {
        case 1: $config_triase = ['sub_judul'=>'TRIASE PRIMER Skala 1 (Resusitasi)', 'warna_bg'=>'#FF0000', 'warna_txt'=>'#FFFFFF']; break;
        case 2: $config_triase = ['sub_judul'=>'TRIASE PRIMER Skala 2 (Emergency)',  'warna_bg'=>'#FF0000', 'warna_txt'=>'#FFFFFF']; break;
        case 3: $config_triase = ['sub_judul'=>'TRIASE SEKUNDER Skala 3 (Urgent)',   'warna_bg'=>'#FFFF00', 'warna_txt'=>'#000000']; break;
        case 4: $config_triase = ['sub_judul'=>'TRIASE SEKUNDER Skala 4 (Semi Urgent)','warna_bg'=>'#00FF00','warna_txt'=>'#000000']; break;
        case 5: $config_triase = ['sub_judul'=>'TRIASE SEKUNDER Skala 5 (Non Urgent)','warna_bg'=>'#FFFFFF','warna_txt'=>'#000000']; break;
    }

    // --- QUERY DATA UMUM (identical to cetak_triase_igd.php - includes macam_kasus join) ---
    $q_umum = "SELECT 
        p.nm_pasien, p.no_rkm_medis, p.tgl_lahir, p.jk, p.alamat,
        rp.tgl_registrasi, rp.jam_reg,
        d.nm_dokter, 
        tri.*,
        mtmk.macam_kasus
    FROM reg_periksa rp
    JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    LEFT JOIN data_triase_igd tri ON rp.no_rawat = tri.no_rawat 
    LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
    LEFT JOIN master_triase_macam_kasus mtmk ON tri.kode_kasus = mtmk.kode_kasus
    WHERE rp.no_rawat = '$no_rawat'";
    $d_umum = mysqli_fetch_assoc(mysqli_query($koneksi, $q_umum));

    // [FIX PENTING] LOGIKA FALLBACK DATA KOSONG (identical to cetak_triase_igd.php)
    if (empty($d_umum['suhu']) || $d_umum['suhu'] == '-' || empty($d_umum['tensi'])) {
        $q_ttv = mysqli_query($koneksi, "SELECT suhu_tubuh, tensi, nadi, respirasi, berat, keluhan 
                                         FROM pemeriksaan_ralan 
                                         WHERE no_rawat='$no_rawat' 
                                         ORDER BY tgl_perawatan ASC, jam_rawat ASC LIMIT 1");
        if($d_ttv = mysqli_fetch_assoc($q_ttv)) {
            if(empty($d_umum['suhu']))         $d_umum['suhu']          = $d_ttv['suhu_tubuh'];
            if(empty($d_umum['tensi']))        $d_umum['tensi']         = $d_ttv['tensi'];
            if(empty($d_umum['nadi']))         $d_umum['nadi']          = $d_ttv['nadi'];
            if(empty($d_umum['napas']))        $d_umum['napas']         = $d_ttv['respirasi'];
            if(empty($d_umum['berat_badan'])) $d_umum['berat_badan']  = $d_ttv['berat'];
            if(empty($d_umum['keluhan_utama'])) $d_umum['keluhan_utama'] = $d_ttv['keluhan'];
        }
    }

    // [FIX PENTING] FALLBACK TANGGAL (identical to cetak_triase_igd.php)
    $tgl_triase_fix = $d_umum['tgl_kunjungan'] ?? '';
    if(empty($tgl_triase_fix) || $tgl_triase_fix == '0000-00-00 00:00:00'){
        $tgl_triase_fix = $d_umum['tgl_registrasi'] . " " . $d_umum['jam_reg'];
    }

    // --- DATA SPESIFIK & PERAWAT (identical to cetak_triase_igd.php) ---
    if ($tipe == "PRIMER") {
        $d_khusus = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM data_triase_igdprimer WHERE no_rawat = '$no_rawat'"));
    } else {
        $d_khusus = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM data_triase_igdsekunder WHERE no_rawat = '$no_rawat'"));
    }
    $nik_perawat = $d_khusus['nik'] ?? '';

    // Nama perawat dari tabel pegawai (BUKAN petugas - sesuai source asli)
    $nama_perawat = "-";
    if(!empty($nik_perawat)){
        $r_peg = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama FROM pegawai WHERE nik = '$nik_perawat'"));
        $nama_perawat = $r_peg['nama'] ?? '-';
    }

    // QR code perawat (identical to cetak_triase_igd.php)
    $qr_triase = "";
    if(!empty($nik_perawat)){
        $finger_code = $nik_perawat;
        $r_finger = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SHA1(sidikjari.sidikjari) as finger FROM sidikjari WHERE id = (SELECT id FROM pegawai WHERE nik='$nik_perawat')"));
        if($r_finger && !empty($r_finger['finger'])) $finger_code = $r_finger['finger'];
        $tgl_tte = $d_khusus['tanggaltriase'] ?? date('Y-m-d H:i:s');
        $qr_content_triase = "Dikeluarkan di " . $setting['nama_instansi'] . ", Kabupaten/Kota " . $setting['kabupaten'] . "\nDitandatangani secara elektronik oleh " . $nama_perawat . "\nID " . $finger_code . "\n" . $tgl_tte;
        $qr_triase = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($qr_content_triase);
    }

    // --- CHECKLIST (identical to cetak_triase_igd.php) ---
    $checklist_data = [];
    $master_skala_tbl = "master_triase_skala" . $skala;
    $kode_skala_col   = "kode_skala" . $skala;
    $pengkajian_col   = "pengkajian_skala" . $skala;
    $q_check = "SELECT mtp.nama_pemeriksaan, mts.$pengkajian_col as hasil 
                FROM $tbl_detail dtd 
                JOIN $master_skala_tbl mts ON dtd.$kode_skala_col = mts.$kode_skala_col 
                JOIN master_triase_pemeriksaan mtp ON mts.kode_pemeriksaan = mtp.kode_pemeriksaan 
                WHERE dtd.no_rawat = '$no_rawat' 
                ORDER BY mtp.kode_pemeriksaan ASC";
    $res_check = mysqli_query($koneksi, $q_check);
    while($row = mysqli_fetch_assoc($res_check)){
        $checklist_data[] = ['kategori' => $row['nama_pemeriksaan'], 'nilai' => $row['hasil']];
    }
?>
<div class="page-container">
    <table class="box-table" style="border: none;">
        <tr>
            <td width="8%" style="border-right: 0; vertical-align: middle; border: none;"><img src="<?= $logo_src ?>" style="width: 55px;"></td>
            <td width="40%" style="border-left: 0; border: none; border-right: 2px solid #000;">
                <div class="text-center">
                    <span class="fs-14 text-bold"><?= strtoupper($setting['nama_instansi']) ?></span><br>
                    <span class="fs-10"><?= $setting['alamat_instansi'] ?>, <?= $setting['kabupaten'] ?>, <?= $setting['propinsi'] ?><br><?= $setting['kontak'] ?><br>E-mail : <?= $setting['email'] ?></span>
                </div>
            </td>
            <td width="52%" style="padding: 0; border: none;">
                <table width="100%" style="border: none;">
                    <tr><td style="border:0;">Nomor RM</td><td style="border:0;">: <b><?= $d_umum['no_rkm_medis'] ?></b></td></tr>
                    <tr><td style="border:0;">Nama</td><td style="border:0;">: <?= $d_umum['nm_pasien'] ?></td></tr>
                    <tr><td style="border:0;">Tanggal Lahir</td><td style="border:0;">: <?= date('d-m-Y', strtotime($d_umum['tgl_lahir'])) ?></td></tr>
                    <tr><td style="border:0;">Jenis Kelamin</td><td style="border:0;">: <?= ($d_umum['jk']=='L'?'Laki-laki':'Perempuan') ?></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="box-table"><tr><td style="background-color:<?= $config_triase['warna_bg'] ?>; color:<?= $config_triase['warna_txt'] ?>; font-weight:bold; text-align:center; padding: 5px; border-top: 1px solid #000;">TRIASE PASIEN GAWAT DARURAT</td></tr></table>

    <table class="box-table"><tr><td class="fs-10 text-center" style="border-top: 0;">Triase dilakukan segera setelah pasien datang dan sebelum pasien/ keluarga mendaftar di TPP IGD</td></tr></table>

    <table class="box-table">
        <tr><td width="50%">Tanggal Kunjungan : <?= date('d-m-Y', strtotime($tgl_triase_fix)) ?></td><td width="50%">Pukul : <?= date('H:i:s', strtotime($tgl_triase_fix)) ?></td></tr>
    </table>

    <table class="box-table">
        <tr><td width="30%">Cara Datang</td><td width="70%"><?= $d_umum['cara_masuk'] ?? '-' ?></td></tr>
        <tr><td width="30%">Macam Kasus</td><td width="70%"><?= $d_umum['macam_kasus'] ?? '-' ?></td></tr>
    </table>

    <table class="box-table">
        <tr class="bg-section"><td width="30%">KETERANGAN</td><td width="70%">TRIASE <?= $config_triase['sub_judul'] ?></td></tr>
        <tr>
            <td height="60"><b class="fs-10">ANAMNESA SINGKAT</b></td>
            <td><?= !empty($d_umum['keluhan_utama']) ? nl2br($d_umum['keluhan_utama']) : '-' ?></td>
        </tr>
        <tr>
            <td><b class="fs-10">TANDA VITAL</b></td>
            <td>Suhu (C) : <?= $d_umum['suhu'] ?>, Nyeri : <?= $d_umum['nyeri'] ?? '-' ?>, Tensi : <?= $d_umum['tensi'] ?>, Nadi(/menit) : <?= $d_umum['nadi'] ?>, Saturasi O²(%) : <?= $d_umum['saturasi_o2'] ?? '-' ?>, Respirasi(/menit) : <?= $d_umum['napas'] ?></td>
        </tr>
    </table>

    <table class="box-table">
        <tr class="bg-section text-center"><td width="30%">PEMERIKSAAN</td><td width="70%" style="background-color:<?= $config_triase['warna_bg'] ?>; color:<?= $config_triase['warna_txt'] ?>; font-weight:bold; text-align:center;">URGENSI</td></tr>
        <?php foreach($checklist_data as $check): ?>
        <tr>
            <td><?= strtoupper($check['kategori']); ?></td>
            <td style="background-color:<?= $config_triase['warna_bg'] ?>; color:<?= $config_triase['warna_txt'] ?>;"><?= $check['nilai']; ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td>PLAN</td>
            <td style="background-color:<?= $config_triase['warna_bg'] ?>; color:<?= $config_triase['warna_txt'] ?>;"><?= $d_khusus['plan'] ?? 'Zona Triase' ?></td>
        </tr>
    </table>

    <table class="box-table">
        <tr class="bg-section text-center"><td width="30%"> &nbsp; </td><td width="70%" class="text-center">Petugas Triase</td></tr>
        <tr><td width="30%">Tanggal &amp; Jam</td><td width="70%"><?= date('d-m-Y H:i:s', strtotime($tgl_triase_fix)) ?></td></tr>
        <tr><td width="30%">Catatan</td><td width="70%"><?= !empty($d_khusus['catatan']) ? nl2br($d_khusus['catatan']) : '-' ?></td></tr>
        <tr>
            <td width="30%">Dokter/Petugas Jaga IGD</td>
            <td width="70%" style="vertical-align: middle; height: 70px;">
                <?php if(!empty($qr_triase)): ?>
                    <div style="float: right; margin-right: 10px;"><img src="<?= $qr_triase ?>" width="60"></div>
                <?php endif; ?>
                <div style="margin-top: 15px; float: left;"><?= $nama_perawat ?></div>
            </td>
        </tr>
    </table>
</div>
<?php endif; ?>



<!-- BERKAS 4: Asesmen Awal Medis IGD -->
<?php
$sql_asesmen = "SELECT rp.no_rawat, p.no_rkm_medis, p.nm_pasien, p.tgl_lahir, p.jk, pg.*, d.nm_dokter
FROM penilaian_medis_igd pg JOIN reg_periksa rp ON pg.no_rawat = rp.no_rawat JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis JOIN dokter d ON pg.kd_dokter = d.kd_dokter WHERE pg.no_rawat = '$no_rawat' LIMIT 1";
$d_asesmen = mysqli_fetch_assoc(mysqli_query($koneksi, $sql_asesmen));
if($d_asesmen):
?>
<div class="page-container">
    <div class="kop-table double-border">
        <table width="100%" style="border: none;">
            <tr>
                <td width="15%" class="text-center"><img src="<?= $logo_src ?>" style="width: 70px;"></td>
                <td width="85%" class="text-center">
                    <span class="fs-14 text-bold"> <b class="rs-name besar"><?= strtoupper($setting['nama_instansi']) ?> </b></span><br>
                    <span class="fs-10"><?= $setting['alamat_instansi'] ?>, <?= $setting['kabupaten'] ?></span><br>
                </td>
            </tr>
        </table>
    </div>

    <div class="text-center text-bold fs-12 bg-header" style="margin-bottom: 5px; border-bottom: none;">PENILAIAN AWAL MEDIS IGD</div>

    <table class="box-table" style="margin-top: -6px;">
        <tr>
            <td width="45%" style="padding: 0;">
                <table width="100%" style="border: none;">
                    <tr><td style="border: none; width: 70px;">No. RM</td><td style="border: none;">: <b><?= $d_asesmen['no_rkm_medis'] ?></b></td></tr>
                    <tr><td style="border: none; width: 70px;">Nama Pasien</td><td style="border: none;">: <?= $d_asesmen['nm_pasien'] ?></td></tr>
                </table>
            </td>
            <td width="23%" style="padding: 0;">
                <table width="100%" style="border: none;">
                    <tr><td style="border: none;">Jenis Kelamin</td><td style="border: none;">: <?= $d_asesmen['jk'] == 'L' ? 'Laki-Laki' : 'Perempuan' ?></td></tr>
                    <tr><td style="border: none;">Tanggal Lahir</td><td style="border: none;">: <?= tglIndoPendek($d_asesmen['tgl_lahir']) ?></td></tr>
                </table>
            </td>
            <td width="32%" style="padding: 0;">
                <table width="100%" style="border: none;">
                    <tr><td style="border: none;">Tanggal</td><td style="border: none;">: <?= date('d/m/Y H:i:s', strtotime($d_asesmen['tanggal'])) ?></td></tr>
                    <tr><td style="border: none;">Anamnesis</td><td style="border: none;">: <?= $d_asesmen['anamnesis'] ?></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="bg-header">I. RIWAYAT KESEHATAN</div>
    <table class="box-table" style="margin-top: 0;">
        <tr><td colspan="2">Keluhan Utama : <?= $d_asesmen['keluhan_utama'] ?></td></tr>
        <tr><td colspan="2">Riwayat Penyakit Sekarang : <?= $d_asesmen['rps'] ?></td></tr>
        <tr><td width="50%">Riwayat Penyakit Dahulu : <?= $d_asesmen['rpd'] ?></td><td width="50%">Riwayat Penyakit Keluarga : <?= $d_asesmen['rpk'] ?></td></tr>
        <tr><td>Riwayat Pengobatan : <?= $d_asesmen['rpo'] ?></td><td>Riwayat Alergi : <?= $d_asesmen['alergi'] ?></td></tr>
    </table>

    <div class="bg-header" style="margin-top: -4px;">II. PEMERIKSAAN FISIK</div>
    <table class="box-table" style="margin-top: 0;">
        <tr>
            <td width="33%">Keadaan Umum : <?= $d_asesmen['keadaan'] ?></td>
            <td width="33%">Kesadaran : <?= $d_asesmen['kesadaran'] ?></td>
            <td width="34%" class="text-center">GCS : <?= $d_asesmen['gcs'] ?></td>
        </tr>
        <tr>
            <td colspan="3" class="text-center" style="padding: 5px;">
                Tanda Vital : TD : <?= $d_asesmen['td'] ?> mmHg | N : <?= $d_asesmen['nadi'] ?> x/m | R : <?= $d_asesmen['rr'] ?> x/m | S : <?= $d_asesmen['suhu'] ?> °C | SPO2 : <?= $d_asesmen['spo'] ?> %
            </td>
        </tr>
    </table>

    <table class="box-table" style="margin-top: -6px; border-top: none;">
        <tr>
            <td width="25%" style="padding: 0; border-top: none;">
                <table width="100%" style="border: none;">
                    <tr><td style="border: none; border-bottom: 1px solid #eee; padding: 2px;">Kepala</td><td style="border: none; border-bottom: 1px solid #eee; text-align:right; padding: 2px;"><?= $d_asesmen['kepala'] ?></td></tr>
                    <tr><td style="border: none; border-bottom: 1px solid #eee; padding: 2px;">Mata</td><td style="border: none; border-bottom: 1px solid #eee; text-align:right; padding: 2px;"><?= $d_asesmen['mata'] ?></td></tr>
                    <tr><td style="border: none; border-bottom: 1px solid #eee; padding: 2px;">Gigi & Mulut</td><td style="border: none; border-bottom: 1px solid #eee; text-align:right; padding: 2px;"><?= $d_asesmen['gigi'] ?></td></tr>
                    <tr><td style="border: none; padding: 2px;">Leher</td><td style="border: none; text-align:right; padding: 2px;"><?= $d_asesmen['leher'] ?></td></tr>
                </table>
            </td>
            <td width="25%" style="padding: 0; border-top: none;">
                <table width="100%" style="border: none;">
                    <tr><td style="border: none; border-bottom: 1px solid #eee; padding: 2px;">Thoraks</td><td style="border: none; border-bottom: 1px solid #eee; text-align:right; padding: 2px;"><?= $d_asesmen['thoraks'] ?></td></tr>
                    <tr><td style="border: none; border-bottom: 1px solid #eee; padding: 2px;">Abdomen</td><td style="border: none; border-bottom: 1px solid #eee; text-align:right; padding: 2px;"><?= $d_asesmen['abdomen'] ?></td></tr>
                    <tr><td style="border: none; border-bottom: 1px solid #eee; padding: 2px;">Genital & Anus</td><td style="border: none; border-bottom: 1px solid #eee; text-align:right; padding: 2px;"><?= $d_asesmen['genital'] ?></td></tr>
                    <tr><td style="border: none; padding: 2px;">Ekstremitas</td><td style="border: none; text-align:right; padding: 2px;"><?= $d_asesmen['ekstremitas'] ?></td></tr>
                </table>
            </td>
            <td width="50%" style="border-top: none; vertical-align: top;"><?= nl2br($d_asesmen['ket_fisik']) ?></td>
        </tr>
    </table>

    <div class="bg-header" style="margin-top: -4px;">III. STATUS LOKALIS</div>
    <table class="box-table" style="margin-top: 0;"><tr><td>Keterangan : <?= nl2br($d_asesmen['ket_lokalis']) ?></td></tr></table>

    <div class="bg-header" style="margin-top: -4px;">IV. PENUNJANG & DIAGNOSIS</div>
    <table class="box-table" style="margin-top: 0;">
        <tr><td width="33%">EKG : <?= $d_asesmen['ekg'] ?></td><td width="33%">Radiologi : <?= $d_asesmen['rad'] ?></td><td width="34%">Lab : <?= $d_asesmen['lab'] ?></td></tr>
        <tr><td colspan="3" style="height: 40px;">Diagnosis: <?= nl2br($d_asesmen['diagnosis']) ?></td></tr>
        <tr><td colspan="3" style="height: 100px;">Tatalaksana: <?= nl2br($d_asesmen['tata']) ?></td></tr>
    </table>

    <table class="box-table" style="margin-top: -1px; border-top: none;">
        <tr><td width="50%" class="text-center">Tanggal dan Jam</td><td width="50%" class="text-center">Nama Dokter IGD</td></tr>
        <tr><td class="text-center"><br><br><br><?= date('d/m/Y H:i:s', strtotime($d_asesmen['tanggal'])) ?> WIB</td><td class="text-center"><br><br><br><span class="fs-10"><?= $d_asesmen['nm_dokter'] ?></span></td></tr>
    </table>
</div>
<?php endif; ?>

<!-- BERKAS 5: Laporan Operasi -->
<?php
$res_ops = mysqli_query($koneksi, "SELECT * FROM operasi WHERE no_rawat='$no_rawat' ORDER BY tgl_operasi ASC");
while($d_op = mysqli_fetch_assoc($res_ops)): 
    $tgl_target = $d_op['tgl_operasi'];
    $d_laporan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM laporan_operasi WHERE no_rawat = '$no_rawat' ORDER BY ABS(TIMESTAMPDIFF(SECOND, tanggal, '$tgl_target')) ASC LIMIT 1"));

    $q_pasien = "SELECT p.nm_pasien, p.no_rkm_medis, p.tgl_lahir, p.jk, p.umur, k.kd_kamar, b.nm_bangsal FROM reg_periksa rp JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis LEFT JOIN kamar_inap ki ON rp.no_rawat = ki.no_rawat LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal WHERE rp.no_rawat = '$no_rawat' LIMIT 1";
    $d_pasien = mysqli_fetch_assoc(mysqli_query($koneksi, $q_pasien));
    $ruang = empty($d_pasien['nm_bangsal']) ? '-' : $d_pasien['nm_bangsal'];

    $dokter_bedah = getNamaValue($koneksi, 'dokter', 'kd_dokter', $d_op['operator1'], 'nm_dokter');
    $dokter_bedah2 = getNamaValue($koneksi, 'dokter', 'kd_dokter', $d_op['operator2'], 'nm_dokter');
    $asisten_bedah = getNamaValue($koneksi, 'petugas', 'nip', $d_op['asisten_operator1'], 'nama');
    $asisten_bedah2 = getNamaValue($koneksi, 'petugas', 'nip', $d_op['asisten_operator2'], 'nama');
    $dokter_anestesi = getNamaValue($koneksi, 'dokter', 'kd_dokter', $d_op['dokter_anestesi'], 'nm_dokter');
    $asisten_anestesi = getNamaValue($koneksi, 'petugas', 'nip', $d_op['asisten_anestesi'], 'nama');
    $perawat_resusitas = getNamaValue($koneksi, 'petugas', 'nip', $d_op['perawaat_resusitas'], 'nama');
    $instrumen = getNamaValue($koneksi, 'petugas', 'nip', $d_op['instrumen'], 'nama');
    $omloop = getNamaValue($koneksi, 'petugas', 'nip', $d_op['omloop'], 'nama');
    $dokter_anak = getNamaValue($koneksi, 'dokter', 'kd_dokter', $d_op['dokter_anak'], 'nm_dokter');
    $dokter_umum = getNamaValue($koneksi, 'dokter', 'kd_dokter', $d_op['dokter_umum'], 'nm_dokter');
    $bidan = getNamaValue($koneksi, 'petugas', 'nip', $d_op['bidan'], 'nama');

    $tgl_operasi = $d_op['tgl_operasi'];
    $jam_mulai = "00:00"; 
    $jam_selesai = "00:00";
    if($d_laporan) { $jam_mulai = date('H:i', strtotime($d_laporan['tanggal'])); $jam_selesai = date('H:i', strtotime($d_laporan['selesaioperasi'] ?? $d_laporan['tanggal'])); }
?>
<div class="page-container">
    <table class="kop-table double-border" style="margin-bottom: 5px;">
        <tr><td width="70" align="center" style="padding-right: 20px;"><img src="<?= $logo_src ?>" width="70" height="70"></td>
        <td><b style="font-size:20px"><?= $setting['nama_instansi'] ?></b><br><span style="font-size:11px"><?= $setting['alamat_instansi'] ?></span><br></td></tr>
    </table>
    
    <div class="op-header-title text-center">LAPORAN OPERASI</div>

    <table width="100%" style="border-top: 1px solid #000; border-bottom: 1px solid #000; margin-bottom: 5px;">
        <tr><td width="15%">Nama Pasien</td><td width="35%">: <span class="text-italic"><?= $d_pasien['nm_pasien'] ?></span></td><td width="15%">No. RM</td><td width="35%">: <span class="text-italic"><?= $d_pasien['no_rkm_medis'] ?></span></td></tr>
        <tr><td>Umur</td><td>: <span class="text-italic"><?= $d_pasien['umur'] ?></span></td><td>Ruang</td><td>: <span class="text-italic"><?= $ruang ?></span></td></tr>
        <tr><td>Tgl Lahir</td><td>: <span class="text-italic"><?= tglIndoPendek($d_pasien['tgl_lahir']) ?></span></td><td>Jenis Kelamin</td><td>: <span class="text-italic"><?= $d_pasien['jk']=='L'?'Laki-Laki':'Perempuan' ?></span></td></tr>
    </table>

    <div class="op-gray-bar">PRE SURGICAL ASSESMENT</div>
    <table width="100%" style="border-bottom: 1px solid #000;">
        <tr><td width="15%">Tanggal</td><td width="25%">: <?= tglIndoPendek($tgl_operasi) ?></td><td width="10%">Waktu :</td><td width="15%"><?= $jam_mulai ?></td><td width="10%">Alergi</td><td width="25%">: <?= $d_laporan['alergi'] ?? 'tidak ada' ?></td></tr>
        <tr><td>Dokter Bedah</td><td colspan="5">: <span class="text-italic"><?= $dokter_bedah ?></span></td></tr>
    </table>

    <div class="op-gray-bar" style="border-top:none; margin-top:0;">POST SURGICAL REPORT</div>
    <table width="100%" cellspacing="0" cellpadding="0" style="border-bottom: 1px solid #000;">
        <tr>
            <td width="70%" style="padding: 0; vertical-align: top;">
                <table width="100%" style="margin-bottom: 5px;">
                    <tr>
                        <td width="50%" style="padding-top: 5px;">
                            <div class="op-field-label">Tanggal & Waktu</div><div class="op-field-value">: <?= tglIndoPendek($tgl_operasi) ?> <?= $jam_selesai ?></div>
                            <div class="op-field-label">Dokter Bedah :</div><div class="op-field-value"><?= $dokter_bedah ?></div>
                            <div class="op-field-label">Dokter Bedah 2 :</div><div class="op-field-value"><?= $dokter_bedah2 ?></div>
                            <div class="op-field-label">Perawat Resusitas :</div><div class="op-field-value"><?= $perawat_resusitas ?></div>
                            <div class="op-field-label">Instrumen :</div><div class="op-field-value"><?= $instrumen ?></div>
                            <div class="op-field-label">Dokter Anak :</div><div class="op-field-value"><?= $dokter_anak ?></div>
                            <div class="op-field-label">Dokter Umum :</div><div class="op-field-value"><?= $dokter_umum ?></div>
                        </td>
                        <td width="50%" style="padding-top: 20px;"> 
                            <div class="op-field-label">Asisten Bedah :</div><div class="op-field-value"><?= $asisten_bedah ?></div>
                            <div class="op-field-label">Asisten Bedah 2 :</div><div class="op-field-value"><?= $asisten_bedah2 ?></div>
                            <div class="op-field-label">Dokter Anastesi :</div><div class="op-field-value"><?= $dokter_anestesi ?></div>
                            <div class="op-field-label">Asisten Anastesi :</div><div class="op-field-value"><?= $asisten_anestesi ?></div>
                            <div class="op-field-label">Bidan :</div><div class="op-field-value"><?= $bidan ?></div>
                            <div class="op-field-label">Onloop :</div><div class="op-field-value"><?= $omloop ?></div>
                        </td>
                    </tr>
                </table>
                <div class="op-gray-sub-bar">Diagnosa Pre-Op / Pre Operation Diagnosis</div><div style="padding: 2px 5px 5px 5px; font-style: italic; min-height: 12px;"><?= $d_laporan['diagnosa_preop'] ?? '-' ?></div>
                <div class="op-gray-sub-bar">Jaringan Yang di-Eksisi/-Insisi</div><div style="padding: 2px 5px 5px 5px; font-style: italic; min-height: 12px;"><?= $d_laporan['jaringan_dieksekusi'] ?? '-' ?></div>
                <div class="op-gray-sub-bar">Diagnosa Post-Op / Post Operation Diagnosis</div><div style="padding: 2px 5px 5px 5px; font-style: italic; min-height: 12px;"><?= $d_laporan['diagnosa_postop'] ?? '-' ?></div>
            </td>
            <td width="30%" class="op-border-left text-center" style="vertical-align: top; padding-top: 20px;">
                <div style="margin-bottom: 20px;">Tipe/Jenis Anastesi<br><span class="text-italic text-bold"><?= $d_op['jenis_anasthesi'] ?></span></div>
                <div style="margin-bottom: 20px;">Dikirim ke PA<br><span class="text-italic text-bold"><?= $d_laporan['permintaan_pa'] ?? 'Tidak' ?></span></div>
                <div style="margin-bottom: 20px;">Kategori Operasi<br><span class="text-italic"><?= $d_op['kategori'] ?></span></div>
            </td>
        </tr>
    </table>

    <div class="op-gray-bar" style="margin-top: 0; border-top: none;">REPORT (PROCEDURES AND COMPLICATIONS)</div>
    <div class="op-report-content"><?= nl2br($d_laporan['laporan_operasi'] ?? '-') ?></div>

    <table style="margin-top: 10px; width: 100%;">
        <tr><td width="65%"></td><td width="35%" align="center"><?= tglIndoPendek($tgl_operasi) ?><br>Dokter Bedah<br><br><br><span style="text-decoration: underline;"><?= $dokter_bedah ?></span></td></tr>
    </table>
    <!-- Copyright Protection - Anti-Tamper Switch -->
    <div style="color: #fff; font-size: 2px; text-align: center; margin-top: 5px; opacity: 0.1;">
        Developed by Ichsan Leonhart | Saweria: saweria.co/ichsanleonhart | WA: 6285726123777 | Telegram: @IchsanLeonhart
        <img src="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" style="width:1px; height:1px; opacity:0.01;">
    </div>
</div>
<?php endwhile; ?>

</div>
