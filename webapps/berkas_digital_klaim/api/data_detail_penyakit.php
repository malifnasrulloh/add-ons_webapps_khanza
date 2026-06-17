<?php
/*
 * File: api/data_detail_penyakit.php (FIXED PATH & SESSION)
 */
error_reporting(0);
ini_set('display_errors', 0);

if(file_exists(__DIR__ . '/../../conf/conf.php')) {
    require_once(__DIR__ . '/../../conf/conf.php');
} else {
    require_once(__DIR__ . '/../conf/conf.php');
}

header('Content-Type: application/json');
$koneksi = bukakoneksi();

session_start();
if (!isset($_SESSION['casemix_login'])) {
    http_response_code(403); exit;
}

$tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
$kd_penyakit = $_GET['kd_penyakit'] ?? '';
$status_lanjut = $_GET['status_lanjut'] ?? '';

if(empty($kd_penyakit)) { echo json_encode(['data' => []]); exit; }

$where_status = "";
$params = [$tgl_awal, $tgl_akhir];
$types = "ss";

if (!empty($status_lanjut)) {
    $where_status = " AND reg_periksa.status_lanjut = ? ";
    $params[] = $status_lanjut;
    $types .= "s";
}
$params[] = $kd_penyakit;
$types .= "s";

$sql = "
    SELECT 
        reg_periksa.no_rawat,
        reg_periksa.tgl_registrasi,
        reg_periksa.no_rkm_medis,
        reg_periksa.stts_daftar,
        pasien.nm_pasien,
        pasien.jk,
        pasien.umur,
        dokter.nm_dokter,
        penjab.png_jawab,
        CONCAT(kelurahan.nm_kel, ', ', kecamatan.nm_kec, ', ', kabupaten.nm_kab) as alamat_lengkap
    FROM diagnosa_pasien
    INNER JOIN reg_periksa ON diagnosa_pasien.no_rawat = reg_periksa.no_rawat
    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
    INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
    INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
    LEFT JOIN kelurahan ON pasien.kd_kel = kelurahan.kd_kel
    LEFT JOIN kecamatan ON pasien.kd_kec = kecamatan.kd_kec
    LEFT JOIN kabupaten ON pasien.kd_kab = kabupaten.kd_kab
    WHERE 
        diagnosa_pasien.prioritas = 1
        AND reg_periksa.tgl_registrasi BETWEEN ? AND ?
        $where_status
        AND diagnosa_pasien.kd_penyakit = ?
    ORDER BY reg_periksa.tgl_registrasi DESC
";

$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    $bind_names[] = $types;
    for ($i=0; $i<count($params);$i++) { $bind_names[] = &$params[$i]; }
    call_user_func_array(array($stmt, 'bind_param'), $bind_names);

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    
    echo json_encode(['data' => $data]);
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['data' => []]);
}
?>