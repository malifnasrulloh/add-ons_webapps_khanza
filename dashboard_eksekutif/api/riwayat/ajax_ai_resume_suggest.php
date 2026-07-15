<?php
/*
 * File: helpers/ajax/ajax_ai_resume_suggest.php
 * Deskripsi: Endpoint AI untuk menyusun rekomendasi Resume Medis (Ralan & Ranap) dari riwayat klinis lengkap (termasuk tindakan, operasi, observasi, dll)
 */

@set_time_limit(300);
@ini_set('memory_limit', '256M');

require_once dirname(__DIR__, 2) . '/config/koneksi.php';
require_once 'llm_helper.php';

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// Cek status AI Assistance
$config_llm = get_llm_config();
if ($config_llm['ai_status'] === 'off') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Fitur AI Assistance sedang dinonaktifkan oleh Super Admin.']);
    exit;
}

$no_rawat = isset($_POST['no_rawat']) ? trim($_POST['no_rawat']) : '';
$resume_type = isset($_POST['resume_type']) ? trim($_POST['resume_type']) : 'ralan'; // 'ralan' | 'ranap'

if (empty($no_rawat)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Parameter no_rawat wajib diisi.']);
    exit;
}

// 1. Ambil data pasien & riwayat kunjungan
$patient = null;
try {
    $stmt = $koneksi_pdo->prepare("
        SELECT p.no_rkm_medis, p.nm_pasien, rp.umurdaftar, rp.sttsumur, p.jk
        FROM reg_periksa rp
        JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        WHERE rp.no_rawat = ?
        LIMIT 1
    ");
    $stmt->execute([$no_rawat]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("[AI Resume Error] Gagal fetch patient: " . $e->getMessage());
}

if (!$patient) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Data pasien tidak ditemukan.']);
    exit;
}

$no_rkm_medis = $patient['no_rkm_medis'];
$patient_name = $patient['nm_pasien'];
$patient_age = $patient['umurdaftar'] . ' ' . $patient['sttsumur'];
$patient_gender = ($patient['jk'] === 'L') ? 'Laki-laki' : 'Perempuan';

// 2. Ambil data riwayat dari frontend (rawData yang sudah di-fetch oleh view_riwayat.php)
//    Strategi: Frontend mengirimkan rawData[] yang identik dengan apa yang tampil di timeline.
//    Ini menjamin AI melihat data yang persis sama dengan apa yang dilihat user.
$history_summary = "";

$raw_data_json = isset($_POST['raw_data']) ? $_POST['raw_data'] : '';
$raw_data = json_decode($raw_data_json, true);

if (is_array($raw_data) && count($raw_data) > 0) {
    $history_summary .= "## DATA RIWAYAT PERAWATAN LENGKAP PASIEN\n\n";
    
    // Group by no_rawat for organized output
    $grouped = [];
    foreach ($raw_data as $entry) {
        $nr = isset($entry['no_rawat']) ? $entry['no_rawat'] : 'unknown';
        if (!isset($grouped[$nr])) $grouped[$nr] = [];
        $grouped[$nr][] = $entry;
    }
    
    foreach ($grouped as $nr => $entries) {
        // Find registration entry for header
        $reg_entry = null;
        foreach ($entries as $e) {
            if (isset($e['jenis']) && $e['jenis'] === 'Registrasi') {
                $reg_entry = $e;
                break;
            }
        }
        
        $tgl = $reg_entry ? ($reg_entry['tanggal'] ?? '') : ($entries[0]['tanggal'] ?? '');
        $status = ($reg_entry && isset($reg_entry['data']['status_lanjut'])) ? $reg_entry['data']['status_lanjut'] : '';
        $poli = ($reg_entry && isset($reg_entry['data']['poli'])) ? $reg_entry['data']['poli'] : '';
        $dokter = ($reg_entry && isset($reg_entry['data']['dokter'])) ? $reg_entry['data']['dokter'] : '';
        
        $history_summary .= "=== Kunjungan: $nr (Tgl: $tgl | $status | Poli: $poli | DPJP: $dokter) ===\n";
        
        // Sort entries by jenis for organized output
        $jenis_order = ['Registrasi','Penilaian Medis','CPPT','Diagnosa','Laboratorium','Radiologi','Tindakan','Resep','Observasi','Berkas Digital'];
        
        foreach ($jenis_order as $jenis) {
            $items = array_filter($entries, function($e) use ($jenis) {
                return isset($e['jenis']) && $e['jenis'] === $jenis;
            });
            
            if (empty($items) || $jenis === 'Registrasi') continue;
            
            $history_summary .= "\n--- $jenis ---\n";
            foreach ($items as $item) {
                $tgl_item = isset($item['tanggal']) ? $item['tanggal'] : '';
                $jam_item = isset($item['jam']) ? $item['jam'] : '';
                if ($tgl_item) $history_summary .= "  [$tgl_item $jam_item]\n";
                
                if (isset($item['data']) && is_array($item['data'])) {
                    foreach ($item['data'] as $key => $val) {
                        if (is_array($val)) {
                            // Nested array (e.g. detail lab results)
                            $history_summary .= "  $key:\n";
                            foreach ($val as $sub_key => $sub_val) {
                                if (is_array($sub_val)) {
                                    $history_summary .= "    - " . json_encode($sub_val, JSON_UNESCAPED_UNICODE) . "\n";
                                } else {
                                    $history_summary .= "    - $sub_key: $sub_val\n";
                                }
                            }
                        } else {
                            $val_str = (string) $val;
                            if (strlen($val_str) > 0 && $val_str !== '-' && $val_str !== '0') {
                                $val_str = str_replace("\n", " ", $val_str);
                                if (strlen($val_str) > 500) $val_str = substr($val_str, 0, 500) . "...";
                                $history_summary .= "  $key: $val_str\n";
                            }
                        }
                    }
                }
            }
        }
        
        $history_summary .= "\n";
    }
} else {
    // Fallback: jika rawData tidak dikirim, lakukan query DB minimal
    error_log("[AI Resume] raw_data kosong, fallback ke query DB");
    try {
        $filter_mode = isset($_POST['filter_mode']) ? $_POST['filter_mode'] : '5_terakhir';
        $tgl_awal = isset($_POST['tgl_awal']) ? $_POST['tgl_awal'] : '';
        $tgl_akhir = isset($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : '';

        $sql_reg = "SELECT no_rawat, tgl_registrasi, status_lanjut FROM reg_periksa WHERE no_rkm_medis = ?";
        $params_reg = [$no_rkm_medis];
        if ($filter_mode === 'tanggal' && !empty($tgl_awal) && !empty($tgl_akhir)) {
            $sql_reg .= " AND tgl_registrasi BETWEEN ? AND ?";
            $params_reg[] = $tgl_awal;
            $params_reg[] = $tgl_akhir;
        }
        $sql_reg .= " ORDER BY tgl_registrasi DESC, jam_reg DESC";
        if ($filter_mode === '5_terakhir') $sql_reg .= " LIMIT 5";
        
        $stmt_k = $koneksi_pdo->prepare($sql_reg);
        $stmt_k->execute($params_reg);
        $kunjungan_list = $stmt_k->fetchAll(PDO::FETCH_ASSOC);
        $list_norawat = array_column($kunjungan_list, 'no_rawat');
        
        if (!empty($list_norawat)) {
            $ph = implode(',', array_fill(0, count($list_norawat), '?'));
            $history_summary .= "## DATA RIWAYAT PERAWATAN PASIEN\n\n";
            
            foreach ($kunjungan_list as $k) {
                $nr = $k['no_rawat'];
                $history_summary .= "=== Kunjungan: $nr (Tgl: " . $k['tgl_registrasi'] . " | " . $k['status_lanjut'] . ") ===\n";
            }
            
            // CPPT
            foreach (['pemeriksaan_ralan', 'pemeriksaan_ranap'] as $tbl_cppt) {
                try {
                    $st = $koneksi_pdo->prepare("SELECT no_rawat, tgl_perawatan, keluhan, pemeriksaan, penilaian, rtl FROM $tbl_cppt WHERE no_rawat IN ($ph) ORDER BY tgl_perawatan DESC");
                    $st->execute($list_norawat);
                    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $c) {
                        $history_summary .= "CPPT [{$c['no_rawat']}] {$c['tgl_perawatan']}: S={$c['keluhan']} O={$c['pemeriksaan']} A={$c['penilaian']} P={$c['rtl']}\n";
                    }
                } catch (Exception $e) {}
            }
            
            // Diagnosa
            try {
                $st = $koneksi_pdo->prepare("SELECT dp.no_rawat, dp.kd_penyakit, p.nm_penyakit, dp.prioritas FROM diagnosa_pasien dp JOIN penyakit p ON dp.kd_penyakit = p.kd_penyakit WHERE dp.no_rawat IN ($ph)");
                $st->execute($list_norawat);
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $d) {
                    $history_summary .= "Diagnosa [{$d['no_rawat']}]: [{$d['kd_penyakit']}] {$d['nm_penyakit']} (P{$d['prioritas']})\n";
                }
            } catch (Exception $e) {}
            
            // Resep
            try {
                $st = $koneksi_pdo->prepare("SELECT rd.no_rawat, db.nama_brng, rd.jumlah, rd.aturan_pakai FROM resep_dokter rd JOIN databarang db ON rd.kode_brng = db.kode_brng WHERE rd.no_rawat IN ($ph)");
                $st->execute($list_norawat);
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $history_summary .= "Resep [{$r['no_rawat']}]: {$r['nama_brng']} ({$r['jumlah']} | {$r['aturan_pakai']})\n";
                }
            } catch (Exception $e) {}
            
            // Lab
            try {
                $st = $koneksi_pdo->prepare("SELECT dpl.no_rawat, tpl.Pemeriksaan, dpl.nilai, tpl.satuan, dpl.keterangan FROM detail_periksa_lab dpl JOIN template_laboratorium tpl ON dpl.id_template = tpl.id_template WHERE dpl.no_rawat IN ($ph)");
                $st->execute($list_norawat);
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $l) {
                    $history_summary .= "Lab [{$l['no_rawat']}]: {$l['Pemeriksaan']}: {$l['nilai']} {$l['satuan']} ({$l['keterangan']})\n";
                }
            } catch (Exception $e) {}
            
            // Penilaian Medis
            foreach (['penilaian_medis_ralan', 'penilaian_medis_igd', 'penilaian_medis_ranap', 'penilaian_awal_keperawatan_ranap'] as $tbl_pam) {
                try {
                    $st = $koneksi_pdo->prepare("SELECT no_rawat, keluhan_utama, rps, rpd, rpo, alergi, td, nadi, rr, suhu FROM $tbl_pam WHERE no_rawat IN ($ph)");
                    $st->execute($list_norawat);
                    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $p) {
                        $history_summary .= "Penilaian [{$p['no_rawat']}]: Keluhan={$p['keluhan_utama']} Alergi={$p['alergi']} TD={$p['td']} Nadi={$p['nadi']} RR={$p['rr']} Suhu={$p['suhu']}\n";
                    }
                } catch (Exception $e) {}
            }
            
            // Observasi
            foreach (['catatan_observasi_igd', 'catatan_observasi_ranap'] as $tbl_obs) {
                try {
                    $st = $koneksi_pdo->prepare("SELECT no_rawat, tgl_perawatan, jam_rawat, gcs, td, hr, rr, suhu, spo2 FROM $tbl_obs WHERE no_rawat IN ($ph) ORDER BY tgl_perawatan DESC, jam_rawat DESC");
                    $st->execute($list_norawat);
                    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $o) {
                        $history_summary .= "Observasi [{$o['no_rawat']}] {$o['tgl_perawatan']} {$o['jam_rawat']}: GCS={$o['gcs']} TD={$o['td']} Nadi={$o['hr']} RR={$o['rr']} Suhu={$o['suhu']} SpO2={$o['spo2']}\n";
                    }
                } catch (Exception $e) {}
            }
        }
    } catch (Exception $e) {
        error_log("[AI Resume Error] Fallback gagal: " . $e->getMessage());
    }
}


