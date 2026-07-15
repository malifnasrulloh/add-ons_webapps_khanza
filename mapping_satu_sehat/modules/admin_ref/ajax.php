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
    'loinc'  => ['table' => 'satu_sehat_ref_loinc',  'pk' => 'loinc_num',  'cols' => ['loinc_num', 'component', 'long_common_name']],
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
