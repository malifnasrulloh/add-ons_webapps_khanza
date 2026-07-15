<?php
// api_riwayat_obat.php
header('Content-Type: application/json');
require_once 'auth.php'; // Proteksi session Zero-Trust
require_once 'koneksi.php';

$kode_brng = isset($_GET['kode_brng']) ? $_GET['kode_brng'] : '';
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$kd_bangsal = isset($_GET['kd_bangsal']) ? $_GET['kd_bangsal'] : '';

if (empty($kode_brng)) {
    echo json_encode(['data' => []]); 
    exit;
}

// Bangun Query Dinamis
$sql = "
    SELECT 
        r.tanggal,
        r.jam,
        r.stok_awal,
        r.masuk,
        r.keluar,
        r.stok_akhir,
        r.posisi,
        r.no_faktur,
        r.keterangan,
        b.nm_bangsal,
        r.petugas
    FROM riwayat_barang_medis r
    LEFT JOIN bangsal b ON r.kd_bangsal = b.kd_bangsal
    WHERE 
        r.kode_brng = ? 
        AND r.tanggal BETWEEN ? AND ?
        AND r.status = 'Simpan'
";

// Siapkan parameter binding
$params = [$kode_brng, $tgl_awal, $tgl_akhir];

// Tambahkan filter bangsal jika ada
if (!empty($kd_bangsal)) {
    $sql .= " AND r.kd_bangsal = ? ";
    $params[] = $kd_bangsal;
}

$sql .= " ORDER BY r.tanggal DESC, r.jam DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchAll();
    
    $data = [];
    $seen = [];
    foreach ($result as $row) {
        // Buat unique key untuk mencegah baris duplikat yang sama persis (karena masalah JOIN)
        $key = $row['tanggal'] . '_' . $row['jam'] . '_' . $row['no_faktur'] . '_' . $row['posisi'] . '_' . $row['keterangan'] . '_' . $row['nm_bangsal'];
        
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $data[] = $row;
        }
    }
    
    echo json_encode(['data' => $data]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database exception']);
}
?>
