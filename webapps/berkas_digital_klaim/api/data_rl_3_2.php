<?php
/*
 * File: api/data_rl_3_2.php
 * Fungsi: Laporan RL 3.2 Rawat Inap (REVISED)
 *
 * Deskripsi Perbaikan:
 * 1. Mengubah sumber data dari poliklinik (rawat jalan) menjadi bangsal (rawat inap) yang sesuai dengan Juknis.
 * 2. Mengimplementasikan pemetaan dari nama bangsal ke 36 Jenis Pelayanan standar SIRS.
 * 3. Menambahkan logika untuk menghitung alokasi tempat tidur.
 * 4. Memperbaiki logika perhitungan pasien awal/masuk/keluar/akhir bulan dan hari perawatan agar lebih akurat sesuai periode laporan.
 * 5. Logika untuk pasien pindahan/dipindahkan belum diimplementasikan karena struktur tabel (pindah_kamar) tidak ditemukan di skema database.
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

// 1. Inisialisasi array hasil dengan 36 Jenis Pelayanan standar
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
        'awal_bulan' => 0, 'masuk' => 0, 'pindahan' => 0, 'dipindahkan' => 0,
        'keluar_hidup' => 0, 'mati_l_kurang_48' => 0, 'mati_l_lebih_48' => 0,
        'mati_p_kurang_48' => 0, 'mati_p_lebih_48' => 0, 'lama_dirawat' => 0,
        'akhir_bulan' => 0, 'hari_perawatan' => 0, 'hp_vvip' => 0, 'hp_vip' => 0,
        'hp_1' => 0, 'hp_2' => 0, 'hp_3' => 0, 'hp_khusus' => 0, 'alokasi_tt' => 0
    ];
}

// 2. Fungsi untuk memetakan nama bangsal ke Jenis Pelayanan standar
function mapBangsalToJenisPelayanan($nama_bangsal) {
    $nama_bangsal_upper = strtoupper($nama_bangsal);
    
    if (strpos($nama_bangsal_upper, 'ICU') !== false && strpos($nama_bangsal_upper, 'NICU') === false && strpos($nama_bangsal_upper, 'PICU') === false && strpos($nama_bangsal_upper, 'ICCU') === false) return 'ICU';
    if (strpos($nama_bangsal_upper, 'NICU') !== false) return 'NICU';
    if (strpos($nama_bangsal_upper, 'PICU') !== false) return 'PICU';
    if (strpos($nama_bangsal_upper, 'HCU') !== false) return 'HCU';
    if (strpos($nama_bangsal_upper, 'ICCU') !== false) return 'ICCU/ICVCU';
    if (strpos($nama_bangsal_upper, 'RICU') !== false) return 'RICU';
    if (strpos($nama_bangsal_upper, 'ANAK') !== false) return 'Kesehatan Anak';
    if (strpos($nama_bangsal_upper, 'OBSTETRI') !== false || strpos($nama_bangsal_upper, 'OBGIN') !== false || strpos($nama_bangsal_upper, 'BERSALIN') !== false) return 'Obstetri';
    if (strpos($nama_bangsal_upper, 'GINEKOLOGI') !== false) return 'Ginekologi';
    if (strpos($nama_bangsal_upper, 'BEDAH') !== false) return 'Bedah';
    if (strpos($nama_bangsal_upper, 'ORTHOPEDI') !== false) return 'Bedah Orthopedi';
    if (strpos($nama_bangsal_upper, 'SARAF') !== false) return 'Saraf';
    if (strpos($nama_bangsal_upper, 'DALAM') !== false || strpos($nama_bangsal_upper, 'INTERNA') !== false) return 'Penyakit Dalam';
    if (strpos($nama_bangsal_upper, 'PARU') !== false) return 'Paru';
    if (strpos($nama_bangsal_upper, 'JANTUNG') !== false || strpos($nama_bangsal_upper, 'KARDIOLOGI') !== false) return 'Kardiologi';
    if (strpos($nama_bangsal_upper, 'JIWA') !== false) return 'Jiwa';
    if (strpos($nama_bangsal_upper, 'ISOLASI') !== false) return 'Isolasi';
    if (strpos($nama_bangsal_upper, 'PERINATOLOGI') !== false) return 'Perinatologi';
    
    return 'Umum';
}

// 3. Ambil data alokasi tempat tidur
$alokasi_tt = [];
$sql_tt = "
    SELECT b.nm_bangsal, COUNT(k.kd_kamar) as total_tt
    FROM bangsal b
    INNER JOIN kamar k ON b.kd_bangsal = k.kd_bangsal
    WHERE k.statusdata = '1'
    GROUP BY b.nm_bangsal;
";
$res_tt = mysqli_query($koneksi, $sql_tt);
while($row_tt = mysqli_fetch_assoc($res_tt)) {
    $jp = mapBangsalToJenisPelayanan($row_tt['nm_bangsal']);
    if (!isset($alokasi_tt[$jp])) $alokasi_tt[$jp] = 0;
    $alokasi_tt[$jp] += $row_tt['total_tt'];
}

foreach($alokasi_tt as $jp => $total) {
    if(isset($result_data[$jp])) {
        $result_data[$jp]['alokasi_tt'] = $total;
    }
}

// 4. Ambil data utama dari kamar_inap (termasuk deteksi pindahan)
$sql_main = "
    SELECT 
        ki.no_rawat,
        ki.tgl_masuk,
        ki.tgl_keluar,
        ki.jam_masuk,
        ki.jam_keluar,
        ki.stts_pulang,
        p.jk,
        b.nm_bangsal,
        k.kelas,
        ki.lama,
        pm.no_rkm_medis as pm_mati
    FROM kamar_inap ki
    INNER JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
    INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar
    INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
    LEFT JOIN pasien_mati pm ON p.no_rkm_medis = pm.no_rkm_medis AND pm.tanggal BETWEEN ki.tgl_masuk AND ki.tgl_keluar
    WHERE 
        ki.tgl_masuk <= ? AND 
        (ki.tgl_keluar >= ? OR ki.stts_pulang = '-' OR ki.stts_pulang = 'Pindah Kamar')
    ORDER BY ki.no_rawat, ki.tgl_masuk, ki.jam_masuk
";
$stmt = mysqli_prepare($koneksi, $sql_main);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $tgl_akhir, $tgl_awal);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $last_no_rawat = '';
    $last_jp = '';

    while ($row = mysqli_fetch_assoc($res)) {
        $jp = mapBangsalToJenisPelayanan($row['nm_bangsal']);
        $no_rawat = $row['no_rawat'];
        
        $tgl_masuk = $row['tgl_masuk'];
        $is_still_in = ($row['stts_pulang'] == '-' || $row['stts_pulang'] == 'Pindah Kamar');
        $tgl_keluar = $is_still_in ? $tgl_akhir : $row['tgl_keluar'];

        // Deteksi Pindahan (Jika ganti baris kamar_inap dengan no_rawat sama)
        if ($no_rawat == $last_no_rawat) {
            // Pasien yang sama, cek apakah Jenis Pelayanan berubah
            if ($jp != $last_jp) {
                if ($tgl_masuk >= $tgl_awal && $tgl_masuk <= $tgl_akhir) {
                    $result_data[$jp]['pindahan']++;
                    $result_data[$last_jp]['dipindahkan']++;
                }
            }
        } else {
            // Pasien Awal Bulan (Hanya untuk baris PERTAMA inapnya)
            if ($tgl_masuk < $tgl_awal) {
                $result_data[$jp]['awal_bulan']++;
            }
            // Pasien Masuk (Hanya untuk baris PERTAMA inapnya)
            if ($tgl_masuk >= $tgl_awal && $tgl_masuk <= $tgl_akhir) {
                $result_data[$jp]['masuk']++;
            }
        }

        // Simpan state untuk baris berikutnya
        $last_no_rawat = $no_rawat;
        $last_jp = $jp;

        // Pasien Keluar (Hidup/Mati) - Berlaku hanya jika baris terakhir (pulang/dirujuk/mati)
        if (!$is_still_in && $row['tgl_keluar'] >= $tgl_awal && $row['tgl_keluar'] <= $tgl_akhir) {
            $stts = strtolower($row['stts_pulang']);
            $is_mati = (strpos($stts, 'meninggal') !== false || !empty($row['pm_mati']));

            if (!$is_mati) {
                $result_data[$jp]['keluar_hidup']++;
            } else {
                $diff_seconds = strtotime($row['tgl_keluar'] . ' ' . $row['jam_keluar']) - strtotime($row['tgl_masuk'] . ' ' . $row['jam_masuk']);
                $kurang_48_jam = ($diff_seconds < 48 * 3600);

                if ($row['jk'] == 'L') {
                    $kurang_48_jam ? $result_data[$jp]['mati_l_kurang_48']++ : $result_data[$jp]['mati_l_lebih_48']++;
                } else {
                    $kurang_48_jam ? $result_data[$jp]['mati_p_kurang_48']++ : $result_data[$jp]['mati_p_lebih_48']++;
                }
            }
            $result_data[$jp]['lama_dirawat'] += $row['lama'];
        }
        
        // Pasien Akhir Bulan
        $result_data[$jp]['akhir_bulan'] = ($result_data[$jp]['awal_bulan'] + $result_data[$jp]['masuk'] + $result_data[$jp]['pindahan']) - ($result_data[$jp]['keluar_hidup'] + $result_data[$jp]['mati_l_kurang_48'] + $result_data[$jp]['mati_l_lebih_48'] + $result_data[$jp]['mati_p_kurang_48'] + $result_data[$jp]['mati_p_lebih_48'] + $result_data[$jp]['dipindahkan']);
        
        // Hari Perawatan
        $start_calc_ts = strtotime(max($tgl_masuk, $tgl_awal));
        $end_calc_ts = strtotime(min($row['tgl_keluar'] === '0000-00-00' ? $tgl_akhir : $row['tgl_keluar'], $tgl_akhir));
        
        if ($end_calc_ts >= $start_calc_ts) {
            $days = floor(($end_calc_ts - $start_calc_ts) / (60 * 60 * 24)) + 1;
            $result_data[$jp]['hari_perawatan'] += $days;

            // Rincian per kelas
            $kelas = strtolower($row['kelas']);
            if (strpos($kelas, 'vvip') !== false) $result_data[$jp]['hp_vvip'] += $days;
            else if (strpos($kelas, 'vip') !== false) $result_data[$jp]['hp_vip'] += $days;
            else if (strpos($kelas, '1') !== false || strpos($kelas, 'i') !== false) $result_data[$jp]['hp_1'] += $days;
            else if (strpos($kelas, '2') !== false || strpos($kelas, 'ii') !== false) $result_data[$jp]['hp_2'] += $days;
            else if (strpos($kelas, '3') !== false || strpos($kelas, 'iii') !== false) $result_data[$jp]['hp_3'] += $days;
            else $result_data[$jp]['hp_khusus'] += $days;
        }
    }
    mysqli_stmt_close($stmt);
}

$output = array_values($result_data);
echo json_encode(['data' => $output]);

mysqli_close($koneksi);
?>
