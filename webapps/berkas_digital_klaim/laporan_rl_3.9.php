<?php
/*
 * File: laporan_rl_3.9.php
 * Fungsi: Laporan RL 3.9 Rekapitulasi Kegiatan Pelayanan Radiologi
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
    <title>Laporan RL 3.9 - <?= $nama_instansi ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; font-size: 0.85rem; }
        .top-navbar { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); padding: 10px 20px; }
        
        .table th { white-space: nowrap; text-align: center; vertical-align: middle; background-color: #f8f9fa; font-size: 0.9rem; border: 1px solid #ddd; padding: 8px;}
        .table td { vertical-align: middle; font-size: 0.9rem; border: 1px solid #ddd; padding: 8px; }
        .table thead th { border-bottom-width: 2px; }
        
        .sub-row td:nth-child(2) { padding-left: 35px !important; }
        .head-row { font-weight: bold; background-color: #e9ecef !important; }
        .total-row { font-weight: bold; background-color: #fce4ec !important; color: #d81b60; font-size: 1rem; }
        
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
                    <h5 class="fw-bold text-success mb-3 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-x-ray me-2"></i>Laporan RL 3.9 (Rekapitulasi Kegiatan Pelayanan Radiologi)</span>
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
                                <button type="button" class="btn btn-success w-100" onclick="loadData()">
                                    <i class="fas fa-search me-2"></i> Tampilkan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="info-box shadow-sm">
                <strong><i class="fas fa-lightbulb me-1"></i> Informasi Mapping:</strong><br>
                <ul class="mb-0 ps-3">
                    <li>Data ditarik dari tabel <code>periksa_radiologi</code> dan <code>jns_perawatan_radiologi</code>.</li>
                    <li>Sistem mengenali pemeriksaan berdasarkan pembedahan <i>keyword</i> di nama tindakannya (misal ada kata 'Rontgen', 'Thorax', 'Gigi', 'Kontras', 'USG', dll).</li>
                    <li>Jika ada item yang tak dikenali/<i>out-of-scope</i>, ia akan otomatis diarahkan ke poin <b>Lain-lain</b>.</li>
                </ul>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm w-100 table-hover" id="dataTable">
                            <thead class="bg-light">
                                <tr>
                                    <th width="8%" class="text-center">No.</th>
                                    <th width="72%" class="text-start">Jenis Kegiatan</th>
                                    <th width="20%" class="text-center bg-success text-white">Jumlah</th>
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
                <h5 class="modal-title"><i class="fas fa-book-open me-2"></i>Petunjuk Teknis Pengisian RL 3.9</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size: 0.85rem; line-height: 1.5;">
                <div class="alert alert-primary">
                    <h6><i class="fas fa-file-alt me-2"></i>Petunjuk Teknis Kemenkes (Juknis SIRS 6.3)</h6>
                    <p>Mencakup data kegiatan pelayanan radiologi yang meliputi Radiodiagnostik, Radioterapi, Kedokteran Nuklir, dan Imaging/Pencitraan. Data yang dilaporkan adalah jumlah kegiatan yang diberikan kepada pasien sesuai dengan jenis pemeriksaan (seperti Foto polos, CT Scan, USG, MRI, dll).</p>
                </div>
                <div class="alert alert-warning">
                    <h6><i class="fas fa-code me-2"></i>Logika Teknis SIMKES Khanza</h6>
                    <p>Sistem menganalisis data dari tabel <code>periksa_radiologi</code> yang digabungkan dengan <code>jns_perawatan_radiologi</code> untuk pengelompokan jenis tindakan. Tindakan dikategorikan berdasarkan kata kunci pada nama pemeriksaan (misalnya 'Kontras', 'Gigi', 'CT Scan', 'USG') untuk disesuaikan dengan formulir SIRS.</p>
                    <hr>
                    <strong>Sumber Data:</strong>
                    <ul class="mb-0">
                        <li><code>periksa_radiologi</code> (Data transaksi pemeriksaan)</li>
                        <li><code>jns_perawatan_radiologi</code> (Master kategori tindakan radiologi)</li>
                        <li><code>reg_periksa</code> (Data kunjungan pasien)</li>
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

        $('#dataTable tbody').html('<tr><td colspan="3" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i> Memuat Laporan Radiologi (RL 3.9)...</td></tr>');

        $.ajax({
            url: 'api/data_rl_3_9.php',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(resp) {
                var tbody = '';
                var d = resp.data;
                var formatNum = (num) => { return num > 0 ? num.toLocaleString('id-ID') : '0'; };

                var buildRow = function(no, judul, key, level = 0, isHead = false, isTotal = false) {
                    var cls = '';
                    if (isTotal) cls = 'total-row';
                    else if (isHead) cls = 'head-row';
                    else if (level == 1) cls = 'sub-row bg-white';
                    
                    var r = d[key] || 0;
                    
                    if (isHead) {
                         return `
                            <tr class="${cls}">
                                <td class="text-center">${no}</td>
                                <td>${judul}</td>
                                <td></td>
                            </tr>
                        `;
                    }

                    return `
                        <tr class="${cls}">
                            <td class="text-center">${no}</td>
                            <td>${judul}</td>
                            <td class="text-center fw-bold">${formatNum(r)}</td>
                        </tr>
                    `;
                };

                tbody += buildRow('1', 'Radiodiagnostik', '1', 0, true);
                tbody += buildRow('1.1', 'Foto tanpa bahan kontras', '1.1', 1);
                tbody += buildRow('1.2', 'Foto dengan bahan kontras', '1.2', 1);
                tbody += buildRow('1.3', 'Foto dengan rol film', '1.3', 1);
                tbody += buildRow('1.4', 'Flouroskopi', '1.4', 1);
                tbody += buildRow('1.5', 'Foto Gigi', '1.5', 1);
                tbody += buildRow('1.6', 'C.T. Scan', '1.6', 1);
                tbody += buildRow('1.7', 'Lymphografi', '1.7', 1);
                tbody += buildRow('1.8', 'Angiograpi', '1.8', 1);
                tbody += buildRow('1.9', 'Lain-Lain', '1.9', 1);
                
                tbody += buildRow('2', 'Radioterapi', '2', 0, true);
                tbody += buildRow('2.1', 'Radioterapi dengan Linac', '2.1', 1);
                tbody += buildRow('2.2', 'Radioterapi dengan Cobalt', '2.2', 1);
                tbody += buildRow('2.3', 'Radioterapi dengan Brakhiterapi', '2.3', 1);
                tbody += buildRow('2.4', 'Lain-Lain', '2.4', 1);
                
                tbody += buildRow('3', 'Kedokteran Nuklir', '3', 0, true);
                tbody += buildRow('3.1', 'Diagnostik', '3.1', 1);
                tbody += buildRow('3.2', 'Therapi', '3.2', 1);
                tbody += buildRow('3.3', 'Lain-Lain', '3.3', 1);
                
                tbody += buildRow('4', 'Imaging/Pencitraan', '4', 0, true);
                tbody += buildRow('4.1', 'USG', '4.1', 1);
                tbody += buildRow('4.2', 'MRI', '4.2', 1);
                tbody += buildRow('4.3', 'Lain-lain', '4.3', 1);
                
                tbody += buildRow('99', 'TOTAL', '99', 0, false, true);

                if ($.fn.DataTable.isDataTable('#dataTable')) {
                    $('#dataTable').DataTable().destroy();
                }

                $('#dataTable tbody').html(tbody);
                
                $('#dataTable').DataTable({
                    dom: 'Bfrtip',
                    ordering: false, paging: false, searching: false, info: false,
                    buttons: [ 
                        { 
                            extend: 'excelHtml5', 
                            className: 'btn btn-success btn-sm mb-2', 
                            text: '<i class="fas fa-file-excel me-1"></i> Excel', 
                            title: 'Laporan RL 3.9 Rekapitulasi Kegiatan Pelayanan Radiologi',
                        },
                        { 
                            extend: 'print', 
                            className: 'btn btn-secondary btn-sm mb-2', 
                            text: '<i class="fas fa-print me-1"></i> Print',
                            title: 'Laporan RL 3.9 Pelayanan Radiologi'
                        } 
                    ]
                });

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
