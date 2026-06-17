<?php
/*
 * File: api/data_rl_3_18.php
 * Fungsi: Laporan RL 3.18 Farmasi Resep dengan Grouping Dinamis (Golongan/Kategori/Jenis)
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
if (!isset($_SESSION['casemix_login'])) {
    http_response_code(403); echo json_encode(['error' => 'Akses ditolak.']); exit;
}

// 1. PARAMETER
$tgl_awal   = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir  = $_GET['tgl_akhir'] ?? date('Y-m-d');
$mode       = $_GET['mode'] ?? 'golongan'; 

// 2. TENTUKAN KOLOM & JOIN BERDASARKAN MODE
$select_group = "";
$join_group   = "";
$group_by     = "";
$col_id       = "";

switch ($mode) {
    case 'jenis':
        $select_group = "COALESCE(j.nama, 'Tanpa Jenis') as nama_group";
        $col_id       = "db.kdjns as kode_group";
        $join_group   = "LEFT JOIN jenis j ON db.kdjns = j.kdjns";
        $group_by     = "db.kdjns";
        break;
        
    case 'kategori':
        $select_group = "COALESCE(kb.nama, 'Tanpa Kategori') as nama_group";
        $col_id       = "db.kode_kategori as kode_group";
        $join_group   = "LEFT JOIN kategori_barang kb ON db.kode_kategori = kb.kode";
        $group_by     = "db.kode_kategori";
        break;

    case 'golongan':
    default:
        $select_group = "COALESCE(gb.nama, 'Tanpa Golongan') as nama_group";
        $col_id       = "db.kode_golongan as kode_group";
        $join_group   = "LEFT JOIN golongan_barang gb ON db.kode_golongan = gb.kode";
        $group_by     = "db.kode_golongan";
        break;
}

// 3. QUERY UTAMA (COUNT R/ sesuai Juknis 6.3)
$sql = "
    SELECT 
        $select_group,
        $col_id,
        COUNT(IF(rp.status_lanjut = 'Ralan' AND rp.kd_poli != 'IGDK', 1, NULL)) as jml_ralan,
        COUNT(IF(rp.kd_poli = 'IGDK', 1, NULL)) as jml_igd,
        COUNT(IF(rp.status_lanjut = 'Ranap', 1, NULL)) as jml_ranap,
        COUNT(*) as total_semua
    FROM detail_pemberian_obat dpo
    INNER JOIN reg_periksa rp ON dpo.no_rawat = rp.no_rawat
    INNER JOIN databarang db ON dpo.kode_brng = db.kode_brng
    $join_group
    WHERE dpo.tgl_perawatan BETWEEN ? AND ?
    GROUP BY $group_by
    ORDER BY nama_group ASC
";

$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $tgl_awal, $tgl_akhir);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['jml_ralan'] = (float)$row['jml_ralan'];
        $row['jml_igd']   = (float)$row['jml_igd'];
        $row['jml_ranap'] = (float)$row['jml_ranap'];
        $row['total_semua'] = (float)$row['total_semua'];
        if(empty($row['kode_group'])) $row['kode_group'] = 'IS_NULL'; 
        $data[] = $row;
    }
    echo json_encode(['data' => $data]);
    mysqli_stmt_close($stmt);
} else {
    http_response_code(500);
    echo json_encode(['error' => mysqli_error($koneksi)]);
}
?>
