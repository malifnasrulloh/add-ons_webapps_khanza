<?php
/**
 * api/akuntansi_jurnal.php
 * Backend AJAX untuk Jurnal Harian.
 * 
 * Replika 100% logika dari DlgJurnalHarian.java (SIMRS Khanza).
 * Query utama: JOIN jurnal + detailjurnal + rekening,
 * filter by tanggal, no_jurnal, nm_rek, dan keyword umum.
 * 
 * SECURITY: Prepared Statements MySQLi, session check, CSRF optional (GET read-only).
 */

// Wajib: Cek session via koneksi
require_once(dirname(__DIR__) . '/config/koneksi.php');
header('Content-Type: application/json; charset=utf-8');

// Tolak akses tanpa session
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

// Ambil parameter dari GET (halaman laporan read-only)
$tgl1    = isset($_GET['tgl1'])    ? trim($_GET['tgl1'])    : date('Y-m-d');
$tgl2    = isset($_GET['tgl2'])    ? trim($_GET['tgl2'])    : date('Y-m-d');
$no_jur  = isset($_GET['no_jur'])  ? trim($_GET['no_jur'])  : '';
$nm_rek  = isset($_GET['nm_rek'])  ? trim($_GET['nm_rek'])  : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// Validasi format tanggal (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl1)) { $tgl1 = date('Y-m-d'); }
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl2)) { $tgl2 = date('Y-m-d'); }

// =========================================================================
// QUERY UTAMA — Replika tepat dari DlgJurnalHarian.java method tampil()
// =========================================================================
// Java:
// "select jurnal.no_jurnal, jurnal.no_bukti, jurnal.tgl_jurnal, jurnal.keterangan,
//  detailjurnal.kd_rek, detailjurnal.debet, detailjurnal.kredit,
//  rekening.nm_rek, jurnal.jam_jurnal
//  from jurnal inner join detailjurnal inner join rekening on
//  jurnal.no_jurnal=detailjurnal.no_jurnal and detailjurnal.kd_rek=rekening.kd_rek
//  where jurnal.no_jurnal like ? and rekening.nm_rek like ? and
//  jurnal.tgl_jurnal between ? and ?
//  [and (jurnal.no_jurnal like ? or jurnal.no_bukti like ? or
//   detailjurnal.kd_rek like ? or jurnal.keterangan like ? or rekening.nm_rek like ?)]
//  order by jurnal.tgl_jurnal asc, jurnal.jam_jurnal asc, jurnal.no_jurnal asc, detailjurnal.debet desc"
// =========================================================================

$sql_base = "SELECT jurnal.no_jurnal, jurnal.no_bukti, jurnal.tgl_jurnal, jurnal.keterangan,
                    detailjurnal.kd_rek, detailjurnal.debet, detailjurnal.kredit,
                    rekening.nm_rek, jurnal.jam_jurnal
             FROM jurnal
             INNER JOIN detailjurnal ON jurnal.no_jurnal = detailjurnal.no_jurnal
             INNER JOIN rekening ON detailjurnal.kd_rek = rekening.kd_rek
             WHERE jurnal.no_jurnal LIKE ?
               AND rekening.nm_rek LIKE ?
               AND jurnal.tgl_jurnal BETWEEN ? AND ?";

// Tambah kondisi keyword jika diisi (sama persis logika Java)
if ($keyword !== '') {
    $sql_base .= " AND (jurnal.no_jurnal LIKE ?
                      OR jurnal.no_bukti LIKE ?
                      OR detailjurnal.kd_rek LIKE ?
                      OR jurnal.keterangan LIKE ?
                      OR rekening.nm_rek LIKE ?)";
}

$sql_base .= " ORDER BY jurnal.tgl_jurnal ASC, jurnal.jam_jurnal ASC, jurnal.no_jurnal ASC, detailjurnal.debet DESC";

$stmt = $koneksi->prepare($sql_base);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Kesalahan sistem database.']);
    exit;
}

// Bind parameter dengan wildcard LIKE
$p_no_jur  = '%' . $no_jur  . '%';
$p_nm_rek  = '%' . $nm_rek  . '%';
$p_keyword = '%' . $keyword . '%';

if ($keyword !== '') {
    // 9 parameter: no_jur, nm_rek, tgl1, tgl2, kw, kw, kw, kw, kw
    $stmt->bind_param(
        'sssssssss',
        $p_no_jur, $p_nm_rek, $tgl1, $tgl2,
        $p_keyword, $p_keyword, $p_keyword, $p_keyword, $p_keyword
    );
} else {
    // 4 parameter: no_jur, nm_rek, tgl1, tgl2
    $stmt->bind_param('ssss', $p_no_jur, $p_nm_rek, $tgl1, $tgl2);
}

$stmt->execute();
$result = $stmt->get_result();

$rows    = [];
$ttldebet  = 0;
$ttlkredit = 0;
$prev_date = null;

// =========================================================================
// PROSES RESULT — Replika logika Java:
// Jika kredit > 0 → nama rekening diberi indent (4 spasi)
// Sisipkan baris separator jika tanggal berganti
// Tambah baris Total di akhir
// =========================================================================
while ($row = $result->fetch_assoc()) {
    $tgl_jam = $row['tgl_jurnal'] . ' ' . $row['jam_jurnal'];
    $keterangan = 'No.Jur ' . $row['no_jurnal'] . ', No.Buk ' . $row['no_bukti'] . ', ' . $row['keterangan'];
    
    // DataTables tidak membutuhkan dummy separator row
    // Jika kredit > 0 → rekening di-indent (replika logika "     "+nm_rek Java)
    $nm_rek_display = ($row['kredit'] > 0)
        ? '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . htmlspecialchars($row['nm_rek'])
        : htmlspecialchars($row['nm_rek']);
    
    $rows[] = [
        'tgl_jam'     => htmlspecialchars($tgl_jam),
        'tgl_jurnal'  => htmlspecialchars($row['tgl_jurnal']), /* Untuk DataTables RowGroup */
        'no_jurnal'   => htmlspecialchars($row['no_jurnal']),
        'no_bukti'    => htmlspecialchars($row['no_bukti']),
        'kd_rek'      => htmlspecialchars($row['kd_rek']),
        'nm_rek'      => $nm_rek_display,
        'keterangan'  => htmlspecialchars($keterangan),
        'debet'       => (float) $row['debet'],
        'kredit'      => (float) $row['kredit']
    ];
    
    $ttldebet  += (float) $row['debet'];
    $ttlkredit += (float) $row['kredit'];
    $prev_date  = $row['tgl_jurnal'];

}
$stmt->close();

// Tambahkan baris total di akhir (replika logika Java) dihapus karena KPI dan footer DataTables akan menanganinya di Frontend.

echo json_encode([
    'success'    => true,
    'rows'       => $rows,
    'ttldebet'   => $ttldebet,
    'ttlkredit'  => $ttlkredit,
    'row_count'  => count($rows)
]);