// [AI CASMIX/MPP] Coba cari referensi Master Clinical Pathway yang relevan dengan diagnosa pasien
$clinical_pathway_ref = '';
if (strpos($resume_type, 'mpp') !== false) {
    $jenis_cp = strpos($resume_type, 'ranap') !== false ? 'ranap' : 'ralan';
    $cp_dir = dirname(dirname(dirname(__FILE__))) . '/data/templates/clinical_pathway/' . $jenis_cp;
    
    // Ambil daftar kode penyakit (ICD-10) dari riwayat_diagnosa array (yg sdh difetch sbg $rawData)
    $icd_codes = [];
    if (!empty($raw_data)) {
        foreach ($raw_data as $item) {
            if (isset($item['type']) && $item['type'] === 'Diagnosa' && isset($item['kd_penyakit'])) {
                $icd_codes[] = $item['kd_penyakit'];
            }
        }
    }
    $icd_codes = array_unique($icd_codes);
    
    // Cari JSON yang sesuai
    if (is_dir($cp_dir) && !empty($icd_codes)) {
        $cp_files = glob($cp_dir . '/*.json');
        foreach ($cp_files as $file) {
            $json_str = file_get_contents($file);
            $cp_data = json_decode($json_str, true);
            if (isset($cp_data['kd_icd10']) && in_array($cp_data['kd_icd10'], $icd_codes)) {
                $clinical_pathway_ref .= "\n\n[REFERENSI CLINICAL PATHWAY: " . $cp_data['nama_pathway'] . "]\n";
                $clinical_pathway_ref .= $json_str;
            }
        }
    }
}

