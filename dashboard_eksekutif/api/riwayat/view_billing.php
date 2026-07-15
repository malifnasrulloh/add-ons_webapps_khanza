<?php
// File: modules/ranap/ajax/view_billing.php
// Deskripsi: Rincian Billing (Replikasi 100% data_rincian_billing.php V29)

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

ini_set('display_errors', 0);
error_reporting(0);

// --- HELPER FUNCTIONS ---
function safeFloat($val) {
    if (is_null($val) || $val === '') return 0.0;
    return (float)$val;
}

function formatRupiah($val) {
    return "Rp " . number_format($val, 0, ',', '.');
}

function fetchOneSafe($koneksi_pdo, $sql, $params = []) {
    try {
        $stmt = $koneksi_pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return false; }
}

function fetchAllSafe($koneksi_pdo, $sql, $params = []) {
    try {
        $stmt = $koneksi_pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
}

$no_rawat = isset($_POST['no_rawat']) ? $_POST['no_rawat'] : '';
if (empty($no_rawat)) { echo '<div class="alert alert-danger">No Rawat kosong</div>'; exit; }

// --- VARIABEL AKUMULATOR (Sesuai V29) ---
$sum_kamar = 0; $sum_reg = 0; 
$sum_dr_ralan = 0; $sum_pr_ralan = 0;
$sum_dr_ranap = 0; $sum_pr_ranap = 0;
$sum_lab = 0; $sum_rad = 0; $sum_op = 0; $sum_obat = 0; 
$sum_retur = 0; $sum_tambah = 0; $sum_potong = 0; $sum_harian = 0;

$rincian = []; // Array untuk menampung baris
$grand_total = 0;

try {
    // 1. SETTING JAM MINIMAL
    $setting_kamar = ['hariawal' => 'no', 'lamajam' => 0]; 
    $r_jam = fetchOneSafe($koneksi_pdo, "SELECT hariawal, lamajam FROM set_jam_minimal LIMIT 1");
    if($r_jam) $setting_kamar = $r_jam;

    // 2. INFO PASIEN
    $pasien = fetchOneSafe($koneksi_pdo, "SELECT p.nm_pasien, p.no_rkm_medis, rp.status_lanjut, rp.kd_pj, ki.tgl_masuk, ki.jam_masuk, b.nm_bangsal, pj.png_jawab 
        FROM reg_periksa rp 
        JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis 
        LEFT JOIN kamar_inap ki ON rp.no_rawat = ki.no_rawat 
        LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar 
        LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal 
        LEFT JOIN penjab pj ON rp.kd_pj = pj.kd_pj
        WHERE rp.no_rawat = ? LIMIT 1", [$no_rawat]);
    
    $status_lanjut = $pasien['status_lanjut'] ?? 'Ralan';
    $kd_pj = $pasien['kd_pj'] ?? '-';

    // 3. SETTING PPN
    $pakai_ppn = false;
    $r_set = fetchOneSafe($koneksi_pdo, "SELECT tampilkan_ppnobat_ralan, tampilkan_ppnobat_ranap FROM set_nota LIMIT 1");
    if($r_set) {
        if($status_lanjut == 'Ralan' && $r_set['tampilkan_ppnobat_ralan'] == 'Yes') $pakai_ppn = true;
        else if($status_lanjut == 'Ranap' && $r_set['tampilkan_ppnobat_ranap'] == 'Yes') $pakai_ppn = true;
    }

    // --- MULAI HITUNG ---

    // A. REGISTRASI
    $reg = fetchOneSafe($koneksi_pdo, "SELECT rp.biaya_reg, b.nm_bangsal FROM reg_periksa rp LEFT JOIN kamar_inap ki ON rp.no_rawat = ki.no_rawat LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal WHERE rp.no_rawat = ?", [$no_rawat]);
    if ($reg) {
        if (!empty($reg['nm_bangsal'])) {
            $rincian[] = ['kategori' => 'Bangsal', 'nama' => ": " . $reg['nm_bangsal'], 'biaya' => 0, 'header' => true];
        }
        $val = safeFloat($reg['biaya_reg']);
        if ($val > 0) {
            $rincian[] = ['kategori' => 'Registrasi', 'nama' => 'Biaya Pendaftaran', 'biaya' => $val];
            $grand_total += $val;
            $sum_reg += $val;
        }
    }

    // B. DOKTER (Display Only)
    $rincian[] = ['kategori' => 'Dokter', 'nama' => ':', 'biaya' => 0, 'header' => true];
    $drs = fetchAllSafe($koneksi_pdo, "SELECT d.nm_dokter FROM rawat_inap_dr rid JOIN dokter d ON rid.kd_dokter = d.kd_dokter WHERE rid.no_rawat=? GROUP BY rid.kd_dokter UNION SELECT d.nm_dokter FROM rawat_jl_dr rjd JOIN dokter d ON rjd.kd_dokter = d.kd_dokter WHERE rjd.no_rawat=? GROUP BY rjd.kd_dokter", [$no_rawat, $no_rawat]);
    foreach($drs as $dr) {
        $rincian[] = ['kategori' => '', 'nama' => $dr['nm_dokter'], 'biaya' => 0, 'header' => true];
    }

    // C. KAMAR INAP
    $kamars = fetchAllSafe($koneksi_pdo, "SELECT k.kd_kamar, b.nm_bangsal, k.trf_kamar, ki.tgl_masuk, ki.jam_masuk, ki.tgl_keluar, ki.jam_keluar, ki.lama, ki.ttl_biaya FROM kamar_inap ki JOIN kamar k ON ki.kd_kamar = k.kd_kamar JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal WHERE ki.no_rawat = ?", [$no_rawat]);
    foreach($kamars as $r) {
        $tgl_masuk = $r['tgl_masuk'];
        $tgl_keluar = ($r['tgl_keluar'] != '0000-00-00') ? $r['tgl_keluar'] : date('Y-m-d');
        
        $d1 = new DateTime($tgl_masuk);
        $d2 = new DateTime($tgl_keluar);
        $hari_raw = $d2->diff($d1)->days;

        $hari = ($setting_kamar['hariawal'] == 'yes') ? $hari_raw + 1 : $hari_raw;
        if (safeFloat($r['ttl_biaya']) > 0 && safeFloat($r['lama']) > 0) $hari = safeFloat($r['lama']);

        $biaya_kamar = $hari * safeFloat($r['trf_kamar']);
        if ($biaya_kamar > 0 || $hari > 0) {
            $rincian[] = ['kategori' => 'Kamar Inap', 'nama' => "{$r['nm_bangsal']} ({$hari} Hari)", 'biaya' => $biaya_kamar];
            $grand_total += $biaya_kamar;
            $sum_kamar += $biaya_kamar;
        }

        // Biaya Sekali & Harian
        $kd = $r['kd_kamar'];
        $biaya_sekali = fetchAllSafe($koneksi_pdo, "SELECT nama_biaya, besar_biaya FROM biaya_sekali WHERE kd_kamar=?", [$kd]);
        foreach($biaya_sekali as $bs) {
            $val = safeFloat($bs['besar_biaya']);
            $rincian[] = ['kategori' => ' + Biaya Awal', 'nama' => $bs['nama_biaya'], 'biaya' => $val];
            $grand_total += $val;
            $sum_harian += $val;
        }

        $biaya_harian = fetchAllSafe($koneksi_pdo, "SELECT nama_biaya, besar_biaya FROM biaya_harian WHERE kd_kamar=?", [$kd]);
        foreach($biaya_harian as $bh) {
            $val = $hari * safeFloat($bh['besar_biaya']);
            $rincian[] = ['kategori' => ' + Biaya Harian', 'nama' => $bh['nama_biaya'], 'biaya' => $val];
            $grand_total += $val;
            $sum_harian += $val;
        }
    }

    // D. OBAT & BHP
    // 1. Tagihan Langsung (FIX NAMA KOLOM & STRING HARDCODED)
    $ol = fetchOneSafe($koneksi_pdo, "SELECT besar_tagihan FROM tagihan_obat_langsung WHERE no_rawat=?", [$no_rawat]);
    if ($ol) {
        $val = safeFloat($ol['besar_tagihan']);
        $rincian[] = ['kategori' => 'Obat & BHP', 'nama' => 'Tagihan Obat Langsung', 'biaya' => $val];
        $grand_total += $val;
        $sum_obat += $val;
    }

    // 2. Beri Obat Operasi
    $oop = fetchAllSafe($koneksi_pdo, "SELECT o.nm_obat, b.hargasatuan, b.jumlah, (b.hargasatuan * b.jumlah) as total FROM beri_obat_operasi b JOIN obatbhp_ok o ON b.kd_obat = o.kd_obat WHERE b.no_rawat=?", [$no_rawat]);
    foreach($oop as $r) {
        $val = safeFloat($r['total']);
        $rincian[] = ['kategori' => 'BHP Operasi', 'nama' => $r['nm_obat'], 'biaya' => $val];
        $grand_total += $val;
        $sum_obat += $val;
    }

    // 3. Detail Pemberian Obat
    $dpo = fetchAllSafe($koneksi_pdo, "SELECT d.nama_brng, dp.total FROM detail_pemberian_obat dp JOIN databarang d ON dp.kode_brng = d.kode_brng WHERE dp.no_rawat=?", [$no_rawat]);
    foreach($dpo as $r) {
        $val = safeFloat($r['total']);
        $rincian[] = ['kategori' => 'Obat/Alkes', 'nama' => $r['nama_brng'], 'biaya' => $val];
        $grand_total += $val;
        $sum_obat += $val;
    }

    // Retur Obat
    $returs = fetchAllSafe($koneksi_pdo, "SELECT d.nama_brng, r.jml, (r.jml * d.ralan) as total_estimasi FROM returpasien r JOIN databarang d ON r.kode_brng = d.kode_brng WHERE r.no_rawat=?", [$no_rawat]);
    foreach($returs as $r) {
        $val = safeFloat($r['total_estimasi']);
        $rincian[] = ['kategori' => 'Retur Obat', 'nama' => $r['nama_brng'], 'biaya' => -abs($val)];
        $grand_total -= abs($val);
        $sum_retur += abs($val);
    }

    // PPN
    if ($pakai_ppn) {
        $obat_bersih = $sum_obat - $sum_retur;
        if ($obat_bersih > 0) {
            $ppn = round($obat_bersih * 0.11);
            $rincian[] = ['kategori' => 'PPN Obat', 'nama' => 'PPN 11% (Obat - Retur)', 'biaya' => $ppn];
            $grand_total += $ppn;
        }
    }

    // E. TINDAKAN (UNION QUERY - SPLIT RALAN/RANAP)
    $sql_tind = "
        SELECT 'Ralan Dokter' as kat, j.nm_perawatan, t.biaya_rawat as total, 1 as jml FROM rawat_jl_dr t JOIN jns_perawatan j ON t.kd_jenis_prw=j.kd_jenis_prw WHERE t.no_rawat=?
        UNION ALL SELECT 'Ralan Paramedis', j.nm_perawatan, t.biaya_rawat, 1 FROM rawat_jl_pr t JOIN jns_perawatan j ON t.kd_jenis_prw=j.kd_jenis_prw WHERE t.no_rawat=?
        UNION ALL SELECT 'Ralan Dr+Pr', j.nm_perawatan, t.biaya_rawat, 1 FROM rawat_jl_drpr t JOIN jns_perawatan j ON t.kd_jenis_prw=j.kd_jenis_prw WHERE t.no_rawat=?
        UNION ALL SELECT 'Ranap Dokter', j.nm_perawatan, t.biaya_rawat, 1 FROM rawat_inap_dr t JOIN jns_perawatan_inap j ON t.kd_jenis_prw=j.kd_jenis_prw WHERE t.no_rawat=?
        UNION ALL SELECT 'Ranap Paramedis', j.nm_perawatan, t.biaya_rawat, 1 FROM rawat_inap_pr t JOIN jns_perawatan_inap j ON t.kd_jenis_prw=j.kd_jenis_prw WHERE t.no_rawat=?
        UNION ALL SELECT 'Ranap Dr+Pr', j.nm_perawatan, t.biaya_rawat, 1 FROM rawat_inap_drpr t JOIN jns_perawatan_inap j ON t.kd_jenis_prw=j.kd_jenis_prw WHERE t.no_rawat=?
        UNION ALL SELECT 'Laboratorium', j.nm_perawatan, t.biaya, 1 FROM periksa_lab t JOIN jns_perawatan_lab j ON t.kd_jenis_prw=j.kd_jenis_prw WHERE t.no_rawat=?
        UNION ALL SELECT 'Radiologi', j.nm_perawatan, t.biaya, 1 FROM periksa_radiologi t JOIN jns_perawatan_radiologi j ON t.kd_jenis_prw=j.kd_jenis_prw WHERE t.no_rawat=?
    ";
    // Parameter diulang 8 kali
    $params_tind = array_fill(0, 8, $no_rawat);
    $tindakan = fetchAllSafe($koneksi_pdo, $sql_tind, $params_tind);

    foreach($tindakan as $r) {
        $val = safeFloat($r['total']);
        $rincian[] = ['kategori' => $r['kat'], 'nama' => $r['nm_perawatan'], 'biaya' => $val];
        $grand_total += $val;

        // Akumulasi Pintar untuk Service Charge
        $kat = strtolower($r['kat']);
        if (strpos($kat, 'lab') !== false) $sum_lab += $val;
        else if (strpos($kat, 'radiologi') !== false) $sum_rad += $val;
        // Ralan
        else if (strpos($kat, 'ralan') !== false) {
            if (strpos($kat, 'dokter') !== false) $sum_dr_ralan += $val;
            else if (strpos($kat, 'paramedis') !== false) $sum_pr_ralan += $val;
            else $sum_dr_ralan += $val; // Dr+Pr
        }
        // Ranap
        else if (strpos($kat, 'ranap') !== false) {
            if (strpos($kat, 'dokter') !== false) $sum_dr_ranap += $val;
            else if (strpos($kat, 'paramedis') !== false) $sum_pr_ranap += $val;
            else $sum_dr_ranap += $val; // Dr+Pr
        }
    }

    // F. OPERASI (Loop Komponen)
    $ops = fetchAllSafe($koneksi_pdo, "SELECT p.nm_perawatan, o.* FROM operasi o JOIN paket_operasi p ON o.kode_paket = p.kode_paket WHERE o.no_rawat=?", [$no_rawat]);
    foreach($ops as $r) {
        $rincian[] = ['kategori' => 'Operasi', 'nama' => $r['nm_perawatan'], 'biaya' => 0, 'header' => true];
        $komponen = ['biayaoperator1','biayaoperator2','biayaoperator3','biayaasisten_operator1','biayaasisten_operator2','biayadokter_anestesi','biayaasisten_anestesi','biayasewaok','biayaalat','akomodasi','bagian_rs','biaya_omloop','biayasarpras','biaya_dokter_anak','biayaperawaat_resusitas','biayabidan'];
        foreach($komponen as $k) {
            if (safeFloat($r[$k]) > 0) {
                $val = safeFloat($r[$k]);
                $rincian[] = ['kategori' => ' - Komponen', 'nama' => $k, 'biaya' => $val];
                $grand_total += $val;
                $sum_op += $val;
            }
        }
    }

    // G. TAMBAHAN & POTONGAN
    $adds = fetchAllSafe($koneksi_pdo, "SELECT nama_biaya, besar_biaya FROM tambahan_biaya WHERE no_rawat=?", [$no_rawat]);
    foreach($adds as $r) {
        $val = safeFloat($r['besar_biaya']);
        $rincian[] = ['kategori' => 'Tambahan', 'nama' => $r['nama_biaya'], 'biaya' => $val];
        $grand_total += $val;
        $sum_tambah += $val;
    }

    $mins = fetchAllSafe($koneksi_pdo, "SELECT nama_pengurangan, besar_pengurangan FROM pengurangan_biaya WHERE no_rawat=?", [$no_rawat]);
    foreach($mins as $r) {
        $val = safeFloat($r['besar_pengurangan']);
        $rincian[] = ['kategori' => 'Potongan', 'nama' => $r['nama_pengurangan'], 'biaya' => -abs($val)];
        $grand_total -= abs($val);
        $sum_potong += abs($val);
    }

    // H. JASA ADMINISTRASI (LOGIKA V29)
    if ($status_lanjut == 'Ranap') {
        $tabel_service = ($kd_pj != '-' && $kd_pj != 'UMUM' && $kd_pj != 'A01') ? 'set_service_ranap_piutang' : 'set_service_ranap';
        $s = fetchOneSafe($koneksi_pdo, "SELECT * FROM $tabel_service LIMIT 1");
        
        if ($s) {
            $total_basis = 0;
            if($s['laborat'] == 'Yes') $total_basis += $sum_lab;
            if($s['radiologi'] == 'Yes') $total_basis += $sum_rad;
            if($s['operasi'] == 'Yes') $total_basis += $sum_op;
            if($s['obat'] == 'Yes') $total_basis += ($sum_obat - $sum_retur);
            
            if($s['ranap_dokter'] == 'Yes') $total_basis += $sum_dr_ranap;
            if($s['ranap_paramedis'] == 'Yes') $total_basis += $sum_pr_ranap;
            if($s['ralan_dokter'] == 'Yes') $total_basis += $sum_dr_ralan;
            if($s['ralan_paramedis'] == 'Yes') $total_basis += $sum_pr_ralan;
            
            if($s['tambahan'] == 'Yes') $total_basis += $sum_tambah;
            if($s['potongan'] == 'Yes') $total_basis += $sum_potong; 
            if($s['kamar'] == 'Yes') $total_basis += $sum_kamar;
            if($s['registrasi'] == 'Yes') $total_basis += $sum_reg;
            if($s['harian'] == 'Yes') $total_basis += $sum_harian;

            $persen = safeFloat($s['besar']);
            if ($total_basis > 0 && $persen > 0) {
                $biaya_jasa = round($total_basis * ($persen / 100));
                
                // Cek Double di Billing
                $cek = fetchOneSafe($koneksi_pdo, "SELECT totalbiaya FROM billing WHERE no_rawat=? AND (nm_perawatan LIKE '%Administrasi%' OR nm_perawatan LIKE '%Service%')", [$no_rawat]);
                if (!$cek) {
                    $rincian[] = ['kategori' => 'Jasa Admin', 'nama' => $s['nama_service'] . " ($persen%)", 'biaya' => $biaya_jasa];
                    $grand_total += $biaya_jasa;
                } else {
                    // Jika sudah ada di billing, biasanya sudah terhitung di komponen lain (atau tidak perlu ditambah lagi)
                    // Tapi untuk view detail, kita tampilkan realnya dari billing jika mau
                    $rincian[] = ['kategori' => 'Jasa Admin', 'nama' => 'Real (Sudah Input)', 'biaya' => safeFloat($cek['totalbiaya'])];
                    $grand_total += safeFloat($cek['totalbiaya']);
                }
            }
        }
    }

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    exit;
}

?>

<style>
/* Dark Mode compatibility styles for billing view */
body.dark-mode .table-secondary,
body.dark-mode .table-secondary td,
body.dark-mode .table-secondary th,
body.dark-mode .table-striped > tbody > tr.table-secondary > td,
body.dark-mode .table-striped > tbody > tr.table-secondary > th,
body.dark-mode .table-striped > tbody > tr.table-secondary:nth-of-type(odd) > td,
body.dark-mode .table-striped > tbody > tr.table-secondary:nth-of-type(even) > td {
    background-color: #1e293b !important;
    color: #f8f9fa !important;
    --bs-table-accent-bg: #1e293b !important;
    --bs-table-striped-bg: #1e293b !important;
    --bs-table-bg: #1e293b !important;
    --bs-table-color: #f8f9fa !important;
}
body.dark-mode .table-light,
body.dark-mode .table-light td,
body.dark-mode .table-light th,
body.dark-mode .table-striped > tbody > tr.table-light > td,
body.dark-mode .table-striped > tbody > tr.table-light > th,
body.dark-mode .table-striped > tbody > tr.table-light:nth-of-type(odd) > td,
body.dark-mode .table-striped > tbody > tr.table-light:nth-of-type(even) > td,
body.dark-mode tfoot.table-light td,
body.dark-mode tfoot.table-light th {
    background-color: #1e293b !important;
    color: #f8f9fa !important;
    --bs-table-accent-bg: #1e293b !important;
    --bs-table-striped-bg: #1e293b !important;
    --bs-table-bg: #1e293b !important;
    --bs-table-color: #f8f9fa !important;
}
body.dark-mode .alert-primary {
    background-color: rgba(13, 110, 253, 0.15) !important;
    border-color: rgba(13, 110, 253, 0.4) !important;
    color: #f8f9fa !important;
}
body.dark-mode .alert-primary .text-primary {
    color: #60a5fa !important;
}
body.dark-mode .alert-primary .text-muted {
    color: #94a3b8 !important;
}
body.dark-mode .badge.bg-white {
    background-color: #334155 !important;
    color: #f8f9fa !important;
    border-color: #475569 !important;
}
.billing-card {
    background-color: #ffffff;
}
body.dark-mode .billing-card {
    background-color: #1e293b !important;
    border-color: #334155 !important;
}
.billing-card-value {
    color: #212529;
}
body.dark-mode .billing-card-value {
    color: #f8f9fa;
}
</style>

<div class="container-fluid p-3">
    <div class="alert alert-primary py-2 px-3 mb-3 border-start border-4 border-primary shadow-sm">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="fw-bold mb-0 text-primary"><?= $pasien['nm_pasien'] ?></h5>
                <small class="text-muted">RM: <?= $pasien['no_rkm_medis'] ?> | Rawat: <?= $no_rawat ?></small>
            </div>
            <div class="col-md-6 text-end">
                <span class="badge bg-white text-dark border"><?= $pasien['nm_bangsal'] ?></span>
                <span class="badge bg-success"><?= $pasien['png_jawab'] ?></span>
                <a href="../../../helpers/cetak_nota_sementara.php?no_rawat=<?= urlencode($no_rawat) ?>" target="_blank" class="btn btn-sm btn-dark ms-2 shadow-sm"><i class="fas fa-print me-1"></i> Cetak Nota Sementara</a>
            </div>
        </div>
    </div>

    <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
        <table class="table table-bordered table-striped table-hover table-sm small align-middle">
            <thead class="table-dark sticky-top">
                <tr>
                    <th>Kategori</th>
                    <th>Nama Tagihan</th>
                    <th class="text-end" width="150">Biaya</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($rincian as $r): ?>
                    <?php if(isset($r['header'])): ?>
                        <tr class="table-secondary fw-bold">
                            <td colspan="3"><?= $r['kategori'] ?> <?= $r['nama'] ?></td>
                        </tr>
                    <?php else: ?>
                        <tr class="<?= ($r['biaya'] < 0) ? 'text-danger' : '' ?>">
                            <td><?= $r['kategori'] ?></td>
                            <td><?= $r['nama'] ?></td>
                            <td class="text-end font-monospace"><?= formatRupiah($r['biaya']) ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light sticky-bottom fw-bold fs-6">
                <tr>
                    <td colspan="2" class="text-end">TOTAL TAGIHAN</td>
                    <td class="text-end text-primary"><?= formatRupiah($grand_total) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php
    // --- SUSUN KOMPONEN RINGKASAN ---
    $obat_bersih    = $sum_obat - $sum_retur;
    $ranap_dr       = $sum_dr_ranap;
    $ranap_pr       = $sum_pr_ranap;
    $ralan_dr       = $sum_dr_ralan;
    $ralan_pr       = $sum_pr_ralan;

    $komponen_ringkasan = [
        ['icon'=>'fa-pills',            'label'=>'Obat / Alkes',       'color'=>'#6f42c1', 'nilai'=>$obat_bersih],
        ['icon'=>'fa-user-md',          'label'=>'Ranap Dokter',       'color'=>'#0d6efd', 'nilai'=>$ranap_dr],
        ['icon'=>'fa-user-nurse',       'label'=>'Ranap Paramedis',    'color'=>'#0dcaf0', 'nilai'=>$ranap_pr],
        ['icon'=>'fa-stethoscope',      'label'=>'Ralan Dokter',       'color'=>'#20c997', 'nilai'=>$ralan_dr],
        ['icon'=>'fa-syringe',          'label'=>'Ralan Paramedis',    'color'=>'#198754', 'nilai'=>$ralan_pr],
        ['icon'=>'fa-flask',            'label'=>'Laboratorium',        'color'=>'#fd7e14', 'nilai'=>$sum_lab],
        ['icon'=>'fa-x-ray',            'label'=>'Radiologi',           'color'=>'#ffc107', 'nilai'=>$sum_rad],
        ['icon'=>'fa-cut',              'label'=>'Operasi',             'color'=>'#dc3545', 'nilai'=>$sum_op],
        ['icon'=>'fa-bed',              'label'=>'Kamar Inap',          'color'=>'#6c757d', 'nilai'=>$sum_kamar + $sum_harian],
        ['icon'=>'fa-plus-circle',      'label'=>'Tambahan',            'color'=>'#495057', 'nilai'=>$sum_tambah],
        ['icon'=>'fa-minus-circle',     'label'=>'Potongan',            'color'=>'#adb5bd', 'nilai'=>-$sum_potong],
        ['icon'=>'fa-file-invoice',     'label'=>'Retur Obat',         'color'=>'#e83e8c', 'nilai'=>-$sum_retur],
    ];
    // Filter hanya yang nilainya non-zero
    $komponen_aktif = array_filter($komponen_ringkasan, function($k) { return abs($k['nilai']) > 0; });
    ?>

    <?php if (!empty($komponen_aktif) && $grand_total > 0): ?>
    <div class="mt-3">
        <div class="d-flex align-items-center mb-2">
            <span class="fw-bold text-secondary me-2" style="font-size:0.85rem;"><i class="fas fa-chart-pie me-1"></i>Ringkasan Komponen Biaya</span>
            <small class="text-muted">(terhadap total tagihan)</small>
        </div>

        <!-- Stacked bar proporsi -->
        <div class="d-flex rounded overflow-hidden mb-3" style="height:14px;">
        <?php foreach($komponen_aktif as $k): ?>
            <?php if($k['nilai'] <= 0) continue; ?>
            <?php $pct = round(($k['nilai'] / $grand_total) * 100, 1); ?>
            <div style="width:<?= $pct ?>%; background:<?= $k['color'] ?>; min-width:<?= ($pct>0?2:0) ?>px;"
                 title="<?= htmlspecialchars($k['label']) ?>: <?= $pct ?>%"
                 data-bs-toggle="tooltip"></div>
        <?php endforeach; ?>
        </div>

        <!-- Grid kartu komponen -->
        <div class="row g-2">
        <?php foreach($komponen_aktif as $k): ?>
            <?php
                $pct_val = ($grand_total > 0) ? round((abs($k['nilai']) / $grand_total) * 100, 1) : 0;
                $is_minus = ($k['nilai'] < 0);
                $bar_pct  = min($pct_val, 100);
            ?>
             <div class="col-6 col-md-4">
                <div class="border rounded p-2 billing-card" style="border-left: 4px solid <?= $k['color'] ?> !important;">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="small fw-semibold text-truncate" style="color:<?= $k['color'] ?>; font-size:0.78rem;">
                            <i class="fas <?= $k['icon'] ?> me-1"></i><?= $k['label'] ?>
                        </span>
                        <span class="badge rounded-pill ms-1 flex-shrink-0" style="background:<?= $k['color'] ?>; font-size:0.7rem;"><?= $pct_val ?>%</span>
                    </div>
                    <div class="fw-bold font-monospace billing-card-value" style="font-size:0.82rem; color:<?= $is_minus ? '#dc3545' : '' ?>;">
                        <?= ($is_minus ? '-' : '') . formatRupiah(abs($k['nilai'])) ?>
                    </div>
                    <div class="mt-1" style="height:4px; background:#e9ecef; border-radius:2px;">
                        <div style="height:100%; width:<?= $bar_pct ?>%; background:<?= $k['color'] ?>; border-radius:2px;"></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
<script>
// Init tooltips untuk stacked bar
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el){
    new bootstrap.Tooltip(el, { trigger: 'hover' });
});
</script>