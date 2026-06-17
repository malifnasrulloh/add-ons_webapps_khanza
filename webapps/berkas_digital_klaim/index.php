<?php
/*
 * File: /webapps/berkas_digital_perawatan/index.php
 * Fungsi: Halaman Login Khusus Aplikasi Berkas Digital (SECURED)
 */
require_once('csrf.php'); // Session started here safely

// Jika sudah login, langsung lempar ke dashboard
if (isset($_SESSION['casemix_login']) && $_SESSION['casemix_login'] === true) {
    header("Location: dashboard.php");
    exit;
}

require_once('../conf/conf.php');
$koneksi = bukakoneksi();

// Ambil Nama Instansi & Logo untuk Tampilan
$nama_instansi = "RS Khanza";
$q_set = mysqli_query($koneksi, "SELECT nama_instansi FROM setting LIMIT 1");
if ($r_set = mysqli_fetch_assoc($q_set)) {
    $nama_instansi = htmlspecialchars($r_set['nama_instansi'], ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Casemix - <?= $nama_instansi ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .login-header {
            background-color: #0d6efd;
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 2rem;
            text-align: center;
        }
        .logo-img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            background: white;
            border-radius: 50%;
            padding: 5px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="card login-card">
    <div class="login-header">
        <img src="logo.php" alt="Logo" class="logo-img">
        <h5 class="mb-0 fw-bold">Portal Berkas Digital</h5>
        <small><?= $nama_instansi ?></small>
    </div>
    <div class="card-body p-4">
        
        <?php if(isset($_GET['pesan'])): ?>
            <div class="alert alert-danger text-center small py-2">
                <?php 
                $pesan = htmlspecialchars($_GET['pesan'], ENT_QUOTES, 'UTF-8');
                if($pesan == 'gagal') echo "Username atau Password Salah!";
                elseif($pesan == 'noaccess') echo "Anda tidak memiliki hak akses Casemix!";
                elseif($pesan == 'logout') echo "Berhasil Logout.";
                else echo "Terjadi Kesalahan.";
                ?>
            </div>
        <?php endif; ?>

        <form action="login_check.php" method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label text-muted small fw-bold">USERNAME / NIP</label>
                <input type="text" name="username" class="form-control form-control-lg" required autofocus placeholder="NIP Pegawai">
            </div>
            <div class="mb-4">
                <label class="form-label text-muted small fw-bold">PASSWORD</label>
                <input type="password" name="password" class="form-control form-control-lg" required placeholder="Password">
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-lg fw-bold shadow-sm">MASUK</button>
        </form>
    </div>
    <div class="card-footer text-center bg-white border-0 pb-4">
        <small class="text-muted">&copy; <?= date('Y') ?> SIMRS Khanza</small>
        <div class="border-top my-2 opacity-50"></div>
        <div style="font-size: 0.75rem; line-height: 1.4;" class="text-secondary mb-2">
            <strong class="text-dark">Ichsan Leonhart</strong>
            <br>
            <a href="https://saweria.co/ichsanleonhart" target="_blank" class="text-warning text-decoration-none fw-bold" id="saweria-link-login">
                <i class="fas fa-donate me-1"></i>saweria.co/ichsanleonhart
            </a>
            <br>
            <a href="https://wa.me/6285726123777" target="_blank" class="text-decoration-none text-secondary"><i class="fab fa-whatsapp text-success me-1"></i>6285726123777</a>
            <span class="mx-1 opacity-25">|</span>
            <a href="https://t.me/IchsanLeonhart" target="_blank" class="text-decoration-none text-secondary"><i class="fab fa-telegram text-info me-1"></i>@IchsanLeonhart</a>
        </div>

        <a href="#" class="text-secondary text-decoration-none small mt-2 d-inline-block" data-bs-toggle="modal" data-bs-target="#changelogModal">
            <i class="fas fa-history me-1"></i> Log Pembaruan & Dukungan
        </a>
    </div>
</div>

<?php include_once 'modal_changelog.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php mysqli_close($koneksi); ?>