// 3. Formulasi prompt sesuai tipe resume
if ($resume_type === 'ralan') {
    $keys = [
        'keluhan_utama' => 'Rangkuman anamnesis gabungan keluhan utama, RPS, RPD, RPO',
        'jalannya_penyakit' => 'Pemeriksaan fisik (keadaan umum) & TTV (Tekanan darah, nadi, rr, suhu)',
        'pemeriksaan_penunjang' => 'Rangkuman hasil radiologi yang signifikan',
        'hasil_laborat' => 'Rangkuman hasil laboratorium abnormal',
        'diagnosa_utama' => 'Nama diagnosa utama (harus match dengan kode ICD-10)',
        'kd_diagnosa_utama' => 'Kode ICD-10 untuk diagnosa utama',
        'diagnosa_sekunder' => 'Nama diagnosa sekunder 1',
        'kd_diagnosa_sekunder' => 'Kode ICD-10 diagnosa sekunder 1',
        'diagnosa_sekunder2' => 'Nama diagnosa sekunder 2',
        'kd_diagnosa_sekunder2' => 'Kode ICD-10 diagnosa sekunder 2',
        'diagnosa_sekunder3' => 'Nama diagnosa sekunder 3',
        'kd_diagnosa_sekunder3' => 'Kode ICD-10 diagnosa sekunder 3',
        'diagnosa_sekunder4' => 'Nama diagnosa sekunder 4',
        'kd_diagnosa_sekunder4' => 'Kode ICD-10 diagnosa sekunder 4',
        'prosedur_utama' => 'Nama tindakan utama (ICD-9)',
        'kd_prosedur_utama' => 'Kode ICD-9 tindakan utama',
        'prosedur_sekunder' => 'Nama tindakan sekunder 1 (ICD-9)',
        'kd_prosedur_sekunder' => 'Kode ICD-9 tindakan sekunder 1',
        'prosedur_sekunder2' => 'Nama tindakan sekunder 2 (ICD-9)',
        'kd_prosedur_sekunder2' => 'Kode ICD-9 tindakan sekunder 2',
        'prosedur_sekunder3' => 'Nama tindakan sekunder 3 (ICD-9)',
        'kd_prosedur_sekunder3' => 'Kode ICD-9 tindakan sekunder 3',
        'obat_pulang' => 'Rincian resep obat terakhir/lanjutan',
        'kondisi_pulang' => 'Pilih salah satu string persis: "Hidup" atau "Meninggal"'
    ];

    $prompt_schema = json_encode($keys, JSON_PRETTY_PRINT);

    $system_prompt = "Anda adalah Asisten Medis AI profesional E-Dokter yang bertindak sebagai Dokter Spesialis Senior. Tugas Anda adalah menganalisis riwayat klinis lengkap pasien rawat jalan dan merumuskan draf Ringkasan Resume Medis Rawat Jalan (Outpatient Medical Resume).

TUGAS ANDA:
1. Berikan penjelasan naratif ringkas mengenai temuan klinis utama pasien di bagian atas respons Anda dalam format Markdown.
2. Di bagian akhir respons Anda, Anda WAJIB menyertakan blok kode JSON yang berisi rekomendasi auto-fill untuk form resume rawat jalan dengan format kunci persis seperti berikut:
```json
$prompt_schema
```

PENTING:
- Blok kode JSON wajib valid dan diletakkan di akhir respons menggunakan blockquote ```json ... ``` agar sistem dapat mengekstraknya secara otomatis.
- Diagnosis utama (ICD-10) and Prosedur utama (ICD-9) harus didasarkan pada data medis riwayat di atas. Jika data kosong, cari kode yang paling logis atau biarkan string kosong.
- Gunakan Bahasa Indonesia medis yang baik dan profesional.";

} else if ($resume_type === 'mpp_ranap' || $resume_type === 'mpp_ralan') {
    // MPP Casemix / Cost Auditor
    $keys = [
        'diagnosis' => 'Diagnosa Medis (Berdasarkan data riwayat)',
        'kelompok' => 'Kelompok Resiko (Misal: Risiko Jatuh, Risiko Tinggi, dll)',
        'assesmen' => 'Asesmen/Kondisi Pasien (Rangkuman singkat medis dan psikososial)',
        'identifikasi' => 'Identifikasi masalah (sosial, finansial, kepatuhan, dsb)',
        'rencana' => 'Rencana pelayanan / tindakan MPP'
    ];

    $prompt_schema = json_encode($keys, JSON_PRETTY_PRINT);

    $system_prompt = "Anda adalah Asisten AI Khusus MPP (Manajer Pelayanan Pasien) / Casemix di E-Dokter.
Tugas Anda adalah bertindak sebagai Cost Auditor dan Clinical Consistency Analyzer.

TUGAS ANDA:
1. Berikan penjelasan naratif (menggunakan Markdown) yang menganalisis efisiensi biaya, potensi pemborosan (contoh: Lab/Radiologi yang berlebihan atau tidak sesuai diagnosa), kesesuaian tindakan dengan diagnosa, dan **kelengkapan berkas/dokumen wajib klaim** (seperti Resume Medis, Laporan Operasi, Hasil Penunjang).
2. Berikan rekomendasi untuk MPP/Casemix.
3. Di bagian akhir respons Anda, Anda WAJIB menyertakan blok kode JSON yang berisi rekomendasi auto-fill untuk form Evaluasi MPP dengan format kunci persis seperti berikut:
```json
$prompt_schema
```

PENTING: Blok kode JSON wajib diletakkan di akhir respons menggunakan blockquote ```json ... ``` agar sistem dapat mengekstraknya secara otomatis.";
} else if ($resume_type === 'ranap') {
    // Ranap
    $keys = [
        'diagnosa_awal' => 'Diagnosa awal masuk RS',
        'alasan' => 'Alasan utama masuk RS / dirawat',
        'keluhan_utama' => 'Rangkuman keluhan utama, RPS, RPD, RPO',
        'pemeriksaan_fisik' => 'Pemeriksaan fisik lengkap & TTV saat masuk & dirawat',
        'jalannya_penyakit' => 'Ringkasan perkembangan penyakit / anamnesis harian selama dirawat',
        'pemeriksaan_penunjang' => 'Rangkuman hasil radiologi selama dirawat',
        'hasil_laborat' => 'Rangkuman hasil lab abnormal selama dirawat',
        'tindakan_dan_operasi' => 'Rincian tindakan medis/operasi yang dijalani',
        'obat_di_rs' => 'Daftar obat-obatan utama yang didapatkan selama dirawat di RS',
        'diagnosa_utama' => 'Nama diagnosa utama (ICD-10)',
        'kd_diagnosa_utama' => 'Kode ICD-10 untuk diagnosa utama',
        'diagnosa_sekunder' => 'Nama diagnosa sekunder 1',
        'kd_diagnosa_sekunder' => 'Kode ICD-10 diagnosa sekunder 1',
        'diagnosa_sekunder2' => 'Nama diagnosa sekunder 2',
        'kd_diagnosa_sekunder2' => 'Kode ICD-10 diagnosa sekunder 2',
        'diagnosa_sekunder3' => 'Nama diagnosa sekunder 3',
        'kd_diagnosa_sekunder3' => 'Kode ICD-10 diagnosa sekunder 3',
        'diagnosa_sekunder4' => 'Nama diagnosa sekunder 4',
        'kd_diagnosa_sekunder4' => 'Kode ICD-10 diagnosa sekunder 4',
        'prosedur_utama' => 'Nama tindakan utama (ICD-9)',
        'kd_prosedur_utama' => 'Kode ICD-9 tindakan utama',
        'prosedur_sekunder' => 'Nama tindakan sekunder 1 (ICD-9)',
        'kd_prosedur_sekunder' => 'Kode ICD-9 tindakan sekunder 1',
        'prosedur_sekunder2' => 'Nama tindakan sekunder 2 (ICD-9)',
        'kd_prosedur_sekunder2' => 'Kode ICD-9 tindakan sekunder 2',
        'prosedur_sekunder3' => 'Nama tindakan sekunder 3 (ICD-9)',
        'kd_prosedur_sekunder3' => 'Kode ICD-9 tindakan sekunder 3',
        'alergi' => 'Riwayat alergi pasien (jika ada)',
        'diet' => 'Anjuran diet / nutrisi selama dirawat & pulang',
        'lab_belum' => 'Pemeriksaan penunjang/lab yang tertunda/belum ada hasilnya (jika ada)',
        'edukasi' => 'Edukasi/instruksi yang diberikan ke pasien & keluarga',
        'cara_keluar' => 'Pilih salah satu string persis: "Atas Izin Dokter", "Pindah RS", "Pulang Atas Permintaan Sendiri", atau "Lainnya"',
        'ket_keluar' => 'Keterangan cara keluar jika ada',
        'keadaan' => 'Pilih salah satu string persis: "Membaik", "Sembuh", "Keadaan Khusus", atau "Meninggal"',
        'ket_keadaan' => 'Keterangan keadaan keluar',
        'dilanjutkan' => 'Pilih salah satu string persis: "Kembali Ke RS", "RS Lain", "Dokter Luar", "Puskesmes", atau "Lainnya"',
        'ket_dilanjutkan' => 'Keterangan rujukan lanjutan',
        'obat_pulang' => 'Daftar resep obat untuk dibawa pulang (disertai jumlah & aturan pakai)'
    ];

    $prompt_schema = json_encode($keys, JSON_PRETTY_PRINT);

    $system_prompt = "Anda adalah Asisten Medis AI profesional E-Dokter yang bertindak sebagai Dokter Spesialis Senior. Tugas Anda adalah menganalisis riwayat klinis lengkap pasien rawat inap dan merumuskan draf Ringkasan Resume Medis Rawat Inap (Discharge Summary / Inpatient Medical Resume).

TUGAS ANDA:
1. Berikan penjelasan naratif ringkas mengenai jalannya perawatan klinis pasien di bagian atas respons Anda dalam format Markdown.
2. Di bagian akhir respons Anda, Anda WAJIB menyertakan blok kode JSON yang berisi rekomendasi auto-fill untuk form resume rawat inap dengan format kunci persis seperti berikut:
```json
$prompt_schema
```

PENTING:
- Blok kode JSON wajib valid dan diletakkan di akhir respons menggunakan blockquote ```json ... ``` agar sistem dapat mengekstraknya secara otomatis.
- Diagnosis utama (ICD-10) and Prosedur utama (ICD-9) harus didasarkan pada data medis riwayat di atas. Jika data kosong, cari kode yang paling logis atau biarkan string kosong.
- Gunakan Bahasa Indonesia medis yang baik dan profesional.";

} else if ($resume_type === 'mpp_ranap' || $resume_type === 'mpp_ralan') {
    // MPP Casemix / Cost Auditor
    $keys = [
        'diagnosis' => 'Diagnosa Medis (Berdasarkan data riwayat)',
        'kelompok' => 'Kelompok Resiko (Misal: Risiko Jatuh, Risiko Tinggi, dll)',
        'assesmen' => 'Asesmen/Kondisi Pasien (Rangkuman singkat medis dan psikososial)',
        'identifikasi' => 'Identifikasi masalah (sosial, finansial, kepatuhan, dsb)',
        'rencana' => 'Rencana pelayanan / tindakan MPP'
    ];

    $prompt_schema = json_encode($keys, JSON_PRETTY_PRINT);

    $system_prompt = "Anda adalah Asisten AI Khusus MPP (Manajer Pelayanan Pasien) / Casemix di E-Dokter.
Tugas Anda adalah bertindak sebagai Cost Auditor dan Clinical Consistency Analyzer.

TUGAS ANDA:
1. Berikan penjelasan naratif (menggunakan Markdown) yang menganalisis efisiensi biaya, potensi pemborosan (contoh: Lab/Radiologi yang berlebihan atau tidak sesuai diagnosa), kesesuaian tindakan dengan diagnosa, dan **kelengkapan berkas/dokumen wajib klaim** (seperti Resume Medis, Laporan Operasi, Hasil Penunjang).
2. Berikan rekomendasi untuk MPP/Casemix.
3. Di bagian akhir respons Anda, Anda WAJIB menyertakan blok kode JSON yang berisi rekomendasi auto-fill untuk form Evaluasi MPP dengan format kunci persis seperti berikut:
```json
$prompt_schema
```

PENTING: Blok kode JSON wajib diletakkan di akhir respons menggunakan blockquote ```json ... ``` agar sistem dapat mengekstraknya secara otomatis.";
}

