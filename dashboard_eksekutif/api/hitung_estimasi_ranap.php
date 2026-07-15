<?php
/*
 * File: api/hitung_estimasi_ranap.php (SECURITY HARDENED - PDO)
 * Fungsi: Menghitung estimasi biaya satu pasien Ranap secara on-demand (Lazy Loading).
 * Logika: PERSIS SAMA dengan blok kalkulasi di data_kunjungan_ranap.php (V9 PARITY)
 */

ob_start();
ini_set('display_errors', 0);
error_reporting(0);
set_time_limit(30);

header('Content-Type: application/json; charset=utf-8');
require_once(dirname(__DIR__) . '/config/koneksi.php');

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak.']);
    exit;
}

$no_rawat = isset($_GET['no_rawat']) ? trim($_GET['no_rawat']) : '';
$kd_pj    = isset($_GET['kd_pj'])    ? trim($_GET['kd_pj'])    : '-';

if (empty($no_rawat)) {
    ob_end_clean();
    echo json_encode(['error' => 'Parameter no_rawat kosong.']);
    exit;
}

// ===== HELPER =====
function safeFloat_r($val) {
    if (is_null($val) || $val === '') return 0.0;
    return (float)$val;
}

try {
    // ===== LOAD GLOBAL SETTINGS =====
    $setting_kamar = ['hariawal' => 'no', 'lamajam' => 0];
    try {
        $stmt = $koneksi_pdo->query("SELECT hariawal, lamajam FROM set_jam_minimal LIMIT 1");
        if ($r_jam = $stmt->fetch(PDO::FETCH_ASSOC)) $setting_kamar = $r_jam;
    } catch (PDOException $e) {}

    $tampilkan_ppn_ranap = false;
    try {
        $stmt = $koneksi_pdo->query("SELECT tampilkan_ppnobat_ranap FROM set_nota LIMIT 1");
        if ($r_set = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($r_set['tampilkan_ppnobat_ranap'] == 'Yes') $tampilkan_ppn_ranap = true;
        }
    } catch (PDOException $e) {}

    $service_umum = null; $service_piutang = null;
    try {
        $stmt = $koneksi_pdo->query("SELECT * FROM set_service_ranap LIMIT 1");
        if ($r_su = $stmt->fetch(PDO::FETCH_ASSOC)) $service_umum = $r_su;
    } catch (PDOException $e) {}
    
    try {
        $stmt = $koneksi_pdo->query("SELECT * FROM set_service_ranap_piutang LIMIT 1");
        if ($r_sp = $stmt->fetch(PDO::FETCH_ASSOC)) $service_piutang = $r_sp;
    } catch (PDOException $e) {}

    // ===== KALKULASI BIAYA =====
    $grand_total = 0.0;
    $sum_kamar = 0; $sum_reg = 0;
    $sum_dr_ralan = 0; $sum_pr_ralan = 0;
    $sum_dr_ranap = 0; $sum_pr_ranap = 0;
    $sum_lab = 0; $sum_rad = 0; $sum_op = 0; $sum_obat = 0;
    $sum_retur = 0; $sum_tambah = 0; $sum_potong = 0; $sum_harian = 0;

    // A. Registrasi
    try {
        $stmt = $koneksi_pdo->prepare("SELECT biaya_reg FROM reg_periksa WHERE no_rawat = :no_rawat");
        $stmt->execute([':no_rawat' => $no_rawat]);
        if ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $val = safeFloat_r($r['biaya_reg']);
            if ($val > 0) { $sum_reg += $val; $grand_total += $val; }
        }
    } catch (PDOException $e) {}

    // B. Kamar Inap (History Mode)
    try {
        $stmt = $koneksi_pdo->prepare("SELECT k.kd_kamar, k.trf_kamar, ki.tgl_masuk, ki.tgl_keluar, ki.lama, ki.ttl_biaya FROM kamar_inap ki JOIN kamar k ON ki.kd_kamar = k.kd_kamar WHERE ki.no_rawat = :no_rawat");
        $stmt->execute([':no_rawat' => $no_rawat]);
        while ($rhk = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tgl_masuk  = $rhk['tgl_masuk'];
            $tgl_keluar = ($rhk['tgl_keluar'] != '0000-00-00') ? $rhk['tgl_keluar'] : date('Y-m-d');
            $d1 = new DateTime($tgl_masuk); $d2 = new DateTime($tgl_keluar);
            $diff = $d2->diff($d1);
            $hari_raw = $diff->days;

            if ($setting_kamar['hariawal'] == 'yes') $hari = $hari_raw + 1;
            else $hari = $hari_raw;

            if (safeFloat_r($rhk['ttl_biaya']) > 0 && safeFloat_r($rhk['lama']) > 0) $hari = safeFloat_r($rhk['lama']);

            $biaya_satu_kamar = $hari * safeFloat_r($rhk['trf_kamar']);
            if ($biaya_satu_kamar > 0) { $sum_kamar += $biaya_satu_kamar; $grand_total += $biaya_satu_kamar; }

            $kd_k = $rhk['kd_kamar'];
            
            try {
                $stmt_bs = $koneksi_pdo->prepare("SELECT SUM(besar_biaya) as tot FROM biaya_sekali WHERE kd_kamar = :kd_kamar");
                $stmt_bs->execute([':kd_kamar' => $kd_k]);
                if ($row_bs = $stmt_bs->fetch(PDO::FETCH_ASSOC)) { 
                    $val = safeFloat_r($row_bs['tot']); 
                    $sum_harian += $val; 
                    $grand_total += $val; 
                }
            } catch (PDOException $e) {}

            try {
                $stmt_bh = $koneksi_pdo->prepare("SELECT SUM(besar_biaya) as tot FROM biaya_harian WHERE kd_kamar = :kd_kamar");
                $stmt_bh->execute([':kd_kamar' => $kd_k]);
                if ($row_bh = $stmt_bh->fetch(PDO::FETCH_ASSOC)) { 
                    $val = ($hari * safeFloat_r($row_bh['tot'])); 
                    $sum_harian += $val; 
                    $grand_total += $val; 
                }
            } catch (PDOException $e) {}
        }
    } catch (PDOException $e) {}

    // C. Operasi
    try {
        $stmt = $koneksi_pdo->prepare("SELECT * FROM operasi WHERE no_rawat = :no_rawat");
        $stmt->execute([':no_rawat' => $no_rawat]);
        while ($r_op = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $komponen = ['biayaoperator1','biayaoperator2','biayaoperator3','biayaasisten_operator1','biayaasisten_operator2','biayadokter_anestesi','biayaasisten_anestesi','biayasewaok','biayaalat','akomodasi','bagian_rs','biaya_omloop','biayasarpras','biaya_dokter_anak','biayaperawaat_resusitas','biayabidan'];
            foreach ($komponen as $k) {
                if (isset($r_op[$k])) { $val = safeFloat_r($r_op[$k]); $sum_op += $val; }
            }
        }
    } catch (PDOException $e) {}
    $grand_total += $sum_op;

    // D. Tindakan
    $tindakan_tables = [
        ['periksa_lab', 'biaya', 'lab'],
        ['periksa_radiologi', 'biaya', 'rad'],
        ['rawat_jl_dr', 'biaya_rawat', 'dr_ralan'],
        ['rawat_jl_pr', 'biaya_rawat', 'pr_ralan'],
        ['rawat_jl_drpr', 'biaya_rawat', 'dr_ralan'],
        ['rawat_inap_dr', 'biaya_rawat', 'dr_ranap'],
        ['rawat_inap_pr', 'biaya_rawat', 'pr_ranap'],
        ['rawat_inap_drpr', 'biaya_rawat', 'dr_ranap'],
        ['tambahan_biaya', 'besar_biaya', 'tambah'],
        ['pengurangan_biaya', 'besar_pengurangan', 'potong']
    ];

    foreach ($tindakan_tables as $t) {
        $tbl = $t[0]; $col = $t[1]; $grp = $t[2];
        try {
            $stmt = $koneksi_pdo->prepare("SELECT SUM($col) as tot FROM $tbl WHERE no_rawat = :no_rawat");
            $stmt->execute([':no_rawat' => $no_rawat]);
            if ($rt = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $val = safeFloat_r($rt['tot']);
                if ($val != 0) {
                    if ($grp == 'lab')      $sum_lab     += $val;
                    elseif ($grp == 'rad')  $sum_rad     += $val;
                    elseif ($grp == 'dr_ralan') $sum_dr_ralan += $val;
                    elseif ($grp == 'pr_ralan') $sum_pr_ralan += $val;
                    elseif ($grp == 'dr_ranap') $sum_dr_ranap += $val;
                    elseif ($grp == 'pr_ranap') $sum_pr_ranap += $val;
                    elseif ($grp == 'tambah')   $sum_tambah   += $val;
                    elseif ($grp == 'potong') { 
                        $sum_potong += (-1 * abs($val)); 
                        $grand_total += (-1 * abs($val)); 
                        continue; 
                    }
                    $grand_total += $val;
                }
            }
        } catch (PDOException $e) {}
    }

    // E. Obat & Retur
    $obat_tables = [
        ['detail_pemberian_obat', 'total'],
        ['tagihan_obat_langsung', 'besar_tagihan']
    ];
    foreach ($obat_tables as $t) {
        try {
            $stmt = $koneksi_pdo->prepare("SELECT SUM(" . $t[1] . ") as tot FROM " . $t[0] . " WHERE no_rawat = :no_rawat");
            $stmt->execute([':no_rawat' => $no_rawat]);
            if ($ro = $stmt->fetch(PDO::FETCH_ASSOC)) $sum_obat += safeFloat_r($ro['tot']);
        } catch (PDOException $e) {}
    }
    
    try {
        $stmt = $koneksi_pdo->prepare("SELECT SUM(hargasatuan * jumlah) as tot FROM beri_obat_operasi WHERE no_rawat = :no_rawat");
        $stmt->execute([':no_rawat' => $no_rawat]);
        if ($ro = $stmt->fetch(PDO::FETCH_ASSOC)) $sum_obat += safeFloat_r($ro['tot']);
    } catch (PDOException $e) {}

    $grand_total += $sum_obat;

    try {
        $stmt = $koneksi_pdo->prepare("SELECT SUM(r.jml * d.ralan) as tot FROM returpasien r JOIN databarang d ON r.kode_brng = d.kode_brng WHERE r.no_rawat = :no_rawat");
        $stmt->execute([':no_rawat' => $no_rawat]);
        if ($rr = $stmt->fetch(PDO::FETCH_ASSOC)) $sum_retur += abs(safeFloat_r($rr['tot']));
    } catch (PDOException $e) {}
    $grand_total -= $sum_retur;

    // F. PPN
    if ($tampilkan_ppn_ranap) {
        $obat_bersih = $sum_obat - $sum_retur;
        if ($obat_bersih > 0) $grand_total += round($obat_bersih * 0.11);
    }

    // G. Jasa Admin (Service)
    $s = null;
    if ($kd_pj != '-' && $kd_pj != 'UMUM' && $kd_pj != 'A01') $s = $service_piutang;
    else $s = $service_umum;

    if ($s) {
        $basis = 0;
        if ($s['laborat']          == 'Yes') $basis += $sum_lab;
        if ($s['radiologi']        == 'Yes') $basis += $sum_rad;
        if ($s['operasi']          == 'Yes') $basis += $sum_op;
        if ($s['obat']             == 'Yes') $basis += ($sum_obat - $sum_retur);
        if ($s['ranap_dokter']     == 'Yes') $basis += $sum_dr_ranap;
        if ($s['ranap_paramedis']  == 'Yes') $basis += $sum_pr_ranap;
        if ($s['ralan_dokter']     == 'Yes') $basis += $sum_dr_ralan;
        if ($s['ralan_paramedis']  == 'Yes') $basis += $sum_pr_ralan;
        if ($s['tambahan']         == 'Yes') $basis += $sum_tambah;
        if ($s['potongan']         == 'Yes') $basis += $sum_potong;
        if ($s['kamar']            == 'Yes') $basis += $sum_kamar;
        if ($s['registrasi']       == 'Yes') $basis += $sum_reg;
        if ($s['harian']           == 'Yes') $basis += $sum_harian;

        $persen = safeFloat_r($s['besar']);
        if ($basis > 0 && $persen > 0) {
            $jasa_admin = round($basis * ($persen / 100));
            
            try {
                $stmt = $koneksi_pdo->prepare("SELECT totalbiaya FROM billing WHERE no_rawat = :no_rawat AND (nm_perawatan LIKE '%Administrasi%' OR nm_perawatan LIKE '%Service%')");
                $stmt->execute([':no_rawat' => $no_rawat]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($rows) == 0) {
                    $grand_total += $jasa_admin;
                } else {
                    foreach ($rows as $row_bill) $grand_total += safeFloat_r($row_bill['totalbiaya']);
                }
            } catch (PDOException $e) {}
        }
    }

    // DPJP
    $dpjp = '';
    $is_dpjp_fallback = false;
    try {
        // First try: Get 1 DPJP (last ordered by kd_dokter DESC)
        $stmt = $koneksi_pdo->prepare("SELECT d.nm_dokter FROM dpjp_ranap dr JOIN dokter d ON dr.kd_dokter = d.kd_dokter WHERE dr.no_rawat = :no_rawat ORDER BY dr.kd_dokter DESC LIMIT 1");
        $stmt->execute([':no_rawat' => $no_rawat]);
        if ($rd = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dpjp = $rd['nm_dokter'];
        } else {
            // Second try: Fallback to reg_periksa doctor
            $is_dpjp_fallback = true;
            $stmt = $koneksi_pdo->prepare("SELECT d.nm_dokter FROM reg_periksa rp JOIN dokter d ON rp.kd_dokter = d.kd_dokter WHERE rp.no_rawat = :no_rawat LIMIT 1");
            $stmt->execute([':no_rawat' => $no_rawat]);
            if ($rd = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $dpjp = $rd['nm_dokter'];
            }
        }
    } catch (PDOException $e) {}

    // H. Plafon
    $plafon_val = 0;
    $has_plafon  = false;
    try {
        $stmt = $koneksi_pdo->prepare("SELECT tarif FROM perkiraan_biaya_ranap WHERE no_rawat = :no_rawat LIMIT 1");
        $stmt->execute([':no_rawat' => $no_rawat]);
        if ($r_plafon = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!is_null($r_plafon['tarif']) && $r_plafon['tarif'] !== '') {
                $plafon_val = safeFloat_r($r_plafon['tarif']);
                $has_plafon = ($plafon_val > 0);
            }
        }
    } catch (PDOException $e) {}

    $selisih_val = $grand_total - $plafon_val;
    $is_over     = ($has_plafon && $grand_total > $plafon_val);
    $pct         = ($has_plafon && $plafon_val > 0) ? min(100, round(($grand_total / $plafon_val) * 100)) : 0;

    ob_end_clean();
    echo json_encode([
        'no_rawat'         => $no_rawat,
        'estimasi'         => number_format($grand_total, 0, ',', '.'),
        'estimasi_raw'     => $grand_total,
        'plafon'           => $has_plafon ? ('Rp ' . number_format($plafon_val, 0, ',', '.')) : '-',
        'plafon_raw'       => $plafon_val,
        'has_plafon'       => $has_plafon,
        'selisih'          => $has_plafon ? ('Rp ' . number_format(abs($selisih_val), 0, ',', '.')) : '-',
        'selisih_raw'      => $has_plafon ? $selisih_val : null,
        'is_over'          => $is_over,
        'pct'              => $pct,
        'dpjp'             => $dpjp,
        'is_dpjp_fallback' => $is_dpjp_fallback,
    ]);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
}
?>