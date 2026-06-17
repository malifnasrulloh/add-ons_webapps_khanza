<?php
/*
 * File: laporan_rl_3.3.php
 * Fungsi: Laporan RL 3.3 Rekapitulasi Kegiatan Pelayanan Rawat Darurat
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
    <title>Laporan RL 3.3 - <?= $nama_instansi ?></title>
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
        
        /* Freeze first 2 columns */
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

        .sub-row td:nth-child(2) { padding-left: 30px !important; }
        .total-row { font-weight: bold; background-color: #e9ecef !important; }
        
        .info-box { background: #e3f2fd; border-left: 4px solid #1976d2; padding: 10px 15px; margin-bottom: 20px; border-radius: 4px; font-size: 0.85rem;}
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
                        <span><i class="fas fa-ambulance me-2"></i>Laporan RL 3.3 (Kegiatan Pelayanan Rawat Darurat)</span>
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
                    <li>Data ditarik dari tabel <code>reg_periksa</code> yang dilayani di Poliklinik IGD.</li>
                    <li><strong>Bedah (Kecelakaan)</strong> dan <strong>Non Bedah (Kekerasan)</strong> di-mapping berdasarkan kode ICD-10 dari tabel <code>diagnosa_pasien</code> (contoh: V01-V99 = Kecelakaan LL Darat, T00-T14 = Bedah Lainnya, dsb).</li>
                    <li><strong>Kebidanan</strong> di-mapping berdasarkan ICD-10 O00-O99.</li>
                    <li><strong>Bayi</strong> (0-11 bulan) dan <strong>Anak</strong> (1-17 tahun) dihitung otomatis berdasar umur pasien. Geriatri (>60 tahun).</li>
                    <li>Pasien tanpa mapping ICD-10 yang jelas (atau umum) akan masuk ke ranah <strong>Non Bedah Lainnya</strong>.</li>
                    <li>Status <strong>DOA / Mati</strong> diambil dari <code>stts_pulang</code> (registrasi/kamar inap). Rujukan/Non Rujukan diambil dari `status_lanjut` atau tabel `rujuk_masuk`.</li>
                </ul>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body p-2">
                    <div class="table-responsive" style="max-height: 600px">
                        <table class="table table-bordered table-sm w-100" id="dataTable" style="min-width: 1500px;">
                            <thead class="text-center bg-light">
                                <tr>
                                    <th rowspan="2" width="5%">No</th>
                                    <th rowspan="2" class="text-start" width="25%">Jenis Pelayanan</th>
                                    <th colspan="2" class="bg-primary text-white">Total Pasien</th>
                                    <th colspan="3" class="bg-success text-white">Tindak Lanjut Pelayanan</th>
                                    <th colspan="2" class="bg-danger text-white">Mati di IGD</th>
                                    <th colspan="2" class="bg-dark text-white">DOA</th>
                                    <th colspan="2" class="bg-warning text-dark">Luka-luka</th>
                                    <th rowspan="2" class="bg-secondary text-white">False<br>Emergency</th>
                                </tr>
                                <tr>
                                    <th class="bg-primary text-white">Rujukan</th>
                                    <th class="bg-primary text-white">Non Rujukan</th>
                                    <th class="bg-success text-white">Dirawat</th>
                                    <th class="bg-success text-white">Dirujuk</th>
                                    <th class="bg-success text-white">Pulang</th>
                                    <th class="bg-danger text-white">Laki-laki</th>
                                    <th class="bg-danger text-white">Perempuan</th>
                                    <th class="bg-dark text-white">Laki-laki</th>
                                    <th class="bg-dark text-white">Perempuan</th>
                                    <th class="bg-warning text-dark">Laki-laki</th>
                                    <th class="bg-warning text-dark">Perempuan</th>
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
                <h5 class="modal-title"><i class="fas fa-book-open me-2"></i>Petunjuk Teknis Pengisian RL 3.3</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size: 0.85rem; line-height: 1.5;">
                <div class="alert alert-primary">
                    <h6><i class="fas fa-file-alt me-2"></i>Petunjuk Teknis Kemenkes (Juknis SIRS 6.3)</h6>
                    <p>Satu pasien hanya boleh dihitung 1 (satu) kali per kali masuk IGD. Total Pasien diisi berdasarkan asal pasien (Rujukan atau Non Rujukan). Tindak lanjut pelayanan meliputi dirawat, dirujuk, atau pulang. Mencakup data Mati di IGD, DOA (Death on Arrival), False Emergency, serta kategori umur Bayi (0-11 bln) dan Anak (1-17 thn).</p>
                </div>
                <div class="alert alert-warning">
                    <h6><i class="fas fa-code me-2"></i>Logika Teknis SIMKES Khanza</h6>
                    <p>Menganalisis data registrasi di unit IGD (kd_poli='IGDK'). Sistem memproses diagnosis ICD-10 untuk pengelompokan jenis pelayanan (Bedah/Non-Bedah/Kebidanan) serta menghitung umur secara presisi saat tanggal registrasi untuk kategori Bayi/Anak/Geriatri.</p>
                    <hr>
                    <strong>Sumber Data:</strong>
                    <ul class="mb-0">
                        <li><code>reg_periksa</code> (Data registrasi & tindak lanjut)</li>
                        <li><code>pasien</code> (Data demografi & umur)</li>
                        <li><code>diagnosa_pasien</code> (ICD-10 untuk kategori kasus)</li>
                        <li><code>rujuk_masuk</code> (Status rujukan dari faskes lain)</li>
                        <li><code>pasien_mati</code> (Validasi kematian tambahan)</li>
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

        $('#dataTable tbody').html('<tr><td colspan="15" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i> Memuat data, memproses ICD-10...</td></tr>');

        $.ajax({
            url: 'api/data_rl_3_3.php',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(resp) {
                var tbody = '';
                var d = resp.data;
                
                // Helper to format rows
                var buildRow = function(no, judul, key, isSub = false, isTotal = false) {
                    var cls = isSub ? 'sub-row' : (isTotal ? 'total-row' : 'fw-bold bg-light');
                    var rowData = d[key] || {
                        t_rujukan:0, t_non_rujukan:0, tl_dirawat:0, tl_dirujuk:0,
                        tl_pulang:0, mati_l:0, mati_p:0, doa_l:0, doa_p:0, luka_l:0, luka_p:0, false_emergency:0
                    };
                    
                    // Format number to Indonesian locale
                    var formatNum = (num) => { return num > 0 ? num.toLocaleString('id-ID') : '0'; };

                    return `
                        <tr class="${cls}">
                            <td class="text-center">${no}</td>
                            <td>${judul}</td>
                            <td class="text-end">${formatNum(rowData.t_rujukan)}</td>
                            <td class="text-end">${formatNum(rowData.t_non_rujukan)}</td>
                            <td class="text-end">${formatNum(rowData.tl_dirawat)}</td>
                            <td class="text-end">${formatNum(rowData.tl_dirujuk)}</td>
                            <td class="text-end">${formatNum(rowData.tl_pulang)}</td>
                            <td class="text-end">${formatNum(rowData.mati_l)}</td>
                            <td class="text-end">${formatNum(rowData.mati_p)}</td>
                            <td class="text-end">${formatNum(rowData.doa_l)}</td>
                            <td class="text-end">${formatNum(rowData.doa_p)}</td>
                            <td class="text-end">${formatNum(rowData.luka_l)}</td>
                            <td class="text-end">${formatNum(rowData.luka_p)}</td>
                            <td class="text-end">${formatNum(rowData.false_emergency)}</td>
                        </tr>
                    `;
                };

                tbody += buildRow('1', 'Bedah di Instalasi Gawat Darurat', 'bedah');
                tbody += buildRow('1.1', 'Kecelakaan lalu lintas darat', '1_1', true);
                tbody += buildRow('1.2', 'Kecelakaan lalu lintas perairan', '1_2', true);
                tbody += buildRow('1.3', 'Kecelakaan lalu lintas udara', '1_3', true);
                tbody += buildRow('1.4', 'Bedah lainnya (non kecelakaan)', '1_4', true);
                tbody += buildRow('2', 'Non Bedah', 'non_bedah');
                tbody += buildRow('2.1', 'Kekerasan terhadap Perempuan (\u226518 tahun)', '2_1', true);
                tbody += buildRow('2.2', 'Kekerasan terhadap Anak (&lt;18 tahun)', '2_2', true);
                tbody += buildRow('2.3', 'Kekerasan lainnya', '2_3', true);
                tbody += buildRow('2.4', 'Non bedah lainnya', '2_4', true);
                tbody += buildRow('3', 'Kebidanan', 'kebidanan');
                tbody += buildRow('4', 'Psikiatrik', 'psikiatrik');
                tbody += buildRow('5', 'Bayi', 'bayi');
                tbody += buildRow('6', 'Anak', 'anak');
                tbody += buildRow('7', 'Geriatri', 'geriatri');
                tbody += buildRow('99', 'TOTAL', 'total', false, true);

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
                            title: 'Laporan RL 3.3 Rawat Darurat',
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
                $('#dataTable tbody').html('<tr><td colspan="15" class="text-center text-danger">Gagal memuat data dari server.</td></tr>');
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
