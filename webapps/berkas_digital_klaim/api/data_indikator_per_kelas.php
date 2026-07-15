<?php
/*
 * File: api/data_indikator_per_kelas.php
 * Fungsi: API Indikator Per Kelas (Presisi Detik / Jam)
 */
error_reporting(0);
ini_set('display_errors', 0);

require_once(__DIR__ . '/../csrf.php');

if(file_exists(__DIR__ . '/../../conf/conf.php')) {
    require_once(__DIR__ . '/../../conf/conf.php');
} else {
    require_once(__DIR__ . '/../conf/conf.php');
}

header('Content-Type: application/json');
$koneksi = bukakoneksi();

if (!isset($_SESSION['casemix_login'])) {
    http_response_code(403); echo json_encode(['error' => 'Akses ditolak.']); exit;
}

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

$start_str = $tgl_awal . " 00:00:00";
$end_str   = $tgl_akhir . " 23:59:59";

$start = new DateTime($tgl_awal);
$end = new DateTime($tgl_akhir);
$days_period = $end->diff($start)->days + 1;

// Helper query untuk grouping kelas
$grouping_logic = "
    CASE 
        WHEN LOWER(b.nm_bangsal) LIKE '%bayi%' OR LOWER(b.nm_bangsal) LIKE '%box bayi%' THEN 'Bed Bayi'
        WHEN LOWER(b.nm_bangsal) LIKE '%isolasi%' THEN 'Isolasi'
        WHEN LOWER(b.nm_bangsal) LIKE '%icu%' OR LOWER(b.nm_bangsal) LIKE '%hcu%' THEN 'Intensive'
        ELSE k.kelas 
    END
";

