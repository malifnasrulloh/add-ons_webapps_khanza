<?php
// File: helpers/ajax/view_riwayat.php
require_once dirname(__DIR__, 2) . '/config/koneksi.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$_debug_mode_js = defined('DEBUG_MODE') && DEBUG_MODE ? 'true' : 'false';

$no_rawat_aktif = $_POST['no_rawat'] ?? '';
$ai_mode = $_POST['ai_mode'] ?? '';

if(empty($no_rawat_aktif)) {
    exit('<div class="alert alert-danger">Parameter no_rawat tidak ditemukan.</div>');
}

// Cari no_rkm_medis dari no_rawat
$stmt = $koneksi_pdo->prepare("SELECT no_rkm_medis FROM reg_periksa WHERE no_rawat = ? LIMIT 1");
$stmt->execute([$no_rawat_aktif]);
$reg = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$reg) {
    exit('<div class="alert alert-danger">Data registrasi tidak ditemukan.</div>');
}

$no_rkm_medis = $reg['no_rkm_medis'];
?>

<div class="card shadow-sm border-0 mb-4 rounded-3">
    <div class="card-header bg-white border-bottom py-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0 text-primary fw-bold"><i class="fas fa-stream me-2"></i> Rekap Riwayat Lengkap Perawatan Pasien</h5>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-success d-none shadow-sm" id="btnAIResumeFromRiwayat" title="Analisis & Diskusi riwayat medis pasien dengan AI"><i class="fas fa-robot"></i> Analisis & Diskusi AI</button>
                <button class="btn btn-sm btn-outline-secondary" id="btnToggleFilterRiwayat"><i class="fas fa-chevron-up"></i> Filter</button>
                <button class="btn btn-sm btn-outline-secondary" id="btnRefreshRiwayat"><i class="fas fa-sync-alt"></i> Segarkan</button>
            </div>
        </div>
        <div id="filterRiwayatBody">
        <div class="row g-2 align-items-center">
            <div class="col-md-9">
                <!-- Tambahan Filter Waktu Kunjungan (Sama seperti CPPT/Lab/Rad) -->
                <div class="d-flex align-items-end gap-2 mb-2 p-2 bg-light rounded border">
                    <div>
                        <label class="small fw-bold text-muted mb-1">Filter Kunjungan</label>
                        <select class="form-select form-select-sm shadow-sm" id="riwayatFilterMode" style="width: auto;">
                            <option value="5_terakhir" selected>5 Kunjungan Terakhir</option>
                            <option value="semua">Semua Kunjungan</option>
                            <option value="tanggal">Rentang Tanggal</option>
                        </select>
                    </div>
                    <div class="riwayat-date-range d-none">
                        <label class="small fw-bold text-muted mb-1">Tgl Awal</label>
                        <input type="date" class="form-control form-control-sm shadow-sm" id="riwayatTglAwal" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="riwayat-date-range d-none">
                        <label class="small fw-bold text-muted mb-1">Tgl Akhir</label>
                        <input type="date" class="form-control form-control-sm shadow-sm" id="riwayatTglAkhir" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div>
                        <button class="btn btn-sm btn-primary shadow-sm" id="btnTerapkanFilterRiwayat"><i class="fas fa-search"></i> Terapkan</button>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2" id="filterRiwayatContainer">
                    <div class="form-check form-check-inline form-switch">
                        <input class="form-check-input filter-chk" type="checkbox" id="chkReg" value="Registrasi" checked>
                        <label class="form-check-label small" for="chkReg">Registrasi</label>
                    </div>
                    <div class="form-check form-check-inline form-switch">
                        <input class="form-check-input filter-chk" type="checkbox" id="chkCPPT" value="CPPT" checked>
                        <label class="form-check-label small" for="chkCPPT">CPPT</label>
                    </div>
                    <div class="form-check form-check-inline form-switch">
                        <input class="form-check-input filter-chk" type="checkbox" id="chkDiag" value="Diagnosa" checked>
                        <label class="form-check-label small" for="chkDiag">Diagnosa</label>
                    </div>
                    <div class="form-check form-check-inline form-switch">
                        <input class="form-check-input filter-chk" type="checkbox" id="chkResep" value="Resep" checked>
                        <label class="form-check-label small" for="chkResep">Resep</label>
                    </div>
                    <div class="form-check form-check-inline form-switch">
                        <input class="form-check-input filter-chk" type="checkbox" id="chkLab" value="Laboratorium" checked>
                        <label class="form-check-label small" for="chkLab">Laboratorium</label>
                    </div>
                    <div class="form-check form-check-inline form-switch">
                        <input class="form-check-input filter-chk" type="checkbox" id="chkRad" value="Radiologi" checked>
                        <label class="form-check-label small" for="chkRad">Radiologi</label>
                    </div>
                    <div class="form-check form-check-inline form-switch">
                        <input class="form-check-input filter-chk" type="checkbox" id="chkTindakan" value="Tindakan" checked>
                        <label class="form-check-label small" for="chkTindakan">Laporan Tindakan</label>
                    </div>
                    <div class="form-check form-check-inline form-switch">
                        <input class="form-check-input filter-chk" type="checkbox" id="chkObservasi" value="Observasi" checked>
                        <label class="form-check-label small" for="chkObservasi">Observasi & EWS</label>
                    </div>
                    <div class="form-check form-check-inline form-switch">
                        <input class="form-check-input filter-chk" type="checkbox" id="chkBerkas" value="Berkas Digital" checked>
                        <label class="form-check-label small" for="chkBerkas">Berkas Digital</label>
                    </div>
                </div>
            </div>
            <div class="col-md-5 d-flex justify-content-end gap-2">
                <select class="form-select form-select-sm" id="viewModeRiwayat" style="width: auto;">
                    <option value="kronologis">Mode: Waktu Kronologis</option>
                    <option value="group">Mode: Grouping Kategori</option>
                </select>
                <select class="form-select form-select-sm" id="sortRiwayat" style="width: auto;">
                    <option value="desc">↓ Terbaru</option>
                    <option value="asc">↑ Terlama</option>
                </select>
            </div>
        </div>
        </div> <!-- End filterRiwayatBody -->
    </div>
    <?php if ($ai_mode === 'mpp'): ?>
    <div class="card-body bg-light p-0">
        <!-- Script ApexCharts via CDN -->
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <div class="row g-0" style="height: calc(100vh - 60px);">
            <!-- Panel Kiri AI (MPP) -->
            <div class="col-md-5 border-end bg-white d-none" id="aiSummaryContainer" style="height: 100%; overflow-y: auto;">
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-primary fw-bold mb-0"><i class="fas fa-robot me-1"></i> Analisis & Diskusi AI</h6>
                        <div>
                            <button type="button" class="btn-close" id="btnTutupAISummary" aria-label="Close" style="border: 0; background: transparent; font-size: 1.2rem; cursor: pointer;">&times;</button>
                        </div>
                    </div>
                    <div id="aiSummaryBody">
                        <div id="aiProgressArea" class="mb-3">
                          <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-bold text-muted" id="aiProgressLabel">Membaca riwayat klinis pasien...</span>
                            <span class="small text-muted" id="aiPercent">0%</span>
                          </div>
                          <div class="progress" style="height: 6px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" id="aiProgressBar" style="width: 0%"></div>
                          </div>
                        </div>

                        <div class="border rounded bg-light p-3 shadow-inner" id="aiResumeStreamContent" style="min-height: 250px; font-family: 'Segoe UI', system-ui; font-size: 0.85rem; line-height: 1.6;">
                            <div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin fa-lg mb-2 text-primary"></i><p>Menyiapkan data riwayat...</p></div>
                        </div>

                        <div class="mt-3">
                            <div id="aiChatHistory" class="p-3 rounded bg-light border shadow-inner mb-2" style="max-height: 250px; overflow-y: auto; display: none; font-size: 0.85rem; font-family: 'Segoe UI', system-ui;">
                            </div>
                            <form id="aiChatForm" class="mt-2" style="display: none;">
                                <div class="input-group input-group-sm">
                                    <input type="text" id="aiChatInput" class="form-control" placeholder="Ketik pertanyaan diskusi..." required>
                                    <button class="btn btn-primary" type="submit" id="btnSendAIChat"><i class="fas fa-paper-plane"></i></button>
                                </div>
                            </form>
                        </div>

                        <div class="mt-3 d-flex justify-content-between align-items-center border-top pt-2">
                            <small class="text-muted fst-italic"><i class="fas fa-info-circle"></i> AI mungkin tidak akurat.</small>
                            <button type="button" class="btn btn-sm btn-success shadow-sm" id="btnApplyAIResume" disabled>
                                <i class="fas fa-magic me-1"></i> Terapkan ke Form MPP
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Panel Kanan Timeline (MPP) -->
            <div class="col-md-12 bg-light position-relative p-0" id="containerTimelineRiwayat" style="height: 100%; overflow-y: auto;">
                <div id="loadingProgressContainer" class="p-3 bg-white border-bottom sticky-top" style="z-index: 10;">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Memuat Riwayat: <b id="textProgress">0/150+ Tabel</b></span>
                        <span id="pctProgress">0%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div id="barProgress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;"></div>
                    </div>
                </div>
                <div id="timelineContent" class="p-4"></div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card-body bg-light position-relative p-0" style="height: 100%; overflow-y: auto;" id="containerTimelineRiwayat">
        
        <!-- AI Summary Area (Sticky/At top) - original inline mode for edokter -->
        <div id="aiSummaryContainer" class="p-3 bg-white border-bottom d-none" style="position: sticky; top: 0; z-index: 11; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="text-primary fw-bold mb-0"><i class="fas fa-robot me-1"></i> Analisis & Diskusi AI</h6>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="btnToggleAICollapse"><i class="fas fa-chevron-up"></i> Lipat</button>
                    <button type="button" class="btn-close" id="btnTutupAISummary" aria-label="Close" style="border: 0; background: transparent; font-size: 1.2rem; cursor: pointer;">&times;</button>
                </div>
            </div>
            <div id="aiSummaryBody">
            <div id="aiProgressArea" class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="small fw-bold text-muted" id="aiProgressLabel">Membaca riwayat klinis pasien...</span>
                <span class="small text-muted" id="aiPercent">0%</span>
              </div>
              <div class="progress" style="height: 6px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" id="aiProgressBar" style="width: 0%"></div>
              </div>
            </div>

            <div class="border rounded bg-light p-3 shadow-inner" id="aiResumeStreamContent" style="min-height: 120px; max-height: 250px; overflow-y: auto; font-family: 'Segoe UI', system-ui; font-size: 0.85rem; line-height: 1.6;">
                <div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin fa-lg mb-2 text-primary"></i><p>Menyiapkan data riwayat...</p></div>
            </div>

            <!-- Fitur Diskusi AI -->
            <div class="mt-3">
                <div id="aiChatHistory" class="p-3 rounded bg-light border shadow-inner" style="max-height: 250px; overflow-y: auto; display: none; font-size: 0.85rem; font-family: 'Segoe UI', system-ui;">
                </div>
                <form id="aiChatForm" class="mt-2" style="display: none;">
                    <div class="input-group input-group-sm">
                        <input type="text" id="aiChatInput" class="form-control" placeholder="Ketik pertanyaan untuk berdiskusi dengan AI terkait rekam medis pasien ini..." required>
                        <button class="btn btn-primary" type="submit" id="btnSendAIChat"><i class="fas fa-paper-plane"></i> Kirim</button>
                    </div>
                </form>
            </div>

            <div class="mt-3 d-flex justify-content-between align-items-center border-top pt-2">
                <small class="text-muted fst-italic"><i class="fas fa-info-circle"></i> AI mungkin tidak akurat. Selalu periksa kembali data klinis.</small>
                <button type="button" class="btn btn-sm btn-success shadow-sm" id="btnApplyAIResume" disabled>
                    <i class="fas fa-magic me-1"></i> Terapkan ke Form Resume
                </button>
            </div>
            </div> <!-- End aiSummaryBody -->
        </div>

        <!-- Script ApexCharts via CDN -->
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        
        <!-- Progress Bar untuk Lazy Loading Murni -->
        <div id="loadingProgressContainer" class="p-3 bg-white border-bottom sticky-top" style="z-index: 10;">
            <div class="d-flex justify-content-between small text-muted mb-1">
                <span>Memuat Riwayat: <b id="textProgress">0/150+ Tabel</b></span>
                <span id="pctProgress">0%</span>
            </div>
            <div class="progress" style="height: 6px;">
                <div id="barProgress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;"></div>
            </div>
        </div>

        <div id="timelineContent" class="p-4">
            <!-- Timeline items will be injected here via AJAX -->
        </div>

    </div>
    <?php endif; ?>
