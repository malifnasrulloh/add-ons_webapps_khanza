<?php
/*
 * File: api/data_dashboard.php (FIX V6 - OMZET CHART DATA)
 * - Fix: Menambahkan array 'labels' dan 'data' pada respon omzet agar Chart Donut muncul.
 * - Logic lainnya tetap sama (sudah robust).
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once(dirname(__DIR__) . '/config/koneksi.php'); 

$today = date('Y-m-d');
$year = date('Y');
$tgl_awal_bulan = date('Y-m-01');

$response = [
    'omzet' => [
        'tunai' => 0,
        'piutang' => 0,
        'total' => 0,
        'labels' => ['Tunai', 'Piutang'],
        'data' => [0, 0]
    ],
    'bed' => [
        'bor_global' => 0,
        'per_kelas' => [],
        'total_terisi' => 0
    ],
    'kunjungan' => ['Ralan' => 0, 'Ranap' => 0, 'Total' => 0],
    'kunjungan_aktif' => 0,
    'top_poli' => [],
    'tren' => [
        'ralan' => array_fill(0, 12, 0),
        'ranap' => array_fill(0, 12, 0),
        'total' => array_fill(0, 12, 0)
    ],
    'top_farmasi' => [
        'labels' => [],
        'data' => []
    ],
    'top_booking' => [
        'labels' => [],
        'data' => []
    ]
];

try {
    // ==========================================================================
    // 1. OMZET HARI INI (FIX CHART DATA)
    // ==========================================================================
    $sql_omzet = "
        SELECT 
            'Ralan' as jenis,
            IF(pp.no_rawat IS NULL OR pp.status='Lunas', 'Tunai', 'Piutang') as status_bayar,
            COALESCE(SUM(b.totalbiaya), 0) as total
        FROM nota_jalan nj
        INNER JOIN billing b ON nj.no_rawat = b.no_rawat
        LEFT JOIN piutang_pasien pp ON nj.no_rawat = pp.no_rawat
        WHERE nj.tanggal = :today_1
        GROUP BY status_bayar

        UNION ALL

        SELECT 
            'Ranap' as jenis,
            IF(pp.no_rawat IS NULL OR pp.status='Lunas', 'Tunai', 'Piutang') as status_bayar,
            COALESCE(SUM(b.totalbiaya), 0) as total
        FROM nota_inap ni
        INNER JOIN billing b ON ni.no_rawat = b.no_rawat
        LEFT JOIN piutang_pasien pp ON ni.no_rawat = pp.no_rawat
        WHERE ni.tanggal = :today_2
        GROUP BY status_bayar
    ";

    $stmt_omzet = $koneksi_pdo->prepare($sql_omzet);
    $stmt_omzet->execute([':today_1' => $today, ':today_2' => $today]);

    // Init Variable
    $total_tunai = 0;
    $total_piutang = 0;
    $total_all = 0;

    while($row = $stmt_omzet->fetch(PDO::FETCH_ASSOC)) {
        $val = (float)$row['total'];
        if($row['status_bayar'] == 'Tunai') {
            $total_tunai += $val;
        } else {
            $total_piutang += $val;
        }
        $total_all += $val;
    }

    // Struktur Response Lengkap untuk Widget & Chart
    $response['omzet'] = [
        'tunai' => $total_tunai,
        'piutang' => $total_piutang,
        'total' => $total_all,
        // Data untuk Chart.js
        'labels' => ['Tunai', 'Piutang'],
        'data' => [$total_tunai, $total_piutang]
    ];


    // ==========================================================================
    // 2. KETERSEDIAAN BED
    // ==========================================================================
    $sql_bed = "
        SELECT k.kelas, k.status, b.nm_bangsal
        FROM kamar k
        INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
        WHERE k.statusdata = '1'
    ";
    $stmt_bed = $koneksi_pdo->query($sql_bed);
    
    $bed_groups = [];
    $total_bed_rs = 0;
    $total_terisi_rs = 0;

    while($row = $stmt_bed->fetch(PDO::FETCH_ASSOC)) {
        $nm = strtoupper($row['nm_bangsal']);
        $status = $row['status']; 
        $kategori = $row['kelas'];
        
        if (strpos($nm, 'ISOLASI') !== false || strpos($nm, 'ICU') !== false || 
            strpos($nm, 'NICU') !== false || strpos($nm, 'PICU') !== false || 
            strpos($nm, 'HCU') !== false || strpos($nm, 'PERINA') !== false) {
            $kategori = 'Kelas Khusus';
        }

        if (!isset($bed_groups[$kategori])) {
            $bed_groups[$kategori] = ['kelas' => $kategori, 'total' => 0, 'terisi' => 0, 'kosong' => 0];
        }

        $bed_groups[$kategori]['total']++;
        if ($status == 'ISI') {
            $bed_groups[$kategori]['terisi']++;
            $total_terisi_rs++;
        } else {
            $bed_groups[$kategori]['kosong']++;
        }
        $total_bed_rs++;
    }
    
    ksort($bed_groups);
    $bed_data = array_values($bed_groups);

    // Hitung BOR Global
    $start = new DateTime($tgl_awal_bulan);
    $end = new DateTime($today);
    $days_period = $end->diff($start)->days + 1;

    $sql_hp = "SELECT SUM(IF(DATEDIFF(tgl_keluar, tgl_masuk) = 0, 1, DATEDIFF(tgl_keluar, tgl_masuk))) as total_hp 
               FROM kamar_inap WHERE tgl_keluar BETWEEN :tgl_awal AND :today";
    $stmt_hp = $koneksi_pdo->prepare($sql_hp);
    $stmt_hp->execute([':tgl_awal' => $tgl_awal_bulan, ':today' => $today]);
    $row_hp = $stmt_hp->fetch(PDO::FETCH_ASSOC);
    $hari_perawatan = $row_hp ? (int)$row_hp['total_hp'] : 0;
    $pembagi_bor = ($total_bed_rs * $days_period);
    $bor_rs = ($pembagi_bor > 0) ? ($hari_perawatan / $pembagi_bor) * 100 : 0;

    $response['bed'] = [
        'bor_global' => round($bor_rs, 2),
        'per_kelas' => $bed_data,
        'total_terisi' => $total_terisi_rs
    ];

    // ==========================================================================
    // 3. KUNJUNGAN HARI INI
    // ==========================================================================
    $sql_visit = "SELECT status_lanjut, COUNT(*) as jumlah FROM reg_periksa WHERE tgl_registrasi = :today AND stts != 'Batal' GROUP BY status_lanjut";
    $stmt_visit = $koneksi_pdo->prepare($sql_visit);
    $stmt_visit->execute([':today' => $today]);
    
    $visit = ['Ralan' => 0, 'Ranap' => 0, 'Total' => 0];
    while($row = $stmt_visit->fetch(PDO::FETCH_ASSOC)) {
        $visit[$row['status_lanjut']] = (int)$row['jumlah'];
        $visit['Total'] += (int)$row['jumlah'];
    }
    $response['kunjungan'] = $visit;

    // ==========================================================================
    // 4. KUNJUNGAN AKTIF
    // ==========================================================================
    $sql_aktif = "SELECT COUNT(*) as total FROM reg_periksa WHERE status_bayar = 'Belum Bayar' AND stts != 'Batal'";
    $stmt_aktif = $koneksi_pdo->query($sql_aktif);
    $response['kunjungan_aktif'] = ($stmt_aktif) ? (int)$stmt_aktif->fetch(PDO::FETCH_ASSOC)['total'] : 0;

    // ==========================================================================
    // 5. TOP 5 POLI
    // ==========================================================================
    $sql_poli = "SELECT p.nm_poli, COUNT(r.no_rawat) as jumlah FROM reg_periksa r INNER JOIN poliklinik p ON r.kd_poli = p.kd_poli WHERE r.tgl_registrasi = :today AND r.stts != 'Batal' GROUP BY r.kd_poli ORDER BY jumlah DESC LIMIT 5";
    $stmt_poli = $koneksi_pdo->prepare($sql_poli);
    $stmt_poli->execute([':today' => $today]);
    
    $top_poli = [];
    while($r = $stmt_poli->fetch(PDO::FETCH_ASSOC)) { 
        $top_poli[] = $r; 
    }
    $response['top_poli'] = $top_poli;

    // ==========================================================================
    // 6. TREN TAHUNAN
    // ==========================================================================
    $sql_tren = "SELECT MONTH(tgl_registrasi) as bulan, status_lanjut, COUNT(*) as jumlah FROM reg_periksa WHERE YEAR(tgl_registrasi) = :year AND stts != 'Batal' GROUP BY MONTH(tgl_registrasi), status_lanjut";
    $stmt_tren = $koneksi_pdo->prepare($sql_tren);
    $stmt_tren->execute([':year' => $year]);
    
    $d_ralan = array_fill(1, 12, 0);
    $d_ranap = array_fill(1, 12, 0);
    $d_total = array_fill(1, 12, 0);
    
    while($row = $stmt_tren->fetch(PDO::FETCH_ASSOC)) {
        $b = (int)$row['bulan']; $j = (int)$row['jumlah'];
        if ($row['status_lanjut'] == 'Ralan') $d_ralan[$b] = $j; else $d_ranap[$b] = $j;
        $d_total[$b] += $j;
    }
    
    $response['tren'] = ['ralan' => array_values($d_ralan), 'ranap' => array_values($d_ranap), 'total' => array_values($d_total)];

    // ==========================================================================
    // 7. TOP 10 FARMASI
    // ==========================================================================
    $sql_farmasi = "SELECT b.nama_brng AS nama, COUNT(a.kode_brng) AS jumlah
                    FROM detail_pemberian_obat a 
                    INNER JOIN databarang b ON a.kode_brng = b.kode_brng  
                    WHERE a.tgl_perawatan = :today 
                    GROUP BY b.nama_brng 
                    ORDER BY jumlah DESC 
                    LIMIT 10";
    $stmt_farmasi = $koneksi_pdo->prepare($sql_farmasi);
    $stmt_farmasi->execute([':today' => $today]);
    while($r = $stmt_farmasi->fetch(PDO::FETCH_ASSOC)) {
        $response['top_farmasi']['labels'][] = strlen($r['nama']) > 20 ? substr($r['nama'], 0, 20) . '...' : $r['nama'];
        $response['top_farmasi']['data'][] = (int)$r['jumlah'];
    }

    // ==========================================================================
    // 8. TOP 10 BOOKING ONLINE
    // ==========================================================================
    $sql_booking = "SELECT p.nm_poli AS nmpoli, COUNT(br.no_rkm_medis) AS total 
                    FROM booking_registrasi br 
                    INNER JOIN poliklinik p ON br.kd_poli = p.kd_poli 
                    WHERE br.limit_reg = '1' AND br.tanggal_periksa = :today 
                    GROUP BY p.nm_poli 
                    ORDER BY total DESC  
                    LIMIT 10";
    $stmt_booking = $koneksi_pdo->prepare($sql_booking);
    $stmt_booking->execute([':today' => $today]);
    while($r = $stmt_booking->fetch(PDO::FETCH_ASSOC)) {
        $response['top_booking']['labels'][] = $r['nmpoli'];
        $response['top_booking']['data'][] = (int)$r['total'];
    }



} catch (PDOException $e) {
    // Return empty valid JSON with safe defaults defined at the top
}

echo json_encode($response);
?>