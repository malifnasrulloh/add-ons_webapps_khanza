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

if ($action === 'get_app_settings') {
    // Public read: returns current app settings from config.php JSON block
    $configPath = __DIR__ . '/config.php';
    $configContent = file_get_contents($configPath);
    // Extract JSON block from $siranap_settings = '{ ... }'
    if (preg_match("/\\\$siranap_settings\\s*=\\s*'({.*?})';/s", $configContent, $m)) {
        $parsed = json_decode($m[1], true);
        if ($parsed !== null) {
            // Never expose kemkes_pass in plaintext to the client — redact it
            $safe = $parsed;
            $safe['kemkes_pass'] = str_repeat('*', strlen($parsed['kemkes_pass'] ?? ''));
            echo json_encode(['status' => 'success', 'data' => $safe]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal parse settings JSON']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Blok settings tidak ditemukan di config.php']);
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

if ($action === 'get_ref_kemenkes') {
    if (!$kemkes_id || !$kemkes_pass) {
        echo json_encode(['status' => 'error', 'message' => 'Kredensial Kemkes belum diatur di config.php']);
        exit;
    }
    
    $url = "https://sirs.kemkes.go.id/fo/index.php/Referensi/tempat_tidur";
    $dt = new DateTime(null, new DateTimeZone("UTC"));
    $timestamp = $dt->getTimestamp();
    
    $headers = [
        "X-rs-id: " . $kemkes_id,
        "X-Timestamp: " . $timestamp,
        "X-pass: " . $kemkes_pass,
        "Content-type: application/json"
    ];
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);
    
    if ($curl_error) {
        echo json_encode(['status' => 'error', 'message' => 'CURL Error: ' . $curl_error]);
        exit;
    }
    
    $dataDecoded = json_decode($response, true);
    if ($dataDecoded === null) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal parse JSON dari Kemenkes. Raw: ' . substr($response, 0, 200)]);
        exit;
    }
    
    if (isset($dataDecoded['tempat_tidur'][0]['message'])) {
        $inner = json_decode($dataDecoded['tempat_tidur'][0]['message'], true);
        if (isset($inner['status']) && $inner['status'] === 'false') {
            echo json_encode(['status' => 'error', 'message' => 'Kemenkes API: ' . ($inner['response'] ?? 'Gagal Autentikasi')]);
            exit;
        }
    }
    
    echo json_encode(['status' => 'success', 'data' => $dataDecoded['tempat_tidur'] ?? []]);
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

if ($action === 'save_app_settings') {
    $kemkes_id_new  = trim($_POST['kemkes_id']  ?? '');
    $kemkes_pass_new = trim($_POST['kemkes_pass'] ?? '');
    $interval_new   = (int)($_POST['force_sync_interval_seconds'] ?? 3600);

    if (!$kemkes_id_new) {
        echo json_encode(['status' => 'error', 'message' => 'Kemkes ID tidak boleh kosong.']);
        exit;
    }
    if ($interval_new < 60) {
        echo json_encode(['status' => 'error', 'message' => 'Interval minimal 60 detik.']);
        exit;
    }

    $configPath    = __DIR__ . '/config.php';
    $configContent = file_get_contents($configPath);

    // Read existing JSON block from config.php
    if (!preg_match("/\\\$siranap_settings\\s*=\\s*'({.*?})';/s", $configContent, $m)) {
        echo json_encode(['status' => 'error', 'message' => 'Blok settings tidak ditemukan di config.php']);
        exit;
    }
    $existing = json_decode($m[1], true) ?? [];

    // Only update kemkes_pass if a real value was sent (not masked ***)
    $existing['kemkes_id']  = $kemkes_id_new;
    if ($kemkes_pass_new !== '' && !preg_match('/^\*+$/', $kemkes_pass_new)) {
        $existing['kemkes_pass'] = $kemkes_pass_new;
    }
    $existing['force_sync_interval_seconds'] = $interval_new;

    $newJson = json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    // Replace single quotes wrapping with safe escaped content
    $newBlock = "\$siranap_settings = '" . str_replace("'", "\\'", $newJson) . "';";

    $newConfigContent = preg_replace(
        "/\\\$siranap_settings\\s*=\\s*'.*?';/s",
        $newBlock,
        $configContent
    );

    if ($newConfigContent === null || $newConfigContent === $configContent) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menulis ulang config.php (regex mismatch).']);
        exit;
    }

    if (file_put_contents($configPath, $newConfigContent) === false) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak dapat menyimpan config.php. Periksa izin file.']);
        exit;
    }

    echo json_encode(['status' => 'success', 'message' => 'Pengaturan berhasil disimpan.']);
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
                `nm_ruang_sirsonline` enum('VVIP','VIP','Kelas Utama','Kelas I','Kelas II','Kelas III','HCU','NICU','Isolasi','Perinatologi','ICU','PICU','RICU','ICCU','KRIS JKN') NOT NULL DEFAULT 'Kelas I',
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
                'nm_ruang_sirsonline' => "enum('VVIP','VIP','Kelas Utama','Kelas I','Kelas II','Kelas III','HCU','NICU','Isolasi','Perinatologi','ICU','PICU','RICU','ICCU','KRIS JKN') NOT NULL DEFAULT 'Kelas I'",
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
                } else if ($colName === 'nm_ruang_sirsonline') {
                    // Always alter nm_ruang_sirsonline to update enum values
                    $pdo->exec("ALTER TABLE `{$tableName}` MODIFY COLUMN `{$colName}` {$colDef}");
                    $messages[] = [
                        'type' => 'alter_modify',
                        'table' => $tableName,
                        'column' => $colName,
                        'status' => 'success',
                        'text' => "Enum kolom `{$colName}` diperbarui."
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
