<?php
// File: api/fix_schema.php
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

if (!isset($_SESSION['casemix_login']) || $_SESSION['casemix_role'] !== 'Super Admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Hanya untuk Super Admin.']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : 'apply';

if ($action === 'apply') {
    // 1. Check if it is already a composite PK
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
        echo json_encode(['status' => 'info', 'message' => 'Tabel perkiraan_biaya_ranap sudah menggunakan composite primary key (Multiple ICD aktif).']);
        exit;
    }

    // 2. Alter table to composite primary key
    $sql_alter = "ALTER TABLE perkiraan_biaya_ranap DROP PRIMARY KEY, ADD PRIMARY KEY (no_rawat, kd_penyakit)";
    if (mysqli_query($koneksi, $sql_alter)) {
        echo json_encode(['status' => 'success', 'message' => 'Berhasil mengubah schema tabel ke Multiple ICD (Composite PK).']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah schema tabel: ' . mysqli_error($koneksi)]);
    }
} elseif ($action === 'rollback') {
    // 1. Check if it is a composite PK
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

    if (!$is_multiple) {
        echo json_encode(['status' => 'info', 'message' => 'Tabel perkiraan_biaya_ranap sudah menggunakan single primary key (Single ICD aktif).']);
        exit;
    }

    // 2. Clean up duplicates keeping the first one (lexicographically smallest kd_penyakit)
    mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS = 0");
    $sql_clean = "
        DELETE p1 FROM perkiraan_biaya_ranap p1
        INNER JOIN perkiraan_biaya_ranap p2 
        ON p1.no_rawat = p2.no_rawat AND p1.kd_penyakit > p2.kd_penyakit
    ";
    mysqli_query($koneksi, $sql_clean);

    // 3. Revert table to single primary key
    $sql_alter = "ALTER TABLE perkiraan_biaya_ranap DROP PRIMARY KEY, ADD PRIMARY KEY (no_rawat)";
    $success = mysqli_query($koneksi, $sql_alter);
    $err = mysqli_error($koneksi);
    mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS = 1");

    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Berhasil mengembalikan schema tabel ke Single ICD (Single PK) dan membersihkan data duplikat.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengembalikan schema tabel: ' . $err]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali.']);
}

mysqli_close($koneksi);
?>