<?php
/*
 * File: /webapps/berkas_digital_perawatan/dashboard.php
 * Fungsi: Dashboard Monitoring Casemix (V7 - Indikator Kelengkapan Data)
 * Update: Fix Prioritas Nama Dokter (DPJP Ranap > Dokter Registrasi)
 */
session_start();

// 1. CEK OTENTIKASI
if (!isset($_SESSION['casemix_login']) || $_SESSION['casemix_login'] !== true) {
    header("Location: index.php");
    exit;
}

require_once('../conf/conf.php');
$koneksi = bukakoneksi();

// 2. AMBIL INFO INSTANSI
$nama_instansi = "RS Khanza";
$q_set = mysqli_query($koneksi, "SELECT nama_instansi FROM setting LIMIT 1");
if($r_set = mysqli_fetch_assoc($q_set)) $nama_instansi = $r_set['nama_instansi'];

// 3. AMBIL NAMA USER
$user_id = $_SESSION['casemix_user'];
$nama_user_login = $user_id; 

$q_pegawai = mysqli_query($koneksi, "SELECT nama FROM pegawai WHERE nik = '$user_id'");
if(mysqli_num_rows($q_pegawai) > 0){
    $r_peg = mysqli_fetch_assoc($q_pegawai);
    $nama_user_login = $r_peg['nama'];
} else {
    $q_dok = mysqli_query($koneksi, "SELECT nm_dokter FROM dokter WHERE kd_dokter = '$user_id'");
    if(mysqli_num_rows($q_dok) > 0){
        $r_dok = mysqli_fetch_assoc($q_dok);
        $nama_user_login = $r_dok['nm_dokter'];
    }
}

// 4. FILTER TANGGAL (DEFAULT HARI INI)
$tgl_awal  = isset($_GET['tgl_awal']) ? validTeks4($_GET['tgl_awal'], 10) : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? validTeks4($_GET['tgl_akhir'], 10) : date('Y-m-d');

