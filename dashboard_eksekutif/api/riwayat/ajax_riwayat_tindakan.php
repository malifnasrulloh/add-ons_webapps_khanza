<?php
// helpers/ajax/ajax_riwayat_tindakan.php
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

try {
    dbg_log('init', 'info', "Mulai fetch tindakan untuk no_rkm_medis: $no_rkm_medis dengan mode: $filter_mode");

    $err = '';
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

    if (empty($list_norawat)) {
        dbg_log('init', 'warning', 'Tidak ada kunjungan ditemukan untuk pasien ini');
        echo json_encode(['status' => 'success', 'data' => [], 'debug_logs' => dbg_get_logs()]);
        exit;
    }

    dbg_log('init', 'success', count($list_norawat) . ' kunjungan ditemukan');
    $placeholders = implode(',', array_fill(0, count($list_norawat), '?'));

    // ================================================================
    // 1. TINDAKAN RALAN & RANAP — 6 tabel, try/catch individual
    //    Catatan skema kolom:
    //    - rawat_jl_dr   : biaya_rawat, tarif_tindakandr, bagian_rs(?), bhp, kso, menejemen
    //    - rawat_jl_pr   : biaya_rawat, bhp (tidak ada kso/menejemen)
    //    - rawat_jl_drpr : biaya_rawat, tarif_tindakandr, bagian_rs(?), bhp, kso, menejemen
    //    - rawat_inap_dr : biaya_rawat, tarif_tindakandr, material, bhp, kso, menejemen
    //    - rawat_inap_pr : biaya_rawat, material, bhp, kso, menejemen
    //    - rawat_inap_drpr: biaya_rawat, tarif_tindakandr, material, bhp, kso, menejemen
    // ================================================================

    $queries_tindakan = [
        // Ralan: Dokter (rawat_jl_dr — kolom bagian_rs bisa ada/tidak tergantung versi Khanza)
        ['tag' => 'rawat_jl_dr', 'kategori' => 'Tindakan Dokter Ralan', 'tipe_join' => 'jns_perawatan',
         'sql' => "SELECT t.no_rawat, t.tgl_perawatan as tanggal, t.jam_rawat as jam,
                    j.nm_perawatan as nama, 'Tindakan Dokter Ralan' as kategori,
                    t.biaya_rawat,
                    d.nm_dokter
             FROM rawat_jl_dr t
             JOIN jns_perawatan j ON t.kd_jenis_prw=j.kd_jenis_prw
             LEFT JOIN dokter d ON t.kd_dokter=d.kd_dokter
             WHERE t.no_rawat IN ($placeholders)"],

        // Ralan: Perawat (rawat_jl_pr)
        ['tag' => 'rawat_jl_pr', 'kategori' => 'Tindakan Perawat Ralan', 'tipe_join' => 'jns_perawatan',
         'sql' => "SELECT t.no_rawat, t.tgl_perawatan as tanggal, t.jam_rawat as jam,
                    j.nm_perawatan as nama, 'Tindakan Perawat Ralan' as kategori,
                    t.biaya_rawat,
                    NULL as nm_dokter
             FROM rawat_jl_pr t
             JOIN jns_perawatan j ON t.kd_jenis_prw=j.kd_jenis_prw
             WHERE t.no_rawat IN ($placeholders)"],

        // Ralan: Dokter+Perawat (rawat_jl_drpr)
        ['tag' => 'rawat_jl_drpr', 'kategori' => 'Tindakan Dr+Pr Ralan', 'tipe_join' => 'jns_perawatan',
         'sql' => "SELECT t.no_rawat, t.tgl_perawatan as tanggal, t.jam_rawat as jam,
                    j.nm_perawatan as nama, 'Tindakan Dr+Pr Ralan' as kategori,
                    t.biaya_rawat,
                    d.nm_dokter
             FROM rawat_jl_drpr t
             JOIN jns_perawatan j ON t.kd_jenis_prw=j.kd_jenis_prw
             LEFT JOIN dokter d ON t.kd_dokter=d.kd_dokter
             WHERE t.no_rawat IN ($placeholders)"],

        // Ranap: Dokter (rawat_inap_dr — kolom material terbukti ada)
        ['tag' => 'rawat_inap_dr', 'kategori' => 'Tindakan Dokter Ranap', 'tipe_join' => 'jns_perawatan_inap',
         'sql' => "SELECT t.no_rawat, t.tgl_perawatan as tanggal, t.jam_rawat as jam,
                    j.nm_perawatan as nama, 'Tindakan Dokter Ranap' as kategori,
                    t.biaya_rawat,
                    d.nm_dokter
             FROM rawat_inap_dr t
             JOIN jns_perawatan_inap j ON t.kd_jenis_prw=j.kd_jenis_prw
             LEFT JOIN dokter d ON t.kd_dokter=d.kd_dokter
             WHERE t.no_rawat IN ($placeholders)"],

        // Ranap: Perawat (rawat_inap_pr)
        ['tag' => 'rawat_inap_pr', 'kategori' => 'Tindakan Perawat Ranap', 'tipe_join' => 'jns_perawatan_inap',
         'sql' => "SELECT t.no_rawat, t.tgl_perawatan as tanggal, t.jam_rawat as jam,
                    j.nm_perawatan as nama, 'Tindakan Perawat Ranap' as kategori,
                    t.biaya_rawat,
                    NULL as nm_dokter
             FROM rawat_inap_pr t
             JOIN jns_perawatan_inap j ON t.kd_jenis_prw=j.kd_jenis_prw
             WHERE t.no_rawat IN ($placeholders)"],

        // Ranap: Dokter+Perawat (rawat_inap_drpr)
        ['tag' => 'rawat_inap_drpr', 'kategori' => 'Tindakan Dr+Pr Ranap', 'tipe_join' => 'jns_perawatan_inap',
         'sql' => "SELECT t.no_rawat, t.tgl_perawatan as tanggal, t.jam_rawat as jam,
                    j.nm_perawatan as nama, 'Tindakan Dr+Pr Ranap' as kategori,
                    t.biaya_rawat,
                    d.nm_dokter
             FROM rawat_inap_drpr t
             JOIN jns_perawatan_inap j ON t.kd_jenis_prw=j.kd_jenis_prw
             LEFT JOIN dokter d ON t.kd_dokter=d.kd_dokter
             WHERE t.no_rawat IN ($placeholders)"],
    ];

    foreach ($queries_tindakan as $q) {
        $rows = dbg_query($koneksi_pdo, $q['tag'], $q['sql'], $list_norawat, $err);
        if(!empty($rows)){
            foreach ($rows as $row) {
                $timeline[] = [
                    'tanggal'  => $row['tanggal'],
                    'jam'      => $row['jam'],
                    'jenis'    => 'Laporan Tindakan',
                    'no_rawat' => $row['no_rawat'],
                    'data'     => [
                        'Nama Tindakan' => $row['nama'],
                        'Kategori'      => $row['kategori'],
                        'Dokter'        => $row['nm_dokter'] ?? '-',
                        'Biaya Total'   => 'Rp ' . number_format(floatval($row['biaya_rawat']), 0, ',', '.'),
                    ]
                ];
            }
        }
    }

    // ================================================================
    // 2. OPERASI / BEDAH
    // ================================================================
    $rows_op = dbg_query($koneksi_pdo, 'operasi',
        "SELECT o.no_rawat, DATE(o.tgl_operasi) as tanggal, TIME(o.tgl_operasi) as jam,
                p.nm_perawatan, o.status
         FROM operasi o
         LEFT JOIN paket_operasi p ON o.kode_paket=p.kode_paket
         WHERE o.no_rawat IN ($placeholders)",
        $list_norawat, $err
    );
    foreach ($rows_op as $row) {
        $timeline[] = [
            'tanggal'  => $row['tanggal'],
            'jam'      => $row['jam'],
            'jenis'    => 'Laporan Tindakan',
            'no_rawat' => $row['no_rawat'],
            'data'     => [
                'Nama Tindakan' => $row['nm_perawatan'] ?? '(Operasi)',
                'Kategori'      => 'Tindakan Operasi/Bedah',
                'Dokter'        => '-',
                'Status'        => $row['status'],
                'Biaya Total'   => '-',
            ]
        ];
    }

    // ================================================================
    // 3. SURGICAL SAFETY CHECKLISTS & ANESTHESIA CHECKS
    // ================================================================
    $tables_bedah_safety = [
        'laporan_operasi'               => 'Laporan Operasi Detail',
        'checklist_pre_operasi'         => 'Checklist Pre-Operasi',
        'checklist_post_operasi'        => 'Checklist Post-Operasi',
        'signin_sebelum_anestesi'       => 'Sign-in Sebelum Anestesi',
        'timeout_sebelum_insisi'        => 'Time-out Sebelum Insisi',
        'signout_sebelum_menutup_luka'  => 'Sign-out Sebelum Menutup Luka',
        'skor_bromage_pasca_anestesi'   => 'Skor Bromage (Pasca Anestesi)',
        'skor_aldrette_pasca_anestesi'  => 'Skor Aldrette (Pasca Anestesi)',
        'skor_steward_pasca_anestesi'   => 'Skor Steward (Pasca Anestesi)'
    ];

    foreach ($tables_bedah_safety as $table => $label) {
        $sql = "SELECT t.* FROM $table t WHERE t.no_rawat IN ($placeholders)";
        $rows = dbg_query($koneksi_pdo, $table, $sql, $list_norawat, $err);
        
        foreach ($rows as $row) {
            $tanggal = $row['tanggal'] ?? '';
            if(empty($tanggal)) continue;
            
            $dt = date('Y-m-d', strtotime($tanggal));
            $tm = date('H:i:s', strtotime($tanggal));
            
            $info = [];
            $exclusions = ['no_rawat', 'tanggal', 'jam', 'nip', 'nip_petugas_ruangan', 'nip_perawat_ok', 'nip_perawat_anestesi', 'kd_dokter', 'kd_dokter_bedah', 'kd_dokter_anestesi', 'waktu_simpan', 'nama', 'nm_dokter'];
            foreach($row as $key => $val) {
                if(!in_array(strtolower($key), $exclusions) && $val !== '' && $val !== null) {
                    $info[$key] = $val;
                }
            }
            
            $info['Tipe'] = $label;
            
            $timeline[] = [
                'tanggal'  => $dt,
                'jam'      => $tm,
                'jenis'    => 'Checklist Keselamatan Bedah',
                'no_rawat' => $row['no_rawat'],
                'data'     => $info
            ];
        }
    }

    // Sort timeline — terbaru di atas
    usort($timeline, function($a, $b) {
        $da = strtotime($a['tanggal'] . ' ' . $a['jam']);
        $db = strtotime($b['tanggal'] . ' ' . $b['jam']);
        return $db - $da;
    });

    dbg_log('final', 'success', 'Total timeline items: ' . count($timeline));
    echo json_encode([
        'status'     => 'success',
        'data'       => $timeline,
        'debug_logs' => dbg_get_logs(),
    ]);

} catch (Exception $e) {
    dbg_log('fatal', 'error', 'Fatal error: ' . $e->getMessage());
    echo json_encode([
        'status'     => 'error',
        'message'    => $e->getMessage(),
        'debug_logs' => dbg_get_logs(),
    ]);
}
?>
