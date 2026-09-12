<?php
/**
 * modules/obat/index.php — Halaman Mapping Obat & Alkes (KFA Satu Sehat)
 * Mendukung KFA v2 (Obat/Farmasi) dan KFA v3 (Alat Kesehatan / Alkes)
 * DataTables server-side pagination dengan filter kategori Obat/Alkes
 */
require_once '../../conf.php';
require_once '../../auth_check.php';
check_module_access('satu_sehat_mapping_obat'); // RBAC Guard
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mapping Obat &amp; Alkes — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="shortcut icon" href="../../logo.php">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }
        .navbar-brand { font-weight: 700; }
        .nav-back { color: #6366f1; font-weight: 500; text-decoration: none; font-size: .875rem; }
        .nav-back:hover { color: #4338ca; }
        .page-header { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; border-radius: 16px; padding: 1.5rem 2rem; margin-bottom: 1.5rem; }
        .page-header h4 { font-weight: 700; margin: 0; }
        .page-header p { margin: 0.25rem 0 0; opacity: 0.8; font-size: .875rem; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .type-pill-btn { border-radius: 8px; font-weight: 600; padding: 8px 16px; transition: all 0.2s; }
        .alkes-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; }

        /* Fix Select2 inside Modal: pastikan selalu di bawah input, z-index tinggi, dan selalu scrollable */
        .modal-body .mb-3, .modal-body .col-md-6 { position: relative; }
        .select2-container { width: 100% !important; z-index: 1060; }
        .input-group > .select2-container--bootstrap-5 {
            flex: 1 1 auto !important;
            width: 1% !important;
        }
        .select2-container--open { z-index: 1065 !important; }
        .select2-container--open .select2-dropdown {
            z-index: 1065 !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.15) !important;
            overflow: hidden !important;
        }
        .select2-container--open .select2-results__options {
            max-height: 220px !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
        }

        .footer-credit { text-align: center; padding: 1.5rem; font-size: .72rem; color: #94a3b8; cursor: pointer; transition: all 0.2s; }
        .footer-credit:hover { color: #6366f1; background: rgba(99, 102, 241, 0.05); }
        .footer-credit a { color: #6d28d9; text-decoration: none; font-weight: 600; }
        .footer-credit a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-light bg-white border-bottom px-3 py-2 mb-4">
    <a class="nav-back" href="../../index.php">
        <i class="fa fa-arrow-left me-2"></i>Dashboard
    </a>
    <span class="navbar-brand text-primary mb-0" style="font-size:1rem">
        <i class="fa-solid fa-pills me-1"></i><i class="fa-solid fa-stethoscope me-2"></i> Mapping Obat &amp; Alkes — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?>
    </span>
    <div>
        <span class="text-muted small me-3"><i class="fa fa-user me-1"></i><?= htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
        <a href="../../logout.php" class="text-danger small text-decoration-none">
            <i class="fa fa-right-from-bracket"></i> Logout
        </a>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4><i class="fa-solid fa-capsules me-2"></i> Mapping Obat &amp; Alkes ke KFA Satu Sehat</h4>
            <p>Mendukung KFA v2 (Obat/Farmasi) dan KFA v3 (Alat Kesehatan &amp; BMHP). Klik tombol Mapping pada baris item untuk menetapkan kode KFA.</p>
        </div>
        <a href="../referensi/index.php" target="_blank" class="btn btn-light text-primary fw-bold px-4 rounded-pill shadow-sm" style="font-size: 0.9rem;">
            <i class="fa fa-database me-2"></i> Master Referensi Data
        </a>
    </div>

    <!-- Panel Pencarian Server-Side & Filter Kategori -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-7">
                    <label class="form-label fw-semibold text-primary small">Cari Nama / Kode Barang (Server)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
                        <input type="text" id="keyword_obat" class="form-control"
                               placeholder="Ketik nama atau kode barang, lalu tekan Enter...">
                        <button class="btn btn-primary px-4" id="btnCariServer">
                            <i class="fa fa-filter me-1"></i> Tampilkan
                        </button>
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold text-primary small">Filter Kategori Barang</label>
                    <select class="form-select" id="filter_kategori">
                        <option value="semua" selected>📋 Semua Barang (Obat &amp; Alkes/BMHP)</option>
                        <option value="obat">💊 Khusus Obat &amp; Farmasi</option>
                        <option value="alkes">🩺 Khusus Alkes &amp; BMHP</option>
                    </select>
                </div>
            </div>
            <div class="form-text mt-2">Kosongkan pencarian untuk menampilkan <strong>semua data</strong>. Filter kategori otomatis menyaring barang farmasi dan peralatan medis.</div>
        </div>
    </div>

    <!-- Tabel -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelObat" class="table table-striped table-hover table-bordered w-100">
                    <thead class="table-light">
                        <tr>
                            <th width="10%">Kode RS</th>
                            <th width="30%">Nama Barang (RS)</th>
                            <th width="40%">Detail Mapping (KFA, Tipe, Satuan/NIE)</th>
                            <th width="10%" class="text-center">Status</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Mapping Obat & Alkes -->
<div class="modal fade" id="modalMap" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-edit me-2"></i>Form Mapping Obat &amp; Alkes (KFA Satu Sehat)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="m_kode_brng">
                <input type="hidden" id="alkes_nie">
                <input type="hidden" id="alkes_manufacturer">
                <input type="hidden" id="alkes_template_code">
                <input type="hidden" id="alkes_template_name">
                <input type="hidden" id="alkes_farmalkes_type" value="device">

                <div class="alert alert-light border d-flex align-items-center mb-3">
                    <div id="m_icon_container" class="me-3">
                        <i class="fa fa-capsules fa-2x text-primary" id="m_icon"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5" id="m_nama_brng_label">Nama Barang</div>
                        <small class="text-muted" id="m_kode_brng_label">Kode RS</small>
                    </div>
                </div>

                <!-- Type Switcher: Obat (KFA v2) vs Alkes (KFA v3) -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark d-block">Pilih Tipe Produk KFA:</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="mapping_type" id="type_obat" value="obat" checked autocomplete="off">
                        <label class="btn btn-outline-primary type-pill-btn" for="type_obat">
                            <i class="fa fa-pills me-1"></i> 1. Obat / Farmasi (KFA v2)
                        </label>

                        <input type="radio" class="btn-check" name="mapping_type" id="type_alkes" value="alkes" autocomplete="off">
                        <label class="btn btn-outline-success type-pill-btn" for="type_alkes">
                            <i class="fa fa-stethoscope me-1"></i> 2. Alat Kesehatan / BMHP (KFA v3)
                        </label>
                    </div>
                </div>

                <!-- Mode Alkes Template Toggle (Visible in Alkes mode only) -->
                <div class="form-check form-switch mb-3 p-2 bg-light rounded border" id="box_template_only" style="display:none;">
                    <input class="form-check-input ms-0 me-2" type="checkbox" id="check_template_only">
                    <label class="form-check-label fw-semibold text-secondary small" for="check_template_only">
                        <i class="fa fa-tags me-1"></i> Cari Master Template Alkes saja (Generic / Non-Brand)
                    </label>
                    <div class="text-muted small" style="font-size: .75rem; margin-left: 28px;">Centang ini jika hanya ingin mapping ke kategori generik alkes tanpa spesifik merk atau nomor izin edar.</div>
                </div>

                <!-- Select2 KFA Input -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-primary" id="lbl_kfa_title">1. Kode KFA (Kamus Farmasi &amp; Alkes)</label>
                    <div class="input-group">
                        <select class="form-select" id="select_kfa" style="width:85%"></select>
                        <a href="https://satusehat.kemkes.go.id/kfa-browser/" target="_blank" class="btn btn-outline-secondary" title="Buka KFA Browser">
                            <i class="fa fa-external-link-alt"></i>
                        </a>
                    </div>

                    <!-- Badge sumber data, loading state, dan tombol resusitasi -->
                    <div class="mt-1 d-flex align-items-center gap-2 flex-wrap" id="kfa_obat_badge_row">
                        <span id="kfa_source_badge" class="badge bg-secondary" style="font-size:.7rem;">
                            <i class="fa fa-database me-1"></i>Sumber: Database Lokal
                        </span>
                        <span id="kfa_autofill_notice" class="text-success small" style="display:none;font-size:.72rem;">
                            <i class="fa fa-magic me-1"></i>Data otomatis terisi dari API KFA
                        </span>
                        <button type="button" id="btnRetryKfaObat"
                            style="display:none;font-size:.72rem;padding:2px 10px;border-radius:20px;"
                            class="btn btn-sm btn-warning fw-semibold">
                            <i class="fa fa-rotate-right me-1"></i>Coba Ulang API
                        </button>
                    </div>

                    <!-- Alkes metadata preview card -->
                    <div id="alkes_metadata_card" class="alkes-box mt-2" style="display:none;">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="badge bg-success"><i class="fa fa-stethoscope me-1"></i>Metadata Alkes (KFA v3)</span>
                            <span id="alkes_type_badge" class="badge bg-secondary">device</span>
                        </div>
                        <div class="small">
                            <div><b>Nama Dagang / Display:</b> <span id="alkes_preview_nama">-</span></div>
                            <div><b>NIE BPOM / Kemenkes:</b> <span id="alkes_preview_nie" class="text-primary fw-semibold">-</span></div>
                            <div><b>Pabrik / Produsen:</b> <span id="alkes_preview_mfg">-</span></div>
                            <div id="alkes_preview_template_row" style="display:none;"><b>Template:</b> <span id="alkes_preview_template">-</span></div>
                        </div>
                    </div>

                    <input type="hidden" id="kfa_display_hidden">
                    <input type="text" class="form-control mt-2" id="kfa_display_manual" placeholder="Atau ketik nama KFA manual jika tidak ditemukan...">
                </div>

                <!-- OBAT SPECIFIC: Bentuk Sediaan & Rute Pemberian -->
                <div id="row_bentuk_rute" class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">2. Bentuk Sediaan (Form)</label>
                        <select class="form-select select2-static" id="select_form" required>
                            <option value="">-- Pilih Bentuk --</option>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM satu_sehat_ref_form ORDER BY display ASC");
                            while ($f = $stmt->fetch()) {
                                echo "<option value='" . htmlspecialchars($f['code'].'|'.$f['display'], ENT_QUOTES, 'UTF-8') . "'>"
                                   . htmlspecialchars($f['display'], ENT_QUOTES, 'UTF-8') . " (" . htmlspecialchars($f['code'], ENT_QUOTES, 'UTF-8') . ")</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold text-danger">3. Rute Pemberian (ATC)</label>
                        <select class="form-select select2-static" id="select_route" required>
                            <option value="">-- Pilih Rute --</option>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM satu_sehat_ref_route");
                            while ($r = $stmt->fetch()) {
                                echo "<option value='" . htmlspecialchars($r['code'].'|'.$r['display'], ENT_QUOTES, 'UTF-8') . "'>"
                                   . htmlspecialchars($r['display'], ENT_QUOTES, 'UTF-8') . " (ATC: " . htmlspecialchars($r['code'], ENT_QUOTES, 'UTF-8') . ")</option>";
                            }
                            ?>
                        </select>
                        <div class="form-text text-danger small">System: http://www.whocc.no/atc</div>
                    </div>
                </div>

                <!-- OBAT SPECIFIC: Numerator & Denominator -->
                <div id="row_satuan" class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small text-muted">4. Satuan Numerator (Kekuatan)</label>
                        <select class="form-select select2-tags" id="select_numerator" style="width:100%;">
                            <option value="">-- Pilih Satuan --</option>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM satu_sehat_ref_numerator ORDER BY code ASC");
                            while ($n = $stmt->fetch()) {
                                echo "<option value='" . htmlspecialchars($n['code'], ENT_QUOTES, 'UTF-8') . "'>"
                                   . htmlspecialchars($n['code'], ENT_QUOTES, 'UTF-8') . " — " . htmlspecialchars($n['display'], ENT_QUOTES, 'UTF-8') . "</option>";
                            }
                            ?>
                        </select>
                        <div id="num_badge" class="badge bg-success mt-1">System: UCUM (http://unitsofmeasure.org)</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small text-muted">5. Satuan Denominator (Penyajian)</label>
                        <select class="form-select select2-tags" id="select_denominator" style="width:100%;">
                            <option value="">-- Pilih Satuan --</option>
                            <optgroup label="Sediaan Padat/Lainnya (DrugForm)">
                            <?php
                            $stmt = $pdo->query("SELECT * FROM satu_sehat_ref_denominator ORDER BY code ASC");
                            while ($dn = $stmt->fetch()) {
                                echo "<option value='" . htmlspecialchars($dn['code'], ENT_QUOTES, 'UTF-8') . "' data-sys='DrugForm'>"
                                   . htmlspecialchars($dn['code'], ENT_QUOTES, 'UTF-8') . " — " . htmlspecialchars($dn['display'], ENT_QUOTES, 'UTF-8') . "</option>";
                            }
                            ?>
                            </optgroup>
                            <optgroup label="Sediaan Cair/Volume (UCUM)">
                            <?php
                            $stmtU = $pdo->query("SELECT * FROM satu_sehat_ref_numerator ORDER BY code ASC");
                            while ($nu = $stmtU->fetch()) {
                                echo "<option value='" . htmlspecialchars($nu['code'], ENT_QUOTES, 'UTF-8') . "' data-sys='UCUM'>"
                                   . htmlspecialchars($nu['code'], ENT_QUOTES, 'UTF-8') . " — " . htmlspecialchars($nu['display'], ENT_QUOTES, 'UTF-8') . "</option>";
                            }
                            ?>
                            </optgroup>
                        </select>
                        <div id="den_badge" class="badge bg-secondary mt-1">System: Auto</div>
                    </div>
                </div>

                <!-- OBAT SPECIFIC: Dual-save sync to vaccine mapping -->
                <div class="form-check p-2 bg-light border rounded mt-2" id="group_sync_vaksin">
                    <input class="form-check-input ms-0 me-2" type="checkbox" id="check_sync_vaksin">
                    <label class="form-check-label fw-semibold small text-primary" for="check_sync_vaksin">
                        <i class="fa fa-syringe text-info me-1"></i> Sinkronkan juga ke Mapping Vaksin (<code>satu_sehat_mapping_vaksin</code>)
                    </label>
                    <div class="text-muted" style="font-size: .75rem; margin-left: 28px;">Centang jika item ini merupakan vaksin/imunisasi agar otomatis tersedia untuk modul Imunisasi Satu Sehat.</div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSimpanObat">
                    <i class="fa fa-save me-1"></i> Simpan Mapping
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Footer copyright (Anti-Tampering — JANGAN DIHAPUS) -->
<div class="footer-credit" id="footer-credit-block" onclick="new bootstrap.Modal(document.getElementById('modalSaweria')).show();">
    &copy; <a href="https://saweria.co/ichsanleonhart" target="_blank" onclick="event.stopPropagation();">Ichsan Leonhart</a> &nbsp;·&nbsp;
    <a href="https://wa.me/6285726123777" target="_blank" onclick="event.stopPropagation();">6285726123777</a> &nbsp;·&nbsp;
    <a href="https://t.me/IchsanLeonhart" target="_blank" onclick="event.stopPropagation();">@IchsanLeonhart</a> &nbsp;·&nbsp;
    <a href="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" target="_blank" onclick="event.stopPropagation();">QRIS Donasi</a>
    — <a href="https://saweria.co/ichsanleonhart" target="_blank" onclick="event.stopPropagation();">saweria.co/ichsanleonhart</a>
</div>

<!-- Modal Saweria (Uneg-uneg Mengemis) -->
<div class="modal fade" id="modalSaweria" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center pt-0 pb-4 px-4">
                <div class="mb-3">
                    <img src="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" class="img-fluid rounded-3 shadow-sm" style="max-width: 280px;" alt="QRIS Donasi">
                </div>
                <h5 class="fw-bold text-primary mb-3">Apresiasi &amp; Dukungan Donasi</h5>
                <p class="text-muted small px-2 mb-4" style="line-height: 1.6;">
                    Halo rekan-rekan IT dan Super Admin. Terima kasih telah menggunakan aplikasi pemetaan Satu Sehat ini.<br><br>
                    Jika aplikasi ini membantu mempermudah pekerjaan Anda, mohon bantuannya untuk sedikit memberikan apresiasi / "traktiran kopi" agar saya tetap semangat melakukan maintenance dan update fitur lainnya. Berapapun dukungan Anda sangat berarti bagi kelangsungan pengembangan aplikasi ini.<br><br>
                    <strong>Terima kasih banyak atas dukungannya! 🙏</strong>
                </p>
                <div class="d-grid gap-2">
                    <a href="https://saweria.co/ichsanleonhart" target="_blank" class="btn btn-primary py-2 fw-bold" style="background:linear-gradient(135deg, #4f46e5, #7c3aed); border:none;">
                        <i class="fa-solid fa-heart me-2"></i> Dukung via Saweria.co
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const CSRF_TOKEN = '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>';

$(function() {
    // 1. Init DataTables — Server-Side Pagination
    var table = $('#tabelObat').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "ajax.php?action=load_table",
            "data": function(d) {
                d.keyword         = $('#keyword_obat').val();
                d.filter_kategori = $('#filter_kategori').val();
            }
        },
        "dom": "<'row mb-3'<'col-md-2'l><'col-md-6 text-center'B><'col-md-4'f>>" +
               "<'row'<'col-md-12'tr>>" +
               "<'row'<'col-md-5'i><'col-md-7'p>>",
        "buttons": [
            { extend: 'excelHtml5', text: '<i class="fa fa-file-excel"></i> Export Excel', className: 'btn btn-success btn-sm' }
        ],
        "columns": [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3, className: "text-center", orderable: true },
            { data: 4, className: "text-center", orderable: false }
        ],
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
        "language": {
            "search": "Filter di halaman ini:",
            "processing": "<i class='fa fa-spinner fa-spin'></i> Memuat...",
            "zeroRecords": "Tidak ada data yang cocok",
            "info": "Menampilkan _START_-_END_ dari _TOTAL_ data",
            "infoEmpty": "Tidak ada data",
            "infoFiltered": "(difilter dari _MAX_ total)",
            "lengthMenu": "Tampilkan _MENU_ baris"
        }
    });

    // 2. Event Tombol Filter & Pencarian
    $('#btnCariServer').click(function() { table.ajax.reload(); });
    $('#keyword_obat').on('keyup', function(e) {
        if (e.key === 'Enter') table.ajax.reload();
    });
    $('#filter_kategori').on('change', function() {
        table.ajax.reload();
    });

    // Extension: Selalu posisikan dropdown Select2 tepat di bawah input (mencegah loncat ke paling atas modal)
    (function($) {
        if (!$.fn.select2 || !$.fn.select2.amd) return;
        try {
            var AttachBody = $.fn.select2.amd.require('select2/dropdown/attachBody');
            AttachBody.prototype._positionDropdown = function() {
                var offset = this.$container.offset();
                if (!offset) return;
                var container = {
                    height: this.$container.outerHeight(false),
                    top: offset.top,
                    bottom: offset.top + this.$container.outerHeight(false)
                };

                var $offsetParent = this.$dropdownParent;
                if ($offsetParent.css('position') === 'static') {
                    $offsetParent = $offsetParent.offsetParent();
                }
                var parentOffset = $offsetParent.offset() || { top: 0, left: 0 };

                // Posisi selalu tepat di bawah kotak input select
                var css = {
                    left: offset.left - parentOffset.left,
                    top: container.bottom - parentOffset.top,
                    width: this.$container.outerWidth(false)
                };

                this.$dropdown
                    .removeClass('select2-dropdown--above')
                    .addClass('select2-dropdown--below');
                this.$container
                    .removeClass('select2-container--above')
                    .addClass('select2-container--below');

                this.$dropdownContainer.css(css);
            };
        } catch (e) {}
    })(jQuery);

    // 3. Select2 Statis
    $('.select2-static').each(function() {
        $(this).select2({
            theme: 'bootstrap-5',
            dropdownParent: $(this).closest('.mb-3')
        });
    });
    $('.select2-tags').each(function() {
        $(this).select2({
            theme: 'bootstrap-5',
            dropdownParent: $(this).closest('.mb-3'),
            tags: true
        });
    });

    // 4. State & Helper Badge KFA
    var kfaLastSource = 'database';
    var kfaLastTerm   = '';

    function kfaSetBadge(state) {
        var badge = $('#kfa_source_badge');
        var retry = $('#btnRetryKfaObat');
        badge.removeClass('bg-secondary bg-success bg-warning bg-info text-dark');
        var isAlkes = $('input[name="mapping_type"]:checked').val() === 'alkes';
        var verText = isAlkes ? 'KFA v3 (Alkes)' : 'KFA v2 (Obat)';

        switch (state) {
            case 'loading':
                badge.addClass('bg-info')
                    .html('<i class="fa fa-spinner fa-spin me-1"></i>Menghubungi API ' + verText + ' Kemenkes...');
                retry.hide();
                break;
            case 'api':
                badge.addClass('bg-success')
                    .html('<i class="fa fa-cloud me-1"></i>Sumber: API ' + verText + ' Kemenkes');
                retry.hide();
                break;
            case 'fallback':
                badge.addClass('bg-warning text-dark')
                    .html('<i class="fa fa-triangle-exclamation me-1"></i>API ' + verText + ' gagal &mdash; menggunakan Database Lokal');
                retry.show();
                break;
            default:
                badge.addClass('bg-secondary')
                    .html('<i class="fa fa-database me-1"></i>Sumber: Database Lokal');
                retry.hide();
        }
    }

    // 5. Setup Switcher Tipe Produk (Obat vs Alkes)
    function applyMappingTypeUI(type) {
        if (type === 'alkes') {
            $('#lbl_kfa_title').html('1. Kode KFA Alkes / BMHP <span class="badge bg-success ms-1">KFA v3</span>');
            $('#select_kfa').attr('data-placeholder', 'Ketik nama alkes, merk, NIE, atau kode KFA...');
            $('#box_template_only').slideDown(150);
            $('#row_bentuk_rute').slideDown(150);
            $('#row_satuan').slideDown(150);
            $('#group_sync_vaksin').slideUp(150);
            $('#m_icon').removeClass('fa-capsules text-primary').addClass('fa-stethoscope text-success');
        } else {
            $('#lbl_kfa_title').html('1. Kode KFA Obat / Farmasi <span class="badge bg-primary ms-1">KFA v2</span>');
            $('#select_kfa').attr('data-placeholder', 'Ketik nama obat, zat aktif, atau kode KFA...');
            $('#box_template_only').slideUp(150);
            $('#alkes_metadata_card').slideUp(150);
            $('#row_bentuk_rute').slideDown(150);
            $('#row_satuan').slideDown(150);
            $('#group_sync_vaksin').slideDown(150);
            $('#m_icon').removeClass('fa-stethoscope text-success').addClass('fa-capsules text-primary');
        }
        kfaSetBadge('database');
    }

    $('input[name="mapping_type"]').on('change', function() {
        applyMappingTypeUI($(this).val());
        // Reset pilihan KFA jika user ganti tipe
        $('#select_kfa').val(null).trigger('change');
        $('#kfa_display_hidden').val('');
        $('#kfa_display_manual').val('');
        $('#alkes_metadata_card').hide();
    });

    $('#check_template_only').on('change', function() {
        $('#select_kfa').val(null).trigger('change');
    });

    // 6. Select2 KFA (AJAX Search)
    $('#select_kfa').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#select_kfa').closest('.mb-3'),
        placeholder: 'Ketik Kode atau Nama Produk KFA...',
        minimumInputLength: 2,
        ajax: {
            url: 'ajax.php?action=search_kfa',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                kfaLastTerm = params.term;
                var currentType = $('input[name="mapping_type"]:checked').val() || 'obat';
                var tmplOnly    = $('#check_template_only').is(':checked') ? 1 : 0;
                return {
                    term: params.term,
                    product_type: currentType === 'alkes' ? 'alkes' : 'farmasi',
                    template_only: tmplOnly,
                    page: params.page || 1
                };
            },
            beforeSend: function() {
                kfaSetBadge('loading');
                $('#kfa_autofill_notice').hide();
            },
            processResults: function(data, params) {
                kfaLastSource = data.source || 'database';
                if (kfaLastSource === 'api') {
                    kfaSetBadge('api');
                } else if (kfaLastSource === 'fallback') {
                    kfaSetBadge('fallback');
                } else {
                    kfaSetBadge('database');
                }
                params.page = params.page || 1;
                return {
                    results: data.results,
                    pagination: { more: data.pagination ? data.pagination.more : false }
                };
            },
            error: function() {
                kfaSetBadge('fallback');
            },
            cache: false
        }
    }).on('select2:select', function(e) {
        var d = e.params.data;
        var currentType = $('input[name="mapping_type"]:checked').val() || 'obat';

        $('#kfa_display_hidden').val(d.display_name);
        $('#kfa_display_manual').val(d.display_name);

        if (currentType === 'alkes') {
            // Isi metadata Alkes
            $('#alkes_nie').val(d.nie || '');
            $('#alkes_manufacturer').val(d.manufacturer || '');
            $('#alkes_template_code').val(d.product_template_code || '');
            $('#alkes_template_name').val(d.product_template_name || '');
            $('#alkes_farmalkes_type').val(d.farmalkes_type || 'device');

            // Tampilkan kartu metadata Alkes
            $('#alkes_preview_nama').text(d.display_name || '-');
            $('#alkes_preview_nie').text(d.nie || '(Tidak ada NIE / Template)');
            $('#alkes_preview_mfg').text(d.manufacturer || '-');
            if (d.product_template_name) {
                $('#alkes_preview_template').text(d.product_template_name + ' (' + d.product_template_code + ')');
                $('#alkes_preview_template_row').show();
            } else {
                $('#alkes_preview_template_row').hide();
            }
            $('#alkes_type_badge').text(d.farmalkes_type || 'device');
            $('#alkes_metadata_card').slideDown(150);

            // AUTO-FILL BENTUK, RUTE, & SATUAN UNTUK ALKES (User dapat mengoreksi di form jika perlu)
            var filledParts = [];

            // 1. Auto-fill Bentuk Sediaan (Form)
            if (d.form_code) {
                var formVal = d.form_code + '|' + d.form_display;
                if ($('#select_form option[value="' + formVal + '"]').length > 0) {
                    $('#select_form').val(formVal).trigger('change');
                } else if ($('#select_form option[value*="' + d.form_code + '"]').length > 0) {
                    $('#select_form option').each(function() {
                        if ($(this).val().indexOf(d.form_code) === 0) {
                            $('#select_form').val($(this).val()).trigger('change');
                            return false;
                        }
                    });
                } else {
                    // Buat option dinamis jika kode template belum ada di dropdown
                    var optForm = new Option(d.form_display + ' (' + d.form_code + ')', formVal, true, true);
                    $('#select_form').append(optForm).trigger('change');
                }
                filledParts.push('Bentuk: ' + d.form_display);
            }

            // 2. Auto-fill Rute Pemberian (Route)
            if (d.route_code) {
                $('#select_route option').each(function() {
                    if ($(this).val().indexOf(d.route_code + '|') === 0 || $(this).val() === d.route_code) {
                        $('#select_route').val($(this).val()).trigger('change');
                        filledParts.push('Rute: ' + (d.route_display || d.route_code));
                        return false;
                    }
                });
            }

            // 3. Auto-fill Satuan Numerator (Kekuatan/Ukuran)
            var numVal = d.numerator_code || '1';
            if ($('#select_numerator option[value="' + numVal + '"]').length > 0) {
                $('#select_numerator').val(numVal).trigger('change');
            } else {
                var optNum = new Option(numVal, numVal, true, true);
                $('#select_numerator').append(optNum).trigger('change');
            }

            // 4. Auto-fill Satuan Denominator (Penyajian/Kemasan)
            var denVal = d.denominator_code || 'PCS';
            if ($('#select_denominator option[value="' + denVal + '"]').length > 0) {
                $('#select_denominator').val(denVal).trigger('change');
            } else {
                var optDen = new Option(denVal, denVal, true, true);
                $('#select_denominator').append(optDen).trigger('change');
            }
            filledParts.push('Satuan: ' + numVal + ' / ' + denVal);

            var noticeText = filledParts.length > 0
                ? '<i class="fa fa-magic me-1"></i>Otomatis terisi untuk Alkes: ' + filledParts.join(' | ') + ' <small class="text-muted">(Dapat Anda ubah manual di bawah jika perlu)</small>'
                : '<i class="fa fa-check-circle me-1"></i>Metadata Alkes berhasil dimuat.';
            $('#kfa_autofill_notice').html(noticeText).show();
        } else {
            // Mode Obat: Auto-fill real-time dari API KFA Detail
            $('#alkes_metadata_card').hide();

            // Auto-check jika KFA mendeteksi tipe vaksin awal
            var isEarlyVaccine = (d.farmalkes_type === 'vaccine') || (d.text && /vaksin|vaccine/i.test(d.text));
            if (isEarlyVaccine) {
                $('#check_sync_vaksin').prop('checked', true);
            }

            $('#kfa_autofill_notice').html('<i class="fa fa-spinner fa-spin me-1"></i>Memuat detail & auto-fill dari KFA...').show();

            // Panggil API get_drug_detail untuk membaca Rute, Bentuk, dan Satuan secara akurat
            $.getJSON('ajax.php?action=get_drug_detail', { kfa_code: d.id }, function(res) {
                if (res && res.status === 'success' && res.data) {
                    var dt = res.data;
                    var filledParts = [];

                    // 1. Auto-fill Bentuk Sediaan (Form)
                    if (dt.form_code) {
                        var formVal = dt.form_code + '|' + dt.form_display;
                        if ($('#select_form option[value="' + formVal + '"]').length > 0) {
                            $('#select_form').val(formVal).trigger('change');
                            filledParts.push(dt.form_display || dt.form_code);
                        } else {
                            $('#select_form option').each(function() {
                                if ($(this).val().indexOf(dt.form_code) === 0) {
                                    $('#select_form').val($(this).val()).trigger('change');
                                    filledParts.push(dt.form_display || dt.form_code);
                                    return false;
                                }
                            });
                        }
                    }

                    // 2. Auto-fill Rute Pemberian (Route)
                    if (dt.route_code) {
                        $('#select_route option').each(function() {
                            if ($(this).val().indexOf(dt.route_code + '|') === 0 || $(this).val() === dt.route_code) {
                                $('#select_route').val($(this).val()).trigger('change');
                                filledParts.push('Rute: ' + (dt.route_display || dt.route_code));
                                return false;
                            }
                        });
                    }

                    // 3. Auto-fill Satuan Numerator (Kekuatan)
                    if (dt.numerator_code) {
                        if ($('#select_numerator option[value="' + dt.numerator_code + '"]').length > 0) {
                            $('#select_numerator').val(dt.numerator_code).trigger('change');
                        } else {
                            var optNum = new Option(dt.numerator_code, dt.numerator_code, true, true);
                            $('#select_numerator').append(optNum).trigger('change');
                        }
                    }

                    // 4. Auto-fill Satuan Denominator (Penyajian)
                    if (dt.denominator_code) {
                        if ($('#select_denominator option[value="' + dt.denominator_code + '"]').length > 0) {
                            $('#select_denominator').val(dt.denominator_code).trigger('change');
                        } else {
                            var optDen = new Option(dt.denominator_code, dt.denominator_code, true, true);
                            $('#select_denominator').append(optDen).trigger('change');
                        }
                    }

                    if (dt.numerator_code && dt.denominator_code) {
                        filledParts.push('Satuan: ' + dt.numerator_code + ' / ' + dt.denominator_code);
                    }

                    // 5. Vaccine Auto-Sync Detection
                    if (dt.is_vaccine) {
                        $('#check_sync_vaksin').prop('checked', true);
                        $('#group_sync_vaksin').addClass('border-success bg-light');
                    }

                    var noticeText = filledParts.length > 0
                        ? '<i class="fa fa-magic me-1"></i>Otomatis terisi: ' + filledParts.join(' | ')
                        : '<i class="fa fa-check me-1"></i>Detail KFA termuat.';
                    $('#kfa_autofill_notice').html(noticeText).show();
                } else {
                    $('#kfa_autofill_notice').html('<i class="fa fa-info-circle me-1"></i>Detail KFA selesai dibaca.').show();
                }
            }).fail(function() {
                $('#kfa_autofill_notice').hide();
            });
        }
    });

    // 7. Tombol Coba Ulang (Resusitasi Token)
    $('#btnRetryKfaObat').on('click', function() {
        var btn = $(this);
        btn.html('<i class="fa fa-spinner fa-spin me-1"></i>Memperbarui token...').prop('disabled', true);
        $.post('ajax.php?action=refresh_kfa_token', { csrf_token: CSRF_TOKEN }, function(r) {
            btn.prop('disabled', false);
            if (r.status === 'success') {
                kfaSetBadge('database');
                btn.hide();
                var toast = $('<span>').addClass('badge bg-success ms-1').css('font-size', '.72rem')
                    .html('<i class="fa fa-check me-1"></i>Token diperbarui! Cari kembali.')
                    .appendTo('#kfa_obat_badge_row');
                setTimeout(function() { toast.fadeOut(400, function() { $(this).remove(); }); }, 3000);
            } else {
                btn.html('<i class="fa fa-rotate-right me-1"></i>Coba Ulang API');
                Swal.fire({
                    icon: 'error', title: 'Gagal Refresh Token',
                    text: r.message,
                    confirmButtonText: 'Mengerti'
                });
            }
        }, 'json').fail(function() {
            btn.html('<i class="fa fa-rotate-right me-1"></i>Coba Ulang API').prop('disabled', false);
            Swal.fire('Error', 'Koneksi server gagal saat refresh token.', 'error');
        });
    });

    // 8. Buka Modal Mapping
    $('#tabelObat tbody').on('click', '.btn-map', function() {
        var data = $(this).data('json');
        $('#m_kode_brng').val(data.kode_brng);
        $('#m_nama_brng_label').text(data.nama_brng);
        $('#m_kode_brng_label').text(data.kode_brng);

        // Reset badge sumber & state
        kfaLastSource = 'database';
        kfaSetBadge('database');
        $('#kfa_autofill_notice').hide();
        $('#btnRetryKfaObat').hide();
        $('#alkes_metadata_card').hide();
        $('#check_template_only').prop('checked', false);
        $('#check_sync_vaksin').prop('checked', false);
        $('#kfa_display_hidden').val('');
        $('#kfa_display_manual').val('');
        $('#alkes_nie').val('');
        $('#alkes_manufacturer').val('');
        $('#alkes_template_code').val('');
        $('#alkes_template_name').val('');

        // Tentukan tipe awal (Obat vs Alkes)
        var isAlkes = data.is_alkes == 1 || (data.kdjns === 'JB03' || data.kdjns === 'JB04' || data.kode_golongan === 'G08' || data.kode_golongan === 'G10');
        if (isAlkes) {
            $('#type_alkes').prop('checked', true);
            applyMappingTypeUI('alkes');
        } else {
            $('#type_obat').prop('checked', true);
            applyMappingTypeUI('obat');
        }

        $('#select_kfa').val(null).trigger('change');

        if (data.obat_code) {
            var opt = new Option(data.obat_code + ' - ' + data.obat_display, data.obat_code, true, true);
            $('#select_kfa').append(opt).trigger('change');
            $('#kfa_display_hidden').val(data.obat_display);
            $('#kfa_display_manual').val(data.obat_display);

            if (data.is_alkes == 1) {
                // Tampilkan metadata alkes jika tersimpan
                $('#alkes_nie').val(data.nie || '');
                $('#alkes_manufacturer').val(data.manufacturer || '');
                $('#alkes_template_code').val(data.product_template_code || '');
                $('#alkes_template_name').val(data.product_template_name || '');
                $('#alkes_preview_nama').text(data.obat_display || '-');
                $('#alkes_preview_nie').text(data.nie || '(Tidak ada NIE / Template)');
                $('#alkes_preview_mfg').text(data.manufacturer || '-');
                if (data.product_template_name) {
                    $('#alkes_preview_template').text(data.product_template_name + (data.product_template_code ? ' (' + data.product_template_code + ')' : ''));
                    $('#alkes_preview_template_row').show();
                } else {
                    $('#alkes_preview_template_row').hide();
                }
                $('#alkes_metadata_card').show();
            }

            // Muat Form, Route, Numerator, Denominator untuk semua item yang sudah ter-mapping
            if (data.form_code) {
                var formVal = data.form_code + '|' + (data.form_display || '');
                if ($('#select_form option[value="' + formVal + '"]').length > 0) {
                    $('#select_form').val(formVal).trigger('change');
                } else {
                    var optF = new Option((data.form_display || data.form_code) + ' (' + data.form_code + ')', formVal, true, true);
                    $('#select_form').append(optF).trigger('change');
                }
            } else {
                $('#select_form').val('').trigger('change');
            }

            if (data.route_code) {
                $("#select_route option").each(function() {
                    if ($(this).val().indexOf(data.route_code + '|') === 0 || $(this).val() === data.route_code) {
                        $(this).prop('selected', true).trigger('change');
                    }
                });
            } else {
                $('#select_route').val('').trigger('change');
            }

            if (data.numerator_code) {
                $('#select_numerator').val(data.numerator_code).trigger('change');
                if (!$('#select_numerator').val()) {
                     var optN = new Option(data.numerator_code, data.numerator_code, true, true);
                     $('#select_numerator').append(optN).trigger('change');
                }
            } else {
                $('#select_numerator').val(null).trigger('change');
            }

            if (data.denominator_code) {
                $('#select_denominator').val(data.denominator_code).trigger('change');
                if (!$('#select_denominator').val()) {
                     var optD = new Option(data.denominator_code, data.denominator_code, true, true);
                     $('#select_denominator').append(optD).trigger('change');
                }
            } else {
                $('#select_denominator').val(null).trigger('change');
            }
        } else {
            $('#kfa_display_manual').val('');
            $('#select_form').val('').trigger('change');
            $('#select_route').val('').trigger('change');
            $('#select_numerator').val(null).trigger('change');
            $('#select_denominator').val(null).trigger('change');
            $('#alkes_nie').val('');
            $('#alkes_manufacturer').val('');
            $('#alkes_template_code').val('');
            $('#alkes_template_name').val('');
        }

        var modal = new bootstrap.Modal(document.getElementById('modalMap'));
        modal.show();
    });

    // 9. Badge Sistem Satuan
    $('#select_numerator').on('change', function() {
        var v = $(this).val();
        if (v) {
            $('#num_badge').removeClass('bg-secondary').addClass('bg-success').text('System: UCUM (http://unitsofmeasure.org)');
        } else {
            $('#num_badge').removeClass('bg-success').addClass('bg-secondary').text('System: UCUM (http://unitsofmeasure.org)');
        }
    });
    $('#select_denominator').on('change', function() {
        var v = $(this).val();
        var sys = $(this).find('option:selected').data('sys');
        if (!sys && v) {
            var liquid = ['ml','l','mg/ml','g/ml','[drp]'].includes(v.toLowerCase());
            sys = liquid ? 'UCUM' : 'DrugForm';
        }
        if (v) {
            if (sys === 'UCUM') {
                $('#den_badge').removeClass('bg-secondary bg-primary').addClass('bg-success').text('System: UCUM (http://unitsofmeasure.org)');
            } else {
                $('#den_badge').removeClass('bg-secondary bg-success').addClass('bg-primary').text('System: HL7 DrugForm');
            }
        } else {
            $('#den_badge').removeClass('bg-primary bg-success').addClass('bg-secondary').text('System: Auto');
        }
    });

    // 10. Simpan Mapping via AJAX
    $('#btnSimpanObat').click(function() {
        var btn = $(this);
        var origHtml = btn.html();
        var mappingType = $('input[name="mapping_type"]:checked').val() || 'obat';

        btn.html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...').prop('disabled', true);

        $.post('ajax.php?action=save_mapping', {
            csrf_token:            CSRF_TOKEN,
            kode_brng:             $('#m_kode_brng').val(),
            mapping_type:          mappingType,
            sync_vaksin:           $('#check_sync_vaksin').is(':checked') ? 1 : 0,
            kfa_code:              $('#select_kfa').val() || '',
            kfa_display_hidden:    $('#kfa_display_hidden').val(),
            kfa_display_manual:    $('#kfa_display_manual').val(),
            form_code:             $('#select_form').val() || '',
            route_code:            $('#select_route').val() || '',
            numerator_code:        $('#select_numerator').val() || '',
            denominator_code:      $('#select_denominator').val() || '',
            alkes_nie:             $('#alkes_nie').val() || '',
            alkes_manufacturer:    $('#alkes_manufacturer').val() || '',
            alkes_template_code:   $('#alkes_template_code').val() || '',
            alkes_template_name:   $('#alkes_template_name').val() || '',
            alkes_farmalkes_type:  $('#alkes_farmalkes_type').val() || 'device'
        }, function(resp) {
            btn.html(origHtml).prop('disabled', false);
            if (resp.status === 'success') {
                btn.html('<i class="fa fa-check"></i> Tersimpan!').addClass('btn-success').removeClass('btn-primary');
                setTimeout(function() {
                    btn.html(origHtml).removeClass('btn-success').addClass('btn-primary');
                    bootstrap.Modal.getInstance(document.getElementById('modalMap')).hide();
                    table.ajax.reload(null, false);
                }, 1200);
            } else {
                Swal.fire('Gagal!', resp.message, 'error');
            }
        }, 'json').fail(function() {
            btn.html(origHtml).prop('disabled', false);
            Swal.fire('Error!', 'Koneksi server gagal.', 'error');
        });
    });

    // Anti-Tampering (Signature check)
    setInterval(function() {
        var el = document.getElementById('footer-credit-block');
        if (!el) { document.body.innerHTML = ''; return; }
        var html = el.innerHTML;
        var cs = window.getComputedStyle(el);
        var checks = [atob('SWNoc2FuIExlb25oYXJ0'),atob('c2F3ZXJpYS5jby9pY2hzYW5sZW9uaGFydA=='),atob('NjI4NTcyNjEyMzc3Nw=='),atob('QEljaHNhbkxlb25oYXJ0'),atob('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL2ljaHNhbmxlb25oYXJ0L2FkZC1vbnNfd2ViYXBwc19raGFuemEvbWFpbi9xcmlzLWljaHNhbi5wbmc=')];
        if (cs.display==='none'||cs.visibility==='hidden'||cs.opacity==='0') { document.body.innerHTML=''; return; }
        for(var i=0;i<checks.length;i++) { if(html.indexOf(checks[i])===-1) { document.body.innerHTML=''; return; } }
    }, 3000);
});
</script>
</body>
</html>
