<?php
require_once(dirname(__DIR__) . '/config/koneksi.php');
require_once('auth_guard.php');

header('Content-Type: application/json');

function getFinanceData($koneksi_pdo, $y, $m, $upToDay = null) {
    $sql = "SELECT 
                SUM(CASE WHEN r.tipe='R' AND r.balance='K' THEN d.kredit - d.debet ELSE 0 END) as rev,
                SUM(CASE WHEN r.tipe='R' AND r.balance='D' THEN d.debet - d.kredit ELSE 0 END) as exp
            FROM jurnal j
            JOIN detailjurnal d ON j.no_jurnal = d.no_jurnal
            JOIN rekening r ON d.kd_rek = r.kd_rek
            WHERE YEAR(j.tgl_jurnal) = ? AND MONTH(j.tgl_jurnal) = ?";
    
    $params = [$y, $m];
    if ($upToDay) {
        $sql .= " AND DAY(j.tgl_jurnal) <= ?";
        $params[] = $upToDay;
    }

    $stmt = $koneksi_pdo->prepare($sql);
    $stmt->execute($params);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    $rev = (float)($res['rev'] ?? 0);
    $exp = (float)($res['exp'] ?? 0);
    return [
        'rev' => $rev,
        'exp' => $exp,
        'net' => $rev - $exp
    ];
}

try {
    $bln = (int)($_GET['bln'] ?? date('m'));
    $thn = (int)($_GET['thn'] ?? date('Y'));

    // Cek apakah bulan/tahun yang dipilih adalah bulan/tahun berjalan
    $isCurrentMonth = ($bln == (int)date('m') && $thn == (int)date('Y'));
    $upToDay = $isCurrentMonth ? (int)date('d') : null;

    // Current (CYCM)
    $cur = getFinanceData($koneksi_pdo, $thn, $bln); // Selalu ambil full data dari bulan terpilih

    // MoM (Previous Month) - Pakai Like-for-Like jika current month
    $mom_date = date('Y-m-d', strtotime("$thn-$bln-01 -1 month"));
    $mom_thn = date('Y', strtotime($mom_date));
    $mom_bln = date('m', strtotime($mom_date));
    $mom = getFinanceData($koneksi_pdo, $mom_thn, $mom_bln, $upToDay);

    // YoY (Same Month Last Year) - Pakai Like-for-Like jika current month
    $yoy_thn = $thn - 1;
    $yoy = getFinanceData($koneksi_pdo, $yoy_thn, $bln, $upToDay);

    function calcPct($curr, $prev) {
        if ($prev == 0) return $curr > 0 ? 100 : 0;
        return (($curr - $prev) / abs($prev)) * 100;
    }

    $mom_pct = [
        'rev' => calcPct($cur['rev'], $mom['rev']),
        'exp' => calcPct($cur['exp'], $mom['exp']),
        'net' => calcPct($cur['net'], $mom['net'])
    ];

    $yoy_pct = [
        'rev' => calcPct($cur['rev'], $yoy['rev']),
        'exp' => calcPct($cur['exp'], $yoy['exp']),
        'net' => calcPct($cur['net'], $yoy['net'])
    ];

    echo json_encode([
        'success' => true,
        'data' => [
            'curr' => $cur,
            'mom' => $mom,
            'yoy' => $yoy,
            'mom_pct' => [
                'rev' => round($mom_pct['rev'], 2),
                'exp' => round($mom_pct['exp'], 2),
                'net' => round($mom_pct['net'], 2)
            ],
            'yoy_pct' => [
                'rev' => round($yoy_pct['rev'], 2),
                'exp' => round($yoy_pct['exp'], 2),
                'net' => round($yoy_pct['net'], 2)
            ]
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>