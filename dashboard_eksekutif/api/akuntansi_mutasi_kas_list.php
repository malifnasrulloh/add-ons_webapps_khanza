<?php
/**
 * api/akuntansi_mutasi_kas_list.php
 * Drill-down Level 2: Daftar jurnal untuk pasangan transfer tertentu.
 *
 * Menerima: from_kd, to_kd, tgl1, tgl2
 * Mengembalikan: List jurnal yang merupakan transfer dari from_kd ke to_kd,
 *                beserta keterangan dan nominal.
 *
 * SECURITY: PDO Prepared Statements, session check.
 */

require_once(dirname(__DIR__) . '/config/koneksi.php');
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

// ─── Validasi Input ───────────────────────────────────────────────────────────
$from_kd = isset($_GET['from_kd']) ? trim($_GET['from_kd']) : '';
$to_kd   = isset($_GET['to_kd'])   ? trim($_GET['to_kd'])   : '';
$tgl1    = isset($_GET['tgl1'])    ? trim($_GET['tgl1'])    : date('Y-01-01');
$tgl2    = isset($_GET['tgl2'])    ? trim($_GET['tgl2'])    : date('Y-m-d');

// Validasi format kd_rek (alphanumeric, maks 15 char)
if (empty($from_kd) || !preg_match('/^[a-zA-Z0-9]{1,15}$/', $from_kd)) {
    echo json_encode(['success' => false, 'message' => 'Kode rekening asal tidak valid.']);
    exit;
}
if (empty($to_kd) || !preg_match('/^[a-zA-Z0-9]{1,15}$/', $to_kd)) {
    echo json_encode(['success' => false, 'message' => 'Kode rekening tujuan tidak valid.']);
    exit;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl1)) { $tgl1 = date('Y-01-01'); }
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl2)) { $tgl2 = date('Y-m-d'); }

$pdo = $koneksi_pdo;

// ─── Ambil nama rekening asal & tujuan ───────────────────────────────────────
$stmt_nm = $pdo->prepare("SELECT kd_rek, nm_rek FROM rekening WHERE kd_rek IN (?, ?)");
$stmt_nm->execute([$from_kd, $to_kd]);
$nm_map = [];
foreach ($stmt_nm->fetchAll() as $r) {
    $nm_map[$r['kd_rek']] = $r['nm_rek'];
}

$from_nm = $nm_map[$from_kd] ?? $from_kd;
$to_nm   = $nm_map[$to_kd]   ?? $to_kd;

// ─── Query: Cari jurnal yang punya baris kredit from_kd DAN baris debet to_kd ─
$sql = "
    SELECT
        j.no_jurnal,
        j.tgl_jurnal,
        j.jam_jurnal,
        j.no_bukti,
        j.keterangan,
        d_kredit.kredit   AS jumlah_keluar,
        d_debet.debet     AS jumlah_masuk
    FROM jurnal j
    -- Pastikan ada baris kredit dari rekening ASAL (from_kd)
    INNER JOIN detailjurnal d_kredit
        ON j.no_jurnal = d_kredit.no_jurnal
       AND d_kredit.kd_rek = ?
       AND d_kredit.kredit > 0
    -- Pastikan ada baris debet ke rekening TUJUAN (to_kd) dalam jurnal yang sama
    INNER JOIN detailjurnal d_debet
        ON j.no_jurnal = d_debet.no_jurnal
       AND d_debet.kd_rek = ?
       AND d_debet.debet > 0
    WHERE j.tgl_jurnal BETWEEN ? AND ?
    ORDER BY j.tgl_jurnal ASC, j.jam_jurnal ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$from_kd, $to_kd, $tgl1, $tgl2]);
$rows = $stmt->fetchAll();

$data = [];
$total_mutasi = 0;
foreach ($rows as $row) {
    $jumlah = (float)$row['jumlah_masuk']; // debet ke to_kd = jumlah yang masuk
    $total_mutasi += $jumlah;
    $data[] = [
        'no_jurnal'    => $row['no_jurnal'],
        'tgl_jurnal'   => $row['tgl_jurnal'],
        'jam_jurnal'   => $row['jam_jurnal'],
        'no_bukti'     => $row['no_bukti']   ?? '-',
        'keterangan'   => $row['keterangan'] ?? '-',
        'jumlah_keluar'=> (float)$row['jumlah_keluar'],
        'jumlah_masuk' => $jumlah,
    ];
}

echo json_encode([
    'success'      => true,
    'from_kd'      => $from_kd,
    'from_nm'      => $from_nm,
    'to_kd'        => $to_kd,
    'to_nm'        => $to_nm,
    'tgl1'         => $tgl1,
    'tgl2'         => $tgl2,
    'rows'         => $data,
    'total_mutasi' => $total_mutasi,
    'row_count'    => count($data),
]);
