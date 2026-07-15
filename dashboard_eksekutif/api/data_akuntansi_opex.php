<?php
require_once(dirname(__DIR__) . '/config/koneksi.php');
require_once('auth_guard.php');

header('Content-Type: application/json');

try {
    $tgl1 = $_GET['tgl1'] ?? date('Y-m-01');
    $tgl2 = $_GET['tgl2'] ?? date('Y-m-d');
    
    // Top 15 Expenses
    $stmt1 = $koneksi_pdo->prepare("
        SELECT 
            r.kd_rek, 
            r.nm_rek, 
            SUM(d.debet - d.kredit) as total_biaya
        FROM jurnal j
        JOIN detailjurnal d ON j.no_jurnal = d.no_jurnal
        JOIN rekening r ON d.kd_rek = r.kd_rek
        WHERE r.tipe = 'R' AND r.balance = 'D' 
          AND j.tgl_jurnal BETWEEN ? AND ?
        GROUP BY r.kd_rek, r.nm_rek
        HAVING total_biaya > 0
        ORDER BY total_biaya DESC
        LIMIT 15
    ");
    $stmt1->execute([$tgl1, $tgl2]);
    $top_expenses = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    
    // Grand Total All Expenses in period
    $stmt2 = $koneksi_pdo->prepare("
        SELECT SUM(d.debet - d.kredit) as grand_total
        FROM jurnal j
        JOIN detailjurnal d ON j.no_jurnal = d.no_jurnal
        JOIN rekening r ON d.kd_rek = r.kd_rek
        WHERE r.tipe = 'R' AND r.balance = 'D' 
          AND j.tgl_jurnal BETWEEN ? AND ?
    ");
    $stmt2->execute([$tgl1, $tgl2]);
    $row_total = $stmt2->fetch(PDO::FETCH_ASSOC);
    $grand_total = (float)($row_total['grand_total'] ?? 0);

    $labels = [];
    $data_bars = [];
    $data_cumulative = [];
    $cumulative_sum = 0;
    
    foreach ($top_expenses as $row) {
        // truncate label if too long for chart
        $lbl = strlen($row['nm_rek']) > 25 ? substr($row['nm_rek'], 0, 25) . '...' : $row['nm_rek'];
        $labels[] = $lbl;
        $val = (float)$row['total_biaya'];
        $data_bars[] = $val;
        
        $cumulative_sum += $val;
        $pct = $grand_total > 0 ? ($cumulative_sum / $grand_total) * 100 : 0;
        $data_cumulative[] = round($pct, 2);
    }

    echo json_encode([
        'success' => true,
        'grand_total' => $grand_total,
        'chart' => [
            'labels' => $labels,
            'bars' => $data_bars,
            'cumulative_pct' => $data_cumulative
        ],
        'table' => $top_expenses
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>