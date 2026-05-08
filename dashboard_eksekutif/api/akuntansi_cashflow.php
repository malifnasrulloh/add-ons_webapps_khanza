<?php
/**
 * api/akuntansi_cashflow.php
 * Backend AJAX untuk Laporan Cash Flow / Arus Kas.
 *
 * Replika 100% logika DlgCashflow.java (SIMRS Khanza).
 * 
 * Struktur laporan:
 *  A. Kas Awal   → rekening tipe='N', balance='D'  → SUM(saldo_awal) dari rekeningtahun
 *  B. Kas Masuk  → rekening tipe='R', balance='K'  → SUM(kredit-debet) dari jurnal + saldo_awal rekening
 *  C. Kas Keluar → rekening tipe='R', balance='D'  → SUM(debet-kredit) dari jurnal + saldo_awal rekening
 *  TOTAL = A + (B - C)
 * 
 * SECURITY: MySQLi Prepared Statements, session check.
 */

require_once(dirname(__DIR__) . '/config/koneksi.php');
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$tgl1 = isset($_GET['tgl1']) ? trim($_GET['tgl1']) : date('Y-01-01');
$tgl2 = isset($_GET['tgl2']) ? trim($_GET['tgl2']) : date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl1)) { $tgl1 = date('Y-01-01'); }
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl2)) { $tgl2 = date('Y-m-d'); }

// Ambil tahun dari tanggal (untuk filter saldo_awal rekeningtahun)
$thn1 = substr($tgl1, 0, 4);
$thn2 = substr($tgl2, 0, 4);

// Helper: ambil saldo_awal rekening tertentu dari rekeningtahun
function getSaldoAwalRekening($koneksi, $kd_rek, $thn1, $thn2) {
    $sql = "SELECT SUM(rekeningtahun.saldo_awal)
            FROM rekeningtahun
            WHERE rekeningtahun.kd_rek = ?
              AND rekeningtahun.thn BETWEEN ? AND ?";
    $stmt = $koneksi->prepare($sql);
    if (!$stmt) { return 0; }
    $stmt->bind_param('sss', $kd_rek, $thn1, $thn2);
    $stmt->execute();
    $val = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
    return (float) $val;
}

$laporan = [];
$kasawal    = 0;
$penerimaan = 0;
$pengeluaran = 0;

// =========================================================================
// A. KAS AWAL — rekening tipe='N', balance='D'
// Replika Java: SUM(rekeningtahun.saldo_awal) WHERE tipe='N' AND balance='D'
//              AND thn between thn1 AND thn2
// =========================================================================
$laporan[] = ['label' => 'A. Kas Awal', 'rekening' => '', 'jumlah' => null, 'type' => 'header'];
$laporan[] = ['label' => '', 'rekening' => 'Rekening', 'jumlah' => 'Saldo Awal', 'type' => 'subheader'];

$sql_a = "SELECT rekening.kd_rek, rekening.nm_rek, SUM(rekeningtahun.saldo_awal) AS total
           FROM rekening
           INNER JOIN rekeningtahun ON rekening.kd_rek = rekeningtahun.kd_rek
           WHERE rekening.tipe = 'N' AND rekening.balance = 'D'
             AND rekeningtahun.thn BETWEEN ? AND ?
           GROUP BY rekening.kd_rek
           ORDER BY rekening.kd_rek";
$stmt_a = $koneksi->prepare($sql_a);
if ($stmt_a) {
    $stmt_a->bind_param('ss', $thn1, $thn2);
    $stmt_a->execute();
    $res_a = $stmt_a->get_result();
    $i = 1;
    while ($row_a = $res_a->fetch_assoc()) {
        $debkred = (float) $row_a['total'];
        $kasawal += $debkred;
        if ($debkred != 0) {
            $laporan[] = [
                'label'    => '',
                'rekening' => $i . '. ' . htmlspecialchars($row_a['kd_rek'] . ' ' . $row_a['nm_rek']),
                'jumlah'   => $debkred,
                'type'     => 'data'
            ];
            $i++;
        }
    }
    $stmt_a->close();
}
$laporan[] = ['label' => '', 'rekening' => 'Jumlah Total Kas Awal :', 'jumlah' => $kasawal, 'type' => 'subtotal'];

// =========================================================================
// B. KAS MASUK — rekening tipe='R', balance='K'
// Replika Java: SUM(kredit-debet) dari jurnal WHERE tipe='R' AND balance='K'
//              PLUS saldo_awal rekeningtahun
// =========================================================================
$laporan[] = ['label' => '', 'rekening' => '', 'jumlah' => null, 'type' => 'spacer'];
$laporan[] = ['label' => 'B. Kas Masuk', 'rekening' => '', 'jumlah' => null, 'type' => 'header'];
$laporan[] = ['label' => '', 'rekening' => 'Rekening', 'jumlah' => 'Kas Masuk', 'type' => 'subheader'];

