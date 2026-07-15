<?php
/**
 * api/akuntansi_jurnal_detail.php
 * Drill-down detail jurnal — support 3 mode:
 *   ?no_jurnal=JR20260423000001          → detail 1 jurnal spesifik
 *   ?trace_jurnal=JR20260423000001       → semua jurnal yang punya no_jurnal yg sama (biasanya cuma 1, tapi berguna untuk audit)
 *   ?trace_bukti=2026/04/23/000006       → semua jurnal yang berkaitan dengan no_bukti (nomor rawat/registrasi)
 *
 * SECURITY: Prepared Statements, session check.
 */
require_once(dirname(__DIR__) . '/config/koneksi.php');
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

/* ──────────────────────────────────────────────────────────────
 * HELPER: cek apakah kolom ada di tabel
 * ────────────────────────────────────────────────────────────── */
function columnExists($db, $table, $column) {
    try {
        $r = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        return $r && $r->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/* ──────────────────────────────────────────────────────────────
 * HELPER: build SELECT kolom jurnal secara defensif
 * Kolom opsional (tidak wajib ada): jenis_jurnal, user_input, jam_input
 * ────────────────────────────────────────────────────────────── */
function buildJurnalSelect($db) {
    $optional = ['jenis_jurnal', 'user_input', 'jam_input'];
    $extras = [];
    foreach ($optional as $col) {
        if (columnExists($db, 'jurnal', $col)) {
            $extras[] = "j.$col";
        } else {
            $extras[] = "NULL AS $col";
        }
    }
    return "j.no_jurnal, j.tgl_jurnal, j.jam_jurnal, j.no_bukti, j.keterangan, " . implode(', ', $extras);
}

/* ──────────────────────────────────────────────────────────────
 * HELPER: build SELECT kolom detailjurnal secara defensif
 * Kolom opsional: keterangan
 * ────────────────────────────────────────────────────────────── */
function buildDetailSelect($db) {
    $col = columnExists($db, 'detailjurnal', 'keterangan') ? 'd.keterangan' : "NULL AS keterangan";
    return "d.no_jurnal, d.kd_rek, r.nm_rek, r.tipe, r.balance, d.debet, d.kredit, $col";
}

/* ──────────────────────────────────────────────────────────────
 * HELPER: format header jurnal dari row DB
 * ────────────────────────────────────────────────────────────── */
function formatHeader($row) {
    return [
        'no_jurnal'    => htmlspecialchars($row['no_jurnal'] ?? ''),
        'tgl_jurnal'   => htmlspecialchars($row['tgl_jurnal'] ?? ''),
        'jam_jurnal'   => htmlspecialchars($row['jam_jurnal'] ?? ''),
        'no_bukti'     => htmlspecialchars($row['no_bukti'] ?? '-'),
        'keterangan'   => htmlspecialchars($row['keterangan'] ?? '-'),
        'jenis_jurnal' => htmlspecialchars($row['jenis_jurnal'] ?? '-'),
        'user_input'   => htmlspecialchars($row['user_input'] ?? '-'),
        'jam_input'    => htmlspecialchars($row['jam_input'] ?? '-'),
    ];
}

/* ──────────────────────────────────────────────────────────────
 * HELPER: ambil semua baris detail untuk array no_jurnal
 * Returns grouped: [ no_jurnal => [header, detail[], ttl_d, ttl_k, balanced] ]
 * ────────────────────────────────────────────────────────────── */
function fetchJurnalGroups($db, array $no_jurnal_list) {
    if (empty($no_jurnal_list)) return [];

    try {
        $select_hdr = buildJurnalSelect($db);
        $in_placeholders = implode(',', array_fill(0, count($no_jurnal_list), '?'));

        /* Header info untuk setiap jurnal */
        $stmt_hdr = $db->prepare(
            "SELECT $select_hdr FROM jurnal j WHERE j.no_jurnal IN ($in_placeholders) ORDER BY j.tgl_jurnal, j.jam_jurnal, j.no_jurnal"
        );
        $stmt_hdr->execute($no_jurnal_list);
        $headers = $stmt_hdr->fetchAll(PDO::FETCH_ASSOC);

        /* Detail baris untuk semua jurnal sekaligus */
        $select_det = buildDetailSelect($db);
        $stmt_det = $db->prepare(
            "SELECT $select_det
             FROM detailjurnal d
             LEFT JOIN rekening r ON r.kd_rek = d.kd_rek
             WHERE d.no_jurnal IN ($in_placeholders)
             ORDER BY d.no_jurnal, d.kd_rek"
        );
        $stmt_det->execute($no_jurnal_list);
        $all_detail = $stmt_det->fetchAll(PDO::FETCH_ASSOC);

        /* Group detail per no_jurnal */
        $detail_map = [];
        foreach ($all_detail as $d) {
            $detail_map[$d['no_jurnal']][] = $d;
        }

        /* Assemble groups */
        $groups = [];
        foreach ($headers as $hrow) {
            $nj      = $hrow['no_jurnal'];
            $det     = $detail_map[$nj] ?? [];
            $ttl_d   = 0; $ttl_k = 0;
            $det_fmt = [];
            foreach ($det as $d) {
                $ttl_d += (float)$d['debet'];
                $ttl_k += (float)$d['kredit'];
                $det_fmt[] = [
                    'kd_rek'     => htmlspecialchars($d['kd_rek'] ?? ''),
                    'nm_rek'     => htmlspecialchars($d['nm_rek'] ?? '-'),
                    'tipe'       => htmlspecialchars($d['tipe'] ?? ''),
                    'balance'    => htmlspecialchars($d['balance'] ?? ''),
                    'debet'      => (float)$d['debet'],
                    'kredit'     => (float)$d['kredit'],
                    'keterangan' => htmlspecialchars($d['keterangan'] ?? ''),
                ];
            }
            $groups[] = [
                'header'   => formatHeader($hrow),
                'detail'   => $det_fmt,
                'ttl_debet'  => $ttl_d,
                'ttl_kredit' => $ttl_k,
                'balanced'   => (abs($ttl_d - $ttl_k) < 0.01),
                'entry_count'=> count($det_fmt),
            ];
        }
        return $groups;
    } catch (PDOException $e) {
        return [];
    }
}

try {
    /* ══════════════════════════════════════════════════════════════
     * MODE A: ?no_jurnal=xxx  → detail 1 jurnal (mode lama, backward-compat)
     * ══════════════════════════════════════════════════════════════ */
    if (!empty($_GET['no_jurnal'])) {
        $no_jurnal = trim($_GET['no_jurnal']);

        $groups = fetchJurnalGroups($koneksi_pdo, [$no_jurnal]);
        if (empty($groups)) {
            echo json_encode(['success' => false, 'message' => 'No jurnal tidak ditemukan: ' . htmlspecialchars($no_jurnal)]);
            exit;
        }

        $g = $groups[0];
        echo json_encode([
            'success'    => true,
            'mode'       => 'single',
            'header'     => $g['header'],
            'detail'     => $g['detail'],
            'ttl_debet'  => $g['ttl_debet'],
            'ttl_kredit' => $g['ttl_kredit'],
            'balanced'   => $g['balanced'],
        ]);
        exit;
    }

    /* ══════════════════════════════════════════════════════════════
     * MODE B: ?trace_bukti=xxx → semua jurnal berkaitan dengan no_bukti (nomor rawat/registrasi)
     * Berguna untuk menelusuri seluruh transaksi 1 pasien/kunjungan
     * ══════════════════════════════════════════════════════════════ */
    if (!empty($_GET['trace_bukti'])) {
        $no_bukti = trim($_GET['trace_bukti']);

        $stmt = $koneksi_pdo->prepare(
            "SELECT DISTINCT no_jurnal FROM jurnal WHERE no_bukti = :no_bukti ORDER BY tgl_jurnal, jam_jurnal, no_jurnal"
        );
        $stmt->execute([':no_bukti' => $no_bukti]);
        $nj_list = [];
        while ($r = $stmt->fetch(PDO::FETCH_NUM)) { $nj_list[] = $r[0]; }

        if (empty($nj_list)) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada jurnal untuk No. Bukti: ' . htmlspecialchars($no_bukti)]);
            exit;
        }

        $groups = fetchJurnalGroups($koneksi_pdo, $nj_list);

        /* Hitung grand total */
        $grand_d = 0; $grand_k = 0;
        foreach ($groups as $g) { $grand_d += $g['ttl_debet']; $grand_k += $g['ttl_kredit']; }

        echo json_encode([
            'success'      => true,
            'mode'         => 'trace_bukti',
            'no_bukti'     => htmlspecialchars($no_bukti),
            'jurnal_count' => count($groups),
            'groups'       => $groups,
            'grand_debet'  => $grand_d,
            'grand_kredit' => $grand_k,
            'grand_balanced' => (abs($grand_d - $grand_k) < 0.01),
        ]);
        exit;
    }

    /* ══════════════════════════════════════════════════════════════
     * MODE C: ?search_bukti=xxx  → cari no_bukti yang mengandung keyword
     * (autocomplete / suggestion untuk input trace)
     * ══════════════════════════════════════════════════════════════ */
    if (!empty($_GET['search_bukti'])) {
        $q = '%' . trim($_GET['search_bukti']) . '%';
        $stmt = $koneksi_pdo->prepare(
            "SELECT DISTINCT no_bukti, COUNT(*) AS jml_jurnal, MIN(tgl_jurnal) AS tgl_awal, MAX(tgl_jurnal) AS tgl_akhir
             FROM jurnal WHERE no_bukti LIKE :q GROUP BY no_bukti ORDER BY MAX(tgl_jurnal) DESC LIMIT 20"
        );
        $stmt->execute([':q' => $q]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'results' => array_map(function($r) {
            return [
                'no_bukti'   => htmlspecialchars($r['no_bukti']),
                'jml_jurnal' => (int)$r['jml_jurnal'],
                'tgl_awal'   => htmlspecialchars($r['tgl_awal']),
                'tgl_akhir'  => htmlspecialchars($r['tgl_akhir']),
            ];
        }, $rows)]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Parameter tidak valid. Gunakan ?no_jurnal=, ?trace_bukti=, atau ?search_bukti=']);
