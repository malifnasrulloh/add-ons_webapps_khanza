<?php
// File: modules/ranap/ajax/view_lab.php
// Deskripsi: View Hasil Lab (Grouped by Paket Pemeriksaan)

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

    $headers = [];
    if (count($list_rawat) > 0) {
        $placeholders = implode(',', array_fill(0, count($list_rawat), '?'));
        // 1. Ambil Header Pemeriksaan (Tanggal & Jam) dengan IN
        $sql_header = "SELECT DISTINCT pl.no_rawat, pl.tgl_periksa, pl.jam, pl.dokter_perujuk, pl.kd_dokter, d.nm_dokter
                       FROM periksa_lab pl
                       LEFT JOIN dokter d ON pl.kd_dokter = d.kd_dokter
                       WHERE pl.no_rawat IN ($placeholders)
                       ORDER BY pl.tgl_periksa DESC, pl.jam DESC";
        
        $stmt = $koneksi_pdo->prepare($sql_header);
        $stmt->execute($list_rawat);
        $headers = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

<!-- Filter UI -->
<div class="container-fluid px-3 pt-3 pb-0 bg-light border-bottom">
    <div class="row align-items-end g-2 mb-3">
        <div class="col-md-auto">
            <label class="small fw-bold text-muted mb-1">Filter Riwayat Lab</label>
            <select class="form-select form-select-sm shadow-sm" id="labFilterMode" style="width: auto;">
                <option value="5_terakhir" <?= ($filter_mode == '5_terakhir') ? 'selected' : '' ?>>5 Kunjungan Terakhir</option>
                <option value="semua" <?= ($filter_mode == 'semua') ? 'selected' : '' ?>>Semua Kunjungan</option>
                <option value="tanggal" <?= ($filter_mode == 'tanggal') ? 'selected' : '' ?>>Rentang Tanggal</option>
            </select>
        </div>
        <div class="col-md-auto lab-date-range <?= ($filter_mode == 'tanggal') ? '' : 'd-none' ?>">
            <label class="small fw-bold text-muted mb-1">Tgl Awal</label>
            <input type="date" class="form-control form-control-sm shadow-sm" id="labTglAwal" value="<?= $tgl_awal ?>">
        </div>
        <div class="col-md-auto lab-date-range <?= ($filter_mode == 'tanggal') ? '' : 'd-none' ?>">
            <label class="small fw-bold text-muted mb-1">Tgl Akhir</label>
            <input type="date" class="form-control form-control-sm shadow-sm" id="labTglAkhir" value="<?= $tgl_akhir ?>">
        </div>
        <div class="col-md-auto">
            <button type="button" class="btn btn-sm btn-primary shadow-sm" id="btnTerapkanFilterLab"><i class="fas fa-search"></i> Terapkan</button>
        </div>
    </div>
</div>

<div class="container-fluid p-3">
    <?php if(empty($headers)): ?>
        <div class="alert alert-info text-center">Tidak ada data hasil laboratorium pada rentang kunjungan ini.</div>
    <?php else: ?>
        <div class="accordion" id="accordionLab">
            <?php foreach($headers as $index => $head): ?>
                <?php 
                    $collapseId = "collapseLab" . $index;
                    $headerId = "headingLab" . $index;
                    $waktu = tgl_indo($head['tgl_periksa']) . " " . $head['jam'];
                ?>
                <div class="accordion-item mb-3 border shadow-sm">
                    <h2 class="accordion-header" id="<?= $headerId ?>">
                        <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>">
                            <div class="d-flex w-100 justify-content-between align-items-center me-3">
                                <div>
                                    <span><i class="fas fa-flask me-2 text-info"></i> <b><?= $waktu ?></b></span>
                                    <span class="badge bg-light text-dark ms-2 border border-secondary"><?= $head['no_rawat'] ?></span>
                                </div>
                                <span class="badge bg-secondary small"><?= $head['nm_dokter'] ?? '-' ?></span>
                            </div>
                        </button>
                    </h2>
                    <div id="<?= $collapseId ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#accordionLab">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle small">
                                    <thead class="table-light text-uppercase">
                                        <tr>
                                            <th style="width: 30%">Pemeriksaan</th>
                                            <th style="width: 20%">Hasil</th>
                                            <th style="width: 15%">Satuan</th>
                                            <th style="width: 20%">Nilai Rujukan</th>
                                            <th style="width: 15%">Ket</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // FIX QUERY: Join ke jns_perawatan_lab untuk nama paket
                                        // Dan urutkan berdasarkan kd_jenis_prw agar hasil terkelompok
                                        try {
                                            $sql_detail = "
                                                SELECT 
                                                    j.nm_perawatan AS nama_paket,
                                                    t.Pemeriksaan, 
                                                    d.nilai, 
                                                    t.satuan, 
                                                    d.nilai_rujukan, 
                                                    d.keterangan
                                                FROM detail_periksa_lab d
                                                INNER JOIN template_laboratorium t ON d.id_template = t.id_template
                                                LEFT JOIN jns_perawatan_lab j ON d.kd_jenis_prw = j.kd_jenis_prw
                                                WHERE d.no_rawat = ? AND d.tgl_periksa = ? AND d.jam = ?
                                                ORDER BY d.kd_jenis_prw ASC, t.urut ASC
                                            ";
                                            $stmt_d = $koneksi_pdo->prepare($sql_detail);
                                            $stmt_d->execute([$head['no_rawat'], $head['tgl_periksa'], $head['jam']]);
                                            $details = $stmt_d->fetchAll(PDO::FETCH_ASSOC);
                                        } catch (Exception $e) {
                                            $details = [];
                                            echo "<tr><td colspan='5' class='text-danger'>Error: " . $e->getMessage() . "</td></tr>";
                                        }
                                        
                                        $current_paket = ""; // Variabel penanda grouping

                                        foreach($details as $det):
                                            // LOGIKA GROUPING
                                            if ($det['nama_paket'] != $current_paket) {
                                                echo "<tr class='table-secondary'><td colspan='5' class='fw-bold text-primary'><i class='fas fa-tag me-2'></i>" . $det['nama_paket'] . "</td></tr>";
                                                $current_paket = $det['nama_paket'];
                                            }

                                            // Logika Warna Keterangan
                                            $ket_color = "";
                                            $ket = strtoupper($det['keterangan']);
                                            if (in_array($ket, ['L','LOW','RENDAH'])) $ket_color = "text-primary fw-bold";
                                            elseif (in_array($ket, ['H','HIGH','TINGGI','*'])) $ket_color = "text-danger fw-bold";
                                        ?>
                                        <tr>
                                            <td class="ps-4"><?= $det['Pemeriksaan'] ?></td> <td class="<?= $ket_color ?>"><?= $det['nilai'] ?></td>
                                            <td><?= $det['satuan'] ?></td>
                                            <td><?= $det['nilai_rujukan'] ?></td>
                                            <td><span class="<?= $ket_color ?>"><?= $det['keterangan'] ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if(empty($details)): ?>
                                            <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada rincian hasil.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    var noRawat = '<?= htmlspecialchars($no_rawat, ENT_QUOTES) ?>';

    // ---- HANDLING FILTER ----
    $('#labFilterMode').change(function(){
        if($(this).val() === 'tanggal') {
            $('.lab-date-range').removeClass('d-none');
        } else {
            $('.lab-date-range').addClass('d-none');
        }
    });

    $('#btnTerapkanFilterLab').click(function(){
        var tglAwal = $('#labTglAwal').val();
        var tglAkhir = $('#labTglAkhir').val();
        var filterMode = $('#labFilterMode').val();
        
        var url = 'api/riwayat/view_lab.php';
        var $target = $('#tab-lab-content');
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
});
</script>