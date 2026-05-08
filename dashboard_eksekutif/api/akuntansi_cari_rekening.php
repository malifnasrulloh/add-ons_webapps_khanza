<?php
/**
 * api/akuntansi_cari_rekening.php
 * AJAX endpoint untuk pencarian rekening (autocomplete / modal pilih rekening).
 * Replika DlgRekeningTahun.java: menampilkan kd_rek, nm_rek, tipe, balance, saldo_awal
 * SECURITY: MySQLi Prepared Statements, session check.
 */

require_once(dirname(__DIR__) . '/config/koneksi.php');
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$tahun   = isset($_GET['thn']) ? trim($_GET['thn']) : date('Y');

if (!preg_match('/^\d{4}$/', $tahun)) { $tahun = date('Y'); }

$sql = "SELECT rekening.kd_rek, rekening.nm_rek, rekening.tipe, rekening.balance,
               IFNULL(SUM(rekeningtahun.saldo_awal), 0) AS saldo_awal
        FROM rekening
        LEFT JOIN rekeningtahun ON rekening.kd_rek = rekeningtahun.kd_rek AND rekeningtahun.thn = ?
        WHERE (rekening.kd_rek LIKE ? OR rekening.nm_rek LIKE ?)
        GROUP BY rekening.kd_rek
        ORDER BY rekening.kd_rek
        LIMIT 50";

$stmt = $koneksi->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'results' => []]);
    exit;
}

$p_kw = '%' . $keyword . '%';
$stmt->bind_param('sss', $tahun, $p_kw, $p_kw);
$stmt->execute();
$result = $stmt->get_result();

$results = [];
while ($row = $result->fetch_assoc()) {
    $results[] = [
        'kd_rek'    => htmlspecialchars($row['kd_rek']),
        'nm_rek'    => htmlspecialchars($row['nm_rek']),
        'tipe'      => htmlspecialchars($row['tipe']),
        'balance'   => htmlspecialchars($row['balance']),
        'saldo_awal'=> (float)$row['saldo_awal']
    ];
}
$stmt->close();

echo json_encode(['success' => true, 'results' => $results]);
