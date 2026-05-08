<?php
require_once(dirname(__DIR__) . '/config/koneksi.php');
require_once('auth_guard.php');

header('Content-Type: application/json');

try {
    $tgl1           = $_GET['tgl1']    ?? date('Y-m-01');
    $tgl2           = $_GET['tgl2']    ?? date('Y-m-d');
    $kd_rek_prefix  = $_GET['kd_rek']  ?? '';
    $type           = $_GET['type']    ?? 'neraca';

    if (empty($kd_rek_prefix)) throw new Exception("Kode rekening tidak valid");

    $thn         = substr($tgl2, 0, 4);
    $startOfYear = $thn . '-01-01';
    $detail      = [];

    // =========================================================================
    // TIPE NERACA — saldo posisi s/d tgl2
    // Rumus: saldo_akhir = saldo_awal_tahun + mutasi_year_to_date
    //
    // Menggunakan dua langkah per rekening untuk menghindari join-multiplication:
    //   1. saldo_awal  : dari tabel rekeningtahun (1 baris per kd_rek per tahun)
    //   2. mutasi_ytd  : dari jurnal × detailjurnal (INNER JOIN, terfilter periode)
    // =========================================================================
    if ($type === 'neraca') {

        // Langkah 1 – ambil saldo awal tahun per rekening
        $stmtSA = $koneksi_pdo->prepare("
            SELECT r.kd_rek, r.nm_rek, r.balance,
                   COALESCE(rt.saldo_awal, 0) AS saldo_awal
            FROM rekening r
            LEFT JOIN rekeningtahun rt
                   ON rt.kd_rek = r.kd_rek AND rt.thn = ?
            WHERE r.kd_rek LIKE ? AND LENGTH(r.kd_rek) > 4
            ORDER BY r.kd_rek
        ");
        $stmtSA->execute([$thn, $kd_rek_prefix . '%']);
        $rekRows = $stmtSA->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rekRows)) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }

        // Buat map kd_rek → data
        $rekMap = [];
        foreach ($rekRows as $r) {
            $rekMap[$r['kd_rek']] = [
                'kd_rek'     => $r['kd_rek'],
                'nm_rek'     => $r['nm_rek'],
                'balance'    => $r['balance'],
                'saldo_awal' => (float)$r['saldo_awal'],
                'mutasi'     => 0.0,
            ];
        }

        // Langkah 2 – hitung mutasi YTD (1 Januari thn s/d tgl2)
        // INNER JOIN memastikan hanya transaksi dalam periode yang masuk.
        $stmtMut = $koneksi_pdo->prepare("
            SELECT d.kd_rek,
                   SUM(d.debet - d.kredit) AS gross_mut
            FROM jurnal j
            INNER JOIN detailjurnal d ON j.no_jurnal = d.no_jurnal
            WHERE j.tgl_jurnal >= ? AND j.tgl_jurnal <= ?
              AND d.kd_rek LIKE ?
            GROUP BY d.kd_rek
        ");
        $stmtMut->execute([$startOfYear, $tgl2, $kd_rek_prefix . '%']);
        while ($row = $stmtMut->fetch(PDO::FETCH_ASSOC)) {
            $kd = $row['kd_rek'];
            if (isset($rekMap[$kd])) {
                $rekMap[$kd]['mutasi'] = (float)$row['gross_mut'];
            }
        }

        // Langkah 3 – hitung saldo akhir dan susun output
        foreach ($rekMap as $kd => $r) {
            // gross_mut = debet - kredit (selalu)
            // Untuk akun D-normal: saldo naik saat debet  → pakai gross_mut apa adanya
            // Untuk akun K-normal: saldo naik saat kredit → balik tanda gross_mut
            if ($r['balance'] === 'D') {
                $saldo = $r['saldo_awal'] + $r['mutasi'];
            } else {
                // saldo_awal disimpan positif untuk K-normal, mutasi kredit-debet
                $saldo = $r['saldo_awal'] - $r['mutasi'];
            }

            if (abs($saldo) > 0.005) {
                $detail[] = [
                    'kd_rek' => $r['kd_rek'],
                    'nm_rek' => $r['nm_rek'],
                    'saldo'  => round($saldo, 2),
                ];
            }
        }

    // =========================================================================
    // TIPE LABARUGI — mutasi bersih rekening pendapatan/beban periode tgl1-tgl2
    //
    // Menggunakan INNER JOIN dari jurnal ke luar agar hanya transaksi dalam
    // rentang tanggal yang dihitung. LEFT JOIN menyebabkan seluruh histori
    // ikut ter-SUM (overflow).
    //
    // Catatan waktu: tgl_jurnal bertipe DATE, sehingga
    //   BETWEEN tgl1 AND tgl2 mencakup seluruh hari (00:00 s/d 23:59).
    // =========================================================================
    } else {

        $stmt = $koneksi_pdo->prepare("
            SELECT
                r.kd_rek,
                r.nm_rek,
                r.balance,
                SUM(CASE WHEN r.balance = 'D'
                         THEN d.debet  - d.kredit
                         ELSE d.kredit - d.debet END) AS mutasi
            FROM jurnal j
            INNER JOIN detailjurnal d ON j.no_jurnal = d.no_jurnal
            INNER JOIN rekening r     ON d.kd_rek    = r.kd_rek
            WHERE j.tgl_jurnal BETWEEN ? AND ?
              AND r.kd_rek LIKE ?
              AND LENGTH(r.kd_rek) > 4
              AND r.tipe = 'R'
            GROUP BY r.kd_rek, r.nm_rek, r.balance
            HAVING ABS(mutasi) > 0
            ORDER BY r.kd_rek ASC
        ");
        $stmt->execute([$tgl1, $tgl2, $kd_rek_prefix . '%']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $r) {
            $saldo = (float)$r['mutasi'];  // sudah ternormalisasi oleh CASE
            if (abs($saldo) > 0.005) {
                $detail[] = [
                    'kd_rek' => $r['kd_rek'],
                    'nm_rek' => $r['nm_rek'],
                    'saldo'  => round($saldo, 2),
                ];
            }
        }
    }

    echo json_encode(['success' => true, 'data' => $detail]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