$sql_b = "SELECT detailjurnal.kd_rek, rekening.nm_rek,
                 (SUM(detailjurnal.kredit) - SUM(detailjurnal.debet)) AS ttlkredit
          FROM jurnal
          INNER JOIN detailjurnal ON jurnal.no_jurnal = detailjurnal.no_jurnal
          INNER JOIN rekening ON detailjurnal.kd_rek = rekening.kd_rek
          WHERE rekening.tipe = 'R' AND rekening.balance = 'K'
            AND jurnal.tgl_jurnal BETWEEN ? AND ?
          GROUP BY detailjurnal.kd_rek
          ORDER BY detailjurnal.kd_rek";
$stmt_b = $koneksi->prepare($sql_b);
if ($stmt_b) {
    $stmt_b->bind_param('ss', $tgl1, $tgl2);
    $stmt_b->execute();
    $res_b = $stmt_b->get_result();
    $i = 1;
    while ($row_b = $res_b->fetch_assoc()) {
        $kd = $row_b['kd_rek'];
        $saldo_awal_rek = getSaldoAwalRekening($koneksi, $kd, $thn1, $thn2);
        $debkred = (float)$row_b['ttlkredit'] + $saldo_awal_rek;
        $penerimaan += $debkred;
        $laporan[] = [
            'label'    => '',
            'rekening' => $i . '. ' . htmlspecialchars($kd . ' ' . $row_b['nm_rek']),
            'jumlah'   => $debkred,
            'type'     => 'data'
        ];
        $i++;
    }
    $stmt_b->close();
}
$laporan[] = ['label' => '', 'rekening' => 'Jumlah Total Kas Masuk :', 'jumlah' => $penerimaan, 'type' => 'subtotal'];

// =========================================================================
// C. KAS KELUAR — rekening tipe='R', balance='D'
// Replika Java: SUM(debet-kredit) dari jurnal WHERE tipe='R' AND balance='D'
//              PLUS saldo_awal rekeningtahun
// =========================================================================
$laporan[] = ['label' => '', 'rekening' => '', 'jumlah' => null, 'type' => 'spacer'];
$laporan[] = ['label' => 'C. Kas Keluar', 'rekening' => '', 'jumlah' => null, 'type' => 'header'];
$laporan[] = ['label' => '', 'rekening' => 'Rekening', 'jumlah' => 'Kas Keluar', 'type' => 'subheader'];

$sql_c = "SELECT detailjurnal.kd_rek, rekening.nm_rek,
                 (SUM(detailjurnal.debet) - SUM(detailjurnal.kredit)) AS ttldebet
          FROM jurnal
          INNER JOIN detailjurnal ON jurnal.no_jurnal = detailjurnal.no_jurnal
          INNER JOIN rekening ON detailjurnal.kd_rek = rekening.kd_rek
          WHERE rekening.tipe = 'R' AND rekening.balance = 'D'
            AND jurnal.tgl_jurnal BETWEEN ? AND ?
          GROUP BY detailjurnal.kd_rek
          ORDER BY detailjurnal.kd_rek";
$stmt_c = $koneksi->prepare($sql_c);
if ($stmt_c) {
    $stmt_c->bind_param('ss', $tgl1, $tgl2);
    $stmt_c->execute();
    $res_c = $stmt_c->get_result();
    $i = 1;
    while ($row_c = $res_c->fetch_assoc()) {
        $kd = $row_c['kd_rek'];
        $saldo_awal_rek = getSaldoAwalRekening($koneksi, $kd, $thn1, $thn2);
        $debkred = (float)$row_c['ttldebet'] + $saldo_awal_rek;
        $pengeluaran += $debkred;
        $laporan[] = [
            'label'    => '',
            'rekening' => $i . '. ' . htmlspecialchars($kd . ' ' . $row_c['nm_rek']),
            'jumlah'   => $debkred,
            'type'     => 'data'
        ];
        $i++;
    }
    $stmt_c->close();
}
$laporan[] = ['label' => '', 'rekening' => 'Jumlah Total Kas Keluar :', 'jumlah' => $pengeluaran, 'type' => 'subtotal'];

// =========================================================================
// TOTAL KAS: A + (B - C)
// Replika Java: tabMode.addRow(">>" Total Kas","A + (B-C)","")
// =========================================================================
$total_kas = $kasawal + $penerimaan - $pengeluaran;
$laporan[] = ['label' => '', 'rekening' => '', 'jumlah' => null, 'type' => 'spacer'];
$laporan[] = ['label' => '>> Total Kas', 'rekening' => 'A + ( B - C ) :', 'jumlah' => null, 'type' => 'header'];
$laporan[] = [
    'label'    => '',
    'rekening' => number_format($kasawal, 0, ',', '.') . ' + ( ' . number_format($penerimaan, 0, ',', '.') . ' - ' . number_format($pengeluaran, 0, ',', '.') . ' ) :',
    'jumlah'   => $total_kas,
    'type'     => 'grandtotal'
];

echo json_encode([
    'success'     => true,
    'laporan'     => $laporan,
    'kasawal'     => $kasawal,
    'penerimaan'  => $penerimaan,
    'pengeluaran' => $pengeluaran,
    'total_kas'   => $total_kas
]);
