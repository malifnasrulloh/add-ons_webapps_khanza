<?php
require_once 'koneksi.php';

$sql = "SELECT p.kode_brng, p.tanggal, p.jam, p.stok_awal, p.masuk, p.keluar, p.stok_akhir, p.posisi, p.status, p.kd_bangsal FROM riwayat_barang_medis p LIMIT 30";
try {
    $res = $pdo->query($sql)->fetchAll();
    foreach($res as $row) {
        echo $row['tanggal']." ".$row['jam']." | ".$row['posisi']." | Status: ".$row['status']." | Bangsal: ".$row['kd_bangsal']."\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
