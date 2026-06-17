<?php
/*
 * File: laporan_rl_4.1.php
 * Fungsi: Laporan RL 4.1 Kompilasi Penyakit/Morbiditas Pasien Rawat Inap
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
    <title>Laporan RL 4.1 - Morbiditas Rawat Inap</title>
    <link rel="icon" href="logo.php" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; font-size: 0.75rem; }
        .top-navbar { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); padding: 10px 20px; }
        .table th { white-space: nowrap; text-align: center; vertical-align: middle; background-color: #f8f9fa; font-size: 0.7rem; }
        .table td { font-size: 0.7rem; }
        .sticky-col { position: sticky; left: 0; background-color: #fff !important; z-index: 5; }
    
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
                <div class="text-end me-3 d-none d-md-block">
                    <div class="fw-bold text-dark small"><?= htmlspecialchars($nama_user_login) ?></div>
                    <small class="text-muted">Petugas Casemix</small>
                </div>
                <img src="<?= $logo_b64 ?>" class="rounded-circle border" width="35">
            </div>
        </nav>

        <div class="container-fluid px-4 py-4">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body">
                    <h5 class="fw-bold text-primary mb-3 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-disease me-2"></i>Laporan RL 4.1 - Kompilasi Penyakit/Morbiditas Rawat Inap</span>
                        <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalJuknis">
                            <i class="fas fa-info-circle me-1"></i> Petunjuk Teknis
                        </button>
                    </h5>
                    <form id="filterForm" class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small">Dari Tanggal</label>
                            <input type="date" class="form-control form-control-sm" id="tgl_awal" value="<?= $tgl_awal ?>">
                        </div>
                        <div class="col-md-2">
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
                                    <th rowspan="3" class="sticky-col">Kode ICD-10</th>
                                    <th rowspan="3" class="text-start sticky-col" style="left: 60px;">Deskripsi Diagnosis</th>
                                    <th colspan="50">Kelompok Umur & Jenis Kelamin (L/P)</th>
                                    <th colspan="2" rowspan="2">Total Hidup & Mati</th>
                                    <th colspan="2" rowspan="2" class="bg-danger text-white">Total Mati</th>
                                </tr>
                                <tr>
                                    <?php 
                                    $ages = ['<1j','1-23j','1-7h','8-28h','29h-<3b','3-<6b','6-11b','1-4t','5-9t','10-14t','15-19t','20-24t','25-29t','30-34t','35-39t','40-44t','45-49t','50-54t','55-59t','60-64t','65-69t','70-74t','75-79t','80-84t','>=85t'];
                                    foreach($ages as $a) echo "<th colspan='2'>$a</th>"; 
                                    ?>
                                </tr>
                                <tr>
                                    <?php for($i=0; $i<27; $i++) echo "<th>L</th><th>P</th>"; ?>
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
                <h5 class="modal-title"><i class="fas fa-book-open me-2"></i>Petunjuk Teknis Pengisian RL 4.1</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size: 0.85rem; line-height: 1.5;">
                <div class="alert alert-primary">
                    <h6><i class="fas fa-file-alt me-2"></i>Petunjuk Teknis Kemenkes (Juknis SIRS 6.3)</h6>
                    <p>Melaporkan data morbiditas pasien rawat inap berdasarkan diagnosa utama (ICD-10) yang direkapitulasi menurut 25 kategori umur dan jenis kelamin untuk pasien keluar (hidup & mati).</p>
                </div>
                <div class="alert alert-warning">
                    <h6><i class="fas fa-code me-2"></i>Logika Teknis SIMKES Khanza</h6>
                    <p>Menyaring data diagnosa pasien dengan status 'Utama' pada pasien yang telah memiliki catatan keluar di rawat inap. Usia dihitung secara presisi berdasarkan tanggal lahir terhadap tanggal keluar untuk pengelompokan 25 kategori umur.</p>
                    <hr>
                    <strong>Sumber Data:</strong>
                    <ul class="mb-0">
                        <li><code>diagnosa_pasien</code> (Diagnosa Primer/Utama)</li>
                        <li><code>kamar_inap</code> (Data Pasien Keluar Hidup/Mati)</li>
                        <li><code>pasien</code> (Data Profil & Tanggal Lahir)</li>
                        <li><code>pasien_mati</code> (Data validasi kematian tambahan)</li>
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
        var columns = [
            { data: "kode", className: "sticky-col bg-light fw-bold" },
            { data: "diagnosis", className: "sticky-col bg-light", width: "200px" }
        ];
        for(let i=0; i<25; i++) {
            columns.push({ data: "u"+i+"_l", defaultContent: 0 });
            columns.push({ data: "u"+i+"_p", defaultContent: 0 });
        }
        columns.push({ data: "total_l", className: "fw-bold" });
        columns.push({ data: "total_p", className: "fw-bold" });
        columns.push({ data: "mati_l", className: "text-danger" });
        columns.push({ data: "mati_p", className: "text-danger" });

        myTable = $('#dataTable').DataTable({
            columns: columns,
            paging: true, pageLength: 50, info: true, searching: true, scrollX: true
        });
        loadData();
    });

    function loadData() {
        $.ajax({
            url: 'api/data_rl_4_1.php',
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
