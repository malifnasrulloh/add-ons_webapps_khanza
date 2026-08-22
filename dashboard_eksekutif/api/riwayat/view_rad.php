<?php
// File: modules/ranap/ajax/view_rad.php
// Deskripsi: View Hasil Radiologi (Clean Version)

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

try {
    $no_rawat    = isset($_POST['no_rawat']) ? $_POST['no_rawat'] : '';
    $filter_mode = isset($_POST['filter_mode']) ? $_POST['filter_mode'] : '5_terakhir';
    $tgl_awal    = isset($_POST['tgl_awal']) ? $_POST['tgl_awal'] : date('Y-m-d');
    $tgl_akhir   = isset($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : date('Y-m-d');

    if (empty($no_rawat)) throw new Exception("No Rawat tidak dikirim.");

    // Cari no_rkm_medis dari no_rawat aktif
    $stmt = $koneksi_pdo->prepare("SELECT no_rkm_medis FROM reg_periksa WHERE no_rawat = ? LIMIT 1");
    $stmt->execute([$no_rawat]);
    $reg = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$reg) throw new Exception("Data registrasi tidak ditemukan.");
    $no_rkm_medis = $reg['no_rkm_medis'];

    // Bangun filter list no_rawat berdasarkan filter_mode
    $sub_query = "SELECT no_rawat FROM reg_periksa WHERE no_rkm_medis = ?";
    $params_rawat = [$no_rkm_medis];

    if ($filter_mode === '5_terakhir') {
        $sub_query .= " ORDER BY tgl_registrasi DESC, jam_reg DESC LIMIT 5";
    } else if ($filter_mode === 'tanggal') {
        $sub_query .= " AND tgl_registrasi BETWEEN ? AND ? ORDER BY tgl_registrasi DESC, jam_reg DESC";
        $params_rawat[] = $tgl_awal;
        $params_rawat[] = $tgl_akhir;
    } else {
        $sub_query .= " ORDER BY tgl_registrasi DESC, jam_reg DESC";
    }

    $stmt_rawat = $koneksi_pdo->prepare($sub_query);
    $stmt_rawat->execute($params_rawat);
    $list_rawat = $stmt_rawat->fetchAll(PDO::FETCH_COLUMN);

    $radiologi_data = [];
    if (count($list_rawat) > 0) {
        $placeholders = implode(',', array_fill(0, count($list_rawat), '?'));
        // Query Data Radiologi
        $sql = "SELECT pr.no_rawat, pr.tgl_periksa, pr.jam, hr.hasil, d.nm_dokter, jp.nm_perawatan as jenis_periksa
                FROM periksa_radiologi pr
                LEFT JOIN hasil_radiologi hr ON pr.no_rawat = hr.no_rawat AND pr.tgl_periksa = hr.tgl_periksa AND pr.jam = hr.jam
                LEFT JOIN dokter d ON pr.kd_dokter = d.kd_dokter
                LEFT JOIN jns_perawatan_radiologi jp ON pr.kd_jenis_prw = jp.kd_jenis_prw
                WHERE pr.no_rawat IN ($placeholders)
                ORDER BY pr.tgl_periksa DESC, pr.jam DESC";
                
        $stmt = $koneksi_pdo->prepare($sql);
        $stmt->execute($list_rawat);
        $radiologi_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function tgl_indo($tanggal){
        $bulan = array (1 => 'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des');
        $pecahkan = explode('-', $tanggal);
        return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
    }

} catch (Exception $e) {
    die('<div class="alert alert-danger">Error Sistem: ' . $e->getMessage() . '</div>');
}
?>
<!-- (BARU) Library Viewer.js untuk Popup Gambar -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.js"></script>

<!-- Filter UI -->
<div class="container-fluid px-3 pt-3 pb-0 bg-light border-bottom">
    <div class="row align-items-end g-2 mb-3">
        <div class="col-md-auto">
            <label class="small fw-bold text-muted mb-1">Filter Riwayat Radiologi</label>
            <select class="form-select form-select-sm shadow-sm" id="radFilterMode" style="width: auto;">
                <option value="5_terakhir" <?= ($filter_mode == '5_terakhir') ? 'selected' : '' ?>>5 Kunjungan Terakhir</option>
                <option value="semua" <?= ($filter_mode == 'semua') ? 'selected' : '' ?>>Semua Kunjungan</option>
                <option value="tanggal" <?= ($filter_mode == 'tanggal') ? 'selected' : '' ?>>Rentang Tanggal</option>
            </select>
        </div>
        <div class="col-md-auto rad-date-range <?= ($filter_mode == 'tanggal') ? '' : 'd-none' ?>">
            <label class="small fw-bold text-muted mb-1">Tgl Awal</label>
            <input type="date" class="form-control form-control-sm shadow-sm" id="radTglAwal" value="<?= $tgl_awal ?>">
        </div>
        <div class="col-md-auto rad-date-range <?= ($filter_mode == 'tanggal') ? '' : 'd-none' ?>">
            <label class="small fw-bold text-muted mb-1">Tgl Akhir</label>
            <input type="date" class="form-control form-control-sm shadow-sm" id="radTglAkhir" value="<?= $tgl_akhir ?>">
        </div>
        <div class="col-md-auto">
            <button type="button" class="btn btn-sm btn-primary shadow-sm" id="btnTerapkanFilterRad"><i class="fas fa-search"></i> Terapkan</button>
        </div>
    </div>
</div>

<div class="container-fluid p-3">
    <div class="alert alert-light border small text-muted py-1 mb-3">
        <i class="fas fa-link me-1"></i> Sumber Gambar: <strong><?= $webapps_url ?></strong>
    </div>

    <?php if(empty($radiologi_data)): ?>
        <div class="alert alert-info text-center">Tidak ada hasil radiologi pada rentang kunjungan ini.</div>
    <?php else: ?>
        <?php foreach($radiologi_data as $rad): ?>
            <?php 
                try {
                    $sql_img = "SELECT lokasi_gambar FROM gambar_radiologi WHERE no_rawat = ? AND tgl_periksa = ? AND jam = ?";
                    $stmt_img = $koneksi_pdo->prepare($sql_img);
                    $stmt_img->execute([$rad['no_rawat'], $rad['tgl_periksa'], $rad['jam']]);
                    $images = $stmt_img->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) { $images = []; }
            ?>

            <div class="card mb-4 border shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-x-ray me-2"></i> <?= $rad['jenis_periksa'] ?></h6>
                        <small class="text-muted"><?= tgl_indo($rad['tgl_periksa']) ?> <?= $rad['jam'] ?> | Dokter: <?= $rad['nm_dokter'] ?></small>
                        <span class="badge bg-secondary ms-2 text-dark bg-opacity-25 border border-secondary"><?= $rad['no_rawat'] ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-7 border-end">
                            <h6 class="text-uppercase small text-muted fw-bold mb-2">Expertise:</h6>
                            <div class="bg-white p-3 border rounded" style="white-space: pre-wrap; font-family: 'Consolas', monospace; font-size: 0.9rem; background: #fdfdfd; min-height: 150px;"><?= $rad['hasil'] ? $rad['hasil'] : '<span class="text-muted fst-italic">Belum ada bacaan dokter (Expertise).</span>' ?></div>
                        </div>
                        <div class="col-lg-5">
                            <h6 class="text-uppercase small text-muted fw-bold mb-2">Citra Radiologi:</h6>
                            <?php if(empty($images)): ?>
                                <div class="text-center text-muted py-5 border rounded bg-light small">
                                    <i class="fas fa-image fa-2x mb-2 text-secondary"></i><br>Tidak ada gambar digital.
                                </div>
                            <?php else: ?>
                                <div class="row g-2 radiology-gallery-mpp">
                                    <?php foreach($images as $img): ?>
                                        <?php 
                                            $lokasi_db = $img['lokasi_gambar'];
                                            $clean_path = str_replace(['pages/upload/', 'radiologi/'], '', $lokasi_db);
                                            $full_url = $webapps_url . "radiologi/pages/upload/" . $clean_path;
                                        ?>
                                        <div class="col-6">
                                            <div class="border rounded p-1 text-center bg-light">
                                                <img src="<?= $full_url ?>" class="img-fluid rounded hover-zoom" 
                                                     style="height: 120px; width: 100%; object-fit: cover;" 
                                                     alt="Rontgen - <?= basename($clean_path) ?>"
                                                     onerror="this.onerror=null; this.src='https://via.placeholder.com/150?text=Gagal+Load';">
                                                <small class="d-block mt-1 text-muted text-truncate" style="font-size: 0.65rem;">
                                                    <?= basename($clean_path) ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</div>

<style>
    .hover-zoom { transition: transform 0.2s; cursor: pointer; }
    .hover-zoom:hover { transform: scale(1.05); }
</style>

<script>
$(document).ready(function() {
    var noRawat = '<?= htmlspecialchars($no_rawat, ENT_QUOTES) ?>';

    // ---- HANDLING FILTER ----
    $('#radFilterMode').change(function(){
        if($(this).val() === 'tanggal') {
            $('.rad-date-range').removeClass('d-none');
        } else {
            $('.rad-date-range').addClass('d-none');
        }
    });

    $('#btnTerapkanFilterRad').click(function(){
        var tglAwal = $('#radTglAwal').val();
        var tglAkhir = $('#radTglAkhir').val();
        var filterMode = $('#radFilterMode').val();
        
        var url = 'api/riwayat/view_rad.php';
        var $target = $('#tab-radiologi-content');
        if($target.length === 0) $target = $('#tab-history');

        $target.html('<div class="text-center mt-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted small">Menyaring data...</p></div>');
        
        $.post(url, { 
            no_rawat: noRawat, 
            filter_mode: filterMode, 
            tgl_awal: tglAwal, 
            tgl_akhir: tglAkhir 
        }, function(data) {
            $target.html(data);
        });
    });

    // ---- INITIALIZE VIEWER.JS ----
    setTimeout(function() {
        var galleries = document.querySelectorAll('.radiology-gallery-mpp');
        galleries.forEach(function(gallery) {
            if (!gallery.viewer) {
                new Viewer(gallery, {
                    inline: false,
                    toolbar: {
                        zoomIn: 4, zoomOut: 4, oneToOne: 4, reset: 4, prev: 4,
                        play: { show: 4, size: 'large' },
                        next: 4, rotateLeft: 4, rotateRight: 4, flipHorizontal: 4, flipVertical: 4,
                    },
                    title: (image) => image.alt,
                    url: 'src'
                });
            }
        });
    }, 100);
});
</script>