<?php
/*
 * File: api/data_rincian_billing.php (SECURITY HARDENED - PDO)
 * - Fix: Memisahkan akumulator Ralan vs Ranap agar tidak double counting.
 * - Fix: Menggunakan persentase dinamis dari tabel set_service_ranap.
 */

ob_start();
ini_set('display_errors', 0);
error_reporting(0);
set_time_limit(0);

header('Content-Type: application/json; charset=utf-8');
require_once(dirname(__DIR__) . '/config/koneksi.php'); 

$no_rawat = isset($_GET['no_rawat']) ? trim($_GET['no_rawat']) : '';
$rows = [];
$grand_total = 0.0;

// --- 1. VARIABEL AKUMULATOR TERPISAH (PENTING!) ---
$sum_kamar = 0; $sum_reg = 0; 
$sum_dr_ralan = 0; $sum_pr_ralan = 0; // Ralan
$sum_dr_ranap = 0; $sum_pr_ranap = 0; // Ranap
$sum_lab = 0; $sum_rad = 0; $sum_op = 0; $sum_obat = 0; 
$sum_retur = 0; $sum_tambah = 0; $sum_potong = 0; $sum_harian = 0;

function textToUtf8($str) {
    if (is_null($str)) return "";
    $str = (string)$str;
    if (function_exists('mb_convert_encoding')) return mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
    return @utf8_encode($str); // Note: utf8_encode is deprecated in PHP 8.2+, but safe for 7.3
}

function safeFloat($val) {
    if (is_null($val) || $val === '') return 0.0;
    return (float)$val;
}

function sendResponse($data) {
    ob_end_clean();
    echo json_encode($data, JSON_INVALID_UTF8_IGNORE);
    exit;
}

function addRow(&$rows, &$grand_total, $keterangan, $tagihan, $biaya, $jumlah, $tambahan, $total, $is_header = false) {
    $rows[] = [
        'keterangan' => textToUtf8($keterangan),
        'tagihan'    => textToUtf8($tagihan),
        'biaya'      => safeFloat($biaya),
        'jumlah'     => safeFloat($jumlah),
        'tambahan'   => safeFloat($tambahan),
        'total'      => safeFloat($total),
        'is_header'  => $is_header
    ];
    if (!$is_header) $grand_total += safeFloat($total);
}

if(empty($no_rawat)) sendResponse(['data' => [], 'total_rupiah' => 0]);

