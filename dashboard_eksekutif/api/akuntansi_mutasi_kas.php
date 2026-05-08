<?php
/**
 * api/akuntansi_mutasi_kas.php
 * Backend AJAX untuk Monitoring Mutasi Kas Internal.
 *
 * KONSEP UTAMA:
 *  "Mutasi Kas Internal" adalah transaksi jurnal di mana KEDUA SISI
 *  (Debet dan Kredit) adalah akun yang termasuk dalam kelompok
 *  KAS atau BANK — artinya uang berpindah antar "kantong" internal RS.
 *
 * PRINSIP DINAMIS (Anti Hardcode kd_rek):
 *  - Kelompok KAS/BANK diidentifikasi dari hierarki tabel `subrekening`.
 *  - Logika: Akun GRUP = rekening yang punya anak di subrekening DAN
 *    nm_rek mengandung 'KAS' atau 'BANK' DAN tipe='N', balance='D'.
 *  - PENTING: Sistem Khanza mengizinkan booking ke node INTERMEDIATE
 *    (bukan hanya leaf). Contoh: KAS LACI (111020) punya anak (1110201,
 *    1110202) tapi juga dipakai langsung sebagai akun transaksi jurnal.
 *    Oleh karena itu kita ambil SEMUA keturunan (intermediate + leaf).
 *  - Hierarki ditangani 3 level karena MySQL 5.x tidak mendukung CTE rekursif.
 *
 * RESPONS JSON:
 *  - saldo_cards   : Saldo real-time per akun Kas/Bank aktif
 *  - flow_matrix   : Pasangan transfer (from → to) dengan total & count
 *  - chart_trend   : Tren total mutasi per bulan dalam periode
 *  - summary       : Grand total mutasi & jumlah transaksi
 *
 * SECURITY: PDO Prepared Statements ($koneksi_pdo), session check.
 */

require_once(dirname(__DIR__) . '/config/koneksi.php');
header('Content-Type: application/json; charset=utf-8');

// ─── Auth Check ───────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

// ─── Validasi Input ───────────────────────────────────────────────────────────
$tgl1 = isset($_GET['tgl1']) ? trim($_GET['tgl1']) : date('Y-01-01');
$tgl2 = isset($_GET['tgl2']) ? trim($_GET['tgl2']) : date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl1)) { $tgl1 = date('Y-01-01'); }
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl2)) { $tgl2 = date('Y-m-d'); }

$tahun = substr($tgl1, 0, 4);

// ─── Gunakan koneksi PDO dari koneksi.php ─────────────────────────────────────
$pdo = $koneksi_pdo;

// =========================================================================
// STEP 1: Identifikasi Akun Kas/Bank secara DINAMIS via hierarki subrekening
//
// KAIDAH: KAS & BANK = Aset yang paling likuid. Dalam COA standar:
//   ASET (1) > ASET LANCAR (11) > KAS (1110) / BANK (1120) > leaf-nodes
//
// Strategi tanpa hardcode kd_rek:
//   a) Ambil semua rekening GRUP (ada di subrekening.kd_rek) yang
//      tipe='N', balance='D', dan nm_rek LIKE '%KAS%' OR '%BANK%'
//   b) Traversal ke bawah hingga leaf (rekening yang tidak punya anak)
//   c) Leaf = akun aktual tempat nilai rupiah dicatat
// =========================================================================

/**
 * Ambil SEMUA kd_rek keturunan (intermediate + leaf) di bawah kd_rek induk.
 *
 * KENAPA BUKAN HANYA LEAF:
 *  Khanza mengizinkan akuntan melakukan booking ke node mana saja dalam
 *  hierarki, tidak selalu ke leaf. Contoh: KAS LACI (111020) punya anak
 *  (1110201, 1110202) tapi juga digunakan langsung sebagai akun di jurnal.
 *  Jika kita hanya ambil leaf, maka KAS LACI akan terlewat dan flow matrix
 *  akan kosong meskipun ada transaksi transfer.
 *
 * Mendukung hingga 4 level kedalaman hierarki.
 */
