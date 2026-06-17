<?php
/*
 * File: laporan_rl_3.1.php
 * Fungsi: Laporan RL 3.1 Indikator Pelayanan
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
$nama_user_login = $_SESSION['namauser'] ?? "User";
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-01'); 
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan RL 3.1 - Indikator Pelayanan</title>
    <link rel="icon" href="logo.php" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; font-size: 0.9rem; }
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
                    <div class="fw-bold text-dark small"><?= htmlspecialchars($nama_user_login) ?></div>
                    <small class="text-muted" style="font-size:0.75rem">Petugas Casemix</small>
                </div>
                <img src="<?= $logo_b64 ?>" class="rounded-circle border" width="35" alt="Logo">
            </div>
        </nav>

        <div class="container-fluid px-4 py-4">
            
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body py-3">
                    <h5 class="fw-bold text-primary mb-3 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-chart-line me-2"></i>Laporan RL 3.1 (Indikator Pelayanan)</span>
                        <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalJuknis">
                            <i class="fas fa-info-circle me-1"></i> Petunjuk Teknis
                        </button>
                    </h5>
                    <form id="filterForm">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Dari Tanggal</label>
                                <input type="date" class="form-control" id="tgl_awal" value="<?= htmlspecialchars($tgl_awal) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Sampai Tanggal</label>
                                <input type="date" class="form-control" id="tgl_akhir" value="<?= htmlspecialchars($tgl_akhir) ?>">
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

            <!-- Card Summary Data Dasar -->
            <div class="row g-3 mb-4" id="summary-cards" style="display:none;">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 border-start border-primary border-4">
                        <div class="card-body py-2">
                            <div class="small text-muted fw-bold">TOTAL TEMPAT TIDUR</div>
                            <div class="h4 mb-0 fw-bold text-primary" id="sum-bed">-</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 border-start border-success border-4">
                        <div class="card-body py-2">
                            <div class="small text-muted fw-bold">TOTAL HARI PERAWATAN</div>
                            <div class="h4 mb-0 fw-bold text-success" id="sum-hp">-</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 border-start border-secondary border-4">
                        <div class="card-body py-2">
                            <div class="small text-muted fw-bold">PERIODE HARI</div>
                            <div class="h4 mb-0 fw-bold text-secondary" id="sum-periode">-</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-box shadow-sm">
                <strong><i class="fas fa-info-circle me-1"></i> Informasi Indikator:</strong><br>
                <ul class="mb-0 ps-3">
                    <li><b>BOR:</b> Persentase pemakaian tempat tidur (Ideal: 60-85%).</li>
                    <li><b>ALOS:</b> Rata-rata lama rawat pasien (Ideal: 6-9 hari).</li>
                    <li><b>BTO:</b> Frekuensi pemakaian tempat tidur (Ideal: 2-4 kali per bulan).</li>
                    <li><b>TOI:</b> Rata-rata hari tempat tidur kosong (Ideal: 1-3 hari).</li>
                    <li><b>NDR:</b> Angka kematian > 48 jam (Toleransi: < 25‰).</li>
                    <li><b>GDR:</b> Angka kematian umum (Toleransi: < 45‰).</li>
                </ul>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm w-100" id="dataTable">
                            <thead class="text-center">
                                <tr>
                                    <th class="bg-dark text-white">No</th>
                                    <th class="bg-dark text-white text-start">Jenis Pelayanan</th>
                                    <th class="bg-primary text-white">BOR (%)</th>
                                    <th class="bg-primary text-white">ALOS (Hari)</th>
                                    <th class="bg-primary text-white">BTO (Kali)</th>
                                    <th class="bg-primary text-white">TOI (Hari)</th>
                                    <th class="bg-danger text-white">NDR (‰)</th>
                                    <th class="bg-danger text-white">GDR (‰)</th>
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
                <h5 class="modal-title"><i class="fas fa-book-open me-2"></i>Petunjuk Teknis Pengisian RL 3.1</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size: 0.85rem; line-height: 1.5;">
                <div class="alert alert-primary">
                    <h6><i class="fas fa-file-alt me-2"></i>Petunjuk Teknis Kemenkes (Juknis SIRS 6.3)</h6>
                    <p>Formulir RL 3.1 melaporkan indikator efisiensi pelayanan rawat inap yang dikelompokkan dalam 5 kategori utama: Non Intensif, ICU, NICU, PICU, dan Intensif Lainnya. Indikator meliputi:</p>
                    <ul class="mb-2">
                        <li><b>BOR:</b> Persentase pemakaian tempat tidur (Ideal 60-85%).</li>
                        <li><b>ALOS:</b> Rata-rata lama rawat pasien keluar (Ideal 6-9 hari).</li>
                        <li><b>BTO:</b> Frekuensi pemakaian tempat tidur (Ideal 40-50 kali/tahun).</li>
                        <li><b>TOI:</b> Rata-rata hari tempat tidur kosong (Ideal 1-3 hari).</li>
                        <li><b>NDR:</b> Angka kematian > 48 jam per 1000 pasien keluar.</li>
                        <li><b>GDR:</b> Angka kematian umum per 1000 pasien keluar.</li>
                    </ul>
                </div>
                <div class="alert alert-warning">
                    <h6><i class="fas fa-code me-2"></i>Logika Teknis SIMKES Khanza (Advanced)</h6>
                    <p>Sistem ini dirancang untuk bekerja secara otomatis pada database SIMRS Khanza tanpa memerlukan konfigurasi manual yang rumit:</p>
                    <ol class="ps-3 mb-2">
                        <li><b>Pemetaan Bangsal Otomatis (Dynamic Mapping):</b> Sistem memindai nama bangsal Anda dan mencocokkannya dengan kategori SIRS menggunakan kata kunci berikut:
                            <div class="bg-white p-2 border rounded mt-1 small">
                                <b>ICU/NICU/PICU/HCU/RICU</b> (berdasarkan nama); <b>Penyakit Dalam</b> (DALAM, INTERNA, IPD); 
                                <b>Anak</b> (ANAK); <b>Obstetri</b> (OBSTETRI, OBGIN, BERSALIN, KANDUNGAN, VK); 
                                <b>Bedah</b> (BEDAH); <b>Saraf</b> (SARAF); <b>Jantung</b> (JANTUNG, KARDIOLOGI); 
                                <b>Perinatologi</b> (PERINATOLOGI, BAYI); <b>Isolasi</b> (ISOLASI). 
                                <br><i class="text-muted">*Selain kata kunci di atas akan masuk kategori 'Umum'.</i>
                            </div>
                        </li>
                        <li><b>Pembersihan Data Gantung:</b> Jika BOR > 100%, biasanya terdapat pasien yang secara fisik sudah pulang namun di sistem Khanza statusnya masih 'Dirawat'. Sistem ini memitigasi hal tersebut dengan memvalidasi status registrasi, namun tetap memunculkan peringatan jika data di modul bangsal belum ditutup.</li>
                        <li><b>Kalkulasi Presisi Tinggi:</b> Menggunakan perhitungan berbasis detik (Time-Based) untuk durasi rawat, menjamin akurasi BOR saat terjadi mutasi pasien antar bangsal di hari yang sama.</li>
                    </ol>
                    <hr>
                    <strong>Langkah Perbaikan Jika Data Anomali:</strong>
                    <ul class="mb-0 small">
                        <li>Cek menu <b>Kamar Inap</b> di Khanza, filter pasien yang belum pulang.</li>
                        <li>Pastikan tidak ada pasien dari tahun/bulan lalu yang statusnya masih menginap.</li>
                        <li>Gunakan tombol <b>'Ganti Kamar'</b> atau <b>'Pulangkan Pasien'</b> untuk menutup record yang menggantung.</li>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
                    title: 'Laporan RL 3.1 Indikator Pelayanan',
                },
                { 
                    extend: 'print', 
                    className: 'btn btn-secondary btn-sm mb-2', 
                    text: '<i class="fas fa-print me-1"></i> Print',
                } 
            ],
            columns: [
                { data: "no", className: "text-center fw-bold" },
                { data: "jenis_pelayanan", className: "fw-bold text-start" },
                { data: "bor", className: "text-end", render: (data) => parseFloat(data).toFixed(2) + ' %' },
                { data: "alos", className: "text-end", render: (data) => parseFloat(data).toFixed(2) },
                { data: "bto", className: "text-end", render: (data) => parseFloat(data).toFixed(2) },
                { data: "toi", className: "text-end", render: (data) => parseFloat(data).toFixed(2) },
                { data: "ndr", className: "text-end text-danger", render: (data) => parseFloat(data).toFixed(2) + ' ‰' },
                { data: "gdr", className: "text-end text-danger", render: (data) => parseFloat(data).toFixed(2) + ' ‰' }
            ],
            rowCallback: function(row, data) {
                if (data.no == 77) {
                    $(row).addClass('table-dark fw-bold');
                }
                if (parseFloat(data.bor) > 100) {
                    $(row).find('td:eq(2)').addClass('bg-danger text-white');
                }
            },
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
            url: 'api/data_rl_3_1.php',
            type: 'GET',
            data: params,
            dataType: 'json',
            beforeSend: function() {
                myTable.clear().draw();
                $('#summary-cards').hide();
            },
            success: function(resp) {
                if(resp && resp.data) {
                    myTable.rows.add(resp.data).draw();
                    
                    if (resp.summary) {
                        $('#sum-bed').text(resp.summary.bed);
                        $('#sum-hp').text(resp.summary.hp);
                        $('#sum-periode').text(resp.summary.periode + ' Hari');
                        $('#summary-cards').fadeIn();
                    }

                    if (resp.anomalies && resp.anomalies.length > 0) {
                        showAnomalies(resp.anomalies);
                    }
                } else {
                    alert("Data tidak ditemukan atau format respons tidak sesuai.");
                }
            },
            error: function(jqXHR, textStatus, errorThrown) { 
                alert("Gagal memuat data: " + textStatus + " (" + errorThrown + ")"); 
            }
        });
    }

    function showAnomalies(anomalies) {
        let listHtml = '<div class="text-start small mt-3"><table class="table table-sm table-bordered"><thead><tr class="bg-light"><th>Kategori</th><th>BOR</th><th>HP</th><th>Kapasitas</th></tr></thead><tbody>';
        anomalies.forEach(a => {
            listHtml += `<tr><td>${a.kategori}</td><td class="text-danger fw-bold">${a.bor}%</td><td>${parseFloat(a.hp).toFixed(2)}</td><td>${parseFloat(a.kapasitas).toFixed(2)}</td></tr>`;
        });
        listHtml += '</tbody></table><p class="mt-2 text-muted small"><b>Mengapa ini terjadi?</b> Di Khanza, bangsal <b>Perinatologi/Bayi</b> seringkali memiliki BOR > 100% karena satu bed bayi digunakan bergantian dengan sangat cepat atau adanya "Data Gantung" (pasien yang sudah pulang fisik tapi status di sistem masih menginap). <br><br><b>Saran:</b> Koordinasikan dengan petugas bangsal untuk melakukan <i>checkout</i> pada pasien yang sudah pulang.</p></div>';

        Swal.fire({
            title: 'Peringatan Anomali Kapasitas!',
            html: `Ditemukan kategori pelayanan dengan hunian melebihi 100%.<br>${listHtml}`,
            icon: 'warning',
            confirmButtonText: 'Saya Mengerti',
            width: '600px'
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
