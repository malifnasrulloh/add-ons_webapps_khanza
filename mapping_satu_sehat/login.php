<?php
/**
 * login.php — Halaman Login mapping_satu_sehat
 * Autentikasi: AES Khanza-style (AES_DECRYPT di sisi DB)
 *   - User umum : tabel `user`  (id_user AES key 'nur', password AES key 'windi')
 *   - Super Admin: tabel `admin` (usere AES key 'nur', passworde AES key 'windi')
 * Setelah login berhasil, hak akses per modul disimpan ke $_SESSION['hak_akses'].
 */
require_once 'conf.php'; // Inisialisasi PDO + session

$error = '';
$flash_error = isset($_SESSION['flash_error']) ? $_SESSION['flash_error'] : '';
unset($_SESSION['flash_error']); // Hapus setelah ditampilkan

// Cek apakah tabel referensi sudah ada
$is_setup_ready = check_tables_exist($pdo);
if (!$is_setup_ready) {
    $flash_error = 'Sistem belum terinisialisasi. Silakan login sebagai <b>Super Admin</b> untuk melakukan instalasi database.';
}

// Jika sudah login, langsung ke dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $logged_in = false;

        // --- PERCOBAAN 1: Login sebagai Super Admin (tabel admin) ---
        $stmt = $pdo->prepare(
            "SELECT usere FROM admin
             WHERE AES_DECRYPT(usere, 'nur') = ?
             AND AES_DECRYPT(passworde, 'windi') = ?
             LIMIT 1"
        );
        $stmt->execute([$username, $password]);
        $admin = $stmt->fetch();

        if ($admin) {
            // Login berhasil sebagai super admin
            session_regenerate_id(true); // Anti-Session Fixation
            $_SESSION['user_id']   = $username;
            $_SESSION['user_name'] = $username;
            $_SESSION['is_admin']  = true;
            $_SESSION['hak_akses'] = []; // Admin bypass semua cek
            $logged_in = true;
        }

        // --- PERCOBAAN 2: Login sebagai User Biasa (tabel user) ---
        if (!$logged_in) {
            // Kolom hak akses yang kita butuhkan untuk 4 modul ini
            $stmt = $pdo->prepare(
                "SELECT AES_DECRYPT(id_user, 'nur') AS nama_user,
                        satu_sehat_mapping_obat,
                        satu_sehat_mapping_lab,
                        satu_sehat_mapping_radiologi,
                        satu_sehat_mapping_vaksin
                 FROM user
                 WHERE AES_DECRYPT(id_user, 'nur') = ?
                 AND AES_DECRYPT(password, 'windi') = ?
                 LIMIT 1"
            );
            $stmt->execute([$username, $password]);
            $user_row = $stmt->fetch();

            if ($user_row) {
                session_regenerate_id(true); // Anti-Session Fixation
                $_SESSION['user_id']   = $username;
                $_SESSION['user_name'] = $user_row['nama_user'] ?? $username;
                $_SESSION['is_admin']  = false;
                // Simpan hak akses ke session agar tidak perlu query DB tiap request
                $_SESSION['hak_akses'] = [
                    'satu_sehat_mapping_obat'       => $user_row['satu_sehat_mapping_obat'] ?? 'false',
                    'satu_sehat_mapping_lab'         => $user_row['satu_sehat_mapping_lab'] ?? 'false',
                    'satu_sehat_mapping_radiologi'   => $user_row['satu_sehat_mapping_radiologi'] ?? 'false',
                    'satu_sehat_mapping_vaksin'      => $user_row['satu_sehat_mapping_vaksin'] ?? 'false',
                ];
                $logged_in = true;
            }
        }

        if ($logged_in) {
            // Jika tabel belum siap dan user adalah admin, paksa ke installer
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
            --primary: #4f46e5;
            --primary-glow: rgba(79, 70, 229, 0.4);
            --secondary: #06b6d4;
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
        }

        /* Ambient orbs latar belakang */
        body::before, body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.35;
            pointer-events: none;
        }
        body::before {
            width: 500px; height: 500px;
            background: var(--primary);
            top: -150px; left: -150px;
        }
        body::after {
            width: 400px; height: 400px;
            background: var(--secondary);
            bottom: -100px; right: -100px;
        }

        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
            position: relative;
            z-index: 10;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .brand-icon {
            width: 80px; height: 80px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 8px 24px var(--primary-glow);
            overflow: hidden;
            background: white;
            border: 3px solid rgba(255,255,255,0.2);
        }
        .brand-icon img {
            width: 100%; height: 100%;
            object-fit: contain;
        }

        h4, p, label { color: #e2e8f0; }
        p { color: #94a3b8 !important; }

        .form-control {
            background: rgba(255,255,255,0.08) !important;
            border: 1px solid rgba(255,255,255,0.18) !important;
            color: #f1f5f9 !important;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            background: rgba(255,255,255,0.12) !important;
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 3px var(--primary-glow) !important;
            outline: none;
        }
        .form-control::placeholder { color: #64748b !important; }

        .input-group-text {
            background: rgba(255,255,255,0.08) !important;
            border: 1px solid rgba(255,255,255,0.18) !important;
            border-right: none !important;
            color: #94a3b8;
            border-radius: 10px 0 0 10px !important;
        }
        .input-group .form-control { border-left: none !important; border-radius: 0 10px 10px 0 !important; }

        .btn-login {
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            border: none;
            border-radius: 10px;
            padding: 0.8rem;
            font-weight: 600;
            color: white;
            font-size: 1rem;
            width: 100%;
            transition: all 0.25s ease;
            box-shadow: 0 4px 20px var(--primary-glow);
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px var(--primary-glow);
            color: white;
        }
        .btn-login:active { transform: translateY(0); }

        .alert-danger-glass {
            background: rgba(239,68,68,0.15);
            border: 1px solid rgba(239,68,68,0.4);
            color: #fca5a5;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
        }

        .footer-credit {
            margin-top: 2.5rem;
            text-align: center;
            font-size: 0.72rem;
            color: rgba(255,255,255,0.4);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .footer-credit:hover { color: rgba(255,255,255,0.8); }
        .footer-credit a {
            color: #a78bfa;
            text-decoration: none;
            transition: color 0.2s;
            font-weight: 600;
        }
        .footer-credit a:hover { color: #c4b5fd; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="brand-icon">
                <img src="logo.php" alt="Logo">
            </div>
            <h4 class="fw-bold mb-1"><?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?></h4>
            <p class="small mb-0">Mapping Satu Sehat terintegrasi SIMRS Khanza</p>
        </div>

        <?php if ($flash_error): ?>
            <div class="alert alert-warning border-0 small py-2 mb-4" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border-radius: 10px;">
                <i class="fa fa-triangle-exclamation me-2"></i> <?= $flash_error ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert-danger-glass mb-3">
            <i class="fa fa-lock me-2"></i><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="loginForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="mb-3">
                <label class="form-label small fw-semibold">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                    <input type="text" id="username" name="username" class="form-control"
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

        <!-- Footer copyright (Anti-Tampering — JANGAN DIHAPUS) -->
        <div class="footer-credit" id="footer-credit-block" onclick="new bootstrap.Modal(document.getElementById('modalSaweria')).show();">
            &copy; <a href="https://saweria.co/ichsanleonhart" target="_blank" onclick="event.stopPropagation();">Ichsan Leonhart</a> &nbsp;|&nbsp; 
            <a href="https://wa.me/6285726123777" target="_blank" onclick="event.stopPropagation();"><i class="fa-brands fa-whatsapp"></i> 6285726123777</a> &nbsp;|&nbsp;
            <a href="https://t.me/IchsanLeonhart" target="_blank" onclick="event.stopPropagation();">@IchsanLeonhart</a><br>
            <a href="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" target="_blank" onclick="event.stopPropagation();">
                <i class="fa fa-qrcode"></i> QRIS Donasi
            </a> — <a href="https://saweria.co/ichsanleonhart" target="_blank" onclick="event.stopPropagation();">saweria.co/ichsanleonhart</a>
        </div>
    </div>

    <!-- Modal Saweria (Uneg-uneg Mengemis) -->
    <div class="modal fade border-0" id="modalSaweria" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg text-dark" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center pt-0 pb-4 px-4">
                    <div class="mb-3">
                        <img src="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" class="img-fluid rounded-3 shadow-sm" style="max-width: 280px;" alt="QRIS Donasi">
                    </div>
                    <h5 class="fw-bold text-primary mb-3">Apresiasi & Dukungan</h5>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Loading state saat submit
    document.getElementById('loginForm').addEventListener('submit', function() {
        var btn = document.getElementById('btnLogin');
        btn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Memverifikasi...';
        btn.disabled = true;
    });

    // Anti-Tampering Client-Side: cek footer tiap 3 detik
    // (Obfuscated Base64 check — jangan modifikasi)
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
