<?php
/*
 * File: laporan_rl_3.8.php
 * Fungsi: Laporan RL 3.8 Rekapitulasi Kegiatan Pelayanan Laboratorium
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
    <title>Laporan RL 3.8 - <?= $nama_instansi ?></title>
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
        table.dataTable thead th:nth-child(1) { left: 0; z-index: 2; width: 40px; }
        table.dataTable tbody td:nth-child(1) { left: 0; }
        table.dataTable thead th:nth-child(2) { left: 40px; z-index: 2; }
        table.dataTable tbody td:nth-child(2) { left: 40px; text-align: left; }
        table.dataTable thead th:nth-child(2), table.dataTable tbody td:nth-child(2) { border-right: 2px solid #ccc; max-width: 350px; white-space: normal; }

        .sub-row td:nth-child(2) { padding-left: 25px !important; }
        .sub-row-2 td:nth-child(2) { padding-left: 45px !important; } 
        .head-row { font-weight: bold; background-color: #e9ecef !important; }
        .section-row { font-weight: bold; background-color: #d1ecf1 !important; color: #0c5460; font-size: 0.9rem;}
        
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
                        <span><i class="fas fa-flask me-2"></i>Laporan RL 3.8 (Kegiatan Pelayanan Laboratorium)</span>
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
                    <li>Data ditarik dari tabel <code>detail_periksa_lab</code> dan dipetakan dengan menggunakan <i>keyword matching</i> nama pemeriksaan di template laboratorium.</li>
                    <li>Sistem menghitung frekuensi pasien diperiksa (Laki-laki / Perempuan) dan menghitung nilai Rata-rata untuk hasil berbentuk numerik/angka.</li>
                    <li>Untuk hasil kualitatif (Negatif, Positif, Reaktif), rata-rata akan bernilai 0.</li>
                </ul>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body p-2">
                    <div class="table-responsive" style="max-height: 600px">
                        <table class="table table-bordered table-sm w-100" id="dataTable" style="min-width: 1000px;">
                            <thead class="text-center bg-light">
                                <tr>
                                    <th rowspan="2" width="4%">No.</th>
                                    <th rowspan="2" class="text-start" width="25%">Pemeriksaan</th>
                                    <th colspan="2" class="bg-primary text-white">Jumlah Pemeriksaan</th>
                                    <th colspan="2" class="bg-success text-white">Nilai Rata-Rata Pemeriksaan</th>
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
                <h5 class="modal-title"><i class="fas fa-book-open me-2"></i>Petunjuk Teknis Pengisian RL 3.8</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size: 0.85rem; line-height: 1.5;">
                <div class="alert alert-primary">
                    <h6><i class="fas fa-file-alt me-2"></i>Petunjuk Teknis Kemenkes (Juknis SIRS 6.3)</h6>
                    <p>Mencakup data kegiatan pelayanan laboratorium (Patologi Klinik, Mikrobiologi Klinik, Parasitologi Klinik, dan Patologi Anatomi). Data yang dilaporkan adalah jumlah pemeriksaan berdasarkan jenis kelamin pasien serta nilai rata-rata hasil pemeriksaan (untuk hasil numerik). Seorang pasien dapat tercatat dalam beberapa jenis pemeriksaan.</p>
                </div>
                <div class="alert alert-warning">
                    <h6><i class="fas fa-code me-2"></i>Logika Teknis SIMKES Khanza</h6>
                    <p>Sistem menarik data dari tabel <code>detail_periksa_lab</code> yang digabungkan dengan <code>template_laboratorium</code> untuk pemetaan kategori pemeriksaan SIRS. Nilai rata-rata dihitung secara otomatis dari hasil pemeriksaan yang bertipe angka (numerik) pada periode laporan.</p>
                    <hr>
                    <strong>Sumber Data:</strong>
                    <ul class="mb-0">
                        <li><code>detail_periksa_lab</code> (Data hasil pemeriksaan detail)</li>
                        <li><code>template_laboratorium</code> (Master kategori & nama tes)</li>
                        <li><code>periksa_lab</code> (Data registrasi lab & identitas pasien)</li>
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

        $('#dataTable tbody').html('<tr><td colspan="6" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i> Memuat Laporan Laboratorium (RL 3.8)...</td></tr>');

        $.ajax({
            url: 'api/data_rl_3_8.php',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(resp) {
                var tbody = '';
                var d = resp.data;
                
                var formatNum = (num) => { return num > 0 ? num.toLocaleString('id-ID') : '0'; };
                var formatAvg = (num) => { return num > 0 ? Number(num).toLocaleString('id-ID', {minimumFractionDigits: 1, maximumFractionDigits: 2}) : '0'; };

                var buildRow = function(no, judul, key, level = 0, isHead = false, isSect = false) {
                    var cls = '';
                    if (isSect) cls = 'section-row';
                    else if (isHead) cls = 'head-row';
                    else if (level == 1) cls = 'sub-row bg-light';
                    else if (level == 2) cls = 'sub-row-2 bg-light';
                    else cls = 'bg-light fw-bold';
                    
                    var r = d[key] || {
                        jml_l:0, jml_p:0, avg_l:0, avg_p:0
                    };
                    
                    if (isSect || isHead) {
                         return `
                            <tr class="${cls}">
                                <td class="text-center">${no}</td>
                                <td>${judul}</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        `;
                    }

                    return `
                        <tr class="${cls}">
                            <td class="text-center">${no}</td>
                            <td>${judul}</td>
                            <td class="text-end fw-bold">${formatNum(r.jml_l)}</td>
                            <td class="text-end fw-bold">${formatNum(r.jml_p)}</td>
                            <td class="text-end fw-bold text-success">${formatAvg(r.avg_l)}</td>
                            <td class="text-end fw-bold text-success">${formatAvg(r.avg_p)}</td>
                        </tr>
                    `;
                };

                // Bagian A
                tbody += buildRow('A', 'PATOLOGI KLINIK', '', 0, false, true);
                
                tbody += buildRow('1', 'Hematologi', '1', 0, true);
                tbody += buildRow('1.1', 'Kadar Hemoglobin', '1.1', 1);
                tbody += buildRow('1.2', 'Nilai Hematokrit', '1.2', 1);
                tbody += buildRow('1.3', 'Hitung Lekosit', '1.3', 1);
                tbody += buildRow('1.4', 'Hitung Eritrosit', '1.4', 1);
                tbody += buildRow('1.5', 'Hitung Eosinophil', '1.5', 1);
                tbody += buildRow('1.6', 'Hitung Jenis Lekosit (%/absolut)', '1.6', 1);
                tbody += buildRow('1.7', 'Laju Endap Darah', '1.7', 1);
                tbody += buildRow('1.8', 'Hitung Retikulosit', '1.8', 1);
                tbody += buildRow('1.9', 'Hitung Trombosit', '1.9', 1);
                
                tbody += buildRow('2', 'Kimia Klinik', '2', 0, true);
                tbody += buildRow('2.1', 'Protein Total', '2.1', 1);
                tbody += buildRow('2.2', 'Albumin', '2.2', 1);
                tbody += buildRow('2.3', 'Globulin', '2.3', 1);
                tbody += buildRow('2.4', 'Bilirubin Total/Direk/Indirek', '2.4', 1);
                tbody += buildRow('2.5', 'SGOT/AST', '2.5', 1);
                tbody += buildRow('2.6', 'SGPT/ALT', '2.6', 1);
                tbody += buildRow('2.7', 'Ureum/BUN', '2.7', 1);
                tbody += buildRow('2.8', 'Kreatinin (eGFR)', '2.8', 1);
                tbody += buildRow('2.9', 'Asam Urat', '2.9', 1);
                tbody += buildRow('2.10', 'Trigliserida', '2.10', 1);
                tbody += buildRow('2.11', 'Kolesterol Total', '2.11', 1);
                tbody += buildRow('2.12', 'Kolesterol HDL', '2.12', 1);
                tbody += buildRow('2.13', 'Kolesterol LDL (direk)', '2.13', 1);
                tbody += buildRow('2.14', 'Glukosa Sewaktu/Puasa / 2jam PP', '2.14', 1);
                tbody += buildRow('2.15', 'HbA1c', '2.15', 1);
                tbody += buildRow('2.16', 'Fosfatase alkali', '2.16', 1);
                tbody += buildRow('2.17', 'Gamma GT', '2.17', 1);
                tbody += buildRow('2.18', 'LDH', '2.18', 1);
                tbody += buildRow('2.19', 'G 6 PD', '2.19', 1);
                tbody += buildRow('2.20', 'Amilase', '2.20', 1);
                tbody += buildRow('2.21', 'Lipase', '2.21', 1);
                tbody += buildRow('2.22', 'Cholinesterase', '2.22', 1);
                tbody += buildRow('2.23', 'CK Total -CK MB', '2.23', 1);
                tbody += buildRow('2.24', 'SI/TIBC', '2.24', 1);
                tbody += buildRow('2.25', 'Elektrolit Darah (Na, K, Cl, Ca, Mg, P)', '2.25', 1);
                tbody += buildRow('2.26', 'Analisa Gas Darah', '2.26', 1);
                
                tbody += buildRow('3', 'Imunologi Klinik', '3', 0, true);
                tbody += buildRow('3.1', 'Widal', '3.1', 1);
                tbody += buildRow('3.2', 'Antibodi anti SARS-CoV-2', '3.2', 1);
                tbody += buildRow('3.3', 'Antigen SARS-CoV-2', '3.3', 1);
                tbody += buildRow('3.4', 'Dengue IgG-IgM', '3.4', 1);
                tbody += buildRow('3.5', 'HBs Ag', '3.5', 1);
                tbody += buildRow('3.6', 'Anti HBs', '3.6', 1);
                tbody += buildRow('3.7', 'Anti HBc', '3.7', 1);
                tbody += buildRow('3.8', 'Anti HBe', '3.8', 1);
                tbody += buildRow('3.9', 'Hbe Ag', '3.9', 1);
                tbody += buildRow('3.10', 'Anti HCV', '3.10', 1);
                tbody += buildRow('3.11', 'IgM Anti HAV', '3.11', 1);
                tbody += buildRow('3.12', 'Anti HIV', '3.12', 1);
                tbody += buildRow('3.13', 'NS1 (non structure antigen) Dengue', '3.13', 1);
                tbody += buildRow('3.14', 'Tes Antigen Malaria', '3.14', 1);
                tbody += buildRow('3.15', 'T3/T4 total', '3.15', 1);
                tbody += buildRow('3.16', 'FT3/FT4', '3.16', 1);
                tbody += buildRow('3.17', 'TSH', '3.17', 1);
                
                tbody += buildRow('4', 'Urinalisis dan analisis cairan', '4', 0, true);
                tbody += buildRow('4.1', 'Protein/albumin', '4.1', 1);
                tbody += buildRow('4.2', 'Urobilinogen', '4.2', 1);
                tbody += buildRow('4.3', 'Bilirubin', '4.3', 1);
                tbody += buildRow('4.4', 'Sedimen Urine', '4.4', 1);
                tbody += buildRow('4.5', 'NAPZA Skrining', '4.5', 1);
                
                tbody += buildRow('5', 'Hemostasis', '5', 0, true);
                tbody += buildRow('5.1', 'Masa perdarahan', '5.1', 1);
                tbody += buildRow('5.2', 'Masa pembekuan', '5.2', 1);
                tbody += buildRow('5.3', 'Masa prothrombin plasma', '5.3', 1);
                tbody += buildRow('5.4', 'Masa tromboplastin partial teraktivasi', '5.4', 1);
                tbody += buildRow('5.5', 'Masa thrombin', '5.5', 1);
                tbody += buildRow('5.6', 'Fibrinogen', '5.6', 1);
                tbody += buildRow('5.7', 'D-dimer', '5.7', 1);
                tbody += buildRow('5.8', 'Lupus anticoagulant', '5.8', 1);

                // Bagian B
                tbody += buildRow('B', 'MIKROBIOLOGI KLINIK', '', 0, false, true);
                tbody += buildRow('6', 'Pemeriksaan dahak mikroskopis TBC BTA', '6', 0, true);
                tbody += buildRow('6.1', 'Negatif', '6.1', 1);
                tbody += buildRow('6.2', '1-9', '6.2', 1);
                tbody += buildRow('6.3', '1+', '6.3', 1);
                tbody += buildRow('6.4', '2+', '6.4', 1);
                tbody += buildRow('6.5', '3+', '6.5', 1);
                tbody += buildRow('6.6', 'Tidak Dilakukan', '6.6', 1);
                
                tbody += buildRow('7', 'Biakan dan identifikasi bakteri aerob, serta uji kepekaan', '7');
                tbody += buildRow('8', 'Biakan virus dan uji kepekaan terhadap antivirus', '8');
                tbody += buildRow('9', 'Biakan dan identifikasi M. tuberculosis dan uji kepekaan tthd OAT', '9');
                
                tbody += buildRow('10', 'Pemeriksaan berbasis molekuler untuk deteksi virus', '10', 0, true);
                tbody += buildRow('10.1', 'PCR', '10.1', 1);
                tbody += buildRow('10.2', 'Real time PCR', '10.2', 1);
                tbody += buildRow('10.3', 'Tes Cepat Molekuler', '10.3', 1);
                tbody += buildRow('10.4', 'Hibridisasi', '10.4', 1);
                tbody += buildRow('10.5', 'Sekuensing', '10.5', 1);
                tbody += buildRow('10.6', 'Metode lainnya', '10.6', 1);
                
                tbody += buildRow('11', 'Pemeriksaan Tes Cepat Molekuler (TCM) untuk TBC / RO', '11', 0, true);
                tbody += buildRow('11.1', 'Negatif', '11.1', 1);
                tbody += buildRow('11.2', 'Rif Sen', '11.2', 1);
                tbody += buildRow('11.3', 'Rif Res', '11.3', 1);
                tbody += buildRow('11.4', 'Rif Indet', '11.4', 1);
                tbody += buildRow('11.5', 'Invalid', '11.5', 1);
                tbody += buildRow('11.6', 'Error', '11.6', 1);
                tbody += buildRow('11.7', 'No Result', '11.7', 1);
                tbody += buildRow('11.8', 'Tidak Dilakukan', '11.8', 1);

                tbody += buildRow('12', 'Pemeriksaan PCR molekuler bakteri aerob, anaerob', '12', 0, true);
                tbody += buildRow('12.1', 'PCR', '12.1', 1);
                tbody += buildRow('12.2', 'Real time PCR', '12.2', 1);
                tbody += buildRow('12.3', 'Tes Cepat Molekuler', '12.3', 1);
                
                tbody += buildRow('13', 'Pemeriksaan berbasis molekuler untuk deteksi gen pengkode', '13', 0, true);
                tbody += buildRow('13.1', 'PCR', '13.1', 1);
                
                tbody += buildRow('14', 'Pemeriksaan berbasis molekuler untuk jamur', '14', 0, true);
                tbody += buildRow('14.1', 'PCR', '14.1', 1);

                // Bagian C
                tbody += buildRow('C', 'PARASITOLOGI KLINIK', '', 0, false, true);
                tbody += buildRow('15', 'Pemeriksaan Mikroskopis', '15', 0, true);
                tbody += buildRow('15.1', 'Identifikasi cacing, larva/proglottid', '15.1', 1);
                tbody += buildRow('15.2', 'Identifikasi arthropoda', '15.2', 1);
                tbody += buildRow('15.3', 'Identifikasi nyamuk', '15.3', 1);
                tbody += buildRow('15.4', 'Identifikasi lalat', '15.4', 1);
                
                tbody += buildRow('16', 'Pemeriksaan Jamur', '16', 0, true);
                tbody += buildRow('16.1', 'Pemeriksaan langsung KOH', '16.1', 1);
                tbody += buildRow('16.2', 'Pemeriksaan langsung LPCB', '16.2', 1);
                tbody += buildRow('16.3', 'Pemeriksaan jamur pulasan', '16.3', 1);

                // Bagian D
                tbody += buildRow('D', 'PATOLOGI ANATOMI', '', 0, false, true);
                tbody += buildRow('17', 'Pemeriksaan tindakan biopsi aspirasi jarum halus', '17');
                
                tbody += buildRow('18', 'Pemeriksaan Sitopatologi', '18', 0, true);
                tbody += buildRow('18.1', 'Pemeriksaan Pap’s Smear', '18.1', 1);
                tbody += buildRow('18.2', 'Pemeriksaan sitologi apus non ginekologi', '18.2', 1);
                tbody += buildRow('18.3', 'Pemeriksaan sitologi cairan', '18.3', 1);
                
                tbody += buildRow('19', 'Pemeriksaan Histopatologi', '19', 0, true);
                tbody += buildRow('19.1', 'Pemeriksaan jaringan kecil', '19.1', 1);
                tbody += buildRow('19.2', 'Pemeriksaan jaringan sedang', '19.2', 1);
                tbody += buildRow('19.3', 'Pemeriksaan jaringan besar', '19.3', 1);
                
                tbody += buildRow('20', 'Pemeriksaan Imunopatologi', '20', 0, true);
                tbody += buildRow('20.1', 'Pemeriksaan imunohistokimia Payudara', '20.1', 1);
                tbody += buildRow('20.2', 'Pemeriksaan imunohistokimia Limfoma', '20.2', 1);
                
                tbody += buildRow('21', 'Pemeriksaan Patologi Molekuler', '21', 0, true);
                tbody += buildRow('21.1', 'Deteksi mutasi EGFR', '21.1', 1);
                
                tbody += buildRow('22', 'Pemeriksaan Potong Beku', '22');
                tbody += buildRow('23', 'Pemeriksaan Otopsi Klinik', '23');

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
                            title: 'Laporan RL 3.8 Rekapitulasi Kegiatan Pelayanan Laboratorium',
                        },
                        { 
                            extend: 'print', 
                            className: 'btn btn-secondary btn-sm mb-2', 
                            text: '<i class="fas fa-print me-1"></i> Print',
                            title: 'Laporan RL 3.8 Pelayanan Laboratorium'
                        } 
                    ]
                });

            },
            error: function() { 
                $('#dataTable tbody').html('<tr><td colspan="6" class="text-center text-danger">Gagal memuat data dari server.</td></tr>');
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
