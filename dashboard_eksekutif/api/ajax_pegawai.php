<?php
/*
 * File: api/ajax_pegawai.php (SECURITY FIX)
 * - FIX KRITIS: SQL Injection → Prepared Statement dengan LIKE + wildcard aman
 * - Auth guard sudah dihandle via auto_prepend_file (api/.htaccess)
 */

ob_start();
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');
require_once(dirname(__DIR__) . '/config/koneksi.php');

$data = [];

try {
    $koneksi_pdo->exec("SET sql_mode = ''");

    // Ambil parameter pencarian dan validasi ketat
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';

    if (!empty($search) && strlen($search) >= 2) {
        // --- FIX SQL INJECTION: Prepared Statement dengan wildcard LIKE ---
        // Wildcard % ditempatkan di dalam nilai bind_param, bukan di query string
        $like_param = '%' . $search . '%';

        $stmt = $koneksi_pdo->prepare(
            "SELECT nik, nama FROM pegawai WHERE nik LIKE :search1 OR nama LIKE :search2 LIMIT 20"
        );
        $stmt->execute([':search1' => $like_param, ':search2' => $like_param]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = [
                'id'   => htmlspecialchars($row['nik']),  // value (username)
                'text' => htmlspecialchars($row['nik'] . ' - ' . $row['nama']) // label
            ];
        }
    }
} catch (PDOException $e) {
    // Return empty array on failure for safe default
    $data = [];
}

ob_end_clean();
echo json_encode($data);
?>