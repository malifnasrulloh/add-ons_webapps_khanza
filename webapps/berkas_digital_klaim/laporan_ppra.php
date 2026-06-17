<?php
/*
 * File: laporan_ppra.php
 * Fungsi: Laporan Pemberian Antibiotik (Data PPRA) dengan filter dinamis & Resume Medis (Dinamis Button)
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
$nama_user_login = $_SESSION['casemix_user'] ?? 'User';

// Ambil Nama Lengkap User
$q_pegawai = mysqli_query($koneksi, "SELECT nama FROM pegawai WHERE nik = '$nama_user_login'");
if (mysqli_num_rows($q_pegawai) > 0) {
    $r_peg = mysqli_fetch_assoc($q_pegawai);
    $nama_user_login = $r_peg['nama'];
} else {
    $q_dok = mysqli_query($koneksi, "SELECT nm_dokter FROM dokter WHERE kd_dokter = '$nama_user_login'");
    if (mysqli_num_rows($q_dok) > 0) {
        $r_dok = mysqli_fetch_assoc($q_dok);
        $nama_user_login = $r_dok['nm_dokter'];
    }
}

$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-01'); 
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');

// Load Golongan Obat secara dinamis dari database
$golongans = [];
$q_gol = mysqli_query($koneksi, "SELECT kode, nama FROM golongan_barang ORDER BY nama ASC");
while ($r_gol = mysqli_fetch_assoc($q_gol)) {
    $golongans[] = $r_gol;
}

// Cari kode golongan antibiotik secara dinamis berdasarkan nama golongan yang mengandung kata "antibio"
$default_antibiotik_kode = '';
$q_def = mysqli_query($koneksi, "SELECT kode FROM golongan_barang WHERE nama LIKE '%antibio%' LIMIT 1");
if ($r_def = mysqli_fetch_assoc($q_def)) {
    $default_antibiotik_kode = $r_def['kode'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data PPRA (Pemberian Antibiotik) - <?= htmlspecialchars($nama_instansi) ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; font-size: 0.85rem; }
        .top-navbar { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); padding: 10px 20px; }
        .table th { white-space: nowrap; text-align: center; vertical-align: middle; background-color: #f8f9fa; }
        .info-box { background: #e8f4fd; border-left: 4px solid #0086f4; padding: 10px 15px; margin-bottom: 20px; border-radius: 4px; font-size: 0.85rem;}
        
        /* Modern styling options */
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.25);
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
                    <div class="fw-bold text-dark small"><?= htmlspecialchars($nama_user_login) ?></div>
                    <small class="text-muted" style="font-size:0.75rem">Petugas Casemix</small>
                </div>
                <img src="logo.php" class="rounded-circle border" width="35">
            </div>
        </nav>

        <div class="container-fluid px-4 py-4">
            
            <div class="card glass-card shadow-sm mb-4 border-0">
                <div class="card-body py-3">
                    <h5 class="fw-bold text-primary mb-3 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-pills me-2"></i>Data Pemberian Antibiotik (PPRA)</span>
                    </h5>
                    <form id="filterForm">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label small text-muted fw-bold">Dari Tanggal</label>
                                <input type="date" class="form-control form-control-sm" id="tgl_awal" value="<?= $tgl_awal ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted fw-bold">Sampai Tanggal</label>
                                <input type="date" class="form-control form-control-sm" id="tgl_akhir" value="<?= $tgl_akhir ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted fw-bold">Golongan Obat</label>
                                <select class="form-select form-select-sm" id="kode_golongan">
                                    <option value="">-- Semua Golongan --</option>
                                    <?php foreach ($golongans as $g): ?>
                                        <option value="<?= htmlspecialchars($g['kode']) ?>" <?= $g['kode'] === $default_antibiotik_kode ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($g['nama']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted fw-bold">Nama Obat</label>
                                <input type="text" class="form-control form-control-sm" id="nama_brng" placeholder="Cari nama obat...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted fw-bold">Kandungan Obat</label>
                                <input type="text" class="form-control form-control-sm" id="letak_barang" placeholder="Cari kandungan...">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-primary btn-sm w-100" onclick="loadData()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="info-box shadow-sm">
                <strong><i class="fas fa-info-circle me-1"></i> Petunjuk Laporan PPRA:</strong><br>
                <ul class="mb-0 ps-3">
                    <li>Secara default, data difilter untuk golongan <b>Antibiotik</b> (terdeteksi otomatis dari database) sesuai sasaran program PPRA.</li>
                    <li>Anda dapat mengubah filter golongan untuk melihat semua jenis obat atau mencari berdasarkan nama obat dan kandungan obat (letak barang).</li>
                    <li>DPJP diprioritaskan dari dokter penanggung jawab pelayanan rawat inap (DPJP Ranap). Jika kosong, menggunakan dokter registrasi pelayanan.</li>
                    <li>Length Of Stay (LOS) dihitung dari akumulasi hari menginap pasien di ruang perawatan.</li>
                    <li>Tombol <b><i class="fas fa-file-alt text-primary"></i> Resume</b> hanya akan muncul apabila dokter telah mengisi resume medis pasien di sistem.</li>
                </ul>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover table-sm w-100" id="dataTable">
                            <thead class="text-center bg-light">
                                <tr>
                                    <th width="3%">No</th>
                                    <th>Bulan</th>
                                    <th>No. Rawat</th>
                                    <th>Nama Pasien</th>
                                    <th>Usia</th>
                                    <th>No. RM</th>
                                    <th>DPJP</th>
                                    <th>Obat Antibiotik / Alkes</th>
                                    <th>Tgl Pemberian</th>
                                    <th>Diagnosa</th>
                                    <th>LOS</th>
                                    <th>Ruangan</th>
                                    <th width="5%">Aksi</th>
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

<!-- Modal Resume Medis -->
<div class="modal fade" id="modalResume" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-file-medical me-2"></i>Resume Medis Pasien</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="resumeBody">
                <!-- Resume details loaded dynamically here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
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
    document.getElementById("menu-toggle").onclick = function () { 
        document.body.classList.toggle("sb-sidenav-toggled"); 
    };
    function toggleMenu() { 
        document.body.classList.remove("sb-sidenav-toggled"); 
    }

    var myTable;

    $(document).ready(function() {
        myTable = $('#dataTable').DataTable({
            dom: 'Bfrtip',
            buttons: [ 
                { 
                    extend: 'excelHtml5', 
                    className: 'btn btn-success btn-sm mb-2', 
                    text: '<i class="fas fa-file-excel me-1"></i> Excel',
                    title: 'Laporan PPRA Pemberian Antibiotik',
                    exportOptions: { columns: ':not(:last-child)' }
                },
                { 
                    extend: 'print', 
                    className: 'btn btn-secondary btn-sm mb-2', 
                    text: '<i class="fas fa-print me-1"></i> Print',
                    exportOptions: { columns: ':not(:last-child)' }
                } 
            ],
            columns: [
                { data: null, render: (d,t,r,m) => m.row + 1, className: "text-center" },
                { data: "bulan", className: "text-center" },
                { data: "no_rawat", className: "fw-bold small" },
                { data: "nm_pasien" },
                { data: "usia", className: "text-center" },
                { data: "no_rkm_medis", className: "text-center" },
                { data: "dpjp" },
                { 
                    data: "nama_brng",
                    render: function(d, t, r) {
                        if (r.letak_barang && r.letak_barang.trim() !== '' && r.letak_barang.trim() !== '-') {
                            return d + '<br><span class="text-muted small">(' + r.letak_barang + ')</span>';
                        }
                        return d;
                    }
                },
                { 
                    data: "tgl_perawatan", 
                    className: "text-center",
                    render: (d, t, r) => d + ' ' + r.jam 
                },
                { data: "diagnosa" },
                { 
                    data: "los", 
                    className: "text-center fw-bold",
                    render: (d) => parseFloat(d) + ' hari'
                },
                { data: "ruangan" },
                {
                    data: "no_rawat",
                    className: "text-center",
                    render: function(d, t, r) {
                        if (r.ada_resume == 1) {
                            return `<button class="btn btn-xs btn-outline-primary shadow-sm" onclick="openResume('${d}')" title="Lihat Resume Medis"><i class="fas fa-file-alt"></i> Resume</button>`;
                        }
                        return `<span class="text-muted small">-</span>`;
                    }
                }
            ],
            pageLength: 50,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' }
        });

        var defaultGolongan = $('#kode_golongan').val();
        if (defaultGolongan !== '') {
            loadData();
        } else {
            myTable.clear().draw();
            $('#dataTable tbody').html('<tr><td colspan="13" class="text-center text-muted py-5"><i class="fas fa-exclamation-triangle text-warning fa-2x mb-3"></i><br><strong>Golongan Antibiotik tidak terdeteksi secara otomatis di database RS ini.</strong><br><span class="small">Silakan pilih Golongan Obat secara manual pada filter di atas, lalu klik tombol cari (ikon <i class="fas fa-search"></i>) untuk memuat data.</span></td></tr>');
        }
    });

    function loadData() {
        $.ajax({
            url: 'api/data_ppra.php',
            type: 'GET',
            data: { 
                tgl_awal: $('#tgl_awal').val(), 
                tgl_akhir: $('#tgl_akhir').val(), 
                kode_golongan: $('#kode_golongan').val(),
                nama_brng: $('#nama_brng').val(),
                letak_barang: $('#letak_barang').val()
            },
            success: function(resp) { 
                myTable.clear().rows.add(resp.data).draw(); 
            },
            error: function() { 
                alert("Gagal memuat data PPRA."); 
            }
        });
    }

    function openResume(no_rawat) {
        $('#resumeBody').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Memuat data resume...</p></div>');
        $('#modalResume').modal('show');
        
        $.ajax({
            url: 'api/data_resume.php',
            type: 'GET',
            data: { no_rawat: no_rawat },
            success: function(resp) {
                if (resp.data) {
                    var r = resp.data;
                    var html = `
                        <div class="row g-3">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless small mb-0">
                                    <tr><td width="30%" class="fw-bold">No. Rawat</td><td>: ${no_rawat}</td></tr>
                                    <tr><td class="fw-bold">Tipe Pelayanan</td><td>: <span class="badge ${r.tipe=='Ranap'?'bg-primary':'bg-success'}">${r.tipe}</span></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless small mb-0">
                                    <tr><td width="30%" class="fw-bold">Dokter Resume</td><td>: ${r.nm_dokter || '-'}</td></tr>
                                </table>
                            </div>
                            <hr class="my-2">
                            <div class="col-12">
                                <h6 class="fw-bold text-primary"><i class="fas fa-stethoscope me-1"></i> Anamnesa & Pemeriksaan</h6>
                                <div class="p-2 bg-light rounded mb-2">
                                    <strong>Keluhan Utama:</strong><br>${r.keluhan_utama || '-'}
                                </div>
                                ${r.tipe == 'Ranap' ? `
                                <div class="p-2 bg-light rounded mb-2">
                                    <strong>Pemeriksaan Fisik:</strong><br>${r.pemeriksaan_fisik || '-'}
                                </div>` : ''}
                                <div class="p-2 bg-light rounded mb-2">
                                    <strong>Jalannya Penyakit:</strong><br>${r.jalannya_penyakit || '-'}
                                </div>
                                <div class="p-2 bg-light rounded mb-2">
                                    <strong>Pemeriksaan Penunjang:</strong><br>${r.pemeriksaan_penunjang || '-'}
                                </div>
                                <div class="p-2 bg-light rounded mb-2">
                                    <strong>Hasil Laborat:</strong><br>${r.hasil_laborat || '-'}
                                </div>
                            </div>
                            <div class="col-12">
                                <h6 class="fw-bold text-primary"><i class="fas fa-notes-medical me-1"></i> Diagnosis & Prosedur</h6>
                                <div class="p-2 bg-light rounded mb-2">
                                    <strong>Diagnosa Utama:</strong><br>${r.kd_diagnosa_utama ? `${r.kd_diagnosa_utama} - ${r.diagnosa_utama}` : '-'}
                                </div>
                                <div class="p-2 bg-light rounded mb-2">
                                    <strong>Diagnosa Sekunder:</strong><br>${r.diagnosa_sekunder || '-'}
                                </div>
                                <div class="p-2 bg-light rounded mb-2">
                                    <strong>Prosedur Utama:</strong><br>${r.kd_prosedur_utama ? `${r.kd_prosedur_utama} - ${r.prosedur_utama}` : '-'}
                                </div>
                                <div class="p-2 bg-light rounded mb-2">
                                    <strong>Prosedur Sekunder:</strong><br>${r.prosedur_sekunder || '-'}
                                </div>
                            </div>
                            <div class="col-12">
                                <h6 class="fw-bold text-primary"><i class="fas fa-pills me-1"></i> Terapi & Obat</h6>
                                ${r.tipe == 'Ranap' ? `
                                <div class="p-2 bg-light rounded mb-2">
                                    <strong>Obat di RS:</strong><br>${r.obat_di_rs || '-'}
                                </div>` : ''}
                                <div class="p-2 bg-light rounded mb-2">
                                    <strong>Obat Pulang:</strong><br>${r.obat_pulang || '-'}
                                </div>
                            </div>
                        </div>
                    `;
                    $('#resumeBody').html(html);
                } else {
                    $('#resumeBody').html('<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-1"></i> Resume medis untuk nomor rawat ini belum diisi oleh dokter.</div>');
                }
            },
            error: function() {
                $('#resumeBody').html('<div class="alert alert-danger"><i class="fas fa-times-circle me-1"></i> Gagal mengambil data resume medis dari server.</div>');
            }
        });
    }
</script>
</body>
</html>
<?php mysqli_close($koneksi); ?>
