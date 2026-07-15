<?php
/**
 * api/akuntansi_bubes.php
 * Backend AJAX untuk Buku Besar (Buku Besar / Ledger).
 *
 * Replika 100% logika dari KeuanganBubes.java (SIMRS Khanza).
 * Alur:
 *  1. Ambil saldo_awal & balance (D/K) dari rekeningtahun + rekening
 *  2. Hitung penyesuaian saldo awal (jika filter bulan/tanggal dipilih)
 *  3. Loop semua transaksi dalam rentang → hitung saldo bergulir
 * 
 * SECURITY: MySQLi Prepared Statements, session check.
 */

require_once(dirname(__DIR__) . '/config/koneksi.php');
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

// Parameter filter
$kd_rek  = isset($_GET['kd_rek'])  ? trim($_GET['kd_rek'])  : '';
$tahun   = isset($_GET['tahun'])   ? trim($_GET['tahun'])   : date('Y');
$bulan   = isset($_GET['bulan'])   ? trim($_GET['bulan'])   : ''; // '' = tidak difilter
$tanggal = isset($_GET['tanggal']) ? trim($_GET['tanggal']) : ''; // '' = tidak difilter

// Validasi tahun
if (!preg_match('/^\d{4}$/', $tahun)) { $tahun = date('Y'); }
if (!preg_match('/^\d{2}$/', $bulan)) { $bulan = ''; }
if (!preg_match('/^\d{2}$/', $tanggal)) { $tanggal = ''; }

if (empty($kd_rek)) {
    echo json_encode(['success' => false, 'message' => 'Kode rekening wajib diisi.']);
    exit;
}

// Parameter filter tambahan (untuk drill-down rentang bebas dari Cashflow/Keuangan)
$tgl1 = isset($_GET['tgl1']) ? trim($_GET['tgl1']) : '';
$tgl2 = isset($_GET['tgl2']) ? trim($_GET['tgl2']) : '';
$ignore_sa = isset($_GET['ignore_sa']) ? (int)$_GET['ignore_sa'] : 0;

// Jika tgl1 ada, ambil tahun dari tgl1 agar saldo_awal diambil dari tahun yang benar
if ($tgl1 !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl1)) {
    $tahun = substr($tgl1, 0, 4);
}

// =========================================================================
// STEP 1: Ambil data rekening & saldo awal dari rekeningtahun
// Replika Java: ps2 = "select rekeningtahun.saldo_awal, rekening.tipe, rekening.balance
//                       from rekeningtahun inner join rekening on rekeningtahun.kd_rek=rekening.kd_rek
//                       where rekeningtahun.kd_rek=? and rekeningtahun.thn like '%?%'"
// =========================================================================
$sql_info = "SELECT rekeningtahun.saldo_awal, rekening.tipe, rekening.balance, rekening.nm_rek
             FROM rekeningtahun
             INNER JOIN rekening ON rekeningtahun.kd_rek = rekening.kd_rek
             WHERE rekeningtahun.kd_rek = ? AND rekeningtahun.thn LIKE ?";
$stmt_info = $koneksi->prepare($sql_info);
if (!$stmt_info) {
    echo json_encode(['success' => false, 'message' => 'DB Error.']);
    exit;
}
$p_thn = '%' . $tahun . '%';
$stmt_info->bind_param('ss', $kd_rek, $p_thn);
$stmt_info->execute();
$r_info = $stmt_info->get_result()->fetch_assoc();
$stmt_info->close();

if (!$r_info) {
    // ─── AUTO-INSERT: belum ada rekeningtahun untuk rekening+tahun ini
    // Cek apakah rekening tersebut valid dulu
    $stmt_rek = $koneksi->prepare("SELECT nm_rek, tipe, balance FROM rekening WHERE kd_rek = ? LIMIT 1");
    if ($stmt_rek) {
        $stmt_rek->bind_param('s', $kd_rek);
        $stmt_rek->execute();
        $rek_data = $stmt_rek->get_result()->fetch_assoc();
        $stmt_rek->close();
    } else {
        $rek_data = null;
    }

    if (!$rek_data) {
        echo json_encode([
            'success'   => false,
            'message'   => 'Kode rekening ' . htmlspecialchars($kd_rek) . ' tidak ditemukan di master rekening.'
        ]);
        exit;
    }

    $stmt_ins = $koneksi->prepare("INSERT IGNORE INTO rekeningtahun (thn, kd_rek, saldo_awal) VALUES (?, ?, 0)");
    if ($stmt_ins) {
        $stmt_ins->bind_param('ss', $tahun, $kd_rek);
        $stmt_ins->execute();
        $stmt_ins->close();
    }

    $r_info = [
        'saldo_awal' => 0,
        'nm_rek'     => $rek_data['nm_rek'],
        'tipe'       => $rek_data['tipe'],
        'balance'    => $rek_data['balance'],
    ];
}


