<?php
require_once(dirname(__DIR__) . '/config/koneksi.php');
require_once('auth_guard.php');

header('Content-Type: application/json');

try {
    $tgl1 = $_GET['tgl1'] ?? date('Y-m-01');
    $tgl2 = $_GET['tgl2'] ?? date('Y-m-d');
    $thn = substr($tgl2, 0, 4);

    $stmt = $koneksi_pdo->prepare("
        SELECT 
            r.kd_rek, r.nm_rek, r.balance,
            COALESCE(rt.saldo_awal, 0) as saldo_awal_thn,
            SUM(CASE WHEN j.tgl_jurnal < ? AND YEAR(j.tgl_jurnal) = ? THEN (CASE WHEN r.balance='D' THEN d.debet-d.kredit ELSE d.kredit-d.debet END) ELSE 0 END) as mut_sebelum,
            SUM(CASE WHEN j.tgl_jurnal >= ? AND j.tgl_jurnal <= ? THEN d.debet ELSE 0 END) as debet_per,
            SUM(CASE WHEN j.tgl_jurnal >= ? AND j.tgl_jurnal <= ? THEN d.kredit ELSE 0 END) as kredit_per,
            SUM(CASE WHEN j.tgl_jurnal >= ? AND j.tgl_jurnal <= ? THEN 1 ELSE 0 END) as act_count
        FROM rekening r
        LEFT JOIN rekeningtahun rt ON r.kd_rek = rt.kd_rek AND rt.thn = ?
        LEFT JOIN detailjurnal d ON r.kd_rek = d.kd_rek
        LEFT JOIN jurnal j ON d.no_jurnal = j.no_jurnal AND YEAR(j.tgl_jurnal) = ?
        GROUP BY r.kd_rek, r.nm_rek, r.balance, rt.saldo_awal
        HAVING (saldo_awal_thn != 0 OR mut_sebelum != 0 OR debet_per != 0 OR kredit_per != 0)
        ORDER BY r.kd_rek ASC
    ");
    $stmt->execute([$tgl1, $thn, $tgl1, $tgl2, $tgl1, $tgl2, $tgl1, $tgl2, $thn, $thn]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    $max_activity = 0;

    foreach ($rows as $row) {
        $sa_thn = (float)$row['saldo_awal_thn'];
        $mut_sebelum = (float)$row['mut_sebelum'];
        $saldo_awal_periode = $sa_thn + $mut_sebelum; 
        
        $debet_per = (float)$row['debet_per'];
        $kredit_per = (float)$row['kredit_per'];
        
        if ($row['balance'] == 'D') {
            $saldo_akhir = $saldo_awal_periode + $debet_per - $kredit_per;
        } else {
            $saldo_akhir = $saldo_awal_periode + $kredit_per - $debet_per;
        }

        $activity = (int)$row['act_count'];
        if ($activity > $max_activity) $max_activity = $activity;

        $data[] = [
            'kd_rek' => $row['kd_rek'],
            'nm_rek' => $row['nm_rek'],
            'balance' => $row['balance'],
            'saldo_awal' => $saldo_awal_periode,
            'debet' => $debet_per,
            'kredit' => $kredit_per,
            'saldo_akhir' => $saldo_akhir,
            'activity_count' => $activity
        ];
    }

    echo json_encode([
        'success' => true,
        'max_activity' => $max_activity > 0 ? $max_activity : 1, // prevent div by zero
        'data' => $data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>