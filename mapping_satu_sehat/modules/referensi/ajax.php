<?php
/**
 * modules/referensi/ajax.php — Backend CRUD Referensi Data
 * Tabel yang dikelola: satu_sehat_ref_form, satu_sehat_ref_route,
 *                      satu_sehat_ref_numerator, satu_sehat_ref_denominator
 * Akses: Super Admin ATAU user dengan hak akses obat/vaksin
 */
error_reporting(0); ini_set('display_errors', 0);
require_once '../../conf.php';
require_once '../../auth_check.php';

// Akses: admin, atau user yg punya salah satu hak akses obat/vaksin
if (empty($_SESSION['is_admin'])) {
    require_login();
    $ha = $_SESSION['hak_akses'] ?? [];
    if (($ha['satu_sehat_mapping_obat'] ?? '') !== 'true' &&
        ($ha['satu_sehat_mapping_vaksin'] ?? '') !== 'true') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
        exit;
    }
}

header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

// Whitelist tabel yang boleh dikelola
$allowed_tables = [
    'form'        => 'satu_sehat_ref_form',
    'route'       => 'satu_sehat_ref_route',
    'numerator'   => 'satu_sehat_ref_numerator',
    'denominator' => 'satu_sehat_ref_denominator',
];

// Kolom PK per tabel (semua pakai 'code')
function get_pk($tbl) { return 'code'; }

try {
    // ============================================================
    // LOAD — DataTables server-side
    // ============================================================
    if ($action === 'load') {
        $tbl_key = $_GET['tbl'] ?? '';
        if (!isset($allowed_tables[$tbl_key])) {
            echo json_encode(['data' => [], 'recordsTotal' => 0, 'recordsFiltered' => 0, 'draw' => 1]);
            exit;
        }
        $tbl = $allowed_tables[$tbl_key];

        $search = trim($_GET['search']['value'] ?? '');
        $start  = (int)($_GET['start'] ?? 0);
        $length = (int)($_GET['length'] ?? 25);
        if ($length < 1 || $length > 200) $length = 25;

        $params = [];
        $where  = '';
        if (!empty($search)) {
            $where = " WHERE code LIKE :s OR display LIKE :s";
            $params[':s'] = "%$search%";
        }

        $total = (int)$pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn();
        $stmtC = $pdo->prepare("SELECT COUNT(*) FROM `$tbl`" . $where);
        $stmtC->execute($params);
        $filtered = (int)$stmtC->fetchColumn();

        $params[':lmt'] = $length;
        $params[':ofs'] = $start;
        $stmt = $pdo->prepare("SELECT code, display FROM `$tbl`" . $where . " ORDER BY code ASC LIMIT :lmt OFFSET :ofs");
        foreach ($params as $k => $v) {
            if ($k === ':lmt' || $k === ':ofs') $stmt->bindValue($k, $v, PDO::PARAM_INT);
            else $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'draw'            => (int)($_GET['draw'] ?? 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows
        ]);
        exit;
    }

    // ============================================================
    // SAVE (insert / update) — butuh CSRF
    // ============================================================
    if ($action === 'save') {
        validate_csrf();
        $tbl_key = $_POST['tbl'] ?? '';
        if (!isset($allowed_tables[$tbl_key])) {
            echo json_encode(['status' => 'error', 'message' => 'Tabel tidak valid.']);
            exit;
        }
        $tbl     = $allowed_tables[$tbl_key];
        $code    = trim($_POST['code'] ?? '');
        $display = trim($_POST['display'] ?? '');
        $old_code= trim($_POST['old_code'] ?? '');  // kosong = insert baru

        if (empty($code)) {
            echo json_encode(['status' => 'error', 'message' => 'Kode tidak boleh kosong.']);
            exit;
        }

        if (!empty($old_code) && $old_code !== $code) {
            // Kode PK berubah: hapus lama, insert baru
            $pdo->prepare("DELETE FROM `$tbl` WHERE code = ?")->execute([$old_code]);
            $pdo->prepare("INSERT INTO `$tbl` (code, display) VALUES (?, ?)")->execute([$code, $display]);
        } else {
            // Upsert
            $pdo->prepare("INSERT INTO `$tbl` (code, display) VALUES (?, ?)
                           ON DUPLICATE KEY UPDATE display = VALUES(display)")->execute([$code, $display]);
        }
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil disimpan.']);
        exit;
    }

    // ============================================================
    // DELETE — butuh CSRF
    // ============================================================
    if ($action === 'delete') {
        validate_csrf();
        $tbl_key = $_POST['tbl'] ?? '';
        if (!isset($allowed_tables[$tbl_key])) {
            echo json_encode(['status' => 'error', 'message' => 'Tabel tidak valid.']);
            exit;
        }
        $tbl  = $allowed_tables[$tbl_key];
        $code = trim($_POST['code'] ?? '');
        if (empty($code)) {
            echo json_encode(['status' => 'error', 'message' => 'Kode tidak ditemukan.']);
            exit;
        }
        $pdo->prepare("DELETE FROM `$tbl` WHERE code = ?")->execute([$code]);
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus.']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenal.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
