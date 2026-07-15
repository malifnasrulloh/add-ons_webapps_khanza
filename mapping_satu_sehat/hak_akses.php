<?php
/**
 * hak_akses.php — Antarmuka Manajemen Hak Akses User
 * Dibatasi HANYA untuk Super Admin.
 */
require_once 'conf.php';
require_once 'auth_check.php';
require_login();

// Validasi extra: mutlak hanya Super Admin yang bisa mengakses halaman ini
if (empty($_SESSION['is_admin'])) {
    die("<div style='padding:20px; text-align:center; font-family:sans-serif;'>
            <h2 style='color:#ef4444;'>Akses Ditolak</h2>
            <p>Hanya Super Admin yang diizinkan mengatur hak akses.</p>
            <a href='index.php'>Kembali ke Dashboard</a>
         </div>");
}

$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'Super Admin', ENT_QUOTES, 'UTF-8');

// Ambil user yang SUDAH memiliki akses untuk masing-masing modul
// Agar bisa kita preload ke Select2
$moduls = [
    'satu_sehat_mapping_obat'      => ['judul' => 'Mapping Obat', 'color' => '#4f46e5', 'icon' => 'fa-pills'],
    'satu_sehat_mapping_lab'       => ['judul' => 'Mapping Lab', 'color' => '#0891b2', 'icon' => 'fa-flask'],
    'satu_sehat_mapping_radiologi' => ['judul' => 'Mapping Radiologi', 'color' => '#be185d', 'icon' => 'fa-x-ray'],
    'satu_sehat_mapping_vaksin'    => ['judul' => 'Mapping Vaksin', 'color' => '#059669', 'icon' => 'fa-syringe']
];

