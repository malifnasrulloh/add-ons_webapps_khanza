<?php
/**
 * modules/admin_ref/ajax.php — Backend CRUD Admin Referensi (Super Admin Only)
 * Tabel: satu_sehat_ref_kfa, satu_sehat_ref_loinc, satu_sehat_ref_snomed
 * LAZY LOADING: data hanya dikirim setelah user mengetik keyword (min 2 karakter)
 */
error_reporting(0); ini_set('display_errors', 0);
require_once '../../conf.php';
require_once '../../auth_check.php';

// Super admin ONLY
if (empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Hanya Super Admin.']);
    exit;
}

header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

$table_map = [
    'kfa'    => ['table' => 'satu_sehat_ref_kfa',    'pk' => 'kfa_code',    'cols' => ['kfa_code', 'display_name']],
    'loinc'  => ['table' => 'satu_sehat_ref_loinc',  'pk' => 'loinc_num',  'cols' => ['loinc_num', 'component', 'long_common_name', 'system_type', 'method_typ', 'property', 'class', 'shortname']],
    'snomed' => ['table' => 'satu_sehat_ref_snomed',  'pk' => 'conceptId', 'cols' => ['conceptId', 'term']],
];

try {
    // ============================================================
    // GET COLUMNS — untuk tau struktur tabel
    // ============================================================
    if ($action === 'schema') {
        $tbl_key = $_GET['tbl'] ?? '';
        if (!isset($table_map[$tbl_key])) {
            echo json_encode(['cols' => []]);
            exit;
        }
        echo json_encode(['cols' => $table_map[$tbl_key]['cols'], 'pk' => $table_map[$tbl_key]['pk']]);
        exit;
    }

    // ============================================================
    // LOAD — Lazy loading: hanya jika ada keyword (min 2 karakter)
    // ============================================================
    if ($action === 'load') {
        $tbl_key = $_GET['tbl'] ?? '';
        if (!isset($table_map[$tbl_key])) {
            echo json_encode(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
            exit;
        }
        $cfg    = $table_map[$tbl_key];
        $tbl    = $cfg['table'];
        $pk     = $cfg['pk'];
        $cols   = $cfg['cols'];

        $search = trim($_GET['search']['value'] ?? '');
        $start  = (int)($_GET['start'] ?? 0);
        $length = (int)($_GET['length'] ?? 25);
        if ($length < 1 || $length > 100) $length = 25;

        // LAZY LOADING: jangan load jika search kosong atau terlalu pendek
        if (strlen($search) < 2) {
            echo json_encode([
                'draw' => (int)($_GET['draw'] ?? 1),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'lazy_notice'     => true
            ]);
            exit;
        }

        $colList = implode(', ', array_map(function($c) { return "`$c`"; }, $cols));
        $params  = [];

        // Build WHERE across all cols
        $where_parts = [];
        $i = 0;
        foreach ($cols as $col) {
            $where_parts[] = "`$col` LIKE :s$i";
            $params[":s$i"] = "%$search%";
            $i++;
        }
        $whereSQL = " WHERE " . implode(' OR ', $where_parts);

        $total    = (int)$pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn();
        $stmtC    = $pdo->prepare("SELECT COUNT(*) FROM `$tbl`" . $whereSQL);
        $stmtC->execute($params);
        $filtered = (int)$stmtC->fetchColumn();

        $params[':lmt'] = $length;
        $params[':ofs'] = $start;
        $stmt = $pdo->prepare("SELECT $colList FROM `$tbl`" . $whereSQL . " ORDER BY `$pk` ASC LIMIT :lmt OFFSET :ofs");
        foreach ($params as $k => $v) {
            if ($k === ':lmt' || $k === ':ofs') $stmt->bindValue($k, $v, PDO::PARAM_INT);
            else $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = array_values($row);
        }

        echo json_encode([
            'draw'            => (int)($_GET['draw'] ?? 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data
        ]);
        exit;
    }

    // ============================================================
    // SAVE
    // ============================================================
    if ($action === 'save') {
        validate_csrf();
        $tbl_key = $_POST['tbl'] ?? '';
        if (!isset($table_map[$tbl_key])) {
            echo json_encode(['status' => 'error', 'message' => 'Tabel tidak valid.']);
            exit;
        }
        $cfg     = $table_map[$tbl_key];
        $tbl     = $cfg['table'];
        $pk      = $cfg['pk'];
        $cols    = $cfg['cols'];
        $old_pk  = trim($_POST['old_pk'] ?? '');

        // Ambil nilai semua kolom dari POST
        $vals = [];
        foreach ($cols as $col) {
            $vals[$col] = trim($_POST[$col] ?? '');
        }
        if (empty($vals[$pk])) {
            echo json_encode(['status' => 'error', 'message' => 'Kode/PK tidak boleh kosong.']);
            exit;
        }

        $col_list   = implode(', ', array_map(function($c) { return "`$c`"; }, $cols));
        $placeholders = implode(', ', array_fill(0, count($cols), '?'));
        $update_set = implode(', ', array_map(function($c) { return "`$c` = VALUES(`$c`)"; }, $cols));

        if (!empty($old_pk) && $old_pk !== $vals[$pk]) {
            // PK berubah: hapus lama, insert baru
            $pdo->prepare("DELETE FROM `$tbl` WHERE `$pk` = ?")->execute([$old_pk]);
            $pdo->prepare("INSERT INTO `$tbl` ($col_list) VALUES ($placeholders)")->execute(array_values($vals));
        } else {
            $pdo->prepare("INSERT INTO `$tbl` ($col_list) VALUES ($placeholders)
                           ON DUPLICATE KEY UPDATE $update_set")->execute(array_values($vals));
        }

        echo json_encode(['status' => 'success', 'message' => 'Data berhasil disimpan.']);
        exit;
    }

    // ============================================================
    // DELETE
    // ============================================================
    if ($action === 'delete') {
        validate_csrf();
        $tbl_key = $_POST['tbl'] ?? '';
        if (!isset($table_map[$tbl_key])) {
            echo json_encode(['status' => 'error', 'message' => 'Tabel tidak valid.']);
            exit;
        }
        $cfg  = $table_map[$tbl_key];
        $tbl  = $cfg['table'];
        $pk   = $cfg['pk'];
        $code = trim($_POST['pk_val'] ?? '');
        if (empty($code)) {
            echo json_encode(['status' => 'error', 'message' => 'Nilai PK kosong.']);
            exit;
        }
        $pdo->prepare("DELETE FROM `$tbl` WHERE `$pk` = ?")->execute([$code]);
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus.']);
        exit;
    }

    // ============================================================
    // UPLOAD LOINC CSV
    // ============================================================
    if ($action === 'upload_loinc_csv') {
        validate_csrf();
        
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES['csv_file']['error'] ?? 'no file';
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah file. Error code: ' . $errCode]);
            exit;
        }

        $tmpName  = $_FILES['csv_file']['tmp_name'];
        $fileName = $_FILES['csv_file']['name'];
        $fileSize = $_FILES['csv_file']['size'];

        // Cek extension
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($ext !== 'csv' && $ext !== 'txt') {
            echo json_encode(['status' => 'error', 'message' => 'Format file tidak didukung. Harap unggah file CSV atau TXT.']);
            exit;
        }

        // Simpan file sementara
        $destPath = tempnam(sys_get_temp_dir(), 'loinc_import_');
        if (!$destPath || !move_uploaded_file($tmpName, $destPath)) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan file sementara di server.']);
            exit;
        }

        // Buka file untuk membaca header
        $handle = fopen($destPath, 'r');
        if (!$handle) {
            @unlink($destPath);
            echo json_encode(['status' => 'error', 'message' => 'Gagal membaca file yang diunggah.']);
            exit;
        }

        // Deteksi delimiter (koma atau titik koma)
        $firstLine = fgets($handle);
        fclose($handle);
        
        $delimiter = ',';
        if (strpos($firstLine, ';') !== false && strpos($firstLine, ',') === false) {
            $delimiter = ';';
        }

        $handle = fopen($destPath, 'r');
        $headers = fgetcsv($handle, 0, $delimiter);
        fclose($handle);

        if (!$headers) {
            @unlink($destPath);
            echo json_encode(['status' => 'error', 'message' => 'File CSV kosong atau baris header tidak valid.']);
            exit;
        }

        // Clean & normalize headers
        $headers_norm = array_map(function($h) {
            return strtolower(trim($h, " \t\n\r\0\x0B\"'"));
        }, $headers);

        $mapping_rules = [
            'loinc_num'        => ['loinc_num', 'loincnum', 'loinc_number', 'loincnumber', 'loinc', 'code', 'loincnumber'],
            'component'        => ['component', 'comp'],
            'long_common_name' => ['long_common_name', 'longcommonname', 'long_name', 'longname', 'display', 'long common name'],
            'system_type'      => ['system_type', 'systemtype', 'system', 'system type'],
            'method_typ'       => ['method_typ', 'methodtyp', 'method_type', 'methodtype', 'method', 'method type'],
            'property'         => ['property', 'prop'],
            'class'            => ['class'],
            'shortname'        => ['shortname', 'short_name', 'shortname', 'short name']
        ];

        $col_map = [];
        $mapped_cols = [];
        foreach ($mapping_rules as $db_col => $candidates) {
            $col_map[$db_col] = null;
            foreach ($candidates as $cand) {
                $idx = array_search($cand, $headers_norm);
                if ($idx !== false) {
                    $col_map[$db_col] = $idx;
                    $mapped_cols[$db_col] = $headers[$idx];
                    break;
                }
            }
        }

        if ($col_map['loinc_num'] === null) {
            @unlink($destPath);
            echo json_encode([
                'status' => 'error', 
                'message' => 'Kolom wajib LOINC_NUM tidak ditemukan. Header yang terdeteksi: ' . implode(', ', $headers)
            ]);
            exit;
        }

        // Simpan info ke session
        $_SESSION['loinc_import_file'] = $destPath;
        $_SESSION['loinc_col_map']     = $col_map;
        $_SESSION['loinc_delimiter']   = $delimiter;

        echo json_encode([
            'status'      => 'success',
            'file_size'   => $fileSize,
            'col_map'     => $mapped_cols,
            'all_headers' => $headers
        ]);
        exit;
    }

    // ============================================================
    // PROCESS LOINC CSV CHUNK
    // ============================================================
    if ($action === 'import_loinc_chunk') {
        validate_csrf();

        $destPath  = $_SESSION['loinc_import_file'] ?? '';
        $col_map   = $_SESSION['loinc_col_map'] ?? null;
        $delimiter = $_SESSION['loinc_delimiter'] ?? ',';

        if (empty($destPath) || !file_exists($destPath) || empty($col_map)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada sesi import yang aktif. Silakan unggah file kembali.']);
            exit;
        }

        $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
        $batch_size = 2000;
        
        $handle = fopen($destPath, 'r');
        if (!$handle) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal membuka file import.']);
            exit;
        }

        // Seek ke offset
        if ($offset > 0) {
            fseek($handle, $offset, SEEK_SET);
        } else {
            // Di awal, lewati baris header
            fgetcsv($handle, 0, $delimiter);
        }

        $rows = [];
        $count = 0;
        while ($count < $batch_size && ($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            // Validasi baris kosong
            if (empty($row) || count($row) === 1 && $row[0] === null) {
                continue;
            }

            // Ambil loinc_num
            $loinc_num_idx = $col_map['loinc_num'];
            $loinc_num = isset($row[$loinc_num_idx]) ? trim($row[$loinc_num_idx], " \t\n\r\0\x0B\"'") : '';
            if (empty($loinc_num)) {
                continue;
            }

            // Map data columns
            $component = null;
            if ($col_map['component'] !== null && isset($row[$col_map['component']])) {
                $component = trim($row[$col_map['component']], " \t\n\r\0\x0B\"'");
            }

            $long_common_name = null;
            if ($col_map['long_common_name'] !== null && isset($row[$col_map['long_common_name']])) {
                $long_common_name = trim($row[$col_map['long_common_name']], " \t\n\r\0\x0B\"'");
            }

            $system_type = null;
            if ($col_map['system_type'] !== null && isset($row[$col_map['system_type']])) {
                $system_type = trim($row[$col_map['system_type']], " \t\n\r\0\x0B\"'");
            }

            $method_typ = null;
            if ($col_map['method_typ'] !== null && isset($row[$col_map['method_typ']])) {
                $method_typ = trim($row[$col_map['method_typ']], " \t\n\r\0\x0B\"'");
            }

            $property = null;
            if ($col_map['property'] !== null && isset($row[$col_map['property']])) {
                $property = trim($row[$col_map['property']], " \t\n\r\0\x0B\"'");
            }

            $class = null;
            if ($col_map['class'] !== null && isset($row[$col_map['class']])) {
                $class = trim($row[$col_map['class']], " \t\n\r\0\x0B\"'");
            }

            $shortname = null;
            if ($col_map['shortname'] !== null && isset($row[$col_map['shortname']])) {
                $shortname = trim($row[$col_map['shortname']], " \t\n\r\0\x0B\"'");
            }

            $rows[] = [
                $loinc_num,
                $component,
                $long_common_name,
                $system_type,
                $method_typ,
                $property,
                $class,
                $shortname
            ];
            $count++;
        }

        $next_offset = ftell($handle);
        $is_eof = feof($handle);
        fclose($handle);

        // Lakukan batch insert/update dalam transaksi
        if (!empty($rows)) {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO `satu_sehat_ref_loinc` 
                    (`loinc_num`, `component`, `long_common_name`, `system_type`, `method_typ`, `property`, `class`, `shortname`) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    `component` = VALUES(`component`), 
                    `long_common_name` = VALUES(`long_common_name`), 
                    `system_type` = VALUES(`system_type`), 
                    `method_typ` = VALUES(`method_typ`), 
                    `property` = VALUES(`property`), 
                    `class` = VALUES(`class`), 
                    `shortname` = VALUES(`shortname`)");
                
                foreach ($rows as $row_data) {
                    $stmt->execute($row_data);
                }
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ke database: ' . $e->getMessage()]);
                exit;
            }
        }

        // Jika selesai, hapus file sementara
        if ($is_eof) {
            @unlink($destPath);
            unset($_SESSION['loinc_import_file']);
            unset($_SESSION['loinc_col_map']);
            unset($_SESSION['loinc_delimiter']);
        }

        echo json_encode([
            'status'      => 'success',
            'next_offset' => $next_offset,
            'imported'    => count($rows),
            'done'        => $is_eof
        ]);
        exit;
    }

    // ============================================================
    // CANCEL LOINC IMPORT
    // ============================================================
    if ($action === 'cancel_loinc_import') {
        validate_csrf();
        $destPath = $_SESSION['loinc_import_file'] ?? '';
        if (!empty($destPath) && file_exists($destPath)) {
            @unlink($destPath);
        }
        unset($_SESSION['loinc_import_file']);
        unset($_SESSION['loinc_col_map']);
        unset($_SESSION['loinc_delimiter']);
        echo json_encode(['status' => 'success', 'message' => 'Import dibatalkan.']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenal.']);

} catch (Exception $e) {
    // DataTables expects the "error" key when an error occurs,
    // otherwise it will throw the generic "Ajax error" (TN/7).
    echo json_encode([
        'draw' => (int)($_GET['draw'] ?? 1),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'status' => 'error',
        'error' => $e->getMessage(),
        'message' => $e->getMessage()
    ]);
}
?>
