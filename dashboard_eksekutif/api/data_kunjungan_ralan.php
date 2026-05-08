<?php
/*
 * File: api/data_kunjungan_ralan.php (SECURITY HARDENED - PDO)
 * - Fitur: Menerima parameter mode=semua atau mode=periode.
 * - Security: Mencegah query data masa depan (Booking).
 */

ob_start();
ini_set('display_errors', 0);
error_reporting(0);
set_time_limit(0);

header('Content-Type: application/json; charset=utf-8');
require_once(dirname(__DIR__) . '/config/koneksi.php'); 

// --- HELPER ---
function safe_utf8($str) {
    if (is_null($str)) return '';
    if (function_exists('mb_convert_encoding')) return mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
    return $str; 
}

// --- SETTINGS ---
function getSettings($conn_pdo) {
    $settings = ['service_charge' => 0, 'ppn_obat' => false, 'components' => []];
    $stmt = $conn_pdo->query("SELECT tampilkan_ppnobat_ralan FROM set_nota LIMIT 1");
    if($r = $stmt->fetch(PDO::FETCH_ASSOC)) $settings['ppn_obat'] = ($r['tampilkan_ppnobat_ralan'] == 'Yes');

    $stmt = $conn_pdo->query("SELECT * FROM set_service_ranap LIMIT 1"); 
    if($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings['service_charge'] = (float)$r['besar'];
        $keys = ['laborat', 'radiologi', 'operasi', 'obat', 'ranap_dokter', 'ranap_paramedis', 'ralan_dokter', 'ralan_paramedis', 'tambahan', 'potongan', 'kamar', 'registrasi', 'harian', 'retur_Obat', 'resep_Pulang'];
        foreach($keys as $k) $settings['components'][$k] = ($r[$k] == 'Yes');
    }
    return $settings;
}

