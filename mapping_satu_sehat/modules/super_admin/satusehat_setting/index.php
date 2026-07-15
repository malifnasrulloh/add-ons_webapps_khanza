<?php
/**
 * modules/super_admin/satusehat_setting/index.php
 * Halaman Setting Credential SatuSehat (KFA API) — Hanya Super Admin
 */
require_once '../../../conf.php';
require_once '../../../auth_check.php';
require_login();
if (empty($_SESSION['is_admin'])) {
    $_SESSION['flash_error'] = 'Halaman ini hanya dapat diakses oleh Super Admin.';
    header('Location: ../../../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setting SatuSehat — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="shortcut icon" href="../../../logo.php">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }

        /* Page header */
        .page-header {
            background: linear-gradient(135deg, #1e1b4b, #4f46e5, #7c3aed);
            color: white; border-radius: 16px; padding: 1.75rem 2rem; margin-bottom: 1.75rem;
            box-shadow: 0 8px 30px rgba(79,70,229,.35);
        }
        .page-header h4 { font-weight: 700; margin: 0; font-size: 1.3rem; }
        .page-header p  { margin: .4rem 0 0; opacity: .8; font-size: .875rem; }

        /* Cards */
        .card { border: none; border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,.08); }
        .card-header-custom {
            background: linear-gradient(90deg, #f8fafc, #f1f5f9);
            border-bottom: 2px solid #e2e8f0;
            border-radius: 14px 14px 0 0;
            padding: .9rem 1.25rem;
            display: flex; align-items: center; gap: .6rem;
        }
        .card-header-custom .section-icon {
            width: 2rem; height: 2rem; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: .85rem; color: white;
        }
        .card-header-custom h6 { margin: 0; font-weight: 700; font-size: .9rem; color: #1e293b; }

        /* Mode toggle */
        .mode-card {
            border: 2px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.25rem;
            cursor: pointer; transition: all .2s ease; background: white;
            display: flex; align-items: center; gap: 1rem;
        }
        .mode-card:hover { border-color: #a5b4fc; background: #f5f3ff; }
        .mode-card.active-db  { border-color: #6366f1; background: #eef2ff; }
        .mode-card.active-api { border-color: #059669; background: #ecfdf5; }
        .mode-card .mode-icon {
            width: 2.5rem; height: 2.5rem; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: white; flex-shrink: 0;
        }
        .mode-card .mode-title { font-weight: 700; font-size: .9rem; color: #1e293b; }
        .mode-card .mode-desc  { font-size: .78rem; color: #64748b; margin: 0; }

        /* Environment toggle */
        .env-pill {
            border: 2px solid #e2e8f0; border-radius: 50px; padding: .4rem 1.1rem;
            cursor: pointer; font-size: .82rem; font-weight: 600; transition: all .2s;
            background: white; color: #64748b;
        }
        .env-pill.active-prod { border-color: #2563eb; background: #eff6ff; color: #2563eb; }
        .env-pill.active-sand { border-color: #d97706; background: #fffbeb; color: #d97706; }

        /* Status badge */
        .conn-status {
            border-radius: 12px; padding: .65rem 1rem;
            display: flex; align-items: center; gap: .6rem;
            font-size: .85rem; font-weight: 600;
        }
        .conn-status.ok { background: #d1fae5; color: #065f46; }
        .conn-status.fail { background: #fee2e2; color: #991b1b; }
        .conn-status.idle { background: #f1f5f9; color: #64748b; }

        /* Secret field */
        .secret-wrapper { position: relative; }
        .secret-wrapper input { padding-right: 2.8rem; font-family: monospace; }
        .secret-wrapper .toggle-eye {
            position: absolute; right: .75rem; top: 50%; transform: translateY(-50%);
            cursor: pointer; color: #94a3b8; font-size: .9rem;
        }
        .secret-wrapper .toggle-eye:hover { color: #4f46e5; }

        /* Footer */
        .footer-credit { text-align: center; padding: 1.5rem; font-size: .72rem; color: #94a3b8; cursor: pointer; transition: all .2s; }
        .footer-credit:hover { color: #6366f1; }
        .footer-credit a { color: #6d28d9; text-decoration: none; font-weight: 600; }

        /* Responsive */
        @media (max-width: 768px) {
            .mode-selector { flex-direction: column; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-light bg-white border-bottom px-3 py-2 mb-4">
    <a class="text-decoration-none" href="../../../index.php" style="color:#4f46e5;font-weight:500;font-size:.875rem;">
        <i class="fa fa-arrow-left me-2"></i>Dashboard
    </a>
    <span class="navbar-brand mb-0" style="font-size:1rem;color:#4f46e5">
        <i class="fa-solid fa-gear me-2"></i>Setting SatuSehat — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?>
    </span>
    <div>
        <span class="text-muted small me-3"><i class="fa fa-user-shield me-1"></i><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?> (Super Admin)</span>
        <a href="../../../logout.php" class="text-danger small text-decoration-none"><i class="fa fa-right-from-bracket"></i> Logout</a>
    </div>
</nav>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex align-items-start gap-3">
            <div style="background:rgba(255,255,255,.15);border-radius:12px;width:3rem;height:3rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa-solid fa-shield-halved fa-lg"></i>
            </div>
            <div>
                <h4><i class="fa-solid fa-gear me-2"></i>Setting Credential SatuSehat</h4>
                <p>Atur Organization ID, Client ID, dan Client Secret untuk koneksi ke API KFA Kemenkes. Data disimpan aman di server, tidak hardcoded.</p>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- Kolom Kiri: Form Credential -->
        <div class="col-lg-7">

            <!-- Card Credential -->
            <div class="card mb-4">
                <div class="card-header-custom">
                    <div class="section-icon" style="background:linear-gradient(135deg,#4f46e5,#7c3aed)"><i class="fa fa-key"></i></div>
                    <h6>Kode Akses API SatuSehat (Credential)</h6>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-warning border-warning py-2 mb-4" style="font-size:.82rem;">
                        <i class="fa fa-triangle-exclamation me-2"></i>
                        <strong>RAHASIA.</strong> Jangan bagikan Client Secret kepada pihak yang tidak berkepentingan. Credential bersifat unik per fasyankes.
                    </div>

                    <!-- Organization ID -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Organization ID (IHS Number)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa fa-hospital text-primary"></i></span>
                            <input type="text" class="form-control" id="f_org_id" placeholder="Contoh: 100027196"
                                   autocomplete="off">
                        </div>
                        <div class="form-text">ID organisasi fasyankes Anda di platform SatuSehat.</div>
                    </div>

                    <!-- Client ID -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Client ID</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa fa-id-card text-info"></i></span>
                            <input type="text" class="form-control font-monospace" id="f_client_id"
                                   placeholder="Client ID dari dashboard SatuSehat..." autocomplete="off">
                        </div>
                    </div>

                    <!-- Client Secret -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Client Secret</label>
                        <div class="secret-wrapper">
                            <input type="password" class="form-control font-monospace" id="f_client_secret"
                                   placeholder="Kosongkan jika tidak ingin mengubah secret yang tersimpan..." autocomplete="new-password">
                            <i class="fa fa-eye toggle-eye" id="toggleSecret"></i>
                        </div>
                        <div class="form-text" id="secretHintText">Kosongkan untuk mempertahankan Client Secret yang sudah tersimpan.</div>
                    </div>

                    <!-- Environment Toggle -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary small d-block">Environment</label>
                        <input type="hidden" id="f_environment" value="production">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="env-pill active-prod" id="btnEnvProduction"
                                    onclick="setEnv('production')">
                                <i class="fa fa-circle-check me-1"></i>Production
                            </button>
                            <button type="button" class="env-pill" id="btnEnvSandbox"
                                    onclick="setEnv('sandbox')">
                                <i class="fa fa-flask me-1"></i>Sandbox (Pengujian)
                            </button>
                        </div>
                        <div class="form-text mt-1" id="envDesc">
                            <span class="text-primary fw-semibold">Production</span>: Terhubung ke API KFA resmi Kemenkes. Gunakan untuk operasional nyata.
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-outline-info" id="btnTest" onclick="testConnection()">
                            <i class="fa fa-plug me-1"></i>Test Koneksi
                        </button>
                        <button class="btn btn-primary px-4" id="btnSave" onclick="saveCredential()">
                            <i class="fa fa-save me-1"></i>Simpan Credential
                        </button>
                    </div>
                </div>
            </div>

            <!-- Card Credential LOINC -->
            <div class="card mb-4" id="card-loinc">
                <div class="card-header-custom">
                    <div class="section-icon" style="background:linear-gradient(135deg,#db2777,#be185d)"><i class="fa fa-flask"></i></div>
                    <h6>Kode Akses FHIR Terminology (LOINC)</h6>
                </div>
                <div class="card-body p-4">
                    <!-- README -->
                    <div class="alert alert-info" style="font-size:.85rem;">
                        <i class="fa fa-circle-info me-2"></i>
                        <strong>PENTING (README):</strong> Untuk dapat menggunakan fitur <em>live search</em> pemetaan Laboratorium dan Radiologi ke kode LOINC, aplikasi memerlukan akses ke <strong>Public FHIR Terminology Server</strong>.
                        <br><br>
                        Silakan buat akun pengguna secara gratis di situs <a href="https://loinc.org" target="_blank" class="fw-bold">loinc.org</a>. Setelah mendaftar dan memverifikasi email, masukkan Username dan Password akun tersebut di bawah ini agar sistem RS dapat terhubung secara real-time ke <code>fhir.loinc.org</code>.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">LOINC Username (Email)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa fa-user text-danger"></i></span>
                            <input type="text" class="form-control" id="f_loinc_username" placeholder="Email akun loinc.org..."
                                   autocomplete="off">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary small">LOINC Password</label>
                        <div class="secret-wrapper">
                            <input type="password" class="form-control font-monospace" id="f_loinc_password"
                                   placeholder="Kosongkan jika tidak ingin mengubah password..." autocomplete="new-password">
                            <i class="fa fa-eye toggle-eye" id="toggleLoincSecret"></i>
                        </div>
                        <div class="form-text" id="loincSecretHintText">Kosongkan untuk tetap menggunakan password lama.</div>
                    </div>
                </div>
            </div>

            <!-- Card Mode Pencarian KFA -->
            <div class="card">
                <div class="card-header-custom">
                    <div class="section-icon" style="background:linear-gradient(135deg,#059669,#10b981)"><i class="fa fa-search"></i></div>
                    <h6>Mode Pencarian KFA (Mapping Obat)</h6>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-3">Pilih sumber data untuk pencarian KFA saat melakukan mapping obat.</p>
                    <input type="hidden" id="f_kfa_search_mode" value="database">

                    <div class="d-flex gap-3 flex-wrap mode-selector">
                        <!-- Database Lokal -->
                        <div class="mode-card active-db flex-fill" id="modeDbCard" onclick="setSearchMode('database')">
                            <div class="mode-icon" style="background:linear-gradient(135deg,#4f46e5,#6366f1)">
                                <i class="fa fa-database"></i>
                            </div>
                            <div>
                                <div class="mode-title">Database Lokal</div>
                                <p class="mode-desc">Cari dari tabel <code>satu_sehat_ref_kfa</code> internal. Cepat, tidak butuh internet, data statis.</p>
                            </div>
                            <i class="fa fa-check-circle ms-auto text-primary" id="checkDb" style="font-size:1.2rem;"></i>
                        </div>

                        <!-- API KFA -->
                        <div class="mode-card flex-fill" id="modeApiCard" onclick="setSearchMode('api')">
                            <div class="mode-icon" style="background:linear-gradient(135deg,#059669,#10b981)">
                                <i class="fa fa-cloud"></i>
                            </div>
                            <div>
                                <div class="mode-title">API KFA Kemenkes <span class="badge bg-success ms-1" style="font-size:.65rem;">REAL-TIME</span></div>
                                <p class="mode-desc">Data langsung dari API resmi Kemenkes. Auto-fill rute, bentuk sediaan &amp; satuan. Butuh internet &amp; credential valid.</p>
                            </div>
                            <i class="fa fa-check-circle ms-auto text-success" id="checkApi" style="font-size:1.2rem;display:none;"></i>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-success px-4" id="btnSaveMode" onclick="saveMode()">
                            <i class="fa fa-save me-1"></i>Simpan Mode
                        </button>
                    </div>
                </div>
            </div>

        </div><!-- /col-lg-7 -->

        <!-- Kolom Kanan: Status & Info -->
        <div class="col-lg-5">

            <!-- Card Status Koneksi -->
            <div class="card mb-4">
                <div class="card-header-custom">
                    <div class="section-icon" style="background:linear-gradient(135deg,#0891b2,#06b6d4)"><i class="fa fa-signal"></i></div>
                    <h6>Status Koneksi API</h6>
                </div>
                <div class="card-body p-4">
                    <div class="conn-status idle mb-3" id="connStatusBox">
                        <i class="fa fa-circle-dot"></i>
                        <span id="connStatusText">Belum ditest. Klik "Test Koneksi" untuk memulai.</span>
                    </div>

                    <div id="connDetail" style="display:none;">
                        <div class="small text-muted mb-1 fw-semibold">Detail Token:</div>
                        <div class="font-monospace small bg-light border rounded p-2" id="connTokenPreview" style="word-break:break-all;"></div>
                    </div>

                    <hr>

                    <!-- Info credential tersimpan -->
                    <div class="small text-muted fw-semibold mb-2">Credential Tersimpan:</div>
                    <table class="table table-sm table-bordered" id="credSummaryTable" style="font-size:.8rem;">
                        <tbody>
                            <tr><th class="text-muted fw-normal" style="width:40%">Organization ID</th><td id="sum_org" class="font-monospace">—</td></tr>
                            <tr><th class="text-muted fw-normal">Client ID</th><td id="sum_cid" class="font-monospace" style="word-break:break-all;">—</td></tr>
                            <tr><th class="text-muted fw-normal">Client Secret</th><td id="sum_csec" class="font-monospace">—</td></tr>
                            <tr><th class="text-muted fw-normal">Environment</th><td id="sum_env">—</td></tr>
                            <tr><th class="text-muted fw-normal">Mode Search</th><td id="sum_mode">—</td></tr>
                            <tr><th class="text-muted fw-normal">Diperbarui</th><td id="sum_updated">—</td></tr>
                            <tr><th class="text-muted fw-normal">Oleh</th><td id="sum_by">—</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Card Admin Referensi -->
            <div class="card mb-4">
                <div class="card-header-custom">
                    <div class="section-icon" style="background:linear-gradient(135deg,#1e1b4b,#4f46e5)"><i class="fa fa-database"></i></div>
                    <h6>Admin Referensi & Master Data</h6>
                </div>
                <div class="card-body p-4 text-center">
                    <p class="text-muted small mb-3">Kelola secara manual ribuan baris data master referensi KFA, LOINC, dan SNOMED-CT (CRUD).</p>
                    <a href="../admin_ref/index.php" target="_blank" class="btn btn-outline-primary w-100 rounded-pill">
                        <i class="fa fa-external-link-alt me-2"></i>Buka Admin Referensi
                    </a>
                </div>
            </div>

            <!-- Card Panduan -->
            <div class="card">
                <div class="card-header-custom">
                    <div class="section-icon" style="background:linear-gradient(135deg,#7c3aed,#a78bfa)"><i class="fa fa-circle-info"></i></div>
                    <h6>Panduan & Informasi</h6>
                </div>
                <div class="card-body p-4">
                    <ul class="list-unstyled small text-muted" style="line-height:1.8;">
                        <li><i class="fa fa-check-circle text-success me-2"></i>Credential didapatkan dari dashboard <strong>SatuSehat Platform</strong> setelah fasyankes terdaftar.</li>
                        <li><i class="fa fa-check-circle text-success me-2"></i>Token OAuth2 berlaku <strong>1 jam</strong> dan akan otomatis di-refresh.</li>
                        <li><i class="fa fa-check-circle text-success me-2"></i>Jika API tidak tersedia, sistem otomatis <strong>fallback ke database lokal</strong>.</li>
                        <li><i class="fa fa-check-circle text-success me-2"></i>Mode API mengaktifkan <strong>auto-fill</strong> rute, bentuk sediaan, dan satuan saat mapping.</li>
                        <li><i class="fa fa-triangle-exclamation text-warning me-2"></i>Gunakan <strong>Sandbox</strong> hanya untuk pengujian. Untuk operasional gunakan <strong>Production</strong>.</li>
                    </ul>
                    <hr>
                    <div class="small">
                        <a href="https://satusehat.kemkes.go.id/platform" target="_blank" class="text-decoration-none text-primary">
                            <i class="fa fa-external-link-alt me-1"></i>SatuSehat Platform (Kemenkes)
                        </a>
                    </div>
                </div>
            </div>

        </div><!-- /col-lg-5 -->
    </div><!-- /row -->
</div><!-- /container -->

<!-- Footer copyright (Anti-Tampering — JANGAN DIHAPUS) -->
<div class="footer-credit" id="footer-credit-block" onclick="new bootstrap.Modal(document.getElementById('modalSaweria')).show();">
    &copy; <a href="https://saweria.co/ichsanleonhart" target="_blank" onclick="event.stopPropagation();">Ichsan Leonhart</a> &nbsp;·&nbsp;
    <a href="https://wa.me/6285726123777" target="_blank" onclick="event.stopPropagation();">6285726123777</a> &nbsp;·&nbsp;
    <a href="https://t.me/IchsanLeonhart" target="_blank" onclick="event.stopPropagation();">@IchsanLeonhart</a> &nbsp;·&nbsp;
    <a href="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" target="_blank" onclick="event.stopPropagation();">QRIS Donasi</a>
    — <a href="https://saweria.co/ichsanleonhart" target="_blank" onclick="event.stopPropagation();">saweria.co/ichsanleonhart</a>
</div>
<div class="modal fade" id="modalSaweria" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">
            <div class="modal-header border-0 pb-0"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center pt-0 pb-4 px-4">
                <div class="mb-3"><img src="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" class="img-fluid rounded-3 shadow-sm" style="max-width:280px;" alt="QRIS Donasi"></div>
                <h5 class="fw-bold text-primary mb-3">Apresiasi &amp; Dukungan</h5>
                <p class="text-muted small px-2 mb-4" style="line-height:1.6;">Terima kasih atas dukungannya! 🙏</p>
                <div class="d-grid gap-2">
                    <a href="https://saweria.co/ichsanleonhart" target="_blank" class="btn btn-primary py-2 fw-bold" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border:none;">
                        <i class="fa-solid fa-heart me-2"></i>Dukung via Saweria.co
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const CSRF_TOKEN = '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>';
const AJAX_URL   = 'ajax.php';

// ===================================================
// Helpers
// ===================================================
function setEnv(env) {
    $('#f_environment').val(env);
    if (env === 'production') {
        $('#btnEnvProduction').addClass('active-prod').removeClass('active-sand');
        $('#btnEnvSandbox').addClass('env-pill').removeClass('active-sand active-prod');
        $('#envDesc').html('<span class="text-primary fw-semibold">Production</span>: Terhubung ke API KFA resmi Kemenkes. Gunakan untuk operasional nyata.');
    } else {
        $('#btnEnvSandbox').addClass('active-sand').removeClass('active-prod');
        $('#btnEnvProduction').addClass('env-pill').removeClass('active-sand active-prod');
        $('#envDesc').html('<span class="text-warning fw-semibold">Sandbox</span>: Endpoint pengujian. <strong>Jangan digunakan untuk operasional</strong>.');
    }
}

function setSearchMode(mode) {
    $('#f_kfa_search_mode').val(mode);
    if (mode === 'database') {
        $('#modeDbCard').addClass('active-db').removeClass('active-api');
        $('#modeApiCard').removeClass('active-db active-api');
        $('#checkDb').show(); $('#checkApi').hide();
    } else {
        $('#modeApiCard').addClass('active-api').removeClass('active-db');
        $('#modeDbCard').removeClass('active-db active-api');
        $('#checkApi').show(); $('#checkDb').hide();
    }
}

function setConnStatus(type, text) {
    var box = $('#connStatusBox');
    box.removeClass('ok fail idle');
    if (type === 'ok') {
        box.addClass('ok').html('<i class="fa fa-circle-check"></i><span>' + text + '</span>');
    } else if (type === 'fail') {
        box.addClass('fail').html('<i class="fa fa-circle-xmark"></i><span>' + text + '</span>');
    } else {
        box.addClass('idle').html('<i class="fa fa-circle-dot"></i><span>' + text + '</span>');
    }
}

function loadCredential() {
    $.get(AJAX_URL + '?action=load', function(r) {
        if (r.status !== 'success') return;
        var d = r.data;
        // Isi form
        $('#f_org_id').val(d.organization_id || '');
        $('#f_client_id').val(d.client_id || '');
        $('#f_client_secret').val(''); // kosongkan, tampilkan hint
        if (d.client_secret_set) {
            $('#secretHintText').html('Secret tersimpan: <code>' + d.client_secret_masked + '</code>. Kosongkan jika tidak ingin mengubah.');
        }
        setEnv(d.environment || 'production');
        setSearchMode(d.kfa_search_mode || 'database');

        // Summary tabel
        $('#sum_org').text(d.organization_id || '—');
        $('#sum_cid').text(d.client_id ? d.client_id.substring(0,20) + '...' : '—');
        $('#sum_csec').text(d.client_secret_set ? d.client_secret_masked : '— (belum diset)');
        var envLabel = d.environment === 'sandbox' ? '⚠️ Sandbox' : '✅ Production';
        var modeLabel = d.kfa_search_mode === 'api' ? '🌐 API KFA Kemenkes (Real-time)' : '🗄️ Database Lokal';
        $('#sum_env').html(envLabel);
        $('#sum_mode').html(modeLabel);
        $('#sum_updated').text(d.updated_at || '—');
        $('#sum_by').text(d.updated_by || '—');
    }, 'json');
}

function testConnection() {
    var btn = $('#btnTest'), orig = btn.html();
    btn.html('<i class="fa fa-spinner fa-spin me-1"></i>Sedang test...').prop('disabled', true);
    setConnStatus('idle', 'Sedang menghubungkan ke API Kemenkes...');
    $('#connDetail').hide();

    $.post(AJAX_URL + '?action=test_connection', {
        csrf_token:      CSRF_TOKEN,
        organization_id: $('#f_org_id').val().trim(),
        client_id:       $('#f_client_id').val().trim(),
        client_secret:   $('#f_client_secret').val().trim(),
        loinc_username:  $('#f_loinc_username').val().trim(),
        loinc_password:  $('#f_loinc_password').val().trim(),
        environment:     $('#f_environment').val(),
    }, function(r) {
        btn.html(orig).prop('disabled', false);
        if (r.status === 'success') {
            setConnStatus('ok', r.message);
            if (r.token_preview) {
                $('#connTokenPreview').text(r.token_preview);
                $('#connDetail').show();
            }
        } else {
            setConnStatus('fail', r.message);
        }
    }, 'json').fail(function() {
        btn.html(orig).prop('disabled', false);
        setConnStatus('fail', 'Koneksi ke server PHP gagal. Periksa server XAMPP.');
    });
}

function saveCredential() {
    var btn = $('#btnSave'), orig = btn.html();
    btn.html('<i class="fa fa-spinner fa-spin me-1"></i>Menyimpan...').prop('disabled', true);

    $.post(AJAX_URL + '?action=save', {
        csrf_token:      CSRF_TOKEN,
        organization_id: $('#f_org_id').val().trim(),
        client_id:       $('#f_client_id').val().trim(),
        client_secret:   $('#f_client_secret').val().trim(),
        loinc_username:  $('#f_loinc_username').val().trim(),
        loinc_password:  $('#f_loinc_password').val().trim(),
        environment:     $('#f_environment').val(),
        kfa_search_mode: $('#f_kfa_search_mode').val(),
    }, function(r) {
        btn.html(orig).prop('disabled', false);
        if (r.status === 'success') {
            Swal.fire({ icon: 'success', title: 'Tersimpan!', text: r.message, timer: 1800, showConfirmButton: false });
            loadCredential(); // Refresh summary
        } else {
            Swal.fire('Gagal!', r.message, 'error');
        }
    }, 'json').fail(function() {
        btn.html(orig).prop('disabled', false);
        Swal.fire('Error!', 'Koneksi server gagal.', 'error');
    });
}

function saveMode() {
    var btn = $('#btnSaveMode'), orig = btn.html();
    btn.html('<i class="fa fa-spinner fa-spin me-1"></i>Menyimpan...').prop('disabled', true);

    $.post(AJAX_URL + '?action=save', {
        csrf_token:      CSRF_TOKEN,
        organization_id: $('#f_org_id').val().trim(),
        client_id:       $('#f_client_id').val().trim(),
        client_secret:   '', // kosong = tidak ubah secret
        loinc_username:  $('#f_loinc_username').val().trim(),
        loinc_password:  '', // kosong = tidak ubah
        environment:     $('#f_environment').val(),
        kfa_search_mode: $('#f_kfa_search_mode').val(),
    }, function(r) {
        btn.html(orig).prop('disabled', false);
        if (r.status === 'success') {
            var mode = $('#f_kfa_search_mode').val();
            var modeLabel = mode === 'api' ? 'API KFA Kemenkes (Real-time)' : 'Database Lokal';
            Swal.fire({ icon: 'success', title: 'Mode Diubah!', text: 'Mode pencarian KFA: ' + modeLabel, timer: 1800, showConfirmButton: false });
            loadCredential();
        } else {
            Swal.fire('Gagal!', r.message, 'error');
        }
    }, 'json').fail(function() {
        btn.html(orig).prop('disabled', false);
        Swal.fire('Error!', 'Koneksi server gagal.', 'error');
    });
}

// Toggle show/hide secret LOINC
$('#toggleLoincSecret').on('click', function() {
    var inp = $('#f_loinc_password');
    if (inp.attr('type') === 'password') {
        inp.attr('type', 'text');
        $(this).removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        inp.attr('type', 'password');
        $(this).removeClass('fa-eye-slash').addClass('fa-eye');
    }
});

// Toggle show/hide secret
$('#toggleSecret').on('click', function() {
    var inp = $('#f_client_secret');
    if (inp.attr('type') === 'password') {
        inp.attr('type', 'text');
        $(this).removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        inp.attr('type', 'password');
        $(this).removeClass('fa-eye-slash').addClass('fa-eye');
    }
});

// Load saat halaman dibuka
$(function() { loadCredential(); });

// Anti-Tampering
setInterval(function(){var el=document.getElementById('footer-credit-block');if(!el){document.body.innerHTML='';return;}var html=el.innerHTML,cs=window.getComputedStyle(el),checks=[atob('SWNoc2FuIExlb25oYXJ0'),atob('c2F3ZXJpYS5jby9pY2hzYW5sZW9uaGFydA=='),atob('NjI4NTcyNjEyMzc3Nw=='),atob('QEljaHNhbkxlb25oYXJ0'),atob('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL2ljaHNhbmxlb25oYXJ0L2FkZC1vbnNfd2ViYXBwc19raGFuemEvbWFpbi9xcmlzLWljaHNhbi5wbmc=')];if(cs.display==='none'||cs.visibility==='hidden'||cs.opacity==='0'){document.body.innerHTML='';return;}for(var i=0;i<checks.length;i++){if(html.indexOf(checks[i])===-1){document.body.innerHTML='';return;}}},3000);
</script>
</body>
</html>
