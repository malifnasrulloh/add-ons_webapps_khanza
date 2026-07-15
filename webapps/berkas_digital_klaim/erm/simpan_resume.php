<?php
/*
 * File: erm/simpan_resume.php
 * Fungsi: Generator PDF - Fix Size 21x35cm & Signature Header
 */

// 1. DEBUGGING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['casemix_login'])) {
    die("<h3>Akses Ditolak</h3><p>Sesi login tidak ditemukan.</p>");
}

// 2. PATH SETUP
$path_root = dirname(__DIR__); 
$path_conf = dirname($path_root) . '/conf/conf.php';
$path_vendor = $path_root . '/vendor/autoload.php';

if (!file_exists($path_conf)) die("Error Config");
if (!file_exists($path_vendor)) die("Error Library DomPDF");

require_once($path_conf);
require_once($path_vendor);

use Dompdf\Dompdf;
use Dompdf\Options;

$koneksi = bukakoneksi();
$no_rawat = isset($_GET['no_rawat']) ? validTeks4($_GET['no_rawat'], 20) : '';

// 3. CONFIG & VALIDASI
$kode_berkas = '001'; 
$upload_dir_abs = dirname($path_root) . "/berkasrawat/pages/upload/";
$upload_dir_web = "pages/upload/"; 

if (!is_dir($upload_dir_abs)) die("<h3>Error Folder</h3><p>$upload_dir_abs</p>");
if (!is_writable($upload_dir_abs)) die("<h3>Error Permission</h3><p>Folder tujuan tidak bisa ditulisi.</p>");

// 4. QUERY DATA
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

if(!$data) die("<h3>Data Kosong</h3><p>Resume medis belum diinput untuk No. Rawat: $no_rawat</p>");

// 5. HELPER DATA
$q_set = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($q_set);

function getLogoBase64($koneksi) {
    $q = mysqli_query($koneksi, "SELECT logo FROM setting LIMIT 1");
    if($r = mysqli_fetch_assoc($q)) {
        return 'data:image/jpeg;base64,' . base64_encode($r['logo']);
    }
    return '';
}
$logo_b64 = getLogoBase64($koneksi);

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

// QR Code
$kd_dokter = $data['kd_dokter'];
$q_finger = mysqli_query($koneksi, "SELECT SHA1(sidikjari.sidikjari) as finger FROM sidikjari INNER JOIN pegawai ON pegawai.id = sidikjari.id WHERE pegawai.nik = '$kd_dokter'");
$finger_code = $kd_dokter;
if($r_finger = mysqli_fetch_assoc($q_finger)) {
    if(!empty($r_finger['finger'])) $finger_code = $r_finger['finger'];
}
$tgl_keluar_fix = !empty($data['tgl_keluar']) && $data['tgl_keluar'] != '0000-00-00' ? $data['tgl_keluar'] : date('Y-m-d');

$qr_content = "Dikeluarkan di " . $setting['nama_instansi'] . ", Kabupaten/Kota " . $setting['kabupaten'] . "\nDitandatangani secara elektronik oleh " . $data['nm_dokter'] . "\nID " . $finger_code . "\n" . $tgl_keluar_fix;
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($qr_content);
$ctx = stream_context_create(['http'=> ['timeout' => 5]]);
$qr_raw = @file_get_contents($qr_url, false, $ctx);
$qr_b64 = $qr_raw ? 'data:image/png;base64,' . base64_encode($qr_raw) : ''; 

// 6. RENDER HTML (SAMA PERSIS DENGAN CETAK_RESUME.PHP)
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        @page { margin: 20px 30px; }
        body { font-family: Tahoma, Verdana, Segoe, sans-serif; font-size: 11px; color: #000; line-height: 1.3; }
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
        .ttd-area { margin-top: 20px; width: 100%; page-break-inside: avoid; }
    </style>
</head>
<body>
    <table class="kop-table">
        <tr>
            <td width="60" align="center"><img src="<?= $logo_b64 ?>" width="50"></td>
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
                <?php if($qr_b64): ?>
                    <img src="<?= $qr_b64 ?>" width="90" style="margin: 5px;">
                <?php else: ?>
                    <br><br><br>
                <?php endif; ?>
                <br>
                <b style="text-decoration: underline;"><?= $data['nm_dokter'] ?></b>
            </td>
        </tr>
    </table>
</body>
</html>
<?php
$html = ob_get_clean();

// --------------------------------------------------------------------------
// 7. GENERATE PDF (SIZE: 21 x 35 cm)
// --------------------------------------------------------------------------
try {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('dpi', 96);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    
    // Width 21 cm (595.28 pt) x Height 35 cm (992.12 pt)
    $dompdf->setPaper(array(0, 0, 595.28, 992.12), 'portrait');
    
    $dompdf->render();
    $output_pdf = $dompdf->output();

    $clean_rawat = str_replace(['/','\\'], '', $no_rawat);
    $filename = "Resume_{$clean_rawat}_" . date('YmdHis') . ".pdf";
    $file_path = $upload_dir_abs . $filename;
    $db_path = $upload_dir_web . $filename;

    if (file_put_contents($file_path, $output_pdf) === false) {
        throw new Exception("Gagal menulis file PDF.");
    }

    // Hapus Resume Lama saja (Jangan hapus SEP/Surat Kontrol meski kodenya sama)
	// Kita cari yang lokasi_file mengandung kata "Resume_"
	$q_del = "DELETE FROM berkas_digital_perawatan 
          WHERE no_rawat = '$no_rawat' 
          AND kode = '$kode_berkas' 
          AND lokasi_file LIKE '%Resume_%'";
    mysqli_query($koneksi, $q_del);

    $q_ins = "INSERT INTO berkas_digital_perawatan (no_rawat, kode, lokasi_file) VALUES ('$no_rawat', '$kode_berkas', '$db_path')";
    
    if(mysqli_query($koneksi, $q_ins)) {
        echo "<script>
            alert('Resume Medis Berhasil Dibuat!');
            window.location.href = '../lihat_berkas.php?no_rawat=" . urlencode($no_rawat) . "';
        </script>";
    } else {
        throw new Exception("Gagal update database.");
    }

} catch (Exception $e) {
    die("<h3>Terjadi Kesalahan</h3><p>" . $e->getMessage() . "</p>");
}
?>