$saldo_awal    = (float) $r_info['saldo_awal'];

// Jika ignore_sa aktif, paksa saldo awal tahun jadi 0 (untuk akun pendapatan/beban dalam rentang murni)
if ($ignore_sa === 1) {
    $saldo_awal = 0;
}

$saldo_akhir   = $saldo_awal; // saldo bergulir dimulai dari saldo_awal
$balance       = $r_info['balance']; // 'D' atau 'K'
$nm_rek        = $r_info['nm_rek'];

// =========================================================================
// STEP 2 & 3: Penyesuaian saldo awal & Range Tanggal
// =========================================================================
$tgl_start_adj = $tahun . '-01-01';
$tgl_batas = '';

if ($tgl1 !== '' && $tgl2 !== '') {
    // MODE RENTANG BEBAS (Cashflow & Keuangan Drill-down)
    $tgl_batas = date('Y-m-d', strtotime($tgl1 . ' -1 day'));
    
    // Hitung penyesuaian dari 01-01 s/d tgl_batas HANYA jika ignore_sa = 0
    if ($ignore_sa === 0) {
        $sql_adj = "SELECT SUM(debet) as d, SUM(kredit) as k FROM detailjurnal 
                    JOIN jurnal ON detailjurnal.no_jurnal = jurnal.no_jurnal 
                    WHERE kd_rek = ? AND jurnal.tgl_jurnal BETWEEN ? AND ?";
        $stmt_adj = $koneksi->prepare($sql_adj);
        $tgl_awal_thn = $tahun . '-01-01';
        $stmt_adj->bind_param('sss', $kd_rek, $tgl_awal_thn, $tgl_batas);
        $stmt_adj->execute();
        $r_adj = $stmt_adj->get_result()->fetch_assoc();
        $stmt_adj->close();

        if ($balance === 'D') {
            $saldo_awal += ((float)$r_adj['d'] - (float)$r_adj['k']);
        } else {
            $saldo_awal += ((float)$r_adj['k'] - (float)$r_adj['d']);
        }
    }

    $sql_tgl_cond = "jurnal.tgl_jurnal BETWEEN ? AND ?";
    $p_tgl1 = $tgl1;
    $p_tgl2 = $tgl2;
} else {
    // MODE BUKU BESAR LAMA (Tahun, Bulan, Tanggal Hierarkis)
    if ($bulan !== '') {
        $bulan_int = intval($bulan) - 1;
        $bulan_before = ($bulan_int <= 9) ? '0' . $bulan_int : (string)$bulan_int;
        if ($tanggal !== '') {
            $tanggal_int = intval($tanggal) - 1;
            $tgl_before = ($tanggal_int <= 9) ? '0' . $tanggal_int : (string)$tanggal_int;
            $tgl_batas = $tahun . '-' . $bulan . '-' . $tgl_before;
        } else {
            $tgl_batas = $tahun . '-' . $bulan_before . '-31';
        }
    }
    
    if ($bulan === '') {
        $sql_tgl_cond = "jurnal.tgl_jurnal LIKE ?";
        $p_tgl1 = '%' . $tahun . '%';
    } elseif ($tanggal === '') {
        $sql_tgl_cond = "jurnal.tgl_jurnal LIKE ?";
        $p_tgl1 = '%' . $tahun . '-' . $bulan . '%';
    } else {
        $sql_tgl_cond = "jurnal.tgl_jurnal LIKE ?";
        $p_tgl1 = '%' . $tahun . '-' . $bulan . '-' . $tanggal . '%';
    }
}

