<?php
/**
 * api/akuntansi_rekening_tahun_api.php
 * CRUD API untuk tabel rekeningtahun.
 *
 * Replika 100% logika DlgRekeningTahun.java:
 *   - GET (list)    : tampil() → JOIN rekening+rekeningtahun, hitung mutasi+saldo_akhir
 *   - POST (insert) : BtnSimpan → INSERT INTO rekeningtahun
 *   - PUT  (update) : BtnEdit   → UPDATE rekeningtahun SET saldo_awal WHERE thn+kd_rek
 *   - DELETE        : BtnHapus  → DELETE FROM rekeningtahun WHERE thn+kd_rek
 *
 * SECURITY: MySQLi Prepared Statements, session check, CSRF-safe via JSON body.
 */
require_once(dirname(__DIR__) . '/config/koneksi.php');
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

/* ─────────────────────────────────────────
 * GET — List / Search
 * Query: SELECT ... JOIN rekening WHERE thn=? AND (kd_rek/nm_rek/tipe/balance LIKE ?)
 * Saldo akhir dihitung: saldo_awal + mutasi jurnal (replika tampil() Java)
 * ───────────────────────────────────────── */
if ($method === 'GET') {
    $thn  = isset($_GET['thn'])  ? trim($_GET['thn'])  : date('Y');
    $cari = isset($_GET['cari']) ? trim($_GET['cari']) : '';

    if (!preg_match('/^\d{4}$/', $thn)) { $thn = date('Y'); }

    $sql = "SELECT rt.thn, r.kd_rek, r.nm_rek, r.tipe, r.balance, rt.saldo_awal
            FROM rekening r
            INNER JOIN rekeningtahun rt ON rt.kd_rek = r.kd_rek
            WHERE rt.thn = ?"
         . ($cari !== '' ? " AND (r.kd_rek LIKE ? OR r.nm_rek LIKE ? OR r.tipe LIKE ? OR r.balance LIKE ?)" : "")
         . " ORDER BY r.kd_rek";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $koneksi->error]);
        exit;
    }
    if ($cari !== '') {
        $like = '%' . $cari . '%';
        $stmt->bind_param('sssss', $thn, $like, $like, $like, $like);
    } else {
        $stmt->bind_param('s', $thn);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        // Hitung mutasi debet & kredit (replika ps2 dalam tampil() Java)
        $kd_rek = $row['kd_rek'];
        $stmt2  = $koneksi->prepare(
            "SELECT IFNULL(SUM(d.debet),0) AS md, IFNULL(SUM(d.kredit),0) AS mk
             FROM jurnal j INNER JOIN detailjurnal d ON d.no_jurnal=j.no_jurnal
             WHERE d.kd_rek=? AND j.tgl_jurnal LIKE ?"
        );
        $like_thn = '%' . $thn . '%';
        $stmt2->bind_param('ss', $kd_rek, $like_thn);
        $stmt2->execute();
        $r2 = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        $md = (float)($r2['md'] ?? 0);
        $mk = (float)($r2['mk'] ?? 0);

        // Replika Java switch(balance): D→debet-kredit, K→kredit-debet
        $mutasi   = ($row['balance'] === 'K') ? ($mk - $md) : ($md - $mk);
        $saldo_ak = (float)$row['saldo_awal'] + $mutasi;

        $rows[] = [
            'thn'        => (string)$row['thn'],
            'kd_rek'     => htmlspecialchars($row['kd_rek']),
            'nm_rek'     => htmlspecialchars($row['nm_rek']),
            'tipe'       => htmlspecialchars($row['tipe']),
            'balance'    => htmlspecialchars($row['balance']),
            'saldo_awal' => (float)$row['saldo_awal'],
            'md'         => ($row['balance'] === 'K') ? $mk : $md,  // mutasi normal
            'mk'         => ($row['balance'] === 'K') ? $md : $mk,
            'saldo_akhir'=> $saldo_ak,
        ];
    }
    $stmt->close();

    echo json_encode(['success' => true, 'rows' => $rows, 'total' => count($rows)]);
    exit;
}

/* Untuk POST / PUT / DELETE: baca body JSON */
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) { $body = $_POST; } // fallback form-data

