<?php
/**
 * index.php — Dashboard Utama Mapping BPJS
 */
require_once 'conf.php';
require_once 'auth_check.php';
require_login();

$is_admin    = !empty($_SESSION['is_admin']);
$hak_akses   = $_SESSION['hak_akses'] ?? [];
$user_name   = htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8');

if (!check_tables_exist($pdo)) {
    if ($is_admin) {
        header('Location: installation.php');
    } else {
        $_SESSION['flash_error'] = 'Sistem belum terinisialisasi. Hubungi IT / Super Admin untuk melakukan instalasi database.';
        header('Location: login.php');
    }
    exit;
}

$moduls = [
    [
        'judul'     => 'Mapping Obat Apotek',
        'deskripsi' => 'Mapping kode obat RS SIMRS Khanza ke kode referensi Obat & DPHO BPJS Kesehatan.',
        'ikon'      => 'fa-pills',
        'gradien'   => 'linear-gradient(135deg,#3b82f6,#1d4ed8)',
        'glow'      => 'rgba(59,130,246,0.35)',
        'kolom'     => 'bpjs_mapping_obat_apotek',
        'url'       => 'modules/apotek/index.php',
        'badge'     => 'APOTEK BPJS',
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bridge BPJS — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="shortcut icon" href="logo.php">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0c29, #1e1b4b, #1e293b);
            min-height: 100vh;
            color: #e2e8f0;
        }
        .navbar-glass {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .navbar-brand { font-weight: 700; color: #60a5fa !important; font-size: 1.1rem; }
        .nav-link { color: #94a3b8 !important; font-size: 0.875rem; }
        .nav-link:hover { color: #e2e8f0 !important; }
        
        .badge-role {
            background: rgba(96,165,250,0.2);
            color: #60a5fa;
            border: 1px solid rgba(96,165,250,0.4);
            font-size: 0.7rem;
            border-radius: 20px;
            padding: 3px 10px;
        }

        .page-header { padding: 3rem 0 2rem; text-align: center; }
        .page-header h1 { font-size: 2rem; font-weight: 700; }
        .page-header p { color: #64748b; }

        .modul-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 18px;
            padding: 2rem;
            transition: all 0.25s ease;
            cursor: pointer;
            text-decoration: none;
            display: block;
            height: 100%;
        }
        .modul-card:hover {
            background: rgba(255,255,255,0.09);
            transform: translateY(-6px);
            border-color: rgba(255,255,255,0.2);
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
        }
        .modul-icon {
            width: 64px; height: 64px;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem;
            color: white;
            margin-bottom: 1.25rem;
        }
        .modul-card h5 { color: #f1f5f9; font-weight: 600; margin-bottom: 0.5rem; }
        .modul-card p { color: #64748b; font-size: 0.875rem; margin-bottom: 1rem; }
        .modul-badge {
            font-size: 0.65rem;
            padding: 3px 8px;
            border-radius: 20px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: white;
        }
        .modul-card .arrow-icon {
            font-size: 1rem; color: #475569;
            transition: all 0.2s ease;
        }
        .modul-card:hover .arrow-icon { color: #94a3b8; transform: translateX(4px); }

        .modul-card.locked {
            opacity: 0.4;
            cursor: not-allowed;
            pointer-events: none;
        }
        .locked-overlay {
            display: flex; align-items: center; gap: 6px;
            color: #ef4444; font-size: 0.8rem; font-weight: 500;
        }

        .footer-credit {
            text-align: center;
            padding: 2.5rem 1rem;
            font-size: 0.72rem;
            color: #94a3b8;
            opacity: 0.8;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .footer-credit:hover { opacity: 1; background: rgba(255,255,255,0.03); }
        .footer-credit a { color: #60a5fa; text-decoration: none; font-weight: 600; }
        .footer-credit a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<nav class="navbar navbar-glass sticky-top navbar-expand-lg">
    <div class="container">
        <span class="navbar-brand d-flex align-items-center gap-2">
            <img src="logo.php" alt="Logo" height="32" style="object-fit:contain;">
            <span>Mapping BPJS Kesehatan</span>
        </span>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-ul navbar-nav ms-auto align-items-center gap-2 mt-2 mt-lg-0">
                <li class="nav-item">
                    <span class="badge-role">
                        <i class="fa <?= $is_admin ? 'fa-crown text-warning' : 'fa-user' ?> me-1"></i>
                        <?= $is_admin ? 'Super Admin' : 'User' ?>: <?= $user_name ?>
                    </span>
                </li>
                <?php if ($is_admin): ?>
                <li class="nav-item">
                    <a class="nav-link" href="hak_akses.php"><i class="fa-solid fa-users-gear me-1"></i>Hak Akses User</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="modules/super_admin/bpjs_setting/index.php"><i class="fa-solid fa-gear me-1"></i>Kredensial BPJS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="installation.php"><i class="fa-solid fa-database me-1"></i>Installer</a>
                </li>
                <?php endif; ?>
                <li class="nav-item ms-lg-2">
                    <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3"><i class="fa-solid fa-right-from-bracket me-1"></i>Keluar</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3" style="background:rgba(96,165,250,0.1); border:1px solid rgba(96,165,250,0.2);">
            <i class="fa-solid fa-hospital-user text-primary" style="font-size:0.8rem;"></i>
            <span style="font-size:0.8rem; color:#60a5fa; font-weight:600;"><?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <h1>Bridge & Mapping BPJS</h1>
        <p>Integrasi & Pemetaan Master Obat RS ke BPJS Kesehatan (Apotek / DPHO)</p>
    </div>

    <div class="row g-4 justify-content-center pb-5">
        <?php foreach ($moduls as $m): ?>
            <?php
            $has_access = $is_admin || (($hak_akses[$m['kolom']] ?? 'false') === 'true');
            ?>
            <div class="col-md-6 col-lg-5">
                <?php if ($has_access): ?>
                    <a href="<?= $m['url'] ?>" class="modul-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="modul-icon" style="background: <?= $m['gradien'] ?>; box-shadow: 0 8px 20px <?= $m['glow'] ?>;">
                                <i class="fa-solid <?= $m['ikon'] ?>"></i>
                            </div>
                            <span class="modul-badge" style="background: <?= $m['gradien'] ?>;"><?= $m['badge'] ?></span>
                        </div>
                        <h5><?= $m['judul'] ?></h5>
                        <p><?= $m['deskripsi'] ?></p>
                        <div class="d-flex align-items-center justify-content-between pt-2 border-top border-secondary border-opacity-25">
                            <span class="small text-primary font-weight-bold">Buka Modul</span>
                            <i class="fa-solid fa-arrow-right arrow-icon"></i>
                        </div>
                    </a>
                <?php else: ?>
                    <div class="modul-card locked">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="modul-icon" style="background: #334155;">
                                <i class="fa-solid <?= $m['ikon'] ?>"></i>
                            </div>
                            <span class="modul-badge bg-secondary"><?= $m['badge'] ?></span>
                        </div>
                        <h5><?= $m['judul'] ?></h5>
                        <p><?= $m['deskripsi'] ?></p>
                        <div class="locked-overlay pt-2 border-top border-secondary border-opacity-25">
                            <i class="fa-solid fa-lock"></i> Tidak ada hak akses
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="footer-credit" id="footer-credit-block">
    &copy; <a href="https://saweria.co/ichsanleonhart" target="_blank">Ichsan Leonhart</a> &nbsp;·&nbsp;
    <a href="https://wa.me/6285726123777" target="_blank">6285726123777</a> &nbsp;·&nbsp;
    <a href="https://t.me/IchsanLeonhart" target="_blank">@IchsanLeonhart</a> &nbsp;·&nbsp;
    <a href="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" target="_blank">QRIS Donasi</a>
    <br><a href="https://saweria.co/ichsanleonhart" target="_blank">saweria.co/ichsanleonhart</a>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
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
