<?php
// helpers/ajax/ajax_riwayat_medis.php
require_once dirname(__DIR__, 2) . '/config/koneksi.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Content-Type: application/json');

$no_rkm_medis = $_POST['no_rkm_medis'] ?? $_GET['no_rkm_medis'] ?? '';

if (empty($no_rkm_medis)) {
    echo json_encode(['status' => 'error', 'message' => 'no_rkm_medis tidak valid']);
    exit;
}

$timeline = [];

try {
    // 1. Data Registrasi Kunjungan
    $stmt_reg = $koneksi_pdo->prepare("SELECT r.no_rawat, r.tgl_registrasi as tanggal, r.jam_reg as jam, r.status_lanjut, r.stts, p.nm_poli, d.nm_dokter 
                               FROM reg_periksa r 
                               JOIN poliklinik p ON r.kd_poli = p.kd_poli 
                               JOIN dokter d ON r.kd_dokter = d.kd_dokter 
                               WHERE r.no_rkm_medis = ? ORDER BY r.tgl_registrasi DESC, r.jam_reg DESC");
    $stmt_reg->execute([$no_rkm_medis]);
    $kunjungan = $stmt_reg->fetchAll(PDO::FETCH_ASSOC);
    
    // Simpan no_rawat array untuk query detail (lebih efisien)
    $list_norawat = array_column($kunjungan, 'no_rawat');
    $placeholders = implode(',', array_fill(0, count($list_norawat), '?'));

    foreach ($kunjungan as $k) {
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
        // 2. CPPT Ralan
        $stmt_cppt_ralan = $koneksi_pdo->prepare("SELECT p.*, g.nama as nama_petugas FROM pemeriksaan_ralan p LEFT JOIN pegawai g ON p.nip = g.nik WHERE p.no_rawat IN ($placeholders)");
        $stmt_cppt_ralan->execute($list_norawat);
        foreach ($stmt_cppt_ralan->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $timeline[] = ['tanggal' => $row['tgl_perawatan'], 'jam' => $row['jam_rawat'], 'jenis' => 'CPPT', 'no_rawat' => $row['no_rawat'], 'data' => array_merge($row, ['tipe' => 'Ralan'])];
        }

        // 3. CPPT Ranap
        $stmt_cppt_ranap = $koneksi_pdo->prepare("SELECT p.*, g.nama as nama_petugas FROM pemeriksaan_ranap p LEFT JOIN pegawai g ON p.nip = g.nik WHERE p.no_rawat IN ($placeholders)");
        $stmt_cppt_ranap->execute($list_norawat);
        foreach ($stmt_cppt_ranap->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $timeline[] = ['tanggal' => $row['tgl_perawatan'], 'jam' => $row['jam_rawat'], 'jenis' => 'CPPT', 'no_rawat' => $row['no_rawat'], 'data' => array_merge($row, ['tipe' => 'Ranap'])];
        }

        // 4. Laboratorium
        $stmt_lab = $koneksi_pdo->prepare("SELECT l.*, j.nm_perawatan FROM periksa_lab l JOIN jns_perawatan_lab j ON l.kd_jenis_prw = j.kd_jenis_prw WHERE l.no_rawat IN ($placeholders)");
        $stmt_lab->execute($list_norawat);
        $lab_headers = $stmt_lab->fetchAll(PDO::FETCH_ASSOC);
        
        $details_grouped = [];
        if (!empty($lab_headers)) {
            $stmt_det = $koneksi_pdo->prepare("SELECT d.*, t.Pemeriksaan as nm_template FROM detail_periksa_lab d JOIN template_laboratorium t ON d.id_template = t.id_template WHERE d.no_rawat IN ($placeholders)");
            $stmt_det->execute($list_norawat);
            foreach ($stmt_det->fetchAll(PDO::FETCH_ASSOC) as $det) {
                $key = $det['no_rawat'] . '_' . $det['tgl_periksa'] . '_' . $det['jam'] . '_' . $det['kd_jenis_prw'];
                $details_grouped[$key][] = $det;
            }
        }
        
        foreach ($lab_headers as $row) {
            $key = $row['no_rawat'] . '_' . $row['tgl_periksa'] . '_' . $row['jam'] . '_' . $row['kd_jenis_prw'];
            $details = $details_grouped[$key] ?? [];
            
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

        // 5. Radiologi -> hasil_radiologi & periksa_radiologi
        $stmt_rad = $koneksi_pdo->prepare("SELECT p.no_rawat, p.tgl_periksa, p.jam, j.nm_perawatan, h.hasil FROM periksa_radiologi p JOIN jns_perawatan_radiologi j ON p.kd_jenis_prw = j.kd_jenis_prw LEFT JOIN hasil_radiologi h ON p.no_rawat=h.no_rawat AND p.tgl_periksa=h.tgl_periksa AND p.jam=h.jam WHERE p.no_rawat IN ($placeholders)");
        $stmt_rad->execute($list_norawat);
        $rad_data = $stmt_rad->fetchAll(PDO::FETCH_ASSOC);
        
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

        // 6. Resep Obat
        $stmt_resep = $koneksi_pdo->prepare("SELECT * FROM resep_obat WHERE no_rawat IN ($placeholders)");
        $stmt_resep->execute($list_norawat);
        $resep_headers = $stmt_resep->fetchAll(PDO::FETCH_ASSOC);
        
        $umum_grouped = [];
        $racik_grouped = [];
        
        if (!empty($resep_headers)) {
            $list_noresep = array_column($resep_headers, 'no_resep');
            $resep_placeholders = implode(',', array_fill(0, count($list_noresep), '?'));
            
            // Ambil obat umum dalam 1 query
            $stmt_umum = $koneksi_pdo->prepare("SELECT r.*, d.nama_brng FROM resep_dokter r JOIN databarang d ON r.kode_brng = d.kode_brng WHERE r.no_resep IN ($resep_placeholders)");
            $stmt_umum->execute($list_noresep);
            foreach ($stmt_umum->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $umum_grouped[$row['no_resep']][] = $row;
            }
            
            // Ambil racikan dalam 1 query
            $stmt_racik = $koneksi_pdo->prepare("SELECT * FROM resep_dokter_racikan WHERE no_resep IN ($resep_placeholders)");
            $stmt_racik->execute($list_noresep);
            foreach ($stmt_racik->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $racik_grouped[$row['no_resep']][] = $row;
            }
        }
        
        foreach ($resep_headers as $row) {
            $no_resep = $row['no_resep'];
            $umum = $umum_grouped[$no_resep] ?? [];
            $racikan = $racik_grouped[$no_resep] ?? [];
            
            if(!empty($umum) || !empty($racikan)) {
                $timeline[] = [
                    'tanggal' => $row['tgl_peresepan'],
                    'jam' => $row['jam_peresepan'],
                    'jenis' => 'Resep',
                    'no_rawat' => $row['no_rawat'],
                    'data' => [
                        'no_resep' => $no_resep,
                        'umum' => $umum,
                        'racikan' => $racikan
                    ]
                ];
            }
        }

        // 7. Tindakan Ralan & Ranap
        $queries_tindakan = [
            "SELECT no_rawat, tgl_perawatan as tanggal, jam_rawat as jam, j.nm_perawatan as nama, 'Tindakan Dokter Ralan' as kategori FROM rawat_jl_dr t JOIN jns_perawatan j ON t.kd_jenis_prw=j.kd_jenis_prw WHERE t.no_rawat IN ($placeholders)",
            "SELECT no_rawat, tgl_perawatan as tanggal, jam_rawat as jam, j.nm_perawatan as nama, 'Tindakan Perawat Ralan' as kategori FROM rawat_jl_pr t JOIN jns_perawatan j ON t.kd_jenis_prw=j.kd_jenis_prw WHERE t.no_rawat IN ($placeholders)",
            "SELECT no_rawat, tgl_perawatan as tanggal, jam_rawat as jam, j.nm_perawatan as nama, 'Tindakan Dr+Pr Ralan' as kategori FROM rawat_jl_drpr t JOIN jns_perawatan j ON t.kd_jenis_prw=j.kd_jenis_prw WHERE t.no_rawat IN ($placeholders)",
            "SELECT no_rawat, tgl_perawatan as tanggal, jam_rawat as jam, j.nm_perawatan as nama, 'Tindakan Dokter Ranap' as kategori FROM rawat_inap_dr t JOIN jns_perawatan_inap j ON t.kd_jenis_prw=j.kd_jenis_prw WHERE t.no_rawat IN ($placeholders)",
            "SELECT no_rawat, tgl_perawatan as tanggal, jam_rawat as jam, j.nm_perawatan as nama, 'Tindakan Perawat Ranap' as kategori FROM rawat_inap_pr t JOIN jns_perawatan_inap j ON t.kd_jenis_prw=j.kd_jenis_prw WHERE t.no_rawat IN ($placeholders)",
            "SELECT no_rawat, tgl_perawatan as tanggal, jam_rawat as jam, j.nm_perawatan as nama, 'Tindakan Dr+Pr Ranap' as kategori FROM rawat_inap_drpr t JOIN jns_perawatan_inap j ON t.kd_jenis_prw=j.kd_jenis_prw WHERE t.no_rawat IN ($placeholders)"
        ];

        foreach($queries_tindakan as $q) {
            $stmt = $koneksi_pdo->prepare($q);
            $stmt->execute($list_norawat);
            foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $timeline[] = [
                    'tanggal' => $row['tanggal'],
                    'jam' => $row['jam'],
                    'jenis' => 'Tindakan',
                    'no_rawat' => $row['no_rawat'],
                    'data' => [
                        'nama' => $row['nama'],
                        'kategori' => $row['kategori']
                    ]
                ];
            }
        }

        // 8. Diagnosa Pasien (ICD-10)
        $stmt_diag = $koneksi_pdo->prepare("SELECT d.no_rawat, d.kd_penyakit, p.nm_penyakit, d.status, d.prioritas FROM diagnosa_pasien d JOIN penyakit p ON d.kd_penyakit = p.kd_penyakit WHERE d.no_rawat IN ($placeholders)");
        $stmt_diag->execute($list_norawat);
        
        $diag_grouped = [];
        foreach($stmt_diag->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if(!isset($diag_grouped[$row['no_rawat']])) {
                $diag_grouped[$row['no_rawat']] = [];
            }
            $diag_grouped[$row['no_rawat']][] = $row;
        }

        foreach($diag_grouped as $norwt => $diagnosas) {
            // Karna diagnosa tidak ada jam input di tabel (hanya no_rawat), kita letakkan sesuai reg jam_reg
            $tgl_reg = ''; $jam_reg = '';
            foreach($kunjungan as $k) {
                if($k['no_rawat'] == $norwt) {
                    $tgl_reg = $k['tanggal']; $jam_reg = $k['jam']; break;
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
    }

    if (!empty($list_norawat)) {
        // 9. Observasi & EWS
        // Observasi IGD
        $stmt_obs_igd = $koneksi_pdo->prepare("SELECT o.*, p.nama as nm_petugas FROM catatan_observasi_igd o LEFT JOIN petugas p ON o.nip=p.nip WHERE o.no_rawat IN ($placeholders)");
        $stmt_obs_igd->execute($list_norawat);
        foreach($stmt_obs_igd->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $timeline[] = [
                'tanggal' => $row['tgl_perawatan'], 'jam' => $row['jam_rawat'], 'jenis' => 'Observasi', 'no_rawat' => $row['no_rawat'],
                'data' => [
                    'tipe' => 'Observasi IGD', 'GCS' => $row['gcs'], 'Tensi' => $row['td'], 'Nadi' => $row['hr'], 
                    'RR' => $row['rr'], 'Suhu' => $row['suhu'], 'SpO2' => $row['spo2'], 'Petugas' => $row['nm_petugas']
                ]
            ];
        }

        // Observasi Ranap
        $stmt_obs_ranap = $koneksi_pdo->prepare("SELECT o.*, p.nama as nm_petugas FROM catatan_observasi_ranap o LEFT JOIN petugas p ON o.nip=p.nip WHERE o.no_rawat IN ($placeholders)");
        $stmt_obs_ranap->execute($list_norawat);
        foreach($stmt_obs_ranap->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $timeline[] = [
                'tanggal' => $row['tgl_perawatan'], 'jam' => $row['jam_rawat'], 'jenis' => 'Observasi', 'no_rawat' => $row['no_rawat'],
                'data' => [
                    'tipe' => 'Observasi Ranap', 'GCS' => $row['gcs'], 'Tensi' => $row['td'], 'Nadi' => $row['hr'], 
                    'RR' => $row['rr'], 'Suhu' => $row['suhu'], 'SpO2' => $row['spo2'], 'IVFD' => $row['IVFD'], 
                    'BAK' => $row['BAK'], 'BAB' => $row['BAB'], 'Intake' => $row['Intake'], 'Muntah' => $row['Muntah'],
                    'Keterangan' => $row['Keterangan'], 'Petugas' => $row['nm_petugas']
                ]
            ];
        }

        // PEWS Anak
        $stmt_pews_anak = $koneksi_pdo->prepare("SELECT o.*, p.nama as nm_petugas FROM pemantauan_pews_anak o LEFT JOIN petugas p ON o.nip=p.nip WHERE o.no_rawat IN ($placeholders)");
        $stmt_pews_anak->execute($list_norawat);
        foreach($stmt_pews_anak->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $dt = date("Y-m-d", strtotime($row['tanggal']));
            $tm = date("H:i:s", strtotime($row['tanggal']));
            $timeline[] = [
                'tanggal' => $dt, 'jam' => $tm, 'jenis' => 'Observasi', 'no_rawat' => $row['no_rawat'],
                'data' => [
                    'tipe' => 'PEWS Anak', 'Perilaku' => $row['parameter_perilaku'], 'CRT_Warna' => $row['parameter_crt_atau_warna_kulit'],
                    'Respirasi' => $row['parameter_perespirasi'], 'Skor_Total' => $row['skor_total'], 'Par_Total' => $row['parameter_total'],
                    'Petugas' => $row['nm_petugas']
                ]
            ];
        }

        // PEWS Dewasa
        $stmt_pews_dewasa = $koneksi_pdo->prepare("SELECT o.*, p.nama as nm_petugas FROM pemantauan_pews_dewasa o LEFT JOIN petugas p ON o.nip=p.nip WHERE o.no_rawat IN ($placeholders)");
        $stmt_pews_dewasa->execute($list_norawat);
        foreach($stmt_pews_dewasa->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $dt = date("Y-m-d", strtotime($row['tanggal']));
            $tm = date("H:i:s", strtotime($row['tanggal']));
            $timeline[] = [
                'tanggal' => $dt, 'jam' => $tm, 'jenis' => 'Observasi', 'no_rawat' => $row['no_rawat'],
                'data' => [
                    'tipe' => 'PEWS Dewasa', 'RR' => $row['parameter_laju_respirasi'], 'SpO2' => $row['parameter_saturasi_oksigen'],
                    'Oksigen' => $row['parameter_suplemen_oksigen'], 'Tensi' => $row['parameter_tekanan_darah_sistolik'], 
                    'Nadi' => $row['parameter_laju_jantung'], 'Sadar' => $row['parameter_kesadaran'], 'Suhu' => $row['parameter_temperatur'],
                    'Skor_Total' => $row['skor_total'], 'Par_Total' => $row['parameter_total'], 'Petugas' => $row['nm_petugas']
                ]
            ];
        }

        // 10. Resume Medis
        // Resume Ralan
        $stmt_resume = $koneksi_pdo->prepare("SELECT r.*, d.nm_dokter FROM resume_pasien r LEFT JOIN dokter d ON r.kd_dokter=d.kd_dokter WHERE r.no_rawat IN ($placeholders)");
        $stmt_resume->execute($list_norawat);
        foreach($stmt_resume->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tgl_reg = ''; $jam_reg = '';
            foreach($kunjungan as $k) { if($k['no_rawat'] == $row['no_rawat']) { $tgl_reg = $k['tanggal']; $jam_reg = '23:59:58'; break; } }
            $timeline[] = [
                'tanggal' => $tgl_reg, 'jam' => $jam_reg, 'jenis' => 'Observasi', 'no_rawat' => $row['no_rawat'],
                'data' => [
                    'tipe' => 'Resume Medis Ralan', 'Keluhan' => $row['keluhan_utama'], 'Jalannya_Penyakit' => $row['jalannya_penyakit'],
                    'Penunjang' => $row['pemeriksaan_penunjang'], 'Lab' => $row['hasil_laborat'], 'Diag_Utama' => $row['diagnosa_utama'],
                    'Tindakan' => $row['prosedur_utama'], 'Obat Pulang' => $row['obat_pulang'], 'Kondisi' => $row['kondisi_pulang'],
                    'Petugas' => $row['nm_dokter']
                ]
            ];
        }

        // Resume Ranap
        $stmt_resume_ranap = $koneksi_pdo->prepare("SELECT r.*, d.nm_dokter FROM resume_pasien_ranap r LEFT JOIN dokter d ON r.kd_dokter=d.kd_dokter WHERE r.no_rawat IN ($placeholders)");
        $stmt_resume_ranap->execute($list_norawat);
        foreach($stmt_resume_ranap->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tgl_reg = ''; $jam_reg = '';
            foreach($kunjungan as $k) { if($k['no_rawat'] == $row['no_rawat']) { $tgl_reg = $k['tanggal']; $jam_reg = '23:59:59'; break; } }
            $timeline[] = [
                'tanggal' => $tgl_reg, 'jam' => $jam_reg, 'jenis' => 'Observasi', 'no_rawat' => $row['no_rawat'],
                'data' => [
                    'tipe' => 'Resume Medis Ranap', 'Diagnosis Awal' => $row['diagnosa_awal'], 'Keluhan' => $row['keluhan_utama'], 
                    'Pmx Fisik' => $row['pemeriksaan_fisik'], 'Tindakan' => $row['tindakan_dan_operasi'], 'Diag_Utama' => $row['diagnosa_utama'],
                    'Obat Pulang' => $row['obat_pulang'], 'Cara Keluar' => $row['cara_keluar'], 'Keadaan' => $row['keadaan'],
                    'Petugas' => $row['nm_dokter']
                ]
            ];
        }
    }

    // Sort Timeline secara Deskending (Terbaru di atas)
    usort($timeline, function($a, $b) {
        $datetimeA = strtotime($a['tanggal'] . ' ' . $a['jam']);
        $datetimeB = strtotime($b['tanggal'] . ' ' . $b['jam']);
        return $datetimeB - $datetimeA;
    });

    echo json_encode(['status' => 'success', 'data' => $timeline]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
