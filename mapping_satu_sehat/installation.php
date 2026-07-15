<?php
/**
 * installation.php — Web Installer mapping_satu_sehat
 * Dibatasi HANYA untuk Super Admin.
 */
require_once 'conf.php';
require_once 'auth_check.php';
require_login();

// Hanya Super Admin yang boleh akses installer
if (empty($_SESSION['is_admin'])) {
    die("<div style='padding:20px; text-align:center; font-family:sans-serif;'>
            <h2 style='color:#ef4444;'>Akses Ditolak</h2>
            <p>Hanya Super Admin yang diizinkan mengakses Database Installer.</p>
            <a href='index.php'>Kembali ke Dashboard</a>
         </div>");
}

$action = $_GET['action'] ?? '';

// =========================================================
// ACTION: RUN IMPORT
// =========================================================
if ($action === 'run_import') {
    // Karena file kfa dan loinc ukurannya cukup besar, matikan time limit & memory limit
    set_time_limit(0);
    ini_set('memory_limit', '-1');
    header('Content-Type: application/json');

    $step = $_GET['step'] ?? '';
    $sql_dir = __DIR__ . DIRECTORY_SEPARATOR . 'tambahan_table' . DIRECTORY_SEPARATOR;

    try {
        if ($step === 'form') {
            $sql = file_get_contents($sql_dir . 'satu_sehat_ref_form.sql');
            $pdo->exec($sql);
            echo json_encode(['status' => 'success', 'message' => 'satu_sehat_ref_form berhasil!']);
            exit;
        }
        if ($step === 'route') {
            $sql = file_get_contents($sql_dir . 'satu_sehat_ref_route.sql');
            $pdo->exec($sql);
            echo json_encode(['status' => 'success', 'message' => 'satu_sehat_ref_route berhasil!']);
            exit;
        }
        if ($step === 'snomed') {
            $sql = file_get_contents($sql_dir . 'satu_sehat_ref_snomed.sql');
            $pdo->exec($sql);
            echo json_encode(['status' => 'success', 'message' => 'satu_sehat_ref_snomed berhasil!']);
            exit;
        }
        if ($step === 'kfa') {
            // File KFA sangat besar. Kita eksekusi string SQL-nya sekaligus karena biasanya MySQL mampu.
            $sql = file_get_contents($sql_dir . 'satu_sehat_ref_kfa.sql');
            $pdo->exec($sql);
            echo json_encode(['status' => 'success', 'message' => 'satu_sehat_ref_kfa berhasil diimpor!']);
            exit;
        }
        if ($step === 'loinc') {
            // File LOINC juga sangat besar.
            $sql = file_get_contents($sql_dir . 'satu_sehat_ref_loinc.sql');
            $pdo->exec($sql);
            echo json_encode(['status' => 'success', 'message' => 'satu_sehat_ref_loinc berhasil diimpor!']);
            exit;
        }
        if ($step === 'numerator') {
            $sql = file_get_contents($sql_dir . 'satu_sehat_ref_numerator.sql');
            $pdo->exec($sql);
            echo json_encode(['status' => 'success', 'message' => 'satu_sehat_ref_numerator berhasil!']);
            exit;
        }
        if ($step === 'denominator') {
            $sql = file_get_contents($sql_dir . 'satu_sehat_ref_denominator.sql');
            $pdo->exec($sql);
            echo json_encode(['status' => 'success', 'message' => 'satu_sehat_ref_denominator berhasil!']);
            exit;
        }
        if ($step === 'tables') {
            // Create target mapping tables
            $tables = [
                "CREATE TABLE IF NOT EXISTS `satu_sehat_mapping_obat` (
                    `kode_brng` varchar(15) NOT NULL, `obat_code` varchar(30) DEFAULT NULL, `obat_system` varchar(100) NOT NULL DEFAULT 'http://sys-ids.kemkes.go.id/kfa', `obat_display` varchar(200) DEFAULT NULL, `form_code` varchar(50) DEFAULT NULL, `form_system` varchar(100) DEFAULT NULL, `form_display` varchar(100) DEFAULT NULL, `route_code` varchar(50) DEFAULT NULL, `route_system` varchar(100) DEFAULT NULL, `route_display` varchar(100) DEFAULT NULL, `denominator_code` varchar(20) DEFAULT NULL, `denominator_system` varchar(100) DEFAULT NULL, PRIMARY KEY (`kode_brng`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1",

                "CREATE TABLE IF NOT EXISTS `satu_sehat_mapping_lab` (
                    `id_template` int(11) NOT NULL, `code` varchar(50) DEFAULT NULL, `system` varchar(100) DEFAULT 'http://loinc.org', `display` varchar(255) DEFAULT NULL, `sampel_code` varchar(50) DEFAULT NULL, `sampel_system` varchar(100) DEFAULT 'http://snomed.info/sct', `sampel_display` varchar(255) DEFAULT NULL, PRIMARY KEY (`id_template`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1",

                "CREATE TABLE IF NOT EXISTS `satu_sehat_mapping_radiologi` (
                    `kd_jenis_prw` varchar(15) NOT NULL, `code` varchar(15) DEFAULT NULL, `system` varchar(100) NOT NULL DEFAULT 'http://loinc.org', `display` varchar(80) DEFAULT NULL, `sampel_code` varchar(15) NOT NULL DEFAULT '', `sampel_system` varchar(100) NOT NULL DEFAULT 'http://snomed.info/sct', `sampel_display` varchar(80) NOT NULL DEFAULT '', PRIMARY KEY (`kd_jenis_prw`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1",

                "CREATE TABLE IF NOT EXISTS `satu_sehat_mapping_vaksin` (
                    `kode_brng` varchar(15) NOT NULL, `vaksin_code` varchar(15) DEFAULT NULL, `vaksin_system` varchar(100) NOT NULL DEFAULT 'http://sys-ids.kemkes.go.id/kfa', `vaksin_display` varchar(80) DEFAULT NULL, `route_code` varchar(30) DEFAULT NULL, `route_system` varchar(100) DEFAULT NULL, `route_display` varchar(80) DEFAULT NULL, `dose_quantity_code` varchar(15) DEFAULT NULL, `dose_quantity_system` varchar(80) DEFAULT NULL, `dose_quantity_unit` varchar(15) DEFAULT NULL, PRIMARY KEY (`kode_brng`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1"
            ];
            foreach ($tables as $t) {
                $pdo->exec($t);
            }
            echo json_encode(['status' => 'success', 'message' => 'Tabel mapping utama berhasil dibuat!']);
            exit;
        }

        echo json_encode(['status' => 'error', 'message' => 'Langkah tidak dikenali.']);
        exit;
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        // Ignore "table already exists" error or standard warning if any.
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit;
    }
}

// Cek status database saat ini
$missing_mapping_tables = [];
$check_mapping_tables = [
    'satu_sehat_mapping_obat', 'satu_sehat_mapping_lab', 'satu_sehat_mapping_radiologi', 'satu_sehat_mapping_vaksin'
];

$missing_ref_tables = [];
$check_ref_tables = [
    'satu_sehat_ref_form', 'satu_sehat_ref_route', 'satu_sehat_ref_kfa', 'satu_sehat_ref_loinc', 'satu_sehat_ref_snomed',
    'satu_sehat_ref_numerator', 'satu_sehat_ref_denominator'
];

// Cek mapping
foreach ($check_mapping_tables as $tb) {
    try {
        $res = $pdo->query("SHOW TABLES LIKE '$tb'");
        if ($res->rowCount() === 0) $missing_mapping_tables[] = $tb;
    } catch (Exception $e) { $missing_mapping_tables[] = $tb; }
}

// Cek referensi
foreach ($check_ref_tables as $tb) {
    try {
        $res = $pdo->query("SHOW TABLES LIKE '$tb'");
        if ($res->rowCount() === 0) $missing_ref_tables[] = $tb;
    } catch (Exception $e) { $missing_ref_tables[] = $tb; }
}

$is_mapping_safe = (count($missing_mapping_tables) === 0);
$is_ref_safe     = (count($missing_ref_tables) === 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database Installer — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="shortcut icon" href="logo.php">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        .hero { background: linear-gradient(135deg, #047857, #10b981); color: white; padding: 2.5rem 0; margin-bottom: 2rem; border-radius: 0 0 24px 24px; box-shadow: 0 10px 30px rgba(16,185,129,0.2); }
        .hero h2 { font-weight: 800; font-size: 1.8rem;}
        .nav-back { color: #a7f3d0; text-decoration: none; font-size: 0.9rem; font-weight: 500; display: inline-block; margin-bottom: 1rem; transition: color 0.2s;}
        .nav-back:hover { color: #fff; }
        
        .card { border: none; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        .step-box { padding: 1rem 1.5rem; border-radius: 12px; background: #f1f5f9; margin-bottom: 0.75rem; display: flex; align-items: center; justify-content: space-between; border: 1px solid #e2e8f0; }
        .step-box.active { background: #eff6ff; border-color: #bfdbfe; }
        .step-box.success { background: #f0fdf4; border-color: #bbf7d0; }
        .step-box.error { background: #fef2f2; border-color: #fecaca; }
        
        .status-icon { font-size: 1.25rem; }
        .status-icon.success { color: #22c55e; }
        .status-icon.error { color: #ef4444; }
        
        .footer-credit { text-align: center; padding: 2rem; font-size: .72rem; color: #94a3b8; cursor: pointer; transition: all 0.2s; }
        .footer-credit:hover { color: #6366f1; background: rgba(99, 102, 241, 0.05); }
        .footer-credit a { color: #059669; text-decoration: none; font-weight: 600; }
        .footer-credit a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="hero">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <a href="index.php" class="nav-back"><i class="fa fa-arrow-left me-2"></i>Kembali ke Dashboard</a>
                <h2 class="mb-1"><i class="fa-solid fa-database me-2 text-emerald-300"></i>Web Installer Database</h2>
                <p class="mb-0 text-emerald-100">Setup tabel referensi & mapping <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?> (Khusus Super Admin)</p>

                <?php if (!$is_mapping_safe || !$is_ref_safe): ?>
                <div class="alert alert-warning border-0 mt-3 mb-0 py-2 small d-flex align-items-center" style="background: rgba(255,255,255,0.2); color: #fff; border-radius: 10px;">
                    <i class="fa fa-triangle-exclamation me-2"></i>
                    Status: Beberapa tabel referensi belum terdeteksi. Silakan pilih mode instalasi.
                </div>
                <?php endif; ?>
            </div>
            <div class="text-end d-none d-md-block">
                <i class="fa fa-shield-halved fa-2x text-emerald-200"></i>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card p-4">
                <?php if ($is_mapping_safe && $is_ref_safe): ?>
                    <div class="text-center py-4">
                        <i class="fa fa-check-circle fa-4x text-success mb-3"></i>
                        <h4 class="fw-bold">Database Sudah Lengkap!</h4>
                        <p class="text-muted">Semua tabel mapping dan tabel referensi (Kamus KFA, LOINC, dll) sudah terinstall. Sistem siap 100%.</p>
                        <hr>
                        <a href="index.php" class="btn btn-emerald px-4 shadow-sm" style="background:#10b981; color:white; border-radius:20px; font-weight:600;">Kembali ke Dashboard</a>
                    </div>
                <?php else: ?>
                    <div class="text-center mb-4">
                        <h4 class="fw-bold text-dark">Pilih Mode Instalasi Database</h4>
                        <p class="text-muted">Silakan pilih opsi sesuai dengan kebutuhan operasional IT Anda.</p>
                    </div>

                    <div class="row g-4">
                        <!-- OPSI 1: CLOUD MODE -->
                        <div class="col-md-6">
                            <div class="p-4 border rounded-4 h-100 position-relative bg-white" style="box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                                <?php if ($is_mapping_safe): ?>
                                    <div class="position-absolute top-0 end-0 mt-3 me-3">
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle"><i class="fa fa-check me-1"></i>Sudah Terinstall</span>
                                    </div>
                                <?php endif; ?>
                                <div style="width: 50px; height: 50px; background: #eff6ff; color: #3b82f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem;">
                                    <i class="fa fa-cloud"></i>
                                </div>
                                <h5 class="fw-bold">Mode Cloud (API 100%)</h5>
                                <div class="badge bg-primary mb-3">Rekomendasi</div>
                                <p class="text-muted small" style="line-height: 1.6;">
                                    Tipe instalasi <strong>super cepat (&lt; 1 detik)</strong>. Hanya menginstall tabel kosong untuk menyimpan hasil mapping RS Anda.
                                </p>
                                <ul class="text-muted small mb-4" style="line-height: 1.6; padding-left: 1.2rem;">
                                    <li>Tidak mengunduh kamus data kemenkes (hemat ratusan MB).</li>
                                    <li><strong>Syarat Utama:</strong> Anda <u>WAJIB</u> mensetting Credential API SatuSehat di menu Super Admin setelah ini agar pencarian KFA berjalan lancar lewat internet.</li>
                                </ul>
                                <button class="btn btn-primary w-100 rounded-pill fw-bold" id="btnInstallCepat" <?= $is_mapping_safe ? 'disabled' : '' ?>>
                                    <?= $is_mapping_safe ? 'Sudah Terinstall' : '<i class="fa fa-bolt me-2"></i>Install Tabel Mapping Saja' ?>
                                </button>
                            </div>
                        </div>

                        <!-- OPSI 2: HIBRID MODE -->
                        <div class="col-md-6">
                            <div class="p-4 border rounded-4 h-100 position-relative bg-white" style="box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                                <div style="width: 50px; height: 50px; background: #f0fdf4; color: #10b981; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem;">
                                    <i class="fa fa-database"></i>
                                </div>
                                <h5 class="fw-bold">Mode Hibrid (Lokal + API)</h5>
                                <div class="badge bg-secondary mb-3">Fallback System</div>
                                <p class="text-muted small" style="line-height: 1.6;">
                                    Tipe instalasi <strong>penuh (butuh waktu agak lama)</strong>. Menginstall tabel mapping sekaligus memasukkan puluhan ribu kamus KFA & LOINC ke database RS.
                                </p>
                                <ul class="text-muted small mb-4" style="line-height: 1.6; padding-left: 1.2rem;">
                                    <li>Aplikasi tetap bisa mencari obat meskipun tidak disetting koneksi API internet.</li>
                                    <li>Membutuhkan waktu untuk import file SQL besar ke MySQL/MariaDB. Jangan tutup halaman selama proses berlangsung.</li>
                                </ul>
                                <button class="btn btn-success w-100 rounded-pill fw-bold" id="btnInstallFull">
                                    <i class="fa fa-download me-2"></i>Install Full + Referensi
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Area (Hidden initially) -->
                    <div id="progress-area" class="mt-5" style="display:none;">
                        <h6 class="fw-bold border-bottom pb-2 mb-3">Status Instalasi</h6>
                        <div id="install-steps">
                            <div class="step-box" id="step-tables" data-step="tables">
                                <div class="fw-semibold text-secondary">A. Membuat Tabel Mapping (Obat, Lab, Rad, Vaksin)</div>
                                <i class="fa fa-circle status-icon pending"></i>
                            </div>
                            <!-- Referensi steps start hidden -->
                            <div class="step-box ref-step" id="step-form" data-step="form" style="display:none;">
                                <div class="fw-semibold text-secondary">B1. Import: satu_sehat_ref_form.sql</div>
                                <i class="fa fa-circle status-icon pending"></i>
                            </div>
                            <div class="step-box ref-step" id="step-route" data-step="route" style="display:none;">
                                <div class="fw-semibold text-secondary">B2. Import: satu_sehat_ref_route.sql</div>
                                <i class="fa fa-circle status-icon pending"></i>
                            </div>
                            <div class="step-box ref-step" id="step-snomed" data-step="snomed" style="display:none;">
                                <div class="fw-semibold text-secondary">B3. Import: satu_sehat_ref_snomed.sql</div>
                                <i class="fa fa-circle status-icon pending"></i>
                            </div>
                            <div class="step-box ref-step" id="step-loinc" data-step="loinc" style="display:none;">
                                <div class="fw-semibold text-secondary">B4. Import: satu_sehat_ref_loinc.sql (Besar)</div>
                                <i class="fa fa-circle status-icon pending"></i>
                            </div>
                            <div class="step-box ref-step" id="step-kfa" data-step="kfa" style="display:none;">
                                <div class="fw-semibold text-secondary">B5. Import: satu_sehat_ref_kfa.sql (Besar)</div>
                                <i class="fa fa-circle status-icon pending"></i>
                            </div>
                            <div class="step-box ref-step" id="step-numerator" data-step="numerator" style="display:none;">
                                <div class="fw-semibold text-secondary">B6. Import: satu_sehat_ref_numerator.sql</div>
                                <i class="fa fa-circle status-icon pending"></i>
                            </div>
                            <div class="step-box ref-step" id="step-denominator" data-step="denominator" style="display:none;">
                                <div class="fw-semibold text-secondary">B7. Import: satu_sehat_ref_denominator.sql</div>
                                <i class="fa fa-circle status-icon pending"></i>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($is_mapping_safe && !$is_ref_safe): ?>
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4" style="background:white;">
                        <i class="fa fa-arrow-left me-2"></i> Abaikan, Kembali ke Dashboard
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    let queue = [];
    
    function processStep(index) {
        if (index >= queue.length) {
            Swal.fire({
                title: 'Instalasi Selesai!',
                text: 'Database mapping siap digunakan.',
                icon: 'success',
                confirmButtonText: 'Ke Dashboard'
            }).then(() => {
                window.location.href = 'index.php';
            });
            return;
        }

        const stepId = queue[index];
        const box = $('#step-' + stepId);
        const icon = box.find('.status-icon');
        const text = box.find('div');

        box.addClass('active');
        text.removeClass('text-secondary').addClass('text-dark');
        icon.removeClass('pending fa-circle').addClass('running fa-spinner fa-spin');

        $.ajax({
            url: 'installation.php?action=run_import&step=' + stepId,
            method: 'GET',
            dataType: 'json',
            success: function(res) {
                icon.removeClass('running fa-spinner fa-spin');
                box.removeClass('active');
                if (res.status === 'success') {
                    box.addClass('success');
                    text.addClass('text-success');
                    icon.addClass('success fa-check-circle');
                    processStep(index + 1);
                } else {
                    box.addClass('error');
                    text.addClass('text-danger');
                    icon.addClass('error fa-exclamation-triangle');
                    console.log("Warning on " + stepId + ": " + res.message);
                    processStep(index + 1);
                }
            },
            error: function(xhr, status, error) {
                icon.removeClass('running fa-spinner fa-spin');
                box.removeClass('active');
                box.addClass('error');
                text.addClass('text-danger');
                icon.addClass('error fa-times-circle');
                
                Swal.fire('Error!', 'Proses terhenti di tahap: ' + stepId + '. Server mungkin timeout.', 'error');
            }
        });
    }

    $('#btnInstallCepat').click(function() {
        $('#btnInstallCepat, #btnInstallFull').prop('disabled', true);
        $('#progress-area').fadeIn();
        queue = ['tables']; // Hanya install tabel mapping
        processStep(0);
    });

    $('#btnInstallFull').click(function() {
        $('#btnInstallCepat, #btnInstallFull').prop('disabled', true);
        $('.ref-step').show(); // Tampilkan kotak indikator referensi
        $('#progress-area').fadeIn();
        // Queue lengkap (selalu buat tabel mapping dulu, lalu import sisanya)
        queue = ['tables', 'form', 'route', 'snomed', 'loinc', 'kfa', 'numerator', 'denominator'];
        processStep(0);
    });
});
</script>

<div class="footer-credit" id="footer-credit-block">
    &copy; Ichsan Leonhart &nbsp;·&nbsp;
    <a href="https://wa.me/6285726123777" target="_blank">6285726123777</a> &nbsp;·&nbsp;
    <a href="https://t.me/IchsanLeonhart" target="_blank">@IchsanLeonhart</a>
    <br><a href="https://saweria.co/ichsanleonhart" target="_blank" style="color:#8b5cf6; font-weight: 600; margin-top:5px; display:inline-block;"><i class="fa fa-heart"></i> saweria.co/ichsanleonhart</a>
</div>
<div style="display:none;">https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png</div>
</body>
</html>