// 1. Array Master Kelas
$kelas_data = [];
$q_bed = mysqli_query($koneksi, "
    SELECT $grouping_logic as kelas_group, COUNT(k.kd_kamar) as jml_bed
    FROM bangsal b
    JOIN kamar k ON b.kd_bangsal = k.kd_bangsal
    WHERE k.statusdata = '1'
    GROUP BY kelas_group
    ORDER BY kelas_group ASC
");
while($row = mysqli_fetch_assoc($q_bed)) {
    $kelas_data[$row['kelas_group']] = [
        'kelas' => $row['kelas_group'],
        'bed' => (int)$row['jml_bed'],
        'detik_hp' => 0, 'd' => 0, 'mati' => 0, 'mati_48' => 0
    ];
}

// 2. Transaksi Hari Perawatan (Presisi Detik)
$sql_hp = "
SELECT 
    $grouping_logic as kelas_group,
    SUM(
        CASE 
            WHEN ki.tgl_keluar <> '0000-00-00' THEN
                GREATEST(0, TIMESTAMPDIFF(SECOND,
                    GREATEST(CONCAT(ki.tgl_masuk, ' ', ki.jam_masuk), '$start_str'),
                    LEAST(CONCAT(ki.tgl_keluar, ' ', ki.jam_keluar), '$end_str')
                ))
            WHEN ki.tgl_keluar = '0000-00-00' AND ki_ibu.tgl_keluar <> '0000-00-00' THEN
                GREATEST(0, TIMESTAMPDIFF(SECOND,
                    GREATEST(CONCAT(ki.tgl_masuk, ' ', ki.jam_masuk), '$start_str'),
                    LEAST(CONCAT(ki_ibu.tgl_keluar, ' ', ki_ibu.jam_keluar), '$end_str')
                ))
            WHEN ki.tgl_keluar = '0000-00-00' AND rp.stts = 'Dirawat' THEN
                GREATEST(0, TIMESTAMPDIFF(SECOND,
                    GREATEST(CONCAT(ki.tgl_masuk, ' ', ki.jam_masuk), '$start_str'),
                    '$end_str'
                ))
            ELSE 0
        END
    ) as total_detik
FROM kamar_inap ki
JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
JOIN kamar k ON ki.kd_kamar = k.kd_kamar
JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
LEFT JOIN ranap_gabung rg ON ki.no_rawat = rg.no_rawat2
LEFT JOIN kamar_inap ki_ibu ON rg.no_rawat = ki_ibu.no_rawat
WHERE 
    (CONCAT(ki.tgl_masuk, ' ', ki.jam_masuk) <= '$end_str') AND
    (
        (ki.tgl_keluar <> '0000-00-00' AND CONCAT(ki.tgl_keluar, ' ', ki.jam_keluar) >= '$start_str') OR
        (ki.tgl_keluar = '0000-00-00' AND ki_ibu.tgl_keluar <> '0000-00-00' AND CONCAT(ki_ibu.tgl_keluar, ' ', ki_ibu.jam_keluar) >= '$start_str') OR
        (ki.tgl_keluar = '0000-00-00' AND rp.stts = 'Dirawat')
    )
GROUP BY kelas_group
";

$q_hp = mysqli_query($koneksi, $sql_hp);
while($row = mysqli_fetch_assoc($q_hp)) {
    $kd = $row['kelas_group'];
    if(isset($kelas_data[$kd])) $kelas_data[$kd]['detik_hp'] = floatval($row['total_detik']);
    else {
        // Jika ada transaksi di kelas yang bed-nya sudah tidak aktif
        $kelas_data[$kd] = [
            'kelas' => $kd, 'bed' => 0, 'detik_hp' => floatval($row['total_detik']), 
            'd' => 0, 'mati' => 0, 'mati_48' => 0
        ];
    }
}

// 3. Transaksi Pasien Keluar (Termasuk Pindah)
$sql_stat = "SELECT 
            $grouping_logic as kelas_group,
            COUNT(ki.no_rawat) as total_keluar,
            SUM(IF(ki.stts_pulang = 'Meninggal', 1, 0)) as total_mati,
            SUM(IF(ki.stts_pulang = 'Meninggal' AND TIMESTAMPDIFF(HOUR, CONCAT(ki.tgl_masuk,' ',ki.jam_masuk), CONCAT(ki.tgl_keluar,' ',ki.jam_keluar)) >= 48, 1, 0)) as mati_lebih_48
        FROM kamar_inap ki
        JOIN kamar k ON ki.kd_kamar = k.kd_kamar
        JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
        WHERE ki.tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
        GROUP BY kelas_group";

$q_stat = mysqli_query($koneksi, $sql_stat);
while($row = mysqli_fetch_assoc($q_stat)) {
    $kd = $row['kelas_group'];
    if(isset($kelas_data[$kd])) {
        $kelas_data[$kd]['d'] = (int)$row['total_keluar'];
        $kelas_data[$kd]['mati'] = (int)$row['total_mati'];
        $kelas_data[$kd]['mati_48'] = (int)$row['mati_lebih_48'];
    }
}

// 4. Kalkulasi Akhir
$final_data = [];
$anomalies = [];
foreach($kelas_data as $row) {
    $bed = $row['bed'];
    $hp  = $row['detik_hp'] / 86400; // Konversi Detik ke Hari
    $d   = $row['d'];
    $mati = $row['mati'];
    $mati48 = $row['mati_48'];

    $p_d = ($d == 0) ? 1 : $d;
    $p_bed = ($bed == 0) ? 1 : $bed;

    $bor  = ($hp / ($p_bed * $days_period)) * 100;
    $alos = $hp / $p_d;
    $toi  = (($p_bed * $days_period) - $hp) / $p_d;
    $bto  = $d / $p_bed;
    $gdr  = ($mati / $p_d) * 1000;
    $ndr  = ($mati48 / $p_d) * 1000;

    if ($bor > 100) {
        $anomalies[] = [
            'bangsal' => $row['kelas'],
            'bor' => round($bor, 2),
            'hp' => round($hp, 2),
            'kapasitas' => ($bed * $days_period)
        ];
    }

    $final_data[] = [
        'kelas' => $row['kelas'],
        'bed' => $bed,
        'hp' => number_format($hp, 2, '.', ','),
        'd' => $d,
        'bor' => round($bor, 2),
        'alos' => round($alos, 2),
        'toi' => round($toi, 2),
        'bto' => round($bto, 2),
        'gdr' => round($gdr, 2),
        'ndr' => round($ndr, 2)
    ];
}

echo json_encode(['data' => array_values($final_data), 'anomalies' => $anomalies]);
?>