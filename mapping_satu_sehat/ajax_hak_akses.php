<?php
/**
 * ajax_hak_akses.php — Backend untuk form hak_akses.php (Management RBAC)
 * Dibatasi HANYA untuk Super Admin.
 */
error_reporting(0);
ini_set('display_errors', 0);

require_once 'conf.php';
require_once 'auth_check.php';
require_login();

// Validasi extra: mutlak hanya Super Admin yang bisa mengakses API ini
if (empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Hanya Super Admin yang diizinkan mengakses data ini.']);
    exit;
}

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    // ========================================================
    // 1. CARI USER (Select2 AJAX)
    // ========================================================
    if ($action === 'search_user') {
        $q = isset($_GET['term']) ? trim($_GET['term']) : '';
        // Kita cari di tabel user. Nama ada di field id_user (terenkripsi) atau mungkin ada field lain?
        // Dalam setup Khanza biasa, user memiliki id_user (AES), dan mungkin terhubung ke pegawai/petugas.
        // Berdasarkan info: tabel user (field: id_user AES).
        $stmt = $pdo->prepare(
            "SELECT 
                AES_DECRYPT(u.id_user, 'nur') as id, 
                CONCAT(AES_DECRYPT(u.id_user, 'nur'), ' - ', COALESCE(p.nama, d.nm_dokter, pt.nama, 'Unknown')) as text 
             FROM user u
             LEFT JOIN pegawai p ON AES_DECRYPT(u.id_user, 'nur') = p.nik
             LEFT JOIN dokter d ON AES_DECRYPT(u.id_user, 'nur') = d.kd_dokter
             LEFT JOIN petugas pt ON AES_DECRYPT(u.id_user, 'nur') = pt.nip
             WHERE AES_DECRYPT(u.id_user, 'nur') LIKE :q 
                OR p.nama LIKE :q 
                OR d.nm_dokter LIKE :q 
                OR pt.nama LIKE :q
             LIMIT 40"
        );
        $stmt->execute([':q' => "%$q%"]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Filter out nulls if decryption failed
        $valid_results = [];
        foreach ($results as $r) {
            if ($r['id']) {
                $valid_results[] = $r;
            }
        }
        
        echo json_encode(['results' => $valid_results]);
        exit;
    }

    // ========================================================
    // 2. SIMPAN HAK AKSES PER MODUL
    // ========================================================
    if ($action === 'save_akses') {
        validate_csrf();

        $modul = $_POST['modul'] ?? '';
        $users = $_POST['users'] ?? []; // Array dari select2
        
        $valid_moduls = [
            'satu_sehat_mapping_obat',
            'satu_sehat_mapping_lab',
            'satu_sehat_mapping_radiologi',
            'satu_sehat_mapping_vaksin'
        ];

        if (!in_array($modul, $valid_moduls)) {
            echo json_encode(['status' => 'error', 'message' => 'Modul tidak valid.']);
            exit;
        }

        $pdo->beginTransaction();

        try {
            // Step 1: Cabut semua hak akses untuk modul ini dari semua user (set jadi 'false')
            $stmtReset = $pdo->prepare("UPDATE user SET $modul = 'false'");
            $stmtReset->execute();

            // Step 2: Berikan hak akses 'true' HANYA pada user yang dikirim
            if (!empty($users) && is_array($users)) {
                $stmtGrant = $pdo->prepare("UPDATE user SET $modul = 'true' WHERE AES_DECRYPT(id_user, 'nur') = ?");
                foreach ($users as $u) {
                    $stmtGrant->execute([$u]);
                }
            }

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => "Hak akses modul berhasil diperbarui!"]);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
