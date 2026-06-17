<?php
/*
 * File: erm/cetak_resume.php (V3 - Fix Variable & Paper Size)
 */
session_start();
require_once('../../conf/conf.php'); 
$koneksi = bukakoneksi();

$no_rawat = isset($_GET['no_rawat']) ? validTeks4($_GET['no_rawat'], 20) : '';

// 1. QUERY DATA
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

if(!$data) die("<div style='text-align:center; margin-top:50px;'><h3>Data Resume Tidak Ditemukan</h3><p>Pastikan dokter sudah input resume di SIMRS.</p></div>");

// 2. HELPER
$q_set = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($q_set);

function tgl_indo($tanggal){
	if(empty($tanggal) || $tanggal == '0000-00-00') return "-";
    $bulan = array (1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
	$pecahkan = explode('-', $tanggal);
	return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

$lahir = new DateTime($data['tgl_lahir']);
$regis = new DateTime($data['tgl_masuk']);
$usia = $regis->diff($lahir);
$umur_str = $usia->y . " Th " . $usia->m . " Bln " . $usia->d . " Hr";

// QR Code Preview
$kd_dokter = $data['kd_dokter'];
$q_finger = mysqli_query($koneksi, "SELECT SHA1(sidikjari.sidikjari) as finger FROM sidikjari INNER JOIN pegawai ON pegawai.id = sidikjari.id WHERE pegawai.nik = '$kd_dokter'");
$finger_code = $kd_dokter;
if($r_finger = mysqli_fetch_assoc($q_finger)) { if(!empty($r_finger['finger'])) $finger_code = $r_finger['finger']; }

// FIX: Variable disamakan jadi $tgl_keluar_fix
$tgl_keluar_fix = !empty($data['tgl_keluar']) && $data['tgl_keluar'] != '0000-00-00' ? $data['tgl_keluar'] : date('Y-m-d');

$qr_content = "Dikeluarkan di " . $setting['nama_instansi'] . ", Kabupaten/Kota " . $setting['kabupaten'] . "\nDitandatangani oleh " . $data['nm_dokter'] . "\nID " . $finger_code . "\n" . $tgl_keluar_fix;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Preview Resume - <?= $data['nm_pasien'] ?></title>
    <style>
        body { font-family: Tahoma, Verdana, Segoe, sans-serif; font-size: 11px; color: #000; line-height: 1.3; background: #525659; margin: 0; padding: 20px; }
        
        /* Kertas Ukuran Legal/F4 (21cm x 35cm) */
        .page { 
            background: white; 
            width: 210mm; 
            min-height: 350mm; /* Sesuai request 35 cm */
            display: block; 
            margin: 0 auto; 
            padding: 10mm 15mm; 
            box-shadow: 0 0 10px rgba(0,0,0,0.5); 
            position: relative;
        }

        /* Stylesheets sama persis */
        .kop-table { width: 100%; border-bottom: 2px solid #000; margin-bottom: 10px; padding-bottom: 5px; }
        .rs-name { font-size: 14px; font-weight: bold; text-transform: uppercase; }
        .rs-detail { font-size: 10px; }
        .judul { text-align: center; font-weight: bold; font-size: 12px; margin: 10px 0; text-decoration: underline; letter-spacing: 1px; }
        .info-table { width: 100%; margin-bottom: 5px; }
        .info-table td { vertical-align: top; padding: 2px 0; }
        .label { width: 100px; }
        .colon { width: 10px; text-align: center; }
        .content-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .content-table td { vertical-align: top; padding: 3px 5px; border-bottom: 1px dotted #ccc; }
        .content-label { width: 25%; font-weight: bold; vertical-align: top; }
        .nested-table { width: 100%; border-collapse: collapse; }
        .nested-table td { border: none; padding: 1px 0; }
        .ttd-area { margin-top: 20px; width: 100%; }
        
        @media print {
            body { background: white; padding: 0; }
            .page { box-shadow: none; width: 100%; margin: 0; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 999;">
    <button onclick="window.location.href='simpan_resume.php?no_rawat=<?= urlencode($no_rawat) ?>'" 
            style="background: #28a745; color: white; padding: 15px 25px; border: none; cursor: pointer; font-weight: bold; border-radius: 50px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); font-size: 14px;">
        <i style="margin-right: 5px;">💾</i> SIMPAN PDF (21x35 cm)
    </button>
    <br><br>
    <button onclick="window.close()" style="background: #dc3545; color: white; padding: 10px 20px; border: none; cursor: pointer; font-weight: bold; border-radius: 50px; width: 100%;">
        TUTUP
    </button>
</div>

<div class="page">
    <table class="kop-table">
        <tr>
            <td width="60" align="center"><img src="../logo.php" width="50"></td>
            <td align="left" style="padding-left: 10px;">
                <div class="rs-name"><?= $setting['nama_instansi'] ?></div>
                <div class="rs-detail"><?= $setting['alamat_instansi'] ?>, <?= $setting['kabupaten'] ?>, <?= $setting['propinsi'] ?></div>
                <div class="rs-detail">Telp: <?= $setting['kontak'] ?> | E-mail: <?= $setting['email'] ?></div>
            </td>
        </tr>
    </table>

    <div class="judul">RESUME MEDIS PASIEN</div>

    <table class="info-table">
        <tr>
            <td width="50%">
                <table width="100%">
                    <tr><td class="label">Nama Pasien</td><td class="colon">:</td><td><b><?= $data['nm_pasien'] ?></b></td></tr>
                    <tr><td class="label">Umur</td><td class="colon">:</td><td><?= $umur_str ?></td></tr>
                    <tr><td class="label">Ruang</td><td class="colon">:</td><td><?= $data['nm_bangsal'] ?? '-' ?></td></tr>
                    <tr><td class="label">Jenis Kelamin</td><td class="colon">:</td><td><?= $data['jk']=='L'?'Laki-Laki':'Perempuan' ?></td></tr>
                    <tr><td class="label">Pekerjaan</td><td class="colon">:</td><td><?= $data['pekerjaan'] ?></td></tr>
                </table>
            </td>
            <td width="50%">
                <table width="100%">
                    <tr><td class="label">No. Rekam Medis</td><td class="colon">:</td><td><b><?= $data['no_rkm_medis'] ?></b></td></tr>
                    <tr><td class="label">Tgl Lahir</td><td class="colon">:</td><td><?= tgl_indo($data['tgl_lahir']) ?></td></tr>
                    <tr><td class="label">Tanggal Masuk</td><td class="colon">:</td><td><?= tgl_indo($data['tgl_masuk']) ?></td></tr>
                    <tr><td class="label">Tanggal Keluar</td><td class="colon">:</td><td><?= tgl_indo($data['tgl_keluar']) ?></td></tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <table width="100%">
                    <tr><td class="label">Alamat</td><td class="colon">:</td><td><?= $data['alamat'] ?></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="content-table">
        <tr><td class="content-label">Diagnosa Awal Masuk</td><td width="2%">:</td><td><?= $data['diagnosa_awal'] ?></td></tr>
        <tr><td class="content-label">Alasan Masuk Dirawat</td><td>:</td><td><?= $data['alasan'] ?></td></tr>
        <tr><td class="content-label">Keluhan Utama Riwayat Penyakit</td><td>:</td><td><?= nl2br($data['keluhan_utama']) ?><br><?= nl2br($data['jalannya_penyakit']) ?></td></tr>
        <tr><td class="content-label">Pemeriksaan Fisik</td><td>:</td><td><?= nl2br($data['pemeriksaan_fisik']) ?></td></tr>
        <tr><td class="content-label">Jalannya Penyakit Selama Perawatan</td><td>:</td><td><?= nl2br($data['jalannya_penyakit']) ?></td></tr>
        <tr><td class="content-label">Pemeriksaan Penunjang Radiologi Terpenting</td><td>:</td><td><?= nl2br($data['pemeriksaan_penunjang']) ?></td></tr>
        <tr><td class="content-label">Pemeriksaan Penunjang Laboratorium Terpenting</td><td>:</td><td><?= nl2br($data['hasil_laborat']) ?></td></tr>
        <tr><td class="content-label">Tindakan/Operasi Selama Perawatan</td><td>:</td><td><?= nl2br($data['tindakan_dan_operasi']) ?></td></tr>
        <tr><td class="content-label">Obat-obatan Selama Perawatan</td><td>:</td><td><?= nl2br($data['obat_di_rs']) ?></td></tr>
        
        <tr>
            <td colspan="3" style="padding: 0;">
                <table width="100%" style="margin-top: 5px;">
                    <tr><td width="25%"><b>Diagnosa Akhir :</b></td><td></td></tr>
                    <tr><td style="padding-left: 20px;">- Diagnosa Utama</td><td>: <b><?= $data['kd_diagnosa_utama'] ?> - <?= $data['diagnosa_utama'] ?></b></td></tr>
                    <tr><td style="padding-left: 20px; vertical-align: top;">- Diagnosa Sekunder</td>
                        <td>
                           <table class="nested-table">
                                <tr><td width="10">: 1.</td><td><!--<?= $data['kd_diagnosa_sekunder'] ?> - --><?= $data['diagnosa_sekunder'] ?></td></tr>
                                <tr><td>&nbsp;&nbsp;2.</td><td><!--<?= $data['kd_diagnosa_sekunder2'] ?> - --><?= $data['diagnosa_sekunder2'] ?></td></tr>
                                <tr><td>&nbsp;&nbsp;3.</td><td><!--<?= $data['kd_diagnosa_sekunder3'] ?> - --><?= $data['diagnosa_sekunder3'] ?></td></tr>
                                <tr><td>&nbsp;&nbsp;4.</td><td><!--<?= $data['kd_diagnosa_sekunder4'] ?> - --><?= $data['diagnosa_sekunder4'] ?></td></tr>
                            </table>
                        </td>
                    </tr>
                    <tr><td style="padding-left: 20px;">- Prosedur/Tindakan Utama</td><td>: <?= $data['kd_prosedur_utama'] ?> - <?= $data['prosedur_utama'] ?></td></tr>
                    <tr><td style="padding-left: 20px; vertical-align: top;">- Prosedur/Tindakan Sekunder</td>
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

        <tr><td class="content-label">Alergi / Reaksi Obat</td><td>:</td><td><?= nl2br($data['alergi']) ?></td></tr>
        <tr><td class="content-label">Diet Selama Perawatan</td><td>:</td><td><?= nl2br($data['diet']) ?></td></tr>
        <tr><td class="content-label">Hasil Lab Yang Belum Selesai (Pending)</td><td>:</td><td><?= nl2br($data['lab_belum']) ?></td></tr>
        <tr><td class="content-label">Instruksi/Anjuran Dan Edukasi (Follow Up)</td><td>:</td><td><?= nl2br($data['edukasi']) ?></td></tr>
    </table>

    <table class="content-table" style="border: none;">
        <tr>
            <td width="15%"><b>Keadaan Pulang</b></td><td width="2%">:</td><td width="30%"><?= $data['keadaan'] ?></td>
            <td width="15%"><b>Cara Keluar</b></td><td width="2%">:</td><td><?= $data['cara_keluar'] ?></td>
        </tr>
        <tr>
            <td><b>Dilanjutkan</b></td><td>:</td><td><?= $data['dilanjutkan'] ?></td>
            <td><b>Tanggal Kontrol</b></td><td>:</td><td><?= $data['kontrol'] ?></td>
        </tr>
    </table>

    <div style="margin-top: 10px; border-bottom: 1px dotted #ccc; padding-bottom: 5px;">
        <b>Obat-obatan waktu pulang :</b><br>
        <?= nl2br($data['obat_pulang']) ?>
    </div>

    <table class="ttd-area">
        <tr>
            <td width="60%"></td>
            <td align="center">
                <?= $setting['kabupaten'] ?>, <?= tgl_indo($tgl_keluar_fix) ?><br>
                Dokter Penanggung Jawab<br>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($qr_content) ?>" style="width: 90px; margin: 10px;">
                <br>
                <b style="text-decoration: underline;"><?= $data['nm_dokter'] ?></b>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
<?php mysqli_close($koneksi); ?>