<?php
require_once(dirname(__DIR__) . '/config/koneksi.php');
require_once('auth_guard.php');

header('Content-Type: application/json');

/**
 * Hitung saldo rekening neraca (Aset/Liabilitas/Ekuitas).
 * 
 * RUMUS: Saldo = saldo_awal_tahun + mutasi_sepanjang_tahun_sd_tgl2
 * 
 * Dua query terpisah untuk menghindari join-multiplication:
 *   - Query 1: ambil saldo_awal dari rekeningtahun (1 baris per rekening)
 *   - Query 2: hitung mutasi dari jurnal dalam periode yang benar
 */
function getSaldoGroup($koneksi_pdo, $thn, $tgl2, $prefix, $balance_normal) {
    $startOfYear = $thn . '-01-01';

    // Query 1: Saldo awal tahun
    $s1 = $koneksi_pdo->prepare("
        SELECT COALESCE(SUM(rt.saldo_awal), 0) AS sa
        FROM rekening r
        JOIN rekeningtahun rt ON r.kd_rek = rt.kd_rek AND rt.thn = ?
        WHERE r.kd_rek LIKE ?
    ");
    $s1->execute([$thn, $prefix . '%']);
    $sa = (float)($s1->fetchColumn() ?? 0);

    // Query 2: Mutasi dalam tahun berjalan s/d tgl2
    // INNER JOIN memastikan hanya transaksi dalam rentang yang dihitung.
    $s2 = $koneksi_pdo->prepare("
        SELECT COALESCE(SUM(
            CASE WHEN r.balance = 'D' THEN d.debet - d.kredit
                 ELSE d.kredit - d.debet END
        ), 0) AS mutasi
        FROM jurnal j
        INNER JOIN detailjurnal d ON j.no_jurnal = d.no_jurnal
        INNER JOIN rekening r     ON d.kd_rek    = r.kd_rek
        WHERE j.tgl_jurnal >= ? AND j.tgl_jurnal <= ?
          AND r.kd_rek LIKE ?
    ");
    $s2->execute([$startOfYear, $tgl2, $prefix . '%']);
    $mutasi = (float)($s2->fetchColumn() ?? 0);

    return $sa + $mutasi;
}

try {
    $tgl1 = $_GET['tgl1'] ?? date('Y-m-01');
    $tgl2 = $_GET['tgl2'] ?? date('Y-m-d');
    $thn  = substr($tgl2, 0, 4);

    // ── Neraca (posisi s/d tgl2) ──────────────────────────────────────────
    $aset_lancar      = getSaldoGroup($koneksi_pdo, $thn, $tgl2, '11', 'D');
    $kewajiban_lancar = getSaldoGroup($koneksi_pdo, $thn, $tgl2, '21', 'K');
    $total_kewajiban  = getSaldoGroup($koneksi_pdo, $thn, $tgl2, '2',  'K');
    $ekuitas          = getSaldoGroup($koneksi_pdo, $thn, $tgl2, '3',  'K');
    $total_aset       = getSaldoGroup($koneksi_pdo, $thn, $tgl2, '1',  'D');

    // ── Laba Rugi periode (tgl1 s/d tgl2) ────────────────────────────────
    $stmt_lr = $koneksi_pdo->prepare("
        SELECT
            SUM(CASE WHEN r.tipe='R' AND r.balance='K' THEN d.kredit - d.debet ELSE 0 END) AS rev,
            SUM(CASE WHEN r.tipe='R' AND r.balance='D' THEN d.debet - d.kredit ELSE 0 END) AS exp
        FROM jurnal j
        INNER JOIN detailjurnal d ON j.no_jurnal = d.no_jurnal
        INNER JOIN rekening r     ON d.kd_rek    = r.kd_rek
        WHERE j.tgl_jurnal >= ? AND j.tgl_jurnal <= ?
    ");
    $stmt_lr->execute([$tgl1, $tgl2]);
    $lr = $stmt_lr->fetch(PDO::FETCH_ASSOC);

    $rev_per        = (float)($lr['rev'] ?? 0);
    $exp_per        = (float)($lr['exp'] ?? 0);
    $net_profit_per = $rev_per - $exp_per;

    // ── Rasio ─────────────────────────────────────────────────────────────
    $current_ratio      = ($kewajiban_lancar > 0) ? ($aset_lancar / $kewajiban_lancar) : 0;
    $debt_to_equity     = ($ekuitas          > 0) ? ($total_kewajiban / $ekuitas)       : 0;
    $net_profit_margin  = ($rev_per          > 0) ? ($net_profit_per  / $rev_per * 100)  : 0;
    $roa                = ($total_aset       > 0) ? ($net_profit_per  / $total_aset * 100): 0;
    $roe                = ($ekuitas          > 0) ? ($net_profit_per  / $ekuitas * 100)   : 0;

    echo json_encode([
        'success' => true,
        'data' => [
            'aset_lancar'      => $aset_lancar,
            'kewajiban_lancar' => $kewajiban_lancar,
            'total_kewajiban'  => $total_kewajiban,
            'total_ekuitas'    => $ekuitas,
            'revenue'          => $rev_per,
            'expense'          => $exp_per,
            'net_profit'       => $net_profit_per,
            'current_ratio'    => $current_ratio,
            'der'              => $debt_to_equity,
            'roe'              => $roe,
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>