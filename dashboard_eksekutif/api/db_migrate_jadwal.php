<?php
/**
 * api/db_migrate_jadwal.php
 * Superadmin-only endpoint untuk memeriksa & menjalankan ALTER TABLE
 * pada tabel yang perlu penambahan nilai ENUM 'Libur' dan 'Cuti'.
 *
 * Tabel yang diperiksa:
 *   - jadwal_pegawai  : kolom h1-h31
 *   - jadwal_tambahan : kolom h1-h31
 *   - rekap_presensi  : kolom shift
 *   - temporary_presensi : kolom shift
 */

session_start();
require_once dirname(__DIR__) . '/config/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

// ─── Guard: hanya Super Admin ────────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Super Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya Super Admin.']);
    exit;
}

$act = $_GET['act'] ?? 'status';

// ─── Definisi ENUM Target ─────────────────────────────────────────────────────
// Nilai ENUM lengkap yang HARUS ada di kolom h1-h31 jadwal_pegawai/tambahan
$enum_target = "'Pagi','Pagi2','Pagi3','Pagi4','Pagi5','Pagi6','Pagi7','Pagi8','Pagi9','Pagi10',"
             . "'Siang','Siang2','Siang3','Siang4','Siang5','Siang6','Siang7','Siang8','Siang9','Siang10',"
             . "'Malam','Malam2','Malam3','Malam4','Malam5','Malam6','Malam7','Malam8','Malam9','Malam10',"
             . "'Midle Pagi1','Midle Pagi2','Midle Pagi3','Midle Pagi4','Midle Pagi5',"
             . "'Midle Pagi6','Midle Pagi7','Midle Pagi8','Midle Pagi9','Midle Pagi10',"
             . "'Midle Siang1','Midle Siang2','Midle Siang3','Midle Siang4','Midle Siang5',"
             . "'Midle Siang6','Midle Siang7','Midle Siang8','Midle Siang9','Midle Siang10',"
             . "'Midle Malam1','Midle Malam2','Midle Malam3','Midle Malam4','Midle Malam5',"
             . "'Midle Malam6','Midle Malam7','Midle Malam8','Midle Malam9','Midle Malam10',"
             . "'',"
             . "'Libur','Cuti'";

// ENUM untuk shift pada rekap_presensi dan temporary_presensi
$enum_shift_target = "'Pagi','Pagi2','Pagi3','Pagi4','Pagi5','Pagi6','Pagi7','Pagi8','Pagi9','Pagi10',"
                   . "'Siang','Siang2','Siang3','Siang4','Siang5','Siang6','Siang7','Siang8','Siang9','Siang10',"
                   . "'Malam','Malam2','Malam3','Malam4','Malam5','Malam6','Malam7','Malam8','Malam9','Malam10',"
                   . "'Midle Pagi1','Midle Pagi2','Midle Pagi3','Midle Pagi4','Midle Pagi5',"
                   . "'Midle Pagi6','Midle Pagi7','Midle Pagi8','Midle Pagi9','Midle Pagi10',"
                   . "'Midle Siang1','Midle Siang2','Midle Siang3','Midle Siang4','Midle Siang5',"
                   . "'Midle Siang6','Midle Siang7','Midle Siang8','Midle Siang9','Midle Siang10',"
                   . "'Midle Malam1','Midle Malam2','Midle Malam3','Midle Malam4','Midle Malam5',"
                   . "'Midle Malam6','Midle Malam7','Midle Malam8','Midle Malam9','Midle Malam10',"
                   . "'Libur','Cuti'";

