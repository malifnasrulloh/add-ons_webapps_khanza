<?php
/*
 * File: laporan_rl_3.2.php
 * Fungsi: Laporan RL 3.2 Rawat Inap
 */
require_once('csrf.php');
if (!isset($_SESSION['casemix_login']) || $_SESSION['casemix_login'] !== true) {
    header("Location: index.php"); exit;
}
require_once('../conf/conf.php');
$koneksi = bukakoneksi();

// Data Instansi & User (Standard)
$q_set = mysqli_query($koneksi, "SELECT nama_instansi, logo FROM setting LIMIT 1");
$r_set = mysqli_fetch_assoc($q_set);
$nama_instansi = $r_set['nama_instansi'] ?? 'RS';
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
    <title>Laporan RL 3.2 - <?= $nama_instansi ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; font-size: 0.9rem; }
        /* Sidebar Style omitted for brevity - use previous sidebar code */
        .top-navbar { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); padding: 10px 20px; }
        
        .table th { white-space: nowrap; text-align: center; vertical-align: middle; background-color: #f8f9fa; font-size: 0.85rem; }
        .table td { vertical-align: middle; font-size: 0.85rem; }
        
        /* Freeze first 2 columns */
        table.dataTable thead th:nth-child(1), table.dataTable tbody td:nth-child(1),
        table.dataTable thead th:nth-child(2), table.dataTable tbody td:nth-child(2) {
            position: sticky;
            background-color: #fff;
            z-index: 1;
        }
        table.dataTable thead th:nth-child(1) { left: 0; z-index: 2; }
        table.dataTable tbody td:nth-child(1) { left: 0; }
        table.dataTable thead th:nth-child(2) { left: 40px; z-index: 2; }
        table.dataTable tbody td:nth-child(2) { left: 40px; }
        /* Add border to sticky columns */
        table.dataTable thead th:nth-child(2), table.dataTable tbody td:nth-child(2) {
            border-right: 2px solid #dee2e6;
        }

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
                        <span><i class="fas fa-bed me-2"></i>Laporan RL 3.2 (Rawat Inap)</span>
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
                <strong><i class="fas fa-info-circle me-1"></i> Informasi Sensus:</strong><br>
                <ul class="mb-0 ps-3">
                    <li><b>LD (Lama Dirawat):</b> Selisih tanggal keluar dan tanggal masuk.</li>
                    <li><b>HP (Hari Perawatan):</b> Jumlah hari pasien dirawat berdasarkan sensus harian.</li>
                    <li><b>Pasien Akhir:</b> (Awal + Masuk + Pindahan) - (Keluar Hidup + Mati + Dipindahkan).</li>
                </ul>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body p-2">
                    <div class="table-responsive" style="max-height: 500px">
                        <table class="table table-bordered table-striped table-sm w-100" id="dataTable">
                            <thead class="text-center" style="font-size:0.75rem;">
                                <tr>
                                    <th width="5%" class="bg-dark text-white">No</th>
                                    <th class="bg-dark text-white text-start">Jenis Pelayanan</th>
                                    <th class="bg-primary text-white">Pasien Awal Bulan</th>
                                    <th class="bg-success text-white">Pasien Masuk</th>
                                    <th class="bg-info text-white">Pasien Pindahan</th>
                                    <th class="bg-warning text-dark">Pasien Dipindahkan</th>
                                    <th class="bg-primary text-white">Pasien Keluar Hidup</th>
                                    <th class="bg-danger text-white">Pasien Laki-Laki<br>Keluar Mati &lt;48 jam</th>
                                    <th class="bg-danger text-white">Pasien Laki-Laki<br>Keluar Mati &ge;48 jam</th>
                                    <th class="bg-danger text-white">Pasien Perempuan<br>Keluar Mati &lt;48 jam</th>
                                    <th class="bg-danger text-white">Pasien Perempuan<br>Keluar Mati &ge;48 jam</th>
                                    <th class="bg-secondary text-white">Jumlah Lama<br>Dirawat</th>
                                    <th class="bg-primary text-white">Pasien Akhir Bulan</th>
                                    <th class="bg-success text-white">Jumlah Hari<br>Perawatan</th>
                                    <th class="bg-dark text-white">Rincian Hari Perawatan<br>per Kelas VVIP</th>
                                    <th class="bg-dark text-white">Rincian Hari Perawatan<br>per Kelas VIP</th>
                                    <th class="bg-dark text-white">Rincian Hari Perawatan<br>per Kelas I</th>
                                    <th class="bg-dark text-white">Rincian Hari Perawatan<br>per Kelas II</th>
                                    <th class="bg-dark text-white">Rincian Hari Perawatan<br>per Kelas III</th>
                                    <th class="bg-dark text-white">Rincian Hari Perawatan<br>per Kelas Khusus</th>
                                    <th class="bg-secondary text-white">Jumlah alokasi tempat<br>tidur awal bulan</th>
                                </tr>
                            </thead>
                            <tbody style="font-size:0.85rem;"></tbody>
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
                <h5 class="modal-title"><i class="fas fa-book-open me-2"></i>Petunjuk Teknis Pengisian RL 3.2</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size: 0.85rem; line-height: 1.5;">
                <div class="alert alert-primary">
                    <h6><i class="fas fa-file-alt me-2"></i>Petunjuk Teknis Kemenkes (Juknis SIRS 6.3)</h6>
                    <p>Formulir kegiatan pelayanan rawat inap dilaporkan bulanan dengan data bersumber dari Instalasi Rawat Inap baik berupa sensus harian pasien rawat inap atau formulir lainnya. Mencakup Pasien Awal Bulan, Masuk, Pindahan, Dipindahkan, Keluar Hidup, Keluar Mati (<48 jam dan ≥ 48 jam), Lama Dirawat, Pasien Akhir Bulan, dan Hari Perawatan per Kelas (VVIP, VIP, I, II, III, Kelas Khusus).</p>
                </div>
                <div class="alert alert-warning">
                    <h6><i class="fas fa-code me-2"></i>Logika Teknis SIMKES Khanza</h6>
                    <p>Sistem melakukan kalkulasi sensus harian otomatis. Pasien Awal dihitung dari sisa pasien hari terakhir bulan sebelumnya. LD (Lama Dirawat) dihitung dari selisih tanggal keluar dan masuk, sedangkan HP (Hari Perawatan) dihitung per hari pasien menempati tempat tidur di setiap kelas perawatan.</p>
                    <hr>
                    <strong>Sumber Data:</strong>
                    <ul class="mb-0">
                        <li><code>kamar_inap</code> (Data mutasi & durasi rawat)</li>
                        <li><code>reg_periksa</code> (Data registrasi & status pulang)</li>
                        <li><code>pasien</code> (Data identitas pasien)</li>
                        <li><code>pasien_mati</code> (Data validasi kematian tambahan)</li>
                        <li><code>kamar</code> & <code>bangsal</code> (Master data bed & unit)</li>
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

    var myTable;

    $(document).ready(function() {
        // 1. TABEL UTAMA
        myTable = $('#dataTable').DataTable({
            dom: 'Bfrtip',
            scrollX: true,
            scrollY: "500px",
            scrollCollapse: true,
            fixedColumns: {
                left: 2
            },
            buttons: [ 
                { 
                    extend: 'excelHtml5', 
                    className: 'btn btn-success btn-sm mb-2', 
                    text: '<i class="fas fa-file-excel me-1"></i> Excel', 
                    title: 'Laporan RL 3.2 Rawat Inap',
                },
                { 
                    extend: 'print', 
                    className: 'btn btn-secondary btn-sm mb-2', 
                    text: '<i class="fas fa-print me-1"></i> Print',
                } 
            ],
            columns: [
                { data: null, render: (d,t,r,m) => m.row + 1, className: "text-center fw-bold bg-light" },
                { data: "jenis_pelayanan", className: "fw-bold bg-light text-start" },
                { data: "awal_bulan", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "masuk", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "pindahan", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "dipindahkan", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "keluar_hidup", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "mati_l_kurang_48", className: "text-end text-danger", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "mati_l_lebih_48", className: "text-end text-danger", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "mati_p_kurang_48", className: "text-end text-danger", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "mati_p_lebih_48", className: "text-end text-danger", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "lama_dirawat", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "akhir_bulan", className: "text-end fw-bold", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "hari_perawatan", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "hp_vvip", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "hp_vip", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "hp_1", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "hp_2", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "hp_3", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "hp_khusus", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "alokasi_tt", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) }
            ],
            ordering: false, paging: false, searching: false, info: false
        });

        loadData();
    });

    function loadData() {
        var params = {
            tgl_awal: $('#tgl_awal').val(),
            tgl_akhir: $('#tgl_akhir').val()
        };

        $.ajax({
            url: 'api/data_rl_3_2.php',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(resp) {
                myTable.clear().rows.add(resp.data).draw();
            },
            error: function() { alert("Gagal memuat data."); }
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