function getAllDescendants(PDO $pdo, string $kd_rek_induk): array {
    $all = []; // kumpulkan SEMUA node keturunan

    // Level 1: anak langsung dari induk
    $stmt = $pdo->prepare("SELECT kd_rek2 FROM subrekening WHERE kd_rek = ?");
    $stmt->execute([$kd_rek_induk]);
    $level1 = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    if (empty($level1)) {
        // Induk tidak punya anak → dia sendiri adalah satu-satunya node
        return [$kd_rek_induk];
    }

    foreach ($level1 as $kd_l1) {
        $all[] = $kd_l1; // tambahkan node level 1 (meski punya anak)

        // Level 2
        $stmt2 = $pdo->prepare("SELECT kd_rek2 FROM subrekening WHERE kd_rek = ?");
        $stmt2->execute([$kd_l1]);
        $level2 = $stmt2->fetchAll(PDO::FETCH_COLUMN, 0);

        foreach ($level2 as $kd_l2) {
            $all[] = $kd_l2; // tambahkan node level 2

            // Level 3
            $stmt3 = $pdo->prepare("SELECT kd_rek2 FROM subrekening WHERE kd_rek = ?");
            $stmt3->execute([$kd_l2]);
            $level3 = $stmt3->fetchAll(PDO::FETCH_COLUMN, 0);

            foreach ($level3 as $kd_l3) {
                $all[] = $kd_l3; // tambahkan node level 3

                // Level 4 (fallback)
                $stmt4 = $pdo->prepare("SELECT kd_rek2 FROM subrekening WHERE kd_rek = ?");
                $stmt4->execute([$kd_l3]);
                $level4 = $stmt4->fetchAll(PDO::FETCH_COLUMN, 0);
                foreach ($level4 as $kd_l4) {
                    $all[] = $kd_l4;
                }
            }
        }
    }
    return array_unique($all);
}

// Ambil semua GRUP rekening yang merupakan kelompok KAS atau BANK
// (rekening yang punya anak, bertipe Neraca/Debet, dan nama mengandung KAS/BANK)
$stmt_roots = $pdo->prepare(
    "SELECT DISTINCT r.kd_rek, r.nm_rek
     FROM rekening r
     INNER JOIN subrekening s ON s.kd_rek = r.kd_rek
     WHERE r.tipe = 'N'
       AND r.balance = 'D'
       AND (r.nm_rek LIKE '%KAS%' OR r.nm_rek LIKE '%BANK%')"
);
$stmt_roots->execute();
$kas_bank_roots = $stmt_roots->fetchAll(PDO::FETCH_ASSOC);

// Traversal: kumpulkan SEMUA node keturunan (intermediate + leaf) dari seluruh grup KAS/BANK
$all_kasbank_kd = [];
$leaf_to_root   = []; // peta: kd_rek → nama grup induk (untuk labeling card)
foreach ($kas_bank_roots as $root) {
    $descendants = getAllDescendants($pdo, $root['kd_rek']);
    foreach ($descendants as $kd) {
        $all_kasbank_kd[] = $kd;
        // Simpan mapping node → grup terdekat (lebih spesifik lebih baik)
        if (!isset($leaf_to_root[$kd])) {
            $leaf_to_root[$kd] = $root['nm_rek'];
        }
    }
}
$all_kasbank_kd = array_values(array_unique($all_kasbank_kd));

if (empty($all_kasbank_kd)) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada akun Kas/Bank yang ditemukan di hierarki COA.']);
    exit;
}

// Buat IN-clause placeholder (digunakan berulang)
$ph = implode(',', array_fill(0, count($all_kasbank_kd), '?'));

// =========================================================================
// STEP 2: Saldo Real-Time per Akun Kas/Bank (aktif saja)
//
// Saldo Berjalan = Saldo Awal Tahun + Total Debet − Total Kredit
// (Balance='D' = Debet Normal → bertambah saat Debet)
// =========================================================================
$sql_saldo = "
    SELECT
        r.kd_rek,
        r.nm_rek,
        COALESCE(rt.saldo_awal, 0)          AS saldo_awal,
        COALESCE(m.total_debet, 0)          AS total_debet,
        COALESCE(m.total_kredit, 0)         AS total_kredit,
        (COALESCE(rt.saldo_awal, 0)
         + COALESCE(m.total_debet, 0)
         - COALESCE(m.total_kredit, 0))     AS saldo_berjalan
    FROM rekening r
    LEFT JOIN rekeningtahun rt
           ON r.kd_rek = rt.kd_rek AND rt.thn = ?
    LEFT JOIN (
        SELECT dj.kd_rek,
               SUM(dj.debet)  AS total_debet,
               SUM(dj.kredit) AS total_kredit
        FROM detailjurnal dj
        INNER JOIN jurnal j ON j.no_jurnal = dj.no_jurnal
        WHERE dj.kd_rek IN ($ph)
          AND j.tgl_jurnal BETWEEN ? AND ?
        GROUP BY dj.kd_rek
    ) AS m ON r.kd_rek = m.kd_rek
    WHERE r.kd_rek IN ($ph)
    ORDER BY r.kd_rek
";
// Parameter: [tahun, ...leafs (inner), tgl1, tgl2, ...leafs (outer)]
$params_saldo = array_merge([$tahun], $all_kasbank_kd, [$tgl1, $tgl2], $all_kasbank_kd);
$stmt_saldo   = $pdo->prepare($sql_saldo);
$stmt_saldo->execute($params_saldo);

