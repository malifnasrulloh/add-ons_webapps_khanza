<?php
require_once('csrf.php');
// Pastikan hanya Super Admin yang bisa mengakses (Aturan #0 Keamanan)
if (!isset($_SESSION['casemix_login']) || $_SESSION['casemix_role'] !== 'Super Admin') { 
    header("Location: index.php"); 
    exit; 
}
require_once('../conf/conf.php');
$koneksi = bukakoneksi();

// Generate CSRF Token secara kriptografi kuat jika belum ada (Aturan #0 Keamanan)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$q_set = mysqli_query($koneksi, "SELECT nama_instansi, logo FROM setting LIMIT 1");
$r_set = mysqli_fetch_assoc($q_set);
$nama_instansi = $r_set['nama_instansi'];
$logo_b64 = isset($r_set['logo']) ? 'data:image/jpeg;base64,' . base64_encode($r_set['logo']) : 'logo.php';

$nama_user_login = $_SESSION['casemix_user'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - <?= htmlspecialchars($nama_instansi) ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Select2 CSS & Bootstrap 5 Theme -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        body { overflow-x: hidden; background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; font-size: 0.9rem; }
        .top-navbar { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); padding: 10px 20px; }
        .card { border-radius: 12px; }
        
        /* Sidebar styles */
        #sidebar-wrapper { min-height: 100vh; width: 250px; margin-left: -250px; position: fixed; top: 0; left: 0; bottom: 0; z-index: 1000; transition: margin .25s ease-out; background: linear-gradient(180deg, #1e3c72 0%, #2a5298 100%); color: #fff; box-shadow: 4px 0 15px rgba(0,0,0,0.1); }
        #sidebar-wrapper .sidebar-heading { padding: 1.2rem 1rem; font-size: 1.1rem; border-bottom: 1px solid rgba(255,255,255,0.15); }
        #sidebar-wrapper .list-group { width: 250px; }
        #sidebar-wrapper .list-group-item { background: transparent; color: rgba(255,255,255,0.85); border: none; padding: 12px 20px; }
        #sidebar-wrapper .list-group-item:hover { background: rgba(255,255,255,0.15); color: #fff; border-left: 4px solid #fff; }
        #sidebar-wrapper .list-group-item.active { background: rgba(255,255,255,0.2); color: #fff; font-weight: bold; border-left: 4px solid #4cd137; }
        
        #page-content-wrapper { width: 100%; transition: margin .25s ease-out; }
        body.sb-sidenav-toggled #sidebar-wrapper { margin-left: 0; }
        @media (min-width: 768px) {
            #sidebar-wrapper { margin-left: 0; }
            #page-content-wrapper { margin-left: 250px; }
            body.sb-sidenav-toggled #sidebar-wrapper { margin-left: -250px; }
            body.sb-sidenav-toggled #page-content-wrapper { margin-left: 0; }
        }
        #overlay { display: none; position: fixed; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 900; }
        body.sb-sidenav-toggled #overlay { display: block; }
        @media (min-width: 768px) { body.sb-sidenav-toggled #overlay { display: none; } }
        
        /* Select2 specific styling for modern glassmorphism / premium feel */
        .select2-container--bootstrap-5 .select2-selection {
            border: 1px solid #ced4da;
            border-radius: 8px;
            padding: 0.375rem 0.75rem;
            min-height: 45px;
            display: flex;
            align-items: center;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            line-height: normal;
        }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">
    <!-- Overlay for mobile sidebar -->
    <div id="overlay" onclick="toggleMenu()"></div>
    
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <nav class="top-navbar d-flex justify-content-between align-items-center sticky-top">
            <button class="btn btn-outline-secondary border-0" id="menu-toggle"><i class="fas fa-bars fa-lg"></i></button>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-md-block line-height-sm">
                    <div class="fw-bold text-dark small"><?= htmlspecialchars($nama_user_login) ?></div>
                    <small class="text-muted" style="font-size:0.75rem">Super Admin</small>
                </div>
                <!-- Gunakan Avatar generik -->
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_user_login) ?>&background=random" class="rounded-circle border" width="35">
            </div>
        </nav>

        <div class="container-fluid px-4 py-4">
            
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0 bg-white">
                        <div class="card-body p-4">
                            <h5 class="fw-bold text-primary mb-3"><i class="fas fa-user-plus me-2"></i> Tambah Hak Akses Casemix</h5>
                            <p class="text-muted small">Cari nama pengguna atau ID pengguna (Ketik minimal 3 huruf), lalu pilih untuk langsung memberikan hak akses.</p>
                            
                            <div class="row align-items-center">
                                <div class="col-md-8 mb-3 mb-md-0">
                                    <!-- Select2 Dropdown -->
                                    <select id="userSelect" class="form-select" data-placeholder="Ketik nama atau ID user..."></select>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" id="btnGrantAccess" class="btn btn-primary w-100 fw-bold shadow-sm" style="min-height: 45px; border-radius: 8px;">
                                        <i class="fas fa-check-circle me-1"></i> Berikan Akses
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="m-0 fw-bold text-dark"><i class="fas fa-users me-2 text-primary"></i> Daftar Pengguna Aktif (Casemix)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tableUser" class="table table-striped table-hover w-100 align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width:20%">ID User</th>
                                    <th>Nama Pengguna</th>
                                    <th class="text-center" style="width:15%">Status Akses</th>
                                    <th class="text-center" style="width:15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch users using native PDO as per Aturan #11
                                try {
                                    $pdo = new PDO("mysql:host={$db_hostname};dbname={$db_name};charset=utf8mb4", $db_username, $db_password);
                                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                    
                                    // Ambil daftar kolom dinamis untuk hak akses INACBG
                                    $q_cols = $pdo->query("SHOW COLUMNS FROM user WHERE Field LIKE '%inacbg%' AND Type LIKE 'enum%'");
                                    $where_clauses = [];
                                    while ($col = $q_cols->fetch(PDO::FETCH_ASSOC)) {
                                        $where_clauses[] = "u." . $col['Field'] . " = 'true'";
                                    }
                                    
                                    $where_sql = empty($where_clauses) ? "1=0" : implode(" OR ", $where_clauses);

                                    $sql = "SELECT AES_DECRYPT(u.id_user, 'nur') as id_user, 
                                            COALESCE(p.nama, d.nm_dokter, ptx.nama, AES_DECRYPT(u.id_user, 'nur')) as nama
                                            FROM user u
                                            LEFT JOIN pegawai p ON p.nik = AES_DECRYPT(u.id_user, 'nur')
                                            LEFT JOIN dokter d ON d.kd_dokter = AES_DECRYPT(u.id_user, 'nur')
                                            LEFT JOIN petugas ptx ON ptx.nip = AES_DECRYPT(u.id_user, 'nur')
                                            WHERE $where_sql
                                            ORDER BY nama ASC";
                                    
                                    $stmt = $pdo->query($sql);
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($row['id_user']) ?></td>
                                            <td><?= htmlspecialchars($row['nama']) ?></td>
                                            <td class="text-center"><span class="badge bg-success"><i class="fas fa-check me-1"></i> Aktif</span></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-danger btn-revoke" data-id="<?= htmlspecialchars($row['id_user']) ?>" title="Cabut Akses">
                                                    <i class="fas fa-ban"></i> Cabut
                                                </button>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } catch (PDOException $e) {
                                    echo "<tr><td colspan='4' class='text-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- CSRF Token Meta untuk keamanan request AJAX -->
<meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    const toggleBtn = document.getElementById("menu-toggle");
    const overlay = document.getElementById("overlay");
    function toggleMenu() { document.body.classList.toggle("sb-sidenav-toggled"); }
    if(toggleBtn) toggleBtn.onclick = toggleMenu;

    $(document).ready(function() {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        
        // Inisialisasi DataTable dan simpan ke variabel untuk manipulasi DOM
        const table = $('#tableUser').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' }
        });

        // Initialize Select2 with AJAX support
        $('#userSelect').select2({
            theme: 'bootstrap-5',
            ajax: {
                url: 'ajax_user_management.php',
                dataType: 'json',
                delay: 250, // Delay request to save bandwidth
                data: function (params) {
                    return {
                        action: 'search',
                        q: params.term 
                    };
                },
                processResults: function (data) {
                    return { results: data.results };
                },
                cache: true
            },
            minimumInputLength: 3,
            language: {
                inputTooShort: function() { return "Ketik minimal 3 huruf..."; },
                noResults: function() { return "User tidak ditemukan"; },
                searching: function() { return "Mencari data..."; }
            }
        });

        // Event: Grant Access (Dopamine Visual Feedback - Aturan #12)
        $('#btnGrantAccess').click(function() {
            const userId = $('#userSelect').val();
            const btn = $(this);

            if (!userId) {
                // Notifikasi visual tanpa alert() browser
                btn.removeClass('btn-primary').addClass('btn-warning').html('<i class="fas fa-exclamation-triangle"></i> Pilih user dulu!');
                setTimeout(() => { 
                    btn.removeClass('btn-warning').addClass('btn-primary').html('<i class="fas fa-check-circle me-1"></i> Berikan Akses'); 
                }, 2000);
                return;
            }

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Proses...');

            $.post('ajax_user_management.php', {
                action: 'update_access',
                type: 'grant',
                id_user: userId,
                csrf_token: csrfToken
            }, function(response) {
                if (response.status === 'success') {
                    // Dopamine feedback (sukses hijau)
                    btn.removeClass('btn-primary').addClass('btn-success').html('<i class="fas fa-check"></i> Akses Diberikan!');
                    
                    // Ekstrak nama pengguna dari text Select2 (Format: "Nama (ID)")
                    let selectText = $('#userSelect option:selected').text();
                    let namaUser = selectText.replace(' (' + userId + ')', '');
                    
                    // Tambahkan baris baru ke DataTable secara dinamis tanpa refresh
                    let aksiBtn = '<button class="btn btn-sm btn-outline-danger btn-revoke" data-id="'+userId+'" title="Cabut Akses"><i class="fas fa-ban"></i> Cabut</button>';
                    let statusBadge = '<span class="badge bg-success"><i class="fas fa-check me-1"></i> Aktif</span>';
                    
                    table.row.add([
                        '<span class="fw-bold">' + userId + '</span>',
                        namaUser,
                        '<div class="text-center">' + statusBadge + '</div>',
                        '<div class="text-center">' + aksiBtn + '</div>'
                    ]).draw(false);
                    
                    // Bersihkan input Select2
                    $('#userSelect').val(null).trigger('change');
                    
                    setTimeout(() => { 
                        btn.removeClass('btn-success').addClass('btn-primary').html('<i class="fas fa-check-circle me-1"></i> Berikan Akses');
                        btn.prop('disabled', false);
                    }, 1500);
                } else {
                    btn.prop('disabled', false).removeClass('btn-primary').addClass('btn-danger').html('<i class="fas fa-times"></i> Gagal');
                    setTimeout(() => { 
                        btn.removeClass('btn-danger').addClass('btn-primary').html('<i class="fas fa-check-circle me-1"></i> Berikan Akses'); 
                    }, 2500);
                }
            }, 'json').fail(function() {
                // Notifikasi visual error jaringan tanpa alert() default
                btn.prop('disabled', false).removeClass('btn-primary').addClass('btn-danger').html('<i class="fas fa-wifi"></i> Error Jaringan');
                setTimeout(() => { 
                    btn.removeClass('btn-danger').addClass('btn-primary').html('<i class="fas fa-check-circle me-1"></i> Berikan Akses'); 
                }, 2500);
            });
        });

        // Event: Revoke Access (Harus pakai event delegation karena ada row yang ditambahkan dinamis)
        $('#tableUser tbody').on('click', '.btn-revoke', function() {
            const userId = $(this).data('id');
            const btn = $(this);
            const tr = btn.closest('tr');
            
            // Inline Confirmation Mechanism (Anti prompt()/confirm() block)
            if (!btn.hasClass('confirming')) {
                btn.addClass('confirming btn-danger').removeClass('btn-outline-danger').html('Yakin?');
                
                // Reset setelah 3 detik jika tidak diklik lagi
                setTimeout(() => {
                    if (btn.hasClass('confirming')) {
                        btn.removeClass('confirming btn-danger').addClass('btn-outline-danger').html('<i class="fas fa-ban"></i> Cabut');
                    }
                }, 3000);
                return;
            }

            // Jika diklik kedua kali
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.post('ajax_user_management.php', {
                action: 'update_access',
                type: 'revoke',
                id_user: userId,
                csrf_token: csrfToken
            }, function(response) {
                if (response.status === 'success') {
                    btn.removeClass('btn-danger').addClass('btn-success').html('<i class="fas fa-check"></i>');
                    setTimeout(() => { 
                        table.row(tr).remove().draw(false);
                    }, 500);
                } else {
                    btn.prop('disabled', false).html('<i class="fas fa-ban"></i> Cabut');
                }
            }, 'json').fail(function() {
                btn.prop('disabled', false).html('<i class="fas fa-ban"></i> Cabut');
            });
        });
    });
</script>
</body>
</html>