/* ─────────────────────────────────────────
 * POST — Insert (BtnSimpan)
 * INSERT INTO rekeningtahun (thn, kd_rek, saldo_awal) VALUES (?,?,?)
 * Validasi: kd_rek & thn wajib, saldo_awal numeric
 * ───────────────────────────────────────── */
if ($method === 'POST') {
    $thn        = trim($body['thn']        ?? '');
    $kd_rek     = trim($body['kd_rek']     ?? '');
    $saldo_awal = trim($body['saldo_awal'] ?? '0');

    if (empty($thn) || !preg_match('/^\d{4}$/', $thn)) {
        echo json_encode(['success' => false, 'message' => 'Tahun tidak valid.']); exit;
    }
    if (empty($kd_rek)) {
        echo json_encode(['success' => false, 'message' => 'Kode rekening wajib diisi.']); exit;
    }
    if (!is_numeric($saldo_awal)) {
        echo json_encode(['success' => false, 'message' => 'Saldo awal harus berupa angka.']); exit;
    }
    $saldo_awal = (float)$saldo_awal;

    // Cek apakah rekening valid
    $stmt_chk = $koneksi->prepare("SELECT kd_rek FROM rekening WHERE kd_rek=? LIMIT 1");
    $stmt_chk->bind_param('s', $kd_rek);
    $stmt_chk->execute();
    if (!$stmt_chk->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'Kode rekening tidak ditemukan.']); exit;
    }
    $stmt_chk->close();

    // INSERT IGNORE → jangan error jika sudah ada (PRIMARY KEY: thn+kd_rek)
    $stmt = $koneksi->prepare("INSERT INTO rekeningtahun (thn, kd_rek, saldo_awal) VALUES (?, ?, ?)");
    if (!$stmt) { echo json_encode(['success' => false, 'message' => 'DB Error.']); exit; }
    $stmt->bind_param('ssd', $thn, $kd_rek, $saldo_awal);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected <= 0) {
        echo json_encode(['success' => false, 'message' => 'Data sudah ada untuk rekening & tahun ini. Gunakan fitur Edit untuk mengubah saldo awal.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Rekening tahunan berhasil ditambahkan.']);
    }
    exit;
}

/* ─────────────────────────────────────────
 * PUT — Update (BtnEdit / Ganti)
 * UPDATE rekeningtahun SET saldo_awal=? WHERE thn=? AND kd_rek=?
 * ───────────────────────────────────────── */
if ($method === 'PUT') {
    $thn        = trim($body['thn']        ?? '');
    $kd_rek     = trim($body['kd_rek']     ?? '');
    $saldo_awal = trim($body['saldo_awal'] ?? '');

    if (empty($thn) || empty($kd_rek) || !is_numeric($saldo_awal)) {
        echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap atau tidak valid.']); exit;
    }
    $saldo_awal = (float)$saldo_awal;

    $stmt = $koneksi->prepare("UPDATE rekeningtahun SET saldo_awal=? WHERE thn=? AND kd_rek=?");
    if (!$stmt) { echo json_encode(['success' => false, 'message' => 'DB Error.']); exit; }
    $stmt->bind_param('dss', $saldo_awal, $thn, $kd_rek);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    echo json_encode([
        'success' => $affected >= 0,
        'message' => $affected >= 0 ? 'Saldo awal berhasil diperbarui.' : 'Data tidak ditemukan.',
    ]);
    exit;
}

/* ─────────────────────────────────────────
 * DELETE — Hapus (BtnHapus)
 * DELETE FROM rekeningtahun WHERE thn=? AND kd_rek=?
 * ───────────────────────────────────────── */
if ($method === 'DELETE') {
    $thn    = trim($body['thn']    ?? '');
    $kd_rek = trim($body['kd_rek'] ?? '');

    if (empty($thn) || empty($kd_rek)) {
        echo json_encode(['success' => false, 'message' => 'Tahun dan kode rekening wajib diisi.']); exit;
    }

    $stmt = $koneksi->prepare("DELETE FROM rekeningtahun WHERE thn=? AND kd_rek=?");
    if (!$stmt) { echo json_encode(['success' => false, 'message' => 'DB Error.']); exit; }
    $stmt->bind_param('ss', $thn, $kd_rek);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    echo json_encode([
        'success' => $affected > 0,
        'message' => $affected > 0 ? 'Data berhasil dihapus.' : 'Data tidak ditemukan.',
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method tidak dikenali.']);