// Helper untuk Badge Status
function renderStatus($status, $type = 'mandatory') {
    // Type: mandatory (Wajib ada -> Merah/Hijau), optional (Ada/Tidak -> Hijau/Abu)
    if ($status == 1) {
        // Ada Data -> Hijau Centang
        return '<span class="badge bg-success" data-bs-toggle="tooltip" title="Data Tersedia"><i class="fas fa-check"></i></span><span style="display:none;">1</span>';
    } else {
        if ($type == 'mandatory') {
            // Wajib tapi Kosong -> Merah Silang
            return '<span class="badge bg-danger" data-bs-toggle="tooltip" title="Data Belum Diinput!"><i class="fas fa-times"></i></span><span style="display:none;">0</span>';
        } else {
            // Optional dan Kosong -> Strip Abu
            return '<span class="text-muted fw-bold">-</span><span style="display:none;">2</span>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Casemix - <?= $nama_instansi ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; font-size: 0.85rem; }
        .navbar { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); }
        .navbar-brand img { height: 35px; border-radius: 4px; background: #fff; padding: 2px; }
        .bg-pink { background-color: #d63384 !important; color: white; }
        .table th { white-space: nowrap; text-align: center; vertical-align: middle; font-size: 0.8rem; }
        .table td { vertical-align: middle; }
        .status-col { width: 5%; text-align: center; }
        /* Mempercantik Tooltip */
        .tooltip-inner { max-width: 200px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="#">
            <img src="logo.php" alt="Logo"> <?= $nama_instansi ?>
        </a>
        <div class="d-flex text-white align-items-center">
            <span class="me-3 fw-bold"><i class="fas fa-user-circle me-2"></i><?= $nama_user_login ?></span>
            <a href="logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body py-3">
            <form action="" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Tgl Closing Awal</label>
                    <input type="date" name="tgl_awal" class="form-control" value="<?= $tgl_awal ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Tgl Closing Akhir</label>
                    <input type="date" name="tgl_akhir" class="form-control" value="<?= $tgl_akhir ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Tampilkan</button>
                </div>
                <div class="col-md-2">
                    <button type="button" onclick="siapkanBulk()" class="btn btn-success w-100 text-white">
                        <i class="fas fa-file-archive me-1"></i> Download ZIP
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-clipboard-check me-2"></i>Monitoring Kelengkapan Berkas</h5>
            <small class="text-muted"><i class="fas fa-info-circle"></i> Klik judul kolom (misal: Resume) untuk menyortir data yang kosong.</small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablePasien" class="table table-hover table-bordered w-100">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2">No. Rawat<br>Tgl Closing</th>
                            <th rowspan="2">Pasien & Dokter</th>
                            <th rowspan="2">SEP & Status</th>
                            <th colspan="8" class="bg-primary text-white">Status Kelengkapan Data (Database)</th>
                            <th rowspan="2" width="5%">Aksi</th>
                        </tr>
                        <tr>
                            <th class="status-col" title="Resume Medis (Wajib)">Resume</th>
                            <th class="status-col" title="Billing (Wajib)">Billing</th>
                            <th class="status-col" title="CPPT (Wajib)">CPPT</th>
                            <th class="status-col" title="Asesmen Awal Medis (Wajib)">Asmed</th>
                            
                            <th class="status-col" title="Triase IGD (Wajib jika IGD)">Triase</th>
                            <th class="status-col" title="Laporan Operasi (Jika ada tindakan)">Op</th>
                            <th class="status-col" title="Laboratorium (Jika ada)">Lab</th>
                            <th class="status-col" title="Radiologi (Jika ada)">Rad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // QUERY SUPER POWERFUL
                        // Menggabungkan pengecekan data langsung di SQL agar performa tetap cepat
                        $query = "SELECT 
                                    rp.no_rawat, rp.tgl_registrasi, rp.jam_reg, rp.status_lanjut, rp.kd_poli,
                                    p.no_rkm_medis, p.nm_pasien, 
                                    
                                    -- PERBAIKAN: Prioritas Ambil DPJP Ranap, jika tidak ada baru ambil Dokter Registrasi
                                    COALESCE(dd.nm_dokter, d.nm_dokter) as nm_dokter,
                                    
                                    COALESCE(bs.no_sep, '-') as no_sep,
                                    COALESCE(ni.tanggal, nj.tanggal) as tgl_closing,
                                    
                                    -- CEK KELENGKAPAN DATA (1 = Ada, 0 = Kosong)
                                    
                                    -- 1. RESUME (Wajib)
                                    (EXISTS(SELECT 1 FROM resume_pasien WHERE no_rawat = rp.no_rawat) 
                                     OR EXISTS(SELECT 1 FROM resume_pasien_ranap WHERE no_rawat = rp.no_rawat)) as ada_resume,
                                     
                                    -- 2. BILLING (Wajib ada detail billing)
                                    (EXISTS(SELECT 1 FROM billing WHERE no_rawat = rp.no_rawat LIMIT 1)) as ada_billing,
                                    
                                    -- 3. CPPT (Wajib)
                                    (EXISTS(SELECT 1 FROM pemeriksaan_ralan WHERE no_rawat = rp.no_rawat)
                                     OR EXISTS(SELECT 1 FROM pemeriksaan_ranap WHERE no_rawat = rp.no_rawat)) as ada_cppt,
                                     
                                    -- 4. ASESMEN AWAL (Wajib) - Cek IGD/Ralan/Ranap
                                    (EXISTS(SELECT 1 FROM penilaian_medis_igd WHERE no_rawat = rp.no_rawat)
                                     OR EXISTS(SELECT 1 FROM penilaian_medis_ralan WHERE no_rawat = rp.no_rawat)
                                     OR EXISTS(SELECT 1 FROM penilaian_medis_ralan_kandungan WHERE no_rawat = rp.no_rawat)
                                     -- Tambahkan tabel spesialis lain jika perlu (agar query tidak terlalu panjang, ini sample utama)
                                     OR EXISTS(SELECT 1 FROM penilaian_medis_ranap WHERE no_rawat = rp.no_rawat)
                                     OR EXISTS(SELECT 1 FROM penilaian_medis_ranap_kandungan WHERE no_rawat = rp.no_rawat)
                                    ) as ada_asmed,
                                    
                                    -- 5. TRIASE (Wajib Khusus IGDK)
                                    (EXISTS(SELECT 1 FROM data_triase_igd WHERE no_rawat = rp.no_rawat)) as ada_triase,
                                    
                                    -- 6. OPERASI (Opsional)
                                    (EXISTS(SELECT 1 FROM operasi WHERE no_rawat = rp.no_rawat)) as ada_op,
                                    
                                    -- 7. LAB (Opsional)
                                    (EXISTS(SELECT 1 FROM periksa_lab WHERE no_rawat = rp.no_rawat)) as ada_lab,
                                    
                                    -- 8. RAD (Opsional)
                                    (EXISTS(SELECT 1 FROM periksa_radiologi WHERE no_rawat = rp.no_rawat)) as ada_rad

                                FROM reg_periksa rp
                                JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                                JOIN dokter d ON rp.kd_dokter = d.kd_dokter -- Dokter Registrasi (Default)
                                
                                -- PERBAIKAN: Join ke DPJP Ranap
                                LEFT JOIN dpjp_ranap dr ON rp.no_rawat = dr.no_rawat
                                LEFT JOIN dokter dd ON dr.kd_dokter = dd.kd_dokter
                                
                                LEFT JOIN nota_jalan nj ON rp.no_rawat = nj.no_rawat
                                LEFT JOIN nota_inap ni ON rp.no_rawat = ni.no_rawat
                                LEFT JOIN bridging_sep bs ON rp.no_rawat = bs.no_rawat
                                
                                WHERE COALESCE(ni.tanggal, nj.tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
                                GROUP BY rp.no_rawat
                                ORDER BY tgl_closing DESC, rp.jam_reg DESC";
                        
                        $hasil = mysqli_query($koneksi, $query);
                        
                        if(!$hasil) {
                            echo "<tr><td colspan='13' class='text-center text-danger'>Error SQL: ".mysqli_error($koneksi)."</td></tr>";
                        } else {
                            while ($row = mysqli_fetch_assoc($hasil)) {
                                $is_igd = ($row['kd_poli'] == 'IGDK');
                        ?>
                            <tr>
                                <td>
                                    <span class="fw-bold"><?= $row['no_rawat'] ?></span><br>
                                    <small class="text-muted"><?= $row['tgl_closing'] ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= $row['nm_pasien'] ?></div>
                                    <small class="text-primary"><i class="fas fa-user-md"></i> <?= $row['nm_dokter'] ?></small>
                                </td>
                                <td>
                                    <?php if($row['no_sep'] !== '-'): ?>
                                        <span class="badge bg-primary mb-1"><?= $row['no_sep'] ?></span><br>
                                    <?php endif; ?>
                                    <span class="badge <?= ($row['status_lanjut']=='Ralan')?'bg-success':'bg-pink' ?>">
                                        <?= $row['status_lanjut'] ?>
                                    </span>
                                </td>

                                <td class="text-center"><?= renderStatus($row['ada_resume'], 'mandatory') ?></td>
                                
                                <td class="text-center"><?= renderStatus($row['ada_billing'], 'mandatory') ?></td>
                                
                                <td class="text-center"><?= renderStatus($row['ada_cppt'], 'mandatory') ?></td>
                                
                                <td class="text-center"><?= renderStatus($row['ada_asmed'], 'mandatory') ?></td>
                                
                                <td class="text-center">
                                    <?php 
                                    if ($is_igd) {
                                        echo renderStatus($row['ada_triase'], 'mandatory');
                                    } else {
                                        echo '<span class="text-muted">-</span><span style="display:none;">2</span>';
                                    }
                                    ?>
                                </td>
                                
                                <td class="text-center"><?= renderStatus($row['ada_op'], 'optional') ?></td>
                                
                                <td class="text-center"><?= renderStatus($row['ada_lab'], 'optional') ?></td>
                                
                                <td class="text-center"><?= renderStatus($row['ada_rad'], 'optional') ?></td>

                                <td class="text-center">
                                    <a href="lihat_berkas.php?no_rawat=<?= urlencode($row['no_rawat']) ?>" target="_blank" class="btn btn-sm btn-outline-danger shadow-sm" title="Kelola Berkas Digital">
                                        <i class="fas fa-folder-open"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php 
                            } 
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBulk" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Memproses Berkas Masal</h5>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3"><i class="fas fa-cog fa-spin fa-3x text-primary"></i></div>
                <h5 id="bulkStatus">Menyiapkan data...</h5>
                <p id="bulkDetail" class="text-muted small">Mohon jangan tutup halaman ini.</p>
                <div class="progress mt-3" style="height: 25px;">
                    <div id="bulkProgress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" disabled id="btnCloseBulk" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<form id="formZip" action="download_zip.php" method="POST" target="_blank" style="display:none;">
    <input type="hidden" name="files" id="inputFilesZip">
</form>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>

<script>
    $(document).ready(function() {
        // Inisialisasi Tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        // DataTable dengan Sorting Canggih
        $('#tablePasien').DataTable({
            dom: 'Bfrtip',
            pageLength: 15,
            order: [], // Biarkan urutan default SQL
            buttons: [
                { extend: 'excel', className: 'btn btn-success btn-sm', text: '<i class="fas fa-file-excel me-2"></i>Export Excel' }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' }
        });
    });

    // Script Bulk Download (Sama seperti sebelumnya)
    let generatedFiles = [];
    async function siapkanBulk() {
        const tglAwal = $('input[name="tgl_awal"]').val();
        const tglAkhir = $('input[name="tgl_akhir"]').val();
        generatedFiles = [];
        $('#bulkProgress').css('width', '0%').text('0%');
        $('#btnCloseBulk').prop('disabled', true);
        const modal = new bootstrap.Modal(document.getElementById('modalBulk'));
        modal.show();
        $('#bulkStatus').text('Mengambil daftar pasien...');
        try {
            const response = await $.post('ajax_get_targets.php', { tgl_awal: tglAwal, tgl_akhir: tglAkhir });
            if(response.status === 'success') {
                const listPasien = response.data;
                const total = listPasien.length;
                if(total === 0) { alert('Tidak ada pasien dengan berkas digital.'); modal.hide(); return; }
                for (let i = 0; i < total; i++) {
                    const pasien = listPasien[i];
                    const percent = Math.round(((i + 1) / total) * 100);
                    $('#bulkStatus').text(`Memproses ${i+1} dari ${total}`);
                    $('#bulkDetail').text(`${pasien.nm_pasien} (${pasien.no_rawat})`);
                    $('#bulkProgress').css('width', percent + '%').text(percent + '%');
                    const resMerge = await $.post('ajax_process_item.php', { no_rawat: pasien.no_rawat, nm_pasien: pasien.nm_pasien });
                    if(resMerge.status === 'success') { generatedFiles.push(resMerge.file); }
                }
                $('#bulkStatus').text('Mengompresi File ZIP...');
                if(generatedFiles.length > 0) {
                    $('#inputFilesZip').val(JSON.stringify(generatedFiles));
                    $('#formZip').submit(); 
                    setTimeout(() => { $('#bulkStatus').text('Selesai!'); $('#btnCloseBulk').prop('disabled', false); }, 2000);
                } else {
                     $('#bulkStatus').text('Gagal!'); $('#bulkDetail').text('Tidak ada file berhasil.'); $('#btnCloseBulk').prop('disabled', false);
                }
            } else { alert('Gagal data: ' + response.message); modal.hide(); }
        } catch (error) { console.error(error); alert('Error koneksi.'); modal.hide(); }
    }
</script>

</body>
</html>
<?php mysqli_close($koneksi); ?>