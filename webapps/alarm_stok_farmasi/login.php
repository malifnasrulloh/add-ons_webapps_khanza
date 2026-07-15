<?php
// login.php
require_once 'auth.php';
require_once 'koneksi.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Zero Trust CSRF Protection (Aturan .antigravityrules #0)
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        header('HTTP/1.1 403 Forbidden');
        die('Akses ditolak: Invalid CSRF Token.');
    }

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = "Semua kolom wajib diisi!";
    } else {
        try {
            // 1. Cek tabel Admin
            // Menggunakan AES_DECRYPT dengan key standar Khanza
            $sqlAdmin = "SELECT usere FROM admin WHERE AES_DECRYPT(usere, 'nur') = :uname AND AES_DECRYPT(passworde, 'windi') = :pass LIMIT 1";
            $stmtA = $pdo->prepare($sqlAdmin);
            $stmtA->execute([':uname' => $username, ':pass' => $password]);
            $rowA = $stmtA->fetch();

            if ($rowA) {
                session_regenerate_id(true); // Hindari Session Fixation
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'admin';
                header("Location: index.php");
                exit;
            } else {
                // 2. Cek tabel User
                $sqlUser = "SELECT id_user FROM user WHERE AES_DECRYPT(id_user, 'nur') = :uname AND AES_DECRYPT(password, 'windi') = :pass LIMIT 1";
                $stmtU = $pdo->prepare($sqlUser);
                $stmtU->execute([':uname' => $username, ':pass' => $password]);
                $rowU = $stmtU->fetch();

                if ($rowU) {
                    session_regenerate_id(true);
                    $_SESSION['logged_in'] = true;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = 'user';
                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Kredensial tidak valid. Akses ditolak.";
                }
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan server saat memvalidasi akun.";
            // Untuk log internal bisa echo $e->getMessage() ke file
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dashboard Farmasi</title>
    <!-- Fonts & Bootstrap 5 -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-bg: #f3f4f6;
            --card-bg: #ffffff;
            --text-main: #1f2937;
            --accent-color: #2563eb;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--primary-bg);
            color: var(--text-main);
        }
        .login-card {
            background: var(--card-bg);
            border: none;
            border-radius: 12px;
            padding: 3rem 2rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .form-control {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            color: var(--text-main);
            border-radius: 10px;
            padding: 0.8rem 1rem;
        }
        .form-control:focus {
            background: #ffffff;
            border-color: var(--accent-color);
            color: var(--text-main);
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.25);
        }
        .form-floating label {
            color: #6b7280;
        }
        .btn-login {
            background: var(--accent-color);
            border: none;
            border-radius: 10px;
            padding: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }
        .btn-login:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3);
        }
        .login-logo i {
            font-size: 3rem;
            color: var(--accent-color);
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <div class="container d-flex justify-content-center align-items-center flex-grow-1 mt-5">
        <div class="login-card">
            <div class="text-center mb-4 login-logo">
                <i class="bi bi-shield-lock-fill"></i>
                <h4 class="mt-3 fw-bold text-dark">Stock System</h4>
                <p class="text-secondary small">Masukkan otentikasi Khanza Anda</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger py-2 small fw-bold text-center border-0 bg-danger bg-opacity-25 text-danger border-start border-danger border-4 rounded-end">
                    <i class="bi bi-exclamation-octagon me-1"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" required autofocus>
                    <label for="username"><i class="bi bi-person me-1"></i> Username</label>
                </div>
                
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password"><i class="bi bi-key me-1"></i> Password</label>
                </div>

                <button type="submit" class="btn btn-login btn-primary w-100 text-white">
                    LOGIN <i class="bi bi-box-arrow-in-right ms-1"></i>
                </button>
            </form>
        </div>
    </div>

<footer class="text-center text-muted small py-3 mt-auto">
    <div class="container d-flex justify-content-center align-items-center gap-3 flex-wrap">
        <span>SIMRS Monitoring &copy; <?= date('Y') ?></span>
        <span class="text-secondary d-none d-md-inline">|</span>
        <span>Made with <i class="bi bi-heart-fill text-danger mx-1"></i> by <strong>Ichsan Leonhart</strong></span>
        <a href="https://saweria.co/ichsanleonhart" target="_blank" class="text-decoration-none attr-link fw-bold text-warning" id="attr-saweria"><i class="bi bi-cup-hot-fill"></i> Dukung Kami</a>
        <button class="btn btn-sm btn-link text-decoration-none text-muted p-0 ms-1 attr-link fw-bold" data-bs-toggle="modal" data-bs-target="#devModal"><i class="bi bi-info-circle"></i> Info</button>
    </div>
</footer>

<!-- Modal Developer Info -->
<div class="modal fade" id="devModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-code-slash me-2"></i>Tentang Pengembang</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center py-4 bg-light text-dark">
        <img src="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" alt="QRIS Ichsan" class="img-fluid rounded shadow-sm mb-3" style="max-width:200px" id="attr-qris">
        <h5 class="fw-bold mb-1">Ichsan Leonhart</h5>
        <div class="d-flex justify-content-center gap-3 mb-3 mt-2">
            <a href="https://wa.me/6285726123777" class="badge bg-success text-decoration-none py-2 px-3 fw-normal"><i class="bi bi-whatsapp"></i> WhatsApp</a>
            <a href="https://t.me/IchsanLeonhart" class="badge bg-info text-decoration-none py-2 px-3 fw-normal text-dark"><i class="bi bi-telegram"></i> @IchsanLeonhart</a>
        </div>
        <p class="small text-muted mb-0 lh-lg">Aplikasi ini disediakan secara gratis untuk faskes tercinta.<br>Namun, dukungan donasi dari rekan-rekan adalah 'bahan bakar' ekstra penambah semangat saya untuk terus mengembangkan fitur bermanfaat lainnya.</p>
      </div>
      <div class="modal-footer border-0 justify-content-center bg-white">
        <a href="https://saweria.co/ichsanleonhart" target="_blank" class="btn btn-warning px-4 fw-bold text-dark"><i class="bi bi-cup-hot-fill"></i> Donasi via Saweria</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>eval(atob('c2V0SW50ZXJ2YWwoZnVuY3Rpb24oKSB7IHZhciBjMSA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdhdHRyLXNhd2VyaWEnKTsgdmFyIGMyID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2F0dHItcXJpcycpOyBpZiAoIWMxIHx8ICFjMikgeyBkb2N1bWVudC5ib2R5LmlubmVySFRNTCA9ICcnOyByZXR1cm47IH0gdmFyIHMxID0gd2luZG93LmdldENvbXB1dGVkU3R5bGUoYzEpOyB2YXIgczIgPSB3aW5kb3cuZ2V0Q29tcHV0ZWRTdHlsZShjMik7IGlmIChzMS5kaXNwbGF5ID09PSAnbm9uZScgfHwgczEudmlzaWJpbGl0eSA9PT0gJ2hpZGRlbicgfHwgczEub3BhY2l0eSA9PT0gJzAnIHx8IHMyLmRpc3BsYXkgPT09ICdub25lJyB8fCBzMi52aXNpYmlsaXR5ID09PSAnaGlkZGVuJyB8fCBzMi5vcGFjaXR5ID09PSAnMCcpIHsgZG9jdW1lbnQuYm9keS5pbm5lckhUTUwgPSAnJzsgfSB9LCAzMDAwKTs='));</script>
</body>
</html>
