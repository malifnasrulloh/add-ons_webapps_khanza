<?php
// File: helpers/ajax/view_cppt.php
// Deskripsi: Menampilkan Riwayat CPPT (SOAP + Instruksi & Evaluasi) + tombol Edit/Hapus

require_once dirname(__DIR__, 2) . '/config/koneksi.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

ini_set('display_errors', 0);
error_reporting(0);

$no_rawat      = isset($_POST['no_rawat']) ? $_POST['no_rawat'] : '';
$filter_mode   = isset($_POST['filter_mode']) ? $_POST['filter_mode'] : '5_terakhir';
$tgl_awal      = isset($_POST['tgl_awal']) ? $_POST['tgl_awal'] : date('Y-m-d');
$tgl_akhir     = isset($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : date('Y-m-d');
$current_nip   = $_SESSION['user_id'] ?? '';
$is_superadmin = isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';
$time_now      = new DateTime();

if (empty($no_rawat)) {
    echo '<div class="alert alert-danger">No Rawat tidak ditemukan.</div>';
    exit;
}

// Cari no_rkm_medis dari no_rawat aktif
$stmt = $koneksi_pdo->prepare("SELECT no_rkm_medis FROM reg_periksa WHERE no_rawat = ? LIMIT 1");
$stmt->execute([$no_rawat]);
$reg = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$reg) {
    echo '<div class="alert alert-danger">Data registrasi tidak ditemukan.</div>';
    exit;
}
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

$cppt_data = [];
$cppt_grouped = [];

if (count($list_rawat) > 0) {
    $placeholders = implode(',', array_fill(0, count($list_rawat), '?'));
    
    // Query UNION Ranap + Ralan menggunakan IN ($placeholders)
    $sql = "
        (
            SELECT no_rawat, tgl_perawatan, jam_rawat, suhu_tubuh, tensi, nadi, respirasi, spo2, 
                   kesadaran, gcs, keluhan, pemeriksaan, penilaian, rtl, instruksi, evaluasi, nip, 
                   'Rawat Inap' as sumber 
            FROM pemeriksaan_ranap 
            WHERE no_rawat IN ($placeholders)
        )
        UNION ALL
        (
            SELECT no_rawat, tgl_perawatan, jam_rawat, suhu_tubuh, tensi, nadi, respirasi, spo2, 
                   kesadaran, gcs, keluhan, pemeriksaan, penilaian, rtl, instruksi, evaluasi, nip, 
                   'Rawat Jalan' as sumber 
            FROM pemeriksaan_ralan 
            WHERE no_rawat IN ($placeholders)
        )
        ORDER BY tgl_perawatan DESC, jam_rawat DESC
    ";
    
    // Siapkan parameter ganda untuk UNION
    $params_union = array_merge($list_rawat, $list_rawat);

    try {
        $stmt = $koneksi_pdo->prepare($sql);
        $stmt->execute($params_union);
        $cppt_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group by no_rawat
        foreach ($cppt_data as $row) {
            $nr = $row['no_rawat'];
            if (!isset($cppt_grouped[$nr])) $cppt_grouped[$nr] = [];
            $cppt_grouped[$nr][] = $row;
        }
    } catch (Exception $e) {
        die('<div class="alert alert-danger">Error Query CPPT: ' . $e->getMessage() . '</div>');
    }
}

function getNamaPetugas($koneksi_pdo, $nip) {
    $stmt = $koneksi_pdo->prepare("SELECT nm_dokter FROM dokter WHERE kd_dokter = ?");
    $stmt->execute([$nip]);
    if ($d = $stmt->fetch()) return $d['nm_dokter'];
    $stmt = $koneksi_pdo->prepare("SELECT nama FROM petugas WHERE nip = ?");
    $stmt->execute([$nip]);
    if ($p = $stmt->fetch()) return $p['nama'];
    $stmt = $koneksi_pdo->prepare("SELECT nama FROM pegawai WHERE nik = ?");
    $stmt->execute([$nip]);
    if ($p = $stmt->fetch()) return $p['nama'];
    return $nip;
}

