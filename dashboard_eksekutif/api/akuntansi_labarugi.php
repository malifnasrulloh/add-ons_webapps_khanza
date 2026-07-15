<?php
/**
 * api/akuntansi_labarugi.php  — VERSI 3 (AGGREGATE SUBTOTALS)
 *
 * PERUBAHAN UTAMA v3:
 *   - buildTreeAgg() menghitung subtotal AGREGAT bottom-up
 *     → setiap node parent menampilkan SUM dari seluruh descendant-nya
 *   - Tipe row baru: 'group-data' = ada anak (bisa di-collapse)
 *                    'sub-data'   = leaf node (tidak ada anak)
 *   - Grand total tetap akurat (SUM dari semua node di rekeningtahun)
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
$thn1 = substr($tgl1, 0, 4);
$thn2 = substr($tgl2, 0, 4);

/* ——————————————————————————————————————————
 * HELPER: Hitung saldo akhir 1 rekening (own account only)
 * —————————————————————————————————————————— */
function hitungSaldoAkhir($k, $kd_rek, $thn1, $thn2, $tgl1, $tgl2, $balance) {
    $stmt = $k->prepare(
        "SELECT IFNULL(SUM(saldo_awal),0) FROM rekeningtahun WHERE kd_rek=? AND thn BETWEEN ? AND ?"
    );
    if (!$stmt) return 0;
    $stmt->bind_param('sss', $kd_rek, $thn1, $thn2);
    $stmt->execute();
    $sa = (float)($stmt->get_result()->fetch_row()[0] ?? 0);
    $stmt->close();

    $sql = ($balance === 'K')
        ? "SELECT IFNULL(SUM(d.kredit)-SUM(d.debet),0) FROM jurnal j JOIN detailjurnal d ON d.no_jurnal=j.no_jurnal WHERE d.kd_rek=? AND j.tgl_jurnal BETWEEN ? AND ?"
        : "SELECT IFNULL(SUM(d.debet)-SUM(d.kredit),0) FROM jurnal j JOIN detailjurnal d ON d.no_jurnal=j.no_jurnal WHERE d.kd_rek=? AND j.tgl_jurnal BETWEEN ? AND ?";
    $stmt2 = $k->prepare($sql);
    if (!$stmt2) return $sa;
    $stmt2->bind_param('sss', $kd_rek, $tgl1, $tgl2);
    $stmt2->execute();
    $mut = (float)($stmt2->get_result()->fetch_row()[0] ?? 0);
    $stmt2->close();
    return $sa + $mut;
}

/* ——————————————————————————————————————————
 * TREE AGREGAT: setiap node MENAMPILKAN total seluruh subtree-nya.
 *
 * Returns: [rows_array, subtree_aggregate_total]
 *   subtree_aggregate_total = own_saldo + sum(all_descendants_saldo)
 *
 * 'group-data' = punya anak → bisa di-collapse, tampil subtotal
 * 'sub-data'   = daun (leaf) → tampil saldo sendiri
 * —————————————————————————————————————————— */
function buildTreeAgg($k, $parent_kd, $balance, $thn1, $thn2, $tgl1, $tgl2, $depth, $section_prefix) {
    $rows          = [];
    $subtree_total = 0;

    $stmt = $k->prepare(
        "SELECT r.kd_rek, r.nm_rek, r.balance
         FROM rekening r
         INNER JOIN subrekening s ON r.kd_rek = s.kd_rek2
         WHERE s.kd_rek = ? AND r.level = '1'
         ORDER BY r.kd_rek"
    );
    if (!$stmt) return [$rows, $subtree_total];
    $stmt->bind_param('s', $parent_kd);
    $stmt->execute();
    $children = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($children as $row) {
        $use_bal   = !empty($row['balance']) ? $row['balance'] : $balance;
        $own_saldo = hitungSaldoAkhir($k, $row['kd_rek'], $thn1, $thn2, $tgl1, $tgl2, $use_bal);
        $sid       = $section_prefix . '_' . $row['kd_rek'];

        // Rekursi: dapatkan grandchildren beserta total agregat mereka
        list($grandkid_rows, $grandkid_total) = buildTreeAgg($k, $row['kd_rek'], $use_bal, $thn1, $thn2, $tgl1, $tgl2, $depth + 1, $sid);

        $node_agg      = $own_saldo + $grandkid_total;
        $subtree_total += $node_agg;
        $has_children  = !empty($grandkid_rows);

        $rows[] = [
            'rek'          => htmlspecialchars($row['kd_rek'] . ' ' . $row['nm_rek']),
            'jumlah'       => $node_agg,        // ditampilkan sebagai subtotal kelompok ini
            'own_saldo'    => $own_saldo,        // saldo akun itu sendiri (informasi)
            'type'         => $has_children ? 'group-data' : 'sub-data',
            'depth'        => $depth,
            'section_id'   => $sid,
            'parent_id'    => $section_prefix,
            'has_children' => $has_children,
        ];

        $rows = array_merge($rows, $grandkid_rows);
    }

    return [$rows, $subtree_total];
}

