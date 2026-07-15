<?php
session_start();
// Pastikan hanya Super Admin yang bisa mengakses (Aturan #0 Keamanan)
if (!isset($_SESSION['casemix_login']) || $_SESSION['casemix_role'] !== 'Super Admin') { 
    header('HTTP/1.1 403 Forbidden'); 
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit; 
}

require_once('../conf/conf.php');

$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';

try {
    // Penggunaan PDO Murni (Aturan #11)
    $pdo = new PDO("mysql:host={$db_hostname};dbname={$db_name};charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. ACTION: Search User untuk Select2
    if ($action === 'search') {
        $q = isset($_GET['q']) ? "%{$_GET['q']}%" : "%";
        
        // Ambil daftar kolom dinamis untuk hak akses INACBG (menghindari error jika struktur tabel berbeda)
        $q_cols = $pdo->query("SHOW COLUMNS FROM user WHERE Field LIKE '%inacbg%' AND Type LIKE 'enum%'");
        $inacbg_cols = [];
        while ($col = $q_cols->fetch(PDO::FETCH_ASSOC)) {
            $inacbg_cols[] = $col['Field'];
        }
        
        $select_inacbg = empty($inacbg_cols) ? "" : ", u." . implode(", u.", $inacbg_cols);

        // Cari dari tabel pegawai, dokter, petugas yang id-nya ada di user
        $sql = "SELECT AES_DECRYPT(u.id_user, 'nur') as id_user, 
                COALESCE(p.nama, d.nm_dokter, ptx.nama, AES_DECRYPT(u.id_user, 'nur')) as nama
                $select_inacbg
                FROM user u
                LEFT JOIN pegawai p ON p.nik = AES_DECRYPT(u.id_user, 'nur')
                LEFT JOIN dokter d ON d.kd_dokter = AES_DECRYPT(u.id_user, 'nur')
                LEFT JOIN petugas ptx ON ptx.nip = AES_DECRYPT(u.id_user, 'nur')
                WHERE p.nama LIKE :q1 OR d.nm_dokter LIKE :q2 OR ptx.nama LIKE :q3 OR AES_DECRYPT(u.id_user, 'nur') LIKE :q4
                LIMIT 30";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['q1' => $q, 'q2' => $q, 'q3' => $q, 'q4' => $q]);
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $has_access = false;
            foreach ($inacbg_cols as $col) {
                if (isset($row[$col]) && $row[$col] == 'true') {
                    $has_access = true;
                    break;
                }
            }
            
            $results[] = [
                'id' => $row['id_user'],
                'text' => $row['nama'] . ' (' . $row['id_user'] . ')',
                'disabled' => $has_access // Disable if already has access
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
        exit;
    }

    // 2. ACTION: Grant atau Revoke Akses
    if ($action === 'update_access') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed');
            exit;
        }

        // Validasi CSRF Token
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['status' => 'error', 'message' => 'CSRF Token Invalid!']);
            exit;
        }

        $id_user = $_POST['id_user'] ?? '';
        $type = $_POST['type'] ?? 'grant'; // grant / revoke

        if (empty($id_user)) {
            echo json_encode(['status' => 'error', 'message' => 'User ID kosong']);
            exit;
        }

        $val = ($type === 'grant') ? 'true' : 'false';
        
        // Ambil kolom dinamis untuk update
        $q_cols = $pdo->query("SHOW COLUMNS FROM user WHERE Field LIKE '%inacbg%' AND Type LIKE 'enum%'");
        $set_clauses = [];
        while ($col = $q_cols->fetch(PDO::FETCH_ASSOC)) {
            $set_clauses[] = $col['Field'] . " = :v";
        }

        if (empty($set_clauses)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ditemukan kolom hak akses INACBG di tabel user.']);
            exit;
        }

        $set_sql = implode(", ", $set_clauses);
        
        $sql = "UPDATE user SET $set_sql WHERE AES_DECRYPT(id_user, 'nur') = :id";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'v' => $val,
            'id' => $id_user
        ]);
        
        $msg = ($type === 'grant') ? 'Hak akses Casemix berhasil diberikan.' : 'Hak akses Casemix berhasil dicabut.';
        echo json_encode(['status' => 'success', 'message' => $msg]);
        exit;
    }

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>
