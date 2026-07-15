<?php
/*
 * File: laporan_penyakit.php (UPDATE V7 - TOP 20 & CHART HEIGHT)
 */
require_once('csrf.php');

if (!isset($_SESSION['casemix_login']) || $_SESSION['casemix_login'] !== true) {
    header("Location: index.php");
    exit;
}

require_once('../conf/conf.php');
$koneksi = bukakoneksi();

// Data Instansi & User
$nama_instansi = "RS Khanza";
$q_set = mysqli_query($koneksi, "SELECT nama_instansi, logo FROM setting LIMIT 1");
if($r_set = mysqli_fetch_assoc($q_set)) $nama_instansi = $r_set['nama_instansi'];
$logo_b64 = isset($r_set['logo']) ? 'data:image/jpeg;base64,' . base64_encode($r_set['logo']) : 'logo.php';

$user_id = $_SESSION['casemix_user'];
$nama_user_login = $user_id; 
$q_pegawai = mysqli_query($koneksi, "SELECT nama FROM pegawai WHERE nik = '$user_id'");
if(mysqli_num_rows($q_pegawai) > 0){ $nama_user_login = mysqli_fetch_assoc($q_pegawai)['nama']; } 
else { $q_dok = mysqli_query($koneksi, "SELECT nm_dokter FROM dokter WHERE kd_dokter = '$user_id'"); if(mysqli_num_rows($q_dok) > 0) $nama_user_login = mysqli_fetch_assoc($q_dok)['nm_dokter']; }

