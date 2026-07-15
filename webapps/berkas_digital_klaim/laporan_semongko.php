<?php
/*
 * File: laporan_semongko.php
 * Fungsi: Laporan Riwayat Bed Pasien BPJS (Exportable)
 */
require_once('csrf.php');
if (!isset($_SESSION['casemix_login']) || $_SESSION['casemix_login'] !== true) { header("Location: index.php"); exit; }
require_once('../conf/conf.php');
$koneksi = bukakoneksi();

// Data Instansi & User
$q_set = mysqli_query($koneksi, "SELECT nama_instansi, logo FROM setting LIMIT 1");
$r_set = mysqli_fetch_assoc($q_set);
$nama_instansi = $r_set['nama_instansi'];
$logo_b64 = isset($r_set['logo']) ? 'data:image/jpeg;base64,' . base64_encode($r_set['logo']) : 'logo.php';

$user_id = $_SESSION['casemix_user'];
$nama_user_login = $user_id; 
$q_pegawai = mysqli_query($koneksi, "SELECT nama FROM pegawai WHERE nik = '$user_id'");
if(mysqli_num_rows($q_pegawai) > 0){ $nama_user_login = mysqli_fetch_assoc($q_pegawai)['nama']; } 
else { $q_dok = mysqli_query($koneksi, "SELECT nm_dokter FROM dokter WHERE kd_dokter = '$user_id'"); if(mysqli_num_rows($q_dok) > 0) $nama_user_login = mysqli_fetch_assoc($q_dok)['nm_dokter']; }

