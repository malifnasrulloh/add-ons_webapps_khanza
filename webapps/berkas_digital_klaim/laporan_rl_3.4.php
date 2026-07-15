<?php
/*
 * File: laporan_rl_3.4.php
 * Fungsi: Laporan RL 3.4 Rekapitulasi Pengunjung
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
$nama_user_login = "User"; // Simplified for brevity

$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-01'); 
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan RL 3.4 - <?= $nama_instansi ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; font-size: 0.9rem; }
        /* Sidebar Style omitted for brevity - use previous sidebar code */
        .top-navbar { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); padding: 10px 20px; }
        
        .table th { white-space: nowrap; text-align: center; vertical-align: middle; background-color: #f8f9fa; font-size: 0.9rem; }
        .table td { vertical-align: middle; font-size: 0.9rem; }
        
        .info-box { background: #e8f5e9; border-left: 4px solid #4caf50; padding: 10px 15px; margin-bottom: 20px; border-radius: 4px; font-size: 0.85rem;}
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
                        <span><i class="fas fa-users me-2"></i>Laporan RL 3.4 (Rekapitulasi Pengunjung)</span>
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
                <strong><i class="fas fa-info-circle me-1"></i> Aturan Perhitungan:</strong><br>
                <ul class="mb-0 ps-3">
                    <li><strong>Pengunjung Baru:</strong> Jumlah Pasien unik (No. RM) yang memiliki status daftar 'Baru'.</li>
                    <li><strong>Pengunjung Lama:</strong> Jumlah Pasien unik (No. RM) yang memiliki status daftar 'Lama', <i>dan tidak dihitung jika pasien tersebut sudah tercatat sebagai pengunjung baru di periode yang sama.</i></li>
                </ul>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body p-2">
                    <div class="table-responsive col-md-8 mx-auto">
                        <table class="table table-bordered table-striped w-100" id="dataTable">
                            <thead class="text-center">
                                <tr>
                                    <th width="15%" class="bg-dark text-white">No</th>
                                    <th width="50%" class="bg-primary text-white text-start">Jenis Pengunjung</th>
                                    <th width="35%" class="bg-success text-white">Jumlah</th>
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
                <h5 class="modal-title"><i class="fas fa-book-open me-2"></i>Petunjuk Teknis Pengisian RL 3.4</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size: 0.85rem; line-height: 1.5;">
                <div class="alert alert-primary">
                    <h6><i class="fas fa-file-alt me-2"></i>Petunjuk Teknis Kemenkes (Juknis SIRS 6.3)</h6>
                    <p>Pengunjung Baru adalah pengunjung yang baru pertama kali datang ke RS dan mendapatkan nomor RM baru. Pengunjung Lama adalah pengunjung yang datang untuk kedua kali dan seterusnya. Jika 1 pasien datang beberapa kali dalam 1 bulan, maka hanya dihitung sebagai 1 pengunjung lama.</p>
                </div>
                <div class="alert alert-warning">
                    <h6><i class="fas fa-code me-2"></i>Logika Teknis SIMKES Khanza</h6>
                    <p>Menghitung jumlah pasien unik (No. RM) berdasarkan status daftar (Baru/Lama) pada tabel registrasi. Sistem memastikan tidak ada duplikasi perhitungan untuk pengunjung lama yang datang berkali-kali dalam satu periode bulan laporan.</p>
                    <hr>
                    <strong>Sumber Data:</strong>
                    <ul class="mb-0">
                        <li><code>reg_periksa</code> (Data kunjungan harian)</li>
                        <li><code>pasien</code> (Data master pasien & tanggal daftar pertama)</li>
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
        myTable = $('#dataTable').DataTable({
            dom: 'Bfrtip',
            buttons: [ 
                { 
                    extend: 'excelHtml5', 
                    className: 'btn btn-success btn-sm mb-2', 
                    text: '<i class="fas fa-file-excel me-1"></i> Excel', 
                    title: 'Laporan RL 3.4 Rekapitulasi Pengunjung',
                },
                { 
                    extend: 'print', 
                    className: 'btn btn-secondary btn-sm mb-2', 
                    text: '<i class="fas fa-print me-1"></i> Print',
                    title: 'Laporan RL 3.4. Rekapitulasi Pengunjung'
                } 
            ],
            columns: [
                { data: "no", className: "text-center fw-bold bg-light" },
                { data: "jenis_pengunjung", className: "fw-bold", render: function(d,t,r) {
                    if(r.no == 99) return '<span class="text-uppercase fw-bold text-danger">' + d + '</span>';
                    return d;
                }},
                { data: "jumlah", className: "text-end fw-bold", render: function(d,t,r) {
                    var formatNum = d.toLocaleString('id-ID');
                    if(r.no == 99) return '<span class="text-danger">' + formatNum + '</span>';
                    return formatNum;
                }}
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

        $('#dataTable tbody').html('<tr><td colspan="3" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i> Sedang merekap data pengunjung...</td></tr>');

        $.ajax({
            url: 'api/data_rl_3_4.php',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(resp) {
                myTable.clear().rows.add(resp.data).draw();
            },
            error: function() { 
                $('#dataTable tbody').html('<tr><td colspan="3" class="text-center text-danger">Gagal memuat data dari server.</td></tr>');
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
