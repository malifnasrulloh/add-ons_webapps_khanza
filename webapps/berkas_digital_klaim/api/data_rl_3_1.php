<?php
/*
 * File: api/data_rl_3_1.php
 * Fungsi: Menghasilkan data untuk Laporan RL 3.1 Indikator Pelayanan
 *
 * Deskripsi:
 * 1. Menggunakan logika yang sama dengan data_rl_3_2.php untuk mengumpulkan data agregat rawat inap.
 * 2. Menghitung indikator BOR, ALOS, BTO, TOI, NDR, dan GDR berdasarkan data agregat.
 * 3. Mengimplementasikan formula perhitungan sesuai Juknis SIRS 6.3.
 * 4. Menambahkan penanganan untuk pembagian dengan nol.
 */

error_reporting(0);
ini_set('display_errors', 0);

if (file_exists(__DIR__ . '/../../conf/conf.php')) {
    require_once(__DIR__ . '/../../conf/conf.php');
} else {
    require_once(__DIR__ . '/../conf/conf.php');
}

header('Content-Type: application/json');
$koneksi = bukakoneksi();

session_start();
if (!isset($_SESSION['casemix_login'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak.']);
    exit;
}

$tgl_awal   = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir  = $_GET['tgl_akhir'] ?? date('Y-m-d');

// 1. Data Dasar & Alokasi Tempat Tidur (Dynamic Mapping)
$jenis_pelayanan_std = [
    'Umum', 'Penyakit Dalam', 'Kesehatan Anak', 'Kesehatan Remaja', 'Obstetri',
    'Ginekologi', 'Bedah', 'Bedah Orthopedi', 'Bedah Saraf', 'Luka Bakar',
    'Saraf', 'Jiwa', 'Psikologi', 'Penatalaksana Penyalahgunaan NAPZA', 'THT',
    'Mata', 'Kulit dan Kelamin', 'Kardiologi', 'Paru', 'Kanker',
    'Uronefrologi', 'Geriatri', 'Kusta', 'Radioterapi', 'Kedokteran Nuklir',
    'Rehabilitasi Medik', 'ICU', 'HCU', 'ICCU/ICVCU', 'RICU', 'NICU', 'PICU',
    'Isolasi', 'Gigi dan Mulut', 'Pelayanan Rawat Darurat', 'Perinatologi'
];

$result_data = [];
foreach ($jenis_pelayanan_std as $jp) {
    $result_data[$jp] = [
        'jenis_pelayanan' => $jp,
        'keluar_hidup' => 0, 'mati_l_kurang_48' => 0, 'mati_l_lebih_48' => 0,
        'mati_p_kurang_48' => 0, 'mati_p_lebih_48' => 0, 'lama_dirawat' => 0,
        'hari_perawatan' => 0, 'alokasi_tt' => 0
    ];
}

function mapBangsalToJenisPelayanan($nama_bangsal) {
    $nama_bangsal_upper = strtoupper($nama_bangsal);
    // Intensif
    if (strpos($nama_bangsal_upper, 'NICU') !== false) return 'NICU';
    if (strpos($nama_bangsal_upper, 'PICU') !== false) return 'PICU';
    if (strpos($nama_bangsal_upper, 'HCU') !== false) return 'HCU';
    if (strpos($nama_bangsal_upper, 'ICCU') !== false || strpos($nama_bangsal_upper, 'ICVCU') !== false) return 'ICCU/ICVCU';
    if (strpos($nama_bangsal_upper, 'RICU') !== false) return 'RICU';
    if (strpos($nama_bangsal_upper, 'ICU') !== false) return 'ICU';
    
    // Spesialisasi
    if (strpos($nama_bangsal_upper, 'REMAJA') !== false) return 'Kesehatan Remaja';
    if (strpos($nama_bangsal_upper, 'ANAK') !== false) return 'Kesehatan Anak';
    if (strpos($nama_bangsal_upper, 'OBSTETRI') !== false || strpos($nama_bangsal_upper, 'OBGIN') !== false || strpos($nama_bangsal_upper, 'BERSALIN') !== false || strpos($nama_bangsal_upper, 'KANDUNGAN') !== false || strpos($nama_bangsal_upper, 'VK') !== false) return 'Obstetri';
    if (strpos($nama_bangsal_upper, 'GINEKOLOGI') !== false) return 'Ginekologi';
    if (strpos($nama_bangsal_upper, 'BEDAH') !== false) return 'Bedah';
    if (strpos($nama_bangsal_upper, 'ORTHOPEDI') !== false) return 'Bedah Orthopedi';
    if (strpos($nama_bangsal_upper, 'SARAF') !== false) return 'Saraf';
    if (strpos($nama_bangsal_upper, 'DALAM') !== false || strpos($nama_bangsal_upper, 'INTERNA') !== false || strpos($nama_bangsal_upper, 'IPD') !== false) return 'Penyakit Dalam';
    if (strpos($nama_bangsal_upper, 'PARU') !== false) return 'Paru';
    if (strpos($nama_bangsal_upper, 'JANTUNG') !== false || strpos($nama_bangsal_upper, 'KARDIOLOGI') !== false) return 'Kardiologi';
    if (strpos($nama_bangsal_upper, 'JIWA') !== false) return 'Jiwa';
    if (strpos($nama_bangsal_upper, 'ISOLASI') !== false) return 'Isolasi';
    if (strpos($nama_bangsal_upper, 'PERINATOLOGI') !== false || strpos($nama_bangsal_upper, 'BAYI') !== false) return 'Perinatologi';
    
    return 'Umum';
}

// Hitung Alokasi TT per Jenis Pelayanan
$sql_tt = "SELECT b.nm_bangsal, COUNT(k.kd_kamar) as total_tt 
           FROM bangsal b 
           INNER JOIN kamar k ON b.kd_bangsal = k.kd_bangsal 
           WHERE k.statusdata = '1' 
           GROUP BY b.nm_bangsal";
$res_tt = mysqli_query($koneksi, $sql_tt);
while($row_tt = mysqli_fetch_assoc($res_tt)) {
    $jp = mapBangsalToJenisPelayanan($row_tt['nm_bangsal']);
    if(isset($result_data[$jp])) $result_data[$jp]['alokasi_tt'] += (int)$row_tt['total_tt'];
}

$start_str = $tgl_awal . " 00:00:00";
$end_str   = $tgl_akhir . " 23:59:59";

// 2. QUERY TRANSAKSI UTAMA (Replikasi Logika Dashboard Eksekutif)
$sql_main = "
    SELECT 
        ki.tgl_masuk, ki.jam_masuk, 
        ki.tgl_keluar, ki.jam_keluar, 
        ki.stts_pulang, 
        p.jk, 
        b.nm_bangsal, 
        ki.lama,
        pm.no_rkm_medis as pm_mati,
        rp.stts as reg_stts,
        ki_ibu.tgl_keluar as tgl_keluar_ibu,
        ki_ibu.jam_keluar as jam_keluar_ibu
    FROM kamar_inap ki 
    INNER JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat 
    INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis 
    INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar 
    INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal 
    LEFT JOIN ranap_gabung rg ON ki.no_rawat = rg.no_rawat2
    LEFT JOIN kamar_inap ki_ibu ON rg.no_rawat = ki_ibu.no_rawat
    LEFT JOIN pasien_mati pm ON p.no_rkm_medis = pm.no_rkm_medis AND pm.tanggal BETWEEN ki.tgl_masuk AND ki.tgl_keluar
    WHERE ki.tgl_masuk <= ? AND (ki.tgl_keluar >= ? OR ki_ibu.tgl_keluar >= ? OR ki.stts_pulang = '-' OR ki.stts_pulang = 'Pindah Kamar')
";

$stmt = mysqli_prepare($koneksi, $sql_main);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "sss", $tgl_akhir, $tgl_awal, $tgl_awal);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $jp = mapBangsalToJenisPelayanan($row['nm_bangsal']);
        
        $is_still_in = ($row['stts_pulang'] == '-' || $row['stts_pulang'] == 'Pindah Kamar');
        $has_ibu_pulang = (!empty($row['tgl_keluar_ibu']) && $row['tgl_keluar_ibu'] != '0000-00-00');

        // Filter Data Gantung (Cleaning Logics)
        if ($is_still_in && $row['tgl_keluar'] == '0000-00-00' && $row['reg_stts'] != 'Dirawat' && !$has_ibu_pulang) {
            continue;
        }

        // Pasien Keluar Hidup/Mati
        if (!$is_still_in && $row['tgl_keluar'] >= $tgl_awal && $row['tgl_keluar'] <= $tgl_akhir) {
            $stts = strtolower($row['stts_pulang']);
            $is_mati = (strpos($stts, 'meninggal') !== false || !empty($row['pm_mati']));
            if (!$is_mati) {
                $result_data[$jp]['keluar_hidup']++;
            } else {
                // Deteksi NDR (Mati >= 48 jam)
                $diff_seconds = strtotime($row['tgl_keluar'] . ' ' . $row['jam_keluar']) - strtotime($row['tgl_masuk'] . ' ' . $row['jam_masuk']);
                $kurang_48_jam = ($diff_seconds < 48 * 3600);
                if ($row['jk'] == 'L') {
                    $kurang_48_jam ? $result_data[$jp]['mati_l_kurang_48']++ : $result_data[$jp]['mati_l_lebih_48']++;
                } else {
                    $kurang_48_jam ? $result_data[$jp]['mati_p_kurang_48']++ : $result_data[$jp]['mati_p_lebih_48']++;
                }
            }
            $result_data[$jp]['lama_dirawat'] += (float)$row['lama'];
        }

        // Kalkulasi Hari Perawatan (HP) Presisi Berbasis Detik
        $tgl_keluar_calc = $row['tgl_keluar'];
        $jam_keluar_calc = $row['jam_keluar'];

        // Sinkronisasi Pemulangan Bayi Rawat Gabung
        if ($row['tgl_keluar'] == '0000-00-00' && $has_ibu_pulang) {
            $tgl_keluar_calc = $row['tgl_keluar_ibu'];
            $jam_keluar_calc = $row['jam_keluar_ibu'];
        }

        $actual_start = max(strtotime($row['tgl_masuk'] . ' ' . $row['jam_masuk']), strtotime($start_str));
        $end_val = ($tgl_keluar_calc == '0000-00-00') ? $end_str : ($tgl_keluar_calc . ' ' . $jam_keluar_calc);
        $actual_end = min(strtotime($end_val), strtotime($end_str));
        
        if ($actual_end > $actual_start) {
            $result_data[$jp]['hari_perawatan'] += ($actual_end - $actual_start) / 86400;
        }
    }
    mysqli_stmt_close($stmt);
}

