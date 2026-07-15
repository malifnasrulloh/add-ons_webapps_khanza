<?php
require_once 'koneksi.php';

$q = "SELECT kode_brng, COUNT(*) AS count 
        FROM riwayat_barang_medis 
        GROUP BY kode_brng 
        ORDER BY count DESC LIMIT 10";
try {
    $res = $pdo->query($q)->fetchAll();
    echo "Top 10 barang paling banyak riwayatnya:\n";
    print_r($res);
} catch (PDOException $e) {}

$q2 = "SELECT kd_bangsal, COUNT(*) AS count 
        FROM riwayat_barang_medis 
        GROUP BY kd_bangsal 
        ORDER BY count DESC LIMIT 10";
try {
    $res2 = $pdo->query($q2)->fetchAll();
    echo "\n\nTop 10 bangsal paling banyak riwayatnya:\n";
    print_r($res2);
} catch (PDOException $e) {}

$q3 = "SELECT min(tanggal), max(tanggal) FROM riwayat_barang_medis";
try {
    $res3 = $pdo->query($q3)->fetch();
    echo "\n\nRentang tanggal riwayat:\n";
    print_r($res3);
} catch (PDOException $e) {}
?>
