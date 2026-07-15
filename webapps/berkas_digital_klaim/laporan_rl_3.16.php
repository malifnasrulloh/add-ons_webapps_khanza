<?php
/*
 * File: laporan_rl_3.16.php
 * Fungsi: Laporan RL 3.16 Rekapitulasi Kegiatan Pelayanan Keluarga Berencana
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
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-01-01'); 
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-12-31');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan RL 3.16 - Keluarga Berencana</title>
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
                        <span><i class="fas fa-female me-2"></i>Laporan RL 3.16 - Rekapitulasi Kegiatan Pelayanan Keluarga Berencana</span>
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
                                <tr>
                                    <th rowspan="2">No</th>
                                    <th rowspan="2" class="text-start">Metode Kontrasepsi</th>
                                    <th colspan="4" class="bg-primary text-white">Pelayanan KB</th>
                                    <th rowspan="2" class="bg-danger text-white">Komplikasi</th>
                                    <th rowspan="2" class="bg-danger text-white">Kegagalan</th>
                                    <th rowspan="2" class="bg-danger text-white">Efek Samping</th>
                                    <th rowspan="2" class="bg-danger text-white">Drop Out</th>
                                </tr>
                                <tr>
                                    <th class="bg-primary-subtle">Pasca Salin</th>
                                    <th class="bg-primary-subtle">Pasca Gugur</th>
                                    <th class="bg-primary-subtle">Interval</th>
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
                <h5 class="modal-title"><i class="fas fa-book-open me-2"></i>Petunjuk Teknis Pengisian RL 3.16</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size: 0.85rem; line-height: 1.5;">
                <div class="alert alert-primary">
                    <h6><i class="fas fa-file-alt me-2"></i>Petunjuk Teknis Kemenkes (Juknis SIRS 6.3)</h6>
                    <p>Melaporkan jumlah kegiatan KB berdasarkan metodenya (Tubektomi, Vasektomi, Implan, IUD, Suntik, Pil, Kondom, MAL) dan kategori pelayanan (Pasca Salin, Pasca Gugur, Interval).</p>
                </div>
                <div class="alert alert-warning">
                    <h6><i class="fas fa-code me-2"></i>Logika Teknis SIMKES Khanza</h6>
                    <p>Menghitung jumlah tindakan KB yang tercatat pada rawat jalan, difilter berdasarkan nama tindakan seperti Pemasangan IUD, Implant, Suntik, dll, serta mengaitkan dengan diagnosa pasca salin atau pasca gugur jika ada.</p>
                    <hr>
                    <strong>Sumber Data:</strong>
                    <ul class="mb-0">
                        <li>rawat_jl_dr (Tindakan Dokter Ralan)</li>
                        <li>rawat_jl_pr (Tindakan Perawat Ralan)</li>
                        <li>jns_perawatan (Kategori/Nama Tindakan)</li>
                        <li>diagnosa_pasien (Filter Pasca Salin/Gugur)</li>
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
                { data: "metode" },
                { data: "pasca_salin", className: "text-end" },
                { data: "pasca_gugur", className: "text-end" },
                { data: "interval", className: "text-end" },
                { data: "total_pelayanan", className: "text-end fw-bold" },
                { data: "komplikasi", className: "text-end" },
                { data: "kegagalan", className: "text-end" },
                { data: "efek_samping", className: "text-end" },
                { data: "drop_out", className: "text-end" }
            ],
            paging: false, info: false, searching: false
        });
        loadData();
    });

    function loadData() {
        $.ajax({
            url: 'api/data_rl_3_16.php',
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
