<?php
/*
 * File: api/data_rl_3_18_detail.php
 * Fungsi: Menampilkan rincian transaksi obat per grup (Golongan/Kategori/Jenis)
 * Sesuai Guideline: sumber_data_RL3.18.txt
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
if (!isset($_SESSION['casemix_login'])) { http_response_code(403); exit; }

$tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
$mode = $_GET['mode'] ?? 'golongan';
$kode_group = $_GET['id'] ?? '';

// Tentukan Filter WHERE berdasarkan mode
$where_detail = "";
if ($kode_group === 'IS_NULL') {
    if ($mode == 'jenis') $where_detail = " AND (db.kdjns IS NULL OR db.kdjns = '') ";
    elseif ($mode == 'kategori') $where_detail = " AND (db.kode_kategori IS NULL OR db.kode_kategori = '') ";
    else $where_detail = " AND (db.kode_golongan IS NULL OR db.kode_golongan = '') ";
} else {
    if ($mode == 'jenis') $where_detail = " AND db.kdjns = '$kode_group' ";
    elseif ($mode == 'kategori') $where_detail = " AND db.kode_kategori = '$kode_group' ";
    else $where_detail = " AND db.kode_golongan = '$kode_group' ";
}

$sql = "
    SELECT 
        dpo.tgl_perawatan,
        dpo.jam,
        dpo.no_rawat,
        p.nm_pasien,
        db.nama_brng,
        dpo.jml,
        dpo.biaya_obat as harga,
        dpo.total,
        rp.status_lanjut,
        rp.kd_poli
    FROM detail_pemberian_obat dpo
    INNER JOIN reg_periksa rp ON dpo.no_rawat = rp.no_rawat
    INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    INNER JOIN databarang db ON dpo.kode_brng = db.kode_brng
    WHERE dpo.tgl_perawatan BETWEEN ? AND ?
    $where_detail
    ORDER BY dpo.tgl_perawatan DESC, dpo.jam DESC
    LIMIT 2000
";

$stmt = mysqli_prepare($koneksi, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $tgl_awal, $tgl_akhir);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        if($row['kd_poli'] == 'IGDK') $unit = 'IGD';
        else $unit = ($row['status_lanjut'] == 'Ralan') ? 'Rawat Jalan' : 'Rawat Inap';
        $row['unit'] = $unit;
        $data[] = $row;
    }
    echo json_encode(['data' => $data]);
} else {
    echo json_encode(['data' => []]);
}
?>
