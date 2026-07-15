<?php
/*
 * File: erm/simpan_laporan_operasi.php
 * Fungsi: Generator PDF Laporan Operasi (Correct Column Mapping)
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

$q_ops = "SELECT * FROM operasi WHERE no_rawat = '$no_rawat' ORDER BY tgl_operasi ASC";
$res_ops = mysqli_query($koneksi, $q_ops);

if(mysqli_num_rows($res_ops) == 0) die("Data Operasi Kosong");

function getNama($koneksi, $tabel, $kolom_kd, $kode, $kolom_nm) {
    if(empty($kode) || $kode == '-') return "-";
    $q = mysqli_query($koneksi, "SELECT $kolom_nm FROM $tabel WHERE $kolom_kd = '$kode'");
    if($r = mysqli_fetch_assoc($q)) return $r[$kolom_nm];
    return "-";
}
function formatTgl($tanggal) {
    if(empty($tanggal) || $tanggal == '0000-00-00') return "-";
    return date('d-m-Y', strtotime($tanggal));
}

$q_set = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($q_set);
$logo_src = 'data:image/jpeg;base64,' . base64_encode($setting['logo']);

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; font-size: 10px; margin: 0; padding: 0; color: #000; }
        table { width: 100%; border-collapse: collapse; page-break-inside: avoid; }
        td, th { vertical-align: top; padding: 2px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        .text-italic { font-style: italic; }
        .uppercase { text-transform: uppercase; }
        .border-top { border-top: 1px solid #000; }
        .border-bottom { border-bottom: 1px solid #000; }
        .border-left { border-left: 1px solid #000; }
        .border-right { border-right: 1px solid #000; }
        .double-border-bottom { border-bottom: 3px double #000; }
        .header-title { font-size: 14px; font-weight: bold; font-style: italic; margin: 5px 0; text-transform: uppercase; }
        .gray-bar { background-color: #d3d3d3; font-weight: bold; text-align: center; padding: 3px; border-top: 1px solid #000; border-bottom: 1px solid #000; font-size: 11px; margin-top: 5px; }
        .gray-sub-bar { background-color: #d3d3d3; padding: 2px 5px; border-top: 1px solid #000; border-bottom: 1px solid #000; font-size: 10px; font-weight: normal; }
        .field-label { font-weight: normal; font-size: 10px; }
        .field-value { font-style: italic; margin-left: 15px; margin-bottom: 2px; font-weight: normal; }
        .report-content { padding: 5px; font-style: italic; min-height: 200px; border-bottom: 1px solid #000; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>

<?php
$counter = 0;
while($d_op = mysqli_fetch_assoc($res_ops)):
    $counter++;
    
    $tgl_target = $d_op['tgl_operasi'];
    $q_laporan = "SELECT * FROM laporan_operasi 
                  WHERE no_rawat = '$no_rawat' 
                  ORDER BY ABS(TIMESTAMPDIFF(SECOND, tanggal, '$tgl_target')) ASC LIMIT 1";
    $d_laporan = mysqli_fetch_assoc(mysqli_query($koneksi, $q_laporan));

    $tgl_operasi = $d_op['tgl_operasi'];
    $jam_mulai = "00:00:00";
    $jam_selesai = "00:00:00";

    if($d_laporan) {
        $tgl_operasi_full = $d_laporan['tanggal']; 
        $tgl_selesai_full = isset($d_laporan['selesaioperasi']) ? $d_laporan['selesaioperasi'] : $d_laporan['tanggal'];
        $jam_mulai = date('H:i:s', strtotime($tgl_operasi_full));
        $jam_selesai = date('H:i:s', strtotime($tgl_selesai_full));
    } else {
        $q_booking = "SELECT jam_mulai, jam_selesai FROM booking_operasi WHERE no_rawat = '$no_rawat' LIMIT 1";
        $d_booking = mysqli_fetch_assoc(mysqli_query($koneksi, $q_booking));
        if($d_booking) {
            $jam_mulai = $d_booking['jam_mulai'];
            $jam_selesai = $d_booking['jam_selesai'];
        }
    }

    $q_pasien = "SELECT 
        p.nm_pasien, p.no_rkm_medis, p.tgl_lahir, p.jk, p.umur,
        rp.no_rawat, rp.status_lanjut,
        k.kd_kamar, b.nm_bangsal 
    FROM reg_periksa rp
    JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    LEFT JOIN kamar_inap ki ON rp.no_rawat = ki.no_rawat 
    LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
    LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
    WHERE rp.no_rawat = '$no_rawat' LIMIT 1";
    $d_pasien = mysqli_fetch_assoc(mysqli_query($koneksi, $q_pasien));

    if(empty($d_pasien['nm_bangsal'])) {
        $q_poli = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nm_poli FROM poliklinik JOIN reg_periksa ON poliklinik.kd_poli=reg_periksa.kd_poli WHERE reg_periksa.no_rawat='$no_rawat'"));
        $ruang = $q_poli['nm_poli'];
    } else {
        $ruang = $d_pasien['nm_bangsal'] . " (Bed: " . $d_pasien['kd_kamar'] . ")";
    }

    // --- PEMETAAN TIM OPERASI FIX ---
    $dokter_bedah     = getNama($koneksi, 'dokter',  'kd_dokter', $d_op['operator1'], 'nm_dokter');
    $dokter_bedah2    = getNama($koneksi, 'dokter',  'kd_dokter', $d_op['operator2'], 'nm_dokter');
    $asisten_bedah    = getNama($koneksi, 'petugas', 'nip',       $d_op['asisten_operator1'], 'nama');
    $asisten_bedah2   = getNama($koneksi, 'petugas', 'nip',       $d_op['asisten_operator2'], 'nama');
    $dokter_anestesi  = getNama($koneksi, 'dokter',  'kd_dokter', $d_op['dokter_anestesi'], 'nm_dokter');
    $asisten_anestesi = getNama($koneksi, 'petugas', 'nip',       $d_op['asisten_anestesi'], 'nama');
    
    // FIX COLUMN: perawaat_resusitas
    $perawat_resusitas = getNama($koneksi, 'petugas', 'nip',      $d_op['perawaat_resusitas'], 'nama');
    
    $instrumen        = getNama($koneksi, 'petugas', 'nip',       $d_op['instrumen'], 'nama');
    $omloop           = getNama($koneksi, 'petugas', 'nip',       $d_op['omloop'], 'nama');
    $dokter_anak      = getNama($koneksi, 'dokter',  'kd_dokter', $d_op['dokter_anak'], 'nm_dokter');
    
    // FIX COLUMN: dokter_umum
    $dokter_umum      = getNama($koneksi, 'dokter',  'kd_dokter', $d_op['dokter_umum'], 'nm_dokter'); 
    
    $bidan            = getNama($koneksi, 'petugas', 'nip',       $d_op['bidan'], 'nama');

    $kd_operator = $d_op['operator1'];
    $finger_code = $kd_operator;
    $q_finger = mysqli_query($koneksi, "SELECT SHA1(sidikjari.sidikjari) as finger FROM sidikjari INNER JOIN pegawai ON pegawai.id = sidikjari.id WHERE pegawai.nik = '$kd_operator'");
    if($r_finger = mysqli_fetch_assoc($q_finger)) { if(!empty($r_finger['finger'])) $finger_code = $r_finger['finger']; }

    $qr_content = "Dikeluarkan di " . $setting['nama_instansi'] . ", Kabupaten/Kota " . $setting['kabupaten'] . "\nDitandatangani secara elektronik oleh " . $dokter_bedah . "\nID " . $finger_code . "\n" . $tgl_operasi;
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($qr_content);
    $ctx = stream_context_create(['http'=> ['timeout' => 5]]);
    $qr_raw = @file_get_contents($qr_url, false, $ctx);
    $qr_api = $qr_raw ? 'data:image/png;base64,' . base64_encode($qr_raw) : '';

    if($counter > 1) {
        echo '<div class="page-break"></div>';
    }
    
    include 'layout_laporan_operasi.php';

endwhile;
?>

</body>
</html>
<?php
$html = ob_get_clean();

try {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
	$customPaper = array(0, 0, 595.28, 935.43);
	$dompdf->setPaper($customPaper, 'portrait');
    $dompdf->render();
    $output = $dompdf->output();

    $clean_rawat = str_replace(['/','\\'], '', $no_rawat);
    $filename = "LaporanOp_{$clean_rawat}_" . date('YmdHis') . ".pdf";
    $abs_path = dirname(dirname(__DIR__)) . "/berkasrawat/pages/upload/" . $filename;
    $db_path = "pages/upload/" . $filename;
    $kode_berkas = '014'; 

    if(file_put_contents($abs_path, $output)){
        mysqli_query($koneksi, "DELETE FROM berkas_digital_perawatan WHERE no_rawat='$no_rawat' AND kode='$kode_berkas' AND lokasi_file LIKE '%LaporanOp_%'");
        mysqli_query($koneksi, "INSERT INTO berkas_digital_perawatan (no_rawat, kode, lokasi_file) VALUES ('$no_rawat', '$kode_berkas', '$db_path')");
        echo "<script>alert('Laporan Operasi Tersimpan!'); window.close();</script>";
    }
} catch (Exception $e) {
    die("Error PDF: " . $e->getMessage());
}
?>