// 3. AGREGASI KE 5 KATEGORI JUKNIS
$categories = [
    'Non Intensif' => ['Umum', 'Penyakit Dalam', 'Kesehatan Anak', 'Kesehatan Remaja', 'Obstetri', 'Ginekologi', 'Bedah', 'Bedah Orthopedi', 'Bedah Saraf', 'Luka Bakar', 'Saraf', 'Jiwa', 'Psikologi', 'Penatalaksana Penyalahgunaan NAPZA', 'THT', 'Mata', 'Kulit dan Kelamin', 'Kardiologi', 'Paru', 'Kanker', 'Uronefrologi', 'Geriatri', 'Kusta', 'Radioterapi', 'Kedokteran Nuklir', 'Rehabilitasi Medik', 'Isolasi', 'Gigi dan Mulut', 'Pelayanan Rawat Darurat', 'Perinatologi'],
    'ICU' => ['ICU'],
    'NICU' => ['NICU'],
    'PICU' => ['PICU'],
    'Intensif lainnya' => ['HCU', 'ICCU/ICVCU', 'RICU']
];

$output = [];
$anomalies = [];
$data_dasar = ['bed'=>0, 'hp'=>0, 'keluar'=>0, 'mati'=>0, 'mati48'=>0];
$jumlah_hari_periode = (strtotime($tgl_akhir) - strtotime($tgl_awal)) / 86400 + 1;