// Lakukan penyesuaian jika ada tgl_batas yang valid
if ($tgl_batas !== '' && $tgl_batas >= $tgl_start_adj) {
    $sql_adj = "SELECT SUM(detailjurnal.debet) AS debet, SUM(detailjurnal.kredit) AS kredit
                FROM jurnal
                INNER JOIN detailjurnal ON detailjurnal.no_jurnal = jurnal.no_jurnal
                WHERE detailjurnal.kd_rek = ?
                  AND jurnal.tgl_jurnal BETWEEN ? AND ?";
    $stmt_adj = $koneksi->prepare($sql_adj);
    if ($stmt_adj) {
        $stmt_adj->bind_param('sss', $kd_rek, $tgl_start_adj, $tgl_batas);
        $stmt_adj->execute();
        $r_adj = $stmt_adj->get_result()->fetch_assoc();
        $stmt_adj->close();
        
        if ($r_adj) {
            $adj_debet  = (float) $r_adj['debet'];
            $adj_kredit = (float) $r_adj['kredit'];
            if ($balance === 'K') {
                $saldo_akhir += ($adj_kredit - $adj_debet);
                $saldo_awal  += ($adj_kredit - $adj_debet);
            } elseif ($balance === 'D') {
                $saldo_akhir += ($adj_debet - $adj_kredit);
                $saldo_awal  += ($adj_debet - $adj_kredit);
            }
        }
    }
}

// =========================================================================
// STEP 4: Query transaksi detail → hitung saldo bergulir
// =========================================================================
$sql_trx = "SELECT jurnal.tgl_jurnal, jurnal.jam_jurnal, jurnal.no_jurnal,
                   detailjurnal.debet, detailjurnal.kredit,
                   jurnal.no_bukti, jurnal.keterangan
            FROM jurnal
            INNER JOIN detailjurnal ON jurnal.no_jurnal = detailjurnal.no_jurnal
            WHERE detailjurnal.kd_rek = ? AND $sql_tgl_cond
            ORDER BY jurnal.tgl_jurnal ASC, jurnal.jam_jurnal ASC";

$stmt_trx = $koneksi->prepare($sql_trx);
if (!$stmt_trx) {
    echo json_encode(['success' => false, 'message' => 'DB Error query transaksi.']);
    exit;
}

// Bind dinamis tergantung jumlah parameter (LIKE butuh 1, BETWEEN butuh 2)
if (isset($p_tgl2)) {
    $stmt_trx->bind_param('sss', $kd_rek, $p_tgl1, $p_tgl2);
} else {
    $stmt_trx->bind_param('ss', $kd_rek, $p_tgl1);
}
$stmt_trx->execute();
$result_trx = $stmt_trx->get_result();

$rows    = [];
$saldo_berjalan = $saldo_akhir; // saldo berjalan dimulai dari saldo_akhir setelah penyesuaian

while ($row = $result_trx->fetch_assoc()) {
    $saldo_sebelum = $saldo_berjalan; // saldo_awal baris ini = saldo sebelum transaksi
    
    // Update saldo_akhir (saldo bergulir per baris)
    // Replika Java switch(balance): K → +(kredit-debet), D → +(debet-kredit)
    if ($balance === 'K') {
        $saldo_berjalan += ((float)$row['kredit'] - (float)$row['debet']);
    } else {
        $saldo_berjalan += ((float)$row['debet'] - (float)$row['kredit']);
    }
    
    // Format saldo (Java: df2.format() dengan abs jika negatif)
    $saldo_akhir_fmt = $saldo_berjalan;
    $saldo_awal_fmt  = $saldo_sebelum;
    
    $rows[] = [
        'tgl_jurnal'   => htmlspecialchars($row['tgl_jurnal'] . ' ' . $row['jam_jurnal']),
        'no_jurnal'    => htmlspecialchars($row['no_jurnal']),
        'no_bukti'     => htmlspecialchars($row['no_bukti']),
        'keterangan'   => htmlspecialchars($row['keterangan']),
        'saldo_awal'   => $saldo_awal_fmt,
        'debet'        => (float) $row['debet'],
        'kredit'       => (float) $row['kredit'],
        'saldo_akhir'  => $saldo_akhir_fmt,
        'type'         => 'data'
    ];
}
$stmt_trx->close();

echo json_encode([
    'success'      => true,
    'rows'         => $rows,
    'kd_rek'       => htmlspecialchars($kd_rek),
    'nm_rek'       => htmlspecialchars($nm_rek),
    'balance'      => $balance,
    'saldo_awal'   => $saldo_awal,
    'saldo_akhir'  => $saldo_berjalan,
    'row_count'    => count($rows)
]);
