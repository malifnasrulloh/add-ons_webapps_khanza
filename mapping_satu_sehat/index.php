<?php
/**
 * index.php — Dashboard Utama mapping_satu_sehat
 * + Popup donasi Saweria (per-session, setelah 3 detik)
 * Menampilkan kartu modul sesuai hak akses user yang sedang login.
 * Super admin melihat semua modul; user biasa hanya yang diizinkan.
 */
require_once 'conf.php';
require_once 'auth_check.php';
require_login(); // Redirect ke login jika belum autentikasi

$is_admin    = !empty($_SESSION['is_admin']);
$hak_akses   = $_SESSION['hak_akses'] ?? [];
$user_name   = htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8');

// Proteksi sistem belum terinisialisasi
if (!check_tables_exist($pdo)) {
    if ($is_admin) {
        header('Location: installation.php');
    } else {
        $_SESSION['flash_error'] = 'Sistem belum terinisialisasi. Hubungi IT / Super Admin untuk melakukan instalasi database.';
        header('Location: login.php');
    }
    exit;
}

// Definisi modul: [judul, deskripsi, ikon, warna gradient, kolom_hak_akses, url]
$moduls = [
    [
        'judul'   => 'Mapping Obat',
        'deskripsi' => 'Mapping kode obat RS ke Kamus Farmasi & Alkes (KFA) Satu Sehat.',
        'ikon'    => 'fa-pills',
        'gradien' => 'linear-gradient(135deg,#4f46e5,#7c3aed)',
        'glow'    => 'rgba(79,70,229,0.35)',
        'kolom'   => 'satu_sehat_mapping_obat',
        'url'     => 'modules/obat/index.php',
        'badge'   => 'KFA',
    ],
    [
        'judul'   => 'Mapping Laboratorium',
        'deskripsi' => 'Mapping pemeriksaan lab RS ke kode LOINC dan spesimen SNOMED-CT.',
        'ikon'    => 'fa-flask',
        'gradien' => 'linear-gradient(135deg,#0891b2,#06b6d4)',
        'glow'    => 'rgba(8,145,178,0.35)',
        'kolom'   => 'satu_sehat_mapping_lab',
        'url'     => 'modules/lab/index.php',
        'badge'   => 'LOINC',
    ],
    [
        'judul'   => 'Mapping Radiologi',
        'deskripsi' => 'Mapping tindakan radiologi RS ke kode LOINC dan SNOMED-CT Satu Sehat.',
        'ikon'    => 'fa-x-ray',
        'gradien' => 'linear-gradient(135deg,#be185d,#ec4899)',
        'glow'    => 'rgba(190,24,93,0.35)',
        'kolom'   => 'satu_sehat_mapping_radiologi',
        'url'     => 'modules/radiologi/index.php',
        'badge'   => 'LOINC',
    ],
    [
        'judul'   => 'Mapping Vaksin',
        'deskripsi' => 'Mapping data vaksin RS ke referensi Satu Sehat (CVX/KFA) beserta rute dan dosis.',
        'ikon'    => 'fa-syringe',
        'gradien' => 'linear-gradient(135deg,#059669,#10b981)',
        'glow'    => 'rgba(5,150,105,0.35)',
        'kolom'   => 'satu_sehat_mapping_vaksin',
        'url'     => 'modules/vaksin/index.php',
        'badge'   => 'CVX',
    ],

];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SatuSehat — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="shortcut icon" href="logo.php">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        .navbar-brand { font-weight: 700; color: #a78bfa !important; font-size: 1.1rem; }
        .nav-link { color: #94a3b8 !important; font-size: 0.875rem; }
        .nav-link:hover { color: #e2e8f0 !important; }
        .btn-donasi-nav {
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            border: none; color: white; font-size: 0.75rem; font-weight: 700;
            border-radius: 20px; padding: 4px 14px; cursor: pointer;
            transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(239,68,68,0.4);
        }
        .btn-donasi-nav:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(239,68,68,0.5); color: white; }
        .badge-role {
            background: rgba(167,139,250,0.2);
            color: #a78bfa;
            border: 1px solid rgba(167,139,250,0.4);
            font-size: 0.7rem;
            border-radius: 20px;
            padding: 3px 10px;
        }

        .page-header { padding: 3rem 0 2rem; text-align: center; }
        .page-header h1 { font-size: 2rem; font-weight: 700; }
        .page-header p { color: #64748b; }

        /* Modul Card */
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

        /* Locked card (tidak punya akses) */
        .modul-card.locked {
            opacity: 0.4;
            cursor: not-allowed;
            pointer-events: none;
        }
        .locked-overlay {
            display: flex; align-items: center; gap: 6px;
            color: #ef4444; font-size: 0.8rem; font-weight: 500;
        }

        /* Footer credit */
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
        .footer-credit a { color: #a78bfa; text-decoration: none; font-weight: 600; }
        .footer-credit a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-glass sticky-top">
    <div class="container">
        <span class="navbar-brand">
            <img src="logo.php" alt="Logo" width="28" height="28" class="d-inline-block align-text-top me-2 rounded-circle" style="object-fit:cover;">
            <span class="d-none d-sm-inline"><?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?></span>
        </span>
        <div class="d-flex align-items-center gap-3">
            <button class="btn-donasi-nav" onclick="showDonasi()">
                <i class="fa fa-heart me-1"></i> Support Dev
            </button>
            <span class="badge-role">
                <i class="fa fa-user-shield me-1"></i>
                <?= $is_admin ? 'Super Admin' : htmlspecialchars($user_name) ?>
            </span>
            <a href="logout.php" class="nav-link">
                <i class="fa fa-right-from-bracket me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>
            <i class="fa-solid fa-layer-group me-2" style="color:#a78bfa"></i>
            Pilih Modul Mapping
        </h1>
        <p>Selamat datang, <strong><?= $user_name ?></strong>. Pilih modul yang ingin Anda kelola.</p>
        
        <a href="PANDUAN_PENGGUNAAN.html" target="_blank"
           style="font-size:0.85rem; color:#a78bfa; text-decoration:none; display:inline-block; margin-bottom: 1rem; border: 1px solid rgba(167,139,250,0.3); padding: 5px 15px; border-radius: 20px;">
            <i class="fa fa-book me-1"></i> Baca Panduan Penggunaan Aplikasi
        </a>
        
        <?php if ($is_admin): ?>
        <div class="mt-4 d-flex justify-content-center gap-3">
            <a href="hak_akses.php" class="btn btn-outline-light btn-sm rounded-pill px-4 shadow-sm" style="border-color: rgba(167,139,250,0.5); color: #c4b5fd;">
                <i class="fa fa-users-gear me-2"></i>Manajemen Hak Akses User
            </a>
            <a href="installation.php" class="btn btn-outline-light btn-sm rounded-pill px-4 shadow-sm" style="border-color: rgba(16,185,129,0.5); color: #6ee7b7;">
                <i class="fa fa-database me-2"></i>Database Installer
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Kartu Modul -->
    <div class="row g-4 justify-content-center mb-5">
        <?php foreach ($moduls as $mod):
            // Cek hak akses
            $punya_akses = $is_admin || (isset($hak_akses[$mod['kolom']]) && $hak_akses[$mod['kolom']] === 'true');
        ?>
        <div class="col-md-6 col-lg-3">
            <a href="<?= $punya_akses ? $mod['url'] : '#' ?>"
               class="modul-card <?= $punya_akses ? '' : 'locked' ?>">
                <div class="modul-icon" style="background: <?= $mod['gradien'] ?>; box-shadow: 0 8px 24px <?= $mod['glow'] ?>;">
                    <i class="fa-solid <?= $mod['ikon'] ?>"></i>
                </div>
                <h5><?= $mod['judul'] ?></h5>
                <p><?= $mod['deskripsi'] ?></p>
                <div class="d-flex align-items-center justify-content-between">
                    <?php if ($punya_akses): ?>
                        <span class="modul-badge" style="background: <?= $mod['gradien'] ?>"><?= $mod['badge'] ?></span>
                        <i class="fa fa-arrow-right arrow-icon"></i>
                    <?php else: ?>
                        <span class="locked-overlay"><i class="fa fa-lock"></i> Tidak ada akses</span>
                    <?php endif; ?>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
        <?php if ($is_admin): ?>

        <div class="col-md-6 col-lg-3">
            <a href="modules/super_admin/satusehat_setting/index.php" class="modul-card">
                <div class="modul-icon" style="background: linear-gradient(135deg,#0f766e,#14b8a6); box-shadow: 0 8px 24px rgba(20,184,166,0.4);">
                    <i class="fa-solid fa-gear"></i>
                </div>
                <h5>Setting SatuSehat</h5>
                <p>Atur credential API SatuSehat (Organization ID, Client ID, Client Secret) dan mode pencarian KFA.</p>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="modul-badge" style="background: linear-gradient(135deg,#0f766e,#14b8a6);">API</span>
                    <i class="fa fa-arrow-right arrow-icon"></i>
                </div>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalDonasi" tabindex="-1">
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
                    Aplikasi <strong style="color:#4f46e5;">mapping_satu_sehat</strong> ini saya buat dan saya bagikan <strong style="color:#ef4444;">gratis</strong> untuk membantu digitalisasi FHIR Satu Sehat di seluruh Indonesia. 🇮🇩<br><br>
                    Jika aplikasi ini membantu operasional RS Anda, mohon bantuannya untuk sedikit memberikan apresiasi / "traktiran kopi" agar saya tetap semangat melakukan maintenance dan update fitur lainnya. Berapapun dukungan Anda sangat berarti bagi kelangsungan pengembangan aplikasi ini.<br><br>
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

<!-- Footer copyright (Anti-Tampering — JANGAN DIHAPUS) -->
<div class="footer-credit" id="footer-credit-block" onclick="showDonasi()">
    &copy; <a href="https://saweria.co/ichsanleonhart" target="_blank" onclick="event.stopPropagation()">Ichsan Leonhart</a> &nbsp;·&nbsp;
    <a href="https://wa.me/6285726123777" target="_blank" onclick="event.stopPropagation()">6285726123777</a> &nbsp;·&nbsp;
    <a href="https://t.me/IchsanLeonhart" target="_blank" onclick="event.stopPropagation()">@IchsanLeonhart</a> &nbsp;·&nbsp;
    <a href="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" target="_blank" onclick="event.stopPropagation()">QRIS Donasi</a>
    <br><a href="https://saweria.co/ichsanleonhart" target="_blank" onclick="event.stopPropagation()" style="color:#8b5cf6; font-weight: 600; margin-top:5px; display:inline-block; text-decoration:none;"><i class="fa fa-heart"></i> saweria.co/ichsanleonhart</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Tampilkan popup donasi otomatis sekali per sesi (setelah 3 detik)
function showDonasi() {
    new bootstrap.Modal(document.getElementById('modalDonasi')).show();
}
document.addEventListener('DOMContentLoaded', function() {
    if (!sessionStorage.getItem('donasi_shown')) {
        setTimeout(function() {
            showDonasi();
            sessionStorage.setItem('donasi_shown', '1');
        }, 3000);
    }
});

// Anti-Tampering Client-Side
setInterval(function() {
    var el = document.getElementById('footer-credit-block');
    if (!el) { document.body.innerHTML = ''; return; }
    var html = el.innerHTML;
    var cs   = window.getComputedStyle(el);
    var checks = [
        atob('SWNoc2FuIExlb25oYXJ0'),
        atob('c2F3ZXJpYS5jby9pY2hzYW5sZW9uaGFydA=='),
        atob('NjI4NTcyNjEyMzc3Nw=='),
        atob('QEljaHNhbkxlb25oYXJ0'),
        atob('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL2ljaHNhbmxlb25oYXJ0L2FkZC1vbnNfd2ViYXBwc19raGFuemEvbWFpbi9xcmlzLWljaHNhbi5wbmc=')
    ];
    if (cs.display === 'none' || cs.visibility === 'hidden' || cs.opacity === '0') {
        document.body.innerHTML = ''; return;
    }
    for (var i = 0; i < checks.length; i++) {
        if (html.indexOf(checks[i]) === -1) { document.body.innerHTML = ''; return; }
    }
}, 3000);
</script>
</body>
</html>
