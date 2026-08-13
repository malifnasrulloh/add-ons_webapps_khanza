<?php
/**
 * installation.php — Web Installer mapping_bpjs
 * Dibatasi HANYA untuk Super Admin.
 */
require_once 'conf.php';
require_once 'auth_check.php';
require_login();

if (empty($_SESSION['is_admin'])) {
    die("<div style='padding:20px; text-align:center; font-family:sans-serif;'>
            <h2 style='color:#ef4444;'>Akses Ditolak</h2>
            <p>Hanya Super Admin yang diizinkan mengakses Database Installer.</p>
            <a href='index.php'>Kembali ke Dashboard</a>
         </div>");
}

$action = $_GET['action'] ?? '';

if ($action === 'run_import') {
    set_time_limit(0);
    ini_set('memory_limit', '-1');
    header('Content-Type: application/json');

    $step = $_GET['step'] ?? '';
    $sql_dir = __DIR__ . DIRECTORY_SEPARATOR . 'tambahan_table' . DIRECTORY_SEPARATOR;

    try {
        if ($step === 'tables') {
            $sql = file_get_contents($sql_dir . 'maping_obat_apotek_bpjs.sql');
            $pdo->exec($sql);

            $sql2 = file_get_contents($sql_dir . 'bpjs_ref_dpho.sql');
            $pdo->exec($sql2);

            try {
                $pdo->exec("ALTER TABLE `user` ADD COLUMN `bpjs_mapping_obat_apotek` enum('true','false') DEFAULT 'false'");
            } catch (Exception $e) {
            }

            echo json_encode(['status' => 'success', 'message' => 'Tabel maping_obat_apotek_bpjs, bpjs_ref_dpho, dan kolom hak akses berhasil dibuat!']);
            exit;
        }

        echo json_encode(['status' => 'error', 'message' => 'Langkah tidak dikenali.']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

$missing_mapping_tables = [];
$check_mapping_tables = ['maping_obat_apotek_bpjs', 'bpjs_ref_dpho'];

foreach ($check_mapping_tables as $tb) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $stmt->execute([$tb]);
        if ((int)$stmt->fetchColumn() === 0) $missing_mapping_tables[] = $tb;
    } catch (Exception $e) { $missing_mapping_tables[] = $tb; }
}

$user_col_exists = false;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'user' AND column_name = ?");
    $stmt->execute(['bpjs_mapping_obat_apotek']);
    if ((int)$stmt->fetchColumn() > 0) $user_col_exists = true;
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Installer — Add-on Mapping BPJS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 1rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); width: 100%; max-width: 650px; }
        .status-badge { font-size: 0.85rem; padding: 0.35em 0.65em; border-radius: 0.375rem; }
        .footer-credit { text-align: center; margin-top: 1.5rem; font-size: 0.72rem; color: rgba(255,255,255,0.4); }
        .footer-credit a { color: #60a5fa; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body class="p-3">
    <div class="card">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-4 border-bottom border-secondary pb-3">
                <i class="fa-solid fa-database text-primary fs-2"></i>
                <div>
                    <h4 class="m-0 text-white">Database Installer</h4>
                    <small class="text-secondary">Pemasangan Tabel Mapping Apotek BPJS</small>
                </div>
            </div>

            <div class="mb-4">
                <h6 class="text-light mb-3">Status Tabel Database Saat Ini:</h6>
                <ul class="list-group list-group-flush bg-transparent">
                    <li class="list-group-item bg-transparent text-light d-flex justify-content-between align-items-center border-secondary px-0">
                        <span>Tabel <code>maping_obat_apotek_bpjs</code></span>
                        <?php if (in_array('maping_obat_apotek_bpjs', $missing_mapping_tables)): ?>
                            <span class="badge bg-danger status-badge"><i class="fa-solid fa-xmark me-1"></i> Belum Ada</span>
                        <?php else: ?>
                            <span class="badge bg-success status-badge"><i class="fa-solid fa-check me-1"></i> Sudah Ada</span>
                        <?php endif; ?>
                    </li>
                    <li class="list-group-item bg-transparent text-light d-flex justify-content-between align-items-center border-secondary px-0">
                        <span>Tabel <code>bpjs_ref_dpho</code> (Cache DPHO)</span>
                        <?php if (in_array('bpjs_ref_dpho', $missing_mapping_tables)): ?>
                            <span class="badge bg-danger status-badge"><i class="fa-solid fa-xmark me-1"></i> Belum Ada</span>
                        <?php else: ?>
                            <span class="badge bg-success status-badge"><i class="fa-solid fa-check me-1"></i> Sudah Ada</span>
                        <?php endif; ?>
                    </li>
                    <li class="list-group-item bg-transparent text-light d-flex justify-content-between align-items-center border-secondary px-0">
                        <span>Hak Akses User (Kolom <code>bpjs_mapping_obat_apotek</code>)</span>
                        <?php if ($user_col_exists): ?>
                            <span class="badge bg-success status-badge"><i class="fa-solid fa-check me-1"></i> Sudah Ada</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark status-badge"><i class="fa-solid fa-triangle-exclamation me-1"></i> Belum Ada</span>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>

            <div id="progressArea" class="d-none mb-4">
                <div class="progress bg-secondary" style="height: 10px;">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
                </div>
                <small id="progressText" class="text-info mt-2 d-block text-center">Menyiapkan eksekusi...</small>
            </div>

            <div id="alertBox" class="alert d-none" role="alert"></div>

            <div class="d-flex justify-content-between align-items-center">
                <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-2"></i>Kembali</a>
                <button id="btnInstall" class="btn btn-primary px-4"><i class="fa-solid fa-download me-2"></i>Jalankan Installer</button>
            </div>

            <div class="footer-credit" id="footer-credit-block">
                &copy; <a href="https://saweria.co/ichsanleonhart" target="_blank">Ichsan Leonhart</a> &nbsp;|&nbsp; 
                <a href="https://wa.me/6285726123777" target="_blank"><i class="fa-brands fa-whatsapp"></i> 6285726123777</a> &nbsp;|&nbsp;
                <a href="https://t.me/IchsanLeonhart" target="_blank">@IchsanLeonhart</a><br>
                <a href="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" target="_blank">
                    <i class="fa fa-qrcode"></i> QRIS Donasi
                </a> — <a href="https://saweria.co/ichsanleonhart" target="_blank">saweria.co/ichsanleonhart</a>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $('#btnInstall').on('click', function() {
            $(this).prop('disabled', true);
            $('#progressArea').removeClass('d-none');
            $('#alertBox').addClass('d-none');
            
            $('#progressBar').css('width', '50%');
            $('#progressText').text('Membuat tabel maping_obat_apotek_bpjs, bpjs_ref_dpho & kolom hak akses...');

            $.getJSON('installation.php?action=run_import&step=tables', function(res) {
                if (res.status === 'success') {
                    $('#progressBar').css('width', '100%').addClass('bg-success');
                    $('#progressText').text('Pemasangan selesai!');
                    $('#alertBox').removeClass('d-none alert-danger').addClass('alert-success').text(res.message);
                    setTimeout(() => location.reload(), 2000);
                } else {
                    $('#progressBar').addClass('bg-danger');
                    $('#progressText').text('Gagal memproses.');
                    $('#alertBox').removeClass('d-none alert-success').addClass('alert-danger').text(res.message);
                    $('#btnInstall').prop('disabled', false);
                }
            }).fail(function(xhr) {
                $('#progressBar').addClass('bg-danger');
                $('#progressText').text('Gagal memproses request.');
                $('#alertBox').removeClass('d-none alert-success').addClass('alert-danger').text(xhr.responseText || 'Terjadi kesalahan sistem.');
                $('#btnInstall').prop('disabled', false);
            });
        });

        setInterval(function() {
            var el = document.getElementById('footer-credit-block');
            if (!el) { document.body.innerHTML = ''; return; }
            var html = el.innerHTML;
            var checks = [
                atob('SWNoc2FuIExlb25oYXJ0'),
                atob('c2F3ZXJpYS5jby9pY2hzYW5sZW9uaGFydA=='),
                atob('NjI4NTcyNjEyMzc3Nw=='),
                atob('QEljaHNhbkxlb25oYXJ0'),
                atob('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL2ljaHNhbmxlb25oYXJ0L2FkZC1vbnNfd2ViYXBwc19raGFuemEvbWFpbi9xcmlzLWljaHNhbi5wbmc=')
            ];
            for (var i = 0; i < checks.length; i++) {
                var cs = window.getComputedStyle(el);
                if (cs.display === 'none' || cs.visibility === 'hidden' || cs.opacity === '0' || html.indexOf(checks[i]) === -1) {
                    document.body.innerHTML = '';
                    return;
                }
            }
        }, 3000);
    </script>
</body>
</html>
