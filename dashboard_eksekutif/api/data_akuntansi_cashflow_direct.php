<?php
require_once(dirname(__DIR__) . '/config/koneksi.php');
require_once('auth_guard.php');

header('Content-Type: application/json');

try {
    $tgl1 = $_GET['tgl1'] ?? date('Y-m-01');
    $tgl2 = $_GET['tgl2'] ?? date('Y-m-d');
    
    // Top Inflows
    $stmt1 = $koneksi_pdo->prepare("
        SELECT r.kd_rek, r.nm_rek as label, SUM(d_opp.kredit) as total
        FROM detailjurnal d_cash
        JOIN jurnal j ON d_cash.no_jurnal = j.no_jurnal
        JOIN detailjurnal d_opp ON j.no_jurnal = d_opp.no_jurnal
        JOIN rekening r ON d_opp.kd_rek = r.kd_rek
        WHERE d_cash.kd_rek LIKE '1110%' AND d_cash.debet > 0
          AND d_opp.kd_rek NOT LIKE '1110%' AND d_opp.kredit > 0
          AND j.tgl_jurnal BETWEEN ? AND ?
        GROUP BY r.kd_rek, r.nm_rek
        ORDER BY total DESC LIMIT 10
    ");
    $stmt1->execute([$tgl1, $tgl2]);
    $inflows = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // Top Outflows
    $stmt2 = $koneksi_pdo->prepare("
        SELECT r.kd_rek, r.nm_rek as label, SUM(d_opp.debet) as total
        FROM detailjurnal d_cash
        JOIN jurnal j ON d_cash.no_jurnal = j.no_jurnal
        JOIN detailjurnal d_opp ON j.no_jurnal = d_opp.no_jurnal
        JOIN rekening r ON d_opp.kd_rek = r.kd_rek
        WHERE d_cash.kd_rek LIKE '1110%' AND d_cash.kredit > 0
          AND d_opp.kd_rek NOT LIKE '1110%' AND d_opp.debet > 0
          AND j.tgl_jurnal BETWEEN ? AND ?
        GROUP BY r.kd_rek, r.nm_rek
        ORDER BY total DESC LIMIT 10
    ");
    $stmt2->execute([$tgl1, $tgl2]);
    $outflows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'inflows' => $inflows,
        'outflows' => $outflows
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>