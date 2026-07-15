<?php
// helpers/ajax/ajax_riwayat_observasi.php
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
    dbg_log('init', 'info', "Mulai fetch observasi untuk no_rkm_medis: $no_rkm_medis dengan mode: $filter_mode");

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
        
        // 1. CPPT (Pemeriksaan Ralan & Ranap)
        $rows_cppt_ralan = dbg_query($koneksi_pdo, 'pemeriksaan_ralan', "SELECT p.*, g.nama as nama_petugas FROM pemeriksaan_ralan p LEFT JOIN pegawai g ON p.nip = g.nik WHERE p.no_rawat IN ($placeholders)", $list_norawat, $err);
        foreach ($rows_cppt_ralan as $row) {
            $timeline[] = ['tanggal' => $row['tgl_perawatan'], 'jam' => $row['jam_rawat'], 'jenis' => 'CPPT', 'no_rawat' => $row['no_rawat'], 'data' => array_merge($row, ['tipe' => 'Ralan'])];
        }

        $rows_cppt_ranap = dbg_query($koneksi_pdo, 'pemeriksaan_ranap', "SELECT p.*, g.nama as nama_petugas FROM pemeriksaan_ranap p LEFT JOIN pegawai g ON p.nip = g.nik WHERE p.no_rawat IN ($placeholders)", $list_norawat, $err);
        foreach ($rows_cppt_ranap as $row) {
            $timeline[] = ['tanggal' => $row['tgl_perawatan'], 'jam' => $row['jam_rawat'], 'jenis' => 'CPPT', 'no_rawat' => $row['no_rawat'], 'data' => array_merge($row, ['tipe' => 'Ranap'])];
        }

        // 2. Observasi Umum
        $tables_observasi = [
            'catatan_observasi_igd' => 'Observasi IGD',
            'catatan_observasi_ranap' => 'Observasi Ranap',
            'catatan_observasi_bayi' => 'Observasi Bayi',
            'catatan_observasi_chbp' => 'Observasi CHBP',
            'catatan_observasi_hemodialisa' => 'Observasi Hemodialisa',
            'catatan_observasi_induksi_persalinan' => 'Observasi Induksi Persalinan',
            'catatan_observasi_ranap_kebidanan' => 'Observasi Kebidanan',
            'catatan_observasi_ranap_postpartum' => 'Observasi Postpartum',
            'catatan_observasi_restrain_nonfarma' => 'Observasi Restrain',
            'catatan_observasi_ventilator' => 'Observasi Ventilator',
            'catatan_perawatan' => 'Catatan Perawatan',
            'catatan_persalinan' => 'Catatan Persalinan',
            'catatan_cairan_hemodialisa' => 'Cairan Hemodialisa',
            'catatan_keseimbangan_cairan' => 'Keseimbangan Cairan',
            'catatan_cek_gds' => 'Catatan Cek GDS',
            'monitoring_reaksi_tranfusi' => 'Monitoring Reaksi Transfusi'
        ];

        foreach($tables_observasi as $table => $label) {
            $sql = "SELECT t.* FROM $table t WHERE t.no_rawat IN ($placeholders)";
            $rows = dbg_query($koneksi_pdo, $table, $sql, $list_norawat, $err);
            
            foreach($rows as $row) {
                // Formatting date and time fallback
                $tanggal = $row['tgl_perawatan'] ?? $row['tanggal'] ?? $row['mulai'] ?? '';
                $jam = $row['jam_rawat'] ?? $row['jam'] ?? '';
                
                if (empty($tanggal) && !empty($row['mulai'])) {
                    $tanggal = date('Y-m-d', strtotime($row['mulai']));
                    $jam = date('H:i:s', strtotime($row['mulai']));
                }

                if(empty($tanggal)) continue;
                
                $info = [];
                $exclusions = ['no_rawat', 'tanggal', 'jam', 'nip', 'nip1', 'nip2', 'kd_dokter', 'waktu_simpan', 'nama', 'nm_dokter'];
                foreach($row as $key => $val) {
                    if(!in_array(strtolower($key), $exclusions) && $val !== '' && $val !== null) {
                        $info[$key] = $val;
                    }
                }

                $info['Tipe'] = $label;

                $timeline[] = [
                    'tanggal' => $tanggal,
                    'jam' => $jam,
                    'jenis' => 'Observasi & EWS',
                    'no_rawat' => $row['no_rawat'],
                    'data' => $info
                ];
            }
        }

        // 3. EWS (Early Warning Systems)
        $tables_ews = [
            'pemantauan_ews_neonatus' => 'EWS Neonatus',
            'pemantauan_meows_obstetri' => 'MEOWS Obstetri',
            'pemantauan_pews_anak' => 'PEWS Anak',
            'pemantauan_pews_dewasa' => 'PEWS Dewasa'
        ];

        foreach($tables_ews as $table => $label) {
            $sql = "SELECT t.* FROM $table t WHERE t.no_rawat IN ($placeholders)";
            $rows = dbg_query($koneksi_pdo, $table, $sql, $list_norawat, $err);
            
            foreach($rows as $row) {
                $tanggal = $row['tanggal'] ?? '';
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
                    'jenis' => 'Observasi & EWS',
                    'no_rawat' => $row['no_rawat'],
                    'data' => $info
                ];
            }
        }
        
        // 4. Skrining (Berbagai macam Skrining)
        $tables_skrining = [
            'skrining_adiksi_nikotin' => 'Skrining Adiksi Nikotin',
            'skrining_anemia' => 'Skrining Anemia',
            'skrining_curb65' => 'Skrining CURB-65',
            'skrining_diabetes_melitus' => 'Skrining DM',
            'skrining_frailty_syndrome' => 'Skrining Frailty Syndrome',
            'skrining_gizi' => 'Skrining Gizi',
            'skrining_gizi_kehamilan' => 'Skrining Gizi Kehamilan',
            'skrining_hipertensi' => 'Skrining Hipertensi',
            'skrining_indra_pendengaran' => 'Skrining Pendengaran',
            'skrining_instrumen_acrs' => 'Skrining ACRS',
            'skrining_instrumen_amt' => 'Skrining AMT',
            'skrining_instrumen_esat' => 'Skrining ESAT',
            'skrining_instrumen_sdq' => 'Skrining SDQ',
            'skrining_instrumen_srq' => 'Skrining SRQ',
            'skrining_kanker_kolorektal' => 'Skrining Kolorektal',
            'skrining_kekerasan_pada_perempuan' => 'Skrining Kekerasan Perempuan',
            'skrining_kesehatan_gigi_mulut_balita' => 'Skrining Gigi Balita',
            'skrining_kesehatan_gigi_mulut_dewasa' => 'Skrining Gigi Dewasa',
            'skrining_kesehatan_gigi_mulut_lansia' => 'Skrining Gigi Lansia',
            'skrining_kesehatan_gigi_mulut_remaja' => 'Skrining Gigi Remaja',
            'skrining_kesehatan_penglihatan' => 'Skrining Penglihatan',
            'skrining_nutrisi_anak' => 'Skrining Nutrisi Anak',
            'skrining_nutrisi_dewasa' => 'Skrining Nutrisi Dewasa',
            'skrining_nutrisi_lansia' => 'Skrining Nutrisi Lansia',
            'skrining_obesitas' => 'Skrining Obesitas',
            'skrining_perilaku_merokok_sekolah_remaja' => 'Skrining Merokok',
            'skrining_pneumonia_severity_index' => 'Skrining Pneumonia',
            'skrining_puma' => 'Skrining PUMA',
            'skrining_risiko_kanker_paru' => 'Skrining Kanker Paru',
            'skrining_risiko_kanker_payudara' => 'Skrining Kanker Payudara',
            'skrining_risiko_kanker_serviks' => 'Skrining Kanker Serviks',
            'skrining_tbc' => 'Skrining TBC',
            'skrining_thalassemia' => 'Skrining Thalassemia',
            'mpp_skrining' => 'Skrining MPP'
        ];
        
        foreach($tables_skrining as $table => $label) {
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
                    'jenis' => 'Skrining',
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
