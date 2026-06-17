<?php
/*
 * File: laporan_rl_5.3.php
 * Fungsi: Laporan RL 5.3 10 Besar Kunjungan Penyakit Rawat Jalan
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
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-01'); 
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan RL 5.3 - 10 Besar Kunjungan Ralan</title>
    <link rel="icon" href="logo.php" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; font-size: 0.85rem; }
        .top-navbar { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); padding: 10px 20px; }
        .table th { white-space: nowrap; text-align: center; vertical-align: middle; background-color: #f8f9fa; }
    
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
                        <span><i class="fas fa-chart-line me-2"></i>Laporan RL 5.3 (10 Besar Kunjungan Penyakit Rawat Jalan)</span>
                        <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalJuknis">
                            <i class="fas fa-info-circle me-1"></i> Petunjuk Teknis
                        </button>
                    </h5>
                    <form id="filterForm" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small">Dari Tanggal</label>
                            <input type="date" class="form-control form-control-sm" id="tgl_awal" value="<?= $tgl_awal ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Sampai Tanggal</label>
                            <input type="date" class="form-control form-control-sm" id="tgl_akhir" value="<?= $tgl_akhir ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary btn-sm w-100" onclick="loadData()"><i class="fas fa-search me-1"></i> Tampilkan</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm w-100" id="dataTable">
                            <thead>
                                <tr class="bg-dark text-white text-center">
                                    <th rowspan="2">No</th>
                                    <th rowspan="2">Kelompok ICD-10</th>
                                    <th rowspan="2" class="text-start">Diagnosis</th>
                                    <th colspan="3" class="bg-primary text-white">Jumlah Kunjungan</th>
                                </tr>
                                <tr>
                                    <th class="bg-primary-subtle">L</th>
                                    <th class="bg-primary-subtle">P</th>
                                    <th class="bg-primary text-white">Total</th>
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
                <h5 class="modal-title"><i class="fas fa-book-open me-2"></i>Petunjuk Teknis Pengisian RL 5.3</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size: 0.85rem; line-height: 1.5;">
                <div class="alert alert-primary">
                    <h6><i class="fas fa-file-alt me-2"></i>Petunjuk Teknis Kemenkes (Juknis SIRS 6.3)</h6>
                    <p>Data 10 Besar Kunjungan Penyakit Rawat Jalan merupakan hasil dari Kompilasi Penyakit/Morbiditas Pasien Rawat Jalan (RL 5.1), yang dikelompokkan berdasarkan Kelompok ICD-10 kemudian diurutkan dari yang terbesar ke kecil berdasarkan total Kunjungan (Kasus Baru + Kasus Lama). Kode ICD-10 yang tidak masuk dalam 10 Besar yaitu O80, O82, R, V, W, X, Y, dan Z.</p>
                </div>
                <div class="alert alert-warning">
                    <h6><i class="fas fa-code me-2"></i>Logika Teknis SIMKES Khanza</h6>
                    <p>Sistem merangkum total kunjungan pasien rawat jalan berdasarkan diagnosa primer (prioritas 1) dari tabel <code>diagnosa_pasien</code>. Data dihitung berdasarkan setiap kedatangan pasien yang mendapatkan diagnosa tersebut dalam periode laporan, tanpa membedakan apakah itu kunjungan baru atau lama untuk diagnosa tersebut.</p>
                    <hr>
                    <strong>Sumber Data:</strong>
                    <ul class="mb-0">
                        <li><code>diagnosa_pasien</code> (Diagnosa utama/primer)</li>
                        <li><code>reg_periksa</code> (Data kunjungan rawat jalan)</li>
                        <li><code>pasien</code> (Data demografi)</li>
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
                { data: "kode_kelompok", className: "fw-bold" },
                { data: "diagnosis" },
                { data: "kunjungan_l", className: "text-end" },
                { data: "kunjungan_p", className: "text-end" },
                { data: "kunjungan_total", className: "text-end fw-bold" }
            ],
            paging: false, info: false, searching: false, ordering: false
        });
        loadData();
    });

    function loadData() {
        $.ajax({
            url: 'api/data_rl_5_3.php',
            data: { tgl_awal: $('#tgl_awal').val(), tgl_akhir: $('#tgl_akhir').val() },
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
