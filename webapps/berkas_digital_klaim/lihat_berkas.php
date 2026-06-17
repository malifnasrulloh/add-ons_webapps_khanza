<?php
/*
 * File: lihat_berkas.php (V.Final - Fixed Merge Logic)
 * Perbaikan: Checkbox value kini menggunakan 'lokasi_file' agar unik
 */
require_once('csrf.php');
if (!isset($_SESSION['casemix_login'])) { header("Location: index.php"); exit; }

require_once('../conf/conf.php');
$koneksi = bukakoneksi();

$no_rawat = isset($_GET['no_rawat']) ? validTeks4($_GET['no_rawat'], 20) : '';
$base_url_berkas = "../berkasrawat/"; 

// =================================================================================
// 1. LOGIC PHP: HANDLE UPLOAD & DELETE
// =================================================================================

if (isset($_POST['btn_upload_manual'])) {
    $kode_berkas = $_POST['kode_berkas'];
    $file_tmp = $_FILES['file_berkas']['tmp_name'];
    $file_name = $_FILES['file_berkas']['name'];
    
    if (!empty($kode_berkas) && !empty($file_tmp)) {
        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $clean_rawat = str_replace(['/','\\'], '', $no_rawat);
        $new_name = $kode_berkas . "_" . $clean_rawat . "_" . date('YmdHis') . "." . $ext;
        
        $target_dir = dirname(__DIR__) . "/berkasrawat/pages/upload/";
        $target_file = $target_dir . $new_name;
        $db_path = "pages/upload/" . $new_name;

        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

        if (move_uploaded_file($file_tmp, $target_file)) {
            $q_upload = "INSERT INTO berkas_digital_perawatan (no_rawat, kode, lokasi_file) VALUES ('$no_rawat', '$kode_berkas', '$db_path')";
            if (mysqli_query($koneksi, $q_upload)) {
                echo "<script>alert('Berkas berhasil diupload!'); window.location.href='lihat_berkas.php?no_rawat=$no_rawat';</script>";
            } else {
                echo "<script>alert('Gagal simpan ke database.');</script>";
            }
        } else {
            echo "<script>alert('Gagal upload file fisik.');</script>";
        }
    }
}

if (isset($_GET['act']) && $_GET['act'] == 'hapus_berkas' && isset($_GET['file'])) {
    $lokasi_db = $_GET['file'];
    $q_del = "DELETE FROM berkas_digital_perawatan WHERE no_rawat='$no_rawat' AND lokasi_file='$lokasi_db'";
    if(mysqli_query($koneksi, $q_del)){
        $path_fisik = dirname(__DIR__) . "/berkasrawat/" . $lokasi_db;
        if(file_exists($path_fisik)){ unlink($path_fisik); }
        echo "<script>alert('Berkas berhasil dihapus!'); window.location.href='lihat_berkas.php?no_rawat=$no_rawat';</script>";
    }
}

// =================================================================================
// 2. QUERY DATA PASIEN
// =================================================================================

$query_pasien = "SELECT 
    rp.no_rawat, rp.tgl_registrasi, rp.jam_reg, rp.status_lanjut, 
    p.no_rkm_medis, p.nm_pasien, p.tgl_lahir, p.jk, p.alamat, p.no_peserta,
    kl.nm_kel, kc.nm_kec, kb.nm_kab, pr.nm_prop,
    d.nm_dokter, poli.nm_poli, pj.png_jawab,
    COALESCE(bs.no_sep, '-') as no_sep, 
    COALESCE(pen.nm_penyakit, '-') as diagnosa_utama, 
    COALESCE(pen.kd_penyakit, '-') as kd_diagnosa
FROM reg_periksa rp
JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
LEFT JOIN kelurahan kl ON p.kd_kel = kl.kd_kel
LEFT JOIN kecamatan kc ON p.kd_kec = kc.kd_kec
LEFT JOIN kabupaten kb ON p.kd_kab = kb.kd_kab
LEFT JOIN propinsi pr ON p.kd_prop = pr.kd_prop
JOIN dokter d ON rp.kd_dokter = d.kd_dokter
JOIN poliklinik poli ON rp.kd_poli = poli.kd_poli
JOIN penjab pj ON rp.kd_pj = pj.kd_pj
LEFT JOIN bridging_sep bs ON rp.no_rawat = bs.no_rawat
LEFT JOIN diagnosa_pasien dp ON rp.no_rawat = dp.no_rawat AND dp.prioritas = 1
LEFT JOIN penyakit pen ON dp.kd_penyakit = pen.kd_penyakit
WHERE rp.no_rawat = '$no_rawat' LIMIT 1";

