<?php
/*
 * File: api/data_additional_widgets.php
 * API endpoint to securely fetch 31 legacy metrics + 3 premium metrics on-demand using PDO.
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

// Make sure session and connection are active
if (!defined('KONEKSI_LOADED')) {
    require_once(dirname(__DIR__) . '/config/koneksi.php');
    define('KONEKSI_LOADED', true);
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Akses ditolak. Silakan login kembali.']);
    exit;
}

$today = date('Y-m-d');
$month = date('Y-m');
$month_pattern = $month . '%';

$response = [
    'general' => [
        'total_rm' => 0,
        'ralan_today' => 0,
        'ranap_today' => 0,
        'total_month' => 0
    ],
    'igd' => [
        'daftar' => 0,
        'dirawat' => 0,
        'belum' => 0,
        'dirujuk' => 0,
        'selesai' => 0,
        'batal' => 0
    ],
    'ralan_kunjungan' => [
        'batal' => 0,
        'bpjs' => 0,
        'tunai' => 0,
        'asuransi' => 0,
        'perusahaan' => 0
    ],
    'ranap_kunjungan' => [
        'masuk' => 0,
        'bpjs' => 0,
        'tunai' => 0,
        'asuransi' => 0,
        'perusahaan' => 0,
        'pulang' => 0
    ],
    'ralan_keuangan' => [
        'total' => 0,
        'tunai' => 0,
        'bpjs' => 0,
        'asuransi' => 0,
        'perusahaan' => 0
    ],
    'ranap_keuangan' => [
        'total' => 0,
        'tunai' => 0,
        'bpjs' => 0,
        'asuransi' => 0,
        'perusahaan' => 0
    ],
    'premium' => [
        'satusehat_rate' => 100.0,
        'dead_stock_value' => 0.0,
        'avg_wait_time' => 0.0
    ]
];

try {
    // 1. GENERAL METRICS
    $response['general']['total_rm'] = (int)$koneksi_pdo->query("SELECT COUNT(*) FROM pasien")->fetchColumn();
    
    $stmt = $koneksi_pdo->prepare("SELECT COUNT(*) FROM reg_periksa WHERE tgl_registrasi = :today AND status_lanjut = 'Ralan'");
    $stmt->execute([':today' => $today]);
    $response['general']['ralan_today'] = (int)$stmt->fetchColumn();
    
    $response['general']['ranap_today'] = (int)$koneksi_pdo->query("SELECT COUNT(*) FROM kamar_inap WHERE stts_pulang = '-'")->fetchColumn();
    
    $stmt = $koneksi_pdo->prepare("SELECT COUNT(*) FROM reg_periksa WHERE tgl_registrasi LIKE :month_pattern");
    $stmt->execute([':month_pattern' => $month_pattern]);
    $response['general']['total_month'] = (int)$stmt->fetchColumn();

    // 2. IGD METRICS
    $stmt = $koneksi_pdo->prepare("SELECT COUNT(*) FROM reg_periksa WHERE tgl_registrasi = :today AND kd_poli = 'IGDK'");
    $stmt->execute([':today' => $today]);
    $response['igd']['daftar'] = (int)$stmt->fetchColumn();

    $stmt = $koneksi_pdo->prepare("SELECT COUNT(*) FROM reg_periksa WHERE tgl_registrasi = :today AND kd_poli = 'IGDK' AND stts = :stts");
    
    $stmt->execute([':today' => $today, ':stts' => 'Dirawat']);
    $response['igd']['dirawat'] = (int)$stmt->fetchColumn();
    
    $stmt->execute([':today' => $today, ':stts' => 'Belum']);
    $response['igd']['belum'] = (int)$stmt->fetchColumn();
    
    $stmt->execute([':today' => $today, ':stts' => 'Dirujuk']);
    $response['igd']['dirujuk'] = (int)$stmt->fetchColumn();
    
    $stmt->execute([':today' => $today, ':stts' => 'Batal']);
    $response['igd']['batal'] = (int)$stmt->fetchColumn();

    $stmt = $koneksi_pdo->prepare("SELECT COUNT(*) FROM reg_periksa WHERE tgl_registrasi = :today AND kd_poli = 'IGDK' AND status_bayar = 'Sudah Bayar'");
    $stmt->execute([':today' => $today]);
    $response['igd']['selesai'] = (int)$stmt->fetchColumn();

    // 3. RALAN KUNJUNGAN METRICS
    $stmt = $koneksi_pdo->prepare("SELECT COUNT(*) FROM reg_periksa WHERE tgl_registrasi = :today AND status_lanjut = 'Ralan' AND stts = 'Batal'");
    $stmt->execute([':today' => $today]);
    $response['ralan_kunjungan']['batal'] = (int)$stmt->fetchColumn();

    $stmt = $koneksi_pdo->prepare("SELECT COUNT(*) FROM reg_periksa WHERE tgl_registrasi = :today AND status_lanjut = 'Ralan' AND kd_pj = :kd_pj");
    
    $stmt->execute([':today' => $today, ':kd_pj' => 'BPJ']);
    $response['ralan_kunjungan']['bpjs'] = (int)$stmt->fetchColumn();
    
    $stmt->execute([':today' => $today, ':kd_pj' => 'A09']);
    $response['ralan_kunjungan']['tunai'] = (int)$stmt->fetchColumn();

    $stmt = $koneksi_pdo->prepare("SELECT COUNT(*) FROM reg_periksa a INNER JOIN penjab b ON a.kd_pj = b.kd_pj WHERE a.tgl_registrasi = :today AND a.status_lanjut = 'Ralan' AND b.kategori = :kategori");
    
    $stmt->execute([':today' => $today, ':kategori' => 'ASURANSI']);
    $response['ralan_kunjungan']['asuransi'] = (int)$stmt->fetchColumn();
    
    $stmt->execute([':today' => $today, ':kategori' => 'PERUSAHAAN']);
    $response['ralan_kunjungan']['perusahaan'] = (int)$stmt->fetchColumn();

    // 4. RANAP KUNJUNGAN METRICS
    $stmt = $koneksi_pdo->prepare("SELECT COUNT(*) FROM kamar_inap WHERE tgl_masuk = :today AND stts_pulang = '-'");
    $stmt->execute([':today' => $today]);
    $response['ranap_kunjungan']['masuk'] = (int)$stmt->fetchColumn();

    $stmt = $koneksi_pdo->prepare("SELECT COUNT(*) FROM kamar_inap WHERE tgl_keluar = :today");
    $stmt->execute([':today' => $today]);
    $response['ranap_kunjungan']['pulang'] = (int)$stmt->fetchColumn();

    $stmt = $koneksi_pdo->prepare("SELECT COUNT(*) FROM reg_periksa a INNER JOIN kamar_inap b ON a.no_rawat = b.no_rawat WHERE b.tgl_masuk = :today AND b.stts_pulang = '-' AND a.status_lanjut = 'Ranap' AND a.kd_pj = :kd_pj");
    $stmt->execute([':today' => $today, ':kd_pj' => 'BPJ']);
    $response['ranap_kunjungan']['bpjs'] = (int)$stmt->fetchColumn();

    $stmt->execute([':today' => $today, ':kd_pj' => 'A09']);
    $response['ranap_kunjungan']['tunai'] = (int)$stmt->fetchColumn();

    $stmt = $koneksi_pdo->prepare("SELECT COUNT(*) FROM reg_periksa a INNER JOIN kamar_inap b ON a.no_rawat = b.no_rawat INNER JOIN penjab c ON a.kd_pj = c.kd_pj WHERE b.tgl_masuk = :today AND b.stts_pulang = '-' AND a.status_lanjut = 'Ranap' AND c.kategori = :kategori");
    
    $stmt->execute([':today' => $today, ':kategori' => 'ASURANSI']);
    $response['ranap_kunjungan']['asuransi'] = (int)$stmt->fetchColumn();
    
    $stmt->execute([':today' => $today, ':kategori' => 'PERUSAHAAN']);
    $response['ranap_kunjungan']['perusahaan'] = (int)$stmt->fetchColumn();

    // 5. RALAN KEUSER/KEUANGAN METRICS
    $stmt = $koneksi_pdo->prepare("SELECT SUM(b.totalbiaya) FROM reg_periksa a INNER JOIN billing b ON a.no_rawat = b.no_rawat WHERE a.status_bayar = 'Sudah Bayar' AND a.status_lanjut = 'Ralan' AND a.tgl_registrasi = :today");
    $stmt->execute([':today' => $today]);
    $response['ralan_keuangan']['total'] = (float)$stmt->fetchColumn();

    $stmt = $koneksi_pdo->prepare("SELECT SUM(b.totalbiaya) FROM reg_periksa a INNER JOIN billing b ON a.no_rawat = b.no_rawat INNER JOIN penjab c ON a.kd_pj = c.kd_pj WHERE a.status_bayar = 'Sudah Bayar' AND a.status_lanjut = 'Ralan' AND c.kategori = :kategori AND a.tgl_registrasi = :today");
    
    $stmt->execute([':today' => $today, ':kategori' => 'TUNAI']);
    $response['ralan_keuangan']['tunai'] = (float)$stmt->fetchColumn();
    
    $stmt->execute([':today' => $today, ':kategori' => 'BPJS']);
    $response['ralan_keuangan']['bpjs'] = (float)$stmt->fetchColumn();
    
    $stmt->execute([':today' => $today, ':kategori' => 'ASURANSI']);
    $response['ralan_keuangan']['asuransi'] = (float)$stmt->fetchColumn();
    
    $stmt->execute([':today' => $today, ':kategori' => 'PERUSAHAAN']);
    $response['ralan_keuangan']['perusahaan'] = (float)$stmt->fetchColumn();

    // 6. RANAP KEUSER/KEUANGAN METRICS
    $stmt = $koneksi_pdo->prepare("SELECT SUM(b.totalbiaya) FROM reg_periksa a INNER JOIN billing b ON a.no_rawat = b.no_rawat WHERE a.status_bayar = 'Sudah Bayar' AND a.status_lanjut = 'Ranap' AND b.tgl_byr = :today");
    $stmt->execute([':today' => $today]);
    $response['ranap_keuangan']['total'] = (float)$stmt->fetchColumn();

    $stmt = $koneksi_pdo->prepare("SELECT SUM(b.totalbiaya) FROM reg_periksa a INNER JOIN billing b ON a.no_rawat = b.no_rawat INNER JOIN penjab c ON a.kd_pj = c.kd_pj WHERE a.status_bayar = 'Sudah Bayar' AND a.status_lanjut = 'Ranap' AND c.kategori = :kategori AND b.tgl_byr = :today");
    
    $stmt->execute([':today' => $today, ':kategori' => 'TUNAI']);
    $response['ranap_keuangan']['tunai'] = (float)$stmt->fetchColumn();
    
    $stmt->execute([':today' => $today, ':kategori' => 'BPJS']);
    $response['ranap_keuangan']['bpjs'] = (float)$stmt->fetchColumn();
    
    $stmt->execute([':today' => $today, ':kategori' => 'ASURANSI']);
    $response['ranap_keuangan']['asuransi'] = (float)$stmt->fetchColumn();
    
    $stmt->execute([':today' => $today, ':kategori' => 'PERUSAHAAN']);
    $response['ranap_keuangan']['perusahaan'] = (float)$stmt->fetchColumn();

    // 7. PREMIUM METRICS (Moved here to optimize initial page speed)
    // A. SatuSehat Rate
    $stmt_ss_total = $koneksi_pdo->prepare("SELECT COUNT(*) FROM reg_periksa WHERE tgl_registrasi = :today AND stts <> 'Batal' AND status_bayar = 'Sudah Bayar'");
    $stmt_ss_total->execute([':today' => $today]);
    $ss_total = (int)$stmt_ss_total->fetchColumn();

    $stmt_ss_sent = $koneksi_pdo->prepare("SELECT COUNT(*) FROM reg_periksa rp INNER JOIN satu_sehat_encounter sse ON rp.no_rawat = sse.no_rawat WHERE rp.tgl_registrasi = :today AND rp.stts <> 'Batal' AND rp.status_bayar = 'Sudah Bayar'");
    $stmt_ss_sent->execute([':today' => $today]);
    $ss_sent = (int)$stmt_ss_sent->fetchColumn();

    $response['premium']['satusehat_rate'] = $ss_total > 0 ? round(($ss_sent / $ss_total) * 100, 1) : 100.0;

    // B. Dead Stock Value
    $cutoff_start = date('Y-m-d', strtotime('-3 months'));
    $cutoff_end = $today;
    $sql_dead = "SELECT COALESCE(SUM(g.stok * d.dasar), 0) as total_val
                 FROM gudangbarang g
                 INNER JOIN databarang d ON g.kode_brng = d.kode_brng
                 WHERE g.stok > 0 
                   AND NOT EXISTS (
                       SELECT 1 
                       FROM riwayat_barang_medis r 
                       WHERE r.kode_brng = g.kode_brng 
                         AND r.kd_bangsal = g.kd_bangsal 
                         AND r.keluar > 0 
                         AND r.tanggal BETWEEN :cutoff_start AND :cutoff_end
                   )";
    $stmt_dead = $koneksi_pdo->prepare($sql_dead);
    $stmt_dead->execute([':cutoff_start' => $cutoff_start, ':cutoff_end' => $cutoff_end]);
    $response['premium']['dead_stock_value'] = (float)$stmt_dead->fetchColumn();

    // C. Outpatient Avg Wait Time
    $sql_wait = "SELECT COALESCE(AVG(TIME_TO_SEC(TIMEDIFF(
                    (SELECT jam_rawat FROM pemeriksaan_ralan WHERE no_rawat = r.no_rawat ORDER BY tgl_perawatan ASC, jam_rawat ASC LIMIT 1),
                    r.jam_reg
                 ))) / 60, 0) as avg_wait
                 FROM reg_periksa r
                 WHERE r.tgl_registrasi = :today AND r.stts <> 'Batal' AND r.status_lanjut = 'Ralan'";
    $stmt_wait = $koneksi_pdo->prepare($sql_wait);
    $stmt_wait->execute([':today' => $today]);
    $response['premium']['avg_wait_time'] = round((float)$stmt_wait->fetchColumn(), 1);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal memproses data metrik: ' . $e->getMessage()]);
    exit;
}

echo json_encode($response);
?>
