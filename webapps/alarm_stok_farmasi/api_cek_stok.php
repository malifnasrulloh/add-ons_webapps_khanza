<?php
// api_cek_stok.php
header('Content-Type: application/json');
require_once 'auth.php'; // Proteksi session Zero-Trust
require_once 'koneksi.php';

$kd_bangsal = isset($_GET['depo']) ? $_GET['depo'] : 'AP';

$sql = "SELECT 
            d.kode_brng, 
            d.nama_brng, 
            k.satuan, 
            d.stokminimal, 
            g.stok 
        FROM gudangbarang g
        INNER JOIN databarang d ON g.kode_brng = d.kode_brng
        INNER JOIN kodesatuan k ON d.kode_sat = k.kode_sat
        WHERE 
            g.kd_bangsal = ? 
            AND g.no_batch = '' 
            AND g.no_faktur = '' 
            AND d.status = '1'
            AND g.stok <= d.stokminimal
            AND NOT (d.stokminimal = 0 AND g.stok = 0)
        ORDER BY g.stok ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$kd_bangsal]);
    $data = $stmt->fetchAll();

    echo json_encode([
        'jumlah_warning' => count($data),
        'data' => $data
    ]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error']);
}
?>