$data_pasien = mysqli_fetch_assoc(mysqli_query($koneksi, $query_pasien));

$tgl_lahir = new DateTime($data_pasien['tgl_lahir']);
$hari_ini = new DateTime($data_pasien['tgl_registrasi']);
$umur = $hari_ini->diff($tgl_lahir);
$umur_str = $umur->y . " Th " . $umur->m . " Bl " . $umur->d . " Hr";
$jk_str = ($data_pasien['jk'] == 'L') ? 'Laki-Laki' : 'Perempuan';
$alamat_lengkap = $data_pasien['alamat'] . ", " . $data_pasien['nm_kel'] . ", " . $data_pasien['nm_kec'] . ", " . $data_pasien['nm_kab'] . ", " . $data_pasien['nm_prop'];

$q_master = mysqli_query($koneksi, "SELECT kode, nama FROM master_berkas_digital ORDER BY nama ASC");
$master_berkas = [];
while($row = mysqli_fetch_assoc($q_master)){ $master_berkas[] = $row; }

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berkas Digital - <?= $data_pasien['nm_pasien'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
        body { background-color: #f4f6f9; font-size: 0.85rem; }
        .header-pasien { background: #fff; padding: 15px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-top: 4px solid #0d6efd; }
        .data-label { font-weight: bold; width: 130px; display: inline-block; color: #555; }
        .data-value { font-weight: bold; color: #000; }
        .file-icon { width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 5px; font-size: 1.1rem; }
        .bg-pdf { background-color: #ffe5e7; color: #dc3545; }
        .bg-img { background-color: #e0f2fe; color: #0ea5e9; }
        .generator-card { border-left: 4px solid #198754; height: 100%; }
        .list-card { border-left: 4px solid #0d6efd; height: 100%; }
        .modal-preview-content { min-height: 500px; width: 100%; border: none; }
    </style>
</head>
<body>

<div class="header-pasien container-fluid">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <h5 class="fw-bold text-primary"><i class="fas fa-hospital-user me-2"></i>Berkas Digital Perawatan Pasien</h5>
        <a href="index.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard</a>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="mb-1"><span class="data-label">No.Rawat</span>: <span class="data-value"><?= $no_rawat ?></span></div>
            <div class="mb-1"><span class="data-label">No.RM</span>: <span class="data-value"><?= $data_pasien['no_rkm_medis'] ?></span></div>
            <div class="mb-1"><span class="data-label">Nama Pasien</span>: <span class="data-value"><?= $data_pasien['nm_pasien'] ?>, <?= $umur_str ?>, <?= $jk_str ?></span></div>
            <div class="mb-1"><span class="data-label">Alamat Pasien</span>: <span class="data-value"><?= $alamat_lengkap ?></span></div>
            <div class="mb-1"><span class="data-label">Tgl.Registrasi</span>: <span class="data-value"><?= $data_pasien['tgl_registrasi'] ?> <?= $data_pasien['jam_reg'] ?></span></div>
            <div class="mb-1"><span class="data-label">Poliklinik</span>: <span class="data-value"><?= $data_pasien['nm_poli'] ?></span></div>
        </div>
        <div class="col-md-6">
            <div class="mb-1"><span class="data-label">Dokter</span>: <span class="data-value"><?= $data_pasien['nm_dokter'] ?></span></div>
            <div class="mb-1"><span class="data-label">Status</span>: <span class="data-value"><?= $data_pasien['status_lanjut'] ?> (<?= $data_pasien['png_jawab'] ?>)</span></div>
            <div class="mb-1"><span class="data-label">No.SEP</span>: <span class="data-value text-primary"><?= $data_pasien['no_sep'] ?></span></div>
            <div class="mb-1"><span class="data-label">Kartu</span>: <span class="data-value"><?= $data_pasien['no_peserta'] ?></span></div>
            <div class="mb-1"><span class="data-label">Diagnosa Utama</span>: <span class="data-value text-danger"><?= $data_pasien['kd_diagnosa'] ?> - <?= $data_pasien['diagnosa_utama'] ?></span></div>
        </div>
    </div>
</div>

<div class="container-fluid mb-5">
    <?php if (!$data_pasien): ?>
        <div class="alert alert-danger">Data pasien tidak ditemukan!</div>
    <?php else: ?>
    <div class="row">
        
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm generator-card">
                <div class="card-header bg-success text-white py-2">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-magic me-2"></i>Generator Dokumen</h6>
                </div>
                <div class="card-body p-2">
                    <div class="d-grid gap-2">
                        <small class="text-muted fw-bold">E-Rekam Medis</small>
                        <a href="erm/cetak_resume.php?no_rawat=<?= urlencode($no_rawat) ?>" target="_blank" class="btn btn-outline-success btn-sm text-start"><i class="fas fa-file-medical me-2"></i> Resume Ranap</a>
                        <a href="erm/cetak_resume_ralan.php?no_rawat=<?= urlencode($no_rawat) ?>" target="_blank" class="btn btn-outline-primary btn-sm text-start"><i class="fas fa-file-prescription me-2"></i> Resume Ralan</a>
                        <a href="erm/cetak_triase_igd.php?no_rawat=<?= urlencode($no_rawat) ?>" target="_blank" class="btn btn-outline-danger btn-sm text-start"><i class="fas fa-ambulance me-2"></i> Triase IGD</a>
                        <a href="erm/cetak_asesmen_igd.php?no_rawat=<?= urlencode($no_rawat) ?>" target="_blank" class="btn btn-outline-info btn-sm text-start"><i class="fas fa-user-md me-2"></i> Asesmen IGD</a>
						<a href="erm/cetak_laporan_operasi.php?no_rawat=<?= urlencode($no_rawat) ?>" target="_blank" class="btn btn-outline-info btn-sm text-start"><i class="fas fa-scalpel me-2"></i> Laporan Operasi</a>

                        <small class="text-muted fw-bold mt-2">Penunjang</small>
                        <a href="erm/cetak_hasil_lab.php?no_rawat=<?= urlencode($no_rawat) ?>" target="_blank" class="btn btn-outline-warning text-dark btn-sm text-start"><i class="fas fa-flask me-2"></i> Hasil Lab</a>
                        <a href="erm/cetak_radiologi.php?no_rawat=<?= urlencode($no_rawat) ?>" target="_blank" class="btn btn-outline-dark btn-sm text-start"><i class="fas fa-x-ray me-2"></i> Hasil Radiologi</a>
                        <a href="erm/cetak_billing.php?no_rawat=<?= urlencode($no_rawat) ?>" target="_blank" class="btn btn-outline-secondary btn-sm text-start"><i class="fas fa-file-invoice-dollar me-2"></i> Billing / Kwitansi</a>
						
                        
                        <hr class="my-1">
                        <button type="button" class="btn btn-primary text-white fw-bold btn-sm" data-bs-toggle="modal" data-bs-target="#modalUpload">
                            <i class="fas fa-upload me-1"></i> Upload Manual
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <form id="formMerge" action="merge.php" method="POST" target="_blank">
                <input type="hidden" name="no_rawat" value="<?= $no_rawat ?>">
                
                <div class="card shadow-sm list-card">
                    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">                            
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="checkAll" checked>
                                <label class="form-check-label small" for="checkAll">Pilih Semua</label>
                            </div>
							<h6 class="mb-0 fw-bold text-primary me-3" style="padding-left: 20px;"><i class="fas fa-folder-open me-2"></i>Berkas Tersimpan</h6>
                        </div>
                        <button type="button" onclick="konfirmasiGabung()" class="btn btn-danger btn-sm fw-bold">
                            <i class="fas fa-file-pdf me-1"></i> GABUNGKAN PDF
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%" class="text-center">#</th>
                                    <th>Nama Dokumen</th>
                                    <th width="20%">Nama File Fisik</th>
                                    <th width="15%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $q_berkas = "SELECT bdp.lokasi_file, mbd.nama, bdp.kode 
                                             FROM berkas_digital_perawatan bdp 
                                             JOIN master_berkas_digital mbd ON bdp.kode = mbd.kode 
                                             WHERE bdp.no_rawat = '$no_rawat' ORDER BY bdp.kode ASC";
                                $r_berkas = mysqli_query($koneksi, $q_berkas);
                                
                                if(mysqli_num_rows($r_berkas) > 0):
                                    while($f = mysqli_fetch_assoc($r_berkas)):
                                        $ext = strtolower(pathinfo($f['lokasi_file'], PATHINFO_EXTENSION));
                                        $icon = in_array($ext, ['jpg','png','jpeg']) ? 'bg-img fa-image' : 'bg-pdf fa-file-pdf';
                                        $real_filename = basename($f['lokasi_file']);
                                        $file_url = $base_url_berkas . $f['lokasi_file'];
                                ?>
                                    <tr>
                                        <td class="text-center">
                                            <input class="form-check-input item-chk" type="checkbox" name="selected_files[]" value="<?= $f['lokasi_file'] ?>" checked>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="file-icon <?= strpos($icon,'bg-img')!==false?'bg-img':'bg-pdf' ?> me-2">
                                                    <i class="fas <?= explode(' ',$icon)[1] ?>"></i>
                                                </div>
                                                <div class="fw-bold"><?= $f['nama'] ?></div>
                                            </div>
                                        </td>
                                        <td class="small text-muted text-truncate" style="max-width: 200px;" title="<?= $real_filename ?>">
                                            <?= $real_filename ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" onclick="showPreview('<?= $file_url ?>', '<?= $ext ?>')" class="btn btn-sm btn-outline-primary" title="Preview">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <a href="<?= $file_url ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Download">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                                <button type="button" onclick="konfirmasiHapus('<?= $f['lokasi_file'] ?>')" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada dokumen tersimpan. Silahkan generate atau upload.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        </div>

    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalUpload" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title"><i class="fas fa-cloud-upload-alt me-2"></i>Upload Berkas Digital</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Jenis Berkas</label>
                        <select class="form-select select2-berkas" name="kode_berkas" style="width: 100%;" required>
                            <option value="">-- Pilih Jenis Berkas --</option>
                            <?php foreach($master_berkas as $mb): ?>
                                <option value="<?= $mb['kode'] ?>"><?= $mb['nama'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">File (PDF/Gambar)</label>
                        <input type="file" class="form-control" name="file_berkas" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="btn_upload_manual" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title"><i class="fas fa-eye me-2"></i>Preview Dokumen</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 bg-light text-center d-flex align-items-center justify-content-center" style="min-height: 500px;">
                <div id="previewContent" class="w-100 h-100"></div>
            </div>
            <div class="modal-footer py-1">
                <a href="#" id="btnDownload" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-download me-1"></i> Download / Buka Penuh</a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        $('.select2-berkas').select2({ theme: 'bootstrap-5', dropdownParent: $('#modalUpload') });
    });

    document.getElementById('checkAll').addEventListener('change', function() {
        document.querySelectorAll('.item-chk').forEach(c => c.checked = this.checked);
    });

    function showPreview(url, ext) {
        var container = document.getElementById('previewContent');
        var btnDown = document.getElementById('btnDownload');
        btnDown.href = url;
        container.innerHTML = ''; 

        if (['jpg', 'jpeg', 'png'].includes(ext)) {
            container.innerHTML = '<img src="' + url + '" class="img-fluid" style="max-height: 80vh;">';
        } else if (ext === 'pdf') {
            container.innerHTML = `
                <object data="${url}" type="application/pdf" width="100%" height="600px">
                    <div class="alert alert-warning m-5">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Preview PDF tidak dapat ditampilkan (Diblokir Server).<br>
                        <a href="${url}" target="_blank" class="btn btn-primary mt-3">Buka Tab Baru</a>
                    </div>
                </object>
            `;
        } else {
            container.innerHTML = '<div class="alert alert-info m-5">Preview tidak tersedia.<br>Klik tombol Download.</div>';
        }
        var myModal = new bootstrap.Modal(document.getElementById('modalPreview'));
        myModal.show();
    }

    function konfirmasiGabung() {
        if(document.querySelectorAll('.item-chk:checked').length === 0) {
            Swal.fire('Warning', 'Pilih file terlebih dahulu!', 'warning'); return;
        }
        document.getElementById('formMerge').submit();
    }

    function konfirmasiHapus(lokasiFile) {
        Swal.fire({
            title: 'Hapus Berkas?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'lihat_berkas.php?no_rawat=<?= urlencode($no_rawat) ?>&act=hapus_berkas&file=' + encodeURIComponent(lokasiFile);
            }
        })
    }
</script>

<div class="text-center text-muted py-3 bg-white border-top mt-5" style="font-size: 0.8rem;">
    <div class="container-fluid">
        <span>&copy; <?= date('Y') ?> Aplikasi Casemix SIMRS Khanza</span>
        <div class="mt-2">
            Developer: <strong class="text-dark">Ichsan Leonhart</strong>
            <span class="mx-2">|</span>
            <a href="https://saweria.co/ichsanleonhart" target="_blank" class="text-warning text-decoration-none fw-bold" id="saweria-link-berkas">
                <i class="fas fa-donate me-1"></i>saweria.co/ichsanleonhart
            </a>
            <a href="https://wa.me/6285726123777" target="_blank" class="text-decoration-none text-muted"><i class="fab fa-whatsapp text-success me-1"></i>6285726123777</a>
            <span class="mx-2">|</span>
            <a href="https://t.me/IchsanLeonhart" target="_blank" class="text-decoration-none text-muted"><i class="fab fa-telegram text-info me-1"></i>@IchsanLeonhart</a>
        </div>

    </div>
</div>

</body>
</html>
<?php mysqli_close($koneksi); ?>