/* ——————————————————————————————————————————
 * BUILD SECTION: header + level-0 accounts + tree children
 * Grand total = SUM of level-0 node aggregates (tidak double-count)
 * —————————————————————————————————————————— */
function buildSection($k, $tipe, $balance, $thn1, $thn2, $tgl1, $tgl2, $header, $section_prefix) {
    $rows  = [];
    $total = 0;

    $rows[] = [
        'rek'        => $header,
        'jumlah'     => null,
        'type'       => 'header',
        'section_id' => $section_prefix,
        'parent_id'  => null,
    ];
    $rows[] = [
        'rek'     => 'Nama Rekening',
        'jumlah'  => 'Saldo Akhir',
        'type'    => 'subheader',
    ];

    $stmt = $k->prepare(
        "SELECT kd_rek, nm_rek FROM rekening WHERE level='0' AND tipe=? AND balance=? ORDER BY kd_rek"
    );
    if (!$stmt) return [$rows, 0];
    $stmt->bind_param('ss', $tipe, $balance);
    $stmt->execute();
    $top_rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($top_rows as $row) {
        $own_saldo = hitungSaldoAkhir($k, $row['kd_rek'], $thn1, $thn2, $tgl1, $tgl2, $balance);
        $sid       = $section_prefix . '_' . $row['kd_rek'];

        list($children, $children_total) = buildTreeAgg($k, $row['kd_rek'], $balance, $thn1, $thn2, $tgl1, $tgl2, 1, $sid);

        $node_agg = $own_saldo + $children_total;
        $total   += $node_agg;

        $rows[] = [
            'rek'          => htmlspecialchars($row['kd_rek'] . ' ' . $row['nm_rek']),
            'jumlah'       => $node_agg,
            'own_saldo'    => $own_saldo,
            'type'         => !empty($children) ? 'group-data' : 'data',
            'depth'        => 0,
            'section_id'   => $sid,
            'parent_id'    => $section_prefix,
            'has_children' => !empty($children),
        ];

        $rows = array_merge($rows, $children);
    }

    return [$rows, $total];
}

// ═══════════════════════════════════════════════════════
// TAB 1 — LABA RUGI
// ═══════════════════════════════════════════════════════
$tab1 = [];
list($rp, $total_pendapatan) = buildSection($koneksi, 'R', 'K', $thn1, $thn2, $tgl1, $tgl2, 'Pendapatan :', 'LR_PEND');
$tab1 = array_merge($tab1, $rp);
$tab1[] = ['rek' => 'Total Pendapatan', 'jumlah' => $total_pendapatan, 'type' => 'subtotal', 'section_id' => 'LR_PEND'];
$tab1[] = ['rek' => '', 'jumlah' => null, 'type' => 'spacer'];

list($rb, $total_biaya) = buildSection($koneksi, 'R', 'D', $thn1, $thn2, $tgl1, $tgl2, 'Biaya-Biaya :', 'LR_BIAYA');
$tab1 = array_merge($tab1, $rb);
$tab1[] = ['rek' => 'Total Biaya-Biaya', 'jumlah' => $total_biaya, 'type' => 'subtotal', 'section_id' => 'LR_BIAYA'];
$tab1[] = ['rek' => '', 'jumlah' => null, 'type' => 'spacer'];

