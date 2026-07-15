<?php
/**
 * api/data_akuntansi_dashboard.php  — v2
 * Mendukung parameter tgl1 & tgl2 agar KPI cards bisa drill-down dengan filter bebas.
 */
require_once(dirname(__DIR__) . '/config/koneksi.php');
require_once('auth_guard.php');

header('Content-Type: application/json');

try {
    // Terima filter tanggal dari request (default: bulan ini)
    $tgl1 = $_GET['tgl1'] ?? date('Y-m-01');
    $tgl2 = $_GET['tgl2'] ?? date('Y-m-d');

    $currentYear  = substr($tgl1, 0, 4);
    $currentMonth = substr($tgl1, 5, 2);

    // 1. Total Revenue & Expenses pada periode yang dipilih
    $stmt1 = $koneksi_pdo->prepare("
        SELECT 
            SUM(CASE WHEN r.tipe='R' AND r.balance='K' THEN d.kredit - d.debet ELSE 0 END) as total_revenue,
            SUM(CASE WHEN r.tipe='R' AND r.balance='D' THEN d.debet - d.kredit ELSE 0 END) as total_expenses
        FROM jurnal j
        JOIN detailjurnal d ON j.no_jurnal = d.no_jurnal
        JOIN rekening r ON d.kd_rek = r.kd_rek
        WHERE j.tgl_jurnal BETWEEN ? AND ?
    ");
    $stmt1->execute([$tgl1, $tgl2]);
    $kpi = $stmt1->fetch(PDO::FETCH_ASSOC);
    
    $revenue    = (float)($kpi['total_revenue']  ?? 0);
    $expenses   = (float)($kpi['total_expenses'] ?? 0);
    $net_profit = $revenue - $expenses;
    $profit_margin = $revenue > 0 ? ($net_profit / $revenue) * 100 : 0;

    // 2. Current Cash Balance — saldo berjalan s/d tgl2
    //    KAS (1110%) + BANK (1120%) — dari saldo awal tahun + mutasi YTD s/d tgl2
    $thnKas = substr($tgl2, 0, 4);
    $stmt2 = $koneksi_pdo->prepare("
        SELECT 
            r.kd_rek, r.nm_rek,
            COALESCE(rt.saldo_awal, 0) as saldo_awal_thn,
            SUM(CASE WHEN j.tgl_jurnal <= ? AND YEAR(j.tgl_jurnal) = ? THEN d.debet - d.kredit ELSE 0 END) as mutasi_ytd
        FROM rekening r
        LEFT JOIN rekeningtahun rt ON r.kd_rek = rt.kd_rek AND rt.thn = ?
        LEFT JOIN detailjurnal d ON r.kd_rek = d.kd_rek
        LEFT JOIN jurnal j ON d.no_jurnal = j.no_jurnal AND YEAR(j.tgl_jurnal) = ?
        WHERE (r.kd_rek LIKE '111%' OR r.kd_rek LIKE '112%') AND LENGTH(r.kd_rek) > 4
        GROUP BY r.kd_rek, r.nm_rek, rt.saldo_awal
    ");
    $stmt2->execute([$tgl2, $thnKas, $thnKas, $thnKas]);
    $kas_rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $current_cash = 0;
    $kas_detail   = [];
    foreach ($kas_rows as $r) {
        $saldo = (float)$r['saldo_awal_thn'] + (float)$r['mutasi_ytd'];
        $current_cash += $saldo;
        if (abs($saldo) > 0) {
            $kas_detail[] = ['nm_rek' => $r['nm_rek'], 'kd_rek' => $r['kd_rek'], 'saldo' => $saldo];
        }
    }

    // 3. Breakdown Revenue per kelompok (untuk drill-down)
    $stmt3 = $koneksi_pdo->prepare("
        SELECT r.kd_rek, r.nm_rek, SUM(d.kredit - d.debet) as subtotal
        FROM jurnal j
        JOIN detailjurnal d ON j.no_jurnal = d.no_jurnal
        JOIN rekening r ON d.kd_rek = r.kd_rek
        WHERE r.tipe='R' AND r.balance='K' AND j.tgl_jurnal BETWEEN ? AND ?
          AND LENGTH(r.kd_rek) > 3
        GROUP BY r.kd_rek, r.nm_rek
        HAVING subtotal > 0
        ORDER BY subtotal DESC
        LIMIT 15
    ");
    $stmt3->execute([$tgl1, $tgl2]);
    $revenue_breakdown = $stmt3->fetchAll(PDO::FETCH_ASSOC);

    // 4. Breakdown Expenses per kelompok (untuk drill-down)
    $stmt4 = $koneksi_pdo->prepare("
        SELECT r.kd_rek, r.nm_rek, SUM(d.debet - d.kredit) as subtotal
        FROM jurnal j
        JOIN detailjurnal d ON j.no_jurnal = d.no_jurnal
        JOIN rekening r ON d.kd_rek = r.kd_rek
        WHERE r.tipe='R' AND r.balance='D' AND j.tgl_jurnal BETWEEN ? AND ?
          AND LENGTH(r.kd_rek) > 3
        GROUP BY r.kd_rek, r.nm_rek
        HAVING subtotal > 0
        ORDER BY subtotal DESC
        LIMIT 15
    ");
    $stmt4->execute([$tgl1, $tgl2]);
    $expense_breakdown = $stmt4->fetchAll(PDO::FETCH_ASSOC);

    // 5. Chart Data (Last 12 Months dari tgl2)
    $stmt5 = $koneksi_pdo->prepare("
        SELECT 
            YEAR(j.tgl_jurnal) as thn, 
            MONTH(j.tgl_jurnal) as bln,
            SUM(CASE WHEN r.tipe='R' AND r.balance='K' THEN d.kredit - d.debet ELSE 0 END) as revenue,
            SUM(CASE WHEN r.tipe='R' AND r.balance='D' THEN d.debet - d.kredit ELSE 0 END) as expense
        FROM jurnal j
        JOIN detailjurnal d ON j.no_jurnal = d.no_jurnal
        JOIN rekening r ON d.kd_rek = r.kd_rek
        WHERE j.tgl_jurnal >= DATE_FORMAT(DATE_SUB(?, INTERVAL 11 MONTH), '%Y-%m-01')
          AND j.tgl_jurnal <= ?
        GROUP BY thn, bln
        ORDER BY thn ASC, bln ASC
    ");
    $stmt5->execute([$tgl2, $tgl2]);
    $chart_raw = $stmt5->fetchAll(PDO::FETCH_ASSOC);

    $months = [
        1=>'Jan', 2=>'Feb', 3=>'Mar', 4=>'Apr', 5=>'Mei', 6=>'Jun',
        7=>'Jul', 8=>'Ags', 9=>'Sep', 10=>'Okt', 11=>'Nov', 12=>'Des'
    ];
    
    $labels = []; $data_revenue = []; $data_expense = []; $data_profit = [];
    foreach ($chart_raw as $row) {
        $labels[]       = $months[(int)$row['bln']] . ' ' . substr($row['thn'], 2, 2);
        $rev            = (float)$row['revenue'];
        $exp            = (float)$row['expense'];
        $data_revenue[] = $rev;
        $data_expense[] = $exp;
        $data_profit[]  = $rev - $exp;
    }

    echo json_encode([
        'success' => true,
        'filter'  => ['tgl1' => $tgl1, 'tgl2' => $tgl2],
        'kpi' => [
            'revenue'       => $revenue,
            'expenses'      => $expenses,
            'net_profit'    => $net_profit,
            'profit_margin' => round($profit_margin, 2),
            'current_cash'  => $current_cash
        ],
        'breakdown' => [
            'revenue'   => $revenue_breakdown,
            'expenses'  => $expense_breakdown,
            'cash'      => $kas_detail
        ],
        'chart' => [
            'labels'  => $labels,
            'revenue' => $data_revenue,
            'expense' => $data_expense,
            'profit'  => $data_profit
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>