// Filter Tanggal
$tgl_awal  = isset($_GET['tgl_awal']) ? validTeks4($_GET['tgl_awal'], 10) : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? validTeks4($_GET['tgl_akhir'], 10) : date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Semongko - <?= $nama_instansi ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { overflow-x: hidden; background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; font-size: 0.9rem; }
        
        /* Sidebar Style (Copied from Dashboard) */
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
        
        .top-navbar { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); padding: 10px 20px; }
        .table th { white-space: nowrap; text-align: center; vertical-align: middle; background-color: #f8f9fa; font-size: 0.85rem; }
        .table td { vertical-align: middle; font-size: 0.85rem; }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">
    <div id="overlay" onclick="toggleMenu()"></div>
    <?php include 'sidebar.php'; ?>

    <div id="page-content-wrapper">
        <nav class="top-navbar d-flex justify-content-between align-items-center sticky-top">
            <button class="btn btn-outline-secondary border-0" id="menu-toggle"><i class="fas fa-bars fa-lg"></i></button>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-md-block line-height-sm">
                    <div class="fw-bold text-dark small"><?= $nama_user_login ?></div>
                    <small class="text-muted" style="font-size:0.75rem">Petugas Casemix</small>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_user_login) ?>&background=random" class="rounded-circle border" width="35">
            </div>
        </nav>

        <div class="container-fluid px-4 py-4">
            
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body py-3">
                    <div class="row align-items-center mb-3">
                        <div class="col-md-12">
                            <h5 class="fw-bold text-primary mb-0"><i class="fas fa-bed me-2"></i>Laporan Bed Pasien BPJS (Semongko)</h5>
                            <small class="text-muted">Periode Close Kasir: <?= date('d/m/Y', strtotime($tgl_awal)) ?> s/d <?= date('d/m/Y', strtotime($tgl_akhir)) ?></small>
                        </div>
                    </div>
                    
                    <form action="" method="GET" class="row g-2 align-items-end">
                        <div class="col-md-3 col-6">
                            <label class="form-label fw-bold small text-secondary">Tgl Awal</label>
                            <input type="date" name="tgl_awal" class="form-control form-control-sm" value="<?= $tgl_awal ?>">
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label fw-bold small text-secondary">Tgl Akhir</label>
                            <input type="date" name="tgl_akhir" class="form-control form-control-sm" value="<?= $tgl_akhir ?>">
                        </div>
                        <div class="col-md-2 col-6">
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold"><i class="fas fa-search me-1"></i> Filter Data</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tableSemongko" class="table table-striped table-hover w-100" style="font-size:0.8rem;">
                            <thead class="bg-light">
                                <tr>
                                    <th>No. Rawat</th>
                                    <th>No. RM</th>
                                    <th>Nama Pasien</th>
                                    <th>No. SEP</th>
                                    <th>Bangsal / Kamar</th>
                                    <th>Kode Bed</th>
                                    <th>Kelas</th>
                                    <th>Tgl. Registrasi</th>
                                    <th>Tgl. Masuk</th>
                                    <th>Jam Masuk</th>
                                    <th>Tgl. Keluar</th>
                                    <th>Jam Keluar</th>
                                    <th>Tgl. Close</th>
                                    <th>Status</th>
                                    <th>Penjamin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT 
                                            reg_periksa.no_rawat, pasien.no_rkm_medis, pasien.nm_pasien, bridging_sep.no_sep,
                                            bangsal.nm_bangsal, kamar.kd_kamar, kamar.kelas, reg_periksa.tgl_registrasi,
                                            kamar_inap.tgl_masuk, kamar_inap.jam_masuk, kamar_inap.tgl_keluar, kamar_inap.jam_keluar,
                                            nota_inap.tanggal as tgl_close, reg_periksa.status_lanjut, penjab.png_jawab
                                        FROM bridging_sep
                                        RIGHT JOIN kamar_inap ON bridging_sep.no_rawat = kamar_inap.no_rawat
                                        JOIN kamar ON kamar_inap.kd_kamar = kamar.kd_kamar
                                        JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal
                                        JOIN reg_periksa ON kamar_inap.no_rawat = reg_periksa.no_rawat
                                        JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                                        JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
                                        LEFT JOIN nota_inap ON reg_periksa.no_rawat = nota_inap.no_rawat
                                        WHERE nota_inap.tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'
                                          AND bridging_sep.no_sep IS NOT NULL
                                        ORDER BY nota_inap.tanggal ASC, bridging_sep.no_sep";
                                
                                $hasil = mysqli_query($koneksi, $query);
                                if($hasil) {
                                    while($row = mysqli_fetch_assoc($hasil)) {
                                ?>
                                    <tr>
                                        <td><?= $row['no_rawat'] ?></td>
                                        <td><?= $row['no_rkm_medis'] ?></td>
                                        <td><?= $row['nm_pasien'] ?></td>
                                        <td><span class="badge bg-success"><?= $row['no_sep'] ?></span></td>
                                        <td><?= $row['nm_bangsal'] ?></td>
                                        <td><?= $row['kd_kamar'] ?></td>
                                        <td><?= $row['kelas'] ?></td>
                                        <td><?= $row['tgl_registrasi'] ?></td>
                                        <td><?= $row['tgl_masuk'] ?></td>
                                        <td><?= $row['jam_masuk'] ?></td>
                                        <td><?= $row['tgl_keluar'] ?></td>
                                        <td><?= $row['jam_keluar'] ?></td>
                                        <td><?= $row['tgl_close'] ?></td>
                                        <td><?= $row['status_lanjut'] ?></td>
                                        <td><?= $row['png_jawab'] ?></td>
                                    </tr>
                                <?php 
                                    } 
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<script>
    // Sidebar Toggle
    document.getElementById("menu-toggle").onclick = function () { document.body.classList.toggle("sb-sidenav-toggled"); };

    $(document).ready(function() {
        $('#tableSemongko').DataTable({
            dom: 'Bfrtip',
            pageLength: 25,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
            buttons: [
                { extend: 'excel', className: 'btn btn-success btn-sm', text: '<i class="fas fa-file-excel me-1"></i> Export Excel', title: 'Laporan Semongko BPJS' },
                { extend: 'print', className: 'btn btn-secondary btn-sm', text: '<i class="fas fa-print me-1"></i> Print' }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' }
        });
    });
</script>

</body>
</html>
<?php mysqli_close($koneksi); ?>