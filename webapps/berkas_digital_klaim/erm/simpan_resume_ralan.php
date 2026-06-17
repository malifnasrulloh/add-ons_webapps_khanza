<?php
/*
 * File: erm/simpan_resume_ralan.php
 * Fungsi: Generator PDF Resume Ralan + Auto Upload + Smart Delete
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

// 3. CONFIG FILE
$kode_berkas = '001'; // Kode Resume Ralan (GANTI JIKA PERLU)
$upload_dir_abs = dirname($path_root) . "/berkasrawat/pages/upload/";
$upload_dir_web = "pages/upload/"; 

// Validasi
if (!is_dir($upload_dir_abs)) die("Error Folder Upload.");
if (!is_writable($upload_dir_abs)) die("Error Permission.");

// 4. QUERY DATA
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

if(!$data) die("<h3>Data Kosong</h3><p>Resume medis rawat jalan belum diinput.</p>");

// 5. HELPER
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
$regis = new DateTime($data['tgl_registrasi']);
$usia = $regis->diff($lahir);
$umur_str = $usia->y . " Th " . $usia->m . " Bln " . $usia->d . " Hr";

// QR
$kd_dokter = $data['kd_dokter'];
$q_finger = mysqli_query($koneksi, "SELECT SHA1(sidikjari.sidikjari) as finger FROM sidikjari INNER JOIN pegawai ON pegawai.id = sidikjari.id WHERE pegawai.nik = '$kd_dokter'");
$finger_code = $kd_dokter;
if($r_finger = mysqli_fetch_assoc($q_finger)) { if(!empty($r_finger['finger'])) $finger_code = $r_finger['finger']; }
$tgl_qr = $data['tgl_registrasi'];

$qr_content = "Dikeluarkan di " . $setting['nama_instansi'] . ", Kabupaten/Kota " . $setting['kabupaten'] . "\nDitandatangani oleh " . $data['nm_dokter'] . "\nID " . $finger_code . "\n" . $tgl_qr;
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($qr_content);
$ctx = stream_context_create(['http'=> ['timeout' => 5]]);
$qr_raw = @file_get_contents($qr_url, false, $ctx);
$qr_b64 = $qr_raw ? 'data:image/png;base64,' . base64_encode($qr_raw) : ''; 

// 6. RENDER HTML
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        /* Ukuran Font dan Layout disamakan dengan Ranap */
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

    <div class="judul">RESUME MEDIS RAWAT JALAN</div>

    <table class="info-table">
        <tr>
            <td width="50%">
                <table width="100%">
                    <tr><td class="label">Nama Pasien</td><td class="colon">:</td><td><b><?= $data['nm_pasien'] ?></b></td></tr>
                    <tr><td class="label">Umur</td><td class="colon">:</td><td><?= $umur_str ?></td></tr>
                    <tr><td class="label">Poli / Unit</td><td class="colon">:</td><td><?= $data['nm_poli'] ?></td></tr>
                    <tr><td class="label">Jenis Kelamin</td><td class="colon">:</td><td><?= $data['jk']=='L'?'Laki-Laki':'Perempuan' ?></td></tr>
                </table>
            </td>
            <td width="50%">
                <table width="100%">
                    <tr><td class="label">No. Rekam Medis</td><td class="colon">:</td><td><b><?= $data['no_rkm_medis'] ?></b></td></tr>
                    <tr><td class="label">Tgl Lahir</td><td class="colon">:</td><td><?= tgl_indo($data['tgl_lahir']) ?></td></tr>
                    <tr><td class="label">Tgl Periksa</td><td class="colon">:</td><td><?= tgl_indo($data['tgl_registrasi']) ?></td></tr>
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
                                <?php if(!empty($data['diagnosa_sekunder2'])): ?><tr><td>2.</td><td><?= $data['kd_diagnosa_sekunder2'] ?> - <?= $data['diagnosa_sekunder2'] ?></td></tr><?php endif; ?>
                                <?php if(!empty($data['diagnosa_sekunder3'])): ?><tr><td>3.</td><td><?= $data['kd_diagnosa_sekunder3'] ?> - <?= $data['diagnosa_sekunder3'] ?></td></tr><?php endif; ?>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <tr>
            <td colspan="3" style="padding: 0;">
                <table width="100%" style="margin-top: 5px;">
                    <tr><td width="25%"><b>Tindakan / Prosedur :</b></td><td></td></tr>
                    <tr><td style="padding-left: 20px;">- Utama</td><td>: <?= $data['kd_prosedur_utama'] ?> - <?= $data['prosedur_utama'] ?></td></tr>
                    <tr><td style="padding-left: 20px; vertical-align: top;">- Sekunder</td>
                        <td>
                            <table class="nested-table">
                                <?php if(!empty($data['prosedur_sekunder'])): ?><tr><td width="10">1.</td><td><?= $data['kd_prosedur_sekunder'] ?> - <?= $data['prosedur_sekunder'] ?></td></tr><?php endif; ?>
                                <?php if(!empty($data['prosedur_sekunder2'])): ?><tr><td>2.</td><td><?= $data['kd_prosedur_sekunder2'] ?> - <?= $data['prosedur_sekunder2'] ?></td></tr><?php endif; ?>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr><td class="content-label">Kondisi Pulang</td><td>:</td><td><?= $data['kondisi_pulang'] ?></td></tr>
    </table>

    <div style="margin-top: 10px; border-bottom: 1px dotted #ccc; padding-bottom: 5px;">
        <b>Terapi / Obat Pulang :</b><br>
        <?= nl2br($data['obat_pulang']) ?>
    </div>

    <table class="ttd-area">
        <tr>
            <td width="60%"></td>
            <td align="center">
                <?= $setting['kabupaten'] ?>, <?= tgl_indo($tgl_qr) ?><br>
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

// 7. GENERATE PDF & UPLOAD
try {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('dpi', 96);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    
    // Kertas A4 Standar (21 x 29.7) atau Legal (21 x 35)
    // Gunakan Legal/F4 agar panjang seperti Resume Ranap
    $dompdf->setPaper(array(0, 0, 595.28, 992.12), 'portrait');
    
    $dompdf->render();
    $output_pdf = $dompdf->output();

    // NAMA FILE: ResumeRalan_NORAWAT_...
    $clean_rawat = str_replace(['/','\\'], '', $no_rawat);
    $filename = "ResumeRalan_{$clean_rawat}_" . date('YmdHis') . ".pdf";
    $file_path = $upload_dir_abs . $filename;
    $db_path = $upload_dir_web . $filename;

    if (file_put_contents($file_path, $output_pdf) === false) throw new Exception("Gagal menulis file.");

    // SMART DELETE (Hapus ResumeRalan_ lama saja)
    $q_del = "DELETE FROM berkas_digital_perawatan WHERE no_rawat = '$no_rawat' AND kode = '$kode_berkas' AND lokasi_file LIKE '%ResumeRalan_%'";
    mysqli_query($koneksi, $q_del);

    // INSERT BARU
    $q_ins = "INSERT INTO berkas_digital_perawatan (no_rawat, kode, lokasi_file) VALUES ('$no_rawat', '$kode_berkas', '$db_path')";
    
    if(mysqli_query($koneksi, $q_ins)) {
        echo "<script>
            alert('Resume Ralan Berhasil Dibuat!');
            window.location.href = '../lihat_berkas.php?no_rawat=" . urlencode($no_rawat) . "';
        </script>";
    } else {
        throw new Exception("Gagal update DB.");
    }

} catch (Exception $e) {
    die("<h3>Terjadi Kesalahan</h3><p>" . $e->getMessage() . "</p>");
}
?>