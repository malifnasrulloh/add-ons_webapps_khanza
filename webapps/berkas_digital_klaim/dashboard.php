<?php
/*
 * File: dashboard.php (V.Modular)
 */
require_once('csrf.php');
if (!isset($_SESSION['casemix_login']) || $_SESSION['casemix_login'] !== true) { header("Location: index.php"); exit; }
require_once('../conf/conf.php');
$koneksi = bukakoneksi();

// Data Instansi & User (Sama seperti sebelumnya)
$q_set = mysqli_query($koneksi, "SELECT nama_instansi, logo FROM setting LIMIT 1");
$r_set = mysqli_fetch_assoc($q_set);
$nama_instansi = $r_set['nama_instansi'];
$logo_b64 = isset($r_set['logo']) ? 'data:image/jpeg;base64,' . base64_encode($r_set['logo']) : 'logo.php';

$user_id = $_SESSION['casemix_user'];
$nama_user_login = $user_id; 
$q_pegawai = mysqli_query($koneksi, "SELECT nama FROM pegawai WHERE nik = '$user_id'");
if(mysqli_num_rows($q_pegawai) > 0){ $nama_user_login = mysqli_fetch_assoc($q_pegawai)['nama']; } 
else { $q_dok = mysqli_query($koneksi, "SELECT nm_dokter FROM dokter WHERE kd_dokter = '$user_id'"); if(mysqli_num_rows($q_dok) > 0) $nama_user_login = mysqli_fetch_assoc($q_dok)['nm_dokter']; }

$tgl_awal  = isset($_GET['tgl_awal']) ? validTeks4($_GET['tgl_awal'], 10) : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? validTeks4($_GET['tgl_akhir'], 10) : date('Y-m-d');

