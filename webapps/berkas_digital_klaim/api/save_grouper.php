<?php
// File: api/save_grouper.php
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
if (!$koneksi) {
    echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal']);
    exit;
}

if (!isset($_SESSION['casemix_login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi habis. Silakan login ulang.']);
    exit;
}

$caseId   = isset($_POST['case']) ? str_replace('-', '/', $_POST['case']) : '';
$kode_raw = isset($_POST['kode']) ? $_POST['kode'] : '';
$tarif    = isset($_POST['tarif']) ? floatval($_POST['tarif']) : 0;

if (empty($caseId)) {
    echo json_encode(['status' => 'error', 'message' => 'No. Rawat tidak valid']);
    exit;
}

if ($tarif <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Nominal harus lebih dari 0']);
    exit;
}

// Parse kode array or string safely
$kodes = [];
if (is_array($kode_raw)) {
    foreach ($kode_raw as $k) {
        $trimmed = trim($k);
        if ($trimmed !== '') {
            $kodes[] = $trimmed;
        }
    }
} else {
    $trimmed = trim($kode_raw);
    if ($trimmed !== '') {
        $kodes[] = $trimmed;
    }
}

// Check if Multiple ICD (Composite Primary Key) schema is active
$q_pk = mysqli_query($koneksi, "
    SELECT COUNT(*) as pk_count 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'perkiraan_biaya_ranap' 
      AND CONSTRAINT_NAME = 'PRIMARY'
");
$is_multiple = false;
if ($q_pk && $r_pk = mysqli_fetch_assoc($q_pk)) {
    if ((int)$r_pk['pk_count'] > 1) {
        $is_multiple = true;
    }
}

if ($is_multiple) {
    // 1. Delete all existing mappings for this patient
    mysqli_query($koneksi, "DELETE FROM perkiraan_biaya_ranap WHERE no_rawat='$caseId'");
    
    // 2. Insert new ones
    if (!empty($kodes)) {
        $q_insert = "INSERT INTO perkiraan_biaya_ranap (no_rawat, kd_penyakit, tarif) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($koneksi, $q_insert);
        if ($stmt) {
            foreach ($kodes as $kd) {
                mysqli_stmt_bind_param($stmt, "ssd", $caseId, $kd, $tarif);
                mysqli_stmt_execute($stmt);
            }
            echo json_encode(['status' => 'success', 'message' => 'Data multiple ICD berhasil disimpan!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyiapkan statement database.']);
        }
    } else {
        echo json_encode(['status' => 'success', 'message' => 'Data ICD berhasil dibersihkan!']);
    }
} else {
    // Legacy single mode
    $kode = isset($kodes[0]) ? $kodes[0] : '';
    if (empty($kode)) {
        mysqli_query($koneksi, "DELETE FROM perkiraan_biaya_ranap WHERE no_rawat='$caseId'");
        echo json_encode(['status' => 'success', 'message' => 'Data ICD berhasil dihapus!']);
    } else {
        $cek = mysqli_query($koneksi, "SELECT no_rawat FROM perkiraan_biaya_ranap WHERE no_rawat='$caseId'");
        if (mysqli_num_rows($cek) > 0) {
            $q = "UPDATE perkiraan_biaya_ranap SET kd_penyakit=?, tarif=? WHERE no_rawat=?";
            $stmt = mysqli_prepare($koneksi, $q);
            mysqli_stmt_bind_param($stmt, "sds", $kode, $tarif, $caseId);
        } else {
            $q = "INSERT INTO perkiraan_biaya_ranap (kd_penyakit, tarif, no_rawat) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($koneksi, $q);
            mysqli_stmt_bind_param($stmt, "sds", $kode, $tarif, $caseId);
        }
        
        if ($stmt && mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'Data ICD berhasil disimpan!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . mysqli_error($koneksi)]);
        }
    }
}

mysqli_close($koneksi);
?>