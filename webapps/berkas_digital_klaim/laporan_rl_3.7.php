<?php
/*
 * File: laporan_rl_3.7.php
 * Fungsi: Laporan RL 3.7 Rekapitulasi Kegiatan Pelayanan Neonatal, Bayi, dan Balita
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
    <title>Laporan RL 3.7 - <?= $nama_instansi ?></title>
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
        .sub-row-2 td:nth-child(2) { padding-left: 45px !important; } /* For 1.1.1 nested structure */
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
                        <span><i class="fas fa-child me-2"></i>Laporan RL 3.7 (Kegiatan Pelayanan Neonatal, Bayi, dan Balita)</span>
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
                    <li>Data ditarik berdasarkan umur pasien (Neonatal: 0-28hr, Bayi: 29hr-11bln, Anak Balita: 12-59bln) dan diagnosa (ICD-10).</li>
                    <li><strong>Bayi Lahir Hidup</strong> diidentifikasi dari tabel <code>penilaian_bayi_baru_lahir</code> menggunakan data berat badan dan estimasi umur kehamilan.</li>
                    <li><strong>Lahir Mati</strong> diidentifikasi dari status lahir pada <code>catatan_persalinan</code>.</li>
                    <li><strong>Imunisasi</strong> dideteksi dari jenis perawatan yang diberikan (BCG, Polio, DPT, Campak).</li>
                    <li><strong>Vitamin A</strong> dideteksi dari pemberian obat (Retinol/Vitamin A) dengan dosis sesuai usia (100k/200k).</li>
                    <li><strong>SHK</strong> dideteksi dari jenis perawatan dengan nama mengandung "SHK".</li>
                </ul>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body p-2">
                    <div class="table-responsive" style="max-height: 600px">
                        <table class="table table-bordered table-sm w-100" id="dataTable" style="min-width: 1500px;">
                            <thead class="text-center bg-light">
                                <tr>
                                    <th rowspan="3" width="4%">No.</th>
                                    <th rowspan="3" class="text-start" width="20%">Jenis Kegiatan</th>
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
                <h5 class="modal-title"><i class="fas fa-book-open me-2"></i>Petunjuk Teknis Pengisian RL 3.7</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size: 0.85rem; line-height: 1.5;">
                <div class="alert alert-primary">
                    <h6><i class="fas fa-file-alt me-2"></i>Petunjuk Teknis Kemenkes (Juknis SIRS 6.3)</h6>
                    <p>Mencatat kegiatan pelayanan neonatal (0-28 hari), bayi (29 hari-11 bulan), dan balita (12-59 bulan). Mencakup data kelahiran hidup (berdasarkan berat badan dan usia gestasi), lahir mati (antepartum/intrapartum), kematian neonatal, komplikasi neonatal, IMD, SHK, serta imunisasi dan vitamin. Data mencatat jumlah kegiatan, bukan jumlah pasien (memungkinkan double counting).</p>
                </div>
                <div class="alert alert-warning">
                    <h6><i class="fas fa-code me-2"></i>Logika Teknis SIMKES Khanza</h6>
                    <p>Sistem memproses data pasien dengan filter usia di bawah 5 tahun. Data kelahiran ditarik dari tabel <code>penilaian_bayi_baru_lahir</code> dan <code>catatan_persalinan</code>. Komplikasi dideteksi melalui kode ICD-10 pada tabel <code>diagnosa_pasien</code>. Data imunisasi dan vitamin dideteksi dari riwayat pemberian obat dan tindakan keperawatan terkait.</p>
                    <hr>
                    <strong>Sumber Data:</strong>
                    <ul class="mb-0">
                        <li><code>pasien</code> (Data demografi & filter usia)</li>
                        <li><code>penilaian_bayi_baru_lahir</code> (Data berat & kondisi lahir)</li>
                        <li><code>diagnosa_pasien</code> (ICD-10 untuk komplikasi & penyakit)</li>
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

        $('#dataTable tbody').html('<tr><td colspan="16" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i> Memuat Laporan Balita, Neonatal, Bayi (RL 3.7)...</td></tr>');

        $.ajax({
            url: 'api/data_rl_3_7.php',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(resp) {
                var tbody = '';
                var d = resp.data;
                
                var formatNum = (num) => { return num > 0 ? num.toLocaleString('id-ID') : '0'; };

                var buildRow = function(no, judul, key, level = 0, isHead = false, isSect = false) {
                    var cls = '';
                    if (isSect) cls = 'section-row';
                    else if (isHead) cls = 'head-row';
                    else if (level == 1) cls = 'sub-row bg-light';
                    else if (level == 2) cls = 'sub-row-2 bg-light';
                    else cls = 'bg-light fw-bold';
                    
                    var r = d[key] || {
                        r_medis_rs:0, r_medis_bidan:0, r_medis_pkm:0, r_medis_faskes_lain:0,
                        r_medis_hidup:0, r_medis_mati:0, total_rm:0,
                        r_non_medis_hidup:0, r_non_medis_mati:0, total_rnm:0,
                        non_rujukan_hidup:0, non_rujukan_mati:0, total_nr:0,
                        dirujuk:0
                    };
                    
                    if (isSect || isHead) {
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

                // Bagian A
                tbody += buildRow('A', 'NEONATAL', '', 0, false, true);
                tbody += buildRow('1', 'Bayi Lahir Hidup', '1', 0, true);
                tbody += buildRow('1.1', 'Lahir Prematur (< 37 minggu)', '1.1', 1, true);
                tbody += buildRow('1.1.1', '1500 - <2500 gram (BBLR)', '1.1.1', 2);
                tbody += buildRow('1.1.2', '1000 - <1500 gram (BBLSR)', '1.1.2', 2);
                tbody += buildRow('1.1.3', '<1000 gram (BBLER)', '1.1.3', 2);
                tbody += buildRow('1.2', 'Lahir Non Prematur (≥ 37 - 41 minggu)', '1.2', 1, true);
                tbody += buildRow('1.2.1', '1500 - <2500 gram (BBLR)', '1.2.1', 2);
                tbody += buildRow('1.2.2', '2500 - <4000 gram (BBLN)', '1.2.2', 2);
                tbody += buildRow('1.2.3', '≥4000 gram (BBLL)', '1.2.3', 2);
                tbody += buildRow('1.3', 'Lahir Lebih dari 41 minggu', '1.3', 1, true);
                tbody += buildRow('1.3.1', '1500 - <2500 gram (BBLR)', '1.3.1', 2);
                tbody += buildRow('1.3.2', '2500 - <4000 gram (BBLN)', '1.3.2', 2);
                tbody += buildRow('1.3.3', '≥4000 gram (BBLL)', '1.3.3', 2);
                
                tbody += buildRow('2', 'Lahir Mati', '2', 0, true);
                tbody += buildRow('2.1', 'Lahir Mati Antepartum', '2.1', 1);
                tbody += buildRow('2.2', 'Lahir Mati Intrapartum', '2.2', 1);
                
                tbody += buildRow('3', 'Kematian Neonatal dan Perinatal', '3', 0, true);
                tbody += buildRow('3.1', 'Kematian Neonatal Dini (0 - 7 hari)', '3.1', 1);
                tbody += buildRow('3.2', 'Kematian Neonatal Lanjut Perinatal (8 - 28 hari)', '3.2', 1);
                
                tbody += buildRow('4', 'Komplikasi Neonatal:', '4', 0, true);
                tbody += buildRow('4.1', 'Asfiksia', '4.1', 1);
                tbody += buildRow('4.2', 'Trauma Kelahiran', '4.2', 1);
                tbody += buildRow('4.3', 'BBLR', '4.3', 1);
                tbody += buildRow('4.4', 'Tetanus Neonatorum', '4.4', 1);
                tbody += buildRow('4.5', 'Kelainan Bawaan', '4.5', 1);
                tbody += buildRow('4.6', 'Covid-19', '4.6', 1);
                tbody += buildRow('4.7', 'Infeksi / Sepsis', '4.7', 1);
                tbody += buildRow('4.8', 'Komplikasi lainnya', '4.8', 1);
                
                tbody += buildRow('5', 'Bayi BBLR yang dilakukan perawatan metode kanguru', '5');
                tbody += buildRow('6', 'Bayi baru lahir yang dilakukan IMD', '6');
                tbody += buildRow('7', 'Bayi baru lahir yang dilakukan Skrining Hipertiroid Kongenital', '7');
                
                // Bagian B
                tbody += buildRow('B', 'BAYI DAN ANAK BALITA', '', 0, false, true);
                tbody += buildRow('8', 'Bayi dan Anak Balita', '8', 0, true);
                tbody += buildRow('8.1', 'Bayi Baru Lahir (0 – 28 hari)', '8.1', 1);
                tbody += buildRow('8.2', 'Bayi (29 hari – 11 bulan)', '8.2', 1);
                tbody += buildRow('8.3', 'Anak Balita (12 - 59 bulan)', '8.3', 1);
                
                tbody += buildRow('9', 'Balita Gizi Buruk', '9', 0, true);
                tbody += buildRow('9.1', 'Balita Gizi Buruk usia 0-5 bulan', '9.1', 1);
                tbody += buildRow('9.2', 'Balita Gizi Buruk usia 6-59 bulan', '9.2', 1);
                
                tbody += buildRow('10', 'Balita menggunakan Buku KIA', '10');
                
                tbody += buildRow('11', 'Balita dilakukan skrining pertumbuhan dan perkembangan', '11', 0, true);
                tbody += buildRow('11.1', 'Skrining Pertumbuhan sesuai umur', '11.1', 1);
                tbody += buildRow('11.2', 'Skrining perkembangan sesuai umur', '11.2', 1);
                tbody += buildRow('11.3', 'Skrining keterlambatan bicara dan bahasa', '11.3', 1);
                tbody += buildRow('11.4', 'Assessment kelainan motoric', '11.4', 1);
                tbody += buildRow('11.5', 'Skrining Kelainan Perilaku', '11.5', 1);
                tbody += buildRow('11.6', 'Skrining Gangguan Pendengaran', '11.6', 1);
                tbody += buildRow('11.7', 'Skrining Gangguan Penglihatan', '11.7', 1);
                
                tbody += buildRow('12', 'Bayi mendapatkan imunisasi, Vitamin, dan Pengobatan Profilaksis:', '12', 0, true);
                tbody += buildRow('12.1', 'Hb 0', '12.1', 1);
                tbody += buildRow('12.2', 'BCG', '12.2', 1);
                tbody += buildRow('12.3', 'Polio 1,2,3', '12.3', 1);
                tbody += buildRow('12.4', 'DPT-HB-HiB 1, 2,3,4', '12.4', 1);
                tbody += buildRow('12.5', 'IPV', '12.5', 1);
                tbody += buildRow('12.6', 'Campak-Rubella', '12.6', 1);
                tbody += buildRow('12.7', 'Vitamin A 100.000 SI', '12.7', 1);
                tbody += buildRow('12.8', 'Pemberian KIE', '12.8', 1);

                tbody += buildRow('13', 'Bayi yang lahir dari Ibu HIV +', '13', 0, true);
                tbody += buildRow('13.1', 'Pemeriksaan Early Infant Diagnosis (EID)', '13.1', 1);
                tbody += buildRow('13.2', 'Pengobatan ARV bagi balita HIV+', '13.2', 1);
                tbody += buildRow('13.3', 'Pengobatan profilaksis kotrimoksazol', '13.3', 1);

                tbody += buildRow('14', 'Bayi yang lahir dari Ibu Sifilis +', '14', 0, true);
                tbody += buildRow('14.1', 'Pemeriksaan Titer RPR', '14.1', 1);
                tbody += buildRow('14.2', 'Pengobatan dosis tunggal Benzatin Penicilin G', '14.2', 1);

                tbody += buildRow('15', 'Bayi yang lahir dari Ibu Hepatitis +', '15', 0, true);
                tbody += buildRow('15.1', 'Pemeriksaan serologis HBs Ag', '15.1', 1);
                tbody += buildRow('15.2', 'Pemberian Hb 0', '15.2', 1);
                tbody += buildRow('15.3', 'Pemberian Hb Ig', '15.3', 1);

                tbody += buildRow('16', 'Anak Balita mendapatkan Imunisasi, Vitamin, dan Pengobatan:', '16', 0, true);
                tbody += buildRow('16.1', 'Campak-Rubela', '16.1', 1);
                tbody += buildRow('16.2', 'Vitamin A 200.000 SI', '16.2', 1);
                tbody += buildRow('16.3', 'Balita mendapat obat pencegahan kecacingan 1 kali setahun', '16.3', 1);
                tbody += buildRow('16.4', 'Balita terduga TBC mendapat TPT', '16.4', 1);
                tbody += buildRow('16.5', 'Balita TBC mendapatkan OAT', '16.5', 1);
                tbody += buildRow('16.6', 'Pemberian KIE', '16.6', 1);

                tbody += buildRow('17', 'Balita Gizi Buruk mendapat perawatan', '17', 0, true);
                tbody += buildRow('17.1', 'Balita Gizi Buruk usia 0-5 bulan rawat inap', '17.1', 1);
                tbody += buildRow('17.2', 'Balita Gizi Buruk usia 6-59 bulan rawat inap', '17.2', 1);
                tbody += buildRow('17.3', 'Balita Gizi Buruk usia 6-59 bulan rawat jalan', '17.3', 1);


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
                            title: 'Laporan RL 3.7 Rekapitulasi Kegiatan Pelayanan Neonatal, Bayi, dan Balita',
                        },
                        { 
                            extend: 'print', 
                            className: 'btn btn-secondary btn-sm mb-2', 
                            text: '<i class="fas fa-print me-1"></i> Print',
                            title: 'Laporan RL 3.7 Pelayanan Balita'
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
