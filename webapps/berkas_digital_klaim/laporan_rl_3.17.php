<?php
/*
 * File: laporan_rl_3.17.php
 * Fungsi: Laporan RL 3.17 Rekapitulasi Kegiatan Pelayanan Farmasi RS - Pengadaan Obat
 */
require_once('csrf.php');
if (!isset($_SESSION['casemix_login']) || $_SESSION['casemix_login'] !== true) {
    header("Location: index.php"); exit;
}
require_once('../conf/conf.php');
$koneksi = bukakoneksi();

$q_set = mysqli_query($koneksi, "SELECT nama_instansi, logo FROM setting LIMIT 1");
$r_set = mysqli_fetch_assoc($q_set);
$nama_instansi = $r_set['nama_instansi'] ?? 'RS';
$logo_b64 = isset($r_set['logo']) ? 'data:image/jpeg;base64,' . base64_encode($r_set['logo']) : 'logo.php';
$nama_user_login = $_SESSION['namauser'] ?? "User";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan RL 3.17 - Pengadaan Obat</title>
    <link rel="icon" href="logo.php" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; font-size: 0.85rem; }
        .top-navbar { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); padding: 10px 20px; }
        .table th { vertical-align: middle; background-color: #f8f9fa; }
    
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
                <div class="text-end me-3 d-none d-md-block small">
                    <div class="fw-bold text-dark"><?= htmlspecialchars($nama_user_login) ?></div>
                    <small class="text-muted">Petugas Casemix</small>
                </div>
                <img src="<?= $logo_b64 ?>" class="rounded-circle border" width="35">
            </div>
        </nav>

        <div class="container-fluid px-4 py-4">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body">
                    <h5 class="fw-bold text-primary mb-3 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-pills me-2"></i>Laporan RL 3.17 - Rekapitulasi Kegiatan Pelayanan Farmasi RS (Pengadaan)</span>
                        <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalJuknis">
                            <i class="fas fa-info-circle me-1"></i> Petunjuk Teknis
                        </button>
                    </h5>
                    <p class="text-muted small">Laporan Tahunan Status Pengadaan dan Ketersediaan Obat.</p>
                    <button class="btn btn-primary btn-sm" onclick="loadData()"><i class="fas fa-sync me-1"></i> Refresh Data</button>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm w-100" id="dataTable">
                            <thead class="bg-dark text-white text-center">
                                <tr>
                                    <th width="5%">No</th>
                                    <th class="text-start">Golongan Obat</th>
                                    <th width="25%">Jumlah Item Obat</th>
                                    <th width="25%">Jumlah Item Obat Tersedia</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Petunjuk Teknis Juknis -->
<div class="modal fade" id="modalJuknis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-book-open me-2"></i>Petunjuk Teknis Pengisian RL 3.17</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size: 0.85rem; line-height: 1.5;">
                <div class="alert alert-primary">
                    <h6><i class="fas fa-file-alt me-2"></i>Petunjuk Teknis Kemenkes (Juknis SIRS 6.3)</h6>
                    <p>Melaporkan jumlah item obat dan ketersediaannya di rumah sakit berdasarkan penggolongan Fornas dan Non-Fornas, baik generik maupun non-generik.</p>
                </div>
                <div class="alert alert-warning">
                    <h6><i class="fas fa-code me-2"></i>Logika Teknis SIMKES Khanza</h6>
                    <p>Menghitung jumlah item obat unik yang pernah diterima (pengadaan) dalam periode tahunan dan mengecek sisa stok (ketersediaan) pada gudang farmasi di akhir periode.</p>
                    <hr>
                    <strong>Sumber Data:</strong>
                    <ul class="mb-0">
                        <li>databarang (Master Obat)</li>
                        <li>penerimaan_obat_7_3 (Data Pengadaan)</li>
                        <li>gudangbarang (Data Stok)</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
    var myTable;
    $(document).ready(function() {
        myTable = $('#dataTable').DataTable({
            columns: [
                { data: null, render: (d,t,r,m) => m.row + 1, className: "text-center fw-bold" },
                { data: "golongan" },
                { data: "jumlah_item", className: "text-end fw-bold" },
                { data: "jumlah_tersedia", className: "text-end fw-bold text-success" }
            ],
            paging: false, info: false, searching: false
        });
        loadData();
    });

    function loadData() {
        $.ajax({
            url: 'api/data_rl_3_17.php',
            success: (resp) => { myTable.clear().rows.add(resp.data).draw(); }
        });
    }
</script>
<script>
    document.getElementById("menu-toggle").onclick = function () { document.body.classList.toggle("sb-sidenav-toggled"); };
    function toggleMenu() { document.body.classList.remove("sb-sidenav-toggled"); }
</script>
</body>
</html>
