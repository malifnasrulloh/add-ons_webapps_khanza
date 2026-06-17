<?php
/*
 * File: laporan_rl_3.18.php
 * Fungsi: Laporan RL 3.18 Farmasi Resep (Grouping Dinamis + Drill Down)
 * Sesuai Guideline: sumber_data_RL3.18.txt
 */
require_once('csrf.php');
if (!isset($_SESSION['casemix_login']) || $_SESSION['casemix_login'] !== true) {
    header("Location: index.php"); exit;
}
require_once('../conf/conf.php');
$koneksi = bukakoneksi();

$q_set = mysqli_query($koneksi, "SELECT nama_instansi FROM setting LIMIT 1");
$r_set = mysqli_fetch_assoc($q_set);
$nama_instansi = $r_set['nama_instansi'] ?? 'RS';
$nama_user_login = "User";

$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-01'); 
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan RL 3.18 - <?= $nama_instansi ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; font-size: 0.85rem; }
        .top-navbar { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); padding: 10px 20px; }
        
        .table th { white-space: nowrap; text-align: center; vertical-align: middle; background-color: #f8f9fa; }
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
                        <span><i class="fas fa-pills me-2"></i>Laporan RL 3.18 (Farmasi Resep)</span>
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
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Pengelompokan Data</label>
                                <select class="form-select" id="group_mode">
                                    <option value="golongan" selected>Berdasarkan Golongan Obat</option>
                                    <option value="kategori">Berdasarkan Kategori Barang</option>
                                    <option value="jenis">Berdasarkan Jenis Barang</option>
                                </select>
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
                <strong><i class="fas fa-info-circle me-1"></i> Informasi Guideline:</strong><br>
                <ul class="mb-0 ps-3">
                    <li>Data ditarik berdasarkan akumulasi <b>kuantitas obat</b> (Sum Jml) per unit pelayanan.</li>
                    <li>Pengelompokan dapat diubah secara dinamis antara Golongan, Kategori, atau Jenis Barang.</li>
                    <li>Rincian detail per transaksi dapat dilihat dengan menekan tombol pencarian pada setiap baris.</li>
                </ul>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm w-100" id="dataTable">
                            <thead class="text-center bg-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th class="text-start">Nama Kelompok</th>
                                    <th width="15%" class="bg-success text-white">Rawat Jalan</th>
                                    <th width="15%" class="bg-danger text-white">IGD</th>
                                    <th width="15%" class="bg-primary text-white">Rawat Inap</th>
                                    <th width="15%" class="bg-dark text-white">Total</th>
                                    <th width="5%" class="bg-dark text-white">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot class="bg-light fw-bold">
                                <tr>
                                    <td colspan="2" class="text-end">Total Keseluruhan :</td>
                                    <td id="sum-ralan" class="text-end">0</td>
                                    <td id="sum-igd" class="text-end">0</td>
                                    <td id="sum-ranap" class="text-end">0</td>
                                    <td id="sum-total" class="text-end">0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
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
                <h5 class="modal-title"><i class="fas fa-list me-2"></i>Rincian Penggunaan Obat</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border shadow-sm mb-3">
                    <strong>Filter:</strong> <span id="detail-title">...</span> | 
                    <strong>Periode:</strong> <span id="detail-periode">...</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm w-100" id="tableDetail">
                        <thead class="table-light">
                            <tr>
                                <th>Waktu</th>
                                <th>No. Rawat</th>
                                <th>Pasien</th>
                                <th>Unit</th>
                                <th>Nama Obat</th>
                                <th class="text-end">Jml</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Total</th>
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

<!-- Modal Petunjuk Teknis Juknis -->
<div class="modal fade" id="modalJuknis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-book-open me-2"></i>Petunjuk Teknis Pengisian RL 3.18</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size: 0.85rem; line-height: 1.5;">
                <div class="alert alert-primary">
                    <h6><i class="fas fa-file-alt me-2"></i>Petunjuk Teknis Kemenkes (Juknis SIRS 6.3)</h6>
                    <p>Data yang dilaporkan adalah jumlah resep yang diberikan ke pasien di rawat jalan, IGD, dan rawat inap sesuai dengan golongan obatnya. Penulisan resep sama seperti item obat, jika rumah sakit masih memberikan resep secara manual, maka dokter akan menulisakan dalam kertas resep dengan tanda R/ untuk 1 item obat. Jika dalam 1 kertas resep tertulis lebih dari 1 R/, maka dihitung jumlah resep sebanyak R/.</p>
                </div>
                <div class="alert alert-warning">
                    <h6><i class="fas fa-code me-2"></i>Logika Teknis SIMKES Khanza</h6>
                    <p>Menghitung jumlah item obat yang diberikan kepada pasien (R/) berdasarkan data pemberian obat/alkes. Sistem mengidentifikasi asal unit (Ralan/Ranap/IGD) untuk pengelompokan laporan sesuai kategori SIRS.</p>
                    <hr>
                    <strong>Sumber Data:</strong>
                    <ul class="mb-0">
                        <li><code>detail_pemberian_obat</code> (Data transaksi pemberian obat)</li>
                        <li><code>databarang</code> (Master data obat dan golongan)</li>
                        <li><code>kategori_barang</code> (Kategori produk)</li>
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

    var myTable, detailTable;

    $(document).ready(function() {
        myTable = $('#dataTable').DataTable({
            dom: 'Bfrtip',
            buttons: [ 
                { extend: 'excelHtml5', className: 'btn btn-success btn-sm mb-2', text: '<i class="fas fa-file-excel me-1"></i> Excel', exportOptions: { columns: ':not(:last-child)' } },
                { extend: 'print', className: 'btn btn-secondary btn-sm mb-2', text: '<i class="fas fa-print me-1"></i> Print', exportOptions: { columns: ':not(:last-child)' } } 
            ],
            columns: [
                { data: null, render: (d,t,r,m) => m.row + 1, className: "text-center" },
                { data: "nama_group", className: "fw-bold" },
                { data: "jml_ralan", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "jml_igd", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "jml_ranap", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "total_semua", className: "text-end fw-bold bg-light", render: $.fn.dataTable.render.number('.', ',', 0) },
                { 
                    data: null, className: "text-center",
                    render: function(data, type, row) {
                        return `<button class="btn btn-sm btn-info text-white" onclick="openDetail('${row.kode_group}', '${row.nama_group}')"><i class="fas fa-search"></i></button>`;
                    }
                }
            ],
            ordering: false, paging: false, searching: false, info: false,
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                var intVal = function (i) { return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0; };
                $('#sum-ralan').html(api.column(2).data().reduce((a, b) => intVal(a) + intVal(b), 0).toLocaleString('id-ID'));
                $('#sum-igd').html(api.column(3).data().reduce((a, b) => intVal(a) + intVal(b), 0).toLocaleString('id-ID'));
                $('#sum-ranap').html(api.column(4).data().reduce((a, b) => intVal(a) + intVal(b), 0).toLocaleString('id-ID'));
                $('#sum-total').html(api.column(5).data().reduce((a, b) => intVal(a) + intVal(b), 0).toLocaleString('id-ID'));
            }
        });

        detailTable = $('#tableDetail').DataTable({
            dom: 'Bfrtip',
            buttons: [ { extend: 'excelHtml5', className: 'btn btn-success btn-sm', text: '<i class="fas fa-file-excel me-1"></i> Export Detail' } ],
            pageLength: 10,
            columns: [
                { data: "tgl_perawatan", render: (d,t,r) => d + ' ' + r.jam },
                { data: "no_rawat" },
                { data: "nm_pasien" },
                { data: "unit", render: (d) => `<span class="badge ${d=='IGD'?'bg-danger':(d=='Rawat Inap'?'bg-primary':'bg-success')}">${d}</span>` },
                { data: "nama_brng" },
                { data: "jml", className: "text-end" },
                { data: "harga", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 0) },
                { data: "total", className: "text-end fw-bold", render: $.fn.dataTable.render.number('.', ',', 0) }
            ]
        });

        loadData();
    });

    function loadData() {
        $.ajax({
            url: 'api/data_rl_3_18.php',
            type: 'GET',
            data: { tgl_awal: $('#tgl_awal').val(), tgl_akhir: $('#tgl_akhir').val(), mode: $('#group_mode').val() },
            success: function(resp) { myTable.clear().rows.add(resp.data).draw(); },
            error: function() { alert("Gagal memuat data."); }
        });
    }

    function openDetail(id, nama) {
        $('#detail-title').text(nama);
        $('#detail-periode').text($('#tgl_awal').val() + ' s.d ' + $('#tgl_akhir').val());
        $('#modalDetail').modal('show');
        detailTable.clear().draw();
        $.ajax({
            url: 'api/data_rl_3_18_detail.php',
            type: 'GET',
            data: { tgl_awal: $('#tgl_awal').val(), tgl_akhir: $('#tgl_akhir').val(), mode: $('#group_mode').val(), id: id },
            success: function(resp) { detailTable.rows.add(resp.data).draw(); },
            error: function() { console.error("Gagal load detail"); }
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
