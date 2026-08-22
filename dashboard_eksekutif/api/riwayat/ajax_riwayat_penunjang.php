<?php
// helpers/ajax/ajax_riwayat_penunjang.php
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
    dbg_log('init', 'info', "Mulai fetch penunjang untuk no_rkm_medis: $no_rkm_medis dengan mode: $filter_mode");

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
        
        // 1. Laboratorium (PK & PA)
        $sql_lab = "SELECT l.*, j.nm_perawatan FROM periksa_lab l JOIN jns_perawatan_lab j ON l.kd_jenis_prw = j.kd_jenis_prw WHERE l.no_rawat IN ($placeholders)";
        $lab_headers = dbg_query($koneksi_pdo, 'periksa_lab', $sql_lab, $list_norawat, $err);
        
        foreach ($lab_headers as $row) {
            $sql_det = "SELECT d.*, t.Pemeriksaan as nm_template FROM detail_periksa_lab d JOIN template_laboratorium t ON d.id_template = t.id_template WHERE d.no_rawat = ? AND d.tgl_periksa = ? AND d.jam = ? AND d.kd_jenis_prw = ?";
            $details = dbg_query($koneksi_pdo, 'detail_periksa_lab', $sql_det, [$row['no_rawat'], $row['tgl_periksa'], $row['jam'], $row['kd_jenis_prw']], $err);
            
            $timeline[] = [
                'tanggal' => $row['tgl_periksa'],
                'jam' => $row['jam'],
                'jenis' => 'Laboratorium',
                'no_rawat' => $row['no_rawat'],
                'data' => [
                    'pemeriksaan' => $row['nm_perawatan'],
                    'detail' => $details
                ]
            ];
        }

        // 2. Radiologi
        $sql_rad = "SELECT p.no_rawat, p.tgl_periksa, p.jam, j.nm_perawatan, h.hasil FROM periksa_radiologi p JOIN jns_perawatan_radiologi j ON p.kd_jenis_prw = j.kd_jenis_prw LEFT JOIN hasil_radiologi h ON p.no_rawat=h.no_rawat AND p.tgl_periksa=h.tgl_periksa AND p.jam=h.jam WHERE p.no_rawat IN ($placeholders)";
        $rad_data = dbg_query($koneksi_pdo, 'periksa_radiologi', $sql_rad, $list_norawat, $err);
        
        $rad_grouped = [];
        foreach($rad_data as $row) {
            $key = $row['no_rawat'] . "_" . $row['tgl_periksa'] . "_" . $row['jam'];
            if(!isset($rad_grouped[$key])) {
                $rad_grouped[$key] = [
                    'tanggal' => $row['tgl_periksa'],
                    'jam' => $row['jam'],
                    'jenis' => 'Radiologi',
                    'no_rawat' => $row['no_rawat'],
                    'data' => [
                        'pemeriksaan' => [],
                        'hasil' => $row['hasil']
                    ]
                ];
            }
            $rad_grouped[$key]['data']['pemeriksaan'][] = $row['nm_perawatan'];
        }
        foreach($rad_grouped as $item) {
            $timeline[] = $item;
        }

        // 3. Hasil-hasil Penunjang Lainnya
        $tables_penunjang = [
            'hasil_endoskopi_faring_laring' => 'Hasil Endoskopi Faring Laring',
            'hasil_endoskopi_hidung' => 'Hasil Endoskopi Hidung',
            'hasil_endoskopi_telinga' => 'Hasil Endoskopi Telinga',
            'hasil_pemeriksaan_echo' => 'Hasil Echocardiography',
            'hasil_pemeriksaan_echo_pediatrik' => 'Hasil Echocardiography Pediatrik',
            'hasil_pemeriksaan_ekg' => 'Hasil EKG / Elektrokardiogram',
            'hasil_pemeriksaan_oct' => 'Hasil Pemeriksaan OCT',
            'hasil_pemeriksaan_slit_lamp' => 'Hasil Pemeriksaan Slit Lamp',
            'hasil_pemeriksaan_treadmill' => 'Hasil Pemeriksaan Treadmill',
            'hasil_pemeriksaan_usg' => 'Hasil USG',
            'hasil_pemeriksaan_usg_gynecologi' => 'Hasil USG Gynecologi',
            'hasil_pemeriksaan_usg_neonatus' => 'Hasil USG Neonatus',
            'hasil_pemeriksaan_usg_urologi' => 'Hasil USG Urologi'
        ];

        foreach($tables_penunjang as $table => $label) {
            $sql = "SELECT t.* FROM $table t WHERE t.no_rawat IN ($placeholders)";
            $rows = dbg_query($koneksi_pdo, $table, $sql, $list_norawat, $err);
            
            foreach($rows as $row) {
                // Semua tabel ini memiliki tgl_perawatan, jam_rawat atau tanggal
                $tanggal = $row['tgl_perawatan'] ?? $row['tanggal'] ?? '';
                $jam = $row['jam_rawat'] ?? '';
                
                if (empty($tanggal) && !empty($row['tanggal'])) {
                    $tanggal = date('Y-m-d', strtotime($row['tanggal']));
                    $jam = date('H:i:s', strtotime($row['tanggal']));
                }

                if(empty($tanggal)) continue;
                
                $info = [];
                // Kita ambil kolom kesimpulan / hasil secara dinamis
                if(isset($row['kesimpulan'])) $info['Kesimpulan'] = $row['kesimpulan'];
                if(isset($row['hasil'])) $info['Hasil'] = $row['hasil'];
                if(isset($row['keterangan_kesimpulan'])) $info['Keterangan'] = $row['keterangan_kesimpulan'];
                if(isset($row['diagnosa_klinis'])) $info['Diagnosa Klinis'] = $row['diagnosa_klinis'];
                
                $info['Tipe'] = $label;

                $timeline[] = [
                    'tanggal' => $tanggal,
                    'jam' => $jam,
                    'jenis' => 'Penunjang Medis',
                    'no_rawat' => $row['no_rawat'],
                    'data' => $info
                ];
            }
        }
    }

    dbg_log('final', 'success', 'Total timeline items: ' . count($timeline));
    echo json_encode(['status' => 'success', 'data' => $timeline, 'debug_logs' => dbg_get_logs()]);

} catch (Exception $e) {
    dbg_log('fatal', 'error', 'Fatal error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'debug_logs' => dbg_get_logs()]);
}
?>