foreach($categories as $cat_name => $members) {
    $c = ['hp' => 0, 'tt' => 0, 'ld' => 0, 'hidup' => 0, 'm48' => 0, 'm_total' => 0];
    foreach($members as $m) {
        if(isset($result_data[$m])) {
            $d = $result_data[$m];
            $c['hp'] += $d['hari_perawatan'];
            $c['tt'] += $d['alokasi_tt'];
            $c['ld'] += $d['lama_dirawat'];
            $c['hidup'] += $d['keluar_hidup'];
            $c['m48'] += ($d['mati_l_lebih_48'] + $d['mati_p_lebih_48']);
            $c['m_total'] += ($d['mati_l_kurang_48'] + $d['mati_l_lebih_48'] + $d['mati_p_kurang_48'] + $d['mati_p_lebih_48']);
        }
    }
    
    $keluar_total = $c['hidup'] + $c['m_total'];
    $p_k = ($keluar_total == 0) ? 1 : $keluar_total;
    $p_tt = ($c['tt'] == 0) ? 1 : $c['tt'];

    $bor = ($c['tt'] * $jumlah_hari_periode > 0) ? ($c['hp'] / ($c['tt'] * $jumlah_hari_periode)) * 100 : 0;
    
    // Deteksi Anomali
    if ($bor > 100) {
        $anomalies[] = ['kategori' => $cat_name, 'bor' => round($bor, 2), 'hp' => round($c['hp'], 2), 'kapasitas' => ($c['tt'] * $jumlah_hari_periode)];
    }

    $output[] = [
        'no' => count($output) + 1,
        'jenis_pelayanan' => $cat_name,
        'bor' => round($bor, 2),
        'alos' => round($c['ld'] / $p_k, 2),
        'bto' => round($keluar_total / $p_tt, 2),
        'toi' => round((($c['tt'] * $jumlah_hari_periode) - $c['hp']) / $p_k, 2),
        'ndr' => round(($c['m48'] / $p_k) * 1000, 2),
        'gdr' => round(($c['m_total'] / $p_k) * 1000, 2)
    ];

    $data_dasar['bed'] += $c['tt'];
    $data_dasar['hp'] += $c['hp'];
    $data_dasar['keluar'] += $keluar_total;
    $data_dasar['mati'] += $c['m_total'];
    $data_dasar['mati48'] += $c['m48'];
}

