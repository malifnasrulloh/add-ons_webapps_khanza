<?php
/*
 * File: laporan_audit_erm.php
 * Deskripsi: Integrasi Audit ERM V2 (Fixed UI & Color Coding)
 * Author: Kamerad (Gemini) for Alicia
 * Date: 2025-11-27
 */

// 1. Integrasi Header & Keamanan
$page_title = "Audit Kepatuhan ERM";
require_once('includes/header.php');
require_once('includes/functions.php');

// ==========================================
// LOGIKA & CONFIG
// ==========================================

// Ambil Data Instansi
$q_instansi = $koneksi->query("SELECT nama_instansi FROM setting LIMIT 1");
$data_instansi = $q_instansi->fetch_assoc();
$nama_rs_audit = $data_instansi['nama_instansi'] ?? 'Rumah Sakit';
// Use cached logo link instead of Base64 embedding
$logo_src_audit = "core/logo.php";
?>

<!-- Warning for high data usage on heavy report -->
<div class="alert alert-warning alert-dismissible fade show m-3 shadow-sm border-start border-4 border-warning" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Perhatian:</strong> Membuka laporan audit lengkap melalui internet akan menyedot kuota yang lumayan besar karena volume data yang sangat tinggi.
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<?php

// Definisi Peta Data ERM
$erm_map = [
    'Triase IGD' => ['tabel' => 'data_triase_igd', 'grup' => 'IGD', 'tipe' => 'All'],
    'Asesmen Awal IGD (Medis)' => ['tabel' => 'penilaian_medis_igd', 'grup' => 'IGD', 'tipe' => 'All'],
    'Asesmen Awal IGD (Kep)' => ['tabel' => 'penilaian_awal_keperawatan_igd', 'grup' => 'IGD', 'tipe' => 'All'],
    'Catatan Observasi IGD' => ['tabel' => 'catatan_observasi_igd', 'grup' => 'IGD', 'tipe' => 'All'],
    'Asesmen Medis Ralan (Umum)' => ['tabel' => 'penilaian_medis_ralan', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Anak' => ['tabel' => 'penilaian_medis_ralan_anak', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Kandungan' => ['tabel' => 'penilaian_medis_ralan_kandungan', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Penyakit Dalam' => ['tabel' => 'penilaian_medis_ralan_penyakit_dalam', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Mata' => ['tabel' => 'penilaian_medis_ralan_mata', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis THT' => ['tabel' => 'penilaian_medis_ralan_tht', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Bedah' => ['tabel' => 'penilaian_medis_ralan_bedah', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Orthopedi' => ['tabel' => 'penilaian_medis_ralan_orthopedi', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Saraf' => ['tabel' => 'penilaian_medis_ralan_neurologi', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Jiwa' => ['tabel' => 'penilaian_medis_ralan_psikiatrik', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Kulit' => ['tabel' => 'penilaian_medis_ralan_kulitdankelamin', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Geriatri' => ['tabel' => 'penilaian_medis_ralan_geriatri', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Medis Rehab Medik' => ['tabel' => 'penilaian_medis_ralan_rehab_medik', 'grup' => 'Asesmen Awal Medis', 'tipe' => 'Ralan'],
    'Asesmen Kep Ralan (Umum)' => ['tabel' => 'penilaian_awal_keperawatan_ralan', 'grup' => 'Asesmen Awal Keperawatan', 'tipe' => 'Ralan'],
    'Asesmen Kep Bayi/Anak' => ['tabel' => 'penilaian_awal_keperawatan_ralan_bayi', 'grup' => 'Asesmen Awal Keperawatan', 'tipe' => 'Ralan'],
    'Asesmen Kep Gigi' => ['tabel' => 'penilaian_awal_keperawatan_gigi', 'grup' => 'Asesmen Awal Keperawatan', 'tipe' => 'Ralan'],
    'Asesmen Kep Kebidanan' => ['tabel' => 'penilaian_awal_keperawatan_kebidanan', 'grup' => 'Asesmen Awal Keperawatan', 'tipe' => 'Ralan'],
    'Asesmen Kep Mata' => ['tabel' => 'penilaian_awal_keperawatan_mata', 'grup' => 'Asesmen Awal Keperawatan', 'tipe' => 'Ralan'],
    'Asesmen Kep Psikiatri' => ['tabel' => 'penilaian_awal_keperawatan_ralan_psikiatri', 'grup' => 'Asesmen Awal Keperawatan', 'tipe' => 'Ralan'],
    'Asesmen Kep Geriatri' => ['tabel' => 'penilaian_awal_keperawatan_ralan_geriatri', 'grup' => 'Asesmen Awal Keperawatan', 'tipe' => 'Ralan'],
    'Transfer Antar Ruang' => ['tabel' => 'transfer_pasien_antar_ruang', 'grup' => 'Asesmen Ranap', 'tipe' => 'Ranap'],
    'Asesmen Awal Medis Ranap' => ['tabel' => 'penilaian_medis_ranap', 'grup' => 'Asesmen Ranap', 'tipe' => 'Ranap'],
    'Asesmen Awal Medis Kandungan' => ['tabel' => 'penilaian_medis_ranap_kandungan', 'grup' => 'Asesmen Ranap', 'tipe' => 'Ranap'],
    'Asesmen Awal Medis Neonatus' => ['tabel' => 'penilaian_medis_ranap_neonatus', 'grup' => 'Asesmen Ranap', 'tipe' => 'Ranap'],
    'Asesmen Awal Kep Ranap' => ['tabel' => 'penilaian_awal_keperawatan_ranap', 'grup' => 'Asesmen Ranap', 'tipe' => 'Ranap'],
    'Asesmen Awal Kebidanan Ranap' => ['tabel' => 'penilaian_awal_keperawatan_kebidanan_ranap', 'grup' => 'Asesmen Ranap', 'tipe' => 'Ranap'],
    'Asesmen Awal Neonatus Ranap' => ['tabel' => 'penilaian_awal_keperawatan_ranap_neonatus', 'grup' => 'Asesmen Ranap', 'tipe' => 'Ranap'],
    'CPPT Ralan' => ['tabel' => 'pemeriksaan_ralan', 'grup' => 'CPPT & SOAP', 'tipe' => 'Ralan'],
    'CPPT Ranap' => ['tabel' => 'pemeriksaan_ranap', 'grup' => 'CPPT & SOAP', 'tipe' => 'Ranap'],
    'Catatan Keperawatan Ranap' => ['tabel' => 'catatan_keperawatan_ranap', 'grup' => 'CPPT & SOAP', 'tipe' => 'Ranap'],
    'Grafik Harian / Observasi' => ['tabel' => 'catatan_observasi_ranap', 'grup' => 'CPPT & SOAP', 'tipe' => 'Ranap'],
    'Resep Dokter' => ['tabel' => 'resep_obat', 'grup' => 'Penunjang', 'tipe' => 'All'],
    'Permintaan Lab' => ['tabel' => 'permintaan_lab', 'grup' => 'Penunjang', 'tipe' => 'All'],
    'Permintaan Radiologi' => ['tabel' => 'permintaan_radiologi', 'grup' => 'Penunjang', 'tipe' => 'All'],
    'Diagnosa (ICD10)' => ['tabel' => 'diagnosa_pasien', 'grup' => 'Penunjang', 'tipe' => 'All'],
    'Prosedur (ICD9)' => ['tabel' => 'prosedur_pasien', 'grup' => 'Penunjang', 'tipe' => 'All'],
    'Penilaian Pre-Operasi' => ['tabel' => 'penilaian_pre_operasi', 'grup' => 'Operasi', 'tipe' => 'All'],
    'Penilaian Pre-Anestesi' => ['tabel' => 'penilaian_pre_anestesi', 'grup' => 'Operasi', 'tipe' => 'All'],
    'Sign In (Sebelum Anestesi)' => ['tabel' => 'signin_sebelum_anestesi', 'grup' => 'Operasi', 'tipe' => 'All'],
    'Time Out (Sebelum Insisi)' => ['tabel' => 'timeout_sebelum_insisi', 'grup' => 'Operasi', 'tipe' => 'All'],
    'Sign Out (Menutup Luka)' => ['tabel' => 'signout_sebelum_menutup_luka', 'grup' => 'Operasi', 'tipe' => 'All'],
    'Laporan Operasi' => ['tabel' => 'laporan_operasi', 'grup' => 'Operasi', 'tipe' => 'All'],
    'Penilaian Ulang Nyeri' => ['tabel' => 'penilaian_ulang_nyeri', 'grup' => 'Monitoring & Risiko', 'tipe' => 'All'],
    'Risiko Dekubitus' => ['tabel' => 'penilaian_risiko_dekubitus', 'grup' => 'Monitoring & Risiko', 'tipe' => 'Ranap'],
    'Risiko Jatuh Dewasa' => ['tabel' => 'penilaian_lanjutan_resiko_jatuh_dewasa', 'grup' => 'Monitoring & Risiko', 'tipe' => 'Ranap'],
    'Risiko Jatuh Anak' => ['tabel' => 'penilaian_lanjutan_resiko_jatuh_anak', 'grup' => 'Monitoring & Risiko', 'tipe' => 'Ranap'],
    'Risiko Jatuh Lansia' => ['tabel' => 'penilaian_lanjutan_resiko_jatuh_lansia', 'grup' => 'Monitoring & Risiko', 'tipe' => 'Ranap'],
    'EWS Neonatus' => ['tabel' => 'pemantauan_ews_neonatus', 'grup' => 'Monitoring & Risiko', 'tipe' => 'Ranap'],
    'MEOWS Obstetri' => ['tabel' => 'pemantauan_meows_obstetri', 'grup' => 'Monitoring & Risiko', 'tipe' => 'Ranap'],
    'PEWS Anak' => ['tabel' => 'pemantauan_pews_anak', 'grup' => 'Monitoring & Risiko', 'tipe' => 'Ranap'],
    'NEWS Dewasa' => ['tabel' => 'pemantauan_pews_dewasa', 'grup' => 'Monitoring & Risiko', 'tipe' => 'Ranap'],
    'Skrining Gizi' => ['tabel' => 'skrining_gizi', 'grup' => 'Gizi', 'tipe' => 'All'],
    'Asuhan Gizi (ADIME)' => ['tabel' => 'catatan_adime_gizi', 'grup' => 'Gizi', 'tipe' => 'Ranap'],
    'Perencanaan Pemulangan' => ['tabel' => 'perencanaan_pemulangan', 'grup' => 'Resume & Pulang', 'tipe' => 'Ranap'],
    'Resume Pasien (Ralan)' => ['tabel' => 'resume_pasien', 'grup' => 'Resume & Pulang', 'tipe' => 'Ralan'],
    'Resume Pasien (Ranap)' => ['tabel' => 'resume_pasien_ranap', 'grup' => 'Resume & Pulang', 'tipe' => 'Ranap'],
];

// Handler Form
$tgl_awal = date('Y-m-d');
$tgl_akhir = date('Y-m-d');
$status_lanjut = 'Semua';
$selected_cols = array_keys($erm_map);

if (isset($_POST['cari'])) {
    $tgl_awal = $_POST['tanggal_awal'];
    $tgl_akhir = $_POST['tanggal_akhir'];
    $status_lanjut = $_POST['status_lanjut'];
    if (isset($_POST['cols']) && is_array($_POST['cols'])) {
        $selected_cols = $_POST['cols'];
    }
}

// Fungsi Helper Format Audit
if (!function_exists('format_audit')) {
    function format_audit($val) {
        if ($val === 'Tidak Ada') {
            return "<span class='badge bg-danger' style='font-size:0.65rem;'>KOSONG</span>";
        } else {
            return "<i class='fas fa-check-circle text-success' style='font-size:1.1rem;'></i>";
        }
    }
}
?>

<style>
    /* General Styling */
    .header-rs { background: #fff; padding: 15px 20px; border-bottom: 3px solid #dc3545; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; border-radius: 5px; }
    .rs-logo { height: 50px; margin-right: 15px; }
    .filter-box { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; }
    
    /* Custom CSS for Color Coding */
    .bg-pink { background-color: #e83e8c !important; color: white; }
    .bg-grey { background-color: #6c757d !important; color: white; }
    
    

    /* Modal Styling */
    .group-header { background-color: #e9ecef; padding: 8px 10px; font-weight: bold; border-radius: 4px; margin-top: 10px; margin-bottom: 5px; }
    .check-item { margin-bottom: 5px; }
    .check-item label { font-size: 0.85rem; cursor: pointer; }
</style>

<div class="container-fluid px-4">
    <div class="header-rs shadow-sm">
        <div class="d-flex align-items-center">
            <img src="<?php echo $logo_src_audit; ?>" alt="Logo" class="rs-logo">
            <div>
                <h4 class="m-0 fw-bold text-dark"><?php echo $nama_rs_audit; ?></h4>
                <small class="text-muted">Audit Kepatuhan & Kelengkapan Rekam Medis Elektronik</small>
            </div>
        </div>
        <div class="text-end">
            <h5 class="m-0 text-danger fw-bold">AUDIT LOG</h5>
            <small class="text-muted"><?php echo date('d F Y'); ?></small>
        </div>
    </div>

    <form method="POST" action="">
        <div class="filter-box">
            <div class="row align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-bold">Tanggal Awal</label>
                    <input type="date" name="tanggal_awal" class="form-control form-control-sm" value="<?php echo $tgl_awal; ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" class="form-control form-control-sm" value="<?php echo $tgl_akhir; ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Status Pelayanan</label>
                    <select name="status_lanjut" class="form-select form-select-sm">
                        <option value="Semua" <?php echo ($status_lanjut == 'Semua') ? 'selected' : ''; ?>>Semua (Ralan & Ranap)</option>
                        <option value="Ralan" <?php echo ($status_lanjut == 'Ralan') ? 'selected' : ''; ?>>Rawat Jalan Saja</option>
                        <option value="Ranap" <?php echo ($status_lanjut == 'Ranap') ? 'selected' : ''; ?>>Rawat Inap Saja</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Kolom Audit</label>
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#columnModal">
                        <i class="fas fa-list-check me-1"></i> Pilih Kolom
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="cari" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search me-1"></i> Tampilkan Data
                    </button>
                </div>
            </div>
        </div>

        <div class="modal fade" id="columnModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title"><i class="fas fa-tasks me-2"></i>Konfigurasi Kolom Audit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3 sticky-top bg-white py-2 border-bottom">
                            <div class="col-md-6">
                                <input type="text" id="searchCol" class="form-control form-control-sm" placeholder="Cari nama formulir/asesmen...">
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="button" class="btn btn-sm btn-success" onclick="checkAll(true)">Pilih Semua</button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="checkAll(false)">Hapus Semua</button>
                            </div>
                        </div>
                        <div class="row" id="checkboxList">
                            <?php
                            $grouped_map = [];
                            foreach ($erm_map as $key => $val) {
                                $grouped_map[$val['grup']][$key] = $val;
                            }
                            foreach ($grouped_map as $grup => $items) {
                                echo "<div class='col-12 group-header'>$grup</div>";
                                foreach ($items as $label => $val) {
                                    $checked = in_array($label, $selected_cols) ? 'checked' : '';
                                    echo "
                                    <div class='col-md-3 col-sm-6 check-item'>
                                        <div class='form-check'>
                                            <input class='form-check-input col-checkbox' type='checkbox' name='cols[]' value='$label' id='chk_$label' $checked>
                                            <label class='form-check-label' for='chk_$label'>$label</label>
                                        </div>
                                    </div>";
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Simpan Pilihan</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- AI ERM COMPLIANCE ADVISOR CONTAINER -->
    <?php if (isset($_POST['cari']) && is_ai_active()): ?>
    <div class="card bg-dark border-secondary shadow-sm mb-4">
        <div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold"><i class="fas fa-brain me-2"></i>Analisis Kepatuhan ERM AI (ERM Audit Advisor)</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseErmPrompt">
                    <i class="fas fa-sliders-h me-1"></i> Tune Prompt
                </button>
                <button id="btnAnalyzeErm" class="btn btn-sm btn-success fw-bold">
                    <i class="fas fa-magic me-1"></i> Jalankan Analisis AI
                </button>
            </div>
        </div>
        <div class="card-body text-light">
            <!-- Collapsible Prompt Tuning Area -->
            <div class="collapse mb-3" id="collapseErmPrompt">
                <div class="p-3 rounded border border-secondary bg-black bg-opacity-50">
                    <label class="form-label text-warning small fw-bold">System Prompt (Instruksi Analisis Kelengkapan ERM):</label>
                    <textarea id="aiErmPrompt" class="form-control form-control-sm bg-dark text-light border-secondary" rows="4">Anda adalah Auditor Utama Rekam Medis & Manajer Mutu Pelayanan Kesehatan RS (Standar Akreditasi KARS/STARKES). Analisis data kepatuhan kelengkapan pengisian dokumen Rekam Medis Elektronik (ERM) berikut (mencakup persentase kelengkapan, form paling sering kosong/tidak diisi, kepatuhan per DPJP/Dokter, serta per unit pelayanan) dan susun Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:
1. Tingkat Kepatuhan Pengisian ERM (hitung estimasi skor kepatuhan global dan kelompok form paling bermasalah).
2. Identifikasi Area Risiko Audit & Akreditasi (dampak kelengkapan resume/CPPT/asesmen medis terhadap klaim BPJS & legalitas medis).
3. Evaluasi Kepatuhan Per Dokter / Unit (soroti unit atau dokter dengan kelengkapan ERM terendah).
4. Rekomendasi Tindakan Korektif & Penegakan SOP bagi Direksi RS.</textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Setel prompt khusus ini untuk menyesuaikan gaya laporan kepatuhan akreditasi ERM yang dihasilkan AI.</small>
                        <button class="btn btn-xs btn-outline-warning text-warning" onclick="resetErmPrompt()"><i class="fas fa-undo me-1"></i>Reset Prompt Default</button>
                    </div>
                </div>
            </div>

            <!-- Display Container Output -->
            <div id="aiErmReportContainer" class="p-3 rounded border border-secondary bg-black bg-opacity-25 text-light" style="min-height: 120px; max-height: 500px; overflow-y: auto;">
                <div class="text-muted small text-center py-4">
                    <i class="fas fa-robot fa-2x mb-2 text-primary d-block"></i>
                    Klik tombol <strong>"Jalankan Analisis AI"</strong> di atas untuk memproses ringkasan audit ERM secara otomatis.
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Kepatuhan dianalisis berdasarkan tanggal registrasi dan status pelayanan terpilih.</small>
                <button class="btn btn-sm btn-outline-info" onclick="exportToWord('aiErmReportContainer', 'Laporan_Analisis_Audit_Kepatuhan_ERM_AI.doc')">
                    <i class="fas fa-file-word me-1"></i> Ekspor Laporan ke Word (.doc)
                </button>
            </div>

            <!-- AI Interactive Chat Assistant -->
            <div class="mt-4 pt-3 border-top border-secondary">
                <h6 class="fw-bold text-info mb-2"><i class="fas fa-comments me-2"></i>Tanya Jawab & Diskusi Kepatuhan ERM dengan AI Assistant</h6>
                <div id="ermChatHistory" class="p-3 rounded border border-secondary bg-black bg-opacity-50 mb-2" style="max-height: 300px; overflow-y: auto; min-height: 100px;">
                    <div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>
                </div>
                <form id="ermChatForm">
                    <div class="input-group input-group-sm">
                        <input type="text" id="ermChatInput" class="form-control bg-dark text-light border-secondary" placeholder="Tanyakan detail kepatuhan (misal: Form apa saja yang paling banyak tidak diisi?)..." required>
                        <button class="btn btn-primary" type="submit" id="btnSendErmChat">
                            <i class="fas fa-paper-plane me-1"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-2">
            <div class="table-responsive">
                <table id="tableAudit" class="table table-striped table-bordered table-hover w-100">
                    <thead>
                        <tr>
                            <th class="fixed-col" style="min-width: 50px;">No.</th>
                            <th class="fixed-col" style="min-width: 120px;">No. Rawat</th>
                            <th style="min-width: 90px;">Tgl Reg</th>
                            <th style="min-width: 80px;">No. RM</th>
                            <th style="min-width: 200px;">Pasien</th>
                            <th style="min-width: 120px;">Penjamin</th>
                            <th style="min-width: 200px;">Dokter</th>
                            <th style="min-width: 150px;">Poli/Bangsal</th>
                            <th style="min-width: 80px;">Status</th>
                            
                            <?php foreach ($selected_cols as $col): ?>
                                <th class="text-center" style="min-width: 100px;"><?php echo $col; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if (isset($_POST['cari'])) {
                        
                        // MODIFIKASI QUERY: Tambah JOIN penjab untuk penjamin
                        $sql = "SELECT 
                                    rp.no_rawat, rp.tgl_registrasi, rp.no_rkm_medis, rp.status_lanjut,
                                    p.nm_pasien, d.nm_dokter, pj.png_jawab,
                                    IF(rp.status_lanjut='Ralan', poli.nm_poli, b.nm_bangsal) as unit
                                FROM reg_periksa rp
                                JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                                JOIN dokter d ON rp.kd_dokter = d.kd_dokter
                                JOIN penjab pj ON rp.kd_pj = pj.kd_pj
                                LEFT JOIN poliklinik poli ON rp.kd_poli = poli.kd_poli
                                LEFT JOIN kamar_inap ki ON rp.no_rawat = ki.no_rawat
                                LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
                                LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
                                WHERE rp.tgl_registrasi BETWEEN ? AND ?
                                AND rp.stts <> 'Batal' ";
                        
                        if ($status_lanjut != 'Semua') {
                            $sql .= " AND rp.status_lanjut = '$status_lanjut' ";
                        }

                        $sql .= " GROUP BY rp.no_rawat ORDER BY rp.tgl_registrasi ASC, rp.jam_reg ASC";

                        $stmt = $koneksi->prepare($sql);
                        $stmt->bind_param("ss", $tgl_awal, $tgl_akhir);
                        $stmt->execute();
                        $result_main = $stmt->get_result();
                        
                        $no_urut = 1;
                        $form_counts = [];
                        $dokter_counts = [];
                        $unit_counts = [];
                        $sample_rows = [];
                        
                        foreach ($selected_cols as $col_label) {
                            $form_counts[$col_label] = ['ada' => 0, 'tidak' => 0];
                        }
                        
                        while ($row = $result_main->fetch_assoc()) {
                            $no_rawat = $row['no_rawat'];
                            $dokter_name = $row['nm_dokter'];
                            $unit_name = $row['unit'];
                            
                            if (!isset($dokter_counts[$dokter_name])) {
                                $dokter_counts[$dokter_name] = ['total' => 0, 'ada' => 0];
                            }
                            if (!isset($unit_counts[$unit_name])) {
                                $unit_counts[$unit_name] = ['total' => 0, 'ada' => 0];
                            }
                            
                            // LOGIKA WARNA STATUS
                            $status_badge = ($row['status_lanjut'] == 'Ralan') 
                                ? 'bg-info'  // Biru Muda
                                : 'bg-warning text-dark'; // Kuning
                                
                            // LOGIKA WARNA PENJAMIN
                            $pj = $row['png_jawab'];
                            $pj_badge = 'bg-secondary'; // Default Abu-abu (Perusahaan/Lainnya)
                            
                            if (stripos($pj, 'BPJS') !== false) {
                                $pj_badge = 'bg-success'; // Hijau
                            } elseif (stripos($pj, 'Umum') !== false || stripos($pj, 'Tunai') !== false) {
                                $pj_badge = 'bg-primary'; // Biru
                            } elseif (stripos($pj, 'Asuransi') !== false) {
                                $pj_badge = 'bg-pink'; // Pink
                            } 
                            
                            echo "<tr>";
                            echo "<td class='fixed-col text-center'>$no_urut</td>";
                            echo "<td class='fixed-col fw-bold'>$no_rawat</td>";
                            echo "<td>{$row['tgl_registrasi']}</td>";
                            echo "<td>{$row['no_rkm_medis']}</td>";
                            echo "<td>{$row['nm_pasien']}</td>";
                            echo "<td><span class='badge $pj_badge'>{$row['png_jawab']}</span></td>";
                            echo "<td>{$row['nm_dokter']}</td>";
                            echo "<td>{$row['unit']}</td>";
                            echo "<td><span class='badge $status_badge'>{$row['status_lanjut']}</span></td>";
                            
                            $row_cells = [];
                            // Loop Dinamis Kolom Terpilih
                            foreach ($selected_cols as $col_label) {
                                $config = $erm_map[$col_label];
                                $table_name = $config['tabel'];
                                
                                $check_sql = "SELECT 1 FROM $table_name WHERE no_rawat = '$no_rawat' LIMIT 1";
                                $check_res = $koneksi->query($check_sql);
                                $status_isi = ($check_res && $check_res->num_rows > 0) ? 'Ada' : 'Tidak Ada';
                                
                                if ($status_isi == 'Ada') {
                                    $form_counts[$col_label]['ada']++;
                                    $dokter_counts[$dokter_name]['ada']++;
                                    $unit_counts[$unit_name]['ada']++;
                                } else {
                                    $form_counts[$col_label]['tidak']++;
                                }
                                $dokter_counts[$dokter_name]['total']++;
                                $unit_counts[$unit_name]['total']++;
                                
                                echo "<td class='text-center'>" . format_audit($status_isi) . "</td>";
                                $row_cells[$col_label] = $status_isi;
                            }
                            echo "</tr>";
                            
                            if ($no_urut <= 30) {
                                $sample_rows[] = [
                                    'no_rawat' => $no_rawat,
                                    'tgl' => $row['tgl_registrasi'],
                                    'pasien' => $row['nm_pasien'],
                                    'dokter' => $dokter_name,
                                    'unit' => $unit_name,
                                    'status_lanjut' => $row['status_lanjut'],
                                    'audit' => $row_cells
                                ];
                            }
                            
                            $no_urut++;
                        }
                        
                        $erm_summary_data = [
                            'filter' => [
                                'tgl_awal' => $tgl_awal,
                                'tgl_akhir' => $tgl_akhir,
                                'status_lanjut' => $status_lanjut
                            ],
                            'total_pasien' => $no_urut - 1,
                            'form_stats' => $form_counts,
                            'dokter_stats' => $dokter_counts,
                            'unit_stats' => $unit_counts,
                            'sample_audit' => $sample_rows
                        ];
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(document).ready(function() {
    $('#tableAudit').DataTable({
        "scrollX": true,
        "scrollY": "60vh",
        "scrollCollapse": true,
        "paging": false, 
        "fixedColumns": {
            left: 2 // Fix No dan No Rawat
        },
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excelHtml5', className: 'btn btn-success btn-sm', text: '<i class="fas fa-file-excel"></i> Excel' },
            { extend: 'print', className: 'btn btn-secondary btn-sm', text: '<i class="fas fa-print"></i> Print' }
        ],
        language: { url: "//cdn.datatables.net/plug-ins/1.10.21/i18n/Indonesian.json" }
    });

    $("#searchCol").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $(".check-item").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
        $(".group-header").each(function() {
            var groupVisible = $(this).nextUntil(".group-header", ".check-item:visible").length > 0;
            $(this).toggle(groupVisible);
        });
    });
});

function checkAll(status) {
    $('.col-checkbox').prop('checked', status);
}

// --- AI ERM ADVISOR JS PIPELINE ---
var _ermAuditResponseData = <?php echo isset($erm_summary_data) ? json_encode($erm_summary_data) : 'null'; ?>;
var currentErmReportContext = "";
var ermChatHistoryData = [];
const defaultErmPromptText = "Anda adalah Auditor Utama Rekam Medis & Manajer Mutu Pelayanan Kesehatan RS (Standar Akreditasi KARS/STARKES). Analisis data kepatuhan kelengkapan pengisian dokumen Rekam Medis Elektronik (ERM) berikut (mencakup persentase kelengkapan, form paling sering kosong/tidak diisi, kepatuhan per DPJP/Dokter, serta per unit pelayanan) dan susun Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:\n1. Tingkat Kepatuhan Pengisian ERM (hitung estimasi skor kepatuhan global dan kelompok form paling bermasalah).\n2. Identifikasi Area Risiko Audit & Akreditasi (dampak kelengkapan resume/CPPT/asesmen medis terhadap klaim BPJS & legalitas medis).\n3. Evaluasi Kepatuhan Per Dokter / Unit (soroti unit atau dokter dengan kelengkapan ERM terendah).\n4. Rekomendasi Tindakan Korektif & Penegakan SOP bagi Direksi RS.";

function resetErmPrompt() {
    $('#aiErmPrompt').val(defaultErmPromptText);
}

function parseMarkdownToHtml(md) {
    if (!md) return '';
    return md
        .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
        .replace(/^### (.*?)$/gm, '<h5 class="fw-bold text-info mt-3">$1</h5>')
        .replace(/^## (.*?)$/gm, '<h4 class="fw-bold text-primary mt-4 border-bottom border-secondary pb-1">$1</h4>')
        .replace(/^# (.*?)$/gm, '<h3 class="fw-bold text-primary mt-4">$1</h3>')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/^\s*[-*+]\s+(.*?)$/gm, '<li>$1</li>')
        .replace(/(<li>.*?<\/li>)/gs, '<ul class="mb-2">$1</ul>')
        .replace(/<\/ul>\s*<ul class="mb-2">/g, '')
        .replace(/^\s*([^#<>\s\-*+].*?)$/gm, '<p class="mb-2">$1</p>')
        .replace(/\n\n/g, '<br>');
}

function exportToWord(elementId, fileName) {
    var content = document.getElementById(elementId).innerHTML;
    var header = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>" +
                 "<head><meta charset='utf-8'><title>Laporan Ekspor</title>" +
                 "<style>body { font-family: Arial, sans-serif; line-height: 1.6; } h1, h2, h3 { color: #0284c7; }</style></head><body>";
    var footer = "</body></html>";
    
    var blob = new Blob(['\ufeff', header + content + footer], { type: 'application/msword' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = fileName || 'Laporan.doc';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

$(document).on('click', '#btnAnalyzeErm', function() {
    if (!_ermAuditResponseData) {
        alert('Silakan lakukan pencarian data audit terlebih dahulu.');
        return;
    }

    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menganalisis...');
    $('#aiErmReportContainer').html('<div class="text-center py-4"><div class="spinner-border text-info mb-2"></div><div class="small text-muted">AI sedang menganalisis kepatuhan ERM...</div></div>');

    var formData = new URLSearchParams();
    formData.append('action', 'batch_summary');
    formData.append('raw_data', JSON.stringify([_ermAuditResponseData]));
    formData.append('custom_prompt', $('#aiErmPrompt').val().trim());
    formData.append('stream', '1');

    fetch('api/ai_analyzer.php', {
        method: 'POST',
        body: formData,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    }).then(async response => {
        const reader = response.body.getReader();
        const decoder = new TextDecoder("utf-8");
        let fullText = "";
        let isError = false;
            let isThinking = false;
            const aiThinkingContainer = document.getElementById('aiErmReportContainer');
        let buffer = "";

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            buffer += decoder.decode(value, {stream: true});
            const lines = buffer.split('\n');
            buffer = lines.pop();

            for (let line of lines) {
                    if (line === 'event: thinking') {
                        isThinking = true;
                        continue;
                    }
                    if (isThinking && line.startsWith('data: ')) {
                        isThinking = false;
                        try {
                            const td = JSON.parse(line.substring(6));
                            if (typeof aiThinkingContainer !== 'undefined' && aiThinkingContainer) {
                                aiThinkingContainer.innerHTML = buildThinkingHTML(td.row_count || 0, td.message || '');
                            }
                        } catch(e) {}
                        continue;
                    }

                line = line.trim();
                if (line.startsWith('data: ')) {
                    const dataStr = line.substring(6);
                    if (dataStr === '[DONE]') continue;
                    try {
                        const data = JSON.parse(dataStr);
                        if (data.message) {
                            isError = true;
                            $('#aiErmReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + data.message + '</div>');
                        }
                        if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                            fullText += data.choices[0].delta.content;
                            $('#aiErmReportContainer').html(parseMarkdownToHtml(fullText));
                        }
                    } catch(e) {}
                } else if (line.startsWith('event: error')) {
                    isError = true;
                }
            }
        }

        btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');

        if (!isError && fullText) {
            currentErmReportContext = fullText;
            ermChatHistoryData = [];
            $('#ermChatHistory').html('<div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>');
        }
    }).catch(err => {
        btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');
        $('#aiErmReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: Gagal menghubungi server (' + err.message + ')</div>');
    });
});

$(document).on('submit', '#ermChatForm', function(e) {
    e.preventDefault();
    const input = $('#ermChatInput');
    const messageText = input.val().trim();
    if (!messageText || !currentErmReportContext) return;

    if (ermChatHistoryData.length === 0) {
        $('#ermChatHistory').empty();
    }

    const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    $('#ermChatHistory').append(
        '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-primary border-3">' +
            '<div class="d-flex justify-content-between mb-1">' +
                '<span class="fw-bold small text-primary"><i class="fas fa-user me-1"></i>Anda</span>' +
                '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
            '</div>' +
            '<div class="small text-light">' + parseMarkdownToHtml(messageText) + '</div>' +
        '</div>'
    );
    $('#ermChatHistory').scrollTop($('#ermChatHistory')[0].scrollHeight);

    input.val('');
    $('#ermChatInput, #btnSendErmChat').prop('disabled', true);

    var replyId = 'erm_reply_' + Date.now();
    $('#ermChatHistory').append(
        '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-info border-3">' +
            '<div class="d-flex justify-content-between mb-1">' +
                '<span class="fw-bold small text-info"><i class="fas fa-robot me-1"></i>AI Audit Assistant</span>' +
                '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
            '</div>' +
            '<div class="small text-light" id="' + replyId + '"><i class="fas fa-spinner fa-spin text-info me-1"></i> Mengetik...</div>' +
        '</div>'
    );
    $('#ermChatHistory').scrollTop($('#ermChatHistory')[0].scrollHeight);

    var chatData = new URLSearchParams();
    chatData.append('action', 'chat_discuss');
    chatData.append('message', messageText);
    chatData.append('report_context', currentErmReportContext);
    chatData.append('raw_data', JSON.stringify([_ermAuditResponseData]));
    chatData.append('custom_prompt', $('#aiErmPrompt').val().trim());
    chatData.append('history', JSON.stringify(ermChatHistoryData));
    chatData.append('stream', '1');

    fetch('api/ai_analyzer.php', {
        method: 'POST',
        body: chatData,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    }).then(async response => {
        const reader = response.body.getReader();
        const decoder = new TextDecoder("utf-8");
        let fullReply = "";
        let isError = false;
            let isThinking = false;
            const aiThinkingContainer = document.getElementById('aiErmReportContainer');
        let buffer = "";

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            buffer += decoder.decode(value, {stream: true});
            const lines = buffer.split('\n');
            buffer = lines.pop();

            for (let line of lines) {
                    if (line === 'event: thinking') {
                        isThinking = true;
                        continue;
                    }
                    if (isThinking && line.startsWith('data: ')) {
                        isThinking = false;
                        try {
                            const td = JSON.parse(line.substring(6));
                            if (typeof aiThinkingContainer !== 'undefined' && aiThinkingContainer) {
                                aiThinkingContainer.innerHTML = buildThinkingHTML(td.row_count || 0, td.message || '');
                            }
                        } catch(e) {}
                        continue;
                    }

                line = line.trim();
                if (line.startsWith('data: ')) {
                    const dataStr = line.substring(6);
                    if (dataStr === '[DONE]') continue;
                    try {
                        const data = JSON.parse(dataStr);
                        if (data.message) {
                            isError = true;
                            $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> ' + data.message + '</span>');
                        }
                        if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                            fullReply += data.choices[0].delta.content;
                            $('#' + replyId).html(parseMarkdownToHtml(fullReply));
                            $('#ermChatHistory').scrollTop($('#ermChatHistory')[0].scrollHeight);
                        }
                    } catch(e) {}
                } else if (line.startsWith('event: error')) {
                    isError = true;
                }
            }
        }

        $('#ermChatInput, #btnSendErmChat').prop('disabled', false);

        if (!isError && fullReply) {
            ermChatHistoryData.push({ role: 'user', content: messageText });
            ermChatHistoryData.push({ role: 'assistant', content: fullReply });
        }
    }).catch(err => {
        $('#ermChatInput, #btnSendErmChat').prop('disabled', false);
        $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Error koneksi</span>');
    });
});
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>