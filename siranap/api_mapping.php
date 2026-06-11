<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// 1. Unprotected actions: get_setting, login, logout
if ($action === 'get_setting') {
    $stmt = $pdo->query("SELECT nama_instansi, logo FROM setting LIMIT 1");
    $data = $stmt->fetch();
    if ($data) {
        $data['logo'] = 'data:image/jpeg;base64,' . base64_encode($data['logo']);
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Setting not found']);
    }
    exit;
}

if ($action === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        echo json_encode(['status' => 'error', 'message' => 'Username dan password wajib diisi.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE AES_DECRYPT(usere, 'nur') = ? AND AES_DECRYPT(passworde, 'windi') = ?");
        $stmt->execute([$username, $password]);
        $admin = $stmt->fetch();

        if ($admin) {
            $_SESSION['siranap_admin'] = $username;
            echo json_encode(['status' => 'success', 'message' => 'Login berhasil.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Username atau password salah.']);
        }
    } catch (\PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'logout') {
    unset($_SESSION['siranap_admin']);
    session_destroy();
    echo json_encode(['status' => 'success', 'message' => 'Logout berhasil.']);
    exit;
}

// 2. Protected actions: must have valid session
if (!isset($_SESSION['siranap_admin'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Silakan login terlebih dahulu.']);
    exit;
}

if ($action === 'list_bangsal') {
    $stmt = $pdo->query("SELECT kd_bangsal, nm_bangsal FROM bangsal WHERE status = '1' ORDER BY nm_bangsal ASC");
    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'list_mapping') {
    $stmt = $pdo->query("SELECT a.id_tt_sirsonline, a.nm_ruang_sirsonline, a.kd_bangsal, a.covid, b.nm_bangsal FROM sirsonline_ketersediaan_kamar a JOIN bangsal b ON a.kd_bangsal = b.kd_bangsal ORDER BY b.nm_bangsal ASC");
    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'save') {
    $id_tt = $_POST['id_tt'] ?? '';
    $nm_ruang = $_POST['nm_ruang'] ?? '';
    $kd_bangsal = $_POST['kd_bangsal'] ?? '';
    $covid = $_POST['covid'] ?? '0';
    $old_id_tt = $_POST['old_id_tt'] ?? '';
    $old_nm_ruang = $_POST['old_nm_ruang'] ?? '';
    $old_kd_bangsal = $_POST['old_kd_bangsal'] ?? '';

    if (!$id_tt || !$nm_ruang || !$kd_bangsal) {
        echo json_encode(['status' => 'error', 'message' => 'Lengkapi data']);
        exit;
    }

    try {
        if ($old_id_tt && $old_nm_ruang && $old_kd_bangsal) {
            // Edit Mode (DELETE & INSERT because primary keys changed)
            $pdo->beginTransaction();
            $stmtDel = $pdo->prepare("DELETE FROM sirsonline_ketersediaan_kamar WHERE id_tt_sirsonline=? AND nm_ruang_sirsonline=? AND kd_bangsal=?");
            $stmtDel->execute([$old_id_tt, $old_nm_ruang, $old_kd_bangsal]);

            $stmtIns = $pdo->prepare("INSERT INTO sirsonline_ketersediaan_kamar (id_tt_sirsonline, nm_ruang_sirsonline, kd_bangsal, covid) VALUES (?, ?, ?, ?)");
            $stmtIns->execute([$id_tt, $nm_ruang, $kd_bangsal, $covid]);
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Data berhasil diubah']);
        } else {
            // Add Mode
            $stmt = $pdo->prepare("INSERT INTO sirsonline_ketersediaan_kamar (id_tt_sirsonline, nm_ruang_sirsonline, kd_bangsal, covid) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id_tt, $nm_ruang, $kd_bangsal, $covid]);
            echo json_encode(['status' => 'success', 'message' => 'Data berhasil ditambah']);
        }
    } catch (\PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete') {
    $id_tt = $_POST['id_tt'] ?? '';
    $nm_ruang = $_POST['nm_ruang'] ?? '';
    $kd_bangsal = $_POST['kd_bangsal'] ?? '';

    try {
        $stmt = $pdo->prepare("DELETE FROM sirsonline_ketersediaan_kamar WHERE id_tt_sirsonline=? AND nm_ruang_sirsonline=? AND kd_bangsal=?");
        $stmt->execute([$id_tt, $nm_ruang, $kd_bangsal]);
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus']);
    } catch (\PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'setup_db') {
    try {
        $messages = [];
        $tableName = 'sirsonline_ketersediaan_kamar';
        
        // Check if table exists using SHOW TABLES LIKE
        $stmt = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
        $tableExists = ($stmt->rowCount() > 0);

        if (!$tableExists) {
            // Table doesn't exist -> Create it
            $createSql = "CREATE TABLE `sirsonline_ketersediaan_kamar` (
                `id_tt_sirsonline` varchar(15) NOT NULL,
                `nm_ruang_sirsonline` enum('VVIP','VIP','Kelas Utama','Kelas I','Kelas II','Kelas III','HCU','NICU','Isolasi','Perinatologi') NOT NULL DEFAULT 'Kelas I',
                `kd_bangsal` varchar(5) NOT NULL,
                `covid` enum('0','1') NOT NULL DEFAULT '0',
                PRIMARY KEY (`id_tt_sirsonline`, `kd_bangsal`, `nm_ruang_sirsonline`),
                KEY `kd_bangsal` (`kd_bangsal`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
            
            $pdo->exec($createSql);
            $messages[] = [
                'type' => 'create',
                'table' => $tableName,
                'status' => 'success',
                'text' => "Tabel `{$tableName}` berhasil dibuat."
            ];
        } else {
            // Table exists -> Check columns
            $descStmt = $pdo->query("DESCRIBE `{$tableName}`");
            $existingCols = [];
            while ($descRow = $descStmt->fetch(PDO::FETCH_ASSOC)) {
                $existingCols[strtolower($descRow['Field'])] = strtolower($descRow['Type']);
            }

            $requiredCols = [
                'id_tt_sirsonline' => "varchar(15) NOT NULL",
                'nm_ruang_sirsonline' => "enum('VVIP','VIP','Kelas Utama','Kelas I','Kelas II','Kelas III','HCU','NICU','Isolasi','Perinatologi') NOT NULL DEFAULT 'Kelas I'",
                'kd_bangsal' => "varchar(5) NOT NULL",
                'covid' => "enum('0','1') NOT NULL DEFAULT '0'"
            ];

            foreach ($requiredCols as $colName => $colDef) {
                if (!isset($existingCols[strtolower($colName)])) {
                    // Column is missing -> Alter table
                    $pdo->exec("ALTER TABLE `{$tableName}` ADD COLUMN `{$colName}` {$colDef}");
                    $messages[] = [
                        'type' => 'alter_add',
                        'table' => $tableName,
                        'column' => $colName,
                        'status' => 'success',
                        'text' => "Kolom `{$colName}` ditambahkan ke tabel `{$tableName}`."
                    ];
                }
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Validasi & penyiapan database selesai.',
            'details' => $messages
        ]);
    } catch (\PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal setup database: ' . $e->getMessage()
        ]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
?>