// Tambahkan Baris Rata-rata (77)
$p_k_rs = ($data_dasar['keluar'] == 0) ? 1 : $data_dasar['keluar'];
$p_tt_rs = ($data_dasar['bed'] == 0) ? 1 : $data_dasar['bed'];
$output[] = [
    'no' => 77,
    'jenis_pelayanan' => 'Rata-rata',
    'bor' => round(($data_dasar['hp'] / ($data_dasar['bed'] * $jumlah_hari_periode)) * 100, 2),
    'alos' => round($data_dasar['hp'] / $p_k_rs, 2),
    'bto' => round($data_dasar['keluar'] / $p_tt_rs, 2),
    'toi' => round((($data_dasar['bed'] * $jumlah_hari_periode) - $data_dasar['hp']) / $p_k_rs, 2),
    'ndr' => round(($data_dasar['mati48'] / $p_k_rs) * 1000, 2),
    'gdr' => round(($data_dasar['mati'] / $p_k_rs) * 1000, 2)
];

echo json_encode([
    'data' => $output, 
    'anomalies' => $anomalies, 
    'summary' => [
        'hp' => number_format($data_dasar['hp'], 2),
        'bed' => $data_dasar['bed'],
        'periode' => $jumlah_hari_periode
    ]
]);

mysqli_close($koneksi);
?>
