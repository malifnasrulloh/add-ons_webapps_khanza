<?php
// api_stok_terakhir.php
header('Content-Type: application/json');
require_once 'auth.php'; // Proteksi session Zero-Trust
require_once 'koneksi.php';

$kd_bangsal = isset($_GET['depo']) ? $_GET['depo'] : 'AP';

// Mengambil data stok beserta nama barang, satuan, dan harga dasar
$sql = "SELECT 
            d.kode_brng, 
            d.nama_brng, 
            k.satuan, 
            d.dasar, 
            g.stok 
        FROM gudangbarang g
        INNER JOIN databarang d ON g.kode_brng = d.kode_brng
        INNER JOIN kodesatuan k ON d.kode_sat = k.kode_sat
        WHERE 
            g.kd_bangsal = ? 
            AND g.no_batch = '' 
            AND g.no_faktur = '' 
            AND d.status = '1'
        ORDER BY d.nama_brng ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$kd_bangsal]);
    $result = $stmt->fetchAll();

    $data = [];
    foreach ($result as $row) {
        // Menghitung total aset
        $row['total_aset'] = (float)$row['stok'] * (float)$row['dasar'];
        $data[] = $row;
    }

    echo json_encode(['data' => $data]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database exception']);
}
?>