$config = get_llm_config();
$fallback_models = isset($config['fallback_models']) ? $config['fallback_models'] : [];
if (!is_array($fallback_models)) {
    $fallback_models = array_map('trim', explode(',', $fallback_models));
}
$models_to_try = array_unique(array_filter(array_merge([$config['model']], $fallback_models)));

$is_stream = isset($_POST['stream']) && $_POST['stream'] == '1';
$action = isset($_POST['action']) ? $_POST['action'] : 'resume';

if ($action === 'chat_discuss') {
    $user_msg = isset($_POST['message']) ? trim($_POST['message']) : '';
    $history_json = isset($_POST['history']) ? $_POST['history'] : '[]';
    
    $chat_history = json_decode($history_json, true);
    if (!is_array($chat_history)) {
        $chat_history = [];
    }

    if (strpos($resume_type, 'mpp') !== false) {
        $system_prompt = "Anda adalah Asisten Medis AI profesional E-Dokter yang bertindak sebagai Konsultan Casemix / MPP (Manajer Pelayanan Pasien).\n";
        $system_prompt .= "TUGAS ANDA: Jawab pertanyaan petugas terkait efisiensi biaya, potensi dispute klaim BPJS, kelengkapan berkas, atau kondisi biopsikososial pasien dengan ringkas, profesional, dan akurat berdasarkan data medis di atas. DILARANG MERESPONS DALAM BENTUK KODE (JSON/SKRIP). Gunakan bahasa Markdown yang rapi.";
    } else {
        $system_prompt = "Anda adalah Asisten Medis AI profesional E-Dokter yang bertindak sebagai teman diskusi Dokter.\n";
        $system_prompt .= "TUGAS ANDA: Jawab pertanyaan Dokter terkait pasien ini dengan ringkas, profesional, dan akurat berdasarkan data medis di atas. DILARANG MERESPONS DALAM BENTUK KODE (JSON/SKRIP). Gunakan bahasa Markdown yang rapi (bullet points, bold, dsb).";
    }

    $messages = [];
    
    // Taruh history_summary di pesan user pertama agar tidak di-drop oleh LLM
    $context_msg = "Berikut adalah data profil dan riwayat klinis pasien sebagai referensi:\n\n";
    $context_msg .= "- Nama Pasien: $patient_name\n- Umur: $patient_age\n- Jenis Kelamin: $patient_gender\n\n";
    $context_msg .= $history_summary;
    if (strpos($resume_type, 'mpp') !== false && !empty($clinical_pathway_ref)) {
        $context_msg .= "\n\n[REFERENSI MASTER CLINICAL PATHWAY STANDAR]\n$clinical_pathway_ref";
    }
    
    $messages[] = ['role' => 'user', 'content' => $context_msg];
    $messages[] = ['role' => 'assistant', 'content' => "Baik Dokter, saya telah memahami riwayat pasien tersebut. Silakan diskusikan."];
    
    foreach ($chat_history as $h) {
        if (isset($h['role']) && isset($h['content'])) {
            $messages[] = ['role' => $h['role'], 'content' => $h['content']];
        }
    }
    $messages[] = ['role' => 'user', 'content' => $user_msg];

    $response = call_llm_api_with_fallback($config['api_endpoint'], $config['api_key'], $models_to_try, $system_prompt, $messages, 4096, $is_stream);
} else {
    $user_message = "Berikut adalah data profil dan riwayat klinis lengkap pasien:\n\nInformasi Profil Pasien:\n- Nama Pasien: $patient_name\n- Umur: $patient_age\n- Jenis Kelamin: $patient_gender\n\n$history_summary\n\n";
    if (strpos($resume_type, 'mpp') !== false) {
        if (!empty($clinical_pathway_ref)) {
            $user_message .= "Gunakan Referensi Clinical Pathway (Standard Operasional) berikut untuk membandingkan kesesuaian tindakan dan efisiensi:\n$clinical_pathway_ref\n\n";
        }
        $user_message .= "Harap lakukan Audit Cost dan Analisis Konsistensi Klinis berdasarkan riwayat di atas, lalu susun draf Evaluasi MPP sesuai instruksi JSON.";
    } else {
        $user_message .= "Harap susun draf resume medis " . ($resume_type === 'ralan' ? 'Rawat Jalan' : 'Rawat Inap') . " berdasarkan riwayat lengkap pasien di atas sesuai instruksi dan skema JSON yang telah ditentukan.";
    }

    $response = call_llm_api_with_fallback($config['api_endpoint'], $config['api_key'], $models_to_try, $system_prompt, [['role' => 'user', 'content' => $user_message]], 4096, $is_stream);
}

if ($is_stream) {
    if ($response['status'] !== 'success') {
        send_sse_error($response['message']);
    }
    exit;
}

header('Content-Type: application/json; charset=utf-8');
if ($response['status'] === 'success') {
    echo json_encode([
        'status' => 'success',
        'summary' => $response['content'],
        'model_used' => $response['model_used']
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => $response['message']
    ]);
}
exit;
?>