function renderStatus($status, $type = 'mandatory') {
    if ($status == 1) return '<span class="badge bg-success" title="Ada"><i class="fas fa-check"></i></span><span style="display:none;">1</span>';
    return ($type == 'mandatory') ? '<span class="badge bg-danger" title="Kosong!"><i class="fas fa-times"></i></span><span style="display:none;">0</span>' : '<span class="text-muted fw-bold">-</span><span style="display:none;">2</span>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= $nama_instansi ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { overflow-x: hidden; background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; font-size: 0.9rem; }
        /* CSS Sidebar & Overlay (Sama seperti sebelumnya) */
        #sidebar-wrapper { min-height: 100vh; width: 250px; margin-left: -250px; position: fixed; top: 0; left: 0; bottom: 0; z-index: 1000; transition: margin .25s ease-out; background: linear-gradient(180deg, #1e3c72 0%, #2a5298 100%); color: #fff; box-shadow: 4px 0 15px rgba(0,0,0,0.1); }
        #sidebar-wrapper .sidebar-heading { padding: 1.2rem 1rem; font-size: 1.1rem; border-bottom: 1px solid rgba(255,255,255,0.15); }
        #sidebar-wrapper .list-group { width: 250px; }
        #sidebar-wrapper .list-group-item { background: transparent; color: rgba(255,255,255,0.85); border: none; padding: 12px 20px; }
        #sidebar-wrapper .list-group-item:hover { background: rgba(255,255,255,0.15); color: #fff; border-left: 4px solid #fff; }
        #sidebar-wrapper .list-group-item.active { background: rgba(255,255,255,0.2); color: #fff; font-weight: bold; border-left: 4px solid #4cd137; }
        #page-content-wrapper { width: 100%; transition: margin .25s ease-out; }
        body.sb-sidenav-toggled #sidebar-wrapper { margin-left: 0; }
        @media (min-width: 768px) {
            #sidebar-wrapper { margin-left: 0; }
            #page-content-wrapper { margin-left: 250px; }
            body.sb-sidenav-toggled #sidebar-wrapper { margin-left: -250px; }
            body.sb-sidenav-toggled #page-content-wrapper { margin-left: 0; }
        }
        #overlay { display: none; position: fixed; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 900; }
        body.sb-sidenav-toggled #overlay { display: block; }
        @media (min-width: 768px) { body.sb-sidenav-toggled #overlay { display: none; } }
        .top-navbar { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); padding: 10px 20px; }
        .table th { white-space: nowrap; text-align: center; vertical-align: middle; background-color: #f8f9fa; font-size: 0.85rem; }
        .table td { vertical-align: middle; font-size: 0.85rem; }
        .bg-pink { background-color: #d63384 !important; color: white; }
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
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_user_login) ?>&background=random" class="rounded-circle border" width="35">
            </div>
        </nav>

        <div class="container-fluid px-4 py-4">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body py-3">
                    <form action="" method="GET" class="row g-2 align-items-end">
                        <div class="col-md-3 col-6">
                            <label class="form-label fw-bold small text-secondary">Tgl Awal</label>
                            <input type="date" name="tgl_awal" class="form-control form-control-sm" value="<?= $tgl_awal ?>">
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label fw-bold small text-secondary">Tgl Akhir</label>
                            <input type="date" name="tgl_akhir" class="form-control form-control-sm" value="<?= $tgl_akhir ?>">
                        </div>
                        <div class="col-md-2 col-6">
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold"><i class="fas fa-search me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2 col-6">
                            <button type="button" onclick="siapkanBulk()" class="btn btn-success btn-sm w-100 fw-bold text-white">
                                <i class="fas fa-download me-1"></i> ZIP
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="tablePasien" class="table table-striped table-hover mb-0 w-100">
                            <thead class="bg-light">
                                <tr>
                                    <th rowspan="2">Rawat/Closing</th>
                                    <th rowspan="2">Pasien & Dokter</th>
                                    <th rowspan="2">Status</th>
                                    <th colspan="8" class="text-center bg-primary text-white py-1">Kelengkapan</th>
                                    <th rowspan="2">Aksi</th>
                                </tr>
                                <tr>
                                    <th class="py-1">Res</th><th class="py-1">Bill</th><th class="py-1">CPPT</th><th class="py-1">Asm</th>
                                    <th class="py-1">Tri</th><th class="py-1">Op</th><th class="py-1">Lab</th><th class="py-1">Rad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT rp.no_rawat, rp.tgl_registrasi, rp.jam_reg, rp.status_lanjut, rp.kd_poli,
                                            p.no_rkm_medis, p.nm_pasien, d.nm_dokter, 
                                            COALESCE(bs.no_sep, '-') as no_sep, COALESCE(ni.tanggal, nj.tanggal) as tgl_closing,
                                            (EXISTS(SELECT 1 FROM resume_pasien WHERE no_rawat = rp.no_rawat) OR EXISTS(SELECT 1 FROM resume_pasien_ranap WHERE no_rawat = rp.no_rawat)) as ada_resume,
                                            (EXISTS(SELECT 1 FROM billing WHERE no_rawat = rp.no_rawat LIMIT 1)) as ada_billing,
                                            (EXISTS(SELECT 1 FROM pemeriksaan_ralan WHERE no_rawat = rp.no_rawat) OR EXISTS(SELECT 1 FROM pemeriksaan_ranap WHERE no_rawat = rp.no_rawat)) as ada_cppt,
                                            (EXISTS(SELECT 1 FROM penilaian_medis_igd WHERE no_rawat = rp.no_rawat) OR EXISTS(SELECT 1 FROM penilaian_medis_ralan WHERE no_rawat = rp.no_rawat) OR EXISTS(SELECT 1 FROM penilaian_medis_ranap WHERE no_rawat = rp.no_rawat)) as ada_asmed,
                                            (EXISTS(SELECT 1 FROM data_triase_igd WHERE no_rawat = rp.no_rawat)) as ada_triase,
                                            (EXISTS(SELECT 1 FROM operasi WHERE no_rawat = rp.no_rawat)) as ada_op,
                                            (EXISTS(SELECT 1 FROM periksa_lab WHERE no_rawat = rp.no_rawat)) as ada_lab,
                                            (EXISTS(SELECT 1 FROM periksa_radiologi WHERE no_rawat = rp.no_rawat)) as ada_rad
                                        FROM reg_periksa rp
                                        JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                                        JOIN dokter d ON rp.kd_dokter = d.kd_dokter
                                        LEFT JOIN nota_jalan nj ON rp.no_rawat = nj.no_rawat
                                        LEFT JOIN nota_inap ni ON rp.no_rawat = ni.no_rawat
                                        LEFT JOIN bridging_sep bs ON rp.no_rawat = bs.no_rawat
                                        WHERE COALESCE(ni.tanggal, nj.tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
                                        GROUP BY rp.no_rawat ORDER BY tgl_closing DESC, rp.jam_reg DESC";
                                
                                $hasil = mysqli_query($koneksi, $query);
                                if($hasil){ while ($row = mysqli_fetch_assoc($hasil)) { $is_igd = ($row['kd_poli'] == 'IGDK'); ?>
                                    <tr>
                                        <td><span class="fw-bold d-block"><?= htmlspecialchars($row['no_rawat'], ENT_QUOTES, 'UTF-8') ?></span><small class="text-muted"><?= htmlspecialchars($row['tgl_closing'], ENT_QUOTES, 'UTF-8') ?></small></td>
                                        <td>
                                            <div class="fw-bold text-truncate" style="max-width:150px;"><?= htmlspecialchars($row['nm_pasien'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <small class="d-block text-secondary" style="font-size:0.75rem"><?= htmlspecialchars($row['no_rkm_medis'], ENT_QUOTES, 'UTF-8') ?></small>
                                            <small class="text-primary text-truncate d-block" style="max-width:150px;"><?= htmlspecialchars($row['nm_dokter'], ENT_QUOTES, 'UTF-8') ?></small>
                                        </td>
                                        <td><span class="badge bg-light text-dark border d-block mb-1"><?= htmlspecialchars($row['no_sep'], ENT_QUOTES, 'UTF-8') ?></span><span class="badge <?= ($row['status_lanjut']=='Ralan')?'bg-success':'bg-pink' ?>"><?= htmlspecialchars($row['status_lanjut'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td class="text-center"><?= renderStatus($row['ada_resume']) ?></td>
                                        <td class="text-center"><?= renderStatus($row['ada_billing']) ?></td>
                                        <td class="text-center"><?= renderStatus($row['ada_cppt']) ?></td>
                                        <td class="text-center"><?= renderStatus($row['ada_asmed']) ?></td>
                                        <td class="text-center"><?= $is_igd ? renderStatus($row['ada_triase']) : '<span class="text-muted">-</span>' ?></td>
                                        <td class="text-center"><?= renderStatus($row['ada_op'], 'opt') ?></td>
                                        <td class="text-center"><?= renderStatus($row['ada_lab'], 'opt') ?></td>
                                        <td class="text-center"><?= renderStatus($row['ada_rad'], 'opt') ?></td>
                                        <td class="text-center"><a href="lihat_berkas.php?no_rawat=<?= urlencode($row['no_rawat']) ?>" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fas fa-folder-open"></i></a></td>
                                    </tr>
                                <?php }} ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBulk" data-bs-backdrop="static"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Proses ZIP</h5></div><div class="modal-body text-center"><div class="mb-3"><i class="fas fa-cog fa-spin fa-3x text-primary"></i></div><h5 id="bulkStatus">Menyiapkan...</h5><p id="bulkDetail" class="text-muted small">Mohon tunggu.</p><div class="progress mt-3" style="height: 20px;"><div id="bulkProgress" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%">0%</div></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" disabled id="btnCloseBulk" data-bs-dismiss="modal">Tutup</button></div></div></div></div>
<form id="formZip" action="download_zip.php" method="POST" target="_blank" style="display:none;"><input type="hidden" name="files" id="inputFilesZip"></form>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>

<script>
    const toggleBtn = document.getElementById("menu-toggle");
    const overlay = document.getElementById("overlay");
    function toggleMenu() { document.body.classList.toggle("sb-sidenav-toggled"); }
    if(toggleBtn) toggleBtn.onclick = toggleMenu;

    $(document).ready(function() {
        $('#tablePasien').DataTable({
            dom: 'Bfrtip', pageLength: 15,
            // UPDATE: ordering: false DIHAPUS agar fitur sorting aktif kembali
            buttons: [ { extend: 'excel', className: 'btn btn-success btn-sm', text: '<i class=\"fas fa-file-excel me-1\"></i> Excel' } ],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' }
        });
    });
    // JS Bulk sama seperti sebelumnya
    let generatedFiles = [];
    async function siapkanBulk() {
        const tglAwal = $('input[name="tgl_awal"]').val();
        const tglAkhir = $('input[name="tgl_akhir"]').val();
        generatedFiles = [];
        $('#bulkProgress').css('width', '0%').text('0%');
        $('#btnCloseBulk').prop('disabled', true);
        const modal = new bootstrap.Modal(document.getElementById('modalBulk'));
        modal.show();
        $('#bulkStatus').text('Mengambil data...');
        try {
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            const response = await $.post('ajax_get_targets.php', { tgl_awal: tglAwal, tgl_akhir: tglAkhir, csrf_token: csrfToken });
            if(response.status === 'success') {
                const listPasien = response.data;
                const total = listPasien.length;
                if(total === 0) { alert('Tidak ada data.'); modal.hide(); return; }
                for (let i = 0; i < total; i++) {
                    const pasien = listPasien[i];
                    const percent = Math.round(((i + 1) / total) * 100);
                    $('#bulkStatus').text(`Memproses ${i+1} / ${total}`);
                    $('#bulkDetail').text(pasien.nm_pasien);
                    $('#bulkProgress').css('width', percent + '%').text(percent + '%');
                    const resMerge = await $.post('ajax_process_item.php', { no_rawat: pasien.no_rawat, nm_pasien: pasien.nm_pasien, csrf_token: csrfToken });
                    if(resMerge.status === 'success') generatedFiles.push(resMerge.file);
                }
                $('#bulkStatus').text('Finalisasi ZIP...');
                if(generatedFiles.length > 0) {
                    $('#inputFilesZip').val(JSON.stringify(generatedFiles));
                    // Insert CSRF dynamically into the form
                    $('#formZip').append('<input type="hidden" name="csrf_token" value="'+csrfToken+'">');
                    $('#formZip').submit(); 
                    setTimeout(() => { $('#bulkStatus').text('Selesai!'); $('#btnCloseBulk').prop('disabled', false); }, 1500);
                } else {
                     $('#bulkStatus').text('Gagal!'); $('#bulkDetail').text('File kosong.'); $('#btnCloseBulk').prop('disabled', false);
                }
            } else { alert(response.message); modal.hide(); }
        } catch (error) { console.error(error); alert('Error koneksi.'); modal.hide(); }
    }
</script>
</body>
</html>
<?php mysqli_close($koneksi); ?>