function hitungEstimasiAkurat($conn_pdo, $no_rawat, $settings) {
    $biaya = [
        'laborat' => 0, 'radiologi' => 0, 'operasi' => 0, 'obat' => 0,
        'ranap_dokter' => 0, 'ranap_paramedis' => 0, 'ralan_dokter' => 0, 'ralan_paramedis' => 0,
        'tambahan' => 0, 'potongan' => 0, 'kamar' => 0, 'registrasi' => 0,
        'harian' => 0, 'retur_Obat' => 0, 'resep_Pulang' => 0
    ];

    $stmt = $conn_pdo->prepare("SELECT biaya_reg FROM reg_periksa WHERE no_rawat = :no_rawat");
    $stmt->execute([':no_rawat' => $no_rawat]);
    if($r = $stmt->fetch(PDO::FETCH_ASSOC)) $biaya['registrasi'] += (float)$r['biaya_reg'];

    $stmt = $conn_pdo->prepare("SELECT SUM(total) as val FROM detail_pemberian_obat WHERE no_rawat = :no_rawat");
    $stmt->execute([':no_rawat' => $no_rawat]);
    if($r = $stmt->fetch(PDO::FETCH_ASSOC)) $biaya['obat'] += (float)$r['val'];
    
    $stmt = $conn_pdo->prepare("SELECT SUM(besar_tagihan) as val FROM tagihan_obat_langsung WHERE no_rawat = :no_rawat");
    $stmt->execute([':no_rawat' => $no_rawat]);
    if($r = $stmt->fetch(PDO::FETCH_ASSOC)) $biaya['obat'] += (float)$r['val'];

    $stmt = $conn_pdo->prepare("SELECT SUM(r.jml * d.ralan) as val FROM returpasien r JOIN databarang d ON r.kode_brng = d.kode_brng WHERE r.no_rawat = :no_rawat");
    $stmt->execute([':no_rawat' => $no_rawat]);
    if($r = $stmt->fetch(PDO::FETCH_ASSOC)) $biaya['retur_Obat'] += (float)$r['val'];

    $tables = [
        'rawat_jl_dr'=>'ralan_dokter', 'rawat_jl_pr'=>'ralan_paramedis', 'rawat_jl_drpr'=>'ralan_dokter', 
        'periksa_lab'=>'laborat', 'periksa_radiologi'=>'radiologi', 'penggunaan_darah_donor'=>'obat'
    ];
    foreach($tables as $tbl => $cat) {
        $col = (strpos($tbl, 'periksa_') !== false || $tbl == 'penggunaan_darah_donor') ? 'biaya' : 'biaya_rawat';
        try {
            $stmt = $conn_pdo->prepare("SELECT SUM($col) as val FROM $tbl WHERE no_rawat = :no_rawat");
            $stmt->execute([':no_rawat' => $no_rawat]);
            if($r = $stmt->fetch(PDO::FETCH_ASSOC)) $biaya[$cat] += (float)$r['val'];
        } catch (PDOException $e) {
            // Ignore missing tables
        }
    }

    $sql_op = "SELECT SUM(biayaoperator1+biayaoperator2+biayaoperator3+biayaasisten_operator1+biayaasisten_operator2+biayadokter_anestesi+biayaasisten_anestesi+biayaasisten_anestesi2+biayadokter_anak+biayaperawaat_resusitas+biayabidan+biayabidan2+biayabidan3+biayaperawat_luar+biayasewaok+biayaalat+akomodasi+bagian_rs+biaya_omloop+biaya_omloop2+biaya_omloop3+biaya_omloop4+biaya_omloop5+biayasarpras+biaya_dokter_pjanak+biaya_dokter_umum) as val FROM operasi WHERE no_rawat = :no_rawat";
    $stmt = $conn_pdo->prepare($sql_op);
    $stmt->execute([':no_rawat' => $no_rawat]);
    if($r = $stmt->fetch(PDO::FETCH_ASSOC)) $biaya['operasi'] += (float)$r['val'];

    $stmt = $conn_pdo->prepare("SELECT SUM(besar_biaya) as val FROM tambahan_biaya WHERE no_rawat = :no_rawat");
    $stmt->execute([':no_rawat' => $no_rawat]);
    if($r = $stmt->fetch(PDO::FETCH_ASSOC)) $biaya['tambahan'] += (float)$r['val'];
    
    $stmt = $conn_pdo->prepare("SELECT SUM(besar_pengurangan) as val FROM pengurangan_biaya WHERE no_rawat = :no_rawat");
    $stmt->execute([':no_rawat' => $no_rawat]);
    if($r = $stmt->fetch(PDO::FETCH_ASSOC)) $biaya['potongan'] += (float)$r['val'];

    $obat_bersih = $biaya['obat'] - $biaya['retur_Obat'];
    $ppn_rp = ($settings['ppn_obat'] && $obat_bersih > 0) ? $obat_bersih * 0.11 : 0;

    $service_base = 0;
    foreach ($settings['components'] as $key => $isActive) {
        if ($isActive && isset($biaya[$key])) {
            $service_base += ($key == 'retur_Obat') ? -($biaya[$key]) : $biaya[$key];
        }
    }
    $service_rp = ($service_base * $settings['service_charge']) / 100;

    return array_sum($biaya) - ($biaya['retur_Obat'] * 2) - ($biaya['potongan'] * 2) + $ppn_rp + $service_rp;
}

function hitungObatSaja($conn_pdo, $no_rawat) {
    $total_obat = 0;
    $stmt = $conn_pdo->prepare("SELECT SUM(total) as val FROM detail_pemberian_obat WHERE no_rawat = :no_rawat");
    $stmt->execute([':no_rawat' => $no_rawat]);
    if($r = $stmt->fetch(PDO::FETCH_ASSOC)) $total_obat += (float)$r['val'];
    
    $stmt = $conn_pdo->prepare("SELECT SUM(besar_tagihan) as val FROM tagihan_obat_langsung WHERE no_rawat = :no_rawat");
    $stmt->execute([':no_rawat' => $no_rawat]);
    if($r = $stmt->fetch(PDO::FETCH_ASSOC)) $total_obat += (float)$r['val'];
    
    $stmt = $conn_pdo->prepare("SELECT SUM(r.jml * d.ralan) as val FROM returpasien r JOIN databarang d ON r.kode_brng = d.kode_brng WHERE r.no_rawat = :no_rawat");
    $stmt->execute([':no_rawat' => $no_rawat]);
    if($r = $stmt->fetch(PDO::FETCH_ASSOC)) $total_obat -= (float)$r['val'];
    
    return $total_obat;
}

