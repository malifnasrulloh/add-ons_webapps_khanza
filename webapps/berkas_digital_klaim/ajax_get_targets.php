<?php
/*
 * File: ajax_get_targets.php
 * Fungsi: Mencari pasien yang punya berkas digital di rentang tgl closing (SECURED)
 */
require_once('csrf.php');

if (!isset($_SESSION['casemix_login'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once('../conf/conf.php');
$koneksi = bukakoneksi();

header('Content-Type: application/json');

$tgl_awal  = isset($_POST['tgl_awal']) ? $_POST['tgl_awal'] : date('Y-m-d');
$tgl_akhir = isset($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : date('Y-m-d');

// PREPARED STATEMENT UNTUK MENCEGAH SQL INJECTION
$query = "SELECT rp.no_rawat, p.nm_pasien
          FROM reg_periksa rp
          JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
          -- Cek Closing Rawat Jalan / Inap
          LEFT JOIN nota_jalan nj ON rp.no_rawat = nj.no_rawat
          LEFT JOIN nota_inap ni ON rp.no_rawat = ni.no_rawat
          -- Hanya ambil yang punya berkas
          INNER JOIN berkas_digital_perawatan bdp ON rp.no_rawat = bdp.no_rawat
          WHERE COALESCE(ni.tanggal, nj.tanggal) BETWEEN ? AND ?
          GROUP BY rp.no_rawat";

$stmt = mysqli_prepare($koneksi, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $tgl_awal, $tgl_akhir);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    
    $data = [];
    while($row = mysqli_fetch_assoc($hasil)) {
        $data[] = [
            'no_rawat' => $row['no_rawat'],
            // Sanitasi nama pasien untuk respons JSON
            'nm_pasien' => htmlspecialchars($row['nm_pasien'], ENT_QUOTES, 'UTF-8')
        ];
    }
    echo json_encode(['status' => 'success', 'data' => $data]);
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database Error']);
}
?>