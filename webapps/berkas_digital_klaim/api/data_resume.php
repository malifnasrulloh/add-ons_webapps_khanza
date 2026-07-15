<?php
/*
 * File: api/data_resume.php
 * Fungsi: API untuk mengambil data resume medis rawat jalan dan rawat inap
 */

error_reporting(0);
ini_set('display_errors', 0);

if(file_exists(__DIR__ . '/../../conf/conf.php')) {
    require_once(__DIR__ . '/../../conf/conf.php');
} else {
    require_once(__DIR__ . '/../conf/conf.php');
}

header('Content-Type: application/json');
$koneksi = bukakoneksi();

session_start();
if (!isset($_SESSION['casemix_login'])) { 
    http_response_code(403); 
    echo json_encode(['error' => 'Akses Ditolak']); 
    exit; 
}

$no_rawat = $_GET['no_rawat'] ?? '';
if (empty($no_rawat)) {
    echo json_encode(['data' => null]);
    exit;
}

// Helper function to dynamically check existing columns
if (!function_exists('getExistingColumns')) {
    function getExistingColumns($koneksi, $table) {
        $columns = [];
        try {
            $res = mysqli_query($koneksi, "DESCRIBE $table");
            if ($res) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $columns[] = strtolower($row['Field']);
                }
                mysqli_free_result($res);
            }
        } catch (Throwable $e) {
            // Ignore exception and return empty
        }
        return $columns;
    }
}

// Helper function to recursively sanitize and convert strings to valid UTF-8
if (!function_exists('utf8ize')) {
    function utf8ize($mixed) {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, "UTF-8", "UTF-8,ISO-8859-1,ASCII,Windows-1252");
        }
        return $mixed;
    }
}

$data = null;

try {
    // 1. Ambil data dari resume_pasien_ranap (Rawat Inap)
    $existing_ranap = getExistingColumns($koneksi, 'resume_pasien_ranap');
    $ranap_map = [
        'keluhan_utama' => 'r.keluhan_utama',
        'pemeriksaan_fisik' => 'r.pemeriksaan_fisik',
        'jalannya_penyakit' => 'r.jalannya_penyakit',
        'pemeriksaan_penunjang' => 'r.pemeriksaan_penunjang',
        'hasil_laborat' => 'r.hasil_laborat',
        'tindakan_dan_operasi' => 'r.tindakan_dan_operasi',
        'obat_di_rs' => 'r.obat_di_rs',
        'diagnosa_utama' => 'r.diagnosa_utama',
        'kd_diagnosa_utama' => 'r.kd_diagnosa_utama',
        'diagnosa_sekunder' => 'r.diagnosa_sekunder',
        'kd_diagnosa_sekunder' => 'r.kd_diagnosa_sekunder',
        'prosedur_utama' => 'r.prosedur_utama',
        'kd_prosedur_utama' => 'r.kd_prosedur_utama',
        'prosedur_sekunder' => 'r.prosedur_sekunder',
        'kd_prosedur_sekunder' => 'r.kd_prosedur_sekunder',
        'obat_pulang' => 'r.obat_pulang',
    ];

    $select_ranap_fields = [];
    foreach ($ranap_map as $alias => $field) {
        $col_name = str_replace('r.', '', $field);
        if (in_array(strtolower($col_name), $existing_ranap)) {
            $select_ranap_fields[] = "$field AS $alias";
        } else {
            $select_ranap_fields[] = "'' AS $alias";
        }
    }
    $select_ranap_sql = implode(", ", $select_ranap_fields);

    $sql_ranap = "
        SELECT 
            'Ranap' AS tipe,
            $select_ranap_sql,
            d.nm_dokter
        FROM resume_pasien_ranap r
        LEFT JOIN dokter d ON r.kd_dokter = d.kd_dokter
        WHERE r.no_rawat = ?
    ";
    
    $stmt = mysqli_prepare($koneksi, $sql_ranap);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $no_rawat);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            $keluhan = trim($row['keluhan_utama'] ?? '');
            $diagnosa = trim($row['diagnosa_utama'] ?? '');
            if (($keluhan !== '' && $keluhan !== '-') || ($diagnosa !== '' && $diagnosa !== '-')) {
                $data = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
} catch (Throwable $e) {
    // Suppress and fallback
    $data = null;
}

// 2. Ambil data dari resume_pasien (Rawat Jalan) jika belum ditemukan di Ranap
if (!$data) {
    try {
        $existing_ralan = getExistingColumns($koneksi, 'resume_pasien');
        $ralan_map = [
            'keluhan_utama' => 'r.keluhan_utama',
            'pemeriksaan_fisik' => "''",
            'jalannya_penyakit' => 'r.jalannya_penyakit',
            'pemeriksaan_penunjang' => 'r.pemeriksaan_penunjang',
            'hasil_laborat' => 'r.hasil_laborat',
            'tindakan_dan_operasi' => "''",
            'obat_di_rs' => "''",
            'diagnosa_utama' => 'r.diagnosa_utama',
            'kd_diagnosa_utama' => 'r.kd_diagnosa_utama',
            'diagnosa_sekunder' => 'r.diagnosa_sekunder',
            'kd_diagnosa_sekunder' => 'r.kd_diagnosa_sekunder',
            'prosedur_utama' => 'r.prosedur_utama',
            'kd_prosedur_utama' => 'r.kd_prosedur_utama',
            'prosedur_sekunder' => 'r.prosedur_sekunder',
            'kd_prosedur_sekunder' => 'r.kd_prosedur_sekunder',
            'obat_pulang' => 'r.obat_pulang',
        ];

        $select_ralan_fields = [];
        foreach ($ralan_map as $alias => $field) {
            if ($field === "''") {
                $select_ralan_fields[] = "'' AS $alias";
            } else {
                $col_name = str_replace('r.', '', $field);
                if (in_array(strtolower($col_name), $existing_ralan)) {
                    $select_ralan_fields[] = "$field AS $alias";
                } else {
                    $select_ralan_fields[] = "'' AS $alias";
                }
            }
        }
        $select_ralan_sql = implode(", ", $select_ralan_fields);

        $sql_ralan = "
            SELECT 
                'Ralan' AS tipe,
                $select_ralan_sql,
                d.nm_dokter
            FROM resume_pasien r
            LEFT JOIN dokter d ON r.kd_dokter = d.kd_dokter
            WHERE r.no_rawat = ?
        ";
        
        $stmt = mysqli_prepare($koneksi, $sql_ralan);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $no_rawat);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($res)) {
                $keluhan = trim($row['keluhan_utama'] ?? '');
                $diagnosa = trim($row['diagnosa_utama'] ?? '');
                if (($keluhan !== '' && $keluhan !== '-') || ($diagnosa !== '' && $diagnosa !== '-')) {
                    $data = $row;
                }
            }
            mysqli_stmt_close($stmt);
        }
    } catch (Throwable $e) {
        $data = null;
    }
}

$data = utf8ize($data);

echo json_encode(['data' => $data]);
mysqli_close($koneksi);
?>
