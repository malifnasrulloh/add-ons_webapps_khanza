<?php
/**
 * login.php — Halaman Login mapping_bpjs
 * Autentikasi: AES Khanza-style (AES_DECRYPT di sisi DB)
 *   - User umum : tabel `user`  (id_user AES key 'nur', password AES key 'windi')
 *   - Super Admin: tabel `admin` (usere AES key 'nur', passworde AES key 'windi')
 * Setelah login berhasil, hak akses per modul disimpan ke $_SESSION['hak_akses'].
 */
require_once 'conf.php';

$error = '';
$flash_error = isset($_SESSION['flash_error']) ? $_SESSION['flash_error'] : '';
unset($_SESSION['flash_error']);

$is_setup_ready = check_tables_exist($pdo);
if (!$is_setup_ready) {
    $flash_error = 'Sistem belum terinisialisasi. Silakan login sebagai <b>Super Admin</b> untuk melakukan instalasi database.';
}

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $logged_in = false;

        $stmt = $pdo->prepare(
            "SELECT usere FROM admin
             WHERE AES_DECRYPT(usere, 'nur') = ?
             AND AES_DECRYPT(passworde, 'windi') = ?
             LIMIT 1"
        );
        $stmt->execute([$username, $password]);
        $admin = $stmt->fetch();

        if ($admin) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $username;
            $_SESSION['user_name'] = $username;
            $_SESSION['is_admin']  = true;
            $_SESSION['hak_akses'] = [];
            $logged_in = true;
        }

        if (!$logged_in) {
            $hasCol = false;
            try {
                $chk = $pdo->query("SHOW COLUMNS FROM `user` LIKE 'bpjs_mapping_obat_apotek'");
                if ($chk && $chk->rowCount() > 0) $hasCol = true;
            } catch (Exception $e) {}

            $colSql = $hasCol ? "bpjs_mapping_obat_apotek" : "'true' AS bpjs_mapping_obat_apotek";

            $stmt = $pdo->prepare(
                "SELECT AES_DECRYPT(id_user, 'nur') AS nama_user, {$colSql}
                 FROM user
                 WHERE AES_DECRYPT(id_user, 'nur') = ?
                 AND AES_DECRYPT(password, 'windi') = ?
                 LIMIT 1"
            );
            $stmt->execute([$username, $password]);
            $user_row = $stmt->fetch();

            if ($user_row) {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $username;
                $_SESSION['user_name'] = $user_row['nama_user'] ?? $username;
                $_SESSION['is_admin']  = false;
                $_SESSION['hak_akses'] = [
                    'bpjs_mapping_obat_apotek' => $user_row['bpjs_mapping_obat_apotek'] ?? 'false'
                ];
                $logged_in = true;
            }
        }

        if ($logged_in) {
            if (!$is_setup_ready && !empty($_SESSION['is_admin'])) {
                header('Location: installation.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="shortcut icon" href="logo.php">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-glow: rgba(59, 130, 246, 0.4);
            --glass-bg: rgba(255,255,255,0.07);
            --glass-border: rgba(255,255,255,0.15);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            color: #f8fafc;
        }
        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2.5rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 10;
        }
        .brand-icon {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.05));
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }
        .brand-icon img { height: 44px; width: auto; object-fit: contain; }
        .form-label { color: #cbd5e1; }
        .form-control {
            background: rgba(0,0,0,0.25);
            border: 1px solid rgba(255,255,255,0.15);
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-size: 0.9rem;
        }
        .form-control:focus {
            background: rgba(0,0,0,0.4);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 0 0 3px var(--primary-glow);
        }
        .input-group-text {
            background: rgba(0,0,0,0.25);
            border: 1px solid rgba(255,255,255,0.15);
            border-right: none;
            color: #94a3b8;
            border-radius: 12px 0 0 12px;
        }
        .input-group .form-control { border-left: none; border-radius: 0 12px 12px 0; }
        .btn-login {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.85rem;
            border-radius: 12px;
            width: 100%;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px var(--primary-glow);
        }
        .btn-login:hover { transform: translateY(-1px); box-shadow: 0 6px 20px var(--primary-glow); color: white; }
        .footer-credit {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.72rem;
            color: rgba(255,255,255,0.4);
        }
        .footer-credit a { color: #60a5fa; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="brand-icon">
                <img src="logo.php" alt="Logo">
            </div>
            <h4 class="fw-bold mb-1"><?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?></h4>
            <p class="small mb-0 text-secondary">Mapping Apotek BPJS terintegrasi SIMRS Khanza</p>
        </div>

        <?php if ($flash_error): ?>
            <div class="alert alert-warning border-0 small py-2 mb-4" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border-radius: 10px;">
                <i class="fa fa-triangle-exclamation me-2"></i> <?= $flash_error ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 small py-2 mb-3" style="background: rgba(239, 68, 68, 0.2); color: #ef4444; border-radius: 10px;">
                <i class="fa fa-lock me-2"></i><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="loginForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="mb-3">
                <label class="form-label small fw-semibold">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                    <input type="password" id="username" name="username" class="form-control"
                           placeholder="Masukkan username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           autocomplete="username" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-lock"></i></span>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Masukkan password"
                           autocomplete="current-password" required>
                </div>
            </div>

            <button type="submit" class="btn-login" id="btnLogin">
                <i class="fa fa-right-to-bracket me-2"></i>Masuk
            </button>
        </form>

        <div class="footer-credit" id="footer-credit-block">
            &copy; <a href="https://saweria.co/ichsanleonhart" target="_blank">Ichsan Leonhart</a> &nbsp;|&nbsp; 
            <a href="https://wa.me/6285726123777" target="_blank"><i class="fa-brands fa-whatsapp"></i> 6285726123777</a> &nbsp;|&nbsp;
            <a href="https://t.me/IchsanLeonhart" target="_blank">@IchsanLeonhart</a><br>
            <a href="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" target="_blank">
                <i class="fa fa-qrcode"></i> QRIS Donasi
            </a> — <a href="https://saweria.co/ichsanleonhart" target="_blank">saweria.co/ichsanleonhart</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('loginForm').addEventListener('submit', function() {
        var btn = document.getElementById('btnLogin');
        btn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Memverifikasi...';
        btn.disabled = true;
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