// ─── Helper: Cek apakah suatu kolom ENUM sudah mengandung 'Libur' dan 'Cuti' ─
function isEnumMigrated($db_pdo, $tabel, $kolom) {
    try {
        // Ambil nama DB aktif
        $r = $db_pdo->query("SELECT DATABASE() AS db");
        $dbname = $r ? $r->fetch(PDO::FETCH_ASSOC)['db'] : '';

        $stmt = $db_pdo->prepare(
            "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = :dbname AND TABLE_NAME = :tabel AND COLUMN_NAME = :kolom LIMIT 1"
        );
        $stmt->execute([':dbname' => $dbname, ':tabel' => $tabel, ':kolom' => $kolom]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return ['exists' => false, 'migrated' => false, 'type' => null];

        $hasLibur = (strpos($row['COLUMN_TYPE'], "'Libur'") !== false);
        $hasCuti  = (strpos($row['COLUMN_TYPE'], "'Cuti'")  !== false);
        return [
            'exists'   => true,
            'migrated' => ($hasLibur && $hasCuti),
            'hasLibur' => $hasLibur,
            'hasCuti'  => $hasCuti,
            'type'     => $row['COLUMN_TYPE']
        ];
    } catch (PDOException $e) {
        return ['status' => 'error', 'info' => $e->getMessage()];
    }
}

// ─── act=status : Cek status seluruh tabel ────────────────────────────────────
if ($act === 'status') {
    $result = [];

    // jadwal_pegawai & jadwal_tambahan: cek h1 sebagai representatif
    foreach (['jadwal_pegawai', 'jadwal_tambahan'] as $tbl) {
        $info = isEnumMigrated($koneksi_pdo, $tbl, 'h1');
        // Hitung berapa kolom (h1-h31) yang sudah dimigrasikan
        $migrated_count = 0;
        for ($i = 1; $i <= 31; $i++) {
            $ci = isEnumMigrated($koneksi_pdo, $tbl, "h$i");
            if (!empty($ci['migrated'])) $migrated_count++;
        }
        $result[$tbl] = [
            'label'          => $tbl,
            'columns_total'  => 31,
            'columns_ok'     => $migrated_count,
            'needs_migration' => ($migrated_count < 31),
            'sample_type'    => $info['type'] ?? null
        ];
    }

    // rekap_presensi & temporary_presensi: cek kolom shift
    foreach (['rekap_presensi', 'temporary_presensi'] as $tbl) {
        $info = isEnumMigrated($koneksi_pdo, $tbl, 'shift');
        $result[$tbl] = [
            'label'           => $tbl,
            'columns_total'   => 1,
            'columns_ok'      => !empty($info['migrated']) ? 1 : 0,
            'needs_migration' => empty($info['migrated']),
            'sample_type'     => $info['type'] ?? null
        ];
    }

    echo json_encode(['success' => true, 'status' => $result]);
    exit;
}

// ─── act=migrate : Jalankan ALTER TABLE ──────────────────────────────────────
if ($act === 'migrate') {
    $logs    = [];
    $errors  = [];
    
    try {
        $koneksi_pdo->beginTransaction();

        // ── 1. Bangun ALTER TABLE jadwal_pegawai (h1-h31) ─────────────────────────
        foreach (['jadwal_pegawai', 'jadwal_tambahan'] as $tbl) {
            $modify_parts = [];
            for ($i = 1; $i <= 31; $i++) {
                $ci = isEnumMigrated($koneksi_pdo, $tbl, "h$i");
                if (isset($ci['migrated']) && !$ci['migrated']) {
                    $modify_parts[] = "MODIFY COLUMN `h$i` enum($enum_target) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT ''";
                }
            }
            if (!empty($modify_parts)) {
                $sql = "ALTER TABLE `$tbl` " . implode(', ', $modify_parts);
                if ($koneksi_pdo->query($sql)) {
                    $logs[] = "✅ ALTER TABLE $tbl : " . count($modify_parts) . " kolom h-col berhasil diperbarui.";
                } else {
                    $errors[] = "❌ ALTER TABLE $tbl GAGAL (query execution failed).";
                }
            } else {
                $logs[] = "ℹ️  $tbl : semua kolom sudah up-to-date, dilewati.";
            }
        }

        // ── 2. Bangun ALTER TABLE rekap_presensi.shift ────────────────────────────
        foreach (['rekap_presensi', 'temporary_presensi'] as $tbl) {
            $ci = isEnumMigrated($koneksi_pdo, $tbl, 'shift');
            if (isset($ci['migrated']) && !$ci['migrated']) {
                // Shift pada tabel presensi: tetap NOT NULL (tidak ada default)
                $sql = "ALTER TABLE `$tbl` MODIFY COLUMN `shift` enum($enum_shift_target) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL";
                if ($koneksi_pdo->query($sql)) {
                    $logs[] = "✅ ALTER TABLE $tbl.shift berhasil diperbarui.";
                } else {
                    $errors[] = "❌ ALTER TABLE $tbl.shift GAGAL (query execution failed).";
                }
            } else {
                $logs[] = "ℹ️  $tbl.shift : sudah up-to-date, dilewati.";
            }
        }

        if (empty($errors)) {
            $koneksi_pdo->commit();
            echo json_encode(['success' => true,  'logs' => $logs, 'errors' => []]);
        } else {
            $koneksi_pdo->rollBack();
            echo json_encode(['success' => false, 'logs' => $logs, 'errors' => $errors]);
        }
    } catch (PDOException $e) {
        $koneksi_pdo->rollBack();
        $errors[] = "Exception: " . $e->getMessage();
        echo json_encode(['success' => false, 'logs' => $logs, 'errors' => $errors]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