$laba_bersih = $total_pendapatan - $total_biaya;
$tab1[] = ['rek' => 'Laba Bersih : Total Pendapatan - Total Biaya-Biaya', 'jumlah' => $laba_bersih, 'type' => 'grandtotal'];

// ═══════════════════════════════════════════════════════
// TAB 2 — PERUBAHAN MODAL
// ═══════════════════════════════════════════════════════
$tab2 = [];
list($rm, $total_modal) = buildSection($koneksi, 'M', 'K', $thn1, $thn2, $tgl1, $tgl2, 'Modal Awal :', 'MOD_AWAL');
$tab2 = array_merge($tab2, $rm);
$tab2[] = ['rek' => 'Total Modal Awal', 'jumlah' => $total_modal, 'type' => 'subtotal', 'section_id' => 'MOD_AWAL'];
$tab2[] = ['rek' => '', 'jumlah' => null, 'type' => 'spacer'];
$tab2[] = ['rek' => 'Laba / Rugi Periode Ini :', 'jumlah' => $laba_bersih, 'type' => 'data', 'depth' => 0];
$tab2[] = ['rek' => '', 'jumlah' => null, 'type' => 'spacer'];
$modal_akhir = $total_modal + $laba_bersih;
$tab2[] = ['rek' => 'Modal Akhir = Modal Awal + Laba/Rugi', 'jumlah' => $modal_akhir, 'type' => 'grandtotal'];

// ═══════════════════════════════════════════════════════
// TAB 3 — NERACA (tipe='N')
// ═══════════════════════════════════════════════════════
$tab3 = [];
list($ra, $total_aktiva)    = buildSection($koneksi, 'N', 'D', $thn1, $thn2, $tgl1, $tgl2, 'Aktiva :', 'NER_AKT');
$tab3 = array_merge($tab3, $ra);
$tab3[] = ['rek' => 'Total Aktiva', 'jumlah' => $total_aktiva, 'type' => 'subtotal', 'section_id' => 'NER_AKT'];
$tab3[] = ['rek' => '', 'jumlah' => null, 'type' => 'spacer'];

list($rk, $total_kewajiban) = buildSection($koneksi, 'N', 'K', $thn1, $thn2, $tgl1, $tgl2, 'Kewajiban / Hutang :', 'NER_KWJ');
$tab3 = array_merge($tab3, $rk);
$tab3[] = ['rek' => 'Total Kewajiban', 'jumlah' => $total_kewajiban, 'type' => 'subtotal', 'section_id' => 'NER_KWJ'];
$tab3[] = ['rek' => '', 'jumlah' => null, 'type' => 'spacer'];

$tab3[] = ['rek' => 'Modal :', 'jumlah' => null, 'type' => 'header', 'section_id' => 'NER_MOD', 'parent_id' => null];
$tab3[] = ['rek' => 'Modal Akhir', 'jumlah' => $modal_akhir, 'type' => 'data', 'depth' => 0, 'section_id' => 'NER_MOD_VAL', 'parent_id' => 'NER_MOD'];
$tab3[] = ['rek' => '', 'jumlah' => null, 'type' => 'spacer'];

$total_pasiva = $total_kewajiban + $modal_akhir;
$tab3[] = ['rek' => 'Total Pasiva (Kewajiban + Modal)', 'jumlah' => $total_pasiva, 'type' => 'subtotal'];
$tab3[] = ['rek' => '', 'jumlah' => null, 'type' => 'spacer'];

$selisih = $total_aktiva - $total_pasiva;
$tab3[] = ['rek' => 'Aktiva - Pasiva (Idealnya = 0)', 'jumlah' => $selisih,
           'type' => ($selisih == 0 ? 'grandtotal' : 'error')];

echo json_encode([
    'success'          => true,
    'tab1'             => $tab1, 'tab2' => $tab2, 'tab3' => $tab3,
    'total_pendapatan' => $total_pendapatan, 'total_biaya'    => $total_biaya,
    'laba_bersih'      => $laba_bersih,      'total_modal'    => $total_modal,
    'modal_akhir'      => $modal_akhir,      'total_aktiva'   => $total_aktiva,
    'total_pasiva'     => $total_pasiva,     'selisih_neraca' => $selisih,
]);
