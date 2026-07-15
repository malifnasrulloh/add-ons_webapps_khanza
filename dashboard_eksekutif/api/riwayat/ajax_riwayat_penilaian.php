<?php
// helpers/ajax/ajax_riwayat_penilaian.php
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
    dbg_log('init', 'info', "Mulai fetch penilaian untuk no_rkm_medis: $no_rkm_medis dengan mode: $filter_mode");

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
        dbg_log('init', 'success', count($list_norawat) . ' kunjungan ditemukan');
        
        // 1a. PENILAIAN AWAL KEPERAWATAN — yang JOIN dengan kolom `nip` standar
        $tables_keperawatan_nip = [
            'penilaian_awal_keperawatan_igd'           => 'Penilaian Awal Keperawatan IGD',
            'penilaian_awal_keperawatan_kebidanan'     => 'Penilaian Awal Keperawatan Kebidanan',
            'penilaian_awal_keperawatan_ralan'         => 'Penilaian Awal Keperawatan Ralan',
            'penilaian_awal_keperawatan_ralan_bayi'    => 'Penilaian Awal Keperawatan Ralan Bayi',
            'penilaian_awal_keperawatan_ralan_geriatri'=> 'Penilaian Awal Keperawatan Ralan Geriatri',
            'penilaian_awal_keperawatan_ralan_psikiatri'=> 'Penilaian Awal Keperawatan Ralan Psikiatri',
        ];

        // 1b. PENILAIAN AWAL KEPERAWATAN — yang JOIN dengan kolom `nip1` (rawat inap, ranap)
        // Lihat DDL: penilaian_awal_keperawatan_kebidanan_ranap, _ranap, _ranap_bayi, _ranap_neonatus
        // tidak punya kolom `nip`, melainkan `nip1` dan `nip2`
        $tables_keperawatan_nip1 = [
            'penilaian_awal_keperawatan_kebidanan_ranap'=> 'Penilaian Awal Keperawatan Kebidanan Ranap',
            'penilaian_awal_keperawatan_ranap'          => 'Penilaian Awal Keperawatan Ranap',
            'penilaian_awal_keperawatan_ranap_bayi'     => 'Penilaian Awal Keperawatan Ranap Bayi',
            'penilaian_awal_keperawatan_ranap_neonatus' => 'Penilaian Awal Keperawatan Ranap Neonatus',
        ];

        // Helper closure untuk mengolah rows menjadi timeline entry
        $process_keperawatan_rows = function($rows, $label) use (&$timeline) {
            foreach($rows as $row) {
                $tanggal = $row['tanggal'] ?? '';
                if(empty($tanggal)) continue;
                $dt = date('Y-m-d', strtotime($tanggal));
                $tm = date('H:i:s', strtotime($tanggal));

                $info = [];
                $exclusions = ['no_rawat', 'tanggal', 'jam', 'nip', 'nip1', 'nip2', 'kd_dokter', 'waktu_simpan', 'nama', 'nm_dokter'];
                foreach($row as $key => $val) {
                    if(!in_array(strtolower($key), $exclusions) && $val !== '' && $val !== null) {
                        $info[$key] = $val;
                    }
                }
                
                $info['Petugas'] = $row['nama'] ?? '-';
                $info['Tipe']    = $label;

                $timeline[] = [
                    'tanggal'  => $dt,
                    'jam'      => $tm,
                    'jenis'    => 'Penilaian Awal Keperawatan',
                    'no_rawat' => $row['no_rawat'],
                    'data'     => $info
                ];
            }
        };

        // Loop tabel dengan kolom `nip`
        foreach($tables_keperawatan_nip as $table => $label) {
            $sql  = "SELECT t.*, p.nama FROM $table t LEFT JOIN petugas p ON t.nip=p.nip WHERE t.no_rawat IN ($placeholders)";
            $rows = dbg_query($koneksi_pdo, $table, $sql, $list_norawat, $err);
            // Fallback minimal — jika kolom nip juga tidak ada (tabel varian lain)
            if (empty($rows) && strpos($err, 'Unknown column') !== false) {
                $sql  = "SELECT t.*, '-' as nama FROM $table t WHERE t.no_rawat IN ($placeholders)";
                $rows = dbg_query($koneksi_pdo, $table . '_fallback', $sql, $list_norawat, $err);
            }
            $process_keperawatan_rows($rows, $label);
        }

        // Loop tabel dengan kolom `nip1` (ranap — JOIN ke petugas via nip1)
        foreach($tables_keperawatan_nip1 as $table => $label) {
            $sql  = "SELECT t.*, p.nama FROM $table t LEFT JOIN petugas p ON t.nip1=p.nip WHERE t.no_rawat IN ($placeholders)";
            $rows = dbg_query($koneksi_pdo, $table, $sql, $list_norawat, $err);
            // Fallback tanpa join jika nip1 juga tidak ada
            if (empty($rows) && strpos($err, 'Unknown column') !== false) {
                $sql  = "SELECT t.*, '-' as nama FROM $table t WHERE t.no_rawat IN ($placeholders)";
                $rows = dbg_query($koneksi_pdo, $table . '_fallback', $sql, $list_norawat, $err);
            }
            $process_keperawatan_rows($rows, $label);
        }

        // 2. PENILAIAN AWAL MEDIS
        $tables_medis = [
            'penilaian_medis_igd' => 'Penilaian Medis IGD',
            'penilaian_medis_ralan' => 'Penilaian Medis Ralan',
            'penilaian_medis_ralan_anak' => 'Penilaian Medis Ralan Anak',
            'penilaian_medis_ralan_bedah' => 'Penilaian Medis Ralan Bedah',
            'penilaian_medis_ralan_bedah_mulut' => 'Penilaian Medis Ralan Bedah Mulut',
            'penilaian_medis_ralan_gawat_darurat_psikiatri' => 'Penilaian Medis Ralan GD Psikiatri',
            'penilaian_medis_ralan_geriatri' => 'Penilaian Medis Ralan Geriatri',
            'penilaian_medis_ralan_jantung' => 'Penilaian Medis Ralan Jantung',
            'penilaian_medis_ralan_kandungan' => 'Penilaian Medis Ralan Kandungan',
            'penilaian_medis_ralan_kulitdankelamin' => 'Penilaian Medis Ralan Kulit & Kelamin',
            'penilaian_medis_ralan_mata' => 'Penilaian Medis Ralan Mata',
            'penilaian_medis_ralan_neurologi' => 'Penilaian Medis Ralan Neurologi',
            'penilaian_medis_ralan_orthopedi' => 'Penilaian Medis Ralan Orthopedi',
            'penilaian_medis_ralan_paru' => 'Penilaian Medis Ralan Paru',
            'penilaian_medis_ralan_penyakit_dalam' => 'Penilaian Medis Ralan Penyakit Dalam',
            'penilaian_medis_ralan_psikiatrik' => 'Penilaian Medis Ralan Psikiatri',
            'penilaian_medis_ralan_rehab_medik' => 'Penilaian Medis Ralan Rehab Medik',
            'penilaian_medis_ralan_tht' => 'Penilaian Medis Ralan THT',
            'penilaian_medis_ralan_urologi' => 'Penilaian Medis Ralan Urologi',
            'penilaian_medis_ranap' => 'Penilaian Medis Ranap',
            'penilaian_medis_ranap_jantung' => 'Penilaian Medis Ranap Jantung',
            'penilaian_medis_ranap_kandungan' => 'Penilaian Medis Ranap Kandungan',
            'penilaian_medis_ranap_neonatus' => 'Penilaian Medis Ranap Neonatus',
            'penilaian_medis_ranap_psikiatrik' => 'Penilaian Medis Ranap Psikiatri'
        ];

        foreach($tables_medis as $table => $label) {
            $sql = "SELECT t.*, d.nm_dokter FROM $table t LEFT JOIN dokter d ON t.kd_dokter=d.kd_dokter WHERE t.no_rawat IN ($placeholders)";
            $rows = dbg_query($koneksi_pdo, $table, $sql, $list_norawat, $err);
            
            // Fallback
            if (empty($rows) && strpos($err, 'Unknown column') !== false) {
                dbg_log($table, 'warning', "Fallback query tanpa JOIN kd_dokter untuk $table");
                $sql_fallback = "SELECT t.*, '-' as nm_dokter FROM $table t WHERE t.no_rawat IN ($placeholders)";
                $rows = dbg_query($koneksi_pdo, $table . '_fallback', $sql_fallback, $list_norawat, $err);
            }

            foreach($rows as $row) {
                $tanggal = $row['tanggal'] ?? '';
                if(empty($tanggal)) continue;
                $dt = date('Y-m-d', strtotime($tanggal));
                $tm = date('H:i:s', strtotime($tanggal));
                
                $info = [];
                $exclusions = ['no_rawat', 'tanggal', 'jam', 'nip', 'nip1', 'nip2', 'kd_dokter', 'waktu_simpan', 'nama', 'nm_dokter'];
                foreach($row as $key => $val) {
                    if(!in_array(strtolower($key), $exclusions) && $val !== '' && $val !== null) {
                        $info[$key] = $val;
                    }
                }

                $info['Dokter'] = $row['nm_dokter'] ?? $row['kd_dokter'] ?? '-';
                $info['Tipe'] = $label;

                $timeline[] = [
                    'tanggal' => $dt,
                    'jam' => $tm,
                    'jenis' => 'Penilaian Awal Medis',
                    'no_rawat' => $row['no_rawat'],
                    'data' => $info
                ];
            }
        }
        
        // 3. Penilaian Psikologi / Lainnya
        $tables_lain = [
            'penilaian_psikologi' => 'Penilaian Psikologi',
            'penilaian_psikologi_klinis' => 'Penilaian Psikologi Klinis',
            'penilaian_mcu' => 'Penilaian MCU',
            'penilaian_bayi_baru_lahir' => 'Penilaian Bayi Baru Lahir',
            'penilaian_fisioterapi' => 'Penilaian Fisioterapi'
        ];
        
        foreach($tables_lain as $table => $label) {
            $sql = "SELECT t.* FROM $table t WHERE t.no_rawat IN ($placeholders)";
            $rows = dbg_query($koneksi_pdo, $table, $sql, $list_norawat, $err);

            foreach($rows as $row) {
                $tanggal = $row['tanggal'] ?? '';
                if(empty($tanggal)) continue;
                $dt = date('Y-m-d', strtotime($tanggal));
                $tm = date('H:i:s', strtotime($tanggal));
                
                $info = [];
                $exclusions = ['no_rawat', 'tanggal', 'jam', 'nip', 'nip1', 'nip2', 'kd_dokter', 'waktu_simpan', 'nama', 'nm_dokter'];
                foreach($row as $key => $val) {
                    if(!in_array(strtolower($key), $exclusions) && $val !== '' && $val !== null) {
                        $info[$key] = $val;
                    }
                }
                
                $info['Tipe'] = $label;

                $timeline[] = [
                    'tanggal' => $dt,
                    'jam' => $tm,
                    'jenis' => 'Penilaian Medis Khusus',
                    'no_rawat' => $row['no_rawat'],
                    'data' => $info
                ];
            }
        }

        // 4. Penilaian Lanjutan (Resiko Jatuh & Nyeri)
        $tables_lanjutan = [
            'penilaian_lanjutan_resiko_jatuh_dewasa'  => 'Penilaian Lanjutan Risiko Jatuh Dewasa',
            'penilaian_lanjutan_resiko_jatuh_anak'    => 'Penilaian Lanjutan Risiko Jatuh Anak',
            'penilaian_lanjutan_resiko_jatuh_geriatri' => 'Penilaian Lanjutan Risiko Jatuh Geriatri',
            'penilaian_lanjutan_resiko_jatuh_lansia'   => 'Penilaian Lanjutan Risiko Jatuh Lansia',
            'penilaian_ulang_nyeri'                   => 'Penilaian Ulang Nyeri'
        ];
        
        foreach($tables_lanjutan as $table => $label) {
            $sql = "SELECT t.* FROM $table t WHERE t.no_rawat IN ($placeholders)";
            $rows = dbg_query($koneksi_pdo, $table, $sql, $list_norawat, $err);

            foreach($rows as $row) {
                $tanggal = $row['tanggal'] ?? '';
                if(empty($tanggal)) continue;
                $dt = date('Y-m-d', strtotime($tanggal));
                $tm = date('H:i:s', strtotime($tanggal));
                
                $info = [];
                $exclusions = ['no_rawat', 'tanggal', 'jam', 'nip', 'nip1', 'nip2', 'kd_dokter', 'waktu_simpan', 'nama', 'nm_dokter'];
                foreach($row as $key => $val) {
                    if(!in_array(strtolower($key), $exclusions) && $val !== '' && $val !== null) {
                        $info[$key] = $val;
                    }
                }
                
                $info['Tipe'] = $label;

                $timeline[] = [
                    'tanggal' => $dt,
                    'jam' => $tm,
                    'jenis' => 'Penilaian Lanjutan',
                    'no_rawat' => $row['no_rawat'],
                    'data' => $info
                ];
            }
        }
    }

    dbg_log('final', 'success', 'Total timeline items: ' . count($timeline));
    echo json_encode([
        'status' => 'success', 
        'data' => $timeline,
        'debug_logs' => dbg_get_logs()
    ]);

} catch (Exception $e) {
    dbg_log('fatal', 'error', 'Fatal error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage(),
        'debug_logs' => dbg_get_logs()
    ]);
}
?>
