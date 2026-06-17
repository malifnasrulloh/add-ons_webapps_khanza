<?php
/*
 * File: laporan_rl_3.5.php
 * Fungsi: Laporan RL 3.5 Rekapitulasi Kunjungan
 */
require_once('csrf.php');
if (!isset($_SESSION['casemix_login']) || $_SESSION['casemix_login'] !== true) {
    header("Location: index.php"); exit;
}
require_once('../conf/conf.php');
$koneksi = bukakoneksi();

$q_set = mysqli_query($koneksi, "SELECT nama_instansi, logo, kabupaten FROM setting LIMIT 1");
$r_set = mysqli_fetch_assoc($q_set);
$nama_instansi = $r_set['nama_instansi'] ?? 'RS';
$kab_rs = $r_set['kabupaten'] ?? 'KABUPATEN';
$logo_b64 = isset($r_set['logo']) ? 'data:image/jpeg;base64,' . base64_encode($r_set['logo']) : 'logo.php';
$nama_user_login = "User"; // Simplified for brevity

$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-01'); 
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan RL 3.5 - <?= $nama_instansi ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; font-size: 0.9rem; }
        /* Sidebar Style omitted for brevity - use previous sidebar code */
        .top-navbar { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); padding: 10px 20px; }
        
        .table th { white-space: nowrap; text-align: center; vertical-align: middle; background-color: #f8f9fa; font-size: 0.85rem; border: 1px solid #ddd; }
        .table td { vertical-align: middle; font-size: 0.85rem; border: 1px solid #ddd; }
        .table thead th { border-bottom-width: 2px; }
        
        table.dataTable thead th:nth-child(1), table.dataTable tbody td:nth-child(1),
        table.dataTable thead th:nth-child(2), table.dataTable tbody td:nth-child(2) {
            position: sticky;
            background-color: #f8f9fa;
            z-index: 1;
        }
        table.dataTable thead th:nth-child(1) { left: 0; z-index: 2; }
        table.dataTable tbody td:nth-child(1) { left: 0; }
        table.dataTable thead th:nth-child(2) { left: 50px; z-index: 2; }
        table.dataTable tbody td:nth-child(2) { left: 50px; text-align: left; }
        table.dataTable thead th:nth-child(2), table.dataTable tbody td:nth-child(2) { border-right: 2px solid #ccc; }

        .total-row { font-weight: bold; background-color: #fce4ec !important; }
        .avg-row { font-weight: bold; background-color: #e3f2fd !important; }
        
        .info-box { background: #fff3e0; border-left: 4px solid #ff9800; padding: 10px 15px; margin-bottom: 20px; border-radius: 4px; font-size: 0.85rem;}
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
                <img src="logo.php" class="rounded-circle border" width="35">
            </div>
        </nav>

        <div class="container-fluid px-4 py-4">
            
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body py-3">
                    <h5 class="fw-bold text-primary mb-3 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-walking me-2"></i>Laporan RL 3.5 (Rekapitulasi Kunjungan)</span>
                        <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalJuknis">
                            <i class="fas fa-info-circle me-1"></i> Petunjuk Teknis
                        </button>
                    </h5>
                    <form id="filterForm">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Dari Tanggal</label>
                                <input type="date" class="form-control" id="tgl_awal" value="<?= $tgl_awal ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Sampai Tanggal</label>
                                <input type="date" class="form-control" id="tgl_akhir" value="<?= $tgl_akhir ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-primary w-100" onclick="loadData()">
                                    <i class="fas fa-search me-2"></i> Tampilkan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="info-box shadow-sm">
                <strong><i class="fas fa-info-circle me-1"></i> Informasi Sumber Data:</strong><br>
                <ul class="mb-0 ps-3">
                    <li>Data ditarik dari status kunjungan rawat jalan/poli, IGD, MCU, dan Day Care.</li>
                    <li><strong>Unifikasi Kunjungan:</strong> Satu pasien (RM yang sama) hanya dihitung 1x dalam sehari meskipun mengunjungi lebih dari satu poli.</li>
                    <li><strong>Mapping Domisili:</strong> Pengelompokan <b>Dalam Kota vs Luar Kota</b> menggunakan perbandingan filter Kabupaten/Kota pada alamat pasien dengan alamat instansi (<code><?= htmlspecialchars($kab_rs) ?></code>).</li>
                </ul>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body p-2">
                    <div class="table-responsive" style="max-height: 600px">
                        <table class="table table-bordered table-sm w-100" id="dataTable" style="min-width: 1000px;">
                            <thead class="text-center bg-light">
                                <tr>
                                    <th rowspan="2" width="5%">No</th>
                                    <th rowspan="2" class="text-start" width="25%">Jenis Kegiatan</th>
                                    <th colspan="2" class="bg-primary text-white">Kunjungan Pasien<br>Dalam Kab/Kota</th>
                                    <th colspan="2" class="bg-success text-white">Kunjungan Pasien<br>Luar Kab/Kota</th>
                                    <th rowspan="2" class="bg-dark text-white">Total Kunjungan</th>
                                </tr>
                                <tr>
                                    <th class="bg-primary text-white">Laki-Laki</th>
                                    <th class="bg-primary text-white">Perempuan</th>
                                    <th class="bg-success text-white">Laki-Laki</th>
                                    <th class="bg-success text-white">Perempuan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be populated via AJAX -->
                            </tbody>
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
                <h5 class="modal-title"><i class="fas fa-book-open me-2"></i>Petunjuk Teknis Pengisian RL 3.5</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size: 0.85rem; line-height: 1.5;">
                <div class="alert alert-primary">
                    <h6><i class="fas fa-file-alt me-2"></i>Petunjuk Teknis Kemenkes (Juknis SIRS 6.3)</h6>
                    <p>Kunjungan diisi dengan jumlah kunjungan (lama & baru) di Instalasi Rawat Jalan, IGD, MCU, dan Day Care. Jika pasien berkunjung ke beberapa unit dalam satu hari yang sama, tetap dihitung 1 kunjungan. Kunjungan dibagi berdasarkan domisili (Dalam/Luar Kab/Kota) dan jenis kelamin. Rata-rata Hari Poliklinik Buka dihitung dari total hari buka seluruh poliklinik dibagi jumlah poliklinik.</p>
                </div>
                <div class="alert alert-warning">
                    <h6><i class="fas fa-code me-2"></i>Logika Teknis SIMKES Khanza</h6>
                    <p>Menghitung jumlah kunjungan unik per pasien per hari pada tabel <code>reg_periksa</code>. Penentuan domisili dilakukan dengan membandingkan kolom kabupaten/kota pada tabel <code>pasien</code> dengan pengaturan kabupaten/kota di tabel <code>setting</code> RS. Pemetaan jenis kegiatan (Penyakit Dalam, Bedah, dll) disesuaikan dengan poli tujuan pada registrasi.</p>
                    <hr>
                    <strong>Sumber Data:</strong>
                    <ul class="mb-0">
                        <li><code>reg_periksa</code> (Data registrasi & kunjungan)</li>
                        <li><code>poliklinik</code> (Pemetaan jenis kegiatan)</li>
                        <li><code>pasien</code> (Data domisili & demografi)</li>
                        <li><code>setting</code> (Data wilayah RS)</li>
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
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<script>
    document.getElementById("menu-toggle").onclick = function () { document.body.classList.toggle("sb-sidenav-toggled"); };

    $(document).ready(function() {
        loadData();
    });

    function loadData() {
        var params = {
            tgl_awal: $('#tgl_awal').val(),
            tgl_akhir: $('#tgl_akhir').val()
        };

        $('#dataTable tbody').html('<tr><td colspan="7" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i> Memuat data Kunjungan...</td></tr>');

        $.ajax({
            url: 'api/data_rl_3_5.php',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(resp) {
                var tbody = '';
                var d = resp.data;
                var formatNum = (num) => { return num > 0 ? num.toLocaleString('id-ID') : '0'; };

                // Build Table Rows
                d.forEach(function(row) {
                    var isTotal = (row.no == 99);
                    var isAvg = (row.no == 66 || row.no == 77);
                    
                    var cls = '';
                    if (isTotal) cls = 'total-row text-danger';
                    else if (isAvg) cls = 'avg-row text-primary';
                    else cls = 'bg-light';

                    if (isAvg) {
                        tbody += `
                            <tr class="${cls}">
                                <td class="text-center">${row.no}</td>
                                <td>${row.kegiatan}</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="text-end fs-6 text-primary fw-bold">${formatNum(row.total)}</td>
                            </tr>
                        `;
                    } else {
                        tbody += `
                            <tr class="${cls}">
                                <td class="text-center">${row.no}</td>
                                <td class="${cls}">${row.kegiatan}</td>
                                <td class="text-end">${formatNum(row.dalam_l)}</td>
                                <td class="text-end">${formatNum(row.dalam_p)}</td>
                                <td class="text-end">${formatNum(row.luar_l)}</td>
                                <td class="text-end">${formatNum(row.luar_p)}</td>
                                <td class="text-end fw-bold">${formatNum(row.total)}</td>
                            </tr>
                        `;
                    }
                });

                // Destroy old table if exists
                if ($.fn.DataTable.isDataTable('#dataTable')) {
                    $('#dataTable').DataTable().destroy();
                }

                $('#dataTable tbody').html(tbody);
                
                // Re-initialize DataTable for Export and Print capabilities
                $('#dataTable').DataTable({
                    dom: 'Bfrtip',
                    scrollX: true,
                    scrollY: "500px",
                    scrollCollapse: true,
                    fixedColumns: { left: 2 },
                    ordering: false, paging: false, searching: false, info: false,
                    buttons: [ 
                        { 
                            extend: 'excelHtml5', 
                            className: 'btn btn-success btn-sm mb-2', 
                            text: '<i class="fas fa-file-excel me-1"></i> Excel', 
                            title: 'Laporan RL 3.5 Rekapitulasi Kunjungan',
                        },
                        { 
                            extend: 'print', 
                            className: 'btn btn-secondary btn-sm mb-2', 
                            text: '<i class="fas fa-print me-1"></i> Print',
                        } 
                    ]
                });

            },
            error: function() { 
                $('#dataTable tbody').html('<tr><td colspan="7" class="text-center text-danger">Gagal memuat data dari server.</td></tr>');
            }
        });
    }

</script>

<script>
    document.getElementById("menu-toggle").onclick = function () { document.body.classList.toggle("sb-sidenav-toggled"); };
    function toggleMenu() { document.body.classList.remove("sb-sidenav-toggled"); }
</script>
</body>
</html>
<?php mysqli_close($koneksi); ?>
center text-danger">Gagal memuat data dari server.</td></tr>');
            }
        });
    }

</script>

<script>
    document.getElementById("menu-toggle").onclick = function () { document.body.classList.toggle("sb-sidenav-toggled"); };
    function toggleMenu() { document.body.classList.remove("sb-sidenav-toggled"); }
</script>
</body>
</html>
<?php mysqli_close($koneksi); ?>
