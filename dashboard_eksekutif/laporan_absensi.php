<?php
session_start();
require_once 'config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$page_title = "Laporan Absensi & Kedisiplinan";
include 'includes/header.php';
?>

<style>
    /* Premium Glassmorphism & UI Tuning */
    :root {
        --glass-bg: rgba(255, 255, 255, 0.85);
        --glass-border: rgba(255, 255, 255, 0.4);
        --glass-shadow: 0 8px 32px rgba(31, 38, 135, 0.1);
        --gradient-primary: linear-gradient(135deg, #0d6efd, #0dcaf0);
        --gradient-danger: linear-gradient(135deg, #dc3545, #fd7e14);
        --gradient-warning: linear-gradient(135deg, #ffc107, #ffecd2);
        --gradient-success: linear-gradient(135deg, #198754, #20c997);
    }
    
    .glass-card {
        background: var(--glass-bg);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid var(--glass-border);
        box-shadow: var(--glass-shadow);
        border-radius: 16px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .glass-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 40px rgba(31, 38, 135, 0.15);
    }

    .nav-tabs .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 600;
        padding: 12px 24px;
        border-radius: 12px 12px 0 0;
        transition: all 0.3s ease;
        position: relative;
    }
    .nav-tabs .nav-link::after {
        content: '';
        position: absolute;
        bottom: 0; left: 50%;
        transform: translateX(-50%);
        width: 0; height: 3px;
        background: var(--gradient-primary);
        transition: width 0.3s ease;
    }
    .nav-tabs .nav-link:hover { color: #0d6efd; background: rgba(13, 110, 253, 0.05); }
    .nav-tabs .nav-link.active {
        color: #0d6efd;
        background: transparent;
        border: none;
    }
    .nav-tabs .nav-link.active::after { width: 100%; }

    .stat-box {
        padding: 20px;
        border-radius: 16px;
        color: white;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .stat-box i {
        position: absolute;
        right: -10px; bottom: -15px;
        font-size: 5rem;
        opacity: 0.2;
    }
    .stat-box h3 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
    .stat-box p { font-size: 0.9rem; font-weight: 600; margin: 0; text-transform: uppercase; letter-spacing: 1px; }

    .bg-gradient-primary { background: var(--gradient-primary); }
    .bg-gradient-danger { background: var(--gradient-danger); }
    .bg-gradient-warning { background: var(--gradient-warning); color: #333 !important; }
    .bg-gradient-success { background: var(--gradient-success); }
    
    .table-glass { background: transparent; }
    .table-glass thead th {
        background: rgba(13, 110, 253, 0.08);
        color: #495057;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid rgba(13, 110, 253, 0.2);
    }
    .table-glass tbody tr { transition: background 0.2s; }
    .table-glass tbody tr:hover { background: rgba(13, 110, 253, 0.04); }

    .filter-panel { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px; }
    
    .chart-container { position: relative; height: 350px; width: 100%; }

    .badge-soft-danger { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; }
    .badge-soft-success { background-color: rgba(25, 135, 84, 0.1); color: #198754; }
    .badge-soft-warning { background-color: rgba(255, 193, 7, 0.2); color: #d39e00; }
    .badge-soft-info { background-color: rgba(13, 202, 240, 0.1); color: #0dcaf0; }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <h1 class="h2"><i class="fas fa-user-clock text-primary me-2"></i> Laporan Absensi &amp; Kedisiplinan</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin'): ?>
        <button class="btn btn-sm btn-outline-warning fw-bold" onclick="openMigrasiModal()">
            <i class="fas fa-database me-1"></i> Migrasi Schema DB
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin'): ?>
<!-- =============================================================
     SUPERADMIN PANEL: Migrasi Schema Database Jadwal
     Menambahkan nilai ENUM 'Libur' & 'Cuti' pada tabel:
       - jadwal_pegawai  (h1-h31)
       - jadwal_tambahan (h1-h31)
       - rekap_presensi  (shift)
       - temporary_presensi (shift)
============================================================== -->
<div class="modal fade" id="modalMigrasi" tabindex="-1" aria-labelledby="labelMigrasi" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border:2px solid #ffc107; border-radius:16px;">
      <div class="modal-header" style="background:linear-gradient(135deg,#856404,#ffc107); color:#fff; border-radius:14px 14px 0 0;">
        <h5 class="modal-title" id="labelMigrasi">
          <i class="fas fa-database me-2"></i> Migrasi Schema DB &mdash; Khanza Custom Edition
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning py-2 small mb-3">
          <i class="fas fa-exclamation-triangle me-1"></i>
          <strong>Superadmin Only.</strong> Panel ini menjalankan <code>ALTER TABLE</code> untuk menambahkan
          nilai ENUM <code>'Libur'</code> dan <code>'Cuti'</code> pada kolom jadwal &amp; presensi.
          Diperlukan agar Khanza Custom Edition dapat mencatat jadwal Libur/Cuti tanpa error MySQL.
        </div>

        <div class="table-responsive mb-1">
          <table class="table table-sm small mb-0">
            <thead class="table-secondary"><tr>
              <th>Tabel yang Diperiksa</th><th>Keterangan Modifikasi</th>
            </tr></thead>
            <tbody>
              <tr><td class="font-monospace">jadwal_pegawai</td><td>31 kolom h1&ndash;h31: tambah <code>'Libur','Cuti'</code> ke ENUM</td></tr>
              <tr><td class="font-monospace">jadwal_tambahan</td><td>31 kolom h1&ndash;h31: tambah <code>'Libur','Cuti'</code> ke ENUM</td></tr>
              <tr><td class="font-monospace">rekap_presensi</td><td>Kolom <code>shift</code>: tambah <code>'Libur','Cuti'</code> ke ENUM</td></tr>
              <tr><td class="font-monospace">temporary_presensi</td><td>Kolom <code>shift</code>: tambah <code>'Libur','Cuti'</code> ke ENUM</td></tr>
            </tbody>
          </table>
        </div>

        <h6 class="fw-bold text-secondary mt-3 mb-2"><i class="fas fa-search-plus me-1"></i> Status Deteksi:</h6>
        <div id="migrasiStatusArea">
          <div class="text-center py-3">
            <div class="spinner-border text-warning spinner-border-sm"></div>
            <span class="ms-2 small text-muted">Memeriksa schema database...</span>
          </div>
        </div>

        <div id="migrasiLogArea" class="mt-3" style="display:none;">
          <h6 class="fw-bold text-secondary mb-2"><i class="fas fa-terminal me-1"></i> Log Eksekusi:</h6>
          <div id="migrasiLogContent"
               class="font-monospace small bg-dark text-light p-3 rounded"
               style="max-height:220px; overflow-y:auto; white-space:pre-wrap;"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Tutup</button>
        <button class="btn btn-warning fw-bold" id="btnJalankanMigrasi" onclick="jalankanMigrasi()" disabled>
          <i class="fas fa-play-circle me-1"></i> Jalankan Migrasi
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- FILTER PANEL -->
<div class="filter-panel d-flex flex-wrap gap-3 align-items-end">
    <div class="flex-grow-1" style="min-width: 200px;">
        <label class="form-label small fw-bold text-muted text-uppercase">Tgl Awal</label>
        <input type="date" id="tgl1" class="form-control" value="<?php echo date('Y-m-01'); ?>">
    </div>
    <div class="flex-grow-1" style="min-width: 200px;">
        <label class="form-label small fw-bold text-muted text-uppercase">Tgl Akhir</label>
        <input type="date" id="tgl2" class="form-control" value="<?php echo date('Y-m-d'); ?>">
    </div>
    <div class="flex-grow-1" style="min-width: 250px;">
        <label class="form-label small fw-bold text-muted text-uppercase">Departemen</label>
        <select id="dep" class="form-select">
            <option value="ALL">Semua Departemen</option>
        </select>
    </div>
    <div>
        <button class="btn btn-primary px-4 fw-bold shadow-sm" onclick="loadAllData()">
            <i class="fas fa-search me-2"></i> Analisa Data
        </button>
    </div>
</div>

<?php if (is_ai_active()): ?>
<!-- AI ABSENSI ANALYZER CONTAINER -->
<div class="card bg-dark border-secondary mt-4 shadow-sm mb-4">
    <div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center py-2">
        <span class="fw-bold"><i class="fas fa-brain me-2"></i>Analisis Kedisiplinan & Burnout Staf AI (AI Workforce Advisor)</span>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAbsensiPrompt">
                <i class="fas fa-sliders-h me-1"></i> Tune Prompt
            </button>
            <button id="btnAnalyzeAbsensi" class="btn btn-sm btn-success fw-bold">
                <i class="fas fa-magic me-1"></i> Jalankan Analisis AI
            </button>
        </div>
    </div>
    <div class="card-body text-light">
        <!-- Collapsible Prompt Tuning Area -->
        <div class="collapse mb-3" id="collapseAbsensiPrompt">
            <div class="p-3 rounded border border-secondary bg-black bg-opacity-50">
                <label class="form-label text-warning small fw-bold">System Prompt (Instruksi Analisis Kehadiran & Lembur Staf):</label>
                <textarea id="aiAbsensiPrompt" class="form-control form-control-sm bg-dark text-light border-secondary" rows="4">Anda adalah AI HR & Workforce Attendance Advisor yang ahli dalam analisis manajemen SDM rumah sakit, evaluasi kedisiplinan pegawai, dan efisiensi anggaran lembur. Analisis data rekap kehadiran, tingkat kemangkiran/keterlambatan, risiko kelelahan kerja (fatigue risk dari shift malam berturut-turut), dan biaya pengajuan lembur berikut. Berikan rekomendasi strategis bagi Direktur untuk meningkatkan kedisiplinan staf, mencegah burnout/error medis akibat fatigue, dan mengoptimalkan beban biaya lembur per departemen.</textarea>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <small class="text-muted">Setel prompt khusus ini untuk menyesuaikan gaya analisis HR yang dihasilkan AI.</small>
                    <button class="btn btn-xs btn-outline-warning text-warning" onclick="resetAbsensiPrompt()"><i class="fas fa-undo me-1"></i>Reset Prompt Default</button>
                </div>
            </div>
        </div>

        <!-- Display Container Output -->
        <div id="aiAbsensiReportContainer" class="p-3 rounded border border-secondary bg-black bg-opacity-25 text-light" style="min-height: 120px; max-height: 500px; overflow-y: auto;">
            <div class="text-muted small text-center py-4">
                <i class="fas fa-robot fa-2x mb-2 text-primary d-block"></i>
                Klik tombol <strong>"Jalankan Analisis AI"</strong> di atas untuk memproses ringkasan analisis absensi secara otomatis.
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
            <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Analisis absensi dievaluasi berdasarkan range filter tanggal terpilih.</small>
            <button class="btn btn-sm btn-outline-info" onclick="exportToWord('aiAbsensiReportContainer', 'Laporan_Analisis_Absensi_AI.doc')">
                <i class="fas fa-file-word me-1"></i> Ekspor Laporan ke Word (.doc)
            </button>
        </div>

        <!-- AI Interactive Chat Assistant -->
        <div class="mt-4 pt-3 border-top border-secondary">
            <h6 class="fw-bold text-info mb-2"><i class="fas fa-comments me-2"></i>Tanya Jawab & Diskusi Absensi & Lembur Staf dengan AI Assistant</h6>
            <div id="absensiChatHistory" class="p-3 rounded border border-secondary bg-black bg-opacity-50 mb-2" style="max-height: 300px; overflow-y: auto; min-height: 100px;">
                <div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>
            </div>
            <form id="absensiChatForm">
                <div class="input-group input-group-sm">
                    <input type="text" id="absensiChatInput" class="form-control bg-dark text-light border-secondary" placeholder="Tanyakan detail absensi (misal: Siapa saja yang berisiko tinggi fatigue di keperawatan?)..." required>
                    <button class="btn btn-primary" type="submit" id="btnSendAbsensiChat">
                        <i class="fas fa-paper-plane me-1"></i> Kirim
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- TABS -->
<ul class="nav nav-tabs mb-4 border-0" id="absensiTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="evaluasi-tab" data-bs-toggle="tab" data-bs-target="#evaluasi" type="button" role="tab"><i class="fas fa-clipboard-check me-2"></i> Evaluasi Kehadiran</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="potongan-tab" data-bs-toggle="tab" data-bs-target="#potongan" type="button" role="tab"><i class="fas fa-exclamation-circle me-2"></i> Rekap Pelanggaran</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="fatigue-tab" data-bs-toggle="tab" data-bs-target="#fatigue" type="button" role="tab"><i class="fas fa-bed me-2"></i> Risiko Kelelahan (Fatigue)</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="lembur-tab" data-bs-toggle="tab" data-bs-target="#lembur" type="button" role="tab"><i class="fas fa-money-bill-wave me-2"></i> Biaya & Jam Lembur</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="analitik-tab" data-bs-toggle="tab" data-bs-target="#analitik" type="button" role="tab"><i class="fas fa-chart-pie me-2"></i> Indikator Kedisiplinan</button>
    </li>
</ul>

<div class="tab-content" id="absensiTabContent">
    
    <!-- TAB 1: EVALUASI KETIDAKHADIRAN -->
    <div class="tab-pane fade show active" id="evaluasi" role="tabpanel">
        <div class="glass-card p-4">
            <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold m-0 text-primary">Data Rekap Kehadiran vs Jadwal (Realtime)</h5>
                <select id="filterEvaluasi" class="form-select w-auto form-select-sm border-danger text-danger fw-bold" onchange="loadEvaluasi()">
                    <option value="MANGKIR">Hanya Mangkir / Belum Hadir</option>
                    <option value="ALL">Tampilkan Semua Jadwal</option>
                </select>
            </div>
            <div class="table-responsive">
                <table id="tblEvaluasi" class="table table-glass table-hover w-100">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Pegawai</th>
                            <th class="text-center">Shift</th>
                            <th class="text-center">Jadwal Wajib</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Masuk</th>
                            <th class="text-center">Pulang</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: DENDA KETERLAMBATAN -->
    <div class="tab-pane fade" id="potongan" role="tabpanel">
        <div class="glass-card p-4">
            <h5 class="fw-bold mb-4 text-primary">Rekapitulasi Denda Terlambat & Mangkir</h5>
            <div class="table-responsive">
                <table id="tblPotongan" class="table table-glass table-hover w-100 align-middle">
                    <thead>
                        <tr>
                            <th>Nama Pegawai</th>
                            <th class="text-center">Durasi Kerja</th>
                            <th class="text-center">Telat 1</th>
                            <th class="text-center">Telat 2</th>
                            <th class="text-center">Mangkir</th>
                            <th class="text-center">Cuti/Libur</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: FATIGUE RISK -->
    <div class="tab-pane fade" id="fatigue" role="tabpanel">
        <div class="glass-card p-4">
            <div class="alert alert-warning py-2 small mb-3">
                <i class="fas fa-exclamation-circle me-1"></i>
                <strong>Analisis Risiko Kelelahan (Fatigue Risk):</strong> Staf yang dijadwalkan shift malam berturut-turut memiliki risiko tinggi mengalami burnout dan menurunkan keselamatan pasien (medical error risk). Tingkat risiko dihitung berdasarkan shift malam berturut-turut dan riwayat keterlambatan.
            </div>
            <div class="table-responsive">
                <table id="tblFatigue" class="table table-glass table-hover w-100 align-middle">
                    <thead>
                        <tr>
                            <th>Nama Pegawai</th>
                            <th>Departemen</th>
                            <th class="text-center">Total Shift Malam</th>
                            <th class="text-center">Malam Berturut-turut (Max)</th>
                            <th class="text-center">Total Terlambat</th>
                            <th class="text-center">Tingkat Risiko</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 4: BIAYA & JAM LEMBUR -->
    <div class="tab-pane fade" id="lembur" role="tabpanel">
        <div class="glass-card p-4">
            <h5 class="fw-bold mb-4 text-primary">Biaya Lembur & Durasi Lembur Aktual (Disetujui)</h5>
            <div class="table-responsive">
                <table id="tblLembur" class="table table-glass table-hover w-100 align-middle">
                    <thead>
                        <tr>
                            <th>Nama Pegawai</th>
                            <th>Departemen</th>
                            <th class="text-center">Jumlah Pengajuan</th>
                            <th class="text-center">Total Jam Lembur</th>
                            <th class="text-center">Estimasi Biaya Lembur</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 5: ANALITIK KEDISIPLINAN -->
    <div class="tab-pane fade" id="analitik" role="tabpanel">
        <div class="row g-4 mb-4">
            <div class="col-md-2 col-6">
                <div class="stat-box bg-gradient-danger shadow-sm">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3 id="statMangkir">0</h3>
                    <p>Total Mangkir</p>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-box bg-gradient-warning shadow-sm">
                    <i class="fas fa-stopwatch"></i>
                    <h3 id="statTelat">0</h3>
                    <p>Total Terlambat</p>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-box bg-gradient-success shadow-sm">
                    <i class="fas fa-check-circle"></i>
                    <h3 id="statHadir">0</h3>
                    <p>Total Hadir</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-box bg-dark border border-secondary shadow-sm">
                    <i class="fas fa-money-bill-wave"></i>
                    <h3 id="statBiayaLembur">Rp 0</h3>
                    <p>Total Biaya Lembur</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-box bg-gradient-danger shadow-sm" style="background: linear-gradient(135deg, #721c24, #f8d7da); color: #721c24 !important;">
                    <i class="fas fa-bed"></i>
                    <h3 id="statFatigue">0</h3>
                    <p>Staf Risiko Fatigue</p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-8">
                <div class="glass-card p-4 h-100">
                    <h6 class="fw-bold text-center mb-3">Peta Pelanggaran Indisipliner per Departemen</h6>
                    <div class="chart-container">
                        <canvas id="chartPelanggaran"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card p-4 h-100">
                    <h6 class="fw-bold text-center mb-3">Distribusi Jenis Pelanggaran</h6>
                    <div class="chart-container">
                        <canvas id="chartPie"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let dtEvaluasi, dtPotongan, dtFatigue, dtLembur;
    let chartBar, chartPie;
    var _absensiResponseData = null;
    var currentAbsensiReportContext = "";
    var absensiChatHistoryData = [];
    const defaultAbsensiPromptText = "Anda adalah AI HR & Workforce Attendance Advisor yang ahli dalam analisis manajemen SDM rumah sakit, evaluasi kedisiplinan pegawai, dan efisiensi anggaran lembur. Analisis data rekap kehadiran, tingkat kemangkiran/keterlambatan, risiko kelelahan kerja (fatigue risk dari shift malam berturut-turut), dan biaya pengajuan lembur berikut. Berikan rekomendasi strategis bagi Direktur untuk meningkatkan kedisiplinan staf, mencegah burnout/error medis akibat fatigue, dan mengoptimalkan beban biaya lembur per departemen.";

    function resetAbsensiPrompt() {
        $('#aiAbsensiPrompt').val(defaultAbsensiPromptText);
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

    const formatRp = (angka) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(angka);
    };

    $(document).ready(function() {
        // Load Departemen
        $.get('api/api_absensi.php?act=get_dep', function(res) {
            try {
                let data = JSON.parse(res);
                data.forEach(d => $('#dep').append(`<option value="${d.dep_id}">${d.nama}</option>`));
            } catch(e) {}
        });

        // Initialize DataTables
        dtEvaluasi = $('#tblEvaluasi').DataTable({
            dom: "<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            buttons: [
                { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Export Excel', className: 'btn btn-success btn-sm' }
            ],
            columns: [
                { data: 'tanggal', className: 'text-nowrap' },
                { data: 'nama', render: (d, t, r) => `<strong>${d}</strong><br><small class="text-muted">${r.nik} - ${r.departemen}</small>` },
                { data: 'jadwal', className: 'text-center fw-bold text-primary text-uppercase' },
                { data: null, className: 'text-center font-monospace', render: r => (r.jadwal_in === '-') ? '-' : `${r.jadwal_in} - ${r.jadwal_out}` },
                { data: 'status_evaluasi', className: 'text-center', render: d => {
                    if(d === 'MANGKIR') return '<span class="badge badge-soft-danger border border-danger">MANGKIR</span>';
                    if(d === 'DINAS') return '<span class="badge badge-soft-warning border border-warning">DINAS</span>';
                    if(d === 'BELUM_WAKTUNYA') return '<span class="badge badge-soft-info border border-info">BELUM HABIS</span>';
                    return '<span class="badge badge-soft-success border border-success">HADIR</span>';
                }},
                { data: 'jam_masuk', className: 'text-center font-monospace text-success fw-bold' },
                { data: 'jam_pulang', className: 'text-center font-monospace text-warning fw-bold' },
                { data: 'keterangan', className: 'small text-muted fst-italic' }
            ]
        });

        dtPotongan = $('#tblPotongan').DataTable({
            dom: "<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            buttons: [
                { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Export Excel', className: 'btn btn-success btn-sm' }
            ],
            order: [[4, 'desc']],
            columns: [
                { data: 'nama', render: (d,t,r) => `<strong>${d}</strong><br><small class="text-muted">${r.nik} | ${r.dept}</small>` },
                { data: 'total_durasi', className: 'text-center font-monospace text-primary fw-bold' },
                { data: null, className: 'text-center', render: r => r.jml_telat1 > 0 ? `<div class="badge bg-warning text-dark">${r.jml_telat1}x</div>` : '-' },
                { data: null, className: 'text-center', render: r => r.jml_telat2 > 0 ? `<div class="badge bg-orange text-white" style="background:#fd7e14">${r.jml_telat2}x</div>` : '-' },
                { data: null, className: 'text-center', render: r => r.jml_mangkir > 0 ? `<div class="badge bg-danger">${r.jml_mangkir}x</div>` : '-' },
                { data: 'jml_cuti', className: 'text-center', render: d => d > 0 ? `<span class="badge bg-secondary">${d}x Cuti</span>` : '-' }
            ]
        });

        dtFatigue = $('#tblFatigue').DataTable({
            dom: "<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            buttons: [
                { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Export Excel', className: 'btn btn-success btn-sm' }
            ],
            columns: [
                { data: 'nama', render: (d, t, r) => `<strong>${d}</strong><br><small class="text-muted">${r.nik}</small>` },
                { data: 'departemen', className: 'text-muted' },
                { data: 'total_night', className: 'text-center font-monospace fw-bold' },
                { data: 'max_consec', className: 'text-center font-monospace text-danger fw-bold' },
                { data: 'late_count', className: 'text-center font-monospace text-warning fw-bold' },
                { data: 'risk', className: 'text-center', render: d => {
                    if (d === 'tinggi') return '<span class="badge bg-danger text-white px-2 py-1"><i class="fas fa-biohazard me-1"></i>TINGGI (Burnout)</span>';
                    if (d === 'sedang') return '<span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-exclamation-triangle me-1"></i>SEDANG</span>';
                    return '<span class="badge bg-success text-white px-2 py-1"><i class="fas fa-check-circle me-1"></i>RENDAH</span>';
                }}
            ]
        });

        dtLembur = $('#tblLembur').DataTable({
            dom: "<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            buttons: [
                { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Export Excel', className: 'btn btn-success btn-sm' }
            ],
            order: [[4, 'desc']],
            columns: [
                { data: 'nama', render: (d, t, r) => `<strong>${d}</strong><br><small class="text-muted">${r.nik}</small>` },
                { data: 'departemen', className: 'text-muted' },
                { data: 'total_pengajuan', className: 'text-center font-monospace' },
                { data: 'total_jam', className: 'text-center font-monospace text-primary fw-bold', render: d => `${d} Jam` },
                { data: 'total_fee', className: 'text-end font-monospace text-success fw-bold', render: d => formatRp(d) }
            ]
        });

        // Initialize Charts
        initCharts();

        // Initial Load
        loadAllData();
    });

    function initCharts() {
        chartBar = new Chart(document.getElementById('chartPelanggaran'), {
            type: 'bar',
            data: { labels: [], datasets: [] },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, stacked: true }, x: { stacked: true } } }
        });
        chartPie = new Chart(document.getElementById('chartPie'), {
            type: 'doughnut',
            data: { labels: ['Telat 1', 'Telat 2', 'Mangkir'], datasets: [] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }

    function loadAllData() {
        $('#globalLoadingOverlay').css('display', 'flex');
        
        // Panggil 4 endpoint secara paralel
        $.when(
            $.get(`api/api_absensi.php?act=analyze&tgl1=${$('#tgl1').val()}&tgl2=${$('#tgl2').val()}&dep=${$('#dep').val()}&filter=${$('#filterEvaluasi').val()}`),
            $.get(`api/api_absensi.php?act=rekap&tgl1=${$('#tgl1').val()}&tgl2=${$('#tgl2').val()}&dep=${$('#dep').val()}`),
            $.get(`api/api_absensi.php?act=fatigue&tgl1=${$('#tgl1').val()}&tgl2=${$('#tgl2').val()}&dep=${$('#dep').val()}`),
            $.get(`api/api_absensi.php?act=lembur&tgl1=${$('#tgl1').val()}&tgl2=${$('#tgl2').val()}&dep=${$('#dep').val()}`)
        ).done(function(resEval, resRekap, resFatigue, resLembur) {
            $('#globalLoadingOverlay').hide();
            
            try {
                let jsonEval = JSON.parse(resEval[0]);
                dtEvaluasi.clear().rows.add(jsonEval.data).draw();
                
                let jsonRekap = JSON.parse(resRekap[0]);
                dtPotongan.clear().rows.add(jsonRekap.data).draw();
                
                let jsonFatigue = JSON.parse(resFatigue[0]);
                dtFatigue.clear().rows.add(jsonFatigue.data).draw();

                let jsonLembur = JSON.parse(resLembur[0]);
                dtLembur.clear().rows.add(jsonLembur.data).draw();
                
                processAnalytics(jsonRekap.data, jsonFatigue.data, jsonLembur.data);

                // Cache dataset untuk LLM AI Context
                _absensiResponseData = {
                    periode: $('#tgl1').val() + ' s.d ' + $('#tgl2').val(),
                    departemen: $('#dep option:selected').text(),
                    kpi: {
                        total_mangkir: $('#statMangkir').text(),
                        total_terlambat: $('#statTelat').text(),
                        total_kehadiran: $('#statHadir').text(),
                        staf_fatigue_risiko: $('#statFatigue').text(),
                        total_biaya_lembur: $('#statBiayaLembur').text()
                    },
                    ringkasan_fatigue: jsonFatigue.data ? jsonFatigue.data : [],
                    ringkasan_lembur: jsonLembur.data ? jsonLembur.data : [],
                    sample_evaluasi: jsonEval.data ? jsonEval.data : [],
                    top_pelanggaran: jsonRekap.data ? jsonRekap.data : []
                };

            } catch(e) {
                Swal.fire('Error', 'Terjadi kesalahan saat mengolah data response.', 'error');
            }
        }).fail(function() {
            $('#globalLoadingOverlay').hide();
            Swal.fire('Error', 'Gagal memuat data dari server.', 'error');
        });
    }

    function loadEvaluasi() {
        $('#globalLoadingOverlay').css('display', 'flex');
        $.get(`api/api_absensi.php?act=analyze&tgl1=${$('#tgl1').val()}&tgl2=${$('#tgl2').val()}&dep=${$('#dep').val()}&filter=${$('#filterEvaluasi').val()}`, function(res) {
            $('#globalLoadingOverlay').hide();
            try {
                let json = JSON.parse(res);
                dtEvaluasi.clear().rows.add(json.data).draw();
            }catch(e) {}
        });
    }

    function processAnalytics(dataRekap, dataFatigue, dataLembur) {
        let tMangkir = 0, tTelat1 = 0, tTelat2 = 0, tHadir = 0;
        let depData = {};

        dataRekap.forEach(p => {
            tMangkir += (p.jml_mangkir || 0);
            tTelat1 += (p.jml_telat1 || 0);
            tTelat2 += (p.jml_telat2 || 0);
            tHadir += (p.jml_hadir || 0);
            
            if(!depData[p.dept]) depData[p.dept] = {m:0, t1:0, t2:0};
            depData[p.dept].m += (p.jml_mangkir || 0);
            depData[p.dept].t1 += (p.jml_telat1 || 0);
            depData[p.dept].t2 += (p.jml_telat2 || 0);
        });

        let totTelat = tTelat1 + tTelat2;
        $('#statMangkir').text(tMangkir);
        $('#statTelat').text(totTelat);
        $('#statHadir').text(tHadir);
        
        // Compute Total Biaya Lembur
        let totalBiayaLembur = 0;
        if (dataLembur && Array.isArray(dataLembur)) {
            dataLembur.forEach(l => {
                totalBiayaLembur += parseFloat(l.total_fee || 0);
            });
        }
        $('#statBiayaLembur').text(formatRp(totalBiayaLembur));

        // Compute Staf Fatigue Risk (Tinggi/Sedang)
        let totalFatigueRisk = 0;
        if (dataFatigue && Array.isArray(dataFatigue)) {
            totalFatigueRisk = dataFatigue.filter(f => f.risk === 'tinggi' || f.risk === 'sedang').length;
        }
        $('#statFatigue').text(totalFatigueRisk);

        let labels = Object.keys(depData);
        let dm = [], dt1 = [], dt2 = [];
        labels.forEach(l => {
            dm.push(depData[l].m);
            dt1.push(depData[l].t1);
            dt2.push(depData[l].t2);
        });

        chartBar.data.labels = labels;
        chartBar.data.datasets = [
            { label: 'Mangkir', data: dm, backgroundColor: '#dc3545' },
            { label: 'Telat 2', data: dt2, backgroundColor: '#fd7e14' },
            { label: 'Telat 1', data: dt1, backgroundColor: '#ffc107' }
        ];
        chartBar.update();

        chartPie.data.datasets = [{
            data: [tTelat1, tTelat2, tMangkir],
            backgroundColor: ['#ffc107', '#fd7e14', '#dc3545'],
            borderWidth: 0
        }];
        chartPie.update();
    }

    // --- AI ABSENSI ADVISOR JS PIPELINE ---
    $(document).on('click', '#btnAnalyzeAbsensi', function() {
        if (!_absensiResponseData) {
            alert('Data absensi belum selesai dimuat.');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menganalisis...');
        $('#aiAbsensiReportContainer').html('<div class="text-center py-4"><div class="spinner-border text-primary mb-2"></div><div class="small text-muted">AI sedang menganalisis kedisiplinan, fatigue risk, dan biaya lembur staf...</div></div>');

        var formData = new URLSearchParams();
        formData.append('action', 'batch_summary');
        formData.append('raw_data', JSON.stringify([_absensiResponseData]));
        formData.append('custom_prompt', $('#aiAbsensiPrompt').val().trim());
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
            const aiThinkingContainer = document.getElementById('aiAbsensiReportContainer');
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
                                $('#aiAbsensiReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + data.message + '</div>');
                            }
                            if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                fullText += data.choices[0].delta.content;
                                $('#aiAbsensiReportContainer').html(parseMarkdownToHtml(fullText));
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');

            if (!isError && fullText) {
                currentAbsensiReportContext = fullText;
                absensiChatHistoryData = [];
                $('#absensiChatHistory').html('<div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>');
            }
        }).catch(err => {
            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');
            $('#aiAbsensiReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: Gagal menghubungi server (' + err.message + ')</div>');
        });
    });

    $(document).on('submit', '#absensiChatForm', function(e) {
        e.preventDefault();
        const input = $('#absensiChatInput');
        const messageText = input.val().trim();
        if (!messageText || !currentAbsensiReportContext) return;

        if (absensiChatHistoryData.length === 0) {
            $('#absensiChatHistory').empty();
        }

        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        $('#absensiChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-primary border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-primary"><i class="fas fa-user me-1"></i>Anda</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light">' + parseMarkdownToHtml(messageText) + '</div>' +
            '</div>'
        );
        $('#absensiChatHistory').scrollTop($('#absensiChatHistory')[0].scrollHeight);

        input.val('');
        $('#absensiChatInput, #btnSendAbsensiChat').prop('disabled', true);

        var replyId = 'absensi_reply_' + Date.now();
        $('#absensiChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-info border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-info"><i class="fas fa-robot me-1"></i>AI Workforce Advisor</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light" id="' + replyId + '"><i class="fas fa-spinner fa-spin text-info me-1"></i> Mengetik...</div>' +
            '</div>'
        );
        $('#absensiChatHistory').scrollTop($('#absensiChatHistory')[0].scrollHeight);

        var chatData = new URLSearchParams();
        chatData.append('action', 'chat_discuss');
        chatData.append('message', messageText);
        chatData.append('report_context', currentAbsensiReportContext);
        chatData.append('raw_data', JSON.stringify([_absensiResponseData]));
        chatData.append('custom_prompt', $('#aiAbsensiPrompt').val().trim());
        chatData.append('history', JSON.stringify(absensiChatHistoryData));
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
            const aiThinkingContainer = document.getElementById('aiAbsensiReportContainer');
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
                                $('#absensiChatHistory').scrollTop($('#absensiChatHistory')[0].scrollHeight);
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            $('#absensiChatInput, #btnSendAbsensiChat').prop('disabled', false);

            if (!isError && fullReply) {
                absensiChatHistoryData.push({ role: 'user', content: messageText });
                absensiChatHistoryData.push({ role: 'assistant', content: fullReply });
            }
        }).catch(err => {
            $('#absensiChatInput, #btnSendAbsensiChat').prop('disabled', false);
            $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Error koneksi</span>');
        });
    });

    // ─── Superadmin: Migrasi Schema DB ───────────────────────────────────────
    function openMigrasiModal() {
        $('#migrasiStatusArea').html('<div class="text-center py-3"><div class="spinner-border text-warning spinner-border-sm"></div> Memeriksa schema...</div>');
        $('#migrasiLogArea').hide();
        $('#btnJalankanMigrasi').prop('disabled', true);
        var modal = new bootstrap.Modal(document.getElementById('modalMigrasi'));
        modal.show();

        $.getJSON('api/db_migrate_jadwal.php?act=status', function(res) {
            if (!res.success) {
                $('#migrasiStatusArea').html('<div class="alert alert-danger small">' + (res.message || 'Error') + '</div>');
                return;
            }
            var html = '<div class="table-responsive"><table class="table table-sm table-bordered small mb-0">';
            html += '<thead class="table-dark"><tr><th>Tabel</th><th class="text-center">Kolom Diperiksa</th><th class="text-center">Sudah OK</th><th class="text-center">Status</th></tr></thead><tbody>';
            var needsMigration = false;
            $.each(res.status, function(key, s) {
                var badge = s.needs_migration
                    ? '<span class="badge bg-danger">Perlu Migrasi</span>'
                    : '<span class="badge bg-success">✓ Up-to-date</span>';
                if (s.needs_migration) needsMigration = true;
                html += '<tr>';
                html += '<td class="font-monospace fw-bold">' + s.label + '</td>';
                html += '<td class="text-center">' + s.columns_total + '</td>';
                html += '<td class="text-center">' + s.columns_ok + '</td>';
                html += '<td class="text-center">' + badge + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            $('#migrasiStatusArea').html(html);
            if (needsMigration) {
                $('#btnJalankanMigrasi').prop('disabled', false);
            } else {
                $('#migrasiStatusArea').append('<div class="alert alert-success mt-2 py-2 small mb-0"><i class="fas fa-check-circle me-1"></i> Semua tabel sudah up-to-date. Tidak ada migrasi diperlukan.</div>');
            }
        }).fail(function(xhr) {
            $('#migrasiStatusArea').html('<div class="alert alert-danger small">Gagal menghubungi server: ' + (xhr.responseText || xhr.status) + '</div>');
        });
    }

    function jalankanMigrasi() {
        $('#btnJalankanMigrasi').prop('disabled', true).html('<div class="spinner-border spinner-border-sm me-1"></div> Memproses...');
        $.getJSON('api/db_migrate_jadwal.php?act=migrate', function(res) {
            $('#migrasiLogArea').show();
            var logHtml = '';
            if (res.logs && res.logs.length > 0) {
                logHtml += res.logs.map(l => '<div>' + l + '</div>').join('');
            }
            if (res.errors && res.errors.length > 0) {
                logHtml += res.errors.map(e => '<div class="text-danger fw-bold">' + e + '</div>').join('');
            }
            $('#migrasiLogContent').html(logHtml || '<div class="text-muted">Tidak ada output.</div>');
            $('#migrasiLogContent').scrollTop($('#migrasiLogContent')[0].scrollHeight);

            if (res.success) {
                $('#btnJalankanMigrasi').html('<i class="fas fa-check me-1"></i> Selesai').addClass('btn-success').removeClass('btn-warning');
                // Refresh status
                setTimeout(openMigrasiModal, 1500);
            } else {
                $('#btnJalankanMigrasi').prop('disabled', false).html('<i class="fas fa-play-circle me-1"></i> Coba Lagi');
                Swal.fire('Migrasi Gagal', 'Ada error pada ALTER TABLE. Lihat log di atas.', 'error');
            }
        }).fail(function(xhr) {
            $('#btnJalankanMigrasi').prop('disabled', false).html('<i class="fas fa-play-circle me-1"></i> Jalankan Migrasi');
            Swal.fire('Error', 'Gagal menghubungi server migrasi.', 'error');
        });
    }
</script>
<?php
$page_js = ob_get_clean();
include 'includes/footer.php';
?>
