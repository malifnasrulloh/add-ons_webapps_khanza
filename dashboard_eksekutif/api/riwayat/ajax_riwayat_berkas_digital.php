<?php
// helpers/ajax/ajax_riwayat_berkas_digital.php
require_once dirname(__DIR__, 2) . '/config/koneksi.php';
require_once 'debug_helper.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Content-Type: application/json');

$no_rkm_medis = $_POST['no_rkm_medis'] ?? $_GET['no_rkm_medis'] ?? '';
$filter_mode  = $_POST['filter_mode'] ?? $_GET['filter_mode'] ?? '5_terakhir';
$tgl_awal     = $_POST['tgl_awal'] ?? $_GET['tgl_awal'] ?? '';
$tgl_akhir    = $_POST['tgl_akhir'] ?? $_GET['tgl_akhir'] ?? '';

if (empty($no_rkm_medis)) {
    echo json_encode(['status' => 'error', 'message' => 'no_rkm_medis tidak valid', 'debug_logs' => dbg_get_logs()]);
    exit;
}

$timeline = [];
$err = '';

try {
    dbg_log('init', 'info', "Mulai fetch berkas_digital_perawatan untuk no_rkm_medis: $no_rkm_medis");

    $sql_reg = "SELECT no_rawat, tgl_registrasi, jam_reg FROM reg_periksa WHERE no_rkm_medis = ?";
    $params_reg = [$no_rkm_medis];

    if ($filter_mode === 'tanggal' && !empty($tgl_awal) && !empty($tgl_akhir)) {
        $sql_reg .= " AND tgl_registrasi BETWEEN ? AND ?";
        $params_reg[] = $tgl_awal;
        $params_reg[] = $tgl_akhir;
    }
    
    $sql_reg .= " ORDER BY tgl_registrasi DESC, jam_reg DESC";
    
    if ($filter_mode === '5_terakhir') {
        $sql_reg .= " LIMIT 5";
    }

    $kunjungan = dbg_query($koneksi_pdo, 'reg_periksa', $sql_reg, $params_reg, $err);
    
    $list_norawat = array_column($kunjungan, 'no_rawat');
    $placeholders = empty($list_norawat) ? "''" : implode(',', array_fill(0, count($list_norawat), '?'));

    if (!empty($list_norawat)) {
        $sql = "SELECT b.no_rawat, b.kode, b.lokasi_file, m.nama as nama_berkas 
                FROM berkas_digital_perawatan b 
                LEFT JOIN master_berkas_digital m ON b.kode = m.kode 
                WHERE b.no_rawat IN ($placeholders)";
        $rows = dbg_query($koneksi_pdo, 'berkas_digital_perawatan', $sql, $list_norawat, $err);

        foreach($rows as $row) {
            $tgl_reg = ''; $jam_reg = '';
            foreach($kunjungan as $k) { 
                if($k['no_rawat'] == $row['no_rawat']) { 
                    $tgl_reg = $k['tgl_registrasi']; 
                    $jam_reg = '23:59:59'; 
                    break; 
                } 
            }
            
            // Format URL (contoh: http://192.168.1.2/webapps/berkasrawat/pages/upload/file.jpg)
            // handle slash trailing logic untuk memastikan URL valid
            $url_base = rtrim($webapps_url ?? 'http://localhost/webapps', '/');
            $file_path = ltrim($row['lokasi_file'], '/');
            $file_url = $url_base . '/berkasrawat/' . $file_path;
            
            $ext = strtolower(pathinfo($row['lokasi_file'], PATHINFO_EXTENSION));
            $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
            
            $info = [
                'Tipe' => $row['nama_berkas'] ?? 'Berkas Digital',
                'Kode' => $row['kode'],
                'url_berkas' => $file_url,
                'is_image' => $is_image ? 'true' : 'false'
            ];

            $timeline[] = [
                'tanggal' => $tgl_reg, 
                'jam' => $jam_reg, 
                'jenis' => 'Berkas Digital', 
                'no_rawat' => $row['no_rawat'],
                'data' => $info
            ];
        }
    }

    dbg_log('final', 'success', 'Total berkas: ' . count($timeline));
    echo json_encode(['status' => 'success', 'data' => $timeline, 'debug_logs' => dbg_get_logs()]);

} catch (Exception $e) {
    dbg_log('fatal', 'error', 'Fatal error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'debug_logs' => dbg_get_logs()]);
}
?>