</div>

<style>
/* Custom Timeline CSS */
.timeline {
    position: relative;
    padding-left: 2rem;
    margin-bottom: 2rem;
}
.timeline::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 15px;
    width: 2px;
    background: #e9ecef;
}
.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
}
.timeline-item::before {
    content: '';
    position: absolute;
    left: -2.3rem;
    top: 0.25rem;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #fff;
    border: 3px solid #0d6efd;
    z-index: 1;
}
.timeline-item.t-cppt::before { border-color: #198754; }
.timeline-item.t-resep::before { border-color: #fd7e14; }
.timeline-item.t-lab::before { border-color: #0dcaf0; }
.timeline-item.t-rad::before { border-color: #6610f2; }
.timeline-item.t-registrasi::before { border-color: #6c757d; }
.timeline-item.t-diagnosa::before { border-color: #dc3545; }

.timeline-date {
    font-size: 0.85rem;
    font-weight: bold;
    color: #6c757d;
    margin-bottom: 0.5rem;
}
.timeline-card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    padding: 1rem;
}
/* DARK MODE OVERRIDES */
body.dark-mode .timeline-card { background-color: #1e293b; border-color: rgba(255,255,255,0.1); color: #f8fafc; }
body.dark-mode .timeline-card.border-primary { border-color: #3b82f6 !important; }

.timeline-card .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #eee;
    padding-bottom: 0.5rem;
    margin-bottom: 0.5rem;
}
body.dark-mode .timeline-card .header { border-bottom-color: rgba(255,255,255,0.1); }
.timeline-card .badge-type {
    font-size: 0.75rem;
}

/* CSS Grid for Data Display */
.data-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.75rem;
    margin-top: 0.5rem;
}
.data-box {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 0.6rem;
}
body.dark-mode .data-box { background-color: transparent !important; border-color: rgba(255,255,255,0.1); }

.data-box.full-width {
    grid-column: 1 / -1;
}
.data-key {
    font-size: 0.75rem;
    color: #6c757d;
    margin-bottom: 0.2rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
body.dark-mode .data-key { color: #94a3b8; }
.data-val {
    font-size: 0.85rem;
    color: #212529;
    font-weight: 500;
    white-space: pre-wrap;
    word-break: break-word;
}
body.dark-mode .data-val { color: #f1f5f9; }

/* ApexCharts Dark Mode Adjustments */
body.dark-mode .apexcharts-canvas .apexcharts-xaxis-label,
body.dark-mode .apexcharts-canvas .apexcharts-yaxis-label,
body.dark-mode .apexcharts-canvas .apexcharts-legend-text { fill: #94a3b8 !important; color: #94a3b8 !important; }
body.dark-mode .apexcharts-canvas .apexcharts-title-text { fill: #f8fafc !important; }
body.dark-mode .apexcharts-canvas .apexcharts-gridline { stroke: rgba(255,255,255,0.05) !important; }
body.dark-mode .apexcharts-canvas .apexcharts-tooltip { background: #1e293b !important; border-color: rgba(255,255,255,0.1) !important; color: #f8fafc !important; }
body.dark-mode .apexcharts-canvas .apexcharts-tooltip-title { background: #0f172a !important; border-bottom-color: rgba(255,255,255,0.1) !important; }
body.dark-mode .apexcharts-canvas .apexcharts-datalabel, 
body.dark-mode .apexcharts-canvas .apexcharts-datalabel-label,
body.dark-mode .apexcharts-canvas .apexcharts-datalabel-value { fill: #f8fafc !important; }

/* Badge Opacity for Dark Mode */
body.dark-mode .bg-primary-subtle { background-color: rgba(59, 130, 246, 0.2) !important; color: #60a5fa !important; border-color: rgba(59, 130, 246, 0.3) !important; }

/* Consistency Fix for .bg-light inside timeline-card */
body.dark-mode .timeline-card .bg-light { background-color: transparent !important; border-color: rgba(255,255,255,0.1) !important; }
body.dark-mode .timeline-card .table-light,
body.dark-mode .timeline-card .table-light th,
body.dark-mode .timeline-card .table-light td {
    background-color: #334155 !important;
    color: #f8fafc !important;
}
body.dark-mode .timeline-card .table-warning,
body.dark-mode .timeline-card .table-warning > td,
body.dark-mode .timeline-card .table-warning > th {
    background-color: #451a03 !important;
    color: #fef08a !important;
}
body.dark-mode .timeline-card .table-warning.text-danger,
body.dark-mode .timeline-card .table-warning.text-danger > td,
body.dark-mode .timeline-card .table-warning.text-danger > th,
body.dark-mode .timeline-card .table-warning .text-danger {
    color: #fca5a5 !important;
}
body.dark-mode .timeline-card .table-warning .text-muted {
    color: #fde047 !important;
}
.ai-field-highlight {
    animation: highlightPulse 2s ease-out;
}
@keyframes highlightPulse {
    0% { background-color: #d1e7dd; border-color: #0f5132; box-shadow: 0 0 10px rgba(25, 135, 84, 0.5); }
    100% { background-color: inherit; border-color: inherit; box-shadow: none; }
}

/* GLASSMORPHISM DARK THEME OVERRIDES FOR EXECUTIVE DASHBOARD */
html.theme-glass-solid .timeline-card, html.theme-glass-animated .timeline-card { background-color: #1e293b !important; border-color: rgba(255,255,255,0.1) !important; color: #f8fafc !important; }
html.theme-glass-solid .timeline-card.border-primary, html.theme-glass-animated .timeline-card.border-primary { border-color: #3b82f6 !important; }
html.theme-glass-solid .timeline-card .header, html.theme-glass-animated .timeline-card .header { border-bottom-color: rgba(255,255,255,0.1) !important; }
html.theme-glass-solid .data-box, html.theme-glass-animated .data-box { background-color: transparent !important; border-color: rgba(255,255,255,0.1) !important; }
html.theme-glass-solid .data-key, html.theme-glass-animated .data-key { color: #94a3b8 !important; }
html.theme-glass-solid .data-val, html.theme-glass-animated .data-val { color: #f1f5f9 !important; }
html.theme-glass-solid .apexcharts-canvas .apexcharts-xaxis-label, html.theme-glass-animated .apexcharts-canvas .apexcharts-xaxis-label,
html.theme-glass-solid .apexcharts-canvas .apexcharts-yaxis-label, html.theme-glass-animated .apexcharts-canvas .apexcharts-yaxis-label,
html.theme-glass-solid .apexcharts-canvas .apexcharts-legend-text, html.theme-glass-animated .apexcharts-canvas .apexcharts-legend-text { fill: #94a3b8 !important; color: #94a3b8 !important; }
html.theme-glass-solid .apexcharts-canvas .apexcharts-title-text, html.theme-glass-animated .apexcharts-canvas .apexcharts-title-text { fill: #f8fafc !important; }
html.theme-glass-solid .apexcharts-canvas .apexcharts-gridline, html.theme-glass-animated .apexcharts-canvas .apexcharts-gridline { stroke: rgba(255,255,255,0.05) !important; }
html.theme-glass-solid .apexcharts-canvas .apexcharts-tooltip, html.theme-glass-animated .apexcharts-canvas .apexcharts-tooltip { background: #1e293b !important; border-color: rgba(255,255,255,0.1) !important; color: #f8fafc !important; }
html.theme-glass-solid .apexcharts-canvas .apexcharts-tooltip-title, html.theme-glass-animated .apexcharts-canvas .apexcharts-tooltip-title { background: #0f172a !important; border-bottom-color: rgba(255,255,255,0.1) !important; }
html.theme-glass-solid .apexcharts-canvas .apexcharts-datalabel, html.theme-glass-animated .apexcharts-canvas .apexcharts-datalabel, 
html.theme-glass-solid .apexcharts-canvas .apexcharts-datalabel-label, html.theme-glass-animated .apexcharts-canvas .apexcharts-datalabel-label,
html.theme-glass-solid .apexcharts-canvas .apexcharts-datalabel-value, html.theme-glass-animated .apexcharts-canvas .apexcharts-datalabel-value { fill: #f8fafc !important; }
html.theme-glass-solid .bg-primary-subtle, html.theme-glass-animated .bg-primary-subtle { background-color: rgba(59, 130, 246, 0.2) !important; color: #60a5fa !important; border-color: rgba(59, 130, 246, 0.3) !important; }
html.theme-glass-solid .timeline-card .bg-light, html.theme-glass-animated .timeline-card .bg-light { background-color: transparent !important; border-color: rgba(255,255,255,0.1) !important; }
html.theme-glass-solid .timeline-card .table-light, html.theme-glass-animated .timeline-card .table-light,
html.theme-glass-solid .timeline-card .table-light th, html.theme-glass-animated .timeline-card .table-light th,
html.theme-glass-solid .timeline-card .table-light td, html.theme-glass-animated .timeline-card .table-light td { background-color: #334155 !important; color: #f8fafc !important; }
html.theme-glass-solid .timeline-card .table-warning, html.theme-glass-animated .timeline-card .table-warning,
html.theme-glass-solid .timeline-card .table-warning > td, html.theme-glass-animated .timeline-card .table-warning > td,
html.theme-glass-solid .timeline-card .table-warning > th, html.theme-glass-animated .timeline-card .table-warning > th { background-color: #451a03 !important; color: #fef08a !important; }
html.theme-glass-solid .timeline-card .table-warning.text-danger, html.theme-glass-animated .timeline-card .table-warning.text-danger,
html.theme-glass-solid .timeline-card .table-warning.text-danger > td, html.theme-glass-animated .timeline-card .table-warning.text-danger > td,
html.theme-glass-solid .timeline-card .table-warning.text-danger > th, html.theme-glass-animated .timeline-card .table-warning.text-danger > th,
html.theme-glass-solid .timeline-card .table-warning .text-danger, html.theme-glass-animated .timeline-card .table-warning .text-danger { color: #fca5a5 !important; }
html.theme-glass-solid .timeline-card .table-warning .text-muted, html.theme-glass-animated .timeline-card .table-warning .text-muted { color: #fde047 !important; }

/* Custom Chatbox & container overrides for glass theme to avoid white text on white bg */
html.theme-glass-solid #aiSummaryContainer, html.theme-glass-animated #aiSummaryContainer {
    background-color: rgba(30, 41, 59, 0.95) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #f1f5f9 !important;
}
html.theme-glass-solid #loadingProgressContainer, html.theme-glass-animated #loadingProgressContainer {
    background-color: rgba(30, 41, 59, 0.95) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}
html.theme-glass-solid .bg-white, html.theme-glass-animated .bg-white {
    background-color: rgba(30, 41, 59, 0.95) !important;
    color: #f1f5f9 !important;
}
html.theme-glass-solid .bg-light, html.theme-glass-animated .bg-light {
    background-color: rgba(30, 41, 59, 0.5) !important;
    color: #cbd5e1 !important;
}
html.theme-glass-solid .card, html.theme-glass-animated .card {
    background-color: rgba(30, 41, 59, 0.8) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}
html.theme-glass-solid .text-dark, html.theme-glass-animated .text-dark {
    color: #f1f5f9 !important;
}
html.theme-glass-solid .list-group-item, html.theme-glass-animated .list-group-item {
    background-color: rgba(30, 41, 59, 0.8) !important;
    color: #f1f5f9 !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}
html.theme-glass-solid .timeline-card, html.theme-glass-animated .timeline-card {
    background-color: rgba(30, 41, 59, 0.8) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}
</style>

<script>
// Array global penampung instance chart
var riwayatChartInstances = [];

// Helper warna
var colors = {
    suhu: '#008FFB', 
    rr: '#00E396', gcs: '#FEB019',
    nadi: '#FF4560', spo2: '#775DD0',
    systole: '#FF4560', diastole: '#008FFB'
};

// Debug Mode (dari PHP config)
var DEBUG_MODE = <?= $_debug_mode_js ?>;
var _debugLogs = []; // Kumpulan log dari semua endpoint

$(document).ready(function() {
    var rawData = [];
    var totalEndpoints = 9;
    var completedEndpoints = 0;
    
    // Array URL endpoint backend
    var endpoints = [
        'api/riwayat/ajax_riwayat_registrasi.php', // Group 1
        'api/riwayat/ajax_riwayat_penilaian.php',  // Group 2
        'api/riwayat/ajax_riwayat_observasi.php',  // Group 3
        'api/riwayat/ajax_riwayat_penunjang.php',  // Group 4
        'api/riwayat/ajax_riwayat_asuhan.php',     // Group 5
        'api/riwayat/ajax_riwayat_tindakan.php',   // Group 6
        'api/riwayat/ajax_riwayat_farmasi.php',    // Group 7
        'api/riwayat/ajax_riwayat_resume.php',     // Group 8
        'api/riwayat/ajax_riwayat_berkas_digital.php' // Group 9 (Berkas Digital Perawatan)
    ];
    
    function loadRiwayat() {
        // Hapus instance chart lama untuk mencegah memory leak
        if (riwayatChartInstances && riwayatChartInstances.length > 0) {
            riwayatChartInstances.forEach(chart => chart.destroy());
            riwayatChartInstances = [];
        }

        // Reset state
        rawData = [];
        completedEndpoints = 0;
        $('#timelineContent').empty();
        $('#btnAIResumeFromRiwayat').addClass('d-none');
        $('#loadingProgressContainer').slideDown();
        updateProgressbar();

        var filterMode = $('#riwayatFilterMode').val() || '5_terakhir';
        var tglAwal = $('#riwayatTglAwal').val() || '';
        var tglAkhir = $('#riwayatTglAkhir').val() || '';

        var params = { 
            no_rkm_medis: '<?= htmlspecialchars($no_rkm_medis) ?>',
            filter_mode: filterMode,
            tgl_awal: tglAwal,
            tgl_akhir: tglAkhir
        };

        // Tembak AJAX secara paralel (async)
        if (DEBUG_MODE) { _debugLogs = []; renderDebugConsole(); }

        endpoints.forEach(function(ep) {
            var startTime = Date.now();
            $.ajax({
                url: ep,
                type: 'GET',
                data: params,
                dataType: 'json',
                success: function(res) {
                    // Kumpulkan debug logs jika DEBUG_MODE aktif
                    if (DEBUG_MODE && res.debug_logs && res.debug_logs.length > 0) {
                        res.debug_logs.forEach(function(log) {
                            _debugLogs.push({ ep: ep, log: log });
                        });
                        renderDebugConsole();
                    }
                    if(res.status === 'success' && res.data && res.data.length > 0) {
                        rawData = rawData.concat(res.data);
                        // Refresh UI secara real-time setiap kali sekumpulan data masuk (Lazy Rendering)
                        applyFiltersAndRender();
                    } else if (DEBUG_MODE && res.status === 'error') {
                        _debugLogs.push({ ep: ep, log: { time: new Date().toLocaleTimeString('id-ID'), tag: ep, level: 'error', message: 'Response error: ' + (res.message || 'unknown') } });
                        renderDebugConsole();
                    }
                },
                error: function(xhr, status, err) {
                    console.error('Error fetching ' + ep + ':', err);
                    if (DEBUG_MODE) {
                        _debugLogs.push({ ep: ep, log: { time: new Date().toLocaleTimeString('id-ID'), tag: ep, level: 'error', message: 'HTTP Error ' + xhr.status + ' - ' + err + ' (response: ' + xhr.responseText.substring(0,200) + ')' } });
                        renderDebugConsole();
                    }
                },
                complete: function() {
                    completedEndpoints++;
                    var elapsed = Date.now() - startTime;
                    if (DEBUG_MODE) {
                        _debugLogs.push({ ep: ep, log: { time: new Date().toLocaleTimeString('id-ID'), tag: '⏱ ' + ep, level: 'info', message: 'Selesai dalam ' + elapsed + 'ms' } });
                        renderDebugConsole();
                    }
                    updateProgressbar();
                    if(completedEndpoints >= totalEndpoints) {
                        // All finished
                        setTimeout(function() { 
                            $('#loadingProgressContainer').slideUp(); 
                            if(rawData.length === 0) {
                                $('#timelineContent').html('<div class="text-center text-muted py-4"><i class="fas fa-folder-open fa-3x mb-3 text-light"></i><br>Belum ada riwayat medis untuk pasien ini.</div>').show();
                            } else {
                                $('#btnAIResumeFromRiwayat').removeClass('d-none');
                                var ai_mode = '<?= $ai_mode ?>';
                                if(ai_mode === 'mpp' && !$('#aiSummaryContainer').is(':visible')) {
                                    $('#btnAIResumeFromRiwayat').click();
                                }
                            }
                        }, 1000);
                    }
                }
            });
        });
    }

    function updateProgressbar() {
        var pct = (completedEndpoints / totalEndpoints) * 100;
        $('#barProgress').css('width', pct + '%');
        $('#pctProgress').text(Math.round(pct) + '%');
        $('#textProgress').text(completedEndpoints + '/' + totalEndpoints + ' Modul (150+ Tabel)');
    }

    function applyFiltersAndRender() {
        if (!rawData || rawData.length === 0) {
            renderTimeline([]);
            return;
        }

        // Ambil filter checkbox yang aktif
        var activeCats = [];
        $('.filter-chk:checked').each(function() {
            activeCats.push($(this).val());
        });

        // Lakukan mapping dari kategori UI ke item.jenis backend
        var filterMapping = {
            'Registrasi': ['Registrasi', 'Data Triase IGD', 'Triase Primer', 'Triase Sekunder'],
            'CPPT': ['CPPT', 'Penilaian Awal Keperawatan', 'Penilaian Awal Medis', 'Penilaian Medis Khusus', 'Penilaian Lanjutan'],
            'Diagnosa': ['Diagnosa', 'Prosedur Medis'],
            'Resep': ['Resep', 'Rekonsiliasi Obat', 'Edukasi & Farmasi'],
            'Laboratorium': ['Laboratorium'],
            'Radiologi': ['Radiologi', 'Penunjang Medis'],
            'Tindakan': ['Laporan Tindakan', 'Asuhan Keperawatan & Gizi', 'Discharge Planning', 'Resume Medis', 'Checklist Keselamatan Bedah'],
            'Observasi': ['Observasi & EWS', 'Skrining'],
            'Berkas Digital': ['Berkas Digital']
        };

        var typesToShow = [];
        activeCats.forEach(function(cat) {
            if(filterMapping[cat]) {
                typesToShow = typesToShow.concat(filterMapping[cat]);
            }
        });

        var sortOrder = $('#sortRiwayat').val();

        // Lakukan filtering data
        var filteredData = rawData.filter(function(item) {
            return typesToShow.includes(item.jenis);
        });

        // Lakukan sorting
        filteredData.sort(function(a, b) {
            var dateA = new Date(a.tanggal + 'T' + a.jam);
            var dateB = new Date(b.tanggal + 'T' + b.jam);
            if (sortOrder === 'desc') {
                return dateB - dateA; // Terbaru ke terlama
            } else {
                return dateA - dateB; // Terlama ke terbaru
            }
        });

        // Hapus instance chart lama
        if (riwayatChartInstances && riwayatChartInstances.length > 0) {
            riwayatChartInstances.forEach(chart => chart.destroy());
            riwayatChartInstances = [];
        }

        renderTimeline(filteredData);
    }

    // Ekstraksi nilai angka dari string (contoh: "120/80 mmHg" -> "120/80")
    function extractNumber(str) {
        if (!str) return null;
        var match = str.match(/[\d\.]+/);
        return match ? parseFloat(match[0]) : null;
    }
    
    // Ekstraksi tensi (contoh: "120/80" -> { sys: 120, dia: 80 })
    function extractTensi(str) {
        if (!str) return null;
        var parts = str.split('/');
        if (parts.length === 2) {
            var sys = parseFloat(parts[0]);
            var dia = parseFloat(parts[1]);
            if (!isNaN(sys) && !isNaN(dia)) return { sys: sys, dia: dia };
        }
        return null;
    }

    function createTimelineHtml(dataItems) {
        var html = '';
        var ttvDataByVisit = {};

        dataItems.forEach(function(item) {
            // MENGUMPULKAN DATA TTV UNTUK CHART
            if (item.jenis.includes('Observasi') || item.jenis.includes('EWS') || item.jenis.includes('Pemantauan')) {
                var no_rawat = item.no_rawat;
                var dtStr = item.tanggal + 'T' + item.jam;
                var ts = new Date(dtStr).getTime();
                
                if (!ttvDataByVisit[no_rawat]) {
                     ttvDataByVisit[no_rawat] = [];
                }

                var tsv = {
                    x: ts,
                    suhu: extractNumber(item.data.suhu_tubuh || item.data.suhu) || null,
                    rr: extractNumber(item.data.respirasi || item.data.rr || item.data.pernapasan) || null,
                    gcs: extractNumber(item.data.gcs || (item.data.evaluasi ? item.data.evaluasi.gcs : null)) || null,
                    nadi: extractNumber(item.data.nadi || item.data.hr) || null,
                    spo2: extractNumber(item.data.spo2 || item.data.saturasi_o2) || null
                };
                
                var tensiVal = extractTensi(item.data.tensi || item.data.td || item.data.tekanan_darah);
                if (tensiVal) {
                    tsv.sys = tensiVal.sys;
                    tsv.dia = tensiVal.dia;
                } else {
                    tsv.sys = null; tsv.dia = null;
                }
                
                if (tsv.suhu !== null || tsv.rr !== null || tsv.nadi !== null || tsv.sys !== null || tsv.spo2 !== null) {
                    ttvDataByVisit[no_rawat].push(tsv);
                }
            }
        });

        // Loop untuk merender timeline per kunjungan secara berurutan
        // Mengelompokkan berdasarkan kunjungan jika view Mode "group" tidak aktif
        var visits = {};
        dataItems.forEach(function(item) {
            if (!visits[item.no_rawat]) visits[item.no_rawat] = [];
            visits[item.no_rawat].push(item);
        });

        // Array unuk menampung id container yang butuh dirender chartnya setelah html di append
        var chartsToRender = [];

        html += '<div class="timeline">';
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };

        // Jika mode default (waktu kronologis), kita tetap render berurutan, TAPI chart hanya muncul 1x per kunjungan.
        // Kita gunakan Set untuk mencatat kapan chart kunjungan X sudah dirender.
        var renderedCharts = new Set();

        dataItems.forEach(function(item) {
            
            // CEK APAKAH PERLU RENDER CHART UNTUK KUNJUNGAN INI
            if (!renderedCharts.has(item.no_rawat) && ttvDataByVisit[item.no_rawat] && ttvDataByVisit[item.no_rawat].length > 1) {
                renderedCharts.add(item.no_rawat);
                var rawatIdSafe = item.no_rawat.replace(/[^a-zA-Z0-9]/g, '_');
                var chartContainerId = 'ttv-charts-riwayat-' + rawatIdSafe;
                
                html += `
                <div class="timeline-item t-cppt">
                    <div class="timeline-date">Grafik TTV Kunjungan: ${item.no_rawat}</div>
                    <div class="timeline-card border-primary border-1 shadow-sm mb-4">
                        <div class="header">
                            <div>
                                <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-chart-line me-2"></i> Grafik Observasi & Tanda-Tanda Vital</h6>
                            </div>
                        </div>
                        <div class="body pt-2 position-relative">
                            <div id="${chartContainerId}" style="min-height: 480px;"></div>
                        </div>
                    </div>
                </div>`;
                
                // Urutkan data x secara ascending untuk ApexCharts
                var sortedData = ttvDataByVisit[item.no_rawat].sort((a,b) => a.x - b.x);
                chartsToRender.push({
                    id: chartContainerId,
                    data: sortedData,
                    rawat: item.no_rawat
                });
            }

            var dateObj = new Date(item.tanggal);
            var dateFormatted = dateObj.toLocaleDateString('id-ID', options);
            var headerColor = 'primary';
            var icon = 'fa-info-circle';
            var tClass = '';

            // Tentukan style base on jenis
            if(item.jenis.includes('Registrasi') || item.jenis.includes('Triase')) { headerColor = 'secondary'; icon = 'fa-hospital-user'; tClass = 't-registrasi'; }
            else if(item.jenis.includes('CPPT'))       { headerColor = 'success'; icon = 'fa-file-medical-alt'; tClass = 't-cppt'; }
            else if(item.jenis.includes('Lab'))        { headerColor = 'info'; icon = 'fa-flask'; tClass = 't-lab'; }
            else if(item.jenis.includes('Rad') || item.jenis.includes('Penunjang')) { headerColor = 'purple'; icon = 'fa-x-ray'; tClass = 't-rad'; }
            else if(item.jenis.includes('Resep') || item.jenis.includes('Farmasi') || item.jenis.includes('Obat')) { headerColor = 'warning text-dark'; icon = 'fa-pills'; tClass = 't-resep'; }
            else if(item.jenis.includes('Diagnosa') || item.jenis.includes('Prosedur')) { headerColor = 'danger'; icon = 'fa-stethoscope'; tClass = 't-diagnosa'; }
            else if(item.jenis.includes('Tindakan') || item.jenis.includes('Operasi') || item.jenis.includes('Asuhan')) { headerColor = 'primary'; icon = 'fa-procedures'; tClass = 't-cppt';}
            else if(item.jenis.includes('Observasi') || item.jenis.includes('EWS') || item.jenis.includes('Skrining')) { headerColor = 'dark'; icon = 'fa-eye'; tClass = 't-cppt';}
            else if(item.jenis.includes('Penilaian'))  { headerColor = 'secondary'; icon = 'fa-clipboard-check'; tClass = 't-cppt';}
            else if(item.jenis.includes('Resume') || item.jenis.includes('Planning')) { headerColor = 'dark text-white'; icon = 'fa-file-signature'; tClass = 't-registrasi';}
            else if(item.jenis.includes('Berkas')) { headerColor = 'info text-dark'; icon = 'fa-file-image'; tClass = 't-registrasi'; }
            else { headerColor = 'secondary'; icon = 'fa-file-alt'; tClass = 't-cppt'; }

            var isActiveColor = (item.no_rawat === '<?=$no_rawat_aktif?>') ? 'border-primary border-2' : '';
            var activeLabel = (item.no_rawat === '<?=$no_rawat_aktif?>') ? '<span class="badge bg-primary ms-2"><i class="fas fa-star text-warning"></i> Kunjungan Saat Ini</span>' : '';

            html += `<div class="timeline-item ${tClass}">
                        <div class="timeline-date">${dateFormatted} • ${item.jam}</div>
                        <div class="timeline-card ${isActiveColor}">
                            <div class="header">
                                <div>
                                    <span class="badge bg-${headerColor} badge-type"><i class="fas ${icon} me-1"></i> ${item.jenis}</span>
                                    <span class="text-muted small ms-2"><i class="fas fa-hashtag"></i> ${item.no_rawat}</span>
                                    ${activeLabel}
                                </div>
                            </div>
                            <div class="body pt-2 small">`;

            // RENDER KONTEN BERDASARKAN JENIS
            var d = item.data;
            if (item.jenis === 'Registrasi') {
                html += `<b>Kunjungan:</b> ${d.poli} (${d.status_lanjut})<br>
                         <b>Dokter:</b> ${d.dokter}<br>
                         <b>Status:</b> ${d.status}`;
            } 
            else if (item.jenis === 'CPPT') {
                html += `<div class="bg-light p-2 rounded mb-2 border">
                            <b>S:</b> ${d.keluhan || '-'}<br>
                            <b>O:</b> ${d.pemeriksaan || '-'} <span class="text-muted">(Tensi: ${d.tensi||'-'}, Suhu: ${d.suhu_tubuh||'-'}, Nadi: ${d.nadi||'-'}, SpO2: ${d.spo2||'-'})</span><br>
                            <b>A:</b> ${d.penilaian || '-'}<br>
                            <b>P:</b> ${d.rtl || '-'} <br>
                            <b>I:</b> ${d.instruksi || '-'}
                         </div>
                         <div class="text-end text-muted font-monospace" style="font-size: 0.7rem;">Petugas: ${d.nama_petugas || d.nip}</div>`;
            }
            else if (item.jenis === 'Laboratorium') {
                html += `<b class="text-info">${d.pemeriksaan}</b><br>
                         <table class="table table-sm table-bordered mt-1 mb-0">
                            <tr class="table-light"><th>Parameter</th><th>Hasil</th><th>Rujukan</th></tr>`;
                d.detail.forEach(function(det) {
                    var isAbnormal = (det.keterangan !== ''); 
                    var trClass = isAbnormal ? 'table-warning text-danger fw-bold' : '';
                    html += `<tr class="${trClass}">
                                <td>${det.nm_template}</td>
                                <td>${det.nilai} ${det.keterangan}</td>
                                <td class="text-muted">${det.nilai_rujukan}</td>
                             </tr>`;
                });
                html += `</table>`;
            }
            else if (item.jenis === 'Radiologi') {
                var listPemeriksaan = d.pemeriksaan.join(', ');
                html += `<b>Pemeriksaan:</b> ${listPemeriksaan}<br>`;
                html += `<div class="bg-light border p-2 mt-2 rounded" style="white-space: pre-wrap;"><b>Hasil Expertise:</b><br>${d.hasil || '<i>(Belum ada expertise)</i>'}</div>`;
            }
            else if (item.jenis === 'Resep') {
                html += `<ul class="mb-0 ps-3">`;
                if(d.no_resep && d.no_resep !== '-') {
                    html += `<li class="text-muted mb-1">No. Resep/Nota: <b>${d.no_resep}</b></li>`;
                }
                if(d.umum && d.umum.length > 0) {
                    d.umum.forEach(function(u) {
                        html += `<li>${u.nama_brng} (Jml: ${u.jml}) - ${u.aturan_pakai}</li>`;
                    });
                }
                if(d.racikan && d.racikan.length > 0) {
                    d.racikan.forEach(function(r) {
                        html += `<li><b>Racikan:</b> ${r.nama_racik} (Jml: ${r.jml_dr}) - ${r.aturan_pakai}</li>`;
                    });
                }
                // Handle Resep Pulang & generic farmasi
                if(d['Kode Barang']) {
                     html += `<li>Barang: ${d['Kode Barang']} (Jml: ${d['Jumlah']}) - ${d['Aturan Pakai']}</li>`;
                }
                html += `</ul>`;
            }
            else if (item.jenis === 'Diagnosa') {
                html += `<ul class="mb-0 ps-3">`;
                d.forEach(function(diag) {
                    html += `<li><b>[${diag.kd_penyakit}]</b> ${diag.nm_penyakit} <span class="badge bg-light text-dark border">${diag.status}</span> <span class="badge bg-light text-dark border">${diag.prioritas}</span></li>`;
                });
                html += `</ul>`;
            }
            else if (item.jenis === 'Prosedur Medis') {
                html += `<ul class="mb-0 ps-3">`;
                d.forEach(function(pro) {
                    html += `<li><b>[${pro.kode}]</b> ${pro.nm_prosedur} <span class="badge bg-light text-dark border">${pro.status}</span> <span class="badge bg-light text-dark border">${pro.prioritas}</span></li>`;
                });
                html += `</ul>`;
            }
            else if (item.jenis === 'Laporan Tindakan') {
                // Renderer tindakan: tampilkan nama tindakan, dokter, dan TOTAL BIAYA saja
                var nmTindakan = d['Nama Tindakan'] || d['Nama Tindakan/Perawatan'] || d['Nama Operasi'] || '-';
                var kategori   = d['Kategori'] || d['Tipe Dokumen'] || '-';
                var dokter     = d['Dokter'] || '-';
                var biayaTotal = d['Biaya Total'] || '-';
                var statusOp   = d['Status'] || '';

                html += `<div class="d-flex align-items-start gap-2 flex-wrap">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">${kategori}</span>
                            <b>${nmTindakan}</b>`;
                if (statusOp) html += `<span class="badge bg-secondary ms-1">${statusOp}</span>`;
                html += `   </div>`;
                if (dokter && dokter !== '-') {
                    html += `<div class="text-muted small mt-1"><i class="fas fa-user-md me-1"></i>${dokter}</div>`;
                }
                if (biayaTotal && biayaTotal !== '-') {
                    html += `<div class="mt-2"><span class="badge bg-light text-dark border fw-bold" style="font-size:0.85rem;"><i class="fas fa-receipt me-1 text-primary"></i> ${biayaTotal}</span></div>`;
                }
            }
            else if (item.jenis === 'Berkas Digital') {
                html += `<b>Kategori:</b> ${d.Tipe}<br>`;
                html += `<div class="mt-2 text-center rounded border p-2 bg-light">`;
                if(d.is_image === 'true') {
                    html += `<a href="${d.url_berkas}" target="_blank"><img src="${d.url_berkas}" alt="Berkas" class="img-fluid rounded border shadow-sm" style="max-height: 250px;"></a>`;
                    html += `<div class="mt-2"><a href="${d.url_berkas}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-search-plus me-1"></i> Buka Gambar Besar</a></div>`;
                } else {
                    html += `<a href="${d.url_berkas}" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-file-pdf me-2"></i>Lihat Dokumen (${d.Tipe})</a>`;
                }
                html += `</div>`;
            }
            else {
                // FALLBACK GENERIC UNTUK SEMUA TABEL BARU SEPERTI OBSERVASI, EWS, PENILAIAN, ASSEMENT, DSB.
                html += `<div class="mt-2">`;
                if(d.Tipe || d.tipe) {
                    html += `<span class="badge bg-secondary mb-2">${d.Tipe || d.tipe}</span>`;
                }
                
                html += `<div class="data-grid">`;
                for (var key in d) {
                    if (key !== 'tipe' && key !== 'Tipe' && key !== 'no_rawat') {
                        var val = d[key] || '-';
                        var isLongText = (String(val).length > 60 || String(val).includes('\n'));
                        var boxClass = isLongText ? 'data-box full-width' : 'data-box';
                        var formattedKey = String(key).replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        
                        html += `<div class="${boxClass}">
                                    <div class="data-key">${formattedKey}</div>
                                    <div class="data-val">${val}</div>
                                 </div>`;
                    }
                }
                html += `</div></div>`;
            }

            html += `       </div>
                        </div>
                    </div>`;
        });

        html += '</div>';
        return { html: html, charts: chartsToRender };
    }

    function renderTimeline(data) {
        if(data.length === 0) {
            $('#timelineContent').html('<div class="text-center text-muted py-4"><i class="fas fa-folder-open fa-3x mb-3 text-light"></i><br>Belum ada riwayat medis untuk pasien ini.</div>').show();
            return;
        }

        var viewMode = $('#viewModeRiwayat').val();
        var html = '';
        var allChartsToRender = [];

        if(viewMode === 'group') {
            var grouped = {};
            data.forEach(function(item) {
                if(!grouped[item.jenis]) grouped[item.jenis] = [];
                grouped[item.jenis].push(item);
            });
            for(var cat in grouped) {
                html += '<h6 class="text-secondary fw-bold mt-4 mb-3 border-bottom pb-2 text-uppercase"><i class="fas fa-layer-group me-2"></i>KATEGORI DATA: ' + cat + '</h6>';
                var res = createTimelineHtml(grouped[cat]);
                html += res.html;
                allChartsToRender = allChartsToRender.concat(res.charts);
            }
        } else {
            var res = createTimelineHtml(data);
            html = res.html;
            allChartsToRender = res.charts;
        }

        $('#timelineContent').html(html).fadeIn(400, function() {
            // SETELAH HTML TER-RENDER, INISIALISASI APEXCHARTS
            allChartsToRender.forEach(function(chartInfo) {
                var d = chartInfo.data;
                var groupName = 'sync-riwayat-' + chartInfo.rawat;
                
                var commonOptions = {
                    chart: { type: 'line', group: groupName, height: 160, animations: { enabled: false }, toolbar: { show: false } },
                    stroke: { curve: 'smooth', width: 2 },
                    markers: { size: 4, hover: { size: 6 } },
                    xaxis: { type: 'datetime', labels: { datetimeUTC: false, format: 'dd MMM HH:mm' }, tooltip: { enabled: false } },
                    yaxis: { labels: { minWidth: 40 } },
                    tooltip: { x: { format: 'dd MMM yyyy HH:mm' } }
                };

                // 1. Suhu (Biru)
                var optSuhu = Object.assign({}, commonOptions, {
                    series: [{ name: 'Suhu (°C)', data: d.map(v => [v.x, v.suhu]).filter(v => v[1] !== null) }],
                    colors: [colors.suhu],
                    title: { text: 'Suhu', align: 'left', style: { fontSize: '10px', color: '#666' } }
                });

                // 2. Respirasi (Hijau) & GCS (Kuning)
                var optRrGcs = Object.assign({}, commonOptions, {
                    series: [
                        { name: 'RR (x/mnt)', data: d.map(v => [v.x, v.rr]).filter(v => v[1] !== null) },
                        { name: 'GCS', data: d.map(v => [v.x, v.gcs]).filter(v => v[1] !== null) }
                    ],
                    colors: [colors.rr, colors.gcs],
                    title: { text: 'Respirasi & GCS', align: 'left', style: { fontSize: '10px', color: '#666' } }
                });

                // 3. Nadi (Merah) & SpO2 (Ungu)
                var optNadiSpo2 = Object.assign({}, commonOptions, {
                    series: [
                        { name: 'Nadi (x/mnt)', data: d.map(v => [v.x, v.nadi]).filter(v => v[1] !== null) },
                        { name: 'SpO2 (%)', data: d.map(v => [v.x, v.spo2]).filter(v => v[1] !== null) }
                    ],
                    colors: [colors.nadi, colors.spo2],
                    title: { text: 'Nadi & SpO2', align: 'left', style: { fontSize: '10px', color: '#666' } }
                });

                // 4. Tekanan Darah (Systole Merah, Diastole Biru)
                var optTensi = Object.assign({}, commonOptions, {
                    series: [
                        { name: 'Systole (mmHg)', data: d.map(v => [v.x, v.sys]).filter(v => v[1] !== null) },
                        { name: 'Diastole (mmHg)', data: d.map(v => [v.x, v.dia]).filter(v => v[1] !== null) }
                    ],
                    colors: [colors.systole, colors.diastole],
                    title: { text: 'Tekanan Darah', align: 'left', style: { fontSize: '10px', color: '#666' } }
                });

                // Render masing-masing chart dalam container div tersendiri
                const containerId = chartInfo.id;
                const containerHtml = `
                    <div id="chart-suhu-${containerId}"></div>
                    <div id="chart-rr-${containerId}"></div>
                    <div id="chart-nadi-${containerId}"></div>
                    <div id="chart-tensi-${containerId}"></div>
                `;
                $('#' + containerId).html(containerHtml);

                var ch1 = new ApexCharts(document.querySelector("#chart-suhu-" + containerId), optSuhu);
                var ch2 = new ApexCharts(document.querySelector("#chart-rr-" + containerId), optRrGcs);
                var ch3 = new ApexCharts(document.querySelector("#chart-nadi-" + containerId), optNadiSpo2);
                var ch4 = new ApexCharts(document.querySelector("#chart-tensi-" + containerId), optTensi);
                
                ch1.render(); ch2.render(); ch3.render(); ch4.render();
                riwayatChartInstances.push(ch1, ch2, ch3, ch4);
            });
        });
    }

    // Bind event switch filters & sort
    $('.filter-chk, #sortRiwayat, #viewModeRiwayat').on('change', function() {
        applyFiltersAndRender();
    });
    
    $('#riwayatFilterMode').on('change', function() {
        if($(this).val() === 'tanggal') {
            $('.riwayat-date-range').removeClass('d-none');
        } else {
            $('.riwayat-date-range').addClass('d-none');
        }
    });

    $('#btnTerapkanFilterRiwayat').on('click', function() {
        _debugLogs = [];
        loadRiwayat();
    });

    window.aiJsonData = null;
    var aiChatHistoryData = [];

    $('#btnAIResumeFromRiwayat').on('click', function() {
        var noRawat = '<?= htmlspecialchars($no_rawat_aktif) ?>';
        var ai_mode = '<?= $ai_mode ?>';
        
        // Tampilkan container AI & scroll ke paling atas
        $('#aiSummaryContainer').removeClass('d-none');
        if (ai_mode === 'mpp') {
            // Split layout for MPP
            $('#containerTimelineRiwayat').removeClass('col-md-12').addClass('col-md-7');
        } else {
            // Inline/Sticky layout for Edokter
            $('#btnToggleAICollapse').html('<i class="fas fa-chevron-up"></i> Lipat');
        }
        $('#aiSummaryBody').show();
        $('#containerTimelineRiwayat').animate({ scrollTop: 0 }, 'slow');

        // Ganti judul dan tombol jika MPP
        if (ai_mode === 'mpp') {
            $('#aiSummaryContainer h6').html('<i class="fas fa-robot me-1"></i> Cost Auditor & Analisis Casemix (AI)');
            if (window.location.href.includes('form_mpp.php')) {
                $('#btnApplyAIResume').html('<i class="fas fa-magic me-1"></i> Terapkan ke Form MPP').data('target', 'mpp');
            } else {
                $('#btnApplyAIResume').hide();
            }
        }

        // Reset UI
        $('#aiProgressBar').css('width', '5%').addClass('bg-info').removeClass('bg-success bg-danger');
        $('#aiProgressLabel').text('Menyiapkan data riwayat untuk AI...');
        $('#aiPercent').text('5%');
        $('#aiResumeStreamContent').html('<div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin fa-lg mb-2 text-primary"></i><p>Menyusun data riwayat...</p></div>');
        $('#btnApplyAIResume').prop('disabled', true).show();

        $('#aiChatHistory').empty().hide();
        $('#aiChatForm').hide();
        aiChatHistoryData = [];
        window.aiJsonData = null;

        var resumeType = window.location.href.includes('/ranap/') ? 'ranap' : 'ralan';
        if (ai_mode === 'mpp') resumeType = 'mpp_' + resumeType; // e.g. mpp_ranap
        
        // Base url logic: view_riwayat is inside helpers/ajax/ so we need ../../
        var streamUrl = 'api/riwayat/ajax_ai_resume_suggest.php';
        var accumulatedText = '';

        $('#aiProgressLabel').text('Menganalisis riwayat klinis dengan AI...');
        $('#aiProgressBar').css('width', '30%');
        $('#aiPercent').text('30%');

        var filterMode = $('#riwayatFilterMode').val() || '5_terakhir';
        var tglAwal = $('#riwayatTglAwal').val() || '';
        var tglAkhir = $('#riwayatTglAkhir').val() || '';

        fetch(streamUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'no_rawat=' + encodeURIComponent(noRawat) + '&resume_type=' + resumeType + '&stream=1&filter_mode=' + encodeURIComponent(filterMode) + '&tgl_awal=' + encodeURIComponent(tglAwal) + '&tgl_akhir=' + encodeURIComponent(tglAkhir) + '&raw_data=' + encodeURIComponent(JSON.stringify(rawData))
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            $('#aiProgressBar').css('width', '60%');
            $('#aiPercent').text('60%');
            $('#aiResumeStreamContent').empty();
            
            var reader = response.body.getReader();
            var decoder = new TextDecoder('utf-8');

            function readChunk() {
                return reader.read().then(function(result) {
                    if (result.done) {
                        $('#aiProgressBar').css('width', '100%').removeClass('bg-info').addClass('bg-success');
                        $('#aiPercent').text('100%');
                        $('#aiProgressLabel').text('Analisis AI Selesai!');
                        
                        var match = accumulatedText.match(/```json\s*([\s\S]*?)\s*```/);
                        if (match && match[1]) {
                            try {
                                window.aiJsonData = JSON.parse(match[1].trim());
                                $('#btnApplyAIResume').prop('disabled', false);
                            } catch (e) {
                                console.error('Gagal parse JSON dari stream AI', e);
                                $('#aiResumeStreamContent').append('<div class="alert alert-warning mt-2 small"><i class="fas fa-exclamation-triangle"></i> AI menghasilkan data JSON yang tidak valid. Pengisian otomatis dinonaktifkan.</div>');
                            }
                        }
                        
                        $('#aiChatHistory').show().html('<div class="text-muted small text-center italic py-2">Mulai diskusi dengan AI terkait riwayat klinis pasien ini...</div>');
                        $('#aiChatForm').show();
                        return;
                    }

                    var chunk = decoder.decode(result.value, { stream: true });
                    var lines = chunk.split('\n');
                    lines.forEach(function(line) {
                        if (line.startsWith('data: ')) {
                            var rawData = line.substring(6).trim();
                            if (rawData === '[DONE]') return;
                            try {
                                var parsed = JSON.parse(rawData);
                                if (parsed.choices && parsed.choices.length > 0 && parsed.choices[0].delta && parsed.choices[0].delta.content) {
                                    accumulatedText += parsed.choices[0].delta.content;
                                    
                                    var displayText = accumulatedText.replace(/```json[\s\S]*?```/g, '').trim();
                                    
                                    var formatted = displayText
                                        .replace(/\n/g, '<br>')
                                        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                                        .replace(/\*(.*?)\*/g, '<em>$1</em>');
                                    
                                    $('#aiResumeStreamContent').html(formatted);
                                    
                                    var container = document.getElementById('aiResumeStreamContent');
                                    if (container) container.scrollTop = container.scrollHeight;
                                }
                            } catch (e) {
                                // Potongan data biasa
                            }
                        }
                    });

                    return readChunk();
                });
            }

            return readChunk();
        })
        .catch(function(err) {
            console.error('Error streaming AI Resume:', err);
            $('#aiProgressBar').addClass('bg-danger');
            $('#aiProgressLabel').text('Koneksi Gagal!');
            $('#aiResumeStreamContent').html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-1"></i> Gagal terhubung ke AI: ' + err.message + '</div>');
        });
    });



    $('#btnToggleFilterRiwayat').on('click', function() {
        $('#filterRiwayatBody').slideToggle(200, function() {
            if ($(this).is(':visible')) {
                $('#btnToggleFilterRiwayat').html('<i class="fas fa-chevron-up"></i> Filter');
            } else {
                $('#btnToggleFilterRiwayat').html('<i class="fas fa-chevron-down"></i> Filter');
            }
        });
    });

    $('#btnToggleAICollapse').on('click', function() {
        $('#aiSummaryBody').slideToggle(200, function() {
            if ($(this).is(':visible')) {
                $('#btnToggleAICollapse').html('<i class="fas fa-chevron-up"></i> Lipat');
            } else {
                $('#btnToggleAICollapse').html('<i class="fas fa-chevron-down"></i> Buka');
            }
        });
    });

    $(document).on('submit', '#aiChatForm', function(e) {
        e.preventDefault();
        const input = $('#aiChatInput');
        const messageText = input.val().trim();
        if (!messageText) return;
        
        if (aiChatHistoryData.length === 0) {
            $('#aiChatHistory').empty();
        }

        $('#aiChatHistory').append(
            '<div class="mb-2 p-2 bg-white rounded border border-primary text-end shadow-sm ms-5">' +
            '<div class="fw-bold text-primary small mb-1">Anda <i class="fas fa-user-md ms-1"></i></div>' +
            '<div class="text-dark d-inline-block text-start">' + messageText.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div>' +
            '</div>'
        );
        $('#aiChatHistory').scrollTop($('#aiChatHistory')[0].scrollHeight);
        
        input.val('');
        $('#aiChatInput, #btnSendAIChat').prop('disabled', true);
        
        const bubbleId = 'chat_reply_' + Date.now();
        $('#aiChatHistory').append(
            '<div class="mb-2 p-2 bg-white rounded border border-info shadow-sm me-5">' +
            '<div class="fw-bold text-info small mb-1"><i class="fas fa-robot me-1"></i>Asisten AI</div>' +
            '<div class="text-dark" id="' + bubbleId + '"><i class="fas fa-circle-notch fa-spin"></i> Mengetik...</div>' +
            '</div>'
        );
        $('#aiChatHistory').scrollTop($('#aiChatHistory')[0].scrollHeight);
        
        var noRawat = '<?= htmlspecialchars($no_rawat_aktif) ?>';
        var resumeType = window.location.href.includes('/ranap/') ? 'ranap' : 'ralan';
        var filterMode = $('#riwayatFilterMode').val() || '5_terakhir';
        var tglAwal = $('#riwayatTglAwal').val() || '';
        var tglAkhir = $('#riwayatTglAkhir').val() || '';

        var chatData = new URLSearchParams();
        chatData.append('action', 'chat_discuss');
        chatData.append('no_rawat', noRawat);
        chatData.append('resume_type', resumeType);
        chatData.append('filter_mode', filterMode);
        chatData.append('tgl_awal', tglAwal);
        chatData.append('tgl_akhir', tglAkhir);
        chatData.append('message', messageText);
        chatData.append('history', JSON.stringify(aiChatHistoryData));
        chatData.append('raw_data', JSON.stringify(rawData));
        chatData.append('stream', '1');

        fetch('api/riwayat/ajax_ai_resume_suggest.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: chatData
        })
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            const reader = response.body.getReader();
            const decoder = new TextDecoder('utf-8');
            let fullReply = '';
            
            $('#' + bubbleId).empty();

            function read() {
                reader.read().then(({ done, value }) => {
                    if (done) {
                        $('#aiChatInput, #btnSendAIChat').prop('disabled', false);
                        aiChatHistoryData.push({ role: 'user', content: messageText });
                        aiChatHistoryData.push({ role: 'assistant', content: fullReply });
                        $('#aiChatInput').focus();
                        return;
                    }
                    var chunk = decoder.decode(value, { stream: true });
                    var lines = chunk.split('\n');
                    lines.forEach(function(line) {
                        if (line.startsWith('data: ')) {
                            var rawData = line.substring(6).trim();
                            if (rawData === '[DONE]') return;
                            try {
                                var parsed = JSON.parse(rawData);
                                if (parsed.choices && parsed.choices.length > 0 && parsed.choices[0].delta && parsed.choices[0].delta.content) {
                                    fullReply += parsed.choices[0].delta.content;
                                    var formattedReply = fullReply
                                        .replace(/\n/g, '<br>')
                                        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                                        .replace(/\*(.*?)\*/g, '<em>$1</em>');
                                    $('#' + bubbleId).html(formattedReply);
                                    $('#aiChatHistory').scrollTop($('#aiChatHistory')[0].scrollHeight);
                                }
                            } catch(e) {}
                        }
                    });
                    read();
                }).catch(err => {
                    $('#' + bubbleId).html('<span class="text-danger">Koneksi terputus.</span>');
                    $('#aiChatInput, #btnSendAIChat').prop('disabled', false);
                });
            }
            read();
        })
        .catch(err => {
            $('#' + bubbleId).html('<span class="text-danger">Terjadi kesalahan sistem.</span>');
            $('#aiChatInput, #btnSendAIChat').prop('disabled', false);
        });
    });

    $('#btnTutupAISummary').on('click', function() {
        $('#aiSummaryContainer').addClass('d-none');
        if (typeof ai_mode !== 'undefined' && ai_mode === 'mpp') {
            $('#containerTimelineRiwayat').removeClass('col-md-7').addClass('col-md-12');
        }
    });
    $('#btnApplyAIResume').on('click', function() {
        if (!window.aiJsonData) return;
        if (typeof ai_mode !== 'undefined' && ai_mode === 'mpp') return;

        var resumeType = window.location.href.includes('/ranap/') ? 'ranap' : 'ralan';
        var textFields = [];
        var dropdowns = [];
        var diagFields = [];
        var prosFields = [];

        if (resumeType === 'ranap') {
            textFields = [
                'diagnosa_awal', 'alasan', 'keluhan_utama', 'pemeriksaan_fisik', 'jalannya_penyakit', 
                'pemeriksaan_penunjang', 'hasil_laborat', 'tindakan_dan_operasi', 'obat_di_rs', 
                'alergi', 'diet', 'lab_belum', 'edukasi', 'ket_keluar', 'ket_keadaan', 'ket_dilanjutkan', 'obat_pulang'
            ];
            dropdowns = ['cara_keluar', 'keadaan', 'dilanjutkan'];
            diagFields = [
                { code: 'kd_diagnosa_utama', name: 'diagnosa_utama' },
                { code: 'kd_diagnosa_sekunder', name: 'diagnosa_sekunder' },
                { code: 'kd_diagnosa_sekunder2', name: 'diagnosa_sekunder2' },
                { code: 'kd_diagnosa_sekunder3', name: 'diagnosa_sekunder3' },
                { code: 'kd_diagnosa_sekunder4', name: 'diagnosa_sekunder4' }
            ];
            prosFields = [
                { code: 'kd_prosedur_utama', name: 'prosedur_utama' },
                { code: 'kd_prosedur_sekunder', name: 'prosedur_sekunder' },
                { code: 'kd_prosedur_sekunder2', name: 'prosedur_sekunder2' },
                { code: 'kd_prosedur_sekunder3', name: 'prosedur_sekunder3' }
            ];
        } else {
            textFields = [
                'keluhan_utama', 'pemeriksaan_fisik', 'diagnosis', 'tindakan', 'obat_diberikan', 
                'evaluasi', 'kontrol_kembali', 'indikasi_kontrol', 'rujuk_ke'
            ];
            dropdowns = ['cara_keluar'];
            diagFields = [
                { code: 'kd_diagnosa_utama', name: 'diagnosa_utama' },
                { code: 'kd_diagnosa_sekunder', name: 'diagnosa_sekunder' },
                { code: 'kd_diagnosa_sekunder2', name: 'diagnosa_sekunder2' },
                { code: 'kd_diagnosa_sekunder3', name: 'diagnosa_sekunder3' }
            ];
            prosFields = [
                { code: 'kd_prosedur_utama', name: 'prosedur_utama' },
                { code: 'kd_prosedur_sekunder', name: 'prosedur_sekunder' },
                { code: 'kd_prosedur_sekunder2', name: 'prosedur_sekunder2' },
                { code: 'kd_prosedur_sekunder3', name: 'prosedur_sekunder3' }
            ];
        }

        // Apply text fields
        textFields.forEach(function(f) {
            if (aiJsonData[f] !== undefined) {
                var el = $('[name="' + f + '"]');
                if (el.length) {
                    el.val(aiJsonData[f]);
                    el.addClass('ai-field-highlight');
                    setTimeout(function() { el.removeClass('ai-field-highlight'); }, 2000);
                }
            }
        });

        // Apply select dropdowns
        dropdowns.forEach(function(d) {
            if (aiJsonData[d]) {
                var el = $('select[name="' + d + '"]');
                if (el.length) {
                    el.val(aiJsonData[d]);
                    el.addClass('ai-field-highlight');
                    setTimeout(function() { el.removeClass('ai-field-highlight'); }, 2000);
                }
            }
        });

        // Apply Diagnoses (ICD-10)
        diagFields.forEach(function(df) {
            var codeVal = aiJsonData[df.code];
            var nameVal = aiJsonData[df.name];
            if (codeVal && nameVal) {
                var selectEl = $('select[name="' + df.code + '"]');
                var textEl = $('input[name="' + df.name + '"]');
                
                if (selectEl.length && textEl.length) {
                    if (selectEl.find("option[value='" + codeVal + "']").length) {
                        selectEl.val(codeVal).trigger('change');
                    } else {
                        var newOption = new Option(codeVal, codeVal, true, true);
                        selectEl.append(newOption).trigger('change');
                    }
                    textEl.val(nameVal);
                    
                    textEl.addClass('ai-field-highlight');
                    selectEl.next('.select2-container').addClass('ai-field-highlight');
                    setTimeout(function() {
                        textEl.removeClass('ai-field-highlight');
                        selectEl.next('.select2-container').removeClass('ai-field-highlight');
                    }, 2000);
                }
            }
        });

        // Apply Procedures (ICD-9)
        prosFields.forEach(function(pf) {
            var codeVal = aiJsonData[pf.code];
            var nameVal = aiJsonData[pf.name];
            if (codeVal && nameVal) {
                var selectEl = $('select[name="' + pf.code + '"]');
                var textEl = $('input[name="' + pf.name + '"]');
                
                if (selectEl.length && textEl.length) {
                    if (selectEl.find("option[value='" + codeVal + "']").length) {
                        selectEl.val(codeVal).trigger('change');
                    } else {
                        var newOption = new Option(codeVal, codeVal, true, true);
                        selectEl.append(newOption).trigger('change');
                    }
                    textEl.val(nameVal);
                    
                    textEl.addClass('ai-field-highlight');
                    selectEl.next('.select2-container').addClass('ai-field-highlight');
                    setTimeout(function() {
                        textEl.removeClass('ai-field-highlight');
                        selectEl.next('.select2-container').removeClass('ai-field-highlight');
                    }, 2000);
                }
            }
        });

        var notify = $('<div class="alert alert-info py-2" style="position:fixed; top:20px; right:20px; z-index:99999; box-shadow: 0 4px 15px rgba(0,0,0,0.2);"><i class="fas fa-check-circle me-1"></i> Form diisi otomatis oleh AI</div>');
        $('body').append(notify);
        setTimeout(function() { notify.fadeOut(500, function() { $(this).remove(); }); }, 3000);
    });

    loadRiwayat();

    $('#btnRefreshRiwayat').click(function() {
        _debugLogs = [];
        loadRiwayat();
    });

    // ================================================================
    // DEBUG CONSOLE — hanya tampil jika DEBUG_MODE = true
    // ================================================================
    function renderDebugConsole() {
        if (!DEBUG_MODE) return;
        var $panel = $('#debugConsolePanel');
        if ($panel.length === 0) return;

        var levelIcon = { 'success':'✅', 'error':'❌', 'warning':'⚠️', 'info':'ℹ️' };
        var levelColor = { 'success':'#198754', 'error':'#dc3545', 'warning':'#fd7e14', 'info':'#6c757d' };

        var html = '';
        _debugLogs.forEach(function(entry) {
            var l = entry.log;
            var ic = levelIcon[l.level] || 'ℹ️';
            var col = levelColor[l.level] || '#000';
            var extra = l.extra ? ('<br><code style="font-size:0.7rem;color:#888;">' + (typeof l.extra === 'object' ? JSON.stringify(l.extra) : l.extra) + '</code>') : '';
            html += `<div style="border-bottom:1px solid #2a2a2a;padding:4px 6px;">
                <span style="color:#777;font-size:0.7rem;">[${l.time}]</span>
                <span style="color:#aaa;font-size:0.7rem;"> ${entry.ep.replace('api/riwayat/ajax_riwayat_','').replace('.php','')} /</span>
                <span style="color:#ccc;font-size:0.75rem;font-weight:bold;"> ${l.tag}</span><br>
                <span style="color:${col};font-size:0.75rem;">${ic} ${l.message}</span>${extra}
            </div>`;
        });
        if (!html) html = '<div style="padding:8px;color:#666;font-size:0.75rem;">Menunggu data...</div>';
        $('#debugConsoleLog').html(html);
        // Auto-scroll ke bawah
        var el = document.getElementById('debugConsoleLog');
        if (el) el.scrollTop = el.scrollHeight;
    }

    // Tombol aksi debug console
    $(document).on('click', '#btnDebugClear', function() {
        _debugLogs = []; renderDebugConsole();
    });
    $(document).on('click', '#btnDebugCopy', function() {
        var text = _debugLogs.map(function(e) {
            return '[' + e.log.time + '] ' + e.ep + ' / ' + e.log.tag + ': ' + e.log.message + (e.log.extra ? ' | ' + JSON.stringify(e.log.extra) : '');
        }).join('\n');
        navigator.clipboard.writeText(text).then(function() {
            $('#btnDebugCopy').html('✅ Tersalin!');
            setTimeout(function() { $('#btnDebugCopy').html('📋 Copy'); }, 2000);
        });
    });
    $(document).on('click', '#btnDebugToggle', function() {
        $('#debugConsoleLog').slideToggle(150);
        $('#debugConsoleFooter').slideToggle(150);
    });
});
</script>

<?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
<!-- DEBUG CONSOLE PANEL (hanya muncul saat DEBUG_MODE = true) -->
<div id="debugConsolePanel" style="
    position: fixed; bottom: 20px; right: 20px; z-index: 99999;
    width: 480px; max-width: 95vw;
    background: #1a1a1a; border: 1px solid #444; border-radius: 8px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.6); font-family: monospace;
">
    <div id="btnDebugToggle" style="padding:8px 12px; background:#2d2d2d; border-radius:8px 8px 0 0; cursor:pointer; display:flex; justify-content:space-between; align-items:center;">
        <span style="color:#f0c040; font-size:0.8rem; font-weight:bold;">🐛 Debug Console <span style="color:#888; font-size:0.7rem;">(DEBUG_MODE=true)</span></span>
        <span style="color:#777; font-size:0.75rem;">▲ klik untuk toggle</span>
    </div>
    <div id="debugConsoleLog" style="max-height: 280px; overflow-y: auto; background:#111; padding:4px 0;"></div>
    <div id="debugConsoleFooter" style="padding:6px 10px; background:#2d2d2d; border-radius:0 0 8px 8px; display:flex; gap:6px;">
        <button id="btnDebugClear" style="background:#444; color:#ccc; border:0; border-radius:4px; padding:3px 8px; font-size:0.72rem; cursor:pointer;">🗑 Clear</button>
        <button id="btnDebugCopy"  style="background:#444; color:#ccc; border:0; border-radius:4px; padding:3px 8px; font-size:0.72rem; cursor:pointer;">📋 Copy</button>
        <span style="color:#555; font-size:0.7rem; margin-left:auto; padding-top:3px;">Matikan di config.php &rarr; DEBUG_MODE = false</span>
    </div>
</div>
<?php endif; ?>