$preloaded_users = [];
foreach ($moduls as $col => $info) {
    $stmt = $pdo->query("
        SELECT 
            AES_DECRYPT(u.id_user, 'nur') as id, 
            COALESCE(p.nama, d.nm_dokter, pt.nama, 'Unknown') as nama
        FROM user u
        LEFT JOIN pegawai p ON AES_DECRYPT(u.id_user, 'nur') = p.nik
        LEFT JOIN dokter d ON AES_DECRYPT(u.id_user, 'nur') = d.kd_dokter
        LEFT JOIN petugas pt ON AES_DECRYPT(u.id_user, 'nur') = pt.nip
        WHERE u.$col = 'true'
    ");
    $arr = [];
    while ($row = $stmt->fetch()) {
        if ($row['id']) {
            $arr[] = [
                'id' => $row['id'], 
                'text' => $row['id'] . ' - ' . $row['nama']
            ];
        }
    }
    $preloaded_users[$col] = $arr;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manajemen Hak Akses — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="shortcut icon" href="logo.php">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        .hero { background: linear-gradient(135deg, #1e1b4b, #4f46e5); color: white; padding: 2.5rem 0; margin-bottom: 2rem; border-radius: 0 0 24px 24px; box-shadow: 0 10px 30px rgba(79,70,229,0.2); }
        .hero h2 { font-weight: 800; font-size: 1.8rem;}
        .nav-back { color: #a5b4fc; text-decoration: none; font-size: 0.9rem; font-weight: 500; display: inline-block; margin-bottom: 1rem; transition: color 0.2s;}
        .nav-back:hover { color: #fff; }
        
        .modul-card { border: none; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 1.5rem; overflow: hidden; }
        .modul-header { padding: 1.25rem 1.5rem; color: white; font-weight: 700; display: flex; align-items: center; justify-content: space-between; }
        .modul-body { padding: 1.5rem; background: white; }
        
        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
            background-color: #f1f5f9; border-color: #cbd5e1; color: #334155; font-size: 0.85rem; border-radius: 8px; padding: 2px 8px;
        }
        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove {
            color: #ef4444; margin-right: 6px;
        }
    </style>
</head>
<body>

<div class="hero">
    <div class="container">
        <a href="index.php" class="nav-back"><i class="fa fa-arrow-left me-2"></i>Kembali ke Dashboard</a>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1"><i class="fa-solid fa-users-gear me-2 text-indigo-300"></i>Manajemen Hak Akses</h2>
                <p class="mb-0 text-indigo-200">Tugaskan user spesifik untuk mengakses modul mapping.</p>
            </div>
            <div class="text-end d-none d-md-block">
                <div class="badge bg-white text-indigo-900 px-3 py-2 rounded-pill shadow-sm">
                    <i class="fa fa-crown me-2 text-warning"></i>Role: <?= $user_name ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row">
        <?php foreach ($moduls as $col => $info): ?>
        <div class="col-md-6">
            <div class="card modul-card">
                <div class="modul-header" style="background: <?= $info['color'] ?>;">
                    <div><i class="fa <?= $info['icon'] ?> me-2"></i><?= $info['judul'] ?></div>
                    <span class="badge bg-white text-dark rounded-pill" style="font-size: 0.7rem; font-weight: 700;">
                        <span id="count-<?= $col ?>"><?= count($preloaded_users[$col]) ?></span> User
                    </span>
                </div>
                <div class="modul-body">
                    <label class="form-label fw-bold text-muted small">Cari & Pilih User (Bisa lebih dari satu)</label>
                    <div class="input-group mb-3">
                        <select class="form-select select2-user" id="sel-<?= $col ?>" multiple="multiple" style="width: 100%;">
                            <?php foreach ($preloaded_users[$col] as $u): ?>
                                <option value="<?= htmlspecialchars($u['id'], ENT_QUOTES, 'UTF-8') ?>" selected="selected">
                                    <?= htmlspecialchars($u['text'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-sm text-white w-100 fw-bold btn-save" data-modul="<?= $col ?>" style="background: <?= $info['color'] ?>;">
                        <i class="fa fa-save me-1"></i> Simpan Akses <?= $info['judul'] ?>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Alert Edukasi -->
    <div class="alert alert-info border-0 shadow-sm rounded-4 mt-4" style="background: #eff6ff;">
        <h6 class="fw-bold text-primary mb-2"><i class="fa fa-info-circle me-2"></i>Tentang Akses Super Admin</h6>
        <p class="mb-0 small text-primary-emphasis">
            Anda login sebagai <strong>Super Admin</strong> (diotentikasi dari tabel <code>admin</code> Khanza). 
            Super Admin memiliki akses penuh (<em>bypass</em>) ke seluruh modul tanpa perlu dimasukkan ke dalam daftar di atas.
            Daftar di atas hanya digunakan untuk membuka akses bagi <strong>User Biasa</strong> yang login menggunakan NIK/Username dari tabel <code>user</code>.
        </p>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?>';

$(function() {
    // Inisialisasi Select2 AJAX
    $('.select2-user').select2({
        theme: 'bootstrap-5',
        placeholder: 'Ketik username...',
        minimumInputLength: 2,
        ajax: {
            url: 'ajax_hak_akses.php?action=search_user',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { term: params.term };
            },
            processResults: function(data) {
                return { results: data.results };
            }
        }
    }).on('change', function() {
        // Update counter badge
        const modul = $(this).attr('id').replace('sel-', '');
        $('#count-' + modul).text($(this).val().length);
    });

    // Simpan Data
    $('.btn-save').click(function() {
        const btn = $(this);
        const modul = btn.data('modul');
        const selId = '#sel-' + modul;
        const users = $(selId).val() || [];
        
        const origText = btn.html();
        btn.html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);

        $.post('ajax_hak_akses.php?action=save_akses', {
            csrf_token: CSRF_TOKEN,
            modul: modul,
            users: users
        }, function(res) {
            btn.html(origText).prop('disabled', false);
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success', 
                    title: 'Berhasil!', 
                    text: res.message, 
                    timer: 2000, 
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } else {
                Swal.fire('Gagal!', res.message, 'error');
            }
        }, 'json').fail(function() {
            btn.html(origText).prop('disabled', false);
            Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
        });
    });
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

<!-- Footer copyright (Anti-Tampering — JANGAN DIHAPUS) -->
<div class="text-center py-4 text-muted small" id="footer-credit-block" style="font-size:0.75rem;">
    &copy; <a href="https://saweria.co/ichsanleonhart" style="color:inherit;text-decoration:none;">Ichsan Leonhart</a> &nbsp;·&nbsp;
    <a href="https://wa.me/6285726123777" style="color:inherit;text-decoration:none;">6285726123777</a> &nbsp;·&nbsp;
    <a href="https://t.me/IchsanLeonhart" style="color:inherit;text-decoration:none;">@IchsanLeonhart</a> &nbsp;·&nbsp;
    <a href="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" style="color:inherit;text-decoration:none;">QRIS Donasi</a>
    — saweria.co/ichsanleonhart
</div>
</body>
</html>
