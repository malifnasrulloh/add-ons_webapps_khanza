<?php
// helpers/ajax/ajax_riwayat_registrasi.php
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
    dbg_log('init', 'info', "Mulai fetch registrasi untuk no_rkm_medis: $no_rkm_medis dengan mode: $filter_mode");

    $sql_reg = "SELECT r.no_rawat, r.tgl_registrasi as tanggal, r.jam_reg as jam, r.status_lanjut, r.stts, p.nm_poli, d.nm_dokter 
                FROM reg_periksa r 
                JOIN poliklinik p ON r.kd_poli = p.kd_poli 
                JOIN dokter d ON r.kd_dokter = d.kd_dokter 
                WHERE r.no_rkm_medis = ?";
    $params_reg = [$no_rkm_medis];

    if ($filter_mode === 'tanggal' && !empty($tgl_awal) && !empty($tgl_akhir)) {
        $sql_reg .= " AND r.tgl_registrasi BETWEEN ? AND ?";
        $params_reg[] = $tgl_awal;
        $params_reg[] = $tgl_akhir;
    }
    
    $sql_reg .= " ORDER BY r.tgl_registrasi DESC, r.jam_reg DESC";
    
    if ($filter_mode === '5_terakhir') {
        $sql_reg .= " LIMIT 5";
    }

    // 1. Data Registrasi Kunjungan
    $rows_reg = dbg_query($koneksi_pdo, 'reg_periksa', $sql_reg, $params_reg, $err);
    
    // Simpan no_rawat array untuk query detail (lebih efisien)
    $list_norawat = array_column($rows_reg, 'no_rawat');
    $placeholders = empty($list_norawat) ? "''" : implode(',', array_fill(0, count($list_norawat), '?'));

    foreach ($rows_reg as $k) {
        $timeline[] = [
            'tanggal' => $k['tanggal'],
            'jam' => $k['jam'],
            'jenis' => 'Registrasi',
            'no_rawat' => $k['no_rawat'],
            'data' => [
                'status_lanjut' => $k['status_lanjut'],
                'poli' => $k['nm_poli'],
                'dokter' => $k['nm_dokter'],
                'status' => $k['stts']
            ]
        ];
    }

    if (!empty($list_norawat)) {
        // Data Triase IGD
        $rows_triase = dbg_query($koneksi_pdo, 'data_triase_igd', "SELECT * FROM data_triase_igd WHERE no_rawat IN ($placeholders)", $list_norawat, $err);
        foreach ($rows_triase as $row) {
            $dt = date('Y-m-d', strtotime($row['tgl_kunjungan']));
            $tm = date('H:i:s', strtotime($row['tgl_kunjungan']));
            $timeline[] = [
                'tanggal' => $dt, 'jam' => $tm, 'jenis' => 'Data Triase IGD', 'no_rawat' => $row['no_rawat'],
                'data' => [
                    'Macam Kasus' => $row['kode_kasus'] ?? '',
                    'Keluhan Utama / Alasan Kedatangan' => ($row['alasan_kedatangan'] ?? '') . ' (' . ($row['keterangan_kedatangan'] ?? '') . ')',
                    'Cara Masuk' => $row['cara_masuk'] ?? '', 'Transportasi' => $row['alat_transportasi'] ?? '',
                    'TD' => $row['tekanan_darah'] ?? '', 'Nadi' => $row['nadi'] ?? '', 'Suhu' => $row['suhu'] ?? '',
                    'RR' => $row['pernapasan'] ?? '', 'SpO2' => $row['saturasi_o2'] ?? '', 'Nyeri' => $row['nyeri'] ?? ''
                ]
            ];
        }

        // Triase IGD Primer
        $rows_t_primer = dbg_query($koneksi_pdo, 'data_triase_igdprimer', "SELECT t.*, p.nama as nm_petugas FROM data_triase_igdprimer t LEFT JOIN petugas p ON t.nik=p.nip WHERE t.no_rawat IN ($placeholders)", $list_norawat, $err);
        foreach ($rows_t_primer as $row) {
            $dt = date('Y-m-d', strtotime($row['tanggaltriase']));
            $tm = date('H:i:s', strtotime($row['tanggaltriase']));
            $timeline[] = [
                'tanggal' => $dt, 'jam' => $tm, 'jenis' => 'Triase Primer', 'no_rawat' => $row['no_rawat'],
                'data' => [
                    'Keluhan Utama' => $row['keluhan_utama'] ?? '', 'Kebutuhan Khusus' => $row['kebutuhan_khusus'] ?? '',
                    'Catatan' => $row['catatan'] ?? '', 'Plan' => $row['plan'] ?? '', 'Petugas' => $row['nm_petugas'] ?? ''
                ]
            ];
        }

        // Triase IGD Sekunder
        $rows_t_sekunder = dbg_query($koneksi_pdo, 'data_triase_igdsekunder', "SELECT t.*, p.nama as nm_petugas FROM data_triase_igdsekunder t LEFT JOIN petugas p ON t.nik=p.nip WHERE t.no_rawat IN ($placeholders)", $list_norawat, $err);
        foreach ($rows_t_sekunder as $row) {
            $dt = date('Y-m-d', strtotime($row['tanggaltriase']));
            $tm = date('H:i:s', strtotime($row['tanggaltriase']));
            $timeline[] = [
                'tanggal' => $dt, 'jam' => $tm, 'jenis' => 'Triase Sekunder', 'no_rawat' => $row['no_rawat'],
                'data' => [
                    'Anamnesa Singkat' => $row['anamnesa_singkat'] ?? '', 'Catatan' => $row['catatan'] ?? '', 'Plan' => $row['plan'] ?? '', 'Petugas' => $row['nm_petugas'] ?? ''
                ]
            ];
        }
    }

    dbg_log('final', 'success', 'Total timeline items: ' . count($timeline));
    echo json_encode(['status' => 'success', 'data' => $timeline, 'debug_logs' => dbg_get_logs()]);

} catch (Exception $e) {
    dbg_log('fatal', 'error', 'Fatal error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'debug_logs' => dbg_get_logs()]);
}
?>
