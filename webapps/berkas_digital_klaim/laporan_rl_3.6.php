<?php
/*
 * File: laporan_rl_3.6.php
 * Fungsi: Laporan RL 3.6 Rekapitulasi Kegiatan Pelayanan Kebidanan
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
    <title>Laporan RL 3.6 - <?= $nama_instansi ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; font-size: 0.85rem; }
        .top-navbar { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); padding: 10px 20px; }
        
        .table th { white-space: nowrap; text-align: center; vertical-align: middle; background-color: #f8f9fa; font-size: 0.8rem; border: 1px solid #ddd; padding: 5px;}
        .table td { vertical-align: middle; font-size: 0.8rem; border: 1px solid #ddd; padding: 5px; }
        .table thead th { border-bottom-width: 2px; }
        
        table.dataTable thead th:nth-child(1), table.dataTable tbody td:nth-child(1),
        table.dataTable thead th:nth-child(2), table.dataTable tbody td:nth-child(2) {
            position: sticky;
            background-color: #f8f9fa;
            z-index: 1;
        }
        table.dataTable thead th:nth-child(1) { left: 0; z-index: 2; }
        table.dataTable tbody td:nth-child(1) { left: 0; }
        table.dataTable thead th:nth-child(2) { left: 40px; z-index: 2; }
        table.dataTable tbody td:nth-child(2) { left: 40px; text-align: left; }
        table.dataTable thead th:nth-child(2), table.dataTable tbody td:nth-child(2) { border-right: 2px solid #ccc; max-width: 300px; white-space: normal; }

        .sub-row td:nth-child(2) { padding-left: 25px !important; }
        .head-row { font-weight: bold; background-color: #e9ecef !important; }
        
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
                        <span><i class="fas fa-baby me-2"></i>Laporan RL 3.6 (Kegiatan Pelayanan Kebidanan)</span>
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
                <strong><i class="fas fa-info-circle me-1"></i> Informasi Pemetaan:</strong><br>
                <ul class="mb-0 ps-3">
                    <li>Data ditarik berdasarkan diagnosis ICD-10 obstetri dan kebidanan (O, Z34, Z35, Z39) serta dikombinasikan dengan tabel <code>operasi</code> untuk tindakan Sectio Caesaria.</li>
                    <li><strong>Pemberian Buku KIA</strong> diestimasi dari kunjungan Antenatal Baru.</li>
                    <li><strong>Skrining Tetanus</strong> diambil dari field imunisasi pada tabel Penilaian Awal Keperawatan Kebidanan.</li>
                    <li><strong>Kortikosteroid (Risiko Prematur)</strong> dan <strong>Vitamin A (Nifas)</strong> dideteksi melalui data pemberian obat.</li>
                    <li>Rujukan medis diidentifikasi dari tabel <code>rujuk_masuk</code> dengan pengelompokan kata kunci pada nama perujuk (RS, Bidan, Puskesmas).</li>
                </ul>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body p-2">
                    <div class="table-responsive" style="max-height: 600px">
                        <table class="table table-bordered table-sm w-100" id="dataTable" style="min-width: 1500px;">
                            <thead class="text-center bg-light">
                                <tr>
                                    <th rowspan="3" width="3%">No.</th>
                                    <th rowspan="3" class="text-start" width="18%">Jenis Kegiatan</th>
                                    <th colspan="7" class="bg-primary text-white">Rujukan Medis</th>
                                    <th colspan="3" class="bg-warning text-dark">Rujukan Non Medis</th>
                                    <th colspan="3" class="bg-success text-white">Non Rujukan</th>
                                    <th rowspan="3" class="bg-danger text-white">Dirujuk<br>(Keluar)</th>
                                </tr>
                                <tr>
                                    <th rowspan="2" class="bg-primary text-white">Rumah Sakit</th>
                                    <th rowspan="2" class="bg-primary text-white">Bidan</th>
                                    <th rowspan="2" class="bg-primary text-white">Puskesmas</th>
                                    <th rowspan="2" class="bg-primary text-white">Faskes Lainnya</th>
                                    <th rowspan="2" class="bg-primary text-white">Jumlah Hidup</th>
                                    <th rowspan="2" class="bg-primary text-white">Jumlah Mati</th>
                                    <th rowspan="2" class="bg-primary text-white fw-bold">Total Rujukan<br>Medis</th>
                                    <th rowspan="2" class="bg-warning text-dark">Jumlah Hidup</th>
                                    <th rowspan="2" class="bg-warning text-dark">Jumlah Mati</th>
                                    <th rowspan="2" class="bg-warning text-dark fw-bold">Total Rujukan<br>Non Medis</th>
                                    <th rowspan="2" class="bg-success text-white">Jumlah Hidup</th>
                                    <th rowspan="2" class="bg-success text-white">Jumlah Mati</th>
                                    <th rowspan="2" class="bg-success text-white fw-bold">Total Non<br>Rujukan</th>
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
                <h5 class="modal-title"><i class="fas fa-book-open me-2"></i>Petunjuk Teknis Pengisian RL 3.6</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size: 0.85rem; line-height: 1.5;">
                <div class="alert alert-primary">
                    <h6><i class="fas fa-file-alt me-2"></i>Petunjuk Teknis Kemenkes (Juknis SIRS 6.3)</h6>
                    <p>Mencatat jumlah kegiatan pelayanan kebidanan meliputi pemberian buku KIA, antenatal, persalinan (Normal, Penyulit, Bantuan, SC), komplikasi obstetri/non-obstetri, aborsi, nifas, dan pemberian Vitamin A. Data mencatat banyaknya kegiatan, bukan jumlah pasien (memungkinkan double counting).</p>
                </div>
                <div class="alert alert-warning">
                    <h6><i class="fas fa-code me-2"></i>Logika Teknis SIMKES Khanza</h6>
                    <p>Data ditarik berdasarkan diagnosis ICD-10 (Kode O/Z), tindakan operasi (Sectio), serta pemberian obat spesifik. Sistem memetakan diagnosis utama ke kategori komplikasi kebidanan SIRS.</p>
                    <hr>
                    <strong>Sumber Data:</strong>
                    <ul class="mb-0">
                        <li><code>reg_periksa</code> & <code>diagnosa_pasien</code> (Diagnosis utama & status rujukan)</li>
                        <li><code>operasi</code> & <code>paket_operasi</code> (Tindakan Sectio Caesaria)</li>
                        <li><code>pemberian_obat</code> (Data Vitamin A & Kortikosteroid)</li>
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

        $('#dataTable tbody').html('<tr><td colspan="16" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i> Memuat Laporan Kebidanan...</td></tr>');

        $.ajax({
            url: 'api/data_rl_3_6.php',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(resp) {
                var tbody = '';
                var d = resp.data;
                
                var formatNum = (num) => { return num > 0 ? num.toLocaleString('id-ID') : '0'; };

                var buildRow = function(no, judul, key, isSub = false, isHead = false) {
                    var cls = isHead ? 'head-row' : (isSub ? 'sub-row bg-light' : 'bg-light fw-bold');
                    var r = d[key] || {
                        r_medis_rs:0, r_medis_bidan:0, r_medis_pkm:0, r_medis_faskes_lain:0,
                        r_medis_hidup:0, r_medis_mati:0, total_rm:0,
                        r_non_medis_hidup:0, r_non_medis_mati:0, total_rnm:0,
                        non_rujukan_hidup:0, non_rujukan_mati:0, total_nr:0,
                        dirujuk:0
                    };
                    
                    if (isHead) {
                         return `
                            <tr class="${cls}">
                                <td class="text-center">${no}</td>
                                <td>${judul}</td>
                                <td colspan="14"></td>
                            </tr>
                        `;
                    }

                    return `
                        <tr class="${cls}">
                            <td class="text-center">${no}</td>
                            <td>${judul}</td>
                            <td class="text-end">${formatNum(r.r_medis_rs)}</td>
                            <td class="text-end">${formatNum(r.r_medis_bidan)}</td>
                            <td class="text-end">${formatNum(r.r_medis_pkm)}</td>
                            <td class="text-end">${formatNum(r.r_medis_faskes_lain)}</td>
                            <td class="text-end">${formatNum(r.r_medis_hidup)}</td>
                            <td class="text-end">${formatNum(r.r_medis_mati)}</td>
                            <td class="text-end fw-bold">${formatNum(r.total_rm)}</td>
                            <td class="text-end">${formatNum(r.r_non_medis_hidup)}</td>
                            <td class="text-end">${formatNum(r.r_non_medis_mati)}</td>
                            <td class="text-end fw-bold">${formatNum(r.total_rnm)}</td>
                            <td class="text-end">${formatNum(r.non_rujukan_hidup)}</td>
                            <td class="text-end">${formatNum(r.non_rujukan_mati)}</td>
                            <td class="text-end fw-bold">${formatNum(r.total_nr)}</td>
                            <td class="text-end fw-bold text-danger">${formatNum(r.dirujuk)}</td>
                        </tr>
                    `;
                };

                tbody += buildRow('1', 'Pemberian Buku KIA pada Ibu Hamil', '1');
                tbody += buildRow('2', 'Pelayanan Antenatal', '2');
                tbody += buildRow('3', 'Persalinan:', '3', false, true);
                tbody += buildRow('3.1', 'Persalinan pervaginam tanpa penyulit (normal)', '3.1', true);
                tbody += buildRow('3.2', 'Persalinan pervaginam spontan dengan penyulit', '3.2', true);
                tbody += buildRow('3.3', 'Persalinan pervaginam dengan bantuan', '3.3', true);
                tbody += buildRow('3.4', 'Persalinan Sectio caesaria', '3.4', true);
                tbody += buildRow('4', 'Komplikasi obstetri pada ibu hamil, bersalin, dan nifas:', '4', false, true);
                tbody += buildRow('4.1', 'Perdarahan sebelum persalinan', '4.1', true);
                tbody += buildRow('4.2', 'Perdarahan setelah persalinan', '4.2', true);
                tbody += buildRow('4.3', 'Pre eklamsia', '4.3', true);
                tbody += buildRow('4.4', 'Eklamsia', '4.4', true);
                tbody += buildRow('4.5', 'Infeksi', '4.5', true);
                tbody += buildRow('4.6', 'Abortus', '4.6', true);
                tbody += buildRow('4.7', 'Komplikasi lainnya', '4.7', true);
                tbody += buildRow('5', 'Aborsi:', '5', false, true);
                tbody += buildRow('5.1', 'Aborsi atas indikasi kedaruratan medis', '5.1', true);
                tbody += buildRow('5.2', 'Aborsi atas indikasi kehamilan akibat perkosaan', '5.2', true);
                tbody += buildRow('6', 'Skrining Status Imunisasi Tetanus', '6');
                tbody += buildRow('7', 'Komplikasi non obstetri pada ibu hamil, bersalin, dan nifas:', '7', false, true);
                tbody += buildRow('7.1', 'HIV', '7.1', true);
                tbody += buildRow('7.2', 'Hepatitis B', '7.2', true);
                tbody += buildRow('7.3', 'Sifilis', '7.3', true);
                tbody += buildRow('7.4', 'Tuberkulosis', '7.4', true);
                tbody += buildRow('7.5', 'Penyakit jantung', '7.5', true);
                tbody += buildRow('7.6', 'Anemia', '7.6', true);
                tbody += buildRow('7.7', 'Diabetes Melitus', '7.7', true);
                tbody += buildRow('7.8', 'Terkonfirmasi COVID-19', '7.8', true);
                tbody += buildRow('7.9', 'Komplikasi lainnya', '7.9', true);
                tbody += buildRow('8', 'Ibu Hamil berisiko mempunyai bayi prematur:', '8', false, true);
                tbody += buildRow('8.1', 'Diberikan antenatal kortikosteroid', '8.1', true);
                tbody += buildRow('8.2', 'Tidak diberikan antenatal kortikosteroid', '8.2', true);
                tbody += buildRow('9', 'Pelayanan Nifas', '9');
                tbody += buildRow('10', 'Ibu Nifas mendapat vitamin A', '10');

                if ($.fn.DataTable.isDataTable('#dataTable')) {
                    $('#dataTable').DataTable().destroy();
                }

                $('#dataTable tbody').html(tbody);
                
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
                            title: 'Laporan RL 3.6 Rekapitulasi Kegiatan Pelayanan Kebidanan',
                        },
                        { 
                            extend: 'print', 
                            className: 'btn btn-secondary btn-sm mb-2', 
                            text: '<i class="fas fa-print me-1"></i> Print',
                            title: 'Laporan RL 3.6 Pelayanan Kebidanan'
                        } 
                    ]
                });

            },
            error: function() { 
                $('#dataTable tbody').html('<tr><td colspan="16" class="text-center text-danger">Gagal memuat data dari server.</td></tr>');
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
