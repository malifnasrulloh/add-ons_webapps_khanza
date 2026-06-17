<?php
/*
 * File: laporan_indikator_ranap.php
 * Fungsi: Halaman Utama Indikator Rawat Inap (Barber Johnson)
 */
require_once('csrf.php');
if (!isset($_SESSION['casemix_login']) || $_SESSION['casemix_login'] !== true) { header("Location: index.php"); exit; }
require_once('../conf/conf.php');
$koneksi = bukakoneksi();

// Data Instansi & User (Standard)
$q_set = mysqli_query($koneksi, "SELECT nama_instansi, logo FROM setting LIMIT 1");
$r_set = mysqli_fetch_assoc($q_set);
$nama_instansi = $r_set['nama_instansi'];
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
    <title>Indikator Ranap - <?= $nama_instansi ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { overflow-x: hidden; background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; font-size: 0.9rem; }
        /* Sidebar Style (Sama dengan file lain) */
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
        
        /* Card Indikator */
        .border-left-primary { border-left: 4px solid #4e73df !important; }
        .border-left-success { border-left: 4px solid #1cc88a !important; }
        .border-left-info { border-left: 4px solid #36b9cc !important; }
        .border-left-warning { border-left: 4px solid #f6c23e !important; }
        .border-left-danger { border-left: 4px solid #e74a3b !important; }
        .border-left-dark { border-left: 4px solid #5a5c69 !important; }
        .text-gray-800 { color: #5a5c69 !important; }
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
                    <h5 class="fw-bold text-primary mb-3 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-chart-line me-2"></i>Indikator Pelayanan Rawat Inap (Barber Johnson)</span>
                        <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalJuknis">
                            <i class="fas fa-info-circle me-1"></i> Petunjuk Teknis
                        </button>
                    </h5>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Dari Tanggal (Pulang)</label>
                            <input type="date" id="tgl_awal" class="form-control" value="<?= $tgl_awal ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Sampai Tanggal (Pulang)</label>
                            <input type="date" id="tgl_akhir" class="form-control" value="<?= $tgl_akhir ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary w-100" onclick="loadAllData()"><i class="fas fa-sync-alt me-1"></i> Hitung Data</button>
                        </div>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-bold" id="global-tab" data-bs-toggle="tab" data-bs-target="#global" type="button">Laporan Global (RS)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="bangsal-tab" data-bs-toggle="tab" data-bs-target="#bangsal" type="button">Per Bangsal</button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                
                <div class="tab-pane fade show active" id="global" role="tabpanel">
                    <div class="alert alert-light border shadow-sm mb-4">
                        <h6 class="fw-bold text-secondary"><i class="fas fa-database me-2"></i>Data Dasar Perhitungan (RS):</h6>
                        <div class="row text-center mt-3" id="data-dasar-container">
                            <div class="col">Loading...</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">BOR (Occupancy)</div>
                                            <div class="h3 mb-0 fw-bold text-gray-800" id="val-bor">...</div>
                                            <small class="text-muted">Target: 60-85%</small>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-bed fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs fw-bold text-success text-uppercase mb-1">ALOS (Length of Stay)</div>
                                            <div class="h3 mb-0 fw-bold text-gray-800" id="val-alos">...</div>
                                            <small class="text-muted">Target: 6-9 Hari</small>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-clock fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs fw-bold text-info text-uppercase mb-1">TOI (Turn Over Interval)</div>
                                            <div class="h3 mb-0 fw-bold text-gray-800" id="val-toi">...</div>
                                            <small class="text-muted">Target: 1-3 Hari</small>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-sync-alt fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">BTO (Bed Turn Over)</div>
                                            <div class="h3 mb-0 fw-bold text-gray-800" id="val-bto">...</div>
                                            <small class="text-muted">Target: 40-50 Kali/Th</small>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-people-arrows fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">NDR (Net Death Rate)</div>
                                            <div class="h3 mb-0 fw-bold text-gray-800" id="val-ndr">...</div>
                                            <small class="text-muted">< 25 per 1000</small>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-heart-broken fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-left-dark shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs fw-bold text-dark text-uppercase mb-1">GDR (Gross Death Rate)</div>
                                            <div class="h3 mb-0 fw-bold text-gray-800" id="val-gdr">...</div>
                                            <small class="text-muted">< 45 per 1000</small>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-cross fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="bangsal" role="tabpanel">
                    <div class="alert alert-warning small">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Catatan:</strong> Untuk perhitungan per bangsal, pasien "Pindah Kamar" dihitung sebagai Pasien Keluar (Discharge) dari bangsal tersebut.
                    </div>
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="table-bangsal" class="table table-striped table-hover w-100" style="font-size:0.85rem">
                                    <thead class="table-dark text-center">
                                        <tr>
                                            <th>Nama Bangsal</th>
                                            <th>Bed</th>
                                            <th>Hari Rawat</th>
                                            <th>Pasien Keluar</th>
                                            <th>BOR (%)</th>
                                            <th>ALOS</th>
                                            <th>TOI</th>
                                            <th>BTO</th>
                                            <th>NDR</th>
                                            <th>GDR</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-center"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="kelas" role="tabpanel">
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Catatan Logika Kelas:</strong> Laporan ini mengelompokkan indikator secara <i>mutually exclusive</i> dengan urutan prioritas: <b>Bed Bayi > Isolasi > Intensive > Enum Kelas Kamar (VVIP, VIP, 1, 2, 3)</b>.
                    </div>
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="table-kelas" class="table table-striped table-hover w-100" style="font-size:0.85rem">
                                    <thead class="table-dark text-center">
                                        <tr>
                                            <th>Grup / Kelas Kamar</th>
                                            <th>Bed</th>
                                            <th>Hari Rawat</th>
                                            <th>Pasien Keluar</th>
                                            <th>BOR (%)</th>
                                            <th>ALOS</th>
                                            <th>TOI</th>
                                            <th>BTO</th>
                                            <th>NDR</th>
                                            <th>GDR</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-center"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div> </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>

<script>
    // Sidebar Toggle
    document.getElementById("menu-toggle").onclick = function () { document.body.classList.toggle("sb-sidenav-toggled"); };

    var tableBangsal;
    var tableKelas;

    $(document).ready(function() {
        // Init DataTable Bangsal
        tableBangsal = $('#table-bangsal').DataTable({
            "responsive": true,
            "dom": 'Bfrtip',
            "buttons": [
                { extend: 'excelHtml5', className: 'btn btn-success btn-sm', title: 'Laporan Indikator Per Bangsal' }
            ],
            "columns": [
                { "data": "bangsal", className: "text-start fw-bold" },
                { "data": "bed" },
                { "data": "hp" },
                { "data": "d" },
                { "data": "bor", className: "fw-bold text-primary" },
                { "data": "alos" },
                { "data": "toi" },
                { "data": "bto" },
                { "data": "ndr", className: "text-danger" },
                { "data": "gdr" }
            ],
            "language": { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' }
        });

        // Init DataTable Kelas
        tableKelas = $('#table-kelas').DataTable({
            "responsive": true,
            "dom": 'Bfrtip',
            "buttons": [
                { extend: 'excelHtml5', className: 'btn btn-success btn-sm', title: 'Laporan Indikator Per Kelas' }
            ],
            "columns": [
                { "data": "kelas", className: "text-start fw-bold" },
                { "data": "bed" },
                { "data": "hp" },
                { "data": "d" },
                { "data": "bor", className: "fw-bold text-primary" },
                { "data": "alos" },
                { "data": "toi" },
                { "data": "bto" },
                { "data": "ndr", className: "text-danger" },
                { "data": "gdr" }
            ],
            "language": { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' }
        });

        // Load awal
        loadAllData();
    });

    function loadAllData() {
        var tglAwal = $('#tgl_awal').val();
        var tglAkhir = $('#tgl_akhir').val();

        // 1. Load Data Global
        $('#data-dasar-container').html('<div class="col">Sedang menghitung...</div>');
        $.ajax({
            url: 'api/data_indikator_ranap.php',
            type: 'GET',
            data: { tgl_awal: tglAwal, tgl_akhir: tglAkhir },
            dataType: 'json',
            success: function(response) {
                updateUIGlobal(response);
            },
            error: function() { $('#data-dasar-container').html('<div class="col text-danger">Gagal memuat data global.</div>'); }
        });

        // 2. Load Data Bangsal (Reload DataTable)
        $.ajax({
            url: 'api/data_indikator_per_bangsal.php',
            type: 'GET',
            data: { tgl_awal: tglAwal, tgl_akhir: tglAkhir },
            dataType: 'json',
            success: function(response) {
                tableBangsal.clear();
                tableBangsal.rows.add(response.data);
                tableBangsal.draw();

                if (response.anomalies && response.anomalies.length > 0) {
                    showAnomalies(response.anomalies);
                }
            }
        });

        // 3. Load Data Kelas (Reload DataTable)
        $.ajax({
            url: 'api/data_indikator_per_kelas.php',
            type: 'GET',
            data: { tgl_awal: tglAwal, tgl_akhir: tglAkhir },
            dataType: 'json',
            success: function(response) {
                tableKelas.clear();
                tableKelas.rows.add(response.data);
                tableKelas.draw();
            }
        });
    }

    function showAnomalies(anomalies) {
        let listHtml = '<div class="text-start small mt-3"><table class="table table-sm table-bordered"><thead><tr class="bg-light"><th>Bangsal</th><th>BOR</th><th>HP</th><th>Kapasitas</th></tr></thead><tbody>';
        anomalies.forEach(a => {
            listHtml += `<tr><td>${a.bangsal}</td><td class="text-danger fw-bold">${a.bor}%</td><td>${parseFloat(a.hp).toFixed(2)}</td><td>${parseFloat(a.kapasitas).toFixed(2)}</td></tr>`;
        });
        listHtml += '</tbody></table><p class="mt-2 text-muted small"><b>Mengapa BOR > 100%?</b> Hal ini sering terjadi terutama pada unit <b>Perinatologi/Bayi</b>. Penyebabnya adalah penggunaan bed yang sangat dinamis atau adanya "Data Gantung" (pasien fisik sudah pulang tapi sistem belum). <br><br><b>Saran:</b> Lakukan pembersihan data berkala di modul Kamar Inap Khanza.</p></div>';

        Swal.fire({
            title: 'Deteksi Anomali Kapasitas!',
            html: `Beberapa bangsal memiliki tingkat hunian melebihi 100%. Mohon periksa kebersihan data.<br>${listHtml}`,
            icon: 'warning',
            confirmButtonText: 'Cek Detail Bangsal',
            width: '600px'
        });
    }

    function updateUIGlobal(data) {
        var d = data.data_dasar;
        var htmlDasar = `
            <div class="col-md-2 col-6 mb-2 border-end"><div class="h4 mb-0 fw-bold text-primary">${d.jumlah_bed}</div><small class="text-muted">Tempat Tidur</small></div>
            <div class="col-md-2 col-6 mb-2 border-end"><div class="h4 mb-0 fw-bold text-success">${d.hari_perawatan}</div><small class="text-muted">Hari Perawatan</small></div>
            <div class="col-md-2 col-6 mb-2 border-end"><div class="h4 mb-0 fw-bold text-info">${d.pasien_keluar}</div><small class="text-muted">Pasien Keluar</small></div>
            <div class="col-md-2 col-6 mb-2 border-end"><div class="h4 mb-0 fw-bold text-danger">${d.pasien_mati}</div><small class="text-muted">Mati (GDR)</small></div>
            <div class="col-md-2 col-6 mb-2 border-end"><div class="h4 mb-0 fw-bold text-dark">${d.pasien_mati_48}</div><small class="text-muted">Mati >48h (NDR)</small></div>
            <div class="col-md-2 col-6 mb-2"><div class="h4 mb-0 fw-bold text-secondary">${data.periode.hari}</div><small class="text-muted">Periode (Hari)</small></div>
        `;
        $('#data-dasar-container').html(htmlDasar);

        var i = data.indikator;
        $('#val-bor').text(i.bor + ' %');
        $('#val-alos').text(i.alos + ' Hari');
        $('#val-toi').text(i.toi + ' Hari');
        $('#val-bto').text(i.bto + ' Kali');
        $('#val-ndr').text(i.ndr + ' ‰');
        $('#val-gdr').text(i.gdr + ' ‰');

        if (data.global_anomaly) {
            Swal.fire({
                title: 'Anomali Data Rumah Sakit!',
                html: `BOR RS mencapai <b>${data.global_anomaly.bor}%</b>. Ini menunjukkan penggunaan tempat tidur melebihi kapasitas fisik RS.<br><br><small class="text-muted">Hal ini biasanya terjadi karena data pasien (terutama di Perinatologi/Bayi) yang lupa dipulangkan di sistem.</small>`,
                icon: 'error',
                confirmButtonText: 'Lihat Detail Per Bangsal'
            }).then(() => {
                const triggerEl = document.querySelector('#bangsal-tab');
                bootstrap.Tab.getOrCreateInstance(triggerEl).show();
            });
        }
    }
</script>

<!-- Modal Petunjuk Teknis Juknis -->
<div class="modal fade" id="modalJuknis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-book-open me-2"></i>Petunjuk Teknis Indikator (Barber Johnson)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size: 0.85rem; line-height: 1.5;">
                <div class="alert alert-primary">
                    <h6><i class="fas fa-chart-pie me-2"></i>Standar Indikator Pelayanan</h6>
                    <p>Halaman ini menyajikan indikator kinerja RS berdasarkan grafik Barber-Johnson (BOR, ALOS, TOI, BTO) serta angka kematian (NDR, GDR).</p>
                </div>
                <div class="alert alert-warning">
                    <h6><i class="fas fa-microchip me-2"></i>Logika Pengolahan Data (Deep Dive)</h6>
                    <p>Sistem ini dirancang untuk kompatibilitas tinggi di berbagai instalasi SIMRS Khanza dengan aturan:</p>
                    <ol class="ps-3 mb-2">
                        <li><b>Pembersihan Data Gantung:</b> Sistem mendeteksi record pasien yang belum dipulangkan sejak tahun-tahun lama. Record ini otomatis <b>DIBUANG</b> jika status registrasinya sudah bukan 'Dirawat'. Jika BOR tetap > 100%, berarti masih ada pasien aktif yang melebihi jumlah bed fisik.</li>
                        <li><b>Kalkulasi Presisi Detik:</b> Hari Perawatan dihitung berdasarkan selisih jam masuk dan jam keluar secara presisi (Time-Based). Jika pasien pindah kamar di hari yang sama, durasi akan terbagi proporsional, bukan dihitung dobel.</li>
                        <li><b>Mapping Bangsal Tanpa Hardcode:</b> Pengelompokan spesialisasi menggunakan <i>Keyword Search</i> pada nama bangsal. Kata kunci utama:
                             <div class="bg-white p-2 border rounded mt-1 small">
                                <b>ICU/NICU/PICU/HCU</b>; <b>Penyakit Dalam</b> (DALAM, INTERNA, IPD); 
                                <b>Anak</b> (ANAK); <b>Obstetri</b> (OBGIN, KANDUNGAN, VK); 
                                <b>Perinatologi</b> (BAYI, PERINATOLOGI).
                            </div>
                        </li>
                    </ol>
                    <hr>
                    <strong>Langkah Perbaikan Data:</strong>
                    <ul class="mb-0 small">
                        <li>Buka menu <b>Kamar Inap</b> di Khanza.</li>
                        <li>Cari pasien dengan status belum pulang (tgl keluar kosong).</li>
                        <li>Pastikan pasien yang sudah pulang secara fisik juga sudah di-checkout di sistem.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>
<?php mysqli_close($koneksi); ?>