<?php
/*
 * File: api/data_ppra.php
 * Fungsi: API untuk mengambil data pemberian antibiotik / obat (PPRA) dengan filter dinamis
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
    http_response_code(403); 
    echo json_encode(['data' => [], 'error' => 'Akses Ditolak']); 
    exit; 
}

$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
$kode_golongan = $_GET['kode_golongan'] ?? '';
$nama_brng = $_GET['nama_brng'] ?? '';
$letak_barang = $_GET['letak_barang'] ?? '';

// Build dynamic WHERE clause
$where_clauses = ["dpo.tgl_perawatan BETWEEN ? AND ?"];
$params = [$tgl_awal, $tgl_akhir];
$types = "ss";

if (!empty($kode_golongan)) {
    $where_clauses[] = "db.kode_golongan = ?";
    $params[] = $kode_golongan;
    $types .= "s";
}

if (!empty($nama_brng)) {
    $where_clauses[] = "db.nama_brng LIKE ?";
    $params[] = "%" . $nama_brng . "%";
    $types .= "s";
}

if (!empty($letak_barang)) {
    $where_clauses[] = "db.letak_barang LIKE ?";
    $params[] = "%" . $letak_barang . "%";
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

$sql = "
    SELECT 
        dpo.tgl_perawatan,
        dpo.jam,
        dpo.no_rawat,
        p.nm_pasien,
        rp.no_rkm_medis,
        CONCAT(rp.umurdaftar, ' ', rp.sttsumur) as usia,
        COALESCE(d_dpjp.nm_dokter, dr_reg.nm_dokter) as dpjp,
        db.nama_brng,
        db.letak_barang,
        (SELECT COALESCE(SUM(ki.lama), 0) FROM kamar_inap ki WHERE ki.no_rawat = rp.no_rawat) as los,
        -- Ruangan logic: actual room of stay in kamar_inap -> kamar -> bangsal (with fallback)
        COALESCE(
            (SELECT b.nm_bangsal 
             FROM kamar_inap ki 
             INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar 
             INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal 
             WHERE ki.no_rawat = dpo.no_rawat 
               AND CONCAT(dpo.tgl_perawatan, ' ', dpo.jam) BETWEEN CONCAT(ki.tgl_masuk, ' ', ki.jam_masuk) 
               AND CONCAT(IF(ki.tgl_keluar = '0000-00-00' OR ki.tgl_keluar IS NULL, '2035-12-31', ki.tgl_keluar), ' ', IF(ki.jam_keluar IS NULL OR ki.jam_keluar = '00:00:00', '23:59:59', ki.jam_keluar))
             LIMIT 1),
            (SELECT b.nm_bangsal 
             FROM kamar_inap ki 
             INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar 
             INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal 
             WHERE ki.no_rawat = dpo.no_rawat 
             ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC 
             LIMIT 1),
            poli.nm_poli,
            '-'
        ) AS ruangan,
        -- Diagnosa logic: primary ICD-10 diagnosis with fallback to kamar_inap.diagnosa_awal
        COALESCE(
            NULLIF(GROUP_CONCAT(DISTINCT CONCAT(dp.kd_penyakit, ' - ', pen.nm_penyakit) SEPARATOR '; '), ''),
            (SELECT ki.diagnosa_awal 
             FROM kamar_inap ki 
             WHERE ki.no_rawat = dpo.no_rawat 
             ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC 
             LIMIT 1),
            '-'
        ) AS diagnosa,
        -- Check if resume exists and is not blank (1 = Yes, 0 = No)
        (EXISTS(SELECT 1 FROM resume_pasien 
                WHERE no_rawat = rp.no_rawat 
                  AND (TRIM(COALESCE(keluhan_utama, '')) NOT IN ('', '-') 
                       OR TRIM(COALESCE(diagnosa_utama, '')) NOT IN ('', '-')))
         OR EXISTS(SELECT 1 FROM resume_pasien_ranap 
                   WHERE no_rawat = rp.no_rawat 
                     AND (TRIM(COALESCE(keluhan_utama, '')) NOT IN ('', '-') 
                          OR TRIM(COALESCE(diagnosa_utama, '')) NOT IN ('', '-')))) AS ada_resume
    FROM detail_pemberian_obat dpo
    INNER JOIN reg_periksa rp ON dpo.no_rawat = rp.no_rawat
    INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    INNER JOIN databarang db ON dpo.kode_brng = db.kode_brng
    LEFT JOIN dpjp_ranap drp ON rp.no_rawat = drp.no_rawat
    LEFT JOIN dokter d_dpjp ON drp.kd_dokter = d_dpjp.kd_dokter
    LEFT JOIN dokter dr_reg ON rp.kd_dokter = dr_reg.kd_dokter
    LEFT JOIN poliklinik poli ON rp.kd_poli = poli.kd_poli
    LEFT JOIN diagnosa_pasien dp ON rp.no_rawat = dp.no_rawat AND dp.prioritas = 1
    LEFT JOIN penyakit pen ON dp.kd_penyakit = pen.kd_penyakit
    WHERE $where_sql
    GROUP BY dpo.no_rawat, dpo.tgl_perawatan, dpo.jam, dpo.kode_brng
    ORDER BY dpo.tgl_perawatan DESC, dpo.jam DESC
    LIMIT 2000
";

$stmt = mysqli_prepare($koneksi, $sql);
if ($stmt) {
    // Dynamic binding of parameters
    $bind_params = [$stmt, $types];
    foreach ($params as $key => $value) {
        $bind_params[] = &$params[$key];
    }
    call_user_func_array('mysqli_stmt_bind_param', $bind_params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        // Konversi bulan menggunakan helper SIMRS Khanza
        $row['bulan'] = konversiBulan(date('m', strtotime($row['tgl_perawatan'])));
        $data[] = $row;
    }
    echo json_encode(['data' => $data]);
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['data' => [], 'error' => mysqli_error($koneksi)]);
}

mysqli_close($koneksi);
?>