$saldo_cards = [];
foreach ($stmt_saldo->fetchAll() as $row) {
    // Tampilkan hanya akun yang ada aktivitas
    if ((float)$row['saldo_awal'] != 0
        || (float)$row['total_debet'] != 0
        || (float)$row['total_kredit'] != 0) {

        $saldo_cards[] = [
            'kd_rek'         => $row['kd_rek'],
            'nm_rek'         => $row['nm_rek'],
            'grup'           => $leaf_to_root[$row['kd_rek']] ?? 'Lainnya',
            'saldo_awal'     => (float)$row['saldo_awal'],
            'total_debet'    => (float)$row['total_debet'],
            'total_kredit'   => (float)$row['total_kredit'],
            'saldo_berjalan' => (float)$row['saldo_berjalan'],
        ];
    }
}

// =========================================================================
// STEP 3: Flow Matrix — Pasangan Transfer Internal (from → to)
//
// Identifikasi: Satu no_jurnal yang memiliki SETIDAKNYA SATU baris kredit
// dari akun KAS/BANK dan SETIDAKNYA SATU baris debet dari akun KAS/BANK
// yang berbeda → itulah perpindahan kas internal.
// =========================================================================
$sql_matrix = "
    SELECT
        r1.kd_rek AS from_kd,
        r1.nm_rek AS from_nm,
        r2.kd_rek AS to_kd,
        r2.nm_rek AS to_nm,
        COUNT(DISTINCT j.no_jurnal) AS jml_trx,
        SUM(d2.debet)               AS total_mutasi
    FROM jurnal j
    INNER JOIN detailjurnal d1 ON j.no_jurnal = d1.no_jurnal AND d1.kredit > 0
    INNER JOIN detailjurnal d2 ON j.no_jurnal = d2.no_jurnal AND d2.debet  > 0
    INNER JOIN rekening r1 ON d1.kd_rek = r1.kd_rek
    INNER JOIN rekening r2 ON d2.kd_rek = r2.kd_rek
    WHERE d1.kd_rek IN ($ph)
      AND d2.kd_rek IN ($ph)
      AND d1.kd_rek <> d2.kd_rek
      AND j.tgl_jurnal BETWEEN ? AND ?
    GROUP BY r1.kd_rek, r1.nm_rek, r2.kd_rek, r2.nm_rek
    ORDER BY total_mutasi DESC
";
$params_matrix = array_merge($all_kasbank_kd, $all_kasbank_kd, [$tgl1, $tgl2]);
$stmt_matrix   = $pdo->prepare($sql_matrix);
$stmt_matrix->execute($params_matrix);
$flow_matrix = $stmt_matrix->fetchAll();

foreach ($flow_matrix as &$fm) {
    $fm['jml_trx']      = (int)$fm['jml_trx'];
    $fm['total_mutasi'] = (float)$fm['total_mutasi'];
}
unset($fm);

// =========================================================================
// STEP 4: Tren Bulanan — Total Mutasi Internal per Bulan
// =========================================================================
$sql_trend = "
    SELECT
        DATE_FORMAT(j.tgl_jurnal, '%Y-%m') AS bulan,
        COUNT(DISTINCT j.no_jurnal)        AS jml_trx,
        SUM(d2.debet)                      AS total_mutasi
    FROM jurnal j
    INNER JOIN detailjurnal d1 ON j.no_jurnal = d1.no_jurnal AND d1.kredit > 0
    INNER JOIN detailjurnal d2 ON j.no_jurnal = d2.no_jurnal AND d2.debet  > 0
    INNER JOIN rekening r1 ON d1.kd_rek = r1.kd_rek
    INNER JOIN rekening r2 ON d2.kd_rek = r2.kd_rek
    WHERE d1.kd_rek IN ($ph)
      AND d2.kd_rek IN ($ph)
      AND d1.kd_rek <> d2.kd_rek
      AND j.tgl_jurnal BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(j.tgl_jurnal, '%Y-%m')
    ORDER BY bulan ASC
";
$params_trend = array_merge($all_kasbank_kd, $all_kasbank_kd, [$tgl1, $tgl2]);
$stmt_trend   = $pdo->prepare($sql_trend);
$stmt_trend->execute($params_trend);
$chart_trend = $stmt_trend->fetchAll();

foreach ($chart_trend as &$ct) {
    $ct['jml_trx']      = (int)$ct['jml_trx'];
    $ct['total_mutasi'] = (float)$ct['total_mutasi'];
}
unset($ct);

// =========================================================================
// STEP 5: Grand Summary
// =========================================================================
$grand_total_mutasi = 0;
$grand_total_trx    = 0;
foreach ($flow_matrix as $fm) {
    $grand_total_mutasi += $fm['total_mutasi'];
    $grand_total_trx    += $fm['jml_trx'];
}

echo json_encode([
    'success'            => true,
    'periode'            => ['tgl1' => $tgl1, 'tgl2' => $tgl2],
    'saldo_cards'        => $saldo_cards,
    'flow_matrix'        => $flow_matrix,
    'chart_trend'        => $chart_trend,
    'grand_total_mutasi' => $grand_total_mutasi,
    'grand_total_trx'    => (int)$grand_total_trx,
]);