/**
 * Tentukan PPA Badge berdasarkan jabatan.nm_jbtn (LIKE pattern).
 * Dokter = tidak ada di tabel petugas -> badge merah 'dr.'
 * Profesi lain = join petugas + jabatan, cocokkan nama jabatan.
 */
function getPPABadge($koneksi_pdo, $nip) {
    // 1. Cek apakah NIP ada di tabel dokter
    $stmt = $koneksi_pdo->prepare("SELECT kd_dokter FROM dokter WHERE kd_dokter = ? LIMIT 1");
    $stmt->execute([$nip]);
    if ($stmt->fetch()) {
        return '<span class="badge ppa-badge" style="background:#c0392b;font-size:.72rem;">dr.</span>';
    }

    // 2. Cek petugas + ambil nama jabatan
    $stmt = $koneksi_pdo->prepare("
        SELECT j.nm_jbtn
        FROM petugas p
        LEFT JOIN jabatan j ON p.kd_jbtn = j.kd_jbtn
        WHERE p.nip = ?
        LIMIT 1
    ");
    $stmt->execute([$nip]);
    $row = $stmt->fetch();

    if (!$row) {
        // Tidak dikenali sama sekali
        return '<span class="badge ppa-badge" style="background:#95a5a6;font-size:.72rem;">Staff</span>';
    }

    $nm = strtolower($row['nm_jbtn'] ?? '');
    $label = htmlspecialchars($row['nm_jbtn']);

    if (str_contains_ci($nm, 'bidan')) {
        return "<span class=\"badge ppa-badge\" style=\"background:#f39c12;font-size:.72rem;\">Bd. ($label)</span>";
    } elseif (str_contains_ci($nm, 'perawat')) {
        return "<span class=\"badge ppa-badge\" style=\"background:#2980b9;font-size:.72rem;\">Ns. ($label)</span>";
    } elseif (str_contains_ci($nm, 'apotek')) {
        return "<span class=\"badge ppa-badge\" style=\"background:#8e44ad;font-size:.72rem;\">Apt. ($label)</span>";
    } elseif (str_contains_ci($nm, 'lab') || str_contains_ci($nm, 'analis')) {
        return "<span class=\"badge ppa-badge\" style=\"background:#27ae60;font-size:.72rem;\">Lab. ($label)</span>";
    } elseif (str_contains_ci($nm, 'rad') || str_contains_ci($nm, 'radiologi')) {
        return "<span class=\"badge ppa-badge\" style=\"background:#d35400;font-size:.72rem;\">Rad. ($label)</span>";
    } elseif (str_contains_ci($nm, 'gizi')) {
        return "<span class=\"badge ppa-badge\" style=\"background:#16a085;font-size:.72rem;\">Gz. ($label)</span>";
    } else {
        return "<span class=\"badge ppa-badge\" style=\"background:#7f8c8d;font-size:.72rem;\">$label</span>";
    }
}

// Compat: PHP <8 tidak punya str_contains; pakai wrapper
if (!function_exists('str_contains_ci')) {
    function str_contains_ci($haystack, $needle) {
        return stripos($haystack, $needle) !== false;
    }
}

function tgl_indo($tanggal){
    $bulan = array(1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
    $pecahkan = explode('-', $tanggal);
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}
?>

<style>
    .timeline-item { border-left: 4px solid #dee2e6; padding-left: 20px; margin-bottom: 20px; position: relative; }
    .timeline-item::before { content: ''; width: 14px; height: 14px; background: #fff; border: 3px solid #0d6efd; border-radius: 50%; position: absolute; left: -9px; top: 0; }
    .timeline-item.ranap { border-left-color: #0d6efd; }
    .timeline-item.ralan { border-left-color: #198754; }
    .timeline-item.ralan::before { border-color: #198754; }
    .soap-box { background: #fff; border: 1px solid #e3e6f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .soap-header { background-color: #f8f9fc; padding: 10px 15px; border-bottom: 1px solid #e3e6f0; border-radius: 8px 8px 0 0; }
    .vital-sign-badge { font-size: 0.8rem; padding: 4px 8px; margin-right: 5px; border-radius: 4px; background-color: #f1f3f5; border: 1px solid #ced4da; }
    .soap-label { font-weight: bold; color: #4e73df; width: 35px; display: inline-block; vertical-align: top; }
    .content-text { color: #333; display: inline-block; width: calc(100% - 40px); }

    /* Dark Mode Overrides */
    body.dark-mode .soap-box { background: #1e293b; border-color: rgba(255,255,255,0.1); }
    body.dark-mode .soap-header { background-color: #0f172a; border-bottom-color: rgba(255,255,255,0.1); }
    body.dark-mode .vital-sign-badge { background-color: #334155; border-color: rgba(255,255,255,0.1); color: #f8fafc; }
    body.dark-mode .content-text { color: #cbd5e1; }
    body.dark-mode .timeline-item::before { background: #1e293b; }
</style>

<!-- Uji ApexCharts via CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<!-- Filter UI -->
<div class="container-fluid px-3 pt-3 pb-0 bg-light border-bottom">
    <div class="row align-items-end g-2 mb-3">
        <div class="col-md-auto">
            <label class="small fw-bold text-muted mb-1">Filter Kunjungan CPPT</label>
            <select class="form-select form-select-sm shadow-sm" id="cpptFilterMode" style="width: auto;">
                <option value="5_terakhir" <?= ($filter_mode == '5_terakhir') ? 'selected' : '' ?>>5 Kunjungan Terakhir</option>
                <option value="semua" <?= ($filter_mode == 'semua') ? 'selected' : '' ?>>Semua Kunjungan</option>
                <option value="tanggal" <?= ($filter_mode == 'tanggal') ? 'selected' : '' ?>>Rentang Tanggal</option>
            </select>
        </div>
        <div class="col-md-auto cppt-date-range <?= ($filter_mode == 'tanggal') ? '' : 'd-none' ?>">
            <label class="small fw-bold text-muted mb-1">Tgl Awal</label>
            <input type="date" class="form-control form-control-sm shadow-sm" id="cpptTglAwal" value="<?= $tgl_awal ?>">
        </div>
        <div class="col-md-auto cppt-date-range <?= ($filter_mode == 'tanggal') ? '' : 'd-none' ?>">
            <label class="small fw-bold text-muted mb-1">Tgl Akhir</label>
            <input type="date" class="form-control form-control-sm shadow-sm" id="cpptTglAkhir" value="<?= $tgl_akhir ?>">
        </div>
        <div class="col-md-auto">
            <button type="button" class="btn btn-sm btn-primary shadow-sm" id="btnTerapkanFilterCPPT"><i class="fas fa-search"></i> Terapkan</button>
        </div>
    </div>
</div>

<div class="container-fluid p-3 bg-light">
    <?php if (empty($cppt_grouped)): ?>
        <div class="alert alert-info text-center"><i class="fas fa-info-circle fa-2x mb-3"></i><br>Belum ada data CPPT.</div>
    <?php else: ?>
        <div class="timeline-container">
            <?php foreach ($cppt_grouped as $nr => $group_data): ?>
                
                <div class="card mb-4 border-0 shadow-sm border-top border-primary border-3">
                    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-procedures me-2"></i>Kunjungan: <?= htmlspecialchars($nr) ?></h6>
                        <span class="badge bg-secondary"><?= count($group_data) ?> Catatan</span>
                    </div>
                    <div class="card-body bg-light p-3">
                        
                        <!-- Tempat Render Chart per Kunjungan -->
                        <div id="chart_cppt_<?= md5($nr) ?>" class="mb-4"></div>
                        
                        <!-- Timeline Items -->
                        <?php foreach ($group_data as $row): 
                            $nama_petugas = getNamaPetugas($koneksi_pdo, $row['nip']);
                            $ppa_badge    = getPPABadge($koneksi_pdo, $row['nip']);
                            $jenis_class  = ($row['sumber'] == 'Rawat Inap') ? 'ranap' : 'ralan';
                            $bg_badge     = ($row['sumber'] == 'Rawat Inap') ? 'bg-primary' : 'bg-success';

                            $waktu_cppt  = new DateTime($row['tgl_perawatan'] . ' ' . $row['jam_rawat']);
                            $diff_hours  = ($time_now->getTimestamp() - $waktu_cppt->getTimestamp()) / 3600;
                            
                            // Deteksi asal modul berdasarkan referer
                            $referer = $_SERVER['HTTP_REFERER'] ?? '';
                            $current_module = (strpos($referer, '/ranap/') !== false) ? 'ranap' : 'ralan';
                            $jenis_source = ($row['sumber'] == 'Rawat Inap') ? 'ranap' : 'ralan';

                            // Bisa diedit jika:
                            // 1. Bukan superadmin, 2. Penulis asli, 3. Belum 48 jam, 4. Sesuai dengan modul tempat dia membuka
                            $is_editable = (!$is_superadmin && $row['nip'] == $current_nip && $diff_hours <= 48 && $jenis_source == $current_module);
                        ?>
            <div class="timeline-item <?= $jenis_class ?>">
                <div class="mb-1 d-flex flex-wrap align-items-center gap-2">
                    <span class="badge <?= $bg_badge ?>"><?= $row['sumber'] ?></span>
                    <span class="text-muted fw-bold small">
                        <i class="far fa-calendar-alt me-1"></i><?= tgl_indo($row['tgl_perawatan']) ?>
                        <i class="far fa-clock ms-2 me-1"></i><?= $row['jam_rawat'] ?>
                    </span>
                    <span class="ms-auto small d-flex align-items-center gap-1">
                        <?= $ppa_badge ?>
                        <i class="fas fa-user-md me-1 text-muted"></i>
                        <span class="text-muted"><?= htmlspecialchars($nama_petugas) ?></span>
                    </span>
                    <?php if($is_editable): ?>
                    <button class="btn btn-outline-warning btn-sm py-0 px-2 btn-edit-cppt" 
                        data-tgl="<?= $row['tgl_perawatan'] ?>"
                        data-jam="<?= $row['jam_rawat'] ?>"
                        data-suhu="<?= htmlspecialchars($row['suhu_tubuh'] ?? '', ENT_QUOTES) ?>"
                        data-tensi="<?= htmlspecialchars($row['tensi'] ?? '', ENT_QUOTES) ?>"
                        data-nadi="<?= htmlspecialchars($row['nadi'] ?? '', ENT_QUOTES) ?>"
                        data-respirasi="<?= htmlspecialchars($row['respirasi'] ?? '', ENT_QUOTES) ?>"
                        data-spo2="<?= htmlspecialchars($row['spo2'] ?? '', ENT_QUOTES) ?>"
                        data-gcs="<?= htmlspecialchars($row['gcs'] ?? '', ENT_QUOTES) ?>"
                        data-kesadaran="<?= htmlspecialchars($row['kesadaran'] ?? '', ENT_QUOTES) ?>"
                        data-keluhan="<?= htmlspecialchars($row['keluhan'] ?? '', ENT_QUOTES) ?>"
                        data-pemeriksaan="<?= htmlspecialchars($row['pemeriksaan'] ?? '', ENT_QUOTES) ?>"
                        data-penilaian="<?= htmlspecialchars($row['penilaian'] ?? '', ENT_QUOTES) ?>"
                        data-rtl="<?= htmlspecialchars($row['rtl'] ?? '', ENT_QUOTES) ?>"
                        data-instruksi="<?= htmlspecialchars($row['instruksi'] ?? '', ENT_QUOTES) ?>"
                        data-evaluasi="<?= htmlspecialchars($row['evaluasi'] ?? '', ENT_QUOTES) ?>"
                        title="Edit CPPT ini"><i class="fas fa-edit"></i> Edit</button>
                    <button class="btn btn-outline-danger btn-sm py-0 px-2 btn-hapus-cppt"
                        data-tgl="<?= $row['tgl_perawatan'] ?>"
                        data-jam="<?= $row['jam_rawat'] ?>"
                        title="Hapus CPPT ini"><i class="fas fa-trash"></i></button>
                    <?php endif; ?>
                </div>

                <div class="soap-box">
                    <div class="soap-header">
                        <div class="d-flex flex-wrap gap-1">
                            <?php if($row['tensi']): ?><span class="vital-sign-badge">TD: <?= $row['tensi'] ?></span><?php endif; ?>
                            <?php if($row['nadi']): ?><span class="vital-sign-badge">Nadi: <?= $row['nadi'] ?></span><?php endif; ?>
                            <?php if($row['suhu_tubuh']): ?><span class="vital-sign-badge">Suhu: <?= $row['suhu_tubuh'] ?></span><?php endif; ?>
                            <?php if($row['respirasi']): ?><span class="vital-sign-badge">RR: <?= $row['respirasi'] ?></span><?php endif; ?>
                            <?php if($row['spo2']): ?><span class="vital-sign-badge">SpO2: <?= $row['spo2'] ?>%</span><?php endif; ?>
                            <?php if($row['gcs']): ?><span class="vital-sign-badge">GCS: <?= $row['gcs'] ?></span><?php endif; ?>
                            <?php if($row['kesadaran']): ?><span class="vital-sign-badge"><?= $row['kesadaran'] ?></span><?php endif; ?>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="mb-2"><span class="soap-label">S</span><div class="content-text"><?= nl2br(htmlspecialchars($row['keluhan'])) ?></div></div>
                        <div class="mb-2"><span class="soap-label">O</span><div class="content-text"><?= nl2br(htmlspecialchars($row['pemeriksaan'])) ?></div></div>
                        <div class="mb-2"><span class="soap-label">A</span><div class="content-text"><?= nl2br(htmlspecialchars($row['penilaian'])) ?></div></div>
                        <div class="mb-2"><span class="soap-label">P</span><div class="content-text"><?= nl2br(htmlspecialchars($row['rtl'])) ?></div></div>
                        <?php if(!empty($row['instruksi'])): ?>
                            <div class="mb-2"><span class="soap-label text-warning">I</span><div class="content-text bg-warning bg-opacity-10 p-1 rounded"><?= nl2br(htmlspecialchars($row['instruksi'])) ?></div></div>
                        <?php endif; ?>
                        <?php if(!empty($row['evaluasi'])): ?>
                            <div class="mb-2"><span class="soap-label text-success">E</span><div class="content-text bg-success bg-opacity-10 p-1 rounded"><?= nl2br(htmlspecialchars($row['evaluasi'])) ?></div></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div> <!-- End timeline-item -->
            <?php endforeach; // End group row ?>
            
            <?php 
            // ==========================================
            // SIAPKAN DATA CHART UNTUK KUNJUNGAN INI
            // ==========================================
            $chart_data = [];
            // Diurutkan secara ascending (dari lama ke baru) untuk chart
            $group_asc = array_reverse($group_data);
            foreach($group_asc as $g) {
                // Parsing Tensi (Sistol / Diastol)
                $sistol = null; $diastol = null;
                if(!empty($g['tensi']) && strpos($g['tensi'], '/') !== false) {
                    $parts = explode('/', $g['tensi']);
                    $sistol = (float)trim($parts[0]);
                    $diastol = (float)trim($parts[1]);
                }
                
                // Timestamp untuk sumbu X
                $ts = strtotime($g['tgl_perawatan'] . ' ' . $g['jam_rawat']) * 1000;
                
                // Ambil nilai valid atau null agar chart bisa skip (interpolate / break line)
                $suhu = (floatval($g['suhu_tubuh']) > 0) ? (float)$g['suhu_tubuh'] : null;
                $rr   = (floatval($g['respirasi']) > 0) ? (float)$g['respirasi'] : null;
                $gcs  = (floatval($g['gcs']) > 0) ? (float)$g['gcs'] : null;
                $nadi = (floatval($g['nadi']) > 0) ? (float)$g['nadi'] : null;
                $spo2 = (floatval($g['spo2']) > 0) ? (float)$g['spo2'] : null;
                
                // Hanya masukkan ke array chart jika minimal punya 1 data numerik TTV
                // (Untuk menghindari titik data yang kosong sama sekali)
                if($suhu || $rr || $gcs || $nadi || $spo2 || $sistol || $diastol) {
                    $chart_data[] = [
                        'x' => $ts,
                        'suhu' => $suhu,
                        'rr' => $rr,
                        'gcs' => $gcs,
                        'nadi' => $nadi,
                        'spo2' => $spo2,
                        'sistol' => $sistol,
                        'diastol' => $diastol,
                        'label' => date('d/m H:i', $ts/1000)
                    ];
                }
            }
            
            $chart_id = "chart_cppt_" . md5($nr);
            
            // JIKA ADA LEBIH DARI 1 DATA TTV UNTUK KUNJUNGAN INI, RENDER CHART
            if(count($chart_data) > 1):
                // Format data untuk Apex
                $ds_suhu = []; $ds_rr = []; $ds_gcs = []; $ds_nadi = []; $ds_spo2 = []; $ds_sistol = []; $ds_diastol = [];
                foreach($chart_data as $c) {
                    $ds_suhu[] = '['.$c['x'].', '.json_encode($c['suhu']).']';
                    $ds_rr[] = '['.$c['x'].', '.json_encode($c['rr']).']';
                    $ds_gcs[] = '['.$c['x'].', '.json_encode($c['gcs']).']';
                    $ds_nadi[] = '['.$c['x'].', '.json_encode($c['nadi']).']';
                    $ds_spo2[] = '['.$c['x'].', '.json_encode($c['spo2']).']';
                    $ds_sistol[] = '['.$c['x'].', '.json_encode($c['sistol']).']';
                    $ds_diastol[] = '['.$c['x'].', '.json_encode($c['diastol']).']';
                }
            ?>
            <script>
            (function() {
                var syncGroup = 'sync_<?= md5($nr) ?>';
                var optsBase = {
                    chart: { type: 'line', height: 160, group: syncGroup, animations: { enabled: false }, toolbar: { show: false } },
                    stroke: { width: 2, curve: 'straight' },
                    markers: { size: 4, hover: { size: 6 } },
                    xaxis: { type: 'datetime', labels: { datetimeUTC: false, format: 'dd/MM HH:mm' }, tooltip: { enabled: false } },
                    tooltip: { x: { format: 'dd MMM yyyy HH:mm' } },
                    grid: { borderColor: '#e7e7e7', row: { colors: ['#f3f3f3', 'transparent'], opacity: 0.5 } },
                    dataLabels: { enabled: true, background: { enabled: true, borderRadius: 2, dropShadow: { enabled: false } }, offsetY: -5 }
                };

                // 1. Tensi (Sistolik & Diastolik)
                var optTensi = Object.assign({}, optsBase, {
                    series: [ { name: 'Sistolik', data: [<?= implode(',', $ds_sistol) ?>] }, { name: 'Diastolik', data: [<?= implode(',', $ds_diastol) ?>] } ],
                    colors: ['#c0392b', '#e67e22'],
                    yaxis: { title: { text: "Tensi (mmHg)" }, min: 40, max: 200, tickAmount: 4 },
                    title: { text: 'Grafik TTV (Tensi, Nadi, Suhu, RR, GCS, SpO2)', align: 'left', style: { fontSize: '13px', color: '#2c3e50' } },
                    chart: { type: 'line', height: 180, group: syncGroup, id: 'twTensi_<?= md5($nr) ?>', toolbar: { show: true, tools: { download: true, selection: false, zoom: false, pan: false } } }
                });
                
                // 2. Nadi & Saturasi
                var optNadi = Object.assign({}, optsBase, {
                    series: [ { name: 'Nadi', data: [<?= implode(',', $ds_nadi) ?>] }, { name: 'SpO2 (%)', data: [<?= implode(',', $ds_spo2) ?>], type: 'area' } ],
                    colors: ['#e74c3c', '#3498db'],
                    stroke: { width: [2, 1], curve: 'straight' },
                    fill: { type: ['solid', 'gradient'], gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.1 } },
                    yaxis: [
                        { title: { text: "Nadi (x/mnt)" }, min: 40, max: 180, tickAmount: 3 },
                        { opposite: true, title: { text: "SpO2 (%)" }, min: 50, max: 100, tickAmount: 3 }
                    ],
                    chart: { type: 'line', height: 160, group: syncGroup, id: 'twNadi_<?= md5($nr) ?>' }
                });

                // 3. Respirasi & GCS
                var optRR = Object.assign({}, optsBase, {
                    series: [ { name: 'Respirasi', data: [<?= implode(',', $ds_rr) ?>] }, { name: 'GCS', data: [<?= implode(',', $ds_gcs) ?>] } ],
                    colors: ['#2ecc71', '#9b59b6'],
                    yaxis: [
                        { title: { text: "Resp (x/mnt)" }, min: 10, max: 60, tickAmount: 2 },
                        { opposite: true, title: { text: "GCS (Total)" }, min: 3, max: 15, tickAmount: 2 }
                    ],
                    chart: { type: 'line', height: 160, group: syncGroup, id: 'twRR_<?= md5($nr) ?>' }
                });

                // 4. Suhu
                var optSuhu = Object.assign({}, optsBase, {
                    series: [ { name: 'Suhu (°C)', data: [<?= implode(',', $ds_suhu) ?>] } ],
                    colors: ['#f1c40f'],
                    yaxis: { title: { text: "Suhu (°C)" }, min: 35, max: 41, tickAmount: 3, decimalsInFloat: 1 },
                    chart: { type: 'line', height: 150, group: syncGroup, id: 'twSuhu_<?= md5($nr) ?>' }
                });

                var cContainer = document.getElementById('<?= $chart_id ?>');
                if(!cContainer) return;
                
                var c1 = document.createElement('div'); cContainer.appendChild(c1);
                var c2 = document.createElement('div'); cContainer.appendChild(c2);
                var c3 = document.createElement('div'); cContainer.appendChild(c3);
                var c4 = document.createElement('div'); cContainer.appendChild(c4);

                new ApexCharts(c1, optTensi).render();
                new ApexCharts(c2, optNadi).render();
                new ApexCharts(c3, optRR).render();
                new ApexCharts(c4, optSuhu).render();
            })();
            </script>
            <?php endif; ?>
            
                    </div> <!-- End card-body group -->
                </div> <!-- End card group -->
            <?php endforeach; // End group kunjungan ?>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    var noRawat = '<?= htmlspecialchars($no_rawat, ENT_QUOTES) ?>';

    // ---- HANDLING FILTER ----
    $('#cpptFilterMode').change(function(){
        if($(this).val() === 'tanggal') {
            $('.cppt-date-range').removeClass('d-none');
        } else {
            $('.cppt-date-range').addClass('d-none');
        }
    });

    $('#btnTerapkanFilterCPPT').click(function(){
        var tglAwal = $('#cpptTglAwal').val();
        var tglAkhir = $('#cpptTglAkhir').val();
        var filterMode = $('#cpptFilterMode').val();
        
        var url = 'api/riwayat/view_cppt.php';
        var $target = $('#tab-cppt-content'); // container utama CPPT
        if($target.length === 0) $target = $('#tab-history'); // fallback jika di modal riwayat lengkap

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

    // ---- TOMBOL EDIT ----
    $('.btn-edit-cppt').off('click').on('click', function() {
        var btn = $(this);
        // Pindah ke tab CPPT di panel kiri
        $('#leftTabs a[href="#tab-cppt"]').tab('show');
        // Isi form dengan data CPPT yang dipilih
        $('#formCPPT input[name="aksi_nyata"]').val('ubah');
        if ($('#formCPPT input[name="tgl_perawatan"]').length === 0) {
            $('#formCPPT').append('<input type="hidden" name="tgl_perawatan">');
            $('#formCPPT').append('<input type="hidden" name="jam_rawat">');
        }
        $('#formCPPT input[name="tgl_perawatan"]').val(btn.data('tgl'));
        $('#formCPPT input[name="jam_rawat"]').val(btn.data('jam'));
        $('#formCPPT input[name="suhu_tubuh"]').val(btn.data('suhu'));
        $('#formCPPT input[name="tensi"]').val(btn.data('tensi'));
        $('#formCPPT input[name="nadi"]').val(btn.data('nadi'));
        $('#formCPPT input[name="respirasi"]').val(btn.data('respirasi'));
        $('#formCPPT input[name="spo2"]').val(btn.data('spo2'));
        $('#formCPPT #gcs_total').val(btn.data('gcs'));
        $('#formCPPT select[name="kesadaran"]').val(btn.data('kesadaran'));
        $('#formCPPT textarea[name="keluhan"]').val(btn.data('keluhan'));
        $('#formCPPT textarea[name="pemeriksaan"]').val(btn.data('pemeriksaan'));
        $('#formCPPT textarea[name="penilaian"]').val(btn.data('penilaian'));
        $('#formCPPT textarea[name="rtl"]').val(btn.data('rtl'));
        $('#formCPPT textarea[name="instruksi"]').val(btn.data('instruksi'));
        $('#formCPPT textarea[name="evaluasi"]').val(btn.data('evaluasi'));
        // Ubah UI tombol ke mode Edit
        $('#mode-status').html('<span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-edit me-1"></i>MODE EDIT CPPT &mdash; ' + btn.data('tgl') + ' ' + btn.data('jam') + '</span>');
        $('#btnSimpanCPPT').html('<i class="fas fa-save"></i> Perbarui CPPT').removeClass('btn-primary').addClass('btn-warning text-dark');
        if ($('#btnBatalEditCPPT').length === 0) {
            $('#btnSimpanCPPT').before('<button type="button" id="btnBatalEditCPPT" class="btn btn-secondary btn-sm px-3 me-2"><i class="fas fa-times"></i> Batal Edit</button>');
            $('#btnBatalEditCPPT').on('click', function() {
                $('#formCPPT input[name="aksi_nyata"]').val('simpan');
                $('#formCPPT input[name="tgl_perawatan"], #formCPPT input[name="jam_rawat"]').remove();
                $('#mode-status').html('<span class="badge bg-success">Mode: Input Baru (Copas TTV Terakhir)</span>');
                $('#btnSimpanCPPT').html('<i class="fas fa-save"></i> Simpan CPPT Ranap').removeClass('btn-warning text-dark').addClass('btn-primary');
                $('#formCPPT')[0].reset();
                $(this).remove();
            });
        }
        $('#leftTabContent').animate({ scrollTop: 0 }, 400);
    });

    $('.btn-hapus-cppt').off('click').on('click', function() {
        var btn = $(this);
        if (!confirm('PERINGATAN!\n\nApakah Anda yakin ingin MENGHAPUS CPPT ini?\nData yang dihapus TIDAK dapat dikembalikan.')) return;
        var oriHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        // Deteksi modul saat ini dari URL browser
        var current_module = window.location.href.includes('/ranap/') ? 'ranap' : 'ralan';
        
        $.ajax({
            url: '<?= $base_url ?>modules/edokter/' + current_module + '/proses.php?act=hapus_cppt',
            type: 'POST',
            data: { no_rawat: noRawat, tgl_perawatan: btn.data('tgl'), jam_rawat: btn.data('jam') },
            dataType: 'json',
            success: function(res) {
                if (res.status == 'success') {
                    refreshRiwayat();
                } else {
                    btn.prop('disabled', false).html(oriHtml);
                    alert('Gagal menghapus: ' + res.message);
                }
            },
            error: function() {
                btn.prop('disabled', false).html(oriHtml);
                alert('Terjadi kesalahan koneksi.');
            }
        });
    });
});

function refreshRiwayat() {
    var filterMode = $('#cpptFilterMode').val();
    var tglAwal = $('#cpptTglAwal').val();
    var tglAkhir = $('#cpptTglAkhir').val();
    
    var url = 'api/riwayat/view_cppt.php';
    var noRawat = '<?= htmlspecialchars($no_rawat, ENT_QUOTES) ?>';
    
    var $target = $('#tab-cppt-content'); // Use specific container if possible
    if($target.length === 0) $target = $('#tab-history');

    $target.html('<div class="text-center mt-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted small">Memuat riwayat...</p></div>');
    $.post(url, { 
        no_rawat: noRawat,
        filter_mode: filterMode, 
        tgl_awal: tglAwal, 
        tgl_akhir: tglAkhir
    }, function(data) {
        $target.html(data);
    });
}
</script>