$tgl_awal  = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01'); 
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penyakit - <?= $nama_instansi ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { overflow-x: hidden; background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; font-size: 0.9rem; }
        
        #sidebar-wrapper { min-height: 100vh; width: 250px; margin-left: -250px; position: fixed; top: 0; left: 0; bottom: 0; z-index: 1000; transition: margin .25s ease-out; background: linear-gradient(180deg, #1e3c72 0%, #2a5298 100%); color: #fff; box-shadow: 4px 0 15px rgba(0,0,0,0.1); }
        #sidebar-wrapper .sidebar-heading { padding: 1.2rem 1rem; font-size: 1.1rem; border-bottom: 1px solid rgba(255,255,255,0.15); }
        #sidebar-wrapper .list-group { width: 250px; }
        #sidebar-wrapper .list-group-item { background: transparent; color: rgba(255,255,255,0.85); border: none; padding: 12px 20px; }
        #sidebar-wrapper .list-group-item:hover { background: rgba(255,255,255,0.15); color: #fff; border-left: 4px solid #fff; }
        #sidebar-wrapper .list-group-item.active { background: rgba(255,255,255,0.2); color: #fff; font-weight: bold; border-left: 4px solid #4cd137; }
        
        #page-content-wrapper { width: 100%; transition: margin .25s ease-out; }
        body.sb-sidenav-toggled #sidebar-wrapper { margin-left: 0; }
        @media (min-width: 768px) { #sidebar-wrapper { margin-left: 0; } #page-content-wrapper { margin-left: 250px; } body.sb-sidenav-toggled #sidebar-wrapper { margin-left: -250px; } body.sb-sidenav-toggled #page-content-wrapper { margin-left: 0; } }
        #overlay { display: none; position: fixed; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 900; }
        body.sb-sidenav-toggled #overlay { display: block; } @media (min-width: 768px) { body.sb-sidenav-toggled #overlay { display: none; } }
        
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
                    <h5 class="fw-bold text-primary mb-3"><i class="fas fa-filter me-2"></i>Filter Laporan</h5>
                    <form id="filterForm" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Dari Tanggal</label>
                            <input type="date" class="form-control" id="tgl_awal" value="<?= $tgl_awal ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Sampai Tanggal</label>
                            <input type="date" class="form-control" id="tgl_akhir" value="<?= $tgl_akhir ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Jenis Kunjungan</label>
                            <select class="form-select" id="status_lanjut">
                                <option value="">-- Semua --</option>
                                <option value="Ralan">Rawat Jalan</option>
                                <option value="Ranap">Rawat Inap</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary w-100" onclick="loadData()">
                                <i class="fas fa-search me-2"></i> Tampilkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-7 mb-4">
                    <div class="card shadow border-0 h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 fw-bold text-primary">Grafik Morbiditas (Top 20)</h6>
                        </div>
                        <div class="card-body">
                            <div style="height: 600px; position: relative;">
                                <canvas id="chartPenyakit"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 mb-4">
                    <div class="card shadow border-0 h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 fw-bold text-primary">Tabel Peringkat</h6>
                        </div>
                        <div class="card-body p-2">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-sm w-100" id="dataTable">
                                    <thead class="text-center" style="font-size:0.75rem;">
                                        <tr>
                                            <th rowspan="2" width="5%" class="align-middle" style="background-color: #343a40 !important; color: #ffffff !important;">No</th>
                                            <th rowspan="2" class="align-middle" style="background-color: #343a40 !important; color: #ffffff !important;">ICD - Penyakit</th>
                                            <th colspan="2" class="bg-success text-white py-1">Baru</th> 
                                            <th colspan="2" class="bg-primary text-white py-1">Lama</th>
                                            <th rowspan="2" width="10%" class="align-middle" style="background-color: #343a40 !important; color: #ffffff !important;">Total</th>
                                            <th rowspan="2" width="5%" class="align-middle" style="background-color: #343a40 !important; color: #ffffff !important;">Act</th>
                                        </tr>
                                        <tr>
                                            <th class="bg-success text-white py-1">L</th> 
                                            <th class="bg-success text-white py-1">P</th> 
                                            <th class="bg-primary text-white py-1">L</th>
                                            <th class="bg-primary text-white py-1">P</th>
                                        </tr>
                                    </thead>
                                    <tbody style="font-size:0.8rem;"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Detail Pasien: <span id="modalTitlePenyakit" class="fw-bold">...</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="tableDetail" class="table table-striped table-hover table-sm w-100" style="font-size:0.85rem">
                        <thead class="table-light">
                            <tr>
                                <th>No. Rawat</th>
                                <th>Tgl Reg</th>
                                <th>No. RM</th>
                                <th>Nama Pasien</th>
                                <th>L/P</th>
                                <th>Status</th>
                                <th>Alamat</th>
                                <th>Dokter</th>
                                <th>Penjamin</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<script>
    document.getElementById("menu-toggle").onclick = function () { document.body.classList.toggle("sb-sidenav-toggled"); };

    var myChart, myTable, detailTable; 

    $(document).ready(function() {
        // Init Table Summary
        myTable = $('#dataTable').DataTable({
            dom: 'Bfrtip',
            buttons: [ 
                { 
                    extend: 'excelHtml5', 
                    className: 'btn btn-success btn-sm mb-2', 
                    text: '<i class="fas fa-file-excel me-1"></i> Excel', 
                    title: 'Laporan 20 Besar Penyakit' 
                } 
            ],
            columns: [
                { data: null, render: (d,t,r,m) => m.row + 1, className: "text-center align-middle" },
                { 
                    data: "nama", 
                    render: function(data, type, row) {
                        return `<span class="fw-bold text-primary">${row.kode}</span><br><span class="small">${data}</span>`;
                    }
                },
                { data: "baru_l", className: "text-center bg-light align-middle" },
                { data: "baru_p", className: "text-center bg-light align-middle" },
                { data: "lama_l", className: "text-center align-middle" },
                { data: "lama_p", className: "text-center align-middle" },
                { data: "total", className: "text-end fw-bold bg-warning align-middle" },
                { 
                    data: null, className: "text-center align-middle",
                    render: function(data, type, row) {
                        return `<button class="btn btn-xs btn-info text-white" onclick="openDetail('${row.kode}', '${row.nama}')"><i class="fas fa-search"></i></button>`;
                    }
                }
            ],
            ordering: false, paging: false, searching: false, info: false
        });

        // Init Detail Table
        detailTable = $('#tableDetail').DataTable({
            dom: 'Bfrtip', 
            buttons: [ 
                { 
                    extend: 'excelHtml5', 
                    className: 'btn btn-success btn-sm', 
                    text: '<i class="fas fa-file-excel me-1"></i> Export Detail',
                    title: function() { return 'Detail Penyakit ' + $('#modalTitlePenyakit').text(); }
                } 
            ],
            pageLength: 20,
            columns: [
                { data: "no_rawat", className: "fw-bold small" }, 
                { data: "tgl_registrasi" }, 
                { data: "no_rkm_medis" },
                { data: "nm_pasien" }, 
                { data: "jk" }, 
                { 
                    data: "stts_daftar", 
                    render: function(data) {
                        return data === 'Baru' ? '<span class="badge bg-success">Baru</span>' : '<span class="badge bg-secondary">Lama</span>';
                    }
                },
                { data: "alamat_lengkap" }, 
                { data: "nm_dokter" }, 
                { data: "png_jawab" }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' }
        });

        loadData();
    });

    function loadData() {
        var params = {
            tgl_awal: $('#tgl_awal').val(),
            tgl_akhir: $('#tgl_akhir').val(),
            status_lanjut: $('#status_lanjut').val()
        };

        $.ajax({
            url: 'api/data_top_penyakit.php',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(resp) {
                renderChart(resp.chart);
                myTable.clear().rows.add(resp.table).draw();
            },
            error: function() { alert("Gagal memuat data. Pastikan API dapat diakses."); }
        });
    }

    function openDetail(kode, nama) {
        $('#modalTitlePenyakit').text(kode + " - " + nama);
        $('#modalDetail').modal('show');
        detailTable.clear().draw();
        
        $.ajax({
            url: 'api/data_detail_penyakit.php',
            type: 'GET',
            data: {
                tgl_awal: $('#tgl_awal').val(),
                tgl_akhir: $('#tgl_akhir').val(),
                status_lanjut: $('#status_lanjut').val(),
                kd_penyakit: kode
            },
            success: function(resp) {
                detailTable.rows.add(resp.data).draw();
            }
        });
    }

    function renderChart(data) {
        var ctx = document.getElementById("chartPenyakit").getContext('2d');
        if(myChart) myChart.destroy();

        myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: "Jumlah Kasus",
                    data: data.data,
                    backgroundColor: '#4e73df',
                    borderRadius: 4,
                    barPercentage: 0.7
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } }
            }
        });
    }
</script>

</body>
</html>
<?php mysqli_close($koneksi); ?>