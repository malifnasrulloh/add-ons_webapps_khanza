<?php
/*
 * File: api/data_indikator_per_kelas.php
 * Fungsi: Menghitung Indikator Barber Johnson per Kelas / Grup.
 * Logika Spesifik:
 * - Menggunakan CASE WHEN untuk klasifikasi grup agar saling eksklusif (tidak dobel hitung).
 * - Pasien Pindah Kamar DIHITUNG sebagai Pasien Keluar (D).
 */

header('Content-Type: application/json');
require_once(dirname(__DIR__) . '/config/koneksi.php'); 

if (!isset($_SESSION['user_id'])) {
    http_response_code(403); echo json_encode(['error' => 'Akses ditolak.']); exit;
}

// 1. Ambil Parameter
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// 2. Hitung Periode Hari (t)
$start = new DateTime($tgl_awal);
$end = new DateTime($tgl_akhir);
$days_period = $end->diff($start)->days + 1;

// 3. Ambil Data Master Bed berdasarkan Grup Kelas (A)
$kelas_data = [];
$sql_bed = "
    SELECT 
        CASE
            WHEN bangsal.nm_bangsal LIKE '%bayi%' OR bangsal.nm_bangsal LIKE '%box bayi%' THEN 'Bed Bayi'
            WHEN bangsal.nm_bangsal LIKE '%isolasi%' THEN 'Isolasi'
            WHEN bangsal.nm_bangsal LIKE '%ICU%' OR bangsal.nm_bangsal LIKE '%HCU%' THEN 'Intensive'
            ELSE kamar.kelas
        END as grup_kelas,
        COUNT(kamar.kd_kamar) as jumlah_bed
    FROM kamar 
    INNER JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal
    WHERE kamar.statusdata='1' 
    GROUP BY grup_kelas
    ORDER BY grup_kelas ASC
";
$res_bed = $koneksi->query($sql_bed);
while($row = $res_bed->fetch_assoc()) {
    $grup = $row['grup_kelas'] ? $row['grup_kelas'] : '-';
    $kelas_data[$grup] = [
        'grup_kelas' => $grup,
        'bed' => (int)$row['jumlah_bed'],
        'hp' => 0,
        'd' => 0,
        'mati' => 0,
        'mati_48' => 0
    ];
}

// 4. Ambil Data Transaksi Pasien per Grup Kelas
$sql_transaksi = "
    SELECT 
        CASE
            WHEN bangsal.nm_bangsal LIKE '%bayi%' OR bangsal.nm_bangsal LIKE '%box bayi%' THEN 'Bed Bayi'
            WHEN bangsal.nm_bangsal LIKE '%isolasi%' THEN 'Isolasi'
            WHEN bangsal.nm_bangsal LIKE '%ICU%' OR bangsal.nm_bangsal LIKE '%HCU%' THEN 'Intensive'
            ELSE kamar.kelas
        END as grup_kelas,
        SUM(IF(DATEDIFF(ki.tgl_keluar, ki.tgl_masuk) = 0, 1, DATEDIFF(ki.tgl_keluar, ki.tgl_masuk))) as total_hp,
        COUNT(ki.no_rawat) as total_keluar,
        SUM(IF(ki.stts_pulang = 'Meninggal', 1, 0)) as total_mati,
        SUM(IF(ki.stts_pulang = 'Meninggal' AND DATEDIFF(ki.tgl_keluar, ki.tgl_masuk) >= 2, 1, 0)) as mati_lebih_48
    FROM kamar_inap ki
    INNER JOIN kamar ON ki.kd_kamar = kamar.kd_kamar
    INNER JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal
    WHERE ki.tgl_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
    GROUP BY grup_kelas
";

$res_trans = $koneksi->query($sql_transaksi);
if ($res_trans) {
    while($row = $res_trans->fetch_assoc()) {
        $grup = $row['grup_kelas'] ? $row['grup_kelas'] : '-';
        if (isset($kelas_data[$grup])) {
            $kelas_data[$grup]['hp'] = (int)$row['total_hp'];
            $kelas_data[$grup]['d'] = (int)$row['total_keluar'];
            $kelas_data[$grup]['mati'] = (int)$row['total_mati'];
            $kelas_data[$grup]['mati_48'] = (int)$row['mati_lebih_48'];
        }
    }
}

// 5. Kalkulasi Indikator per Kelas
$final_data = [];
foreach ($kelas_data as $grup => $row) {
    $bed = $row['bed'];
    $hp = $row['hp'];
    $d = $row['d'];
    $mati = $row['mati'];
    $mati_48 = $row['mati_48'];

    // Mencegah division by zero
    $pembagi_d = ($d == 0) ? 1 : $d;
    $pembagi_bed = ($bed == 0) ? 1 : $bed;

    // Rumus Barber Johnson
    $bor = ($hp / ($bed * $days_period)) * 100;
    $alos = $hp / $pembagi_d;
    $toi = (($bed * $days_period) - $hp) / $pembagi_d;
    $bto = $d / $pembagi_bed;
    $gdr = ($mati / $pembagi_d) * 1000;
    $ndr = ($mati_48 / $pembagi_d) * 1000;

    $final_data[] = [
        'kelas' => $grup,
        'bed' => $bed,
        'hp' => $hp,
        'd' => $d,
        'bor' => round($bor, 2),
        'alos' => round($alos, 2),
        'toi' => round($toi, 2),
        'bto' => round($bto, 2),
        'gdr' => round($gdr, 2),
        'ndr' => round($ndr, 2)
    ];
}

// 6. Kirim Response
echo json_encode(['data' => $final_data]);
?>
