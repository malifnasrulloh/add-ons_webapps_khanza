<?php
/*
 * File: api/data_top_penyakit.php (FIXED PATH & SESSION)
 */
error_reporting(0); // Matikan error text agar JSON tidak rusak
ini_set('display_errors', 0);

// 1. KONEKSI DATABASE (Jalur Standar Khanza)
if(file_exists(__DIR__ . '/../../conf/conf.php')) {
    require_once(__DIR__ . '/../../conf/conf.php');
} else {
    require_once(__DIR__ . '/../conf/conf.php');
}

header('Content-Type: application/json');
$koneksi = bukakoneksi();

// 2. CEK SESI (Sesuai Dashboard)
session_start();
if (!isset($_SESSION['casemix_login'])) {
    http_response_code(403); 
    echo json_encode(['error' => 'Sesi habis, silakan login ulang.']); 
    exit;
}

// 3. AMBIL PARAMETER
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$status_lanjut = isset($_GET['status_lanjut']) ? $_GET['status_lanjut'] : '';

// 4. QUERY AGREGASI
$where_tambahan = "";
$params = [$tgl_awal, $tgl_akhir];
$types = "ss";

if (!empty($status_lanjut)) {
    $where_tambahan = " AND reg_periksa.status_lanjut = ? ";
    $params[] = $status_lanjut;
    $types .= "s";
}

$sql = "
    SELECT 
        dp.kd_penyakit,
        pny.nm_penyakit,
        SUM(IF(reg_periksa.stts_daftar = 'Baru' AND p.jk = 'L', 1, 0)) as baru_l,
        SUM(IF(reg_periksa.stts_daftar = 'Baru' AND p.jk = 'P', 1, 0)) as baru_p,
        SUM(IF(reg_periksa.stts_daftar = 'Lama' AND p.jk = 'L', 1, 0)) as lama_l,
        SUM(IF(reg_periksa.stts_daftar = 'Lama' AND p.jk = 'P', 1, 0)) as lama_p,
        COUNT(dp.no_rawat) as total_kunjungan
    FROM diagnosa_pasien dp
    INNER JOIN reg_periksa ON dp.no_rawat = reg_periksa.no_rawat
    INNER JOIN penyakit pny ON dp.kd_penyakit = pny.kd_penyakit
    INNER JOIN pasien p ON reg_periksa.no_rkm_medis = p.no_rkm_medis
    WHERE 
        reg_periksa.tgl_registrasi BETWEEN ? AND ?
        AND reg_periksa.stts != 'Batal'
        AND dp.prioritas = 1
        $where_tambahan
    GROUP BY dp.kd_penyakit
    ORDER BY total_kunjungan DESC
    LIMIT 20
";

$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    $bind_names[] = $types;
    for ($i=0; $i<count($params);$i++) { $bind_names[] = &$params[$i]; }
    call_user_func_array(array($stmt, 'bind_param'), $bind_names);

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $labels = [];
    $data = [];
    $details = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $nama_pendek = strlen($row['nm_penyakit']) > 30 ? substr($row['nm_penyakit'], 0, 30) . '...' : $row['nm_penyakit'];
        
        $labels[] = $row['kd_penyakit'] . ' - ' . $nama_pendek;
        $data[] = (int)$row['total_kunjungan'];
        
        $details[] = [
            'kode'   => $row['kd_penyakit'],
            'nama'   => $row['nm_penyakit'],
            'baru_l' => (int)$row['baru_l'],
            'baru_p' => (int)$row['baru_p'],
            'lama_l' => (int)$row['lama_l'],
            'lama_p' => (int)$row['lama_p'],
            'total'  => (int)$row['total_kunjungan']
        ];
    }
    
    echo json_encode([
        'chart' => ['labels' => $labels, 'data' => $data],
        'table' => $details
    ]);
    
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['chart'=>['labels'=>[],'data'=>[]], 'table'=>[]]);
}
?>