try {
    $settings = getSettings($koneksi_pdo);
    
    $start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
    $length = isset($_GET['length']) ? (int)$_GET['length'] : 10;
    $search_value = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';
    $draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;

    // --- LOGIKA TANGGAL & FILTER ---
    $mode = isset($_GET['mode']) ? $_GET['mode'] : 'periode';
    $tgl_awal = isset($_GET['tgl_awal']) ? trim($_GET['tgl_awal']) : date('Y-m-d');
    $tgl_akhir = isset($_GET['tgl_akhir']) ? trim($_GET['tgl_akhir']) : date('Y-m-d');
    $hari_ini = date('Y-m-d');

    // RULE: JANGAN PERNAH TAMPILKAN MASA DEPAN
    // Jika tgl_akhir yang diminta > hari ini, paksa jadi hari ini.
    if ($tgl_akhir > $hari_ini) {
        $tgl_akhir = $hari_ini;
    }

    $filter_sql = "";
    $params = [];

    if ($mode == 'semua') {
        // Mode Audit: Tampilkan semua yang belum bayar sampai hari ini
        $filter_sql = " AND rp.tgl_registrasi <= :hari_ini ";
        $params[':hari_ini'] = $hari_ini;
    } else {
        // Mode Periode: Sesuai range tanggal (tapi sudah di-cap hari ini)
        $filter_sql = " AND rp.tgl_registrasi BETWEEN :tgl_awal AND :tgl_akhir ";
        $params[':tgl_awal'] = $tgl_awal;
        $params[':tgl_akhir'] = $tgl_akhir;
    }

    $sql_from = "
        FROM reg_periksa rp
        INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
        INNER JOIN dokter d ON rp.kd_dokter = d.kd_dokter
        INNER JOIN poliklinik pl ON rp.kd_poli = pl.kd_poli
        INNER JOIN penjab pj ON rp.kd_pj = pj.kd_pj
        WHERE rp.status_lanjut = 'Ralan' 
        AND rp.status_bayar = 'Belum Bayar' 
        $filter_sql
    ";

    if (!empty($search_value)) {
        $sql_from .= " AND (p.nm_pasien LIKE :search OR rp.no_rawat LIKE :search OR rp.no_rkm_medis LIKE :search OR pl.nm_poli LIKE :search) ";
        $params[':search'] = "%$search_value%";
    }

    $stmt_cnt = $koneksi_pdo->prepare("SELECT COUNT(*) as total " . $sql_from);
    $stmt_cnt->execute($params);
    $totalFiltered = (int)$stmt_cnt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalData = $totalFiltered;

    // Tambahkan Limit (tanpa parameter binding karena PDO bind value by default is string, Limit needs integer)
    // Walaupun PHP 7.3 PDO punya bindValue dengan PDO::PARAM_INT, concatenation start & length yang di casting (int) sudah aman
    $sql_limit = ($length != -1) ? " LIMIT " . (int)$start . ", " . (int)$length : "";

    $sql_data = "SELECT rp.no_rawat, rp.tgl_registrasi, rp.jam_reg, p.no_rkm_medis, p.nm_pasien, d.nm_dokter, pl.nm_poli, pj.png_jawab, pj.kd_pj, rp.stts " . $sql_from . " ORDER BY rp.tgl_registrasi DESC, rp.jam_reg DESC $sql_limit";

    $stmt_data = $koneksi_pdo->prepare($sql_data);
    $stmt_data->execute($params);
    
    $raw_data = [];

    while ($row = $stmt_data->fetch(PDO::FETCH_ASSOC)) {
        // [LAZY LOADING v2] Data struktural saja — biaya dihitung async di frontend
        $is_anomali = ($row['stts'] == 'Batal');

        $raw_data[] = [
            'waktu'      => $row['tgl_registrasi'] . ' ' . $row['jam_reg'],
            'no_rawat'   => $row['no_rawat'],
            'rm'         => $row['no_rkm_medis'],
            'pasien'     => safe_utf8($row['nm_pasien']),
            'poli'       => safe_utf8($row['nm_poli']),
            'dokter'     => safe_utf8($row['nm_dokter']),
            'penjamin'   => safe_utf8($row['png_jawab']),
            'kd_pj'      => $row['kd_pj'],
            'status'     => $row['stts'],
            'is_anomali' => $is_anomali,
            // Placeholder — akan diisi async oleh frontend
            'biaya_obat_raw' => null,
            'biaya_obat'     => null,
            'estimasi_raw'   => null,
            'estimasi'       => null,
        ];
    }

    $output = [
        "draw" => $draw,
        "recordsTotal" => (int)$totalData,
        "recordsFiltered" => (int)$totalFiltered,
        "data" => $raw_data
    ];
} catch (Exception $e) {
    $output = ["draw" => $draw, "recordsTotal" => 0, "recordsFiltered" => 0, "data" => [], "error" => "Server Error"];
}

// 4. Final Output Clean
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
echo json_encode($output);
?>