try {
    // CEK SETTING JAM MINIMAL (KAMAR)
    $setting_kamar = ['hariawal' => 'no', 'lamajam' => 0]; 
    try {
        $stmt = $koneksi_pdo->query("SELECT hariawal, lamajam FROM set_jam_minimal LIMIT 1");
        if($r_jam = $stmt->fetch(PDO::FETCH_ASSOC)) $setting_kamar = $r_jam;
    } catch (PDOException $e) {}

    // CEK INFO PASIEN
    $status_lanjut = 'Ralan'; 
    $kd_pj = '-';
    try {
        $stmt = $koneksi_pdo->prepare("SELECT status_lanjut, kd_pj FROM reg_periksa WHERE no_rawat = :no_rawat");
        $stmt->execute([':no_rawat' => $no_rawat]);
        if($r_info = $stmt->fetch(PDO::FETCH_ASSOC)){
            $status_lanjut = $r_info['status_lanjut'];
            $kd_pj = $r_info['kd_pj'];
        }
    } catch (PDOException $e) {}

    // CEK PPN
    $pakai_ppn = false;
    try {
        $stmt = $koneksi_pdo->query("SELECT tampilkan_ppnobat_ralan, tampilkan_ppnobat_ranap FROM set_nota LIMIT 1");
        if($r_set = $stmt->fetch(PDO::FETCH_ASSOC)){
            if($status_lanjut == 'Ralan' && $r_set['tampilkan_ppnobat_ralan'] == 'Yes') $pakai_ppn = true;
            else if($status_lanjut == 'Ranap' && $r_set['tampilkan_ppnobat_ranap'] == 'Yes') $pakai_ppn = true;
        }
    } catch (PDOException $e) {}

    // A. REGISTRASI
    try {
        $stmt = $koneksi_pdo->prepare("SELECT rp.biaya_reg, k.kd_kamar, b.nm_bangsal FROM reg_periksa rp LEFT JOIN kamar_inap ki ON rp.no_rawat = ki.no_rawat LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal WHERE rp.no_rawat = :no_rawat LIMIT 1");
        $stmt->execute([':no_rawat' => $no_rawat]);
        if($r = $stmt->fetch(PDO::FETCH_ASSOC)){
            if(!empty($r['nm_bangsal'])) addRow($rows, $grand_total, "Bangsal/Kamar", ": " . $r['nm_bangsal'], 0, 0, 0, 0, true);
            if(safeFloat($r['biaya_reg']) > 0) {
                $val = safeFloat($r['biaya_reg']);
                addRow($rows, $grand_total, "Registrasi", "Biaya Pendaftaran", $val, 1, 0, $val);
                $sum_reg += $val;
            }
        }
    } catch (PDOException $e) {}

    // B. DOKTER
    try {
        $sql_dr = "SELECT d.nm_dokter FROM rawat_inap_dr rid JOIN dokter d ON rid.kd_dokter = d.kd_dokter WHERE rid.no_rawat = :no_rawat GROUP BY rid.kd_dokter UNION SELECT d.nm_dokter FROM rawat_jl_dr rjd JOIN dokter d ON rjd.kd_dokter = d.kd_dokter WHERE rjd.no_rawat = :no_rawat GROUP BY rjd.kd_dokter";
        $stmt = $koneksi_pdo->prepare($sql_dr);
        $stmt->execute([':no_rawat' => $no_rawat]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if(count($results) > 0){
            addRow($rows, $grand_total, "Dokter", ":", 0, 0, 0, 0, true);
            foreach($results as $r) addRow($rows, $grand_total, "", $r['nm_dokter'], 0, 0, 0, 0, true);
        }
    } catch (PDOException $e) {}

    // C. KAMAR INAP
    try {
        $stmt = $koneksi_pdo->prepare("SELECT k.kd_kamar, b.nm_bangsal, k.trf_kamar, ki.tgl_masuk, ki.jam_masuk, ki.tgl_keluar, ki.jam_keluar, ki.stts_pulang, ki.lama, ki.ttl_biaya FROM kamar_inap ki JOIN kamar k ON ki.kd_kamar = k.kd_kamar JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal WHERE ki.no_rawat = :no_rawat");
        $stmt->execute([':no_rawat' => $no_rawat]);
        while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tgl_masuk = $r['tgl_masuk'];
            $tgl_keluar = ($r['tgl_keluar'] != '0000-00-00') ? $r['tgl_keluar'] : date('Y-m-d');
            
            $d1 = new DateTime($tgl_masuk);
            $d2 = new DateTime($tgl_keluar);
            $diff = $d2->diff($d1);
            $hari_raw = $diff->days;

            if ($setting_kamar['hariawal'] == 'yes') {
                $hari = $hari_raw + 1;
            } else {
                $hari = $hari_raw; // Bisa 0 jika hari yang sama
            }
            
            if (safeFloat($r['ttl_biaya']) > 0 && safeFloat($r['lama']) > 0) $hari = safeFloat($r['lama']);

            $biaya_kamar = $hari * safeFloat($r['trf_kamar']);
            
            if($biaya_kamar > 0 || $hari > 0) {
                addRow($rows, $grand_total, "Kamar Inap", $r['nm_bangsal'], $r['trf_kamar'], $hari, 0, $biaya_kamar);
                $sum_kamar += $biaya_kamar;
            }

            $kd = $r['kd_kamar'];
            
            try {
                $stmt_s = $koneksi_pdo->prepare("SELECT nama_biaya, besar_biaya FROM biaya_sekali WHERE kd_kamar = :kd");
                $stmt_s->execute([':kd' => $kd]);
                while($rs = $stmt_s->fetch(PDO::FETCH_ASSOC)) {
                     $val = safeFloat($rs['besar_biaya']);
                     addRow($rows, $grand_total, "  + Biaya Awal", $rs['nama_biaya'], $val, 1, 0, $val);
                     $sum_harian += $val;
                }
            } catch (PDOException $e) {}

            try {
                $stmt_h = $koneksi_pdo->prepare("SELECT nama_biaya, besar_biaya FROM biaya_harian WHERE kd_kamar = :kd");
                $stmt_h->execute([':kd' => $kd]);
                while($rh = $stmt_h->fetch(PDO::FETCH_ASSOC)) {
                    $val = $hari * safeFloat($rh['besar_biaya']);
                    addRow($rows, $grand_total, "  + Biaya Harian", $rh['nama_biaya'], $rh['besar_biaya'], $hari, 0, $val);
                    $sum_harian += $val;
                }
            } catch (PDOException $e) {}
        }
    } catch (PDOException $e) {}

    // D. OBAT & BHP
    try {
        $stmt = $koneksi_pdo->prepare("SELECT besar_tagihan FROM tagihan_obat_langsung WHERE no_rawat = :no_rawat");
        $stmt->execute([':no_rawat' => $no_rawat]);
        if($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $val = safeFloat($r['besar_tagihan']);
            addRow($rows, $grand_total, "Obat & BHP", "Tagihan Obat Langsung", $val, 1, 0, $val);
            $sum_obat += $val;
        }
    } catch (PDOException $e) {}
    
    try {
        $stmt = $koneksi_pdo->prepare("SELECT o.nm_obat, b.hargasatuan, b.jumlah, (b.hargasatuan * b.jumlah) as total FROM beri_obat_operasi b JOIN obatbhp_ok o ON b.kd_obat = o.kd_obat WHERE b.no_rawat = :no_rawat");
        $stmt->execute([':no_rawat' => $no_rawat]);
        while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $val = safeFloat($r['total']);
            addRow($rows, $grand_total, "BHP Operasi", $r['nm_obat'], $r['hargasatuan'], $r['jumlah'], 0, $val);
            $sum_obat += $val;
        }
    } catch (PDOException $e) {}

    try {
        $stmt = $koneksi_pdo->prepare("SELECT d.nama_brng, dp.biaya_obat, dp.jml, (dp.embalase + dp.tuslah) as tambahan, dp.total FROM detail_pemberian_obat dp JOIN databarang d ON dp.kode_brng = d.kode_brng WHERE dp.no_rawat = :no_rawat");
        $stmt->execute([':no_rawat' => $no_rawat]);
        while($r = $stmt->fetch(PDO::FETCH_ASSOC)){
            $val = safeFloat($r['total']);
            addRow($rows, $grand_total, "Obat/Alkes", $r['nama_brng'], $r['biaya_obat'], $r['jml'], $r['tambahan'], $val);
            $sum_obat += $val;
        }
    } catch (PDOException $e) {}

    try {
        $stmt = $koneksi_pdo->prepare("SELECT d.nama_brng, r.jml, (r.jml * d.ralan) as total_estimasi FROM returpasien r JOIN databarang d ON r.kode_brng = d.kode_brng WHERE r.no_rawat = :no_rawat");
        $stmt->execute([':no_rawat' => $no_rawat]);
        while($r = $stmt->fetch(PDO::FETCH_ASSOC)){
            $val_total = safeFloat($r['total_estimasi']);
            addRow($rows, $grand_total, "Retur Obat", $r['nama_brng'], 0, $r['jml'], 0, (-1 * abs($val_total)));
            $sum_retur += abs($val_total);
        }
    } catch (PDOException $e) {}

    if ($pakai_ppn) {
        $obat_bersih = $sum_obat - $sum_retur;
        if ($obat_bersih > 0) {
            $ppn_rp = round($obat_bersih * 0.11); 
            addRow($rows, $grand_total, "PPN Obat", "PPN 11% (Obat - Retur)", $ppn_rp, 1, 0, $ppn_rp);
        }
    }

    // E. TINDAKAN (DIPISAH RALAN/RANAP AGAR TIDAK DOUBLE COUNTING)
    $tindakan_list = [
        ['rawat_jl_dr', 'Ralan Dokter', 'jns_perawatan', 'biaya_rawat'],
        ['rawat_jl_pr', 'Ralan Paramedis', 'jns_perawatan', 'biaya_rawat'],
        ['rawat_jl_drpr', 'Ralan Dr+Pr', 'jns_perawatan', 'biaya_rawat'],
        ['rawat_inap_dr', 'Ranap Dokter', 'jns_perawatan_inap', 'biaya_rawat'],
        ['rawat_inap_pr', 'Ranap Paramedis', 'jns_perawatan_inap', 'biaya_rawat'],
        ['rawat_inap_drpr', 'Ranap Dr+Pr', 'jns_perawatan_inap', 'biaya_rawat'],
        ['periksa_lab', 'Laboratorium', 'jns_perawatan_lab', 'biaya'],
        ['periksa_radiologi', 'Radiologi', 'jns_perawatan_radiologi', 'biaya']
    ];

    foreach ($tindakan_list as $t) {
        $tbl = $t[0]; $kat = $t[1]; $jt = $t[2]; $col = $t[3];
        try {
            $stmt = $koneksi_pdo->prepare("SELECT j.nm_perawatan, t.$col as biaya, t.$col as total FROM $tbl t JOIN $jt j ON t.kd_jenis_prw = j.kd_jenis_prw WHERE t.no_rawat = :no_rawat");
            $stmt->execute([':no_rawat' => $no_rawat]);
            while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $val = safeFloat($r['total']);
                addRow($rows, $grand_total, $kat, $r['nm_perawatan'], $r['biaya'], 1, 0, $val);
                
                $k = strtolower($kat);
                if(strpos($k, 'lab') !== false) $sum_lab += $val;
                else if(strpos($k, 'radiologi') !== false) $sum_rad += $val;
                // Ralan
                else if(strpos($k, 'ralan') !== false && strpos($k, 'dokter') !== false) $sum_dr_ralan += $val;
                else if(strpos($k, 'ralan') !== false && strpos($k, 'paramedis') !== false) $sum_pr_ralan += $val;
                else if(strpos($k, 'ralan') !== false && strpos($k, 'dr+pr') !== false) $sum_dr_ralan += $val; 
                // Ranap
                else if(strpos($k, 'ranap') !== false && strpos($k, 'dokter') !== false) $sum_dr_ranap += $val;
                else if(strpos($k, 'ranap') !== false && strpos($k, 'paramedis') !== false) $sum_pr_ranap += $val;
                else if(strpos($k, 'ranap') !== false && strpos($k, 'dr+pr') !== false) $sum_dr_ranap += $val; 
            }
        } catch (PDOException $e) {}
    }

    // F. OPERASI
    try {
        $stmt = $koneksi_pdo->prepare("SELECT p.nm_perawatan, o.* FROM operasi o JOIN paket_operasi p ON o.kode_paket = p.kode_paket WHERE o.no_rawat = :no_rawat");
        $stmt->execute([':no_rawat' => $no_rawat]);
        while($r = $stmt->fetch(PDO::FETCH_ASSOC)){
            addRow($rows, $grand_total, "Tindakan Operasi", $r['nm_perawatan'], 0, 0, 0, 0, true);
            $komponen = ['biayaoperator1','biayaoperator2','biayaoperator3','biayaasisten_operator1','biayaasisten_operator2','biayadokter_anestesi','biayaasisten_anestesi','biayasewaok','biayaalat','akomodasi','bagian_rs','biaya_omloop','biayasarpras','biaya_dokter_anak','biayaperawaat_resusitas','biayabidan'];
            foreach($komponen as $k) { 
                if(safeFloat($r[$k]) > 0) {
                    $val = safeFloat($r[$k]);
                    addRow($rows, $grand_total, " - Komponen", $k, $val, 1, 0, $val);
                    $sum_op += $val;
                }
            }
        }
    } catch (PDOException $e) {}

    // G. TAMBAHAN & POTONGAN
    try {
        $stmt = $koneksi_pdo->prepare("SELECT nama_biaya, besar_biaya FROM tambahan_biaya WHERE no_rawat = :no_rawat");
        $stmt->execute([':no_rawat' => $no_rawat]);
        while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $val = safeFloat($r['besar_biaya']);
            addRow($rows, $grand_total, "Biaya Tambahan", $r['nama_biaya'], $val, 1, 0, $val);
            $sum_tambah += $val;
        }
    } catch (PDOException $e) {}

    try {
        $stmt = $koneksi_pdo->prepare("SELECT nama_pengurangan, besar_pengurangan FROM pengurangan_biaya WHERE no_rawat = :no_rawat");
        $stmt->execute([':no_rawat' => $no_rawat]);
        while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $val = (-1 * abs(safeFloat($r['besar_pengurangan'])));
            addRow($rows, $grand_total, "Potongan Biaya", $r['nama_pengurangan'], $r['besar_pengurangan'], 1, 0, $val);
            $sum_potong += $val;
        }
    } catch (PDOException $e) {}

    // H. JASA ADMINISTRASI MEDIS
    if($status_lanjut == 'Ranap') {
        $tabel_service = 'set_service_ranap'; 
        if($kd_pj != '-' && $kd_pj != 'UMUM' && $kd_pj != 'A01') $tabel_service = 'set_service_ranap_piutang';
        
        try {
            $stmt = $koneksi_pdo->query("SELECT * FROM $tabel_service LIMIT 1");
            if($s = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
                if($total_basis > 0 && $persen > 0) {
                    $biaya_jasa = round($total_basis * ($persen / 100));
                    
                    $stmt_cek = $koneksi_pdo->prepare("SELECT totalbiaya FROM billing WHERE no_rawat = :no_rawat AND (nm_perawatan LIKE '%Administrasi%' OR nm_perawatan LIKE '%Service%')");
                    $stmt_cek->execute([':no_rawat' => $no_rawat]);
                    if($stmt_cek->rowCount() > 0) {
                         // Sudah ada di billing real, jangan double charge
                    } else {
                         addRow($rows, $grand_total, "Jasa Administrasi", $s['nama_service'] . " ($persen%)", $biaya_jasa, 1, 0, $biaya_jasa);
                    }
                }
            }
        } catch (PDOException $e) {}
    }

    sendResponse([
        'data' => $rows,
        'total_rupiah' => number_format((float)$grand_total, 0, ',', '.'),
        'total_raw' => $grand_total
    ]);

} catch (Exception $e) {
    sendResponse(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>