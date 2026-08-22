<?php
// helpers/ajax/ajax_riwayat_resume.php
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
    dbg_log('init', 'info', "Mulai fetch resume untuk no_rkm_medis: $no_rkm_medis dengan mode: $filter_mode");

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
        
        // 1. Resume Medis Ralan
        $rows_r = dbg_query($koneksi_pdo, 'resume_pasien', "SELECT r.*, d.nm_dokter FROM resume_pasien r LEFT JOIN dokter d ON r.kd_dokter=d.kd_dokter WHERE r.no_rawat IN ($placeholders)", $list_norawat, $err);
        foreach($rows_r as $row) {
            $tgl_reg = ''; $jam_reg = '';
            foreach($kunjungan as $k) { if($k['no_rawat'] == $row['no_rawat']) { $tgl_reg = $k['tgl_registrasi']; $jam_reg = '23:59:58'; break; } }
            $info = [];
            $exclusions = ['no_rawat', 'tanggal', 'jam', 'nip', 'nip1', 'nip2', 'kd_dokter', 'waktu_simpan', 'nama', 'nm_dokter'];
            foreach($row as $key => $val) {
                if(!in_array(strtolower($key), $exclusions) && $val !== '' && $val !== null) {
                    $info[$key] = $val;
                }
            }
            $info['Petugas'] = $row['nm_dokter'] ?? '';
            $info['tipe'] = 'Resume Medis Ralan';

            $timeline[] = [
                'tanggal' => $tgl_reg, 'jam' => $jam_reg, 'jenis' => 'Resume Medis', 'no_rawat' => $row['no_rawat'],
                'data' => $info
            ];
        }

        // 2. Resume Medis Ranap
        $rows_rr = dbg_query($koneksi_pdo, 'resume_pasien_ranap', "SELECT r.*, d.nm_dokter FROM resume_pasien_ranap r LEFT JOIN dokter d ON r.kd_dokter=d.kd_dokter WHERE r.no_rawat IN ($placeholders)", $list_norawat, $err);
        foreach($rows_rr as $row) {
            $tgl_reg = ''; $jam_reg = '';
            foreach($kunjungan as $k) { if($k['no_rawat'] == $row['no_rawat']) { $tgl_reg = $k['tgl_registrasi']; $jam_reg = '23:59:59'; break; } }
            $info = [];
            $exclusions = ['no_rawat', 'tanggal', 'jam', 'nip', 'nip1', 'nip2', 'kd_dokter', 'waktu_simpan', 'nama', 'nm_dokter'];
            foreach($row as $key => $val) {
                if(!in_array(strtolower($key), $exclusions) && $val !== '' && $val !== null) {
                    $info[$key] = $val;
                }
            }
            $info['Petugas'] = $row['nm_dokter'] ?? '';
            $info['tipe'] = 'Resume Medis Ranap';

            $timeline[] = [
                'tanggal' => $tgl_reg, 'jam' => $jam_reg, 'jenis' => 'Resume Medis', 'no_rawat' => $row['no_rawat'],
                'data' => $info
            ];
        }

        // 3. Temporary Resume
        $rows_temp = dbg_query($koneksi_pdo, 'temporary_resume', "SELECT r.* FROM temporary_resume r WHERE r.temp2 IN ($placeholders)", $list_norawat, $err);
        foreach($rows_temp as $row) {
            $tgl_reg = ''; $jam_reg = '';
            foreach($kunjungan as $k) { if($k['no_rawat'] == $row['temp2']) { $tgl_reg = $k['tgl_registrasi']; $jam_reg = '23:59:57'; break; } }
            $info = [];
            $exclusions = ['no_rawat', 'tanggal', 'jam', 'nip', 'nip1', 'nip2', 'kd_dokter', 'waktu_simpan', 'nama', 'nm_dokter'];
            foreach($row as $key => $val) {
                if(!in_array(strtolower($key), $exclusions) && $val !== '' && $val !== null) {
                    $info[$key] = $val;
                }
            }
            $info['Petugas'] = '-';
            $info['tipe'] = 'Temporary Resume';

            $timeline[] = [
                'tanggal' => $tgl_reg, 'jam' => $jam_reg, 'jenis' => 'Resume Medis', 'no_rawat' => $row['temp2'],
                'data' => $info
            ];
        }

        // 4. Perencanaan Pemulangan & Evaluasi
        $tables_pemulangan = [
            'perencanaan_pemulangan' => 'Perencanaan Pemulangan Pasien (Discharge Planning)',
            'checklist_kriteria_keluar_hcu' => 'Kriteria Keluar HCU',
            'checklist_kriteria_keluar_icu' => 'Kriteria Keluar ICU',
            'checklist_kriteria_keluar_nicu' => 'Kriteria Keluar NICU',
            'checklist_kriteria_keluar_picu' => 'Kriteria Keluar PICU',
            'checklist_kriteria_masuk_hcu' => 'Kriteria Masuk HCU',
            'checklist_kriteria_masuk_icu' => 'Kriteria Masuk ICU',
            'checklist_kriteria_masuk_nicu' => 'Kriteria Masuk NICU',
            'checklist_kriteria_masuk_picu' => 'Kriteria Masuk PICU'
        ];

        foreach($tables_pemulangan as $table => $label) {
            $sql = "SELECT t.* FROM $table t WHERE t.no_rawat IN ($placeholders)";
            $rows = dbg_query($koneksi_pdo, $table, $sql, $list_norawat, $err);
            
            foreach($rows as $row) {
                $tanggal = $row['tanggal'] ?? '';
                if(empty($tanggal)) {
                    foreach($kunjungan as $k) { 
                        if($k['no_rawat'] == $row['no_rawat']) { 
                            $tanggal = $k['tgl_registrasi'] . ' ' . $k['jam_reg']; 
                            break; 
                        } 
                    }
                }
                
                $dt = date('Y-m-d', strtotime($tanggal));
                $tm = date('H:i:s', strtotime($tanggal));
                
                $info = [];
                if(isset($row['keputusan'])) $info['Keputusan'] = $row['keputusan'];
                if(isset($row['keterangan'])) $info['Keterangan'] = $row['keterangan'];
                if(isset($row['rencana_pulang_tgl'])) $info['Rencana Tgl Pulang'] = $row['rencana_pulang_tgl'];
                
                $info['Tipe'] = $label;

                $timeline[] = [
                    'tanggal' => $dt,
                    'jam' => $tm,
                    'jenis' => 'Discharge Planning',
                    'no_rawat' => $row['no_rawat'],
                    'data' => $info
                ];
            }
        }

        // 5. MPP (Manajer Pelayanan Pasien)
        $rows_mpp = dbg_query($koneksi_pdo, 'mpp_evaluasi', "SELECT t.* FROM mpp_evaluasi t WHERE t.no_rawat IN ($placeholders)", $list_norawat, $err);
        foreach($rows_mpp as $row) {
            $dt = date('Y-m-d', strtotime($row['tanggal']));
            $tm = date('H:i:s', strtotime($row['tanggal']));
            $info = [];
            $exclusions = ['no_rawat', 'tanggal', 'jam', 'nip', 'nip1', 'nip2', 'kd_dokter', 'waktu_simpan', 'nama', 'nm_dokter'];
            foreach($row as $key => $val) {
                if(!in_array(strtolower($key), $exclusions) && $val !== '' && $val !== null) {
                    $info[$key] = $val;
                }
            }
            $info['Tipe'] = 'Evaluasi Awal MPP';

            $timeline[] = [
                'tanggal' => $dt, 'jam' => $tm, 'jenis' => 'Asuhan', 'no_rawat' => $row['no_rawat'],
                'data' => $info
            ];
        }

        $rows_mpp_c = dbg_query($koneksi_pdo, 'mpp_evaluasi_catatan', "SELECT t.* FROM mpp_evaluasi_catatan t WHERE t.no_rawat IN ($placeholders)", $list_norawat, $err);
        foreach($rows_mpp_c as $row) {
            $dt = date('Y-m-d', strtotime($row['tgl_implementasi']));
            $tm = date('H:i:s', strtotime($row['tgl_implementasi']));
            $info = [];
            $exclusions = ['no_rawat', 'tanggal', 'jam', 'nip', 'nip1', 'nip2', 'kd_dokter', 'waktu_simpan', 'nama', 'nm_dokter', 'tgl_implementasi'];
            foreach($row as $key => $val) {
                if(!in_array(strtolower($key), $exclusions) && $val !== '' && $val !== null) {
                    $info[$key] = $val;
                }
            }
            $info['Tipe'] = 'Catatan Implementasi MPP';

            $timeline[] = [
                'tanggal' => $dt, 'jam' => $tm, 'jenis' => 'Asuhan', 'no_rawat' => $row['no_rawat'],
                'data' => $info
            ];
        }

        $rows_mpp_m = dbg_query($koneksi_pdo, 'mpp_evaluasi_masalah', "SELECT t.*, m.nama_masalah FROM mpp_evaluasi_masalah t LEFT JOIN master_masalah_mpp m ON t.kode_masalah=m.kode_masalah WHERE t.no_rawat IN ($placeholders)", $list_norawat, $err);
        foreach($rows_mpp_m as $row) {
            $dt = date('Y-m-d', strtotime($row['tanggal']));
            $tm = date('H:i:s', strtotime($row['tanggal']));
            $info = [];
            $exclusions = ['no_rawat', 'tanggal', 'jam', 'nip', 'nip1', 'nip2', 'kd_dokter', 'waktu_simpan', 'nama', 'nm_dokter'];
            foreach($row as $key => $val) {
                if(!in_array(strtolower($key), $exclusions) && $val !== '' && $val !== null) {
                    $info[$key] = $val;
                }
            }
            $info['Tipe'] = 'Analisa Masalah MPP';

            $timeline[] = [
                'tanggal' => $dt, 'jam' => $tm, 'jenis' => 'Asuhan', 'no_rawat' => $row['no_rawat'],
                'data' => $info
            ];
        }

        // 6. Surat Persetujuan & Penolakan & APS
        $tables_surat = [
            'surat_persetujuan_umum'               => 'Persetujuan Umum (General Consent)',
            'surat_persetujuan_rawat_inap'         => 'Persetujuan Rawat Inap',
            'surat_pulang_atas_permintaan_sendiri' => 'Pulang Atas Permintaan Sendiri (APS)',
            'surat_penolakan_anjuran_medis'        => 'Penolakan Anjuran Medis'
        ];

        foreach ($tables_surat as $table => $label) {
            $sql = "SELECT t.* FROM $table t WHERE t.no_rawat IN ($placeholders)";
            $rows = dbg_query($koneksi_pdo, $table, $sql, $list_norawat, $err);
            
            foreach ($rows as $row) {
                $tanggal = $row['tanggal'] ?? $row['tgl_pulang'] ?? '';
                if (empty($tanggal)) continue;
                
                $dt = date('Y-m-d', strtotime($tanggal));
                $tm = (strlen($tanggal) > 10) ? date('H:i:s', strtotime($tanggal)) : '23:59:00';
                
                $info = [];
                $exclusions = ['no_rawat', 'tanggal', 'jam', 'nip', 'nik', 'waktu_simpan', 'tgl_pulang'];
                foreach ($row as $key => $val) {
                    if (!in_array(strtolower($key), $exclusions) && $val !== '' && $val !== null) {
                        $info[$key] = $val;
                    }
                }
                $info['Tipe'] = $label;
                
                $timeline[] = [
                    'tanggal' => $dt,
                    'jam' => $tm,
                    'jenis' => 'Resume Medis',
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
