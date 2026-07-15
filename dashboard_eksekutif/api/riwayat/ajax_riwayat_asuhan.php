<?php
// helpers/ajax/ajax_riwayat_asuhan.php
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
    dbg_log('init', 'info', "Mulai fetch asuhan untuk no_rkm_medis: $no_rkm_medis dengan mode: $filter_mode");

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
        
        // 1. Diagnosa Pasien (ICD-10)
        $sql_diag = "SELECT d.no_rawat, d.kd_penyakit, p.nm_penyakit, d.status, d.prioritas FROM diagnosa_pasien d JOIN penyakit p ON d.kd_penyakit = p.kd_penyakit WHERE d.no_rawat IN ($placeholders)";
        $rows_diag = dbg_query($koneksi_pdo, 'diagnosa_pasien', $sql_diag, $list_norawat, $err);
        
        $diag_grouped = [];
        foreach($rows_diag as $row) {
            if(!isset($diag_grouped[$row['no_rawat']])) {
                $diag_grouped[$row['no_rawat']] = [];
            }
            $diag_grouped[$row['no_rawat']][] = $row;
        }

        foreach($diag_grouped as $norwt => $diagnosas) {
            $tgl_reg = ''; $jam_reg = '';
            foreach($kunjungan as $k) {
                if($k['no_rawat'] == $norwt) {
                    $tgl_reg = $k['tgl_registrasi']; $jam_reg = $k['jam_reg']; break;
                }
            }
            $timeline[] = [
                'tanggal' => $tgl_reg,
                'jam' => $jam_reg,
                'jenis' => 'Diagnosa',
                'no_rawat' => $norwt,
                'data' => $diagnosas
            ];
        }

        // 2. Prosedur Pasien (ICD-9)
        $sql_pro = "SELECT d.no_rawat, d.kode, p.deskripsi_pendek as nm_prosedur, d.status, d.prioritas FROM prosedur_pasien d LEFT JOIN icd9 p ON d.kode = p.kode WHERE d.no_rawat IN ($placeholders)";
        $rows_pro = dbg_query($koneksi_pdo, 'prosedur_pasien', $sql_pro, $list_norawat, $err);
        
        $pro_grouped = [];
        foreach($rows_pro as $row) {
            if(!isset($pro_grouped[$row['no_rawat']])) {
                $pro_grouped[$row['no_rawat']] = [];
            }
            $pro_grouped[$row['no_rawat']][] = $row;
        }

        foreach($pro_grouped as $norwt => $prosedurs) {
            $tgl_reg = ''; $jam_reg = '';
            foreach($kunjungan as $k) {
                if($k['no_rawat'] == $norwt) {
                    $tgl_reg = $k['tgl_registrasi']; $jam_reg = $k['jam_reg']; break;
                }
            }
            $timeline[] = [
                'tanggal' => $tgl_reg,
                'jam' => $jam_reg,
                'jenis' => 'Prosedur Medis',
                'no_rawat' => $norwt,
                'data' => $prosedurs
            ];
        }

        // 3. Asuhan (Gizi, Keperawatan, Medis, Anestesi)
        $tables_asuhan = [
            'asuhan_gizi' => 'Asuhan Gizi',
            'catatan_keperawatan_ralan' => 'Catatan Keperawatan Ralan',
            'catatan_keperawatan_ranap' => 'Catatan Keperawatan Ranap',
            'monitoring_asuhan_gizi' => 'Monitoring Asuhan Gizi'
        ];

        foreach($tables_asuhan as $table => $label) {
            // Coba join petugas dengan nip
            $sql = "SELECT t.*, p.nama FROM $table t LEFT JOIN petugas p ON t.nip=p.nip WHERE t.no_rawat IN ($placeholders)";
            $rows = dbg_query($koneksi_pdo, $table, $sql, $list_norawat, $err);
            
            // Fallback tanpa nip jika terjadi Unknown Column
            if (empty($rows) && strpos($err, 'Unknown column') !== false) {
                dbg_log($table, 'warning', "Fallback query tanpa JOIN untuk $table");
                $sql_fallback = "SELECT t.*, '-' as nama FROM $table t WHERE t.no_rawat IN ($placeholders)";
                $rows = dbg_query($koneksi_pdo, $table . '_fallback', $sql_fallback, $list_norawat, $err);
            }
            
            foreach($rows as $row) {
                $tanggal = $row['tanggal'] ?? '';
                $jam = $row['jam'] ?? '';
                
                if(empty($tanggal)) continue;
                
                // Beberapa tabel menyimpan format tanggal lengkap datetime, beberapa dipisah tanggal dan jam
                if (strlen($tanggal) > 10) {
                    $dt = date('Y-m-d', strtotime($tanggal));
                    $tm = date('H:i:s', strtotime($tanggal));
                } else {
                    $dt = $tanggal;
                    $tm = !empty($jam) ? $jam : '00:00:00';
                }
                
                $info = [];
                $exclusions = ['no_rawat', 'tanggal', 'jam', 'nip', 'nip1', 'nip2', 'kd_dokter', 'waktu_simpan', 'nama', 'nm_dokter'];
                foreach($row as $key => $val) {
                    if(!in_array(strtolower($key), $exclusions) && $val !== '' && $val !== null) {
                        $info[$key] = $val;
                    }
                }
                
                if(isset($row['nama']) && $row['nama'] !== '-') $info['Petugas'] = $row['nama'];
                
                $info['Tipe'] = $label;

                $timeline[] = [
                    'tanggal' => $dt,
                    'jam' => $tm,
                    'jenis' => 'Asuhan Keperawatan & Gizi',
                    'no_rawat' => $row['no_rawat'],
                    'data' => $info
                ];
            }
        }

        // 4. Pelaksanaan Informasi & Edukasi (materi_edukasi, dll)
        $sql_edu = "SELECT t.* FROM pelaksanaan_informasi_edukasi t WHERE t.no_rawat IN ($placeholders)";
        $rows_edu = dbg_query($koneksi_pdo, 'pelaksanaan_informasi_edukasi', $sql_edu, $list_norawat, $err);
        foreach($rows_edu as $row) {
            $tanggal = $row['tanggal'] ?? '';
            if(empty($tanggal)) continue;
            
            $dt = date('Y-m-d', strtotime($tanggal));
            $tm = date('H:i:s', strtotime($tanggal));
            
            $info = [];
            $exclusions = ['no_rawat', 'tanggal', 'jam', 'nip', 'nik', 'waktu_simpan'];
            foreach($row as $key => $val) {
                if(!in_array(strtolower($key), $exclusions) && $val !== '' && $val !== null) {
                    $info[$key] = $val;
                }
            }
            $info['Tipe'] = 'Pelaksanaan Informasi & Edukasi';
            
            $timeline[] = [
                'tanggal' => $dt,
                'jam' => $tm,
                'jenis' => 'Asuhan Keperawatan & Gizi',
                'no_rawat' => $row['no_rawat'],
                'data' => $info
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
